<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler;

use Core\View\Template\{ContentType,
    Exception\CompileException,
    Exception\SecurityViolationException,
    Exception\TemplateException,
    Extension,
    Support\Helpers
};
use Core\View\Template\Compiler\Nodes\{
    AreaNode,
    FragmentNode,
    NopNode,
    TemplateNode,
    TextNode,
};
use Core\View\Template\Interface\Policy;
use Core\View\Template\Runtime\Template;
use Generator;
use LogicException;
use WeakMap;
use stdClass;
use ReflectionException;

final class TemplateParser
{
    /** @var Block[][] */
    public array $blocks = [[]];

    public int $blockLayer = Template::LAYER_TOP;

    public bool $inHead = true;

    public bool $strict = false;

    public ?TextNode $lastIndentation = null;

    /** @var array<string, callable(Tag, self): (Generator|Node|void)> */
    private array $tagParsers = [];

    /** @var array<string, stdClass> */
    private array $attrParsersInfo = [];

    private TemplateParserHtml $html;

    private ?TokenStream $stream = null;

    private TemplateLexer $lexer;

    private ?Policy $policy = null;

    private ContentType $contentType;

    private int $counter = 0;

    private ?Tag $tag = null;

    /** @var callable */
    private $lastResolver;

    private WeakMap $lookFor;

    public function __construct()
    {
        $this->lexer = new TemplateLexer();
        $this->setContentType( ContentType::HTML );
    }

    /**
     * Parses tokens to nodes.
     *
     * @param string $template
     *
     * @return TemplateNode
     * @throws CompileException
     */
    public function parse( string $template ) : TemplateNode
    {
        $this->html    = new TemplateParserHtml( $this, $this->completeAttrParsers() );
        $this->stream  = new TokenStream( $this->lexer->tokenize( $template ) );
        $this->lookFor = new WeakMap();

        $headLength = 0;
        $findLength = function( FragmentNode $fragment ) use ( &$headLength ) {
            if ( $this->inHead && ! \end( $fragment->children ) instanceof TextNode ) {
                $headLength = \count( $fragment->children );
            }
        };

        $node              = new TemplateNode();
        $node->main        = $this->parseFragment( [$this->html, 'inTextResolve'], $findLength );
        $node->head        = new FragmentNode( \array_splice( $node->main->children, 0, $headLength ) );
        $node->contentType = $this->contentType;

        if ( ! $this->stream->peek()->isEnd() ) {
            $this->stream->throwUnexpectedException();
        }

        return $node;
    }

    public function parseFragment( callable $resolver, ?callable $after = null ) : FragmentNode
    {
        $res                = new FragmentNode();
        $save               = [$this->lastResolver, $this->tag];
        $this->lastResolver = $resolver;
        try {
            while ( ! $this->stream->peek()->isEnd() ) {
                if ( $node = $resolver( $res ) ) {
                    $res->append( $node );
                    $after && $after( $res );
                }
                else {
                    break;
                }
            }

            return $res;
        }
        finally {
            [$this->lastResolver, $this->tag] = $save;
        }
    }

    /**
     * @throws CompileException
     * @throws SecurityViolationException
     */
    public function inTextResolve() : ?Node
    {
        $token = $this->stream->peek();
        return match ( $token->type ) {
            Token::Text              => $this->parseText(),
            Token::Indentation       => $this->parseIndentation(),
            Token::Newline           => $this->parseNewline(),
            Token::Latte_TagOpen     => $this->parseLatteStatement(),
            Token::Latte_CommentOpen => $this->parseLatteComment(),
            default                  => null,
        };
    }

    /**
     * @throws CompileException
     */
    public function parseText() : TextNode
    {
        $token                 = $this->stream->consume( Token::Text, Token::Html_Name );
        $this->inHead          = $this->inHead && \trim( $token->text ) === '';
        $this->lastIndentation = null;
        return new TextNode( $token->text, $token->position );
    }

    /**
     * @throws CompileException
     */
    private function parseIndentation() : TextNode
    {
        $token                        = $this->stream->consume( Token::Indentation );
        return $this->lastIndentation = new TextNode( $token->text, $token->position );
    }

