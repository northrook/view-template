<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Core\View\Template\Engine;

$template = $latte->createTemplate(__DIR__ . '/templates/block.latte');
Assert::type(Core\View\Template\Runtime\Template::class, $template);
Assert::null($template->getReferringTemplate());
Assert::null($template->getReferenceType());
Assert::same(['menu'], $template->getBlockNames());


$template = $latte->createTemplate(__DIR__ . '/templates/block.latte');
Assert::type(Core\View\Template\Runtime\Template::class, $template);
Assert::null($template->getReferringTemplate());
Assert::null($template->getReferenceType());
Assert::same(['menu'], $template->getBlockNames());
