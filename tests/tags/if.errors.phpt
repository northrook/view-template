<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);

Assert::exception(
	fn() => $latte->compile('{if 1}{else if a}{/if}'),
	Core\View\Template\Exception\CompileException::class,
	'Arguments are not allowed in {else}, did you mean {elseif}? (on line 1 at column 7)',
);

Assert::exception(
	fn() => $latte->compile('{if 1}{else a}{/if}'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected 'a', expecting end of tag in {else} (on line 1 at column 13)",
);

Assert::exception(
	fn() => $latte->compile('{else}'),
	Core\View\Template\Exception\CompileException::class,
	'Unexpected tag {else} (on line 1 at column 1)',
);

Assert::exception(
	fn() => $latte->compile('{if 1}{else}{else}{/if}'),
	Core\View\Template\Exception\CompileException::class,
	'Unexpected tag {else} (on line 1 at column 13)',
);

Assert::exception(
	fn() => $latte->compile('{elseif a}'),
	Core\View\Template\Exception\CompileException::class,
	'Unexpected tag {elseif} (on line 1 at column 1)',
);

Assert::exception(
	fn() => $latte->compile('{if 1}{else}{elseif a}{/if}'),
	Core\View\Template\Exception\CompileException::class,
	'Unexpected tag {elseif} (on line 1 at column 13)',
);

Assert::exception(
	fn() => $latte->compile('{if}{elseif a}{/if 1}'),
	Core\View\Template\Exception\CompileException::class,
	'Unexpected tag {elseif} (on line 1 at column 5)',
);
