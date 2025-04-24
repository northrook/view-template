<?php

/**
 * Test: {contentType application/xml}
 */

declare(strict_types=1);

use Core\View\Template\ContentType;
use Core\View\Template\Runtime\Html;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);

Assert::same(
	' &lt;&gt;&quot;&apos;',
	$latte->renderToString('{contentType xml} {$foo}', ['foo' => '<>"\'']),
);

Assert::same(
	' &lt;&gt;&quot;&apos;',
	$latte->renderToString('{contentType application/xml} {$foo}', ['foo' => '<>"\'']),
);


$latte = new Core\View\Template\Engine;
$latte->setContentType(ContentType::Xml);

$params['hello'] = '<i>Hello</i>';
$params['id'] = ':/item';
$params['people'] = ['John', 'Mary', 'Paul', ']]> <!--'];
$params['comment'] = 'test -- comment';
$params['el'] = new Html("<div title='1/2\"'></div>");
$params['xss'] = 'some&<>"\'/chars';

Assert::matchFile(
	__DIR__ . '/expected/contentType.xml.php',
	$latte->compile(__DIR__ . '/templates/contentType.xml.latte'),
);
Assert::matchFile(
	__DIR__ . '/expected/contentType.xml.html',
	$latte->renderToString(
		__DIR__ . '/templates/contentType.xml.latte',
		$params,
	),
);
