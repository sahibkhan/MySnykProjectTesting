<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Events_Save_Action extends Calendar_Save_Action
{

	/**
	 * Function to save record
	 * @param <Vtiger_Request> $request - values of the record
	 * @return <RecordModel> - record Model of saved record
	 */
	public function saveRecord($request)
	{
		$adb = PearDatabase::getInstance();
		$recordModel = $this->getRecordModelFromRequest($request);
		$recurObjDb = false;
		if ($recordModel->get('mode') == 'edit') {
			$recurObjDb = $recordModel->getRecurringObject();
		}
		$recordModel->save();
		// echo 'ID =' . $recordModel->getId(); exit;
		// $this->prepareEmailNotification($recordModel->getId());

		$originalRecordId = $recordModel->getId();
		if ($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			$parentModuleModel = Vtiger_Module_Model::getInstance($parentModuleName);
			$parentRecordId = $request->get('sourceRecord');
			$relatedModule = $recordModel->getModule();
			if ($relatedModule->getName() == 'Events') {
				$relatedModule = Vtiger_Module_Model::getInstance('Calendar');
			}
			$relatedRecordId = $recordModel->getId();

			$relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relatedModule);
			$relationModel->addRelation($parentRecordId, $relatedRecordId);
		}

		// Handled to save follow up event
		$followupMode = $request->get('followup');

		//Start Date and Time values
		$startTime = Vtiger_Time_UIType::getTimeValueWithSeconds($request->get('followup_time_start'));
		$startDateTime = Vtiger_Datetime_UIType::getDBDateTimeValue($request->get('followup_date_start') . " " . $startTime);
		list($startDate, $startTime) = explode(' ', $startDateTime);

		$subject = $request->get('subject');
		if ($followupMode == 'on' && $startTime != '' && $startDate != '') {
			$record = $this->getRecordModelFromRequest($request);
			$record->set('eventstatus', 'Planned');
			//recurring events status should not be held for future events
			$recordModel->set('eventstatus', 'Planned');
			$record->set('subject', '[Followup] ' . $subject);
			$record->set('date_start', $startDate);
			$record->set('time_start', $startTime);

			$currentUser = Users_Record_Model::getCurrentUserModel();
			$activityType = $record->get('activitytype');
			if ($activityType == 'Call') {
				$minutes = $currentUser->get('callduration');
			} else {
				$minutes = $currentUser->get('othereventduration');
			}
			$dueDateTime = date('Y-m-d H:i:s', strtotime("$startDateTime+$minutes minutes"));
			list($startDate, $startTime) = explode(' ', $dueDateTime);

			$record->set('due_date', $startDate);
			$record->set('time_end', $startTime);
			$record->set('recurringtype', '');
			$record->set('mode', 'create');
			$record->save();
			$heldevent = true;
		}
		$recurringEditMode = $request->get('recurringEditMode');
		$recordModel->set('recurringEditMode', $recurringEditMode);

		vimport('~~/modules/Calendar/RepeatEvents.php');
		$recurObj = getrecurringObjValue();
		$recurringDataChanged = Calendar_RepeatEvents::checkRecurringDataChanged($recurObj, $recurObjDb);
		//TODO: remove the dependency on $_REQUEST
		if (($_REQUEST['recurringtype'] != '' && $_REQUEST['recurringtype'] != '--None--' && $recurringEditMode != 'current') || ($recurringDataChanged && empty($recurObj))) {
			$focus =  CRMEntity::getInstance('Events');
			//get all the stored data to this object
			$focus->column_fields = new TrackableObject($recordModel->getData());
			try {
				Calendar_RepeatEvents::repeatFromRequest($focus, $recurObjDb);
			} catch (DuplicateException $e) {
				$requestData = $request->getAll();
				$requestData['view'] = 'Edit';
				$requestData['mode'] = 'Events';
				$requestData['module'] = 'Events';
				$requestData['duplicateRecords'] = $e->getDuplicateRecordIds();

				global $vtiger_current_version;
				$viewer = new Vtiger_Viewer();
				$viewer->assign('REQUEST_DATA', $requestData);
				$viewer->assign('REQUEST_URL', 'index.php?module=Calendar&view=Edit&mode=Events&record=' . $request->get('record'));
				$viewer->view('RedirectToEditView.tpl', 'Vtiger');
				exit();
			} catch (Exception $ex) {
				throw new Exception($ex->getMessage());
			}
		}
		return $recordModel;
	}


	function get_activity_details($record, $field)
	{
		global $adb;
		$sql_activity =  $adb->pquery('SELECT * FROM `vtiger_activity` WHERE `activityid`=' . $record);
		$r_activity = $adb->fetch_array($sql_activity);
		return $r_activity["$field"];
	}



	function prepareEmailNotification($record)
	{
		//	global $adb;

		// ini_set('display_errors', 1);
		// error_reporting(E_ALL);


		$cc = '';
		// #1    Gathering Basic details for email

		// Event Creator Details	

		$lead = Vtiger_Record_Model::getInstanceById($record, 'Calendar');
		$assisgnedUserId = $lead->get('assigned_user_id');
		$users = Users_Record_Model::getInstanceById($assisgnedUserId, 'Users');
		$creator_id = $assisgnedUserId;
		$creator_name = $users->get('first_name') . ' ' . $users->get('last_name');

		// Event Update Details	

		$who_updated_id = $lead->get('modifiedby');
		$updatedByInfo = Users_Record_Model::getInstanceById($who_updated_id, 'Users');
		$who_updated_email = $updatedByInfo->get('email1');
		$who_updated_name = $updatedByInfo->get('first_name') . ' ' . $updatedByInfo->get('last_name');


		if (!empty($lead->get('cf_833'))) {
			$subject .= $lead->get('cf_833');
		} else {
			if ($lead->get('company')) $subject .= $lead->get('company');
		}

		// #2   Gathering Message Body Details
		$all_field_details = '';
		// $all_field_details = $this->get_lead_history($record, '');

		// #3   Gathering people to put in CC	

		// Gathering Assigned Person to Event
		$creator_id = $assisgnedUserId;
		/* 		$cc .= get_user_details($creator_id,1).',';	 */
		$creatorInfo = Users_Record_Model::getInstanceById($creator_id, 'Users');
		$cc = $creatorInfo->get('email1') . ',';

		$event_status = parent::getUpdateCounts($record);
		if ($event_status == 1)  $message_status = "Lead for $subject has been created";
		if ($event_status > 1)   $message_status = "Lead from $subject has been updated";

		/* 	$arranged_details = $this->handleLeadDetails($record,$assigned_users,$all_field_details,$event_status);	

	$this->prepareLeadEmailNotification($creator_email,$creator_name,$who_updated_email,$who_updated_name,$module,$record,$subject,$message_status,$arranged_details,$cc,$event_status);
	 */




		// ------------



		$link = "https://gems.globalink.net/index.php?module=Leads&view=Detail&record=" . $record;
		// $link = $record;
		$to = "r.gusseinov@globalinklogistics.com";
		// $to = "i.nikolayenko@globalinklogistics.com";
		$cc = '';

		$from = $to;
		$body = '';
		$creator_name = 'Ruslan';
		$message_status = '';
		$body .= "<html><head> <style> #calendar_notification tr td{ margin:3px; } </style> </head>
							<body><table id='calendar_notification'> ";
		$body .= "<tr><td colspan=2>Dear <b>" . $creator_name . ",</b> </td></tr>";
		$body .= "<tr><td colspan=2> $message_status </td></tr>
				 $sum	
				<tr><td colspan=2>Link: <a href='$link'> Link to GEMS </a></td></tr>";
		$body .= "</table> </body> </html> ";

		// Set content-type when sending HTML email
		$headers = "MIME-Version: 1.0" . "\n";
		$headers .= "Content-type:text/html;charset=UTF-8" . "\n";

		$headers .= $from . "\n";
		$headers .= 'Reply-To: ' . $to . '' . "\n";

		$headers .= "CC:" . $cc . "\n";
		require_once("modules/Emails/mail.php");
		$r = send_mail('Events', $to, $from, $from, $subject, $body, $cc, '', '', '', '', true);
	}


	/**
	 * Function to get the record model based on the request parameters
	 * @param Vtiger_Request $request
	 * @return Vtiger_Record_Model or Module specific Record Model instance
	 */
	protected function getRecordModelFromRequest(Vtiger_Request $request)
	{
		$recordModel = parent::getRecordModelFromRequest($request);
		if ($request->has('selectedusers')) {
			$recordModel->set('selectedusers', $request->get('selectedusers'));
		}
		return $recordModel;
	}
}
