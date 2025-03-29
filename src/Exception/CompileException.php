<?php

namespace Core\View\Template\Exception;

use Core\View\Template\{Compiler, Support\PositionAwareException};
use Throwable;
use Exception;

/**
 * The exception occurred during Latte compilation.
 */
class CompileException extends Exception
{
    use PositionAwareException;

    /** @deprecated */
    public ?int $sourceLine;

    public function __construct(
        string             $message,
        ?Compiler\Position $position = null,
        ?Throwable         $previous = null,
    ) {
        parent::__construct( $message, 0, $previous );
        $this->position   = $position;
        $this->sourceLine = $position?->line;
        $this->generateMessage();
    }
}
