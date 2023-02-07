<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
//include_once 'modules/Leaverequest/LeaverequestHandler.php';

class NCRS_Approval_Action extends Vtiger_Action_Controller {

    function __construct() {
        parent::__construct();       
        $this->exposeMethod('approveNCR');
    }

    public function requiresPermission(Vtiger_Request $request){
		$permissions = parent::requiresPermission($request);
		$mode = $request->getMode();
		if(!empty($mode)) {
			switch ($mode) {
				case 'approveNCR':
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
    
    function approveNCR(Vtiger_Request $request)
    {
        global $db;
        $db = PearDatabase::getInstance();
        $moduleName = $request->getModule();
        
        $current_user = Users_Record_Model::getCurrentUserModel();
        $initiator_approved_by      = $current_user->get('first_name').' '.$current_user->get('last_name');
                
        $recordId = $request->get('record');
        
        $db->pquery("UPDATE vtiger_ncrscf SET cf_6428=?, cf_6426=? WHERE ncrsid=? AND cf_6426=?", array($initiator_approved_by,'In Progress',$recordId,'Pending'));
        
        $recordModel = $this->record?$this->record:Vtiger_Record_Model::getInstanceById($recordId, $moduleName);

        $ncr_obj = new NCRS();
        
        $ncr_obj->notification_NCR($recordId,$module, 'Approved');
        
        $loadUrl = $recordModel->getDetailViewUrl();
        ob_clean();
        
        header("Location: https://gems.globalink.net/".$loadUrl); 
        exit; 
    }

}