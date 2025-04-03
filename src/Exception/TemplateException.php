<?php

namespace Core\View\Template\Exception;

use Core\View\Template\Support\PositionAwareException;
use RuntimeException;
use Throwable;

class TemplateException extends RuntimeException
{
    use PositionAwareException;

    public function __construct(
        string                 $message,
        public readonly string $caller,
        int                    $code = E_RECOVERABLE_ERROR,
        ?Throwable             $previous = null,
    ) {
        $message = \trim( $message, " \n\r\t\v\0.:,;" ).".\n'{$this->caller}'.";
        parent::__construct( $message, $code, $previous );
    }
}
