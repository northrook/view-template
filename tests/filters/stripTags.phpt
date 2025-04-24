<?php

/**
 * Test: Core\View\Template\Essential\Filters::stripTags()
 */

declare(strict_types=1);

use Core\View\Template\ContentType;
use Core\View\Template\Engine;
use Core\View\Template\Essential\Filters;
use Core\View\Template\Runtime\FilterInfo;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);
Assert::same(
	'&quot;',
	$latte->renderToString('{="<br>&quot;"|stripTags}'),
);


test('exception on incompatible content type (TEXT)', function () {
	$info = new FilterInfo(ContentType::Text);
	Assert::exception(
		fn() => Filters::stripTags($info, ''),
		Core\View\Template\Exception\RuntimeException::class,
		'Filter |stripTags used with incompatible type TEXT.',
	);
});


test('HTML tag stripping with entity preservation', function () {
	$info = new FilterInfo(ContentType::Html);
	Assert::same('', Filters::stripTags($info, ''));
	Assert::same('abc', Filters::stripTags($info, 'abc'));
	Assert::same('&lt;  c', Filters::stripTags($info, '&lt; <b> c'));
});


test('XML tag stripping with entity preservation', function () {
	$info = new FilterInfo(ContentType::Xml);
	Assert::same('', Filters::stripTags($info, ''));
	Assert::same('abc', Filters::stripTags($info, 'abc'));
	Assert::same('&lt;  c', Filters::stripTags($info, '&lt; <b> c'));
});
