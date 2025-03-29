<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php\Expression;

use Core\View\Template\Compiler\Nodes\Php\ExpressionNode;
use Core\View\Template\Compiler\{Position, PrintContext};
use Generator;

class NotNode extends ExpressionNode
{
    public function __construct(
        public ExpressionNode $expr,
        public ?Position      $position = null,
    ) {}

    public function print( PrintContext $context ) : string
    {
        return $context->prefixOp( $this, '!', $this->expr );
    }

    public function &getIterator() : Generator
    {
        yield $this->expr;
    }
}
