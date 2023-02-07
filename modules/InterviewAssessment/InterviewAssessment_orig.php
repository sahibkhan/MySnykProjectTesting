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

class InterviewAssessment extends Vtiger_CRMEntity {
	var $table_name = 'vtiger_interviewassessment';
	var $table_index= 'interviewassessmentid';

	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('vtiger_interviewassessmentcf', 'interviewassessmentid');

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array('vtiger_crmentity', 'vtiger_interviewassessment', 'vtiger_interviewassessmentcf');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_interviewassessment' => 'interviewassessmentid',
		'vtiger_interviewassessmentcf'=>'interviewassessmentid');

	/**
	 * Mandatory for Listing (Related listview)
	 */
	var $list_fields = Array (
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'Name' => Array('interviewassessment', 'name'),
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
		'Name' => Array('interviewassessment', 'name'),
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
		$this->insertIntoAttachment($this->id,'InterviewAssessment');
		$this->generateRefNo();
		//Handling for invitees
		$selected_users_string =  $_REQUEST['selectedusers'];
		// $invitees_array = explode(';', $selected_users_string);

/* 		echo "<pre>";
		print_r($selected_users_string);
		exit; */


		$this->insertIntoInviteeTableIR($module,$selected_users_string);
	}

	function generateRefNo(){
			global $adb;
			// GENERATE Ref. no for agreement	
			// Creator location code
			$recordId = $this->id;
			$recordIA = Vtiger_Record_Model::getInstanceById($recordId, 'InterviewAssessment');
			$location_id = $recordIA->get('cf_7182');
			$currentRefNo = $recordIA->get('cf_7184');

			$recordLocation = Vtiger_Record_Model::getInstanceById($location_id, 'Location');
			$locationCode = $recordLocation->get('cf_1559');

			// Number of records relevant to current location and department
			$sql_m =  "SELECT vtiger_crmentity.crmid
				FROM vtiger_interviewassessmentcf
				INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_interviewassessmentcf.interviewassessmentid
				WHERE vtiger_crmentity.deleted = 0 AND vtiger_interviewassessmentcf.cf_7182 = ?";
		
			$result_m = $adb->pquery($sql_m, array($location_id));
			$nOfRecords = $adb->num_rows($result_m);
			$refNo = trim(sprintf("%'.05d\n", $nOfRecords + 1));		
			$subject = $locationCode.'-'.$refNo.'/'.Date('y');

			// Update ref. no
			if (strlen($currentRefNo) == 0){
				$adb->pquery("UPDATE `vtiger_interviewassessmentcf` SET `cf_7184` = '$subject' WHERE `interviewassessmentid` = ? LIMIT 1", array($recordId));
			}

			return $subject;
	}


	/** Function to insert values in vtiger_invitees table for the specified module,tablename ,invitees_array
  	  * @param $table_name -- table name:: Type varchar
  	  * @param $module -- module:: Type varchar
	  * @param $invitees_array Array
 	 */
		function insertIntoInviteeTableIR($module,$invitees_array)
		{
			global $log,$adb;
			$log->debug("Entering insertIntoInviteeTableIR(".$module.",".$invitees_array.") method ...");
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
			$log->debug("Exiting insertIntoInviteeTableIR method ...");
	
		}


	function insertIntoAttachment($id,$module){
		global $log, $adb;

/* 		$s_mod = $adb->pquery("SELECT max(id) as maxid  FROM `vtiger_modtracker_basic` WHERE `crmid` = '".$record."'");
		$r_mod = $adb->fetch_array($s_mod);
		$maxid = $r_mod['maxid']; */

		$recordId = $this->id;
		// $recordModel = Vtiger_Record_model::getInstanceById($recordId, $module);
/* 		$sql = $adb->pquery("SELECT `cf_7184` FROM `vtiger_interviewassessmentcf` WHERE `interviewassessmentid`= ? LIMIT 1", array($recordId));
		$numRows = $adb->num_rows($sql);
		$r_data = $adb->query_result($sql, 0, 'cf_7184');

 		if ($numRows > 0){
			if (trim($r_data) == '') {
				
				$sUser = $adb->pquery("SELECT `smownerid` FROM `vtiger_crmentity` WHERE `crmid`= ? LIMIT 1", array($recordId));
				$rUser = $adb->query_result($sUser, 0, 'smownerid');
				$userid = $rUser['smownerid'];

				$this->saveUniqueID('vtiger_interviewreportcf','cf_7184','interviewreportid',$recordId,$userid);
			}
		}
 */
		
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

/*  	public function saveUniqueID($module,$column,$table_id,$id,$userid) {

		global $adb;

		$sUser = $adb->pquery("SELECT `loc`.`cf_1559` FROM `vtiger_users` AS `user` 
												INNER JOIN `vtiger_locationcf` AS `loc` ON `loc`.`locationid`=`user`.`location_id` 
												WHERE `user`.`id` = ? LIMIT 1", array($userid));
		$arr0 = $adb->query_result($sUser, 0, 'cf_1559'); //location ALA 


		$date = date('y');
		$seq_no  = $arr0.'%/'.$date;

		$sUser = $adb->pquery("SELECT `$column` FROM `$module` WHERE `$column` LIKE '$seq_no' ORDER BY `$column` DESC LIMIT 1");
		$rUser = $adb->query_result($sUser, 0, $column);

		if ($arr != '') {
			$arr = explode('-',$arr);
			$arr1 = explode('/',$arr[1]);
			if ($arr1[1] == $date) {
				$num = (int)$arr1[0];
				$num++;
				$res = $arr0.'-'.sprintf("%05s", $num).'/'.$date;
			} else $res = $arr0.'-00001/'.$date;
		} else $res = $arr0.'-00001/'.$date;
		// mysql_query("UPDATE `$module` SET `$column`='".$res."' WHERE `$table_id`='".$id."' LIMIT 1");
	} */
 

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