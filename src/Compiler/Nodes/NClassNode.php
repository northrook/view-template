<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Exception\CompileException;
use Core\View\Template\Compiler\Nodes\Php\Expression\ArrayNode;
use Core\View\Template\Compiler\{PrintContext, Tag, TemplateGenerator};
use Generator;

/**
 * n:class="..."
 */
final class NClassNode extends StatementNode
{
    public ArrayNode $args;

    /**
     * @param Tag $tag
     *
     * @return NClassNode
     * @throws CompileException
     */
    public static function create( Tag $tag ) : static
    {
        if ( $tag->htmlElement->getAttribute( 'class' ) ) {
            throw new CompileException( 'It is not possible to combine class with n:class.', $tag->position );
        }

        $tag->expectArguments();
        $node       = new self();
        $node->args = $tag->parser->parseArguments();
        return $node;
    }

    public function print( ?PrintContext $context ) : string
    {
        $tmp = TemplateGenerator::ARG_TEMP;
        $ns  = TemplateGenerator::NAMESPACE;

        return $context->format(
            <<<MASK
                echo ({$tmp} = array_filter(%node)) ? \' class="\' . {$ns}\Filters::escapeHtmlAttr(implode(" ", array_unique({$tmp}))) . \'"\' : "" %line;
                MASK,
            $this->args,
            $this->position,
        );
    }

    public function &getIterator() : Generator
    {
        yield $this->args;
    }
}
