<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler;

use Core\View\Template\Compiler\Nodes\Html\ElementNode;
use Core\View\Template\ContentType;
use Core\View\Template\Runtime\Filters;
use LogicException;

/**
 * Context-aware escaping.
 */
final class Escaper
{
    public const string
        Text       = 'text',
        JavaScript = 'js',
        Css        = 'css',
        ICal       = 'ical',
        Url        = 'url';

    public const string
        HtmlText      = 'html',
        HtmlComment   = 'html/comment',
        HtmlBogusTag  = 'html/bogus',
        HtmlRawText   = 'html/raw',
        HtmlTag       = 'html/tag',
        HtmlAttribute = 'html/attr';

    private const array Convertors = [
        self::Text => [
            self::HtmlText                           => 'escapeHtmlText',
            self::HtmlAttribute                      => 'escapeHtmlAttr',
            self::HtmlAttribute.'/'.self::JavaScript => 'escapeHtmlAttr',
            self::HtmlAttribute.'/'.self::Css        => 'escapeHtmlAttr',
            self::HtmlAttribute.'/'.self::Url        => 'escapeHtmlAttr',
            self::HtmlComment                        => 'escapeHtmlComment',
            'xml'                                    => 'escapeXml',
            'xml/attr'                               => 'escapeXml',
        ],
        self::JavaScript => [
            self::HtmlText                           => 'escapeHtmlText',
            self::HtmlAttribute                      => 'escapeHtmlAttr',
            self::HtmlAttribute.'/'.self::JavaScript => 'escapeHtmlAttr',
            self::HtmlRawText.'/'.self::JavaScript   => 'convertJSToHtmlRawText',
            self::HtmlComment                        => 'escapeHtmlComment',
        ],
        self::Css => [
            self::HtmlText                    => 'escapeHtmlText',
            self::HtmlAttribute               => 'escapeHtmlAttr',
            self::HtmlAttribute.'/'.self::Css => 'escapeHtmlAttr',
            self::HtmlRawText.'/'.self::Css   => 'convertJSToHtmlRawText',
            self::HtmlComment                 => 'escapeHtmlComment',
        ],
        self::HtmlText => [
            self::HtmlAttribute                      => 'convertHtmlToHtmlAttr',
            self::HtmlAttribute.'/'.self::JavaScript => 'convertHtmlToHtmlAttr',
            self::HtmlAttribute.'/'.self::Css        => 'convertHtmlToHtmlAttr',
            self::HtmlAttribute.'/'.self::Url        => 'convertHtmlToHtmlAttr',
            self::HtmlComment                        => 'escapeHtmlComment',
            self::HtmlRawText.'/'.self::HtmlText     => 'convertHtmlToHtmlRawText',
        ],
        self::HtmlAttribute => [
            self::HtmlText => 'convertHtmlToHtmlAttr',
        ],
        self::HtmlAttribute.'/'.self::Url => [
            self::HtmlText      => 'convertHtmlToHtmlAttr',
            self::HtmlAttribute => 'nop',
        ],
    ];

    private string $state;

    private string $tag = '';

    private string $subType = '';

    private readonly string $ns;

    public function __construct(
        private ContentType $contentType,
    ) {
        $this->ns    = TemplateGenerator::NAMESPACE;
        $this->state = \in_array( $contentType, [ContentType::HTML, ContentType::XML], true )
                ? self::HtmlText
                : $contentType->value;
    }

    public function getContentType() : ContentType
    {
        return $this->contentType;
    }

    public function getState() : string
    {
        return $this->state;
    }

    public function export() : string
    {
        return $this->state.( $this->subType ? '/'.$this->subType : '' );
    }

    public function enterHtmlRaw( ContentType|string $subType ) : void
    {
        $this->state   = self::HtmlRawText;
        $this->subType = $subType instanceof ContentType ? $subType->value : $subType;
    }

    public function enterHtmlText( ElementNode $el ) : void
    {
        if ( $el->isRawText() ) {
            $this->state   = self::HtmlRawText;
            $this->subType = self::Text;
            if ( $el->is( 'script' ) ) {
                $type = $el->getAttribute( 'type' );
                if ( $type === true || $type === null
                                    || \is_string( $type )
                        && \preg_match(
                            pattern : '#((application|text)/(((x-)?java|ecma|j|live)script|json)|application/.+\+json|text/plain|module|importmap|)$#Ai',
                            subject : $type,
                        )
                ) {
                    $this->subType = self::JavaScript;
                }
                elseif ( \is_string( $type ) && \preg_match( '#text/((x-)?template|html)$#Ai', $type ) ) {
                    $this->subType = self::HtmlText;
                }
            }
            elseif ( $el->is( 'style' ) ) {
                $this->subType = self::Css;
            }
        }
        else {
            $this->state   = self::HtmlText;
            $this->subType = '';
        }
    }

    public function enterHtmlTag( string $name ) : void
    {
        $this->state = self::HtmlTag;
        $this->tag   = \strtolower( $name );
    }

    public function enterContentType( ContentType $type ) : void
    {
        $this->state       = $type->value;
        $this->contentType = $type;
    }

