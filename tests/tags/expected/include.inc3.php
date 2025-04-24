<?php
%A%
final class Template%a% extends Core\View\Template\Runtime\Template
{
	public const Source = '%a%.latte';


	public function main(array $__args__): void
	{
%A%
		echo '<p>Included file #3 (';
		echo Runtime\Filters::escapeHtmlText($localvar) /* line %d% */;
		echo ', ';
		echo Runtime\Filters::escapeHtmlText($hello) /* line %d% */;
		echo ')</p>
';
	}
}
