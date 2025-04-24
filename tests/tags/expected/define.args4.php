<?php
%A%
final class Template%a% extends Core\View\Template\Runtime\Template
{
	public const Source = 'main';


	public function main(array $__args__): void
	{
		extract($__args__);
		unset($__args__);

		echo '
named arguments import

';
		$this->createTemplate('import.latte', $this->params, "import")->render() /* line %d% */;
		echo '
a) ';
		$this->renderBlock('test', [1, 'var1' => 2] + [], 'html') /* line %d% */;
		echo '

b) ';
		$this->renderBlock('test', ['var2' => 1] + [], 'html') /* line %d% */;
		echo '

c) ';
		$this->renderBlock('test', ['hello' => 1] + [], 'html') /* line %d% */;
	}
}
