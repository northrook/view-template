<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Compiler\{Block, PrintContext, Tag, TemplateGenerator, TemplateParser};
use Core\View\Template\Compiler\Nodes\BlockNode;
use Core\View\Template\Compiler\Nodes\DefineNode;
use Core\View\Template\Exception\CompileException;
use Core\View\Template\Runtime\Template;
use Core\View\Template\Support\PhpGenerator;
use Core\View\Template\Compiler\Nodes\Php\{ExpressionNode, ModifierNode, Scalar};
use Core\View\Template\Compiler\Nodes\Php\Expression\ArrayNode;
use Core\View\Template\Compiler\Nodes\StatementNode;
use Generator;

/**
 * {include [block] name [from file] [, args]}
 */
class IncludeBlockNode extends StatementNode
{
    public ExpressionNode $name;

    public ?ExpressionNode $from = null;

    public ArrayNode $args;

    public ModifierNode $modifier;

    public int|string|null $layer;

    public bool $parent = false;

    /** @var Block[][] */
    public array $blocks;

    /**
     * @param Tag            $tag
     * @param TemplateParser $parser
     *
     * @return IncludeBlockNode
     * @throws CompileException
     */
    public static function create( Tag $tag, TemplateParser $parser ) : static
    {
        $tag->outputMode = $tag::OutputRemoveIndentation;

        $tag->expectArguments();
        $node = new static();
        $tag->parser->tryConsumeTokenBeforeUnquotedString( 'block' ) ?? $tag->parser->stream->tryConsume( '#' );
        $node->name = $tag->parser->parseUnquotedStringOrExpression();
        $tokenName  = $tag->parser->stream->peek( -1 );

        $stream = $tag->parser->stream;
        if ( $stream->tryConsume( 'from' ) ) {
            $node->from = $tag->parser->parseUnquotedStringOrExpression();
            $tag->parser->stream->tryConsume( ',' );
        }

        $stream->tryConsume( ',' );
        $node->args             = $tag->parser->parseArguments();
        $node->modifier         = $tag->parser->parseModifier();
        $node->modifier->escape = (bool) $node->modifier->filters;

        $node->parent = $tokenName->is( 'parent' );
        if ( $node->parent && $node->modifier->filters ) {
            throw new CompileException( 'Filters are not allowed in {include parent}', $tag->position );
        }
        if ( $node->parent || $tokenName->is( 'this' ) ) {
            $item = $tag->closestTag(
                [BlockNode::class, DefineNode::class],
                fn( $item ) => $item->node?->block && ! $item->node->block->isDynamic(
                ) && $item->node->block->name !== '',
            );
            if ( ! $item ) {
                throw new CompileException(
                    "Cannot include {$tokenName->text} block outside of any block.",
                    $tag->position,
                );
            }

            $node->name = $item->node->block->name;
        }

        $node->blocks = &$parser->blocks;
        $node->layer  = $parser->blockLayer;
        return $node;
    }

    public function print( PrintContext $context ) : string
    {
        $fl       = TemplateGenerator::ARG_FILTER;
        $ns       = TemplateGenerator::NAMESPACE;
        $noEscape = $this->modifier->hasFilter( 'noescape' );
        $modArg   = \count( $this->modifier->filters ) > (int) $noEscape
                ? $context->format(
                    "function (\$s, \$type) { {$fl} = new {$ns}\FilterInfo(\$type); return %modifyContent(\$s); }",
                    $this->modifier,
                )
                : ( $noEscape || $this->parent ? '' : PhpGenerator::dump( $context->getEscaper()->export() ) );

        return $this->from
                ? $this->printBlockFrom( $context, $modArg )
                : $this->printBlock( $context, $modArg );
    }

    private function printBlock( PrintContext $context, string $modArg ) : string
    {
        if ( $this->name instanceof Scalar\StringNode || $this->name instanceof Scalar\IntegerNode ) {
            $staticName = (string) $this->name->value;
            $block
                    = $this->blocks[$this->layer][$staticName] ?? $this->blocks[Template::LAYER_LOCAL][$staticName] ?? null;
        }

        return $context->format(
            '$this->renderBlock'.( $this->parent ? 'Parent' : '' )
                .'(%node, %node? + '
                .( isset( $block ) && ! $block->parameters ? 'get_defined_vars()' : '[]' )
                .'%raw) %line;',
            $this->name,
            $this->args,
            $modArg ? ", {$modArg}" : '',
            $this->position,
        );
    }

    private function printBlockFrom( PrintContext $context, string $modArg ) : string
    {
        return $context->format(
            '$this->createTemplate(%node, %node? + $this->params, "include")->renderToContentType(%raw, %node) %line;',
            $this->from,
            $this->args,
            $modArg,
            $this->name,
            $this->position,
        );
    }

    public function &getIterator() : Generator
    {
        yield $this->name;
        if ( $this->from ) {
            yield $this->from;
        }
        yield $this->args;
        yield $this->modifier;
    }
}
