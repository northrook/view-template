<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php\Expression;

use Core\View\Template\Compiler\Nodes\Php\ExpressionNode;
use Core\View\Template\Compiler\{Nodes\Php\MatchArmNode, Position, PrintContext};
use Generator;

class MatchNode extends ExpressionNode
{
    /**
     * @param ExpressionNode $cond
     * @param MatchArmNode[] $arms
     * @param null|Position  $position
     */
    public function __construct(
        public ExpressionNode $cond,
        public array          $arms = [],
        public ?Position      $position = null,
    ) {
        ( function( MatchArmNode ...$args ) {} )( ...$arms );
    }

    public function print( PrintContext $context ) : string
    {
        $res = 'match ('.$this->cond->print( $context ).') {';

        foreach ( $this->arms as $node ) {
            $res .= "\n".$node->print( $context ).',';
        }

        $res .= "\n}";
        return $res;
    }

    public function &getIterator() : Generator
    {
        yield $this->cond;

        foreach ( $this->arms as &$item ) {
            yield $item;
        }
    }
}
