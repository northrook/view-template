<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Runtime;

interface HtmlStringable
{
    /**
     * in HTML format
     */
    public function __toString() : string;
}
