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

		echo "\n";
		$this->renderBlock('test', [1] + [], 'html') /* line %d% */;
	}


	/** {define test $var1, ?stdClass $var2, \C\B|null $var3} on line %d% */
	public function blockTest(array $__args__): void
	{
	}
}
