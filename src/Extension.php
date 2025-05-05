<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template;

use Core\View\Template\Compiler\Node;
use Core\View\Template\Compiler\Nodes\TemplateNode;
use Core\View\Template\Runtime\Template;
use Psr\Log\{LoggerAwareInterface, LoggerInterface};
use stdClass;

/**
 * View Template {@see Engine} extension.
 */
abstract class Extension implements LoggerAwareInterface
{
    protected ?LoggerInterface $logger = null;

    /**
     * @param ?LoggerInterface $logger
     */
    final public function setLogger( ?LoggerInterface $logger ) : void
    {
        $this->logger = $logger;
    }

    /**
     * Initializes before the template is compiled.
     *
     * @param Engine $engine
     */
    public function beforeCompile( Engine $engine ) : void {}

    /**
     * Returns a list of parsers for Latte tags.
     *
     * @return array<array-key, callable(TemplateNode):Node|callable(TemplateNode):void>
     */
    public function getPasses() : array
    {
        return [];
    }

    /**
     * Returns a list of parsers for Latte tags.
     *
     * @return array<string, callable>
     */
    public function getTags() : array
    {
        return [];
    }

    /**
     * Returns a list of |filters.
     *
     * @return array<string, callable>
     */
    public function getFilters() : array
    {
        return [];
    }

    /**
     * Returns a list of functions used in templates.
     *
     * @return array<string, callable>
     */
    public function getFunctions() : array
    {
        return [];
    }

    /**
     * Returns a list of providers.
     *
     * @return array<string, mixed>
     */
    public function getProviders() : array
    {
        return [];
    }

    /**
     * Returns a value to distinguish multiple versions of the template.
     *
     * @param Engine $engine
     *
     * @return null|bool|object|string
     */
    public function getCacheKey( Engine $engine ) : null|bool|string|object
    {
        return null;
    }

    /**
     * Initializes before the template is rendered.
     *
     * @param Template $template
     */
    public function beforeRender( Template $template ) : void {}

    final public static function order(
        callable     $subject,
        array|string $before = [],
        array|string $after = [],
    ) : stdClass {
        return (object) \get_defined_vars();
    }

    final public static function validName( string $name ) : bool
    {
        return $name !== ''
               && \ctype_alpha( $name[0] )
               && \ctype_alnum( \str_replace( '_', '', $name ) );
    }
}
