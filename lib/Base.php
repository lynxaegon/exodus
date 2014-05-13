<?php
require('smarty/libs/Smarty.class.php');
require_once("_config.php");
require_once("DB.php");
require_once("MobilPay.php");

class SmartySingleton extends Smarty 
{ 
  public static function getInstance($type = "admin",$newInstance = null) 
  { 
    static $instance = null; 
    if(isset($newInstance)) 
      $instance = $newInstance; 
    if ( $instance == null ) 
      $instance = new SmartySingleton($type); 
    return $instance;
  } 

  public $type = "admin";

  public function __construct($type = "admin") 
  { 
    $basePATH = $_SERVER['DOCUMENT_ROOT']."/lib/";
    parent::__construct(); 

    $this->type = $type;
    // initialize smarty here 
    $this->setTemplateDir($basePATH.'smarty/templates');
    $this->setCompileDir($basePATH.'smarty/templates_c');
    $this->setCacheDir($basePATH.'smarty/cache');
    $this->setConfigDir($basePATH.'smarty/configs');
    $this->setPluginsDir($basePATH.'smarty/plugins');
  }

  public function fetch($template)
  {
  		$config = Config::getInstance();
  		if($this->type == "api")
  			return parent::fetch($config->appName."/".$template);
  		else
  			return parent::fetch($config->appName."/admin/".$template);
  }
}

abstract class Request
{
	// All external data is cleaned and verified
	protected $params = array();
	protected $files = array();
	protected $db = null;
	protected $config = null;
	function __construct($required_params=array(),$optional_params=array(),$type="_GET")
	{
		$this->config = Config::getInstance();
		$this->config->execution_start = microtime(true); 
		if(isset($GLOBALS['print_only']))
		{
			echo "<div style='width:300px;background-color:#eaeaea;padding:10px;'>";
			echo "<b>".get_called_class()."</b><br/>";
			echo "<ul>";
			echo "<li>Required:</li>";
			echo "<ul>";
			foreach($required_params as $key)
				echo "<li>".$key."</li>";	
			echo "</ul>";

			echo "<li>Optional:</li>";
			echo "<ul>";
			foreach($optional_params as $key)
				if($key != "")
					echo "<li>".$key."</li>";	
			echo "</ul>";		
			echo "</ul>";
			echo "<hr/>";
			echo "</div>";		
			return;
		}
		if($this->config->uses_api_key == APIKEY_REQUIRED)
		{
			try{
				if(!isset($_SERVER['HTTP_'.HEADER_API_KEY]))
				{
					throw new Exception("No Api Key header !");
				}
				if(!in_array($_SERVER['HTTP_'.HEADER_API_KEY],$this->config->allowed_apikeys))
				{
					throw new Exception("Invalid Api Key !");	
				}
			}
			catch(Exception $ex)
			{
				error_log($ex);
				Renderer::ThrowError(StatusCodes::$INVALID_REQUEST);
			}
		}
		$_REQUEST = $this->escape($_REQUEST);
		$missingParams = array();
		foreach($required_params as $param)
		{
			if( !isset($_REQUEST[$param]) )
			{
				$missingParams[] = $param;
				continue;
			}

			$this->params[$param] = $_REQUEST[$param];
		}

		foreach($optional_params as $param)
		{
			if( !isset($_REQUEST[$param]) )
				$this->params[$param] = "";
			else
				$this->params[$param] = $_REQUEST[$param];
		
		}

		foreach($_FILES as $key => $param)
		{
			$this->files[] = $param;
		}

		if($GLOBALS['DEBUG_MODE'] == true && count($missingParams))
		{
			Renderer::ThrowError(StatusCodes::$INVALID_REQUEST,$missingParams);
		}
		else if(count($missingParams) > 0)
		{
			Renderer::ThrowError(StatusCodes::$INVALID_REQUEST);
		}

		if($this->config->hasDB)
			$this->db = DB::getInstance();
	}
	
	protected function getJson($value)
	{
		return json_decode(stripcslashes($value));
	}

	private function escape($array){
		foreach($array as $key=>$value) {
	      if(is_array($value)) { $this->escape($value); }
	      else { $array[$key] = mysql_real_escape_string($value); }
	   }
	   return $array;
	}

	abstract protected function _do();

}

class Renderer
{
	private static $status = 200;
	private static $mode = "json";
	
	public static function ThrowError($statusCode, $data = array())
	{
		$config = Config::getInstance();
		$execution_time = microtime(true) - $config->execution_start;

		header("Content-type: application/json; charset=utf-8");
		echo json_encode(array(
			"status" => $statusCode['ID'],
			"error" => $statusCode['message'],
			"error_data" => $data,
			"execution_time" => (float)number_format($execution_time,6)
		));
		exit();
	}

