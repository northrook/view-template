<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php;

use Generator;

use Core\View\Template\Compiler\{Node, Position, PrintContext};
class IdentifierNode extends Node
{
    public function __construct(
        public string    $name,
        public ?Position $position = null,
    ) {}

    public function __toString() : string
    {
        return $this->name;
    }

    public function print( PrintContext $context ) : string
    {
        return $this->name;
    }
}
