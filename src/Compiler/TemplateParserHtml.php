<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler;

use Core\View\Template\{ContentType, Exception\CompileException, Exception\SecurityViolationException, Support\Helpers};
use Core\View\Template\Compiler\Nodes\Html\CommentNode;
use Core\View\Template\Compiler\Nodes\PrintNode;
use Generator;
use Core\View\Template\Compiler\Nodes\{AreaNode,
    FragmentNode,
    Html\AttributeNode,
    Html\BogusTagNode,
    Html\ElementNode,
    NopNode,
    Php\Expression\BinaryOpNode,
    Php\Scalar\StringNode,
    TextNode
};

/**
 * Template parser extension for HTML.
 */
final class TemplateParserHtml
{
    /** @var array<string, callable(Tag, TemplateParser): (Generator|Node|void)> */
    private array // readonly
        $attrParsers;

    private ?ElementNode $element = null;

    private TemplateParser // readonly
        $parser;

    /** @var ?array{string, ?Nodes\Php\ExpressionNode} */
    private ?array $endName = null;

    public function __construct( TemplateParser $parser, array $attrParsers )
    {
        $this->parser      = $parser;
        $this->attrParsers = $attrParsers;
    }

    public function getElement() : ?ElementNode
    {
        return $this->element;
    }

    /**
     * @throws CompileException|SecurityViolationException
     */
    public function inTextResolve() : ?Node
    {
        $stream = $this->parser->getStream();
        $token  = $stream->peek();
        return match ( $token->type ) {
            Token::Html_TagOpen     => $this->parseTag(),
            Token::Html_CommentOpen => $this->parseComment(),
            Token::Html_BogusOpen   => $this->parseBogusTag(),
            default                 => $this->parser->inTextResolve(),
        };
    }

    /**
     * @throws CompileException|SecurityViolationException
     */
    public function inTagResolve() : ?Node
    {
        $stream = $this->parser->getStream();
        $token  = $stream->peek();
        return match ( $token->type ) {
            Token::Html_Name => \str_starts_with( $token->text, TemplateLexer::NPrefix )
                    ? $this->parseNAttribute()
                    : $this->parseAttribute(),
            Token::Latte_TagOpen => $this->parseAttribute(),
            Token::Whitespace    => $this->parseAttributeWhitespace(),
            Token::Html_TagClose => null,
            default              => $this->parser->inTextResolve(),
        };
    }

    /**
     * @return ?Node
     *
     * @throws CompileException
     * @throws SecurityViolationException
     */
    private function parseTag() : ?Node
    {
        $stream = $this->parser->getStream();
        $this->parser->getLexer()->setState( TemplateLexer::StateHtmlTag );
        if ( ! $stream->peek( 1 )?->is( Token::Slash ) ) {
            return $this->parseElement();
        }

        if ( $this->element
             && $this->parser->peekTag() === $this->element->data->tag // is directly in the element
        ) {
            $save          = $stream->getIndex();
            $this->endName = [$endText] = $this->parseEndTag();
            if ( $this->element->is( $endText ) || $this->element->data->textualName === $endText ) {
                return null; // go to parseElement() one level up to close the element
            }
            $stream->seek( $save );
            if ( ! \in_array( $endText, $this->element->data->unclosedTags ?? [], true ) ) {
                return null; // go to parseElement() one level up to collapse
            }
        }

        if ( $this->parser->strict ) {
            $stream->throwUnexpectedException( excerpt : '/' );
        }
        return $this->parseBogusEndTag();
    }

