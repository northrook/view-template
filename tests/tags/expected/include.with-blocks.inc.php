<?php
%A%
final class Template%a% extends Core\View\Template\Runtime\Template
{
	public const Source = 'inc';

	public const Blocks = [
		['test' => 'blockTest'],
	];


	public function main(array $__args__): void
	{
		echo "\n";
	}


	/** {define test} on line %d% */
	public function blockTest(array $__args__): void
	{
		extract($this->params);
		extract($__args__);
		unset($__args__);

		echo '	Parent: ';
		echo Runtime\Filters::escapeHtmlText(basename($this->getReferringTemplate()->getName())) /* line %d% */;
		echo '/';
		echo Runtime\Filters::escapeHtmlText($this->getReferenceType()) /* line %d% */;
		echo "\n";
	}
}
