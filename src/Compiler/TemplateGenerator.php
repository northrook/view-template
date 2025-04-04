<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler;

use Core\Profiler\{ClerkProfiler};
use Core\View\Template\Support\PhpGenerator;
use Core\View\Template\Compiler\Nodes\{NopNode, TemplateNode, TextNode};
use Core\View\Template\ContentType;

/**
 * @internal
 */
final class TemplateGenerator
{
    public const string FQN = 'Core\\View\\Template\\Runtime';

    public const string NAMESPACE = 'Runtime';

    /** $ʟ_args */
    public const string ARGS = '$__args__';

    /** $ʟ_v */
    public const string ARG_VAR = '$__var__';

    /** $ʟ_fi */
    public const string ARG_FILTER = '$__filter__';

    /** $ʟ_tmp */
    public const string ARG_TEMP = '$__temp__';

    /** $ʟ_tag */
    public const string ARG_TAG = '$__tag__';

    /** $ʟ_loc */
    public const string ARG_LOC = '$__loc__';

    /** $ʟ_ifA */
    public const string ARG_IF_A = '$__if_a__';

    /** $ʟ_ifB */
    public const string ARG_IF_B = '$__if_b__';

    /** $ʟ_ifc */
    public const string ARG_IF_C = '$__if_c__';

    /** $ʟ_try */
    public const string ARG_TRY = '$__try__';

    /** $ʟ_it */
    public const string ARG_IT = '$__it__';

    /** $ʟ_nm */
    public const string ARG_NAME = '$__name__';

    /** $ʟ_bp */
    public const string ARG_BEGIN_PRINT = '$__begin_print__';

    /** $ʟ_switch */
    public const string ARG_SWITCH = '$__switch__';

    /** $ʟ_e */
    public const string ARG_EXCEPTION = '$__exception__';

    /** $ʟ_l */
    public const string ARG_LINE = '$__line__';

    public function __construct( protected readonly ?ClerkProfiler $profiler ) {}

    /**
     * Compiles nodes to PHP file
     *
     * @param TemplateNode $node
     * @param string       $className
     * @param ?string      $templateName
     * @param bool         $strictMode
     *
     * @return string
     */
    public function generate(
        TemplateNode $node,
        string       $className,
        ?string      $templateName = null,
        bool         $strictMode = false,
    ) : string {
        $profiler = $this->profiler?->event( "template.generate.{$templateName}" );

        $context   = new PrintContext( $node->contentType );
        $generator = new PhpGenerator(
            className : $className,
            generator : $this::class,
        );

        $code = $node->main->print( $context );
        $code = $this->templateParameters( $code, [], self::ARGS, $context );
        $profiler?->lap();

        $generator
                // TODO : Multi-line $templateName should be treated as a phpdoc codeblock
            ->comment( $templateName ? "Source: {$templateName}" : null )
            ->final()
            ->uses( $this::FQN )
            ->extends( $this::NAMESPACE.'\\Template' )
            ->addMethod( 'main', $code, 'array '.self::ARGS );
        $profiler?->lap();

        $head = ( new NodeTraverser() )->traverse(
            $node->head,
            fn( Node $node ) => $node instanceof TextNode ? new NopNode() : $node,
        );

        $code = $head->print( $context );
        $profiler?->lap();

        if ( $code || $context->paramsExtraction ) {
            $code .= 'return get_defined_vars();';
            $code = $this->templateParameters(
                $code,
                $context->paramsExtraction,
                '$this->parameters',
                $context,
            );
            $generator->addMethod( 'prepare', $code, returns : 'array' );
        }

        if ( $node->contentType !== ContentType::HTML ) {
            $generator->addConstant( 'ContentType', $node->contentType );
        }

        if ( $templateName !== null && ! \str_contains( $templateName, "\n" ) ) {
            $generator->addConstant( 'SOURCE', $templateName );
        }

        $contentType = $context->getEscaper()->getContentType();
        $blocks      = $context->blocks;

        foreach ( $blocks as $block ) {
            $isDynamic = $block->isDynamic();

            if ( ! $isDynamic && \property_exists( $block->name, 'value' ) ) {
                $meta[$block->layer][$block->name->value]
                        = $contentType->type() === $block->escaping
                        ? $block->method
                        : [$block->method, $block->escaping];
            }

            $body = $this->templateParameters( $block->content, $block->parameters, self::ARGS, $context );
            if ( ! $isDynamic && \str_contains( $body, '$' ) ) {
                $embedded = $block->tag->name === 'block' && \is_int( $block->layer ) && $block->layer;
                $body     = 'extract( '.( $embedded ? 'end($this->varStack)' : '$this->parameters' ).' );'.$body;
            }

            $generator->addMethod(
                name      : $block->method,
                code      : $body,
                arguments : 'array '.self::ARGS,
                comment   : $block->tag->getNotation( true ).' on line '.$block->tag->position->line,
            );
        }
        $profiler?->lap();

        if ( isset( $meta ) ) {
            $generator->addConstant(
                name  : 'BLOCKS',
                value : $meta,
            );
        }

        $template = $generator->toString();

        $profiler?->stop();

        return $template;
    }

    private function templateParameters(
        string       $body,
        array        $params,
        string       $cont,
        PrintContext $context,
    ) : string {
        if ( ! \str_contains( $body, '$' ) && ! \str_contains( $body, 'get_defined_vars()' ) ) {
            return $body;
        }

        $res = [];

        foreach ( $params as $i => $param ) {
            $res[] = $context->format(
                '%node = %raw[%dump] ?? %raw[%dump] ?? %node;',
                $param->var,
                $cont,
                $i,
                $cont,
                $param->var->name,
                $param->default,
            );
        }
        $extract = $params
                ? \implode( '', $res ).'unset( '.self::ARGS.' );'
                : "extract( {$cont} );\n".( \str_contains( $cont, '$this' ) ? '' : "unset( {$cont} );" );
        return $extract."\n\n".$body;
    }
}
