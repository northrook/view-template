<?php

/**
 * Test: Core\View\Template\Essential\Filters::breaklines()
 */

declare(strict_types=1);

use Core\View\Template\Essential\Filters;
use Core\View\Template\Runtime\Html;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$input = "Hello\nmy\r\nfriend\n\r";

Assert::equal(new Html("Hello<br>\nmy<br>\r\nfriend<br>\n\r"), Filters::breaklines($input));

Assert::equal(new Html("&lt;&gt;<br>\n&amp;"), Filters::breaklines("<>\n&"));

// Html is ignored
Assert::equal(new Html("&lt;&gt;<br>\n&amp;"), Filters::breaklines(new Html("<>\n&")));
