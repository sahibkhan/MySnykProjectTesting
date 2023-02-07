<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class ProcurementApproval_Approvalparty_Action extends Vtiger_Action_Controller {

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
	//$job_id = $_GET['record'];
	print_r($request);exit;
	$job_id = $request->get('search');
	$query = "SELECT max(invoice_instruction_no) as invoice_instruction_no
						FROM vtiger_redexposetotheclient where name=$job_id";
	$query_result  = $adb->pquery($query);
	$last_invoice_instruction_number = $adb->query_result($query_result, 0, 'invoice_instruction_no');

	// $currentUser = Users_Record_Model::getCurrentUserModel();
	// $currency_code = $currentUser->get('currency_code');

	//	$thisIRDetail = Vtiger_Record_Model::getInstanceById($IR_ID, 'IntermediateRoutes');
	$response = new Vtiger_Response();
	$response->setResult($last_invoice_instruction_number+1);
//	$response->setResult($request);
	$response->emit();
    }


}
