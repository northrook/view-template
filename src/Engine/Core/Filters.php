<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Engine\Core;

use Core\View\Template\{ContentType, Support\AuxiliaryIterator, Runtime\Html, Exception\RuntimeException};
use Core\View\Template\Runtime\{FilterInfo, Filters as RuntimeFilters};
use Stringable;
use Closure;
use Collator;
use Countable;
use DateInterval;
use DateTime;
use DateTimeInterface;
use Generator;
use IntlDateFormatter;
use InvalidArgumentException;
use NumberFormatter;
use Traversable;
use function Support\slug;
use const Support\AUTO;
use DateMalformedStringException;
use ErrorException;

/**
 * Template filters. Uses UTF-8 only.
 *
 * @internal
 */
final class Filters
{
    public ?string $locale = null;

    /**
     * Converts HTML to plain text.
     *
     * @param FilterInfo             $info
     * @param null|string|Stringable $string
     *
     * @return string
     */
    public static function stripHtml( FilterInfo $info, null|string|Stringable $string ) : string
    {
        if ( ! $string = (string) $string ) {
            return '';
        }
        $info->validate( [null, 'html', 'html/attr', 'xml', 'xml/attr'], __FUNCTION__ );
        $info->contentType = ContentType::TEXT;
        return RuntimeFilters::convertHtmlToText( $string );
    }

    /**
     * Removes tags from HTML (but remains HTML entities).
     *
     * @param FilterInfo             $info
     * @param null|string|Stringable $string
     *
     * @return string
     */
    public static function stripTags( FilterInfo $info, null|string|Stringable $string ) : string
    {
        $info->contentType ??= ContentType::HTML;
        $info->validate( ['html', 'html/attr', 'xml', 'xml/attr'], __FUNCTION__ );
        return \strip_tags( (string) $string );
    }

    /**
     * Replaces all repeated white spaces with a single space.
     *
     * @param FilterInfo             $info
     * @param null|string|Stringable $string $string
     *
     * @return string
     */
    public static function strip( FilterInfo $info, null|string|Stringable $string ) : string
    {
        return $info->contentType === ContentType::HTML
                ? \trim( self::spacelessHtml( $string ) )
                : \trim( self::spacelessText( $string ) );
    }

    /**
     * Replaces all repeated white spaces with a single space.
     *
     * @param null|string|Stringable $string $string
     * @param bool                   $strip
     *
     * @return string
     */
    public static function spacelessHtml( null|string|Stringable $string, bool &$strip = true ) : string
    {
        if ( ! $string = (string) $string ) {
            return '';
        }

        return \preg_replace_callback(
            '#[ \t\r\n]+|<(/)?(textarea|pre|script)(?=\W)#i',
            function( $m ) use ( &$strip ) {
                if ( empty( $m[2] ) ) {
                    return $strip ? ' ' : $m[0];
                }

                $strip = ! empty( $m[1] );
                return $m[0];
            },
            $string,
        );
    }

    /**
     * Output buffering handler for spacelessHtml.
     *
     * @param null|string|Stringable $string $string
     * @param ?int                   $phase
     *
     * @return string
     */
    public static function spacelessHtmlHandler( null|string|Stringable $string, ?int $phase = null ) : string
    {
        static $strip;
        $left = $right = '';

        if ( $phase & PHP_OUTPUT_HANDLER_START ) {
            $strip  = true;
            $tmp    = \ltrim( $string );
            $left   = \substr( $string, 0, \strlen( $string ) - \strlen( $tmp ) );
            $string = $tmp;
        }

        if ( $phase & PHP_OUTPUT_HANDLER_FINAL ) {
            $tmp    = \rtrim( $string );
            $right  = \substr( $string, \strlen( $tmp ) );
            $string = $tmp;
        }

        return $left.self::spacelessHtml( $string, $strip ).$right;
    }

    /**
     * Replaces all repeated white spaces with a single space.
     *
     * @param null|string|Stringable $string $string
     *
     * @return string
     */
    public static function spacelessText( null|string|Stringable $string ) : string
    {
        return \preg_replace( '#[ \t\r\n]+#', ' ', $string );
    }

