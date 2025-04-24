<?php

declare(strict_types=1);

use Core\View\Template\Compiler\TagLexer;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


function tokenize(string $code): array
{
	$lexer = new TagLexer;
	return $lexer->tokenize($code);
}


Assert::exception(
	fn() => tokenize("\0 foo"),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '\x00' (on line 1 at column 1)",
);

Assert::exception(
	fn() => tokenize('"$a[]"'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '[]\"' (on line 1 at column 5)",
);

Assert::exception(
	fn() => tokenize('"aa'),
	Core\View\Template\Exception\CompileException::class,
	'Unterminated string (on line 1 at column 4)',
);

Assert::exception(
	fn() => tokenize("'aa"),
	Core\View\Template\Exception\CompileException::class,
	'Unterminated string (on line 1 at column 1)',
);

Assert::exception(
	fn() => tokenize('"aa $a'),
	Core\View\Template\Exception\CompileException::class,
	'Unterminated string (on line 1 at column 7)',
);

Assert::exception(
	fn() => tokenize('"aa {$a "'),
	Core\View\Template\Exception\CompileException::class,
	'Unterminated string (on line 1 at column 10)',
);

Assert::exception(
	fn() => tokenize('"aa $a["'),
	Core\View\Template\Exception\CompileException::class,
	"Missing ']' (on line 1 at column 8)",
);

Assert::exception(
	fn() => tokenize('"aa ${a}"'),
	Core\View\Template\Exception\CompileException::class,
	'Syntax ${...} is not supported (on line 1 at column 5)',
);

Assert::exception(
	fn() => tokenize("<<<DOC\n"),
	Core\View\Template\Exception\CompileException::class,
	'Unterminated string (on line 2 at column 1)',
);

Assert::exception(
	fn() => tokenize("<<<'DOC'\n"),
	Core\View\Template\Exception\CompileException::class,
	'Unterminated NOWDOC (on line 2 at column 1)',
);

Assert::exception(
	fn() => tokenize('/*'),
	Core\View\Template\Exception\CompileException::class,
	'Unterminated comment (on line 1 at column 1)',
);
