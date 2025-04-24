%A%
		foreach ($iterator = $__it__ = new Core\View\Template\Essential\CachingIterator([1, 2, 3], $__it__ ?? null) as $foo) /* line 2 */ {
			echo '	<b';
			echo ($__temp__ = array_filter([$iterator->even ? 'even' : null])) ? ' class="' . Runtime\Filters::escapeHtmlAttr(implode(" ", array_unique($__temp__))) . '"' : "" /* line 3 */;
			echo '>item</b>
';

		}
		$iterator = $__it__ = $__it__->getParent();

		echo "\n";
		foreach ($iterator = $__it__ = new Core\View\Template\Essential\CachingIterator([1, 2, 3], $__it__ ?? null) as $foo) /* line 6 */ {
			echo '<p';
			echo ($__temp__ = array_filter([$iterator->even ? 'even' : null])) ? ' class="' . Runtime\Filters::escapeHtmlAttr(implode(" ", array_unique($__temp__))) . '"' : "" /* line 6 */;
			echo '>';
			echo Runtime\Filters::escapeHtmlText($foo) /* line 6 */;
			echo '</p>
';

		}
		$iterator = $__it__ = $__it__->getParent();

		echo '
<p';
		echo ($__temp__ = array_filter(['foo', false ? 'first' : null, 'odd', true ? 'foo' : 'bar'])) ? ' class="' . Runtime\Filters::escapeHtmlAttr(implode(" ", array_unique($__temp__))) . '"' : "" /* line 8 */;
		echo '>n:class</p>

<p';
		echo ($__temp__ = array_filter([false ? 'first' : null])) ? ' class="' . Runtime\Filters::escapeHtmlAttr(implode(" ", array_unique($__temp__))) . '"' : "" /* line 10 */;
		echo '>n:class empty</p>

<p';
		echo ($__temp__ = array_filter([true ? 'bem--modifier' : null])) ? ' class="' . Runtime\Filters::escapeHtmlAttr(implode(" ", array_unique($__temp__))) . '"' : "" /* line 12 */;
		echo '>n:class with BEM</p>
';
%A%
