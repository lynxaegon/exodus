<?php
	$DEV_MODE = false;
	$GLOBALS['DEBUG_MODE'] = false;
	require_once("lib/Base.php");

	if( isset($_GET['request']) && isset($_GET['app']) && isset($_GET['version']) )
	{
		try{
			if(is_dir("requests/".$_GET['app']))
			{
				if(preg_match("/(.+)\-dev$/i",$_GET['version'],$matches))
				{
					$_GET['version'] = $matches[1];
					$DEV_MODE = true;
				}

				if(preg_match("/(.+)\-debug$/i",$_GET['version'],$matches))
				{
					$_GET['version'] = $matches[1];
					$GLOBALS['DEBUG_MODE'] = true;
				}
				
				require_once("requests/".$_GET['app']."/_config.php");
				$config->appName = $_GET['app'];
				$config->version = $_GET['version'];
				$config->smarty = SmartySingleton::getInstance("api");
				$config->filesystem = new FileSystem();

				$db = DB::getInstance();

				if(is_dir("requests/".$_GET['app']."/".$_GET['version']))
				{
					if(file_exists("requests/".$_GET['app']."/".$_GET['version']."/".$_GET['request'].".php"))
					{
						require_once("requests/".$_GET['app']."/".$_GET['version']."/".$_GET['request'].".php");
					}
					else
					{
						throw new ApiException("Method not found !");
					}	
				}
				else
				{
					throw new ApiException("Invalid version !");
				}
			}
			else
			{
				throw new ApiException("App not found !");
			}
		}
		catch(ApiException $ex)
		{
			error_log($ex);
			Renderer::ThrowError(StatusCodes::$INVALID_REQUEST);
		}
	}
	else
	{
		Renderer::ThrowError(StatusCodes::$INVALID_REQUEST);
	}

	$request = new $_GET['request'];
	unset($_GET['request']);

	Renderer::render( $request->_do() );

?>