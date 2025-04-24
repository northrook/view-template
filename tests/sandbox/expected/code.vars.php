<?php
%A%
		echo 'vars

';
		echo Runtime\Filters::escapeHtmlText($var['x']) /* line %d% */;
		echo "\n";
		echo Runtime\Filters::escapeHtmlText($this->global->sandbox->prop($var['' . change(...$this->global->sandbox->args(10 + inner()))], 'prop')->prop) /* line %d% */;
		echo "\n";
		echo Runtime\Filters::escapeHtmlText($this->global->sandbox->callMethod($var[0 + 1], 'method', [], false)) /* line %d% */;
		echo '
-';
%A%
