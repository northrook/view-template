<?php

/**
 * Test: {include this}
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);

// out of block
Assert::exception(
	fn() => $latte->renderToString('{include this}'),
	Core\View\Template\Exception\CompileException::class,
	'Cannot include this block outside of any block (on line 1 at column 1)',
);

// in anonymous block
Assert::exception(
	fn() => $latte->renderToString('{block} {include this} {/block}'),
	Core\View\Template\Exception\CompileException::class,
	'Cannot include this block outside of any block (on line 1 at column 9)',
);

Assert::match(
	'  2   1',
	$latte->renderToString('{block foo} {block} {$i--} {if $i}{include this}{/if} {/block} {/block}', ['i' => 2]),
);

// with modifier
Assert::match(
	'  2 1',
	$latte->renderToString('{block foo}  {$i--} {if $i}{include this|trim}{/if}  {/block}', ['i' => 2]),
);

// with params
Assert::match(
	' 2  -1',
	$latte->renderToString('{block foo} {$i--} {if $i > 0}{include this, i: $i - 2}{/if} {/block}', ['i' => 2]),
);

// double
Assert::match(
	' 2  1     1',
	$latte->renderToString('{block foo} {$i--} {if $i}{include this}{/if} {if $i}{include this}{/if} {/block}', ['i' => 2]),
);
