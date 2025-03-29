<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Engine;

use Core\View\Template\Compiler\Nodes\RawPhpNode;
use Core\View\Template\Extension;

/**
 * Raw PHP in {php ...}
 */
final class RawPhpExtension extends \Core\View\Template\Extension
{
    public function getTags() : array
    {
        return ['php' => [RawPhpNode::class, 'create']];
    }
}
