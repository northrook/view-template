<?php

declare(strict_types=1);

namespace Core\View\Template\Compiler;

use Core\View\Element\Attributes;
use Core\View\Template\Compiler\Nodes\{FragmentNode, PrintNode};
use Core\View\Template\Compiler\Nodes\Html\{AttributeNode, ElementNode};
use Core\View\Template\Support\NewNode;

final class NodeAttributes
{
    /** @var array<array-key, AttributeNode|string> */
    private array $variables = [];

    public readonly Attributes $attributes;

    public function __construct( ElementNode|FragmentNode $from = null )
    {
        $attributeFragmentNode = match ( true ) {
            $from instanceof ElementNode  => $from->attributes ?? new FragmentNode(),
            $from instanceof FragmentNode => $from,
            default                       => new FragmentNode(),
        };

        $this->attributes = new Attributes();

        // TODO : Validate inline expressions: class="flex {$var ?? 'column'} px:16"
        foreach ( $attributeFragmentNode as $index => $attribute ) {
            // Skip separators
            if ( ! $attribute instanceof AttributeNode ) {
                continue;
            }

            // Preserve expressions
            if ( $attribute->name instanceof PrintNode ) {
                $this->variables[$index] = $attribute;
            }
            // Set current index/name and attribute values
            else {
                $name                   = NodeHelpers::toText( $attribute->name );
                $value                  = NodeHelpers::toText( $attribute->value );
                $this->variables[$name] = $value;
                $this->attributes->add( (string) $name, $value );
            }
        }
    }

    public function __invoke() : Attributes
    {
        return $this->attributes;
    }

    public function getNode() : ?FragmentNode
    {
        $fragmentNode = new FragmentNode();

        foreach ( \array_merge(
            $this->variables,
            $this->attributes->resolveAttributes( true ),
        ) as $name => $value ) {
            $fragmentNode->append( NewNode::text( WHITESPACE ) );

            if ( $value instanceof AttributeNode ) {
                $fragmentNode->append( $value );

                continue;
            }

            $fragmentNode->append( new AttributeNode( NewNode::text( $name ), NewNode::text( $value ), '"' ) );
        }

        return empty( $fragmentNode->children ) ? null : $fragmentNode;
    }
}
