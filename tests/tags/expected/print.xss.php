%A%
		echo Runtime\Filters::escapeHtmlText($el) /* line %d% */;
		echo "\n";
		echo Runtime\Filters::escapeHtmlText($el2) /* line %d% */;
		echo '

<p val="';
		echo Runtime\Filters::escapeHtmlAttr($xss) /* line %d% */;
		echo '" > </p>
<p onclick="';
		echo Runtime\Filters::escapeHtmlAttr(Runtime\Filters::escapeJs($xss)) /* line %d% */;
		echo '"> </p>
<p ONCLICK="';
		echo Runtime\Filters::escapeHtmlAttr(Runtime\Filters::escapeJs($xss)) /* line %d% */;
		echo '" ';
		echo Runtime\Filters::escapeHtmlTag($xss) /* line %d% */;
		echo '> </p>

<STYLE type="text/css">
<!--
#';
		echo Runtime\Filters::escapeCss($xss) /* line %d% */;
		echo ' {
	background: blue;
}
-->
</STYLE>

<script>
<!--
alert(\'</div>\');

var prop = ';
		echo Runtime\Filters::escapeJs($people) /* line %d% */;
		echo ';

document.getElementById(';
		echo Runtime\Filters::escapeJs($xss) /* line %d% */;
		echo ').style.backgroundColor = \'red\';

var html = ';
		echo Runtime\Filters::escapeJs($el) /* line %d% */;
		echo ' || ';
		echo Runtime\Filters::escapeJs($el2) /* line %d% */;
		echo ';
-->
</script>

<SCRIPT>
/* <![CDATA[ */

var prop2 = ';
		echo Runtime\Filters::escapeJs($people) /* line %d% */;
		echo ';

/* ]]> */
</SCRIPT>

<p onclick=\'alert(';
		echo Runtime\Filters::escapeHtmlAttr(Runtime\Filters::escapeJs($xss)) /* line %d% */;
		echo ');alert("hello");\'
 title=\'';
		echo Runtime\Filters::escapeHtmlAttr($xss) /* line %d% */;
		echo '\'
 STYLE="color:';
		echo Runtime\Filters::escapeHtmlAttr(Runtime\Filters::escapeCss($xss)) /* line %d% */;
		echo ';"
 rel="';
		echo Runtime\Filters::escapeHtmlAttr($xss) /* line %d% */;
		echo '"
 onblur="alert(';
		echo Runtime\Filters::escapeHtmlAttr(Runtime\Filters::escapeJs($xss)) /* line %d% */;
		echo ')"
 alt=\'';
		echo Runtime\Filters::escapeHtmlAttr($el) /* line %d% */;
		echo ' ';
		echo Runtime\Filters::escapeHtmlAttr($el2) /* line %d% */;
		echo '\'
 onfocus="alert(';
		echo Runtime\Filters::escapeHtmlAttr(Runtime\Filters::escapeJs($el)) /* line %d% */;
		echo ')"
>click on me ';
		echo Runtime\Filters::escapeHtmlText($xss) /* line %d% */;
		echo '</p>';
%A%
