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
class ITEquipment_Record_Model extends Vtiger_Record_Model {


	public function isValidApproval() {
		global $adb;
		$isValidApproval = false;
		$recordId = $this->getId();

    $IT_MANAGER_EMAIL = 's.aftab@globalinklogistics.com';
    $CFO_EMAIL = 's.mansoor@globalinklogistics.com';
    $CEO_EMAIL = 's.khan@globalinklogistics.com';
    $GPM_EMAIL = 'z.kazlykov@globalinklogistics.com';


		$currentUser = Users_Record_Model::getCurrentUserModel();
		$currentUserEmail = strtolower(trim($currentUser->get('email1')));
		$recordITEquipment = Vtiger_Record_Model::getInstanceById($recordId, 'ITEquipment');
		

		$ITApproval = $recordITEquipment->get('cf_4541');
		$ITApprovalDate = $recordITEquipment->get('cf_4543');

		$CFOApproval = $recordITEquipment->get('cf_4545');
		$CFOApprovalDate = $recordITEquipment->get('cf_4547');
 
		$CEOApproval = $recordITEquipment->get('cf_4549');
		$CEOApprovalDate = $recordITEquipment->get('cf_4551');

		$GPMApproval = $recordITEquipment->get('cf_6444');
		$GPMApprovalDate = $recordITEquipment->get('cf_6446');



		if ($currentUserEmail == $IT_MANAGER_EMAIL && empty($ITApproval) && empty($ITApprovalDate)){
			// IT Manager
			$isValidApproval = true;
		} else 
		if ($currentUserEmail == $CFO_EMAIL && empty($CFOApproval) && empty($CFOApprovalDate)){
			// CFO
			$isValidApproval = true;
		} else 
		if ($currentUserEmail == $CEO_EMAIL && empty($CEOApproval) && empty($CEOApprovalDate)){
			// CEO
			$isValidApproval = true;
		} else 
		if ($currentUserEmail == $GPM_EMAIL && empty($GPMApproval) && empty($GPMApprovalDate)){
			// GPM
			$isValidApproval = true;
		}
		
		return $isValidApproval;
	}


}
