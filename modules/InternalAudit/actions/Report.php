<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Project_Report_Action extends Vtiger_Action_Controller {

	function __construct() {
			parent::__construct();       
			$this->exposeMethod('setReport');
	}

	public function requiresPermission(Vtiger_Request $request){
		$permissions = parent::requiresPermission($request);
		$mode = $request->getMode();
		if(!empty($mode)) {
			switch ($mode) {
				case 'setReport':
					$permissions[] = array('module_parameter' => 'module', 'action' => 'Report', 'record_parameter' => 'recordId');
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


    
	public function setReport(Vtiger_Request $request) {
		global $adb;
		$recordId = $request->get('record'); 
    $attachmentid = $request->get('attachmentid');
 		$sql = "UPDATE vtiger_crmentity SET deleted = ? WHERE setype='Project Attachment' AND crmid = ? LIMIT 1";
		$result = $adb->pquery($sql, array(1, $attachmentid));
		
		$loadUrl = "index.php?module=Project&view=Edit&record=$recordId&app=MARKETING";
        echo '<script>
			var url= "'.$loadUrl.'"; 
			window.location = url; 
		</script>'; 
    
	}




}