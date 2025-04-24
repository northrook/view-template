<?php

/**
 * Test: Compile errors.
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);

Assert::exception(
	fn() => $latte->compile('{'),
	Core\View\Template\Exception\CompileException::class,
	'Unexpected end, expecting end of Latte tag started on line 1 at column 1 (on line 1 at column 2)',
);

Assert::exception(
	fn() => $latte->compile('{foo'),
	Core\View\Template\Exception\CompileException::class,
	'Unexpected end, expecting end of Latte tag started on line 1 at column 1 (on line 1 at column 5)',
);

Assert::exception(
	fn() => $latte->compile("{* \n'abc}"),
	Core\View\Template\Exception\CompileException::class,
	'Unexpected end, expecting end of Latte comment started on line 1 at column 1 (on line 2 at column 6)',
);

Assert::exception(
	fn() => $latte->compile('{syntax double} {{a'),
	Core\View\Template\Exception\CompileException::class,
	'Unexpected end, expecting end of Latte tag started on line 1 at column 17 (on line 1 at column 20)',
);

Assert::exception(
	fn() => $latte->compile('{syntax double} {{a } b'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '} b' (on line 1 at column 21)",
);

Assert::exception(
	fn() => $latte->compile('<! foo'),
	Core\View\Template\Exception\CompileException::class,
	'Unexpected end, expecting end of HTML tag (on line 1 at column 7)',
);

Assert::exception(
	fn() => $latte->compile("<a href='xx{* xx *}>"),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected end, expecting ', end of HTML attribute started on line 1 at column 9 (on line 1 at column 21)",
);

Assert::exception(
	fn() => $latte->compile("<a n:href='xx>"),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected end, expecting ', end of n:attribute started on line 1 at column 11 (on line 1 at column 15)",
);

Assert::exception(
	fn() => $latte->compile('<!-- xxx'),
	Core\View\Template\Exception\CompileException::class,
	'Unexpected end, expecting end of HTML comment started on line 1 at column 1 (on line 1 at column 9)',
);

Assert::exception(
	fn() => $latte->compile('Block{/block}'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '{' (on line 1 at column 6)",
);

Assert::exception(
	fn() => $latte->compile("{var \n'abc}"),
	Core\View\Template\Exception\CompileException::class,
	'Unterminated string (on line 2 at column 1)',
);

Assert::exception(
	fn() => $latte->compile('{contentType xml}<a n:if=1></A>'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '</A>', expecting </a> for element started on line 1 at column 18 (on line 1 at column 28)",
);

Assert::exception(
	fn() => $latte->compile('<a {if}n:href>'),
	Core\View\Template\Exception\CompileException::class,
	'Attribute n:href must not appear inside {tags} (on line 1 at column 8)',
);

Assert::exception(
	fn() => $latte->compile('<a></a foo>'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected 'foo', expecting end of HTML tag (on line 1 at column 8)",
);

Assert::exception(
	fn() => $latte->compile('<{if 1}{/if}>'),
	Core\View\Template\Exception\CompileException::class,
	'Only expression can be used as a HTML tag name (on line 1 at column 2)',
);

Assert::exception(
	fn() => $latte->compile('<{$foo}>'),
	Core\View\Template\Exception\CompileException::class,
	'Unexpected end, expecting </{$foo}> for element started on line 1 at column 1 (on line 1 at column 9)',
);

Assert::exception(
	fn() => $latte->compile('<{$foo}></{$bar}>'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '</{\$bar}>', expecting </{\$foo}> for element started on line 1 at column 1 (on line 1 at column 9)",
);

Assert::exception(
	fn() => $latte->compile('<{$foo}>...</{if 1}{/if}>'),
	Core\View\Template\Exception\CompileException::class,
	'Only expression can be used as a HTML tag name (on line 1 at column 14)',
);

Assert::exception(
	fn() => $latte->compile('<a>...</{$foo}>'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '</{\$foo}>', expecting </a> for element started on line 1 at column 1 (on line 1 at column 7)",
);

Assert::exception(
	fn() => $latte->compile('<{$foo}><span></{$foo}>'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '</{\$foo}>', expecting </span> for element started on line 1 at column 9 (on line 1 at column 15)",
);

Assert::exception(
	fn() => $latte->compile('<{$foo}></span></{$foo}>'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '</span>', expecting </{\$foo}> for element started on line 1 at column 1 (on line 1 at column 9)",
);

Assert::exception(
	fn() => $latte->compile('</{$foo}>'), // bogus tag
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '{', expecting HTML name (on line 1 at column 3)",
);

Assert::exception(
	fn() => $latte->compile('<span title={if true}a b{/if}></span>'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected ' ', expecting {/if} (on line 1 at column 23)",
);

Assert::exception(
	fn() => $latte->compile('<span title={if true}"a"{/if}></span>'),
	Core\View\Template\Exception\CompileException::class,
	'Unexpected \'"\', expecting {/if} (on line 1 at column 22)',
);

Assert::exception(
	fn() => $latte->compile('<span {if true}title{/if}=a></span>'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '=', expecting end of HTML tag (on line 1 at column 26)",
);

Assert::exception(
	fn() => $latte->compile('<span title{if true}{/if}=a></span>'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '=', expecting end of HTML tag (on line 1 at column 26)",
);

Assert::exception(
	fn() => $latte->compile('<a n:href n:href>'),
	Core\View\Template\Exception\CompileException::class,
	'Found multiple attributes n:href (on line 1 at column 11)',
);

Assert::match(
	'<div c=comment -->',
	$latte->renderToString('<div c=comment {="--"}>'),
);

Assert::exception(
	fn() => $latte->compile('<a n:inner-syntax>'),
	Core\View\Template\Exception\CompileException::class,
	'Use n:syntax instead of n:inner-syntax (on line 1 at column 4)',
);

Assert::exception(
	fn() => $latte->compile('<a n:class class>'),
	Core\View\Template\Exception\CompileException::class,
	'It is not possible to combine class with n:class (on line 1 at column 4)',
);

Assert::exception(
	fn() => $latte->compile('<p title=""</p>'),
	'Core\View\Template\Exception\CompileException',
	"Unexpected '</p>' (on line 1 at column 12)",
);

Assert::exception(
	fn() => $latte->compile('<p title=>'),
	'Core\View\Template\Exception\CompileException',
	"Unexpected '>' (on line 1 at column 10)",
);

Assert::exception(
	fn() => $latte->compile('<a {$foo}<'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '<' (on line 1 at column 10)",
);

Assert::exception(
	fn() => $latte->compile('{time() /}'),
	Core\View\Template\Exception\CompileException::class,
	'Unexpected /} in tag {=time() /} (on line 1 at column 1)',
);


// <script> & <style> must be closed
Assert::exception(
	fn() => $latte->compile('<STYLE>'),
	Core\View\Template\Exception\CompileException::class,
	'Unexpected end, expecting </STYLE> for element started on line 1 at column 1 (on line 1 at column 8)',
);

Assert::exception(
	fn() => $latte->compile('<script>'),
	Core\View\Template\Exception\CompileException::class,
	'Unexpected end, expecting </script> for element started on line 1 at column 1 (on line 1 at column 9)',
);

Assert::noError(
	fn() => $latte->compile('{contentType xml}<script>'),
);


// brackets balancing
Assert::exception(
	fn() => $latte->compile('{=)}'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected ')' (on line 1 at column 3)",
);

Assert::exception(
	fn() => $latte->compile('{=[(])}'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected ']' (on line 1 at column 5)",
);


// forbidden keywords
Assert::exception(
	fn() => $latte->compile('{= function test() }'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected 'test' (on line 1 at column 13)",
);

Assert::exception(
	fn() => $latte->compile('{= class test }'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected 'test' (on line 1 at column 10)",
);

Assert::exception(
	fn() => $latte->compile('{= return}'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected 'return' (on line 1 at column 4)",
);

Assert::noError( // prints 'yield'
	fn() => $latte->compile('{= yield}'),
);

Assert::exception(
	fn() => $latte->compile('{= yield $x}'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '\$x' (on line 1 at column 10)",
);

Assert::exception(
	fn() => $latte->compile('{=`whoami`}'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '`' (on line 1 at column 3)",
);

Assert::exception(
	fn() => $latte->compile('{$__temp__}'),
	Core\View\Template\Exception\CompileException::class,
	'Forbidden variable $__temp__ (on line 1 at column 2)',
);

Assert::exception(
	fn() => $latte->compile('{$GLOBALS}'),
	Core\View\Template\Exception\CompileException::class,
	'Forbidden variable $GLOBALS (on line 1 at column 2)',
);


// unclosed macros
Assert::exception(
	fn() => $latte->compile('{if 1}'),
	Core\View\Template\Exception\CompileException::class,
	'Unexpected end, expecting {/if} (on line 1 at column 7)',
);

Assert::exception(
	fn() => $latte->compile('<p n:if=1><span n:if=1>'),
	Core\View\Template\Exception\CompileException::class,
	'Unexpected end, expecting </span> for element started on line 1 at column 11 (on line 1 at column 24)',
);

Assert::exception(
	fn() => $latte->compile('<p n:if=1><span n:if=1></i>'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '</i>', expecting </span> for element started on line 1 at column 11 (on line 1 at column 24)",
);

Assert::exception(
	fn() => $latte->compile('{/if}'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '{' (on line 1 at column 1)",
);

Assert::exception(
	fn() => $latte->compile('{if 1}{/foreach}'),
	Core\View\Template\Exception\CompileException::class,
	'Unexpected {/foreach}, expecting {/if} (on line 1 at column 7)',
);

Assert::exception(
	fn() => $latte->compile('{if 1}{/if 2}'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '2', expecting end of tag in {/if} (on line 1 at column 12)",
);

Assert::exception(
	fn() => $latte->compile('<span n:if=1>{foreach $a as $b}</span>'),
	Core\View\Template\Exception\CompileException::class,
	'Unexpected end, expecting {/foreach} (on line 1 at column 39)',
);

Assert::exception(
	fn() => $latte->compile('<span n:if=1>{/if}'),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '{/if', expecting </span> for element started on line 1 at column 1 (on line 1 at column 14)",
);

Assert::exception(
	fn() => $latte->compile(<<<'XX'
				{foreach [] as $item}
					<li><a n:tag-if="$iterator->odd"></li>
				{/foreach}
		XX),
	Core\View\Template\Exception\CompileException::class,
	"Unexpected '</li>', expecting </a> for element started on line 2 at column 8 (on line 2 at column 37)",
);