    /**
     * @throws CompileException
     */
    private function parseNewline() : AreaNode
    {
        $token = $this->stream->consume( Token::Newline );

        if ( $this->lastIndentation ) {
            $this->lastIndentation->content = '';
            $this->lastIndentation          = null;
            return new NopNode();
        }

        return new TextNode( $token->text, $token->position );
    }

    /**
     * @throws CompileException
     */
    public function parseLatteComment() : NopNode
    {
        if ( \str_ends_with( $this->stream->peek( -1 )?->text ?? "\n", "\n" ) ) {
            $this->lastIndentation ??= new TextNode( '' );
        }
        $openToken = $this->stream->consume( Token::Latte_CommentOpen );
        $this->lexer->pushState( TemplateLexer::StateLatteComment );
        $this->stream->consume( Token::Text );
        $this->stream->tryConsume( Token::Latte_CommentClose ) || $this->stream->throwUnexpectedException(
            [Token::Latte_CommentClose],
            addendum : " started {$openToken->position}",
        );
        $this->lexer->popState();
        return new NopNode();
    }

    /**
     * @param ?callable $resolver
     *
     * @return null|Node
     * @throws CompileException
     * @throws SecurityViolationException
     */
    public function parseLatteStatement( ?callable $resolver = null ) : ?Node
    {
        $this->lexer->pushState( TemplateLexer::StateLatteTag );
        if ( $this->stream->peek( 1 )->is( Token::Slash )
             || ( isset( $this->tag, $this->lookFor[$this->tag] ) && \in_array(
                 $this->stream->peek( 1 )->text,
                 $this->lookFor[$this->tag],
                 true,
             ) )
        ) {
            $this->lexer->popState();
            return null; // go back to previous parseLatteStatement()
        }
        $this->lexer->popState();

        $token    = $this->stream->peek();
        $startTag = $this->pushTag( $this->parseLatteTag() );

        $parser = $this->getTagParser( $startTag->name, $token->position );
        $res    = $parser( $startTag, $this );
        if ( $res instanceof Generator ) {
            if ( ! $res->valid() && ! $startTag->void ) {
                throw new LogicException(
                    "Incorrect behavior of {{$startTag->name}} parser, yield call is expected (on line {$startTag->position->line})",
                );
            }

            $this->ensureIsConsumed( $startTag );
            if ( $startTag->outputMode === $startTag::OutputKeepIndentation ) {
                $this->lastIndentation = null;
            }

            if ( $startTag->void ) {
                $res->send( [new FragmentNode(), $startTag] );
            }
            else {
                while ( $res->valid() ) {
                    $this->lookFor[$startTag] = $res->current() ?: null;
                    $content                  = $this->parseFragment( $resolver ?? $this->lastResolver );

                    if ( ! $this->stream->is( Token::Latte_TagOpen ) ) {
                        $this->checkEndTag( $startTag, null );
                        $res->send( [$content, null] );

                        break;
                    }

                    $tag = $this->parseLatteTag();

                    if ( $startTag->outputMode === $startTag::OutputKeepIndentation ) {
                        $this->lastIndentation = null;
                    }

                    if ( $tag->closing ) {
                        $this->checkEndTag( $startTag, $tag );
                        $res->send( [$content, $tag] );
                        $this->ensureIsConsumed( $tag );

                        break;
                    }
                    if ( \in_array( $tag->name, $this->lookFor[$startTag] ?? [], true ) ) {
                        $this->pushTag( $tag );
                        $res->send( [$content, $tag] );
                        $this->ensureIsConsumed( $tag );
                        $this->popTag();
                    }
                    else {
                        throw new CompileException(
                            'Unexpected tag '.\substr( $tag->getNotation( true ), 0, -1 ).'}',
                            $tag->position,
                        );
                    }
                }
            }

            if ( $res->valid() ) {
                throw new LogicException(
                    "Incorrect behavior of {{$startTag->name}} parser, more yield calls than expected (on line {$startTag->position->line})",
                );
            }

            $node = $res->getReturn();
        }
        elseif ( $startTag->void ) {
            throw new CompileException(
                'Unexpected /} in tag '.\substr( $startTag->getNotation( true ), 0, -1 ).'/}',
                $startTag->position,
            );
        }
        else {
            $this->ensureIsConsumed( $startTag );
            $node = $res;
            if ( $startTag->outputMode === $startTag::OutputKeepIndentation ) {
                $this->lastIndentation = null;
            }
        }

        if ( ! $node instanceof Node ) {
            throw new LogicException(
                "Incorrect behavior of {{$startTag->name}} parser, unexpected returned value (on line {$startTag->position->line})",
            );
        }

        $this->inHead = $this->inHead && $startTag->outputMode === $startTag::OutputNone;

        $this->popTag();

        $node->position = $startTag->position;
        return $node;
    }

