<?php

/**
 * Test: {extends ...} test VI.
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader([
	'parent' => '{$foo}',

	'main' => '{layout "parent"}
{* This should be erased *}
{var $foo = 1}
This should be erased
',
]));

Assert::matchFile(
	__DIR__ . '/expected/extends.1.php',
	$latte->compile('main'),
);
Assert::same(
	'1',
	$latte->renderToString('main'),
);
