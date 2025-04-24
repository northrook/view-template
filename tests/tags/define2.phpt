<?php

/**
 * Test: Core\View\Template\Engine and blocks.
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);

Assert::match('', $latte->renderToString('{define foobar}Hello{/define}'));

Assert::match('', $latte->renderToString('{define foo-bar}Hello{/define}'));

Assert::match('', $latte->renderToString('{define $foo}Hello{/define}', ['foo' => 'bar']));

// no empty line
Assert::match(
	<<<'XX'
		one

		two
		XX,
	$latte->renderToString(
		<<<'XX'
			one
			{define foo}Hello{/define}
			two
			XX,
	),
);

Assert::exception(
	fn() => $latte->renderToString('{define _foobar}Hello{/define}'),
	Core\View\Template\Exception\CompileException::class,
	"Define name must start with letter a-z, '_foobar' given (on line 1 at column 1)",
);

Assert::exception(
	fn() => $latte->renderToString('{define 123}Hello{/define}'),
	Core\View\Template\Exception\CompileException::class,
	"Define name must start with letter a-z, '123' given (on line 1 at column 1)",
);
