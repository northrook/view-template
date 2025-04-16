<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Exception\CompileException;
use Generator;
use Core\View\Template\Compiler\{PrintContext, Tag, TemplateParser};
use Core\View\Template\Compiler\Nodes\Php\{ExpressionNode, ModifierNode};

/**
 * {= ...}
 */
class PrintNode extends StatementNode
{
    public ExpressionNode $expression;

    public ModifierNode $modifier;

    private ?string $followsQuote = null;

    /**
     * @param Tag            $tag
     * @param TemplateParser $parser
     *
     * @return PrintNode
     * @throws CompileException
     */
    public static function create( Tag $tag, TemplateParser $parser ) : static
    {
        $tag->outputMode = $tag::OutputKeepIndentation;
        $tag->expectArguments();
        $node               = new static();
        $node->followsQuote = \preg_match( '#["\']#A', $parser->getStream()->peek()->text )
                ? $tag->getNotation( true )
                : null;
        $node->expression       = $tag->parser->parseExpression();
        $node->modifier         = $tag->parser->parseModifier();
        $node->modifier->escape = true;
        return $node;
    }

    /**
     * @param PrintContext $context
     *
     * @return string
     * @throws CompileException
     */
    public function print( ?PrintContext $context ) : string
    {
        if ( $this->followsQuote && $context->getEscaper()->export() === 'html/raw/js' ) {
            throw new CompileException(
                "Do not place {$this->followsQuote} inside quotes in JavaScript.",
                $this->position,
            );
        }

        if ( $context->raw ) {
            return $context->format(
                "' . %modify(%node) . '",
                $this->modifier,
                $this->expression,
            );
        }
        return $context->format(
            "echo %modify(%node) %line;\n",
            $this->modifier,
            $this->expression,
            $this->position,
        );
    }

    public function &getIterator() : Generator
    {
        yield $this->expression;
        yield $this->modifier;
    }
}
