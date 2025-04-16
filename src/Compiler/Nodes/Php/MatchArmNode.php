<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php;

use Generator;

use Core\View\Template\Compiler\{Node, Position, PrintContext};
class MatchArmNode extends Node
{
    /**
     * @param ?ExpressionNode[] $conds
     * @param ExpressionNode    $body
     * @param null|Position     $position
     */
    public function __construct(
        public ?array         $conds,
        public ExpressionNode $body,
        public ?Position      $position = null,
    ) {}

    public function print( ?PrintContext $context ) : string
    {
        return ( $this->conds ? $context->implode( $this->conds ) : 'default' )
               .' => '
               .$this->body->print( $context );
    }

    public function &getIterator() : Generator
    {
        if ( $this->conds ) {
            foreach ( $this->conds as &$item ) {
                yield $item;
            }
        }
        yield $this->body;
    }
}
