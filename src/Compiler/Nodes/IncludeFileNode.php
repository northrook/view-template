<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Compiler\Nodes\Php\Expression\ArrayNode;
use Core\View\Template\Support\PhpGenerator;
use Generator;
use Core\View\Template\Compiler\{PrintContext, Tag, TemplateGenerator};
use Core\View\Template\Compiler\Nodes\Php\{ExpressionNode, ModifierNode};

/**
 * {include [file] "file" [with blocks] [,] [params]}
 */
class IncludeFileNode extends StatementNode
{
    public ExpressionNode $file;

    public ArrayNode $args;

    public ModifierNode $modifier;

    public string $mode;

    /**
     * @param Tag $tag
     *
     * @return IncludeFileNode
     * @throws \Core\View\Template\Exception\CompileException
     */
    public static function create( Tag $tag ) : static
    {
        $tag->outputMode = $tag::OutputRemoveIndentation;

        $tag->expectArguments();
        $node = new static();
        $tag->parser->tryConsumeTokenBeforeUnquotedString( 'file' );
        $node->file = $tag->parser->parseUnquotedStringOrExpression();
        $node->mode = 'include';

        $stream = $tag->parser->stream;
        if ( $stream->tryConsume( 'with' ) ) {
            $stream->consume( 'blocks' );
            $node->mode = 'includeblock';
        }

        $stream->tryConsume( ',' );
        $node->args             = $tag->parser->parseArguments();
        $node->modifier         = $tag->parser->parseModifier();
        $node->modifier->escape = (bool) $node->modifier->filters;
        return $node;
    }

    public function print( PrintContext $context ) : string
    {
        $noEscape = $this->modifier->hasFilter( 'noescape' );

        $fl = TemplateGenerator::ARG_FILTER;
        $ns = TemplateGenerator::NAMESPACE;
        return $context->format(
            '$this->createTemplate(%node, %node? + $this->parameters, %dump)->renderToContentType(%raw) %line;',
            $this->file,
            $this->args,
            $this->mode,
            \count( $this->modifier->filters ) > (int) $noEscape
                        ? $context->format(
                            'function ($s, $type) { '.$fl.' = new '.$ns.'\FilterInfo($type); return %modifyContent($s); }',
                            $this->modifier,
                        )
                        : PhpGenerator::dump( $noEscape ? null : $context->getEscaper()->export() ),
            $this->position,
        );
    }

    public function &getIterator() : Generator
    {
        yield $this->file;
        yield $this->args;
        yield $this->modifier;
    }
}
