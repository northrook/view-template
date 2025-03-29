<?php

declare(strict_types=1);

namespace Core\View\Template\Exception;

use Core\View\Template\Compiler\Position;
use Core\View\Template\Support\PositionAwareException;
use Exception;

/**
 * Exception thrown when a not allowed construction is used in a template.
 */
class SecurityViolationException extends Exception
{
    use PositionAwareException;

    public function __construct( string $message, ?Position $position = null )
    {
        parent::__construct( $message );
        $this->position = $position;
        $this->generateMessage();
    }
}
