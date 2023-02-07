<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Class CreditTermsRevisionForm_Edit_View extends Vtiger_Index_View {
  
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

  public function process(Vtiger_Request $request) {
    
    global $adb;

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
    } else {
      $recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
      $viewer->assign('MODE', '');

      if(empty($record)) {
        
        $isRelationOperation = $request->get('relationOperation');
        
        if($isRelationOperation) {
          $sourceRecord = $request->get('sourceRecord');
          $sourceModule = $request->get('sourceModule');
          $credit_terms_info = Vtiger_Record_Model::getInstanceById($sourceRecord, $sourceModule);

          $request->set('name', $credit_terms_info->get('name')); // Record Name = accounts.name_for_lookup
          $request->set('cf_4857', $credit_terms_info->get('cf_4815')); // Company Name = accounts.legal_name
          $request->set('cf_4859', $credit_terms_info->get('cf_4817')); // Customer Name = accounts.legal_name
          $request->set('cf_4861', $credit_terms_info->get('cf_4821')); // Account no.
          $request->set('cf_4917', $credit_terms_info->get('cf_4915')); // Type of KLN services required
          $request->set('cf_4869', $credit_terms_info->get('cf_4847')); // Credit Limit (existing)
          $request->set('cf_4871', $credit_terms_info->get('cf_4849')); // Credit Day (existing)
        }
      }
    }
    if(!$this->record){
        $this->record = $recordModel;
    }
        
    $moduleModel = $recordModel->getModule();
    $fieldList = $moduleModel->getFields();
    $requestFieldList = array_intersect_key($request->getAll(), $fieldList);

    foreach($requestFieldList as $fieldName=>$fieldValue) {
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
    $viewer->view('EditView.tpl', $moduleName);
  }

  function getHeaderScripts(Vtiger_Request $request) {
    $headerScriptInstances = parent::getHeaderScripts($request);

    $moduleName = $request->getModule();

    $jsFileNames = array(
        "include.CreditTermsRevisionForm.Edit",
    );
    $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
    $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
    return $headerScriptInstances;
  }
}