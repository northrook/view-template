<?php

/**
 * Test: Core\View\Template\Engine and blocks.
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);

Assert::match(
	'<br class="123">',
	$latte->renderToString('{block test}<br n:class="$var">{/block}', ['var' => 123]),
);

Assert::exception(
	fn() => $latte->renderToString('{block _foobar}Hello{/block}'),
	Core\View\Template\Exception\CompileException::class,
	"Block name must start with letter a-z, '_foobar' given (on line 1 at column 1)",
);

Assert::exception(
	fn() => $latte->renderToString('{block 123}Hello{/block}'),
	Core\View\Template\Exception\CompileException::class,
	"Block name must start with letter a-z, '123' given (on line 1 at column 1)",
);
