<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Html;

use Generator;

use Core\View\Template\Compiler\{NodeHelpers, Position, PrintContext};
use Core\View\Template\Compiler\Nodes\{AreaNode, FragmentNode, TextNode};

class AttributeNode extends AreaNode
{
    public function __construct(
        public AreaNode  $name,
        public ?AreaNode $value = null,
        public ?string   $quote = null,
        public ?Position $position = null,
    ) {}

    public function print( ?PrintContext $context ) : string
    {
        $context ??= new PrintContext();
        $output = $this->name->print( $context );

        if ( ! $this->value ) {
            return $output;
        }

        $escaper = $context->beginEscape();
        $output .= $context->output( '=' );

        $quote = $this->quote ?? ( $this->value instanceof TextNode ? null : '"' );

        if ( $quote ) {
            $output .= $context->output( $quote );
        }

        $escaper->enterHtmlAttribute( NodeHelpers::toText( $this->name ) );

        if ( $this->value instanceof FragmentNode && $escaper->export() === 'html/attr/url' ) {
            foreach ( $this->value->children as $child ) {
                $output .= $child->print( $context );
                $escaper->enterHtmlAttribute();
            }
        }
        else {
            $output .= $this->value->print( $context );
        }

        $context->restoreEscape();

        return $quote ? $output.$context->output( $quote ) : $output;
    }

    public function &getIterator() : Generator
    {
        yield $this->name;
        if ( $this->value ) {
            yield $this->value;
        }
    }
}
