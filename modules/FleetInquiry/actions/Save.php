<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class FleetInquiry_Save_Action extends Vtiger_Save_Action {

	public function requiresPermission(\Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$moduleParameter = $request->get('source_module');
		if (!$moduleParameter) {
			$moduleParameter = 'module';
		}else{
			$moduleParameter = 'source_module';
		}
		$record = $request->get('record');
		$recordId = $request->get('id');
		if (!$record) {
			$recordParameter = '';
		}else{
			$recordParameter = 'record';
		}
		$actionName = ($record || $recordId) ? 'EditView' : 'CreateView';
        $permissions[] = array('module_parameter' => $moduleParameter, 'action' => 'DetailView', 'record_parameter' => $recordParameter);
		$permissions[] = array('module_parameter' => $moduleParameter, 'action' => $actionName, 'record_parameter' => $recordParameter);
		return $permissions;
	}
	
	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$record = $request->get('record');

		$nonEntityModules = array('Users', 'Events', 'Calendar', 'Portal', 'Reports', 'Rss', 'EmailTemplates');
		if ($record && !in_array($moduleName, $nonEntityModules)) {
			$recordEntityName = getSalesEntityType($record);
			if ($recordEntityName !== $moduleName) {
				throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
			}
		}
		return parent::checkPermission($request);
	}
	
	public function validateRequest(Vtiger_Request $request) {
		return $request->validateWriteAccess();
	}

	public function process(Vtiger_Request $request) {
		try {

			
			$recordModel = $this->saveRecord($request);

			

			if ($request->get('returntab_label')){
				$loadUrl = 'index.php?'.$request->getReturnURL();
			} else if($request->get('relationOperation')) {
				$parentModuleName = $request->get('sourceModule');
				$parentRecordId = $request->get('sourceRecord');
				$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentRecordId, $parentModuleName);
				//TODO : Url should load the related list instead of detail view of record
				$loadUrl = $parentRecordModel->getDetailViewUrl();
			} else if ($request->get('returnToList')) {
				$loadUrl = $recordModel->getModule()->getListViewUrl();
			} else if ($request->get('returnmodule') && $request->get('returnview')) {
				$loadUrl = 'index.php?'.$request->getReturnURL();
			} else {
				$loadUrl = $recordModel->getDetailViewUrl();
			}
			//append App name to callback url
			//Special handling for vtiger7.
			$appName = $request->get('appName');
			if(strlen($appName) > 0){
				$loadUrl = $loadUrl.$appName;
			}
			header("Location: $loadUrl");
		} catch (DuplicateException $e) {
			$requestData = $request->getAll();
			$moduleName = $request->getModule();
			unset($requestData['action']);
			unset($requestData['__vtrftk']);

			if ($request->isAjax()) {
				$response = new Vtiger_Response();
				$response->setError($e->getMessage(), $e->getDuplicationMessage(), $e->getMessage());
				$response->emit();
			} else {
				$requestData['view'] = 'Edit';
				$requestData['duplicateRecords'] = $e->getDuplicateRecordIds();
				$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

				global $vtiger_current_version;
				$viewer = new Vtiger_Viewer();

				$viewer->assign('REQUEST_DATA', $requestData);
				$viewer->assign('REQUEST_URL', $moduleModel->getCreateRecordUrl().'&record='.$request->get('record'));
				$viewer->view('RedirectToEditView.tpl', 'Vtiger');
			}
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}

	/**
	 * Function to save record
	 * @param <Vtiger_Request> $request - values of the record
	 * @return <RecordModel> - record Model of saved record
	 */
	public function saveRecord($request) {
		$recordModel = $this->getRecordModelFromRequest($request);
		if($request->get('imgDeleted')) {
			$imageIds = $request->get('imageid');
			foreach($imageIds as $imageId) {
				$status = $recordModel->deleteImage($imageId);
			}
		}	


			$recordId = $request->get('record');
			$old_fleet_inquiry_status = '';
			$assigned_user_id = 0;
			if(!empty($recordId))
			{
				$fleet_inquiry_info = Vtiger_Record_Model::getInstanceById($recordId, 'FleetInquiry');
	
				$old_fleet_inquiry_status = $fleet_inquiry_info->get('cf_3295');
				$assigned_user_id = $fleet_inquiry_info->get('assigned_user_id');
			
				//To Send Notification if Inquiry Status changed to Rejected
				$fleet_inquiry_status = $request->get('cf_3295');

				if($fleet_inquiry_status=='Rejected' && ($old_fleet_inquiry_status=='Pending' || $old_fleet_inquiry_status=='Accepted'))
				{
					$rejection_reason = $request->get('cf_6114');
					if(empty($rejection_reason))
					{					
						$_SESSION['rejection_reason'] = '1';			
						//$loadUrl = $recordModel->getEditViewUrl();
						//header("Location: $loadUrl");
						//exit;
					}
					else{
						$request->set('cf_6116','No');//Rejection Confirmed
						$current_user = Users_Record_Model::getCurrentUserModel();
						$fleet_coordinator = $current_user->get('first_name').' '.$current_user->get('last_name');
						
						$body  = "<p>Dear Dauren,</p>";  		
						$body .= "<p>Writing just to let you know that we are rejecting below fleet inquiry due to following reason.</p>";
						$body .="<p>".$rejection_reason."</p>";
						$body .="<pre>Please see details on this link:<a href='/index.php?module=FleetInquiry&view=Edit&record=".$recordId."&app=MARKETING'>Click To Follow Inquiry</a></p>";
						$body .="<p>Regards,</p>";
						$body .="<p><strong>".$fleet_coordinator."</strong>, RTD Coordinator </p>";
						$body .="<p><strong>Globalink Logistics - </strong>52, Kabanbai Batyr Street, 050010, Almaty, Kazakhstan&nbsp;<br />";
						
						$from = $current_user->get('email1');
						$to = "d.israilov@globalinklogistics.com;k.bhat@globalinklogistics.com;a.oriyashova@globalinklogistics.com";
						$email = 's.mehtab@globalinklogistics.com';
			
						$from = "From: ".$from." <".$from.">";
						$headers = "MIME-Version: 1.0" . "\n";
						$headers .= "Content-type:text/html;charset=UTF-8" . "\n";
						$headers .= $from . "\n";
						$headers .= 'Reply-To: '.$to.'' . "\n";
						$headers .= "CC:" . $email . "\r\n";
						
						mail($to,"Fleet Inquiry Rejection Notification",$body,$headers);	
					}
				}
			}
	
		

		$recordModel->save();
		if($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			$parentModuleModel = Vtiger_Module_Model::getInstance($parentModuleName);
			$parentRecordId = $request->get('sourceRecord');
			$relatedModule = $recordModel->getModule();
			$relatedRecordId = $recordModel->getId();
			if($relatedModule->getName() == 'Events'){
				$relatedModule = Vtiger_Module_Model::getInstance('Calendar');
			}

			$relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relatedModule);
			$relationModel->addRelation($parentRecordId, $relatedRecordId);
		}elseif($request->get('returnmodule') == 'Job' && $request->get('returnrecord')) {
			$parentModuleName = $request->get('returnmodule');
			$parentRecordId = $request->get('returnrecord');
			$parentModuleModel = Vtiger_Module_Model::getInstance($parentModuleName);
			$relatedModule = $recordModel->getModule();
			$relatedRecordId = $recordModel->getId();
			$relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relatedModule);
			$relationModel->addRelation($parentRecordId, $relatedRecordId);
		}


		$this->savedRecordId = $recordModel->getId();

		$recordId = $request->get('record');
		if(!empty($recordId)) {
			$fleet_inquiry_status = $request->get('cf_3295');
			
			$current_user = Users_Record_Model::getCurrentUserModel();
			$fleet_coordinator_arr = array('1312','286','64','796','386','860', '30','1033','1070','465','1086','1039', '816','1176','1215','795','250','1455','1070','1521', '1513','1587','1032','1405','1695');
			
			if($fleet_inquiry_status=='Accepted' && $old_fleet_inquiry_status=='Pending' && in_array($current_user->getId(), $fleet_coordinator_arr))
			{
				
				$db = PearDatabase::getInstance();
				$sql = "SELECT * FROM `vtiger_crmentityrel` WHERE module=? AND relcrmid=? AND relmodule=? limit 1";
				$params = array('Job', $recordId, 'FleetInquiry');
				$result = $db->pquery($sql, $params);
				$row = $db->fetch_array($result);
				$job_id = $row['crmid'];
				if(!empty($job_id))
				{
					$sql_task = "SELECT * FROM `vtiger_jobtask` WHERE job_id=? AND user_id=?";
					$params_task =  array($job_id, $current_user->getId());
					$result_task = $db->pquery($sql_task, $params_task);
					if($db->num_rows($result_task)==0)
					{						
						$new_id = $db->getUniqueId('vtiger_crmentity');					
						$db->pquery("INSERT INTO vtiger_crmentity SET crmid = '".$new_id."', smcreatorid ='".$assigned_user_id."' ,smownerid ='".$current_user->getId()."', setype = 'JobTask'");
						$db->pquery("INSERT INTO vtiger_jobtask SET jobtaskid = '".$new_id."', job_id = '".$job_id."', name = 'FLT', user_id ='".$current_user->getId()."', job_owner = '0'");
						$db->pquery("INSERT INTO vtiger_crmentityrel SET crmid = '".$job_id."', module = 'Job', relcrmid = '".$new_id."', relmodule = 'JobTask'");
					}
					
				}
			}
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
			$recordModel->set('id', $recordId);
			$recordModel->set('mode', 'edit');
		} else {
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$recordModel->set('mode', '');
		}

		$fieldModelList = $moduleModel->getFields();
		foreach ($fieldModelList as $fieldName => $fieldModel) {
			$fieldValue = $request->get($fieldName, null);
			$fieldDataType = $fieldModel->getFieldDataType();
			if($fieldDataType == 'time' && $fieldValue !== null){
				$fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
			}
			if($fieldValue !== null) {
				if(!is_array($fieldValue) && $fieldDataType != 'currency') {
					$fieldValue = trim($fieldValue);
				}
				$recordModel->set($fieldName, $fieldValue);
			}
		}
		return $recordModel;
	}
}
