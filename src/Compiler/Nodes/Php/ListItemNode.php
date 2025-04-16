<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php;

use Generator;

use Core\View\Template\Compiler\{Node, Position, PrintContext};
class ListItemNode extends Node
{
    public function __construct(
        public ExpressionNode|ListNode            $value,
        public ExpressionNode|IdentifierNode|null $key = null,
        public bool                               $byRef = false,
        public ?Position                          $position = null,
    ) {}

    public function print( ?PrintContext $context ) : string
    {
        $key = match ( true ) {
            $this->key instanceof ExpressionNode => $this->key->print( $context ).' => ',
            $this->key instanceof IdentifierNode => $context->encodeString( $this->key->name ).' => ',
            $this->key === null                  => '',
        };
        return $key
               .( $this->byRef ? '&' : '' )
               .$this->value->print( $context );
    }

    public function &getIterator() : Generator
    {
        if ( $this->key ) {
            yield $this->key;
        }
        yield $this->value;
    }
}
