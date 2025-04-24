<?php

/**
 * Test: Core\View\Template\Runtime\Filters::escapeHtmlRawTextHtml
 */

declare(strict_types=1);

use Core\View\Template\Runtime\Filters;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class Test implements Core\View\Template\Runtime\HtmlStringable
{
	public function __toString(): string
	{
		return '<br>';
	}
}

Assert::same('', Filters::escapeHtmlRawTextHtml(null));
Assert::same('', Filters::escapeHtmlRawTextHtml(''));
Assert::same('1', Filters::escapeHtmlRawTextHtml(1));
Assert::same('string', Filters::escapeHtmlRawTextHtml('string'));
Assert::same('&lt; &amp; &apos; &quot; &gt;', Filters::escapeHtmlRawTextHtml('< & \' " >'));
Assert::same('&amp;quot;', Filters::escapeHtmlRawTextHtml('&quot;'));
Assert::same('&lt;/p&gt;', Filters::escapeHtmlRawTextHtml('</p>'));
Assert::same('&lt;/script&gt;', Filters::escapeHtmlRawTextHtml('</script>'));
Assert::same('<br>', Filters::escapeHtmlRawTextHtml(new Test));
Assert::same('<p></p>', Filters::escapeHtmlRawTextHtml(new Core\View\Template\Runtime\Html('<p></p>')));
Assert::same('<x-script></x-script>', Filters::escapeHtmlRawTextHtml(new Core\View\Template\Runtime\Html('<script></script>')));

// invalid UTF-8
Assert::same("foo \u{FFFD} bar", Filters::escapeHtmlRawTextHtml("foo \u{D800} bar")); // invalid codepoint high surrogates
Assert::same("foo \u{FFFD}&quot; bar", Filters::escapeHtmlRawTextHtml("foo \xE3\x80\x22 bar")); // stripped UTF
