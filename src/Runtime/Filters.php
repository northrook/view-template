<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Runtime;

use Core\View\Element\Attributes;
use Stringable;

use Core\View\Template\{ContentType, Exception\RuntimeException};
use Core\View\Template\Compiler\{Escaper};
use function Support\is_stringable;
use const Support\{ENCODED_APOSTROPHE, ENCODED_QUOTE, ENCODED_SPACE};

/**
 * Escaping & sanitization filters.
 *
 * @internal
 */
class Filters
{
    /**
     * TODO@ Create globally settable default
     *
     * @deprecated
     */
    public static string $dateFormat = "j.\u{a0}n.\u{a0}Y";

    /**
     * Escapes string for use everywhere inside HTML (except for comments).
     *
     * @param mixed $value
     *
     * @return string
     */
    public static function escapeHtml( mixed $value ) : string
    {
        return \htmlspecialchars( (string) $value, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, CHARSET );
    }

    /**
     * Escapes string for use inside HTML text.
     *
     * @param mixed $value
     *
     * @return string
     */
    public static function escapeHtmlText( mixed $value ) : string
    {
        if ( $value instanceof Stringable ) {
            return $value->__toString();
        }

        $value = \htmlspecialchars( (string) $value, ENT_NOQUOTES | ENT_SUBSTITUTE, CHARSET );
        return \strtr( $value, ['{{' => '{<!-- -->{', '{' => '&#123;'] );
    }

    /**
     * Escapes string for use inside HTML attribute value.
     *
     * @param mixed $value
     * @param bool  $double
     *
     * @return string
     */
    public static function escapeHtmlAttr( mixed $value, bool $double = true ) : string
    {
        $double = $double && $value instanceof Stringable ? false : $double;
        $value  = (string) $value;
        if ( \str_contains( $value, '`' ) && \strpbrk( $value, ' <>"\'' ) === false ) {
            $value .= ' '; // protection against innerHTML mXSS vulnerability nette/nette#1496
        }

        $value = \htmlspecialchars( $value, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, CHARSET, $double );
        return \str_replace( '{', '&#123;', $value );
    }

    /**
     * Escapes string for use inside HTML tag.
     *
     * @param mixed $value
     *
     * @return string
     */
    public static function escapeHtmlTag( mixed $value ) : string
    {
        // TODO: [lo] Parse iterables as [attribute=>value] and escape each
        $escape = match ( true ) {
            $value instanceof Attributes => $value->resolveAttributes( true ),
            $value instanceof Stringable => [(string) $value],
            default                      => throw new RuntimeException( 'Unexpected value type.' ),
        };

        $attributes = [];

        foreach ( $escape as $attribute => $value ) {
            $encoded = \preg_replace_callback(
                '#[=/\s]#',
                fn( $m ) => '&#'.\ord( $m[0] ).';',
                \htmlspecialchars( $value, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, CHARSET ),
            );
            $value = \str_replace( ENCODED_SPACE, WHITESPACE, $encoded );

            if ( \is_string( $attribute ) ) {
                $value = " {$attribute}=\"{$value}\"";
            }

            $attributes[] = $value;
        }

        return \implode( ' ', $attributes );
    }

    /**
     * Escapes string for use inside HTML/XML comments.
     *
     * @param mixed $s
     *
     * @return string
     */
    public static function escapeHtmlComment( mixed $s ) : string
    {
        $s = (string) $s;
        if ( $s && ( $s[0] === '-' || $s[0] === '>' || $s[0] === '!' ) ) {
            $s = ' '.$s;
        }

        $s = \str_replace( '--', '- - ', $s );
        if ( \str_ends_with( $s, '-' ) ) {
            $s .= ' ';
        }

        return $s;
    }

    /**
     * Escapes HTML for usage in <script type=text/html>
     *
     * @param mixed $s
     *
     * @return string
     */
    public static function escapeHtmlRawTextHtml( mixed $s ) : string
    {
        if ( $s instanceof Stringable ) {
            return self::convertHtmlToHtmlRawText( $s->__toString() );
        }

        return \htmlspecialchars( (string) $s, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, CHARSET );
    }

    /**
     * Escapes only quotes.
     *
     * @param mixed $s
     *
     * @return string
     */
    public static function escapeHtmlQuotes( mixed $s ) : string
    {
        return \strtr( (string) $s, ['"' => ENCODED_QUOTE, "'" => ENCODED_APOSTROPHE] );
    }

    /**
     * Escapes string for use everywhere inside XML (except for comments and tags).
     *
     * @param mixed $s
     *
     * @return string
     */
    public static function escapeXml( mixed $s ) : string
    {
        if ( $s instanceof Stringable ) {
            return $s->__toString();
        }

        // XML 1.0: \x09 \x0A \x0D and C1 allowed directly, C0 forbidden
        // XML 1.1: \x00 forbidden directly and as a character reference,
        //   \x09 \x0A \x0D \x85 allowed directly, C0, C1 and \x7F allowed as character references
        $s = \preg_replace( '#[\x00-\x08\x0B\x0C\x0E-\x1F]#', "\u{FFFD}", (string) $s );
        return \htmlspecialchars( $s, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, CHARSET );
    }

    /**
     * Escapes string for use inside XML tag.
     *
     * @param mixed $value
     *
     * @return string
     */
    public static function escapeXmlTag( mixed $value ) : string
    {
        $string = self::escapeXml( (string) $value );
        return \preg_replace_callback(
            '#[=/\s]#',
            static fn( $m ) => '&#'.\ord( $m[0] ).';',
            $string,
        );
    }

