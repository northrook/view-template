<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);
$latte->addExtension(new Core\View\Template\Essential\TranslatorExtension(null));

Assert::match(
	<<<'XX'
		%A%
				$__filter__ = new Runtime\FilterInfo('html');
				echo Runtime\Filters::convertTo($__filter__, 'html', $this->filters->filterContent('translate', $__filter__, 'abc')) /* line 1 */;
		%A%
		XX,
	$latte->compile('{translate}abc{/translate}'),
);

Assert::contains(
	'echo Runtime\Filters::convertTo($__filter__, \'html\', $this->filters->filterContent(\'translate\', $__filter__, \'abc\', 10, 20)) /* line 1 */;',
	$latte->compile('{translate 10, 20}abc{/translate}'),
);

Assert::match(
	<<<'XX'
		%A%
				$__filter__ = new Runtime\FilterInfo('html');
				echo Runtime\Filters::convertTo($__filter__, 'html', $this->filters->filterContent('filter', $__filter__, $this->filters->filterContent('translate', $__filter__, 'abc'))) /* line 1 */;
		%A%
		XX,
	$latte->compile('{translate|filter}abc{/translate}'),
);

Assert::match(
	<<<'XX'
		%A%
				ob_start(fn() => '');
				try {
					if (true) /* line 1 */ {
						echo 'abc';
					}

				} finally {
					$__temp__ = ob_get_clean();
				}
				$__filter__ = new Runtime\FilterInfo('html');
				echo Runtime\Filters::convertTo($__filter__, 'html', $this->filters->filterContent('translate', $__filter__, $__temp__)) /* line 1 */;
		%A%
		XX,
	$latte->compile('{translate}{if true}abc{/if}{/translate}'),
);

Assert::notContains(
	"'translate'",
	$latte->compile('{translate /}'),
);


function translate($message, ...$parameters): string
{
	return strrev($message) . implode(',', $parameters);
}


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);
$latte->addExtension(new Core\View\Template\Essential\TranslatorExtension('translate'));
Assert::contains(
	'echo Runtime\Filters::convertTo($__filter__, \'html\', $this->filters->filterContent(\'translate\', $__filter__, \'a&b\', 1, 2))',
	$latte->compile('{translate 1,2}a&b{/translate}'),
);
Assert::same(
	'b&a1,2',
	$latte->renderToString('{translate 1,2}a&b{/translate}'),
);


$latte->addExtension(new Core\View\Template\Essential\TranslatorExtension('translate', 'en'));
Assert::contains(
	'echo Runtime\Filters::convertTo($__filter__, \'html\', \'b&a1,2\')',
	$latte->compile('{translate 1,2}a&b{/translate}'),
);
Assert::same(
	'b&a1,2',
	$latte->renderToString('{translate 1,2}a&b{/translate}'),
);
