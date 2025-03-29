<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler;

use ArrayIterator;
use Core\View\Template\Exception\CompileException;
use JetBrains\PhpStorm\Deprecated;
use LogicException;

/**
 * PHP helpers.
 *
 * @internal
 */
final class PhpHelpers
{
    /**
     * Optimizes code readability.
     *
     * @param string $source
     *
     * @return string
     */
    #[Deprecated] // :: See what we can learn from this
    public static function reformatCode(
        string $source,
    ) : string {
        $res      = '';
        $lastChar = ';';
        $tokens   = new ArrayIterator( \token_get_all( $source ) );
        $level    = 0;

        foreach ( $tokens as $n => $token ) {
            $next = $tokens[$n + 1] ?? [null, ''];

            if ( \is_array( $token ) ) {
                [$name, $token] = $token;
                if ( $name === T_ELSE || $name === T_ELSEIF ) {
                    if ( $next === ':' && $lastChar === '}' ) {
                        $res .= ';'; // semicolon needed in if(): ... if() ... else:
                    }

                    $lastChar = '';
                    $res .= $token;
                }
                elseif ( $name === T_DOC_COMMENT || $name === T_COMMENT ) {
                    $res .= \preg_replace( "#\n[ \t]*+(?!\n)#", "\n".\str_repeat( "\t", $level ), $token );
                }
                elseif ( $name === T_WHITESPACE ) {
                    $prev  = $tokens[$n - 1];
                    $lines = \substr_count( $token, "\n" );
                    if ( $prev === '}' && \in_array( $next[0], [T_ELSE, T_ELSEIF, T_CATCH, T_FINALLY], true ) ) {
                        $token = ' ';
                    }
                    elseif ( $prev === '{' || $prev === '}' || $prev === ';' || $lines ) {
                        $token = \str_repeat( "\n", \max( 1, $lines ) ).\str_repeat(
                            "\t",
                            $level,
                        ); // indent last line
                    }
                    elseif ( $prev[0] === T_OPEN_TAG ) {
                        $token = '';
                    }

                    $res .= $token;
                }
                elseif ( $name === T_OBJECT_OPERATOR ) {
                    $lastChar = '->';
                    $res .= $token;
                }
                elseif ( $name === T_OPEN_TAG ) {
                    $res .= "<?php\n";
                }
                elseif ( $name === T_CLOSE_TAG ) {
                    throw new LogicException( 'Unexpected token' );
                }
                else {
                    if ( \in_array( $name, [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true ) ) {
                        $level++;
                    }

                    $lastChar = '';
                    $res .= $token;
                }
            }
            else {
                if ( $token === '{' || $token === '[' ) {
                    $level++;
                }
                elseif ( $token === '}' || $token === ']' ) {
                    $level--;
                    $res .= "\x08";
                }
                elseif ( $token === ';' ) {
                    if ( $next[0] !== T_WHITESPACE ) {
                        $token .= "\n".\str_repeat( "\t", $level ); // indent last line
                    }
                }

                $lastChar = $token;
                $res .= $token;
            }
        }

        return \str_replace( ["\t\x08", "\x08"], '', $res );
    }

    public static function decodeNumber( string $str, &$base = null ) : int|float|null
    {
        $str = \str_replace( '_', '', $str );

        if ( $str[0] !== '0' || $str === '0' ) {
            $base = 10;
            return $str + 0;
        }
        if ( $str[1] === 'x' || $str[1] === 'X' ) {
            $base = 16;
            return \hexdec( $str );
        }
        if ( $str[1] === 'b' || $str[1] === 'B' ) {
            $base = 2;
            return \bindec( $str );
        }
        if ( \strpbrk( $str, '89' ) ) {
            return null;
        }

        $base = 8;
        return \octdec( $str );
    }

    /**
     * @param string  $str
     * @param ?string $quote
     *
     * @return string
     * @throws CompileException
     */
    public static function decodeEscapeSequences( string $str, ?string $quote ) : string
    {
        if ( $quote !== null ) {
            $str = \str_replace( '\\'.$quote, $quote, $str );
        }

        return \preg_replace_callback(
            // '~\\\\([\\\\$nrtfve]|[xX][0-9a-fA-F]{1,2}|[0-7]{1,3}|u\{([0-9a-fA-F]+)\})~',
            '~\\\\([\\\\$nrtfve]|[xX][0-9a-fA-F]{1,2}|[0-7]{1,3}|u\{([0-9a-fA-F]+)})~',
            // :: [Removed Redundant escapes]
            function( $matches ) {
                $ch           = $matches[1];
                $replacements = [
                    '\\' => '\\',
                    '$'  => '$',
                    'n'  => "\n",
                    'r'  => "\r",
                    't'  => "\t",
                    'f'  => "\f",
                    'v'  => "\v",
                    'e'  => "\x1B",
                ];
                if ( isset( $replacements[$ch] ) ) {
                    return $replacements[$ch];
                }
                if ( $ch[0] === 'x' || $ch[0] === 'X' ) {
                    return \chr( \hexdec( \substr( $ch, 1 ) ) );
                }
                if ( $ch[0] === 'u' ) {
                    return self::codePointToUtf8( \hexdec( $matches[2] ) );
                }

                return \chr( \octdec( $ch ) );
            },
            $str,
        );
    }

    /**
     * @param int $num
     *
     * @return string
     * @throws CompileException
     */
    private static function codePointToUtf8( int $num ) : string
    {
        return match ( true ) {
            $num <= 0x7F    => \chr( $num ),
            $num <= 0x7_FF  => \chr( ( $num >> 6 ) + 0xC0 ).\chr( ( $num & 0x3F ) + 0x80 ),
            $num <= 0xFF_FF => \chr( ( $num >> 12 ) + 0xE0 ).\chr( ( ( $num >> 6 ) & 0x3F ) + 0x80 ).\chr(
                ( $num & 0x3F ) + 0x80,
            ),
            $num <= 0x1F_FF_FF => \chr( ( $num >> 18 ) + 0xF0 ).\chr( ( ( $num >> 12 ) & 0x3F ) + 0x80 )
                                  .\chr( ( ( $num >> 6 ) & 0x3F ) + 0x80 ).\chr( ( $num & 0x3F ) + 0x80 ),
            default => throw new CompileException(
                'Invalid UTF-8 codepoint escape sequence: Codepoint too large',
            ),
        };
    }

    /**
     * @param string $phpBinary
     * @param string $code
     * @param string $name
     *
     * @throws CompileException
     */
    public static function checkCode( string $phpBinary, string $code, string $name ) : void
    {
        $process = \proc_open(
            $phpBinary.' -l -n',
            [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
            $pipes,
            null,
            null,
            ['bypass_shell' => true],
        );
        if ( ! \is_resource( $process ) ) {
            throw new CompileException( 'Unable to check that the generated PHP is correct.' );
        }

        \fwrite( $pipes[0], $code );
        \fclose( $pipes[0] );
        $error = \stream_get_contents( $pipes[1] );
        if ( ! \proc_close( $process ) ) {
            return;
        }
        $error    = \strip_tags( \explode( "\n", $error )[1] );
        $position = \preg_match( '~ on line (\d+)~', $error, $m )
                ? new Position( (int) $m[1], 0 )
                : null;
        $error = \preg_replace( '~(^Fatal error: | in Standard input code| on line \d+)~', '', $error );
        throw ( new CompileException( 'Error in generated code: '.\trim( $error ), $position ) )
            ->setSource( $code, $name );
    }
}
