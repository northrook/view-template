<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Compiler\Nodes\Php;

use Core\View\Template\Compiler\Node;
use Core\View\Template\Compiler\Position;
use Core\View\Template\Compiler\PrintContext;


class InterpolatedStringPartNode extends Node
{
	public function __construct(
		public string $value,
		public ?Position $position = null,
	) {
	}


	public function print(PrintContext $context): string
	{
		throw new \LogicException('Cannot directly print InterpolatedStringPart');
	}


	public function &getIterator(): \Generator
	{
		false && yield;
	}
}
