<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php\Expression;

use Core\View\Template\Compiler\Nodes\Php\ExpressionNode;
use Core\View\Template\Compiler\{Nodes\Php\ArgumentNode, Nodes\Php\IdentifierNode, Position, PrintContext};
use Generator;

class MethodCallNode extends ExpressionNode
{
    /**
     * @param ExpressionNode                $object
     * @param ExpressionNode|IdentifierNode $name
     * @param ArgumentNode[]                $args
     * @param bool                          $nullsafe
     * @param null|Position                 $position
     */
    public function __construct(
        public ExpressionNode                $object,
        public IdentifierNode|ExpressionNode $name,
        public array                         $args = [],
        public bool                          $nullsafe = false,
        public ?Position                     $position = null,
    ) {
        ( function( ArgumentNode ...$args ) {} )( ...$args );
    }

    public function print( ?PrintContext $context ) : string
    {
        return $context->dereferenceExpr( $this->object )
               .( $this->nullsafe ? '?->' : '->' )
               .$context->objectProperty( $this->name )
               .'('.$context->implode( $this->args ).')';
    }

    public function &getIterator() : Generator
    {
        yield $this->object;
        yield $this->name;

        foreach ( $this->args as &$item ) {
            yield $item;
        }
    }
}
