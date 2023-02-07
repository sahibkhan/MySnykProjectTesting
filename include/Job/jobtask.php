<?php
chdir(dirname(__FILE__) . '/../..');
include_once 'vtlib/Vtiger/Module.php';
include_once 'includes/main/WebUI.php';
include_once 'include/Webservices/Utils.php';
require_once('include/custom_connectdb2.php'); // Подключение к базе данных
set_time_limit(0);
ini_set('memory_limit','64M');

$db = PearDatabase::getInstance();

//echo $new_id;
//exit;
$current_user = Users_Record_Model::getCurrentUserModel();
//location:: cf_1188 :: 85805 (ALA)
//department:: cf_1190 :: 85837::CTD, 85840::RRS
//file title:: cf_1186 :: 85757:: KZ

$job_rrs_ctd = $db->pquery("SELECT * FROM ctdjobs");
$numRows_rrs_ctd = $db->num_rows($job_rrs_ctd);
$jobs_arr = array();
for($kk=0; $kk< $db->num_rows($job_rrs_ctd); $kk++ ) {

	$row_job_rrs_ctd = $db->fetch_row($job_rrs_ctd,$kk);
	
	$job_ref_no = $row_job_rrs_ctd['job_ref_no'];
	
	$query_jobcf = "SELECT vtiger_jobcf.jobid as jobid, vtiger_crmentity.smcreatorid as smcreatorid FROM vtiger_jobcf
					INNER JOIN  vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobcf.jobid
					WHERE 
					vtiger_jobcf.cf_1198=? AND vtiger_jobcf.cf_2197!='Cancelled' ";
	$params_jobcf = array($job_ref_no);	
	$result_jobcf = $db->pquery($query_jobcf, $params_jobcf);
	$numRows_jobcf = $db->num_rows($result_jobcf);
	
	for($jj=0; $jj< $db->num_rows($result_jobcf); $jj++ ) {
		$row_jobcf = $db->fetch_row($result_jobcf,$jj);
		$jobs_arr[] = array('job_id' => $row_jobcf['jobid'], 'smcreatorid' => $row_jobcf['smcreatorid']);
	}		
	
}
//echo "<pre>";
//print_r($jobs_arr);
//exit;
foreach($jobs_arr as $key => $job_info)
{
	//echo $key." :: ".$job_info['job_id']."::";
	
	$query_chk_jobtask = "SELECT * FROM vtiger_jobtask WHERE job_id=? AND user_id=? AND job_owner='0'";
	$params_chk_jobtask = array($job_info['job_id'], '604');
	$result_chk_jobtask = $db->pquery($query_chk_jobtask, $params_chk_jobtask);
	$numRows_chk_jobtask = $db->num_rows($result_chk_jobtask);
	//echo "<br>";
	
	if($numRows_chk_jobtask==0){
	
	$adb = PearDatabase::getInstance();
	$new_id = $adb->getUniqueId('vtiger_crmentity');
	$db->pquery("INSERT INTO vtiger_crmentity SET crmid = '".$new_id."', smcreatorid ='".$job_info['smcreatorid']."', smownerid ='604', setype = 'JobTask', createdtime='".date('Y-m-d H:i:s')."', modifiedtime='".date('Y-m-d H:i:s')."' ");
	$db->pquery("INSERT INTO vtiger_jobtask SET jobtaskid = '".$new_id."', job_id = '".$job_info['job_id']."', name = 'Add Expense', user_id ='604', job_owner = '0'");

	$db->pquery("INSERT INTO vtiger_crmentityrel SET crmid = '".$job_info['job_id']."', module = 'Job', relcrmid = '".$new_id."', relmodule = 'JobTask'");	
	
	}
	else{
		echo $key." :: ".$job_info['job_id'];
		echo "<br>";
	}
}
?>