<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class InsuranceClaim_QuickCreateAjax_View extends Vtiger_QuickCreateAjax_View {

	public function requiresPermission(\Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		
		$permissions[] = array('module_parameter' => 'module', 'action' => 'CreateView');
		return $permissions;
	}

	public function process(Vtiger_Request $request) {
	
		$moduleName = $request->getModule();

		if($request->get('returnmodule') == 'CargoInsurance' && $request->get('returnrecord')) 
		{
			$sourceRecord = $request->get('returnrecord');
			$sourceModule = $request->get('returnmodule');
			$insurance_info = Vtiger_Record_Model::getInstanceById($sourceRecord, $sourceModule);
			$request->set('name', $insurance_info->get('name')); //Job ref no
			$request->set('cf_5439', $insurance_info->get('cf_3621')); // Insurance REF
			$request->set('cf_5455', $insurance_info->get('cf_3605'));//From Country
			$request->set('cf_5461', $insurance_info->get('cf_3609'));//To Country
			$request->set('cf_5457', $insurance_info->get('cf_3607')); //From City
			$request->set('cf_5459', $insurance_info->get('cf_3611')); //To City			
			//Beneficiary
			$request->set('cf_5441', $insurance_info->get('cf_3601')); 
			$request->set('cf_6408','Cargo Insurance');
		}
		if($request->get('returnmodule') == 'RRSInsurance' && $request->get('returnrecord')) 
		{
			$sourceRecord = $request->get('returnrecord');
			$sourceModule = $request->get('returnmodule');
			$insurance_info = Vtiger_Record_Model::getInstanceById($sourceRecord, $sourceModule);
			$request->set('name', $insurance_info->get('name')); //Job ref no
			$request->set('cf_5439', $insurance_info->get('cf_5209')); // Insurance REF
			$request->set('cf_5455', $insurance_info->get('cf_5199'));//From Country
			$request->set('cf_5461', $insurance_info->get('cf_5207'));//To Country
			$request->set('cf_5457', $insurance_info->get('cf_5195')); //From City
			$request->set('cf_5459', $insurance_info->get('cf_5203')); //To City				
			$request->set('cf_5441', $insurance_info->get('cf_5189')); //Beneficiary
			$request->set('cf_6408','Declaration');
		}
		elseif($request->get('returnmodule')=='Truck')
		{
			$request->set('cf_6408','Truck');
		}

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