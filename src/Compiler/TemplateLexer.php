<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler;

use Core\Exception\RegexpException;
use Core\View\Template\Exception\{CompileException};
use Generator;
use InvalidArgumentException;

final class TemplateLexer
{
    public const string
        StatePlain                = 'Plain',
        StateLatteTag             = 'LatteTag',
        StateLatteComment         = 'LatteComment',
        StateHtmlText             = 'HtmlText',
        StateHtmlTag              = 'HtmlTag',
        StateHtmlQuotedValue      = 'HtmlQuotedValue',
        StateHtmlQuotedNAttrValue = 'HtmlQuotedNAttrValue',
        StateHtmlRawText          = 'HtmlRawText',
        StateHtmlComment          = 'HtmlComment',
        StateHtmlBogus            = 'HtmlBogus';

    /** HTML tag name for Latte needs (actually is [a-zA-Z][^\s/>]*) */
    // public const string ReTagName = '[a-zA-Z][a-zA-Z0-9:_.-]*';

    /** special HTML attribute prefix */
    public const string NPrefix = 'n:';

    /** HTML attribute name/value (\p{C} means \x00-\x1F except space) */
    private const string ReAttrName = '[^\p{C} "\'<>=`/]';

    private string $openDelimiter = '';

    private string $closeDelimiter = '';

    private array $delimiters = [];

    private TagLexer $tagLexer;

    /** @var array<array{name: string, args: array}> */
    private array $states = [];

    private string $input;

    private Position $position;

    public function __construct()
    {
        $this->position = new Position();
        $this->setState( self::StatePlain );
        $this->setSyntax( null );
        $this->tagLexer = new TagLexer();
    }

    /**
     * @param string $template
     *
     * @return Generator<Token>
     * @throws CompileException
     */
    public function tokenize( string $template ) : Generator
    {
        $this->input = $this->normalize( $template );

        do {
            $offset = $this->position->offset;
            $state  = $this->states[0];
            $tokens = $this->{"state{$state['name']}"}( ...$state['args'] );
            yield from $tokens;
        }
        while ( $offset !== $this->position->offset );

        if ( $offset < \strlen( $this->input ) ) {
            throw new CompileException( "Unexpected '".\substr( $this->input, $offset, 10 )."'", $this->position );
        }

        yield new Token( Token::End, '', $this->position );
    }

    protected function statePlain() : array
    {
        return $this->match(
            '~'
                .'(?<Text>.+?)?'                                 // Optional non-greedy text
                .'(?<Indentation>(?<=\n|^)[ \t]+)?'              // Optional indentation at start of line
                .'('
                .'(?<Latte_TagOpen>'.$this->openDelimiter.'(?!\*))'   // Latte tag open
                .'|(?<Latte_CommentOpen>'.$this->openDelimiter.'\*)'  // Latte comment open
                .'|$'
                .')'
                .'~xsiAuD',
        );

        // return $this->match('~
        // 	(?<Text>.+?)??
        // 	(?<Indentation>(?<=\n|^)[ \t]+)?
        // 	(
        // 		(?<Latte_TagOpen>' . $this->openDelimiter . '(?!\*))|      # {tag
        // 		(?<Latte_CommentOpen>' . $this->openDelimiter . '\*)|      # {* comment
        // 		$
        // 	)
        // ~xsiAuD');
    }

    /**
     * @throws CompileException
     */
    protected function stateLatteTag() : array
    {
        $pattern = '~'
                   .'(?<Slash>/)?' // Optional slash
                   .'(?<Latte_Name>'
                   .'=|'                            // Equal sign (for shorthand)
                   .'_(?!_)|'                       // Underscore not followed by another underscore
                   .'[a-z]\w*+'                     // Name starting with a-z
                   .'(?:[.:-]\w+)*+'                // Followed by . or : or - plus more word chars
                   .'(?!::|\(|\\\\)?'               // Not followed by ::, ( or backslash
                   .')'
                   .'~xsiAu';

        $tokens[] = $this->match( $pattern );
        // $tokens[] = $this->match('~
        // 	(?<Slash>/)?
        // 	(?<Latte_Name> = | _(?!_) | [a-z]\w*+(?:[.:-]\w+)*+(?!::|\(|\\\\))?   # name, /name, but not function( or class:: or namespace\
        // ~xsiAu');

        $tokens[] = $this->tagLexer->tokenizePartially( $this->input, $this->position );

        $pattern = '~'
                   .'(?<Slash>/)?'                                 // Optional slash
                   .'(?<Latte_TagClose>'.$this->closeDelimiter.')' // Required Latte tag close
                   .'(?<Newline>[ \t]*\R)?'                        // Optional trailing whitespace + newline
                   .'~xsiAu';

        $tokens[] = $this->match( $pattern );
        // $tokens[] = $this->match('~
        // 	(?<Slash>/)?
        // 	(?<Latte_TagClose>' . $this->closeDelimiter . ')
        // 	(?<Newline>[ \t]*\R)?
        // ~xsiAu');

        return \array_merge( ...$tokens );
    }

