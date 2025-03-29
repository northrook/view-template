<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Engine;

use Core\View\Template\{Engine,
    Engine\Core\Filters,
    Compiler\Nodes\BlockNode,
    Compiler\Nodes\CaptureNode,
    Compiler\Nodes\ContentTypeNode,
    Compiler\Nodes\DebugbreakNode,
    Compiler\Nodes\DefineNode,
    Compiler\Nodes\DoNode,
    Compiler\Nodes\DumpNode,
    Compiler\Nodes\EmbedNode,
    Compiler\Nodes\ExtendsNode,
    Compiler\Nodes\FirstLastSepNode,
    Compiler\Nodes\ForeachNode,
    Compiler\Nodes\ForNode,
    Compiler\Nodes\IfChangedNode,
    Compiler\Nodes\IfContentNode,
    Compiler\Nodes\IfNode,
    Compiler\Nodes\ImportNode,
    Compiler\Nodes\IncludeBlockNode,
    Compiler\Nodes\IncludeFileNode,
    Compiler\Nodes\IterateWhileNode,
    Compiler\Nodes\JumpNode,
    Compiler\Nodes\NAttrNode,
    Compiler\Nodes\NClassNode,
    Compiler\Nodes\NElseNode,
    Compiler\Nodes\NTagNode,
    Compiler\Nodes\ParametersNode,
    Compiler\Nodes\PrintNode,
    Compiler\Nodes\RollbackNode,
    Compiler\Nodes\SpacelessNode,
    Compiler\Nodes\SwitchNode,
    Compiler\Nodes\TemplatePrintNode,
    Compiler\Nodes\TemplateTypeNode,
    Compiler\Nodes\TraceNode,
    Compiler\Nodes\TryNode,
    Compiler\Nodes\VarNode,
    Compiler\Nodes\VarPrintNode,
    Compiler\Nodes\VarTypeNode,
    Compiler\Nodes\WhileNode,
    Engine\Core\Passes,
    Extension,
    Exception\CompileException,
    Exception\RuntimeException
};
use Core\View\Template\Compiler\{Nodes\Php\Scalar\StringNode, Tag, TemplateParser};
use Core\View\Template\Compiler\Nodes\TextNode;
use Core\View\Template\Runtime\{Filters as RuntimeFilters, Template};
use Generator;

/**
 * Basic tags and filters for Latte.
 */
final class CoreExtension extends Extension
{
    private Engine $engine;

    private Filters $filters;

    public function __construct()
    {
        $this->filters = new Filters();
    }

    public function beforeCompile( Engine $engine ) : void
    {
        $this->engine = $engine;
    }

    public function beforeRender( Template $template ) : void
    {
        $this->filters->locale = $template->getEngine()->getLocale();
    }

