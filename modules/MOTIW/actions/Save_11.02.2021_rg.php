<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class MOTIW_Save_Action extends Vtiger_Save_Action {

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
		
		global $adb, $current_user;
		

		// cf_6778 Attachment field
		$result = Vtiger_Util_Helper::transformUploadedFiles($_FILES, true); 
		$_FILES = $result['cf_6778'];


		$finaldoc = $request->get('finaldoc');
		$finalrecord = $request->get('record');
		//echo "<pre>"; print_r($request); exit;
		if(!empty($_FILES["cf_6778"]["name"][0]) && isset($_REQUEST['firstdoc'])){
		   $firstdocid = $adb->getUniqueID("vtiger_crmentity");
		   if(empty($finalrecord)){
		   $firstdocnewid = $firstdocid+2;
		   } else {
		   $firstdocnewid = $firstdocid+1;   
		   }
		   $firstdoc[] = $firstdocnewid;
		}
		
		end($finaldoc);
		$max = key($finaldoc); //Get the final key as max!
		for($i = 0; $i < $max; $i++)
		{
			if(empty($finaldoc[$i]))
			{
				$finaldoc[$i] = '1123';
			}
		}
		ksort($finaldoc);
		
		if(is_array($firstdoc) && is_array($finaldoc)){
		$revcheck = array_merge($finaldoc,$firstdoc); 
		} elseif(is_array($firstdoc)){
		$addArray = array("0"=>"1122");
		$revcheck = array_merge($addArray,$firstdoc); 
		} elseif(is_array($finaldoc)){
		$revcheck = $finaldoc;
		} else { $revcheck = array("0"=>"1122"); }
		
		$revcheck = implode(",",$revcheck);
		
		$request->set('cf_7056',$revcheck);
			

/* 		$result = Vtiger_Util_Helper::transformUploadedFiles($_FILES, true);
		$_FILES = $result['cf_6778']; */
/* 
		echo "here 2";
		echo "<pre>";
		print_r($_FILES); exit;

 */
		
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
		
		global $adb, $current_user;
		$currentuserid = $current_user->id;
		
		$recordModel = $this->getRecordModelFromRequest($request);
		if($request->get('imgDeleted')) {
			$imageIds = $request->get('imageid');
			foreach($imageIds as $imageId) {
				$status = $recordModel->deleteImage($imageId);
			}
		}
		$recordModel->set('cf_7046','Pending');
		$recordModel->save();
		$sourcerecordid = $recordModel->get('id');
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
		}
		
		$this->savedRecordId = $recordModel->getId();
		
		$recordId = $request->get('record');
		
		$date_var = date("Y-m-d H:i:s");
	    $currentDateTime = $adb->formatDate($date_var, true);
		
		if(empty($recordId)) {
			
			$linkModule1='ApprovalRouteHistory';
			$recordModel1 = Vtiger_Record_Model::getCleanInstance($linkModule1);
			
			$recordModel1->set('assigned_user_id', $userId); // $curid= Assigned User id
			$recordModel1->set('mode', 'create');
			$recordModel1->set("name",'');
			$recordModel1->set('cf_6784', $recordModel->getId());
			$recordModel1->set("cf_6786",'');
			$recordModel1->set("cf_6788",$currentuserid);
			$recordModel1->set("cf_6790",1);
			$recordModel1->set("cf_6792",'inline');		
			$recordModel1->set("cf_6794",$currentDateTime);
			$recordModel1->save();
					
			$request->set('record',$sourcerecordid);
		    
		
		}

		// Save Officer
		// Adding partie to officer field. This is required for reporting.
		if ($recordId > 0){

			$sqlApprovalParties = "SELECT vtiger_users.first_name, vtiger_users.last_name
		 FROM vtiger_approvalroutehistorycf 
		 INNER JOIN vtiger_users ON vtiger_users.id = vtiger_approvalroutehistorycf.cf_6788			
		 WHERE vtiger_approvalroutehistorycf.cf_6784 = $recordId
		 GROUP BY vtiger_approvalroutehistorycf.cf_6788";
		 $row_parties = $adb->pquery($sqlApprovalParties);
		 $noofrow = $adb->num_rows($row_parties);
		 $partieNames = '';

		 for($i=0; $i<$noofrow ; $i++) {				
			 $partieNames .= $adb->query_result($row_parties, $i, 'last_name').' '. $adb->query_result($row_parties, $i, 'first_name') .', ';
		 }
	 
		 $sqlOfficer = "UPDATE `vtiger_motiwcf` SET `cf_7158` = '$partieNames' WHERE `motiwid` = $recordId LIMIT 1";
		 $resultOfficer = $adb->pquery($sqlOfficer);
	 }
	 	

		$this->updateStatus($request);
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
	
	public function updateStatus(Vtiger_Request $request){
		
		global $adb;
		$adb = PearDatabase::getInstance();
		$recordId = $request->get('record');
		$groupid = $request->get('cf_6366');
		$companyType = $request->get('cf_7346');

		$module = $request->get('module');
		$action = $request->get('action');
		
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		
		$currentUserId = $currentUserModel->get('id');
		$currentAddressCountry = $currentUserModel->get('address_country'); 
		$currentUserName = $currentUserModel->get('user_name');
		$currentUserLocationId = $currentUserModel->get('location_id');
		$currentUserCompanyId = $currentUserModel->get('company_id');
		$currentUserCurrencyCode = $currentUserModel->get('currency_code');
		$currentUserEmail = $currentUserModel->get('email1');
		
		$sql = "SELECT  cf_3385 FROM vtiger_userlistcf WHERE cf_3355='".$currentUserEmail."'";
		$result = $adb->pquery($sql);
		$rows = $adb->num_rows($result);
		$userlistheadid=$adb->query_result($result,0,'cf_3385');
		$userlistheadtitle=$adb->query_result($result,0,'cf_3341');
		
		$sql = "SELECT  cf_3355 FROM vtiger_userlistcf WHERE userlistid='".$userlistheadid."'";
		$result = $adb->pquery($sql);
		$rows = $adb->num_rows($result);
		$userlistheademail=$adb->query_result($result,0,'cf_3355');
		
		$sql = "SELECT id FROM vtiger_users WHERE email1='".$userlistheademail."'";
		$result = $adb->pquery($sql);
		$rows = $adb->num_rows($result);
		$usersheadid=$adb->query_result($result,0,'id');
		
		$recordLocation = Vtiger_Record_Model::getInstanceById($currentUserLocationId, 'Location');
		$location = $recordLocation->get('cf_1559');
		
		$sql = "SELECT id FROM vtiger_users WHERE (title='Chief Accountant' OR title='Branch Finance Manager') AND user_name='".$currentUserName."'";
		$result = $adb->pquery($sql);
		$rows = $adb->num_rows($result);
		$usersaccountantid=$adb->query_result($result,0,'id');
		
		$currentUserDepartmentId = $currentUserModel->get('department_id');
		$recordDepartment = Vtiger_Record_Model::getInstanceById($currentUserDepartmentId, 'Department');
		$department = $recordDepartment->get('cf_1542');
		
		
		$sql = "SELECT  * FROM vtiger_approvalroutehistorycf 
						INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_approvalroutehistorycf.approvalroutehistoryid 
						WHERE vtiger_approvalroutehistorycf.cf_6784='".$recordId."' 
						AND vtiger_crmentity.deleted=0";
		$result = $adb->pquery($sql);
		$rows = $adb->num_rows($result);
		$approvalroutehistoryid=$adb->query_result($result,0,'approvalroutehistoryid');
			
		if($rows < 2){
		
		$sql_g = "SELECT  * 
							FROM vtiger_approvalroutepartiescf 
							INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_approvalroutepartiescf.approvalroutepartiesid 
							WHERE vtiger_crmentity.deleted=0 AND vtiger_approvalroutepartiescf.cf_6834='".$groupid."'"; 
		$result_g = $adb->pquery($sql_g);
		$noofrow = $adb->num_rows($result_g);
		 
			$final = array();
			$n = 2;
			for($i=0; $i<$noofrow ; $i++) {
				
				$l=$i+1;
				
				$groupid=$adb->query_result($result_g,$i,'cf_6834');
				$groupname=$adb->query_result($result_g,$i,'cf_6834'); 
				$description=$adb->query_result($result_g,$i,'cf_6834');
				$userid=$adb->query_result($result_g,$i,'cf_6820');
				$sequence=$adb->query_result($result_g,$i,'cf_6822');
				
				$status = ($i == 0)? 'pending':'pending';
				$date_var = date("Y-m-d H:i:s");
	            $currentDateTime = $adb->formatDate($date_var, true);
				
				$linkModule1='ApprovalRouteHistory';
				$recordModel1 = Vtiger_Record_Model::getCleanInstance($linkModule1);
				$recordModel1->set('id', $approvalroutehistoryid);
				$recordModel1->set('mode', 'edit');
				$recordModel1->set("name",$description);
				$recordModel1->set("cf_6786",$groupid);
				$recordModel1->save();
								
					
				    /* almaty office id */
					
					if($sequence == 1 && strpos($userlistheadtitle, 'Manager') !== false){
						    continue;
					} elseif($sequence == 1 && $usersheadid == 1122){
						    continue;
					} elseif($sequence == 1 && $usersheadid == 60){
						    continue;
					} elseif($sequence == 1 && $usersheadid == 123){
						// Exception for Feroz, as he has two accounts
						$userid = 1038;

					} elseif($location == 'ALA' && ($groupid == 'Freight Forwarding' || $groupid == 'Administrative') && $sequence == 1){

					        $userid=$usersheadid;
										
					} elseif($location == 'ALA' && $groupid == 'Customs Brokerage' && $sequence == 1){
						
						    $userid = $userid;
					
					} elseif($groupid == 'Customs Brokerage (Bishkek)' && $sequence == 1){
						
						    $userid = $userid;
					
					} elseif($currentUserCurrencyCode == 'KZT' && $sequence == 1){
						
						    $userid=$usersheadid;
						
					} elseif($sequence == 1) {
						
						    $userid=$usersheadid;
					
					} elseif ($sequence == 3){
						
								// Agent should be assigned to Zhanat. Others to Khadisha
								if ($companyType == 'Agent') $userid = 1493; else $userid = $userid;
						
					}
					 elseif ($sequence == 4){
						
						    								// If location is Kazakhstan
								if ($currentUserCompanyId == 85757){
									// Kazakhstan / Gaukhar Moldakanova
									$userid = 419;
								} else if ($currentUserCompanyId == 85766){
									// MOSCOW / Inna Nabokova
									$userid = 1201;
								} else if ($currentUserCompanyId == 85768){
									// MOSCOW / Natalia Nichiporova
									$userid = 853;
								} else if ($currentUserCompanyId == 85770){
									// Tashkent / Nisso Fazylova
									$userid = 1147;
								} else if ($currentUserCompanyId == 85767){
									// Dushanbe / Yilia Amirova
									$userid = 702;
								} else if ($currentUserCompanyId == 85769){
									// Kiev / Vitalii Boiko
									$userid = 706;								
								} else if ($currentUserCompanyId == 205751){
									// Erevan / Margaryan
									$userid = 182;								
								} else if ($currentUserCompanyId == 258701){
									// Tbilisi / Zurab
									$userid = 1191;
								}
						    // $userid=$usersaccountantid;
								// $userid = 223;
								
					} else {

								$userid = $userid;
						
					}
			
					/*$sql_i = "SELECT  * FROM vtiger_approvalroutehistorycf INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_approvalroutehistorycf.approvalroutehistoryid WHERE vtiger_approvalroutehistorycf.cf_6784='".$recordId."' AND vtiger_approvalroutehistorycf.cf_6788 = '".$userid."' AND vtiger_crmentity.deleted=0";
					$result_i = $adb->pquery($sql_i);
					$rows_i = $adb->num_rows($result_i);
					if($rows_i > 0){
							continue;
					}*/

					
					$linkModule2='ApprovalRouteHistory';
					$recordModel2 = Vtiger_Record_Model::getCleanInstance($linkModule2);
					
					$recordModel2->set('assigned_user_id', $currentUserId); // $curid= Assigned User id
					$recordModel2->set('mode', 'create');
					$recordModel2->set("name",$description);
					$recordModel2->set('cf_6784',$recordId);
					$recordModel2->set("cf_6786",$groupid);
					$recordModel2->set("cf_6788",$userid);
					$recordModel2->set("cf_6790",$n);
					$recordModel2->set("cf_6792",$status);		
					$recordModel2->set("cf_6794",$currentDateTime);
					$recordModel2->save();
					
				$n++;
									
			}
			
		}
				
	}
}
