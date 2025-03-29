<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Compiler\Nodes\Php\ExpressionNode;
use Core\View\Template\Compiler\Nodes\Php\Scalar\NullNode;
use Core\View\Template\Compiler\Nodes\StatementNode;
use Core\View\Template\Compiler\{PrintContext, Tag, Token};
use Core\View\Template\Compiler\Nodes\Php\Expression\{AssignNode, VariableNode};
use Generator;

/**
 * {var [type] $var = value, ...}
 * {default [type] $var = value, ...}
 */
class VarNode extends StatementNode
{
    public bool $default;

    /** @var AssignNode[] */
    public array $assignments = [];

    /**
     * @param Tag $tag
     *
     * @return VarNode
     * @throws \Core\View\Template\Exception\CompileException
     */
    public static function create( Tag $tag ) : static
    {
        $tag->expectArguments();
        $node              = new static();
        $node->default     = $tag->name === 'default';
        $node->assignments = self::parseAssignments( $tag, $node->default );
        return $node;
    }

    /**
     * @param Tag  $tag
     * @param bool $default
     *
     * @return array
     * @throws \Core\View\Template\Exception\CompileException
     */
    private static function parseAssignments( Tag $tag, bool $default ) : array
    {
        $stream = $tag->parser->stream;
        $res    = [];
        do {
            $tag->parser->parseType();

            $save = $stream->getIndex();
            $expr = $stream->is( Token::Php_Variable ) ? $tag->parser->parseExpression() : null;
            if ( $expr instanceof VariableNode ) {
                $res[] = new AssignNode( $expr, new NullNode() );
            }
            elseif ( $expr instanceof AssignNode && ( ! $default || $expr->var instanceof VariableNode ) ) {
                $res[] = $expr;
            }
            else {
                $stream->seek( $save );
                $stream->throwUnexpectedException( addendum : ' in '.$tag->getNotation() );
            }
        }
        while ( $stream->tryConsume( ',' ) && ! $stream->peek()->isEnd() );

        return $res;
    }

    public function print( PrintContext $context ) : string
    {
        $res = [];
        if ( $this->default ) {
            foreach ( $this->assignments as $assign ) {
                \assert( $assign->var instanceof VariableNode );
                if ( $assign->var->name instanceof ExpressionNode ) {
                    $var = $assign->var->name->print( $context );
                }
                else {
                    $var = $context->encodeString( $assign->var->name );
                }
                $res[] = $var.' => '.$assign->expr->print( $context );
            }

            return $context->format(
                'extract([%raw], EXTR_SKIP) %line;',
                \implode( ', ', $res ),
                $this->position,
            );
        }

        foreach ( $this->assignments as $assign ) {
            $res[] = $assign->print( $context );
        }

        return $context->format(
            '%raw %line;',
            \implode( '; ', $res ),
            $this->position,
        );
    }

    public function &getIterator() : Generator
    {
        foreach ( $this->assignments as &$assign ) {
            yield $assign;
        }
    }
}
