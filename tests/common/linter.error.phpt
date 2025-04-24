<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

if (str_contains(PHP_BINARY, 'phpdbg')) {
	Tester\Environment::skip('Is not compatible with phpdbg');
}


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);
$latte->enablePhpLinter(PHP_BINARY);

Assert::exception(
	fn() => $latte->compile('{= [&$x] = []}'),
	Core\View\Template\Exception\CompileException::class,
	'Error in generated code: Cannot assign %a% (on line %d%)',
);
