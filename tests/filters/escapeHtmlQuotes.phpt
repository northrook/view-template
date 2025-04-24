<?php

/**
 * Test: Core\View\Template\Runtime\Filters::escapeHtmlQuotes
 */

declare(strict_types=1);

use Core\View\Template\Runtime\Filters;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


Assert::same('', Filters::escapeHtmlQuotes(null));
Assert::same('', Filters::escapeHtmlQuotes(''));
Assert::same('1', Filters::escapeHtmlQuotes(1));
Assert::same('string', Filters::escapeHtmlQuotes('string'));
Assert::same('< & &apos; &quot; >', Filters::escapeHtmlQuotes('< & \' " >'));
Assert::same('&quot;', Filters::escapeHtmlQuotes('&quot;'));
