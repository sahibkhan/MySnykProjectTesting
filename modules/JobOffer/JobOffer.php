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

class JobOffer extends Vtiger_CRMEntity {
	var $table_name = 'vtiger_joboffer';
	var $table_index= 'jobofferid';

	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('vtiger_joboffercf', 'jobofferid');

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array('vtiger_crmentity', 'vtiger_joboffer', 'vtiger_joboffercf');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_joboffer' => 'jobofferid',
		'vtiger_joboffercf'=>'jobofferid');

	/**
	 * Mandatory for Listing (Related listview)
	 */
	var $list_fields = Array (
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'Name' => Array('joboffer', 'name'),
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
		'Name' => Array('joboffer', 'name'),
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
		$this->generateRefNo();
	}

	function generateRefNo(){
		global $adb;
		// GENERATE Ref. no for agreement	
		// Creator location code
		$recordId = $this->id;
		$recordJobOffer = Vtiger_Record_Model::getInstanceById($recordId, 'JobOffer');
		$location_id = $recordJobOffer->get('cf_7236');
		$currentRefNo = $recordJobOffer->get('cf_7240');

		$recordLocation = Vtiger_Record_Model::getInstanceById($location_id, 'Location');
		$locationCode = $recordLocation->get('cf_1559');

		// Number of records relevant to current location and department
		$sql_m =  "SELECT vtiger_crmentity.crmid
			FROM vtiger_joboffercf
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_joboffercf.jobofferid
			WHERE vtiger_crmentity.deleted = 0 AND vtiger_joboffercf.cf_7236 = ?";
	
		$result_m = $adb->pquery($sql_m, array($location_id));
		$nOfRecords = $adb->num_rows($result_m);
		$refNo = trim(sprintf("%'.05d\n", $nOfRecords + 1));		
		$subject = $locationCode.'-'.$refNo.'/'.Date('y');

		// Update ref. no
		if (strlen($currentRefNo) == 0){
			$adb->pquery("UPDATE `vtiger_joboffercf` SET `cf_7240` = '$subject' WHERE `jobofferid` = ? LIMIT 1", array($recordId));
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