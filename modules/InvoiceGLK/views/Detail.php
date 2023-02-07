<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class InvoiceGLK_Detail_View extends Vtiger_Detail_View {
	protected $record = false;
	protected $isAjaxEnabled = null;

	function __construct() {
		parent::__construct();
		$this->exposeMethod('getJobFileInfo');
	}


	public function getJobFileInfo(Vtiger_Request $request){
		global $adb;
		$data = array();
		$jobFileId = $request->get('jobfileid'); 
		$recordModel = Vtiger_Record_Model::getInstanceById($jobFileId, 'Job');
		$accountId = $recordModel->get('cf_1441');
		$jobFileRefNo = $recordModel->get('cf_1198');

		$details = array();
		$details['accountId'] = $accountId;
		$details['jobFileRefNo'] = $jobFileRefNo;
	
		$parseToJson = json_encode($details);
		return $parseToJson;
	}

}
