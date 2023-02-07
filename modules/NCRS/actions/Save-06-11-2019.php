<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
class NCRS_Save_Action extends Vtiger_Save_Action {

	function __construct() {
		
		parent::__construct();
		$this->exposeMethod('approveNCR');
	}

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$record = $request->get('record');
		
		$module_get = $_GET['module'];
		$record_get = $_GET['record'];
			
		if ((!Users_Privileges_Model::isPermitted($moduleName, 'Save', $record)) ) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
		
	}

	public function process(Vtiger_Request $request) {

		$mode = $request->get('mode');
        if(!empty($mode)) {
            $this->invokeExposedMethod($mode,$request);
			exit;
		}

		$recordModel = $this->saveRecord($request);
		if($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			$parentRecordId = $request->get('sourceRecord');
			$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentRecordId, $parentModuleName);
			//TODO : Url should load the related list instead of detail view of record
			$loadUrl = $parentRecordModel->getDetailViewUrl();
		} else if ($request->get('returnToList')) {
			$loadUrl = $recordModel->getModule()->getListViewUrl();
		} else {
			$loadUrl = $recordModel->getDetailViewUrl();
		}
		header("Location: $loadUrl");
	}

	/**
	 * Function to save record
	 * @param <Vtiger_Request> $request - values of the record
	 * @return <RecordModel> - record Model of saved record
	 */
	public function saveRecord($request) {
		$recordModel = $this->getRecordModelFromRequest($request);
		$_SESSION['sendmsg_repeat'] = $request->getModule();
		
		$recordModel->save();
		if($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			$parentModuleModel = Vtiger_Module_Model::getInstance($parentModuleName);
			$parentRecordId = $request->get('sourceRecord');
			$relatedModule = $recordModel->getModule();
			$relatedRecordId = $recordModel->getId();

			$relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relatedModule);
			$relationModel->addRelation($parentRecordId, $relatedRecordId);
		}
		return $recordModel;
	}

	function approveNCR(Vtiger_Request $request)
	{
		global $db;
		$db = PearDatabase::getInstance();
		$moduleName = $request->getModule();
		
		$current_user = Users_Record_Model::getCurrentUserModel();
		$initiator_approved_by 		= $current_user->get('first_name').' '.$current_user->get('last_name');
				
		$recordId = $request->get('record');
		
		$db->pquery("UPDATE vtiger_ncrscf SET cf_6428=?, cf_6426=? WHERE ncrsid=? AND cf_6426=?", array($initiator_approved_by,'In Progress',$recordId,'Pending'));
		
		$recordModel = $this->record?$this->record:Vtiger_Record_Model::getInstanceById($recordId, $moduleName);

		$ncr_obj = new NCRS();
		
		$ncr_obj->notification_NCR($recordId,$module, 'Approved');
		
		$loadUrl = $recordModel->getDetailViewUrl();
		ob_clean();
		
		header("Location: https://erp.globalink.net/".$loadUrl); 
		exit; 
	}

	/**
	 * Function to get the record model based on the request parameters
	 * @param Vtiger_Request $request
	 * @return Vtiger_Record_Model or Module specific Record Model instance
	 */
	protected function getRecordModelFromRequest(Vtiger_Request $request) {

		$moduleName = $request->getModule();
		$recordId = $request->get('record');

		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

		if(!empty($recordId)) {
			$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
			$modelData = $recordModel->getData();
			$recordModel->set('id', $recordId);
			$recordModel->set('mode', 'edit');
		} else {
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$modelData = $recordModel->getData();
			$recordModel->set('mode', '');
		}

		$fieldModelList = $moduleModel->getFields();
		foreach ($fieldModelList as $fieldName => $fieldModel) {
			$fieldValue = $request->get($fieldName, null);
			$fieldDataType = $fieldModel->getFieldDataType();
			if($fieldDataType == 'time'){
				$fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
			}
			if($fieldValue !== null) {
				if(!is_array($fieldValue)) {
					$fieldValue = trim($fieldValue);
				}
				$recordModel->set($fieldName, $fieldValue);
			}
		}
		return $recordModel;
	}
}