    /**
     * @throws CompileException
     * @throws SecurityViolationException
     * @throws SecurityViolationException
     * @throws SecurityViolationException
     */
    private function parseElement() : Node
    {
        $res = new FragmentNode();
        $res->append( $this->extractIndentation() );
        $res->append( $this->parseStartTag( $this->element ) );
        $elem = $this->element;

        $stream               = $this->parser->getStream();
        $void                 = $this->resolveVoidness( $elem );
        $attrs                = $this->prepareNAttrs( $elem->nAttributes, $void );
        $outerNodes           = $this->openNAttrNodes( $attrs[Tag::PrefixNone] ?? [] );
        $tagNodes             = $this->openNAttrNodes( $attrs[Tag::PrefixTag] ?? [] );
        $elem->tagNode        = $this->finishNAttrNodes( $elem->tagNode, $tagNodes );
        $elem->captureTagName = (bool) $tagNodes;

        if ( ! $void ) {
            $content = new FragmentNode();
            if ( $token = $stream->tryConsume( Token::Newline ) ) {
                $content->append( new TextNode( $token->text, $token->position ) );
            }

            $innerNodes      = $this->openNAttrNodes( $attrs[Tag::PrefixInner] ?? [] );
            $elem->data->tag = $this->parser->peekTag();
            $frag            = $this->parser->parseFragment( [$this, 'inTextResolve'] );
            $content->append( $this->finishNAttrNodes( $frag, $innerNodes ) );

            [$endText, $endVariable] = $this->endName;
            $this->endName           = null;
            if ( $endText && ( $this->element->is( $endText ) || $this->element->data->textualName === $endText ) ) {
                $elem->content = $content;
                $elem->content->append( $this->extractIndentation() );
            }
            elseif ( $outerNodes || $innerNodes || $tagNodes
                                 || $this->parser->strict
                                 || $elem->variableName
                                 || $endVariable
                                 || $elem->isRawText()
            ) {
                $stream->throwUnexpectedException(
                    addendum : ", expecting </{$elem->data->textualName}> for element started {$elem->position}",
                    excerpt  : $endText ? "/{$endText}>" : $stream->peek( 1 )?->text.$stream->peek( 2 )?->text,
                );
            }
            else { // element collapsed to tags
                $res->append( $content );
                $this->element = $elem->parent;
                if ( $this->element && ! $stream->is( Token::Html_TagOpen ) ) {
                    $this->element->data->unclosedTags[] = $elem->name;
                }
                return $res;
            }
        }

        if ( $token = $stream->tryConsume( Token::Newline ) ) {
            $res->append( new TextNode( $token->text, $token->position ) );
        }

        $res           = $this->finishNAttrNodes( $res, $outerNodes );
        $this->element = $elem->parent;
        return $res;
    }

    private function extractIndentation() : AreaNode
    {
        if ( $this->parser->lastIndentation ) {
            $dolly                                  = clone $this->parser->lastIndentation;
            $this->parser->lastIndentation->content = '';
            return $dolly;
        }

        return new NopNode();
    }

    /**
     * @param null|mixed $elem
     *
     * @throws CompileException|SecurityViolationException
     */
    private function parseStartTag( mixed &$elem = null ) : ElementNode
    {
        $stream    = $this->parser->getStream();
        $openToken = $stream->consume( Token::Html_TagOpen );
        $this->parser->getLexer()->setState( TemplateLexer::StateHtmlTag );

        [$textual, $variable] = $this->parseTagName( $this->parser->strict );
        if ( ( $this->parser->strict || $variable )
             && ! $stream->is( Token::Whitespace, Token::Slash, Token::Html_TagClose )
        ) {
            $stream->throwUnexpectedException();
        }

        $this->parser->lastIndentation = null;
        $this->parser->inHead          = false;
        $elem                          = new ElementNode(
            name        : $variable ? '' : $textual,
            position    : $openToken->position,
            parent      : $this->element,
            data        : (object) ['tag' => $this->parser->peekTag()],
            contentType : $this->parser->getContentType(),
        );
        $elem->attributes        = $this->parser->parseFragment( [$this, 'inTagResolve'] );
        $elem->selfClosing       = (bool) $stream->tryConsume( Token::Slash );
        $elem->variableName      = $variable;
        $elem->data->textualName = $textual;
        $stream->consume( Token::Html_TagClose );
        $state = ! $elem->selfClosing && $elem->isRawText()
                ? TemplateLexer::StateHtmlRawText
                : TemplateLexer::StateHtmlText;
        $this->parser->getLexer()->setState( $state, $elem->name );
        return $elem;
    }

    /**
     * @return array{string, ?Nodes\Php\ExpressionNode}
     * @throws CompileException|SecurityViolationException
     */
    private function parseEndTag() : array
    {
        $stream = $this->parser->getStream();
        $lexer  = $this->parser->getLexer();
        $stream->consume( Token::Html_TagOpen );
        $lexer->setState( TemplateLexer::StateHtmlTag );
        $stream->consume( Token::Slash );
        if ( isset( $this->element->nAttributes['syntax'] ) ) {  // hardcoded
            $lexer->popSyntax();
        }
        $name = $this->parseTagName();
        $stream->tryConsume( Token::Whitespace );
        $stream->consume( Token::Html_TagClose );
        $lexer->setState( TemplateLexer::StateHtmlText );
        return $name;
    }

