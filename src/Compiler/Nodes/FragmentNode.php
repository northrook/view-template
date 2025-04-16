<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Compiler\PrintContext;
use Generator;

final class FragmentNode extends AreaNode
{
    /** @var AreaNode[] */
    public array $children = [];

    /**
     * @param AreaNode[] $children
     */
    public function __construct( array $children = [] )
    {
        foreach ( $children as $child ) {
            $this->append( $child );
        }
    }

    public function append( AreaNode $node ) : static
    {
        if ( $node instanceof self ) {
            $this->children = \array_merge( $this->children, $node->children );
        }
        elseif ( ! $node instanceof NopNode ) {
            $this->children[] = $node;
        }
        $this->position ??= $node->position;
        return $this;
    }

    public function simplify( bool $allowsNull = true ) : ?AreaNode
    {
        return match ( true ) {
            ! $this->children               => $allowsNull ? null : $this,
            \count( $this->children ) === 1 => $this->children[0],
            default                         => $this,
        };
    }

    public function print( ?PrintContext $context ) : string
    {
        $context ??= new PrintContext();
        $output = '';

        foreach ( $this->children as $child ) {
            $output .= $child->print( $context );
        }

        return $output;
    }

    public function &getIterator() : Generator
    {
        foreach ( $this->children as &$item ) {
            yield $item;
        }
    }
}
