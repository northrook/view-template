<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\ContentType;
use Generator;
use LogicException;

use Core\View\Template\Compiler\{Node, PrintContext};
final class TemplateNode extends Node
{
    public FragmentNode $head;

    public FragmentNode $main;

    public ContentType $contentType;

    public function print( PrintContext $context ) : string
    {
        throw new LogicException( 'Cannot directly print TemplateNode' );
    }

    public function &getIterator() : Generator
    {
        yield $this->head;
        yield $this->main;
    }
}
