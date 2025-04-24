<?php

/**
 * Test: {do}
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);

Assert::match(
	'%A%$a = \'test\' ? [] : null%A%',
	$latte->compile('{do $a = test ? ([])}'),
);


// reserved keywords
Assert::exception(
	fn() => $latte->compile('{do break}'),
	Core\View\Template\Exception\CompileException::class,
	"Keyword 'break' cannot be used in Latte (on line 1 at column 5)",
);

Assert::exception(
	fn() => $latte->compile('{do exit}'),
	Core\View\Template\Exception\CompileException::class,
	"Keyword 'exit' cannot be used in Latte (on line 1 at column 5)",
);

Assert::exception(
	fn() => $latte->compile('{do return}'),
	Core\View\Template\Exception\CompileException::class,
	"Keyword 'return' cannot be used in Latte (on line 1 at column 5)",
);

Assert::exception(
	fn() => $latte->compile('{php function test() }'),
	Core\View\Template\Exception\CompileException::class,
	"Keyword 'function' cannot be used in Latte (on line 1 at column 6)",
);
