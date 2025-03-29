<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Exception\CompileException;
use Core\View\Template\Compiler\Nodes\Php\ExpressionNode;
use Core\View\Template\Compiler\Nodes\StatementNode;
use Generator;
use Core\View\Template\Compiler\{PrintContext, Tag};
use Core\View\Template\Compiler\Nodes\Php\Scalar\{BooleanNode, NullNode};

/**
 * {extends none | auto | "file"}
 * {layout none | auto | "file"}
 */
class ExtendsNode extends StatementNode
{
    public ExpressionNode $extends;

    /**
     * @param Tag $tag
     *
     * @return ExtendsNode
     * @throws \Core\View\Template\Exception\CompileException
     */
    public static function create( Tag $tag ) : static
    {
        $tag->expectArguments();
        $node = new static();
        if ( ! $tag->isInHead() ) {
            throw new CompileException( "{{$tag->name}} must be placed in template head.", $tag->position );
        }
        if ( $tag->parser->stream->tryConsume( 'auto' ) ) {
            $node->extends = new NullNode();
        }
        elseif ( $tag->parser->stream->tryConsume( 'none' ) ) {
            $node->extends = new BooleanNode( false );
        }
        else {
            $node->extends = $tag->parser->parseUnquotedStringOrExpression();
        }
        return $node;
    }

    public function print( PrintContext $context ) : string
    {
        return $context->format( '$this->parentName = %node;', $this->extends );
    }

    public function &getIterator() : Generator
    {
        yield $this->extends;
    }
}
