<?php
%A%
final class Template%a% extends Core\View\Template\Runtime\Template
{
	public const Source = '%a%.latte';


	public function main(array $__args__): void
	{
%A%
		$this->createTemplate('subdir/include1.latte' . '', ['localvar' => 10] + $this->params, 'include')->renderToContentType(function ($s, $type) {
			$__filter__ = new Runtime\FilterInfo($type);
			return Runtime\Filters::convertTo($__filter__, 'html', $this->filters->filterContent('indent', $__filter__, $s));
		}) /* line %d% */;
	}
}
