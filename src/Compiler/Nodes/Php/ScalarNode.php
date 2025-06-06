<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php;

use Generator;

abstract class ScalarNode extends ExpressionNode
{
    public function &getIterator() : Generator
    {
        false && yield;
    }
}
