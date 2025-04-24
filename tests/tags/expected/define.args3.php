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

		echo 'named arguments order

a) ';
		$this->renderBlock('test', [1, 'var1' => 2] + [], 'html') /* line %d% */;
		echo '

b) ';
		$this->renderBlock('test', ['var2' => 1] + [], 'html') /* line %d% */;
		echo '

c) ';
		$this->renderBlock('test', ['hello' => 1] + [], 'html') /* line %d% */;
		echo '

';
	}


	/** {define test $var1, $var2, $var3} on line %d% */
	public function blockTest(array $__args__): void
	{
		extract($this->params);
		$var1 = $__args__[0] ?? $__args__['var1'] ?? null;
		$var2 = $__args__[1] ?? $__args__['var2'] ?? null;
		$var3 = $__args__[2] ?? $__args__['var3'] ?? null;
		unset($__args__);

		echo '	Variables ';
		echo Runtime\Filters::escapeHtmlText($var1) /* line %d% */;
		echo ', ';
		echo Runtime\Filters::escapeHtmlText($var2) /* line %d% */;
		echo ', ';
		echo Runtime\Filters::escapeHtmlText($var3) /* line %d% */;
		echo "\n";
	}
}
