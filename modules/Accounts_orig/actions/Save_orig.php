<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Accounts_Save_Action extends Vtiger_Save_Action {

	public function requiresPermission(\Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$moduleParameter = $request->get('source_module');
		if (!$moduleParameter) {
			$moduleParameter = 'module';
		}else{
			$moduleParameter = 'source_module';
		}
		$record = $request->get('record');
		$recordId = $request->get('id');
		if (!$record) {
			$recordParameter = '';
		}else{
			$recordParameter = 'record';
		}
		$actionName = ($record || $recordId) ? 'EditView' : 'CreateView';
        $permissions[] = array('module_parameter' => $moduleParameter, 'action' => 'DetailView', 'record_parameter' => $recordParameter);
		$permissions[] = array('module_parameter' => $moduleParameter, 'action' => $actionName, 'record_parameter' => $recordParameter);
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
	
	public function validateRequest(Vtiger_Request $request) {
		return $request->validateWriteAccess();
	}

	/**
	 * Function to add new recipients
	 * Alevtina, Pasha Dennis country vise 
	 */


	public function addNewRecipients($record){
		global $adb;
		$inviteesCSM = array(5, 14);

		foreach($inviteesCSM as $inviteeid){	 
			if ($inviteeid != ''){

				$query="insert into vtiger_invitees values(?,?,?)";
				$adb->pquery($query, array($record, $inviteeid, 'sent'));
			
/* 				$query="insert into vtiger_invitees values(?,?,?)";
				$adb->pquery($query, array($record, $inviteeid, 'sent'));   */

			}
		}

/* 		$accountInfo = Vtiger_Record_Model::getInstanceById($record, 'Accounts');
		$country = trim($accountInfo->get('bill_country'));
		$country = strtolower($country);
 */
			// Deprecated in 29.03.2021

/* 		$DennisLocations = array('azerbaijan', 'kyrgyzstan', 'kazakhstan', 'kazan', 'belgium', 'netherlands','luxembourg', 'казахстан', 'кыргыстан', 'казань', 'бельгия', 'нидерланды', 'люксембург');
		if (in_array($country, $DennisLocations)){
			 $query="insert into vtiger_invitees values(?,?,?)";
			 $adb->pquery($query, array($record, 882, 'sent'));
		} */

	}


	public function process(Vtiger_Request $request) {
		try {
			global $adb;
			$record = $request->get('record');
			$recordModel = $this->saveRecord($request);
			$inst = new Accounts();
			if ($recordModel->get('id')) $record = $recordModel->get('id');

			// $this->addNewRecipients($record);
		  // $inst->handleEmailNotification($record);


			// $adb->pquery("UPDATE `a_test` SET `name` = 'Ruslan test' WHERE `userlistid` = 992790");



			/*	
			$s_mod = $adb->pquery("SELECT max(id) as maxid  FROM `vtiger_modtracker_basic` WHERE `crmid` = '".$record."'");
			$r_mod = $adb->fetch_array($s_mod);
			$maxid = $r_mod['maxid'];

			$dresult_1 = $adb->pquery("SELECT * FROM `vtiger_modtracker_detail` WHERE `id` = $maxid AND fieldname = 'cf_1855'");
			$numRows = $adb->num_rows($dresult_1);		
			$r_mod = $adb->fetch_array($dresult_1);
			$postvalue = $r_mod['postvalue'];
			$prevalue = $r_mod['prevalue'];

			$dres_user = $adb->pquery("SELECT modifiedby FROM `vtiger_crmentity` WHERE `crmid` = '".$record."' ");	
			$r_user = $adb->fetch_array($dres_user);
			$r_user['modifiedby'] ? $updated_by = $r_user['modifiedby']: $updated_by = 1;

			if ($numRows > 0){
				if ($postvalue <> $prevalue){
					$s_modMax = $adb->pquery("SELECT * FROM `vtiger_crmentity_seq`");
					$r_modMax = $adb->fetch_array($s_modMax);
					$maxCRM_id = $r_modMax['id'];
					$maxCRM_id ++;
	
					 $smcreatorid = $updated_by;
					 $smownerid = $updated_by;
					 $modifiedby = $updated_by;
					 $createdtime = date('Y-m-d h:i:s', time());
					 $modifiedtime = date('Y-m-d h:i:s', time());
					 $presence = 1;
					 $label = $prevalue;
					 $deleted = 0;
					 $setype = 'PaymentHistory';
					 $accountId = $record;
					 if ($maxCRM_id > 100){
	
						$insertResult = $adb->pquery("INSERT INTO vtiger_crmentity(`crmid`, `smcreatorid`, `smownerid`, `modifiedby`, `setype`,
						`createdtime`, `modifiedtime`, `presence`, `label`, `deleted`)
							VALUES ($maxCRM_id, $smcreatorid, $smownerid, $modifiedby, '$setype', '$createdtime','$modifiedtime', $presence, '$label', '$deleted')");
							$updateResult = $adb->pquery("UPDATE vtiger_crmentity_seq SET id = $maxCRM_id");
	
							$insertResult = $adb->pquery("INSERT INTO vtiger_paymenthistory(`paymenthistoryid`, `name`) VALUES ($maxCRM_id, '$postvalue')");
							$insertCFResult = $adb->pquery("INSERT INTO vtiger_paymenthistorycf(`paymenthistoryid`, `cf_6830`) VALUES ($maxCRM_id, $accountId)"); 
	
							// Adding relation between Account and Payment History module
							$insertResult = $adb->pquery("INSERT INTO `vtiger_crmentityrel` (`crmid`, `module`, `relcrmid`, `relmodule`) VALUES ($accountId, 'Accounts', $maxCRM_id, 'PaymentHistory')");
					 }
	
				} else {
					//	$dresult_2 = $adb->pquery("UPDATE `a_test_survey` SET `name` = ' Нет обновления ' WHERE `test_id` = 29");
				}
			}
			*/

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
		if($request->get('imgDeleted')) {
			$imageIds = $request->get('imageid');
			foreach($imageIds as $imageId) {
				$status = $recordModel->deleteImage($imageId);
			}
		}
		$recordModel->save();
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
			if($fieldDataType == 'time' && $fieldValue !== null){
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
}
