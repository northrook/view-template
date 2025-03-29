<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php\Expression;

use Core\View\Template\Exception\CompileException;
use Core\View\Template\Compiler\Nodes\Php\ExpressionNode;
use Generator;
use TypeError;
use Core\View\Template\Compiler\{Position, PrintContext};

class IssetNode extends ExpressionNode
{
    /**
     * @param array     $vars
     * @param ?Position $position
     *
     * @throws CompileException
     */
    public function __construct(
        /** @var ExpressionNode[] */
        public array     $vars,
        public ?Position $position = null,
    ) {
        $this->validate();
    }

    /**
     * @param PrintContext $context
     *
     * @throws CompileException
     */
    public function print( PrintContext $context ) : string
    {
        $this->validate();
        return 'isset('.$context->implode( $this->vars ).')';
    }

    /**
     * @throws CompileException
     */
    public function validate() : void
    {
        foreach ( $this->vars as $var ) {
            if ( ! $var instanceof ExpressionNode ) {
                throw new TypeError( 'Variable must be ExpressionNode, '.\get_debug_type( $var ).' given.' );
            }
            if ( ! $var->isVariable() ) {
                throw new CompileException(
                    'Cannot use isset() on expression: '.$var->print( new PrintContext() ),
                    $var->position,
                );
            }
        }
    }

    public function &getIterator() : Generator
    {
        foreach ( $this->vars as &$item ) {
            yield $item;
        }
    }
}
