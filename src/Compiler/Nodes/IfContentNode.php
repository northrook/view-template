<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Exception\CompileException;
use Core\View\Template\Compiler\Nodes\Html\ElementNode;
use Generator;
use Core\View\Template\Compiler\{PrintContext, Tag, TemplateGenerator, TemplateParser};

/**
 * n:ifcontent
 */
class IfContentNode extends StatementNode
{
    public AreaNode $content;

    public int $id;

    public ElementNode $htmlElement;

    public ?AreaNode $else = null;

    /**
     * @param Tag            $tag
     * @param TemplateParser $parser
     *
     * @return Generator<int, ?array, array{AreaNode, ?Tag}, static>
     * @throws CompileException
     */
    public static function create( Tag $tag, TemplateParser $parser ) : Generator
    {
        $node              = $tag->node = new static();
        $node->id          = $parser->generateId();
        [$node->content]   = yield;
        $node->htmlElement = $tag->htmlElement;
        if ( ! $node->htmlElement->content ) {
            throw new CompileException(
                "Unnecessary n:ifcontent on empty element <{$node->htmlElement->name}>",
                $tag->position,
            );
        }
        return $node;
    }

    public function print( ?PrintContext $context ) : string
    {
        $_if_c = TemplateGenerator::ARG_IF_C;

        try {
            $saved                      = $this->htmlElement->content;
            $else                       = $this->else ?? new AuxiliaryNode( fn() => '' );
            $this->htmlElement->content = new AuxiliaryNode(
                fn() => <<<XX
                    ob_start();
                    try {
                    	{$saved->print( $context )}
                    } finally {
                    	{$_if_c}[{$this->id}] = rtrim(ob_get_flush()) === '';
                    }
                    XX,
            );
            return <<<XX
                ob_start(fn() => '');
                try {
                	{$this->content->print( $context )}
                } finally {
                	if ({$_if_c}[{$this->id}] ?? null) {
                		ob_end_clean();
                		{$else->print( $context )}
                	} else {
                		echo ob_get_clean();
                	}
                }
                XX;
        }
        finally {
            $this->htmlElement->content = $saved;
        }
    }

    public function &getIterator() : Generator
    {
        yield $this->content;
    }
}
