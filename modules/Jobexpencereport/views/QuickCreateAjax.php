<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Jobexpencereport_QuickCreateAjax_View extends Vtiger_QuickCreateAjax_View {

	public function requiresPermission(\Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		
		$permissions[] = array('module_parameter' => 'module', 'action' => 'CreateView');
		return $permissions;
	}

	public function process(Vtiger_Request $request) {
		global $adb;
		$moduleName = $request->getModule();

		$sourcemodule_check = $request->get('sourceModule');
		$returnmodule = $request->get('returnmodule');
		if($sourcemodule_check=='Job' || $returnmodule=='Job'){
			$job_id = $request->get('returnrecord');
			$sourceModule = $returnmodule;
			
			$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule);	 
			$consinee_name = $job_info->get('cf_1074');
			
			$d_city = $job_info->get('cf_1510');
			$d_country = $job_info->get('cf_1506');
			$delivery_address = $job_info->get('cf_1514');
			if ($d_city != '') $consinee_info = $d_city.PHP_EOL;

			$sql_country = $adb->pquery("SELECT c.country_name as country_name FROM `countries` as c 
										 WHERE c.country_code = '$d_country'", array());
			$o_country = $adb->query_result($sql_country, 0, 'country_name'); 

			if ($o_country != '') $consinee_info .= $o_country.PHP_EOL;
			if ($delivery_address != '') $consinee_info .= $delivery_address.PHP_EOL;

			if (!empty($consinee_name)) $request->set('cf_1361', $consinee_name); //Consinee Name
			if (!empty($consinee_info)) $request->set('cf_1363', $consinee_info); //Consinee Address

			$agent_vendor_id = $job_info->get('cf_1082');
			if(!empty($agent_vendor_id))
			{
				$request->set('cf_1367',$job_info->get('cf_1082'));			
			}

			$bill_to_id = $job_info->get('cf_1441');
			if(!empty($bill_to_id))
			{
				$request->set('cf_1445', $job_info->get('cf_1441'));			
			}

			
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

		//$recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_QUICKCREATE);
		$recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_EDIT);
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

		$jrertype = $request->get('jrertype');		
		$viewer->assign('JRER_TYPE', ($jrertype=='expence') ? 'selling' : 'expence' );

		if($sourcemodule_check=='Job' || $returnmodule=='Job'){
			$job_id = $request->get('returnrecord');
			$sourceModule = $returnmodule;

			include("include/Exchangerate/exchange_rate_class.php");
			$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule);
			$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info->get('cf_1186'));
			$file_title_currency = $reporting_currency;		
			$viewer->assign('FILE_TITLE_CURRENCY', $file_title_currency);

			$currency_id = Vtiger_CompanyList_UIType::getCompanyReportingCurrencyID(@$job_info->get('cf_1186'));
			$viewer->assign('FILE_CURRENCY', $currency_id);
			if($file_title_currency!='USD')
			{			
				$final_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, date('Y-m-d'));			
			}else{
				$final_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, date('Y-m-d'));
			}
			$viewer->assign('FINAL_EXCHANGE_RATE' , number_format ( $final_exchange_rate , 2 ,  "." , "" ));

			$ref_no = @$job_info->get('cf_1198');		
			$viewer->assign('REFERENCE_NO' , $ref_no);

			$current_user = Users_Record_Model::getCurrentUserModel();
			$department_id = $current_user->get('department_id');
			$location_id = $current_user->get('location_id');
			$company_id = $current_user->get('company_id');
			
			$viewer->assign('USER_COMPANY', $company_id);
			$viewer->assign('USER_DEPARTMENT', $department_id);
			$viewer->assign('USER_LOCATION', $location_id);

			$viewer->assign('FILE_TITLE', $job_info->get('cf_1186'));
			$viewer->assign('FILE_TITLE_FLAG', true);

			$job_office_id = $job_info->get('cf_1188');
			$current_user_office_id = $current_user->get('location_id');

			if($job_info->get('assigned_user_id')!=$current_user->getId())
			{
				if($job_office_id==$current_user_office_id){
					//$viewer->assign('FILE_TITLE_FLAG', true);
					//$viewer->assign('FILE_TITLE', $job_info->get('cf_1186'));
				}
				else{
					$query_sub = 'SELECT sub_jrer_file_title from vtiger_jobtask WHERE job_id=? and user_id=? limit 1';
					$params_sub = array($job_info->get('record_id'), $current_user->getId());
					$result_sub = $adb->pquery($query_sub,$params_sub);
					$file_title_info = $adb->fetch_array($result_sub);
					$viewer->assign('FILE_TITLE', (empty($file_title_info['sub_jrer_file_title']) ? $company_id : $file_title_info['sub_jrer_file_title']) );
					$viewer->assign('FILE_TITLE_FLAG', (empty($file_title_info['sub_jrer_file_title']) ? false : true ) );
					$assignee_user_company_id = (empty($file_title_info['sub_jrer_file_title']) ? $company_id : $file_title_info['sub_jrer_file_title']);
					$currency_id = Vtiger_CompanyList_UIType::getCompanyReportingCurrencyID(@$assignee_user_company_id);
					$viewer->assign('FILE_CURRENCY', $currency_id);
				}
			}

			$viewer->assign('AGENT_VENDOR_ID', '');
			$viewer->assign('AGENT_VENDOR_DISPLAY_NAME', '');
			$agent_vendor_id = $job_info->get('cf_1082');
			if(!empty($agent_vendor_id))
			{
				$viewer->assign('AGENT_VENDOR_ID', $job_info->get('cf_1082'));
				$agent_info = Vtiger_Record_Model::getInstanceById($job_info->get('cf_1082'), 'Accounts');
				$viewer->assign('AGENT_VENDOR_DISPLAY_NAME', @$agent_info->get('accountname'));
			}

			$viewer->assign('BILL_TO_ID', '');
			$viewer->assign('BILL_TO_DISPLAY_NAME', '');
			$bill_to_id = $job_info->get('cf_1441');
			if(!empty($bill_to_id))
			{
				$viewer->assign('BILL_TO_ID', $job_info->get('cf_1441'));
				$bill_to_info = Vtiger_Record_Model::getInstanceById($job_info->get('cf_1441'), 'Accounts');
				$viewer->assign('BILL_TO_DISPLAY_NAME', @$bill_to_info->get('accountname'));
			}
		}
		else{

			$current_user = Users_Record_Model::getCurrentUserModel();
			$department_id = $current_user->get('department_id');
			$location_id = $current_user->get('location_id');
			$company_id = $current_user->get('company_id');
			
			$viewer->assign('USER_COMPANY', $company_id);
			$viewer->assign('USER_DEPARTMENT', $department_id);
			$viewer->assign('USER_LOCATION', $location_id);
			
			$viewer->assign('FILE_TITLE', $company_id);
			//$viewer->assign('FILE_TITLE', '85757');
			$viewer->assign('FILE_TITLE_FLAG', true);

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