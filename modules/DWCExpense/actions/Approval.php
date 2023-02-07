<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
include_once 'modules/DWCExpense/DWCExpenseHandler.php';
//include_once 'vtlib/Vtiger/Module.php';
//include_once 'modules/Vtiger/CRMEntity.php';

class DWCExpense_Approval_Action extends Vtiger_Action_Controller {

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
      //$adb->setDebug(true);
      global $current_user;
      $sign_date = Date('Y-m-d'); 
      

      $moduleName = 'DWCExpense';
      $recordId = $request->get('record');
      $recordModel = Vtiger_Record_model::getInstanceById($recordId, $moduleName);
      $recordModel->set('mode', 'edit');
      
       //Get current and assigned users roleID
	   //$current_user_role = Users_Record_Model::getCurrentUserModel();
       $currentUserName = $current_user->first_name.' '.$current_user->last_name;
       $userId = $current_user->id;
       $user_name = $current_user->user_name;

       if ($user_name === 'a.ahmed') {//GM       
         // mysql_query("UPDATE vtiger_dwcexpensecf SET cf_4743 = 'Aftab Ahmed', cf_4745 = '$dt' WHERE dwcexpenseid = $recordId");
         $recordModel->set('cf_4743', 'Aftab Ahmed');
         $recordModel->set('cf_4745', date('Y-m-d'));
         $recordModel->save(); 
      } else if ($user_name === 's.mansoor') {//CFO       
         // mysql_query("UPDATE vtiger_dwcexpensecf SET cf_4747 = 'Sohail Mansoor', cf_4749 = '$dt' WHERE dwcexpenseid = $recordId");
         $recordModel->set('cf_4747', 'Sohail Mansoor');
         $recordModel->set('cf_4749', date('Y-m-d'));
         $recordModel->save(); 
       
      } elseif ($user_name === 's.khan') {//CEO
         //mysql_query("UPDATE vtiger_dwcexpensecf SET cf_4751 = 'Siddique Khan', cf_4753 = '$dt' WHERE dwcexpenseid = $recordId");
         $recordModel->set('cf_4751', 'Siddique Khan');
         $recordModel->set('cf_4753', date('Y-m-d'));
         $recordModel->save(); 
      }
     
      
        $result = message_Send('DWCExpense',$recordId, $userId); 

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

    function setReject($request){
        global $adb;
        //$adb->setDebug(true);
        global $current_user;
        $sign_date = Date('Y-m-d'); 
        
  
        $moduleName = 'DWCExpense';
        $recordId = $request->get('record');
        $recordModel = Vtiger_Record_model::getInstanceById($recordId, $moduleName);
        $recordModel->set('mode', 'edit');
        
         //Get current and assigned users roleID
         //$current_user_role = Users_Record_Model::getCurrentUserModel();
         $currentUserName = $current_user->first_name.' '.$current_user->last_name;
         $userId = $current_user->id;
         $user_name = $current_user->user_name;
  
         if ($user_name === 'a.ahmed') {//GM       
           // mysql_query("UPDATE vtiger_dwcexpensecf SET cf_4743 = 'Aftab Ahmed', cf_4745 = '$dt' WHERE dwcexpenseid = $recordId");
           $recordModel->set('cf_4743', 'Aftab Ahmed (Cancelled)');
           $recordModel->set('cf_4745', date('Y-m-d'));
           $recordModel->save(); 
        } else if ($user_name === 's.mansoor') {//CFO       
           // mysql_query("UPDATE vtiger_dwcexpensecf SET cf_4747 = 'Sohail Mansoor', cf_4749 = '$dt' WHERE dwcexpenseid = $recordId");
           $recordModel->set('cf_4747', 'Sohail Mansoor (Cancelled)');
           $recordModel->set('cf_4749', date('Y-m-d'));
           $recordModel->save(); 
         
        } elseif ($user_name === 's.khan') {//CEO
           //mysql_query("UPDATE vtiger_dwcexpensecf SET cf_4751 = 'Siddique Khan', cf_4753 = '$dt' WHERE dwcexpenseid = $recordId");
           $recordModel->set('cf_4751', 'Siddique Khan (Cancelled)');
           $recordModel->set('cf_4753', date('Y-m-d'));
           $recordModel->save(); 
        }
       
        
          $result = message_Send('DWCExpense',$recordId, $userId); 
  
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