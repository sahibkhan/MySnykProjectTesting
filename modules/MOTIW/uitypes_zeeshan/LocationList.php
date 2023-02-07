<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
class MOTIW_LocationList_UIType extends Vtiger_Base_UIType {
	/**
	 * Function to get the Template name for the current UI Type Object
	 * @return <String> - Template Name
	 */
	public function getTemplateName() {
		return 'uitypes/LocationList.tpl';
	}

	public function getDisplayValue($value) {
        

		$db = PearDatabase::getInstance();
		if ($_REQUEST['module'] == "UserList" || $_REQUEST['module'] == "CalendarDays"){							   
			$result = $db->pquery('SELECT vtiger_location.name 
							   FROM vtiger_locationcf
							   INNER JOIN vtiger_location ON vtiger_location.locationid = vtiger_locationcf.locationid
							   WHERE vtiger_location.locationid = ? ',
					array($value));   
			$field_name = "name";		
							   
		} else {
		
			$result = $db->pquery('SELECT cf_1559 FROM vtiger_locationcf WHERE locationid = ? ',
					array($value));
			$field_name = 'cf_1559';		
		}
					
		if($db->num_rows($result)) {
			return $db->query_result($result, 0, $field_name);
		}
		return $value;
		
	
	}
}
?>
