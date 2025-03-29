<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler;

use Core\View\Template\Exception\CompileException;
use InvalidArgumentException;
use Iterator;

/**
 * TokenStream loads tokens from $source iterator on-demand, and places them in a buffer to provide access
 * to any previous token by index.
 */
final class TokenStream
{
    /** @var Token[] */
    private array $tokens = [];

    private Iterator // readonly
        $source;

    private int $index = 0;

    public function __construct( Iterator $source )
    {
        $this->source = $source;
    }

    /**
     * Tells whether the token at current position is of given kind.
     *
     * @param int|string[] $kind
     */
    public function is( int|string ...$kind ) : bool
    {
        return $this->peek()->is( ...$kind );
    }

    /**
     * Gets the token at $offset from the current position.
     *
     * @param int $offset
     *
     * @return null|Token
     */
    public function peek( int $offset = 0 ) : ?Token
    {
        $pos = $this->index + $offset;
        while ( $pos >= 0 && ! isset( $this->tokens[$pos] ) && $this->source->valid() ) {
            if ( $this->tokens ) {
                $this->source->next();
            }

            if ( $this->source->valid() ) {
                $this->tokens[] = $this->source->current();
            }
        }

        return $this->tokens[$pos] ?? null;
    }

    /**
     * Consumes the current token (if is of given kind) or throws exception on end.
     *
     * @param int|string[] $kind
     *
     * @throws CompileException
     */
    public function consume( int|string ...$kind ) : Token
    {
        $token = $this->peek();
        if ( $kind && ! $token->is( ...$kind ) ) {
            $kind = \array_map( fn( $item ) => \is_string( $item ) ? "'{$item}'" : Token::Names[$item], $kind );
            $this->throwUnexpectedException( $kind );
        }
        elseif ( ! $token->isEnd() ) {
            $this->index++;
        }
        return $token;
    }

    /**
     * Consumes the current token of given kind or returns null.
     *
     * @param int|string[] $kind
     */
    public function tryConsume( int|string ...$kind ) : ?Token
    {
        $token = $this->peek();
        if ( ! $token->is( ...$kind ) ) {
            return null;
        }
        if ( ! $token->isEnd() ) {
            $this->index++;
        }
        return $token;
    }

    /**
     * Sets the input cursor to the position.
     *
     * @param int $index
     */
    public function seek( int $index ) : void
    {
        if ( $index >= \count( $this->tokens ) || $index < 0 ) {
            throw new InvalidArgumentException( 'The position is out of range.' );
        }
        $this->index = $index;
    }

    /**
     * Returns the cursor position.
     */
    public function getIndex() : int
    {
        return $this->index;
    }

    /**
     * @param array  $expected
     * @param string $addendum
     * @param string $excerpt
     *
     * @return void
     * @throws CompileException
     */
    public function throwUnexpectedException( array $expected = [], string $addendum = '', string $excerpt = '' ) : void
    {
        $token    = $this->peek()->text.$excerpt;
        $expected = \array_map( fn( $item ) => \is_int( $item ) ? Token::Names[$item] : $item, $expected );
        throw new CompileException(
            'Unexpected '
                .( $token === ''
                        ? 'end'
                        : "'".\trim( $token, "\n" )."'" )
                .( $expected && \count( $expected ) < 5
                        ? ', expecting '.\implode( ', ', $expected )
                        : '' )
                .$addendum,
            $this->peek()->position,
        );
    }
}
