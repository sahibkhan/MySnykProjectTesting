<?php
chdir(dirname(__FILE__) . '/../..');
include_once 'vtlib/Vtiger/Module.php';
include_once 'includes/main/WebUI.php';
include_once 'include/Webservices/Utils.php';
require_once('include/custom_connectdb2.php'); // Подключение к базе данных
set_time_limit(0);
ini_set('memory_limit','64M');

$db = PearDatabase::getInstance();


$job_task = $db->pquery("SELECT * FROM `vtiger_jobtask` where job_id=''  ");
$numRows_task = $db->num_rows($job_task);
$jobs_arr = array();
for($kk=0; $kk< $db->num_rows($job_task); $kk++ ) {

	$row_job_task = $db->fetch_row($job_task,$kk);
	
	$jobtaskid = $row_job_task['jobtaskid'];
	
	$query_rel = "SELECT * FROM `vtiger_crmentityrel` where relcrmid=? AND module=? AND relmodule=?";
	$params_rel = array($jobtaskid, 'Job', 'JobTask');	
	$result_rel = $db->pquery($query_rel, $params_rel);
	$numRows_rel = $db->num_rows($result_rel);
	
	for($jj=0; $jj< $db->num_rows($result_rel); $jj++ ) {
		$row_rel = $db->fetch_row($result_rel,$jj);
		$job_id = $row_rel['crmid'];
		
		$db->pquery("UPDATE vtiger_jobtask SET job_id = '".$job_id."' WHERE jobtaskid = '".$jobtaskid."'");

	}		
	
}

?>