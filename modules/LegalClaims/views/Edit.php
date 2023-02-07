<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Class LegalClaims_Edit_View extends Vtiger_Edit_View {
  protected $record = false;

	function __construct() {
		parent::__construct();
		$this->exposeMethod('deleteAttachment');
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
		$legalClaimsFeeType = 'Claim Additional Fees';
		$legalClaimsSettlementType = 'Settlement Amount';

		//Vtiger7 - TO show custom view name in Module Header
		global $adb;
		$viewer = $this->getViewer($request); 
		$moduleName = $request->getModule(); 
		$viewer->assign('CUSTOM_VIEWS', CustomView_Record_Model::getAllByGroup($moduleName)); 
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$record = $request->get('record'); 
		if(!empty($record) && $moduleModel->isEntityModule()) { 
			$recordModel = $this->record?$this->record:Vtiger_Record_Model::getInstanceById($record, $moduleName); 
			$viewer->assign('RECORD',$recordModel); 
		}  

		$currentUserModel = Users_Record_Model::getCurrentUserModel();

		$duplicateRecordsList = array();
		$duplicateRecords = $request->get('duplicateRecords');
		if (is_array($duplicateRecords)) {
			$duplicateRecordsList = $duplicateRecords;
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('DUPLICATE_RECORDS', $duplicateRecordsList);
	

		$FEES_SUB_DATA = $this->getLegalClaimsSubModuleData($legalClaimsFeeType, $record);
		// echo "<pre>";print_r($FEES_SUB_DATA);exit;

		$SETTLEMENT_SUB_DATA = $this->getLegalClaimsSubModuleData($legalClaimsSettlementType, $record);




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

		
		$FEE_TYPES[] = array("type" => "Penalties", "id" => 1);
		$FEE_TYPES[] = array("type" => "State / Court fee", "id" => 2);
		$FEE_TYPES[] = array("type" => "Legal fees", "id" => 3);
		$FEE_TYPES[] = array("type" => "Other fees", "id" => 4);		


		$viewer->assign('FEE_TYPES', $FEE_TYPES);
		$viewer->assign('USERS', $USER_LIST);
		$viewer->assign('CURRENT_USER_ID', $currentUserModel->getId());
		$viewer->assign('FEES_SUB_DATA', $FEES_SUB_DATA);
		$viewer->assign('SETTLEMENT_SUB_DATA', $SETTLEMENT_SUB_DATA); 
		parent::preProcess($request, $display); 
	}


	public function process(Vtiger_Request $request) {
		$mode = $request->getMode();
		if(!empty($mode)) {
			echo $this->invokeExposedMethod($mode, $request);
			return;
		}		

		$currentUser = Users_Record_Model::getCurrentUserModel();
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


		$currentUser = Users_Record_Model::getCurrentUserModel();
		$accessibleUsers = $currentUser->getAccessibleUsers();

		foreach ($accessibleUsers as $key => $accessibleUser){
			$inviteeUserDetails = $recordModel->getEventInvitee($key);
			$accessibleUsersNew[$key] = $inviteeUserDetails['nameWithLocationAndDepartment'];
	  }

		$INVITIES_SELECTED = array();
		if ($request->get('returnmodule') == 'Potentials' && !empty($request->get('returnrecord'))){
			$RFQInviteUsers = $recordModel->getInvities($request->get('returnrecord'));
			$INVITIES_SELECTED = [...$RFQInviteUsers];
		}
		
		$INVITIES_SELECTED = [...$INVITIES_SELECTED, ...$recordModel->getInvities()];
		// echo "<pre>"; print_r($INVITIES_SELECTED); exit;

		$viewer->assign('INVITIES_SELECTED', $INVITIES_SELECTED);
		$viewer->assign('ACCESSIBLE_USERS', $accessibleUsersNew);
		$viewer->assign('CURRENT_USER', $currentUser);
		$viewer->assign('LIST_OF_INVITEES', $recordModel->getEventInvitees());


		$viewer->assign('IMAGE_DETAILS', $recordModel->getImageDetails());
		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE',Zend_Json::encode($picklistDependencyDatasource));
		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
		$viewer->assign('RECORD_STRUCTURE', $recordStructureInstance->getStructure());
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());

		if (!empty($request->get('record'))){
				$legalClaimsRecord = Vtiger_Record_Model::getInstanceById($request->get('record'), 'LegalClaims');
				$jobFileId = $legalClaimsRecord->get('name');
				
				if (!empty($jobFileId)){
					$jobFileRecord = Vtiger_Record_Model::getInstanceById($jobFileId, 'Job');
					$viewer->assign('ACCOUNT_ID', $jobFileRecord->get('cf_1441'));
					$viewer->assign('JOBREF_NO', $jobFileRecord->get('cf_1198'));
				}				
		}
			
			// echo "<pre>"; print_r($request);	exit;
		
		$isRelationOperation = $request->get('relationOperation');

		//if it is relation edit
		$viewer->assign('IS_RELATION_OPERATION', $isRelationOperation);
		if($isRelationOperation) {
			$viewer->assign('SOURCE_MODULE', $request->get('sourceModule'));
			$viewer->assign('SOURCE_RECORD', $request->get('sourceRecord'));
		}

		$salutationFieldModelCargoValue = Vtiger_Field_Model::getInstance('cf_8460', $recordModel->getModule());
		$salutationFieldModelCargoValue->set('fieldvalue', $recordModel->get('cf_8460'));
		$viewer->assign('SALUTATION_FIELD_MODEL_CARGO_VALUE', $salutationFieldModelCargoValue);	

		
		$viewer->assign('MAX_UPLOAD_LIMIT_MB', Vtiger_Util_Helper::getMaxUploadSize());
		$viewer->assign('MAX_UPLOAD_LIMIT', vglobal('upload_maxsize'));
		
		// $accessibleUsers = $currentUser->getAccessibleUsers();
    // $viewer->assign('INVITIES_SELECTED', $recordModel->getInvities());
		// $viewer->assign('ACCESSIBLE_USERS', $accessibleUsers);
		$viewer->assign('CURRENT_USER', $currentUser);
		
		$viewer->view('EditView.tpl', $moduleName);
	}

	function getLegalClaimsSubModuleData($legalClaimsSubType, $record){
		global $adb;

		if ($record == 0) return;
		$queryTypes = $adb->pquery("SELECT vtiger_legalclaimssubmodule.legalclaimssubmoduleid, vtiger_legalclaimssubmodule.name, 
																	vtiger_crmentity.smownerid, vtiger_legalclaimssubmodulecf.*
																FROM vtiger_legalclaimssubmodule
																INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_legalclaimssubmodule.legalclaimssubmoduleid
																INNER JOIN vtiger_legalclaimssubmodulecf ON vtiger_legalclaimssubmodulecf.legalclaimssubmoduleid = vtiger_legalclaimssubmodule.legalclaimssubmoduleid
																WHERE vtiger_crmentity.deleted = 0 AND vtiger_legalclaimssubmodulecf.cf_8466 = ? 
																AND vtiger_legalclaimssubmodulecf.cf_8480 = ?", array($legalClaimsSubType, $record));
		$nRows = $adb->num_rows($queryTypes);
		$SUB_DATA = array();

		for ($i = 0; $i < $nRows; $i++){
			$id = $adb->query_result($queryTypes, $i, 'legalclaimssubmoduleid'); 
			$userId = $adb->query_result($queryTypes, $i, 'smownerid'); 
			$sum = $adb->query_result($queryTypes, $i, 'name'); 
			$comment = $adb->query_result($queryTypes, $i, 'cf_8476');
			$feeType = $adb->query_result($queryTypes, $i, 'cf_8478');
			$feeType = $adb->query_result($queryTypes, $i, 'cf_8478');
			$date = $adb->query_result($queryTypes, $i, 'cf_8474');
			$SUB_DATA[] = array('legalClaimsRecordId' => $record, 'legalClaimsSubRecordId' => $id, 
														   'userId' => $userId, 'type' => $feeType, 
															 'sum' => $sum, 'comment' => $comment,
															 'date' => $date);
			}

			return $SUB_DATA;
	}



	public function deleteAttachment(Vtiger_Request $request){
		global $adb;
		$adb = PearDatabase::getInstance();		
		$recordId = $request->get('record'); 
		$attachmentid = $request->get('attachmentid');

		$sql = "UPDATE vtiger_crmentity SET deleted = 1 WHERE setype='LegalClaims Attachment' AND crmid='".$attachmentid."' LIMIT 1";

		$result = $adb->pquery($sql);		
		$loadUrl = "index.php?module=LegalClaims&view=Edit&record=$recordId&app=MARKETING";
        echo '<script>
			var url= "'.$loadUrl.'"; 
			window.location = url; 
		</script>';
	}


}