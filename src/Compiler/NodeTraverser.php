<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler;

final class NodeTraverser
{
    // DontTraverseChildren
    public const int CONTINUE = 1;

    // StopTraversal
    public const int BREAK = 2;

    /** @var ?callable(Node): (null|int|Node) */
    private $enter;

    /** @var ?callable(Node): (null|int|Node) */
    private $leave;

    private bool $stop = false;

    public function traverse(
        Node      $node,
        ?callable $enter = null,
        ?callable $leave = null,
    ) : Node {
        $this->enter = $enter;
        $this->leave = $leave;
        return $this->traverseNode( $node );
    }

    private function traverseNode( Node $node ) : Node
    {
        $children = true;
        if ( $this->enter ) {
            $step = ( $this->enter )( $node );
            if ( $step instanceof Node ) {
                $node = $step;
            }
            elseif ( $step === $this::CONTINUE ) {
                $children = false;
            }
            elseif ( $step === $this::BREAK ) {
                $this->stop = true;
                $children   = false;
            }
        }

        if ( $children ) {
            foreach ( $node as &$subnode ) {
                $subnode = $this->traverseNode( $subnode );
                if ( $this->stop ) {
                    break;
                }
            }
        }

        if ( ! $this->stop && $this->leave ) {
            $step = ( $this->leave )( $node );
            if ( $step instanceof Node ) {
                $node = $step;
            }
            elseif ( $step === $this::BREAK ) {
                $this->stop = true;
            }
        }

        return $node;
    }
}
