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

class PMRItem extends Vtiger_CRMEntity {
	var $table_name = 'vtiger_pmritem';
	var $table_index= 'pmritemid';

	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('vtiger_pmritemcf', 'pmritemid');

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array('vtiger_crmentity', 'vtiger_pmritem', 'vtiger_pmritemcf');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_pmritem' => 'pmritemid',
		'vtiger_pmritemcf'=>'pmritemid');

	/**
	 * Mandatory for Listing (Related listview)
	 */
	var $list_fields = Array (
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'Name' => Array('pmritem', 'name'),
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
		'Name' => Array('pmritem', 'name'),
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
	
    function save_module($module) {
        $parent = 0;
		$record = $this->id;
        $sql = mysql_query("SELECT crmid FROM vtiger_crmentityrel where relcrmid = ".$record." and relmodule = 'PMRItem'");
        if (empty($sql) === false) {
            $row = mysql_fetch_assoc($sql);
            $parent = $row['crmid'];
        }
        if ($parent) {
            $sql = mysql_query("SELECT cf.cf_2373 from vtiger_pmritemcf cf
                inner join vtiger_crmentityrel r on r.crmid = ".$parent." 
                inner join vtiger_crmentity e on e.crmid = r.relcrmid and e.deleted = 0
                where cf.pmritemid = r.relcrmid limit 1");
            if (empty($sql) === false) {
                $row = mysql_fetch_assoc($sql);
                $currency = $row['cf_2373'];
                $sql = mysql_query("update vtiger_pmrlistcf set cf_2359 = ".$currency." where pmrlistid = ".$parent);
            }
            $sql = mysql_query("SELECT sum(cf.cf_2379) as prelSum, sum(cf.cf_2381) as actualSum from vtiger_pmritemcf cf
                inner join vtiger_crmentityrel r on r.crmid = ".$parent." 
                inner join vtiger_crmentity e on e.crmid = r.relcrmid and e.deleted = 0
                where cf.pmritemid = r.relcrmid");
            if (empty($sql) === false) {
                $row = mysql_fetch_assoc($sql);
                $prelSum = $row['prelSum'];
                if (is_null($prelSum)) { $prelSum = 0; }
                $actualSum = $row['actualSum'];
                if (is_null($actualSum)) { $actualSum = 0; }
                $sql = mysql_query("update vtiger_pmrlistcf set cf_2355 = ".$prelSum.", cf_2357 = ".$actualSum." where pmrlistid = ".$parent);
            } else {
                $sql = mysql_query("update vtiger_pmrlistcf set cf_2355 = 0, cf_2357 = 0 where pmrlistid = ".$parent);
            }
        }
	}
}