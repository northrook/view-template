<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Compiler\Nodes\Php\ExpressionNode;
use Core\View\Template\Compiler\Nodes\StatementNode;
use Core\View\Template\Compiler\{PrintContext, Tag};
use Generator;

/**
 * {dump [$var]}
 */
class DumpNode extends StatementNode
{
    public ?ExpressionNode $expression = null;

    /**
     * @throws \Core\View\Template\Exception\CompileException
     */
    public static function create( Tag $tag ) : static
    {
        $node             = new static();
        $node->expression = $tag->parser->isEnd()
                ? null
                : $tag->parser->parseExpression();
        return $node;
    }

    public function print( PrintContext $context ) : string
    {
        return $this->expression
                ? $context->format(
                    'Tracy\Debugger::barDump(%node, %dump) %line;',
                    $this->expression,
                    $this->expression->print( $context ),
                    $this->position,
                )
                : $context->format(
                    "Tracy\\Debugger::barDump(get_defined_vars(), 'variables') %line;",
                    $this->position,
                );
    }

    public function &getIterator() : Generator
    {
        if ( $this->expression ) {
            yield $this->expression;
        }
    }
}
