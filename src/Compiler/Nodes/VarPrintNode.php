<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes;

use Core\View\Template\Compiler\{PrintContext, Tag, TemplateGenerator};

/**
 * {varPrint [all]}
 */
class VarPrintNode extends StatementNode
{
    public bool $all;

    /**
     * @param Tag $tag
     *
     * @return VarPrintNode
     * @throws \Core\View\Template\Exception\CompileException
     */
    public static function create( Tag $tag ) : static
    {
        $stream    = $tag->parser->stream;
        $node      = new static();
        $node->all = $stream->consume()->text === 'all';
        return $node;
    }

    public function print( ?PrintContext $context ) : string
    {
        $_bp = TemplateGenerator::ARG_BEGIN_PRINT;

        $vars = $this->all
                ? 'get_defined_vars()'
                : 'array_diff_key(get_defined_vars(), $this->getParameters())';
        return <<<XX
            {$_bp} = new \Core\View\Template\Support\Blueprint;
            {$_bp}->printBegin();
            {$_bp}->printVars({$vars});
            {$_bp}->printEnd();
            exit;
            XX;
    }
}
