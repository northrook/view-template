<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php\Expression;

use Core\View\Template\Compiler\{Node, PrintContext};
use Core\View\Template\Compiler\Nodes\Php\ExpressionNode;
use Closure;
use Generator;
class AuxiliaryNode extends ExpressionNode
{
    public function __construct(
        public /* readonly */ Closure $print,
        /** @var (?Node)[] */
        public array   $nodes = [],
    ) {
        ( function( ?Node ...$nodes ) {} )( ...$nodes );
    }

    public function print( PrintContext $context ) : string
    {
        return ( $this->print )( $context, ...$this->nodes );
    }

    public function &getIterator() : Generator
    {
        foreach ( $this->nodes as &$node ) {
            if ( $node ) {
                yield $node;
            }
        }
    }
}
