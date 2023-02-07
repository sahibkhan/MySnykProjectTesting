<?php	
	chdir(dirname(__FILE__) . '/../..');
	include_once 'vtlib/Vtiger/Module.php';
	include_once 'includes/main/WebUI.php';
	include_once 'include/Webservices/Utils.php';
	global $adb;
	
	set_time_limit(0);
	date_default_timezone_set("UTC");	
	ini_set('memory_limit','64M');
	$indirectcost_info_arr =array();	  
	
	$truck_id = $_REQUEST['truck_id'];
	
	$rs_truck = $adb->pquery("SELECT * from vtiger_truck 
							   INNER JOIN vtiger_truckcf ON vtiger_truckcf.truckid=vtiger_truck.truckid
							   WHERE vtiger_truck.truckid='".$truck_id."'", array());
	$truck_info = $adb->fetch_array($rs_truck);
	
	
	$rs_trucktypes = $adb->pquery("SELECT * from vtiger_trucktypes 
							   INNER JOIN vtiger_trucktypescf ON vtiger_trucktypescf.trucktypesid=vtiger_trucktypes.trucktypesid
							   WHERE vtiger_trucktypes.trucktypesid='".$truck_info['cf_1911']."'", array());
	$trucktypes_info = $adb->fetch_array($rs_trucktypes);						  
	$indirect_cost = $trucktypes_info['cf_5157'];
	
	//if(!empty($indirect_cost))
	//{
		$indirectcost_info_arr['indirect_cost'] = (!empty($trucktypes_info)?$trucktypes_info['cf_5157']:'0.00');
	//}
	echo json_encode($indirectcost_info_arr);
?>