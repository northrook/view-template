<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class FooNode extends Core\View\Template\Compiler\Nodes\AreaNode
{
	public function print(Core\View\Template\Compiler\PrintContext $context): string
	{
		return '';
	}


	public function &getIterator(): Generator
	{
		false && yield;
	}
}


function parse($s)
{
	$parser = new Core\View\Template\Compiler\TemplateParser;
	$parser->addTags(['foo' => function () {
		$node = new FooNode;
		yield;
		return $node;
	}]);

	$node = $parser->parse($s);
	return exportNode($node);
}


Assert::match(<<<'XX'
	Core\View\Template\Compiler\Nodes\TemplateNode
	   head: Core\View\Template\Compiler\Nodes\FragmentNode
	   |  children: array (0)
	   |  position: null
	   main: Core\View\Template\Compiler\Nodes\FragmentNode
	   |  children: array (0)
	   |  position: null
	   contentType: 'html'
	   position: null
	XX, parse(''));


Assert::match(<<<'XX'
	Core\View\Template\Compiler\Nodes\TemplateNode
	   head: Core\View\Template\Compiler\Nodes\FragmentNode
	   |  children: array (0)
	   |  position: null
	   main: Core\View\Template\Compiler\Nodes\FragmentNode
	   |  children: array (1)
	   |  |  0 => Core\View\Template\Compiler\Nodes\TextNode
	   |  |  |  content: string
	   |  |  |  |  '\n
	   |  |  |  |   text\n'
	   |  |  |  position: 1:1 (offset 0)
	   |  position: 1:1 (offset 0)
	   contentType: 'html'
	   position: null
	XX, parse("\ntext\n"));


Assert::match(<<<'XX'
	Core\View\Template\Compiler\Nodes\TemplateNode
	   head: Core\View\Template\Compiler\Nodes\FragmentNode
	   |  children: array (0)
	   |  position: null
	   main: Core\View\Template\Compiler\Nodes\FragmentNode
	   |  children: array (3)
	   |  |  0 => Core\View\Template\Compiler\Nodes\TextNode
	   |  |  |  content: 'foo '
	   |  |  |  position: 1:1 (offset 0)
	   |  |  1 => Core\View\Template\Compiler\Nodes\TextNode
	   |  |  |  content: '\n'
	   |  |  |  position: 1:18 (offset 17)
	   |  |  2 => Core\View\Template\Compiler\Nodes\TextNode
	   |  |  |  content: ' bar'
	   |  |  |  position: 2:6 (offset 23)
	   |  position: 1:1 (offset 0)
	   contentType: 'html'
	   position: null
	XX, parse("foo {* comment *}\n{* *} bar"));


Assert::match(<<<'XX'
	Core\View\Template\Compiler\Nodes\TemplateNode
	   head: Core\View\Template\Compiler\Nodes\FragmentNode
	   |  children: array (0)
	   |  position: null
	   main: Core\View\Template\Compiler\Nodes\FragmentNode
	   |  children: array (2)
	   |  |  0 => Core\View\Template\Compiler\Nodes\TextNode
	   |  |  |  content: '\n'
	   |  |  |  position: 1:1 (offset 0)
	   |  |  1 => FooNode
	   |  |  |  position: 2:1 (offset 1)
	   |  position: 1:1 (offset 0)
	   contentType: 'html'
	   position: null
	XX, parse("\n{foo\n} ... \n {/foo}"));


