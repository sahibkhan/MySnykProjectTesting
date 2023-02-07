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

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		
		if (!(Users_Privileges_Model::isPermitted($moduleName, 'EditView'))) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED', $moduleName));
		}
	}

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		
		$sourcemodule_check = $request->get('sourceModule');
		
		if($sourcemodule_check!='Fleettrip' && $sourcemodule_check!='WagonTrip')
		{
			// Consinee details
			$job_id = $request->get('sourceRecord');	 
			$sourceModule = 'Job';	
			$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule);	 
			$consinee_name = $job_info->get('cf_1074');
			
			$d_city = $job_info->get('cf_1510');
			$d_country = $job_info->get('cf_1506');
			$delivery_address = $job_info->get('cf_1514');
		    if ($d_city != '') $consinee_info = $d_city.PHP_EOL;
		  
		    $adb = PearDatabase::getInstance();				
		    $sql_country = $adb->pquery("SELECT c.country_name as country_name FROM `countries` as c 
										where c.country_code = '$d_country'", array());
		    $o_country = $adb->query_result($sql_country, 0, 'country_name'); 
		  
			if ($o_country != '') $consinee_info .= $o_country.PHP_EOL;
			if ($delivery_address != '') $consinee_info .= $delivery_address.PHP_EOL;
				
			if (!empty($consinee_name)) $request->set('cf_1361', $consinee_name); //Consinee Name
			if (!empty($consinee_info)) $request->set('cf_1363', $consinee_info); //Consinee Address
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
		
		$isRelationOperation = $request->get('relationOperation');

		//if it is relation edit
		$viewer->assign('IS_RELATION_OPERATION', $isRelationOperation);
		if($isRelationOperation) {
			$viewer->assign('SOURCE_MODULE', $request->get('sourceModule'));
			$viewer->assign('SOURCE_RECORD', $request->get('sourceRecord'));
		}
		
		$jrertype = $request->get('jrertype');
		
		$viewer->assign('JRER_TYPE', ($jrertype=='expence') ? 'selling' : 'expence' );
		
		if($sourcemodule_check!='Fleettrip' && $sourcemodule_check!='WagonTrip')
		{
		$job_id = $request->get('sourceRecord');
		include("include/Exchangerate/exchange_rate_class.php");
		$job_info = get_job_details($job_id);
		if(empty($job_info))
	    {
		   $job_id = $this->get_job_id_from_fleet($job_id);
		   $job_info = get_job_details($job_id);
		   $request->set('sourceModule', 'Job');
	    }
		
		//$reporting_currency = get_company_details(@$job_info['cf_1186'], 'currency');
		$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info['cf_1186']);
		$file_title_currency = $reporting_currency;		
		$viewer->assign('FILE_TITLE_CURRENCY', $file_title_currency);
		
		$currency_id = Vtiger_CompanyList_UIType::getCompanyReportingCurrencyID(@$job_info['cf_1186']);
		$viewer->assign('FILE_CURRENCY', $currency_id);
		
		if($file_title_currency!='USD')
		{			
			$final_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, date('Y-m-d'));			
		}else{
			$final_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, date('Y-m-d'));
		}
		$viewer->assign('FINAL_EXCHANGE_RATE' , number_format ( $final_exchange_rate , 2 ,  "." , "" ));
		
		$ref_no = @$job_info['cf_1198'];		
		$viewer->assign('REFERENCE_NO' , $ref_no);
		
		//if(empty($record)) {
		$current_user = Users_Record_Model::getCurrentUserModel();
		$department_id = $current_user->get('department_id');
		$location_id = $current_user->get('location_id');
		$company_id = $current_user->get('company_id');
		
		$viewer->assign('USER_COMPANY', $company_id);
		$viewer->assign('USER_DEPARTMENT', $department_id);
		$viewer->assign('USER_LOCATION', $location_id);
		//}
		
		//$parentId = $request->get('sourceRecord');
		$parentId = $job_id;
		$sourceModule = $request->get('sourceModule');
		$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentId, $sourceModule);
		/*
		$viewer->assign('AGENT_VENDOR_ID', $parentRecordModel->get('cf_1082'));
		$agent_info = Vtiger_Record_Model::getInstanceById($parentRecordModel->get('cf_1082'), 'Accounts');
		$viewer->assign('AGENT_VENDOR_DISPLAY_NAME', @$agent_info->get('accountname'));
		
		$viewer->assign('BILL_TO_ID', $parentRecordModel->get('cf_1441'));
		$bill_to_info = Vtiger_Record_Model::getInstanceById($parentRecordModel->get('cf_1441'), 'Accounts');
		$viewer->assign('BILL_TO_DISPLAY_NAME', @$bill_to_info->get('accountname'));
		*/
		
		$viewer->assign('FILE_TITLE', $parentRecordModel->get('cf_1186'));
		$viewer->assign('FILE_TITLE_FLAG', true);
		
		//$current_user = Users_Record_Model::getCurrentUserModel();
		$job_office_id = $parentRecordModel->get('cf_1188');
		$current_user_office_id = $current_user->get('location_id');
		if($parentRecordModel->get('assigned_user_id')!=$current_user->getId())
		{
			if($job_office_id==$current_user_office_id){
				//$viewer->assign('FILE_TITLE_FLAG', true);
				//$viewer->assign('FILE_TITLE', $parentRecordModel->get('cf_1186'));
			}
			else{
				$db_sub = PearDatabase::getInstance();
				$query_sub = 'SELECT sub_jrer_file_title from vtiger_jobtask WHERE job_id=? and user_id=? limit 1';
				$params_sub = array($parentRecordModel->get('record_id'), $current_user->getId());
				$result_sub = $db_sub->pquery($query_sub,$params_sub);
				$file_title_info = $db_sub->fetch_array($result_sub);
			  	$viewer->assign('FILE_TITLE', (empty($file_title_info['sub_jrer_file_title']) ? $company_id : $file_title_info['sub_jrer_file_title']) );
				$viewer->assign('FILE_TITLE_FLAG', (empty($file_title_info['sub_jrer_file_title']) ? false : true ) );
			}
		}

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
		
		}
		else{
			
			$current_user = Users_Record_Model::getCurrentUserModel();
			$department_id = $current_user->get('department_id');
			$location_id = $current_user->get('location_id');
			$company_id = $current_user->get('company_id');
			
			$viewer->assign('USER_COMPANY', $company_id);
			$viewer->assign('USER_DEPARTMENT', $department_id);
			$viewer->assign('USER_LOCATION', $location_id);
			
			$viewer->assign('FILE_TITLE', '85757');
			$viewer->assign('FILE_TITLE_FLAG', true);
			
		}

		echo $viewer->view('QuickCreate.tpl',$moduleName,true);

	}
	
	public function get_job_id_from_fleet($recordId=0)
	{
		 $adb = PearDatabase::getInstance();
		 $checkjob = $adb->pquery("SELECT crmid as job_id FROM `vtiger_crmentityrel` where relcrmid='".$recordId."' AND module='Job' AND relmodule='Fleet'", array());
		 $crmId = $adb->query_result($checkjob, 0, 'job_id');
		 $job_id = $crmId;
		 return $job_id;		  
	}
	
	
	public function getHeaderScripts(Vtiger_Request $request) {
		
		$moduleName = $request->getModule();
		
		$jsFileNames = array(
			"modules.$moduleName.resources.Edit"
			//,"modules.$moduleName.resources.Exchangerate"
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return $jsScriptInstances;
	}
}