    /**
     * @throws CompileException
     */
    private function parseLatteTag() : Tag
    {
        $stream = $this->stream;
        if ( \str_ends_with( $stream->peek( -1 )?->text ?? "\n", "\n" ) ) {
            $this->lastIndentation ??= new TextNode( '' );
        }

        $inTag = \in_array(
            $this->lexer->getState(),
            [
                TemplateLexer::StateHtmlTag,
                TemplateLexer::StateHtmlQuotedValue,
                TemplateLexer::StateHtmlComment,
                TemplateLexer::StateHtmlBogus,
            ],
            true,
        );
        $openToken = $stream->consume( Token::Latte_TagOpen );
        $this->lexer->pushState( TemplateLexer::StateLatteTag );
        $closing = (bool) $stream->tryConsume( Token::Slash );
        $void    = (bool) $stream->tryConsume( Token::Slash );
        $name    = $stream->tryConsume( Token::Latte_Name )?->text ?? ( $closing ? '' : '=' );
        $tag     = new Tag(
            name        : $name,
            tokens      : $this->consumeTag(),
            position    : $openToken->position,
            void        : $void,
            closing     : $closing,
            inHead      : $this->inHead,
            inTag       : $inTag,
            htmlElement : $this->html->getElement(),
        );
        $stream->tryConsume( Token::Latte_TagClose )
        || $stream->throwUnexpectedException(
            [Token::Latte_TagClose],
            addendum : " started {$openToken->position}",
        );
        $this->lexer->popState();
        return $tag;
    }

    /**
     * @throws CompileException
     */
    private function consumeTag() : array
    {
        $res = [];
        while ( $this->stream->peek()->isPhpKind() ) {
            $res[] = $this->stream->consume();
        }

        $res[] = new Token( Token::End, '', $this->stream->peek()->position );
        return $res;
    }

    /**
     * @param array<string, callable(Tag, self): (Generator|Node|void)|stdClass> $parsers
     *
     * @return TemplateParser
     */
    public function addTags( array $parsers ) : static
    {
        foreach ( $parsers as $name => $info ) {
            $info = $info instanceof stdClass ? $info : Extension::order( $info );
            if ( \str_starts_with( $name, TemplateLexer::NPrefix ) ) {
                $this->attrParsersInfo[\substr( $name, 2 )] = $info;
            }
            else {
                $this->tagParsers[$name] = $info->subject;
                try {
                    if ( $info->generator = Helpers::toReflection( $info->subject )->isGenerator() ) {
                        $this->attrParsersInfo[$name] = $info;
                    }
                }
                catch ( ReflectionException $e ) {
                    throw new TemplateException( $e->getMessage(), __METHOD__, previous : $e );
                }
            }
        }

        return $this;
    }

    /**
     * @param string   $name
     * @param Position $pos
     *
     * @return callable(Tag, self): (Generator|Node|void)
     * @throws CompileException
     * @throws SecurityViolationException
     */
    private function getTagParser( string $name, Position $pos ) : callable
    {
        if ( ! isset( $this->tagParsers[$name] ) ) {
            $hint = ( $t = Helpers::getSuggestion( \array_keys( $this->tagParsers ), $name ) )
                    ? ", did you mean {{$t}}?"
                    : '';
            if ( $this->html->getElement()?->isRawText() ) {
                $hint .= ' (in JavaScript or CSS, try to put a space after bracket or use n:syntax=off)';
            }
            throw new CompileException( "Unexpected tag {{$name}}{$hint}", $pos );
        }
        elseif ( ! $this->isTagAllowed( $name ) ) {
            throw new SecurityViolationException( "Tag {{$name}} is not allowed", $pos );
        }

        return $this->tagParsers[$name];
    }

