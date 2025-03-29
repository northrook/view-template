<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Html;

use Core\View\Template\ContentType;
use Generator;
use stdClass;

use Core\View\Template\Compiler\{Node, NodeHelpers, Nodes, Position, PrintContext, Tag, TemplateGenerator};
use Core\View\Template\Compiler\Nodes\{AreaNode, AuxiliaryNode, FragmentNode};

/**
 * HTML element node.
 */
class ElementNode extends AreaNode
{
    public ?Nodes\Php\ExpressionNode $variableName = null;

    public ?FragmentNode $attributes = null;

    public bool $selfClosing = false;

    public ?AreaNode $content = null;

    /** @var Tag[] */
    public array $nAttributes = [];

    /** n:tag & n:tag- support */
    public AreaNode $tagNode;

    public bool $captureTagName = false;

    public bool $breakable = false;

    private ?string $endTagVar;

    public function __construct(
        public /* readonly */ string    $name,
        public ?Position $position = null,
        public /* readonly */ ?self     $parent = null,
        public ?stdClass $data = null,
        public ContentType    $contentType = ContentType::HTML,
    ) {
        $this->data ??= new stdClass();
        $this->tagNode = new AuxiliaryNode( $this->printStartTag( ... ) );
    }

    public function getAttribute( string $name ) : string|Node|bool|null
    {
        foreach ( $this->attributes?->children as $child ) {
            if ( $child instanceof AttributeNode
                 && $child->name instanceof Nodes\TextNode
                 && \strcasecmp( $name, $child->name->content ) === 0
            ) {
                return NodeHelpers::toText( $child->value ) ?? $child->value ?? true;
            }
        }

        return null;
    }

    public function is( string $name ) : bool
    {
        return $this->contentType                   === ContentType::HTML
                ? \strcasecmp( $this->name, $name ) === 0
                : $this->name                       === $name;
    }

    public function isRawText() : bool
    {
        return $this->contentType === ContentType::HTML
               && ( $this->is( 'script' ) || $this->is( 'style' ) );
    }

    public function print( PrintContext $context ) : string
    {
        $_tag = TemplateGenerator::ARG_TAG;

        $res = $this->endTagVar = null;
        if ( $this->captureTagName || $this->variableName ) {
            $endTag = $this->endTagVar = $_tag.'['.$context->generateId().']';
            $res    = "{$this->endTagVar} = '';";
        }
        else {
            $endTag = \var_export( '</'.$this->name.'>', true );
        }

        $res .= $this->tagNode->print( $context ); // calls $this->printStartTag()

        if ( $this->content ) {
            $context->beginEscape()->enterHtmlText( $this );
            $content = $this->content->print( $context );
            $context->restoreEscape();
            $res .= $this->breakable
                    ? 'try { '.$content.' } finally { echo '.$endTag.'; } '
                    : $content.'echo '.$endTag.';';
        }

        return $res;
    }

    private function printStartTag( PrintContext $context ) : string
    {
        $context->beginEscape()->enterHtmlTag( $this->name );
        $res  = "echo '<';";
        $_tmp = TemplateGenerator::ARG_TEMP;

        if ( $this->endTagVar ) {
            $expr = $this->variableName
                    ? TemplateGenerator::NAMESPACE.'\Filters::safeTag('
                      .$this->variableName->print( $context )
                      .( $this->contentType === ContentType::XML ? ', true' : '' )
                      .')'
                    : \var_export( $this->name, true );
            $res .= "echo {$_tmp} = {$expr} /* line {$this->position->line} */;"
                     ."{$this->endTagVar} = '</' . {$_tmp} . '>' . {$this->endTagVar};";
        }
        else {
            $res .= 'echo '.\var_export( $this->name, true ).';';
        }

        foreach ( $this->attributes?->children ?? [] as $attr ) {
            $res .= $attr->print( $context );
        }

        $res .= "echo '".( $this->selfClosing ? '/>' : '>' )."';";
        $context->restoreEscape();
        return $res;
    }

    public function &getIterator() : Generator
    {
        yield $this->tagNode;
        if ( $this->variableName ) {
            yield $this->variableName;
        }
        if ( $this->attributes ) {
            yield $this->attributes;
        }
        if ( $this->content ) {
            yield $this->content;
        }
    }
}
