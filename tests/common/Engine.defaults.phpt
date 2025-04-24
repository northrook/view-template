<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
Assert::same(
	'12345',
	$latte->invokeFilter('reverse', ['54321']),
);

Assert::same(
	5,
	$latte->invokeFunction('clamp', [10, 1, 5]),
);
