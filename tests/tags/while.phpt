<?php

/**
 * Test: {while}
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);

$template = <<<'EOD'

	{while $i++ < 10}
		{$i}
	{/while}


	{while}
		{$i}
	{/while $i++ < 10}


	{while $i++ < 10}
		{breakIf true}
		{continueIf true}
		{$i}
	{/while}

	EOD;

Assert::matchFile(
	__DIR__ . '/expected/while.php',
	$latte->compile($template),
);
