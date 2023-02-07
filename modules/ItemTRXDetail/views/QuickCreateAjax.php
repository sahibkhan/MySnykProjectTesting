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

	public function requiresPermission(\Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		
		$permissions[] = array('module_parameter' => 'module', 'action' => 'CreateView');
		return $permissions;
	}

	public function process(Vtiger_Request $request) {
	
		$moduleName = $request->getModule();

		$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
		$moduleModel = $recordModel->getModule();
		
		$fieldList = $moduleModel->getFields();
		$requestFieldList = array_intersect_key($request->getAll(), $fieldList);

		$isRelationOperation = $request->get('relationOperation');
		//if it is relation edit

		$parentId = $request->get('returnrecord');
		$sourceModule = $request->get('returnmodule');
		
		if($isRelationOperation || $parentId) {
			$ItemTRXMaster_id = $request->get('returnrecord');	 
			$sourceModule = 'ItemTRXMaster';	
			$item_trx_info = Vtiger_Record_Model::getInstanceById($ItemTRXMaster_id, $sourceModule);	 
			$item_trx_wh_id = $item_trx_info->get('cf_5591');
			$item_trx_doc_type = $item_trx_info->getDisplayValue('cf_5583');
			$in_house = $item_trx_info->get('cf_5593');
			$glk_company_id = $item_trx_info->get('cf_5595');
			$item_trx_customer_id = $item_trx_info->get('cf_5597');
			
			$recordModel->set('cf_5615', $item_trx_wh_id); // Warehouse ID
			$recordModel->set('cf_5609', $item_trx_info->getDisplayValue('cf_5583'));// Document Type
			$recordModel->set('cf_5611', $item_trx_info->getDisplayValue('cf_5585'));// Document Number
			$recordModel->set('cf_5710','-'); //Batch ID
		}

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

		$recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_QUICKCREATE);
		//$recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_EDIT);
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

		$viewer->assign('SCRIPTS', $this->getHeaderScripts($request));

		$viewer->assign('IS_RELATION_OPERATION', $isRelationOperation);
		if($isRelationOperation || $parentId) {
			$viewer->assign('WHID', $item_trx_wh_id); //FROM WAREHOUSE
			$viewer->assign('DOC_TYPE', $item_trx_doc_type); //Document Type		
			$viewer->assign('TO_WHID', 0); //FROM WAREHOUSE
			$viewer->assign('CUSTOMER_ID', $item_trx_customer_id);
			$viewer->assign('IN_HOUSE', $in_house);
			$viewer->assign('GLK_COMPANY_ID', $glk_company_id);
			
			$whlocationlist = Vtiger_Field_Model::getWHLocationList($item_trx_wh_id, $parent_id=0, $item_trx_doc_type, $item_trx_customer_id, $in_house, $glk_company_id);
			$viewer->assign('WH_LOCATION_LIST', $whlocationlist);
		}	

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