    public function getTags() : array
    {
        return [
            'embed'   => [EmbedNode::class, 'create'],
            'define'  => [DefineNode::class, 'create'],
            'block'   => [BlockNode::class, 'create'],
            'layout'  => [ExtendsNode::class, 'create'],
            'extends' => [ExtendsNode::class, 'create'],
            'import'  => [ImportNode::class, 'create'],
            'include' => $this->includeSplitter( ... ),

            'n:attr'  => [NAttrNode::class, 'create'],
            'n:class' => [NClassNode::class, 'create'],
            'n:tag'   => [NTagNode::class, 'create'],

            'parameters'   => [ParametersNode::class, 'create'],
            'varType'      => [VarTypeNode::class, 'create'],
            'varPrint'     => [VarPrintNode::class, 'create'],
            'templateType' => [TemplateTypeNode::class, 'create'],

            // : Debugging - prints the literal PHP class code
            'templatePrint' => [TemplatePrintNode::class, 'create'],

            '='   => [PrintNode::class, 'create'],
            'do'  => [DoNode::class, 'create'],
            'php' => [DoNode::class, 'create'],
            // obsolete
            'contentType' => [ContentTypeNode::class, 'create'],
            'spaceless'   => [SpacelessNode::class, 'create'],
            'capture'     => [CaptureNode::class, 'create'],
            'l'           => fn( Tag $tag ) => new TextNode( '{', $tag->position ),
            'r'           => fn( Tag $tag ) => new TextNode( '}', $tag->position ),
            'syntax'      => $this->parseSyntax( ... ),

            'dump'       => [DumpNode::class, 'create'],
            'debugbreak' => [DebugbreakNode::class, 'create'],
            'trace'      => [TraceNode::class, 'create'],

            'var'     => [VarNode::class, 'create'],
            'default' => [VarNode::class, 'create'],

            'try'      => [TryNode::class, 'create'],
            'rollback' => [RollbackNode::class, 'create'],

            'foreach'      => [ForeachNode::class, 'create'],
            'for'          => [ForNode::class, 'create'],
            'while'        => [WhileNode::class, 'create'],
            'iterateWhile' => [IterateWhileNode::class, 'create'],
            'sep'          => [FirstLastSepNode::class, 'create'],
            'last'         => [FirstLastSepNode::class, 'create'],
            'first'        => [FirstLastSepNode::class, 'create'],
            'skipIf'       => [JumpNode::class, 'create'],
            'breakIf'      => [JumpNode::class, 'create'],
            'exitIf'       => [JumpNode::class, 'create'],
            'continueIf'   => [JumpNode::class, 'create'],

            'if'          => [IfNode::class, 'create'],
            'ifset'       => [IfNode::class, 'create'],
            'ifchanged'   => [IfChangedNode::class, 'create'],
            'n:ifcontent' => [IfContentNode::class, 'create'],
            'n:else'      => [NElseNode::class, 'create'],
            'switch'      => [SwitchNode::class, 'create'],
        ];
    }

    public function getFilters() : array
    {
        return [
            'batch'      => [$this->filters, 'batch'],
            'breakLines' => [$this->filters, 'breaklines'],
            'breaklines' => [$this->filters, 'breaklines'],
            'bytes'      => [$this->filters, 'bytes'],
            'capitalize' => \extension_loaded( 'mbstring' )
                    ? [$this->filters, 'capitalize']
                    : fn() => throw new RuntimeException( 'Filter |capitalize requires mbstring extension.' ),
            'ceil'              => [$this->filters, 'ceil'],
            'checkUrl'          => [RuntimeFilters::class, 'safeUrl'],
            'clamp'             => [$this->filters, 'clamp'],
            'dataStream'        => [$this->filters, 'dataStream'],
            'datastream'        => [$this->filters, 'dataStream'],
            'date'              => [$this->filters, 'date'],
            'escape'            => [RuntimeFilters::class, 'nop'],
            'escapeCss'         => [RuntimeFilters::class, 'escapeCss'],
            'escapeHtml'        => [RuntimeFilters::class, 'escapeHtml'],
            'escapeHtmlComment' => [RuntimeFilters::class, 'escapeHtmlComment'],
            'escapeICal'        => [RuntimeFilters::class, 'escapeICal'],
            'escapeJs'          => [RuntimeFilters::class, 'escapeJs'],
            'escapeUrl'         => 'rawurlencode',
            'escapeXml'         => [RuntimeFilters::class, 'escapeXml'],
            'explode'           => [$this->filters, 'explode'],
            'first'             => [$this->filters, 'first'],
            'firstUpper'        => \extension_loaded( 'mbstring' )
                    ? [$this->filters, 'firstUpper']
                    : fn() => throw new RuntimeException( 'Filter |firstUpper requires mbstring extension.' ),
            'floor'   => [$this->filters, 'floor'],
            'group'   => [$this->filters, 'group'],
            'implode' => [$this->filters, 'implode'],
            'indent'  => [$this->filters, 'indent'],
            'join'    => [$this->filters, 'implode'],
            'last'    => [$this->filters, 'last'],
            'length'  => [$this->filters, 'length'],
            'lower'   => \extension_loaded( 'mbstring' )
                    ? [$this->filters, 'lower']
                    : fn() => throw new RuntimeException( 'Filter |lower requires mbstring extension.' ),
            'number'    => [$this->filters, 'number'],
            'padLeft'   => [$this->filters, 'padLeft'],
            'padRight'  => [$this->filters, 'padRight'],
            'query'     => [$this->filters, 'query'],
            'random'    => [$this->filters, 'random'],
            'repeat'    => [$this->filters, 'repeat'],
            'replace'   => [$this->filters, 'replace'],
            'replaceRe' => [$this->filters, 'replaceRe'],
            'replaceRE' => [$this->filters, 'replaceRe'],
            'reverse'   => [$this->filters, 'reverse'],
            'round'     => [$this->filters, 'round'],
            'slice'     => [$this->filters, 'slice'],
            'sort'      => [$this->filters, 'sort'],
            'spaceless' => [$this->filters, 'strip'],
            'split'     => [$this->filters, 'explode'],
            'strip'     => [$this->filters, 'strip'], // obsolete
            'stripHtml' => [$this->filters, 'stripHtml'],
            'striphtml' => [$this->filters, 'stripHtml'],
            'stripTags' => [$this->filters, 'stripTags'],
            'striptags' => [$this->filters, 'stripTags'],
            'substr'    => [$this->filters, 'substring'],
            'trim'      => [$this->filters, 'trim'],
            'truncate'  => [$this->filters, 'truncate'],
            'upper'     => \extension_loaded( 'mbstring' )
                    ? [$this->filters, 'upper']
                    : fn() => throw new RuntimeException( 'Filter |upper requires mbstring extension.' ),
            'webalize' => [$this->filters, 'webalize'],
        ];
    }

