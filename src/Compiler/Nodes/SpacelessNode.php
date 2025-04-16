<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Compiler\{PrintContext, Tag};
use Core\View\Template\ContentType;
use Generator;

/**
 * {spaceless}
 */
class SpacelessNode extends StatementNode
{
    public AreaNode $content;

    /**
     * @return Generator<int, ?array, array{AreaNode, ?Tag}, static>
     * @param  Tag                                                   $tag
     */
    public static function create( Tag $tag ) : Generator
    {
        $node            = $tag->node = new static();
        [$node->content] = yield;
        return $node;
    }

    public function print( ?PrintContext $context ) : string
    {
        return $context->format(
            <<<'XX'
                ob_start('\Core\View\Template\Engine\Core\Filters::%raw', 4096) %line;
                try {
                	%node
                } finally {
                	ob_end_flush();
                }
                XX,
            $context->getEscaper()->getContentType() === ContentType::HTML
                        ? 'spacelessHtmlHandler'
                        : 'spacelessText',
            $this->position,
            $this->content,
        );
    }

    public function &getIterator() : Generator
    {
        yield $this->content;
    }
}
