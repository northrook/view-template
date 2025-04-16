<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php;

use Generator;

use Core\View\Template\Compiler\{
    Node,
    Position,
    PrintContext,
};

class ArgumentNode extends Node
{
    public function __construct(
        public ExpressionNode  $value,
        public bool            $byRef = false,
        public bool            $unpack = false,
        public ?IdentifierNode $name = null,
        public ?Position       $position = null,
    ) {}

    public function print( ?PrintContext $context ) : string
    {
        return ( $this->name ? $this->name.': ' : '' )
               .( $this->byRef ? '&' : '' )
               .( $this->unpack ? '...' : '' )
               .$this->value->print( $context );
    }

    public function toArrayItem() : ArrayItemNode
    {
        return new ArrayItemNode( $this->value, $this->name, $this->byRef, $this->unpack, $this->position );
    }

    public function &getIterator() : Generator
    {
        if ( $this->name ) {
            yield $this->name;
        }
        yield $this->value;
    }
}
