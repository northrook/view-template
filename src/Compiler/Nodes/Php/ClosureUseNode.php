<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php;

use Core\View\Template\Compiler\{Node, Position, PrintContext};
use Core\View\Template\Compiler\Nodes\Php\Expression\VariableNode;
use Generator;

class ClosureUseNode extends Node
{
    public function __construct(
        public VariableNode $var,
        public bool         $byRef = false,
        public ?Position    $position = null,
    ) {}

    public function print( ?PrintContext $context ) : string
    {
        return ( $this->byRef ? '&' : '' ).$this->var->print( $context ?? new PrintContext() );
    }

    public function &getIterator() : Generator
    {
        yield $this->var;
    }
}
