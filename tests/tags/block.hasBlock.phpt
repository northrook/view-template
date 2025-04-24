<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);

Assert::match(
	<<<'EOD'
		Main: true
		Foo: false
		EOD,
	$latte->renderToString(<<<'EOD'
		{block main}{/block}
		Main: {=var_export(hasBlock(main), true)}
		Foo: {=var_export(hasBlock(foo), true)}
		EOD),
);
