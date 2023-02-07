<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
// version_compare(PHP_VERSION, '5.5.0') <= 0 ? error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED & E_ERROR) : error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED  & E_ERROR & ~E_STRICT); // PRODUCTION

class MasterProfiles_Record_Model extends Vtiger_Record_Model {

/*      public function getInvities() {
         $adb = PearDatabase::getInstance();
         $sql = "select vtiger_invitees.* from vtiger_invitees where activityid=?";
         $result = $adb->pquery($sql,array($this->getId()));
         $invitiesId = array();

         $num_rows = $adb->num_rows($result);

         for($i=0; $i<$num_rows; $i++) {
             $invitiesId[] = $adb->query_result($result, $i,'inviteeid');
         }
         return $invitiesId;
     } */

	function getUserListIDs(){
		return '1, 2, 3, 4, 5';
	}

	public function getMPRecipientUsers($masterProfileId = 0) {
		global $adb;
		$recordId = (!empty($masterProfileId)) ? $masterProfileId : $this->getId();
		$accountsIds = $this->getRegionalAccountsIdsByMasterProfileId($recordId);
		$recipientUsersIds = array();
		$recipientUsersInfo = array();
		
		foreach ($accountsIds as $accountsId){
			$query = $adb->pquery("SELECT smownerid
														 FROM vtiger_crmentity
														 WHERE vtiger_crmentity.crmid = ? AND vtiger_crmentity.setype = ?", array($accountsId, 'Accounts'));
			$recipientUsersIds[] = $adb->query_result($query, 0, 'smownerid');

			$queryAccount = $adb->pquery("SELECT vtiger_users.id
																		FROM vtiger_users
																		LEFT JOIN vtiger_invitees ON vtiger_invitees.inviteeid = vtiger_users.id
																		WHERE vtiger_users.status = 'Active' AND
																		vtiger_invitees.activityid = ?", array($accountsId));
			$nRows = $adb->num_rows($queryAccount);
			for($i=0; $i<$nRows; $i++){
				$recipientUsersIds[] = $adb->query_result($queryAccount, $i,'id');
			}
		}

		$filteredRecipientUsersIds = array_unique($recipientUsersIds);
		foreach ($filteredRecipientUsersIds as $userId){
			$queryRecipient = $adb->pquery("SELECT vtiger_users.first_name, vtiger_users.last_name, vtiger_users.email1,
																						 vtiger_location.name as location, vtiger_departmentcf.cf_1542 as department
																		FROM vtiger_users
																		LEFT JOIN vtiger_location ON vtiger_location.locationid = vtiger_users.location_id
																		LEFT JOIN vtiger_departmentcf ON vtiger_departmentcf.departmentid = vtiger_users.department_id
																		WHERE vtiger_users.id = ?", array($userId));

			$userLocation = $adb->query_result($queryRecipient, 0, 'location');
			$userDepartment = $adb->query_result($queryRecipient, 0, 'department');
			$nameWithLocationAndDepartment =  $adb->query_result($queryRecipient, 0, 'first_name').' '.$adb->query_result($queryRecipient, 0, 'last_name').' ('.$userLocation.'/'.$userDepartment.')';

			$recipientUsersInfo[] = array("firstName" => $adb->query_result($queryRecipient, 0, 'first_name'),
																	"lastName" => $adb->query_result($queryRecipient, 0, 'last_name'),
																	"email" => $adb->query_result($queryRecipient, 0, 'email1'),
																	"nameWithLocationAndDepartment" => $nameWithLocationAndDepartment);
		}
 
		return $recipientUsersInfo;
	}

	public function getRegionalAccountsIdsByMasterProfileId($recordId){
		global $adb;
		$accountsIds = array();
		$queryAccounts = $adb->pquery("SELECT vtiger_accountscf.accountid
																	 FROM vtiger_accountscf
																	 LEFT JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_accountscf.accountid
																	 WHERE vtiger_crmentity.deleted = 0 AND vtiger_accountscf.cf_3207 = ?", array($recordId));
		$nRows = $adb->num_rows($queryAccounts);
		for($i=0; $i<$nRows; $i++){
			$accountsIds[] = $adb->query_result($queryAccounts, $i, 'accountid');
		}
		return $accountsIds;
	}

}
