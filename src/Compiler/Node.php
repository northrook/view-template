<?php

declare(strict_types=1);

namespace Core\View\Template\Compiler;

use Generator;
use IteratorAggregate;

/**
 * @implements \IteratorAggregate<Node>
 */
abstract class Node implements IteratorAggregate
{
    public ?Position $position = null;

    abstract public function print( PrintContext $context ) : string;

    /**
     * @return Generator<self>
     */
    abstract public function &getIterator() : Generator;

    public static function traverse(
        Node      $node,
        ?callable $enter = null,
        ?callable $leave = null,
    ) : Node {
        return ( new NodeTraverser() )->traverse( $node, $enter, $leave );
    }
}
