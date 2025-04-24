<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);
$latte->addFunction('Abc', function (stdClass $a, $b = 132) {});


class ParentTemplate
{
	public $int;
}

$params = ['int' => 123, 'unknown' => null];

$blueprint = new Core\View\Template\Essential\Blueprint;
ob_start();
$blueprint->printClass($blueprint->generateTemplateClass($params));
$res = ob_get_clean();

Assert::match(
	<<<'XX'
		%A%class Template
		{
			public int $int;
			public mixed $unknown;
		}
		%A%
		XX,
	$res,
);


ob_start();
$blueprint->printClass($blueprint->generateTemplateClass($params, name: Foo\Template::class, extends: ParentTemplate::class));
$res = ob_get_clean();

Assert::match(
	<<<'XX'
		%A%namespace Foo;

		class Template extends \ParentTemplate
		{
			public mixed $unknown;
		}
		%A%
		XX,
	$res,
);
