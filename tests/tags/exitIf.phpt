<?php

/**
 * Test: {exitIf}
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);

Assert::exception(
	fn() => $latte->compile('{foreach $a as $b}{exitIf true}'),
	Core\View\Template\Exception\CompileException::class,
	'Tag {exitIf} is unexpected here (on line 1 at column 19)',
);

Assert::exception(
	fn() => $latte->compile('{exitIf}'),
	Core\View\Template\Exception\CompileException::class,
	'Missing arguments in {exitIf} (on line 1 at column 1)',
);


$template = <<<'XX'
	{exitIf true}
	c
	XX;

Assert::match(
	<<<'XX'
		%A%
			{
				if (true) /* line 1 */ return;
				echo 'c';
			}
		%A%
		XX,
	$latte->compile($template),
);


$template = <<<'XX'
	a
	{exitIf true}
	b
	{exitIf false}
	c
	XX;

Assert::match(
	<<<'XX'
		%A%
			{
				echo 'a
		';
				if (true) /* line 2 */ return;
				echo 'b
		';
				if (false) /* line 4 */ return;
				echo 'c';
			}
		%A%
		XX,
	$latte->compile($template),
);


$template = <<<'XX'
	{block foo}
		a
		{exitIf true}
		b
		{exitIf false}
		c
	{/block}
	XX;

Assert::match(
	<<<'XX'
		%A%
			{
				echo '	a
		';
				if (true) /* line 3 */ return;
				echo '	b
		';
				if (false) /* line 5 */ return;
				echo '	c
		';
			}
		%A%
		XX,
	$latte->compile($template),
);


$template = <<<'XX'
	{define foo}
		a
		{exitIf true}
		b
		{exitIf false}
		c
	{/define}
	XX;

Assert::match(
	<<<'XX'
		%A%
			{
				echo '	a
		';
				if (true) /* line 3 */ return;
				echo '	b
		';
				if (false) /* line 5 */ return;
				echo '	c
		';
			}
		%A%
		XX,
	$latte->compile($template),
);


$template = <<<'XX'
	<div>{exitIf true}</div>
	XX;

Assert::match(
	<<<'XX'
		%A%
				echo '<div>';
				try {
					if (true) /* line 1 */ return;
				} finally {
					echo '</div>';
				}
		%A%
		XX,
	$latte->compile($template),
);
