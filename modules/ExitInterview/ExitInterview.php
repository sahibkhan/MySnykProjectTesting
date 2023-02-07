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

class ExitInterview extends Vtiger_CRMEntity {
	var $table_name = 'vtiger_exitinterview';
	var $table_index= 'exitinterviewid';

	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('vtiger_exitinterviewcf', 'exitinterviewid');

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array('vtiger_crmentity', 'vtiger_exitinterview', 'vtiger_exitinterviewcf');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_exitinterview' => 'exitinterviewid',
		'vtiger_exitinterviewcf'=>'exitinterviewid');

	/**
	 * Mandatory for Listing (Related listview)
	 */
	var $list_fields = Array (
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'Name' => Array('exitinterview', 'name'),
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
		'Name' => Array('exitinterview', 'name'),
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
		global $adb;
		$this->generateRefNo();
		$recordId = $this->id;
		// $moduleName = $this->moduleName;
		$recordExitInterview = Vtiger_Record_Model::getInstanceById($recordId, 'ExitInterview');
		$requestedById = $recordExitInterview->get('name');
		$refNo = $recordExitInterview->get('cf_7494');

		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$currentUserEmail = $currentUserModel->get('email1');
				
		
/* 		$sqlMod =  "SELECT status
		FROM `vtiger_modtracker_basic`
		WHERE `crmid` = ? AND module = ?";
		$resultMod = $adb->pquery($sqlMod, array($recordId, $moduleName));
		$nOfRecords = $adb->num_rows($resultMod);
		$status = $adb->query_result($resultMod, 0, 'status');

		if ($nOfRecords == 0 || ($nOfRecords == 1 && $status == 2)){ */

			// Fetch request user email		
			$queryUser = $adb->pquery("
									SELECT vtiger_userlistcf.cf_3355, vtiger_userlist.name
									FROM vtiger_userlistcf
									INNER JOIN vtiger_userlist ON vtiger_userlist.userlistid = vtiger_userlistcf.userlistid
									WHERE vtiger_userlistcf.userlistid = ?",array($requestedById));
			$recordCreatorEmail = trim($adb->query_result($queryUser, 0, 'cf_3355'));
			$currentEmployeeName = trim($adb->query_result($queryUser, 0, 'name'));
			
		
		// Fetch current user's head
		$queryHead = $adb->pquery("SELECT vtiger_userlistcf.cf_3355, vtiger_userlist.name
															FROM vtiger_userlistcf
															INNER JOIN vtiger_userlist ON vtiger_userlist.userlistid = vtiger_userlistcf.userlistid
															WHERE vtiger_userlistcf.userlistid IN (
																SELECT cf_3385
																FROM vtiger_userlistcf
																WHERE userlistid = ?)", array($requestedById));
		$userHeadEmail = $adb->query_result($queryHead, 0, 'cf_3355');


			// Fetch HR Manager
			$queryHRManager = $adb->pquery("SELECT vtiger_userlistcf.cf_3355, vtiger_userlist.name
																			FROM `vtiger_userlistcf` 
																			INNER JOIN vtiger_userlist ON vtiger_userlist.userlistid = vtiger_userlistcf.userlistid
																			WHERE vtiger_userlistcf.cf_3385 = 412373 AND vtiger_userlistcf.cf_3421 = 85805 
																			AND vtiger_userlistcf.cf_3353 = 85757 AND vtiger_userlistcf.cf_3349 = 414370 
																			AND vtiger_userlistcf.cf_6206 = 'Active'");
			$HRMangerEmail = $adb->query_result($queryHRManager, 0, 'cf_3355');
			
			// Add in CC
			$cc[] = $userHeadEmail;
						
			//Gathering email info
			$details = array();
			$details['name'] = $currentEmployeeName;
			$details['fromEmail'] = $currentUserEmail;
			$details['to'] = $HRMangerEmail;
			$details['cc'] = $cc;
			$details['recordId'] = $recordId;
			$details['refNo'] = $refNo;
			$details['requestType'] = 1;
			$approvalHistory = [];
			$this->sendApproveEmail($details); 

	}

	function generateRefNo(){
		global $adb;
		// GENERATE Ref. no for agreement	
		// Creator location code
		$recordId = $this->id;
		$recordExitInterview = Vtiger_Record_Model::getInstanceById($recordId, 'ExitInterview');
		$location_id = $recordExitInterview->get('cf_7780');
		$currentRefNo = $recordExitInterview->get('cf_7494');

		$recordLocation = Vtiger_Record_Model::getInstanceById($location_id, 'Location');
		$locationCode = $recordLocation->get('cf_1559');

		// Number of records relevant to current location and department
		$sql_m =  "SELECT vtiger_crmentity.crmid
			FROM vtiger_exitinterviewcf
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_exitinterviewcf.exitinterviewid
			WHERE vtiger_crmentity.deleted = 0 AND vtiger_exitinterviewcf.cf_7780 = ?";
	
		$result_m = $adb->pquery($sql_m, array($location_id));
		$nOfRecords = $adb->num_rows($result_m);
		$refNo = trim(sprintf("%'.05d\n", $nOfRecords + 1));
		$subject = $locationCode.'-'.$refNo.'/'.Date('y');

		// echo 'subject = ' . strlen($currentRefNo); exit;
		// Update ref. no
		if (strlen($currentRefNo) == 0){
			$adb->pquery("UPDATE `vtiger_exitinterviewcf` SET `cf_7494` = '$subject' WHERE exitinterviewid = ? LIMIT 1", array($recordId));
		}

}



public function sendApproveEmail($details){

	$userName = $details['name'];
	$recordId = $details['recordId'];
	$refNo = $details['refNo'];
	$requestType = $details['requestType'];
	

	$link = $_SERVER['SERVER_NAME'];
	$link .= "/index.php?module=ExitInterview&view=Detail&record=".$recordId;
	$date_time = date('Y-m-d H:i:s');
	$from = trim($details['fromEmail']);
	$to = trim($details['to']);
	$cc = implode(',', $details['cc']);

	$body = '';
	if ($requestType == 1){
		$message_status = "Please note that exit interview form for $userName has been created/updated. <br> Please follow below link: $refNo";
	}

	$subject = 'Exit Interview '.$refNo;
	$body .= "<html><head> <style> #tableBody tr td{ margin:3px; } </style> </head>
						<body><table id='tableBody'> ";
	$body .= "<tr><td colspan=2> $message_status </td></tr>

						<tr><td colspan=2>Link: <a href='$link'> Link to GEMS </a></td></tr>";
	$body .= "</table> </body> </html> ";
																
	// Set content-type when sending HTML email
	$headers = "MIME-Version: 1.0" . "\n";
	$headers .= "Content-type:text/html;charset=UTF-8" . "\n";

	$headers .= $from . "\n";
	$headers .= 'Reply-To: '.$to.'' . "\n";

	require_once("modules/Emails/mail.php");
	$r = send_mail('ExitInterview', $to, $from, $from, $subject, $body, $cc,'','','','',true);
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