    /**
     * @param bool $strict
     *
     * @return array{string, ?Nodes\Php\ExpressionNode}
     * @throws CompileException|SecurityViolationException
     */
    private function parseTagName( bool $strict = true ) : array
    {
        $variable = $text = null;
        $parts    = [];
        $stream   = $this->parser->getStream();
        while ( true ) {
            if ( $stream->is( Token::Latte_TagOpen ) ) {
                $save      = $stream->getIndex();
                $statement = $this->parser->parseLatteStatement( [$this, 'inTagResolve'] );
                if ( ! $statement instanceof PrintNode ) {
                    if ( ! $parts || $strict ) {
                        throw new CompileException(
                            'Only expression can be used as a HTML tag name.',
                            $statement->position,
                        );
                    }
                    $stream->seek( $save );

                    break;
                }
                $parts[] = $statement->expression;
                $save -= $stream->getIndex();
                while ( $save < 0 ) {
                    $text .= $stream->peek( $save++ )->text;
                }
                $variable = true;
            }
            elseif ( $token = $stream->tryConsume( Token::Html_Name ) ) {
                $parts[] = new StringNode( $token->text, $token->position );
                $text .= $token->text;
            }
            elseif ( ! $parts ) {
                $stream->throwUnexpectedException( [Token::Html_Name, Token::Latte_TagOpen] );
            }
            else {
                break;
            }
        }

        $variable = $variable
                ? BinaryOpNode::nest( '.', ...$parts )
                : null;
        return [$text, $variable];
    }

    /**
     * @throws CompileException
     */
    private function parseBogusEndTag() : ?BogusTagNode
    {
        $stream    = $this->parser->getStream();
        $openToken = $stream->consume( Token::Html_TagOpen );
        $this->parser->getLexer()->setState( TemplateLexer::StateHtmlTag );
        $this->parser->lastIndentation = null;
        $this->parser->inHead          = false;
        $node                          = new BogusTagNode(
            openDelimiter : $openToken->text.$stream->consume( Token::Slash )->text.$stream->consume(
                Token::Html_Name,
            )->text,
            content       : new TextNode( $stream->tryConsume( Token::Whitespace )->text ?? '' ),
            endDelimiter  : $stream->consume( Token::Html_TagClose )->text,
            position      : $openToken->position,
        );
        $this->parser->getLexer()->setState( TemplateLexer::StateHtmlText );
        return $node;
    }

    /**
     * @throws CompileException
     */
    private function parseBogusTag() : BogusTagNode
    {
        $stream    = $this->parser->getStream();
        $openToken = $stream->consume( Token::Html_BogusOpen );
        $this->parser->getLexer()->setState( TemplateLexer::StateHtmlBogus );
        $this->parser->lastIndentation = null;
        $this->parser->inHead          = false;
        $content                       = $this->parser->parseFragment( [$this->parser, 'inTextResolve'] );
        $this->parser->getLexer()->setState( TemplateLexer::StateHtmlText );
        return new BogusTagNode(
            openDelimiter : $openToken->text,
            content       : $content,
            endDelimiter  : $stream->consume( Token::Html_TagClose )->text,
            position      : $openToken->position,
        );
    }

    private function resolveVoidness( ElementNode $elem ) : bool
    {
        if ( $elem->contentType !== ContentType::HTML ) {
            return $elem->selfClosing;
        }
        if ( isset( Helpers::$emptyElements[\strtolower( $elem->name )] ) ) {
            return true;
        }
        if ( $elem->selfClosing ) { // auto-correct
            $elem->content     = new NopNode();
            $elem->selfClosing = false;
            $last              = \end( $elem->attributes->children );
            if ( $last instanceof TextNode && $last->isWhitespace() ) {
                \array_pop( $elem->attributes->children );
            }
            return true;
        }

        return $elem->selfClosing;
    }

    /**
     * @throws CompileException
     */
    private function parseAttributeWhitespace() : Node
    {
        $stream = $this->parser->getStream();
        $token  = $stream->consume( Token::Whitespace );
        return $stream->is( Token::Html_Name ) && \str_starts_with( $stream->peek()->text, TemplateLexer::NPrefix )
                ? new NopNode()
                : new TextNode( $token->text, $token->position );
    }

    /**
     * @throws CompileException|SecurityViolationException
     */
    private function parseAttribute() : ?Node
    {
        $stream = $this->parser->getStream();
        if ( $stream->is( Token::Latte_TagOpen ) ) {
            $name = $this->parser->parseLatteStatement();
            if ( ! $name instanceof PrintNode ) {
                return $name; // value like '<span {if true}attr1=val{/if}>'
            }
        }
        else {
            $name = $this->parser->parseText();
        }

        [$value, $quote] = $this->parseAttributeValue();
        return new AttributeNode(
            name     : $name,
            value    : $value,
            quote    : $quote,
            position : $name->position,
        );
    }

