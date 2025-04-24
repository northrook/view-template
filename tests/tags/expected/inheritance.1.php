<?php
%A%
final class Template%a% extends Core\View\Template\Runtime\Template
{
	public const Source = 'main';

	public const Blocks = [
		['title' => 'blockTitle', 'content' => 'blockContent'],
	];


	public function main(array $__args__): void
	{
%A%
		$this->createTemplate('inc', $this->params, 'includeblock')->renderToContentType('html') /* line %d% */;
		echo "\n";
		$this->renderBlock('title', get_defined_vars()) /* line %d% */;
		echo '

';
		$this->renderBlock('content', get_defined_vars()) /* line %d% */;
	}


	public function prepare(): array
	{
		extract($this->params);

		if (!$this->getReferringTemplate() || $this->getReferenceType() === 'extends') {
			foreach (array_intersect_key(['person' => '11'], $this->params) as $__var__ => $__line__) {
				trigger_error("Variable \$$__var__ overwritten in foreach on line $__line__");
			}
		}
		$this->parentName = 'parent';
		$this->createTemplate('inc', $this->params, "import")->render() /* line %d% */;
		return get_defined_vars();
	}


	/** {block title} on line %d% */
	public function blockTitle(array $__args__): void
	{
		extract($this->params);
		extract($__args__);
		unset($__args__);

		echo 'Homepage | ';
		$this->renderBlockParent('title', get_defined_vars()) /* line %d% */;
		$this->renderBlockParent('title', get_defined_vars()) /* line %d% */;
	}


	/** {block content} on line %d% */
	public function blockContent(array $__args__): void
	{
		extract($this->params);
		extract($__args__);
		unset($__args__);

		echo '	<ul>
';
		foreach ($people as $person) /* line %d% */ {
			echo '		<li>';
			echo Runtime\Filters::escapeHtmlText($person) /* line %d% */;
			echo '</li>
';

		}

		echo '	</ul>
	Parent: ';
		echo Runtime\Filters::escapeHtmlText(gettype($this->getReferringTemplate())) /* line %d% */;
		echo "\n";
	}
}
