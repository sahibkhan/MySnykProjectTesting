<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Class Jobexpencereport_Edit_View extends Vtiger_Edit_View {
    protected $record = false;
	function __construct() {
		parent::__construct();
		
	}
	
	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$record = $request->get('record');

		$recordPermission = Users_Privileges_Model::isPermitted($moduleName, 'EditView', $record);
		
		if(!$recordPermission) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		
	}
	
	public function get_parent_module($recordId=0)
	{
		$adb = PearDatabase::getInstance();
		
		$query_job_expense =  'SELECT * from vtiger_crmentityrel where vtiger_crmentityrel.relcrmid=? AND vtiger_crmentityrel.relmodule="Jobexpencereport"';
		$check_params = array($recordId);
		$result       = $adb->pquery($query_job_expense, $check_params);
		$row          = $adb->fetch_array($result);
		$module 	   = $row['module'];
		//$sourceModule = $row['module'];
		return $module;
	}
	
	public function get_job_id($recordId=0)
	{
		$adb = PearDatabase::getInstance();
		
		$query_job_expense =  'SELECT * from vtiger_crmentityrel where vtiger_crmentityrel.relcrmid=? AND vtiger_crmentityrel.module="Job" AND vtiger_crmentityrel.relmodule="Jobexpencereport"';
		$check_params = array($recordId);
		$result       = $adb->pquery($query_job_expense, $check_params);
		$row          = $adb->fetch_array($result);
		$job_id 	   = $row['crmid'];
		//$sourceModule = $row['module'];
		return $job_id;
	}
	
	public function get_job_id_from_fleet($recordId=0)
	{
		 $adb = PearDatabase::getInstance();
		 $checkjob = $adb->pquery("SELECT rel1.crmid as job_id FROM `vtiger_crmentityrel` as rel1 
				  							INNER JOIN vtiger_crmentityrel as rel2 ON rel1.relcrmid = rel2.crmid 
											where rel2.relcrmid='".$recordId."'", array());
		 $crmId = $adb->query_result($checkjob, 0, 'job_id');
		 $job_id = $crmId;
		 return $job_id;		  
	}

	public function process(Vtiger_Request $request) {
		$viewer = $this->getViewer ($request);
		$moduleName = $request->getModule();
		
		$record = $request->get('record');
		
        if(!empty($record) && $request->get('isDuplicate') == true) {
            $recordModel = $this->record?$this->record:Vtiger_Record_Model::getInstanceById($record, $moduleName);
            $viewer->assign('MODE', '');
        }else if(!empty($record)) {
            $recordModel = $this->record?$this->record:Vtiger_Record_Model::getInstanceById($record, $moduleName);
			
            $viewer->assign('RECORD_ID', $record);
            $viewer->assign('MODE', 'edit');
			$selling_expense_type = $recordModel->get('cf_1457');
			$jrertype = (($selling_expense_type=='Expence') ? 'expence' : 'selling');
			$request->set('jrertype', $jrertype);
			
			$parent_crmmodule = $this->get_parent_module($record);
			
			if($parent_crmmodule!='Fleettrip')
			{
			
			$job_id = $this->get_job_id($record);
			if(empty($job_id))
			{
				$job_id  = $this->get_job_id_from_fleet($record);
			}
			$sourceModule = 'Job';	
			$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule);
			
			
			$viewer->assign('FILE_TITLE', $job_info->get('cf_1186'));
			$viewer->assign('FILE_TITLE_FLAG', true);
			
			$current_user = Users_Record_Model::getCurrentUserModel();
			$company_id = $current_user->get('company_id');
			$job_office_id = $job_info->get('cf_1188');
			$current_user_office_id = $current_user->get('location_id');

						// Consinee details
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
				
			if ($recordModel->get('cf_1361') == ''){	
			  $request->set('cf_1361', $consinee_name); //Consinee Name
			} 
			
			if ($recordModel->get('cf_1363') == ''){
				 $request->set('cf_1363', $consinee_info); //Consinee Address
			}

			
			if($job_info->get('assigned_user_id')!=$current_user->getId())
			{
				if($job_office_id==$current_user_office_id){
					//$viewer->assign('FILE_TITLE_FLAG', true);
					//$viewer->assign('FILE_TITLE', $parentRecordModel->get('cf_1186'));
				}
				else{
					$db_sub = PearDatabase::getInstance();
					$query_sub = 'SELECT sub_jrer_file_title from vtiger_jobtask WHERE job_id=? and user_id=? limit 1';
					//$job_info->get('record_id') = jobid
					$params_sub = array($job_info->get('record_id'), $current_user->getId());
					
					$result_sub = $db_sub->pquery($query_sub,$params_sub);
					$file_title_info = $db_sub->fetch_array($result_sub);
					$viewer->assign('FILE_TITLE', (empty($file_title_info['sub_jrer_file_title']) ? $company_id : $file_title_info['sub_jrer_file_title']) );
					$viewer->assign('FILE_TITLE_FLAG', (empty($file_title_info['sub_jrer_file_title']) ? false : true ) );
				}
			}
			
			}
			else{
				
				$current_user = Users_Record_Model::getCurrentUserModel();
				$company_id = $current_user->get('company_id');
				$current_user_office_id = $current_user->get('location_id');
				
				$viewer->assign('FILE_TITLE', '85757');
				$viewer->assign('FILE_TITLE_FLAG', true);
			}
			
        } else {
            $recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
            $viewer->assign('MODE', '');
        }
        if(!$this->record){
            $this->record = $recordModel;
        }
        
		$moduleModel = $recordModel->getModule();
		$fieldList = $moduleModel->getFields();
		$requestFieldList = array_intersect_key($request->getAll(), $fieldList);

		foreach($requestFieldList as $fieldName=>$fieldValue){
			$fieldModel = $fieldList[$fieldName];
			$specialField = false;
			// We collate date and time part together in the EditView UI handling 
			// so a bit of special treatment is required if we come from QuickCreate 
			if ($moduleName == 'Calendar' && empty($record) && $fieldName == 'time_start' && !empty($fieldValue)) { 
				$specialField = true; 
				// Convert the incoming user-picked time to GMT time 
				// which will get re-translated based on user-time zone on EditForm 
				$fieldValue = DateTimeField::convertToDBTimeZone($fieldValue)->format("H:i"); 
                
			}
            
            if ($moduleName == 'Calendar' && empty($record) && $fieldName == 'date_start' && !empty($fieldValue)) { 
                $startTime = Vtiger_Time_UIType::getTimeValueWithSeconds($requestFieldList['time_start']);
                $startDateTime = Vtiger_Datetime_UIType::getDBDateTimeValue($fieldValue." ".$startTime);
                list($startDate, $startTime) = explode(' ', $startDateTime);
                $fieldValue = Vtiger_Date_UIType::getDisplayDateValue($startDate);
            }
			if($fieldModel->isEditable() || $specialField) {
				$recordModel->set($fieldName, $fieldModel->getDBInsertValue($fieldValue));
			}
		}
		$recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_EDIT);
		$picklistDependencyDatasource = Vtiger_DependencyPicklist::getPicklistDependencyDatasource($moduleName);

		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE',Zend_Json::encode($picklistDependencyDatasource));
		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
		$viewer->assign('RECORD_STRUCTURE', $recordStructureInstance->getStructure());
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		
		$isRelationOperation = $request->get('relationOperation');
		
		//if it is relation edit
		$viewer->assign('IS_RELATION_OPERATION', $isRelationOperation);
		if($isRelationOperation) {
			$viewer->assign('SOURCE_MODULE', $request->get('sourceModule'));
			$viewer->assign('SOURCE_RECORD', $request->get('sourceRecord'));
		}
		
		$viewer->assign('MAX_UPLOAD_LIMIT_MB', Vtiger_Util_Helper::getMaxUploadSize());
		$viewer->assign('MAX_UPLOAD_LIMIT', vglobal('upload_maxsize'));
		
		// Mehtab Code :: Hide or Show block according to expense and selling type 
		// 19-10-2015
		$jrertype = $request->get('jrertype');
		
		$viewer->assign('JRER_TYPE', ($jrertype=='expence') ? 'selling' : 'expence' );
		
		//$viewer->assign('cf_1457', 'Selling');
		$final_exchange_rate = 1;
		$viewer->assign('FINAL_EXCHANGE_RATE' , number_format ( $final_exchange_rate , 2 ,  "." , "," ));
		
		
		
		$current_user = Users_Record_Model::getCurrentUserModel();
		$department_id = $current_user->get('department_id');
		$location_id = $current_user->get('location_id');
		$company_id = $current_user->get('company_id');
		
		$viewer->assign('USER_COMPANY', $company_id);
		
		$viewer->view('EditView.tpl', $moduleName);
	}
}