<?php
class DB 
{
	private static $_instance = null;
	private $debug = false;
	private $query_string = "";

	public static function getInstance()
	{
		if(self::$_instance == null)
			self::$_instance = new DB();
		return self::$_instance;
	}

	protected function __construct()
	{
		$link = mysql_connect(DB_HOST,DB_USER,DB_PASS);
		mysql_select_db(DB_DB,$link);
		if (!$link) {
		    Renderer::ThrowError(StatusCodes::$DBLINK_ERROR);
		}
		mysql_query("SET NAMES 'utf8'");
	}
	
	public function beginTransaction()
        {
                mysql_query("START TRANSACTION");
        }

        public function commit()
        {
                mysql_query("COMMIT");
        }

        public function rollback()
        {
                mysql_query("ROLLBACK");
        }
	
	public function query($query)
	{
		$this->query_string = $query;
		// All querys return valid data
		$result = mysql_query($query);
		if(!$result)
		{
			if($this->debug)
			{
				echo mysql_error()."\n";
				$this->showQuery();
			}
			error_log("mysql error: ".mysql_error());
			error_log("mysql error: ".$this->query_string);
			
			Renderer::ThrowError(StatusCodes::$DBQUERY_ERROR);
		}

		return $result;
	}

	public function showQuery()
	{
		echo $this->query_string;
	}

	public function debug($enabled)
	{
		$this->debug = $enabled;
	}
}

?>
