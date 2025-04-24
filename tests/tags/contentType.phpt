<?php

declare(strict_types=1);

use Core\View\Template\ContentType;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);


$template = $latte->createTemplate('');
Assert::same(ContentType::Html, $template::ContentType);

$template = $latte->createTemplate('{contentType xml}');
Assert::same(ContentType::Xml, $template::ContentType);

Assert::exception(
	fn() => $latte->createTemplate('{block}{contentType xml}{/block}'),
	Core\View\Template\Exception\CompileException::class,
	'{contentType} is allowed only in template header (on line 1 at column 8)',
);

Assert::exception(
	fn() => $latte->createTemplate('<div>{contentType xml}</div>'),
	Core\View\Template\Exception\CompileException::class,
	'{contentType} is allowed only in template header (on line 1 at column 6)',
);

Assert::same(
	'<script> <p n:if=0 /> </script>',
	$latte->renderToString('{contentType html}<script> <p n:if=0 /> </script>'),
);

Assert::same(
	'<script>  </script>',
	$latte->renderToString('{contentType xml}<script> <p n:if=0 /> </script>'),
);

Assert::same(
	'<p n:if=0 />',
	$latte->renderToString('{contentType text}<p n:if=0 />'),
);

// defined on $latte
$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);
$latte->setContentType(ContentType::Xml);

$template = $latte->createTemplate('--');
Assert::same(ContentType::Xml, $template::ContentType);
