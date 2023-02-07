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

class JobDescription_Record_Model extends Vtiger_Record_Model {

	protected $module = false;


	public function isValidApproval() {
		global $adb;
		$isValidApproval = false;
		$recordId = $this->getId();
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$currentUserEmail = trim($currentUser->get('email1'));
		$recordProbationAssessment = Vtiger_Record_Model::getInstanceById($recordId, 'JobDescription');
		$requestedById = $recordProbationAssessment->get('cf_7552');
		

		$headApproval = $recordProbationAssessment->get('cf_7556');
		// $headApprovalDate = $recordProbationAssessment->get('cf_7344');
		
		$employeeApproval = $recordProbationAssessment->get('cf_7554');
		// $employeeApprovalDate = $recordProbationAssessment->get('cf_7342');
/* 
		$HRApproval = $recordProbationAssessment->get('cf_7332');
		$HRApprovalDate = $recordProbationAssessment->get('cf_7340'); */
 
		// Fetch request user email		
		$queryUser = $adb->pquery("
								SELECT vtiger_userlistcf.cf_3355
								FROM vtiger_userlistcf
								WHERE vtiger_userlistcf.userlistid = ?",array($requestedById));
		$recordCreatorEmail = trim($adb->query_result($queryUser, 0, 'cf_3355'));

		// Fetch current user's head
		$queryHead = $adb->pquery("SELECT vtiger_userlistcf.cf_3355
															FROM vtiger_userlistcf
															WHERE vtiger_userlistcf.userlistid IN (
																SELECT cf_3385
																FROM vtiger_userlistcf
																WHERE userlistid = ?)", array($requestedById));
		$headEmail = $adb->query_result($queryHead, 0, 'cf_3355');

		// Fetch HR Manager
/* 		$queryHRManager = $adb->pquery("SELECT .vtiger_userlistcf.cf_3355
															FROM `vtiger_userlistcf` 
															WHERE cf_3385 = 412373 AND cf_3421 = 85805 
															AND cf_3353 = 85757 AND cf_3349 = 414370 
															AND cf_6206 = 'Active'");
		$HRMangerEmail = $adb->query_result($queryHRManager, 0, 'cf_3355'); */
		
	
		if ($currentUserEmail == $headEmail && empty($headApproval)){
			// Head
			$isValidApproval = true;
		} else 
		if ($currentUserEmail === $recordCreatorEmail && empty($employeeApproval)){
			// Employee 
			$isValidApproval = true; 
		}
		
		return $isValidApproval;
	}


}
