<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
include_once 'modules/Leaverequest/LeaverequestHandler.php';

class Leaverequest_Approval_Action extends Vtiger_Action_Controller {

    function __construct() {
        parent::__construct();       
        $this->exposeMethod('setApproval');
        $this->exposeMethod('setReject');
    }

    public function requiresPermission(Vtiger_Request $request){
		$permissions = parent::requiresPermission($request);
		$mode = $request->getMode();
		if(!empty($mode)) {
			switch ($mode) {
				case 'setReject':
					$permissions[] = array('module_parameter' => 'module', 'action' => 'Approval', 'record_parameter' => 'recordId');
					break;
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
      global $current_user;
      $sign_date = date('Y-m-d');
    //   echo 'setApproval'; exit;

      $recordId = $request->get('record');
      $userId = $current_user->id;
      $user_name = $current_user->first_name.' '.$current_user->last_name;
      $current_user_email = strtolower($current_user->email1);     
      $user_roleid  = $current_user->roleid;
      
      $leave_info = Vtiger_Record_Model::getInstanceById($recordId, 'Leaverequest');
      $requested_by_id = $leave_info->get('cf_3423');
    
      //To GET User head info
      $user_info = Vtiger_Record_Model::getInstanceById($requested_by_id, 'UserList');
      $general_manager_id = $user_info->get('cf_3385');
      $user_head_info = Vtiger_Record_Model::getInstanceById($general_manager_id, 'UserList');
      
      $head_email = strtolower($user_head_info->get('cf_3355')); //Head Department Email
      $head_user_name = $user_head_info->get('name');
      
      if($current_user_email == $head_email){
		$head_user_name = $head_user_name;
		$result = $adb->pquery("UPDATE vtiger_leaverequestcf SET cf_3411 = '$head_user_name', cf_3413 = '$sign_date' WHERE leaverequestid = $recordId"); 
		//echo json_encode($user_role); 
        }else if(($user_roleid == 'H2') && ($user_name == 'Siddique Khan')){
            $head_user_name = 'Siddique Khan';
            $result = $adb->pquery("UPDATE vtiger_leaverequestcf SET cf_3411 = '$head_user_name', cf_3413 = '$sign_date' WHERE leaverequestid = $recordId"); 
            //echo json_encode($user_role); 
       // }else if($user_role['roleid'] == 'H201') {
    }else if($userId =='1588' && $user_roleid == 'H201') {
            $head_user_name = 'Ardak Gaisina';
            $result = $adb->pquery("UPDATE vtiger_leaverequestcf SET cf_3415 = '$head_user_name', cf_3417 = '$sign_date' WHERE leaverequestid = $recordId"); 
            //echo json_encode($user_role); 
        } else if($userId == 1078) {
            $head_user_name = "Gulzira Kobegeneva";
            $result = $adb->pquery("UPDATE vtiger_leaverequestcf SET cf_3415 = '$head_user_name', cf_3417 = '$sign_date' WHERE leaverequestid = $recordId"); 
            //echo json_encode($user_role); 
        }  else if($userId == 1269) {
            $head_user_name = "Dariya Rorokina";
            $result = $adb->pquery("UPDATE vtiger_leaverequestcf SET cf_3415 = '$head_user_name', cf_3417 = '$sign_date' WHERE leaverequestid = $recordId"); 
            //echo json_encode($user_role); 
        } else if($userId == 1253) {
            $head_user_name = "Nadira Sagitova";
            $result = $adb->pquery("UPDATE vtiger_leaverequestcf SET cf_3415 = '$head_user_name', cf_3417 = '$sign_date' WHERE leaverequestid = $recordId"); 
            //echo json_encode($user_role); 
        }  else if($userId == 1600) {
            $head_user_name = "Nurgul Arystanbayeva";
            $result = $adb->pquery("UPDATE vtiger_leaverequestcf SET cf_3415 = '$head_user_name', cf_3417 = '$sign_date' WHERE leaverequestid = $recordId"); 
            //echo json_encode($user_role); 
        } else if($userId == 279) {
            $head_user_name = "Lyubov Belyakova";
            $result = $adb->pquery("UPDATE vtiger_leaverequestcf SET cf_6618 = '$head_user_name', cf_6620 = '$sign_date' WHERE leaverequestid = $recordId"); 
            //echo json_encode($user_role); 
        }
        $recordModel = Vtiger_Record_Model::getInstanceById($recordId, 'Leaverequest');
        $result = leave_msg_handler('Leaverequest',$recordId, $userId, $recordModel);

        $result['_recordId'] = $recordModel->getId();
        //$response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $response = new Vtiger_Response();
        $response->setResult($result);
        $response->emit();

    }


    function setReject($request){
        global $adb;
        global $current_user;
        $sign_date = date('Y-m-d');
  
        $recordId = $request->get('record');
        $userId = $current_user->id;
        $user_name = $current_user->first_name.' '.$current_user->last_name;
        $current_user_email = $current_user->email1;      
        $user_roleid  = $current_user->roleid;

        $leave_info = Vtiger_Record_Model::getInstanceById($recordId, 'Leaverequest');
        $requested_by_id = $leave_info->get('cf_3423');

        //To GET User head info
        $user_info = Vtiger_Record_Model::getInstanceById($requested_by_id, 'UserList');
        $general_manager_id = $user_info->get('cf_3385');
        $user_head_info = Vtiger_Record_Model::getInstanceById($general_manager_id, 'UserList');
        
        $head_email = $user_head_info->get('cf_3355'); //Head Department Email
        $head_user_name = $user_head_info->get('name');

            //if($user_name == $head_user_name){
        if($current_user_email == $head_email && $user_roleid != 'H201'){
            $head_user_name = $head_user_name .' - (Canceled)';
            $result = $adb->pquery("UPDATE vtiger_leaverequestcf SET cf_3411 = '$head_user_name', cf_3413 = '$sign_date' WHERE leaverequestid = $recordId"); 
            //echo json_encode($user_role); 
        } else if(($user_roleid == 'H2') && ($user_name == 'Siddique Khan')) {
            $head_user_name = 'Siddique Khan - (Canceled)';
            $sql = "UPDATE vtiger_leaverequestcf SET cf_3411 = '$head_user_name', cf_3413 = '$sign_date' WHERE leaverequestid = $recordId";
            $result = $adb->pquery($sql); 
            //echo json_encode($user_role); 
        } else if($user_roleid == 'H201') {
            $head_user_name = 'Makhabbat Mussina' .' - (Canceled)';
            $result = $adb->pquery("UPDATE vtiger_leaverequestcf SET cf_3415 = '$head_user_name', cf_3417 = '$sign_date' WHERE leaverequestid = $recordId"); 
            //echo json_encode($user_role);
        }
        $recordModel = Vtiger_Record_Model::getInstanceById($recordId, 'Leaverequest');
        $result['_recordId'] = $recordModel->getId();
        //$response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $response = new Vtiger_Response();
        $response->setResult($result);
        $response->emit();
        //exit;
        //$result = leave_msg_handler('Leaverequest',$record_id, $userId);


    }

}