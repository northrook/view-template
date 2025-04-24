<?php

/**
 * Test: {templatePrint}
 */

declare(strict_types=1);

use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;
$latte->setLoader(new Core\View\Template\Loaders\StringLoader);

Assert::match(
	<<<'XX'
		%A%
			public function prepare(): array
			{
				extract($this->params);

				$__begin_print__ = new Core\View\Template\Essential\Blueprint;
				$__begin_print__->printBegin();
				$__begin_print__->printClass($__begin_print__->generateTemplateClass($this->getParameters(), extends: null));
				$__begin_print__->printEnd();
				exit;
		%A%
		XX,
	$latte->compile('Foo {block}{/block} {templatePrint}'),
);


Assert::match(
	<<<'XX'
		%A%
			public function prepare(): array
			{
				extract($this->params);

				$__begin_print__ = new Core\View\Template\Essential\Blueprint;
				$__begin_print__->printBegin();
				$__begin_print__->printClass($__begin_print__->generateTemplateClass($this->getParameters(), extends: 'Foo'));
				$__begin_print__->printEnd();
				exit;
		%A%
		XX,
	$latte->compile('{templatePrint Foo}'),
);
