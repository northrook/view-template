<?php
%A%
		echo 'consts

';
		echo Runtime\Filters::escapeHtmlText(\Name\MyClass::CONST) /* line %d% */;
		echo "\n";
		echo Runtime\Filters::escapeHtmlText($obj::CONST) /* line %d% */;
		echo "\n";
		echo Runtime\Filters::escapeHtmlText($obj::CONST) /* line %d% */;
		echo "\n";
		echo Runtime\Filters::escapeHtmlText($this->global->sandbox->call($obj::CONST, [])) /* line %d% */;
		echo "\n";
		echo Runtime\Filters::escapeHtmlText($this->global->sandbox->call($obj::CONST[0], [])) /* line %d% */;
		echo "\n";
		echo Runtime\Filters::escapeHtmlText($this->global->sandbox->call(namespace\CONST[0], [])) /* line %d% */;
		echo '
-';
%A%
