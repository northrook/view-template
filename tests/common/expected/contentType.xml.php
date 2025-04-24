<?php
%A%
final class Template%a% extends Core\View\Template\Runtime\Template
{
	public const ContentType = 'xml';

	public const Source = '%a%.latte';


	public function main(array $__args__): void
	{
		extract($__args__);
		unset($__args__);

		echo '<?xml version="1.0" encoding="utf-8"?>
<?xml-stylesheet type="text/css" href="';
		echo Runtime\Filters::escapeXml($id) /* line %d% */;
		echo '"?>

<script>';
		if (1) /* line %d% */ {
			echo '<meta />';
		}
		echo '</script>


<ul>
	<li>Escaped: ';
		echo Runtime\Filters::escapeXml($hello) /* line %d% */;
		echo '</li>
	<li>Non-escaped: ';
		echo $hello /* line %d% */;
		echo '</li>
	<li>Escaped expression: ';
		echo Runtime\Filters::escapeXml('<' . 'b' . '>hello' . '</b>') /* line %d% */;
		echo '</li>
	<li>Non-escaped expression: ';
		echo '<' . 'b' . '>hello' . '</b>' /* line %d% */;
		echo '</li>
	<li>Array access: ';
		echo Runtime\Filters::escapeXml($people[1]) /* line %d% */;
		echo '</li>
	<li>Html: ';
		echo Runtime\Filters::escapeXml($el) /* line %d% */;
		echo '</li>
</ul>

<style type="text/css">
<!--
#';
		echo Runtime\Filters::escapeHtmlComment($id) /* line %d% */;
		echo ' {
	background: blue;
}
-->
</style>


<script>
<!--
var html = ';
		echo Runtime\Filters::escapeHtmlComment($el) /* line %d% */;
		echo ';
-->
</script>


<p onclick=\'alert(';
		echo Runtime\Filters::escapeXml($id) /* line %d% */;
		echo ');alert("hello");\'
 title=\'';
		echo Runtime\Filters::escapeXml($id) /* line %d% */;
		echo '"\'
 style="color:';
		echo Runtime\Filters::escapeXml($id) /* line %d% */;
		echo ';\'"
 alt=\'';
		echo Runtime\Filters::escapeXml($el) /* line %d% */;
		echo '\'
 onfocus="alert(';
		echo Runtime\Filters::escapeXml($el) /* line %d% */;
		echo ')"
>click on me</p>


<!-- ';
		echo Runtime\Filters::escapeHtmlComment($comment) /* line %d% */;
		echo ' -->


</ul>


<ul>
';
		foreach ($people as $person) /* line %d% */ {
			echo '	<li>';
			echo Runtime\Filters::escapeXml($person) /* line %d% */;
			echo '</li>
';

		}

		echo '</ul>

';
		if (true) /* line %d% */ {
			echo '<p>
	<div><p>true</div>
</p>
';
		}
		echo '
<input/> <input />

<p val="';
		if (true) /* line %d% */ {
			echo 'a';
		} else /* line %d% */ {
			echo 'b';
		}
		echo '"> </p>

<p val="';
		echo Runtime\Filters::escapeXml($xss) /* line %d% */;
		echo '" > </p>

<p onclick="';
		echo Runtime\Filters::escapeXml($xss) /* line %d% */;
		echo '"> </p>
';
	}


	public function prepare(): array
	{
		extract($this->params);

		if (!$this->getReferringTemplate() || $this->getReferenceType() === 'extends') {
			foreach (array_intersect_key(['person' => '50'], $this->params) as $__var__ => $__line__) {
				trigger_error("Variable \$$__var__ overwritten in foreach on line $__line__");
			}
		}
		if (empty($this->global->coreCaptured) && in_array($this->getReferenceType(), ['extends', null], true)) {
			header('Content-Type: application/xml; charset=utf-8') /* line %d% */;
		}
		return get_defined_vars();
	}
}
