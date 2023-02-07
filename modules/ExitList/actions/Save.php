<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
class ExitList_Save_Action extends Vtiger_Save_Action {

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$record = $request->get('record');
		
		// $module_get = $_GET['module'];
		// $record_get = $_GET['record'];
		// $custom_permission_check = custom_access_rules($record_get,$module_get);
		//$record_owner = get_crmentity_details_own($record_get,'smcreatorid');
		//global $current_user;
/* 		
		if ((!Users_Privileges_Model::isPermitted($moduleName, 'Save', $record)) && ($custom_permission_check == 'yes')) {
			throw new AppException('LBL_PERMISSION_DENIED');
		} */
		
	}

	public function process(Vtiger_Request $request) {
		// $request->set('cf_6668', rtrim($request->get('cf_6668').",".implode(',', $request->get('item')),','));
		$notification = $request->get('record');
		$recordModel = $this->saveRecord($request);
		$sourcerecordid = $recordModel->get('id');
		if($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			$parentRecordId = $request->get('sourceRecord');
			$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentRecordId, $parentModuleName);
			//TODO : Url should load the related list instead of detail view of record
			$loadUrl = $parentRecordModel->getDetailViewUrl();
		} else if ($request->get('returnToList')) {
			$loadUrl = $recordModel->getModule()->getListViewUrl();
		} else {
			$loadUrl = $recordModel->getDetailViewUrl();
		}
		header("Location: $loadUrl");
	}

	/**
	 * Function to save record
	 * @param <Vtiger_Request> $request - values of the record
	 * @return <RecordModel> - record Model of saved record
	 */
	public function saveRecord($request) {
		global $adb;
		$recordModel = $this->getRecordModelFromRequest($request);		
		$recordModel->save();
		
		if($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			$parentModuleModel = Vtiger_Module_Model::getInstance($parentModuleName);
			$parentRecordId = $request->get('sourceRecord');
			$relatedModule = $recordModel->getModule();
			$relatedRecordId = $recordModel->getId();

			$relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relatedModule);
			$relationModel->addRelation($parentRecordId, $relatedRecordId);
		}

		  // Exit List ID relatedRecordId
			$employeePositionName = strtolower($recordModel->getDisplayValue('cf_7540'));
			$employeeLocationId = $recordModel->get('cf_7540');