    public function enterHtmlAttribute( ?string $name = null ) : void
    {
        $this->state   = self::HtmlAttribute;
        $this->subType = '';

        if ( $this->contentType === ContentType::HTML && \is_string( $name ) ) {
            $name = \strtolower( $name );
            if ( \str_starts_with( $name, 'on' ) ) {
                $this->subType = self::JavaScript;
            }
            elseif ( $name === 'style' ) {
                $this->subType = self::Css;
            }
            elseif ( ( \in_array( $name, ['href', 'src', 'action', 'formaction'], true )
                       || ( $name === 'data' && $this->tag === 'object' ) )
            ) {
                $this->subType = self::Url;
            }
        }
    }

    public function enterHtmlBogusTag() : void
    {
        $this->state = self::HtmlBogusTag;
    }

    public function enterHtmlComment() : void
    {
        $this->state = self::HtmlComment;
    }

    public function escape( string $str ) : string
    {
        /**
         *Always have a `default` fallback
         *
         * @noinspection PhpUnusedMatchConditionInspection
         */
        return match ( $this->contentType ) {
            ContentType::HTML => match ( $this->state ) {
                self::HtmlText      => $this->ns.'\Filters::escapeHtmlText('.$str.')',
                self::HtmlTag       => $this->ns.'\Filters::escapeHtmlTag('.$str.')',
                self::HtmlAttribute => match ( $this->subType ) {
                    '',
                    self::Url        => $this->ns.'\Filters::escapeHtmlAttr('.$str.')',
                    self::JavaScript => $this->ns.'\Filters::escapeHtmlAttr('.$this->ns.'\Filters::escapeJs('.$str.'))',
                    self::Css        => $this->ns.'\Filters::escapeHtmlAttr('.$this->ns.'\Filters::escapeCss('.$str.'))',
                },
                self::HtmlComment  => $this->ns.'\Filters::escapeHtmlComment('.$str.')',
                self::HtmlBogusTag => $this->ns.'\Filters::escapeHtml('.$str.')',
                self::HtmlRawText  => match ( $this->subType ) {
                    self::Text       => $this->ns.'\Filters::convertJSToHtmlRawText('.$str.')', // sanitization, escaping is not possible
                    self::HtmlText   => $this->ns.'\Filters::escapeHtmlRawTextHtml('.$str.')',
                    self::JavaScript => $this->ns.'\Filters::escapeJs('.$str.')',
                    self::Css        => $this->ns.'\Filters::escapeCss('.$str.')',
                },
                default => throw new LogicException(
                    "Unknown context {$this->contentType}, {$this->state}.",
                ),
            },
            ContentType::XML => match ( $this->state ) {
                self::HtmlText,
                self::HtmlBogusTag,
                self::HtmlAttribute => $this->ns.'\Filters::escapeXml('.$str.')',
                self::HtmlComment   => $this->ns.'\Filters::escapeHtmlComment('.$str.')',
                self::HtmlTag       => $this->ns.'\Filters::escapeXmlTag('.$str.')',
                default             => throw new LogicException(
                    "Unknown context {$this->contentType}, {$this->state}.",
                ),
            },
            ContentType::JS   => $this->ns.'\Filters::escapeJs('.$str.')',
            ContentType::CSS  => $this->ns.'\Filters::escapeCss('.$str.')',
            ContentType::ICAL => $this->ns.'\Filters::escapeIcal('.$str.')',
            ContentType::TEXT => '($this->filters->escape)('.$str.')',
            default           => throw new LogicException( "Unknown content-type {$this->contentType}." ),
        };
    }

    public function escapeMandatory( string $str ) : string
    {
        return match ( $this->contentType ) {
            ContentType::HTML => match ( $this->state ) {
                self::HtmlAttribute => $this->ns."\\Filters::escapeHtmlQuotes({$str})",
                self::HtmlRawText   => match ( $this->subType ) {
                    self::HtmlText => $this->ns.'\Filters::convertHtmlToHtmlRawText('.$str.')',
                    default        => $this->ns."\\Filters::convertJSToHtmlRawText({$str})",
                },
                self::HtmlComment => $this->ns.'\Filters::escapeHtmlComment('.$str.')',
                default           => $str,
            },
            ContentType::XML => match ( $this->state ) {
                self::HtmlAttribute => $this->ns."\\Filters::escapeHtmlQuotes({$str})",
                self::HtmlComment   => $this->ns.'\Filters::escapeHtmlComment('.$str.')',
                default             => $str,
            },
            default => $str,
        };
    }

    public function check( string $str ) : string
    {
        if ( $this->state === self::HtmlAttribute && $this->subType === self::Url ) {
            $str = $this->ns.'\Filters::safeUrl('.$str.')';
        }
        return $str;
    }

    public static function getConvertor(
        string|ContentType $source,
        string|ContentType $dest,
    ) : ?callable {
        if ( $source instanceof ContentType ) {
            $source = $source->value;
        }
        if ( $dest instanceof ContentType ) {
            $dest = $dest->value;
        }

        return match ( true ) {
            $source === $dest                         => [Filters::class, 'nop'],
            isset( self::Convertors[$source][$dest] ) => [Filters::class, self::Convertors[$source][$dest]],
            default                                   => null,
        };
    }
}
