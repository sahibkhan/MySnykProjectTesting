<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
include_once 'modules/FurnitureRequest/FurnitureRequestHandler.php';
//include_once 'vtlib/Vtiger/Module.php';
//include_once 'modules/Vtiger/CRMEntity.php';

class FurnitureRequest_Approval_Action extends Vtiger_Action_Controller {

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

      $moduleName = 'FurnitureRequest';
      $recordId = $request->get('record');
      $recordModel = Vtiger_Record_model::getInstanceById($recordId, $moduleName);
      $recordModel->set('mode', 'edit');
      
       //Get current and assigned users roleID
	   //$current_user_role = Users_Record_Model::getCurrentUserModel();
       $currentUserName = $current_user->first_name.' '.$current_user->last_name;
       $roleid = $current_user->roleid;
       $userId = $current_user->id;
       $user_name = $current_user->user_name;
       $currentUserEmail = strtolower(trim($current_user->email1));

       $office_val = $recordModel->getDisplayValue('cf_4683');
       //assigned_user_dep :: cf_4697
       $assigned_user_dep = $recordModel->getDisplayValue('cf_4697');
       $assigned_user_branch = trim($recordModel->get('cf_4795'));
       $recordCreatorId = $recordModel->get('assigned_user_id');

       if($office_val == 'ALA'){
           
           $dep_name = $assigned_user_dep;
       }
       else{
     
           $dep_name = $assigned_user_branch;
       }

        // echo 'assigned_user_branch = '.$assigned_user_branch; exit;

       $head_dep_login = get_branch_details($dep_name, 'manager_login');
	   $headEmail = getUserEmailByUserName($head_dep_login);
    
       
/*         echo $currentUserEmail .'___'.$recordCreatorId.'_';
       exit; */

       
        // Head Approval
        if (($currentUserEmail == 'e.aitzhanov@globalinklogistics.com') && ($recordCreatorId == 1351)){
            $head_dep_login = 'e.aitzhanov';
        }

        if ($recordCreatorId == 1227){
            $head_dep_login = 'a.ahmed';
        }
        
       if ($currentUserEmail == $headEmail 
        || (($currentUserEmail == 'e.aitzhanov@globalinklogistics.com') && ($recordCreatorId == 1351)) 
        || ($recordCreatorId == 1227)){

            $recordModel->set('cf_4705', $head_dep_login);
            $recordModel->set('cf_4707', date('Y-m-d'));
            $recordModel->save(); 		
      }
      

      if($current_user->roleid == 'H2'){

        // CEO Approval
        $recordModel->set('cf_4713', $user_name);
        $recordModel->set('cf_4715', date('Y-m-d'));
        $recordModel->save();

        }else if($current_user->roleid == 'H74'){

            // FD Approval
            $recordModel->set('cf_4709', $user_name);
            $recordModel->set('cf_4711', date('Y-m-d'));
            $recordModel->save();
        } else if($userId == 1663){
    
           // Procurement Approval
           $recordModel->set('cf_6450', $user_name);
           $recordModel->set('cf_6452', date('Y-m-d'));
           $recordModel->save();
        }
       
      
        $result = furniture_msg_handler('FurnitureRequest',$recordId, $userId, $recordModel); 
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