    /**
     * Indents plain text or HTML the content from the left.
     *
     * @param FilterInfo             $info
     * @param null|string|Stringable $string $string
     * @param int                    $level
     * @param string                 $chars
     *
     * @return string
     */
    public static function indent(
        FilterInfo             $info,
        null|string|Stringable $string,
        int                    $level = 1,
        string                 $chars = "\t",
    ) : string {
        if ( $level < 1 ) {
            // do nothing
        }
        elseif ( $info->contentType === ContentType::HTML ) {
            $string = \preg_replace_callback(
                '#<(textarea|pre).*?</\1#si',
                fn( $m ) => \strtr( $m[0], " \t\r\n", "\x1F\x1E\x1D\x1A" ),
                $string,
            );
            if ( \preg_last_error() ) {
                throw new RuntimeException( \preg_last_error_msg() );
            }

            $string = \preg_replace( '#(?:^|[\r\n]+)(?=[^\r\n])#', '$0'.\str_repeat( $chars, $level ), $string );
            $string = \strtr( $string, "\x1F\x1E\x1D\x1A", " \t\r\n" );
        }
        else {
            $string = \preg_replace( '#(?:^|[\r\n]+)(?=[^\r\n])#', '$0'.\str_repeat( $chars, $level ), $string );
        }

        return $string;
    }

    /**
     * Join an array of text or HTML elements with a string.
     *
     * @param string[] $arr
     * @param string   $glue
     *
     * @return string
     */
    public static function implode( array $arr, string $glue = '' ) : string
    {
        return \implode( $glue, $arr );
    }

    /**
     * Splits a string by a string.
     *
     * @param string $value
     * @param string $separator
     *
     * @return array
     */
    public static function explode( string $value, string $separator = '' ) : array
    {
        return $separator === ''
                ? \preg_split( '//u', $value, -1, PREG_SPLIT_NO_EMPTY )
                : \explode( $separator, $value );
    }

    /**
     * Repeats text.
     *
     * @param FilterInfo $info
     * @param mixed      $string
     * @param int        $count
     *
     * @return string
     */
    public static function repeat( FilterInfo $info, null|string|Stringable $string, int $count ) : string
    {
        return \str_repeat( (string) $string, $count );
    }

    /**
     * Date/time formatting.
     *
     * @param null|DateInterval|DateTimeInterface|int|string $time
     * @param ?string                                        $format
     *
     * @return null|string
     * @throws DateMalformedStringException
     * @throws ErrorException
     */
    public function date(
        string|int|DateTimeInterface|DateInterval|null $time,
        ?string                                        $format = null,
    ) : ?string {
        $format ??= RuntimeFilters::$dateFormat;
        if ( $time == null ) { // intentionally ==
            return null;
        }
        if ( $time instanceof DateInterval ) {
            return $time->format( $format );
        }
        if ( \is_numeric( $time ) ) {
            $time = ( new DateTime() )->setTimestamp( (int) $time );
        }
        elseif ( ! $time instanceof DateTimeInterface ) {
            $time = new DateTime( $time );
        }

        if ( \str_contains( $format, '%' ) && PHP_VERSION_ID >= 80_100 ) {
            throw new ErrorException(
                "Function strftime() used by filter |date is deprecated since PHP 8.1, use format without % characters like 'Y-m-d'.",
                E_USER_DEPRECATED,
            );
        }
        if ( \preg_match( '#^(\+(short|medium|long|full))?(\+time(\+sec)?)?$#', '+'.$format, $m ) ) {
            $formatter = new IntlDateFormatter(
                $this->getLocale( 'date' ),
                match ( $m[2] ) {
                    'short'  => IntlDateFormatter::SHORT,
                    'medium' => IntlDateFormatter::MEDIUM,
                    'long'   => IntlDateFormatter::LONG,
                    'full'   => IntlDateFormatter::FULL,
                    ''       => IntlDateFormatter::NONE,
                },
                isset( $m[3] ) ? ( isset( $m[4] ) ? IntlDateFormatter::MEDIUM : IntlDateFormatter::SHORT )
                            : IntlDateFormatter::NONE,
            );
            $res = $formatter->format( $time );
            return \preg_replace( '~(\d\.) ~', "\$1\u{a0}", $res );
        }

        return $time->format( $format );
    }

