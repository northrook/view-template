<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php\Expression;

use Generator;

use Core\View\Template\Compiler\{Position, PrintContext};
use Core\View\Template\Compiler\Nodes\Php\{ExpressionNode, IdentifierNode};

class PropertyFetchNode extends ExpressionNode
{
    public function __construct(
        public ExpressionNode                $object,
        public IdentifierNode|ExpressionNode $name,
        public bool                          $nullsafe = false,
        public ?Position                     $position = null,
    ) {}

    public function print( PrintContext $context ) : string
    {
        return $context->dereferenceExpr( $this->object )
               .( $this->nullsafe ? '?->' : '->' )
               .$context->objectProperty( $this->name );
    }

    public function &getIterator() : Generator
    {
        yield $this->object;
        yield $this->name;
    }
}
