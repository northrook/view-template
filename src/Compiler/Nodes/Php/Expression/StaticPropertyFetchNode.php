<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php\Expression;

use Core\View\Template\Compiler\Nodes\Php\{ExpressionNode, NameNode, VarLikeIdentifierNode};
use Core\View\Template\Compiler\{Position, PrintContext};
use Generator;

class StaticPropertyFetchNode extends ExpressionNode
{
    public function __construct(
        public NameNode|ExpressionNode              $class,
        public VarLikeIdentifierNode|ExpressionNode $name,
        public ?Position                            $position = null,
    ) {}

    public function print( ?PrintContext $context ) : string
    {
        return $context->dereferenceExpr( $this->class )
               .'::$'
               .$context->objectProperty( $this->name );
    }

    public function &getIterator() : Generator
    {
        yield $this->class;
        yield $this->name;
    }
}
