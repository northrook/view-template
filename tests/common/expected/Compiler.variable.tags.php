<?php
%A%
final class Template%a% extends Core\View\Template\Runtime\Template
{

	public function main(array $__args__): void
	{
%A%
		echo "\n";
		$__tag__[0] = '';
		echo '<';
		echo $__temp__ = Runtime\Filters::safeTag($tag) /* line %d% */;
		$__tag__[0] = '</' . $__temp__ . '>' . $__tag__[0];
		echo '>...';
		echo $__tag__[0];
		echo '

';
		$__tag__[1] = '';
		echo '<';
		echo $__temp__ = Runtime\Filters::safeTag($ns . ':' . $tag) /* line %d% */;
		$__tag__[1] = '</' . $__temp__ . '>' . $__tag__[1];
		echo '>...';
		echo $__tag__[1];
		echo '

';
		$__tag__[2] = '';
		echo '<';
		echo $__temp__ = Runtime\Filters::safeTag('h' . 1) /* line %d% */;
		$__tag__[2] = '</' . $__temp__ . '>' . $__tag__[2];
		echo '>...';
		echo $__tag__[2];
	}
%A%
}
