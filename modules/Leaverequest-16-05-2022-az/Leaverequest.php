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
//include_once 'include/Vtiger/crm_data_arrange.php';
//require_once('include/uniq_domain.php');
//require_once('include/Leaverequest/hide_blocks.php');
//require_once('include/Leaverequest/leaverequest_email_handler.php');
include_once 'modules/Leaverequest/LeaverequestHandler.php';

class Leaverequest extends Vtiger_CRMEntity {
	var $table_name = 'vtiger_leaverequest';
	var $table_index= 'leaverequestid';

	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('vtiger_leaverequestcf', 'leaverequestid');

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array('vtiger_crmentity', 'vtiger_leaverequest', 'vtiger_leaverequestcf');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_leaverequest' => 'leaverequestid',
		'vtiger_leaverequestcf'=>'leaverequestid');

	/**
	 * Mandatory for Listing (Related listview)
	 */
	var $list_fields = Array (
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'Name' => Array('leaverequest', 'name'),
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
		'Name' => Array('leaverequest', 'name'),
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
		$recordId = $this->id;
		$recordModel = Vtiger_Record_model::getInstanceById($recordId, $module);
		$current_user = Users_Record_Model::getCurrentUserModel();
		$current_userid = $current_user->getId();
		//leave_msg_handler('Leaverequest',$recordId, $current_userid, $recordModel);
		
	/* echo "<pre>";
		print_r($recordModel);
		exit; */
	}

	function leave_balance($emp_ID, $leave_from_date)
	{
		$web1C = 'http://89.218.38.221/glwshrm/ws/leavebalance?wsdl';
		$con1C = array( 'login' => 'AdmWS',
						'password' => '906900',
						'soap_version' => SOAP_1_2,
						'cache_wsdl' => WSDL_CACHE_NONE, //WSDL_CACHE_MEMORY, //, WSDL_CACHE_NONE, WSDL_CACHE_DISK or WSDL_CACHE_BOTH
						'exceptions' => true,
						'trace' => 1);
								
		if (!function_exists('is_soap_fault')) {
			//echo '<br>not found module php-soap.<br>';
			return false;
		}

		try {
			$Client1C = new SoapClient($web1C, $con1C);
		} catch(SoapFault $e) {
			//var_dump($e);
			//echo '<br>error at connecting to 1C<br>';
			return false;
		}
		if (is_soap_fault($Client1C)){
			//echo '<br>inner server error at connecting to 1C<br>';
			return false;
		}

   		$record = array('EmployeID' => $emp_ID,
                    	'Date'=> $leave_from_date); 
		   
		/* $record = array('EmployeID' => 'GLK000324',
			'Date'=> date('Ymd'));*/         

		if (is_object($Client1C)) {
			try {
					
				$ret1c = $Client1C->LeaveBalance($record);
				$leavebalance = $ret1c->QuantityOfDays;
				
			} catch (SoapFault $e) {
				//echo "<pre>";
				//print_r($e);
				$leavebalance =  'Failed_';
			}   
		}
		else{
			//var_dump($idc);
			$leavebalance= '<br>no connection to 1C<br>';
		}
    
    	return $leavebalance;    
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