<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Runtime;

use Stringable;

use Core\View\Template\{ContentType, Exception\RuntimeException};
use Core\View\Template\Compiler\{Escaper, TemplateLexer};

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
     * @param mixed $s
     *
     * @return string
     */
    public static function escapeHtml( mixed $s ) : string
    {
        return \htmlspecialchars( (string) $s, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8' );
    }

    /**
     * Escapes string for use inside HTML text.
     *
     * @param mixed $s
     *
     * @return string
     */
    public static function escapeHtmlText( mixed $s ) : string
    {
        if ( $s instanceof Stringable ) {
            return $s->__toString();
        }

        $s = \htmlspecialchars( (string) $s, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8' );
        return \strtr( $s, ['{{' => '{<!-- -->{', '{' => '&#123;'] );
    }

    /**
     * Escapes string for use inside HTML attribute value.
     *
     * @param mixed $s
     * @param bool  $double
     *
     * @return string
     */
    public static function escapeHtmlAttr( mixed $s, bool $double = true ) : string
    {
        $double = $double && $s instanceof Stringable ? false : $double;
        $s      = (string) $s;
        if ( \str_contains( $s, '`' ) && \strpbrk( $s, ' <>"\'' ) === false ) {
            $s .= ' '; // protection against innerHTML mXSS vulnerability nette/nette#1496
        }

        $s = \htmlspecialchars( $s, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8', $double );
        return \str_replace( '{', '&#123;', $s );
    }

    /**
     * Escapes string for use inside HTML tag.
     *
     * @param mixed $s
     *
     * @return string
     */
    public static function escapeHtmlTag( mixed $s ) : string
    {
        $s = (string) $s;
        $s = \htmlspecialchars( $s, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8' );
        return \preg_replace_callback(
            '#[=/\s]#',
            fn( $m ) => '&#'.\ord( $m[0] ).';',
            $s,
        );
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

        return \htmlspecialchars( (string) $s, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8' );
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
        return \strtr( (string) $s, ['"' => '&quot;', "'" => '&apos;'] );
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
        return \htmlspecialchars( $s, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8' );
    }

    /**
     * Escapes string for use inside XML tag.
     *
     * @param mixed $s
     *
     * @return string
     */
    public static function escapeXmlTag( mixed $s ) : string
    {
        $s = self::escapeXml( (string) $s );
        return \preg_replace_callback(
            '#[=/\s]#',
            fn( $m ) => '&#'.\ord( $m[0] ).';',
            $s,
        );
    }

    /**
     * Escapes string for use inside CSS template.
     *
     * @param mixed $s
     *
     * @return string
     */
    public static function escapeCss( mixed $s ) : string
    {
        // http://www.w3.org/TR/2006/WD-CSS21-20060411/syndata.html#q6
        return \addcslashes( (string) $s, "\x00..\x1F!\"#$%&'()*+,./:;<=>?@[\\]^`{|}~" );
    }

    /**
     * Escapes variables for use inside <script>.
     *
     * @param mixed $s
     *
     * @return string
     */
    public static function escapeJs( mixed $s ) : string
    {
        if ( $s instanceof Stringable ) {
            $s = $s->__toString();
        }

        $json = \json_encode( $s, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE );

        if ( $error = \json_last_error() ) {
            throw new RuntimeException( \json_last_error_msg(), $error );
        }

        return \str_replace( [']]>', '<!', '</'], [']]\u003E', '\u003C!', '<\/'], $json );
    }

    /**
     * Escapes string for use inside iCal template.
     *
     * @param mixed $s
     *
     * @return string
     */
    public static function escapeICal( mixed $s ) : string
    {
        // https://www.ietf.org/rfc/rfc5545.txt
        $s = \str_replace( "\r", '', (string) $s );
        $s = \preg_replace( '#[\x00-\x08\x0B-\x1F]#', "\u{FFFD}", (string) $s );
        return \addcslashes( $s, "\";\\,:\n" );
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

    public static function nop( $s ) : string
    {
        return (string) $s;
    }

    /**
     * Converts JS and CSS for usage in <script> or <style>
     *
     * @param mixed $s
     *
     * @return string
     */
    public static function convertJSToHtmlRawText( mixed $s ) : string
    {
        return \preg_replace( '#</(script|style)#i', '<\/$1', (string) $s );
    }

    /**
     * Sanitizes <script> in <script type=text/html>
     *
     * @param string $s
     *
     * @return string
     */
    public static function convertHtmlToHtmlRawText( string $s ) : string
    {
        return \preg_replace( '#(</?)(script)#i', '$1x-$2', $s );
    }

    /**
     * Converts HTML text to quoted attribute. The quotation marks need to be escaped.
     *
     * @param string $s
     *
     * @return string
     */
    public static function convertHtmlToHtmlAttr( string $s ) : string
    {
        return self::escapeHtmlAttr( $s, false );
    }

    /**
     * Converts HTML to plain text.
     *
     * @param string $s
     *
     * @return string
     */
    public static function convertHtmlToText( string $s ) : string
    {
        $s = \strip_tags( $s );
        return \html_entity_decode( $s, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    }

    /**
     * Sanitizes string for use inside href attribute.
     *
     * @param mixed $s
     *
     * @return string
     */
    public static function safeUrl( mixed $s ) : string
    {
        $s = $s instanceof Stringable
                ? self::convertHtmlToText( (string) $s )
                : (string) $s;

        return \preg_match( '~^(?:(?:https?|ftp)://[^@]+(?:/.*)?|(?:mailto|tel|sms):.+|[/?#].*|[^:]+)$~Di', $s ) ? $s
                : '';
    }

    /**
     * Validates HTML tag name.
     *
     * @param mixed $name
     * @param bool  $xml
     *
     * @return string
     */
    public static function safeTag( mixed $name, bool $xml = false ) : string
    {
        if ( ! \is_string( $name ) ) {
            throw new RuntimeException( 'Tag name must be string, '.\get_debug_type( $name ).' given' );
        }
        if ( ! \preg_match( '~'.TemplateLexer::ReTagName.'$~DA', $name ) ) {
            throw new RuntimeException( "Invalid tag name '{$name}'" );
        }
        if ( ! $xml && \in_array( \strtolower( $name ), ['style', 'script'], true ) ) {
            throw new RuntimeException( "Forbidden variable tag name <{$name}>" );
        }
        return $name;
    }
}
