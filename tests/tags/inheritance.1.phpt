<?php

/**
 * Test: {extends ...} test I.
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader([
	'parent' => file_get_contents(__DIR__ . '/templates/parent.latte'),

	'main' => <<<'XX'

		{extends "parent"}

		{import "inc"}
		{include file "inc" with blocks}

		{block title}Homepage | {include parent}{include parent}{/block}

		{block content}
			<ul>
			{foreach $people as $person}
				<li>{$person}</li>
			{/foreach}
			</ul>
			Parent: {gettype($this->getReferringTemplate())}
		{/block}

		XX,

	'inc' => '{define test /}',
]));

Assert::matchFile(
	__DIR__ . '/expected/inheritance.1.php',
	$latte->compile('main'),
);
Assert::matchFile(
	__DIR__ . '/expected/inheritance.1.html',
	$latte->renderToString('main', ['people' => ['John', 'Mary', 'Paul']]),
);
Assert::matchFile(
	__DIR__ . '/expected/inheritance.1.parent.php',
	$latte->compile('parent'),
);
