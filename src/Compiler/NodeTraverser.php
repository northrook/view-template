<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler;

final class NodeTraverser
{
    public const int DontTraverseChildren = 1;

    public const int StopTraversal = 2;

    /** @var ?callable(Node): (null|int|Node) */
    private $enter;

    /** @var ?callable(Node): (null|int|Node) */
    private $leave;

    private bool $stop;

    public function traverse( Node $node, ?callable $enter = null, ?callable $leave = null ) : Node
    {
        $this->enter = $enter;
        $this->leave = $leave;
        $this->stop  = false;
        return $this->traverseNode( $node );
    }

    private function traverseNode( Node $node ) : Node
    {
        $children = true;
        if ( $this->enter ) {
            $res = ( $this->enter )( $node );
            if ( $res instanceof Node ) {
                $node = $res;
            }
            elseif ( $res === self::DontTraverseChildren ) {
                $children = false;
            }
            elseif ( $res === self::StopTraversal ) {
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
            $res = ( $this->leave )( $node );
            if ( $res instanceof Node ) {
                $node = $res;
            }
            elseif ( $res === self::StopTraversal ) {
                $this->stop = true;
            }
        }

        return $node;
    }
}
