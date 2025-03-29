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

class CastNode extends ExpressionNode
{
    private const array Types = ['int' => 1, 'float' => 1, 'string' => 1, 'array' => 1, 'object' => 1, 'bool' => 1];

    public function __construct(
        public /* readonly */ string         $type,
        public ExpressionNode $expr,
        public ?Position      $position = null,
    ) {
        if ( ! isset( self::Types[\strtolower( $this->type )] ) ) {
            throw new InvalidArgumentException( "Unexpected type '{$this->type}'" );
        }
    }

    public function print( PrintContext $context ) : string
    {
        return $context->prefixOp( $this, '('.$this->type.') ', $this->expr );
    }

    public function &getIterator() : Generator
    {
        yield $this->expr;
    }
}
