<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Compiler\{PrintContext, Tag};
use Core\View\Template\Compiler\Nodes\Php\ExpressionNode;
use Core\View\Template\Exception\CompileException;
use Generator;

/**
 * {dd [$var]}
 */
final class DumpDieNode extends StatementNode
{
    public ?ExpressionNode $expression = null;

    /**
     * @param Tag $tag
     *
     * @throws CompileException
     */
    public function __construct( Tag $tag )
    {
        $this->expression = $tag->parser->isEnd()
                ? null
                : $tag->parser->parseExpression();
    }

    /**
     * @param Tag $tag
     *
     * @return static
     *
     * @throws CompileException
     */
    public static function create( Tag $tag ) : static
    {
        return new self( $tag );
    }

    public function print( ?PrintContext $context ) : string
    {
        if ( ! \function_exists( 'dd' ) ) {
            return '/* '.\implode(
                ' ',
                [
                    "dd( {$this->expression->print( $context )} )",
                    $this->position ? "line {$this->position->line}" : '',
                ],
            ).' */';
        }

        return $this->expression
                ? $context->format(
                    'dd( %node ) %line;',
                    $this->expression,
                    $this->position,
                )
                : $context->format(
                    "dd( ['\$this->global' => \$this->global, ...get_defined_vars()] ) %line;",
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
