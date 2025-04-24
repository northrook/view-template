<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);
$latte->addExtension(new Core\View\Template\Essential\TranslatorExtension(null));

Assert::contains(
	'echo Runtime\Filters::escapeHtmlText(($this->filters->translate)(\'var\')) /*',
	$latte->compile('{_var}'),
);

Assert::contains(
	'echo Runtime\Filters::escapeHtmlText(($this->filters->filter)(($this->filters->translate)(\'var\'))) /*',
	$latte->compile('{_var|filter}'),
);

Assert::contains(
	'echo Runtime\Filters::escapeHtmlText(($this->filters->translate)(\'messages.hello\', 10, 20)) /* line 1 */;',
	$latte->compile('{_messages.hello, 10, 20}'),
);


function translate($message, ...$parameters): string
{
	return strrev($message) . implode(',', $parameters);
}


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);
$latte->addExtension(new Core\View\Template\Essential\TranslatorExtension('translate'));
Assert::contains(
	'echo Runtime\Filters::escapeHtmlText(($this->filters->translate)(\'a&b\', 1, 2))',
	$latte->compile('{_"a&b", 1, 2}'),
);
Assert::same(
	'b&amp;a1,2',
	$latte->renderToString('{_"a&b", 1, 2}'),
);


$latte->addExtension(new Core\View\Template\Essential\TranslatorExtension('translate', 'en'));
Assert::contains(
	'echo Runtime\Filters::escapeHtmlText(\'b&a1,2\')',
	$latte->compile('{_"a&b", 1, 2}'),
);
Assert::same(
	'b&amp;a1,2',
	$latte->renderToString('{_"a&b", 1, 2}'),
);
