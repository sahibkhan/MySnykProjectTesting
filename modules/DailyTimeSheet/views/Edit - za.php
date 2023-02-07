<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Class DailyTimeSheet_Edit_View extends Vtiger_Edit_View {
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
			$recordModel->set('name', '-');
			
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


		// creating record structure for related modules
        if(!empty($record)){
			$dailyTaskList = $this->getDailyTask($record);

			$dailyTaskID = $dailyTaskList[0]['relatedrecordid'];

			$viewer->assign('dailyTaskID', $dailyTaskID);

			unset($dailyTaskList[0]);

			$viewer->assign('dailyTaskList', $dailyTaskList);

			// getting employee education module
			if(!empty($dailyTaskID))
			{
				$dailyTaskRecordModel = $this->record?$this->record:Vtiger_Record_Model::getInstanceById($dailyTaskID, "DailyTimeSheetTask");
			}
			else
			{
				$dailyTaskRecordModel = Vtiger_Record_Model::getCleanInstance("DailyTimeSheetTask");
			}

			
		}
		else{
			$dailyTaskRecordModel = Vtiger_Record_Model::getCleanInstance("DailyTimeSheetTask");
		}


		if(!$this->record){
            $this->record = $recordModel;
        }

        
		// preparing fields structure for education
		$dailyTaskModuleModel = $dailyTaskRecordModel->getModule();
		$dailyTaskFieldList = $dailyTaskModuleModel->getFields(); 
		$dailyTaskRequestFieldList = array_intersect_key($request->getAll(), $dailyTaskFieldList);


		foreach($dailyTaskRequestFieldList as $fieldName=>$fieldValue){
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
				$dailyTaskRecordModel->set($fieldName, $fieldModel->getDBInsertValue($fieldValue));
			}
		}

		$dailyTaskRecordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($dailyTaskRecordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_EDIT);

		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE',Zend_Json::encode($picklistDependencyDatasource));
		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
		$viewer->assign('RECORD_STRUCTURE', $recordStructureInstance->getStructure());
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
        //echo "<pre>"; print_r($dailyTaskRecordStructureInstance->getStructure()); exit;
		// record structure for Employee Education
		$viewer->assign('DT_RECORD_STRUCTURE_MODEL', $dailyTaskRecordStructureInstance);
		$viewer->assign('DT_RECORD_STRUCTURE', $dailyTaskRecordStructureInstance->getStructure());

		$isRelationOperation = $request->get('relationOperation');

		//if it is relation edit
		$viewer->assign('IS_RELATION_OPERATION', $isRelationOperation);
		if($isRelationOperation) {
			$viewer->assign('SOURCE_MODULE', $request->get('sourceModule'));
			$viewer->assign('SOURCE_RECORD', $request->get('sourceRecord'));
		}

		if(empty($record)) {
			$current_user = Users_Record_Model::getCurrentUserModel();
			$department_id = $current_user->get('department_id');
			$location_id = $current_user->get('location_id');
			
			$viewer->assign('USER_DEPARTMENT', $department_id);
			$viewer->assign('USER_LOCATION', $location_id);
		}

		
		
		$viewer->assign('MAX_UPLOAD_LIMIT_MB', Vtiger_Util_Helper::getMaxUploadSize());
		$viewer->assign('MAX_UPLOAD_LIMIT', vglobal('upload_maxsize'));
		$viewer->view('EditView.tpl', $moduleName);
	}

	// get employee's education info
	public function getDailyTask($sourcerecordid)
	{
		$db = PearDatabase::getInstance();
		//$id = $request->get('record');	
			$query = "SELECT * 
						FROM
							vtiger_dailytimesheettaskcf
							INNER JOIN vtiger_dailytimesheettask ON vtiger_dailytimesheettask.dailytimesheettaskid = vtiger_dailytimesheettaskcf.dailytimesheettaskid
							INNER JOIN vtiger_crmentity ON vtiger_dailytimesheettask.dailytimesheettaskid = vtiger_crmentity.crmid
							INNER JOIN vtiger_crmentityrel ON vtiger_dailytimesheettask.dailytimesheettaskid = vtiger_crmentityrel.relcrmid
						WHERE
							vtiger_crmentityrel.crmid = '$sourcerecordid' 
							AND vtiger_crmentity.deleted = 0 
						ORDER BY
							vtiger_dailytimesheettaskcf.dailytimesheettaskid ASC;"; 
			// echo $queryEdu; exit;
        	$result = $db->pquery($query, array());

				for($i=0;$i<$db->num_rows($result);$i++){
					$dailyTaskList[$i]['relatedrecordid'] = $db->query_result($result,$i,'dailytimesheettaskid');
					$dailyTaskList[$i]['entryType'] = $db->query_result($result,$i,'name'); 
					$dailyTaskList[$i]['taskType'] = $db->query_result($result,$i,'cf_6904');
					$dailyTaskList[$i]['description'] = $db->query_result($result,$i,'cf_6906');
					$dailyTaskList[$i]['quantity'] = $db->query_result($result,$i,'cf_6908');
					$dailyTaskList[$i]['hoursSpent'] = $db->query_result($result,$i,'cf_6910');
				}
		    //echo "<pre>"; print_r($dailyTaskList); exit;
			return $dailyTaskList;
	}
}