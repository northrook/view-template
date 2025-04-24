<?php
%A%
final class Template%a% extends Core\View\Template\Runtime\Template
{
	public const Source = 'main';


	public function main(array $__args__): void
	{
%A%
		$this->renderBlock('test', [], 'html') /* line 3 */;
	}


	public function prepare(): array
	{
		extract($this->params);

		$this->createTemplate('inc', $this->params, "import")->render() /* line 2 */;
		return get_defined_vars();
	}
}
