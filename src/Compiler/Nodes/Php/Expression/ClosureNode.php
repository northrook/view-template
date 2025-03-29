<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php\Expression;

use Generator;

use Core\View\Template\Compiler\{Position, PrintContext};
use Core\View\Template\Compiler\Nodes\Php\{ClosureUseNode,
    ComplexTypeNode,
    ExpressionNode,
    IdentifierNode,
    NameNode,
    ParameterNode
};

class ClosureNode extends ExpressionNode
{
    /**
     * @param bool                                         $byRef
     * @param ParameterNode[]                              $params
     * @param ClosureUseNode[]                             $uses
     * @param null|ComplexTypeNode|IdentifierNode|NameNode $returnType
     * @param null|ExpressionNode                          $expr
     * @param null|Position                                $position
     */
    public function __construct(
        public bool                                         $byRef,
        public array                                        $params,
        public array                                        $uses,
        public null|IdentifierNode|NameNode|ComplexTypeNode $returnType = null,
        public ?ExpressionNode                              $expr = null,
        public ?Position                                    $position = null,
    ) {
        ( function( ParameterNode ...$args ) {} )( ...$params );
        ( function( ClosureUseNode ...$args ) {} )( ...$uses );
    }

    public function print( PrintContext $context ) : string
    {
        $arrow = (bool) $this->expr;

        foreach ( $this->uses as $use ) {
            $arrow = $arrow && ! $use->byRef;
        }

        return $arrow
                ? 'fn'.( $this->byRef ? '&' : '' )
                  .'('.$context->implode( $this->params ).')'
                  .( $this->returnType !== null ? ': '.$this->returnType->print( $context ) : '' )
                  .' => '
                  .$this->expr->print( $context )
                : 'function '.( $this->byRef ? '&' : '' )
                  .'('.$context->implode( $this->params ).')'
                  .( ! empty( $this->uses ) ? ' use ('.$context->implode( $this->uses ).')' : '' )
                  .( $this->returnType !== null ? ' : '.$this->returnType->print( $context ) : '' )
                  .( $this->expr ? ' { return '.$this->expr->print( $context ).'; }' : ' {}' );
    }

    public function &getIterator() : Generator
    {
        foreach ( $this->params as &$item ) {
            yield $item;
        }

        foreach ( $this->uses as &$item ) {
            yield $item;
        }

        if ( $this->returnType ) {
            yield $this->returnType;
        }
        if ( $this->expr ) {
            yield $this->expr;
        }
    }
}
