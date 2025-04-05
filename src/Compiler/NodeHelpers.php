<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler;

use Core\View\Template\Compiler\Nodes\{FragmentNode, NopNode, TextNode};
use InvalidArgumentException;

use Core\View\Template\Compiler\Nodes\Php\{Expression\ArrayNode,
    Expression\ClassConstantFetchNode,
    Expression\ConstantFetchNode,
    ExpressionNode,
    IdentifierNode,
    NameNode,
    Scalar\BooleanNode,
    Scalar\FloatNode,
    Scalar\IntegerNode,
    Scalar\NullNode,
    Scalar\StringNode
};

final class NodeHelpers
{
    /**
     * @param Node     $node
     * @param callable $filter
     *
     * @return Node[]
     */
    public static function find( Node $node, callable $filter ) : array
    {
        $found = [];
        ( new NodeTraverser() )
            ->traverse(
                $node,
                enter : function( Node $node ) use ( $filter, &$found ) {
                    if ( $filter( $node ) ) {
                        $found[] = $node;
                    }
                },
            );
        return $found;
    }

    public static function findFirst( Node $node, callable $filter ) : ?Node
    {
        $found = null;
        ( new NodeTraverser() )
            ->traverse(
                $node,
                enter : function( Node $node ) use ( $filter, &$found ) {
                    if ( $filter( $node ) ) {
                        $found = $node;
                        return NodeTraverser::BREAK;
                    }
                    return $node; // :: [ADDED]
                },
            );
        return $found;
    }

    public static function clone( Node $node ) : Node
    {
        return ( new NodeTraverser() )
            ->traverse( $node, enter : fn( Node $node ) => clone $node );
    }

    public static function toValue( ExpressionNode $node, bool $constants = false ) : mixed
    {
        if ( $node instanceof BooleanNode
             || $node instanceof FloatNode
             || $node instanceof IntegerNode
             || $node instanceof StringNode
        ) {
            return $node->value;
        }
        if ( $node instanceof NullNode ) {
            return null;
        }
        if ( $node instanceof ArrayNode ) {
            $res = [];

            foreach ( $node->items as $item ) {
                $value = self::toValue( $item->value, $constants );
                if ( $item->key ) {
                    $key = $item->key instanceof IdentifierNode
                            ? $item->key->name
                            : self::toValue( $item->key, $constants );
                    $res[$key] = $value;
                }
                elseif ( $item->unpack ) {
                    $res = \array_merge( $res, $value );
                }
                else {
                    $res[] = $value;
                }
            }

            return $res;
        }
        if ( $node instanceof ConstantFetchNode && $constants ) {
            $name = $node->name->toCodeString();
            return \defined( $name )
                    ? \constant( $name )
                    : throw new InvalidArgumentException( "The constant '{$name}' is not defined." );
        }
        elseif ( $node instanceof ClassConstantFetchNode && $constants ) {
            $class = $node->class instanceof NameNode
                    ? $node->class->toCodeString()
                    : self::toValue( $node->class, $constants );
            $name = $class.'::'.$node->name->name;
            return \defined( $name )
                    ? \constant( $name )
                    : throw new InvalidArgumentException( "The constant '{$name}' is not defined." );
        }
        else {
            throw new InvalidArgumentException( 'The expression cannot be converted to PHP value.' );
        }
    }

    public static function toText( ?Node $node ) : ?string
    {
        if ( $node instanceof FragmentNode ) {
            $res = '';

            foreach ( $node->children as $child ) {
                if ( ( $s = self::toText( $child ) ) === null ) {
                    return null;
                }
                $res .= $s;
            }

            return $res;
        }

        return match ( true ) {
            $node instanceof TextNode => $node->content,
            $node instanceof NopNode  => '',
            default                   => null,
        };
    }
}
