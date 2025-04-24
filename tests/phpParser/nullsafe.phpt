<?php

// Nullsafe operator

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$test = <<<'XX'
	$a?->b,
	$a?->b($c),
	new $a?->b,
	"{$a?->b}",
	"$a?->b",
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
   |  |  |  object: Core\View\Template\Compiler\Nodes\Php\Expression\VariableNode
   |  |  |  |  name: 'a'
   |  |  |  |  position: 1:1 (offset 0)
   |  |  |  name: Core\View\Template\Compiler\Nodes\Php\IdentifierNode
   |  |  |  |  name: 'b'
   |  |  |  |  position: 1:6 (offset 5)
   |  |  |  nullsafe: true
   |  |  |  position: 1:1 (offset 0)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 1:1 (offset 0)
   |  1 => Core\View\Template\Compiler\Nodes\Php\ArrayItemNode
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\MethodCallNode
   |  |  |  object: Core\View\Template\Compiler\Nodes\Php\Expression\VariableNode
   |  |  |  |  name: 'a'
   |  |  |  |  position: 2:1 (offset 8)
   |  |  |  name: Core\View\Template\Compiler\Nodes\Php\IdentifierNode
   |  |  |  |  name: 'b'
   |  |  |  |  position: 2:6 (offset 13)
   |  |  |  args: array (1)
   |  |  |  |  0 => Core\View\Template\Compiler\Nodes\Php\ArgumentNode
   |  |  |  |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\VariableNode
   |  |  |  |  |  |  name: 'c'
   |  |  |  |  |  |  position: 2:8 (offset 15)
   |  |  |  |  |  byRef: false
   |  |  |  |  |  unpack: false
   |  |  |  |  |  name: null
   |  |  |  |  |  position: 2:8 (offset 15)
   |  |  |  nullsafe: true
   |  |  |  position: 2:1 (offset 8)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 2:1 (offset 8)
   |  2 => Core\View\Template\Compiler\Nodes\Php\ArrayItemNode
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\NewNode
   |  |  |  class: Core\View\Template\Compiler\Nodes\Php\Expression\PropertyFetchNode
   |  |  |  |  object: Core\View\Template\Compiler\Nodes\Php\Expression\VariableNode
   |  |  |  |  |  name: 'a'
   |  |  |  |  |  position: 3:5 (offset 24)
   |  |  |  |  name: Core\View\Template\Compiler\Nodes\Php\IdentifierNode
   |  |  |  |  |  name: 'b'
   |  |  |  |  |  position: 3:10 (offset 29)
   |  |  |  |  nullsafe: true
   |  |  |  |  position: 3:5 (offset 24)
   |  |  |  args: array (0)
   |  |  |  position: 3:1 (offset 20)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 3:1 (offset 20)
   |  3 => Core\View\Template\Compiler\Nodes\Php\ArrayItemNode
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Scalar\InterpolatedStringNode
   |  |  |  parts: array (1)
   |  |  |  |  0 => Core\View\Template\Compiler\Nodes\Php\Expression\PropertyFetchNode
   |  |  |  |  |  object: Core\View\Template\Compiler\Nodes\Php\Expression\VariableNode
   |  |  |  |  |  |  name: 'a'
   |  |  |  |  |  |  position: 4:3 (offset 34)
   |  |  |  |  |  name: Core\View\Template\Compiler\Nodes\Php\IdentifierNode
   |  |  |  |  |  |  name: 'b'
   |  |  |  |  |  |  position: 4:8 (offset 39)
   |  |  |  |  |  nullsafe: true
   |  |  |  |  |  position: 4:3 (offset 34)
   |  |  |  position: 4:1 (offset 32)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 4:1 (offset 32)
   |  4 => Core\View\Template\Compiler\Nodes\Php\ArrayItemNode
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Scalar\InterpolatedStringNode
   |  |  |  parts: array (1)
   |  |  |  |  0 => Core\View\Template\Compiler\Nodes\Php\Expression\PropertyFetchNode
   |  |  |  |  |  object: Core\View\Template\Compiler\Nodes\Php\Expression\VariableNode
   |  |  |  |  |  |  name: 'a'
   |  |  |  |  |  |  position: 5:2 (offset 45)
   |  |  |  |  |  name: Core\View\Template\Compiler\Nodes\Php\IdentifierNode
   |  |  |  |  |  |  name: 'b'
   |  |  |  |  |  |  position: 5:7 (offset 50)
   |  |  |  |  |  nullsafe: true
   |  |  |  |  |  position: 5:2 (offset 45)
   |  |  |  position: 5:1 (offset 44)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 5:1 (offset 44)
   position: 1:1 (offset 0)
