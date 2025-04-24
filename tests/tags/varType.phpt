<?php

/**
 * Test: {varType}
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);

Assert::exception(
	fn() => $latte->compile('{varType}'),
	Core\View\Template\Exception\CompileException::class,
	'Missing arguments in {varType} (on line 1 at column 1)',
);

Assert::exception(
	fn() => $latte->compile('{varType type}'),
	Core\View\Template\Exception\CompileException::class,
	'Unexpected end, expecting variable (on line 1 at column 14)',
);

Assert::exception(
	fn() => $latte->compile('{varType type var}'),
	Core\View\Template\Exception\CompileException::class,
	'Unexpected end, expecting variable (on line 1 at column 18)',
);

Assert::exception(
	fn() => $latte->compile('{varType $var type}'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected 'type', expecting end of tag in {varType} (on line 1 at column 15)",
);

Assert::noError(fn() => $latte->compile('{varType type $var}'));

Assert::noError(fn() => $latte->compile('{varType ?\Nm\Class $var}'));

Assert::noError(fn() => $latte->compile('{varType int|null $var}'));

Assert::noError(fn() => $latte->compile('{varType array{0: int, 1: int} $var}'));
