<?php

declare( strict_types = 1 );

namespace Core\View\Template\Compiler;

use Core\View\Element\Attributes;
use Core\View\Template\Compiler\Nodes\{FragmentNode, PrintNode, TextNode};
use Core\View\Template\Compiler\Nodes\Html\{AttributeNode, ElementNode};
use Core\View\Template\Support\NewNode;

final class NodeAttributes
{
    /** @var array<array-key, AttributeNode|string> */
    private array $variables = [];

    public readonly Attributes $attributes;

    public function __construct( ElementNode | FragmentNode $from = null )
    {
        dump( $from );
        $attributeFragmentNode = match ( true ) {
            $from instanceof ElementNode  => $from->attributes ?? new FragmentNode(),
            $from instanceof FragmentNode => $from,
            default                       => new FragmentNode(),
        };

        $this->attributes = new Attributes();

        // TODO : Validate inline expressions: class="flex {$var ?? 'column'} px:16"
        foreach ( $attributeFragmentNode as $index => $attribute ) {
            // Skip separators
            if ( !$attribute instanceof AttributeNode ) {
                continue;
            }
            // Preserve expressions
            if ( $attribute->value instanceof PrintNode ) {
                $this->variables[ $index ] = $attribute->value;
                continue;
            }

            if ( $attribute->name instanceof PrintNode ) {
                $this->variables[ $index ] = $attribute->name;
                continue;
            }

            // Set current index/name and attribute values
            $name                     = NodeHelpers::toText( $attribute->name );
            $value                    = NodeHelpers::toText( $attribute->value );
            $this->variables[ $name ] = $value;
            $this->attributes->add( (string) $name, $value );
        }
    }

    public function __invoke() : Attributes
    {
        return $this->attributes;
    }

    public function getNode() : ?FragmentNode
    {
        $fragmentNode = new FragmentNode();

        dump( $this->variables );
        foreach ( \array_merge(
                $this->variables,
                $this->attributes->resolveAttributes( true ),
        ) as $name => $node ) {
            $fragmentNode->append( TextNode::from( WHITESPACE ) );

            $name       = is_int( $name ) ? false : TextNode::from( $name );
            $expression = is_int( $name );

            if ( $node instanceof PrintNode && $name ) {
                $fragmentNode->append( new AttributeNode( $name, NodeHelpers::toValue( $node->expression ), '"', ) );
                continue;
            }

            if ( $node instanceof PrintNode ) {
                $fragmentNode->append( $node );
                continue;
            }

            if ( $node instanceof AttributeNode ) {
                $fragmentNode->append( $node );

                continue;
            }

            $fragmentNode->append( new AttributeNode( $name, TextNode::from( $node ), '"' ) );
        }

        // dd( $fragmentNode->children );

        return empty( $fragmentNode->children ) ? null : $fragmentNode;
    }
}
