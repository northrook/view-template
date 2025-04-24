<?php
%A%
final class Template%a% extends Core\View\Template\Runtime\Template
{
	public const Source = 'main';

	public const Blocks = [
		['content' => 'blockContent'],
	];


	public function main(array $__args__): void
	{
%A%
		$this->renderBlock('content', get_defined_vars()) /* line %d% */;
	}


	public function prepare(): array
	{
		extract($this->params);

		$this->parentName = true ? $ext : 'undefined';
		return get_defined_vars();
	}


	/** {block content} on line %d% */
	public function blockContent(array $__args__): void
	{
		echo '	Content
';
	}
}
