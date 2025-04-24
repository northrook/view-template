<?php

// Casts

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$test = <<<'XX'
	(array)   $a,
	(bool)    $a,
	(float)   $a,
	(int)     $a,
	(object)  $a,
	(string)  $a,
	XX;

$node = parseCode($test);

Assert::same(
	loadContent(__FILE__, __COMPILER_HALT_OFFSET__),
	exportNode($node),
);

__halt_compiler();
Core\View\Template\Compiler\Nodes\Php\Expression\ArrayNode
   items: array (6)
   |  0 => Core\View\Template\Compiler\Nodes\Php\ArrayItemNode
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\CastNode
   |  |  |  type: 'array'
   |  |  |  expr: Core\View\Template\Compiler\Nodes\Php\Expression\VariableNode
   |  |  |  |  name: 'a'
   |  |  |  |  position: 1:11 (offset 10)
   |  |  |  position: 1:1 (offset 0)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 1:1 (offset 0)
   |  1 => Core\View\Template\Compiler\Nodes\Php\ArrayItemNode
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\CastNode
   |  |  |  type: 'bool'
   |  |  |  expr: Core\View\Template\Compiler\Nodes\Php\Expression\VariableNode
   |  |  |  |  name: 'a'
   |  |  |  |  position: 2:11 (offset 24)
   |  |  |  position: 2:1 (offset 14)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 2:1 (offset 14)
   |  2 => Core\View\Template\Compiler\Nodes\Php\ArrayItemNode
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\CastNode
   |  |  |  type: 'float'
   |  |  |  expr: Core\View\Template\Compiler\Nodes\Php\Expression\VariableNode
   |  |  |  |  name: 'a'
   |  |  |  |  position: 3:11 (offset 38)
   |  |  |  position: 3:1 (offset 28)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 3:1 (offset 28)
   |  3 => Core\View\Template\Compiler\Nodes\Php\ArrayItemNode
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\CastNode
   |  |  |  type: 'int'
   |  |  |  expr: Core\View\Template\Compiler\Nodes\Php\Expression\VariableNode
   |  |  |  |  name: 'a'
   |  |  |  |  position: 4:11 (offset 52)
   |  |  |  position: 4:1 (offset 42)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 4:1 (offset 42)
   |  4 => Core\View\Template\Compiler\Nodes\Php\ArrayItemNode
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\CastNode
   |  |  |  type: 'object'
   |  |  |  expr: Core\View\Template\Compiler\Nodes\Php\Expression\VariableNode
   |  |  |  |  name: 'a'
   |  |  |  |  position: 5:11 (offset 66)
   |  |  |  position: 5:1 (offset 56)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 5:1 (offset 56)
   |  5 => Core\View\Template\Compiler\Nodes\Php\ArrayItemNode
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\CastNode
   |  |  |  type: 'string'
   |  |  |  expr: Core\View\Template\Compiler\Nodes\Php\Expression\VariableNode
   |  |  |  |  name: 'a'
   |  |  |  |  position: 6:11 (offset 80)
   |  |  |  position: 6:1 (offset 70)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 6:1 (offset 70)
   position: 1:1 (offset 0)
