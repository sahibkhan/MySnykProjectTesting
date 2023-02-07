<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
class Jobexpencereport_FindAgreement_Action extends Vtiger_Save_Action {

	public function requiresPermission(\Vtiger_Request $request) {

		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView');
		$permissions[] = array('module_parameter' => 'module', 'action' => 'EditView', 'record_parameter' => 'record');
		return $permissions;
	}

	public function checkPermission(Vtiger_Request $request) {
		// parent::checkPermission($request);
		// $recordIds = $this->getRecordIds($request);
		// foreach ($recordIds as $key => $recordId) {
		// 	$moduleName = getSalesEntityType($recordId);
		// 	$permissionStatus  = Users_Privileges_Model::isPermitted($moduleName,  'EditView', $recordId);
		// 	if($permissionStatus){
		// 		$this->transferRecordIds[] = $recordId;
		// 	}
		// 	if(empty($this->transferRecordIds)){
		// 		throw new AppException(vtranslate('LBL_RECORD_PERMISSION_DENIED'));
		// 	}
		// }
		return true;
	}

	public function process(Vtiger_Request $request) {
	$adb = PearDatabase::getInstance();
	$customer_id = $request->get('customer_id');
	$record_id = $request->get('record_id');
	$returnrecord = $request->get('returnrecord');

	// $invoice_date = date("Y-m-d", strtotime('-1 year', strtotime($request->get('invoice_date'))));
	$invoice_date = date("Y-m-d", strtotime($request->get('invoice_date')));
	$action = $request->get('actions');


	if($customer_id){

			if($record_id!='' && $action=='Detail'){
					$jobexpense_query = "SELECT *
					FROM vtiger_jobexpencereportcf
					WHERE  vtiger_jobexpencereportcf.jobexpencereportid = '$record_id'";
					$jobexpense_query_result =  $adb->pquery($jobexpense_query);
					$agreement_id =  $adb->query_result($jobexpense_query_result, 0, 'cf_7914');
			}else{
					$agreement_id = 0 ;
			}

			///// find job data
				$job_info_detail = Vtiger_Record_Model::getInstanceById($returnrecord, 'Job');
			if($job_info_detail){

				$DepId = $job_info_detail->get('cf_1190');
				$file_title = $job_info_detail->get('cf_1186');
				$department_info_detail = Vtiger_Record_Model::getInstanceById($DepId, 'Department');
				$Dep_code = $department_info_detail->get('cf_1542');

			if($Dep_code=='CTD')
			{
				$agreement = "Customs Brokerage";
			}else{
				$agreement = "Freight Forwarding";
			}

			///// query to find data of service agreement module  //////
			 	//  $query = "SELECT *
				// FROM vtiger_serviceagreementcf
				// INNER JOIN vtiger_serviceagreement ON vtiger_serviceagreementcf.serviceagreementid =  vtiger_serviceagreement.serviceagreementid
				// INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid =  vtiger_serviceagreementcf.serviceagreementid
				// WHERE ('$invoice_date' BETWEEN DATE_SUB(vtiger_serviceagreementcf.cf_6018, INTERVAL 1 YEAR) AND vtiger_serviceagreementcf.cf_6020) AND  vtiger_crmentity.deleted = 0  AND vtiger_serviceagreementcf.cf_6094 = '$customer_id' AND vtiger_serviceagreementcf.cf_6068='$agreement' AND vtiger_serviceagreementcf.cf_6026='$file_title'";
			 $query = "SELECT *
			 FROM vtiger_serviceagreementcf
			 INNER JOIN vtiger_serviceagreement ON vtiger_serviceagreementcf.serviceagreementid =  vtiger_serviceagreement.serviceagreementid
			 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid =  vtiger_serviceagreementcf.serviceagreementid
			 WHERE (DATE_SUB('$invoice_date', INTERVAL 1 YEAR) <= vtiger_serviceagreementcf.cf_6020)
			 AND  vtiger_crmentity.deleted = 0
			 AND vtiger_serviceagreementcf.cf_6094 = '$customer_id'
			 AND vtiger_serviceagreementcf.cf_6068='$agreement'
			 AND vtiger_serviceagreementcf.cf_6026='$file_title'
			 order by vtiger_serviceagreementcf.cf_6018 DESC
			 ";

				$query_rate =  $adb->pquery($query);

			if($adb->num_rows($query_rate)>0){     ///// check if service agreement query return any data //////?>

			<!-- <select name="cf_1445" data-rule-required="true" aria-required="true" class="select2 agreementdropdown" style="width:75%"> -->
				<select name="cf_7914" class="select2 agreementdropdown" style="width:75%">
				<option value="">Select Option</option>
			<?php
			for($i=0;$i<$adb->num_rows($query_rate);$i++){   /// for loop start from here ////
				$serviceagreementid = $adb->query_result($query_rate, $i, 'serviceagreementid');
				$serviceagreementname = $adb->query_result($query_rate, $i, 'name');
				$serviceagreementstart_date = $adb->query_result($query_rate, $i, 'cf_6018');
				$serviceagreementend_date = $adb->query_result($query_rate, $i, 'cf_6020');
			?>

				<option value="<?php echo $serviceagreementid;?>" <?php if($agreement_id){if($serviceagreementid==$agreement_id){?> selected <?php }}?>><?php echo $serviceagreementname;?></option>

			<?php
			}    //// for loop end here //////
			?>
				</select>
			<?php
			}else{    ///// service agreement if condition end here /////?>
				<select name="cf_7914" class="select2 agreementdropdown" style="width:75%">
				<option value="">Select Option</option>
				</select>
			<?php
			}
			}else{   //// job condition end here ////?>
				<select name="cf_7914" class="select2 agreementdropdown" style="width:75%">
				<option value="">Select Option</option>
				</select>
			<?php
			}

  }  ////  cutomer ID if condition end here //////
}   //// process function end here ////
}
