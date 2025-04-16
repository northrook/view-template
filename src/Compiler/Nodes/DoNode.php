<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Compiler\Nodes\Php\ExpressionNode;
use Core\View\Template\Compiler\{PrintContext, Tag};
use Generator;

/**
 * {do expression}
 */
class DoNode extends StatementNode
{
    private const array RefusedKeywords = [
        'for',
        'foreach',
        'switch',
        'while',
        'if',
        'do',
        'try',
        'include',
        'include_once',
        'require',
        'require_once',
        'throw',
        'yield',
        'return',
        'exit',
        'break',
        'continue',
        'class',
        'function',
        'interface',
        'trait',
        'enum',
    ];

    public ExpressionNode $expression;

    /**
     * @param Tag $tag
     *
     * @return DoNode
     * @throws \Core\View\Template\Exception\CompileException
     */
    public static function create( Tag $tag ) : static
    {
        $tag->expectArguments();

        $token = $tag->parser->stream->peek();
        if ( $token->is( ...self::RefusedKeywords ) ) {
            $tag->parser->throwReservedKeywordException( $token );
        }

        $node             = new static();
        $node->expression = $tag->parser->parseExpression();
        return $node;
    }

    public function print( ?PrintContext $context ) : string
    {
        return $context->format(
            '%node %line;',
            $this->expression,
            $this->position,
        );
    }

    public function &getIterator() : Generator
    {
        yield $this->expression;
    }
}
