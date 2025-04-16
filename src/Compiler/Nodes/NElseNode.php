<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Exception\CompileException;
use Generator;
use LogicException;

use Core\View\Template\Compiler\{Node, PrintContext, Tag};

/**
 * n:else
 */
final class NElseNode extends StatementNode
{
    public AreaNode $content;

    /**
     * @param Tag $tag
     *
     * @return Generator<int, ?array, array{AreaNode, ?Tag}, static>
     */
    public static function create( Tag $tag ) : Generator
    {
        $node            = $tag->node = new self();
        [$node->content] = yield;
        return $node;
    }

    public function print( ?PrintContext $context ) : string
    {
        throw new LogicException( 'Cannot directly print' );
    }

    public function &getIterator() : Generator
    {
        yield $this->content;
    }

    public static function processPass( Node $node ) : void
    {
        Node::traverse(
            $node,
            function( Node $node ) {
                if ( $node instanceof FragmentNode ) {
                    for ( $i = \count( $node->children ) - 1; $i >= 0; $i-- ) {
                        $nElse = $node->children[$i];
                        if ( ! $nElse instanceof self ) {
                            continue;
                        }

                        \array_splice( $node->children, $i, 1 );
                        $prev = $node->children[--$i] ?? null;
                        if ( $prev instanceof TextNode && \trim(
                            $prev->content,
                        ) === '' ) {
                            \array_splice( $node->children, $i, 1 );
                            $prev = $node->children[--$i] ?? null;
                        }

                        if (
                            $prev instanceof IfNode
                            || $prev instanceof ForeachNode
                            || $prev instanceof TryNode
                            || $prev instanceof IfChangedNode
                            || $prev instanceof IfContentNode
                        ) {
                            if ( $prev->else ) {
                                throw new CompileException( 'Multiple "else" found.', $nElse->position );
                            }
                            $prev->else = $nElse->content;
                        }
                        else {
                            throw new CompileException(
                                'n:else must be immediately after n:if, n:foreach etc',
                                $nElse->position,
                            );
                        }
                    }
                }
                elseif ( $node instanceof self ) {
                    throw new CompileException(
                        'n:else must be immediately after n:if, n:foreach etc',
                        $node->position,
                    );
                }
            },
        );
    }
}
