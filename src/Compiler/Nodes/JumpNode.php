<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Exception\CompileException;
use Core\View\Template\Compiler\Nodes\Php\ExpressionNode;
use Generator;
use Core\View\Template\Compiler\{PrintContext, Tag};

/**
 * {breakIf ...}
 * {continueIf ...}
 * {skipIf ...}
 * {exitIf ...}
 */
class JumpNode extends StatementNode
{
    public string $type;

    public ExpressionNode $condition;

    /**
     * @param Tag $tag
     *
     * @return JumpNode
     * @throws CompileException
     */
    public static function create( Tag $tag ) : static
    {
        $tag->expectArguments();
        $tag->outputMode = $tag->name === 'exitIf' // to not be in prepare()
                ? $tag::OutputRemoveIndentation
                : $tag::OutputNone;

        for (
            $parent = $tag->parent;
            $parent?->node instanceof IfNode || $parent?->node instanceof IfContentNode;
            $parent = $parent->parent
        ) {
            //
        }
        $pnode = $parent?->node;
        if ( ! match ( $tag->name ) {
            'breakIf', 'continueIf' => $pnode instanceof ForNode || $pnode instanceof ForeachNode || $pnode instanceof WhileNode,
            'skipIf' => $pnode instanceof ForeachNode,
            'exitIf' => ! $pnode || $pnode instanceof BlockNode || $pnode instanceof DefineNode,
        } ) {
            throw new CompileException( "Tag {{$tag->name}} is unexpected here.", $tag->position );
        }

        $last = $parent?->prefix === Tag::PrefixNone
                ? $parent->htmlElement->parent
                : $parent?->htmlElement;
        $el = $tag->htmlElement;

        while ( $el && $el !== $last ) {
            $el->breakable = true;
            $el            = $el->parent;
        }

        $node            = new static();
        $node->type      = $tag->name;
        $node->condition = $tag->parser->parseExpression();
        return $node;
    }

    public function print( PrintContext $context ) : string
    {
        return $context->format(
            "if (%node) %line %raw\n",
            $this->condition,
            $this->position,
            match ( $this->type ) {
                'breakIf'    => 'break;',
                'continueIf' => 'continue;',
                'skipIf'     => '{ $iterator->skipRound(); continue; }',
                'exitIf'     => 'return;',
            },
        );
    }

    public function &getIterator() : Generator
    {
        yield $this->condition;
    }
}
