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

				{import "inc"}
				{include test}

		XX,
	'main-dynamic' => <<<'XX'

				{import "i" . "nc"}
				{include test}

		XX,
	'inc' => <<<'XX'

				outer text
				{define test}
					Test block
				{/define}

		XX,
]));

Assert::matchFile(
	__DIR__ . '/expected/import.php',
	$latte->compile('main'),
);
Assert::match(
	'Test block',
	trim($latte->renderToString('main')),
);

Assert::match(
	'Test block',
	trim($latte->renderToString('main-dynamic')),
);
