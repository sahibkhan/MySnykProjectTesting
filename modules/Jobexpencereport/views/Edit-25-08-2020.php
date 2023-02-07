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

	public function requiresPermission(\Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$record = $request->get('record');
		$actionName = 'CreateView';
		if ($record && !$request->get('isDuplicate')) {
			$actionName = 'EditView';
		}
		$permissions[] = array('module_parameter' => 'module', 'action' => $actionName, 'record_parameter' => 'record');
		return $permissions;
	}
	
	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$record = $request->get('record');

		$nonEntityModules = array('Users', 'Events', 'Calendar', 'Portal', 'Reports', 'Rss', 'EmailTemplates');
		if ($record && !in_array($moduleName, $nonEntityModules)) {
			$recordEntityName = getSalesEntityType($record);
			if ($recordEntityName !== $moduleName) {
				throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
			}
		}
		return parent::checkPermission($request);
	}

	public function setModuleInfo($request, $moduleModel) {
		$fieldsInfo = array();
		$basicLinks = array();
		$settingLinks = array();

		$moduleFields = $moduleModel->getFields();
		foreach($moduleFields as $fieldName => $fieldModel){
			$fieldsInfo[$fieldName] = $fieldModel->getFieldInfo();
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('FIELDS_INFO', json_encode($fieldsInfo));
		$viewer->assign('MODULE_BASIC_ACTIONS', $basicLinks);
		$viewer->assign('MODULE_SETTING_ACTIONS', $settingLinks);
	}

	function preProcess(Vtiger_Request $request, $display=true) { 
		//Vtiger7 - TO show custom view name in Module Header
		$viewer = $this->getViewer ($request); 
		$moduleName = $request->getModule(); 
		$viewer->assign('CUSTOM_VIEWS', CustomView_Record_Model::getAllByGroup($moduleName)); 
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$record = $request->get('record'); 
		if(!empty($record) && $moduleModel->isEntityModule()) { 
			$recordModel = $this->record?$this->record:Vtiger_Record_Model::getInstanceById($record, $moduleName); 
			$viewer->assign('RECORD',$recordModel); 
		}  

		$duplicateRecordsList = array();
		$duplicateRecords = $request->get('duplicateRecords');
		if (is_array($duplicateRecords)) {
			$duplicateRecordsList = $duplicateRecords;
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('DUPLICATE_RECORDS', $duplicateRecordsList);
		parent::preProcess($request, $display); 
	}

	public function process(Vtiger_Request $request) {
		$viewer = $this->getViewer ($request);
		$moduleName = $request->getModule();
		$record = $request->get('record');
		if(!empty($record) && $request->get('isDuplicate') == true) {
			$recordModel = $this->record?$this->record:Vtiger_Record_Model::getInstanceById($record, $moduleName);
			$viewer->assign('MODE', '');

			//While Duplicating record, If the related record is deleted then we are removing related record info in record model
			$mandatoryFieldModels = $recordModel->getModule()->getMandatoryFieldModels();
			foreach ($mandatoryFieldModels as $fieldModel) {
				if ($fieldModel->isReferenceField()) {
					$fieldName = $fieldModel->get('name');
					if (Vtiger_Util_Helper::checkRecordExistance($recordModel->get($fieldName))) {
						$recordModel->set($fieldName, '');
					}
				}
			}  
		}else if(!empty($record)) {
			$recordModel = $this->record?$this->record:Vtiger_Record_Model::getInstanceById($record, $moduleName);
			$viewer->assign('RECORD_ID', $record);
			$viewer->assign('MODE', 'edit');
			
			$selling_expense_type = $recordModel->get('cf_1457');
			$jrertype = (($selling_expense_type=='Expence') ? 'expence' : 'selling');
			$request->set('jrertype', $jrertype);

			$returnmodule = $request->get('returnmodule');
			if($returnmodule!='Fleettrip' && $returnmodule!='WagonTrip')
			{
				$returnrecord = $request->get('returnrecord');
				$job_id = $returnrecord;
				$job_info = Vtiger_Record_Model::getInstanceById($job_id, $returnmodule);
				
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
				else{
					$current_user = Users_Record_Model::getCurrentUserModel();
					$company_id = $current_user->get('company_id');
					$current_user_office_id = $current_user->get('location_id');
					
					$viewer->assign('FILE_TITLE', $company_id);
					//$viewer->assign('FILE_TITLE', '85757');
					$viewer->assign('FILE_TITLE_FLAG', true);
				}


			}
			else{

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
		$requestFieldList = array_intersect_key($request->getAllPurified(), $fieldList);

		$relContactId = $request->get('contact_id');
		if ($relContactId && $moduleName == 'Calendar') {
			$contactRecordModel = Vtiger_Record_Model::getInstanceById($relContactId);
			$requestFieldList['parent_id'] = $contactRecordModel->get('account_id');
		}
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

		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE',Vtiger_Functions::jsonEncode($picklistDependencyDatasource));
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

		// added to set the return values
		if($request->get('returnview')) {
			$request->setViewerReturnValues($viewer);
		}
		$viewer->assign('MAX_UPLOAD_LIMIT_MB', Vtiger_Util_Helper::getMaxUploadSize());
		$viewer->assign('MAX_UPLOAD_LIMIT_BYTES', Vtiger_Util_Helper::getMaxUploadSizeInBytes());
		if($request->get('displayMode')=='overlay'){
			$viewer->assign('SCRIPTS',$this->getOverlayHeaderScripts($request));
			$viewer->view('OverlayEditView.tpl', $moduleName);
		}
		else{
			$viewer->view('EditView.tpl', $moduleName);
		}
	}

	public function getOverlayHeaderScripts(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$jsFileNames = array(
			"modules.$moduleName.resources.Edit",
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return $jsScriptInstances;
	}
}
