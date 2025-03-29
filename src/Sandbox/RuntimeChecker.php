<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Sandbox;

use Core\View\Template\Interface\Policy;
use Core\View\Template\Exception\SecurityViolationException;
use Closure;
use Error;

/**
 * @internal
 */
final class RuntimeChecker
{
    public function __construct( public Policy $policy ) {}

    /**
     * @param mixed                   $callable
     * @param array<array-key, mixed> $args
     *
     * @return mixed
     * @throws SecurityViolationException
     */
    public function call( mixed $callable, array $args ) : mixed
    {
        self::checkCallable( $callable );
        self::args( ...$args );
        return $callable( ...$args );
    }

    /**
     * @param mixed $object
     * @param mixed $method
     * @param array $args
     * @param bool  $nullsafe
     *
     * @return mixed
     * @throws SecurityViolationException
     */
    public function callMethod( mixed $object, mixed $method, array $args, bool $nullsafe = false ) : mixed
    {
        if ( $object === null ) {
            if ( $nullsafe ) {
                throw new Error( "Call to a member function {$method}() on null" );
            }
            return null;
        }
        if ( ! \is_object( $object ) || ! \is_string( $method ) ) {
            throw new SecurityViolationException( 'Invalid callable.' );
        }
        if ( ! $this->policy->isMethodAllowed( $class = $object::class, $method ) ) {
            throw new SecurityViolationException( "Calling {$class}::{$method}() is not allowed." );
        }

        self::args( ...$args );
        return [$object, $method]( ...$args );
    }

    /**
     * @param mixed $callable
     *
     * @return Closure
     * @throws SecurityViolationException
     */
    public function closure( mixed $callable ) : Closure
    {
        self::checkCallable( $callable );
        return $callable( ... );
        // return Closure::fromCallable( $callable );
    }

    /**
     * @param array $args
     *
     * @throws SecurityViolationException
     */
    public function args( ...$args ) : array
    {
        foreach ( $args as $arg ) {
            if (
                \is_array( $arg )
                && \is_callable( $arg, true, $text )
                && ! $this->policy->isMethodAllowed(
                    \is_object( $arg[0] ) ? $arg[0]::class : $arg[0],
                    $arg[1],
                )
            ) {
                throw new SecurityViolationException( "Calling {$text}() is not allowed." );
            }
        }

        return $args;
    }

    /**
     * @param mixed $object
     * @param mixed $property
     *
     * @return mixed
     * @throws SecurityViolationException
     */
    public function prop( mixed $object, mixed $property ) : mixed
    {
        $class = \is_object( $object ) ? $object::class : $object;
        if ( \is_string( $class ) && ! $this->policy->isPropertyAllowed( $class, (string) $property ) ) {
            throw new SecurityViolationException(
                "Access to '{$property}' property on a {$class} object is not allowed.",
            );
        }

        return $object;
    }

    /**
     * @param mixed $callable
     *
     * @throws SecurityViolationException
     */
    private function checkCallable( mixed $callable ) : void
    {
        if ( ! \is_callable( $callable ) ) {
            throw new SecurityViolationException( 'Invalid callable.' );
        }
        if ( \is_string( $callable ) ) {
            $parts   = \explode( '::', $callable );
            $allowed = \count( $parts ) === 1
                    ? $this->policy->isFunctionAllowed( $parts[0] )
                    : $this->policy->isMethodAllowed( ...$parts );
        }
        elseif ( \is_array( $callable ) ) {
            $allowed = $this->policy->isMethodAllowed(
                \is_object( $callable[0] ) ? $callable[0]::class : $callable[0],
                $callable[1],
            );
        }
        elseif ( \is_object( $callable ) ) {
            $allowed = $callable instanceof Closure
                       || $this->policy->isMethodAllowed( $callable::class, '__invoke' );
        }
        else {
            $allowed = false;
        }

        if ( ! $allowed ) {
            \is_callable( $callable, false, $text );
            throw new SecurityViolationException( "Calling {$text}() is not allowed." );
        }
    }
}
