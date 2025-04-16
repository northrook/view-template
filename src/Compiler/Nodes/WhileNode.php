<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Compiler\Nodes\Php\ExpressionNode;
use Core\View\Template\Compiler\{PrintContext, Tag};
use Generator;

/**
 * {while $cond}
 */
class WhileNode extends StatementNode
{
    public ExpressionNode $condition;

    public AreaNode $content;

    public bool $postTest;

    /**
     * @param Tag $tag
     *
     * @return Generator<int, ?array, array{AreaNode, ?Tag}, static>
     * @throws \Core\View\Template\Exception\CompileException
     */
    public static function create( Tag $tag ) : Generator
    {
        $node           = $tag->node = new static();
        $node->postTest = $tag->parser->isEnd();
        if ( ! $node->postTest ) {
            $node->condition = $tag->parser->parseExpression();
        }

        [$node->content, $nextTag] = yield;
        if ( $node->postTest ) {
            $nextTag->expectArguments();
            $node->condition = $nextTag->parser->parseExpression();
        }

        return $node;
    }

    public function print( ?PrintContext $context ) : string
    {
        return $this->postTest
                ? $context->format(
                    <<<'XX'
                        do %line {
                        	%node
                        } while (%node);
                        XX,
                    $this->position,
                    $this->content,
                    $this->condition,
                )
                : $context->format(
                    <<<'XX'
                        while (%node) %line {
                        	%node
                        }
                        XX,
                    $this->condition,
                    $this->position,
                    $this->content,
                );
    }

    public function &getIterator() : Generator
    {
        yield $this->condition;
        yield $this->content;
    }
}
