<?php
class CardPayment extends Request
{
	function __construct()
	{
		parent::__construct(
			array(),
			array()
		);
		Renderer::setMode("html");
	}

	public function _do()
	{
		$this->config->mobilpay->cardPayment($this->config->confirmUrl, $this->config->returnUrl);

		$result = $this->config->mobilpay->pay(200,"Test payment");

		return $result;
	}
}
?>