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
     * @param ParameterNode[]                              $parameters
     * @param ClosureUseNode[]                             $uses
     * @param null|ComplexTypeNode|IdentifierNode|NameNode $returnType
     * @param null|ExpressionNode                          $expr
     * @param null|Position                                $position
     */
    public function __construct(
        public bool                                         $byRef,
        public array                                        $parameters,
        public array                                        $uses,
        public null|IdentifierNode|NameNode|ComplexTypeNode $returnType = null,
        public ?ExpressionNode                              $expr = null,
        public ?Position                                    $position = null,
    ) {
        ( function( ParameterNode ...$args ) {} )( ...$parameters );
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
                  .'('.$context->implode( $this->parameters ).')'
                  .( $this->returnType !== null ? ': '.$this->returnType->print( $context ) : '' )
                  .' => '
                  .$this->expr->print( $context )
                : 'function '.( $this->byRef ? '&' : '' )
                  .'('.$context->implode( $this->parameters ).')'
                  .( ! empty( $this->uses ) ? ' use ('.$context->implode( $this->uses ).')' : '' )
                  .( $this->returnType !== null ? ' : '.$this->returnType->print( $context ) : '' )
                  .( $this->expr ? ' { return '.$this->expr->print( $context ).'; }' : ' {}' );
    }

    public function &getIterator() : Generator
    {
        foreach ( $this->parameters as &$item ) {
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
