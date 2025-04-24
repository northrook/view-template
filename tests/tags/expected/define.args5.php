<?php
%A%
final class Template%a% extends Core\View\Template\Runtime\Template
{
	public const Blocks = [
		['test' => 'blockTest'],
	];


	public function main(array $__args__): void
	{
		extract($__args__);
		unset($__args__);

		echo 'default values


a) ';
		$this->renderBlock('test', [1] + [], 'html') /* line %d% */;
		echo '

b) ';
		$this->renderBlock('test', ['var1' => 1] + [], 'html') /* line %d% */;
	}


	/** {define test $var1 = 0, $var2 = [1, 2, 3], $var3 = 10} on line %d% */
	public function blockTest(array $__args__): void
	{
		extract($this->params);
		$var1 = $__args__[0] ?? $__args__['var1'] ?? 0;
		$var2 = $__args__[1] ?? $__args__['var2'] ?? [1, 2, 3];
		$var3 = $__args__[2] ?? $__args__['var3'] ?? 10;
		unset($__args__);

		echo '	Variables ';
		echo Runtime\Filters::escapeHtmlText($var1) /* line %d% */;
		echo ', ';
		echo Runtime\Filters::escapeHtmlText(($this->filters->implode)($var2)) /* line %d% */;
		echo ', ';
		echo Runtime\Filters::escapeHtmlText($var3) /* line %d% */;
		echo "\n";
	}
}
