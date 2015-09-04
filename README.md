Introduction
=
The API framework is a modular project and version system. 

Every parameter that enters the API framework is sanitized before entering the requested class.

##Modules
* JSON / HTML rendering support
* MobilPay integration (http://mobilpay.ro)
* Smarty Templates
* Swift Mailer
* Basic DB module
* File System abstractization
* Support for dev / debug modes

##Installation
```
$ git clone https://github.com/lynxaegon/exodus.git .
$ mkdir files
$ mkdir lib/smarty/templates_c
$ mkdir lib/smarty/cache
$ chmod 775 files
$ chmod 777 lib/smarty/templates_c
$ chmod 777 lib/smarty/cache
```

Nginx setup:

You need to add this to your website config:
```
try_files $uri @api_request;

location @api_request {
       rewrite ^/(.+)/(.+)/(.+)$ /index.php?app=$1&version=$2&request=$3 last;
}
```

##Class Structure
* Request (basic class for every request)
* Renderer (the class that renders either json or html)
* StatusCodes (the status codes that are used in the renderer class)
* FileSystem (the abstractization of the filesystem)

##Usefull functions
* Renderer::ThrowError($statusCode, $data)
```
usage: Renderer::ThrowError(StatusCodes::$NOT_MODIFIED, array("data has not been modified, you should request again later..");

When ThrowError is executed, the whole script exists after the error is thrown.
```
* Renderer::setMode($mode)
```
usage: Renderer::setMode("html");

$mode can be either "html" or "json". (default: json)
```

##Creating a request
The class name and the filename have to be identical. 

Example: 
If you wish to create a request named "Test" then the filename should be named "Test.php"
```php
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
```
The constructor has 2 arrays. The first contains the **required** params and the second one contains the **optional** params.

The params can be accessed via **$this->params['PARAM_NAME']**. If the param is optional, and it wasn't sent in the request, then the param will default to "" (empty string)

##Accesssing the db
```php
<?php
class Test extends Request
{
        function __construct()
        {
                parent::__construct(
                        array("clientID"),
                        array()
                );
        }

        public function _do()
        {
            $result = $db->query("SELECT * FROM clients WHERE clientID = '".$this->params['clientID']."'");
            if($result && mysql_num_rows($result) > 0)
              return mysql_fetch_assoc($result);
            
            Renderer::ThrowError(StatusCodes::$REQUEST_FAILED);
        }
}
?>
```

##Usage:

###Normal mode
http://example.com/**project_name**/**version_name**/**request**?**params**

**Example**:
http://example.com/examples/v1/Test

Response:
```json
{
status: 200,
data: [
"This is a test request"
],
execution_time: 0.000056982040405273
}
```

###Dev mode
http://example.com/**project_name**-dev/**version_name**/**request**?**params**

**Example**:
http://example.com/examples-dev/v1/Test

By default it will try to connect to another database (your db_name with a suffix "_dev")

Response:
```json
{
status: 200,
data: [
"This is a test request"
],
execution_time: 0.000056982040405273
}
```

###Debug mode
http://example.com/**project_name**-debug/**version_name**/**request**?**params**

**Example**:
http://example.com/examples-dev/v1/Test

While debug mode is active, it will connect to the live database, but you can set detailed logging.

Response:
```json
{
status: 200,
data: [
"This is a test request"
],
execution_time: 0.000056982040405273
}
```

###Credits
* [liviucmg](https://github.com/liviucmg)
