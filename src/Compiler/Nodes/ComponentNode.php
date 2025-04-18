<?php

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Compiler\{Position, PrintContext};
use Generator;
use const Support\AUTO;

final class ComponentNode extends AreaNode
{
    /** @var AreaNode[] */
    public array $children;

    /**
     * @param AreaNode[]|FragmentNode|TemplateNode $content
     * @param ?Position                            $position
     */
    public function __construct(
        TemplateNode|FragmentNode|AreaNode|array $content,
        public ?Position                         $position = null,
    ) {
        $this->setChildren( $content );
    }

    public function setChildren( TemplateNode|FragmentNode|AreaNode|array $from ) : static
    {
        $this->children = [];

        $nodes = match ( true ) {
            $from instanceof TemplateNode => $from->main->children,
            $from instanceof FragmentNode => $from->children,
            $from instanceof AreaNode     => [$from],
            default                       => $from,
        };

        foreach ( $nodes as $node ) {
            $this->append( $node );
        }
        return $this;
    }

    public function append( AreaNode $node ) : static
    {
        if ( $node instanceof self ) {
            $this->children = \array_merge( $this->children, $node->children );
        }
        elseif ( ! $node instanceof NopNode ) {
            $this->children[] = $node;
        }
        $this->position ??= $node->position;
        return $this;
    }

    public function simplify( bool $allowsNull = true ) : ?AreaNode
    {
        return match ( true ) {
            ! $this->children               => $allowsNull ? null : $this,
            \count( $this->children ) === 1 => $this->children[0],
            default                         => $this,
        };
    }

    public function print( ?PrintContext $context = AUTO ) : string
    {
        $context ??= new PrintContext();
        $output = '';

        foreach ( $this->children as $child ) {
            $output .= $child->print( $context );
        }

        return $output;
    }

    public function &getIterator() : Generator
    {
        foreach ( $this->children as &$item ) {
            yield $item;
        }
    }
}
