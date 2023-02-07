<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Class InvoiceGLK_Edit_View extends Vtiger_Edit_View {


	public function process(Vtiger_Request $request) {
		global $adb;
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
			if(empty($record)){
				if($request->get('returnmodule') == 'Job' && $request->get('returnrecord')){
					$sourceModule = $request->get('returnmodule');
					$sourceRecord = $request->get('returnrecord');
					$currentUserId = $request->get('assigned_user_id');
					$currentUserModel = Vtiger_Record_Model::getInstanceById($currentUserId, 'Users');
					$userDateFormat = $currentUserModel->get('date_format');
	
					// Get user email from user list of requested person
					$jobFileRecord = Vtiger_Record_Model::getInstanceById($sourceRecord, $sourceModule);	
					// $sequenceNumber = $this->getAppendixSequenceNumber($sourceRecord);

					// Getting Director of company

					
					$customerId = $jobFileRecord->get('cf_1441');
					$contactId = $this->getContactByCustomerId($customerId);
					
					// echo "<pre>"; print_r($contactId); exit;
					
					/* 					$currentUserModel = Vtiger_Record_Model::getInstanceById($jobFileRecord, 'Users');
					$userDateFormat = $currentUserModel->get('date_format'); */
					
					
					
					// $request->set('account_id', 123); // Number
					
					$jobRefNo = $jobFileRecord->get('cf_1198');
				  $request->set('name', $jobRefNo); // Appendix No.
					// $request->set('cf_1050', $sequenceNumber + 1); // Order No.
					$request->set('cf_5079', $sourceRecord); // Job		
					if ($contactId){
						$request->set('cf_3427', $contactId); // Contact
						//$request->set('cf_3427', '1218356'); // Contact
					}
					

	
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


	function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);

		$moduleName = $request->getModule();

		$jsFileNames = array(
				"libraries.jquery.ckeditor.ckeditor",
				"libraries.jquery.ckeditor.adapters.jquery",
				'modules.Vtiger.resources.CkEditor',
				"include.InvoiceGLK.appendix",
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
	

	function getAppendixSequenceNumber($jobFileId){
		global $adb;
		$querySequence = $adb->pquery("SELECT vtiger_crmentityrel.relcrmid
																		FROM vtiger_crmentityrel
																		LEFT JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_crmentityrel.relcrmid
																		WHERE vtiger_crmentity.deleted = 0 AND vtiger_crmentityrel.crmid = ? AND vtiger_crmentityrel.module = ? 
																		AND vtiger_crmentityrel.relmodule = ?", array($jobFileId, 'Job', 'InvoiceGLK'));
		return $adb->num_rows($querySequence);
	}

	function getContactByCustomerId($customerId){
		global $adb;
		/*
		$queryContact = $adb->pquery("SELECT vtiger_contactdetails.contactid
																	FROM vtiger_contactdetails
																	INNER JOIN vtiger_contactscf ON vtiger_contactscf.contactid = vtiger_contactdetails.contactid
																	INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_contactdetails.contactid
																	
																	WHERE vtiger_crmentity.deleted = 0 AND vtiger_contactdetails.accountid = ? 
																	AND vtiger_contactscf.cf_5515 <> '' ", array($customerId));
		*/
		$queryContact = $adb->pquery("SELECT vtiger_contactdetails.contactid
																	FROM vtiger_contactdetails
																	INNER JOIN vtiger_contactscf ON vtiger_contactscf.contactid = vtiger_contactdetails.contactid
																	INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_contactdetails.contactid
																	
																	WHERE vtiger_crmentity.deleted = 0 AND vtiger_contactdetails.accountid = ? 
																	AND (vtiger_contactscf.cf_5359 IS NOT NULL AND vtiger_contactscf.cf_5359 != '')
																	
																	", array($customerId));
		$contactId = $adb->query_result($queryContact, 0, 'contactid');
		return $contactId;


	}




}