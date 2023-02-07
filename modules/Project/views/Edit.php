<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Class Project_Edit_View extends Vtiger_Edit_View {
	protected $record = false;
	function __construct() {
		parent::__construct();
		// $this->exposeMethod('deleteAttachment');
	}

	
	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$record = $request->get('record');

		$actionName = 'CreateView';
		if ($record && !$request->get('isDuplicate')) {
			$actionName = 'EditView';
		}

		if(!Users_Privileges_Model::isPermitted($moduleName, $actionName, $record)) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}

		if ($record) {
			$recordEntityName = getSalesEntityType($record);
			if ($recordEntityName !== $moduleName) {
				throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
			}
		}
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
		global $adb;
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
		
		// Collect Users
		$queryUsers = $adb->pquery("SELECT CONCAT(first_name,' ',last_name) as name, id  
									FROM vtiger_users
									WHERE status = ? AND title <> ?", array('Active', 'driver'));
		$nRows = $adb->num_rows($queryUsers);

		for ($i = 0; $i < $nRows; $i++){
			$userName = $adb->query_result($queryUsers, $i, 'name'); 
			$userID = $adb->query_result($queryUsers, $i, 'id');
			$USER_LIST[] = array("id" => $userID, "name" => $userName);
		}
		
		// Collect Statuses
		$queryStatus = $adb->pquery("SELECT cf_7838 FROM `vtiger_cf_7838` WHERE presence = ?", array(1));
		$nRows = $adb->num_rows($queryStatus);

		for ($i = 0; $i < $nRows; $i++){
			$status = $adb->query_result($queryStatus, $i, 'cf_7838'); 
			$STATUS_LIST[] = array("name" => $status);
		}
		
 
		$resultTasks = $adb->pquery("SELECT vtiger_projectmilestone.projectmilestoneid, vtiger_projectmilestone.projectmilestonename, 
											vtiger_projectmilestone.projectmilestonedate, vtiger_projectmilestonecf.cf_7838,
											vtiger_crmentity.smownerid, vtiger_crmentity.description,
											vtiger_projectmilestonecf.cf_7844,
											vtiger_projectmilestonecf.cf_7846,
											CONCAT(vtiger_location.name, ' / ', vtiger_departmentcf.cf_1542) as branch
 

									FROM `vtiger_crmentityrel` 
									INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_crmentityrel.relcrmid
									INNER JOIN vtiger_projectmilestone ON vtiger_projectmilestone.projectmilestoneid = vtiger_crmentityrel.relcrmid
									INNER JOIN vtiger_projectmilestonecf ON vtiger_projectmilestonecf.projectmilestoneid = vtiger_crmentityrel.relcrmid
									LEFT JOIN vtiger_location ON vtiger_location.locationid = vtiger_projectmilestonecf.cf_7844
									LEFT JOIN vtiger_departmentcf ON vtiger_departmentcf.departmentid = vtiger_projectmilestonecf.cf_7846
									
									WHERE vtiger_crmentity.deleted = 0 AND vtiger_crmentityrel.`module` = 'Project' 
									AND vtiger_crmentityrel.`relmodule` = 'ProjectMilestone' 
									AND vtiger_crmentityrel.crmid = ?
									
									ORDER BY vtiger_crmentity.crmid
									", array($record));
		$nTasks = $adb->num_rows($resultTasks);

 		for ($i = 0; $i < $nTasks; $i++){
			$taskId = $adb->query_result($resultTasks, $i, 'projectmilestoneid');
			$taskDeadline = $adb->query_result($resultTasks, $i, 'projectmilestonedate');
			$taskName = $adb->query_result($resultTasks, $i, 'projectmilestonename');			
			$taskStatus = $adb->query_result($resultTasks, $i, 'cf_7838');
			$taskComment = trim($adb->query_result($resultTasks, $i, 'description'));
			$assignedUserId = $adb->query_result($resultTasks, $i, 'smownerid');

			$locationId = $adb->query_result($resultTasks, $i, 'cf_7844');
	 		$departmentId = $adb->query_result($resultTasks, $i, 'cf_7846');
			$branchName = $adb->query_result($resultTasks, $i, 'branch');
  
			
			$TASK_LIST[] = array("id" => $taskId, 
								 "name" => $taskName, 
								 "deadline" => $taskDeadline, 
								 "status" => $taskStatus,
								 "comment" => $taskComment,								 
								 "userId" => $assignedUserId,
								 "locationId" => $locationId,
								 "departmentId" => $departmentId,
								 "branchName" => $branchName
								);
		}
		 
		// print_r($TASK_LIST);exit;

		$viewer->assign('STATUSES', $STATUS_LIST);
		$viewer->assign('USERS', $USER_LIST);
		$viewer->assign('TASKS', $TASK_LIST);
		
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
			$currentUserModel = Users_Record_Model::getCurrentUserModel();
			$recordModel->set('cf_7166', $currentUserModel->get('department_id'));
			$recordModel->set('cf_7854', $currentUserModel->get('location_id'));
			
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

		$viewer->assign('IMAGE_DETAILS', $recordModel->getImageDetails());
		

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


/* 	public function deleteAttachment(Vtiger_Request $request){

	} */

	public function getOverlayHeaderScripts(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$jsFileNames = array(
			"modules.$moduleName.resources.Edit",
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return $jsScriptInstances;
	}
}
