<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Engine\TranslatorExtension;
use Core\View\Template\Compiler\{NodeHelpers, PrintContext, Tag, TemplateGenerator};
use Core\View\Template\Compiler\Nodes\{AreaNode, NopNode, Php\FilterNode, Php\IdentifierNode, StatementNode, TextNode};
use Core\View\Template\Compiler\Nodes\Php\ModifierNode;
use Generator;

/**
 * {translate} ... {/translate}
 */
class TranslateNode extends StatementNode
{
    public AreaNode $content;

    public ModifierNode $modifier;

    /**
     * @param Tag       $tag
     * @param ?callable $translator
     *
     * @return Generator<int, ?array, array{AreaNode, ?Tag}, NopNode|static>
     * @throws \Core\View\Template\Exception\CompileException
     */
    public static function create( Tag $tag, ?callable $translator ) : Generator
    {
        $tag->outputMode = $tag::OutputKeepIndentation;

        $node                   = $tag->node = new static();
        $args                   = $tag->parser->parseArguments();
        $node->modifier         = $tag->parser->parseModifier();
        $node->modifier->escape = true;
        if ( $tag->void ) {
            return new NopNode();
        }

        [$node->content] = yield;

        if ( $text = NodeHelpers::toText( $node->content ) ) {
            if ( $translator
                 && \is_array( $values = TranslatorExtension::toValue( $args ) )
                 && \is_string( $translation = $translator( $text, ...$values ) )
            ) {
                $node->content = new TextNode( $translation );
                return $node;
            }
            $node->content = new TextNode( $text );
        }

        \array_unshift(
            $node->modifier->filters,
            new FilterNode(
                new IdentifierNode( 'translate' ),
                $args->toArguments(),
            ),
        );

        return $node;
    }

    public function print( PrintContext $context ) : string
    {
        $tmp = TemplateGenerator::ARG_TEMP;
        $fl  = TemplateGenerator::ARG_FILTER;
        $ns  = TemplateGenerator::NAMESPACE;
        if ( $this->content instanceof TextNode ) {
            return $context->format(
                <<<XX
                    {$fl} = new {$ns}\FilterInfo(%dump);
                    echo %modifyContent(%dump) %line;
                    XX,
                $context->getEscaper()->export(),
                $this->modifier,
                $this->content->content,
                $this->position,
            );
        }

        $ns = TemplateGenerator::NAMESPACE;
        return $context->format(
            <<<XX
                ob_start(fn() => ''); try {
                	%node
                } finally {
                	{$tmp} = ob_get_clean();
                }
                {$fl} = new {$ns}\FilterInfo(%dump);
                echo %modifyContent({$tmp}) %line;
                XX,
            $this->content,
            $context->getEscaper()->export(),
            $this->modifier,
            $this->position,
        );
    }

    public function &getIterator() : Generator
    {
        yield $this->content;
        yield $this->modifier;
    }
}
