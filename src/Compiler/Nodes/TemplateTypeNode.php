<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Exception\CompileException;
use Core\View\Template\Compiler\Nodes\StatementNode;
use Generator;
use Core\View\Template\Compiler\{PrintContext, Tag};

/**
 * {templateType ClassName}
 */
class TemplateTypeNode extends StatementNode
{
    /**
     * @param Tag $tag
     *
     * @return TemplateTypeNode
     * @throws CompileException
     */
    public static function create( Tag $tag ) : static
    {
        if ( ! $tag->isInHead() ) {
            throw new CompileException( '{templateType} is allowed only in template header.', $tag->position );
        }
        $tag->expectArguments( 'class name' );
        $tag->parser->parseExpression();
        return new static();
    }

    public function print( PrintContext $context ) : string
    {
        return '';
    }

    public function &getIterator() : Generator
    {
        false && yield;
    }
}
