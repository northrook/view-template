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

class EmptyNode extends ExpressionNode
{
    public function __construct(
        public ExpressionNode $expr,
        public ?Position      $position = null,
    ) {}

    public function print( PrintContext $context ) : string
    {
        return 'empty('.$this->expr->print( $context ).')';
    }

    public function &getIterator() : Generator
    {
        yield $this->expr;
    }
}
