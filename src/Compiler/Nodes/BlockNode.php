<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Exception\CompileException;
use Core\View\Template\Compiler\{Block, Escaper, PrintContext, Tag, TemplateGenerator, TemplateParser, Token};
use Core\View\Template\Compiler\Nodes\{AreaNode, StatementNode};
use Core\View\Template\Compiler\Nodes\Php\{ModifierNode, Scalar};
use Core\View\Template\Compiler\Nodes\Php\Expression\{AssignNode, VariableNode};
use Core\View\Template\Runtime\Template;
use Generator;

/**
 * {block [local] [name]}
 */
class BlockNode extends StatementNode
{
    public ?Block $block = null;

    public ModifierNode $modifier;

    public AreaNode $content;

    /**
     * @param Tag            $tag
     * @param TemplateParser $parser
     *
     * @return Generator<int, ?array, array{AreaNode, ?Tag}, AreaNode|static>
     * @throws CompileException
     */
    public static function create( Tag $tag, TemplateParser $parser ) : Generator
    {
        $tag->outputMode = $tag::OutputRemoveIndentation;
        $stream          = $tag->parser->stream;
        $node            = $tag->node = new static();
        $name            = null;

        if ( ! $stream->is( '|', Token::End ) ) {
            $layer = $tag->parser->tryConsumeTokenBeforeUnquotedString( 'local' )
                    ? Template::LAYER_LOCAL
                    : $parser->blockLayer;
            $stream->tryConsume( '#' );
            $name        = $tag->parser->parseUnquotedStringOrExpression();
            $node->block = new Block( $name, $layer, $tag );

            if ( ! $node->block->isDynamic() ) {
                $parser->checkBlockIsUnique( $node->block );
            }
        }

        $node->modifier         = $tag->parser->parseModifier();
        $node->modifier->escape = (bool) $node->modifier->filters;
        if ( $node->modifier->hasFilter( 'noescape' ) && \count( $node->modifier->filters ) === 1 ) {
            throw new CompileException( 'Filter |noescape is not expected here.', $tag->position );
        }

        [$node->content, $endTag] = yield;

        if ( $node->block && $endTag && $name instanceof Scalar\StringNode ) {
            $endTag->parser->stream->tryConsume( $name->value );
        }

        return $node;
    }

    public function print( PrintContext $context ) : string
    {
        if ( ! $this->block ) {
            return $this->printFilter( $context );
        }
        if ( $this->block->isDynamic() ) {
            return $this->printDynamic( $context );
        }

        return $this->printStatic( $context );
    }

    private function printFilter( PrintContext $context ) : string
    {
        $tmp = TemplateGenerator::ARG_TEMP;
        $fl  = TemplateGenerator::ARG_FILTER;
        $ns  = TemplateGenerator::NAMESPACE;
        return $context->format(
            <<<XX
                ob_start(fn() => '') %line;
                try {
                	(function () { extract(func_get_arg(0));
                		%node
                	})(get_defined_vars());
                } finally {
                	{$fl} = new {$ns}\FilterInfo(%dump);
                	echo %modifyContent(ob_get_clean());
                }
                XX,
            $this->position,
            $this->content,
            $context->getEscaper()->export(),
            $this->modifier,
        );
    }

    private function printStatic( PrintContext $context ) : string
    {
        $this->modifier->escape
                = $this->modifier->escape || $context->getEscaper()->getState() === Escaper::HtmlAttribute;
        $context->addBlock( $this->block );
        $this->block->content = $this->content->print( $context ); // must be compiled after is added

        $tmp = TemplateGenerator::ARG_TEMP;
        $fl  = TemplateGenerator::ARG_FILTER;
        $ns  = TemplateGenerator::NAMESPACE;
        return $context->format(
            '$this->renderBlock(%node, get_defined_vars()'
                .( $this->modifier->filters || $this->modifier->escape
                        ? ', function ($s, $type) { '.$fl.' = new '.$ns.'\FilterInfo($type); return %modifyContent($s); }'
                        : '' )
                .') %2.line;',
            $this->block->name,
            $this->modifier,
            $this->position,
        );
    }

    private function printDynamic( PrintContext $context ) : string
    {
        $context->addBlock( $this->block );
        $this->block->content   = $this->content->print( $context ); // must be compiled after is added
        $escaper                = $context->getEscaper();
        $this->modifier->escape = $this->modifier->escape || $escaper->getState() === Escaper::HtmlAttribute;

        $_name   = TemplateGenerator::ARG_NAME;
        $_filter = TemplateGenerator::ARG_FILTER;
        $_ns     = TemplateGenerator::NAMESPACE;
        return $context->format(
            <<<EOD
                \$this->addBlock(%node, %dump, [[\$this, %dump]], %dump);
                			\$this->renderBlock({$_name}, get_defined_vars()
                EOD
                .( $this->modifier->filters || $this->modifier->escape
                        ? ', function ($s, $type) { '.$_filter.' = new '.$_ns.'\FilterInfo($type); return %modifyContent($s); }'
                        : '' )
                .');',
            new AssignNode( new VariableNode( $_name ), $this->block->name ),
            $escaper->export(),
            $this->block->method,
            $this->block->layer,
            $this->modifier,
        );
    }

    public function &getIterator() : Generator
    {
        if ( $this->block ) {
            yield $this->block->name;
        }
        yield $this->modifier;
        yield $this->content;
    }
}