    /**
     * Escapes string for use inside CSS template.
     *
     * @param mixed $value
     *
     * @return string
     */
    public static function escapeCss( mixed $value ) : string
    {
        // http://www.w3.org/TR/2006/WD-CSS21-20060411/syndata.html#q6
        return \addcslashes( (string) $value, "\x00..\x1F!\"#$%&'()*+,./:;<=>?@[\\]^`{|}~" );
    }

    /**
     * Escapes variables for use inside <script>.
     *
     * @param mixed $value
     *
     * @return string
     */
    public static function escapeJs( mixed $value ) : string
    {
        if ( $value instanceof Stringable ) {
            $value = $value->__toString();
        }

        $json = \json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE );

        if ( $error = \json_last_error() ) {
            throw new RuntimeException( \json_last_error_msg(), $error );
        }

        return \str_replace( [']]>', '<!', '</'], [']]\u003E', '\u003C!', '<\/'], $json );
    }

    /**
     * Escapes string for use inside iCal template.
     *
     * @param mixed $value
     *
     * @return string
     */
    public static function escapeICal( mixed $value ) : string
    {
        // https://www.ietf.org/rfc/rfc5545.txt
        $string = (string) \str_replace( "\r", '', (string) $value );
        $string = \preg_replace( '#[\x00-\x08\x0B-\x1F]#', "\u{FFFD}", $string );
        return \addcslashes( $string, "\";\\,:\n" );
    }

    /**
     * Converts ... to ...
     *
     * @param FilterInfo         $info
     * @param ContentType|string $dest
     * @param string             $string
     *
     * @return string
     */
    public static function convertTo(
        FilterInfo         $info,
        string|ContentType $dest,
        string             $string,
    ) : string {
        $dest   = ContentType::from( $dest );
        $source = $info->contentType ?: ContentType::TEXT;
        if ( $source === $dest ) {
            return $string;
        }
        if ( $conv = Escaper::getConvertor( $source, $dest ) ) {
            $info->contentType = ContentType::from( $dest );
            return $conv( $string );
        }

        throw new RuntimeException(
            "Filters: unable to convert content type '{$source->name}' to '{$dest->name}'",
        );
    }

    public static function nop( $value ) : string
    {
        return (string) $value;
    }

    /**
     * Converts JS and CSS for usage in <script> or <style>
     *
     * @param mixed $value
     *
     * @return string
     */
    public static function convertJSToHtmlRawText( mixed $value ) : string
    {
        return \preg_replace( '#</(script|style)#i', '<\/$1', (string) $value );
    }

    /**
     * Sanitizes <script> in <script type=text/html>
     *
     * @param string $value
     *
     * @return string
     */
    public static function convertHtmlToHtmlRawText( string $value ) : string
    {
        return \preg_replace( '#(</?)(script)#i', '$1x-$2', $value );
    }

    /**
     * Converts HTML text to quoted attribute. The quotation marks need to be escaped.
     *
     * @param string $value
     *
     * @return string
     */
    public static function convertHtmlToHtmlAttr( string $value ) : string
    {
        return self::escapeHtmlAttr( $value, false );
    }

    /**
     * Converts HTML to plain text.
     *
     * @param string $value
     *
     * @return string
     */
    public static function convertHtmlToText( string $value ) : string
    {
        return \html_entity_decode(
            \strip_tags( $value ),
            ENT_QUOTES | ENT_HTML5,
            CHARSET,
        );
    }

    /**
     * Sanitizes string for use inside href attribute.
     *
     * @param mixed $value
     *
     * @return string
     */
    public static function safeUrl( mixed $value ) : string
    {
        $string = $value instanceof Stringable
                ? self::convertHtmlToText( (string) $value )
                : (string) $value;

        return \preg_match( '~^(?:(?:https?|ftp)://[^@]+(?:/.*)?|(?:mailto|tel|sms):.+|[/?#].*|[^:]+)$~Di', $string )
                ? $string
                : '';
    }

    /**
     * Validates HTML tag name.
     *
     * @param mixed $value
     * @param bool  $xml
     *
     * @return string
     */
    public static function safeTag( mixed $value, bool $xml = false ) : string
    {
        // $value be castable to (string)
        if ( ! is_stringable( $value ) ) {
            $type = \get_debug_type( $value );
            throw new RuntimeException( "Tag name must be string, '{$type}' provided." );
        }

        $name = \strtolower( \trim( (string) $value ) );

        if ( ! $name ) {
            throw new RuntimeException( 'Tag name cannot be empty' );
        }

        if ( ! \ctype_alpha( $name[0] ) ) {
            throw new \RuntimeException( "Tags must start with an ASCII letter, '{$name[0]}' provided." );
        }

        if ( ! \ctype_alnum( \str_replace( [':', '_', '.', '-'], '', $name ) ) ) {
            throw new RuntimeException( "Invalid tag name '{$name}'" );
        }

        if ( $name[0] === 'h' && ! \is_numeric( \substr( $name, 1 ) ) ) {
            throw new RuntimeException(
                "Invalid tag name '{$name}'. Headings must be 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'",
            );
        }

        if ( ! $xml && \in_array( \strtolower( $name ), ['style', 'script'], true ) ) {
            throw new RuntimeException( "Forbidden variable tag name <{$name}>" );
        }

        return $name;
    }
}
