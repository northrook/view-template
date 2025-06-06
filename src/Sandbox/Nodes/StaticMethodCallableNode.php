<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Sandbox\Nodes;

use Core\View\Template\Compiler\Nodes\Php\Expression;
use Core\View\Template\Compiler\PrintContext;

class StaticMethodCallableNode extends Expression\StaticMethodCallableNode
{
    public function __construct( Expression\StaticMethodCallableNode $from )
    {
        parent::__construct( $from->class, $from->name, $from->position );
    }

    public function print( ?PrintContext $context ) : string
    {
        return '$this->global->sandbox->closure(['
               .$context->memberAsString( $this->class ).', '
               .$context->memberAsString( $this->name ).'])';
    }
}
