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
//include_once 'modules/Vtiger/CRMEntity.php';
//include_once 'include/Vtiger/crm_data_arrange.php';
//require_once('include/NewEmail/newEmailNotification.php');
include_once 'modules/NewEmail/NewEmailHandler.php';

class NewEmail extends Vtiger_CRMEntity {
	var $table_name = 'vtiger_newemail';
	var $table_index= 'newemailid';

	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('vtiger_newemailcf', 'newemailid');

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array('vtiger_crmentity', 'vtiger_newemail', 'vtiger_newemailcf');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_newemail' => 'newemailid',
		'vtiger_newemailcf'=>'newemailid');

	/**
	 * Mandatory for Listing (Related listview)
	 */
	var $list_fields = Array (
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'Name' => Array('newemail', 'name'),
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
		'Name' => Array('newemail', 'name'),
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
        $recordModel = Vtiger_Record_model::getInstanceById($recordId, $module);
		
		$assigned_user_id = $recordModel->get('assigned_user_id');
		
		/*Commented :: Mehtab :: 12/27/20017:: Reason is we need current user data not creater data*/
		//$assigned_user_id :: Who created New Email Request
		$assigned_user_id_sql = $adb->pquery("SELECT * FROM vtiger_users WHERE id=".$assigned_user_id);
        $assigned_user_id_query = $adb->fetch_array($assigned_user_id_sql);
        $assigned_user_email = $assigned_user_id_query['email1']; 
		$assigned_user_name = $assigned_user_id_query['first_name'].' '.$assigned_user_id_query['last_name'];
		
		$current_user = Users_Record_Model::getCurrentUserModel(); 
		$current_user_email = $current_user->get('email1');
		$current_user_name = $current_user->get('first_name').' '.$current_user->get('last_name');
		$currentUserId = $current_user->getId();
		
		$gm_user_id = $recordModel->get('cf_4069');
		
		
		//UPDATE ID IN NAME FIELD		
		$adb->pquery("UPDATE vtiger_newemail SET name=? WHERE newemailid=?", array($recordId, $recordId));
		$adb->pquery("UPDATE vtiger_crmentity SET label=? WHERE crmid=?", array($recordId, $recordId));
				
		$email_sql = $adb->pquery("SELECT email1 FROM vtiger_users WHERE id=".$gm_user_id);
		$email_query = $adb->fetch_array($email_sql);
		$gm_user_email = $email_query['email1'];
		
		//$gm_user_email = 'tvixa40@gmail.com';		
		$ceo_user_email = '';		
		//$ceo_user_email = 's.mehtab@globalinklogistics.com';		
		$it_user_email = 'it@globalinklogistics.com';
		$hr_user_email = 'hr@globalinklogistics.com';
		
		$ceo_signature = $recordModel->get('cf_4079');
		$ceo_signature_date = $recordModel->get('cf_4081');
        $ceo_signature_date = (empty($ceo_signature_date) ? ' ' :' ( '.date('Y-M-d',strtotime($ceo_signature_date)).' ) ');

        $gm_signature = $recordModel->get('cf_4083');
		$gm_signature_date = $recordModel->get('cf_4085');
        $gm_signature_date = (empty($gm_signature_date) ? ' ' :' ( '.date('Y-M-d',strtotime($gm_signature_date)).' ) '); 
		
		if($currentUserId == $gm_user_id){
			
			$gm_approval_query = "UPDATE vtiger_newemailcf SET cf_4083=?, cf_4085=? WHERE newemailid=?";
			$gm_approval_params = array($current_user_name, date('Y-m-d'), $recordId);
			$adb->pquery($gm_approval_query, $gm_approval_params);
			$gm_signature = $current_user_name;
			$gm_signature_date = '( '.date('Y-M-d').' )';
		}
		
		
		
		$hr_signature = $recordModel->get('cf_4087');
		$hr_signature_date = $recordModel->get('cf_4089');
        $hr_signature_date = (empty($hr_signature_date) ? ' ' :' ( '.date('Y-M-d',strtotime($hr_signature_date)).' ) ');  
		
		$user_info = "Request ID: ".$recordId." for new email account: ".$recordModel->get('cf_4061')." ".strtoupper($recordModel->get('cf_4059'))." / ".$recordModel->get('cf_4063')." / ".$recordModel->getDisplayValue('cf_4065')." / ".$recordModel->getDisplayValue('cf_4067')." ";
		$subject = "Request for new email account: ".$recordModel->get('cf_4061')."; ID: ".$recordId." ";
		$createdtime = $recordModel->get('CreatedTime');
		
		new_email_notification($createdtime, $subject, $user_info,
							   $current_user_email, $current_user_name,
							   $ceo_user_email, $gm_user_email, 
							   $it_user_email, $hr_user_email, $recordId, 
							   $assigned_user_email, $assigned_user_name, 
							   $ceo_signature, $ceo_signature_date, 
							   $gm_signature, $gm_signature_date, 
							   $hr_signature, $hr_signature_date							   
							   ); 
		
					   
		
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