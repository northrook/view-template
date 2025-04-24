<?php

/**
 * Test: Core\View\Template\Engine and blocks.
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);


// order of block & include
Assert::match(
	<<<'XX'



			X
		XX,
	$latte->renderToString(
		<<<'XX'

			{define a}
				{var $x = "X"}
				{include #b}
			{/define}

			{define b}
				{$x}
			{/define}

			{include a}

			XX,
	),
);
