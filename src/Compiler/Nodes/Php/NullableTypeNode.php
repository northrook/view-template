<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php;

use Generator;

use Core\View\Template\Compiler\{Position, PrintContext};
class NullableTypeNode extends ComplexTypeNode
{
    public function __construct(
        public IdentifierNode|NameNode $type,
        public ?Position               $position = null,
    ) {}

    public function print( ?PrintContext $context ) : string
    {
        return '?'.$this->type->print( $context );
    }

    public function &getIterator() : Generator
    {
        yield $this->type;
    }
}
