<?php

//include('custom_connectdb.php'); // Подключение к базе данных

//include_once 'index.php';
include_once '/vtlib/Vtiger/Module.php';
include_once '/modules/Vtiger/CRMEntity.php';
include_once '/includes/main/WebUI.php';
include_once '/include/Webservices/Utils.php';
$adb = PearDatabase::getInstance();
//$adb = PearDatabase::getInstance();
// set_time_limit(0);
// ini_set('memory_limit','64M');
$current_user_model = Users_Record_Model::getCurrentUserModel();
$currentUserID = $current_user_model->id;
//print_r($currentUserID); exit;
$user_arr=Array();
$roleid = fetchUserRole($currentUserID);
//echo $roleid; 
$all_users=getSubordinateRoleAndUsers($roleid);
$useridall = '';
foreach($all_users as $users)
{
	foreach($users as $key=>$value)
{
	$useridall .= $key.","; 
}
}
$data = "<option value='".$useridall.$currentUserID."'>All</option>";
foreach($all_users as $users)
{
	foreach($users as $key=>$value)
{
	$user_arr[$key] = $value;
	$data = $data."<option value='".$key."'>".$value."</option>";
}
}
echo $data;

?>