<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php\Expression;

use Generator;

use Core\View\Template\Compiler\{Position, PrintContext};
use Core\View\Template\Compiler\Nodes\Php\{ArgumentNode, ExpressionNode, NameNode};

class FunctionCallNode extends ExpressionNode
{
    public function __construct(
        public NameNode|ExpressionNode $name,
        /** @var array<ArgumentNode> */
        public array                   $args = [],
        public ?Position               $position = null,
    ) {
        ( function( ArgumentNode ...$args ) {} )( ...$args );
    }

    public function print( PrintContext $context ) : string
    {
        return $context->callExpr( $this->name ).'('.$context->implode( $this->args ).')';
    }

    public function &getIterator() : Generator
    {
        yield $this->name;

        foreach ( $this->args as &$item ) {
            yield $item;
        }
    }
}
