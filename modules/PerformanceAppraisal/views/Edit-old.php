<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Class PerformanceAppraisal_Edit_View extends Vtiger_Edit_View {
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
		$lang = $request->get('lang');
		$lang = ((isset($lang) && $lang=='ru') ? 'ru':'en');		
		
		
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
			$request->set('name', '-'); //Name value

			$current_user = Users_Record_Model::getCurrentUserModel();
			$department_id = $current_user->get('department_id');
			$location_id = $current_user->get('location_id');
			$request->set('cf_6564',$department_id);
			$request->set('cf_6562',$location_id);
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

		//Appraisal Parent Category
		global $adb;
		$final_appraisal_arr = array();
		$result_parent_cat = $adb->pquery("SELECT cf_6528id, cf_6528 FROM `vtiger_cf_6528` ORDER BY `vtiger_cf_6528`.`sortorderid` ASC ", array());
		for($jj=0; $jj< $adb->num_rows($result_parent_cat); $jj++ ) {
				$row_parent_cat = $adb->fetch_row($result_parent_cat,$jj);
				$parent_cat_id = $row_parent_cat['cf_6528id'];
				$parent_cat_name = $row_parent_cat['cf_6528'];
				
				$final_appraisal_arr[$parent_cat_id] = array('parent_cat' => $parent_cat_name);

				$result_questions = $adb->pquery("SELECT * FROM `vtiger_appraisalquestionscf` WHERE cf_6528=? 
												  ORDER BY vtiger_appraisalquestionscf.appraisalquestionsid ASC",array($parent_cat_name));
				for($qq=0; $qq< $adb->num_rows($result_questions); $qq++ ) {
					$row_question = $adb->fetch_row($result_questions,$qq);
					$question_id = $row_question['appraisalquestionsid'];	
					$question_name_eng = $row_question['cf_6532'];
					$question_name_rus = $row_question['cf_6534'];
					$question_sub_cat_id = $row_question['cf_6530'];

					$result_cat = $adb->pquery("SELECT * FROM `vtiger_appraisalcategoriescf` WHERE appraisalcategoriesid=? ",array($question_sub_cat_id));
					$row_sub_cat = $adb->fetch_array($result_cat);
					$sub_cat_eng = $row_sub_cat['cf_6520'];
					$sub_cat_rus = $row_sub_cat['cf_6522'];
					
					$final_appraisal_arr[$parent_cat_id]['cat_question'][] = array('question_sub_cat_id' => $question_sub_cat_id, 
																				   'sub_cat_eng' => $sub_cat_eng,
																				   'sub_cat_rus' => $sub_cat_rus,
																   				   'question_id' => $question_id, 
																   				   'question_name_eng' => $question_name_eng,
																				   'question_name_rus' => $question_name_rus);
					
					$result_level = $adb->pquery("SELECT * FROM `vtiger_perfomancelevelcf` WHERE cf_6550=? 
													  ",array($question_sub_cat_id));
					for($mm=0; $mm< $adb->num_rows($result_level); $mm++ ) {
						$row_level = $adb->fetch_row($result_level,$mm);
						$perfomancelevelid = $row_level['perfomancelevelid'];
						$level = $row_level['cf_6548'];
						$level_ans_eng = $row_level['cf_6552'];
						$level_ans_rus = $row_level['cf_6554'];
						
						$level_key =str_replace(' ','_', $level);
						$final_appraisal_arr[$parent_cat_id]['cat_question'][$qq]['level'][$level_key] = array('level' => $level, 
																									'level_ans_eng' => $level_ans_eng,
																									'level_ans_rus' => $level_ans_rus
																									);

					}								  
																					  
				}
		}
		$viewer->assign('FINAL_APPRAISAL_ARR', $final_appraisal_arr);
		//echo "<pre>";
		//print_r($final_appraisal_arr);
		//exit;

		$isRelationOperation = $request->get('relationOperation');

		//if it is relation edit
		$viewer->assign('IS_RELATION_OPERATION', $isRelationOperation);
		if($isRelationOperation) {
			$viewer->assign('SOURCE_MODULE', $request->get('sourceModule'));
			$viewer->assign('SOURCE_RECORD', $request->get('sourceRecord'));
		}
		
		$viewer->assign('MAX_UPLOAD_LIMIT_MB', Vtiger_Util_Helper::getMaxUploadSize());
		$viewer->assign('MAX_UPLOAD_LIMIT', vglobal('upload_maxsize'));
		$viewer->assign('MAX_UPLOAD_LIMIT', vglobal('upload_maxsize'));
		$viewer->assign('LANG',$lang);
		
		$viewer->view('EditView.tpl', $moduleName);
	}
}