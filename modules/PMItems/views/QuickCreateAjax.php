<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class PMItems_QuickCreateAjax_View extends Vtiger_QuickCreateAjax_View {

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

		// $isRelationOperation = $request->get('relationOperation');

		// //if it is relation edit
		// $viewer->assign('IS_RELATION_OPERATION', $isRelationOperation);
		// if($isRelationOperation) {
			
		// }
		$viewer->assign('SOURCE_MODULE', $request->get('returnmodule'));
		$viewer->assign('SOURCE_RECORD', $request->get('returnrecord'));
		$parentId = $request->get('returnrecord');
		$sourceModule = $request->get('returnmodule');
		$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentId, $sourceModule);
		
		$createdtime =  $parentRecordModel->get('CreatedTime');
		$createdtime_ex = date('Y-m-d', strtotime($createdtime));
		
		$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency($parentRecordModel->get('cf_4271'));
		$file_title_currency = $reporting_currency;
		$viewer->assign('FILE_TITLE_CURRENCY', $file_title_currency);
		
		$currency_id = Vtiger_CompanyList_UIType::getCompanyReportingCurrencyID($parentRecordModel->get('cf_4271'));
		$viewer->assign('FILE_CURRENCY', $currency_id);
		
		include("include/Exchangerate/exchange_rate_class.php");
		if($file_title_currency!='USD')
		{			
			$final_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $createdtime_ex);			
		}else{
			$final_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $createdtime_ex);
		}
		
		$viewer->assign('FINAL_EXCHANGE_RATE' , number_format ( $final_exchange_rate , 2 ,  "." , "," ));

		$viewer->assign('MAX_UPLOAD_LIMIT_MB', Vtiger_Util_Helper::getMaxUploadSize());
		$viewer->assign('MAX_UPLOAD_LIMIT_BYTES', Vtiger_Util_Helper::getMaxUploadSizeInBytes());

		if(!isset($_SESSION['trid']))
		{
			$_SESSION['trid'] = 1;
		}
		else{
			$i =$_SESSION['trid'];
			$_SESSION['trid'] = ++$i;			
		}		
		$viewer->assign('TRID', $_SESSION['trid']);
		
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