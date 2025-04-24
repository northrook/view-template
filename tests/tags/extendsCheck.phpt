<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);

$template = <<<'XX'

	{extends layout.latte}

	{capture $foo}
	    {block bar}{/block}
	{/capture}

	{block content}
	content

	XX;

Assert::matchFile(
	__DIR__ . '/expected/extendsCheck.php',
	$latte->compile($template),
);
