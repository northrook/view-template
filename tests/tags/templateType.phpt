<?php

/**
 * Test: {templateType}
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);

Assert::exception(
	fn() => $latte->compile('{templateType}'),
	Core\View\Template\Exception\CompileException::class,
	'Missing class name in {templateType} (on line 1 at column 1)',
);

Assert::exception(
	fn() => $latte->compile('{if true}{templateType stdClass}{/if}'),
	Core\View\Template\Exception\CompileException::class,
	'{templateType} is allowed only in template header (on line 1 at column 10)',
);

Assert::noError(fn() => $latte->compile('{templateType stdClass}'));
