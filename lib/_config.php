<?
class Config {
	private static $_instance = null;
	private $config = array();

	public static function getInstance()
	{
		if(self::$_instance == null)
			self::$_instance = new Config();
		return self::$_instance;
	}

	private function __construct()
	{
		
	}

	public function setDevMode($DEV_MODE = false)
	{
		$this->config['DEV_MODE'] = $DEV_MODE;
	}

	public function __set($name,$value)
	{
		$this->config[$name] = $value;
	}

	public function __get($name)
	{
		if (array_key_exists($name, $this->config)) {
            return $this->config[$name];
        }
        return null;
	}
}

$config = Config::getInstance();

define("DB_HOST","localhost");
define("DB_USER","DB_USER_HERE");
define("DB_PASS","DB_PASS_HERE");

define("APIKEY_NOT_REQUIRED",0);
define("APIKEY_REQUIRED",1);
define("APIKEY_OPTIONAL",2);
define("HEADER_API_KEY","X_API_KEY"); // X-API-KEY is the real header


// MobilPay Stuff
srand((double) microtime() * 1000000);

$config->publicKey = "";
$config->privateKey = "";

$config->merchantSignature = "";

$config->confirmUrl = "";
$config->userReturnUrl = "";
?>
