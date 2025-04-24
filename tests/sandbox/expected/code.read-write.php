<?php
%A%
		echo 'read-write

';
		echo Runtime\Filters::escapeHtmlText($this->global->sandbox->prop($obj, 'bar')->bar++) /* line %d% */;
		echo "\n";
		echo Runtime\Filters::escapeHtmlText($this->global->sandbox->prop($obj, 'static')::$static++) /* line %d% */;
		echo '
-';
%A%
