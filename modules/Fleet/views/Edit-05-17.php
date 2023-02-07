<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Class Fleet_Edit_View extends Vtiger_Edit_View {
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
		
		$salutationFieldModel = Vtiger_Field_Model::getInstance('cf_2039', $recordModel->getModule());
		$salutationFieldModel->set('fieldvalue', $recordModel->get('cf_2039'));
		$viewer->assign('SALUTATION_FIELD_MODEL', $salutationFieldModel);
		
		$salutationFieldModelVolume = Vtiger_Field_Model::getInstance('cf_2041', $recordModel->getModule());
		$salutationFieldModelVolume->set('fieldvalue', $recordModel->get('cf_2041'));
		$viewer->assign('SALUTATION_FIELD_MODEL_VOLUME', $salutationFieldModelVolume);
		
		$viewer->assign('JOB_INFO_FLAG', FALSE);
		if($isRelationOperation)
		{
			$recordId = $request->get('sourceRecord');
			$job_id = $recordId;
			$current_user = Users_Record_Model::getCurrentUserModel();
			$job_info_detail = Vtiger_Record_Model::getInstanceById($job_id, 'Job');
			
			//$job_user_info = Users_Record_Model::getCurrentUserModel($job_info_detail->get('assigned_user_id'), 'Users');
			$job_user_info = Users_Record_Model::getInstanceById($job_info_detail->get('assigned_user_id'), 'Users');
			$viewer->assign('JOB_USER_INFO', $job_user_info);	
			$viewer->assign('JOB_INFO_DETAIL', $job_info_detail);
			$viewer->assign('JOB_INFO_FLAG', TRUE);	
			
			
			$adb_job_quotes = PearDatabase::getInstance();
			$query_job_quotes = 'SELECT * FROM `vtiger_crmentityrel` where relcrmid=?';
			$params_job_quotes = array($recordId);
				
			$result_job_quotes = $adb_job_quotes->pquery($query_job_quotes, $params_job_quotes);
			$row_job_quotes = $adb_job_quotes->fetch_array($result_job_quotes);
			$viewer->assign('JOB_QUOTATION_REF','');
			if(isset($row_job_quotes['crmid']))
			{
				$viewer->assign('JOB_QUOTATION_REF','GL/QT - '.$row_job_quotes['crmid']);
			}
			
		}
			
		
		$viewer->assign('MAX_UPLOAD_LIMIT_MB', Vtiger_Util_Helper::getMaxUploadSize());
		$viewer->assign('MAX_UPLOAD_LIMIT', vglobal('upload_maxsize'));
		$viewer->view('EditView.tpl', $moduleName);
	}
}