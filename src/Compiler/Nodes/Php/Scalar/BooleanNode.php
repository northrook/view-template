<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php\Scalar;

use Core\View\Template\Compiler\Nodes\Php\ScalarNode;
use Core\View\Template\Compiler\{Position, PrintContext};

class BooleanNode extends ScalarNode
{
    public function __construct(
        public bool      $value,
        public ?Position $position = null,
    ) {}

    public function print( ?PrintContext $context ) : string
    {
        return $this->value ? 'true' : 'false';
    }
}
