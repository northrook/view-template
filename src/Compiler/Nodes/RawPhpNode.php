<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Compiler\{PrintContext, Tag};

/**
 * {php statement; statement; ...}
 */
class RawPhpNode extends StatementNode
{
    public string $code;

    /**
     * @param Tag $tag
     *
     * @return RawPhpNode
     * @throws \Core\View\Template\Exception\CompileException
     */
    public static function create( Tag $tag ) : static
    {
        $tag->expectArguments();
        while ( ! $tag->parser->stream->consume()->isEnd() ) {
            //
        }
        $node       = new static();
        $node->code = \trim( $tag->parser->text );
        if ( ! \preg_match( '~[;}]$~', $node->code ) ) {
            $node->code .= ';';
        }
        return $node;
    }

    public function print( ?PrintContext $context ) : string
    {
        return $context->format(
            '%line; %raw ',
            $this->position,
            $this->code,
        );
    }
}
