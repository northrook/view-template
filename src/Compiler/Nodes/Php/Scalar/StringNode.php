<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php\Scalar;

use Core\View\Template\Compiler\Nodes\Php\ScalarNode;
use Core\View\Template\Compiler\{PhpHelpers, Position, PrintContext};

class StringNode extends ScalarNode
{
    public function __construct(
        public string    $value,
        public ?Position $position = null,
    ) {}

    /**
     * @throws \Core\View\Template\Exception\CompileException
     */
    public static function parse( string $str, Position $position ) : static
    {
        $str = $str[0] === "'"
                ? \strtr( \substr( $str, 1, -1 ), ['\\\\' => '\\', "\\'" => "'"] )
                : PhpHelpers::decodeEscapeSequences( \substr( $str, 1, -1 ), '"' );
        return new static( $str, $position );
    }

    public function print( ?PrintContext $context ) : string
    {
        return $context->encodeString( $this->value );
    }
}
