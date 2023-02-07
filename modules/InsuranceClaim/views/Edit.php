<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Class InsuranceClaim_Edit_View extends Vtiger_Edit_View {
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
		} else {
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$viewer->assign('MODE', '');

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

				$request->set('cf_6850',$insurance_info->get('cf_3639')); //Insured Amount
				$request->set('cf_6852',$insurance_info->get('cf_3637')); //WIS Premium Amount
				$request->set('cf_6848',$insurance_info->get('cf_3663')); //Insured Currency

				$db = PearDatabase::getInstance();
				$job_ref_no = $insurance_info->get('name');
				$query_job = "SELECT cf_1188 as location_id, cf_1190 as department_id from vtiger_jobcf
									INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobcf.jobid
									where vtiger_crmentity.deleted=0 AND vtiger_jobcf.cf_1198=?";

				$params_job = array($job_ref_no);
				$result_job = $db->pquery($query_job, $params_job);
				$row_job_info = $db->fetch_array($result_job);
				$location_id = $row_job_info['location_id'];
				$department_id = $row_job_info['department_id'];
				//$request->set('cf_6844',$location_id); //Job Location
				//$request->set('cf_6846',$department_id); //Job Department
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

				$db = PearDatabase::getInstance();
				$job_ref_no = $insurance_info->get('name');
				$query_job = "SELECT cf_1188 as location_id, cf_1190 as department_id from vtiger_jobcf
									INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobcf.jobid
									where vtiger_crmentity.deleted=0 AND vtiger_jobcf.cf_1198=?";

				$params_job = array($job_ref_no);
				$result_job = $db->pquery($query_job, $params_job);
				$row_job_info = $db->fetch_array($result_job);
				$location_id = $row_job_info['location_id'];
				$department_id = $row_job_info['department_id'];
				//$request->set('cf_6844',$location_id); //Job Location
				//$request->set('cf_6846',$department_id); //Job Department
			}
			elseif($request->get('returnmodule')=='Truck')
			{
				$request->set('cf_6408','Truck');
			}

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
