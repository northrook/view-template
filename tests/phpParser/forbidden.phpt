<?php

// Forbidden syntax

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


// operator | is used for filters
Assert::exception(
	fn() => parseCode('$a | $b'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '|' (on line 1 at column 4)",
);

// function declaration
Assert::exception(
	fn() => parseCode('function getArr() {	return [4, 5]; }'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected 'getArr' (on line 1 at column 10)",
);

// missing return
Assert::exception(
	fn() => parseCode('function($a) { $a; }'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '\$a' (on line 1 at column 16)",
);

// static closure
Assert::exception(
	fn() => parseCode('static function() { return 0; }'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected 'function' (on line 1 at column 8)",
);

// variable variable
Assert::exception(
	fn() => parseCode('$$a'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '\$a' (on line 1 at column 2)",
);

// forbidden keyword
Assert::exception(
	fn() => parseCode('include "A.php"'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '\"A.php\"' (on line 1 at column 9)",
);

Assert::exception(
	fn() => parseCode('include ("A.php")'),
	Core\View\Template\Exception\CompileException::class,
	"Keyword 'include' cannot be used in Latte (on line 1 at column 1)",
);

Assert::exception(
	fn() => parseCode('return 10'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '10' (on line 1 at column 8)",
);

Assert::exception(
	fn() => parseCode('unset($x)'),
	Core\View\Template\Exception\CompileException::class,
	"Keyword 'unset' cannot be used in Latte (on line 1 at column 1)",
);

Assert::exception(
	fn() => parseCode('unset(...)'),
	Core\View\Template\Exception\CompileException::class,
	"Keyword 'unset' cannot be used in Latte (on line 1 at column 1)",
);

// shell execution
Assert::exception(
	fn() => parseCode('`test`'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '`' (on line 1 at column 1)",
);

// throwing
Assert::exception(
	fn() => parseCode('throw new Exception'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected 'new' (on line 1 at column 7)",
);

// syntax error, not number
Assert::exception(
	fn() => parseCode('100_'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '_' (on line 1 at column 4)",
);

// syntax error, not number
Assert::exception(
	fn() => parseCode('1__1'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '__1' (on line 1 at column 2)",
);

// syntax error, not unquoted string
Assert::exception(
	fn() => parseCode('a---b--c'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '--' (on line 1 at column 2)",
);

// syntax error, not unquoted string
Assert::exception(
	fn() => parseCode('--ab'),
	Core\View\Template\Exception\CompileException::class,
	'Unexpected end (on line 1 at column 5)',
);

// invalid octal
Assert::exception(
	fn() => parseCode('0787'),
	Core\View\Template\Exception\CompileException::class,
	'Invalid numeric literal (on line 1 at column 1)',
);

// "comments"
Assert::exception(
	fn() => parseCode('#comment'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '#' (on line 1 at column 1)",
);

// "comments"
Assert::exception(
	fn() => parseCode('//comment'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '/' (on line 1 at column 1)",
);

// { } access
Assert::exception(
	fn() => parseCode('$a{"b"}'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '{' (on line 1 at column 3)",
);

// ${...} is not supported
Assert::exception(
	fn() => parseCode('"a${b}c"'),
	Core\View\Template\Exception\CompileException::class,
	'Syntax ${...} is not supported (on line 1 at column 3)',
);

// b"" is not supported
Assert::exception(
	fn() => parseCode('b""'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '\"\"' (on line 1 at column 2)",
);

// invalid octal number
Assert::exception(
	fn() => parseCode('01777777777787'),
	Core\View\Template\Exception\CompileException::class,
	'Invalid numeric literal (on line 1 at column 1)',
);

// casts to boolean, double, real, unset
Assert::exception(
	fn() => parseCode('(boolean) $a'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '\$a' (on line 1 at column 11)",
);

// invalid firstclass callables
Assert::exception(
	fn() => parseCode('new Foo(...)'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected ')' (on line 1 at column 12)",
);

Assert::exception(
	fn() => parseCode('$this?->foo(...)'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected ')' (on line 1 at column 16)",
);