    protected function stateLatteComment() : array
    {
        $pattern = '~'
                   .'(?<Text>.+?)??'  // Non-greedy match for any text
                   .'('
                   .'(?<Latte_CommentClose>\*'.$this->closeDelimiter.')'  // Match comment close tag
                   .'(?<Newline>[ \t]*\R{1,2})?'                              // Optional trailing newline
                   .'|'
                   .'$'                                                       // Or end of input
                   .')'
                   .'~xsiAu';

        return $this->match( $pattern );
        // return $this->match('~
        // 	(?<Text>.+?)??
        // 	(
        // 		(?<Latte_CommentClose>\*' . $this->closeDelimiter . ')(?<Newline>[ \t]*\R{1,2})?|
        // 		$
        // 	)
        // ~xsiAu');
    }

    protected function stateHtmlText() : array
    {
        $pattern = '~(?J)'
                   .'(?<Text>.+?)??'
                   .'('
                   .'(?<Indentation>(?<=\n|^)[ \t]+)?(?<Html_TagOpen><)(?<Slash>/)?(?=[a-z]|'.$this->openDelimiter.')|'
                   .'(?<Html_CommentOpen><!--(?!>|->))|'
                   .'(?<Html_BogusOpen><[?!])|'
                   .'(?<Indentation>(?<=\n|^)[ \t]+)?(?<Latte_TagOpen>'.$this->openDelimiter.'(?!\*))|'
                   .'(?<Indentation>(?<=\n|^)[ \t]+)?(?<Latte_CommentOpen>'.$this->openDelimiter.'\*)|'
                   .'$'
                   .')'
                   .'~xsiAuD';

        return $this->match( $pattern );
        // return $this->match('~(?J)
        // 	(?<Text>.+?)??
        // 	(
        // 		(?<Indentation>(?<=\n|^)[ \t]+)?(?<Html_TagOpen><)(?<Slash>/)?(?=[a-z]|' . $this->openDelimiter . ')|  # < </ tag
        // 		(?<Html_CommentOpen><!--(?!>|->))|                                                      # <!-- comment
        // 		(?<Html_BogusOpen><[?!])|                                                               # <!doctype <?xml or error
        // 		(?<Indentation>(?<=\n|^)[ \t]+)?(?<Latte_TagOpen>' . $this->openDelimiter . '(?!\*))|   # {tag
        // 		(?<Indentation>(?<=\n|^)[ \t]+)?(?<Latte_CommentOpen>' . $this->openDelimiter . '\*)|   # {* comment
        // 		$
        // 	)
        // ~xsiAuD');
    }

    protected function stateHtmlTag() : array
    {
        $pattern = '~(?J)'
                   .'(?<Equals>=)'
                   .'(?<Whitespace>\s+)?'
                   .'(?<Html_Name>(?:(?!'.$this->openDelimiter.')'.self::ReAttrName.'|/)+)?'
                   .'|'
                   .'(?<Whitespace>\s+)|'
                   .'(?<Quote>["\'])|'
                   .'(?<Slash>/)?(?<Html_TagClose>>)(?<Newline>[ \t]*\R)?|'
                   .'(?<Html_Name>(?:(?!'.$this->openDelimiter.')'.self::ReAttrName.')+)|'
                   .'(?<Latte_TagOpen>'.$this->openDelimiter.'(?!\*))|'
                   .'(?<Latte_CommentOpen>'.$this->openDelimiter.'\*)'
                   .'~xsiAu';

        return $this->match( $pattern );
        // return $this->match('~(?J)
        // 	(?<Equals>=)
        // 		(?<Whitespace>\s+)?
        // 		(?<Html_Name>(?:(?!' . $this->openDelimiter . ')' . self::ReAttrName . '|/)+)?  # HTML attribute value can contain /
        // 	|
        // 	(?<Whitespace>\s+)|                                        # whitespace
        // 	(?<Quote>["\'])|
        // 	(?<Slash>/)?(?<Html_TagClose>>)(?<Newline>[ \t]*\R)?|      # > />
        // 	(?<Html_Name>(?:(?!' . $this->openDelimiter . ')' . self::ReAttrName . ')+)|  # HTML attribute name/value
        // 	(?<Latte_TagOpen>' . $this->openDelimiter . '(?!\*))|      # {tag
        // 	(?<Latte_CommentOpen>' . $this->openDelimiter . '\*)       # {* comment
        // ~xsiAu');
    }

