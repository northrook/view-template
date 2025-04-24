<?php

/**
 * Test: Core\View\Template\Engine and blocks.
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);

Assert::match(<<<'EOD'
	<head>
		<script src="nette.js"></script>
		<link rel="alternate">
	</head>

		<link rel="alternate">
	EOD, $latte->renderToString(
	<<<'EOD'
		<head>
			<script src="nette.js"></script>
			{include meta}
		</head>

		{block meta}
			<link rel="alternate">
		{/block}
		EOD,
));
