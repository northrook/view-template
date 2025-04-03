<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler;

use Stringable;
use Core\Interface\DataInterface;

final readonly class Position implements DataInterface, Stringable
{
    public function __construct(
        public int $line = 1,
        public int $column = 1,
        public int $offset = 0,
    ) {}

    public function getId() : string
    {
        return $this->line.'-'.$this->column.'-'.$this->offset;
    }

    public function advance( string $str ) : self
    {
        if ( $lines = \substr_count( $str, "\n" ) ) {
            return new self(
                $this->line + $lines,
                \strlen( $str ) - \strrpos( $str, "\n" ),
                $this->offset + \strlen( $str ),
            );
        }

        return new self(
            $this->line,
            $this->column + \strlen( $str ),
            $this->offset + \strlen( $str ),
        );
    }

    public function __toString() : string
    {
        return "on line {$this->line}".( $this->column ? " at column {$this->column}" : '' );
    }
}
