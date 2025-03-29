<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php\Expression;

use Core\View\Template\Compiler\Nodes\Php\ExpressionNode;
use Generator;
use Core\View\Template\Compiler\{Position, PrintContext};

class VariableNode extends ExpressionNode
{
    public function __construct(
        public string|ExpressionNode $name,
        public ?Position             $position = null,
    ) {}

    public function print( PrintContext $context ) : string
    {
        return $this->name instanceof ExpressionNode
                ? '${'.$this->name->print( $context ).'}'
                : '$'.$this->name;
    }

    public function &getIterator() : Generator
    {
        if ( $this->name instanceof ExpressionNode ) {
            yield $this->name;
        }
    }
}
