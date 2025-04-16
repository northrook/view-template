<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Compiler\Nodes\Php\Expression\ArrayNode;
use Core\View\Template\Exception\CompileException;
use Generator;
use Core\View\Template\Compiler\{Position, PrintContext, Tag, TemplateGenerator};
use Core\View\Template\Compiler\Nodes\{AreaNode, StatementNode};

/**
 * {ifchanged [$var]} ... {else}
 */
class IfChangedNode extends StatementNode
{
    public ArrayNode $conditions;

    public AreaNode $then;

    public ?AreaNode $else = null;

    public ?Position $elseLine = null;

    /**
     * @param Tag $tag
     *
     * @return Generator<int, ?array, array{AreaNode, ?Tag}, static>
     * @throws CompileException
     */
    public static function create( Tag $tag ) : Generator
    {
        $node             = $tag->node = new static();
        $node->conditions = $tag->parser->parseArguments();

        [$node->then, $nextTag] = yield ['else'];
        if ( $nextTag?->name === 'else' ) {
            $node->elseLine = $nextTag->position;
            [$node->else]   = yield;
        }

        return $node;
    }

    public function print( ?PrintContext $context ) : string
    {
        return $this->conditions->items
                ? $this->printExpression( $context )
                : $this->printCapturing( $context );
    }

    private function printExpression( PrintContext $context ) : string
    {
        $tmp = TemplateGenerator::ARG_TEMP;
        $loc = TemplateGenerator::ARG_LOC;
        return $this->else
                ? $context->format(
                    <<<XX
                        if (({$loc}[%dump] ?? null) !== ({$tmp} = %node)) {
                        	{$loc}[%0.dump] = {$tmp};
                        	%node
                        } else %line {
                        	%node
                        }
                        XX,
                    $context->generateId(),
                    $this->conditions,
                    $this->then,
                    $this->elseLine,
                    $this->else,
                )
                : $context->format(
                    <<<XX
                        if (({$loc}[%dump] ?? null) !== ({$tmp} = %node)) {
                        	{$loc}[%0.dump] = {$tmp};
                        	%2.node
                        }
                        XX,
                    $context->generateId(),
                    $this->conditions,
                    $this->then,
                );
    }

    private function printCapturing( PrintContext $context ) : string
    {
        $tmp = TemplateGenerator::ARG_TEMP;
        $loc = TemplateGenerator::ARG_LOC;
        return $this->else
                ? $context->format(
                    <<<XX
                        ob_start(fn() => '');
                        try %line {
                        	%node
                        } finally { {$tmp} = ob_get_clean(); }
                        if (({$loc}[%dump] ?? null) !== {$tmp}) {
                        	echo {$loc}[%2.dump] = {$tmp};
                        } else %line {
                        	%node
                        }
                        XX,
                    $this->position,
                    $this->then,
                    $context->generateId(),
                    $this->elseLine,
                    $this->else,
                )
                : $context->format(
                    <<<XX
                        ob_start(fn() => '');
                        try %line {
                        	%node
                        } finally { {$tmp} = ob_get_clean(); }
                        if (({$loc}[%dump] ?? null) !== {$tmp}) {
                        	echo {$loc}[%2.dump] = {$tmp};
                        }
                        XX,
                    $this->position,
                    $this->then,
                    $context->generateId(),
                );
    }

    public function &getIterator() : Generator
    {
        yield $this->conditions;
        yield $this->then;
        if ( $this->else ) {
            yield $this->else;
        }
    }
}
