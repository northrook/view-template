<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Sandbox\Nodes;

use Core\View\Template\Compiler\Nodes\Php\Expression\ArrayNode;
use Core\View\Template\Compiler\Nodes\Php\ExpressionNode;
use Core\View\Template\Compiler\Nodes\StatementNode;
use Generator;
use Core\View\Template\Compiler\{PrintContext, Tag, TemplateGenerator};

/**
 * {sandbox "file" [,] [params]}
 */
class SandboxNode extends StatementNode
{
    public ExpressionNode $file;

    public ArrayNode $args;

    /**
     * @param Tag $tag
     *
     * @return static
     * @throws \Core\View\Template\Exception\CompileException
     */
    public static function create( Tag $tag ) : static
    {
        $tag->outputMode = $tag::OutputRemoveIndentation;
        $tag->expectArguments();
        $node       = new static();
        $node->file = $tag->parser->parseUnquotedStringOrExpression();
        $tag->parser->stream->tryConsume( ',' );
        $node->args = $tag->parser->parseArguments();
        return $node;
    }

    public function print( PrintContext $context ) : string
    {
        $_exception = TemplateGenerator::ARG_EXCEPTION;
        return $context->format(
            <<<XX
                ob_start(fn() => '');
                try {
                	\$this->createTemplate(%node, %node, 'sandbox')->renderToContentType(%dump) %line;
                } catch (\Throwable {$_exception}) {
                	if (isset(\$this->global->coreExceptionHandler)) {
                		ob_clean();
                		(\$this->global->coreExceptionHandler)({$_exception}, \$this);
                	} else {
                		throw {$_exception};
                	}
                } finally {
                	echo ob_get_clean();
                }
                XX,
            $this->file,
            $this->args,
            $context->getEscaper()->export(),
            $this->position,
        );
    }

    public function &getIterator() : Generator
    {
        yield $this->file;
        yield $this->args;
    }
}
