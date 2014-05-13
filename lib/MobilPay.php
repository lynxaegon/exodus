<?php

class MobilPay {
	private $type = "none";
	private $paymentUrl;
	private $confirmUrl;
	private $returnUrl;
	private $merchantSignature;
	private $generatedOrderID;
	private $publicKey;

	function __construct($merchantSignature = "", $publicKey = "", $live = false)
	{
		$this->merchantSignature = $merchantSignature;
		$this->publicKey = $publicKey;

		if($live == true)
		{
			$this->paymentUrl = 'https://secure.mobilpay.ro';
		}
		else
		{
			$this->paymentUrl = 'http://sandboxsecure.mobilpay.ro';
		}
	}

	public function cardPayment($confirmUrl, $returnUrl)
	{
		if($this->merchantSignature == "" || $this->publicKey == "")
			throw new Exception("Merchant_Signature or PublicKey not set", 1);
			
		require_once 'Mobilpay/Payment/Request/Abstract.php';
		require_once 'Mobilpay/Payment/Request/Card.php';
		require_once 'lib/Mobilpay/Payment/Invoice.php';
		require_once 'lib/Mobilpay/Payment/Address.php';

		$this->type = "card";
	
		$this->confirmUrl = $confirmUrl;
		$this->returnUrl = $returnUrl;
	}

	public function pay($price, $description = "", $billingData = array("firstname" => "","lastname" => "","email" => "","mobilePhone" => ""), $custom_params = array())
	{
		switch ($this->type) {
			case 'card':
				try
				{
					$this->generatedOrderID 			= md5(uniqid(rand()));
					$objPmReqCard 						= new Mobilpay_Payment_Request_Card();
					$objPmReqCard->signature 			= $this->merchantSignature;
					$objPmReqCard->orderId 				= $this->generatedOrderID;
					$objPmReqCard->confirmUrl 			= $this->confirmUrl; 
					$objPmReqCard->returnUrl 			= $this->returnUrl;

					$objPmReqCard->invoice = new Mobilpay_Payment_Invoice();

					$objPmReqCard->invoice->currency	= 'RON';
					$objPmReqCard->invoice->amount		= $price;

					$objPmReqCard->invoice->details		= $description;
					
					$billingAddress 				= new Mobilpay_Payment_Address();
					$billingAddress->firstName		= $billingData['firstname'];
					$billingAddress->lastName		= $billingData['lastname'];
					$billingAddress->email			= $billingData['email'];
					$billingAddress->mobilePhone    = $billingData['mobilePhone'];

					$objPmReqCard->invoice->setBillingAddress($billingAddress);

					$objPmReqCard->params = $custom_params;

					$objPmReqCard->encrypt($this->publicKey);
				}
				catch(Exception $e)
				{
					error_log($e);
					throw new Exception("MobilPay payment has falied", 1);
				}
				break;
			
			default:
				throw new Exception("No MobilPay type set...How would you like to pay ? Free ? :)", 1);
				break;
		}
		
		return '
			<form name="frmPaymentRedirect" method="post" action="'.$this->paymentUrl.'">
				<input type="hidden" name="env_key" value="'.$objPmReqCard->getEnvKey().'"/>
				<input type="hidden" name="data" value="'.$objPmReqCard->getEncData().'"/>
			</form>
			<script type="text/javascript" language="javascript">
				window.setTimeout(document.frmPaymentRedirect.submit(), 0);
			</script>
		';
	}

	public function getOrderID()
	{
		return $this->generatedOrderID;
	}

}

?>