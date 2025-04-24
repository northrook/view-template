<?php

/**
 * Test: {if}
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);

$template = <<<'EOD'

	{if true}
		a
		{elseif $b}
		b
		{elseifset $c}
		c
		{else}
		d
	{/if}

	--

	{if}
		a
	{/if true}

	--

	{if}
		a
		{else}
		d
	{/if true}

	--

	{ifset $a}
		a
		{elseif $b}
		b
		{elseifset $c}
		c
		{else}
		d
	{/ifset}

	EOD;

Assert::matchFile(
	__DIR__ . '/expected/if.php',
	$latte->compile($template),
);



// breaking
$template = <<<'X'
	{foreach [1, 0] as $cond}
		{$cond}
		{if}
			if
			{else}
			else
			{continueIf $cond}
			breaked
		{/if true}
		end
	{/foreach}
	X;

Assert::match(
	<<<'XX'
			1
			0
				if
			end
		XX,
	$latte->renderToString($template),
);
