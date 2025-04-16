<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Compiler\Nodes\Php\ExpressionNode;
use Generator;
use Core\View\Template\Compiler\{PrintContext, Tag};

/**
 * {debugbreak [$cond]}
 */
class DebugbreakNode extends StatementNode
{
    public ?ExpressionNode $condition;

    /**
     * @param Tag $tag
     *
     * @return DebugbreakNode
     * @throws \Core\View\Template\Exception\CompileException
     */
    public static function create( Tag $tag ) : static
    {
        $node            = new static();
        $node->condition = $tag->parser->isEnd() ? null : $tag->parser->parseExpression();
        return $node;
    }

    public function print( ?PrintContext $context ) : string
    {
        if ( \function_exists( $func = 'debugbreak' ) || \function_exists( $func = 'xdebug_break' ) ) {
            return $context->format(
                ( $this->condition ? 'if (%1.node) ' : '' ).$func.'() %0.line;',
                $this->position,
                $this->condition,
            );
        }
        return '';
    }

    public function &getIterator() : Generator
    {
        if ( $this->condition ) {
            yield $this->condition;
        }
    }
}
