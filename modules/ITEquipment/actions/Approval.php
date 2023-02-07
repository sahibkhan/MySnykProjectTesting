<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
include_once 'modules/ITEquipment/ITEquipmentHandler.php';
//include_once 'vtlib/Vtiger/Module.php';
//include_once 'modules/Vtiger/CRMEntity.php';

class ITEquipment_Approval_Action extends Vtiger_Action_Controller {

    function __construct() {
        parent::__construct();       
        $this->exposeMethod('setApproval');
       //$this->exposeMethod('setReject');
    }

    public function requiresPermission(Vtiger_Request $request){
		$permissions = parent::requiresPermission($request);
		$mode = $request->getMode();
		if(!empty($mode)) {
			switch ($mode) {
				//case 'setReject':
			//		$permissions[] = array('module_parameter' => 'module', 'action' => 'Approval', 'record_parameter' => 'recordId');
			//		break;
				case 'setApproval':
					$permissions[] = array('module_parameter' => 'module', 'action' => 'Approval', 'record_parameter' => 'recordId');
					break;
				default:
					break;
			}
		}
		return $permissions;
	}


    function process(Vtiger_Request $request) {
		$mode = $request->getMode();
		if(!empty($mode)) {
			echo $this->invokeExposedMethod($mode, $request);
			return;
		}
		return false;
    }
    
    function setApproval($request){
      global $adb;
      //$adb->setDebug(true);
      global $current_user;
      $sign_date = Date('Y-m-d'); 

      $moduleName = 'ITEquipment';
      $recordId = $request->get('record');
      $recordModel = Vtiger_Record_model::getInstanceById($recordId, $moduleName);
    //   $recordModel->set('mode', 'edit');
      
       //Get current and assigned users roleID
	   //$current_user_role = Users_Record_Model::getCurrentUserModel();
       $currentUserName = $current_user->first_name.' '.$current_user->last_name;
       $roleid = $current_user->roleid;
       $userId = $current_user->id;

    //    echo "currentUserName = " . $sign_date; exit;
       
       if($current_user->roleid == 'H204' || $current_user->roleid == 'H205') {
        //$result = mysql_query("UPDATE vtiger_itequipmentcf SET cf_4541 = '$user_name', cf_4543 = '$sign_date' WHERE itequipmentid = $record_id");
/*             $recordModel->set('cf_4541', $currentUserName);
            $recordModel->set('cf_4543', date('Y-m-d'));
            $recordModel->save(); */
            
            $adb->pquery("UPDATE `vtiger_itequipmentcf` 
                          SET cf_4541 = ?, cf_4543 = ?
                          WHERE itequipmentid = ? LIMIT 1", array($currentUserName, $sign_date, $recordId));          


        } else if($current_user->roleid == 'H2') {
            //$result = mysql_query("UPDATE vtiger_itequipmentcf SET cf_4549 = '$user_name', cf_4551 = '$sign_date' WHERE itequipmentid = $record_id");  
/*             $recordModel->set('cf_4549', $currentUserName);
            $recordModel->set('cf_4551', date('Y-m-d'));
            $recordModel->save(); */

            $adb->pquery("UPDATE `vtiger_itequipmentcf` 
                          SET cf_4549 = ?, cf_4551 = ?
                          WHERE itequipmentid = ? LIMIT 1", array($currentUserName, $sign_date, $recordId));    


        } else if(($current_user->roleid == 'H74') and ($userId == 277)){
            //$result = mysql_query("UPDATE vtiger_itequipmentcf SET cf_4545 = '$user_name', cf_4547 = '$sign_date' WHERE itequipmentid = $record_id"); 
/*             $recordModel->set('cf_4545', $currentUserName);
            $recordModel->set('cf_4547', date('Y-m-d'));
            $recordModel->save(); */

            $adb->pquery("UPDATE `vtiger_itequipmentcf` 
                          SET cf_4545 = ?, cf_4547 = ?
                          WHERE itequipmentid = ? LIMIT 1", array($currentUserName, $sign_date, $recordId));    

            
        } else if($userId == 1663){
           // $result = mysql_query("UPDATE vtiger_itequipmentcf SET cf_6444 = '$user_name', cf_6446 = '$sign_date' WHERE itequipmentid = $record_id"); 
/*             $recordModel->set('cf_6444', $currentUserName);
            $recordModel->set('cf_6446', date('Y-m-d'));
            $recordModel->save(); */

            $adb->pquery("UPDATE `vtiger_itequipmentcf` 
                          SET cf_6444 = ?, cf_6446 = ?
                          WHERE itequipmentid = ? LIMIT 1", array($currentUserName, $sign_date, $recordId));

        }  

        $result = itequipment_msg_handler('ITEquipment',$recordId, $userId, $recordModel);
        $loadUrl = $recordModel->getDetailViewUrl();	
        //append App name to callback url
        //Special handling for vtiger7.
        $appName = $request->get('appName');
        if(strlen($appName) > 0){
            $loadUrl = $loadUrl.$appName;
        }
        header("Location: $loadUrl");
      
        exit;
    }

   

}