/* 			$instanceOfExitList = new ExitList();
			$responsible_ids = $instanceOfExitList->getExitListApprovers($employeeLocationId); */
		  // echo "<pre>"; print_r($employeeLocationId); exit;

			
			$searchKeyword = 'driver';
			$pos = strpos($employeePositionName, $searchKeyword);
			if ($pos === false) $isDriver = false; else $isDriver = true;
			
			// Number of records relevant to current location and department
			$sql_m = "SELECT crmid FROM `vtiger_crmentityrel` WHERE crmid = ? AND `module` = 'ExitList'";
			$result_m = $adb->pquery($sql_m, array($request->get('record')));
			$nOfRecords = $adb->num_rows($result_m);

			$userId = 9;
			$locationId = 0;

			if ($nOfRecords == 0){
				

/* 				$instanceOfExitList = new ExitList();
				$responsible_ids = $instanceOfExitList->getExitListApprovers($employeeLocationId);
 */

				// echo "<pre>"; print_r($responsible_ids); exit;



 				foreach ($responsible_ids as $responsible_id){

					$responsibleId = $responsible_id['userId'];
					$approverName = $responsible_id['approverName'];
					$recordModel2 = Vtiger_Record_Model::getCleanInstance('ExitListEntries');						
					$recordModel2->set('assigned_user_id', $userId);
					$recordModel2->set('mode', 'create');
					$recordModel2->set("name", $responsibleId);
					$recordModel2->save();
					$exitListEntryId = $recordModel2->get('id');

					//related list entry
					$parentModuleModel1 = Vtiger_Module_Model::getInstance("ExitList");
					$parentRecordId1 = $recordModel->getId();
					$relatedModule1 = $recordModel2->getModule();
					$relatedRecordId1 = $recordModel2->get('id');
					$relationModel1 = Vtiger_Relation_Model::getInstance($parentModuleModel1, $relatedModule1);
					$relationModel1->addRelation($parentRecordId1, $relatedRecordId1);
					$exitListInstance = new ExitList();

					//Gathering email info
					$recordExitList = Vtiger_Record_Model::getInstanceById($parentRecordId1, 'ExitList');
					$currentRefNo = $recordExitList->get('cf_7538');
					$forwardTo = $recordExitList->getDisplayValue('cf_7534');
					$resigningDate = $recordExitList->get('cf_7536');
					$recordCreatorId = $recordExitList->get('assigned_user_id');
					$employeeName = $recordExitList->getDisplayValue('name');
			
					$creatorRecord = Vtiger_Record_Model::getInstanceById($recordCreatorId, 'Users');
					$requestedByEmail = $creatorRecord->get('email1');
					$requestedByName = $creatorRecord->get('first_name').' '.$creatorRecord->get('last_name');
			
					// Fetch request user email		
					$queryUser = $adb->pquery("
											SELECT vtiger_userlistcf.cf_3355
											FROM vtiger_userlistcf
											INNER JOIN vtiger_userlist ON vtiger_userlist.userlistid = vtiger_userlistcf.userlistid
											WHERE vtiger_userlistcf.userlistid = ?",array($responsibleId));
					$forwardToEmail = trim($adb->query_result($queryUser, 0, 'cf_3355'));


					// Getting Exit list link and Hand Over list links in order to send in email
					$queryResignation = $adb->pquery("SELECT crmid
																		 FROM `vtiger_crmentityrel`
																		 WHERE `relcrmid` = ? AND relmodule = ? ",array($relatedRecordId, 'ExitList'));
					$resignationId = $adb->query_result($queryResignation, 0, 'crmid');

					$queryHandOverList = $adb->pquery("SELECT relcrmid 
																						 FROM `vtiger_crmentityrel`
																						 WHERE `crmid` = ? AND module = ?
																						 AND relmodule = ? ",array($resignationId, 'Resignation', 'HandOverList'));
					$nOfHandOverLists = $adb->num_rows($queryHandOverList);
					$handOverLink = '';

					for ($i=0; $i<$nOfHandOverLists; $i++){
						$handOverListId = $adb->query_result($queryHandOverList, $i, 'relcrmid');
						$linkUrl = $_SERVER['SERVER_NAME'] . "/index.php?module=HandOverList&view=Detail&record=".$handOverListId;
						$handOverLink .= "<tr><td colspan=2> <a href='$linkUrl'> Hand Over List </a> </td> </tr>";
					}										
					
/*  					$details = array();
					$details['employeeName'] = $employeeName;
					$details['fromEmail'] = $requestedByEmail;
					$details['fromName'] = $requestedByName;
					$details['approverName'] = $approverName;

					$details['recordId'] = $relatedRecordId1;
					$details['exitListId'] = $relatedRecordId;
					$details['handOverLink'] = $handOverLink;
					$details['refNo'] = $currentRefNo;
					$details['resigningDate'] = $resigningDate;
					$details['forwardTo'] = $forwardTo;
					$details['forwardToEmail'] = $forwardToEmail;
					$details['type'] = 'exitList';				
					$exitListInstance->sendEmailNotification($details); */
			
				}

			}

 
		return $recordModel;
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
			$modelData = $recordModel->getData();
			$recordModel->set('id', $recordId);
			$recordModel->set('mode', 'edit');
		} else {
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$modelData = $recordModel->getData();
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
				if(!is_array($fieldValue)) {
					$fieldValue = trim($fieldValue);
				}
				$recordModel->set($fieldName, $fieldValue);
			}
		}
		return $recordModel;
	}
}
