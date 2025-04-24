<?php
%A%
final class Template%a% extends Core\View\Template\Runtime\Template
{

	public function main(array $__args__): void
	{
%A%
		echo '<span title="';
		echo Runtime\Filters::escapeHtmlAttr($x) /* line %d% */;
		echo '" class="';
		echo Runtime\Filters::escapeHtmlAttr($x) /* line %d% */;
		echo '"></span>

<span title="';
		echo Runtime\Filters::escapeHtmlAttr($x) /* line %d% */;
		echo '" ';
		echo Runtime\Filters::escapeHtmlTag($x) /* line %d% */;
		echo '></span>

<span title="';
		if (true) /* line %d% */ {
			echo Runtime\Filters::escapeHtmlAttr($x) /* line %d% */;
		} else /* line %d% */ {
			echo 'item';
		}
		echo '"></span>

<span ';
		echo Runtime\Filters::escapeHtmlTag('title') /* line %d% */;
		echo '="';
		echo Runtime\Filters::escapeHtmlAttr($x) /* line %d% */;
		echo '"></span>

<span attr="c';
		echo Runtime\Filters::escapeHtmlAttr($x) /* line %d% */;
		echo 'd"></span>

<span onclick="';
		echo Runtime\Filters::escapeHtmlAttr(Runtime\Filters::escapeJs($x)) /* line %d% */;
		echo '" ';
		echo Runtime\Filters::escapeHtmlTag($x) /* line %d% */;
		echo '></span>

<span onclick="c';
		echo Runtime\Filters::escapeHtmlAttr(Runtime\Filters::escapeJs($x)) /* line %d% */;
		echo 'd"></span>

<span attr';
		echo Runtime\Filters::escapeHtmlTag($x) /* line %d% */;
		echo 'b="c';
		echo Runtime\Filters::escapeHtmlAttr($x) /* line %d% */;
		echo 'd"></span>
';
	}
}
