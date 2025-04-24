<?php
%A%
final class Template%a% extends Core\View\Template\Runtime\Template
{
	public const Blocks = [
		['test' => 'blockTest', 'outer' => 'blockOuter'],
	];


	public function main(array $__args__): void
	{
		extract($__args__);
		unset($__args__);

		echo '
a) ';
		$this->renderBlock('test', [1] + [], 'html') /* line %d% */;
		echo '


b) ';
		$this->renderBlock('outer', get_defined_vars(), 'html') /* line %d% */;
		echo '

';
		$var1 = 'outer' /* line %d% */;
		echo 'c) ';
		$this->renderBlock('test', [], 'html') /* line %d% */;
		echo '

d) ';
		$this->renderBlock('test', [null] + [], 'html') /* line %d% */;
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
		echo Runtime\Filters::escapeHtmlText($hello) /* line %d% */;
		echo "\n";
	}


	/** {define outer} on line %d% */
	public function blockOuter(array $__args__): void
	{
		extract($this->params);
		extract($__args__);
		unset($__args__);

		$this->renderBlock('test', ['hello'] + [], 'html') /* line %d% */;
	}
}
