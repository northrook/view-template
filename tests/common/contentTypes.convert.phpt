<?php

/**
 * Test: Core\View\Template\Filters content type
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);

Assert::exception(
	fn() => $latte->renderToString('{define title} <h1>title</h1> {/define}  {include title|upper}'),
	Core\View\Template\Exception\RuntimeException::class,
	'Filter |upper is called with incompatible content type HTML, try to prepend |stripHtml.',
);

Assert::exception(
	fn() => $latte->renderToString('{block|upper} <h1>title</h1> {/block}'),
	Core\View\Template\Exception\RuntimeException::class,
	'Filter |upper is called with incompatible content type HTML, try to prepend |stripHtml.',
);

Assert::exception(
	fn() => $latte->renderToString('{capture $var|upper} <h1>title</h1> {/capture}'),
	Core\View\Template\Exception\RuntimeException::class,
	'Filter |upper is called with incompatible content type HTML, try to prepend |stripHtml.',
);

Assert::same(
	' title ',
	$latte->renderToString('{block|striptags} <h1>title</h1> {/block}'),
);

Assert::exception(
	fn() => $latte->renderToString('{block|striptags|upper} <h1>title</h1> {/block}'),
	Core\View\Template\Exception\RuntimeException::class,
	'Filter |upper is called with incompatible content type HTML, try to prepend |stripHtml.',
);

Assert::same(
	' title ',
	$latte->renderToString('{block|striphtml} <h1>title</h1> {/block}'),
);

Assert::same(
	' title ',
	$latte->renderToString('{block name|striphtml} <h1>title</h1> {/block}'),
);

Assert::same(
	' TITLE ',
	$latte->renderToString('{block|striphtml|upper} <h1>title</h1> {/block}'),
);

Assert::same(
	' ONE &lt; TWO ',
	$latte->renderToString('{block|striphtml|upper} one < two {/block}'),
);

Assert::same(
	' one &amp; two ',
	$latte->renderToString('{block|striptags} one &amp; two {/block}'),
);

Assert::same(
	'<meta content="val">',
	$latte->renderToString('<meta content="{block|stripHtml}val{/block}">'),
);

Assert::same(
	'<meta content="val">',
	$latte->renderToString('<meta content="{block name|stripHtml}val{/block}">'),
);



Assert::same(
	' one &amp; two ',
	$latte->renderToString('{contentType xml}{block|striptags} one &amp; two {/block}'),
);

Assert::same(
	' title ',
	$latte->renderToString('{contentType xml}{block|striphtml} <h1>title</h1> {/block}'),
);

Assert::same(
	' title ',
	$latte->renderToString('{contentType xml}{block name|striphtml} <h1>title</h1> {/block}'),
);

Assert::same(
	'<meta content="val" />',
	$latte->renderToString('{contentType xml}<meta content="{block|stripHtml}val{/block}" />'),
);

Assert::same(
	'<meta content="val" />',
	$latte->renderToString('{contentType xml}<meta content="{block name|stripHtml}val{/block}" />'),
);
