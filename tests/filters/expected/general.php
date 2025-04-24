<?php
%A%
final class Template%a% extends Core\View\Template\Runtime\Template
{
	public const Source = '%a%.latte';


	public function main(array $__args__): void
	{
%A%
		echo '<ul>
	<li>';
		echo Runtime\Filters::escapeHtmlText(($this->filters->h2)(($this->filters->h1)($hello))) /* line %d% */;
		echo '</li>
	<li>';
		echo ($this->filters->h2)(($this->filters->h1)($hello)) /* line %d% */;
		echo '</li>
	<li>';
		echo Runtime\Filters::escapeHtmlText(($this->filters->h1)(($this->filters->h2)($hello))) /* line %d% */;
		echo '</li>
</ul>

<ul>
	<li>';
		echo Runtime\Filters::escapeHtmlText(($this->filters->types)((int) $hello * 0, 0, 0.0, '0')) /* line %d% */;
		echo '</li>
	<li>';
		echo Runtime\Filters::escapeHtmlText(($this->filters->types)((int) $hello * 1, 1, '1')) /* line %d% */;
		echo '</li>
	<li>';
		echo Runtime\Filters::escapeHtmlText(($this->filters->types)($hello, true, null, false)) /* line %d% */;
		echo '</li>
	<li>';
		echo Runtime\Filters::escapeHtmlText(($this->filters->types)($hello, true, null, false)) /* line %d% */;
		echo '</li>
	<li>';
		echo Runtime\Filters::escapeHtmlText(($this->filters->types)($hello, '', '', "{$hello}")) /* line %d% */;
		echo '</li>
</ul>



';
		ob_start(fn() => '') /* line %d% */;
		try {
			(function () {
				extract(func_get_arg(0));
				echo '  <a   href="#"
> test</a>
A   A

<script>
// comment
alert();
</script>
';

			})(get_defined_vars());
		} finally {
			$__filter__ = new Runtime\FilterInfo('html');
			echo Runtime\Filters::convertTo($__filter__, 'html', $this->filters->filterContent('strip', $__filter__, ob_get_clean()));
		}
		echo '


<p>
Nested blocks: ';
		ob_start(fn() => '') /* line %d% */;
		try {
			(function () {
				extract(func_get_arg(0));
				echo ' Outer   ';
				ob_start(fn() => '') /* line %d% */;
				try {
					(function () {
						extract(func_get_arg(0));
						echo ' Inner Block ';

					})(get_defined_vars());
				} finally {
					$__filter__ = new Runtime\FilterInfo('html');
					echo Runtime\Filters::convertTo($__filter__, 'html', $this->filters->filterContent('upper', $__filter__, $this->filters->filterContent('striphtml', $__filter__, ob_get_clean())));
				}
				echo '  Block ';

			})(get_defined_vars());
		} finally {
			$__filter__ = new Runtime\FilterInfo('html');
			echo Runtime\Filters::convertTo($__filter__, 'html', $this->filters->filterContent('truncate', $__filter__, $this->filters->filterContent('striphtml', $__filter__, ob_get_clean()), 20));
		}
		echo '
</p>

Breaklines: ';
		echo Runtime\Filters::escapeHtmlText(($this->filters->breakLines)('hello
bar')) /* line %d% */;
		echo "\n";
	}
}
