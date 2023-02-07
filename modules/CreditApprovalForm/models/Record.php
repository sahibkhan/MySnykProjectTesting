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
 * TruckParts Record Model Class
 */
class CreditApprovalForm_Record_Model extends Inventory_Record_Model {
 	
	/**
	  Get current user login
	*/
	public function getCurUserInfo1() {
	  $current_user = Users_Record_Model::getCurrentUserModel();
	  $login = $current_user->get('user_name');
	  $position = '';
	  if ($login == 's.mansoor') $position = 'fd_manager';  
	  
	  return $position;
	}
	/**
	  Get Truck Parts status
	*/
/*	public function getTruckPartsStatus($position) {
	
	  // Status OPS cf_3591
	  // Status Fleet cf_3571
	  $current_tp_status = '';
	  $record = $this->getId();		
	  $tp_info = Vtiger_Record_Model::getInstanceById($record, 'TruckParts');
 
	  if ($position == 'director'){
        $current_tp_status = $tp_info->get('cf_3571'); 
	  }
	
	  if ($position == 'fleet_manager'){
		$current_tp_status = $tp_info->get('cf_3591');  
	  }
	  
	  if ($position == 'other_coordinator'){
		$current_tp_status = $tp_info->get('cf_3571');  
	  }	  	  


	  
	  return $current_tp_status;	
	
	}*/

}