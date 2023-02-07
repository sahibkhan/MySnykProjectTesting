<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

include_once 'modules/Vtiger/CRMEntity.php';


class LegalClaims extends Vtiger_CRMEntity {
	var $table_name = 'vtiger_legalclaims';
	var $table_index= 'legalclaimsid';

	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('vtiger_legalclaims', 'legalclaimsid');

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array('vtiger_crmentity', 'vtiger_legalclaims', 'vtiger_legalclaimscf');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_legalclaims' => 'legalclaimsid',
		'vtiger_legalclaimscf'=>'legalclaimsid');

	/**
	 * Mandatory for Listing (Related listview)
	 */
	var $list_fields = Array (
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'Name' => Array('legalclaims', 'name'),
		'Assigned To' => Array('crmentity','smownerid')
	);
	var $list_fields_name = Array (
		/* Format: Field Label => fieldname */
		'Name' => 'name',
		'Assigned To' => 'assigned_user_id',
	);

	// Make the field link to detail view
	var $list_link_field = 'name';

	// For Popup listview and UI type support
	var $search_fields = Array(
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'Name' => Array('legalclaims', 'name'),
		'Assigned To' => Array('vtiger_crmentity','assigned_user_id'),
	);
	var $search_fields_name = Array (
		/* Format: Field Label => fieldname */
		'Name' => 'name',
		'Assigned To' => 'assigned_user_id',
	);

	// For Popup window record selection
	var $popup_fields = Array ('name');

	// For Alphabetical search
	var $def_basicsearch_col = 'name';

	// Column value to use on detail view record text display
	var $def_detailview_recname = 'name';

	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vtiger_field.fieldname values.
	var $mandatory_fields = Array('name','assigned_user_id');

	var $default_order_by = 'name';
	var $default_sort_order='ASC';

	function save_module($module){

		$selectedUsers = $_REQUEST['selectedusers'];
		// echo "<pre>"; print_r($module); exit;
		$this->insertIntoInviteeTable($module, $selectedUsers);

		$this->insertIntoAttachment($this->id, 'LegalClaims');		
	}

	function insertIntoInviteeTable($module, $invitees_array){
		global $log,$adb;
		$log->debug("Entering insertIntoInviteeTable(".$module.",".$invitees_array.") method ...");
		if($this->mode == 'edit'){
			
 			 $dresult = $adb->pquery("SELECT * FROM vtiger_invitees WHERE activityid=?",array($this->id));
			 $numRows = $this->db->num_rows($dresult);
				
				for($i=0; $i < $numRows;$i++){
					$cuser_id = $this->db->query_result($dresult,$i,"inviteeid");
					if (!in_array($cuser_id, $invitees_array)){
						$sql = "delete from vtiger_invitees where activityid=? and inviteeid=?";
						$adb->pquery($sql, array($this->id,$cuser_id));							
					}					
				}
		}

		// updated user list	
 		foreach($invitees_array as $inviteeid){
			 
			if($inviteeid != ''){
				
			  // If new user selected, then we are adding him to database	
			  $res = $adb->pquery("SELECT * FROM vtiger_invitees WHERE activityid = ? AND inviteeid = ?",array($this->id, $inviteeid));
			  $numRows = $this->db->num_rows($res);
				// echo 'numRows='.$numRows.'<br>';

			  if ($numRows == 0){
					$query="insert into vtiger_invitees values(?,?,?,?)";
					$adb->pquery($query, array($this->id, $inviteeid, 'sent', 'no'));  
				}
				
			}
		}
		// exit;
		$log->debug("Exiting insertIntoInviteeTable method ...");
	}
	
	function insertIntoAttachment($id,$module)
	{
		global $log, $adb;
		$log->debug("Entering into insertIntoAttachment($id,$module) method.");
		$file_saved = false;
 		
		
		foreach($_FILES as $fileindex => $files)
		{
			if($files['name'] != '' && $files['size'] > 0)
			{
			      if($_REQUEST[$fileindex.'_hidden'] != '')
				      $files['original_name'] = vtlib_purify($_REQUEST[$fileindex.'_hidden']);
			      else
				      $files['original_name'] = stripslashes($files['name']);
			    $files['original_name'] = str_replace('"','',$files['original_name']);
				$file_saved = $this->uploadAndSaveFile($id,$module,$files);				
			}
		}

		$log->debug("Exiting from insertIntoAttachment($id,$module) method.");
	}

	public function handleEmailNotification($record){
		$cc = ''; 
			
		$LegalClaimsModel = Vtiger_Record_Model::getInstanceById($record, 'LegalClaims');
		$assisgnedUserId = $LegalClaimsModel->get('assigned_user_id');
		$inviteeDetail = Vtiger_Record_Model::getInviteeDetails($assisgnedUserId);
		$creator_name = $inviteeDetail['nameWithLocationAndDepartment'];
		// $creatorEmail = $inviteeDetail['email'];

		$jobFileModel = Vtiger_Record_Model::getInstanceById($LegalClaimsModel->get('name'), 'Job');
		$subject = $jobFileModel->get('name');
		$jobFileRef = $jobFileModel->get('cf_1198');

		// echo 'subject = ' . $subject; exit;
		// getEventInvitee
		// require_once('getEventInvitee');
		// $cc = $this->arrange_invite_usersLeads($record);

		// Event Update Details	
		// $who_updated_id = $LegalClaimsModel->get('modifiedby');
		$updatedByInfo = Users_Record_Model::getInstanceById($assisgnedUserId, 'Users');		
		$who_updated_email = $updatedByInfo->get('email1');
		$who_updated_name = $updatedByInfo->get('first_name') . ' ' . $updatedByInfo->get('last_name');

	  $all_field_details = $this->getLegalClaimsHistory($record, '');
		// echo "<pre>"; print_r($all_field_details); exit;

		// $contractRefNo = $this->getLegalClaimsHistory($record, 'cf_8424');
	
									// #3   Gathering people to put in CC	
					
					// Gathering Assigned Person to Event
	  $creator_id = $assisgnedUserId;
		$creatorInfo = Users_Record_Model::getInstanceById($creator_id, 'Users');
		$cc .= $creatorInfo->get('email1').',';
						
		$event_status = parent::getUpdateCounts($record);
		if ($event_status == 1) $message_status = "Legal Claim form job file <b>$jobFileRef</b> has been created";
		if ($event_status > 1) $message_status = "Legal Claim form job file <b>$jobFileRef</b> has been updated"; 

		$arranged_details = $this->getLegalClaimsDetails($record,$assigned_users,$all_field_details,$event_status);	

		$this->prepareLegalClaimsEmailNotification($creator_email,$creator_name,$who_updated_email,$who_updated_name,$module,$record,$subject, $jobFileRef, $message_status,$arranged_details,$cc,$event_status);
		
	}

	public function getLegalClaimsHistory($record, $history_field){
		global $adb;
		//Excluding already mentioned fields
		$exclude_field = "'assigned_user_id', 'record_module', 'record_id', 'Label', 'name', 'Contract Currency', 'Claim Currency', 'cf_8452', 'cf_8454'";

		$s_modtracker_basic =  $adb->pquery("SELECT max(`id`) as `maxid` FROM `vtiger_modtracker_basic` where `crmid` = ".$record);
		$r_modtracker_basic =  $adb->fetch_array($s_modtracker_basic);  
		$max_id = $r_modtracker_basic['maxid'];
		$history = '';
		if (!$max_id) $max_id = 0;

		$vendor_value = '';
		$val = '';
		
		if ($history_field != ''){
			
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

								$queryField = $adb->pquery("SELECT fieldlabel FROM `vtiger_field` where `columnname` = '".strtolower($r_list['fieldname'])."'");
								$fieldDetails = $adb->fetch_array($queryField);

								$array_history[$i][3] = $fieldDetails['fieldlabel'];
								
						}
				$history = $array_history;
			}
		return $history; 
	}

	public function getLegalClaimsDetails($record,$assigned_users,$all_field_details,$event_status){
		$table = ''; 
		global $adb;
		
							// Arranging event details in table    
		$n = count($assigned_users);
		if ($n > 0){
			$table .= "<tr><td colspan='2'> <b>Assigned person(s) </b> </td> </tr>";
			for ($i = 1; $i <= $n; $i++){
				$user = $assigned_users[$i];
				$table .= "<tr><td colspan=2> $user </td></tr>"; 
			}
		}
	
						// Gathering Event history details	
		$n = count($all_field_details);
		if ($n > 0){
			if ($event_status == 1) $lbl = 'Legal Claim'; else $lbl = 'Updated';
			$table .= "<tr><td colspan='2'> <b> $lbl details </b> </td></tr>";	
			for($i = 1; $i <= $n; $i++){

					$label = ucfirst($all_field_details[$i][1]);
					$value = $all_field_details[$i][2];
					$fieldLabel = $all_field_details[$i][3];

					if ($fieldLabel == 'Contract Reference Number' && $value == 0) $value = "not selected";
					if ($fieldLabel == 'Contract Reference Number' && $value > 0) {
						$CMS = Vtiger_Record_Model::getInstanceById($value, 'MOTIW');
						$value = $CMS->get('name');
					}

					if ($fieldLabel == 'Claim From' && $value > 0) {
						$accountModel = Vtiger_Record_Model::getInstanceById($value, 'Accounts');
						$value = $accountModel->get('cf_2395');
					}

					if ($fieldLabel == 'Claim To' && $value > 0) {
						$accountModel = Vtiger_Record_Model::getInstanceById($value, 'Accounts');
						$value = $accountModel->get('cf_2395');
					}

					if ($fieldLabel == 'Contract Currency') {
						$queryField = $adb->pquery("SELECT currency_code FROM `vtiger_currency_info` where `id` = '".$value."'");
						$fieldDetails = $adb->fetch_array($queryField);
						$value = $fieldDetails['currency_code'];
					}

					if ($fieldLabel == 'Claim Currency') {
						$queryField = $adb->pquery("SELECT currency_code FROM `vtiger_currency_info` where `id` = '".$value."'");
						$fieldDetails = $adb->fetch_array($queryField);
						$value = $fieldDetails['currency_code'];
					}


					if ($label != 'Updates are provided to') {
						$table .= "<tr> <td> $fieldLabel </td> <td> $value </td></tr>";
					}
			
			}
		}
		return $table;
	}

 
	public function prepareLegalClaimsEmailNotification($creator_email,$creator_name,$who_updated_email,$who_updated_name,$module,$record,$subject, $jobFileRef, $message_status,$sum,$cc,$event_status){

		// $who_updated_email = "r.gusseinov@globalinklogistics.com";
		$link = "http://tiger.globalink.net/live-gems/index.php?module=LegalClaims&view=Detail&record=".$record;
		$to = $who_updated_email;

		$subject = "Legal Claim for job file " . $jobFileRef;

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
		//$body .= "b = $conflict_baku<br> e = $conflict_erevan";
		// $result = mail($to,$subject,$body,$headers);	


		require_once("modules/Emails/mail.php");
		$r = send_mail('LegalClaims', $to, $from, $from, $subject, $body, $cc ,'','','','',true);		
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


	/**
	* Invoked when special actions are performed on the module.
	* @param String Module name
	* @param String Event Type
	*/
	function vtlib_handler($moduleName, $eventType) {
		global $adb;
 		if($eventType == 'module.postinstall') {
			// TODO Handle actions after this module is installed.
		} else if($eventType == 'module.disabled') {
			// TODO Handle actions before this module is being uninstalled.
		} else if($eventType == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
		} else if($eventType == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} else if($eventType == 'module.postupdate') {
			// TODO Handle actions after this module is updated.
		}
 	}
}