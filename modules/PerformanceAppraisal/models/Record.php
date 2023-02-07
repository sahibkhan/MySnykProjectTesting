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
class PerformanceAppraisal_Record_Model extends Vtiger_Record_Model {

	protected $module = false;
	/**
	  Get current user login
	*/
	public function getCurUserInfo() {
		$current_user = Users_Record_Model::getCurrentUserModel();		
		$login = $current_user->get('user_name');
		return $login;
	}

}
