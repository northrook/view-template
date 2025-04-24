<?php
%A%
		foreach ($iterator = $__it__ = new Core\View\Template\Essential\CachingIterator([0, 1, 2, 3], $__it__ ?? null) as $item) /* line 2 */ {
			if ($item % 2) /* line 3 */ continue;
			echo '	';
			echo Runtime\Filters::escapeHtmlText($iterator->counter) /* line 4 */;
			echo '. item
';

		}
		$iterator = $__it__ = $__it__->getParent();

		echo '
---

';
		foreach ($iterator = $__it__ = new Core\View\Template\Essential\CachingIterator([0, 1, 2, 3], $__it__ ?? null) as $item) /* line 9 */ {
			if ($item % 2) /* line 10 */ {
				$iterator->skipRound();
				continue;
			}
			echo '	';
			echo Runtime\Filters::escapeHtmlText($iterator->counter) /* line 11 */;
			echo '. item
';

		}
		$iterator = $__it__ = $__it__->getParent();

		echo '
---

';
		foreach ($iterator = $__it__ = new Core\View\Template\Essential\CachingIterator([0, 1, 2, 3], $__it__ ?? null) as $item) /* line 16 */ {
			if ($item % 2) /* line 17 */ break;
			echo '	';
			echo Runtime\Filters::escapeHtmlText($iterator->counter) /* line 18 */;
			echo '. item
';

		}
		$iterator = $__it__ = $__it__->getParent();
%A%
