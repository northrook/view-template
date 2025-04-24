<?php
%A%
		echo 'static methods

';
		echo Runtime\Filters::escapeHtmlText($this->global->sandbox->call(['MyClass', 'method'], [])) /* line %d% */;
		echo "\n";
		echo Runtime\Filters::escapeHtmlText($this->global->sandbox->call(['Name\\MyClass', 'method'], [])) /* line %d% */;
		echo "\n";
		echo Runtime\Filters::escapeHtmlText($this->global->sandbox->call(['Name\\MyClass', 'method'], [])) /* line %d% */;
		echo "\n";
		echo Runtime\Filters::escapeHtmlText($this->global->sandbox->call(['Name\\MyClass', $method], [])) /* line %d% */;
		echo '
-';
%A%
