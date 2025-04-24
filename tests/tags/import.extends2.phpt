<?php

/**
 * Test: {import ...}
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader([
	'main' => <<<'XX'

				{extends parent}
				{block main}
					{include test}
				{/block}

		XX,
	'parent' => <<<'XX'

				{import inc}
				{include main}

		XX,
	'inc' => <<<'XX'

				{define test}test block{/define}

		XX,
]));

Assert::match(
	'test block',
	trim($latte->renderToString('main')),
);
