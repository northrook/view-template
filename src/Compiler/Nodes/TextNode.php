<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Generator;

use Core\View\Template\Compiler\{Position, PrintContext};

class TextNode extends AreaNode
{
    public function __construct(
        public string    $content,
        public ?Position $position = null,
    ) {}

    public function print( PrintContext $context ) : string
    {
        return $this->content === ''
                ? ''
                : 'echo '.\var_export( $this->content, true ).";\n";
    }

    public function isWhitespace() : bool
    {
        return \trim( $this->content ) === '';
    }

    public function &getIterator() : Generator
    {
        false && yield;
    }
}
