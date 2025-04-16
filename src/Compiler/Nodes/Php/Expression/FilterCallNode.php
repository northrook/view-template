<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php\Expression;

use Generator;

use Core\View\Template\Compiler\{Position, PrintContext};
use Core\View\Template\Compiler\Nodes\Php\{ExpressionNode, FilterNode};

class FilterCallNode extends ExpressionNode
{
    public function __construct(
        public ExpressionNode $expr,
        public FilterNode     $filter,
        public ?Position      $position = null,
    ) {}

    public function print( ?PrintContext $context ) : string
    {
        return $this->filter->printSimple( $context, $this->expr->print( $context ) );
    }

    public function &getIterator() : Generator
    {
        yield $this->expr;
        yield $this->filter;
    }
}
