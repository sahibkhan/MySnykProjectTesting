<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Calendar_Save_Action extends Vtiger_Save_Action {

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$record = $request->get('record');
		parent::checkPermission($request);
		if ($record) {
			$activityModulesList = array('Calendar', 'Events');
			$recordEntityName = getSalesEntityType($record);

			if (!in_array($recordEntityName, $activityModulesList) || !in_array($moduleName, $activityModulesList)) {
				throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
			}
		}
	}

	public function process(Vtiger_Request $request) {
		try {
			$recordModel = $this->saveRecord($request);
			$loadUrl = $recordModel->getDetailViewUrl();
/* 		
				
  		ini_set('display_errors', 1);
			error_reporting(E_ALL); 
   */


			$record = $request->get('record');
			if ($recordModel->get('id')) $record = $recordModel->get('id');

/* 			echo 'ID = ' . $record;
			exit;
 */
				$this->handleEmailNotification($record);

			if ($request->get('returntab_label')) {
				$loadUrl = 'index.php?'.$request->getReturnURL();
			} else if($request->get('relationOperation')) {
				$parentModuleName = $request->get('sourceModule');
				$parentRecordId = $request->get('sourceRecord');
				$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentRecordId, $parentModuleName);
				//TODO : Url should load the related list instead of detail view of record
				$loadUrl = $parentRecordModel->getDetailViewUrl();
			} else if ($request->get('returnToList')) {
				$moduleModel = $recordModel->getModule();
				$listViewUrl = $moduleModel->getListViewUrl();

				if ($recordModel->get('visibility') === 'Private') {
					$loadUrl = $listViewUrl;
				} else {
					$userId = $recordModel->get('assigned_user_id');
					$sharedType = $moduleModel->getSharedType($userId);
					if ($sharedType === 'selectedusers') {
						$currentUserModel = Users_Record_Model::getCurrentUserModel();
						$sharedUserIds = Calendar_Module_Model::getCaledarSharedUsers($userId);
						if (!array_key_exists($currentUserModel->id, $sharedUserIds)) {
							$loadUrl = $listViewUrl;
						}
					} else if ($sharedType === 'private') {
						$loadUrl = $listViewUrl;
					}
				}
			} else if ($request->get('returnmodule') && $request->get('returnview')){
				$loadUrl = 'index.php?'.$request->getReturnURL();
			}
			header("Location: $loadUrl");
		} catch (DuplicateException $e) {
			$mode = '';
			if ($request->getModule() === 'Events') {
				$mode = 'Events';
			}

			$requestData = $request->getAll();
			unset($requestData['action']);
			unset($requestData['__vtrftk']);

			if ($request->isAjax()) {
				$response = new Vtiger_Response();
				$response->setError($e->getMessage(), $e->getDuplicationMessage(), $e->getMessage());
				$response->emit();
			} else {
				$requestData['view'] = 'Edit';
				$requestData['mode'] = $mode;
				$requestData['module'] = 'Calendar';
				$requestData['duplicateRecords'] = $e->getDuplicateRecordIds();

				global $vtiger_current_version;
				$viewer = new Vtiger_Viewer();
				$viewer->assign('REQUEST_DATA', $requestData);
				$viewer->assign('REQUEST_URL', "index.php?module=Calendar&view=Edit&mode=$mode&record=".$request->get('record'));
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
		
		$recordModel->save();
		if($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			$parentModuleModel = Vtiger_Module_Model::getInstance($parentModuleName);
			$parentRecordId = $request->get('sourceRecord');
			$relatedModule = $recordModel->getModule();
			if($relatedModule->getName() == 'Events'){
				$relatedModule = Vtiger_Module_Model::getInstance('Calendar');
			}
			$relatedRecordId = $recordModel->getId();

			$relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relatedModule);
			$relationModel->addRelation($parentRecordId, $relatedRecordId);
		}
		return $recordModel;
	}


	public function handleEmailNotification($record){

		$module = 'Calendar';
		$cc = '';  
		// #1    Gathering Basic details for email
			
 		$calendar_info = Vtiger_Record_Model::getInstanceById($record, 'Calendar');
		$assisgnedUserId = $calendar_info->get('assigned_user_id');
		$users = Users_Record_Model::getInstanceById($assisgnedUserId, 'Users');
		$creator_id = $assisgnedUserId;
		$creator_name = $users->get('first_name') .' ' . $users->get('last_name');

		// Event Update Details	

		$who_updated_id = $calendar_info->get('modifiedby');
		$updatedByInfo = Users_Record_Model::getInstanceById($who_updated_id, 'Users');		
		$who_updated_email = $updatedByInfo->get('email1');
		$who_updated_name = $updatedByInfo->get('first_name') . ' ' . $updatedByInfo->get('last_name');
		
		$subject = $calendar_info->get('subject');
		$eventstatus = $calendar_info->get('eventstatus');
		$activity_fld_value = $calendar_info->get('activitytype');		

		if (($activity_fld_value == 'Meeting') || ($activity_fld_value == 'Call')){

					// Gathering Assigned Person to Event
			// $assigned_users = get_calendar_history($record,'cf_839');
			$assigned_users = $calendar_info->get('cf_839');	
	  

			$actionplan_assigned_users = $this->get_vr_history($record, 'cf_745');
			$actionplan_text = $this->get_vr_history($record, 'cf_747');
			$actionplan_deadline = $this->get_vr_history($record, 'cf_749');	
			

										// #2   Gathering Message Body Details
	
							// Gathering history details
			  $all_field_details = $this->get_vr_history($record,'');
			
				// Gathering Assigned Person Action Plan
				$users = $calendar_info->get('cf_745');
				$actionplan_cc = $this->arrange_muptiple_users_vr($users, 3);
				$cc .= $this->arrange_people_cc_vr($actionplan_cc);
 	
			
							// Gathering Invited People
 				$invited_cc = $this->get_calendar_invited_RFQ($record,2);
				$cc .= $this->arrange_people_cc_vr($invited_cc);

					// Gathering Event Status (Just created or updated)
					$event_type = $this->detect_event_type($record,$module);
					$event_status = $this->detect_message_status_vr($record);
					if ($event_status == 1) $message_status = "A $event_type for $subject has been created";
					else
					if ($event_status > 1) $message_status = "Please note that an update was made to the $event_type for $subject as following:";  


					$arranged_details = $this->arrange_fetch_details_vr($record,$assigned_users,$actionplan_assigned_users,$actionplan_text,$actionplan_deadline,$all_field_details,$event_status);	
							
										// Message title 
					if ($eventstatus != 'Held'){
						$subject = ucfirst($event_type).'_'.$subject;
					} 
					else 
					if ($eventstatus == 'Held'){
						$subject = $subject;
					}	  

					$this->send_calendar_notification($creator_email,$creator_name,$who_updated_email,$who_updated_name,$module,$record,$subject,$message_status,$arranged_details,$cc,$event_status);


		} else
		if ($activity_fld_value == 'Task'){
										// #1 Gathering Message Body Details
			
						// Gathering Assigned Person to Event
			$assigned_users = $this->get_vr_history($record, 'cf_865');
			
						// Gathering history details
			$all_field_details = $this->get_vr_history($record, '');
		
						// Gathering Assigned Person to Task
			$users = $calendar_info->get('cf_865');		
			$assigned_cc = $this->arrange_muptiple_users_vr($users, 3);
			$cc = $this->arrange_people_cc_vr($assigned_cc);
		 
						// Gathering Task Status (Just created or updated)
			$event_status = $this->detect_message_status_vr($record);
			if ($event_status == 1) $message_status = "Task has been created";
			else
			if ($event_status > 1) $message_status = "Please note that an update was made to the task"; 
			
			$fake_array = array();
			$arranged_details = $this->arrange_fetch_details_vr($record,$assigned_users,$fake_array,0,0,$all_field_details,$event_status);	
		 
			$this->send_calendar_notification($creator_email,$creator_name,$who_updated_email,$who_updated_name,$module,$record,$subject,$message_status,$arranged_details,$cc,$event_status);
			
		}



	}


	function get_calendar_invited_RFQ($record,$mode){
		global $adb;
    $value = '';
    $n = 0;
		$person_array = array();
		$result =  $adb->pquery("SELECT * FROM `vtiger_invitees` WHERE `activityid`=$record");
		$numRows = $adb->num_rows($result);
		for($i=0; $i < $numRows;$i++){
			$inviteeid = $adb->query_result($result,$i,"inviteeid");
			// echo "inviteeid" . $inviteeid.'<br>';
			$n ++;
			$res_inner =  $adb->pquery("SELECT * FROM `vtiger_users` where `id`=".$inviteeid);
			$row_inner =  $adb->fetch_array($res_inner);  
			if ($mode == 1){
				$login = $row_inner['user_name'];
				$person_array[$n] = $login;
			} else if ($mode == 2){
					$person_array[$n] = $row_inner['email1'].',';
			}
		}
		return $person_array;
	}



	function send_calendar_notification($creator_email,$creator_name,$who_updated_email,$who_updated_name,$module,$record,$subject,$message_status,$sum, $cc, $event_status){
		// $link = domain_name(); 
	 
		$link = "https://gems.globalink.net/index.php?module=Calendar&view=Detail&record=$record";

		// $link = $record;
		$to = $who_updated_email;

		$from = $who_updated_email;  
		$body = '';
		$body .= "<html><head> <style> #calendar_notification tr td{ margin:3px; } </style> </head>
							<body><table id='calendar_notification'> ";
		$body .= "<tr><td colspan=2>Dear <b>".$creator_name.",</b> </td></tr>";
		$body .= "<tr><td colspan=2> $message_status </td></tr>
				 $sum	
				<tr><td colspan=2>Link: <a href='$link'> Link to GEMS </a></td></tr>";
		$body .= "</table> </body> </html> ";
									
		// Set content-type when sending HTML email
		$headers = "MIME-Version: 1.0" . "\n";
		$headers .= "Content-type:text/html;charset=UTF-8" . "\n";
	
		$headers .= $from . "\n";
		$headers .= 'Reply-To: '.$to.'' . "\n";
    
		$headers .= "CC:" . $cc . "\n";
 		require_once("modules/Emails/mail.php");
		$r = send_mail('Calendar', $to, $from, $from, $subject, $body, $cc ,'','','','',true);

	}


	public function get_vr_history($record,$history_field){
		global $adb;

			//Excluding already mentioned fields
			$exclude_field = "'label', 'duration_minutes' ,'cf_839','cf_745','cf_747','cf_749','assigned_user_id','record_id','record_module','sendnotification','recurringtype','reminder_time','duration_hours','cf_865'";

			$s_modtracker_basic =  $adb->pquery("SELECT max(`id`) as `maxid` FROM `vtiger_modtracker_basic` where `crmid` = ".$record);
			$r_modtracker_basic =  $adb->fetch_array($s_modtracker_basic);  
			$max_id = $r_modtracker_basic['maxid'];
			if (!isset($max_id)) $max_id = 0;

			$history = '';
			$vendor_value = '';
			$val = '';
			
			if ($history_field != ''){

					if ($history_field == 'cf_839'){
								$query_details = $adb->pquery("SELECT * FROM `vtiger_modtracker_detail` where `id` = $max_id and `fieldname`='$history_field'");
								$details = $adb->fetch_array($query_details);
								$history = $this->arrange_muptiple_users_vr($details['postvalue'], 1);
					} else 	if ($history_field == 'cf_745'){
						
						$query_details = $adb->pquery("SELECT * FROM `vtiger_modtracker_detail` where `id` = $max_id and `fieldname`='cf_745'");
							$details = $adb->fetch_array($query_details);
							$history = $this->arrange_muptiple_users_vr($details['postvalue'], 1);
							// echo $details['postvalue'];
							// print_r($history);
							// exit;

				} else if ($history_field == 'cf_747'){
						$query_details = $adb->pquery("SELECT * FROM `vtiger_modtracker_detail` where `id` = $max_id and `fieldname`='cf_747'");
						$details = $adb->fetch_array($query_details);
						$history = $this->arrange_actionplan_text($details['postvalue']);

				} else if ($history_field == 'cf_749'){
						$query_details = $adb->pquery("SELECT * FROM `vtiger_modtracker_detail` where `id` = $max_id and `fieldname`='cf_749'");
						$details = $adb->fetch_array($query_details);
						$history = $this->arrange_muptiple_users_vr($details['postvalue'], 1);
				} else if ($history_field == 'cf_865'){
						$query_details = $adb->pquery("SELECT * FROM `vtiger_modtracker_detail` where `id` = $max_id and `fieldname`='cf_865'");
						$details = $adb->fetch_array($query_details);
						$history = $this->arrange_muptiple_users_vr($details['postvalue'], 1);
				}
					
			}
			else  
			if ($history_field == ''){
					$query_details = $adb->pquery("SELECT * FROM `vtiger_modtracker_detail` where `id`=$max_id and (`fieldname` not in ($exclude_field))");
					$array_history = array();    	
					
					$i = 0; 		
							While ($r_list = $adb->fetch_array($query_details)){
									$i ++;
									$array_history[$i][1] = $r_list['fieldname'];
									$array_history[$i][2] = $r_list['postvalue']; 	
									$array_history[$i][3] = $r_list['prevalue'];
							}
					$history = $array_history;
				}
			return $history; 

	}


