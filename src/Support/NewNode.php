<?php

declare(strict_types=1);

namespace Core\View\Template\Support;

use Core\View\Element\Attributes;
use Core\View\Template\Compiler\Nodes\{FragmentNode, TextNode};
use Core\View\Template\Compiler\Nodes\Html\{AttributeNode, ElementNode};
use Core\View\Template\Compiler\Position;
use Stringable;

final class NewNode
{
    /**
     * @param string           $name
     * @param null|Position    $position
     * @param null|ElementNode $parent
     * @param null|Attributes  $attributes
     *
     * @return ElementNode
     */
    public static function element(
        string       $name,
        ?Position    $position = null,
        ?ElementNode $parent = null,
        ?Attributes  $attributes = null,
    ) : ElementNode {
        $element = new ElementNode(
            $name,
            $position,
            $parent,
        );
        $element->attributes = new FragmentNode();
        $element->content    = new FragmentNode();

        if ( $attributes ) {
            foreach ( $attributes->resolveAttributes( true ) as $attribute => $value ) {
                $element->attributes->append( self::text( ' ' ) );
                $element->attributes->append(
                    new AttributeNode(
                        self::text( $attribute ),
                        $value ? self::text( $value ) : null,
                        '"',
                    ),
                );
            }
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
