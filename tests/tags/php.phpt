<?php

/**
 * Test: {php}
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);
$latte->addExtension(new Core\View\Template\Essential\RawPhpExtension);

Assert::match(
	<<<'XX'
		%A%
				/* line 1 */;
				if ($a) {
					echo 10;
				}
		%A%
		XX,
	$latte->compile('{php if ($a) { echo 10; }}'),
);
