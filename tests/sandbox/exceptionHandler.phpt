<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);
$latte->setExceptionHandler(function () use (&$args) {
	$args = func_get_args();
});


// sandbox compile-time
$args = null;
$latte->setPolicy((new Core\View\Template\Sandbox\SecurityPolicy)->allowTags(['=']));
$latte->setLoader(new Core\View\Template\Loaders\StringLoader([
	'main' => 'before {sandbox inc.latte} after',
	'inc.latte' => '{if}',
]));

Assert::match(
	'before  after',
	$latte->renderToString('main'),
);
Assert::type(Core\View\Template\Exception\SecurityViolationException::class, $args[0]);
Assert::type(Core\View\Template\Runtime\Template::class, $args[1]);


// sandbox run-time
$args = null;
$latte->setPolicy((new Core\View\Template\Sandbox\SecurityPolicy)->allowTags(['=']));
$latte->setLoader(new Core\View\Template\Loaders\StringLoader([
	'main' => 'before {sandbox inc.latte} after',
	'inc.latte' => '{="trim"()}',
]));

Assert::match(
	'before  after',
	$latte->renderToString('main'),
);
Assert::type(Core\View\Template\Exception\SecurityViolationException::class, $args[0]);
Assert::type(Core\View\Template\Runtime\Template::class, $args[1]);


$latte->setExceptionHandler(fn(Throwable $e) => throw $e);
Assert::exception(
	fn() => $latte->renderToString('main'),
	Core\View\Template\Exception\SecurityViolationException::class,
);
