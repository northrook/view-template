<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php\Expression;

use Generator;

use Core\View\Template\Exception\CompileException;
use Core\View\Template\Compiler\{Position, PrintContext};
use Core\View\Template\Compiler\Nodes\Php\{ExpressionNode, ListNode};

class AssignNode extends ExpressionNode
{
    /**
     * @param ExpressionNode|ListNode $var
     * @param ExpressionNode          $expr
     * @param bool                    $byRef
     * @param ?Position               $position
     *
     * @throws CompileException
     */
    public function __construct(
        public ExpressionNode|ListNode $var,
        public ExpressionNode          $expr,
        public bool                    $byRef = false,
        public ?Position               $position = null,
    ) {
        $this->validate();
    }

    /**
     * @param PrintContext $context
     *
     * @return string
     * @throws CompileException
     */
    public function print( ?PrintContext $context ) : string
    {
        $this->validate();
        return $context->infixOp( $this, $this->var, $this->byRef ? ' = &' : ' = ', $this->expr );
    }

    /**
     * @throws CompileException
     */
    public function validate() : void
    {
        if ( $this->var instanceof ExpressionNode && ! $this->var->isWritable() ) {
            throw new CompileException(
                'Cannot write to the expression: '.$this->var->print( new PrintContext() ),
                $this->var->position,
            );
        }
        if ( $this->byRef && ! $this->expr->isWritable() ) {
            throw new CompileException(
                'Cannot take reference to the expression: '.$this->expr->print( new PrintContext() ),
                $this->expr->position,
            );
        }
    }

    public function &getIterator() : Generator
    {
        yield $this->var;
        yield $this->expr;
    }
}
