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

class PackerInquiry extends Vtiger_CRMEntity {
	var $table_name = 'vtiger_packerinquiry';
	var $table_index= 'packerinquiryid';

	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('vtiger_packerinquirycf', 'packerinquiryid');

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array('vtiger_crmentity', 'vtiger_packerinquiry', 'vtiger_packerinquirycf');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_packerinquiry' => 'packerinquiryid',
		'vtiger_packerinquirycf'=>'packerinquiryid');

	/**
	 * Mandatory for Listing (Related listview)
	 */
	var $list_fields = Array (
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'Name' => Array('packerinquiry', 'name'),
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
		'Name' => Array('packerinquiry', 'name'),
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
		$this->packerinquiry_msg_handler($module,$this->id);
		$_SESSION['fi_current_record'] = $this->id;
	}

	function packerinquiry_msg_handler($module,$record)
	{
		global $adb;
		$cc = '';
		$moduleName = $module;
		$record = $record;
		
		$packerinquiry_id = $record;
		$current_user = Users_Record_Model::getCurrentUserModel();
		$current_user_id = $current_user->getId();
		$user_info = Vtiger_Record_Model::getInstanceById($current_user_id, 'Users');

		$packerinquiry_info_detail = Vtiger_Record_Model::getInstanceById($packerinquiry_id, $moduleName);
			
		//$assignedto_id = $packerinquiry_info_detail->get('cf_3303');
		//$assigned_user_info = Vtiger_Record_Model::getInstanceById($assignedto_id, 'Users');
		//$to_email = $assigned_user_info->get('email1');
		//$to_name = $assigned_user_info->get('first_name');

		$owner_user_info = Vtiger_Record_Model::getInstanceById($packerinquiry_info_detail->get('assigned_user_id'), 'Users');
		//$to_email = $owner_user_info->get('email1');
		//$to_name = $owner_user_info->get('first_name');
		$to_name ='RRS Team';
		$user_location_id = $owner_user_info->get('location_id');
		
		
		$subject = '[From Packer Inquiry]  [ Inquiry Id : '.$packerinquiry_id.' ] ' .$packerinquiry_info_detail->get('cf_5331');
		$contents = 'Packer Inquiry Id : '.$packerinquiry_id. 
					'<br> Job Title : '.$packerinquiry_info_detail->get('cf_5331').
					'<br> Status :'.$packerinquiry_info_detail->get('cf_5327').
					'<br><br> Comments : '.$packerinquiry_info_detail->get('cf_5333').
					'<br><br> UR: https://erp.globalink.net/'.$packerinquiry_info_detail->getDetailViewUrl();
		
		$contact_email = $owner_user_info->get('email1');
		$name = $owner_user_info->get('first_name');
		$from_email = $contact_email;

		$query_packer_location =  $adb->pquery("SELECT `user`.`email1` FROM `vtiger_users` AS `user` 
									 WHERE `user`.`location_id`='".$user_location_id."' AND `title`='RRSSupervisor'", array());
		$packer_number = $adb->num_rows($query_packer_location);
		$emails = [];
		while($row_email = $adb->fetch_array($query_packer_location))
		{
			$emails[] = $row_email['email1'];
		}
		if (sizeof($emails) === 0) return; else $emails[] = $from_email;
		$to_email = 'supervisors@globalinklogistics.com';
		$cc = implode(';',$emails);
		
		//send mail to assigned to user
		$mail_status = send_mail('PackerInquiry',$to_email,$name,$from_email,$subject,$contents,$cc);

		//send mail to the coordinator who created the fleet inquiry)
		$mail_status = send_mail('PackerInquiry',$contact_email,$to_name,$to_email,$subject,$contents);

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