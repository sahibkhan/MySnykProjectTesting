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

class ExternalTraining extends Vtiger_CRMEntity {
	var $table_name = 'vtiger_externaltraining';
	var $table_index= 'externaltrainingid';

	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('vtiger_externaltrainingcf', 'externaltrainingid');

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array('vtiger_crmentity', 'vtiger_externaltraining', 'vtiger_externaltrainingcf');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_externaltraining' => 'externaltrainingid',
		'vtiger_externaltrainingcf'=>'externaltrainingid');

	/**
	 * Mandatory for Listing (Related listview)
	 */
	var $list_fields = Array (
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'Name' => Array('externaltraining', 'name'),
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
		'Name' => Array('externaltraining', 'name'),
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

	function save_module(){
		$this->insertIntoAttachment($this->id,'ExternalTraining');

		$selected_users_string =  $_REQUEST['selectedusers'];
		$this->insertIntoInviteeTableET($module,$selected_users_string);

		// Generate Ref.No
		$this->generateRefNo();

		// Email notification
		$recordModel = Vtiger_Record_Model::getInstanceById($this->id, 'ExternalTraining');

 		if ($recordModel->get('cf_7368') == 'Internal'){
			$this->sendInivitsToTrainees();
		}

	}


	function sendInivitsToTrainees(){
/* 
		SELECT vtiger_users.email1
		FROM vtiger_users
		INNER JOIN vtiger_invitees ON vtiger_invitees.inviteeid = vtiger_users.id
		WHERE vtiger_invitees.activityid = 3977687 */
		global $adb;
		$recordId = $this->id;
		$recordId = $this->id;
		$recordModel = Vtiger_Record_Model::getInstanceById($recordId, 'ExternalTraining');
 		$trainingName = $recordModel->get('name');


		$getUserEmail_query = $adb->pquery("SELECT vtiger_users.email1, vtiger_users.first_name, vtiger_users.last_name
		FROM vtiger_users
		INNER JOIN vtiger_invitees ON vtiger_invitees.inviteeid = vtiger_users.id
		WHERE vtiger_invitees.activityid = ?", array($recordId));
		

		$rows = $adb->num_rows($getUserEmail_query);
		for($i=0; $i<$rows; $i++) {
			$toName = $adb->query_result($getUserEmail_query, $i, 'first_name').' '.$adb->query_result($getUserEmail_query, $i, 'last_name');
			$toEmail = $adb->query_result($getUserEmail_query, $i, 'email1');

			$link = "https://gems.globalink.net/index.php?module=ExternalTraining&view=Detail&record=".$recordId;
			$to = $toEmail;
			$from = "hr@globalinklogistics.com";
			$subject = "Training Inivation: ".$trainingName;
			$cc = '';
			$body = '';
			$body .= "<html><head> <style> #calendar_notification tr td{ margin:3px; } </style> </head>
								<body><table id='calendar_notification'> ";
			$body .= "<tr><td colspan=2>Dear <b>".$toName.",</b> </td></tr>";
			$body .= "<tr><td colspan=2> ".$message_status." </td></tr>";

			

			$body .= "<tr><td>You have been invited to attend a training for <strong>".$trainingName."</strong>";
			$body .= " at ".$recordModel->get('cf_7356').".</td></tr>";
			$body .= "<tr><td>This training will start from ".$recordModel->get('cf_7364')." to ".$recordModel->get('cf_7366'). " and timings are ".$recordModel->get('cf_7400');

			$body .= "<tr><td colspan=2>Link: <a href='$link'> Link to GEMS </a></td></tr>";
			$body .= "</table> </body> </html>";
										
			// Set content-type when sending HTML email
			$headers = "MIME-Version: 1.0" . "\n";
			$headers .= "Content-type:text/html;charset=UTF-8" . "\n";
		
			$headers .= $from . "\n";
			$headers .= 'Reply-To: '.$to.'' . "\n";
			$headers .= "CC:" . $cc . "\n";

			require_once("modules/Emails/mail.php");
			$r = send_mail('ExternalTraining', $to, $from, $from, $subject, $body, $cc ,'','','','',true);
			
		}
	}



	function generateRefNo(){
			global $adb;
			// GENERATE Ref. no for agreement	
			// Creator location code
			$recordId = $this->id;
			$recordIA = Vtiger_Record_Model::getInstanceById($recordId, 'ExternalTraining');
			$location_id = $recordIA->get('cf_7356');
			$currentRefNo = $recordIA->get('cf_7354');

			$recordLocation = Vtiger_Record_Model::getInstanceById($location_id, 'Location');
			$locationCode = $recordLocation->get('cf_1559');

			// Number of records relevant to current location and department
			$sql_m =  "SELECT vtiger_crmentity.crmid
				FROM vtiger_externaltrainingcf
				INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_externaltrainingcf.externaltrainingid
				WHERE vtiger_crmentity.deleted = 0 AND vtiger_externaltrainingcf.cf_7356 = ?";
		
			$result_m = $adb->pquery($sql_m, array($location_id));
			$nOfRecords = $adb->num_rows($result_m);
			$refNo = trim(sprintf("%'.05d\n", $nOfRecords + 1));		
			$subject = $locationCode.'-'.$refNo.'/'.Date('y');

			// Update ref. no
			if (strlen($currentRefNo) == 0){
				$adb->pquery("UPDATE `vtiger_externaltrainingcf` SET `cf_7354` = '$subject' WHERE `externaltrainingid` = ? LIMIT 1", array($recordId));
			}

			// return $subject;
	}



	/** Function to insert values in vtiger_invitees table for the specified module,tablename ,invitees_array
  	  * @param $table_name -- table name:: Type varchar
  	  * @param $module -- module:: Type varchar
	  * @param $invitees_array Array
 	 */
		function insertIntoInviteeTableET($module,$invitees_array)
		{
			global $log,$adb;
			$log->debug("Entering insertIntoInviteeTableET(".$module.",".$invitees_array.") method ...");
			if($this->mode == 'edit'){
				$sql = "DELETE FROM vtiger_invitees WHERE activityid=?";
				$adb->pquery($sql, array($this->id));
			}
			foreach($invitees_array as $inviteeid)
			{
				if($inviteeid != '')
				{
					$query="INSERT INTO vtiger_invitees VALUES (?,?,?)";
					$adb->pquery($query, array($this->id, $inviteeid, 'sent'));
				}
			}
			$log->debug("Exiting insertIntoInviteeTableET method ...");
	
		}


	function insertIntoAttachment($id,$module){
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