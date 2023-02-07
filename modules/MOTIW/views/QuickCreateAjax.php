<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class MOTIW_QuickCreateAjax_View extends Vtiger_QuickCreateAjax_View {

	public function requiresPermission(\Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		
		$permissions[] = array('module_parameter' => 'module', 'action' => 'CreateView');
		return $permissions;
	}

	public function process(Vtiger_Request $request) {
		
		global $adb;
		
		$moduleName = $request->getModule();

		$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
		$moduleModel = $recordModel->getModule();
		
		$fieldList = $moduleModel->getFields();
		$requestFieldList = array_intersect_key($request->getAll(), $fieldList);

		foreach($requestFieldList as $fieldName => $fieldValue){
			$fieldModel = $fieldList[$fieldName];
			if($fieldModel->isEditable()) {
				$recordModel->set($fieldName, $fieldModel->getDBInsertValue($fieldValue));
			}
		}

		$fieldsInfo = array();
		foreach($fieldList as $name => $model){
			$fieldsInfo[$name] = $model->getFieldInfo();
		}
		
		$sourceRecordId = $request->get('sourceRecord');
		$sourceModule = $request->get('sourceModule');
		if($sourceModule == 'BO'){
		$recordBO = Vtiger_Record_Model::getInstanceById($sourceRecordId, 'BO');
		$name = $recordBO->get('name');
		$accountName = $recordBO->get('cf_1277'); 
		$refNo = $recordBO->get('cf_2687');
		$adb = PearDatabase::getInstance();
		$sql = "SELECT  accountid FROM vtiger_bo WHERE boid='".$sourceRecordId."'";
		$result = $adb->pquery($sql);
		$rows = $adb->num_rows($result);
		$accountId=$adb->query_result($result,0,'accountid');
		}
		
		if($sourceModule == 'VPO'){
		$recordVPO = Vtiger_Record_Model::getInstanceById($sourceRecordId, 'VPO');
		$name = $recordVPO->get('name');
		$accountId = $recordVPO->get('cf_1377');
		$recordAccounts = Vtiger_Record_Model::getInstanceById($accountId, 'Accounts');
		$accountName = $recordAccounts->get('accountname');
		$refNo = $recordVPO->get('cf_1379');
		
		}

		$recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_QUICKCREATE);
		$picklistDependencyDatasource = Vtiger_DependencyPicklist::getPicklistDependencyDatasource($moduleName);

		$viewer = $this->getViewer($request);
		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE', Vtiger_Functions::jsonEncode($picklistDependencyDatasource));
		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('SINGLE_MODULE', 'SINGLE_'.$moduleName);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
		$viewer->assign('RECORD_STRUCTURE', $recordStructureInstance->getStructure());
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('FIELDS_INFO', json_encode($fieldsInfo));
		$viewer->assign('isQuickCreate', 1);
		$viewer->assign('REF_NO', $refNo);
		$viewer->assign('ACCOUNT_NAME', $accountName);
		$viewer->assign('ACCOUNT_ID', $accountId);

		$viewer->assign('SCRIPTS', $this->getHeaderScripts($request));

		$viewer->assign('MAX_UPLOAD_LIMIT_MB', Vtiger_Util_Helper::getMaxUploadSize());
		$viewer->assign('MAX_UPLOAD_LIMIT_BYTES', Vtiger_Util_Helper::getMaxUploadSizeInBytes());
		echo $viewer->view('QuickCreate.tpl',$moduleName,true);

	}
	
	
	public function getHeaderScripts(Vtiger_Request $request) {
		
		$moduleName = $request->getModule();
		
		$jsFileNames = array(
			"modules.$moduleName.resources.Edit"
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return $jsScriptInstances;
	}
    
}