<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class LegalClaims_Save_Action extends Vtiger_Save_Action {

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$record = $request->get('record');

		$actionName = ($record) ? 'EditView' : 'CreateView';
		if(!Users_Privileges_Model::isPermitted($moduleName, $actionName, $record)) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}

		if(!Users_Privileges_Model::isPermitted($moduleName, 'Save', $record)) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}

		if ($record) {
			$recordEntityName = getSalesEntityType($record);
			if ($recordEntityName !== $moduleName) {
				throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
			}
		}
	}
	
	public function validateRequest(Vtiger_Request $request) {
		return $request->validateWriteAccess();
	}

	public function process(Vtiger_Request $request) {
		$result = Vtiger_Util_Helper::transformUploadedFiles($_FILES, true); 
		$_FILES = $result['cf_8490']; 
	
		try {
			$record = $request->get('record');
			$recordModel = $this->saveRecord($request);

			$LegalClaimsInstance = new LegalClaims();
			$LegalClaimsInstance->handleEmailNotification($recordModel->get('id'));


			if ($request->get('returntab_label')){
				$loadUrl = 'index.php?'.$request->getReturnURL();
			} else if($request->get('relationOperation')) {
				$parentModuleName = $request->get('sourceModule');
				$parentRecordId = $request->get('sourceRecord');
				$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentRecordId, $parentModuleName);
				//TODO : Url should load the related list instead of detail view of record
				$loadUrl = $parentRecordModel->getDetailViewUrl();
			} else if ($request->get('returnToList')) {
				$loadUrl = $recordModel->getModule()->getListViewUrl();
			} else if ($request->get('returnmodule') && $request->get('returnview')) {
				$loadUrl = 'index.php?'.$request->getReturnURL();
			} else {
				$loadUrl = $recordModel->getDetailViewUrl();
			}
			//append App name to callback url
			//Special handling for vtiger7.
			$appName = $request->get('appName');
			if(strlen($appName) > 0){
				$loadUrl = $loadUrl.$appName;
			}
			header("Location: $loadUrl");
		} catch (DuplicateException $e) {
			$requestData = $request->getAll();
			$moduleName = $request->getModule();
			unset($requestData['action']);
			unset($requestData['__vtrftk']);

			if ($request->isAjax()) {
				$response = new Vtiger_Response();
				$response->setError($e->getMessage(), $e->getDuplicationMessage(), $e->getMessage());
				$response->emit();
			} else {
				$requestData['view'] = 'Edit';
				$requestData['duplicateRecords'] = $e->getDuplicateRecordIds();
				$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

				global $vtiger_current_version;
				$viewer = new Vtiger_Viewer();

				$viewer->assign('REQUEST_DATA', $requestData);
				$viewer->assign('REQUEST_URL', $moduleModel->getCreateRecordUrl().'&record='.$request->get('record'));
				$viewer->view('RedirectToEditView.tpl', 'Vtiger');
			}
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}

	/**
	 * Function to save record
	 * @param <Vtiger_Request> $request - values of the record
	 * @return <RecordModel> - record Model of saved record
	 */
	public function saveRecord($request) {
		$recordModel = $this->getRecordModelFromRequest($request);
		

 		/* ini_set('display_errors', 1);
		error_reporting(E_ALL); */

		if($request->get('imgDeleted')) {
			$imageIds = $request->get('imageid');
			foreach($imageIds as $imageId) {
				$status = $recordModel->deleteImage($imageId);
			}
		}
		$recordModel->save();
		$recordId = $recordModel->getId();

 		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$fromEmail = $currentUserModel->get('email1');
		$fromName = $currentUserModel->get('first_name').' '.$currentUserModel->get('last_name');
	
  	$creatorUserModel = Vtiger_Record_Model::getInstanceById($recordModel->get('assigned_user_id'), 'Users');
		$projectCreator = $creatorUserModel->get('first_name').' '.$creatorUserModel->get('last_name');
		$toEmail = strtolower(trim($creatorUserModel->get('email1')));
		
		$this->setupAdditionalFees($request, $recordId);
		$this->setupSettlementAmount($request, $recordId);

		// exit;
		if($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			$parentModuleModel = Vtiger_Module_Model::getInstance($parentModuleName);
			$parentRecordId = $request->get('sourceRecord');
			$relatedModule = $recordModel->getModule();
			$relatedRecordId = $recordModel->getId();
			if($relatedModule->getName() == 'Events'){
				$relatedModule = Vtiger_Module_Model::getInstance('Calendar');
			}

			$relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relatedModule);
			$relationModel->addRelation($parentRecordId, $relatedRecordId);
		}

		$this->savedRecordId = $recordModel->getId();
		return $recordModel;
	}

	function setupAdditionalFees($request, $legalClaimsRecord){

		// echo "<pre>"; print_r($request);	exit;
		// $legalClaimsRecord = $request->get('record');
		$subRecordIds = $request->get('legalClaimsSubRecordsFees');
		$feeCreator = $request->get('users');

		$feeTypes = $request->get('feeTypes');
		$feeSums = $request->get('feeSums');
		$feeComments = $request->get('feeComments');

		$feeUpdateStatuses = $request->get('feeUpdateStatuses');
		$feeDeleteStatuses = $request->get('feeDeleteStatuses');
 		
		$users = $request->get('users');
		$nOfFees = count($feeTypes);
		$isRecordNew = false;	
	
		 for ($i=0; $i<=$nOfFees; $i++){
			$subRecordId = $subRecordIds[$i];
			$user = $users[$i];
			$feeType = trim($feeTypes[$i]);
			$feeCreator = $feeCreator[$i];	
			$feeSum = $feeSums[$i];
			$feeComment = trim($feeComments[$i]);
			// echo "<pre>"; print_r($request);	exit;

			$isFeeItemUpdated = $feeUpdateStatuses[$i];
			$isFeeItemDeleted = $feeDeleteStatuses[$i];
			if (!empty($feeType) && (int)$subRecordId == 0){

				/* echo 'create: ' . $feeType.' feeSum = '.$feeSum. ' feeComment = '.$feeComment.' feeCreator = '.$feeCreator.'<br>'; */

				$recordModel2 = Vtiger_Record_Model::getCleanInstance('LegalClaimsSubModule');						
				$recordModel2->set('assigned_user_id', $feeCreator);
				$recordModel2->set('cf_8480', $legalClaimsRecord);
				$recordModel2->set('mode', 'create');
				$recordModel2->set("name", $feeSum);
				$recordModel2->set("cf_8478", $feeType);
				$recordModel2->set("cf_8476", $feeComment);
				$recordModel2->set("cf_8466", 'Claim Additional Fees');
				$recordModel2->save();

				$parentModuleModel1 = Vtiger_Module_Model::getInstance("LegalClaims");
				$relatedModule1 = $recordModel2->getModule();
				$relatedRecordId1 = $recordModel2->get('id');
				$relationModel1 = Vtiger_Relation_Model::getInstance($parentModuleModel1, $relatedModule1);
				
				$relationModel1->addRelation($parentRecordId, $relatedRecordId1);
				$isRecordNew = true;
			} else if ((int)$subRecordId > 0 && $isFeeItemDeleted == 1){

				// echo 'delete: ' . $feeType.' recordId = ' . $recordId.'<br>';

				$LegalClaimsSubModuleModel = Vtiger_Record_model::getInstanceById($subRecordId, 'LegalClaimsSubModule');
				$LegalClaimsSubModuleModel->delete();

			}	else if ((int)$subRecordId > 0 && $isFeeItemUpdated == 1){
 
				// echo 'update: ' . $feeType.' recordId = ' . $recordId.'<br>';

				$LegalClaimsSubModuleModel = Vtiger_Record_model::getInstanceById($subRecordId, 'LegalClaimsSubModule');
				$LegalClaimsSubModuleModel->set('mode', 'edit');
				$LegalClaimsSubModuleModel->set("name", $feeSum); // Sum
				$LegalClaimsSubModuleModel->set("cf_8478", $feeType); // Fee type
				$LegalClaimsSubModuleModel->set("cf_8476", $feeComment); // Comment
				$LegalClaimsSubModuleModel->save();

				$isRecordNew = false;
			}
		}
	}

	function setupSettlementAmount($request, $legalClaimsRecord){

		// echo "<pre>"; print_r($request);	exit;
		// $legalClaimsRecord = $request->get('record');

		$subRecordIds = $request->get('legalClaimsSubRecordsSettlement');

		$recordIds = $request->get('recordSettlementIds');
		$settlementCreators = $request->get('settlementUsers');

		$settlementSums = $request->get('settlementSums');
		$settlementDates = $request->get('settlementDates');
		$settlementComments = $request->get('settlementComments');			  

		$updateSettlementStatuses = $request->get('updateSettlementStatuses');
		$deleteSettlementStatuses = $request->get('deleteSettlementStatuses');
		// recordSettlementIds recordSettlementType updateSettlementStatus deleteSettlementStatus
		// recordFeeIds recordFeeType updateFeeStatus deleteFeeStatus
 		
		$settlementUsers = $request->get('settlementUsers');
		$nOfRecords = count($settlementSums);
		$isRecordNew = false;	
	
		 for ($i=0; $i<=$nOfRecords; $i++){
			$recordId = $subRecordIds[$i];
			// $user = $settlementUsers[$i];

			$settlementSum = trim($settlementSums[$i]);
			$settlementCreator = $settlementCreators[$i];	
			$settlementComment = trim($settlementComments[$i]);
			$settlementDate = $settlementDates[$i];

			$isSettlementItemUpdated = $updateSettlementStatuses[$i];
			$isSettlementItemDeleted = $deleteSettlementStatuses[$i];

			if (!empty($settlementSum) && (int)$recordId == 0){

				/* echo 'create: ' . $feeType.' feeSum = '.$feeSum. ' feeComment = '.$feeComment.' feeCreator = '.$feeCreator.'<br>'; */

				$recordModel2 = Vtiger_Record_Model::getCleanInstance('LegalClaimsSubModule');						
				$recordModel2->set('assigned_user_id', $settlementCreator);
				$recordModel2->set('cf_8480', $legalClaimsRecord);
				$recordModel2->set('mode', 'create');
				$recordModel2->set("name", $settlementSum);
				$recordModel2->set("cf_8476", $settlementComment);
				$recordModel2->set("cf_8474", $settlementDate);
				$recordModel2->set("cf_8466", 'Settlement Amount');
				$recordModel2->save();

				$parentModuleModel1 = Vtiger_Module_Model::getInstance("LegalClaims");
				$relatedModule1 = $recordModel2->getModule();
				$relatedRecordId1 = $recordModel2->get('id');
				$relationModel1 = Vtiger_Relation_Model::getInstance($parentModuleModel1, $relatedModule1);
				
				$relationModel1->addRelation($parentRecordId, $relatedRecordId1);
				$isRecordNew = true;
			} else if ((int)$recordId > 0 && $isSettlementItemDeleted == 1){

				// echo 'delete: ' . $feeType.' recordId = ' . $recordId.'<br>';

				$LegalClaimsSubModuleModel = Vtiger_Record_model::getInstanceById($recordId, 'LegalClaimsSubModule');
				$LegalClaimsSubModuleModel->delete();

			}	else if ((int)$recordId > 0 && $isSettlementItemUpdated == 1){
 
				// echo 'settlementDate: ' . $settlementDate.' recordId = ' . $recordId.'<br>'; exit;

				$LegalClaimsSubModuleModel = Vtiger_Record_model::getInstanceById($recordId, 'LegalClaimsSubModule');
				$LegalClaimsSubModuleModel->set('mode', 'edit');
				$LegalClaimsSubModuleModel->set("name", $settlementSum); // Sum
				$LegalClaimsSubModuleModel->set("cf_8476", $settlementComment); // comment
				$LegalClaimsSubModuleModel->set("cf_8474", $settlementDate); // Date
				$LegalClaimsSubModuleModel->save();

				$isRecordNew = false;
			}
		}
	}

	/**
	 * Function to get the record model based on the request parameters
	 * @param Vtiger_Request $request
	 * @return Vtiger_Record_Model or Module specific Record Model instance
	 */
	protected function getRecordModelFromRequest(Vtiger_Request $request) {

		$moduleName = $request->getModule();
		$recordId = $request->get('record');

		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

		if(!empty($recordId)) {
			$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
			$recordModel->set('id', $recordId);
			$recordModel->set('mode', 'edit');
		} else {
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$recordModel->set('mode', '');
		}

		$fieldModelList = $moduleModel->getFields();
		foreach ($fieldModelList as $fieldName => $fieldModel) {
			$fieldValue = $request->get($fieldName, null);
			$fieldDataType = $fieldModel->getFieldDataType();
			if($fieldDataType == 'time'){
				$fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
			}
			if($fieldValue !== null) {
				if(!is_array($fieldValue) && $fieldDataType != 'currency') {
					$fieldValue = trim($fieldValue);
				}
				$recordModel->set($fieldName, $fieldValue);
			}
		}
		return $recordModel;
	}

	function addInvitePersonToTask($options){
		$adb = PearDatabase::getInstance();
		$mode = $options['mode'];
		$taskId = $options['taskId'];
		$inviteUserId = $options['inviteUserId'];

		$result_g = $adb->pquery("Select vtiger_invitees.inviteeid
															FROM vtiger_invitees
															WHERE vtiger_invitees.activityid = ?", array($taskId));
		$noofrow = $adb->num_rows($result_g);
		
		if ($mode == "add"){
			if ($noofrow == 0){
				$adb->pquery("INSERT INTO vtiger_invitees(activityid, inviteeid) VALUES (?, ?)", array($taskId, $inviteUserId));
			}
		} else if ($mode == "update"){
				$adb->pquery("DELETE FROM vtiger_invitees WHERE activityid = ? LIMIT 1", array($taskId));
				$adb->pquery("INSERT INTO vtiger_invitees(activityid, inviteeid) VALUES (?, ?)", array($taskId, $inviteUserId));
		}
	}

}
