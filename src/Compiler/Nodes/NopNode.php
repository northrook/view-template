<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Compiler\PrintContext;
use Generator;

class NopNode extends AreaNode
{
    public function print( PrintContext $context ) : string
    {
        return '';
    }
}
