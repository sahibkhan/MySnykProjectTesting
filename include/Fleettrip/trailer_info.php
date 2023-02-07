<?php	
	chdir(dirname(__FILE__) . '/../..');
	include_once 'vtlib/Vtiger/Module.php';
	include_once 'includes/main/WebUI.php';
	include_once 'include/Webservices/Utils.php';
	global $adb;
	
	set_time_limit(0);
	date_default_timezone_set("UTC");	
	ini_set('memory_limit','64M');
	$trailer_info_arr =array();	  
	
	$truck_id = $_REQUEST['truck_id'];
	
	$rs_truck =  $adb->pquery("SELECT * from vtiger_truck 
							   INNER JOIN vtiger_truckcf ON vtiger_truckcf.truckid=vtiger_truck.truckid
							   WHERE vtiger_truck.truckid='".$truck_id."'", array());
	$truck_info = $adb->fetch_array($rs_truck);						  
	$truck_type_id = $truck_info['cf_1911'];
	
	if(!empty($truck_info))
	{
		$trailer_info_arr['trailer_id'] = (!empty($truck_info)?$truck_info['cf_2199']:'');
	}
	echo json_encode($trailer_info_arr);
?>