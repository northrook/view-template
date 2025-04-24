<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);
$latte->setPolicy((new Core\View\Template\Sandbox\SecurityPolicy)->allowTags(['=']));

Assert::noError(fn() => $latte->compile('{var $abc}'));

Assert::noError(fn() => $latte->renderToString('{="trim"("hello")}'));


$latte->setSandboxMode();

Assert::exception(
	fn() => $latte->compile('{var $abc}'),
	Core\View\Template\Exception\SecurityViolationException::class,
	'Tag {var} is not allowed (on line 1 at column 1)',
);

Assert::exception(
	fn() => $latte->renderToString('{="trim"("hello")}'),
	Core\View\Template\Exception\SecurityViolationException::class,
	'Calling trim() is not allowed.',
);

$latte->setPolicy(null);
Assert::exception(
	fn() => $latte->compile(''),
	LogicException::class,
	'In sandboxed mode you need to set a security policy.',
);
