<?php

/**
 * Test: {try} ... {else} {rollback} ... {/try}
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);

Assert::exception(
	fn() => $latte->compile('{rollback}'),
	Core\View\Template\Exception\CompileException::class,
	'Tag {rollback} must be inside {try} ... {/try} (on line 1 at column 1)',
);
