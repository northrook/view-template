<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Runtime;

use ArrayAccess;
use Stringable;
use RuntimeException;

/**
 * HTML literal.
 */
class Html implements Stringable
{
    private string $value = '';

    public function __construct( mixed $value )
    {
        if ( \is_iterable( $value ) || $value instanceof ArrayAccess ) {
            foreach ( $value as $item ) {
                $this->addValue( $item );
            }
        }
        else {
            $this->addValue( $value );
        }
    }

    private function addValue( mixed $value ) : void
    {
        if ( \is_scalar( $value ) || $value instanceof Stringable || \is_null( $value ) ) {
            $this->value .= $value;
        }
        else {
            throw new RuntimeException(
                $this::class." only accepts stringable values, '".\gettype( $value )."' provided.",
            );
        }
    }

    public function __toString() : string
    {
        return $this->value;
    }
}
