<?php
%A%
		foreach ([1, 1, 2, 3, 3, 3] as $i) /* line 2 */ {
			echo ' ';
			if (($__loc__[0] ?? null) !== ($__temp__ = [$i])) {
				$__loc__[0] = $__temp__;
				echo ' ';
				echo Runtime\Filters::escapeHtmlText($i) /* line 2 */;
				echo ' ';

			}

			echo ' ';
			if (($__loc__[1] ?? null) !== ($__temp__ = ['a', 'b'])) {
				$__loc__[1] = $__temp__;
				echo ' const ';

			}

			echo ' ';

		}

		echo '

--

';
		foreach ([1, 1, 2, 3, 3, 3] as $i) /* line 6 */ {
			echo ' ';
			if (($__loc__[2] ?? null) !== ($__temp__ = [$i])) {
				$__loc__[2] = $__temp__;
				echo ' ';
				echo Runtime\Filters::escapeHtmlText($i) /* line 6 */;
				echo ' ';

			} else /* line 6 */ {
				echo ' else ';

			}

			echo ' ';

		}

		echo '

--

';
		foreach ([1, 1, 2, 3, 3, 3] as $i) /* line 10 */ {
			echo ' ';
			ob_start(fn() => '');
			try /* line 10 */ {
				echo ' -';
				echo Runtime\Filters::escapeHtmlText($i) /* line 10 */;
				echo '- ';

			} finally {
				$__temp__ = ob_get_clean();
			}
			if (($__loc__[3] ?? null) !== $__temp__) {
				echo $__loc__[3] = $__temp__;
			}

			echo ' ';

		}

		echo '

--

';
		foreach ([1, 1, 2, 3, 3, 3] as $i) /* line 14 */ {
			echo ' ';
			ob_start(fn() => '');
			try /* line 14 */ {
				echo ' -';
				echo Runtime\Filters::escapeHtmlText($i) /* line 14 */;
				echo '- ';

			} finally {
				$__temp__ = ob_get_clean();
			}
			if (($__loc__[4] ?? null) !== $__temp__) {
				echo $__loc__[4] = $__temp__;
			} else /* line 14 */ {
				echo ' else ';

			}

			echo ' ';

		}

		echo '

--

';
		foreach ([1, 1, 2, 3, 3, 3] as $i) /* line 18 */ {
			echo ' ';
			ob_start(fn() => '');
			try /* line 18 */ {
				echo '<span>';
				echo Runtime\Filters::escapeHtmlText($i) /* line 18 */;
				echo '</span>';
			} finally {
				$__temp__ = ob_get_clean();
			}
			if (($__loc__[5] ?? null) !== $__temp__) {
				echo $__loc__[5] = $__temp__;
			}

			echo ' ';

		}

		echo '

--

';
		foreach ([1, 1, 2, 3, 3, 3] as $i) /* line 22 */ {
			echo ' ';
			ob_start(fn() => '');
			try /* line 22 */ {
				echo '<span class="';
				echo Runtime\Filters::escapeHtmlAttr($i) /* line 22 */;
				echo '"></span>';
			} finally {
				$__temp__ = ob_get_clean();
			}
			if (($__loc__[6] ?? null) !== $__temp__) {
				echo $__loc__[6] = $__temp__;
			}

			echo ' ';

		}
%A%
