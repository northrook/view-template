<?php
%A%
final class Template%a% extends Core\View\Template\Runtime\Template
{
	public const ContentType = 'text';

	public const Source = '%a%.latte';


	public function main(array $__args__): void
	{
		extract($__args__);
		unset($__args__);

		echo 'Pure text ';
		echo ($this->filters->escape)($foo) /* line 1 */;
		echo '
<a b
';
	}
}
