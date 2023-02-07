<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class ItemTRXDetail_QuickCreateAjax_View extends Vtiger_QuickCreateAjax_View {

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();

		if (!(Users_Privileges_Model::isPermitted($moduleName, 'EditView'))) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED', $moduleName));
		}
	}

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();

		$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
		$moduleModel = $recordModel->getModule();
		
		$fieldList = $moduleModel->getFields();
		$requestFieldList = array_intersect_key($request->getAll(), $fieldList);
		
		$isRelationOperation = $request->get('relationOperation');
		//if it is relation edit
		
		if($isRelationOperation) {
			$job_id = $request->get('sourceRecord');	 
			$sourceModule = 'ItemTRXMaster';	
			$item_trx_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule);	 
			$item_trx_wh_id = $item_trx_info->get('cf_5591');
			$item_trx_doc_type = $item_trx_info->getDisplayValue('cf_5583');
			$in_house = $item_trx_info->get('cf_5593');
			$glk_company_id = $item_trx_info->get('cf_5595');
			$item_trx_customer_id = $item_trx_info->get('cf_5597');
			
			$recordModel->set('cf_5615', $item_trx_wh_id); // Warehouse ID
			$recordModel->set('cf_5609', $item_trx_info->getDisplayValue('cf_5583'));// Document Type
			$recordModel->set('cf_5611', $item_trx_info->getDisplayValue('cf_5585'));// Document Number
			
		}

		foreach($requestFieldList as $fieldName => $fieldValue){
			$fieldModel = $fieldList[$fieldName];
			if($fieldModel->isEditable()) {
				$recordModel->set($fieldName, $fieldModel->getDBInsertValue($fieldValue));
			}
		}

		//$recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_QUICKCREATE);
		$recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_EDIT);
		$picklistDependencyDatasource = Vtiger_DependencyPicklist::getPicklistDependencyDatasource($moduleName);

		$viewer = $this->getViewer($request);
		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE',Zend_Json::encode($picklistDependencyDatasource));
		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('SINGLE_MODULE', 'SINGLE_'.$moduleName);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
		$viewer->assign('RECORD_STRUCTURE', $recordStructureInstance->getStructure());
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		
		$viewer->assign('SCRIPTS', $this->getHeaderScripts($request));
		
		$viewer->assign('IS_RELATION_OPERATION', $isRelationOperation);
		if($isRelationOperation) {
			$viewer->assign('WHID', $item_trx_wh_id); //FROM WAREHOUSE
			$viewer->assign('DOC_TYPE', $item_trx_doc_type); //Document Type		
			$viewer->assign('TO_WHID', 0); //FROM WAREHOUSE
			$viewer->assign('CUSTOMER_ID', $item_trx_customer_id);
			$viewer->assign('IN_HOUSE', $in_house);
			$viewer->assign('GLK_COMPANY_ID', $glk_company_id);
		}	
		
		
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