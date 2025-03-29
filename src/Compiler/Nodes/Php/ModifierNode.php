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

class ModifierNode extends Node
{
    /**
     * @param FilterNode[]  $filters
     * @param bool          $escape
     * @param bool          $check
     * @param null|Position $position
     */
    public function __construct(
        public array     $filters,
        public bool      $escape = false,
        public bool      $check = true,
        public ?Position $position = null,
    ) {
        ( function( FilterNode ...$args ) {} )( ...$filters );
    }

    public function hasFilter( string $name ) : bool
    {
        foreach ( $this->filters as $filter ) {
            if ( $filter->name->name === $name ) {
                return true;
            }
        }

        return false;
    }

    public function print( PrintContext $context ) : string
    {
        throw new LogicException( 'Cannot directly print ModifierNode' );
    }

    public function printSimple( PrintContext $context, string $expr ) : string
    {
        $escape = $this->escape;
        $check  = $this->check;

        foreach ( $this->filters as $filter ) {
            $name = $filter->name->name;
            if ( $name === 'nocheck' || $name === 'noCheck' ) {
                $check = false;
            }
            elseif ( $name === 'noescape' ) {
                $escape = false;
            }
            else {
                if ( $name === 'datastream' || $name === 'dataStream' ) {
                    $check = false;
                }
                $expr = $filter->printSimple( $context, $expr );
            }
        }

        $escaper = $context->getEscaper();
        if ( $check ) {
            $expr = $escaper->check( $expr );
        }

        $expr = $escape
                ? $escaper->escape( $expr )
                : $escaper->escapeMandatory( $expr );

        return $expr;
    }

    public function printContentAware( PrintContext $context, string $expr ) : string
    {
        foreach ( $this->filters as $filter ) {
            $name = $filter->name->name;
            if ( $name === 'noescape' ) {
                $noescape = true;
            }
            else {
                $expr = $filter->printContentAware( $context, $expr );
            }
        }

        $fl = TemplateGenerator::ARG_FILTER;
        $ns = TemplateGenerator::NAMESPACE;

        if ( $this->escape && empty( $noescape ) ) {
            $expr = $ns.'\Filters::convertTo('.$fl.', '
                    .\var_export( $context->getEscaper()->export(), true ).', '
                    .$expr
                    .')';
        }

        return $expr;
    }

    public function &getIterator() : Generator
    {
        foreach ( $this->filters as &$filter ) {
            yield $filter;
        }
    }
}
