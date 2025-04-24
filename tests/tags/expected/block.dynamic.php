<?php
%A%
final class Template%a% extends Core\View\Template\Runtime\Template
{
	public const Blocks = [
		['static' => 'blockStatic'],
	];


	public function main(array $__args__): void
	{
		extract($__args__);
		unset($__args__);

		echo "\n";
		$this->renderBlock('static', get_defined_vars()) /* line %d% */;
		echo '

';
		foreach ($iterator = $__it__ = new Core\View\Template\Essential\CachingIterator(['dynamic', 'static'], $__it__ ?? null) as $name) /* line %d% */ {
			$this->addBlock($__name__ = $name, 'html', [[$this, 'blockName']], 0);
			$this->renderBlock($__name__, get_defined_vars());
		}
		$iterator = $__it__ = $__it__->getParent();

		echo "\n";
		$this->renderBlock('dynamic', ['var' => 20] + [], 'html') /* line %d% */;
		echo "\n";
		$this->renderBlock('static', ['var' => 30] + get_defined_vars(), 'html') /* line %d% */;
		echo "\n";
		$this->renderBlock($name . '', ['var' => 40] + [], 'html') /* line %d% */;
		echo "\n";
		$this->addBlock($__name__ = "word{$name}", 'html', [[$this, 'blockWord_name']], 0);
		$this->renderBlock($__name__, get_defined_vars());
		echo '

';
		$this->addBlock($__name__ = "strip{$name}", 'html', [[$this, 'blockStrip_name']], 0);
		$this->renderBlock($__name__, get_defined_vars(), function ($s, $type) {
			$__filter__ = new Runtime\FilterInfo($type);
			return Runtime\Filters::convertTo($__filter__, 'html', $this->filters->filterContent('striptags', $__filter__, $s));
		});
		echo '

';
		$this->addBlock($__name__ = rand() < 5 ? 'a' : 'b', 'html', [[$this, 'blockRand_5_a_b']], 0);
		$this->renderBlock($__name__, get_defined_vars());
	}


	public function prepare(): array
	{
		extract($this->params);

		if (!$this->getReferringTemplate() || $this->getReferenceType() === 'extends') {
			foreach (array_intersect_key(['name' => '8'], $this->params) as $__var__ => $__line__) {
				trigger_error("Variable \$$__var__ overwritten in foreach on line $__line__");
			}
		}
		$var = 10 /* line %d% */;
		return get_defined_vars();
	}


	/** {block static} on line %d% */
	public function blockStatic(array $__args__): void
	{
		extract($this->params);
		extract($__args__);
		unset($__args__);

		echo '	Static block #';
		echo Runtime\Filters::escapeHtmlText($var) /* line %d% */;
		echo "\n";
	}


	/** {block $name} on line %d% */
	public function blockName(array $__args__): void
	{
		extract($__args__);
		unset($__args__);

		echo '		Dynamic block #';
		echo Runtime\Filters::escapeHtmlText($var) /* line %d% */;
		echo "\n";
	}


	/** {block "word$name"} on line %d% */
	public function blockWord_name(array $__args__): void
	{
		if (false) /* line %d% */ {
			echo '<div></div>';
		}
	}


	/** {block "strip$name"|striptags} on line %d% */
	public function blockStrip_name(array $__args__): void
	{
		echo '<span>hello</span>';
	}


	/** {block rand() < 5 ? a : b} on line %d% */
	public function blockRand_5_a_b(array $__args__): void
	{
		echo ' expression ';
	}
}
