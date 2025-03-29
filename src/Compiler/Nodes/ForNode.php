<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Compiler\Nodes\Php\ExpressionNode;
use Generator;
use Core\View\Template\Compiler\{PrintContext, Tag};
use Core\View\Template\Compiler\Nodes\{AreaNode, StatementNode};
/**
 * {for $init; $cond; $next}
 */
class ForNode extends StatementNode
{
    /** @var ExpressionNode[] */
    public array $init = [];

    public ?ExpressionNode $condition;

    /** @var ExpressionNode[] */
    public array $next = [];

    public AreaNode $content;

    /**
     * @return Generator<int, ?array, array{AreaNode, ?Tag}, static>
     * @throws \Core\View\Template\Exception\CompileException
     */
    public static function create( Tag $tag ) : Generator
    {
        $tag->expectArguments();
        $stream = $tag->parser->stream;
        $node   = $tag->node = new static();
        while ( ! $stream->is( ';' ) ) {
            $node->init[] = $tag->parser->parseExpression();
            $stream->tryConsume( ',' );
        }

        $stream->consume( ';' );
        $node->condition = $stream->is( ';' ) ? null : $tag->parser->parseExpression();
        $stream->consume( ';' );
        while ( ! $tag->parser->isEnd() ) {
            $node->next[] = $tag->parser->parseExpression();
            $stream->tryConsume( ',' );
        }

        [$node->content] = yield;
        return $node;
    }

    public function print( PrintContext $context ) : string
    {
        return $context->format(
            <<<'XX'
                for (%args; %node; %args) %line {
                	%node
                }
                XX,
            $this->init,
            $this->condition,
            $this->next,
            $this->position,
            $this->content,
        );
    }

    public function &getIterator() : Generator
    {
        foreach ( $this->init as &$item ) {
            yield $item;
        }

        if ( $this->condition ) {
            yield $this->condition;
        }

        foreach ( $this->next as &$item ) {
            yield $item;
        }

        yield $this->content;
    }
}
