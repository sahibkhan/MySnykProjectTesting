<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
//include_once 'modules/StaffHiring/StaffHiringHandler.php';
//ini_set('display_errors','on'); version_compare(PHP_VERSION, '5.5.0') <= 0 ? error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED) : error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);   // DEBUGGING

//include_once 'vtlib/Vtiger/Module.php';
//include_once 'modules/Vtiger/CRMEntity.php';

class NewEmail_Approval_Action extends Vtiger_Action_Controller {

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
      $dt = Date('Y-m-d'); 

      $moduleName = 'NewEmail';
      $recordId = $request->get('record');
      $recordModel = Vtiger_Record_model::getInstanceById($recordId, $moduleName);
      $recordModel->set('mode', 'edit');
      
        //Get current and assigned users roleID
        $current_user_role = Users_Record_Model::getCurrentUserModel();
        $currentUserName = $current_user_role->get('first_name').' '.$current_user_role->get('last_name');
        $currentUserEmail = $current_user_role->get('email1'); //email
        $currentUserId = $current_user_role->getId(); //id
        
        $gm_user_id = $recordModel->get('cf_4069');
        $email_sql = $adb->pquery("SELECT email1 FROM vtiger_users WHERE id=".$gm_user_id);
        $email_query = $adb->fetch_array($email_sql);
        $gm_user_email = $email_query['email1'];
        
        if($currentUserId == $gm_user_id){
            //approveNewEmailRequest('cf_4083', 'cf_4085'); //GM Approval
            $recordModel->set('cf_4083', $currentUserName);
            $recordModel->set('cf_4085', date('Y-m-d'));
            $recordModel->save();
            if($current_user_role->roleid =='H201')
            {
                //approveNewEmailRequest('cf_4087', 'cf_4089'); //HR Approval
                $recordModel->set('cf_4087', $currentUserName);
                $recordModel->set('cf_4089', date('Y-m-d'));
                $recordModel->save();	
            }
        }else if ($current_user_role->roleid =='H201' || $currentUserId == 1078 || $currentUserId == 1600 || $currentUserId == 1269){
            //approveNewEmailRequest('cf_4087', 'cf_4089'); //HR Approval
            $recordModel->set('cf_4087', $currentUserName);
            $recordModel->set('cf_4089', date('Y-m-d'));
            $recordModel->save();	
        }else if($current_user_role->roleid =='H2'){ 
            //approveNewEmailRequest('cf_4079', 'cf_4081'); // MD Approval
            $recordModel->set('cf_4079', $currentUserName);
            $recordModel->set('cf_4081', date('Y-m-d'));
            $recordModel->save();
        }

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