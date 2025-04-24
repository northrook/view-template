<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/Policy.Logger.php';


class MyClass
{
	public static function foo()
	{
	}
}


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);
$latte->setTempDirectory(getTempDir());

$policy = new PolicyLogger;
$latte->setPolicy($policy);
$latte->setSandboxMode();

$template = <<<'EOD'
	{var $var = 10}
	{$var}
	{foreach [] as $item}
	{/foreach}

	EOD;

// compile-time
$latte->compile($template);
Assert::same(
	[
		'tags' => ['var', '=', 'foreach'],
	],
	$policy->log,
);


// run-time
$latte->warmupCache($template);
$policy->log = [];
$latte->renderToString($template);
Assert::same(
	[],
	$policy->log,
);
