<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

/**
 * Vtiger Entity Record Model Class
 */
class ExitList_Record_Model extends Vtiger_Record_Model {

	/**
	 * Function to get the id of the record
	 * @return <Number> - Record Id
	 */
	public function getId() {
		return $this->get('id');
	}

	/**
	 * Function to get the Module to which the record belongs
	 * @return Vtiger_Module_Model
	 */
	public function getModule() {
		return $this->module;
	}

	

	public function getUserPositionName($id){
		$recordExitList = Vtiger_Record_Model::getInstanceById($id, 'ExitListEntries');
		$name = $recordExitList->get('name');
		$recordUserList = Vtiger_Record_Model::getInstanceById($name, 'UserList');
		$positionName = $recordUserList->getDisplayValue('cf_823') ?$recordUserList->getDisplayValue('cf_823') : $recordUserList->getDisplayValue('cf_3341');
		return $positionName;
	}


}
