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

    abstract public function print( ?PrintContext $context ) : string;

    /**
     * @return Generator<self>
     * @noinspection PhpBooleanCanBeSimplifiedInspection
     * @noinspection PhpInconsistentReturnPointsInspection
     */
    public function &getIterator() : Generator
    {
        false && yield;
    }

    /**
     * Walk a {@see Node} using the {@see NodeTraverser}.
     *
     * @param Node          $node
     * @param null|callable $enter
     * @param null|callable $leave
     *
     * @return Node
     */
    final public static function traverse(
        Node      $node,
        ?callable $enter = null,
        ?callable $leave = null,
    ) : Node {
        return ( new NodeTraverser() )->traverse( $node, $enter, $leave );
    }
}
