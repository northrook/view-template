<?php

/**
 * Test: {import ..., args}
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader([
	'main' => <<<'XX'

				{import "inc", val: 123}
				{include test}

		XX,
	'inc' => <<<'XX'

				outer text
				{define test}
					Test {$val}
				{/define}

		XX,
]));

Assert::match(
	'Test 123',
	trim($latte->renderToString('main')),
);
