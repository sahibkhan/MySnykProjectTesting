<?php

    class InitialReport_CheckUserPermission_Action extends Vtiger_BasicAjax_Action {

        public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$record = $request->get('record');

		if(!Users_Privileges_Model::isPermitted($moduleName, 'Save', $record)) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
	}
		
		public function process(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();

		$moduleName = $request->get('module');
        $record = $request->get('recordId');
		
		$current_user = Users_Record_Model::getCurrentUserModel();
		
		 if($current_user->get('is_admin')!='on' && $record)
		 {
			 $report_record = Vtiger_Record_Model::getInstanceById($record, 'InitialReport');
			 $current_user_id = $current_user->getId();
			 $creator_id = $report_record->get('assigned_user_id');
			 $role = $current_user->get('roleid');
			if($record)
			{
		 	 $qry = $db->pquery('select emailSend from vtiger_initialreport where initialreportid = ?', array($record));		 
		 	 	if($db->num_rows($qry)  > 0){
			  	$emailSend = $db->fetch_array($qry);
		     	}	
			}
			
			if($current_user_id==$creator_id && $emailSend[0] == "Yes"){
			 //throw new AppException('LBL_PERMISSION_DENIED');	
			 $emailtrue = 1;
			}
			
			$emailData = array($emailtrue);
            $response = new Vtiger_Response();
            $response->setResult($emailData);
            $response->emit();
		 }else{
			$emailtrue = 0;
			
			$emailData = array($emailtrue);
            $response = new Vtiger_Response();
            $response->setResult($emailData);
            $response->emit();	 
		}
		

	}

    }

    ?>