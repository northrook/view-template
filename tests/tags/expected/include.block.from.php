<?php
%A%
final class Template%a% extends Core\View\Template\Runtime\Template
{
%A%
		echo 'before ';
		$this->createTemplate('inc.ext', ['var' => 1] + $this->params, "include")->renderToContentType('html', 'bl') /* line 1 */;
		echo ' after';
%A%
}
