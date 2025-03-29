<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Closure;
use Generator;

use Core\View\Template\Compiler\{Node, PrintContext};

class AuxiliaryNode extends AreaNode
{
    /**
     * @param Closure $print
     * @param ?Node[] $nodes
     */
    public function __construct(
        public readonly Closure $print,
        public array            $nodes = [],
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
