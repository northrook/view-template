<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Exception\CompileException;
use Core\View\Template\Compiler\Nodes\Php\Expression\ArrayNode;
use Core\View\Template\Compiler\Nodes\Php\ExpressionNode;
use Core\View\Template\Compiler\{PrintContext, Tag, TemplateGenerator};
use Core\View\Template\Compiler\Nodes\{FragmentNode, StatementNode, TextNode};
use Generator;

/**
 * {switch} ... {case} ... {default}
 */
class SwitchNode extends StatementNode
{
    public ?ExpressionNode $expression;

    /** @var array<array{?ArrayNode, int, FragmentNode}> */
    public array $cases = [];

    /**
     * @param Tag $tag
     *
     * @return Generator<int, ?array, array{FragmentNode, Tag}, static>
     * @throws CompileException
     * @throws CompileException
     * @throws CompileException
     * @throws CompileException
     */
    public static function create( Tag $tag ) : Generator
    {
        if ( $tag->isNAttribute() ) {
            throw new CompileException( 'Attribute n:switch is not supported.', $tag->position );
        }

        $node             = $tag->node = new static();
        $node->expression = $tag->parser->isEnd()
                ? null
                : $tag->parser->parseExpression();

        [$content, $nextTag] = yield ['case', 'default'];

        foreach ( $content->children as $child ) {
            if ( ! $child instanceof TextNode || ! $child->isWhitespace() ) {
                throw new CompileException( 'No content is allowed between {switch} and {case}', $child->position );
            }
        }

        $default = 0;
        while ( true ) {
            if ( $nextTag->name === 'case' ) {
                $nextTag->expectArguments();
                [$case, $line]       = [$nextTag->parser->parseArguments(), $nextTag->position];
                [$content, $nextTag] = yield ['case', 'default'];
                $node->cases[]       = [$case, $line, $content];
            }
            elseif ( $nextTag->name === 'default' ) {
                if ( $default++ ) {
                    throw new CompileException(
                        'Tag {switch} may only contain one {default} clause.',
                        $nextTag->position,
                    );
                }
                $line                = $nextTag->position;
                [$content, $nextTag] = yield ['case', 'default'];
                $node->cases[]       = [null, $line, $content];
            }
            else {
                return $node;
            }
        }
    }

    public function print( PrintContext $context ) : string
    {
        $_switch = TemplateGenerator::ARG_SWITCH;
        $res     = $context->format(
            $_switch.' = (%node) %line;',
            $this->expression,
            $this->position,
        );
        $first   = true;
        $default = null;

        foreach ( $this->cases as [$case, $line, $content] ) {
            if ( ! $case ) {
                $default = $content->print( $context );

                continue;
            }
            if ( ! $first ) {
                $res .= 'else';
            }

            $first = false;
            $res .= $context->format(
                "if (in_array({$_switch}, %node, true)) %line { %node } ",
                $case,
                $line,
                $content,
            );
        }

        if ( $default ) {
            $res .= $first ? $default : 'else { '.$default.' } ';
        }
        return $res;
    }

    public function &getIterator() : Generator
    {
        yield $this->expression;

        foreach ( $this->cases as [&$case, , &$stmt] ) {
            if ( $case ) {
                yield $case;
            }
            yield $stmt;
        }
    }
}