    public function getFunctions() : array
    {
        return [
            'clamp'       => [$this->filters, 'clamp'],
            'divisibleBy' => [$this->filters, 'divisibleBy'],
            'even'        => [$this->filters, 'even'],
            'first'       => [$this->filters, 'first'],
            'group'       => [$this->filters, 'group'],
            'last'        => [$this->filters, 'last'],
            'odd'         => [$this->filters, 'odd'],
            'slice'       => [$this->filters, 'slice'],
            'hasBlock'    => fn(
                Template $template,
                string   $name,
            ) : bool => $template->hasBlock( $name ),
        ];
    }

    public function getPasses() : array
    {
        $passes = new Passes( $this->engine );
        return [
            'internalVariables'    => [$passes, 'forbiddenVariablesPass'],
            'overwrittenVariables' => [
                ForeachNode::class,
                'overwrittenVariablesPass',
            ],
            'customFunctions'         => [$passes, 'customFunctionsPass'],
            'moveTemplatePrintToHead' => [
                TemplatePrintNode::class,
                'moveToHeadPass',
            ],
            'nElse' => [NElseNode::class, 'processPass'],
        ];
    }

    /**
     * {include [file] "file" [with blocks] [,] [params]}
     * {include [block] name [,] [params]}
     *
     * @param Tag            $tag
     * @param TemplateParser $parser
     *
     * @return IncludeBlockNode|IncludeFileNode
     * @throws CompileException
     */
    private function includeSplitter(
        Tag            $tag,
        TemplateParser $parser,
    ) : IncludeBlockNode|IncludeFileNode {
        $tag->expectArguments();
        $mod = $tag->parser->tryConsumeTokenBeforeUnquotedString( 'block', 'file' );
        if ( $mod ) {
            $block = $mod->text === 'block';
        }
        elseif ( $tag->parser->stream->tryConsume( '#' ) ) {
            $block = true;
        }
        else {
            $name  = $tag->parser->parseUnquotedStringOrExpression();
            $block = $name instanceof StringNode && \preg_match(
                '~[\w-]+$~DA',
                $name->value,
            );
        }
        $tag->parser->stream->seek( 0 );

        return $block
                ? IncludeBlockNode::create( $tag, $parser )
                : IncludeFileNode::create( $tag );
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
