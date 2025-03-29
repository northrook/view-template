<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Sandbox\Nodes;

use Core\View\Template\Compiler\Nodes\Php\Expression;
use Core\View\Template\Compiler\PrintContext;

class StaticMethodCallNode extends Expression\StaticMethodCallNode
{
    public function __construct( Expression\StaticMethodCallNode $from )
    {
        parent::__construct( $from->class, $from->name, $from->args, $from->position );
    }

    public function print( PrintContext $context ) : string
    {
        return '$this->global->sandbox->call(['
               .$context->memberAsString( $this->class ).', '
               .$context->memberAsString( $this->name ).'], '
               .$context->argumentsAsArray( $this->args ).')';
    }
}
