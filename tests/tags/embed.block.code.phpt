<?php

/**
 * Test: {embed block}
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader(templates: [
	'main' => <<<'XX'

				{embed embed1}
					{block a}
						{embed embed1}
							{block a}nested embeds A{/block}
						{/embed}
					{/block}
				{/embed}

				{define embed1}
				embed1-start
					{block a}embed1-A{/block}
				embed1-end
				{/define}

		XX,
]));

Assert::matchFile(
	__DIR__ . '/expected/embed.block.php',
	$latte->compile('main'),
);
