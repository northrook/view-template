<?php
%A%
		echo 'firstclass callable

';
		echo Runtime\Filters::escapeHtmlText($this->global->sandbox->closure('trim')) /* line %d% */;
		echo "\n";
		echo Runtime\Filters::escapeHtmlText($this->global->sandbox->closure([$obj, 'foo'])) /* line %d% */;
		echo "\n";
		echo Runtime\Filters::escapeHtmlText($this->global->sandbox->closure([$obj, 'foo'])) /* line %d% */;
		echo '
-';
%A%
