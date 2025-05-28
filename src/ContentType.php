<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 *
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template;

use InvalidArgumentException;

enum ContentType : string
{
    case TEXT = 'text';
    case HTML = 'html';
    case XML  = 'xml';
    case JS   = 'js';
    case CSS  = 'css';
    case ICAL = 'ical';

    public static function by( self|string $value ) : static
    {
        if ( $value instanceof self ) {
            return $value;
        }

        return self::{\strtoupper( $value )} ?? throw new InvalidArgumentException();
    }
}
