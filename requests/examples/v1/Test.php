<?php
class Test extends Request
{
	function __construct()
	{
		parent::__construct(
			array(),
			array()
		);
	}

	public function _do()
	{
		return array("This is a test request");
	}
}
?>
