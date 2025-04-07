<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler;

use Core\View\Template\Exception\CompileException;
use ArrayIterator;
use SplObjectStorage;
use Core\View\Template\Compiler\Nodes\Php\{Expression,
    ExpressionNode,
    IdentifierNode,
    InterpolatedStringPartNode,
    ListItemNode,
    ListNode,
    ModifierNode,
    NameNode,
    Scalar,
    SuperiorTypeNode
};
use Core\View\Template\Compiler\Nodes\Php\Expression\{ArrayNode, TemporaryNode};

/**
 * Parser for PHP-like expression language used in tags.
 * Based on works by Nikita Popov, Moriyoshi Koizumi and Masato Bito.
 */
final class TagParser extends TagParserData
{
    private const string
        SchemaExpression = 'e',
        SchemaArguments  = 'a',
        SchemaFilters    = 'm',
        SchemaForeach    = 'f';

    private const int  SymbolNone = -1;

    public readonly TokenStream $stream;

    public string $text;

    /** @var SplObjectStorage<ArrayNode> */
    protected SplObjectStorage $shortArrays;

    private int // readonly
        $offsetDelta;

    public function __construct( array $tokens )
    {
        $this->offsetDelta = $tokens[0]->position->offset ?? 0;
        $tokens            = $this->filterTokens( $tokens );
        $this->stream      = new TokenStream( new ArrayIterator( $tokens ) );
    }

    /**
     * Parses PHP-like expression.
     *
     * @throws CompileException
     */
    public function parseExpression() : ExpressionNode
    {
        return $this->parse( self::SchemaExpression, recovery : true );
    }

    /**
     * Parses optional list of arguments. Named and variadic arguments are also supported.
     *
     * @throws CompileException
     */
    public function parseArguments() : ArrayNode
    {
        return $this->parse( self::SchemaArguments, recovery : true );
    }

    /**
     * Parses optional list of filters.
     *
     * @throws CompileException
     */
    public function parseModifier() : ModifierNode
    {
        return $this->isEnd()
                ? new ModifierNode( [] )
                : $this->parse( self::SchemaFilters );
    }

    /**
     * Parses unquoted string or PHP-like expression.
     *
     * @param bool $colon
     *
     * @return ExpressionNode
     * @throws CompileException
     */
    public function parseUnquotedStringOrExpression( bool $colon = true ) : ExpressionNode
    {
        $position = $this->stream->peek()->position;
        $lexer    = new TagLexer();
        $tokens   = $lexer->tokenizeUnquotedString( $this->text, $position, $colon, $this->offsetDelta );

        if ( ! $tokens ) {
            return $this->parseExpression();
        }

        $parser = new self( $tokens );
        $end    = $position->offset + \strlen( $parser->text ) - 2; // 2 quotes
        do {
            $this->stream->consume();
        }
        while ( $this->stream->peek()->position->offset < $end );

        return $parser->parseExpression();
    }

    /**
     * Parses optional type declaration.
     */
    public function parseType() : ?SuperiorTypeNode
    {
        $kind = [
            Token::Php_Identifier,
            Token::Php_Constant,
            Token::Php_Ellipsis,
            Token::Php_Array,
            Token::Php_Integer,
            Token::Php_NameFullyQualified,
            Token::Php_NameQualified,
            Token::Php_Null,
            Token::Php_False,
            '(',
            ')',
            '<',
            '>',
            '[',
            ']',
            '|',
            '&',
            '{',
            '}',
            ':',
            ',',
            '=',
            '?',
        ];
        $res = null;
        while ( $token = $this->stream->tryConsume( ...$kind ) ) {
            $res .= $token->text;
        }

        return $res ? new SuperiorTypeNode( $res ) : null;
    }

    /**
     * Parses variables used in foreach.
     *
     * @internal
     * @throws CompileException
     */
    public function parseForeach() : array
    {
        return $this->parse( self::SchemaForeach );
    }

    /**
     * Consumes optional token followed by whitespace. Suitable before parseUnquotedStringOrExpression().
     *
     * @param string[] $kind
     *
     * @throws CompileException
     * @throws CompileException
     */
    public function tryConsumeTokenBeforeUnquotedString( string ...$kind ) : ?Token
    {
        $token = $this->stream->peek();
        return $token->is( ...$kind ) // is followed by whitespace
               && $this->stream->peek( 1 )->position->offset > $token->position->offset + \strlen( $token->text )
                ? $this->stream->consume()
                : null;
    }