    /**
     * @throws CompileException|SecurityViolationException
     */
    private function parseAttributeValue() : ?array
    {
        $stream = $this->parser->getStream();
        $save   = $stream->getIndex();
        $this->consumeIgnored();
        if ( ! $stream->tryConsume( Token::Equals ) ) {
            $stream->seek( $save );
            return null;
        }

        $this->consumeIgnored();
        if ( $quoteToken = $stream->tryConsume( Token::Quote ) ) {
            $this->parser->getLexer()->setState( TemplateLexer::StateHtmlQuotedValue, $quoteToken->text );
            $value = $this->parser->parseFragment( [$this->parser, 'inTextResolve'] );
            $stream->tryConsume( Token::Quote ) || $stream->throwUnexpectedException(
                [$quoteToken->text],
                addendum : ", end of HTML attribute started {$quoteToken->position}",
            );
            $this->parser->getLexer()->setState( TemplateLexer::StateHtmlTag );
            return [$value, $quoteToken->text];
        }

        $value = $this->parser->parseFragment(
            /**
             * @throws CompileException|SecurityViolationException
             */
            fn() => match ( $stream->peek()->type ) {
                Token::Html_Name         => $this->parser->parseText(),
                Token::Latte_TagOpen     => $this->parser->parseLatteStatement(),
                Token::Latte_CommentOpen => $this->parser->parseLatteComment(),
                default                  => null,
            },
        )->simplify() ?? $stream->throwUnexpectedException();
        return [$value, null];
    }

    /**
     * @throws CompileException
     */
    private function parseNAttribute() : TextNode
    {
        $stream    = $this->parser->getStream();
        $nameToken = $stream->consume( Token::Html_Name );
        $save      = $stream->getIndex();
        $pos       = $stream->peek()->position;
        $name      = \substr( $nameToken->text, \strlen( TemplateLexer::NPrefix ) );
        if ( $this->parser->peekTag() !== $this->element->data->tag ) {
            throw new CompileException( "Attribute n:{$name} must not appear inside {tags}", $nameToken->position );
        }
        if ( isset( $this->element->nAttributes[$name] ) ) {
            throw new CompileException( "Found multiple attributes n:{$name}.", $nameToken->position );
        }

        $this->consumeIgnored();
        if ( $stream->tryConsume( Token::Equals ) ) {
            $this->consumeIgnored();
            if ( $quoteToken = $stream->tryConsume( Token::Quote ) ) {
                $this->parser->getLexer()->setState( TemplateLexer::StateHtmlQuotedNAttrValue, $quoteToken->text );
                $valueToken = $stream->tryConsume( Token::Text );
                $pos        = $stream->peek()->position;
                $stream->tryConsume( Token::Quote ) || $stream->throwUnexpectedException(
                    [$quoteToken->text],
                    addendum : ", end of n:attribute started {$quoteToken->position}",
                );
                $this->parser->getLexer()->setState( TemplateLexer::StateHtmlTag );
            }
            else {
                $valueToken = $stream->consume( Token::Html_Name );
            }
            if ( $valueToken ) {
                $tokens = ( new TagLexer() )->tokenize( $valueToken->text, $valueToken->position );
            }
        }
        else {
            $stream->seek( $save );
        }
        $tokens ??= [new Token( Token::End, '', $pos )];

        $this->element->nAttributes[$name] = new Tag(
            name           : \preg_replace( '~(inner-|tag-|)~', '', $name ),
            tokens         : $tokens,
            position       : $nameToken->position,
            inTag          : true,
            htmlElement    : $this->element,
            prefix         : $this->getPrefix( $name ),
            nAttributeNode : $node = new TextNode( '' ),
        );
        return $node;
    }

    /**
     * @throws CompileException
     */
    private function parseComment() : CommentNode
    {
        $this->parser->lastIndentation = null;
        $this->parser->inHead          = false;
        $this->parser->getLexer()->setState( TemplateLexer::StateHtmlComment );
        $stream    = $this->parser->getStream();
        $openToken = $stream->consume( Token::Html_CommentOpen );
        $node      = new CommentNode(
            content  : $this->parser->parseFragment( [$this->parser, 'inTextResolve'] ),
            position : $openToken->position,
        );
        $stream->tryConsume( Token::Html_CommentClose ) || $stream->throwUnexpectedException(
            [Token::Html_CommentClose],
            addendum : " started {$openToken->position}",
        );
        $this->parser->getLexer()->setState( TemplateLexer::StateHtmlText );
        return $node;
    }

