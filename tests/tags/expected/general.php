<?php

use Core\View\Template\Runtime as $__exception__;

/** source: %A% */
final class Template%a% extends Core\View\Template\Runtime\Template
{
	public const Source = '%a%.latte';

	public const Blocks = [
		['menu' => 'blockMenu'],
	];


	public function main(array $__args__): void
	{
		extract($__args__);
		unset($__args__);

		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<input/> <input /> <input>

<input checked> <input checked="checked">

<button></button>

{ test} {"test} {\'test}

';
		echo Runtime\Filters::escapeHtmlText((string) (bool) (float) (int) (array) 10) /* line %d% */;
		echo '


';
		foreach ($iterator = $__it__ = new Core\View\Template\Essential\CachingIterator([true], $__it__ ?? null) as $foo) /* line %d% */ {
			foreach ($iterator = $__it__ = new Core\View\Template\Essential\CachingIterator($people, $__it__ ?? null) as $person) /* line %d% */ {
				if ($iterator->isFirst()) /* line %d% */ {
					echo '	<ul>';
				}
				echo '
	<li id="item-';
				echo Runtime\Filters::escapeHtmlAttr($iterator->getCounter()) /* line %d% */;
				echo '" class="';
				echo Runtime\Filters::escapeHtmlAttr($iterator->isOdd() ? 'odd' : 'even') /* line %d% */;
				echo '">';
				echo Runtime\Filters::escapeHtmlText($person) /* line %d% */;
				echo '</li>
	';
				if ($iterator->isLast()) /* line %d% */ {
					echo '</ul>';
				}
				echo "\n";

			}
			$iterator = $__it__ = $__it__->getParent();


		}
		$iterator = $__it__ = $__it__->getParent();

		echo '

';
		$counter = 0 /* line %d% */;
		$this->renderBlock('menu', get_defined_vars()) /* line %d% */;
	}


	public function prepare(): array
	{
		extract($this->params);

		if (!$this->getReferringTemplate() || $this->getReferenceType() === 'extends') {
			foreach (array_intersect_key(['foo' => '14', 'person' => '15', 'item' => '26'], $this->params) as $__var__ => $__line__) {
				trigger_error("Variable \$$__var__ overwritten in foreach on line $__line__");
			}
		}
		return get_defined_vars();
	}


	/** {block menu} on line %d% */
	public function blockMenu(array $__args__): void
	{
		extract($this->params);
		extract($__args__);
		unset($__args__);

		echo '<ul>
';
		foreach ($iterator = $__it__ = new Core\View\Template\Essential\CachingIterator($menu, $__it__ ?? null) as $item) /* line %d% */ {
			echo '	<li>';
			echo Runtime\Filters::escapeHtmlText($counter++) /* line %d% */;
			echo ' ';
			if (is_array($item)) /* line %d% */ {
				echo ' ';
				$this->renderBlock('menu', ['menu' => $item] + get_defined_vars(), 'html') /* line %d% */;
				echo ' ';
			} else /* line %d% */ {
				echo Runtime\Filters::escapeHtmlText($item) /* line %d% */;
			}
			echo '</li>
';

		}
		$iterator = $__it__ = $__it__->getParent();

		echo '</ul>
';
	}
}
