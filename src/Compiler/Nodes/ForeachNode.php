<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Support\CachingIterator;
use Core\View\Template\Exception\CompileException;
use Core\View\Template\Compiler\{Node, NodeTraverser, Position, PrintContext, Tag, TagParser, TemplateGenerator};
use Core\View\Template\Compiler\Nodes\{AreaNode, AuxiliaryNode, NopNode, StatementNode, TemplateNode};
use Core\View\Template\Compiler\Nodes\Php\{ExpressionNode, ListNode};
use Core\View\Template\Compiler\Nodes\Php\Expression\VariableNode;

use Generator;

/**
 * {foreach $expr as $key => $value} & {else}
 */
class ForeachNode extends StatementNode
{
    public ExpressionNode $expression;

    public ?ExpressionNode $key = null;

    public bool $byRef = false;

    public ExpressionNode|ListNode $value;

    public AreaNode $content;

    public ?AreaNode $else = null;

    public ?Position $elseLine = null;

    public ?bool $iterator = null;

    public bool $checkArgs = true;

    /**
     * @param Tag $tag
     *
     * @return Generator<int, ?array, array{AreaNode, ?Tag}, NopNode|static>
     * @throws CompileException
     */
    public static function create( Tag $tag ) : Generator
    {
        $tag->expectArguments();
        $node = $tag->node = new static();
        self::parseArguments( $tag->parser, $node );

        $modifier = $tag->parser->parseModifier();

        foreach ( $modifier->filters as $filter ) {
            match ( $filter->name->name ) {
                'nocheck', 'noCheck' => $node->checkArgs      = false,
                'noiterator', 'noIterator' => $node->iterator = false,
                default => throw new CompileException(
                    'Only modifiers |noiterator and |nocheck are allowed here.',
                    $tag->position,
                ),
            };
        }

        if ( $tag->void ) {
            $node->content = new NopNode();
            return $node;
        }

        [$node->content, $nextTag] = yield ['else'];
        if ( $nextTag?->name === 'else' ) {
            $node->elseLine = $nextTag->position;
            [$node->else]   = yield;
        }

        return $node;
    }

    /**
     * @param TagParser $parser
     * @param self      $node
     *
     * @throws CompileException
     */
    private static function parseArguments( TagParser $parser, self $node ) : void
    {
        $stream           = $parser->stream;
        $node->expression = $parser->parseExpression();
        $stream->consume( 'as' );
        [$node->key, $node->value, $node->byRef] = $parser->parseForeach();
    }

    public function print( PrintContext $context ) : string
    {
        $_it = TemplateGenerator::ARG_IT;

        $content = $this->content->print( $context );
        $iterator
                 = $this->else || ( $this->iterator ?? \preg_match( '#\$iterator\W|\Wget_defined_vars\W#', $content ) );

        if ( $this->else ) {
            $content .= $context->format(
                '} if ($iterator->isEmpty()) %line { ',
                $this->elseLine,
            ).$this->else->print( $context );
        }

        if ( $iterator ) {
            $_cacheIterator = CachingIterator::class;
            return $context->format(
                <<<XX
                    foreach (\$iterator = {$_it} = new {$_cacheIterator}(%node, {$_it} ?? null) as %raw) %line {
                    	%raw
                    }
                    \$iterator = {$_it} = {$_it}->getParent();
                    XX,
                $this->expression,
                $this->printArgs( $context ),
                $this->position,
                $content,
            );
        }

        return $context->format(
            <<<'XX'
                foreach (%node as %raw) %line {
                	%raw
                }
                XX,
            $this->expression,
            $this->printArgs( $context ),
            $this->position,
            $content,
        );
    }

    private function printArgs( PrintContext $context ) : string
    {
        return ( $this->key ? $this->key->print( $context ).' => ' : '' )
               .( $this->byRef ? '&' : '' )
               .$this->value->print( $context );
    }

    public function &getIterator() : Generator
    {
        yield $this->expression;
        if ( $this->key ) {
            yield $this->key;
        }
        yield $this->value;
        yield $this->content;
        if ( $this->else ) {
            yield $this->else;
        }
    }

    /**
     * Pass: checks if foreach overrides template variables.
     *
     * @param TemplateNode $node
     */
    public static function overwrittenVariablesPass( TemplateNode $node ) : void
    {
        $_var  = TemplateGenerator::ARG_VAR;
        $_line = TemplateGenerator::ARG_LINE;
        $vars  = [];
        ( new NodeTraverser() )->traverse(
            $node,
            function( Node $node ) use ( &$vars ) {
                if ( $node instanceof self && $node->checkArgs ) {
                    foreach ( [$node->key, $node->value] as $var ) {
                        if ( $var instanceof VariableNode ) {
                            $vars[$var->name][] = $node->position->line;
                        }
                    }
                }
            },
        );
        if ( $vars ) {
            \array_unshift(
                $node->head->children,
                new AuxiliaryNode(
                    fn( PrintContext $context ) => $context->format(
                        <<<XX
                            if (!\$this->getReferringTemplate() || \$this->getReferenceType() === 'extends') {
                            	foreach (array_intersect_key(%dump, \$this->params) as {$_var} => {$_line}) {
                            		trigger_error("Variable \${$_var} overwritten in foreach on line {$_line}");
                            	}
                            }
                            XX,
                        \array_map( fn( $l ) => \implode( ', ', $l ), $vars ),
                    ),
                ),
            );
        }
    }
}
