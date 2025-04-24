<?php

// UVS static access

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$test = <<<'XX'
	A::$b,
	$A::$b,
	'A'::$b,
	('A' . '')::$b,
	'A'[0]::$b,
	A::$A::$b,
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
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\StaticPropertyFetchNode
   |  |  |  class: Core\View\Template\Compiler\Nodes\Php\NameNode
   |  |  |  |  name: 'A'
   |  |  |  |  kind: 1
   |  |  |  |  position: 1:1 (offset 0)
   |  |  |  name: Core\View\Template\Compiler\Nodes\Php\VarLikeIdentifierNode
   |  |  |  |  name: 'b'
   |  |  |  |  position: 1:4 (offset 3)
   |  |  |  position: 1:1 (offset 0)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 1:1 (offset 0)
   |  1 => Core\View\Template\Compiler\Nodes\Php\ArrayItemNode
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\StaticPropertyFetchNode
   |  |  |  class: Core\View\Template\Compiler\Nodes\Php\Expression\VariableNode
   |  |  |  |  name: 'A'
   |  |  |  |  position: 2:1 (offset 7)
   |  |  |  name: Core\View\Template\Compiler\Nodes\Php\VarLikeIdentifierNode
   |  |  |  |  name: 'b'
   |  |  |  |  position: 2:5 (offset 11)
   |  |  |  position: 2:1 (offset 7)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 2:1 (offset 7)
   |  2 => Core\View\Template\Compiler\Nodes\Php\ArrayItemNode
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\StaticPropertyFetchNode
   |  |  |  class: Core\View\Template\Compiler\Nodes\Php\Scalar\StringNode
   |  |  |  |  value: 'A'
   |  |  |  |  position: 3:1 (offset 15)
   |  |  |  name: Core\View\Template\Compiler\Nodes\Php\VarLikeIdentifierNode
   |  |  |  |  name: 'b'
   |  |  |  |  position: 3:6 (offset 20)
   |  |  |  position: 3:1 (offset 15)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 3:1 (offset 15)
   |  3 => Core\View\Template\Compiler\Nodes\Php\ArrayItemNode
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\StaticPropertyFetchNode
   |  |  |  class: Core\View\Template\Compiler\Nodes\Php\Expression\BinaryOpNode
   |  |  |  |  left: Core\View\Template\Compiler\Nodes\Php\Scalar\StringNode
   |  |  |  |  |  value: 'A'
   |  |  |  |  |  position: 4:2 (offset 25)
   |  |  |  |  operator: '.'
   |  |  |  |  right: Core\View\Template\Compiler\Nodes\Php\Scalar\StringNode
   |  |  |  |  |  value: ''
   |  |  |  |  |  position: 4:8 (offset 31)
   |  |  |  |  position: 4:2 (offset 25)
   |  |  |  name: Core\View\Template\Compiler\Nodes\Php\VarLikeIdentifierNode
   |  |  |  |  name: 'b'
   |  |  |  |  position: 4:13 (offset 36)
   |  |  |  position: 4:1 (offset 24)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 4:1 (offset 24)
   |  4 => Core\View\Template\Compiler\Nodes\Php\ArrayItemNode
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\StaticPropertyFetchNode
   |  |  |  class: Core\View\Template\Compiler\Nodes\Php\Expression\ArrayAccessNode
   |  |  |  |  expr: Core\View\Template\Compiler\Nodes\Php\Scalar\StringNode
   |  |  |  |  |  value: 'A'
   |  |  |  |  |  position: 5:1 (offset 40)
   |  |  |  |  index: Core\View\Template\Compiler\Nodes\Php\Scalar\IntegerNode
   |  |  |  |  |  value: 0
   |  |  |  |  |  kind: 10
   |  |  |  |  |  position: 5:5 (offset 44)
   |  |  |  |  position: 5:1 (offset 40)
   |  |  |  name: Core\View\Template\Compiler\Nodes\Php\VarLikeIdentifierNode
   |  |  |  |  name: 'b'
   |  |  |  |  position: 5:9 (offset 48)
   |  |  |  position: 5:1 (offset 40)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 5:1 (offset 40)
   |  5 => Core\View\Template\Compiler\Nodes\Php\ArrayItemNode
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\StaticPropertyFetchNode
   |  |  |  class: Core\View\Template\Compiler\Nodes\Php\Expression\StaticPropertyFetchNode
   |  |  |  |  class: Core\View\Template\Compiler\Nodes\Php\NameNode
   |  |  |  |  |  name: 'A'
   |  |  |  |  |  kind: 1
   |  |  |  |  |  position: 6:1 (offset 52)
   |  |  |  |  name: Core\View\Template\Compiler\Nodes\Php\VarLikeIdentifierNode
   |  |  |  |  |  name: 'A'
   |  |  |  |  |  position: 6:4 (offset 55)
   |  |  |  |  position: 6:1 (offset 52)
   |  |  |  name: Core\View\Template\Compiler\Nodes\Php\VarLikeIdentifierNode
   |  |  |  |  name: 'b'
   |  |  |  |  position: 6:8 (offset 59)
   |  |  |  position: 6:1 (offset 52)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 6:1 (offset 52)
   position: 1:1 (offset 0)
