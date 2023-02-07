<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
class Procurement_LocationList_UIType extends Vtiger_LocationList_UIType {
	/**
	 * Function to get the Template name for the current UI Type Object
	 * @return <String> - Template Name
	 */
	public function getTemplateName() {
		return 'uitypes/LocationList.tpl';
	}

	public function getDisplayValue($value, $record=false, $recordInstance=false) {

		//echo $template = $this->get('field')->getFieldParams();
		//$value_id = $value;
		/*
		if ($_REQUEST['module'] == "UserList" || $_REQUEST['module'] == "CalendarDays"){
			$name = Vtiger_Cache::get('LocationData_Module' . $value, $value);
		}
		else
		{
			$name = Vtiger_Cache::get('LocationData' . $value, $value);
		}
		if ($name) {
			return $name;
		}
		*/
		
		$db = PearDatabase::getInstance();
		if ($_REQUEST['module'] == "UserList" || $_REQUEST['module'] == "CalendarDays"){							   
			$result = $db->pquery('SELECT vtiger_location.name 
							   FROM vtiger_locationcf
							   INNER JOIN vtiger_location ON vtiger_location.locationid = vtiger_locationcf.locationid
							   WHERE vtiger_location.locationid = ? ',
					array($value));   
			$field_name = "name";		
			//$field_module = 'LocationData_Module';				   
		} else {
		
			$result = $db->pquery('SELECT cf_1559 FROM vtiger_locationcf WHERE locationid = ? ',
					array($value));
			$field_name = 'cf_1559';
			//$field_module = 'LocationData';		
		}
		//$name = false;			
		if($db->num_rows($result)) {
			return $db->query_result($result, 0, $field_name);
			//$name =  $db->query_result($result, 0, $field_name);
		}		
		//Vtiger_Cache::set($field_module . $value, $value, $name);
		//return $name;
		return $value;
		
	
	}
	public function getListSearchTemplateName() {
        return 'uitypes/LocationListFieldSearchView.tpl';
    }
}
?>
