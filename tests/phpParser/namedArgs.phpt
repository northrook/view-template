<?php

// Named arguments

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$test = <<<'XX'
	foo(a: $b, c: $d),
	bar(class: 0),
	XX;

$node = parseCode($test);

Assert::same(
	loadContent(__FILE__, __COMPILER_HALT_OFFSET__),
	exportNode($node),
);

__halt_compiler();
Core\View\Template\Compiler\Nodes\Php\Expression\ArrayNode
   items: array (2)
   |  0 => Core\View\Template\Compiler\Nodes\Php\ArrayItemNode
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\FunctionCallNode
   |  |  |  name: Core\View\Template\Compiler\Nodes\Php\NameNode
   |  |  |  |  name: 'foo'
   |  |  |  |  kind: 1
   |  |  |  |  position: 1:1 (offset 0)
   |  |  |  args: array (2)
   |  |  |  |  0 => Core\View\Template\Compiler\Nodes\Php\ArgumentNode
   |  |  |  |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\VariableNode
   |  |  |  |  |  |  name: 'b'
   |  |  |  |  |  |  position: 1:8 (offset 7)
   |  |  |  |  |  byRef: false
   |  |  |  |  |  unpack: false
   |  |  |  |  |  name: Core\View\Template\Compiler\Nodes\Php\IdentifierNode
   |  |  |  |  |  |  name: 'a'
   |  |  |  |  |  |  position: 1:5 (offset 4)
   |  |  |  |  |  position: 1:5 (offset 4)
   |  |  |  |  1 => Core\View\Template\Compiler\Nodes\Php\ArgumentNode
   |  |  |  |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\VariableNode
   |  |  |  |  |  |  name: 'd'
   |  |  |  |  |  |  position: 1:15 (offset 14)
   |  |  |  |  |  byRef: false
   |  |  |  |  |  unpack: false
   |  |  |  |  |  name: Core\View\Template\Compiler\Nodes\Php\IdentifierNode
   |  |  |  |  |  |  name: 'c'
   |  |  |  |  |  |  position: 1:12 (offset 11)
   |  |  |  |  |  position: 1:12 (offset 11)
   |  |  |  position: 1:1 (offset 0)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 1:1 (offset 0)
   |  1 => Core\View\Template\Compiler\Nodes\Php\ArrayItemNode
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\FunctionCallNode
   |  |  |  name: Core\View\Template\Compiler\Nodes\Php\NameNode
   |  |  |  |  name: 'bar'
   |  |  |  |  kind: 1
   |  |  |  |  position: 2:1 (offset 19)
   |  |  |  args: array (1)
   |  |  |  |  0 => Core\View\Template\Compiler\Nodes\Php\ArgumentNode
   |  |  |  |  |  value: Core\View\Template\Compiler\Nodes\Php\Scalar\IntegerNode
   |  |  |  |  |  |  value: 0
   |  |  |  |  |  |  kind: 10
   |  |  |  |  |  |  position: 2:12 (offset 30)
   |  |  |  |  |  byRef: false
   |  |  |  |  |  unpack: false
   |  |  |  |  |  name: Core\View\Template\Compiler\Nodes\Php\IdentifierNode
   |  |  |  |  |  |  name: 'class'
   |  |  |  |  |  |  position: 2:5 (offset 23)
   |  |  |  |  |  position: 2:5 (offset 23)
   |  |  |  position: 2:1 (offset 19)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 2:1 (offset 19)
   position: 1:1 (offset 0)
