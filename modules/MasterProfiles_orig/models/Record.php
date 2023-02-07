<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class MasterProfiles_Record_Model extends Vtiger_Record_Model {

     public function getInvities() {
         $adb = PearDatabase::getInstance();
         $sql = "select vtiger_invitees.* from vtiger_invitees where activityid=?";
         $result = $adb->pquery($sql,array($this->getId()));
         $invitiesId = array();

         $num_rows = $adb->num_rows($result);

         for($i=0; $i<$num_rows; $i++) {
             $invitiesId[] = $adb->query_result($result, $i,'inviteeid');
         }
         return $invitiesId;
     }

    /*
			Get Invitee list
	*/
	public function getMPInviteeUsers() {
		global $adb;
		/*
			 Fetch user invitees
		*/
			$inviteUsers = '';
			$q_invitees = $adb->pquery("SELECT vtiger_users.first_name, vtiger_users.last_name
																	FROM vtiger_users
																	LEFT JOIN vtiger_invitees ON  vtiger_invitees.inviteeid = vtiger_users.id
																	WHERE vtiger_users.status = 'Active' AND 
																	vtiger_invitees.activityid = ?", array($this->getId())
			);
			$num_rows = $adb->num_rows($q_invitees);

			for($i=0; $i<$num_rows; $i++) {
				$inviteUsers .= "<tr><td>".$adb->query_result($q_invitees, $i,'first_name').' '. $adb->query_result($q_invitees, $i,'last_name').'</td></tr>';
			}

			return $inviteUsers;
		}     
	 
}