    private function completeAttrParsers() : array
    {
        $list    = Helpers::sortBeforeAfter( $this->attrParsersInfo );
        $parsers = [];

        foreach ( $list as $name => $info ) {
            $parsers[$name] = $info->subject;
            if ( $info->generator ?? false ) {
                $parsers[Tag::PrefixInner.'-'.$name]
                        = $parsers[Tag::PrefixTag.'-'.$name] = $parsers[$name];
            }
        }

        return $parsers;
    }

    /**
     * @param Tag  $start
     * @param ?Tag $end
     *
     * @throws CompileException
     */
    private function checkEndTag( Tag $start, ?Tag $end ) : void
    {
        if ( ! $end ) {
            if ( $start->name !== 'syntax' && ( $start->name !== 'block' || $this->tag->parent ) ) { // TODO: hardcoded
                $this->stream->throwUnexpectedException( expected : ["{/{$start->name}}"] );
            }
        }
        elseif (
            ( $end->name !== $start->name && $end->name !== '' )
            || ! $end->closing
            || $end->void
        ) {
            throw new CompileException(
                "Unexpected {$end->getNotation()}, expecting {/{$start->name}}",
                $end->position,
            );
        }
    }

    /**
     * @param Tag $tag
     *
     * @throws CompileException
     */
    public function ensureIsConsumed( Tag $tag ) : void
    {
        if ( ! $tag->parser->isEnd() ) {
            $end = $tag->isNAttribute() ? ['end of attribute'] : ['end of tag'];
            $tag->parser->stream->throwUnexpectedException( $end, addendum : ' in '.$tag->getNotation() );
        }
    }

    /**
     * @param Block $block
     *
     * @throws CompileException
     */
    public function checkBlockIsUnique( Block $block ) : void
    {
        $name = \property_exists( $block->name, 'value' )
                ? (string) $block->name->value
                : $block->name::class;

        if ( $block->isDynamic() || ! \preg_match( '#^[a-z]#i', $name ) ) {
            throw new CompileException(
                \ucfirst( $block->tag->name )." name must start with letter a-z, '{$name}' given.",
                $block->tag->position,
            );
        }

        if ( $block->layer === Template::LAYER_SNIPPET
                ? isset( $this->blocks[$block->layer][$name] )
                : ( isset( $this->blocks[Template::LAYER_LOCAL][$name] ) || isset( $this->blocks[$this->blockLayer][$name] ) )
        ) {
            throw new CompileException( "Cannot redeclare {$block->tag->name} '{$name}'", $block->tag->position );
        }

        $this->blocks[$block->layer][$name] = $block;
    }

    public function setPolicy( ?Policy $policy ) : static
    {
        $this->policy = $policy;
        return $this;
    }

    public function setContentType( ContentType|string $type ) : static
    {
        $this->contentType = ContentType::by( $type );
        $this->lexer->setState(
            $type === ContentType::HTML || $type === ContentType::XML
                        ? TemplateLexer::StateHtmlText
                        : TemplateLexer::StatePlain,
        );
        return $this;
    }

    public function getContentType() : ContentType
    {
        return $this->contentType;
    }

    /**
     * @internal
     */
    public function getStream() : TokenStream
    {
        return $this->stream;
    }

    public function getLexer() : TemplateLexer
    {
        return $this->lexer;
    }

    public function peekTag() : ?Tag
    {
        return $this->tag;
    }

    public function pushTag( Tag $tag ) : Tag
    {
        $tag->parent = $this->tag;
        $this->tag   = $tag;
        return $tag;
    }

    public function popTag() : void
    {
        $this->tag = $this->tag->parent;
    }

    public function generateId() : int
    {
        return $this->counter++;
    }

    public function isTagAllowed( string $name ) : bool
    {
        return ! $this->policy || $this->policy->isTagAllowed( $name );
    }
}
