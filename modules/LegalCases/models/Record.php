<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class LegalCases_Record_Model extends Vtiger_Record_Model {

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
	
}