    public function isEnd() : bool
    {
        return $this->stream->peek()->isEnd();
    }

    /**
     * @param string $schema
     * @param bool   $recovery
     *
     * @return mixed
     * @throws CompileException
     */
    private function parse( string $schema, bool $recovery = false ) : mixed
    {
        $symbol                = self::SymbolNone; // We start off with no lookahead-token
        $this->startTokenStack = [];               // Keep stack of start token
        $token                 = null;
        $state                 = 0; // Start off in the initial state and keep a stack of previous states
        $stateStack            = [$state];
        $this->semStack        = []; // Semantic value stack (contains values of tokens and semantic action results)
        $stackPos              = 0;  // Current position in the stack(s)
        $this->shortArrays     = new SplObjectStorage();

        while ( true ) {
            if ( self::ActionBase[$state] === 0 ) {
                $rule = self::ActionDefault[$state];
            }
            else {
                if ( $symbol === self::SymbolNone ) {
                    $recovery = $recovery
                            ? [
                                $this->stream->getIndex(),
                                $state,
                                $stateStack,
                                $stackPos,
                                $this->semValue,
                                $this->semStack,
                                $this->startTokenStack,
                            ]
                            : null;

                    $token = $token
                            ? $this->stream->consume()
                            : new Token( \ord( $schema ), $schema );

                    recovery:
                    $symbol = self::TokenToSymbol[$token->type];
                }

                $idx = self::ActionBase[$state] + $symbol;
                if ( ( ( $idx >= 0 && $idx < \count( self::Action ) && self::ActionCheck[$idx] === $symbol )
                       || ( $state < self::Yy2Tblstate
                            && ( $idx = self::ActionBase[$state + self::NumNonLeafStates] + $symbol ) >= 0
                            && $idx < \count( self::Action ) && self::ActionCheck[$idx] === $symbol ) )
                     && ( $action = self::Action[$idx] ) !== self::DefaultAction
                ) {
                    /*
                    >= numNonLeafStates: shift and reduce
                    > 0: shift
                    = 0: accept
                    < 0: reduce
                    = -YYUNEXPECTED: error
                     */
                    if ( $action > 0 ) { // shift
                        $stackPos++;
                        $stateStack[$stackPos]            = $state = $action;
                        $this->semStack[$stackPos]        = $token->text;
                        $this->startTokenStack[$stackPos] = $token;
                        $symbol                           = self::SymbolNone;
                        if ( $action < self::NumNonLeafStates ) {
                            continue;
                        }

                        $rule = $action - self::NumNonLeafStates; // shift-and-reduce
                    }
                    else {
                        $rule = -$action;
                    }
                }
                else {
                    $rule = self::ActionDefault[$state];
                }
            }

            while ( true ) {
                if ( $rule === 0 ) { // accept
                    $this->finalizeShortArrays();
                    return $this->semValue;
                }
                if ( $rule !== self::UnexpectedTokenRule ) { // reduce
                    $this->reduce( $rule, $stackPos );

                    // Goto - shift non-terminal
                    $ruleLength = self::RuleToLength[$rule];
                    $stackPos -= $ruleLength;
                    $nonTerminal = self::RuleToNonTerminal[$rule];
                    $idx         = self::GotoBase[$nonTerminal] + $stateStack[$stackPos];
                    if ( $idx >= 0 && $idx < \count( self::Goto ) && self::GotoCheck[$idx] === $nonTerminal ) {
                        $state = self::Goto[$idx];
                    }
                    else {
                        $state = self::GotoDefault[$nonTerminal];
                    }

                    $stackPos++;
                    $stateStack[$stackPos]     = $state;
                    $this->semStack[$stackPos] = $this->semValue;
                    if ( $ruleLength === 0 ) {
                        $this->startTokenStack[$stackPos] = $token;
                    }
                }
                elseif ( $recovery && $this->isExpectedEof( $state ) ) { // recoverable error
                    [, $state, $stateStack, $stackPos, $this->semValue, $this->semStack, $this->startTokenStack]
                            = $recovery;
                    $this->stream->seek( $recovery[0] );
                    $token = new Token( Token::End, '' );
                    goto recovery;
                }
                else { // error
                    throw new CompileException(
                        'Unexpected '.( $token->text ? "'{$token->text}'" : 'end' ),
                        $token->position,
                    );
                }

                if ( $state < self::NumNonLeafStates ) {
                    break;
                }

                $rule = $state - self::NumNonLeafStates; // shift-and-reduce
            }
        }
    }

