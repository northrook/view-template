<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php;

use Core\View\Template\Compiler\PrintContext;

class VarLikeIdentifierNode extends IdentifierNode
{
    public function print( ?PrintContext $context ) : string
    {
        return '$'.$this->name;
    }
}
