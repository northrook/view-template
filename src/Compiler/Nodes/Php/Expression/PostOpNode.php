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
use InvalidArgumentException;
use Core\View\Template\Compiler\{Position, PrintContext};

class PostOpNode extends ExpressionNode
{
    private const array Ops = ['++' => 1, '--' => 1];

    /**
     * @param ExpressionNode $var
     * @param string         $operator
     * @param ?Position      $position
     *
     * @throws CompileException
     */
    public function __construct(
        public ExpressionNode $var,
        public /* readonly */ string         $operator,
        public ?Position      $position = null,
    ) {
        if ( ! isset( self::Ops[$this->operator] ) ) {
            throw new InvalidArgumentException( "Unexpected operator '{$this->operator}'" );
        }
        $this->validate();
    }

    /**
     * @param PrintContext $context
     *
     * @throws CompileException
     */
    public function print( ?PrintContext $context ) : string
    {
        $this->validate();
        return $context->postfixOp( $this, $this->var, $this->operator );
    }

    public function validate() : void
    {
        if ( ! $this->var->isWritable() ) {
            throw new CompileException(
                'Cannot write to the expression: '.$this->var->print( new PrintContext() ),
                $this->var->position,
            );
        }
    }

    public function &getIterator() : Generator
    {
        yield $this->var;
    }
}
