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
 * ProjectMilestone Record Model Class
 */
class ProjectMilestone_Record_Model extends Vtiger_Record_Model {
 	
	function getProjectId(){
		global $adb;
		$queryResult = $adb->pquery("SELECT projectid
																 FROM vtiger_projectmilestone
																 WHERE projectmilestoneid = ?", array($this->getId()));
		$projectid = $adb->query_result($queryResult, 0, 'projectid');
		return $projectid;
	}

	function getTaskStatus(){
		global $adb;
		$queryResult = $adb->pquery("SELECT cf_7838
																 FROM vtiger_projectmilestonecf
																 WHERE projectmilestoneid = ?", array($this->getId()));
		$taskStatus = $adb->query_result($queryResult, 0, 'cf_7838');
		return $taskStatus;
	}

}