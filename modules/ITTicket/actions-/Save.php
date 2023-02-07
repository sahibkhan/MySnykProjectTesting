<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
class ITTicket_Save_Action extends Vtiger_Save_Action {

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$record = $request->get('record');
		
			
		if ((!Users_Privileges_Model::isPermitted($moduleName, 'Save', $record)) ) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
		
	}

	public function process(Vtiger_Request $request) {
		// $request->set('cf_6340', strip_tags( $request->get('cf_6340'), ""));
		$recordModel = $this->saveRecord($request);
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
	
			$locationCode = Vtiger_LocationList_UIType::getDisplayValue($request->get('cf_6348'));
			$ticketRefNumber = $request->get('cf_6376');
			
			$reffNumber = array();
			$sendEmailNotification = '';

			if( empty( $ticketRefNumber ) )
			{
				$db = PearDatabase::getInstance();
				$getExistingRefNumber = $db->pquery('SELECT cf_6376 FROM vtiger_itticketcf ORDER BY itticketid DESC LIMIT 1', array());
				
				$lastReffNumber = $db->query_result($getExistingRefNumber,0,'cf_6376');
				
				if( $lastReffNumber == '' )
				{
					$number = 1;
					$digits = 5;
					$newReff = str_pad($number, $digits, "0", STR_PAD_LEFT);

					$reffNumber = $locationCode.'-'.$newReff;
					$sendEmailNotification = 'newTicket';
 				}
 				else
 				{
 					$getExistingRefNumber = $db->pquery('SELECT cf_6376 FROM vtiger_itticketcf WHERE cf_6376 = $ticketRefNumber', array());
				
					$reffExists = $db->query_result($getExistingRefNumber,0,'cf_6376');

					if (!$reffExists)
					{
						$tempLastReffNumber[] = substr($lastReffNumber, 3, 8);
						$tempNewReffNumber = (int)$tempLastReffNumber[0]+1;

						$number = $tempNewReffNumber;
						$digits = 5;
						$newReffNumber = str_pad($number, $digits, "0", STR_PAD_LEFT);
						
						$reffNumber = $locationCode.$newReffNumber;

						$sendEmailNotification = "existingTicket";
					}
					else
						$reffNumber = $ticketRefNumber;
				 }
				 $request->set('cf_6376', $reffNumber);
			}
			
			else
				{
					//$reffNumber = $ticketRefNumber;					
					$sendEmailNotification = "updatingTicket";
				}
			

			

		// $result = Vtiger_Util_Helper::transformUploadedFiles($_FILES, true);
		// $_FILES = $result['cf_6342'];

		$recordModel = $this->getRecordModelFromRequest($request);
		$_SESSION['sendmsg_repeat'] = $request->getModule();
		
		$recordModel->save();
			
		// if ($sendEmailNotification)
		// {
		// 	$emailStatus = $this->sendTicketEmailNotification($sendEmailNotification, $request);
		// }

		//$emailStatus = $this->sendTicketEmailNotification($sendEmailNotification);
		//echo '<pre>';
		//print_r($emailStatus);
		//exit;


		if($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			$parentModuleModel = Vtiger_Module_Model::getInstance($parentModuleName);
			$parentRecordId = $request->get('sourceRecord');
			$relatedModule = $recordModel->getModule();
			$relatedRecordId = $recordModel->getId();

			echo '<pre>'; print_r($relatedRecordId); exit;

			$relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relatedModule);
			$relationModel->addRelation($parentRecordId, $relatedRecordId);
		}
		return $recordModel;
	}


	/**
	 * Function to get the record model based on the request parameters
	 * @param Vtiger_Request $request
	 * @return Vtiger_Record_Model or Module specific Record Model instance
	*/
	public function sendTicketEmailNotification($notificationStatus, Vtiger_Request $request) {
		$supportGroupTable = 'vtiger_groups';
		$support_group_id = 1197; /* group id for IT Ticket */

		$db = PearDatabase::getInstance();
		// $current_user = Users_Record_Model::getCurrentUserModel();
		// $currentUserId = $current_user->id;

		$supportGroupQuery = "SELECT userid FROM $supportGroupTable WHERE groupid = $support_group_id";

		$supportGroupUsers = $db->query_result($db->pquery($supportGroupQuery, array()));
		print_r($supportGroupUsers);
		return $supportGroupUsers;
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
