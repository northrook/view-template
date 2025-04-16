<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Exception\CompileException;
use Core\View\Template\Compiler\Nodes\Php\Scalar\StringNode;
use Generator;
use Core\View\Template\Compiler\{Position, PrintContext, Tag, TagParser, TemplateGenerator, TemplateParser};
use Core\View\Template\Compiler\Nodes\{AreaNode, StatementNode};
use Core\View\Template\Compiler\Nodes\Php\{Expression, ExpressionNode};

/**
 * {if $cond} & {elseif $cond} & {else}
 * {if} & {/if $cond}
 * {ifset $var} & {elseifset $var}
 * {ifset block} & {elseifset block}
 */
class IfNode extends StatementNode
{
    public ExpressionNode $condition;

    public AreaNode $then;

    public ?AreaNode $else = null;

    public ?Position $elseLine = null;

    public bool $capture = false;

    public bool $ifset = false;

    /**
     * @param Tag            $tag
     * @param TemplateParser $parser
     *
     * @return Generator<int, ?array, array{AreaNode, ?Tag}, static>
     * @throws CompileException
     */
    public static function create( Tag $tag, TemplateParser $parser ) : Generator
    {
        $node           = $tag->node = new static();
        $node->ifset    = \in_array( $tag->name, ['ifset', 'elseifset'], true );
        $node->capture  = ! $tag->isNAttribute() && $tag->name === 'if' && $tag->parser->isEnd();
        $node->position = $tag->position;
        if ( ! $node->capture ) {
            $node->condition = $node->ifset
                    ? self::buildCondition( $tag->parser )
                    : $tag->parser->parseExpression();
        }

        [$node->then, $nextTag] = yield $node->capture ? ['else'] : ['else', 'elseif', 'elseifset'];

        if ( $nextTag?->name === 'else' ) {
            if ( $nextTag->parser->stream->is( 'if' ) ) {
                throw new CompileException(
                    'Arguments are not allowed in {else}, did you mean {elseif}?',
                    $nextTag->position,
                );
            }
            $node->elseLine         = $nextTag->position;
            [$node->else, $nextTag] = yield;
        }
        elseif ( $nextTag?->name === 'elseif' || $nextTag?->name === 'elseifset' ) {
            if ( $node->capture ) {
                throw new CompileException(
                    'Tag '.$nextTag->getNotation().' is unexpected here.',
                    $nextTag->position,
                );
            }
            $node->else = yield from self::create( $nextTag, $parser );
        }

        if ( $node->capture ) {
            $node->condition = $nextTag->parser->parseExpression();
        }

        return $node;
    }

    private static function buildCondition( TagParser $parser ) : ExpressionNode
    {
        $list = [];
        do {
            $block  = $parser->tryConsumeTokenBeforeUnquotedString( 'block' ) ?? $parser->stream->tryConsume( '#' );
            $name   = $parser->parseUnquotedStringOrExpression();
            $list[] = $block || $name instanceof StringNode
                    ? new Expression\AuxiliaryNode(
                        fn( PrintContext $context, ExpressionNode $name ) => '$this->hasBlock('.$name->print(
                            $context,
                        ).')',
                        [$name],
                    )
                    : new Expression\IssetNode( [$name] );
        }
        while ( $parser->stream->tryConsume( ',' ) );

        return Expression\BinaryOpNode::nest( '&&', ...$list );
    }

    public function print( ?PrintContext $context ) : string
    {
        return $this->capture
                ? $this->printCapturing( $context )
                : $this->printCommon( $context );
    }

    private function printCommon( PrintContext $context ) : string
    {
        if ( $this->else ) {
            return $context->format(
                ( $this->else instanceof self
                            ? "if (%node) %line { %node } else%node\n"
                            : "if (%node) %line { %node } else %4.line { %3.node }\n" ),
                $this->condition,
                $this->position,
                $this->then,
                $this->else,
                $this->elseLine,
            );
        }
        return $context->format(
            "if (%node) %line { %node }\n",
            $this->condition,
            $this->position,
            $this->then,
        );
    }

    private function printCapturing( PrintContext $context ) : string
    {
        $_if_a = TemplateGenerator::ARG_IF_A;
        $_if_b = TemplateGenerator::ARG_IF_B;

        if ( $this->else ) {
            return $context->format(
                <<<XX
                    ob_start(fn() => '') %line;
                    try {
                    	%node
                    	ob_start(fn() => '') %line;
                    	try {
                    		%node
                    	} finally {
                    		{$_if_b} = ob_get_clean();
                    	}
                    } finally {
                    	{$_if_a} = ob_get_clean();
                    }
                    echo (%node) ? {$_if_a} : {$_if_b} %0.line;
                    XX,
                $this->position,
                $this->then,
                $this->elseLine,
                $this->else,
                $this->condition,
            );
        }

        return $context->format(
            <<<XX
                ob_start(fn() => '') %line;
                try {
                	%node
                } finally {
                	{$_if_a} = ob_get_clean();
                }
                if (%node) %0.line { echo {$_if_a}; }
                XX,
            $this->position,
            $this->then,
            $this->condition,
        );
    }

    public function &getIterator() : Generator
    {
        yield $this->condition;
        yield $this->then;
        if ( $this->else ) {
            yield $this->else;
        }
    }
}
