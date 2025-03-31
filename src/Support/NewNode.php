<?php

declare(strict_types=1);

namespace Core\View\Template\Support;

use Core\View\Template\Compiler\Nodes\{FragmentNode, TextNode};
use Core\View\Template\Compiler\Nodes\Html\{AttributeNode, ElementNode};
use Core\View\Template\Compiler\Position;
use Stringable;

final class NewNode
{
    /**
     * @param string               $name
     * @param null|Position        $position
     * @param null|ElementNode     $parent
     * @param array<string,string> $attributes
     *
     * @return ElementNode
     */
    public static function element(
        string       $name,
        ?Position    $position = null,
        ?ElementNode $parent = null,
        array        $attributes = [],
    ) : ElementNode {
        $element = new ElementNode(
            $name,
            $position,
            $parent,
        );
        $element->attributes = new FragmentNode();
        $element->content    = new FragmentNode();

        foreach ( $attributes as $attribute => $value ) {
            $element->attributes->append( NewNode::text( ' ' ) );
            $element->attributes->append(
                new AttributeNode(
                    NewNode::text( $attribute ),
                    $value ? NewNode::text( $value ) : null,
                    '"',
                ),
            );
        }

        return $element;
    }

    public static function text(
        bool|int|string|null|Stringable|float $value,
        ?Position                             $position = null,
    ) : TextNode {
        $value = match ( \gettype( $value ) ) {
            'boolean' => $value ? 'true' : 'false',
            default   => (string) $value,
        };
        return new TextNode( $value, $position );
    }
}
