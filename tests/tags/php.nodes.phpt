<?php

/**
 * Test: {php}
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->addExtension(new Core\View\Template\Essential\RawPhpExtension);

Assert::match(<<<'XX'
	Template:
		Fragment:
			RawPhp:
		Fragment:
	XX, exportTraversing('{php $var}', $latte));
