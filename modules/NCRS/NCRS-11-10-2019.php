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

class NCRS extends Vtiger_CRMEntity {
	var $table_name = 'vtiger_ncrs';
	var $table_index= 'ncrsid';

	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('vtiger_ncrscf', 'ncrsid');

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array('vtiger_crmentity', 'vtiger_ncrs', 'vtiger_ncrscf');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_ncrs' => 'ncrsid',
		'vtiger_ncrscf'=>'ncrsid');

	/**
	 * Mandatory for Listing (Related listview)
	 */
	var $list_fields = Array (
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'Name' => Array('ncrs', 'name'),
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
		'Name' => Array('ncrs', 'name'),
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
	
	public function saveUniqueID($module,$column,$table_id,$id) {
		
		$sql = mysql_query("SELECT `$column` FROM `$module` ORDER BY `$column` DESC LIMIT 1");
		$arr = mysql_fetch_array($sql)[$column];
		$date = date('y');
		if ($arr != '') {
			$arr = explode('/',$arr);
			if ($arr[1] == $date) {
				$num = (int)$arr[0];
				$num++;
				$res = sprintf("%05s", $num).'/'.$date;
			} else $res = '00001/'.$date;
		} else $res = '00001/'.$date;
		mysql_query("UPDATE `$module` SET `$column`='".$res."', cf_6426='Pending' WHERE `$table_id`='".$id."' LIMIT 1");
	}
	
	function save_module($module) {
		$recordId = $this->id;
		$recordModel = Vtiger_Record_model::getInstanceById($recordId, $module);
		$sql = mysql_query("SELECT cf_1963 FROM vtiger_ncrscf WHERE ncrsid='".$recordId."' LIMIT 1");
		if (mysql_num_rows($sql)>0) {
			if (trim(mysql_fetch_array($sql)['cf_1963']) == '') {
				//$sql1 = mysql_query("SELECT `smownerid` FROM `vtiger_crmentity` WHERE `crmid`='".$recordId."' LIMIT 1");
				//$userid = mysql_fetch_array($sql1)['smownerid'];
				$this->saveUniqueID('vtiger_ncrscf','cf_1963','ncrsid',$recordId);
			}
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