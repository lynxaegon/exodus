<?php
if( !isset($_GET['app']) || !isset($_GET['version']))
	return;

$GLOBALS['print_only'] = true;
require_once("lib/Base.php");

echo "API Docs for <b>".ucfirst($_GET['app'])."</b> - ". $_GET['version']."<br/><hr/>";

foreach (glob("requests/".$_GET['app']."/".$_GET['version']."/*.php") as $file) {
	require_once($file);
    $file = explode(".",basename($file));
    unset($file[count($file)-1]);
    $file = implode("",$file);

    $class = new $file();
}


?>