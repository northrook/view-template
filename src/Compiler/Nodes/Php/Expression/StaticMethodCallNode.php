<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php\Expression;

use Core\View\Template\Compiler\Nodes\Php\{ArgumentNode, ExpressionNode, IdentifierNode, NameNode};
use Core\View\Template\Compiler\{Position, PrintContext};
use Generator;

class StaticMethodCallNode extends ExpressionNode
{
    /**
     * @param ExpressionNode|NameNode       $class
     * @param ExpressionNode|IdentifierNode $name
     * @param ArgumentNode[]                $args
     * @param null|Position                 $position
     */
    public function __construct(
        public NameNode|ExpressionNode       $class,
        public IdentifierNode|ExpressionNode $name,
        public array                         $args = [],
        public ?Position                     $position = null,
    ) {
        ( function( ArgumentNode ...$args ) {} )( ...$args );
    }

    public function print( PrintContext $context ) : string
    {
        $name = match ( true ) {
            $this->name instanceof VariableNode   => $this->name->print( $context ),
            $this->name instanceof ExpressionNode => '{'.$this->name->print( $context ).'}',
            default                               => $this->name,
        };
        return $context->dereferenceExpr( $this->class )
               .'::'
               .$name
               .'('.$context->implode( $this->args ).')';
    }

    public function &getIterator() : Generator
    {
        yield $this->class;
        yield $this->name;

        foreach ( $this->args as &$item ) {
            yield $item;
        }
    }
}
