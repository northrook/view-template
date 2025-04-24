<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);
Assert::notContains('declare(strict_types=1)', $latte->compile(''));

$latte->setStrictTypes(true);
Assert::contains('declare(strict_types=1)', $latte->compile(''));

Assert::noError(fn() => $latte->render(''));