    protected function stateHtmlQuotedValue( string $quote ) : array
    {
        $pattern = '~'
                   .'(?<Text>.+?)??'
                   .'('
                   .'(?<Quote>'.$quote.')|'
                   .'(?<Latte_TagOpen>'.$this->openDelimiter.'(?!\*))|'
                   .'(?<Latte_CommentOpen>'.$this->openDelimiter.'\*)|'
                   .'$'
                   .')'
                   .'~xsiAu';

        return $this->match( $pattern );
        // return $this->match('~
        // 	(?<Text>.+?)??
        // 	(
        // 		(?<Quote>' . $quote . ')|
        // 		(?<Latte_TagOpen>' . $this->openDelimiter . '(?!\*))|      # {tag
        // 		(?<Latte_CommentOpen>' . $this->openDelimiter . '\*)|      # {* comment
        // 		$
        // 	)
        // ~xsiAu');
    }

    protected function stateHtmlQuotedNAttrValue( string $quote ) : array
    {
        return $this->match( '~(?<Text>.+?)??((?<Quote>'.$quote.')|$)~xsiAu' );
        // return $this->match('~
        // 	(?<Text>.+?)??
        // 	(
        // 		(?<Quote>' . $quote . ')|
        // 		$
        // 	)
        // ~xsiAu');
    }

    protected function stateHtmlRawText( string $tagName ) : array
    {
        $pattern = '~'
                   .'(?<Text>.+?)??'
                   .'(?<Indentation>(?<=\n|^)[ \t]+)?'
                   .'('
                   .'(?<Html_TagOpen><)(?<Slash>/)(?<Html_Name>'.\preg_quote( $tagName, '~' ).')|'
                   .'(?<Latte_TagOpen>'.$this->openDelimiter.'(?!\*))|'
                   .'(?<Latte_CommentOpen>'.$this->openDelimiter.'\*)|'
                   .'$'
                   .')'
                   .'~xsiAu';

        return $this->match( $pattern );
        // return $this->match('~
        // 	(?<Text>.+?)??
        // 	(?<Indentation>(?<=\n|^)[ \t]+)?
        // 	(
        // 		(?<Html_TagOpen><)(?<Slash>/)(?<Html_Name>' . preg_quote($tagName, '~') . ')|  # </tag
        // 		(?<Latte_TagOpen>' . $this->openDelimiter . '(?!\*))|                          # {tag
        // 		(?<Latte_CommentOpen>' . $this->openDelimiter . '\*)|                          # {* comment
        // 		$
        // 	)
        // ~xsiAu');
    }

    protected function stateHtmlComment() : array
    {
        $pattern = '~(?J)'
                   .'(?<Text>.+?)??'
                   .'('
                   .'(?<Html_CommentClose>-->)|'
                   .'(?<Indentation>(?<=\n|^)[ \t]+)?(?<Latte_TagOpen>'.$this->openDelimiter.'(?!\*))|'
                   .'(?<Indentation>(?<=\n|^)[ \t]+)?(?<Latte_CommentOpen>'.$this->openDelimiter.'\*)|'
                   .'$'
                   .')'
                   .'~xsiAu';

        return $this->match( $pattern );
        // return $this->match('~(?J)
        // 	(?<Text>.+?)??
        // 	(
        // 		(?<Html_CommentClose>-->)|                                                              # -->
        // 		(?<Indentation>(?<=\n|^)[ \t]+)?(?<Latte_TagOpen>' . $this->openDelimiter . '(?!\*))|   # {tag
        // 		(?<Indentation>(?<=\n|^)[ \t]+)?(?<Latte_CommentOpen>' . $this->openDelimiter . '\*)|   # {* comment
        // 		$
        // 	)
        // ~xsiAu');
    }

