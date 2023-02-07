<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Class Potentials_Edit_View extends Vtiger_Edit_View {
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
			$viewer->assign('RECEIPENT_LIST', '');

			$current_user = Users_Record_Model::getCurrentUserModel();
			$user_id = $current_user->getId(); 
			$request->set('cf_755', $user_id);	
			$request->set('sales_stage', 'Pending');
			$request->set('closingdate','');					
			
			//Mehtab Code :: 25-10-2016
			if(empty($record)) {
				//$current_user = Users_Record_Model::getCurrentUserModel();
				$user_id = $current_user->getId(); 
				$viewer->assign('USER_ID', $user_id);
			}			

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
			//General Country and states code
			$GEN_COUNTRY_CODE = $recordModel->get('cf_1657');
			$GEN_STATE_CODE = $recordModel->get('potentials_origin_state');
			$viewer->assign('GEN_COUNTRY_CODE',$GEN_COUNTRY_CODE);
			$viewer->assign('GEN_STATE_CODE',$GEN_STATE_CODE);
			//General Destination Country and Destination states code

			$GEN_DEST_COUNTRY_CODE = $recordModel->get('cf_1661');
			$GEN_DEST_STATE_CODE = $recordModel->get('potentials_destination_state');
			$viewer->assign('GEN_DEST_COUNTRY_CODE',$GEN_DEST_COUNTRY_CODE);
			$viewer->assign('GEN_DEST_STATE_CODE',$GEN_DEST_STATE_CODE);
				
			

			$recipientList = $recordModel->get('cf_757');

			$user_login = $this->arrange_muptiple_users($recipientList,2);
			$users = $this->arrange_muptiple_users($recipientList,1);
	
			$n = count($users);
			$value = '';
			for ($i=1;$i<=$n;$i++){
				$value .= '<tr class="remove_invite_user'.$i.'"><td class="hide_invite_login" style="display:none">'.$user_login[$i].'</td>
			<td id="invite_user_format'.$i.'">'.$users[$i].'</td><td id="removeinviteduser"  data-id="'.$i.'">
			<img src="include/images/delete.png"></td></tr>';
			}
			$viewer->assign('RECEIPENT_LIST', $value);

		} else {
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$viewer->assign('MODE', '');

			$current_user = Users_Record_Model::getCurrentUserModel();
			$user_id = $current_user->getId(); 
			$request->set('cf_755', $user_id);						
			
			//Mehtab Code :: 25-10-2016
			if(empty($record)) {
				//$current_user = Users_Record_Model::getCurrentUserModel();
				$user_id = $current_user->getId(); 
				$viewer->assign('USER_ID', $user_id);
			}
			$viewer->assign('RECEIPENT_LIST', '');
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


		$salutationFieldModel = Vtiger_Field_Model::getInstance('cf_1685', $recordModel->getModule());
		$salutationFieldModel->set('fieldvalue', $recordModel->get('cf_1685'));
		$viewer->assign('SALUTATION_FIELD_MODEL', $salutationFieldModel);
		
		$salutationFieldModelVolume = Vtiger_Field_Model::getInstance('cf_1689', $recordModel->getModule());
		$salutationFieldModelVolume->set('fieldvalue', $recordModel->get('cf_1689'));
		$viewer->assign('SALUTATION_FIELD_MODEL_VOLUME', $salutationFieldModelVolume);
		
		$salutationFieldModelCargoValue = Vtiger_Field_Model::getInstance('cf_1723', $recordModel->getModule());
		$salutationFieldModelCargoValue->set('fieldvalue', $recordModel->get('cf_1723'));
		$viewer->assign('SALUTATION_FIELD_MODEL_CARGO_VALUE', $salutationFieldModelCargoValue);

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

	function arrange_muptiple_users($users,$format){
		global $adb;
		$person_array = array();
		$buffer = '';
		$n = 0;
	
		// Search count of person
		for($i = 0; $i <= strlen($users); $i++){
			if ($users[$i] == '|'){
				$n ++;
				$buffer = trim($buffer);
				$sql_user = $adb->pquery("SELECT * FROM `vtiger_users` where `user_name` = '$buffer' ");
				$r_user = $adb->fetch_array($sql_user);
				if ($format == 1){
					$person_array[$n] = $this->arrange_user_format($r_user['user_name'],1);
				}
				else
					if ($format == 2){
						$person_array[$n] = $r_user['user_name'];
					}
					else
						if ($format == 3){
							$person_array[$n] = $r_user['email1'].';';
						}
				$buffer = '';
			} else $buffer = $buffer . $users[$i];
		}
		return $person_array;
	}

	// Mentioning full user format:  first name, last name, Department;
function arrange_user_format($users,$mode){
	global $adb;
    // Вывод данных пользователей
    $user_login = trim($users);
    $res_users = $adb->pquery("Select * From `vtiger_users` where `user_name` = '$user_login' ");
    $row_user = $adb->fetch_array($res_users);
    if ($mode == 1){
        $title = $row_user['department'];
        $location = $row_user['address_city'];
        $str = '';
        if ($location == 'Almaty'){
            $str = $title.', Almaty';
        } else {
            $str = $location;
        }
        $output_detail = $row_user['first_name'] . ' ' . $row_user['last_name'].' / '.$str;
    }

    else
        if ($mode == 2){
            $output_detail = $row_user['email1'].';';
        }
        else
            if ($mode == 3){
                $output_detail = $row_user['user_name'];
            }
            else
                if ($mode == 4){
                    $output_detail = $row_user['first_name'] . ' ' . $row_user['last_name'];
                }
    return $output_detail;
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
