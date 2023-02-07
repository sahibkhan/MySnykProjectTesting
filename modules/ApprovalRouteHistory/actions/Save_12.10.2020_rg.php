<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class ApprovalRouteHistory_Save_Action extends Vtiger_Save_Action {

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

			// MOTIW CODE START
	global $adb;
		
	$motiwid = $request->get('cf_6784');
	$currentUserModel = Users_Record_Model::getCurrentUserModel();
	$current_user_id = $currentUserModel->get('id');
	$routeapprove = $request->get('routeapprove');
			
	$sql_a = "SELECT  * FROM vtiger_approvalroutehistorycf INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_approvalroutehistorycf.approvalroutehistoryid WHERE vtiger_crmentity.deleted=0 AND vtiger_approvalroutehistorycf.cf_6784 ='".$motiwid."' AND vtiger_approvalroutehistorycf.cf_6788 = '".$current_user_id."' AND cf_6792 = 'inline'"; 
	$result_a = $adb->pquery($sql_a);
	
	$tSequence=$adb->query_result($result_a,0,'cf_6790');	
	$c_approvalroutehistoryid=$adb->query_result($result_a,0,'approvalroutehistoryid');
		
 			if(empty($routeapprove)){
			
						$request->set('cf_6790',$tSequence);
						$request->set('cf_6792','inline');
						if($current_user_id == 1122){ $request->set('cf_7118',1); }

						$sql = "SELECT  * FROM vtiger_approvalroutehistorycf INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_approvalroutehistorycf.approvalroutehistoryid WHERE vtiger_crmentity.deleted=0 AND vtiger_approvalroutehistorycf.cf_6784 ='".$motiwid."' AND cf_6790 >= '".$tSequence."'"; 
						$result = $adb->pquery($sql);
						$noofrow = $adb->num_rows($result);
						
  							$final = array();
								$n = 2;
								for($i=0; $i<$noofrow ; $i++) {
										$approvalroutehistoryid=$adb->query_result($result,$i,'approvalroutehistoryid');
										$subject=$adb->query_result($result,$i,'cf_6786');
										$userid=$adb->query_result($result,$i,'cf_6788');
										$sequence=$adb->query_result($result,$i,'cf_6790') + 1;
										$status=$adb->query_result($result,$i,'cf_6792');
										$currentDateTime=$adb->query_result($result,$i,'cf_6794');


										$test = array();
										$saverelatedrecord = new Vtiger_Save_Action();
										$related_request = new Vtiger_Request($test);
										$related_request->set("__vtrftk",$request->get("__vtrftk"));
										$related_request->set("module", 'ApprovalRouteHistory');
										$related_request->set("appName",$request->get("appName"));
										$related_request->set("action","Save");
										$related_request->set("record",$approvalroutehistoryid);

										$related_request->set("name",$subject);
										$related_request->set("cf_6784",$motiwid);
										$related_request->set("cf_6786",$subject);
										$related_request->set("cf_6788",$userid); 
										$related_request->set("cf_6790",$sequence);
										$related_request->set("cf_6792",'pending');
										$related_request->set("cf_6794",$currentDateTime);
										$saverelatedrecord->saveRecord($related_request);


/*  										$vtigerSaveAction = new Vtiger_Save_Action();
										$requestNew = new Vtiger_Request();
										$requestNew->set("module","ApprovalRouteHistory");
										$requestNew->set("action","Save");
										$requestNew->set("record",$approvalroutehistoryid);
										$requestNew->set("name",$subject);
										$requestNew->set("cf_6784",$motiwid);
										$requestNew->set("cf_6786",$subject);
										$requestNew->set("cf_6788",$userid); 
										$requestNew->set("cf_6790",$sequence);
										$requestNew->set("cf_6792",'pending');
										$requestNew->set("cf_6794",$currentDateTime);
										$vtigerSaveAction->saveRecord($requestNew); */
								
								}
			}


			if(!empty($routeapprove)){
				
				$sql = "UPDATE vtiger_approvalroutehistorycf SET cf_6792 = 'approved' WHERE vtiger_approvalroutehistorycf.cf_6784 ='".$motiwid."' AND vtiger_approvalroutehistorycf.cf_6788 = '".$current_user_id."'";
				$result = $adb->pquery($sql);
				
				$tSequence = $tSequence+1;
			
				$request->set('cf_6790',$tSequence);
				$request->set('cf_6792','inline');
		
				$sql = "SELECT  * FROM vtiger_approvalroutehistorycf INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_approvalroutehistorycf.approvalroutehistoryid WHERE vtiger_crmentity.deleted=0 AND vtiger_approvalroutehistorycf.cf_6784 ='".$motiwid."' AND cf_6790 >= '".$tSequence."'"; 
				$result = $adb->pquery($sql);
				$noofrow = $adb->num_rows($result);
				 
					$final = array();
					$n = 2;
					for($i=0; $i<$noofrow ; $i++) {
		
						$approvalroutehistoryid=$adb->query_result($result,$i,'approvalroutehistoryid');
						$subject=$adb->query_result($result,$i,'cf_6786');
						$userid=$adb->query_result($result,$i,'cf_6788');
						$sequence=$adb->query_result($result,$i,'cf_6790')+1;
						$status=$adb->query_result($result,$i,'cf_6792');
						$currentDateTime=$adb->query_result($result,$i,'cf_6794');

						$test = array();
						$saverelatedrecord = new Vtiger_Save_Action();
						$related_request = new Vtiger_Request($test);
						$related_request->set("__vtrftk",$request->get("__vtrftk"));
						$related_request->set("module", 'ApprovalRouteHistory');
						$related_request->set("appName",$request->get("appName"));
						$related_request->set("action","Save");
						$related_request->set("record",$approvalroutehistoryid);

						$related_request->set("name",$subject);
						$related_request->set("cf_6784",$motiwid);
						$related_request->set("cf_6786",$subject);
						$related_request->set("cf_6788",$userid); 
						$related_request->set("cf_6790",$sequence);
						$related_request->set("cf_6792",'pending');
						$related_request->set("cf_6794",$currentDateTime);
						$saverelatedrecord->saveRecord($related_request);

					

/* 		
						$vtigerSaveAction = new Vtiger_Save_Action();
						$requestNew = new Vtiger_Request();
						$requestNew->set("module","ApprovalRouteHistory");
						$requestNew->set("action","Save");
						$requestNew->set("record",$approvalroutehistoryid);
						$requestNew->set("name",$subject);
						$requestNew->set("cf_6784",$motiwid);
						$requestNew->set("cf_6786",$subject);
						$requestNew->set("cf_6788",$userid); 
						$requestNew->set("cf_6790",$sequence);
						$requestNew->set("cf_6792",'pending');
						$requestNew->set("cf_6794",$currentDateTime);
						$vtigerSaveAction->saveRecord($requestNew); */
				
					
					}
			  
			  
		    }


			// MOTIW CODE END

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
		$recordModel->save();
		if($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			if(empty($parentModuleName)){
				$parentModuleName = $request->get('returnmodule');
			}
			$parentModuleModel = Vtiger_Module_Model::getInstance($parentModuleName);
			$parentRecordId = $request->get('sourceRecord');
			if(empty($parentRecordId)){
				$parentRecordId = $request->get('returnrecord');				
			}
			$relatedModule = $recordModel->getModule();
			$relatedRecordId = $recordModel->getId();
			if($relatedModule->getName() == 'Events'){
				$relatedModule = Vtiger_Module_Model::getInstance('Calendar');
			}

			$relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relatedModule);
			$relationModel->addRelation($parentRecordId, $relatedRecordId);
		}
		$this->savedRecordId = $recordModel->getId();
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
