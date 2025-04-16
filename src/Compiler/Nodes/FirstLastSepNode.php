<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Compiler\Nodes\Php\ExpressionNode;
use Generator;
use Core\View\Template\Compiler\{Position, PrintContext, Tag};
use Core\View\Template\Compiler\Nodes\{AreaNode, StatementNode};

/**
 * {first [$width]}
 * {last [$width]}
 * {sep [$width]}
 */
class FirstLastSepNode extends StatementNode
{
    public string $name;

    public ?ExpressionNode $width;

    public AreaNode $then;

    public ?AreaNode $else = null;

    public ?Position $elseLine = null;

    /**
     * @param Tag $tag
     *
     * @return Generator<int, ?array, array{AreaNode, ?Tag}, static>
     * @throws \Core\View\Template\Exception\CompileException
     */
    public static function create( Tag $tag ) : Generator
    {
        $node        = $tag->node = new static();
        $node->name  = $tag->name;
        $node->width = $tag->parser->isEnd() ? null : $tag->parser->parseExpression();

        [$node->then, $nextTag] = yield ['else'];
        if ( $nextTag?->name === 'else' ) {
            $node->elseLine = $nextTag->position;
            [$node->else]   = yield;
        }

        return $node;
    }

    public function print( ?PrintContext $context ) : string
    {
        $cond = match ( $this->name ) {
            'first' => '$iterator->isFirst',
            'last'  => '$iterator->isLast',
            'sep'   => '!$iterator->isLast',
        };
        return $context->format(
            $this->else
                        ? "if ({$cond}(%node)) %line { %node } else %line { %node }\n"
                        : "if ({$cond}(%node)) %line { %node }\n",
            $this->width,
            $this->position,
            $this->then,
            $this->elseLine,
            $this->else,
        );
    }

    public function &getIterator() : Generator
    {
        if ( $this->width ) {
            yield $this->width;
        }
        yield $this->then;
        if ( $this->else ) {
            yield $this->else;
        }
    }
}
