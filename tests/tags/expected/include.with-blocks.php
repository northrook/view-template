<?php
%A%
final class Template%a% extends Core\View\Template\Runtime\Template
{
	public const Source = 'main';


	public function main(array $__args__): void
	{
%A%
		$this->createTemplate(true ? 'inc' : '', $this->params, 'includeblock')->renderToContentType('html') /* line %d% */;
		echo "\n";
		$this->renderBlock('test', [], 'html') /* line %d% */;
	}
}
