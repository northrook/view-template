<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Compiler\Nodes\StatementNode;
use Core\View\Template\Compiler\{PrintContext, Tag, Token};
use Generator;

/**
 * {varType type $var}
 */
class VarTypeNode extends StatementNode
{
    /**
     * @param Tag $tag
     *
     * @return VarTypeNode
     * @throws \Core\View\Template\Exception\CompileException
     */
    public static function create( Tag $tag ) : static
    {
        $tag->expectArguments();
        $tag->parser->parseType();
        $tag->parser->stream->consume( Token::Php_Variable );
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
