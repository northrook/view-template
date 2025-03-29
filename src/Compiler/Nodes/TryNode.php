<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Exception\RollbackException;
use Generator;

use Core\View\Template\Compiler\{PrintContext, Tag, TemplateGenerator};
use Core\View\Template\Compiler\Nodes\{AreaNode, StatementNode};

/**
 * {try} ... {else}
 */
class TryNode extends StatementNode
{
    public AreaNode $try;

    public ?AreaNode $else = null;

    /**
     * @param Tag $tag
     *
     * @return Generator<int, ?array, array{AreaNode, ?Tag}, static>
     */
    public static function create( Tag $tag ) : Generator
    {
        $node                  = $tag->node = new static();
        [$node->try, $nextTag] = yield ['else'];
        if ( $nextTag?->name === 'else' ) {
            [$node->else] = yield;
        }

        return $node;
    }

    public function print( PrintContext $context ) : string
    {
        $_try             = TemplateGenerator::ARG_TRY;
        $_it              = TemplateGenerator::ARG_IT;
        $_exception       = TemplateGenerator::ARG_EXCEPTION;
        $_exception_class = RollbackException::class;

        return $context->format(
            <<<XX
                {$_try}[%dump] = [{$_it} ?? null];
                ob_start(fn() => '');
                try %line {
                	%node
                } catch (Throwable {$_exception}) {
                	ob_clean();
                	if (!({$_exception} instanceof {$_exception_class}) && isset(\$this->global->coreExceptionHandler)) {
                		(\$this->global->coreExceptionHandler)({$_exception}, \$this);
                	}
                	%node
                } finally {
                	echo ob_get_clean();
                	\$iterator = {$_it} = {$_try}[%0.dump][0];
                }
                XX,
            $context->generateId(),
            $this->position,
            $this->try,
            $this->else,
        );
    }

    public function &getIterator() : Generator
    {
        yield $this->try;
        if ( $this->else ) {
            yield $this->else;
        }
    }
}
