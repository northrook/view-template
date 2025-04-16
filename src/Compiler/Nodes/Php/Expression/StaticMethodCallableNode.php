<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php\Expression;

use Core\View\Template\Compiler\Nodes\Php\ExpressionNode;
use Generator;
use Core\View\Template\Compiler\{Nodes\Php\IdentifierNode, Nodes\Php\NameNode, Position, PrintContext};

class StaticMethodCallableNode extends ExpressionNode
{
    public function __construct(
        public NameNode|ExpressionNode       $class,
        public IdentifierNode|ExpressionNode $name,
        public ?Position                     $position = null,
    ) {}

    public function print( ?PrintContext $context ) : string
    {
        $name = match ( true ) {
            $this->name instanceof VariableNode   => $this->name->print( $context ),
            $this->name instanceof ExpressionNode => '{'.$this->name->print( $context ).'}',
            default                               => $this->name,
        };
        return PHP_VERSION_ID < 80_100
                ? '['.$this->class->print( $context ).', '.$context->memberAsString( $this->name ).']'
                : $context->dereferenceExpr( $this->class ).'::'.$name.'(...)';
    }

    public function &getIterator() : Generator
    {
        yield $this->class;
        yield $this->name;
    }
}
