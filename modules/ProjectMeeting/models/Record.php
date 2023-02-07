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
 * ProjectMeeting Record Model Class
 */
class ProjectMeeting_Record_Model extends Inventory_Record_Model {
 	
	/**
	  Get current user login
	*/
	public function getCurUserInfo() {
	  $current_user = Users_Record_Model::getCurrentUserModel();
	  $login = $current_user->get('user_name');
	  $position = '';
	  if ($login == 's.khan') $position = 'CEO'; else $position = 'GM';	 
	  //$position = 'CEO';	  
	  return $position;
	}
	/**
	  Get ProjectMeeting status
	*/
	public function getTravelFormStatus(){
	
	  // Before trip status 	- cf_5121
	  // After trip status 		- cf_5123
	  
	  $pm_status = '';
	  $record = $this->getId();		
	  $tf_info = Vtiger_Record_Model::getInstanceById($record, 'ProjectMeeting');	  
		
	
	  $ceo_approval = $tf_info->get('cf_5700'); // CEO
	  $gm_approval = $tf_info->get('cf_5704');	// GM
	  
	  
	  if ((empty($ceo_approval)) && (empty($gm_approval))){
			$pm_status = "";	  
	  } else if ((!empty($ceo_approval)) && (empty($gm_approval))){
			if ($ceo_approval == "Submitted"){
				$pm_status = $ceo_approval;
			} else if ($ceo_approval == "Approved"){
				$trip_status = $gm_approval;
			} else if ($ceo_approval == "Rejected"){
				$pm_status = $ceo_approval;
			}
						
	  } else if ((!empty($ceo_approval)) && (!empty($gm_approval))){
		  
			$pm_status = $gm_approval;
	  }
	  
	  

	  return $pm_status;	
	
	}

}