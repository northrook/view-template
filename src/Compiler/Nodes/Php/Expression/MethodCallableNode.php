<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php\Expression;

use Core\View\Template\Compiler\Nodes\Php\ExpressionNode;
use Core\View\Template\Compiler\{Nodes\Php\IdentifierNode, Position, PrintContext};
use Generator;

class MethodCallableNode extends ExpressionNode
{
    public function __construct(
        public ExpressionNode                $object,
        public IdentifierNode|ExpressionNode $name,
        public ?Position                     $position = null,
    ) {}

    public function print( PrintContext $context ) : string
    {
        return PHP_VERSION_ID < 80_100
                ? '['.$this->object->print( $context ).', '.$context->memberAsString( $this->name ).']'
                : $context->dereferenceExpr( $this->object )
                  .'->'.$context->objectProperty( $this->name ).'(...)';
    }

    public function &getIterator() : Generator
    {
        yield $this->object;
        yield $this->name;
    }
}
