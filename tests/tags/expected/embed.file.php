<?php
%A%
final class Template%a% extends Core\View\Template\Runtime\Template
{
	public const Source = 'main';

	public const Blocks = [
		1 => ['a' => 'blockA'],
		2 => ['a' => 'blockA1'],
	];


	public function main(array $__args__): void
	{
		extract($__args__);
		unset($__args__);

		echo "\n";
		$this->enterBlockLayer(1, get_defined_vars()) /* line 2 */;
		$this->createTemplate('import.latte', $this->params, "import")->render() /* line 8 */;
		try {
			$this->createTemplate('embed1.latte', [], "embed")->renderToContentType('html') /* line 2 */;
		} finally {
			$this->leaveBlockLayer();
		}
	}


	/** {block a} on line 3 */
	public function blockA(array $__args__): void
	{
		extract(end($this->varStack));
		extract($__args__);
		unset($__args__);

		$this->enterBlockLayer(2, get_defined_vars()) /* line 4 */;
		try {
			$this->createTemplate('embed2.latte', [], "embed")->renderToContentType('html') /* line 4 */;
		} finally {
			$this->leaveBlockLayer();
		}
	}


	/** {block a} on line 5 */
	public function blockA1(array $__args__): void
	{
		echo 'nested embeds A';
	}
}
