<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
class Procurement_FindAgreement_Action extends Vtiger_Save_Action {

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
	$file_title = $request->get('file_title');

	// $invoice_date = date("Y-m-d", strtotime('-1 year', strtotime($request->get('invoice_date'))));
	$invoice_date = date("Y-m-d", strtotime($request->get('invoice_date')));
	$action = $request->get('actions');

	$compare_date = date('Y-m-d');
	if($customer_id){

			if($record_id!=''){
				$pm_info_detail = Vtiger_Record_Model::getInstanceById($record_id, 'Procurement'); //procurement
				$createdtime =  $pm_info_detail->get('createdtime');
				$compare_date = date('Y-m-d', strtotime($createdtime));
				$agreementNo =  $pm_info_detail->get('proc_agreement_no');
				$cleanAgreementNo = explode('(Expired)',$agreementNo);
				$cleanedAgreementNo = trim($cleanAgreementNo[0]);
			}else{
					$agreementNo = 0 ;
			}
				
			 $query = "SELECT vtiger_serviceagreement.name as agreement_no,vtiger_serviceagreementcf.cf_6020 as expiry_date FROM vtiger_serviceagreement 
													INNER JOIN vtiger_serviceagreementcf ON vtiger_serviceagreementcf.serviceagreementid = vtiger_serviceagreement.serviceagreementid
													INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid =  vtiger_serviceagreementcf.serviceagreementid
													WHERE vtiger_serviceagreementcf.cf_6094='".$customer_id."'  
													
													
													AND  vtiger_crmentity.deleted = 0
			 order by vtiger_serviceagreementcf.cf_6018 DESC
			 "; //AND '".$compare_date."' between vtiger_serviceagreementcf.cf_6018 AND vtiger_serviceagreementcf.cf_6020
			 
			 //AND vtiger_serviceagreementcf.cf_6026='$file_title'

				$query_rate =  $adb->pquery($query);

			if($adb->num_rows($query_rate)>0){     ///// check if service agreement query return any data //////?>

			<!-- <select name="cf_1445" data-rule-required="true" aria-required="true" class="select2 agreementdropdown" style="width:75%"> -->
				<select name="proc_agreement_no" class="select2 agreementdropdown" required style="width:75%">
				<option value="">Select Option</option>
			<?php
				for($i=0;$i<$adb->num_rows($query_rate);$i++){   /// for loop start from here ////
			    $agreement_expired = '';
				$serviceagreementid = $adb->query_result($query_rate, $i, 'serviceagreementid');
				$actual_serviceagreementname = $adb->query_result($query_rate, $i, 'agreement_no');
				$serviceagreementstart_date = $adb->query_result($query_rate, $i, 'cf_6018');
				$serviceagreementend_date = $adb->query_result($query_rate, $i, 'expiry_date');
				$serviceagreementname = $actual_serviceagreementname;
				if($serviceagreementend_date < date('Y-m-d')) 
				{ 
					$agreement_expired = ' (Agreement Expired, Plz Renew for future requests)'; 
					//$serviceagreementname = $actual_serviceagreementname.' (Expired)';
				}
			?>

				<option value="<?php echo $serviceagreementname;?>" <?php if($agreementNo){if($serviceagreementname==$agreementNo){?> selected <?php }}?>><?php echo $serviceagreementname.$agreement_expired;?></option>

			<?php
			}    //// for loop end here //////
			?>
				</select>
			<?php
			}else{    ///// service agreement if condition end here /////?>
				<select name="proc_agreement_no" class="select2 agreementdropdown" required style="width:75%">
				<option value="">Select Option</option>
				</select>
			<?php
			}
			

  }  ////  cutomer ID if condition end here //////
}   //// process function end here ////
}
