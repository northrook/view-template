<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php;

use Generator;

use Core\View\Template\Compiler\{Position, PrintContext};

class UnionTypeNode extends ComplexTypeNode
{
    /**
     * @param IdentifierNode[]|NameNode[] $types
     * @param null|Position               $position
     */
    public function __construct(
        public array     $types,
        public ?Position $position = null,
    ) {
        ( function( IdentifierNode|NameNode ...$args ) {} )( ...$types );
    }

    public function print( ?PrintContext $context ) : string
    {
        return $context->implode( $this->types, '|' );
    }

    public function &getIterator() : Generator
    {
        foreach ( $this->types as &$item ) {
            yield $item;
        }
    }
}
