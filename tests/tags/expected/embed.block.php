<?php
%A%
final class Template%a% extends Core\View\Template\Runtime\Template
{
	public const Source = 'main';

	public const Blocks = [
		1 => ['a' => 'blockA'],
		2 => ['a' => 'blockA1'],
		0 => ['embed1' => 'blockEmbed1', 'a' => 'blockA2'],
	];


	public function main(array $__args__): void
	{
		extract($__args__);
		unset($__args__);

		echo "\n";
		$this->enterBlockLayer(1, get_defined_vars()) /* line 2 */;
		$this->copyBlockLayer();
		try {
			$this->renderBlock('embed1', [], 'html') /* line 2 */;
		} finally {
			$this->leaveBlockLayer();
		}
		echo "\n";
	}


	/** {block a} on line 3 */
	public function blockA(array $__args__): void
	{
		extract(end($this->varStack));
		extract($__args__);
		unset($__args__);

		$this->enterBlockLayer(2, get_defined_vars()) /* line 4 */;
		$this->copyBlockLayer();
		try {
			$this->renderBlock('embed1', [], 'html') /* line 4 */;
		} finally {
			$this->leaveBlockLayer();
		}
	}


	/** {block a} on line 5 */
	public function blockA1(array $__args__): void
	{
		echo 'nested embeds A';
	}


	/** {define embed1} on line 10 */
	public function blockEmbed1(array $__args__): void
	{
		extract($this->params);
		extract($__args__);
		unset($__args__);

		echo '		embed1-start
			';
		$this->renderBlock('a', get_defined_vars()) /* line 12 */;
		echo '
		embed1-end
';
	}


	/** {block a} on line 12 */
	public function blockA2(array $__args__): void
	{
		echo 'embed1-A';
	}
}
