<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Runtime;

use Core\View\Template\ContentType;
use Core\View\Template\Exception\RuntimeException;

/**
 * Filter runtime info
 */
class FilterInfo
{
    public ?ContentType $contentType = null;

    public function __construct( ?ContentType $contentType = null )
    {
        $this->contentType = $contentType;
    }

    public function validate( array $contentTypes, ?string $name = null ) : void
    {
        if ( ! \in_array( $this->contentType->type(), $contentTypes, true ) ) {
            $name = $name ? " |{$name}" : $name;
            $type = $this->contentType ? ' '.$this->contentType->name : '';
            throw new RuntimeException( "Filter{$name} used with incompatible type{$type}." );
        }
    }
}
