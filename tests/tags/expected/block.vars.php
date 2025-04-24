<?php
%A%
		echo Runtime\Filters::escapeHtmlText($var) /* line %d% */;
		echo '


';
		$this->renderBlock('b', get_defined_vars()) /* line %d% */;
		echo "\n";
		echo Runtime\Filters::escapeHtmlText($var) /* line %d% */;
		echo '


';
		ob_start(fn() => '') /* line %d% */;
		try {
			(function () {
				extract(func_get_arg(0));
				echo '	';
				echo Runtime\Filters::escapeHtmlText($var) /* line %d% */;
				echo "\n";
				$var = 'blockmod' /* line %d% */;
			})(get_defined_vars());
		} finally {
			$__filter__ = new Runtime\FilterInfo('html');
			echo Runtime\Filters::convertTo($__filter__, 'html', $this->filters->filterContent('trim', $__filter__, ob_get_clean()));
		}
		echo "\n";
		echo Runtime\Filters::escapeHtmlText($var) /* line %d% */;
		echo '


';
		ob_start(fn() => '') /* line %d% */;
		try {
			(function () {
				extract(func_get_arg(0));
				echo '	';
				echo Runtime\Filters::escapeHtmlText($var) /* line %d% */;
				echo "\n";
				$var = 'block' /* line %d% */;
			})(get_defined_vars());
		} finally {
			$__filter__ = new Runtime\FilterInfo('html');
			echo ob_get_clean();
		}
		echo "\n";
		echo Runtime\Filters::escapeHtmlText($var) /* line %d% */;
	}


	public function prepare(): array
	{
		extract($this->params);

		$var = 'a' /* line %d% */;
		return get_defined_vars();
	}


	/** {define a} on line %d% */
	public function blockA(array $__args__): void
	{
		extract($this->params);
		extract($__args__);
		unset($__args__);

		echo '	';
		echo Runtime\Filters::escapeHtmlText($var) /* line %d% */;
		echo "\n";
		$var = 'define' /* line %d% */;
	}


	/** {block b} on line %d% */
	public function blockB(array $__args__): void
	{
		extract($this->params);
		extract($__args__);
		unset($__args__);

		echo '	';
		echo Runtime\Filters::escapeHtmlText($var) /* line %d% */;
		echo "\n";
		$var = 'blocknamed' /* line %d% */;
	}
%A%