	public static function setMode($mode)
	{
		self::$mode = $mode;
	}

	public static function setStatus($status)
	{
		if(!is_object($status))
			self::$status = $status;
		else
			self::$status = $status['ID'];
	}

	public static function render($data)
	{
		$config = Config::getInstance();
		$execution_time = microtime(true) - $config->execution_start;

		if(self::$mode == "json")
		{
			header("Content-type: application/json; charset=utf-8");	
			if($data === false)
			{
				echo json_encode( 
					array( 
						"status" => StatusCodes::$INTERNAL_ERROR,
						"execution_time" => $execution_time,
					) 
				);
				return false;
			}
			$tmp = array(
				"status" => self::$status
			);
			$tmp['data'] = $data;
			$tmp['execution_time'] = $execution_time;
			echo json_encode( $tmp );
		}
		else
		{
			echo $data;
		}
	}
}

class StatusCodes
{
	static $NOT_MODIFIED = array(
		"ID" => 304,
		"message" => "Not modified"
	);

	static $INVALID_REQUEST = array(
		"ID" => 305,
		"message" => "Invalid Request"
	);

	static $INTERNAL_ERROR = array(
		"ID" => 306,
		"message" => "Internal Request Error"
	);
	
	static $DBLINK_ERROR = array(
		"ID" => 401,
		"message" => "Cannot establish DB connection"
	);

	static $DBQUERY_ERROR = array(
		"ID" => 402,
		"message" => "Database query error"
	);

	static $REQUEST_FAILED = array(
		"ID" => 600,
		"message" => "Request Failed"
	);	

	static $INVALID_DATA = array(
		"ID" => 601,
		"message" => "Invalid data sent to server"
	);	
}

class ApiException extends Exception{
	
	public function __construct($message)
	{
		parent::__construct($message);
	}

	public function __toString() {
        return __CLASS__ . ": {$this->message}\n";
    }
}

class FileSystem {
	private $config;
	private $base_location;

	public function __construct()
	{
		$this->base_location = $_SERVER['DOCUMENT_ROOT']."/files/";
		$this->config = Config::getInstance();
		if( $this->config->appName == "")
			die("No appname found!");

		$this->base_location .= $this->config->appName;

		if(!is_dir($this->base_location))
		{
			if (!mkdir($this->base_location, 0777, true)) {
			    die('Failed to create folders...');
			}
		}
	}

	public function createDir($dir)
	{
		if(!is_dir($this->base_location . "/" . $dir))
		{
			if (!mkdir($this->base_location."/".$dir, 0777, true)) {
			    die('Failed to create dir...');
			}
		}
	}

	// $to is relative to the base location
	public function copy($from, $to)
	{
		if(is_dir($from))
			$this->recurse_copy($from, $this->base_location . "/" . $to);
		else
			copy($from, $this->base_location . "/" . $to);		
	}

	// $to is relative to the base location
	public function move($from, $to)
	{
		rename($from, $this->base_location . "/" . $to);
	}

	public function delete($path)
	{
		if($this->exists($path))
		{
			if(is_dir($this->base_location . "/" . $path))
				$this->deleteDir($this->base_location . "/" . $path);
			else
				unlink($this->base_location . "/" . $path);
		}
	}

	public function exists($path)
	{
		return file_exists($this->base_location . "/" . $path);
	}


	public function write($path,$data,$append = false)
	{
        $flags = 0;
        if($append)
            $flags = FILE_APPEND;

		file_put_contents($this->base_location . "/" .  $path, $data,$flags);
	}

	public function read($path)
	{
		return file_get_contents($this->base_location . "/" . $path);
	}

	private function recurse_copy($src,$dst) { 
	    $dir = opendir($src); 
	    @mkdir($dst); 
	    while(false !== ( $file = readdir($dir)) ) { 
	        if (( $file != '.' ) && ( $file != '..' )) { 
	            if ( is_dir($src . '/' . $file) ) { 
	                $this->recurse_copy($src . '/' . $file,$dst . '/' . $file); 
	            } 
	            else { 
	                copy($src . '/' . $file,$dst . '/' . $file); 
	            } 
	        } 
	    } 
	    closedir($dir); 
	} 

	private function deleteDir($path)
	{
	    return is_file($path) ?
	            @unlink($path) :
	            array_map(array($this,__FUNCTION__), glob($path.'/*')) == @rmdir($path);
	}

}
?>
