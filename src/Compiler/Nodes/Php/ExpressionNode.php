<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php;

use Core\View\Template\Compiler\Node;
use Core\View\Template\Compiler\Nodes\Php\Expression\{ArrayAccessNode, FunctionCallNode, MethodCallNode, PropertyFetchNode, StaticMethodCallNode, StaticPropertyFetchNode, VariableNode};

abstract class ExpressionNode extends Node
{
    public function isWritable() : bool
    {
        return $this instanceof ArrayAccessNode
               || ( $this instanceof PropertyFetchNode && ! $this->nullsafe )
               || $this instanceof StaticPropertyFetchNode
               || $this instanceof VariableNode;
    }

    public function isVariable() : bool
    {
        return $this instanceof ArrayAccessNode
               || $this instanceof PropertyFetchNode
               || $this instanceof StaticPropertyFetchNode
               || $this instanceof VariableNode;
    }

    public function isCall() : bool
    {
        return $this instanceof FunctionCallNode
               || $this instanceof MethodCallNode
               || $this instanceof StaticMethodCallNode;
    }
}
