<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
class MOTIW_GLKUserList_UIType extends Vtiger_GLKUserList_UIType {
	/**
	 * Function to get the Template name for the current UI Type Object
	 * @return <String> - Template Name
	 */
	public function getTemplateName() {
		return 'uitypes/GLKUserList.tpl';
	}

	public function getDisplayValue($value) {
		$db = PearDatabase::getInstance();
		
		$result = $db->pquery('SELECT first_name, last_name FROM vtiger_users WHERE id = ? ',
					array($value));
					
		if($db->num_rows($result)) {
			 $first_name = $db->query_result($result, 0, 'first_name');
			 $last_name = $db->query_result($result, 0, 'last_name');
			 $glk_user_name = $first_name. ' '. $last_name;
			 return $glk_user_name;
		}
		return $value;
	}
}
?>
