<?php
%A%
final class Template%a% extends Core\View\Template\Runtime\Template
{
	public const Source = 'parent';

	public const Blocks = [
		['title' => 'blockTitle', 'sidebar' => 'blockSidebar'],
	];


	public function main(array $__args__): void
	{
		extract($__args__);
		unset($__args__);

		echo '<!DOCTYPE html>
<head>
	<title>';
		$this->renderBlock('title', get_defined_vars(), function ($s, $type) {
			$__filter__ = new Runtime\FilterInfo($type);
			return Runtime\Filters::convertTo($__filter__, 'html', $this->filters->filterContent('upper', $__filter__, $this->filters->filterContent('stripHtml', $__filter__, $s)));
		}) /* line %d% */;
		echo '</title>
</head>

<body>
	<div id="sidebar">
';
		$this->renderBlock('sidebar', get_defined_vars()) /* line %d% */;
		echo '	</div>

	<div id="content">
';
		$this->renderBlock('content', [], 'html') /* line %d% */;
		echo "\n";
		$this->renderBlock('content', [], function ($s, $type) {
			$__filter__ = new Runtime\FilterInfo($type);
			return Runtime\Filters::convertTo($__filter__, 'html', $this->filters->filterContent('upper', $__filter__, $this->filters->filterContent('stripHtml', $__filter__, $s)));
		}) /* line %d% */;
		echo '	</div>
</body>
</html>
Parent: ';
		echo Runtime\Filters::escapeHtmlText(basename($this->getReferringTemplate()->getName())) /* line %d% */;
		echo '/';
		echo Runtime\Filters::escapeHtmlText($this->getReferenceType()) /* line %d% */;
		echo "\n";
	}


	public function prepare(): array
	{
		extract($this->params);

		$class ??= array_key_exists('class', get_defined_vars()) ? null : null;
		$namespace ??= array_key_exists('namespace', get_defined_vars()) ? null : null;
		$top ??= array_key_exists('top', get_defined_vars()) ? null : true /* line 1 */;
		return get_defined_vars();
	}


	/** {block title|stripHtml|upper} on line %d% */
	public function blockTitle(array $__args__): void
	{
		echo 'My website';
	}


	/** {block sidebar} on line %d% */
	public function blockSidebar(array $__args__): void
	{
		echo '		<ul>
			<li><a href="/">Homepage</a></li>
		</ul>
';
	}
}
