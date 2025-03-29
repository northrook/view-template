<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 *
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template;

use InvalidArgumentException;

enum ContentType
{
    case TEXT;
    case HTML;
    case XML;
    case JS;
    case CSS;
    case ICAL;

    public static function from( self|string $value ) : static
    {
        if ( $value instanceof self ) {
            return $value;
        }

        return self::{\strtoupper( $value )} ?? throw new InvalidArgumentException();
    }

    public function type() : string
    {
        return \strtolower( $this->name );
    }
}
