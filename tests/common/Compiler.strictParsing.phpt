<?php

/**
 * Test: Compile errors in strict parsing
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setStrictParsing();
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);

Assert::exception(
	fn() => $latte->compile('{$this}'),
	Core\View\Template\Exception\CompileException::class,
	'Forbidden variable $this (on line 1 at column 2)',
);

Assert::exception(
	fn() => $latte->compile('<a>'),
	Core\View\Template\Exception\CompileException::class,
	'Unexpected end, expecting </a> for element started on line 1 at column 1 (on line 1 at column 4)',
);

Assert::exception(
	fn() => $latte->compile('</a>'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '</' (on line 1 at column 1)",
);

Assert::exception(
	fn() => $latte->compile('<a></b>'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '</b>', expecting </a> for element started on line 1 at column 1 (on line 1 at column 4)",
);

Assert::exception(
	fn() => $latte->compile('<a{if 1}{/if}>'),
	Core\View\Template\Exception\CompileException::class,
	'Only expression can be used as a HTML tag name (on line 1 at column 3)',
);

Assert::exception(
	fn() => $latte->compile('{contentType xml}<a></A>'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '</A>', expecting </a> for element started on line 1 at column 18 (on line 1 at column 21)",
);
