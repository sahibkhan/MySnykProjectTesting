<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
//error_reporting(E_ALL);
//ini_set('display_errors', 'on');
class InitialReport_Save_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();
		$moduleName = $request->getModule();
		$record = $request->get('record');
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
			 throw new AppException('LBL_PERMISSION_DENIED');	
			}
		 }
		
		if(!Users_Privileges_Model::isPermitted($moduleName, 'Save', $record)) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
	}

	public function process(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();
		if($request->get('cf_4145')){
		$qhse = implode(' |##| ', $request->get('cf_4145'));
		$request->set('cf_4145', $qhse);
		}else{
		$request->set('cf_4145', '');	
		}
		if($request->get('direct_supervisor')){
		$request->set('cf_4147', $request->get('direct_supervisor'));
		}else{
	   	$request->set('cf_4147', '');
		}
		$recordModel = $this->saveRecord($request);
		$recordId = $recordModel->get('record_id');
		if($recordId){
		 $qry = $db->pquery('select emailSend from vtiger_initialreport where initialreportid = ?', array($recordId));		 
		 if($db->num_rows($qry)  > 0){
			  $emailSend = $db->fetch_array($qry);
			  //$emailSend = $db->query_result($qry, 0, 'emailSend');
		  }	
		}
	
	
		if($request->get('cf_4149') == "on" && $emailSend[0] != "Yes")
		{
			$qhseMember = $request->get('cf_4145');
			$fleetMember = $request->get('cf_4147');
			if($qhseMember)
			{
				 $email_qhse = array();
				 if(strstr($qhseMember, ' |##| ') ){
					$qhseMember = explode(' |##| ', $qhseMember);
					foreach($qhseMember as $value)
				 	{
						$result = $db->pquery('SELECT email1 FROM vtiger_users WHERE id = ?', array($value));
						if($db->num_rows($result)) 
						{
						$email_qhse[] = $db->query_result($result, 0, 'email1');
						}
				 	}
				  
				 }else{
					$qhseMember	= $qhseMember;
					$result = $db->pquery('SELECT email1 FROM vtiger_users WHERE id = ?', array($qhseMember));
						if($db->num_rows($result)) 
						{
						$email_qhse[] = $db->query_result($result, 0, 'email1');
						}
				 }
				 
		    }

		if($fleetMember)
		{
			$db = PearDatabase::getInstance();
			$result = $db->pquery('SELECT email1 FROM vtiger_users WHERE id = ?', array($fleetMember));
			if($db->num_rows($result)) 
			{
			$email_fleet = $db->query_result($result, 0, 'email1');
			}
		}
		
		$to = implode(",", $email_qhse).','.$email_fleet;
		
		global $site_URL;	
	    $loadUrl = $recordModel->getDetailViewUrl();
		$fullURL = $site_URL."/".$loadUrl;
		
		$reference = "This is an autogenerated Email. Please do not reply to this email<br><br>";
        $title  = "First Generic Report Submitted";
	    $reference .= "$title<br>";
        
	    $reference .= "<br>\n\rPlease find more information about first generic report by visiting the <a href='".$fullURL."'><strong>link</strong></a><br><br>";
	   
	  	    
	   $message = $reference;
       $message .= "<strong>--------------------------------------------------</strong><br><br>";  
		
		
	    $subject = "First Generic Report Submitted";
		$headers = "MIME-Version: 1.0" . "\r\n";
		$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
		$headers .= 'From: no-reply@globalink.net' . "\r\n" .
                    'Bcc: shah.m86@gmail.com' . "\r\n" ;
         if(mail($to, $subject,$message, $headers)){
		  $db = PearDatabase::getInstance();
		  $result = $db->pquery('update vtiger_initialreport set emailSend = "Yes" WHERE initialreportid = ?', array($recordId));
		  }
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
}