    protected function stateHtmlBogus() : array
    {
        $pattern = '~'
                   .'(?<Text>.+?)??'
                   .'('
                   .'(?<Html_TagClose>>)|'
                   .'(?<Latte_TagOpen>'.$this->openDelimiter.'(?!\*))|'
                   .'(?<Latte_CommentOpen>'.$this->openDelimiter.'\*)|'
                   .'$'
                   .')'
                   .'~xsiAu';

        return $this->match( $pattern );
        // return $this->match('~
        // 	(?<Text>.+?)??
        // 	(
        // 		(?<Html_TagClose>>)|                                       # >
        // 		(?<Latte_TagOpen>' . $this->openDelimiter . '(?!\*))|      # {tag
        // 		(?<Latte_CommentOpen>' . $this->openDelimiter . '\*)|      # {* comment
        // 		$
        // 	)
        // ~xsiAu');
    }

    /**
     * Matches next token.
     *
     * @param string $pattern
     *
     * @return Token[]
     */
    private function match( string $pattern ) : array
    {
        \preg_match( $pattern, $this->input, $matches, PREG_UNMATCHED_AS_NULL, $this->position->offset );

        RegexpException::check();

        $tokens = [];

        foreach ( $matches as $key => $value ) {
            if ( $value !== null && ! \is_int( $key ) ) {
                $tokens[] = new Token( \constant( Token::class.'::'.$key ), $value, $this->position );

                $this->position = $this->position->advance( $value );
            }
        }

        return $tokens;
    }

    public function setState( string $state, ...$args ) : void
    {
        $this->states[0] = ['name' => $state, 'args' => $args];
    }

    public function pushState( string $state, ...$args ) : void
    {
        \array_unshift( $this->states, null );
        $this->setState( $state, ...$args );
    }

    public function popState() : void
    {
        \array_shift( $this->states );
    }

    public function getState() : string
    {
        return $this->states[0]['name'];
    }

    /**
     * Changes tag delimiters.
     *
     * @param ?string $type
     * @param ?string $endTag
     *
     * @return TemplateLexer
     */
    public function setSyntax( ?string $type, ?string $endTag = null ) : static
    {
        $left = '\{(?![\s\'"{}])';
        $end  = $endTag ? '\{/'.\preg_quote( $endTag, '~' ).'\}' : null;

        $this->delimiters[] = [$this->openDelimiter, $this->closeDelimiter];

        [$this->openDelimiter, $this->closeDelimiter] = match ( $type ) {
            null     => [$left, '\}'], // {...}
            'off'    => [$endTag ? '(?='.$end.')\{' : '(?!x)x', '\}'],
            'double' => $endTag // {{...}}
                    ? ['(?:\{'.$left.'|(?='.$end.')\{)', '\}(?:\}|(?<='.$end.'))']
                    : ['\{'.$left, '\}\}'],
            default => throw new InvalidArgumentException( "Unknown syntax '{$type}'" ),
        };
        return $this;
    }

    public function popSyntax() : void
    {
        [$this->openDelimiter, $this->closeDelimiter] = \array_pop( $this->delimiters );
    }

    /**
     * @param string $string
     *
     * @return string
     *
     * @throws CompileException
     */
    protected function normalize( string $string ) : string
    {
        if ( \str_starts_with( $string, "\u{FEFF}" ) ) { // BOM
            $string = \substr( $string, 3 );
        }

        $string = \str_replace( "\r\n", "\n", $string );

        if ( ! \preg_match( '##u', $string ) ) {
            \preg_match(
                '#(?:[\x00-\x7F]|[\xC0-\xDF][\x80-\xBF]|[\xE0-\xEF][\x80-\xBF]{2}|[\xF0-\xF7][\x80-\xBF]{3})*+#A',
                $string,
                $m,
            );
            throw new CompileException(
                'Template is not valid UTF-8 stream.',
                $this->position->advance( $m[0] ),
            );
        }
        if ( \preg_match( '#(.*?)([\x00-\x08\x0B\x0C\x0E-\x1F\x7F])#s', $string, $m ) ) {
            throw new CompileException(
                'Template contains control character \x'.\dechex( \ord( $m[2] ) ),
                $this->position->advance( $m[1] ),
            );
        }
        return $string;
    }
}
