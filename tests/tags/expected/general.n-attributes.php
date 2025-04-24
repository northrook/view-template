<?php
%A%
final class Template%a% extends Core\View\Template\Runtime\Template
{
	public const Source = '%a%.latte';

	public const Blocks = [
		['bl' => 'blockBl'],
	];


	public function main(array $__args__): void
	{
%A%
		echo '
<p';
		$__temp__ = ['title' => 'hello', 'lang' => isset($lang) ? $lang : null];
		Core\View\Template\Essential\Nodes\NAttrNode::attrs(is_array($__temp__[0] ?? null) ? $__temp__[0] : $__temp__, false) /* line %d% */;
		echo '> </p>

<p';
		$__temp__ = [['title' => 'hello']];
		Core\View\Template\Essential\Nodes\NAttrNode::attrs(is_array($__temp__[0] ?? null) ? $__temp__[0] : $__temp__, false) /* line %d% */;
		echo '> </p>

';
		foreach ($iterator = $__it__ = new Core\View\Template\Essential\CachingIterator([1, 2, 3], $__it__ ?? null) as $foo) /* line %d% */ {
			echo '	<b';
			echo ($__temp__ = array_filter([$iterator->even ? 'even' : null])) ? ' class="' . Runtime\Filters::escapeHtmlAttr(implode(" ", array_unique($__temp__))) . '"' : "" /* line %d% */;
			echo '>item</b>
';

		}
		$iterator = $__it__ = $__it__->getParent();

		echo "\n";
		foreach ($iterator = $__it__ = new Core\View\Template\Essential\CachingIterator([1, 2, 3], $__it__ ?? null) as $foo) /* line %d% */ {
			echo '<p';
			echo ($__temp__ = array_filter([$iterator->even ? 'even' : null])) ? ' class="' . Runtime\Filters::escapeHtmlAttr(implode(" ", array_unique($__temp__))) . '"' : "" /* line %d% */;
			echo '>';
			echo Runtime\Filters::escapeHtmlText($foo) /* line %d% */;
			echo '</p>
';

		}
		$iterator = $__it__ = $__it__->getParent();

		echo '
<p';
		echo ($__temp__ = array_filter(['foo', false ? 'first' : null, 'odd', true ? 'foo' : 'bar'])) ? ' class="' . Runtime\Filters::escapeHtmlAttr(implode(" ", array_unique($__temp__))) . '"' : "" /* line %d% */;
		echo '>n:class</p>

<p';
		echo ($__temp__ = array_filter([false ? 'first' : null])) ? ' class="' . Runtime\Filters::escapeHtmlAttr(implode(" ", array_unique($__temp__))) . '"' : "" /* line %d% */;
		echo '>n:class empty</p>
<p';
		echo ($__temp__ = array_filter([true ? 'bem--modifier' : null])) ? ' class="' . Runtime\Filters::escapeHtmlAttr(implode(" ", array_unique($__temp__))) . '"' : "" /* line %d% */;
		echo '>n:class with BEM</p>


';
		$this->renderBlock('bl', get_defined_vars()) /* line %d% */;
		echo '




<ul title="foreach">
';
		foreach ($people as $person) /* line %d% */ {
			echo '	<li>';
			echo Runtime\Filters::escapeHtmlText($person) /* line %d% */;
			echo '</li>
';

		}

		echo '</ul>

<ul title="foreach">
';
		foreach ($people as $person) /* line %d% */ {
			echo '	<li>
		';
			echo Runtime\Filters::escapeHtmlText($person) /* line %d% */;
			echo '
	</li>
';

		}

		echo '</ul>

<ul title="inner foreach">
	<li>
';
		foreach ($people as $person) /* line %d% */ {
			echo '		';
			echo Runtime\Filters::escapeHtmlText($person) /* line %d% */;
			echo "\n";

		}

		echo '	</li>
</ul>

<ul title="tag if">
	';
		$__tag__[0] = '';
		if (true) /* line %d% */ {
			echo '<';
			echo $__temp__ = 'li' /* line %d% */;
			$__tag__[0] = '</' . $__temp__ . '>' . $__tag__[0];
			echo '>';
		}
		echo '
		';
		echo Runtime\Filters::escapeHtmlText($person) /* line %d% */;
		echo '
	';
		echo $__tag__[0];
		echo '
</ul>

<ul title="for">
';
		for ($i = 0;
		$i < 3;
		$i++) /* line %d% */ {
			echo '	<li>';
			echo Runtime\Filters::escapeHtmlText($i) /* line %d% */;
			echo '</li>
';

		}
		echo '</ul>

<ul title="white">
';
		while (--$i > 0) /* line %d% */ {
			echo '	<li>';
			echo Runtime\Filters::escapeHtmlText($i) /* line %d% */;
			echo '</li>
';

		}
		echo '</ul>

';
		if (true) /* line %d% */ {
			echo '<p>
	<div><p>true</div>
</p>
';
		}
		echo "\n";
		if (true) /* line %d% */ {
			echo '<p>
	<div><p>true</p></div>
</p>
';
		}
		echo "\n";
		if (false) /* line %d% */ {
			echo '<p>
	<div><p>false</div>
</p>
';
		}
		echo "\n";
		if (false) /* line %d% */ {
			echo '<p>
	<div><p>false</p></div>
</p>
';
		}
		echo "\n";
		if (strlen('{$name}') > 5) /* line %d% */ {
			echo '<p>noLatte</p>
';
		}
		echo '
<ul title="if + foreach">
';
		foreach ($people as $person) /* line %d% */ {
			if (strlen($person) === 4) /* line %d% */ {
				echo '	<li>';
				echo Runtime\Filters::escapeHtmlText($person) /* line %d% */;
				echo '</li>
';
			}

		}

		echo '</ul>

<ul title="if + inner-if + inner-foreach">
';
		if (empty($iterator)) /* line %d% */ {
			echo '	<li>';
			foreach ($people as $person) /* line %d% */ {
				if (strlen($person) === 4) /* line %d% */ {
					echo Runtime\Filters::escapeHtmlText($person) /* line %d% */;
				}

			}

			echo '</li>
';
		}
		echo '</ul>

<ul title="inner-if + inner-foreach">
';
		foreach ($people as $person) /* line %d% */ {
			if (strlen($person) === 4) /* line %d% */ {
				echo '	<li>';
				echo Runtime\Filters::escapeHtmlText(($this->filters->lower)($person)) /* line %d% */;
				echo '</li>
';
			}

		}

		echo '</ul>

';
		$__tag__[1] = '';
		if (true) /* line %d% */ {
			echo '<';
			echo $__temp__ = 'b' /* line %d% */;
			$__tag__[1] = '</' . $__temp__ . '>' . $__tag__[1];
			echo '>';
		}
		echo 'bold';
		echo $__tag__[1];
		echo ' ';
		$__tag__[2] = '';
		if (false) /* line %d% */ {
			echo '<';
			echo $__temp__ = 'b' /* line %d% */;
			$__tag__[2] = '</' . $__temp__ . '>' . $__tag__[2];
			echo '>';
		}
		echo 'normal';
		echo $__tag__[2];
		echo '

';
		$__tag__[3] = '';
		if (true) /* line %d% */ {
			echo '<';
			echo $__temp__ = 'b' /* line %d% */;
			$__tag__[3] = '</' . $__temp__ . '>' . $__tag__[3];
			echo ($__temp__ = array_filter(['first'])) ? ' class="' . Runtime\Filters::escapeHtmlAttr(implode(" ", array_unique($__temp__))) . '"' : "" /* line %d% */;
			echo '>';
		}
		echo 'bold';
		echo $__tag__[3];
		echo '

<meta>
';
		if (true) /* line %d% */ {
			echo '<meta>
';
		}
		echo '<meta>

';
		foreach ([0] as $foo) /* line %d% */ {
			if (1) /* line %d% */ {
				$__tag__[4] = '';
				foreach ([1] as $foo) /* line %d% */ {
					if (0) /* line %d% */ {
						echo '<';
						echo $__temp__ = 'span' /* line %d% */;
						$__tag__[4] = '</' . $__temp__ . '>' . $__tag__[4];
						echo '>';
					}

				}

				foreach ([2] as $foo) /* line %d% */ {
					if (2) /* line %d% */ {
						echo 'Hello';
					}

				}

				echo $__tag__[4];
				echo "\n";
			}

		}

		echo '

';
		$__tag__[5] = '';
		if (true) /* line %d% */ {
			echo '<';
			echo $__temp__ = 'div' /* line %d% */;
			$__tag__[5] = '</' . $__temp__ . '>' . $__tag__[5];
			echo '>';
		}
		echo "\n";
		$__try__[6] = [$__it__ ?? null];
		ob_start(fn() => '');
		try /* line %d% */ {
			echo '	';
			$__tag__[7] = '';
			if (false) /* line %d% */ {
				echo '<';
				echo $__temp__ = 'span' /* line %d% */;
				$__tag__[7] = '</' . $__temp__ . '>' . $__tag__[7];
				echo '>';
			}
			echo "\n";
			throw new \Core\View\Template\Exception\RollbackException;
			echo '	';
			echo $__tag__[7];
			echo "\n";

		} catch (Throwable $__exception__) {
			ob_clean();
			if ( !( $__exception__ instanceof \Core\View\Template\Exception\RollbackException) && isset($this->global->coreExceptionHandler)) {
				($this->global->coreExceptionHandler)($__exception__, $this);
			}
		} finally {
			echo ob_get_clean();
			$iterator = $__it__ = $__try__[6][0];
		}echo $__tag__[5];
		echo '


<ul title="foreach break">
';
		foreach ($people as $person) /* line %d% */ {
			echo '	<li>';
			try {
				echo Runtime\Filters::escapeHtmlText($person) /* line 107 */;
				if (true) /* line 107 */ break;
			} finally {
				echo '</li>';
			}
			echo "\n";

		}

		echo '</ul>

<ul title="foreach continue">
';
		foreach ($people as $person) /* line %d% */ {
			echo '	<li>';
			try {
				echo Runtime\Filters::escapeHtmlText($person) /* line 111 */;
				if (true) /* line 111 */ continue;
			} finally {
				echo '</li>';
			}
			echo "\n";

		}

		echo '</ul>


<ul title="inner foreach break">
	<li>';
		foreach ($people as $person) /* line %d% */ {
			echo Runtime\Filters::escapeHtmlText($person) /* line %d% */;
			if (true) /* line %d% */ break;

		}

		echo '</li>
</ul>

<ul title="inner foreach continue">
	<li>';
		foreach ($people as $person) /* line %d% */ {
			echo Runtime\Filters::escapeHtmlText($person) /* line %d% */;
			if (true) /* line %d% */ continue;

		}

		echo '</li>
</ul>


';
		ob_start(fn() => '');
		try {
			$__tag__[8] = '';
			echo '<';
			echo $__temp__ = Runtime\Filters::safeTag(Core\View\Template\Essential\Nodes\NTagNode::check('div', 'span', false)) /* line %d% */;
			$__tag__[8] = '</' . $__temp__ . '>' . $__tag__[8];
			echo '>';
			ob_start();
			try {
				echo 'n:tag & n:ifcontent';

			} finally {
				$__if_c__[0] = rtrim(ob_get_flush()) === '';
			}
			echo $__tag__[8];
			echo "\n";

		} finally {
			if ($__if_c__[0] ?? null) {
				ob_end_clean();

			} else {
				echo ob_get_clean();
			}
		}
		ob_start(fn() => '');
		try {
			$__tag__[9] = '';
			echo '<';
			echo $__temp__ = Runtime\Filters::safeTag(Core\View\Template\Essential\Nodes\NTagNode::check('div', 'span', false)) /* line %d% */;
			$__tag__[9] = '</' . $__temp__ . '>' . $__tag__[9];
			echo '>';
			ob_start();
			try {

			} finally {
				$__if_c__[1] = rtrim(ob_get_flush()) === '';
			}
			echo $__tag__[9];
			echo "\n";

		} finally {
			if ($__if_c__[1] ?? null) {
				ob_end_clean();

			} else {
				echo ob_get_clean();
			}
		}
		echo "\n";
		if (1) /* line %d% */ {
			$__tag__[10] = '';
			echo '<';
			echo $__temp__ = Runtime\Filters::safeTag(Core\View\Template\Essential\Nodes\NTagNode::check('div', 'span', false)) /* line %d% */;
			$__tag__[10] = '</' . $__temp__ . '>' . $__tag__[10];
			echo '>n:tag & n:if=1';
			echo $__tag__[10];
			echo "\n";
		}
		if (0) /* line %d% */ {
			$__tag__[11] = '';
			echo '<';
			echo $__temp__ = Runtime\Filters::safeTag(Core\View\Template\Essential\Nodes\NTagNode::check('div', 'span', false)) /* line %d% */;
			$__tag__[11] = '</' . $__temp__ . '>' . $__tag__[11];
			echo '>n:tag & n:if=0';
			echo $__tag__[11];
			echo "\n";
		}
	}


	public function prepare(): array
	{
		extract($this->params);

		if (!$this->getReferringTemplate() || $this->getReferenceType() === 'extends') {
			foreach (array_intersect_key(['foo' => '6, 10, 94, 94, 94', 'person' => '19, 27, 31, 37, 75, 79, 82, 107, 111, 116, 120'], $this->params) as $__var__ => $__line__) {
				trigger_error("Variable \$$__var__ overwritten in foreach on line $__line__");
			}
		}
		return get_defined_vars();
	}


	/** n:block="bl" on line 18 */
	public function blockBl(array $__args__): void
	{
		extract($this->params);
		extract($__args__);
		unset($__args__);

		echo '<ul title="block + if + foreach">
';
		foreach ($people as $person) /* line %d% */ {
			if (strlen($person) === 4) /* line %d% */ {
				echo '	<li>';
				echo Runtime\Filters::escapeHtmlText($person) /* line %d% */;
				echo '</li>
';
			}

		}

		echo '</ul>
';
	}
}
