<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class Test
{
	public function __call($nm, $arg)
	{
	}
}


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);
$latte->setPolicy((new Core\View\Template\Sandbox\SecurityPolicy)->allowTags(['=', 'do', 'var', 'parameters']));
$latte->setSandboxMode();

Assert::exception(
	fn() => $latte->compile('{default $abc}'),
	Core\View\Template\Exception\SecurityViolationException::class,
	'Tag {default} is not allowed (on line 1 at column 1)',
);

Assert::exception(
	fn() => $latte->compile('<span n:class=""></span>'),
	Core\View\Template\Exception\SecurityViolationException::class,
	'Attribute n:class is not allowed (on line 1 at column 7)',
);

Assert::exception(
	fn() => $latte->compile('{$abc|upper}'),
	Core\View\Template\Exception\SecurityViolationException::class,
	'Filter |upper is not allowed (on line 1 at column 6)',
);

Assert::exception(
	fn() => $latte->compile('{$abc|noescape}'),
	Core\View\Template\Exception\SecurityViolationException::class,
	'Filter |noescape is not allowed (on line 1 at column 6)',
);

Assert::exception(
	fn() => $latte->compile('<a href="{$abc|nocheck}">'),
	Core\View\Template\Exception\SecurityViolationException::class,
	'Filter |nocheck is not allowed (on line 1 at column 15)',
);

Assert::exception(
	fn() => $latte->compile('<a href="{$abc|datastream}">'),
	Core\View\Template\Exception\SecurityViolationException::class,
	'Filter |datastream is not allowed (on line 1 at column 15)',
);

Assert::exception(
	fn() => $latte->compile('{trim(123)}'),
	Core\View\Template\Exception\SecurityViolationException::class,
	'Function trim() is not allowed (on line 1 at column 2)',
);

Assert::exception(
	fn() => $latte->renderToString('{="trim"(123)}'),
	Core\View\Template\Exception\SecurityViolationException::class,
	'Calling trim() is not allowed.',
);

Assert::exception(
	fn() => $latte->renderToString('{=$obj->error(123)}', ['obj' => new Test]),
	Core\View\Template\Exception\SecurityViolationException::class,
	'Calling Test::error() is not allowed.',
);

Assert::exception(
	fn() => $latte->renderToString('{=$obj?->error(123)}', ['obj' => new Test]),
	Core\View\Template\Exception\SecurityViolationException::class,
	'Calling Test::error() is not allowed.',
);

Assert::exception(
	fn() => $latte->renderToString('{=$obj??->error(123)}', ['obj' => new Test]),
	Core\View\Template\Exception\SecurityViolationException::class,
	'Calling Test::error() is not allowed.',
);

Assert::exception(
	fn() => $latte->renderToString('{=[$obj, "error"](123)}', ['obj' => new Test]),
	Core\View\Template\Exception\SecurityViolationException::class,
	'Calling Test::error() is not allowed.',
);

Assert::exception(
	fn() => $latte->renderToString('{=$obj->error}', ['obj' => new Test]),
	Core\View\Template\Exception\SecurityViolationException::class,
	"Access to 'error' property on a Test object is not allowed.",
);

Assert::exception(
	fn() => $latte->renderToString('{=$obj?->error}', ['obj' => new Test]),
	Core\View\Template\Exception\SecurityViolationException::class,
	"Access to 'error' property on a Test object is not allowed.",
);

Assert::exception(
	fn() => $latte->renderToString('{=$obj??->error}', ['obj' => new Test]),
	Core\View\Template\Exception\SecurityViolationException::class,
	"Access to 'error' property on a Test object is not allowed.",
);

Assert::exception(
	fn() => $latte->renderToString('{=$obj->$prop}', ['obj' => new Test, 'prop' => 'error']),
	Core\View\Template\Exception\SecurityViolationException::class,
	"Access to 'error' property on a Test object is not allowed.",
);

Assert::exception(
	fn() => $latte->renderToString('{=$obj::$prop}', ['obj' => new Test]),
	Core\View\Template\Exception\SecurityViolationException::class,
	"Access to 'prop' property on a Test object is not allowed.",
);

Assert::exception(
	fn() => $latte->renderToString('{=$obj->method()}', ['obj' => 1]),
	Core\View\Template\Exception\SecurityViolationException::class,
	'Invalid callable.',
);

Assert::exception(
	fn() => $latte->renderToString('{=$obj->$prop}', ['obj' => new Test, 'prop' => 1]),
	Core\View\Template\Exception\SecurityViolationException::class,
	"Access to '1' property on a Test object is not allowed.",
);

Assert::error(
	fn() => $latte->renderToString('{=$obj->$prop}', ['obj' => 1, 'prop' => 1]),
	E_WARNING,
	'%a% property %a%',
);

Assert::exception(
	fn() => $latte->compile('{$this->filters}'),
	Core\View\Template\Exception\SecurityViolationException::class,
	'Forbidden variable $this (on line 1 at column 2)',
);

Assert::exception(
	fn() => $latte->compile('{${"this"}}'),
	Core\View\Template\Exception\SecurityViolationException::class,
	'Forbidden variable variables (on line 1 at column 2)',
);

Assert::exception(
	fn() => $latte->compile('{= echo 123}'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '123' (on line 1 at column 9)",
);

Assert::exception(
	fn() => $latte->compile('{= return 123}'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected 'return' (on line 1 at column 4)",
);

Assert::exception(
	fn() => $latte->compile('{= new stdClass}'),
	Core\View\Template\Exception\SecurityViolationException::class,
	"Forbidden keyword 'new' (on line 1 at column 4)",
);

Assert::exception(
	fn() => $latte->compile('{var $a = new stdClass}'),
	Core\View\Template\Exception\SecurityViolationException::class,
	"Forbidden keyword 'new' (on line 1 at column 11)",
);

Assert::exception(
	fn() => $latte->compile('{parameters $a = new stdClass}'),
	Core\View\Template\Exception\SecurityViolationException::class,
	"Forbidden keyword 'new' (on line 1 at column 18)",
);

Assert::noError(fn() => $latte->compile('{=\'${var}\'}'));
