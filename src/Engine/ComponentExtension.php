<?php

namespace Core\View\Template\Engine;

use Core\View\Template\Compiler\{Tag, TemplateParser};
use Core\View\Template\Exception\CompileException;
use Core\View\Template\Extension;
use Generator;

final class ComponentExtension extends Extension
{
    public function getTags() : array
    {
        return [
            'syntax' => $this->parseSyntax( ... ),
        ];
    }

    /**
     * {syntax ...}
     *
     * @param Tag            $tag
     * @param TemplateParser $parser
     *
     * @return Generator
     * @throws CompileException
     */
    private function parseSyntax( Tag $tag, TemplateParser $parser ) : Generator
    {
        if ( $tag->isNAttribute() && $tag->prefix !== $tag::PrefixNone ) {
            throw new CompileException( "Use n:syntax instead of {$tag->getNotation()}", $tag->position );
        }
        $tag->expectArguments();
        $token = $tag->parser->stream->consume();
        $lexer = $parser->getLexer();
        $lexer->setSyntax( $token->text, $tag->isNAttribute() ? null : $tag->name );
        [$inner] = yield;
        if ( ! $tag->isNAttribute() ) {
            $lexer->popSyntax();
        }
        return $inner;
    }
}
