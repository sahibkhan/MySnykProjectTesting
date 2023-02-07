<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
class NCRS_Save_Action extends Vtiger_Save_Action {

	function __construct() {
		
		parent::__construct();
		$this->exposeMethod('approveNCR');
	}

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$record = $request->get('record');
		
		$module_get = $_GET['module'];
		$record_get = $_GET['record'];
			
		if ((!Users_Privileges_Model::isPermitted($moduleName, 'Save', $record)) ) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
		
	}

	public function process(Vtiger_Request $request) {

		$mode = $request->get('mode');
        if(!empty($mode)) {
            $this->invokeExposedMethod($mode,$request);
			exit;
		}

		$NCRSID = $request->get('record');
		if($NCRSID != "")
		{
		$ncr_old_info = Vtiger_Record_model::getInstanceById($NCRSID, "NCRS");
		$ncr_old_status = $ncr_old_info->getDisplayValue('cf_6426');
		
		$ncrs_status = $request->get('cf_6426');
		if($ncr_old_status == "Completed" or $ncr_old_status == "Cancelled")
		{
			$request->set('cf_6426',$ncr_old_status);
		}
		else
		{
			if($ncrs_status == "Completed" or $ncrs_status == "Cancelled")
			{
				$request->set('cf_6514',date("Y/m/d"));
			}
		}
		}
		//exit;
		
				
		
		$person[0][] = implode (",",$request->get('cf_6512'));
		foreach($request->get('person') as $pers)
		{
			$person[0][] = implode (",",$pers);
		}
		
		$department[0][] = $request->get('cf_6508');
		foreach($request->get('department') as $depar)
		{
			$department[0][] = $depar;
		}
		
		$location[0][] = $request->get('cf_6510');
		foreach($request->get('location') as $locate)
		{
			$location[0][] = $locate;
		}
		$recordModel = $this->saveRecord($request);
		$sourcerecordid = $recordModel->get('id');
		
		$rcount= count($person[0]);
		for($x = 0; $x < $rcount; $x++){
			$saveattendencereport = new Vtiger_Save_Action();
			$request1 = new Vtiger_Request("","");
			$request1->set("module","NCRRaised");
			$request1->set("action","Save");
			if($request->get('ncrrecordid')[$x] == 0){
				$request1->set("record","");
			}else{
			$request1->set("record",$request->get('ncrrecordid')[$x]);}
			$request1->set("assigned_user_id", 1);
			$request1->set("cf_6512",$person[0][$x]);
			$request1->set("cf_6510",$location[0][$x]);
			$request1->set("cf_6508",$department[0][$x]);
			$recordModel2 = $saveattendencereport->saveRecord($request1);
			//related list entry
			$parentModuleName1 = "NCRS";
			$parentModuleModel1 = Vtiger_Module_Model::getInstance("NCRS");
			$parentRecordId1 = $sourcerecordid;
			$relatedModule1 = $recordModel2->getModule();
			$relatedRecordId1 = $recordModel2->get('id');
			$relationModel1 = Vtiger_Relation_Model::getInstance($parentModuleModel1, $relatedModule1);
			
			$relationModel1->addRelation($parentRecordId1, $relatedRecordId1);
		 }

		if($NCRSID == "")
		{
			$ncr_obj = new NCRS();	
			$ncr_obj->notification_NCR($sourcerecordid,'NCRS', 'Pending');
		}


		if($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			$parentRecordId = $request->get('sourceRecord');
			$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentRecordId, $parentModuleName);
			//TODO : Url should load the related list instead of detail view of record
			$loadUrl = $parentRecordModel->getDetailViewUrl();
		} else if ($request->get('returnToList')) {
			$loadUrl = $recordModel->getModule()->getListViewUrl();
		} else {
			$loadUrl = $recordModel->getDetailViewUrl();
		}
		header("Location: $loadUrl");
	}

	/**
	 * Function to save record
	 * @param <Vtiger_Request> $request - values of the record
	 * @return <RecordModel> - record Model of saved record
	 */
	public function saveRecord($request) {
		$recordModel = $this->getRecordModelFromRequest($request);
		$_SESSION['sendmsg_repeat'] = $request->getModule();
		
		$recordModel->save();
		if($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			$parentModuleModel = Vtiger_Module_Model::getInstance($parentModuleName);
			$parentRecordId = $request->get('sourceRecord');
			$relatedModule = $recordModel->getModule();
			$relatedRecordId = $recordModel->getId();

			$relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relatedModule);
			$relationModel->addRelation($parentRecordId, $relatedRecordId);
		}
		return $recordModel;
	}

	function approveNCR(Vtiger_Request $request)
	{
		global $db;
		$db = PearDatabase::getInstance();
		$moduleName = $request->getModule();
		
		$current_user = Users_Record_Model::getCurrentUserModel();
		$initiator_approved_by 		= $current_user->get('first_name').' '.$current_user->get('last_name');
				
		$recordId = $request->get('record');
		
		$db->pquery("UPDATE vtiger_ncrscf SET cf_6428=?, cf_6426=? WHERE ncrsid=? AND cf_6426=?", array($initiator_approved_by,'In Progress',$recordId,'Pending'));
		
		$recordModel = $this->record?$this->record:Vtiger_Record_Model::getInstanceById($recordId, $moduleName);

		$ncr_obj = new NCRS();
		
		$ncr_obj->notification_NCR($recordId,$module, 'Approved');
		
		$loadUrl = $recordModel->getDetailViewUrl();
		ob_clean();
		
		header("Location: https://erp.globalink.net/".$loadUrl); 
		exit; 
	}

	/**
	 * Function to get the record model based on the request parameters
	 * @param Vtiger_Request $request
	 * @return Vtiger_Record_Model or Module specific Record Model instance
	 */
	protected function getRecordModelFromRequest(Vtiger_Request $request) {

		$moduleName = $request->getModule();
		$recordId = $request->get('record');

		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

		if(!empty($recordId)) {
			$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
			$modelData = $recordModel->getData();
			$recordModel->set('id', $recordId);
			$recordModel->set('mode', 'edit');
		} else {
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$modelData = $recordModel->getData();
			$recordModel->set('mode', '');
		}

		$fieldModelList = $moduleModel->getFields();
		foreach ($fieldModelList as $fieldName => $fieldModel) {
			$fieldValue = $request->get($fieldName, null);
			$fieldDataType = $fieldModel->getFieldDataType();
			if($fieldDataType == 'time'){
				$fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
			}
			if($fieldValue !== null) {
				if(!is_array($fieldValue)) {
					$fieldValue = trim($fieldValue);
				}
				$recordModel->set($fieldName, $fieldValue);
			}
		}
		return $recordModel;
	}
	
	
	/* Send notification to initiator and ncr raised for if NCR status is pending for approval */
	private function notification_NCRraised($recordId,$ncrraisedid,$userlistid, $action){
	
		global $adb;
		$recordId = $recordId;
		$recordModel = Vtiger_Record_model::getInstanceById($recordId, "NCRS");
		$ncr_status = $recordModel->get('cf_6416');

		//$raised_for_id 				= $recordModel->get('cf_6420');
		$raised_for_info 			= Vtiger_Record_model::getInstanceById($ncrraisedid, "NCRRaised");
		$raised_for_person_info 	= Vtiger_Record_model::getInstanceById($userlistid, "UserList");
		$raised_for_email			= $raised_for_person_info->get('cf_3355');
		//$raised_for_email   		= $raised_for_info->get('cf_3355');
		$raised_for_first_name 		= $raised_for_person_info->get('name');
		//$raised_for_last_time  		= $raised_for_info->get('last_name');
		$for_department 			= Vtiger_DepartmentList_UIType::getDisplayValue($raised_for_info->get('cf_6508'));
		$for_location 				= Vtiger_LocationList_UIType::getDisplayValue($raised_for_info->get('cf_6510'));

		$loadUrl = $recordModel->getDetailViewUrl();
		$link  = "https://erp.globalink.net/".$loadUrl;
		
		if($action=='Pending'){			
		
			$smcreator_id 				= $recordModel->get('assigned_user_id');
			$ncr_created_by_info    	= Users_Record_Model::getInstanceById($smcreator_id, 'Users');
			$created_by_email      		= $ncr_created_by_info->get('email1');	
			$created_by_first_name  	= $ncr_created_by_info->get('first_name');
			$created_by_last_name   	= $ncr_created_by_info->get('last_name');
			$created_by_name   			= $created_by_first_name.' '.$created_by_last_name;

			$initiator_id            	= $recordModel->get('cf_1961');
			$initiator_info          	= Users_Record_Model::getInstanceById($initiator_id, 'Users');
			$initiator_email   		 	= $initiator_info->get('email1');
			$initiator_first_name    	= $initiator_info->get('first_name');
			$initiator_last_time     	= $initiator_info->get('last_name');

			$from  = "From: ".$created_by_name." <".$created_by_email.">";
			$to_arr[] = $initiator_email;

			$body  = '';
			$body .="<p>Dear&nbsp;".$initiator_first_name.",</p>";
			$body .="<p>As per your request, we initiated NCR against ".$raised_for_first_name." / ".$for_department." ".$for_location.".<br />";
			$body .="Please check and confirm general information of Non-Conformance for further corrective action.";
			$body .= "<br>Please see details on this link: <a href='$link'> Click To Follow Link </a></p>";
			$body .="<p>Regards,</p>";
			$body .="<p><strong>".$created_by_name."</strong></p>";
			$body .="<p><strong>Globalink Logistics - </strong></p>";

		}
		elseif($action=='Approved')
		{
			$initiator_id            	= $recordModel->get('cf_1961');
			$initiator_info          	= Users_Record_Model::getInstanceById($initiator_id, 'Users');
			$initiator_email   		 	= $initiator_info->get('email1');
			$initiator_first_name    	= $initiator_info->get('first_name');
			$initiator_last_time     	= $initiator_info->get('last_name');
			$initiator_name   			= $initiator_first_name.' '.$initiator_last_time;

			$from  = "From: ".$initiator_name." <".$initiator_email.">";

			$smcreator_id 				= $recordModel->get('assigned_user_id');
			$ncr_created_by_info    	= Users_Record_Model::getInstanceById($smcreator_id, 'Users');
			$created_by_email      		= $ncr_created_by_info->get('email1');	
			$created_by_first_name  	= $ncr_created_by_info->get('first_name');
			$created_by_last_name   	= $ncr_created_by_info->get('last_name');
			$created_by_name   			= $created_by_first_name.' '.$created_by_last_name;
			$to_arr[] = $initiator_email;

			$body  = '';
			$body .="<p>Dear&nbsp;".$created_by_first_name.",</p>";
			$body .="<p>Thanks for initiating NCR against ".$raised_for_first_name."  ".$for_department." / ".$for_location.".<br />";
			$body .="Please proceed for further corrective action.";
			$body .= "<br>Please see details on this link: <a href='$link'> Click To Follow Link </a></p>";
			$body .="<p>Regards,</p>";
			$body .="<p><strong>".$initiator_name."</strong></p>";
			$body .="<p><strong>Globalink Logistics - </strong></p>";
		}	
			
			
			
			$to_arr[] = $raised_for_email;
			$to = implode(',',$to_arr);

			$cc_arr[] = $created_by_email;
			
			$ncr_type = $recordModel->get('cf_6406');
			switch($ncr_type){
				case 'Job File Related':
				$cc_arr[]='s.mansoor@globalinklogistics.com';
				$cc_arr[]='d.israilov@globalinklogistics.com';
				$cc_arr[]='s.mehtab@globalinklogistics.com';
				break;
				case 'HR Related':
				$cc_arr[]='hr@globalinklogistics.com';
				$cc_arr[]='s.mehtab@globalinklogistics.com';
				break;
				case 'QHSE Related':
				$cc_arr[]='d.israilov@globalinklogistics.com';
				$cc_arr[]='s.mehtab@globalinklogistics.com';
				break;
				default:
			}
			//$cc_arr[]='t.malik@globalinklogistics.com';
			$cc = implode(',',$cc_arr);

			$headers  = "MIME-Version: 1.0" . "\n";
			$headers .= "Content-type:text/html;charset=UTF-8" . "\n";
			$headers .= $from . "\n";
			$headers .= 'Reply-To: '.$to.'' . "\n";
			$headers .= "CC:" . $cc . "\r\n";
			$subject  = "NCR For ".$recordModel->get('cf_6406');
			mail($to,$subject,$body,$headers);
			//return;
		}
}
