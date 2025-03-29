<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

/**
 * Suppress {@see Nette\Localization\Translator} warnings.
 *
 * @noinspection PhpUndefinedNamespaceInspection
 * @noinspection PhpUndefinedClassInspection
 */

declare(strict_types=1);

namespace Core\View\Template\Engine;

use Core\View\Template\Compiler\Nodes\Php\Expression\ArrayNode;
use Core\View\Template\Compiler\Nodes\Php\Scalar\StringNode;
use Core\View\Template\{Compiler\Nodes\PrintNode, Exception\CompileException, Engine, Extension};
use Core\View\Template\Compiler\{NodeHelpers, Tag};
use Core\View\Template\Compiler\Nodes\Php\{FilterNode, IdentifierNode};
use Core\View\Template\Compiler\Nodes\{TranslateNode};
use Core\View\Template\Runtime\FilterInfo;
use InvalidArgumentException;
use Nette\Localization\Translator;

/**
 * Extension for translations.
 */
final class TranslatorExtension extends Extension
{
    /** @var null|callable|Translator */
    private $translator;

    public function __construct(
        callable|Translator|null $translator,
        private readonly ?string $key = null,
    ) {
        $this->translator = $translator;
        if ( $translator instanceof Translator ) {
            $this->translator = [$translator, 'translate'];
        }
    }

    public function getTags() : array
    {
        return [
            '_'         => [$this, 'parseTranslate'],
            'translate' => fn( Tag $tag ) => yield from TranslateNode::create(
                $tag,
                $this->key ? $this->translator : null,
            ),
        ];
    }

    public function getFilters() : array
    {
        return [
            'translate' => fn( FilterInfo $fi, ...$args ) : string => $this->translator
                    ? ( $this->translator )( ...$args )
                    : $args[0],
        ];
    }

    public function getCacheKey( Engine $engine ) : ?string
    {
        return $this->key;
    }

    /**
     * {_ ...}
     *
     * @param Tag $tag
     *
     * @return PrintNode
     * @throws CompileException
     */
    public function parseTranslate( Tag $tag ) : PrintNode
    {
        $tag->outputMode = $tag::OutputKeepIndentation;
        $tag->expectArguments();
        $node             = new PrintNode();
        $node->expression = $tag->parser->parseUnquotedStringOrExpression();
        $args             = new ArrayNode();
        if ( $tag->parser->stream->tryConsume( ',' ) ) {
            $args = $tag->parser->parseArguments();
        }

        $node->modifier         = $tag->parser->parseModifier();
        $node->modifier->escape = true;

        if ( $this->translator
             && $this->key
             && ( $expr = self::toValue( $node->expression ) )
             && \is_array( $values = self::toValue( $args ) )
             && \is_string( $translation = ( $this->translator )( $expr, ...$values ) )
        ) {
            $node->expression = new StringNode( $translation );
            return $node;
        }

        \array_unshift(
            $node->modifier->filters,
            new FilterNode(
                new IdentifierNode( 'translate' ),
                $args->toArguments(),
            ),
        );
        return $node;
    }

    public static function toValue( $args ) : mixed
    {
        try {
            return NodeHelpers::toValue( $args, constants : true );
        }
        catch ( InvalidArgumentException ) {
            return null;
        }
    }
}
