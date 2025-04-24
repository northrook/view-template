<?php

/**
 * Test: {block} autoclosing
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);

Assert::match(
	'Block',
	$latte->renderToString('{block}Block'),
);

Assert::exception(
	fn() => $latte->renderToString('{block}{block}Block'),
	Core\View\Template\Exception\CompileException::class,
	'Unexpected end, expecting {/block} (on line 1 at column 20)',
);
