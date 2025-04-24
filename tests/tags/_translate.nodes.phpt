<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$latte = new Core\View\Template\Engine;
$latte->addExtension(new Core\View\Template\Essential\TranslatorExtension(null));

Assert::match(<<<'XX'
	Template:
		Fragment:
		Fragment:
			Print:
				Variable:
					name: var
				Modifier:
					Filter:
						Identifier:
							name: translate
					Filter:
						Identifier:
							name: trim
	XX, exportTraversing('{_$var|trim}', $latte));
