<?php
%A%
		foreach (['a', 'b'] as $item) /* line 2 */ {
			echo '	item
';

		}

		echo '
---

';
		foreach ($iterator = $__it__ = new Core\View\Template\Essential\CachingIterator(['a', 'b'], $__it__ ?? null) as $item) /* line 8 */ {
			echo '	';
			echo Runtime\Filters::escapeHtmlText($iterator->counter) /* line 9 */;
			echo "\n";

		}
		$iterator = $__it__ = $__it__->getParent();

		echo Runtime\Filters::escapeHtmlText($iterator === null ? 'is null' : null) /* line 11 */;
		echo '

---

';
		foreach (['a', 'b'] as [$a, , [$b, [$c]]]) /* line 15 */ {
		}
%A%
