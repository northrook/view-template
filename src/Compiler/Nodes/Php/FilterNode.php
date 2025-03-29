<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php;

use Generator;
use LogicException;

use Core\View\Template\Compiler\{Node, Position, PrintContext, TemplateGenerator};

class FilterNode extends Node
{
    /**
     * @param IdentifierNode $name
     * @param ArgumentNode[] $args
     * @param null|Position  $position
     */
    public function __construct(
        public IdentifierNode $name,
        public array          $args = [],
        public ?Position      $position = null,
    ) {
        ( function( ArgumentNode ...$args ) {} )( ...$args );
    }

    public function print( PrintContext $context ) : string
    {
        throw new LogicException( 'Cannot directly print FilterNode' );
    }

    public function printSimple( PrintContext $context, string $expr ) : string
    {
        return '($this->filters->'.$context->objectProperty( $this->name ).')('
               .$expr
               .( $this->args ? ', '.$context->implode( $this->args ) : '' )
               .')';
    }

    public function printContentAware( PrintContext $context, string $expr ) : string
    {
        return '$this->filters->filterContent('
               .$context->encodeString( $this->name->name )
               .', '.TemplateGenerator::ARG_FILTER.', '
               .$expr
               .( $this->args ? ', '.$context->implode( $this->args ) : '' )
               .')';
    }

    public function &getIterator() : Generator
    {
        yield $this->name;

        foreach ( $this->args as &$item ) {
            yield $item;
        }
    }
}
