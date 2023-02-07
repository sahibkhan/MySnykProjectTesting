<?php
	chdir(dirname(__FILE__) . '/../..');
	include_once 'vtlib/Vtiger/Module.php';
	include_once 'includes/main/WebUI.php';
	include_once 'include/Webservices/Utils.php';
    set_time_limit(0);
	date_default_timezone_set("UTC");	
	ini_set('memory_limit','64M');
  global $adb;
  
  $tms='';
  if (isset($_REQUEST['tms'])){
    $tms = $_REQUEST['tms'];
  }
  $sql = $adb->pquery("SELECT * From `vtiger_cf_817` where `cf_817` = '$tms' ");
  $row = $adb->fetch_array($sql);
  $tms_details = html_entity_decode($row['body']);

  echo $tms_details;
?>