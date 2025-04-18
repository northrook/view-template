<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Compiler\{Position, PrintContext};
use Stringable;
use const Support\AUTO;

class TextNode extends AreaNode
{
    public function __construct(
        public string    $content,
        public ?Position $position = null,
    ) {}

    public function print( ?PrintContext $context = AUTO ) : string
    {
        $context ??= new PrintContext();

        if ( $this->content === '' ) {
            return '';
        }

        return $context->output( $this->content, NEWLINE );
    }

    public function isWhitespace() : bool
    {
        return \trim( $this->content ) === '';
    }

    final public static function from(
        bool|int|string|null|Stringable|float $value,
        ?Position                             $position = null,
    ) : TextNode {
        $content = match ( \gettype( $value ) ) {
            'boolean' => $value ? 'true' : 'false',
            default   => (string) $value,
        };
        return new TextNode( $content, $position );
    }
}
