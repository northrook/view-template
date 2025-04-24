<?php

// Interpolated strings

declare(strict_types=1);

use Core\View\Template\Compiler\Nodes\Php\Expression\FunctionCallNode;
use Core\View\Template\Compiler\Nodes\Php\Expression\PropertyFetchNode;
use Core\View\Template\Compiler\Nodes\Php\Expression\VariableNode;
use Core\View\Template\Compiler\Nodes\Php\IdentifierNode;
use Core\View\Template\Compiler\Nodes\Php\InterpolatedStringPartNode;
use Core\View\Template\Compiler\Nodes\Php\NameNode;
use Core\View\Template\Compiler\Nodes\Php\Scalar\InterpolatedStringNode;
use Core\View\Template\Compiler\PrintContext;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$parts = [];
$parts[] = new PropertyFetchNode(new VariableNode('foo'), new IdentifierNode('bar'));
$parts[] = new InterpolatedStringPartNode(' ');
$node = new InterpolatedStringNode($parts);

Assert::same(
	'"{$foo->bar} "',
	$node->print(new PrintContext),
);

$parts[] = new PropertyFetchNode(new FunctionCallNode(new NameNode('foo')), new IdentifierNode('bar'));
$node = new InterpolatedStringNode($parts);

Assert::same(
	'("{$foo->bar} " . (foo()->bar) . "")',
	$node->print(new PrintContext),
);
