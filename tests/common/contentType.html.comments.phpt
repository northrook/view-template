<?php

/**
 * Test: comments HTML test
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$params['gt'] = '>';
$params['dash'] = '-';
$params['basePath'] = '/www';

Assert::matchFile(
	__DIR__ . '/expected/contentType.html.comments.html',
	$latte->renderToString(
		__DIR__ . '/templates/contentType.html.comments.latte',
		$params,
	),
);


// no escape
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);
Assert::match(
	'<!--  - - > -->',
	$latte->renderToString('<!-- {="-->"|noescape} -->'),
);
