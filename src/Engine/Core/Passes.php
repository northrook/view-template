<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Engine\Core;

use Core\View\Template\{Exception\CompileException, Engine};
use Core\View\Template\Compiler\{Node, NodeTraverser, PrintContext};
use Core\View\Template\Compiler\Nodes\Php\{Expression, NameNode};
use Core\View\Template\Compiler\Nodes\Php\Expression\VariableNode;
use Core\View\Template\Compiler\Nodes\TemplateNode;

/**
 * @internal
 */
final readonly class Passes
{
    public function __construct( private Engine $engine ) {}

    /**
     * Enable custom functions.
     *
     * @param TemplateNode $node
     */
    public function customFunctionsPass( TemplateNode $node ) : void
    {
        $functions = $this->engine->getFunctions();
        $names     = \array_keys( $functions );
        $names     = \array_combine( \array_map( 'strtolower', $names ), $names );

        ( new NodeTraverser() )->traverse(
            $node,
            function( Node $node ) use ( $names ) {
                if ( ( $node instanceof Expression\FunctionCallNode || $node instanceof Expression\FunctionCallableNode )
                     && $node->name instanceof NameNode
                     && ( $orig = $names[\strtolower( (string) $node->name )] ?? null )
                ) {
                    if ( (string) $node->name !== $orig ) {
                        \trigger_error(
                            "Case mismatch on function name '{$node->name}', correct name is '{$orig}'.",
                            E_USER_WARNING,
                        );
                    }

                    return new Expression\AuxiliaryNode(
                        fn(
                            PrintContext $context,
                                      ...$args,
                        ) => '($this->global->fn->'.$orig.')($this, '.$context->implode( $args ).')',
                        $node->args,
                    );
                }
                return $node;
            },
        );
    }

    /**
     * $__ARG__, $GLOBALS and $this are forbidden
     *
     * @param TemplateNode $node
     */
    public function forbiddenVariablesPass( TemplateNode $node ) : void
    {
        $forbidden = $this->engine->isStrictParsing() ? ['GLOBALS', 'this'] : ['GLOBALS'];
        ( new NodeTraverser() )->traverse(
            $node,
            function( Node $node ) use ( $forbidden ) {
                if ( $node instanceof VariableNode
                     && \is_string( $node->name )
                     && (
                         ( \str_starts_with( $node->name, '__' ) && \str_ends_with( $node->name, '__' ) )
                             || \in_array( $node->name, $forbidden, true )
                     )
                ) {
                    throw new CompileException( "Forbidden variable \${$node->name}.", $node->position );
                }
            },
        );
    }
}
