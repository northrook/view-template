<?php
%A%
		echo 'functions

';
		echo Runtime\Filters::escapeHtmlText(func()) /* line %d% */;
		echo '
';
		echo Runtime\Filters::escapeHtmlText($this->global->sandbox->call('func', [])) /* line %d% */;
		echo "\n";
		echo Runtime\Filters::escapeHtmlText($this->global->sandbox->call('fu' . 'nc', [])) /* line %d% */;
		echo "\n";
		echo Runtime\Filters::escapeHtmlText(\func()) /* line %d% */;
		echo '
';
		echo Runtime\Filters::escapeHtmlText(ns\func()) /* line %d% */;
		echo '
';
		echo Runtime\Filters::escapeHtmlText($this->global->sandbox->prop(func(), 'prop')->prop) /* line %d% */;
		echo "\n";
		echo Runtime\Filters::escapeHtmlText($this->global->sandbox->call(func(), [])) /* line %d% */;
		echo "\n";
		echo Runtime\Filters::escapeHtmlText($this->global->sandbox->call(func(...$this->global->sandbox->args($this->global->sandbox->prop($a, 'prop')->prop)), [func()])) /* line %d% */;
		echo "\n";
		echo Runtime\Filters::escapeHtmlText($this->global->sandbox->call(func()['x'], [])) /* line %d% */;
		echo '
-';
%A%
