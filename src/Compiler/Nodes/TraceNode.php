<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Compiler\{PrintContext, Tag};

/**
 * {trace}
 */
class TraceNode extends StatementNode
{
    public static function create( Tag $tag ) : static
    {
        return new static();
    }

    public function print( ?PrintContext $context ) : string
    {
        return $context->format(
            '\Core\View\Template\Support\Tracer::throw() %line;',
            $this->position,
        );
    }
}