// Pullout action plan text, for each users in one array
function arrange_actionplan_text($text){
	$rec_count = 0;
	$buffer = '';
	$text_array = array();

	for($c = 0; $c <=strlen($text); $c++){
			if ($text[$c] == "}"){
					$rec_count ++;
					$text_array[$rec_count] = $buffer;
					$buffer = '';
			} else $buffer = $buffer . $text[$c];
	}
	return $text_array;
}





	function arrange_fetch_details_vr($record,$assigned_users,$actionplan_assigned_users,$actionplan_text,$actionplan_deadline,$all_field_details,$event_status){
		$table = ''; 
		global $adb;
		
							// Arranging event details in table  
		
		/*$n = count($assigned_users);
		if ($n > 0){
			$table .= "<tr><td colspan='2'> <b>Assigned Person(s) </b> </td> </tr>";
			for($i = 1; $i <= $n; $i++){
				$user = $assigned_users[$i];
				$table .= "<tr><td colspan=2> $user </td></tr>"; 
			}
		}	*/
	 
		
		$n_1 = count($actionplan_assigned_users);
		$n_2 = count($actionplan_text);
		$n_3 = count($actionplan_deadline);
		$max = 0;
		if ($n_1 > $n_2) $max = $n_1; else $max = $n_2;
		if ($max > $n_3) $max = $max; else $max = $n_3;
		 
		if ($max > 0){
			$table .= "<tr><td colspan='2'> <b>Action Plan </b> </td> </tr> ";
			for($i = 1; $i <= $max; $i++){
				$user = $actionplan_assigned_users[$i];
			$text = $actionplan_text[$i];
			$deadline = $actionplan_deadline[$i];
		 
			$data = '<b>'.$user .'</b> - ' . $text . ' - ' . $deadline.'<br/>';   	
				$table .= "<tr><td colspan=2>  $data </td></tr>"; 
			}
		}
		


						// Gathering Event history details	
		$n = count($all_field_details);
		if ($n > 0){
			if ($event_status == 1) 
			$lbl = "Event"; 
		else 
			$lbl = "Updated";
			$table .= "<tr><td colspan='2'> <b>$lbl details </b> </td></tr>";	
			for($i = 1; $i <= $n; $i++){
			$label = ucfirst($all_field_details[$i][1]);
			$value = $all_field_details[$i][2];
			$prevalue = $all_field_details[$i][3];
			
			
			if ($label == 'Parent_id'){				
				$sql_account = $adb->pquery("SELECT * From `vtiger_account` where `accountid` = ".$value);
				$r_account = $adb->fetch_array($sql_account);
				$value = $r_account['accountname'];
				$label = 'Related to';
			}  
	
			if ($label == 'Cf_6566') $label = "Customer business";
			if ($label == 'Cf_6568') $label = "Head Office / Decition Maker for CIS";
			if ($label == 'Cf_6570') $label = "Customer Logistics Requirements";
			if ($label == 'Cf_6590') $label = "Tender Platform";
			if ($label == 'Cf_6592') $label = "Preferred Logistics Partner";
			if ($label == 'Cf_6594') $label = "Existing Cooperation / Contact History with GLK";
			if ($label == 'Cf_6596') $label = "Future Business Development Potential with GLK";
			if ($label == 'Cf_6598') $label = "Last Updated by/on ";
			if ($label == 'Cf_6600') $label = "Comments";
			if ($label == 'Cf_4797') $label = "Type of task";
	
			if ($label == 'Contact_id'){
				//$sql_contactdetails = mysql_query("SELECT * From `vtiger_contactdetails` where `contactid` = $value");
			//$row = mysql_fetch_array($sql_contactdetails);
					//$value = $row['salutation']. ' ' . $row['firstname'] . ' ' . $row['lastname'];
			$label = 'Contact Name';
			$value = '';
			
/* 				$sql_contactdetails = mysql_query("SELECT vtiger_contactdetails.*
												FROM `vtiger_cntactivityrel` 
												LEFT JOIN vtiger_contactdetails ON vtiger_contactdetails.contactid = vtiger_cntactivityrel.contactid
												WHERE vtiger_cntactivityrel.`activityid` = $record");
				While ($row = mysql_fetch_array($sql_contactdetails)){
						$value .= $row['salutation']. ' ' . $row['firstname'] . ' ' . $row['lastname'].', ';
						
				} */


				
				$result =  $adb->pquery("SELECT vtiger_contactdetails.*
				FROM `vtiger_cntactivityrel` 
				LEFT JOIN vtiger_contactdetails ON vtiger_contactdetails.contactid = vtiger_cntactivityrel.contactid
				WHERE vtiger_cntactivityrel.`activityid` = $record");
				$numRows = $adb->num_rows($result);
				for($j=0; $j < $numRows;$j++){
					$value .= $adb->query_result($result,$j,"salutation").' ';
					$value .= $adb->query_result($result,$j,"firstname").' ';
					$value .= $adb->query_result($result,$j,"lastname").', ';
				}

	
			}
	
			if ($label == 'Modifiedby'){
				if ($event_status == 1) {
					$label = "Created by"; 
					$sql_user = $adb->pquery("SELECT * FROM `vtiger_users` where `id` = ".$value);
					$r_user = $adb->fetch_array($sql_user);
					$value = $r_user['first_name'].' '.$r_user['last_name'];
				
				} else 
				 $label = "Updated by";
					// $login = get_user_details($value,3);
			 		// $value = arrange_user_format($login,1);
			 }
			
	 
			
			if ($label == 'Description'){
					//$value = str_replace("\n","<br>",$value);
					//$value = hightlight_updated_description($prevalue,$value);
			}
			if ($label == 'Updates are provided to ') {
				//$value = get_user_name_by_id($value);
			}
	 
				$table .= "<tr> <td> $label </td> <td> $value </td></tr>"; 
			}
		}
	
		return $table;
	}




	function detect_message_status_vr($record){
		global $adb;
    $s_modtracker_basic = $adb->pquery("SELECT * FROM `vtiger_modtracker_basic` where `crmid` = $record");
		$count = $adb->num_rows($s_modtracker_basic);
    return $count;
	}




	function detect_event_type($record,$module){
		global $adb;

    if ($module == 'Calendar'){
				
					$s_activity = $adb->pquery("SELECT * FROM `vtiger_activity` where `activityid`= $record");
					$r_activity = $adb->fetch_array($s_activity);

        if ($r_activity['eventstatus'] == 'Planned'){
            // IF Planned Event
            if ($r_activity['activitytype'] == 'Meeting') $value = 'meeting'; else
                if ($r_activity['activitytype'] == 'Call') $value = 'call';
        } else if (($r_activity['eventstatus'] == 'Held') || ($r_activity['eventstatus'] == 'Postponed')){
            // IF VR or CR
            if ($r_activity['activitytype'] == 'Meeting') $value = 'VR'; else
                if ($r_activity['activitytype'] == 'Call') $value = 'CR';
        }
    }
    return $value;
	}




	function arrange_people_cc_vr($cc){
    $n = count($cc);
    if ($n > 0){
        for($i = 0; $i <= $n; $i++) $value .= $cc[$i];
    }
    return $value;
 }



	function arrange_muptiple_users_vr($users,$format){

		// $adb = PearDatabase::getInstance();
		global $adb;

		$person_array = array();
		$buffer = '';
		$n = 0;

		// Search count of person
		for($i = 0; $i <= strlen($users); $i++){
				if ($users[$i] == '|'){
						$n ++;
						$buffer = trim($buffer);
						$buffer = str_replace('|', '', $buffer);
						$buffer = str_replace('}', '', $buffer);
						// echo 'user = ' . $buffer.'<br>';

						$sql_user = $adb->pquery("SELECT * FROM `vtiger_users` where `user_name` = '$buffer' ");
						$r_user = $adb->fetch_array($sql_user);
						if ($format == 1){
								// echo $r_user['user_name'].'<br>';
								$person_array[$n] = $this->arrange_user_format_vr($r_user['user_name'], 1);

						}
						else
								if ($format == 2){
										$person_array[$n] = $r_user['user_name'];
								}
								else
										if ($format == 3){
												$person_array[$n] = $r_user['email1'].',';
										}
						$buffer = '';
				} else $buffer = $buffer . $users[$i];
		}
		//print_r($person_array);
	  // exit;
		return $person_array;
}


// Mentioning full user format:  first name, last name, Department;
function arrange_user_format_vr($users,$mode){
	global $adb;
	// Вывод данных пользователей
	$user_login = trim($users);

	$res_users = $adb->pquery("SELECT * FROM `vtiger_users` where `user_name` = '$user_login' ");
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
            //Due to dependencies on the activity_reminder api in Activity.php(5.x)
            $_REQUEST['mode'] = 'edit';
		} else {
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$modelData = $recordModel->getData();
			$recordModel->set('mode', '');
		}

		$fieldModelList = $moduleModel->getFields();
		foreach ($fieldModelList as $fieldName => $fieldModel) {
			$fieldValue = $request->get($fieldName, null);
            // For custom time fields in Calendar, it was not converting to db insert format(sending as 10:00 AM/PM)
            $fieldDataType = $fieldModel->getFieldDataType();
            if($fieldValue){
                $fieldValue = Vtiger_Util_Helper::validateFieldValue($fieldValue,$fieldModel);
            }
			if($fieldDataType == 'time' && $fieldValue !== null){
				$fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
            }
            // End
            if ($fieldName === $request->get('field')) {
				$fieldValue = $request->get('value');
			}

			if($fieldValue !== null) {
				if(!is_array($fieldValue)) {
					$fieldValue = trim($fieldValue);
				}
				$recordModel->set($fieldName, $fieldValue);
			}
		}

		//Start Date and Time values
        if($request->get('date_start') && $request->get('time_start')) {
            $startTime = Vtiger_Time_UIType::getTimeValueWithSeconds($request->get('time_start'));
            $startDateTime = Vtiger_Datetime_UIType::getDBDateTimeValue($request->get('date_start')." ".$startTime);
            list($startDate, $startTime) = explode(' ', $startDateTime);

            $recordModel->set('date_start', $startDate);
            $recordModel->set('time_start', $startTime);
        }

		//End Date and Time values
        if($request->get('due_date')) {
            $endTime = $request->get('time_end');
            $endDate = Vtiger_Date_UIType::getDBInsertedValue($request->get('due_date'));

            if ($endTime) {
                $endTime = Vtiger_Time_UIType::getTimeValueWithSeconds($endTime);
                $endDateTime = Vtiger_Datetime_UIType::getDBDateTimeValue($request->get('due_date')." ".$endTime);
                list($endDate, $endTime) = explode(' ', $endDateTime);
            }

            $recordModel->set('time_end', $endTime);
            $recordModel->set('due_date', $endDate);
        }

		$activityType = $request->get('activitytype');
		if(empty($activityType)) {
			$recordModel->set('activitytype', 'Task');
			$recordModel->set('visibility', 'Private');
		}

		//Due to dependencies on the older code
		$setReminder = $request->get('set_reminder');
		if($setReminder) {
			$_REQUEST['set_reminder'] = 'Yes';
		} else {
			$_REQUEST['set_reminder'] = 'No';
		}

		return $recordModel;
	}
}
