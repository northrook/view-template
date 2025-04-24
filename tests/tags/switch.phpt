<?php

/**
 * Test: {switch}
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);

Assert::exception(
	fn() => $latte->compile('{case}'),
	Core\View\Template\Exception\CompileException::class,
	'Unexpected tag {case} (on line 1 at column 1)',
);

Assert::exception(
	fn() => $latte->compile('{switch}{case}{/switch}'),
	Core\View\Template\Exception\CompileException::class,
	'Missing arguments in {case} (on line 1 at column 9)',
);

Assert::exception(
	fn() => $latte->compile('{switch}{default 123}{/switch}'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '123', expecting end of tag in {default} (on line 1 at column 18)",
);

Assert::exception(
	fn() => $latte->compile('{switch}{default}{default}{/switch}'),
	Core\View\Template\Exception\CompileException::class,
	'Tag {switch} may only contain one {default} clause (on line 1 at column 18)',
);


$template = <<<'EOD'

	{switch 0}
	{case ''}string
	{default}def
	{case 0.0}flot
	{/switch}

	---

	{switch a}
	{case 1, 2, a}a
	{/switch}

	---

	{switch a}
	{default}def
	{/switch}

	---

	{switch a}
	{/switch}

	EOD;

Assert::matchFile(
	__DIR__ . '/expected/switch.php',
	$latte->compile($template),
);

Assert::match(
	<<<'X'

		def

		---

		a

		---

		def

		---

		X
	,
	$latte->renderToString($template),
);
