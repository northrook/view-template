<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php\Expression;

use Core\View\Template\Compiler\Nodes\Php\ExpressionNode;
use Generator;
use InvalidArgumentException;
use Core\View\Template\Compiler\{Position, PrintContext};

class BinaryOpNode extends ExpressionNode
{
    private const array Ops = [
        '||',
        '&&',
        'or',
        'and',
        'xor',
        '&',
        '^',
        '.',
        '+',
        '-',
        '*',
        '/',
        '%',
        '<<',
        '>>',
        '**',
        '==',
        '!=',
        '===',
        '!==',
        '<=>',
        '<',
        '<=',
        '>',
        '>=',
        '??',
    ];

    public function __construct(
        public ExpressionNode $left,
        public /* readonly */ string         $operator,
        public ExpressionNode $right,
        public ?Position      $position = null,
    ) {
        if ( ! \in_array( \strtolower( $this->operator ), self::Ops, true ) ) {
            throw new InvalidArgumentException( "Unexpected operator '{$this->operator}'" );
        }
    }

    /**
     * Creates nested BinaryOp nodes from a list of expressions.
     *
     * @param string           $operator
     * @param ExpressionNode[] $exprs
     *
     * @return ExpressionNode
     */
    public static function nest( string $operator, ExpressionNode ...$exprs ) : ExpressionNode
    {
        $count = \count( $exprs );
        if ( $count < 2 ) {
            return $exprs[0];
        }

        $last = $exprs[0];
        for ( $i = 1; $i < $count; $i++ ) {
            $last = new static( $last, $operator, $exprs[$i] );
        }

        return $last;
    }

    public function print( ?PrintContext $context ) : string
    {
        return $context->infixOp( $this, $this->left, ' '.$this->operator.' ', $this->right );
    }

    public function &getIterator() : Generator
    {
        yield $this->left;
        yield $this->right;
    }
}
