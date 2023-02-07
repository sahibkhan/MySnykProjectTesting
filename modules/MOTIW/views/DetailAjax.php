<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class MOTIW_DetailAjax_View extends Vtiger_IndexAjax_View {

	public function process(Vtiger_Request $request) {
		
		global $adb;
		$adb = PearDatabase::getInstance();
		
		$record = $request->get('record');
		
		$recordModel = ModComments_Record_Model::getInstanceById($record);
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
				
		$sql_g = "SELECT  vtiger_groups.groupid, vtiger_groups.groupname,  vtiger_groups.description, vtiger_users2group.userid  FROM vtiger_groups INNER JOIN vtiger_users2group ON vtiger_users2group.groupid = vtiger_groups.groupid WHERE vtiger_groups.groupname='Motiw Cordinator'";
		$result_g = $adb->pquery($sql_g);
		$noofrow = $adb->num_rows($result_g);
			 
			$final = array();
			$n = 2;
			for($i=0; $i<$noofrow ; $i++) {
				
				$cordinator[$i]=$adb->query_result($result_g,$i,'userid');
				
			}
		
		
		$current_user_id = $currentUserModel->get('id');
		
		$viewer = $this->getViewer($request);
		$viewer->assign('CURRENTUSER', $currentUserModel);
		$viewer->assign('COMMENT', $recordModel);
		$viewer->assign('CURRENT_USER', $current_user_id);
		$viewer->assign('CORDINATOR', $cordinator);

		$moduleName = 'MOTIW';
		echo $viewer->view('childComment.tpl', $moduleName, true); 
	}
}