<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php\Scalar;

use Core\View\Template\Exception\CompileException;
use Core\View\Template\Compiler\Nodes\Php\ScalarNode;
use Exception;
use const PHP_INT_MAX;
use Core\View\Template\Compiler\{PhpHelpers, Position, PrintContext};

class IntegerNode extends ScalarNode
{
    public const int KindBinary = 2;

    public const int KindOctal = 8;

    public const int KindDecimal = 10;

    public const int KindHexa = 16;

    public function __construct(
        public int       $value,
        public int       $kind = self::KindDecimal,
        public ?Position $position = null,
    ) {}

    /**
     * @throws CompileException
     * @param  string           $str
     * @param  Position         $position
     */
    public static function parse( string $str, Position $position ) : static
    {
        $num = PhpHelpers::decodeNumber( $str, $base );
        if ( $num === null ) {
            throw new CompileException( 'Invalid numeric literal', $position );
        }
        return new static( $num, (int) $base, $position );
    }

    /**
     * @param PrintContext $context
     *
     * @return string
     * @throws Exception
     */
    public function print( ?PrintContext $context ) : string
    {
        if ( $this->value === -PHP_INT_MAX - 1 ) {
            // PHP_INT_MIN cannot be represented as a literal, because the sign is not part of the literal
            return '(-'.PHP_INT_MAX.'-1)';
        }
        if ( $this->kind === self::KindDecimal ) {
            return (string) $this->value;
        }

        if ( $this->value < 0 ) {
            $sign = '-';
            $str  = (string) -$this->value;
        }
        else {
            $sign = '';
            $str  = (string) $this->value;
        }

        return match ( $this->kind ) {
            self::KindBinary => $sign.'0b'.\base_convert( $str, 10, 2 ),
            self::KindOctal  => $sign.'0'.\base_convert( $str, 10, 8 ),
            self::KindHexa   => $sign.'0x'.\base_convert( $str, 10, 16 ),
            default          => throw new Exception( 'Invalid number kind' ),
        };
    }
}
