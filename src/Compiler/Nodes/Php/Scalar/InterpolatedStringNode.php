<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php\Scalar;

use Generator;
use Core\View\Template\Compiler\Nodes\Php\{
    ScalarNode,
    ExpressionNode,
    InterpolatedStringPartNode,
};
use Core\View\Template\Compiler\{
    PhpHelpers,
    Position,
    PrintContext
};

class InterpolatedStringNode extends ScalarNode
{
    public function __construct(
        /** @var array<ExpressionNode|InterpolatedStringPartNode> */
        public array     $parts,
        public ?Position $position = null,
    ) {}

    /**
     * @param array<ExpressionNode|InterpolatedStringPartNode> $parts
     * @param Position                                         $position
     *
     * @return InterpolatedStringNode
     * @throws \Core\View\Template\Exception\CompileException
     */
    public static function parse( array $parts, Position $position ) : static
    {
        foreach ( $parts as $part ) {
            if ( $part instanceof InterpolatedStringPartNode ) {
                $part->value = PhpHelpers::decodeEscapeSequences( $part->value, '"' );
            }
        }

        return new static( $parts, $position );
    }

    public function print( ?PrintContext $context ) : string
    {
        $s    = '';
        $expr = false;

        foreach ( $this->parts as $part ) {
            if ( $part instanceof InterpolatedStringPartNode ) {
                $s .= \substr( $context->encodeString( $part->value, '"' ), 1, -1 );

                continue;
            }

            $partStr = $part->print( $context );
            if ( $partStr[0] === '$' && $part->isVariable() ) {
                $s .= '{'.$partStr.'}';
            }
            else {
                $s .= '" . ('.$partStr.') . "';
                $expr = true;
            }
        }

        return $expr
                ? '("'.$s.'")'
                : '"'.$s.'"';
    }

    public function &getIterator() : Generator
    {
        foreach ( $this->parts as &$item ) {
            yield $item;
        }
    }
}
