<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Exception\CompileException;
use Core\View\Template\Compiler\{PrintContext, Tag};

/**
 * {rollback}
 */
class RollbackNode extends StatementNode
{
    /**
     * @param Tag $tag
     *
     * @return RollbackNode
     * @throws CompileException
     */
    public static function create( Tag $tag ) : static
    {
        if ( ! $tag->closestTag( [TryNode::class] ) ) {
            throw new CompileException( 'Tag {rollback} must be inside {try} ... {/try}.', $tag->position );
        }

        return new static();
    }

    public function print( PrintContext $context ) : string
    {
        return 'throw new Core\View\Template\Essential\RollbackException;';
    }
}
