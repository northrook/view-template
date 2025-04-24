<?php
%A%
final class Template%a% extends Core\View\Template\Runtime\Template
{
%A%
	public function main(array $__args__): void
	{
		extract($__args__);
		unset($__args__);

		echo "\n";
		ob_start(fn() => '') /* line %d% */;
		try {
			$this->renderBlock('bar', get_defined_vars()) /* line %d% */;
		} finally {
			$__temp__ = ob_get_length() ? new Runtime\Html(ob_get_clean()) : ob_get_clean();
		}
		$__filter__ = new Runtime\FilterInfo('html');
		$foo = $__temp__;

		echo "\n";
		$this->renderBlock('content', get_defined_vars()) /* line %d% */;
	}


	public function prepare(): array
	{
		extract($this->params);

		$this->parentName = 'layout.latte';
		return get_defined_vars();
	}
%A%
}
