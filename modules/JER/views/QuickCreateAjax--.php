<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

//class JER_QuickCreateAjax_View extends Vtiger_QuickCreateAjax_View {
class JER_QuickCreateAjax_View extends Vtiger_Edit_View {

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
		//$recordStructureInstance_final = $recordStructureInstance->getStructure();
		$viewer->assign('RECORD_STRUCTURE', $recordStructureInstance->getStructure());
		//$viewer->assign('RECORD_STRUCTURE', $recordStructureInstance_final);
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());	
		
		$viewer->assign('SCRIPTS', $this->getHeaderScripts($request));
		
		$isRelationOperation = $request->get('relationOperation');

		//if it is relation edit
		$viewer->assign('IS_RELATION_OPERATION', $isRelationOperation);
		if($isRelationOperation) {
			$viewer->assign('SOURCE_MODULE', $request->get('sourceModule'));
			$viewer->assign('SOURCE_RECORD', $request->get('sourceRecord'));
		}
		
		//Mehtab Code
		$parentId = $request->get('sourceRecord');
		$sourceModule = $request->get('sourceModule');
		$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentId, $sourceModule);
			
		
		$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency($parentRecordModel->get('cf_1186'));
		$file_title_currency = $reporting_currency;
		$viewer->assign('FILE_TITLE_CURRENCY', $file_title_currency);
		
		$currency_id = Vtiger_CompanyList_UIType::getCompanyReportingCurrencyID($parentRecordModel->get('cf_1186'));
		$viewer->assign('FILE_CURRENCY', $currency_id);
		
		include("include/Exchangerate/exchange_rate_class.php");
		if($file_title_currency!='USD')
		{			
			$final_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, date('Y-m-d'));			
		}else{
			$final_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, date('Y-m-d'));
		}
		
		$viewer->assign('FINAL_EXCHANGE_RATE' , number_format ( $final_exchange_rate , 2 ,  "." , "," ));		
		//Mehtab Code
		if(!isset($_SESSION['trid']))
		{
			$_SESSION['trid'] = 1;
		}
		else{
			$i =$_SESSION['trid'];
			$_SESSION['trid'] = ++$i;			
		}		
		$viewer->assign('TRID', $_SESSION['trid']);
		
		//if(empty($record)) {
		$current_user = Users_Record_Model::getCurrentUserModel();
		$department_id = $current_user->get('department_id');
		$location_id = $current_user->get('location_id');
		$company_id = $current_user->get('company_id');
		
		$viewer->assign('USER_COMPANY', $company_id);
		$viewer->assign('USER_DEPARTMENT', $department_id);
		$viewer->assign('USER_LOCATION', $location_id);
		
		//}
		/*
		$viewer->assign('AGENT_VENDOR_ID', $parentRecordModel->get('cf_1082'));
		$agent_info = Vtiger_Record_Model::getInstanceById($parentRecordModel->get('cf_1082'), 'Accounts');
		$viewer->assign('AGENT_VENDOR_DISPLAY_NAME', @$agent_info->get('accountname'));
		
		$viewer->assign('BILL_TO_ID', $parentRecordModel->get('cf_1441'));
		$bill_to_info = Vtiger_Record_Model::getInstanceById($parentRecordModel->get('cf_1441'), 'Accounts');
		$viewer->assign('BILL_TO_DISPLAY_NAME', @$bill_to_info->get('accountname'));
		*/

		$viewer->assign('AGENT_VENDOR_ID', '');
		$viewer->assign('AGENT_VENDOR_DISPLAY_NAME', '');
		$agent_vendor_id = $parentRecordModel->get('cf_1082');
		if(!empty($agent_vendor_id))
		{
		$viewer->assign('AGENT_VENDOR_ID', $parentRecordModel->get('cf_1082'));
		$agent_info = Vtiger_Record_Model::getInstanceById($parentRecordModel->get('cf_1082'), 'Accounts');
		$viewer->assign('AGENT_VENDOR_DISPLAY_NAME', @$agent_info->get('accountname'));
		}
		
		$viewer->assign('BILL_TO_ID', '');
		$viewer->assign('BILL_TO_DISPLAY_NAME', '');
		$bill_to_id = $parentRecordModel->get('cf_1441');
		if(!empty($bill_to_id))
		{
		$viewer->assign('BILL_TO_ID', $parentRecordModel->get('cf_1441'));
		$bill_to_info = Vtiger_Record_Model::getInstanceById($parentRecordModel->get('cf_1441'), 'Accounts');
		$viewer->assign('BILL_TO_DISPLAY_NAME', @$bill_to_info->get('accountname'));
		}
		
		
		echo $viewer->view('QuickCreateCosting.tpl',$moduleName,true);
	}
	
	
	public function getHeaderScripts(Vtiger_Request $request) {
		
		$moduleName = $request->getModule();
		
		$jsFileNames = array(
			"modules.$moduleName.resources.Edit"
			//"modules.$moduleName.resources.Exchangerate"
		);
		
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return $jsScriptInstances;
	}
}