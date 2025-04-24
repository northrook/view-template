<?php

/**
 * Test: Core\View\Template\Runtime\Filters::safeTag()
 */

declare(strict_types=1);

use Core\View\Template\Runtime\Filters;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


Assert::same('foo', Filters::safeTag('foo'));
Assert::same('foo:bar', Filters::safeTag('foo:bar'));

Assert::exception(
	fn() => Filters::safeTag(null),
	Core\View\Template\Exception\RuntimeException::class,
	'Tag name must be string, null given',
);

Assert::exception(
	fn() => Filters::safeTag(''),
	Core\View\Template\Exception\RuntimeException::class,
	"Invalid tag name ''",
);

Assert::exception(
	fn() => Filters::safeTag('0'),
	Core\View\Template\Exception\RuntimeException::class,
	"Invalid tag name '0'",
);

Assert::exception(
	fn() => Filters::safeTag(':foo'),
	Core\View\Template\Exception\RuntimeException::class,
	"Invalid tag name ':foo'",
);

Assert::exception(
	fn() => Filters::safeTag('Script'),
	Core\View\Template\Exception\RuntimeException::class,
	'Forbidden variable tag name <Script>',
);

Assert::noError(
	fn() => Filters::safeTag('Script', xml: true),
);