Assert::match(<<<'XX'
	Core\View\Template\Compiler\Nodes\TemplateNode
	   head: Core\View\Template\Compiler\Nodes\FragmentNode
	   |  children: array (0)
	   |  position: null
	   main: Core\View\Template\Compiler\Nodes\FragmentNode
	   |  children: array (1)
	   |  |  0 => Core\View\Template\Compiler\Nodes\Html\ElementNode
	   |  |  |  variableName: null
	   |  |  |  attributes: Core\View\Template\Compiler\Nodes\FragmentNode
	   |  |  |  |  children: array (6)
	   |  |  |  |  |  0 => Core\View\Template\Compiler\Nodes\TextNode
	   |  |  |  |  |  |  content: ' '
	   |  |  |  |  |  |  position: 1:4 (offset 3)
	   |  |  |  |  |  1 => Core\View\Template\Compiler\Nodes\Html\AttributeNode
	   |  |  |  |  |  |  name: Core\View\Template\Compiler\Nodes\TextNode
	   |  |  |  |  |  |  |  content: 'attr1'
	   |  |  |  |  |  |  |  position: 1:5 (offset 4)
	   |  |  |  |  |  |  value: null
	   |  |  |  |  |  |  quote: null
	   |  |  |  |  |  |  position: 1:5 (offset 4)
	   |  |  |  |  |  2 => Core\View\Template\Compiler\Nodes\TextNode
	   |  |  |  |  |  |  content: ' \n'
	   |  |  |  |  |  |  position: 1:10 (offset 9)
	   |  |  |  |  |  3 => Core\View\Template\Compiler\Nodes\Html\AttributeNode
	   |  |  |  |  |  |  name: Core\View\Template\Compiler\Nodes\TextNode
	   |  |  |  |  |  |  |  content: 'attr2'
	   |  |  |  |  |  |  |  position: 2:1 (offset 11)
	   |  |  |  |  |  |  value: Core\View\Template\Compiler\Nodes\TextNode
	   |  |  |  |  |  |  |  content: 'val'
	   |  |  |  |  |  |  |  position: 2:7 (offset 17)
	   |  |  |  |  |  |  quote: null
	   |  |  |  |  |  |  position: 2:1 (offset 11)
	   |  |  |  |  |  4 => Core\View\Template\Compiler\Nodes\TextNode
	   |  |  |  |  |  |  content: string
	   |  |  |  |  |  |  |  '\n
	   |  |  |  |  |  |  |    '
	   |  |  |  |  |  |  position: 2:10 (offset 20)
	   |  |  |  |  |  5 => Core\View\Template\Compiler\Nodes\Html\AttributeNode
	   |  |  |  |  |  |  name: Core\View\Template\Compiler\Nodes\TextNode
	   |  |  |  |  |  |  |  content: 'attr3'
	   |  |  |  |  |  |  |  position: 3:2 (offset 22)
	   |  |  |  |  |  |  value: Core\View\Template\Compiler\Nodes\FragmentNode
	   |  |  |  |  |  |  |  children: array (1)
	   |  |  |  |  |  |  |  |  0 => Core\View\Template\Compiler\Nodes\TextNode
	   |  |  |  |  |  |  |  |  |  content: 'val'
	   |  |  |  |  |  |  |  |  |  position: 4:2 (offset 30)
	   |  |  |  |  |  |  |  position: 4:2 (offset 30)
	   |  |  |  |  |  |  quote: '''
	   |  |  |  |  |  |  position: 3:2 (offset 22)
	   |  |  |  |  position: 1:4 (offset 3)
	   |  |  |  selfClosing: false
	   |  |  |  content: null
	   |  |  |  nAttributes: array (0)
	   |  |  |  tagNode: Core\View\Template\Compiler\Nodes\AuxiliaryNode
	   |  |  |  |  print: Closure($context)
	   |  |  |  |  nodes: array (0)
	   |  |  |  |  position: null
	   |  |  |  captureTagName: false
	   |  |  |  breakable: false
	   |  |  |  endTagVar: unset
	   |  |  |  name: 'br'
	   |  |  |  position: 1:1 (offset 0)
	   |  |  |  parent: null
	   |  |  |  data: stdClass
	   |  |  |  |  tag: null
	   |  |  |  |  textualName: 'br'
	   |  |  |  contentType: 'html'
	   |  position: 1:1 (offset 0)
	   contentType: 'html'
	   position: null

	XX, parse("<br attr1 \nattr2=val\n attr3=\n'val'>"));


Assert::match(<<<'XX'
	Core\View\Template\Compiler\Nodes\TemplateNode
	   head: Core\View\Template\Compiler\Nodes\FragmentNode
	   |  children: array (0)
	   |  position: null
	   main: Core\View\Template\Compiler\Nodes\FragmentNode
	   |  children: array (1)
	   |  |  0 => Core\View\Template\Compiler\Nodes\Html\ElementNode
	   |  |  |  variableName: null
	   |  |  |  attributes: Core\View\Template\Compiler\Nodes\FragmentNode
	   |  |  |  |  children: array (6)
	   |  |  |  |  |  0 => Core\View\Template\Compiler\Nodes\TextNode
	   |  |  |  |  |  |  content: ' '
	   |  |  |  |  |  |  position: 1:4 (offset 3)
	   |  |  |  |  |  1 => FooNode
	   |  |  |  |  |  |  position: 1:5 (offset 4)
	   |  |  |  |  |  2 => Core\View\Template\Compiler\Nodes\TextNode
	   |  |  |  |  |  |  content: ' '
	   |  |  |  |  |  |  position: 1:27 (offset 26)
	   |  |  |  |  |  3 => Core\View\Template\Compiler\Nodes\Html\AttributeNode
	   |  |  |  |  |  |  name: Core\View\Template\Compiler\Nodes\TextNode
	   |  |  |  |  |  |  |  content: 'attr5'
	   |  |  |  |  |  |  |  position: 1:28 (offset 27)
	   |  |  |  |  |  |  value: FooNode
	   |  |  |  |  |  |  |  position: 1:34 (offset 33)
	   |  |  |  |  |  |  quote: null
	   |  |  |  |  |  |  position: 1:28 (offset 27)
	   |  |  |  |  |  4 => Core\View\Template\Compiler\Nodes\TextNode
	   |  |  |  |  |  |  content: ' '
	   |  |  |  |  |  |  position: 1:46 (offset 45)
	   |  |  |  |  |  5 => Core\View\Template\Compiler\Nodes\Html\AttributeNode
	   |  |  |  |  |  |  name: Core\View\Template\Compiler\Nodes\TextNode
	   |  |  |  |  |  |  |  content: 'attr6'
	   |  |  |  |  |  |  |  position: 1:47 (offset 46)
	   |  |  |  |  |  |  value: Core\View\Template\Compiler\Nodes\FragmentNode
	   |  |  |  |  |  |  |  children: array (3)
	   |  |  |  |  |  |  |  |  0 => Core\View\Template\Compiler\Nodes\TextNode
	   |  |  |  |  |  |  |  |  |  content: 'c'
	   |  |  |  |  |  |  |  |  |  position: 1:53 (offset 52)
	   |  |  |  |  |  |  |  |  1 => FooNode
	   |  |  |  |  |  |  |  |  |  position: 1:54 (offset 53)
	   |  |  |  |  |  |  |  |  2 => Core\View\Template\Compiler\Nodes\TextNode
	   |  |  |  |  |  |  |  |  |  content: 'd'
	   |  |  |  |  |  |  |  |  |  position: 1:60 (offset 59)
	   |  |  |  |  |  |  |  position: 1:53 (offset 52)
	   |  |  |  |  |  |  quote: null
	   |  |  |  |  |  |  position: 1:47 (offset 46)
	   |  |  |  |  position: 1:4 (offset 3)
	   |  |  |  selfClosing: false
	   |  |  |  content: null
	   |  |  |  nAttributes: array (0)
	   |  |  |  tagNode: Core\View\Template\Compiler\Nodes\AuxiliaryNode
	   |  |  |  |  print: Closure($context)
	   |  |  |  |  nodes: array (0)
	   |  |  |  |  position: null
	   |  |  |  captureTagName: false
	   |  |  |  breakable: false
	   |  |  |  endTagVar: unset
	   |  |  |  name: 'br'
	   |  |  |  position: 1:1 (offset 0)
	   |  |  |  parent: null
	   |  |  |  data: stdClass
	   |  |  |  |  tag: null
	   |  |  |  |  textualName: 'br'
	   |  |  |  contentType: 'html'
	   |  position: 1:1 (offset 0)
	   contentType: 'html'
	   position: null

	XX, parse("<br {foo}attr4='val'{/foo} attr5={foo}b{/foo} attr6=c{foo/}d>"));


Assert::match(<<<'XX'
	Core\View\Template\Compiler\Nodes\TemplateNode
	   head: Core\View\Template\Compiler\Nodes\FragmentNode
	   |  children: array (0)
	   |  position: null
	   main: Core\View\Template\Compiler\Nodes\FragmentNode
	   |  children: array (1)
	   |  |  0 => FooNode
	   |  |  |  position: 1:5 (offset 4)
	   |  position: 1:5 (offset 4)
	   contentType: 'html'
	   position: null
	XX, parse('<br n:foo>'));


Assert::match(<<<'XX'
	Core\View\Template\Compiler\Nodes\TemplateNode
	   head: Core\View\Template\Compiler\Nodes\FragmentNode
	   |  children: array (0)
	   |  position: null
	   main: Core\View\Template\Compiler\Nodes\FragmentNode
	   |  children: array (1)
	   |  |  0 => Core\View\Template\Compiler\Nodes\Html\ElementNode
	   |  |  |  variableName: null
	   |  |  |  attributes: Core\View\Template\Compiler\Nodes\FragmentNode
	   |  |  |  |  children: array (0)
	   |  |  |  |  position: null
	   |  |  |  selfClosing: false
	   |  |  |  content: Core\View\Template\Compiler\Nodes\FragmentNode
	   |  |  |  |  children: array (2)
	   |  |  |  |  |  0 => Core\View\Template\Compiler\Nodes\TextNode
	   |  |  |  |  |  |  content: '\n'
	   |  |  |  |  |  |  position: 1:4 (offset 3)
	   |  |  |  |  |  1 => Core\View\Template\Compiler\Nodes\TextNode
	   |  |  |  |  |  |  content: '...\n'
	   |  |  |  |  |  |  position: 2:1 (offset 4)
	   |  |  |  |  position: 1:4 (offset 3)
	   |  |  |  nAttributes: array (0)
	   |  |  |  tagNode: Core\View\Template\Compiler\Nodes\AuxiliaryNode
	   |  |  |  |  print: Closure($context)
	   |  |  |  |  nodes: array (0)
	   |  |  |  |  position: null
	   |  |  |  captureTagName: false
	   |  |  |  breakable: false
	   |  |  |  endTagVar: unset
	   |  |  |  name: 'p'
	   |  |  |  position: 1:1 (offset 0)
	   |  |  |  parent: null
	   |  |  |  data: stdClass
	   |  |  |  |  tag: null
	   |  |  |  |  textualName: 'p'
	   |  |  |  contentType: 'html'
	   |  position: 1:1 (offset 0)
	   contentType: 'html'
	   position: null

	XX, parse("<p>\n...\n</p>"));
