<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Compiler\Nodes\Php\Expression\ArrayNode;
use Core\View\Template\ContentType;
use Generator;
use Core\View\Template\Compiler\{PrintContext, Tag, TemplateGenerator};

/**
 * n:attr="..."
 */
final class NAttrNode extends StatementNode
{
    public ArrayNode $args;

    /**
     * @param Tag $tag
     *
     * @return NAttrNode
     * @throws \Core\View\Template\Exception\CompileException
     */
    public static function create( Tag $tag ) : static
    {
        $tag->expectArguments();
        $node       = new self();
        $node->args = $tag->parser->parseArguments();
        return $node;
    }

    public function print( ?PrintContext $context ) : string
    {
        $tmp = TemplateGenerator::ARG_TEMP;
        $loc = TemplateGenerator::ARG_LOC;
        $fl  = TemplateGenerator::ARG_FILTER;
        $ns  = TemplateGenerator::NAMESPACE;
        return $context->format(
            <<<EOD
                {$tmp} = %node;
                			echo %raw::attrs(isset({$tmp}[0]) && is_array({$tmp}[0]) ? {$tmp}[0] : {$tmp}, %dump) %line;
                EOD,
            $this->args,
            self::class,
            $context->getEscaper()->getContentType() === ContentType::XML,
            $this->position,
        );
    }

    /**
     * @internal
     *
     * @param mixed $attrs
     * @param bool  $xml
     *
     * @return string
     */
    public static function attrs( $attrs, bool $xml ) : string
    {
        if ( ! \is_array( $attrs ) ) {
            return '';
        }

        $s = '';

        foreach ( $attrs as $key => $value ) {
            if ( $value === null || $value === false ) {
                continue;
            }
            if ( $value === true ) {
                $s .= ' '.$key.( $xml ? '="'.$key.'"' : '' );

                continue;
            }
            if ( \is_array( $value ) ) {
                $tmp = null;

                foreach ( $value as $k => $v ) {
                    if ( $v != null ) { // intentionally ==, skip nulls & empty string
                        //  composite 'style' vs. 'others'
                        $tmp[] = $v === true
                                ? $k
                                : ( \is_string( $k ) ? $k.':'.$v : $v );
                    }
                }

                if ( $tmp === null ) {
                    continue;
                }

                $value = \implode( $key === 'style' || ! \strncmp( $key, 'on', 2 ) ? ';' : ' ', $tmp );
            }
            else {
                $value = (string) $value;
            }

            $q = ! \str_contains( $value, '"' ) ? '"' : "'";
            $s .= ' '.$key.'='.$q
                  .\str_replace(
                      ['&', $q, '<'],
                      ['&amp;', $q === '"' ? '&quot;' : '&#39;', $xml ? '&lt;' : '<'],
                      $value,
                  )
                  .( \str_contains( $value, '`' ) && \strpbrk( $value, ' <>"\'' ) === false ? ' ' : '' )
                  .$q;
        }

        return $s;
    }

    public function &getIterator() : Generator
    {
        yield $this->args;
    }
}
