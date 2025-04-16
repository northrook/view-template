<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php;

use Generator;
use InvalidArgumentException;

use Core\View\Template\Compiler\{Node, Nodes\Php\Scalar\StringNode, Position, PrintContext};

class ArrayItemNode extends Node
{
    public function __construct(
        public ExpressionNode                     $value,
        public ExpressionNode|IdentifierNode|null $key = null,
        public bool                               $byRef = false,
        public bool                               $unpack = false,
        public ?Position                          $position = null,
    ) {}

    public function print( ?PrintContext $context ) : string
    {
        $key = match ( true ) {
            $this->key instanceof ExpressionNode => $this->key->print( $context ).' => ',
            $this->key instanceof IdentifierNode => $context->encodeString( $this->key->name ).' => ',
            $this->key === null                  => '',
        };
        return $key
               .( $this->byRef ? '&' : '' )
               .( $this->unpack ? '...' : '' )
               .$this->value->print( $context );
    }

    public function toArgument() : ArgumentNode
    {
        $key = match ( true ) {
            $this->key instanceof StringNode     => new IdentifierNode( $this->key->value ),
            $this->key instanceof IdentifierNode => $this->key,
            $this->key === null                  => null,
            default                              => throw new InvalidArgumentException(
                'The expression used in the key cannot be converted to an argument.',
            ),
        };
        return new ArgumentNode( $this->value, $this->byRef, $this->unpack, $key, $this->position );
    }

    public function &getIterator() : Generator
    {
        if ( $this->key ) {
            yield $this->key;
        }
        yield $this->value;
    }
}
