<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler;

use Core\View\Template\Compiler\Nodes\Php\{ExpressionNode, ParameterNode};
use Core\View\Template\Compiler\Nodes\Php\Scalar\{IntegerNode, StringNode};

/**
 * @internal
 */
final class Block
{
    public string $method;

    public string $content;

    public string $escaping;

    /** @var ParameterNode[] */
    public array $parameters = [];

    public function __construct(
        public readonly ExpressionNode $name,
        public readonly int|string     $layer,
        public readonly Tag            $tag,
    ) {}

    public function isDynamic() : bool
    {
        return ! $this->name instanceof StringNode
               && ! $this->name instanceof IntegerNode;
    }
}