    /**
     * @throws CompileException
     */
    private function consumeIgnored() : void
    {
        $stream = $this->parser->getStream();
        while ( true ) {
            if ( $stream->tryConsume( Token::Whitespace ) ) {
                continue;
            }
            if ( $stream->tryConsume( Token::Latte_CommentOpen ) ) {
                $this->parser->getLexer()->pushState( TemplateLexer::StateLatteComment );
                $stream->consume( Token::Text );
                $stream->consume( Token::Latte_CommentClose );
                $this->parser->getLexer()->popState();
                $stream->tryConsume( Token::Newline );

                continue;
            }
            return;
        }
    }

    /**
     * @param array $attrs
     * @param bool  $void
     *
     * @return array
     * @throws CompileException
     */
    private function prepareNAttrs( array $attrs, bool $void ) : array
    {
        $res = [];

        foreach ( $this->attrParsers as $name => $foo ) {
            if ( $tag = $attrs[$name] ?? null ) {
                $prefix = $this->getPrefix( $name );
                if ( ! $prefix || ! $void ) {
                    $res[$prefix][] = $tag;
                    unset( $attrs[$name] );
                }
            }
        }

        if ( $attrs ) {
            $hint = Helpers::getSuggestion( \array_keys( $this->attrParsers ), $k = \key( $attrs ) );
            throw new CompileException(
                'Unexpected attribute n:'
                    .( $hint
                            ? "{$k}, did you mean n:{$hint}?"
                            : \implode(
                                ' and n:',
                                \array_keys(
                                    $attrs,
                                ),
                            ) ),
                $attrs[$k]->position,
            );
        }

        return $res;
    }

    /**
     * @param array<Tag> $toOpen
     *
     * @return array<array{Generator, Tag}>
     * @throws CompileException
     * @throws SecurityViolationException
     */
    private function openNAttrNodes( array $toOpen ) : array
    {
        $toClose = [];

        foreach ( $toOpen as $tag ) {
            $parser = $this->getAttrParser( $tag->name, $tag->position );
            $this->parser->pushTag( $tag );
            $res = $parser( $tag, $this->parser );
            if ( $res instanceof Generator && $res->valid() ) {
                $toClose[] = [$res, $tag];
            }
            elseif ( $res instanceof AreaNode ) {
                $this->parser->ensureIsConsumed( $tag );
                $res->position = $tag->position;
                $tag->replaceNAttribute( $res );
                $this->parser->popTag();
            }
            elseif ( ! $res ) {
                $this->parser->ensureIsConsumed( $tag );
                $this->parser->popTag();
            }
            else {
                throw new CompileException(
                    "Unexpected value returned by {$tag->getNotation()} parser.",
                    $tag->position,
                );
            }
        }

        return $toClose;
    }

    /**
     * @param AreaNode                     $node
     * @param array<array{Generator, Tag}> $toClose
     *
     * @return AreaNode
     * @throws CompileException
     */
    private function finishNAttrNodes( AreaNode $node, array $toClose ) : AreaNode
    {
        while ( [$gen, $tag] = \array_pop( $toClose ) ) {
            $gen->send( [$node, null] );
            $node           = $gen->getReturn();
            $node->position = $tag->position;
            $this->parser->popTag();
            $this->parser->ensureIsConsumed( $tag );
        }

        return $node;
    }

    /**
     * @param string   $name
     * @param Position $pos
     *
     * @return callable(Tag, TemplateParser): (Generator|Node|void)
     * @throws CompileException
     * @throws SecurityViolationException
     */
    private function getAttrParser( string $name, Position $pos ) : callable
    {
        if ( ! isset( $this->attrParsers[$name] ) ) {
            $hint = ( $t = Helpers::getSuggestion( \array_keys( $this->attrParsers ), $name ) )
                    ? ", did you mean n:{$t}?"
                    : '';
            throw new CompileException( "Unknown n:{$name}{$hint}", $pos );
        }
        if ( ! $this->parser->isTagAllowed( $name ) ) {
            throw new SecurityViolationException( "Attribute n:{$name} is not allowed", $pos );
        }
        return $this->attrParsers[$name];
    }

    private function getPrefix( string $name ) : string
    {
        return match ( true ) {
            \str_starts_with( $name, 'inner-' ) => Tag::PrefixInner,
            \str_starts_with( $name, 'tag-' )   => Tag::PrefixTag,
            default                             => Tag::PrefixNone,
        };
    }
}
