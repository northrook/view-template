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
    /** @var array */
    private array $fragments = [];

    public function __construct( mixed $value = null )
    {
        // Directly handle arrays or iterables to reduce overhead
        if ( \is_iterable( $value ) || $value instanceof ArrayAccess ) {
            foreach ( $value as $item ) {
                $this->addValue( $item );
            }
        }
        elseif ( $value !== null ) {
            $this->addValue( $value );
        }
    }

    final protected function addValue( mixed $value ) : void
    {
        if ( \is_scalar( $value ) || $value instanceof Stringable ) {
            $this->fragments[] = (string) $value;
        }
        else {
            throw new RuntimeException(
                $this::class." only accepts stringable values, '".\gettype( $value )."' provided.",
            );
        }
    }

    public function __toString() : string
    {
        return \implode( '', $this->fragments );
    }
}