    /**
     * Converts to human-readable file size.
     *
     * @param float $bytes
     * @param int   $precision
     *
     * @return string
     */
    public function bytes( float $bytes, int $precision = 2 ) : string
    {
        $bytes = \round( $bytes );
        $units = ['B', 'kB', 'MB', 'GB', 'TB', 'PB'];

        foreach ( $units as $unit ) {
            if ( \abs( $bytes ) < 1_024 || $unit === \end( $units ) ) {
                break;
            }

            $bytes /= 1_024;
        }

        if ( $this->locale === null ) {
            $bytes = (string) \round( $bytes, $precision );
        }
        else {
            $formatter = new NumberFormatter( $this->locale, NumberFormatter::DECIMAL );
            $formatter->setAttribute( NumberFormatter::MAX_FRACTION_DIGITS, $precision );
            $bytes = $formatter->format( $bytes );
        }

        return $bytes.' '.$unit;
    }

    /**
     * Performs a search and replace.
     *
     * @param FilterInfo        $info
     * @param array|string      $subject
     * @param array|string      $search
     * @param null|array|string $replace
     *
     * @return string
     */
    public static function replace(
        FilterInfo        $info,
        string|array      $subject,
        string|array      $search,
        string|array|null $replace = null,
    ) : string {
        $subject = (string) $subject;
        if ( \is_array( $search ) ) {
            if ( \is_array( $replace ) ) {
                return \strtr( $subject, \array_combine( $search, $replace ) );
            }
            if ( $replace === null && \is_string( \key( $search ) ) ) {
                return \strtr( $subject, $search );
            }

            return \strtr( $subject, \array_fill_keys( $search, $replace ) );
        }

        return \str_replace( $search, $replace ?? '', $subject );
    }

    /**
     * Perform a regular expression search and replace.
     *
     * @param string $subject
     * @param string $pattern
     * @param string $replacement
     *
     * @return string
     */
    public static function replaceRe( string $subject, string $pattern, string $replacement = '' ) : string
    {
        $res = \preg_replace( $pattern, $replacement, $subject );
        if ( \preg_last_error() ) {
            throw new RuntimeException( \preg_last_error_msg() );
        }

        return $res;
    }

    /**
     * The data: URI generator.
     *
     * @param string  $data
     * @param ?string $type
     *
     * @return string
     */
    public static function dataStream( string $data, ?string $type = null ) : string
    {
        $type ??= \finfo_buffer( \finfo_open( FILEINFO_MIME_TYPE ), $data );
        return 'data:'.( $type ? "{$type};" : '' ).'base64,'.\base64_encode( $data );
    }

