<?php

// Undefined operator

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$test = <<<'XX'
	$a??->b,
	$a??->b($c),
	new $a??->b,
	"{$a??->b}",
	"$a??->b",
	XX;

$node = parseCode($test);

Assert::same(
	loadContent(__FILE__, __COMPILER_HALT_OFFSET__),
	exportNode($node),
);

__halt_compiler();
Core\View\Template\Compiler\Nodes\Php\Expression\ArrayNode
   items: array (5)
   |  0 => Core\View\Template\Compiler\Nodes\Php\ArrayItemNode
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\PropertyFetchNode
   |  |  |  object: Core\View\Template\Compiler\Nodes\Php\Expression\BinaryOpNode
   |  |  |  |  left: Core\View\Template\Compiler\Nodes\Php\Expression\VariableNode
   |  |  |  |  |  name: 'a'
   |  |  |  |  |  position: 1:1 (offset 0)
   |  |  |  |  operator: '??'
   |  |  |  |  right: Core\View\Template\Compiler\Nodes\Php\Scalar\NullNode
   |  |  |  |  |  position: 1:1 (offset 0)
   |  |  |  |  position: 1:1 (offset 0)
   |  |  |  name: Core\View\Template\Compiler\Nodes\Php\IdentifierNode
   |  |  |  |  name: 'b'
   |  |  |  |  position: 1:7 (offset 6)
   |  |  |  nullsafe: true
   |  |  |  position: 1:1 (offset 0)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 1:1 (offset 0)
   |  1 => Core\View\Template\Compiler\Nodes\Php\ArrayItemNode
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\MethodCallNode
   |  |  |  object: Core\View\Template\Compiler\Nodes\Php\Expression\BinaryOpNode
   |  |  |  |  left: Core\View\Template\Compiler\Nodes\Php\Expression\VariableNode
   |  |  |  |  |  name: 'a'
   |  |  |  |  |  position: 2:1 (offset 9)
   |  |  |  |  operator: '??'
   |  |  |  |  right: Core\View\Template\Compiler\Nodes\Php\Scalar\NullNode
   |  |  |  |  |  position: 2:1 (offset 9)
   |  |  |  |  position: 2:1 (offset 9)
   |  |  |  name: Core\View\Template\Compiler\Nodes\Php\IdentifierNode
   |  |  |  |  name: 'b'
   |  |  |  |  position: 2:7 (offset 15)
   |  |  |  args: array (1)
   |  |  |  |  0 => Core\View\Template\Compiler\Nodes\Php\ArgumentNode
   |  |  |  |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\VariableNode
   |  |  |  |  |  |  name: 'c'
   |  |  |  |  |  |  position: 2:9 (offset 17)
   |  |  |  |  |  byRef: false
   |  |  |  |  |  unpack: false
   |  |  |  |  |  name: null
   |  |  |  |  |  position: 2:9 (offset 17)
   |  |  |  nullsafe: true
   |  |  |  position: 2:1 (offset 9)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 2:1 (offset 9)
   |  2 => Core\View\Template\Compiler\Nodes\Php\ArrayItemNode
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\NewNode
   |  |  |  class: Core\View\Template\Compiler\Nodes\Php\Expression\PropertyFetchNode
   |  |  |  |  object: Core\View\Template\Compiler\Nodes\Php\Expression\BinaryOpNode
   |  |  |  |  |  left: Core\View\Template\Compiler\Nodes\Php\Expression\VariableNode
   |  |  |  |  |  |  name: 'a'
   |  |  |  |  |  |  position: 3:5 (offset 26)
   |  |  |  |  |  operator: '??'
   |  |  |  |  |  right: Core\View\Template\Compiler\Nodes\Php\Scalar\NullNode
   |  |  |  |  |  |  position: 3:5 (offset 26)
   |  |  |  |  |  position: 3:5 (offset 26)
   |  |  |  |  name: Core\View\Template\Compiler\Nodes\Php\IdentifierNode
   |  |  |  |  |  name: 'b'
   |  |  |  |  |  position: 3:11 (offset 32)
   |  |  |  |  nullsafe: true
   |  |  |  |  position: 3:5 (offset 26)
   |  |  |  args: array (0)
   |  |  |  position: 3:1 (offset 22)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 3:1 (offset 22)
   |  3 => Core\View\Template\Compiler\Nodes\Php\ArrayItemNode
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Scalar\InterpolatedStringNode
   |  |  |  parts: array (1)
   |  |  |  |  0 => Core\View\Template\Compiler\Nodes\Php\Expression\PropertyFetchNode
   |  |  |  |  |  object: Core\View\Template\Compiler\Nodes\Php\Expression\BinaryOpNode
   |  |  |  |  |  |  left: Core\View\Template\Compiler\Nodes\Php\Expression\VariableNode
   |  |  |  |  |  |  |  name: 'a'
   |  |  |  |  |  |  |  position: 4:3 (offset 37)
   |  |  |  |  |  |  operator: '??'
   |  |  |  |  |  |  right: Core\View\Template\Compiler\Nodes\Php\Scalar\NullNode
   |  |  |  |  |  |  |  position: 4:3 (offset 37)
   |  |  |  |  |  |  position: 4:3 (offset 37)
   |  |  |  |  |  name: Core\View\Template\Compiler\Nodes\Php\IdentifierNode
   |  |  |  |  |  |  name: 'b'
   |  |  |  |  |  |  position: 4:9 (offset 43)
   |  |  |  |  |  nullsafe: true
   |  |  |  |  |  position: 4:3 (offset 37)
   |  |  |  position: 4:1 (offset 35)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 4:1 (offset 35)
   |  4 => Core\View\Template\Compiler\Nodes\Php\ArrayItemNode
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Scalar\InterpolatedStringNode
   |  |  |  parts: array (1)
   |  |  |  |  0 => Core\View\Template\Compiler\Nodes\Php\Expression\PropertyFetchNode
   |  |  |  |  |  object: Core\View\Template\Compiler\Nodes\Php\Expression\BinaryOpNode
   |  |  |  |  |  |  left: Core\View\Template\Compiler\Nodes\Php\Expression\VariableNode
   |  |  |  |  |  |  |  name: 'a'
   |  |  |  |  |  |  |  position: 5:2 (offset 49)
   |  |  |  |  |  |  operator: '??'
   |  |  |  |  |  |  right: Core\View\Template\Compiler\Nodes\Php\Scalar\NullNode
   |  |  |  |  |  |  |  position: 5:2 (offset 49)
   |  |  |  |  |  |  position: 5:2 (offset 49)
   |  |  |  |  |  name: Core\View\Template\Compiler\Nodes\Php\IdentifierNode
   |  |  |  |  |  |  name: 'b'
   |  |  |  |  |  |  position: 5:8 (offset 55)
   |  |  |  |  |  nullsafe: true
   |  |  |  |  |  position: 5:2 (offset 49)
   |  |  |  position: 5:1 (offset 48)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 5:1 (offset 48)
   position: 1:1 (offset 0)
