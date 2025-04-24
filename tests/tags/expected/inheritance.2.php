<?php
%A%
final class Template%a% extends Core\View\Template\Runtime\Template
{
	public const Source = 'main';

	public const Blocks = [
		['content' => 'blockContent', 'title' => 'blockTitle', 'sidebar' => 'blockSidebar'],
	];


	public function main(array $__args__): void
	{
%A%
		echo "\n";
		$this->renderBlock('content', get_defined_vars()) /* line %d% */;
		echo "\n";
		$this->renderBlock('sidebar', get_defined_vars()) /* line %d% */;
	}


	public function prepare(): array
	{
		extract($this->params);

		if (!$this->getReferringTemplate() || $this->getReferenceType() === 'extends') {
			foreach (array_intersect_key(['person' => '8'], $this->params) as $__var__ => $__line__) {
				trigger_error("Variable \$$__var__ overwritten in foreach on line $__line__");
			}
		}
		$this->parentName = 'parent';
		return get_defined_vars();
	}


	/** {block content} on line %d% */
	public function blockContent(array $__args__): void
	{
		extract($this->params);
		extract($__args__);
		unset($__args__);

		echo '	<h1>';
		$this->renderBlock('title', get_defined_vars()) /* line %d% */;
		echo '</h1>

	<ul>
';
		foreach ($people as $person) /* line %d% */ {
			echo '		<li>';
			echo Runtime\Filters::escapeHtmlText($person) /* line %d% */;
			echo '</li>
';

		}

		echo '	</ul>
';
	}


	/** {block title} on line %d% */
	public function blockTitle(array $__args__): void
	{
		echo 'Homepage ';
	}


	/** {block sidebar} on line %d% */
	public function blockSidebar(array $__args__): void
	{
	}
}
