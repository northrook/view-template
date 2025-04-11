<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Compiler\{Block, PrintContext, Tag, TemplateParser, Token};
use Core\View\Template\Exception\CompileException;
use Core\View\Template\Compiler\Nodes\Php\{ParameterNode};
use Core\View\Template\Compiler\Nodes\Php\Scalar\{NullNode, StringNode};
use Core\View\Template\Compiler\Nodes\Php\Expression\{AssignNode, VariableNode};
use Core\View\Template\Runtime\Template;
use Generator;

/**
 * {define [local] name}
 */
class DefineNode extends StatementNode
{
    public Block $block;

    public AreaNode $content;

    /**
     * @param Tag            $tag
     * @param TemplateParser $parser
     *
     * @return Generator<int, ?array, array{AreaNode, ?Tag}, static>
     * @throws CompileException
     */
    public static function create( Tag $tag, TemplateParser $parser ) : Generator
    {
        $tag->expectArguments();
        $layer = $tag->parser->tryConsumeTokenBeforeUnquotedString( 'local' )
                ? Template::LAYER_LOCAL
                : $parser->blockLayer;
        $tag->parser->stream->tryConsume( '#' );
        $name = $tag->parser->parseUnquotedStringOrExpression();

        $node        = $tag->node = new static();
        $node->block = new Block( $name, $layer, $tag );
        if ( ! $node->block->isDynamic() ) {
            $parser->checkBlockIsUnique( $node->block );
            $tag->parser->stream->tryConsume( ',' );
            $node->block->parameters = self::parseParameters( $tag );
        }

        [$node->content, $endTag] = yield;
        if ( $endTag && $name instanceof StringNode ) {
            $endTag->parser->stream->tryConsume( $name->value );
        }

        return $node;
    }

    /**
     * @param Tag $tag
     *
     * @return array
     * @throws CompileException
     */
    private static function parseParameters( Tag $tag ) : array
    {
        $stream = $tag->parser->stream;
        $params = [];
        while ( ! $stream->is( Token::End ) ) {
            $type = $tag->parser->parseType();

            $save = $stream->getIndex();
            $expr = $stream->is( Token::Php_Variable ) ? $tag->parser->parseExpression() : null;
            if ( $expr instanceof VariableNode && \is_string( $expr->name ) ) {
                $params[] = new ParameterNode( $expr, new NullNode(), $type );
            }
            elseif (
                $expr instanceof AssignNode
                && $expr->var instanceof VariableNode
                && \is_string( $expr->var->name )
            ) {
                $params[] = new ParameterNode( $expr->var, $expr->expr, $type );
            }
            else {
                $stream->seek( $save );
                $stream->throwUnexpectedException( addendum : ' in '.$tag->getNotation() );
            }

            if ( ! $stream->tryConsume( ',' ) ) {
                break;
            }
        }

        return $params;
    }

    /**
     * @param PrintContext $context
     *
     * @return string
     * @throws CompileException
     */
    public function print( PrintContext $context ) : string
    {
        return $this->block->isDynamic()
                ? $this->printDynamic( $context )
                : $this->printStatic( $context );
    }

    private function printStatic( PrintContext $context ) : string
    {
        $context->addBlock( $this->block );
        $this->block->content = $this->content->print( $context ); // must be compiled after is added
        return '';
    }

    /**
     * @param PrintContext $context
     *
     * @return string
     * @throws CompileException
     */
    private function printDynamic( PrintContext $context ) : string
    {
        $context->addBlock( $this->block );
        $this->block->content = $this->content->print( $context ); // must be compiled after is added

        return $context->format(
            '$this->addBlock(%node, %dump, [[$this, %dump]], %dump);',
            new AssignNode( new VariableNode( 'ʟ_nm' ), $this->block->name ),
            $context->getEscaper()->export(),
            $this->block->method,
            $this->block->layer,
        );
    }

    public function &getIterator() : Generator
    {
        yield $this->block->name;

        foreach ( $this->block->parameters as &$param ) {
            yield $param;
        }

        yield $this->content;
    }
}
