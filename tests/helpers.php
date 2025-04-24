<?php

declare(strict_types=1);

use Core\View\Template\Engine;
use Core\View\Template\Loaders\StringLoader;
use Core\View\Template\Compiler\{Node, Nodes, PhpHelpers, Position, PrintContext, TagLexer, TagParser, Token};
use Tester\Helpers;
use Tracy\Dumper;

function getTempDir() : string
{
    $dir = __DIR__.'/tmp/'.getmypid();

    if ( empty( $GLOBALS['\lock'] ) ) {
        // garbage collector
        $GLOBALS['\lock'] = $lock = fopen( __DIR__.'/lock', 'w' );
        if ( rand( 0, 100 ) ) {
            flock( $lock, LOCK_SH );
            @mkdir( dirname( $dir ) );
        }
        elseif ( flock( $lock, LOCK_EX ) ) {
            Helpers::purge( dirname( $dir ) );
        }

        @mkdir( $dir );
    }

    return $dir;
}

function test( string $title, \Closure $function ) : void
{
    $function();
}

function normalizeNl( string $s ) : string
{
    return str_replace( "\r\n", "\n", $s );
}

/**
 * @param string $code
 *
 * @return Nodes\Php\Expression\ArrayNode
 * @throws \Core\View\Template\Exception\CompileException
 */
function parseCode( string $code ) : Nodes\Php\Expression\ArrayNode
{
    $code   = normalizeNl( $code );
    $tokens = ( new TagLexer() )->tokenize( $code );
    $parser = new TagParser( $tokens );
    $node   = $parser->parseArguments();
    if ( ! $parser->isEnd() ) {
        $parser->stream->throwUnexpectedException();
    }
    return $node;
}

/** @noinspection PhpInternalEntityUsedInspection */
function exportNode( Node $node ) : string
{
    $exporters = [
        Position::class => function( Position $pos, Dumper\Value $value ) {
            $value->value = $pos->line.':'.$pos->column.' (offset '.$pos->offset.')';
        },
    ];
    $dump = Dumper::toText(
        $node,
        [Dumper::HASH => false, Dumper::DEPTH => 20, Dumper::OBJECT_EXPORTERS => $exporters],
    );
    return trim( $dump )."\n";
}

function printNode( Nodes\Php\Expression\ArrayNode $node ) : string
{
    $context = new PrintContext();
    $code    = $context->implode( $node->items, ",\n" );
    return $code."\n";
}

function exportTokens( array $tokens ) : string
{
    static $table;
    if ( ! $table ) {
        $table = @array_flip( ( new \ReflectionClass( Token::class ) )->getConstants() );
    }
    $res = '';

    foreach ( $tokens as $token ) {
        $res .= str_pad( '#'.$token->position->line.':'.$token->position->column, 6 ).' ';
        if ( isset( $table[$token->type] ) ) {
            $res .= str_pad( $table[$token->type], 15 ).' ';
        }
        $res .= "'".addcslashes( normalizeNl( $token->text ), "\n\t\f\v\"\\" )."'\n";
    }

    return $res;
}

function loadContent( string $file, int $offset ) : string
{
    $s = file_get_contents( $file );
    $s = substr( $s, $offset );
    return normalizeNl( ltrim( $s ) );
}

function exportAST( Node $node ) : string
{
    $prop = match ( true ) {
        $node instanceof Nodes\TextNode => 'content: '.var_export(
            $node->content,
            true,
        ),
        $node instanceof Nodes\Html\ElementNode,
        $node instanceof Nodes\Php\IdentifierNode,
        $node instanceof Nodes\Php\NameNode         => 'name: '.$node->name,
        $node instanceof Nodes\Php\SuperiorTypeNode => PhpHelpers::dump(
            $node->type,
        ),
        $node instanceof Nodes\Php\Scalar\FloatNode,
        $node instanceof Nodes\Php\InterpolatedStringPartNode,
        $node instanceof Nodes\Php\Scalar\IntegerNode,
        $node instanceof Nodes\Php\Scalar\StringNode => 'value: '.$node->value,
        $node instanceof Nodes\Php\Expression\AssignOpNode,
        $node instanceof Nodes\Php\Expression\BinaryOpNode                             => 'operator: '.$node->operator,
        $node instanceof Nodes\Php\Expression\CastNode                                 => 'type: '.$node->type,
        $node instanceof Nodes\Php\Expression\VariableNode && is_string( $node->name ) => 'name: '.$node->name,
        default                                                                        => '',
    };
    $res = $prop ? $prop."\n" : '';

    foreach ( $node as $sub ) {
        $res .= rtrim( exportAST( $sub ), "\n" )."\n";
    }

    return substr( $node::class, strrpos( $node::class, '\\' ) + 1, -4 )
           .':'
           .( $res ? "\n".preg_replace( '#^(?=.)#m', "\t", $res ) : '' )
           ."\n";
}

/**
 * @param string  $template
 * @param ?Engine $latte
 *
 * @return string
 * @throws \Core\View\Template\Exception\CompileException
 */
function exportTraversing( string $template, ?Engine $latte = null ) : string
{
    $latte ??= new Engine();
    $latte->setLoader( new StringLoader() );
    $node = $latte->parse( $template );
    return exportAST( $node );
}
