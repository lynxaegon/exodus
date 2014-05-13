<?php
	$DB_DB = "test_db";
	if($DEV_MODE)
		$DB_DB = "dev_" . $DB_DB;
	define("DB_DB",$DB_DB);

	$config = array();
	if(class_exists("Config"))
	{
		$config = Config::getInstance();
		$config->setDevMode($DEV_MODE);
		$config->hasDB = true;
		$config->uses_api_key = APIKEY_OPTIONAL;
		$config->allowed_apikeys = array(
			"55e425743ce8debc4a18dc688e76647c691b34fb",
			"af5f1a0e0d6c9574a03ccbd54c25bb6fa3527efc"
		);
		$config->version = "v1";

		$config->publicKey = '';
		$config->privateKey = '';

		$config->merchantSignature = '';

		$config->confirmUrl = "http://api.thepolesociety.com/v6/PaymentProcessing";
		$config->userReturnUrl = "http://api.thepolesociety.com/v6/PaymentDone";

		$config->mobilpay = new MobilPay($config->merchantSignature, $config->publicKey, false);
	}
?>
