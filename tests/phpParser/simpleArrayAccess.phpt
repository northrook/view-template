<?php

// Simple array access

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$test = <<<'XX'
	$a['b'],
	$a['b']['c'],
	$a[] = $b,
	${$a}['b'],
	XX;

$node = parseCode($test);

Assert::same(
	loadContent(__FILE__, __COMPILER_HALT_OFFSET__),
	exportNode($node),
);

__halt_compiler();
Core\View\Template\Compiler\Nodes\Php\Expression\ArrayNode
   items: array (4)
   |  0 => Core\View\Template\Compiler\Nodes\Php\ArrayItemNode
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\ArrayAccessNode
   |  |  |  expr: Core\View\Template\Compiler\Nodes\Php\Expression\VariableNode
   |  |  |  |  name: 'a'
   |  |  |  |  position: 1:1 (offset 0)
   |  |  |  index: Core\View\Template\Compiler\Nodes\Php\Scalar\StringNode
   |  |  |  |  value: 'b'
   |  |  |  |  position: 1:4 (offset 3)
   |  |  |  position: 1:1 (offset 0)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 1:1 (offset 0)
   |  1 => Core\View\Template\Compiler\Nodes\Php\ArrayItemNode
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\ArrayAccessNode
   |  |  |  expr: Core\View\Template\Compiler\Nodes\Php\Expression\ArrayAccessNode
   |  |  |  |  expr: Core\View\Template\Compiler\Nodes\Php\Expression\VariableNode
   |  |  |  |  |  name: 'a'
   |  |  |  |  |  position: 2:1 (offset 9)
   |  |  |  |  index: Core\View\Template\Compiler\Nodes\Php\Scalar\StringNode
   |  |  |  |  |  value: 'b'
   |  |  |  |  |  position: 2:4 (offset 12)
   |  |  |  |  position: 2:1 (offset 9)
   |  |  |  index: Core\View\Template\Compiler\Nodes\Php\Scalar\StringNode
   |  |  |  |  value: 'c'
   |  |  |  |  position: 2:9 (offset 17)
   |  |  |  position: 2:1 (offset 9)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 2:1 (offset 9)
   |  2 => Core\View\Template\Compiler\Nodes\Php\ArrayItemNode
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\AssignNode
   |  |  |  var: Core\View\Template\Compiler\Nodes\Php\Expression\ArrayAccessNode
   |  |  |  |  expr: Core\View\Template\Compiler\Nodes\Php\Expression\VariableNode
   |  |  |  |  |  name: 'a'
   |  |  |  |  |  position: 3:1 (offset 23)
   |  |  |  |  index: null
   |  |  |  |  position: 3:1 (offset 23)
   |  |  |  expr: Core\View\Template\Compiler\Nodes\Php\Expression\VariableNode
   |  |  |  |  name: 'b'
   |  |  |  |  position: 3:8 (offset 30)
   |  |  |  byRef: false
   |  |  |  position: 3:1 (offset 23)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 3:1 (offset 23)
   |  3 => Core\View\Template\Compiler\Nodes\Php\ArrayItemNode
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\ArrayAccessNode
   |  |  |  expr: Core\View\Template\Compiler\Nodes\Php\Expression\VariableNode
   |  |  |  |  name: Core\View\Template\Compiler\Nodes\Php\Expression\VariableNode
   |  |  |  |  |  name: 'a'
   |  |  |  |  |  position: 4:3 (offset 36)
   |  |  |  |  position: 4:1 (offset 34)
   |  |  |  index: Core\View\Template\Compiler\Nodes\Php\Scalar\StringNode
   |  |  |  |  value: 'b'
   |  |  |  |  position: 4:7 (offset 40)
   |  |  |  position: 4:1 (offset 34)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 4:1 (offset 34)
   position: 1:1 (offset 0)
