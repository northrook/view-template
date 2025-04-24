<?php
%A%
final class Template%a% extends Core\View\Template\Runtime\Template
{
	public const Source = 'main';


	public function main(array $__args__): void
	{
		echo 'This should be erased
';
	}


	public function prepare(): array
	{
		extract($this->params);

		$this->parentName = 'parent';
		$foo = 1 /* line 3 */;
		return get_defined_vars();
	}
}
