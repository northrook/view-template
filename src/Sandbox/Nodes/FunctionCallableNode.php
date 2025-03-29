<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Sandbox\Nodes;

use Core\View\Template\Compiler\Nodes\Php\Expression;
use Core\View\Template\Compiler\PrintContext;

class FunctionCallableNode extends Expression\FunctionCallableNode
{
    public function __construct( Expression\FunctionCallableNode $from )
    {
        parent::__construct( $from->name, $from->position );
    }

    public function print( PrintContext $context ) : string
    {
        return '$this->global->sandbox->closure('.$context->memberAsString( $this->name ).')';
    }
}
