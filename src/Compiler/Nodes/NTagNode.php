<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\{Exception\CompileException, ContentType, Support\Helpers, Exception\RuntimeException};
use Core\View\Template\Compiler\Nodes\Php\Expression\AuxiliaryNode;
use LogicException;
use Core\View\Template\Compiler\{PrintContext, Tag, TemplateParser};

/**
 * n:tag="..."
 */
final class NTagNode extends StatementNode
{
    /**
     * @param Tag            $tag
     * @param TemplateParser $parser
     *
     * @throws CompileException
     */
    public static function create(
        Tag            $tag,
        TemplateParser $parser,
    ) : void {
        if ( \preg_match( '(style$|script$)iA', $tag->htmlElement->name ) ) {
            throw new CompileException(
                /** @lang text */
                "Attribute n:tag is not allowed in '<script>' or '<style>'",
                $tag->position,
            );
        }

        $tag->expectArguments();
        $tag->htmlElement->variableName = new AuxiliaryNode(
            fn( PrintContext $context, $newName ) => $context->format(
                self::class.'::check(%dump, %node, %dump)',
                $tag->htmlElement->name,
                $newName,
                $parser->getContentType() === ContentType::XML,
            ),
            [$tag->parser->parseExpression()],
        );
    }

    /**
     * @param PrintContext $context
     *
     * @return string
     */
    public function print( PrintContext $context ) : string
    {
        throw new LogicException( 'Cannot directly print' );
    }

    public static function check(
        string $orig,
        mixed  $new,
        bool   $xml,
    ) : mixed {
        if ( $new === null ) {
            return $orig;
        }
        if ( ! $xml
             && \is_string( $new )
             && isset(
                 Helpers::$emptyElements[\strtolower( $orig )],
             ) !== isset( Helpers::$emptyElements[\strtolower( $new )] )
        ) {
            throw new RuntimeException( "Forbidden tag <{$orig}> change to <{$new}>" );
        }

        return $new;
    }
}
