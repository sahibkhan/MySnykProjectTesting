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
require_once 'modules/Emails/mail.php';
//require_once('include/FleetInquiry/fleetinquiry_message_handler.php');

class FleetInquiry extends Vtiger_CRMEntity {
	var $table_name = 'vtiger_fleetinquiry';
	var $table_index= 'fleetinquiryid';

	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('vtiger_fleetinquirycf', 'fleetinquiryid');

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array('vtiger_crmentity', 'vtiger_fleetinquiry', 'vtiger_fleetinquirycf');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_fleetinquiry' => 'fleetinquiryid',
		'vtiger_fleetinquirycf'=>'fleetinquiryid');

	/**
	 * Mandatory for Listing (Related listview)
	 */
	var $list_fields = Array (
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'Name' => Array('fleetinquiry', 'name'),
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
		'Name' => Array('fleetinquiry', 'name'),
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
	  //global $adb;
	  $this->fleetinquiry_msg_handler($module,$this->id);
	  $_SESSION['fi_current_record'] = $this->id;
	}


	function fleetinquiry_msg_handler($module,$record)
	{
		$cc = '';
		$moduleName = $module;
		$record = $record;
		
		$fleetinquiry_id = $record;
		$current_user = Users_Record_Model::getCurrentUserModel();
		$current_user_id = $current_user->getId();
		$user_info = Vtiger_Record_Model::getInstanceById($current_user_id, 'Users');

		$fleetinquiry_info_detail = Vtiger_Record_Model::getInstanceById($fleetinquiry_id, $moduleName);
		$assignedto_id = $fleetinquiry_info_detail->get('cf_3303');
		$assigned_user_info = Vtiger_Record_Model::getInstanceById($assignedto_id, 'Users');
		$to_email = $assigned_user_info->get('email1');
		$to_name = $assigned_user_info->get('first_name');

		$owner_user_info = Vtiger_Record_Model::getInstanceById($fleetinquiry_info_detail->get('assigned_user_id'), 'Users');

		if ($fleetinquiry_info_detail->get('cf_3295') == 'Accepted') $cc .= 'a.zorin@globalinklogistics.com;dispatcher@globalinklogistics.com;';

		$subject = '[From Fleet Inquiry]  [ Inquiry Id : '.$fleetinquiry_id.' ] ' .$fleetinquiry_info_detail->get('cf_3297');
		$contents = 'Fleet Inquiry No : '.$fleetinquiry_info_detail->get('cf_3301'). 
					'<br> Job Title : '.$fleetinquiry_info_detail->get('cf_3297').
					'<br> Status :'.$fleetinquiry_info_detail->get('cf_3295').
					'<br><br> Special Instructions : '.$fleetinquiry_info_detail->get('cf_2143');
		
		$contact_email = $owner_user_info->get('email1');
		$name = $owner_user_info->get('first_name');
		$from_email = $contact_email;
	

		//send mail to assigned to user
		$mail_status = send_mail('FleetInquiry',$to_email,$name,$from_email,$subject,$contents,$cc);

		//send mail to the coordinator who created the fleet inquiry)
		$mail_status = send_mail('FleetInquiry',$contact_email,$to_name,$to_email,$subject,$contents);

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