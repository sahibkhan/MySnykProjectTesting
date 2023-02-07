<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Class JobDescription_Edit_View extends Vtiger_Edit_View {
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

    // Implementing data flow
    if(empty($record)){
      if($request->get('returnmodule') == 'UserList' && $request->get('returnrecord')){
                  
        $sourceModule = $request->get('returnmodule');
        $sourceRecord = $request->get('returnrecord');
        $userList = Vtiger_Record_Model::getInstanceById($sourceRecord, $sourceModule);
        $request->set('name', $userList->get('name')); // Name
 
        $request->set('cf_7548', $userList->get('cf_3421')); // Location
        $request->set('cf_7550', $userList->get('cf_3349')); // Department 
        $request->set('cf_7552', $sourceRecord); // Incumbent 
             
/*         $isRelationOperation = $request->get('relationOperation');
        echo 'isRelationOperation='.$isRelationOperation;
        exit; */


				$isRelationOperation = $request->get('relationOperation');
				if($isRelationOperation) {
					
					$sourceRecord = $request->get('sourceRecord');
					$sourceModule = $request->get('sourceModule');
					$related_info = Vtiger_Record_Model::getInstanceById($sourceRecord, $sourceModule);
					$jd_info = Vtiger_Record_Model::getInstanceById($related_info->get('cf_7586'), 'JDTemplates');
					$request->set('cf_7542', $related_info->get('name'));

					$request->set('cf_7542', $jd_info->get('cf_7588')); 

					$request->set('cf_7560', $jd_info->get('cf_7598'));
					$request->set('cf_7566', $jd_info->get('cf_7602')); // Internal
					$request->set('cf_7570', $jd_info->get('cf_7606')); // External

 					// $request->set('cf_6802', $request->get('sourceRecord'));
					$request->set('cf_7564', $jd_info->get('cf_7600')); // Non-fin
					$request->set('cf_7568', $jd_info->get('cf_7604')); // Org chart
					$request->set('cf_7562', $jd_info->get('cf_7596')); // Fin if any
					$request->set('cf_7572', $jd_info->get('cf_7608')); // education

					$request->set('cf_7574', $jd_info->get('cf_7610')); // Work exp
					$request->set('cf_7576', $jd_info->get('cf_7612')); // Lang
					$request->set('cf_7578', $jd_info->get('cf_7614')); // Speic knowledge
					$request->set('cf_7580', $jd_info->get('cf_7616')); //Business Understanding

					$request->set('cf_7582', $jd_info->get('cf_7618')); //Key abil
					$request->set('cf_7546', $jd_info->get('cf_7590')); // Position Title
					$request->set('cf_7784', $jd_info->get('cf_7782'));
					// $request->set('cf_6836', $jd_info->get('cf_7290'));
					// $request->set('cf_6808', $related_info->get('cf_3385'));
				}			
      }
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