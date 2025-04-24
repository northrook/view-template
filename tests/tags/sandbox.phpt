<?php

/**
 * Test: {include file}
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setPolicy((new Core\View\Template\Sandbox\SecurityPolicy)->allowTags(['=']));
$latte->setLoader(new Core\View\Template\Loaders\StringLoader([
	'main1' => 'before {sandbox inc1.latte} after',
	'main2' => 'before {sandbox inc1.latte, var => 1} after',
	'main3' => 'before {sandbox inc2.latte} after',
	'main4' => 'before {sandbox inc3.latte, obj => new stdClass} after',

	'inc1.latte' => '<b>included {$var}</b>',
	'inc2.latte' => '<b>{var $var}</b>',
	'inc3.latte' => '<b>{$obj->item}</b>',
]));


Assert::error(
	fn() => $latte->renderToString('main1'),
	E_WARNING,
	'Undefined variable%a%var',
);

Assert::error(
	fn() => $latte->renderToString('main1', ['var' => 123]),
	E_WARNING,
	'Undefined variable%a%var',
);

Assert::match(
	'before <b>included 1</b> after',
	$latte->renderToString('main2'),
);

Assert::exception(
	fn() => $latte->renderToString('main3'),
	Core\View\Template\Exception\SecurityViolationException::class,
	'Tag {var} is not allowed (on line 1 at column 4)',
);

Assert::exception(
	fn() => $latte->renderToString('main4'),
	Core\View\Template\Exception\SecurityViolationException::class,
	"Access to 'item' property on a stdClass object is not allowed.",
);
