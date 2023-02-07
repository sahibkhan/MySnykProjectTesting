<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
// ini_set('display_errors','on'); version_compare(PHP_VERSION, '5.5.0') <= 0 ? error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED) : error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);   // DEBUGGING

include_once 'modules/Vtiger/CRMEntity.php';

class MOTIW extends Vtiger_CRMEntity {
	var $table_name = 'vtiger_motiw';
	var $table_index= 'motiwid';

	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('vtiger_motiwcf', 'motiwid');

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array('vtiger_crmentity', 'vtiger_motiw', 'vtiger_motiwcf');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_motiw' => 'motiwid',
		'vtiger_motiwcf'=>'motiwid');

	/**
	 * Mandatory for Listing (Related listview)
	 */
	var $list_fields = Array (
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'Name' => Array('motiw', 'name'),
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
		'Name' => Array('motiw', 'name'),
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

	function save_module($module)
	{
		$this->insertIntoAttachment($this->id,'MOTIW');
	}
	
	function insertIntoAttachment($id,$module)
	{
		global $log, $adb;
		$log->debug("Entering into insertIntoAttachment($id,$module) method.");
		$file_saved = false;
 		

/* 		echo "<pre>"; 
		print_r($_FILES); 
		print_r($_REQUEST);
		exit;		  */
		
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


	public function sendEmailNotification($details){
		$contractRefNo = $details['contractRefNo'];

		if ($contractRefNo == '-'){
			$URL = $_SERVER['SERVER_NAME'];
			$fromName = $details['fromName'];
			$fromEmail = $details['fromEmail'];
			$recordId = $details['recordId'];
			$creatorHeadEmail = $details['creatorHeadEmail'];
			$creatorHeadName = $details['creatorHeadName'];
			$agentName = strip_tags($details['agentName']);

			$from = "r.gusseinov@globalinklogistics.com";
			$to = "r.gusseinov@globalinklogistics.com";

			$subject = "New Agency(Bilateral Partnership) Contract: $agentName";
			$body .= "<html><head> <style> #tableBody tr td{ margin:3px; } </style> </head><body><table id='tableBody'> ";

			$body .= "<tr><td colspan=2> Dear $creatorHeadName </td></tr>";		
			$body .= "<tr><td colspan=2> Please be informed that agent (bilateral partnership) has been created for $agentName </td></tr>";
	
			$link .= "https://gems.globalink.net/index.php?module=MOTIW&view=Detail&record=$recordId";
			$body .= "<tr><td colspan=2>Link: <a href='$link'> Link to GEMS </a></td></tr>";
			$body .= "</table> </body> </html> ";
			
			// Set content-type when sending HTML email
			$headers = "MIME-Version: 1.0" . "\n";
			$headers .= "Content-type:text/html;charset=UTF-8" . "\n";

			$headers .= $from . "\n";
			$headers .= 'Reply-To: '.$to.'' . "\n";

			require_once("modules/Emails/mail.php");
			// $r = send_mail('MOTIW', $to, $fromName, $from, $subject, $body, $cc,'','','','',true);

		}
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