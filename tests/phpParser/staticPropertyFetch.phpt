<?php

// Static property fetches

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$test = <<<'XX'
	/* property name variations */
	A::$b,
	A::${'b'},

	/* array access */
	A::$b['c'],
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
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\StaticPropertyFetchNode
   |  |  |  class: Core\View\Template\Compiler\Nodes\Php\NameNode
   |  |  |  |  name: 'A'
   |  |  |  |  kind: 1
   |  |  |  |  position: 2:1 (offset 31)
   |  |  |  name: Core\View\Template\Compiler\Nodes\Php\VarLikeIdentifierNode
   |  |  |  |  name: 'b'
   |  |  |  |  position: 2:4 (offset 34)
   |  |  |  position: 2:1 (offset 31)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 2:1 (offset 31)
   |  1 => Core\View\Template\Compiler\Nodes\Php\ArrayItemNode
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\StaticPropertyFetchNode
   |  |  |  class: Core\View\Template\Compiler\Nodes\Php\NameNode
   |  |  |  |  name: 'A'
   |  |  |  |  kind: 1
   |  |  |  |  position: 3:1 (offset 38)
   |  |  |  name: Core\View\Template\Compiler\Nodes\Php\Scalar\StringNode
   |  |  |  |  value: 'b'
   |  |  |  |  position: 3:6 (offset 43)
   |  |  |  position: 3:1 (offset 38)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 3:1 (offset 38)
   |  2 => Core\View\Template\Compiler\Nodes\Php\ArrayItemNode
   |  |  value: Core\View\Template\Compiler\Nodes\Php\Expression\ArrayAccessNode
   |  |  |  expr: Core\View\Template\Compiler\Nodes\Php\Expression\StaticPropertyFetchNode
   |  |  |  |  class: Core\View\Template\Compiler\Nodes\Php\NameNode
   |  |  |  |  |  name: 'A'
   |  |  |  |  |  kind: 1
   |  |  |  |  |  position: 6:1 (offset 69)
   |  |  |  |  name: Core\View\Template\Compiler\Nodes\Php\VarLikeIdentifierNode
   |  |  |  |  |  name: 'b'
   |  |  |  |  |  position: 6:4 (offset 72)
   |  |  |  |  position: 6:1 (offset 69)
   |  |  |  index: Core\View\Template\Compiler\Nodes\Php\Scalar\StringNode
   |  |  |  |  value: 'c'
   |  |  |  |  position: 6:7 (offset 75)
   |  |  |  position: 6:1 (offset 69)
   |  |  key: null
   |  |  byRef: false
   |  |  unpack: false
   |  |  position: 6:1 (offset 69)
   position: 2:1 (offset 31)
