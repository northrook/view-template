<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Compiler\{Node, NodeTraverser, PrintContext, Tag, TemplateGenerator, Token};

/**
 * {templatePrint [ParentClass]}
 */
class TemplatePrintNode extends StatementNode
{
    public ?string $template;

    public static function create( Tag $tag ) : static
    {
        $node           = new static();
        $node->template = $tag->parser->stream->tryConsume(
            Token::Php_Identifier,
            Token::Php_NameFullyQualified,
            Token::Php_NameQualified,
        )?->text;
        return $node;
    }

    public function print( PrintContext $context ) : string
    {
        $_bp = TemplateGenerator::ARG_BEGIN_PRINT;
        return $context->format(
            <<<XX
                {$_bp} = new \Core\View\Template\Support\TracerBlueprint;
                {$_bp}->printBegin();
                {$_bp}->printClass(
                    {$_bp}->generateTemplateClass(
                        \$this->getParameters(),
                        extends: %dump
                    )
                );
                {$_bp}->printEnd();
                exit;
                XX,
            $this->template,
        );
    }

    /**
     * Pass: moves this node to head.
     *
     * @param TemplateNode $templateNode
     */
    public static function moveToHeadPass( TemplateNode $templateNode ) : void
    {
        ( new NodeTraverser() )->traverse(
            $templateNode->main,
            function( Node $node ) use ( $templateNode ) {
                if ( $node instanceof self ) {
                    \array_unshift( $templateNode->head->children, $node );
                    return new NopNode();
                }
                return $node;
            },
        );
    }
}
