<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Sandbox;

use Core\View\Template\Compiler\Nodes\TemplateNode;
use Core\View\Template\{Engine, Extension, Interface\Policy, Exception\SecurityViolationException};
use Core\View\Template\Compiler\{Node, NodeTraverser, PrintContext};
use Core\View\Template\Compiler\Nodes\Php\{ArgumentNode, FilterNode, NameNode};
use Core\View\Template\Compiler\Nodes\Php\Expression\{AuxiliaryNode,
    FunctionCallableNode,
    FunctionCallNode,
    MethodCallableNode,
    MethodCallNode,
    NewNode,
    PropertyFetchNode,
    StaticMethodCallableNode,
    StaticMethodCallNode,
    StaticPropertyFetchNode,
    VariableNode
};
use Core\View\Template\Runtime\Template;
use Core\View\Template\Sandbox\Nodes\SandboxNode;

/**
 * Security protection for the sandbox.
 */
final class SandboxExtension extends Extension
{
    private ?Policy $policy;

    public function beforeCompile( Engine $engine ) : void
    {
        $this->policy = $engine->getPolicy( effective : true );
    }

    public function getTags() : array
    {
        return [
            'sandbox' => [SandboxNode::class, 'create'],
        ];
    }

    public function getPasses() : array
    {
        return $this->policy
                ? ['sandbox' => $this->order( [$this, 'processPass'], before : '*' )]
                : [];
    }

    public function beforeRender( Template $template ) : void
    {
        $engine = $template->getEngine();
        if ( $policy = $engine->getPolicy() ) {
            $engine->addProvider( 'sandbox', new RuntimeChecker( $policy ) );
        }
    }

    public function getCacheKey( Engine $engine ) : bool
    {
        return (bool) $engine->getPolicy( effective : true );
    }

    public function processPass( TemplateNode $node ) : void
    {
        ( new NodeTraverser() )->traverse( $node, leave : $this->sandboxVisitor( ... ) );
    }

    /**
     * @param Node $node
     *
     * @return Node
     * @throws SecurityViolationException
     */
    private function sandboxVisitor( Node $node ) : Node
    {
        if ( $node instanceof VariableNode ) {
            if ( $node->name === 'this' ) {
                throw new SecurityViolationException( "Forbidden variable \${$node->name}.", $node->position );
            }
            if ( ! \is_string( $node->name ) ) {
                throw new SecurityViolationException( 'Forbidden variable variables.', $node->position );
            }

            return $node;
        }
        if ( $node instanceof NewNode ) {
            throw new SecurityViolationException( "Forbidden keyword 'new'", $node->position );
        }
        if ( $node instanceof FunctionCallNode
             && $node->name instanceof NameNode
        ) {
            if ( ! $this->policy->isFunctionAllowed( (string) $node->name ) ) {
                throw new SecurityViolationException( "Function {$node->name}() is not allowed.", $node->position );
            }
            if ( $node->args ) {
                $arg = new AuxiliaryNode(
                    fn( PrintContext $context, ...$args ) => '$this->global->sandbox->args('.$context->implode(
                        $args,
                    ).')',
                    $node->args,
                );
                $node->args = [new ArgumentNode( $arg, unpack : true )];
            }

            return $node;
        }
        if ( $node instanceof FilterNode ) {
            $name = (string) $node->name;
            if ( ! $this->policy->isFilterAllowed( $name ) ) {
                throw new SecurityViolationException( "Filter |{$name} is not allowed.", $node->position );
            }
            if ( $node->args ) {
                $arg = new AuxiliaryNode(
                    fn( PrintContext $context, ...$args ) => '$this->global->sandbox->args('.$context->implode(
                        $args,
                    ).')',
                    $node->args,
                );
                $node->args = [new ArgumentNode( $arg, unpack : true )];
            }

            return $node;
        }
        if (
            $node instanceof PropertyFetchNode
            || $node instanceof StaticPropertyFetchNode
            || $node instanceof FunctionCallNode
            || $node instanceof FunctionCallableNode
            || $node instanceof MethodCallNode
            || $node instanceof MethodCallableNode
            || $node instanceof StaticMethodCallNode
            || $node instanceof StaticMethodCallableNode
        ) {
            $class = __NAMESPACE__.'\\Nodes'.\strrchr( $node::class, '\\' );
            dump( $class ); // :: TESTING
            return new $class( $node );
        }

        return $node;
    }
}
