<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Class ExternalTraining_Edit_View extends Vtiger_Edit_View {
	protected $record = false;
	function __construct() {
		parent::__construct();
		// $this->exposeMethod('deleteAttachment');
	}


	public function process(Vtiger_Request $request) {
		
		$mode = $request->getMode();
		if(!empty($mode)) {
			echo $this->invokeExposedMethod($mode, $request);
			return;
		}
		
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
			if(empty($record))
			{
				if($request->get('returnmodule') == 'UserList' && $request->get('returnrecord')){
					
					$sourceModule = $request->get('returnmodule');
					$sourceRecord = $request->get('returnrecord');
					$externalTraining = Vtiger_Record_Model::getInstanceById($sourceRecord, $sourceModule);
					$request->set('name', $sourceRecord); // Name
		
					$request->set('cf_7356', $externalTraining->get('cf_3421')); // Location
					$request->set('cf_7358', $externalTraining->get('cf_3349')); // Department
										
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
		
		$request->set('fieldvalue', 1);
		
		$recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_EDIT);
		$picklistDependencyDatasource = Vtiger_DependencyPicklist::getPicklistDependencyDatasource($moduleName);
		
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$current_user = $currentUserModel->get('id');
		
		$accountid = $recordModel->get('cf_6362');
		
		if(!empty($record) && !empty($accountid)){
				
		//echo 'accountid = ' . $accountid;
	        //	exit;
		$recordAccounts = Vtiger_Record_Model::getInstanceById($accountid, 'Accounts');
		$accountname = $recordAccounts->get('accountname');
	        
		$viewer->assign('ACCOUNT_NAME', $accountname);
		$viewer->assign('ACCOUNT_ID', $accountid);
		
		
		
		
		}
		if(!empty($record)) {
		$recordMotiw = Vtiger_Record_Model::getInstanceById($record, 'MOTIW');
		$recordMotiw->get('cf_7056');
		$finalversion = explode(",",$recordMotiw->get('cf_7056'));
		}

    
    $viewer->assign('IMAGE_DETAILS', $recordModel->getImageDetails());
		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE',Vtiger_Functions::jsonEncode($picklistDependencyDatasource));
		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
		$viewer->assign('RECORD_STRUCTURE', $recordStructureInstance->getStructure());
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('isEdit', 1);
		$viewer->assign('SEQUENCE', $sequence);
	
		
		$viewer->assign('FINALVERSION', $finalversion);
		
		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$isRelationOperation = $request->get('relationOperation');
		
		if(empty($record)) {
		   
			$department_id = $currentUserModel->get('department_id');
			if($department_id=='85844') //for FLT
			{
				$department_id = 85841;
			}
			
			$location_id = $currentUserModel->get('location_id');
			$company_id = $currentUserModel->get('company_id');
			
			//access company for jz
			$access_company_id = explode(" |##| ",$currentUserModel->get('access_company_id'));
			
			$viewer->assign('CURRENT_USER_ID', $current_user);
			$viewer->assign('USER_COMPANY', $company_id);
			$viewer->assign('USER_DEPARTMENT', $department_id);
			$viewer->assign('USER_LOCATION', $location_id);
		
		} else {
			
			global $adb;
		    $adb = PearDatabase::getInstance();
			
			$sql = "SELECT * FROM vtiger_approvalroutehistorycf WHERE cf_6784='".$record."' AND cf_6790=1";
		    $result = $adb->pquery($sql);
			$cordinator_id = $adb->query_result($result,0,'cf_6788');
			
			$cordinator_user_model = Vtiger_Record_Model::getInstanceById($cordinator_id, 'Users');
			$cordinator_user = $cordinator_user_model->get('id');
			$department_id = $cordinator_user_model->get('department_id');
			if($department_id=='85844') //for FLT
			{
				$department_id = 85841;
			}
			
			$location_id = $cordinator_user_model->get('location_id');
			$company_id = $cordinator_user_model->get('company_id');
			
			//access company for jz
			$access_company_id = explode(" |##| ",$cordinator_user_model->get('access_company_id'));
			
			$viewer->assign('CURRENT_USER_ID', $cordinator_user);			
			$viewer->assign('USER_COMPANY', $company_id);
			$viewer->assign('USER_DEPARTMENT', $department_id);
			$viewer->assign('USER_LOCATION', $location_id);
			
			
		}

		$currentUser = Users_Record_Model::getCurrentUserModel();
		$accessibleUsers = $currentUser->getAccessibleUsers();
		$viewer->assign('ACCESSIBLE_USERS', $accessibleUsers);
		$viewer->assign('CURRENT_USER', $currentUser);
		$viewer->assign('INVITIES_SELECTED', $recordModel->getInvities());


		/* 
		echo 'record='.$record;
		exit; */
		if(!empty($record)){
			
			$viewer->assign('RECORD_ID', $record);
		} 
		
		
		$viewer->assign('ACCESS_USER_COMPANY', $access_company_id);
		
		
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

		$salutationFieldModelCargoValue = Vtiger_Field_Model::getInstance('cf_7442', $recordModel->getModule());
		$salutationFieldModelCargoValue->set('fieldvalue', $recordModel->get('cf_7442'));
		$viewer->assign('SALUTATION_FIELD_MODEL_CARGO_VALUE', $salutationFieldModelCargoValue);	


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
	
	public function deleteAttachment(Vtiger_Request $request){
		
		// echo "<pre>"; print_r($request); exit;
		global $adb;
		$adb = PearDatabase::getInstance();		
		$recordId = $request->get('record'); 
		$attachmentid = $request->get('attachmentid');


		
		$sql = "UPDATE vtiger_crmentity SET deleted=1 WHERE setype='MOTIW Attachment' AND crmid='".$attachmentid."' LIMIT 1";

/* 		echo $sql;
		exit; */

		$result = $adb->pquery($sql);
		
		$loadUrl = "index.php?module=MOTIW&view=Edit&record=$recordId&app=MARKETING";
        echo '<script>
			var url= "'.$loadUrl.'"; 
			window.location = url; 
		</script>';
		
		
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
