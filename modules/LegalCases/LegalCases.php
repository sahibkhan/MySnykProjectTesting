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

class LegalCases extends Vtiger_CRMEntity {
	var $table_name = 'vtiger_legalcases';
	var $table_index= 'legalcasesid';

	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('vtiger_legalcasescf', 'legalcasesid');

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array('vtiger_crmentity', 'vtiger_legalcases', 'vtiger_legalcasescf');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_legalcases' => 'legalcasesid',
		'vtiger_legalcasescf'=>'legalcasesid');

	/**
	 * Mandatory for Listing (Related listview)
	 */
	var $list_fields = Array (
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'Name' => Array('legalcases', 'name'),
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
		'Name' => Array('legalcases', 'name'),
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
	  $recordId = $this->id;
	  
	  $selected_users_string = $_REQUEST['selectedusers'];
	  $this->insertIntoInviteeTableLegalCases($module,$selected_users_string);
	  
	}
	
	/** Function to insert values in vtiger_invitees table for the specified module,tablename ,invitees_array
  	  * @param $table_name -- table name:: Type varchar
  	  * @param $module -- module:: Type varchar
	  * @param $invitees_array Array
 	 */
	function insertIntoInviteeTableLegalCases($module,$invitees_array){
		global $log,$adb;
		$log->debug("Entering insertIntoInviteeTableLegalCases(".$module.",".$invitees_array.") method ...");
		if($this->mode == 'edit'){
			
			 $dresult = $adb->pquery("SELECT * FROM vtiger_invitees WHERE activityid=?",array($this->id));
			 $numRows = $this->db->num_rows($dresult);
				
				for($i=0; $i < $numRows;$i++){
					$cuser_id = $this->db->query_result($dresult,$i,"inviteeid");
					if (!in_array($cuser_id,$invitees_array)){
						//echo  "just removed = " . $cuser_id.'<br>';						
						$sql = "delete from vtiger_invitees where activityid=? and inviteeid=?";
						$adb->pquery($sql, array($this->id,$cuser_id));						
					}				
					
				}
		}

		
		// updated user list	
		foreach($invitees_array as $inviteeid){
			if($inviteeid != ''){
			  // If new user selected, then we are adding him to database	
			  $res = $adb->pquery("SELECT * FROM vtiger_invitees WHERE activityid=? AND inviteeid=?",array($this->id, $inviteeid));
			  $numRows = $this->db->num_rows($res);			  

			  if ($numRows == 0){
				$query="insert into vtiger_invitees values(?,?)";
				$adb->pquery($query, array($this->id, $inviteeid));  
			  }			
			}		
		}	
		
		$log->debug("Exiting insertIntoInviteeTableLegalCases method ...");
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