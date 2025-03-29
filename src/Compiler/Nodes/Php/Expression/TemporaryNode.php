<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php\Expression;

use Core\View\Template\Compiler\{Position, PrintContext};
use Core\View\Template\Compiler\Nodes\Php\{
    ListNode,
    ExpressionNode,
};
use Generator;

/**
 * Only for parser needs.
 *
 * @internal
 */
class TemporaryNode extends ExpressionNode
{
    public function __construct(
        public ?ListNode $value = null,
        public ?Position $position = null,
    ) {}

    public function print( PrintContext $context ) : string
    {
        return 'TemporaryNode';
    }

    public function &getIterator() : Generator
    {
        yield;
    }
}
