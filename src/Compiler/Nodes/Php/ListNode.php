<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php;

use Core\View\Template\Exception\CompileException;
use Generator;
use TypeError;
use Core\View\Template\Compiler\{Node, Position, PrintContext};

class ListNode extends Node
{
    /**
     * @param ?ListItemNode[] $items
     * @param null|Position   $position
     */
    public function __construct(
        public array     $items = [],
        public ?Position $position = null,
    ) {
        $this->validate();
    }

    /**
     * @param PrintContext $context
     *
     * @return string
     * @throws CompileException
     */
    public function print( PrintContext $context ) : string
    {
        $this->validate();
        return '['.$context->implode( $this->items ).']';
    }

    /**
     * @throws CompileException
     */
    public function validate() : void
    {
        foreach ( $this->items as $item ) {
            if ( $item !== null && ! $item instanceof ListItemNode ) {
                throw new TypeError( 'Item must be null or ListItemNode, '.\get_debug_type( $item ).' given.' );
            }
            if ( $item?->value instanceof ExpressionNode && ! $item->value->isWritable() ) {
                throw new CompileException(
                    'Cannot write to the expression: '.$item->value->print( new PrintContext() ),
                    $item->value->position,
                );
            }
        }
    }

    public function &getIterator() : Generator
    {
        foreach ( $this->items as &$item ) {
            if ( $item ) {
                yield $item;
            }
        }
    }
}
