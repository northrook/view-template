<?php
%A%
final class Template%a% extends Core\View\Template\Runtime\Template
{
	public const Blocks = [
		['test' => 'blockTest', 'true' => 'blockTrue'],
	];


	public function main(array $__args__): void
	{
		extract($__args__);
		unset($__args__);

		echo '

';
		$this->renderBlock('test', ['var' => 20] + get_defined_vars(), 'html') /* line 7 */;
		echo '

';
		$this->renderBlock('true', get_defined_vars(), 'html') /* line 10 */;
	}


	public function prepare(): array
	{
		extract($this->params);

		$var = 10 /* line 1 */;
		return get_defined_vars();
	}


	/** {define test} on line 3 */
	public function blockTest(array $__args__): void
	{
		extract($this->params);
		extract($__args__);
		unset($__args__);

		echo '	This is definition #';
		echo Runtime\Filters::escapeHtmlText($var) /* line 4 */;
		echo "\n";
	}


	/** {define true} on line 9 */
	public function blockTrue(array $__args__): void
	{
		echo 'true';
	}
}