    public static function breaklines( string|Stringable|null $string ) : Html
    {
        $string = \htmlspecialchars( (string) $string, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8' );
        return new Html( \nl2br( $string, false ) );
    }

    /**
     * Returns a part of string.
     *
     * @param null|string|Stringable $string
     * @param int                    $start
     * @param ?int                   $length
     *
     * @return string
     */
    public static function substring( string|Stringable|null $string, int $start, ?int $length = null ) : string
    {
        $string = (string) $string;
        return match ( true ) {
            \extension_loaded( 'mbstring' ) => \mb_substr( $string, $start, $length, 'UTF-8' ),
            \extension_loaded( 'iconv' )    => \iconv_substr( $string, $start, $length, 'UTF-8' ),
            default                         => throw new RuntimeException(
                "Filter |substr requires 'mbstring' or 'iconv' extension.",
            ),
        };
    }

    /**
     * Truncates string to maximal length.
     *
     * @param null|string|Stringable $string
     * @param int                    $length
     * @param string                 $append
     *
     * @return string
     */
    public static function truncate(
        string|Stringable|null $string,
        int                    $length,
        string                 $append = "\u{2026}",
    ) : string {
        $string = (string) $string;
        if ( self::strLength( $string ) > $length ) {
            $length -= self::strLength( $append );
            if ( $length < 1 ) {
                return $append;
            }
            if ( \preg_match( '#^.{1,'.$length.'}(?=[\s\x00-/:-@\[-`{-~])#us', $string, $matches ) ) {
                return $matches[0].$append;
            }

            return self::substring( $string, 0, $length ).$append;
        }

        return $string;
    }

    public function webalize(
        string  $string,
        bool    $lower = true,
        ?string $charlist = null,
        ?string $language = AUTO,
    ) : string {
        $language ??= $this->locale;
        $string = \preg_replace(
            pattern     : '#[^a-z0-9'.( $charlist !== null ? \preg_quote( $charlist, '#' ) : '' ).']+#i',
            replacement : '-',
            subject     : slug( $string, '-', $lower ? '\strtolower' : null, $language ),
        );
        return \trim( $string, '-' );
    }

    /**
     * Convert to lower case.
     *
     * @param mixed $string
     */
    public static function lower( null|string|Stringable $string ) : string
    {
        return \mb_strtolower( (string) $string, 'UTF-8' );
    }

    /**
     * Convert to upper case.
     *
     * @param null|string|Stringable $string
     *
     * @return string
     */
    public static function upper( null|string|Stringable $string ) : string
    {
        return \mb_strtoupper( (string) $string, 'UTF-8' );
    }

    /**
     * Convert first character to upper case.
     *
     * @param mixed $string
     */
    public static function firstUpper( null|string|Stringable $string ) : string
    {
        $string = (string) $string;
        return self::upper( self::substring( $string, 0, 1 ) ).self::substring( $string, 1 );
    }

    /**
     * Capitalize string.
     *
     * @param mixed $string
     */
    public static function capitalize( null|string|Stringable $string ) : string
    {
        return \mb_convert_case( (string) $string, MB_CASE_TITLE, 'UTF-8' );
    }

    /**
     * Returns length of string or iterable.
     *
     * @param Countable|iterable|string $val
     *
     * @return int
     */
    public static function length( Countable|iterable|string $val ) : int
    {
        if ( \is_array( $val ) || $val instanceof Countable ) {
            return \count( $val );
        }
        if ( $val instanceof Traversable ) {
            return \iterator_count( $val );
        }

        return self::strLength( $val );
    }

    private static function strLength( null|string|Stringable $string ) : int
    {
        if ( ! $string = (string) $string ) {
            return 0;
        }

        return match ( true ) {
            \extension_loaded( 'mbstring' ) => \mb_strlen( $string, 'UTF-8' ),
            \extension_loaded( 'iconv' )    => \iconv_strlen( $string, 'UTF-8' ),
            default                         => \strlen( \mb_convert_encoding( $string, 'ISO-8859-1', 'UTF-8' ) ),
        };
    }

    /**
     * Strips whitespace.
     *
     * @param FilterInfo             $info
     * @param null|string|Stringable $string
     * @param string                 $charlist
     *
     * @return string
     */
    public static function trim(
        FilterInfo             $info,
        null|string|Stringable $string,
        string                 $charlist = " \t\n\r\0\x0B\u{A0}",
    ) : string {
        if ( ! $string = (string) $string ) {
            return '';
        }
        $charlist = \preg_quote( $charlist, '#' );
        $string   = \preg_replace( '#^['.$charlist.']+|['.$charlist.']+$#Du', '', $string );
        if ( \preg_last_error() ) {
            throw new \RuntimeException( \preg_last_error_msg() );
        }

        return $string;
    }

    /**
     * Pad a string to a certain length with another string.
     *
     * @param mixed  $string
     * @param int    $length
     * @param string $append
     *
     * @return string
     */
    public static function padLeft( null|string|Stringable $string, int $length, string $append = ' ' ) : string
    {
        $string = (string) $string;
        $length = \max( 0, $length - self::strLength( $string ) );
        $l      = self::strLength( $append );
        return \str_repeat( $append, (int) ( $length / $l ) ).self::substring( $append, 0, $length % $l ).$string;
    }

    /**
     * Pad a string to a certain length with another string.
     *
     * @param mixed  $string
     * @param int    $length
     * @param string $append
     *
     * @return string
     */
    public static function padRight( null|string|Stringable $string, int $length, string $append = ' ' ) : string
    {
        $string = (string) $string;
        $length = \max( 0, $length - self::strLength( $string ) );
        $l      = self::strLength( $append );
        return $string.\str_repeat( $append, (int) ( $length / $l ) ).self::substring( $append, 0, $length % $l );
    }

    /**
     * Reverses string or array.
     *
     * @param iterable|string $val
     * @param bool            $preserveKeys
     *
     * @return array|string
     */
    public static function reverse( string|iterable $val, bool $preserveKeys = false ) : string|array
    {
        if ( \is_array( $val ) ) {
            return \array_reverse( $val, $preserveKeys );
        }
        if ( $val instanceof Traversable ) {
            return \array_reverse( \iterator_to_array( $val ), $preserveKeys );
        }

        return \iconv( 'UTF-32LE', 'UTF-8', \strrev( \iconv( 'UTF-8', 'UTF-32BE', (string) $val ) ) );
    }

    /**
     * Chunks items by returning an array of arrays with the given number of items.
     *
     * @param iterable   $list
     * @param int        $length
     * @param null|mixed $rest
     *
     * @return Generator
     */
    public static function batch( iterable $list, int $length, mixed $rest = null ) : Generator
    {
        $batch = [];

        foreach ( $list as $key => $value ) {
            $batch[$key] = $value;
            if ( \count( $batch ) >= $length ) {
                yield $batch;
                $batch = [];
            }
        }

        if ( $batch ) {
            if ( $rest !== null ) {
                while ( \count( $batch ) < $length ) {
                    $batch[] = $rest;
                }
            }

            yield $batch;
        }
    }

    /**
     * Sorts elements using the comparison function and preserves the key association.
     * @template K
     * @template V
     *
     * @param iterable<K, V>          $data
     * @param ?Closure                $comparison
     * @param null|Closure|int|string $by
     * @param bool|Closure|int|string $byKey
     *
     * @return iterable<K, V>
     */
    public function sort(
        iterable                $data,
        ?Closure                $comparison = null,
        string|int|Closure|null $by = null,
        string|int|Closure|bool $byKey = false,
    ) : iterable {
        if ( $byKey !== false ) {
            if ( $by !== null ) {
                throw new InvalidArgumentException( 'Filter |sort cannot use both $by and $byKey.' );
            }
            $by = $byKey === true ? null : $byKey;
        }

        if ( $comparison ) {
            //
        }
        elseif ( $this->locale === null ) {
            $comparison = fn( $a, $b ) => $a <=> $b;
        }
        else {
            $collator   = new Collator( $this->locale );
            $comparison = fn( $a, $b ) => \is_string( $a ) && \is_string( $b )
                    ? $collator->compare( $a, $b )
                    : $a <=> $b;
        }

        $comparison = match ( true ) {
            $by === null           => $comparison,
            $by instanceof Closure => fn( $a, $b ) => $comparison( $by( $a ), $by( $b ) ),
            default                => fn( $a, $b ) => $comparison(
                \is_array( $a ) ? $a[$by] : $a->{$by},
                \is_array( $b ) ? $b[$by] : $b->{$by},
            ),
        };

        if ( \is_array( $data ) ) {
            $byKey ? \uksort( $data, $comparison ) : \uasort( $data, $comparison );
            return $data;
        }

        $pairs = [];

        foreach ( $data as $key => $value ) {
            $pairs[] = [$key, $value];
        }
        \uasort( $pairs, fn( $a, $b ) => $byKey ? $comparison( $a[0], $b[0] ) : $comparison( $a[1], $b[1] ) );

        return new AuxiliaryIterator( $pairs );
    }

    /**
     * Groups elements by the element indices and preserves the key association and order.
     * @template K
     * @template V
     *
     * @param iterable<K, V>     $data
     * @param Closure|int|string $by
     *
     * @return iterable<iterable<K, V>>
     */
    public static function group( iterable $data, string|int|Closure $by ) : iterable
    {
        $fn      = $by instanceof Closure ? $by : fn( $a ) => \is_array( $a ) ? $a[$by] : $a->{$by};
        $keys    = $groups = [];
        $prevKey = null;

        foreach ( $data as $k => $v ) {
            $groupKey = $fn( $v, $k );
            if ( ! $groups || $prevKey !== $groupKey ) {
                $index = \array_search( $groupKey, $keys, true );
                if ( $index === false ) {
                    $index        = \count( $keys );
                    $keys[$index] = $groupKey;
                }
                $prevKey = $groupKey;
            }
            // TODO@
            // if ( $index ) { .. }
            $groups[$index][] = [$k, $v];
        }

        return new AuxiliaryIterator(
            \array_map(
                fn( $key, $group ) => [$key, new AuxiliaryIterator( $group )],
                $keys,
                $groups,
            ),
        );
    }

    /**
     * Returns value clamped to the inclusive range of min and max.
     *
     * @param float|int $value
     * @param float|int $min
     * @param float|int $max
     *
     * @return float|int
     */
    public static function clamp( int|float $value, int|float $min, int|float $max ) : int|float
    {
        if ( $min > $max ) {
            throw new InvalidArgumentException( "Minimum ({$min}) is not less than maximum ({$max})." );
        }

        return \min( \max( $value, $min ), $max );
    }

    /**
     * Generates URL-encoded query string
     *
     * @param array|string $data
     *
     * @return string
     */
    public static function query( string|array $data ) : string
    {
        return \is_array( $data )
                ? \http_build_query( $data, '', '&' )
                : \urlencode( $data );
    }

    /**
     * Is divisible by?
     *
     * @param int $value
     * @param int $by
     *
     * @return bool
     */
    public static function divisibleBy( int $value, int $by ) : bool
    {
        return $value % $by === 0;
    }

    /**
     * Is odd?
     *
     * @param int $value
     *
     * @return bool
     */
    public static function odd( int $value ) : bool
    {
        return $value % 2 !== 0;
    }

    /**
     * Is even?
     *
     * @param int $value
     *
     * @return bool
     */
    public static function even( int $value ) : bool
    {
        return $value % 2 === 0;
    }

    /**
     * Returns the first element in an array or character in a string, or null if none.
     *
     * @param iterable|string $value
     *
     * @return mixed
     */
    public static function first( string|iterable $value ) : mixed
    {
        if ( \is_string( $value ) ) {
            return self::substring( $value, 0, 1 );
        }

        foreach ( $value as $item ) {
            return $item;
        }

        return null;
    }

    /**
     * Returns the last element in an array or character in a string, or null if none.
     *
     * @param array|string $value
     *
     * @return mixed
     */
    public static function last( string|array $value ) : mixed
    {
        return \is_array( $value )
                ? ( $value[\array_key_last( $value )] ?? null )
                : self::substring( $value, -1 );
    }

    /**
     * Extracts a slice of an array or string.
     *
     * @param array|string $value
     * @param int          $start
     * @param ?int         $length
     * @param bool         $preserveKeys
     *
     * @return array|string
     */
    public static function slice(
        string|array $value,
        int          $start,
        ?int         $length = null,
        bool         $preserveKeys = false,
    ) : string|array {
        return \is_array( $value )
                ? \array_slice( $value, $start, $length, $preserveKeys )
                : self::substring( $value, $start, $length );
    }

    public static function round( float $value, int $precision = 0 ) : float
    {
        return \round( $value, $precision );
    }

    public static function floor( float $value, int $precision = 0 ) : float
    {
        return \floor( $value * 10 ** $precision ) / 10 ** $precision;
    }

    public static function ceil( float $value, int $precision = 0 ) : float
    {
        return \ceil( $value * 10 ** $precision ) / 10 ** $precision;
    }

    /**
     * Picks random element/char.
     *
     * @param array|string $values
     *
     * @return mixed
     */
    public static function random( string|array $values ) : mixed
    {
        if ( \is_string( $values ) ) {
            $values = \preg_split( '//u', $values, -1, PREG_SPLIT_NO_EMPTY );
        }

        return $values
                ? $values[\array_rand( $values )]
                : null;
    }

    /**
     * Formats a number with grouped thousands and optionally decimal digits according to locale.
     *
     * @param float      $number
     * @param int|string $patternOrDecimals
     * @param string     $decimalSeparator
     * @param string     $thousandsSeparator
     *
     * @return string
     */
    public function number(
        float      $number,
        string|int $patternOrDecimals = 0,
        string     $decimalSeparator = '.',
        string     $thousandsSeparator = ',',
    ) : string {
        if ( \is_int( $patternOrDecimals ) && $patternOrDecimals < 0 ) {
            throw new RuntimeException( 'Filter |number: number of decimal must not be negative' );
        }
        if ( $this->locale === null || \func_num_args() > 2 ) {
            return \number_format( $number, $patternOrDecimals, $decimalSeparator, $thousandsSeparator );
        }

        $formatter = new NumberFormatter( $this->locale, NumberFormatter::DECIMAL );
        if ( \is_string( $patternOrDecimals ) ) {
            $formatter->setPattern( $patternOrDecimals );
        }
        else {
            $formatter->setAttribute( NumberFormatter::FRACTION_DIGITS, $patternOrDecimals );
        }
        return $formatter->format( $number );
    }

    /**
     * @param string $name
     *
     * @return string
     * @noinspection PhpSameParameterValueInspection
     */
    private function getLocale( string $name ) : string
    {
        if ( $this->locale === null ) {
            throw new RuntimeException(
                "Filter |{$name} requires the locale to be set using Engine::setLocale()",
            );
        }
        return $this->locale;
    }
}
