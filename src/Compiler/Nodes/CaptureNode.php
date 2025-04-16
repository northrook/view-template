<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Exception\CompileException;
use Generator;
use Core\View\Template\Compiler\{Escaper, PrintContext, Tag, TemplateGenerator};
use Core\View\Template\Compiler\Nodes\Php\{ExpressionNode, ModifierNode};

/**
 * {capture $variable}
 */
class CaptureNode extends StatementNode
{
    public ExpressionNode $variable;

    public ModifierNode $modifier;

    public AreaNode $content;

    /**
     * @param Tag $tag
     *
     * @return Generator<int, ?array, array{AreaNode, ?Tag}, static>
     * @throws CompileException
     */
    public static function create( Tag $tag ) : Generator
    {
        $tag->expectArguments();
        $variable = $tag->parser->parseExpression();
        if ( ! $variable->isWritable() ) {
            $text = '';
            $i    = 0;
            while ( $token = $tag->parser->stream->peek( --$i ) ) {
                $text = $token->text.$text;
            }

            throw new CompileException(
                "It is not possible to write into '{$text}' in ".$tag->getNotation(),
                $tag->position,
            );
        }
        $node            = $tag->node = new static();
        $node->variable  = $variable;
        $node->modifier  = $tag->parser->parseModifier();
        [$node->content] = yield;
        return $node;
    }

    public function print( ?PrintContext $context ) : string
    {
        $tmp     = TemplateGenerator::ARG_TEMP;
        $fl      = TemplateGenerator::ARG_FILTER;
        $ns      = TemplateGenerator::NAMESPACE;
        $escaper = $context->getEscaper();
        return $context->format(
            <<<XX
                ob_start(fn() => '') %line;
                try {
                	%node
                } finally {
                	{$tmp} = %raw;
                }
                {$fl} = new {$ns}\FilterInfo(%dump); %node = %modifyContent({$tmp});
                XX,
            $this->position,
            $this->content,
            $escaper->getState() === Escaper::HtmlText
                        ? "ob_get_length() ? new {$ns}\Html(ob_get_clean()) : ob_get_clean()"
                        : 'ob_get_clean()',
            $escaper->export(),
            $this->variable,
            $this->modifier,
        );
    }

    public function &getIterator() : Generator
    {
        yield $this->variable;
        yield $this->modifier;
        yield $this->content;
    }
}
