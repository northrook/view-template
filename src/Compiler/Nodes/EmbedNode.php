<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Compiler\Nodes\BlockNode;
use Core\View\Template\Compiler\Nodes\ImportNode;
use Core\View\Template\Exception\CompileException;
use Core\View\Template\Compiler\Nodes\Php\Expression\ArrayNode;
use Core\View\Template\Compiler\Nodes\Php\ExpressionNode;
use Core\View\Template\Compiler\Nodes\Php\Scalar\StringNode;
use Generator;
use Core\View\Template\Compiler\{PrintContext, Tag, TemplateParser};
use Core\View\Template\Compiler\Nodes\{FragmentNode, StatementNode, TextNode};

/**
 * {embed [block|file] name [,] [params]}
 */
class EmbedNode extends StatementNode
{
    public ExpressionNode $name;

    public string $mode;

    public ArrayNode $args;

    public FragmentNode $blocks;

    public int|string|null $layer;

    /**
     * @return Generator<int, ?array, array{FragmentNode, ?Tag}, static>
     * @throws CompileException
     * @param  Tag                                                       $tag
     * @param  TemplateParser                                            $parser
     */
    public static function create( Tag $tag, TemplateParser $parser ) : Generator
    {
        if ( $tag->isNAttribute() ) {
            throw new CompileException( 'Attribute n:embed is not supported.', $tag->position );
        }

        $tag->outputMode = $tag::OutputRemoveIndentation;
        $tag->expectArguments();

        $node       = $tag->node = new static();
        $mode       = $tag->parser->tryConsumeTokenBeforeUnquotedString( 'block', 'file' )?->text;
        $node->name = $tag->parser->parseUnquotedStringOrExpression();
        $node->mode = $mode ?? ( $node->name instanceof StringNode && \preg_match( '~[\w-]+$~DA', $node->name->value )
                ? 'block' : 'file' );
        $tag->parser->stream->tryConsume( ',' );
        $node->args = $tag->parser->parseArguments();

        $prevIndex                           = $parser->blockLayer;
        $parser->blockLayer                  = $node->layer = \count( $parser->blocks );
        $parser->blocks[$parser->blockLayer] = [];
        [$node->blocks]                      = yield;

        foreach ( $node->blocks->children as $child ) {
            if ( !$child instanceof \Core\View\Template\Compiler\Nodes\ImportNode && !$child instanceof \Core\View\Template\Compiler\Nodes\BlockNode && !$child instanceof TextNode ) {
                throw new CompileException( 'Unexpected content inside {embed} tags.', $child->position );
            }
        }

        $parser->blockLayer = $prevIndex;
        return $node;
    }

    public function print( ?PrintContext $context ) : string
    {
        $imports = '';

        foreach ( $this->blocks->children as $child ) {
            if ( $child instanceof ImportNode ) {
                $imports .= $child->print( $context );
            }
            else {
                $child->print( $context );
            }
        }

        return $this->mode === 'file'
                ? $context->format(
                    <<<'XX'
                        $this->enterBlockLayer(%dump, get_defined_vars()) %line; %raw
                        try {
                        	$this->createTemplate(%node, %node, "embed")->renderToContentType(%dump) %1.line;
                        } finally {
                        	$this->leaveBlockLayer();
                        }
                        XX,
                    $this->layer,
                    $this->position,
                    $imports,
                    $this->name,
                    $this->args,
                    $context->getEscaper()->export(),
                )
                : $context->format(
                    <<<'XX'
                        $this->enterBlockLayer(%dump, get_defined_vars()) %line; %raw
                        $this->copyBlockLayer();
                        try {
                        	$this->renderBlock(%node, %node, %dump) %1.line;
                        } finally {
                        	$this->leaveBlockLayer();
                        }
                        XX,
                    $this->layer,
                    $this->position,
                    $imports,
                    $this->name,
                    $this->args,
                    $context->getEscaper()->export(),
                );
    }

    public function &getIterator() : Generator
    {
        yield $this->name;
        yield $this->args;
        yield $this->blocks;
    }
}
