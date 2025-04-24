<?php

// First-class callables

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$test = <<<'XX'
	foo(...),
	$this->foo(...),
	A::foo(...),
	XX;

$node = parseCode($test);

Assert::same(
	loadContent(__FILE__, __COMPILER_HALT_OFFSET__),
	exportNode($node),
);

__halt_compiler();
Core\View\Template\Compiler\Nodes\Php\Expression\ArrayNode
   items: array (3)
   |  0 => Core\View\Template\Compiler\Nodes\Php\ArrayItemNode
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\FunctionCallableNode
   |  |  |  name: Core\View\Template\Compiler\Nodes\Php\NameNode
   |  |  |  |  name: 'foo'
   |  |  |  |  kind: 1
   |  |  |  |  position: 1:1 (offset 0)
   |  |  |  position: 1:1 (offset 0)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 1:1 (offset 0)
   |  1 => Core\View\Template\Compiler\Nodes\Php\ArrayItemNode
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\MethodCallableNode
   |  |  |  object: Core\View\Template\Compiler\Nodes\Php\Expression\VariableNode
   |  |  |  |  name: 'this'
   |  |  |  |  position: 2:1 (offset 10)
   |  |  |  name: Core\View\Template\Compiler\Nodes\Php\IdentifierNode
   |  |  |  |  name: 'foo'
   |  |  |  |  position: 2:8 (offset 17)
   |  |  |  position: 2:1 (offset 10)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 2:1 (offset 10)
   |  2 => Core\View\Template\Compiler\Nodes\Php\ArrayItemNode
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\StaticMethodCallableNode
   |  |  |  class: Core\View\Template\Compiler\Nodes\Php\NameNode
   |  |  |  |  name: 'A'
   |  |  |  |  kind: 1
   |  |  |  |  position: 3:1 (offset 27)
   |  |  |  name: Core\View\Template\Compiler\Nodes\Php\IdentifierNode
   |  |  |  |  name: 'foo'
   |  |  |  |  position: 3:4 (offset 30)
   |  |  |  position: 3:1 (offset 27)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 3:1 (offset 27)
   position: 1:1 (offset 0)
