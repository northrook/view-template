<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Runtime;

use AllowDynamicProperties;
use LogicException;
use ReflectionException;
use Stringable;

use Core\View\Template\{ContentType, Support\Helpers, Exception\RuntimeException};

/**
 * Filter executor.
 *
 * @internal
 */
#[AllowDynamicProperties]
class FilterExecutor
{
    /** @var callable[] */
    private array $_dynamic = [];

    /** @var array<string, array{callable, ?bool}> */
    private array $_static = [];

    /**
     * Registers run-time filter.
     *
     * @param ?string  $name
     * @param callable $callback
     *
     * @return FilterExecutor
     */
    public function add( ?string $name, callable $callback ) : static
    {
        if ( $name === null ) {
            \array_unshift( $this->_dynamic, $callback );
        }
        else {
            $this->_static[$name] = [$callback, null];
            unset( $this->{$name} );
        }

        return $this;
    }

    /**
     * Returns all run-time filters.
     *
     * @return callable[]
     */
    public function getAll() : array
    {
        return \array_combine( \array_keys( $this->_static ), \array_column( $this->_static, 0 ) );
    }

    /**
     * Returns filter for classic calling.
     *
     * @param string $name
     *
     * @return callable
     * @throws ReflectionException
     */
    public function __get( string $name ) : callable
    {
        [$callback, $infoAware] = $this->prepareFilter( $name );
        return $this->{$name}   = $infoAware
                ? fn( ...$args ) => $this->callInfoAwareAsClassic( $callback, ...$args )
                : $callback;
    }

    /**
     * Calls filter with FilterInfo.
     *
     * @param string     $name
     * @param FilterInfo $info
     * @param array      $args
     *
     * @return mixed
     * @throws ReflectionException
     */
    public function filterContent( string $name, FilterInfo $info, mixed ...$args ) : mixed
    {
        if ( $info->contentType === ContentType::HTML && $args[0] instanceof Stringable ) {
            $args[0] = $args[0]->__toString();
        }

        [$callback, $infoAware] = $this->prepareFilter( $name );
        if ( $infoAware ) {
            \array_unshift( $args, $info );
            return $callback( ...$args );
        }

        // classic filter
        if ( $info->contentType !== ContentType::TEXT ) {
            throw new RuntimeException(
                "Filter |{$name} is called with incompatible content type ".\strtoupper(
                    $info->contentType ?? 'NULL',
                )
                    .( $info->contentType === ContentType::HTML ? ', try to prepend |stripHtml.' : '.' ),
            );
        }

        $res = $callback( ...$args );
        if ( $res instanceof Stringable ) {
            \trigger_error( "Filter |{$name} should be changed to content-aware filter." );
            $info->contentType = ContentType::HTML;
            $res               = $res->__toString();
        }

        return $res;
    }

    /**
     * @param string $name
     *
     * @return array{callable, bool}
     * @throws ReflectionException
     */
    private function prepareFilter( string $name ) : array
    {
        if ( isset( $this->_static[$name] ) ) {
            $this->_static[$name][1] ??= $this->isInfoAware( $this->_static[$name][0] );
            return $this->_static[$name];
        }

        foreach ( $this->_dynamic as $loader ) {
            if ( $callback = $loader( $name ) ) {
                return $this->_static[$name] = [$callback, $this->isInfoAware( $callback )];
            }
        }

        $hint = ( $t = Helpers::getSuggestion( \array_keys( $this->_static ), $name ) )
                ? ", did you mean '{$t}'?"
                : '.';
        throw new LogicException( "Filter '{$name}' is not defined{$hint}" );
    }

    /**
     * @param callable $filter
     *
     * @return bool
     * @throws ReflectionException
     */
    private function isInfoAware( callable $filter ) : bool
    {
        $params = Helpers::toReflection( $filter )->getParameters();
        return $params && (string) $params[0]->getType() === FilterInfo::class;
    }

    private function callInfoAwareAsClassic( callable $filter, mixed ...$args ) : mixed
    {
        \array_unshift( $args, $info = new FilterInfo() );
        if ( $args[1] instanceof Stringable ) {
            $args[1]           = $args[1]->__toString();
            $info->contentType = ContentType::HTML;
        }

        $res = $filter( ...$args );
        return $info->contentType === ContentType::HTML
                ? new Html( $res )
                : $res;
    }
}
