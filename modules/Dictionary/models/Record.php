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
 * Dictionary Record Model Class
 */
class Dictionary_Record_Model extends Inventory_Record_Model {
	 
	public function delete() {
		global $adb;
		$record = $this->getId();
		$date = date('Y-m-d h:i:s', time());
		$sql = $adb->pquery("UPDATE `vtiger_dictionarycf` SET `cf_6400` = '$date' WHERE `dictionaryid` = $record");
		$this->getModule()->deleteRecord($this);
	}

}