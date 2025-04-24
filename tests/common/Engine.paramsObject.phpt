<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class TemplateParams
{
	public $a = 123;
	protected $protected = 'x';
	private $private = 'x';


	#[Core\View\Template\Attributes\TemplateFunction]
	public function myFunc($a)
	{
		return "*$a*";
	}


	#[Core\View\Template\Attributes\TemplateFilter]
	public function myFilter($a)
	{
		return "%$a%";
	}


	#[Core\View\Template\Attributes\TemplateFilter, Core\View\Template\Attributes\TemplateFunction]
	public function both($a)
	{
		return "#$a#";
	}
}


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);
$latte->setTempDirectory(getTempDir());

Assert::same(
	'%*123*% ##123## ',
	$latte->renderToString('{myFunc($a)|myFilter} {both(123)|both} {if isset($protected) || isset($private)}invisible{/if}', new TemplateParams),
);