    /**
     * Can EOF be the next token?
     *
     * @param int $state
     *
     * @return bool
     */
    private function isExpectedEof( int $state ) : bool
    {
        foreach ( self::SymbolToName as $symbol => $name ) {
            $idx = self::ActionBase[$state] + $symbol;
            if ( ( $idx >= 0 && $idx < \count( self::Action ) && self::ActionCheck[$idx] === $symbol
                   || $state         < self::Yy2Tblstate
                      && ( $idx = self::ActionBase[$state + self::NumNonLeafStates] + $symbol ) >= 0
                      && $idx < \count( self::Action ) && self::ActionCheck[$idx] === $symbol )
                 && self::Action[$idx] !== self::UnexpectedTokenRule
                 && self::Action[$idx] !== self::DefaultAction
                 && $symbol === 0
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Token $token
     *
     * @throws CompileException
     */
    public function throwReservedKeywordException( Token $token ) : void
    {
        throw new CompileException( "Keyword '{$token->text}' cannot be used in Latte.", $token->position );
    }

    /**
     * @param Expression\FunctionCallableNode|Expression\FunctionCallNode $func
     *
     * @return ExpressionNode
     * @throws CompileException
     */
    protected function checkFunctionName(
        Expression\FunctionCallNode|Expression\FunctionCallableNode $func,
    ) : ExpressionNode {
        if ( $func->name instanceof NameNode && $func->name->isKeyword() ) {
            $this->throwReservedKeywordException( new Token( 0, (string) $func->name, $func->name->position ) );
        }
        return $func;
    }

    protected static function handleBuiltinTypes( NameNode $name ) : NameNode|IdentifierNode
    {
        $builtinTypes = [
            'bool'     => true,
            'int'      => true,
            'float'    => true,
            'string'   => true,
            'iterable' => true,
            'void'     => true,
            'object'   => true,
            'null'     => true,
            'false'    => true,
            'mixed'    => true,
            'never'    => true,
        ];

        $lowerName = \strtolower( $name->toCodeString() );
        return isset( $builtinTypes[$lowerName] )
                ? new IdentifierNode( $lowerName, $name->position )
                : $name;
    }

    protected static function parseOffset( string $str, Position $position ) : Scalar\StringNode|Scalar\IntegerNode
    {
        if ( ! \preg_match( '/^(?:0|-?[1-9][0-9]*)$/', $str ) ) {
            return new Scalar\StringNode( $str, $position );
        }

        $num = +$str;
        if ( ! \is_int( $num ) ) {
            return new Scalar\StringNode( $str, $position );
        }

        return new Scalar\IntegerNode( $num, Scalar\IntegerNode::KindDecimal, $position );
    }

    /**
     * @param string           $startToken
     * @param ExpressionNode[] $parts
     * @param string           $endToken
     * @param Position         $startPos
     * @param Position         $endPos
     *
     * @return Scalar\InterpolatedStringNode|Scalar\StringNode
     * @throws CompileException
     */
    protected function parseDocString(
        string   $startToken,
        array    $parts,
        string   $endToken,
        Position $startPos,
        Position $endPos,
    ) : Scalar\StringNode|Scalar\InterpolatedStringNode {
        $hereDoc = ! \str_contains( $startToken, "'" );
        \preg_match( '/\A[ \t]*/', $endToken, $matches );
        $indentation = $matches[0];
        if ( \str_contains( $indentation, ' ' ) && \str_contains( $indentation, "\t" ) ) {
            throw new CompileException( 'Invalid indentation - tabs and spaces cannot be mixed', $endPos );
        }
        if ( ! $parts ) {
            return new Scalar\StringNode( '', $startPos );
        }
        if ( ! $parts[0] instanceof InterpolatedStringPartNode ) {
            // If there is no leading encapsed string part, pretend there is an empty one
            $this->stripIndentation( '', $indentation, true, false, $parts[0]->position );
        }

        $newParts = [];

        foreach ( $parts as $i => $part ) {
            if ( $part instanceof InterpolatedStringPartNode ) {
                $isLast      = $i === \count( $parts ) - 1;
                $part->value = $this->stripIndentation(
                    $part->value,
                    $indentation,
                    $i === 0,
                    $isLast,
                    $part->position,
                );
                if ( $isLast ) {
                    $part->value = \preg_replace( '~(\r\n|\n|\r)\z~', '', $part->value );
                }
                if ( $hereDoc ) {
                    $part->value = PhpHelpers::decodeEscapeSequences( $part->value, null );
                }
                if ( $i === 0 && $isLast ) {
                    return new Scalar\StringNode( $part->value, $startPos );
                }
                if ( $part->value === '' ) {
                    continue;
                }
            }
            $newParts[] = $part;
        }

        return new Scalar\InterpolatedStringNode( $newParts, $startPos );
    }

    /**
     * @param string   $str
     * @param string   $indentation
     * @param bool     $atStart
     * @param bool     $atEnd
     * @param Position $position
     *
     * @return string
     * @throws CompileException
     */
    private function stripIndentation(
        string   $str,
        string   $indentation,
        bool     $atStart,
        bool     $atEnd,
        Position $position,
    ) : string {
        if ( $indentation === '' ) {
            return $str;
        }
        $start = $atStart ? '(?:(?<=\n)|\A)' : '(?<=\n)';
        $end   = $atEnd ? '(?:(?=[\r\n])|\z)' : '(?=[\r\n])';
        $regex = '/'.$start.'([ \t]*)('.$end.')?/D';
        return \preg_replace_callback(
            $regex,
            function( $matches ) use ( $indentation, $position ) {
                $indentLen = \strlen( $indentation );
                $prefix    = \substr( $matches[1], 0, $indentLen );
                if ( \str_contains( $prefix, $indentation[0] === ' ' ? "\t" : ' ' ) ) {
                    throw new CompileException(
                        'Invalid indentation - tabs and spaces cannot be mixed',
                        $position,
                    );
                }
                if ( \strlen( $prefix ) < $indentLen && ! isset( $matches[2] ) ) {
                    throw new CompileException(
                        'Invalid body indentation level '
                            .'(expecting an indentation level of at least '.$indentLen.')',
                        $position,
                    );
                }
                return \substr( $matches[0], \strlen( $prefix ) );
            },
            $str,
        );
    }

    /**
     * @param ArrayNode $array
     *
     * @return ListNode
     * @throws CompileException
     */
    public function convertArrayToList(
        ArrayNode $array,
    ) : ListNode {
        $this->shortArrays->detach( $array );
        $items = [];

        foreach ( $array->items as $item ) {
            $value = $item->value;
            if ( $item->unpack ) {
                throw new CompileException( 'Spread operator is not supported in assignments.', $value->position );
            }
            $value = match ( true ) {
                $value instanceof TemporaryNode                                       => $value->value,
                $value instanceof ArrayNode && $this->shortArrays->contains( $value ) => $this->convertArrayToList(
                    $value,
                ),
                default => $value,
            };
            $items[] = $value
                    ? new ListItemNode( $value, $item->key, $item->byRef, $item->position )
                    : null;
        }
        return new ListNode( $items, $array->position );
    }

    /**
     * @throws CompileException
     */
    private function finalizeShortArrays() : void
    {
        foreach ( $this->shortArrays as $node ) {
            foreach ( $node->items as $item ) {
                if ( $item->value instanceof TemporaryNode ) {
                    throw new CompileException(
                        'Cannot use empty array elements or list() in arrays.',
                        $item->position,
                    );
                }
            }
        }
    }

    /**
     * @param Token[] $tokens
     */
    private function filterTokens( array $tokens ) : array
    {
        $this->text = '';
        $res        = [];

        foreach ( $tokens as $token ) {
            $this->text .= $token->text;
            if ( ! $token->is( Token::Php_Whitespace, Token::Php_Comment ) ) {
                $res[] = $token;
            }
        }

        return $res;
    }
}
