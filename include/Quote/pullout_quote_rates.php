<?php	
	chdir(dirname(__FILE__) . '/../..');
	include_once 'vtlib/Vtiger/Module.php';
	include_once 'includes/main/WebUI.php';
	include_once 'include/Webservices/Utils.php';
	
	  set_time_limit(0);
	  date_default_timezone_set("UTC");	
	  ini_set('memory_limit','64M');
    global $adb;
    $rates='';
  if (isset($_REQUEST['rates'])){
    $rates = $_REQUEST['rates'];
  }
  $sql = $adb->pquery("SELECT * From `vtiger_cf_1719` where `cf_1719` = '".$rates."' ");
  $row = $adb->fetch_array($sql);
  echo $row['body'];
?>