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
		// cf_7444
		$result = Vtiger_Util_Helper::transformUploadedFiles($_FILES, true); 
		$_FILES = $result['cf_6778']; 

		$scanDocs = $request->get('scanDocs');
		if ($scanDocs){
			$scanDocsIds = implode(',', $scanDocs);
			$request->set('cf_7774', $scanDocsIds);
		} else {
			$request->set('cf_7774', '');
		}


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
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		
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

			// Update current reviewer
			$currentReviewer = $currentUserModel->get('first_name').' '. $currentUserModel->get('last_name');
			$queryRevisionBy = "UPDATE `vtiger_motiwcf` SET `cf_7040` = ? WHERE `motiwid` = ? LIMIT 1";
			$resultRevisionBy = $adb->pquery($queryRevisionBy, array($currentReviewer, $recordModel->getId()));
		
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
		// $this->notifyUsers($request);
		
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
	

	public function getAccountant($currentUserCompanyId){

		if ($currentUserCompanyId == 85757){
			// Kazakhstan / Gaukhar Moldakanova
			$userid = 419;
		} else if ($currentUserCompanyId == 85766 || $currentUserCompanyId == 1763699){
			// MOSCOW / Tatyana Shirokova
			$userid = 218;
		} else if ($currentUserCompanyId == 85768){
			// Turkmenistan / Svetlana Ivleyeva
			$userid = 409;
		} else if ($currentUserCompanyId == 85770){
			// Tashkent / Zlata 
			$userid = 1241;
		} else if ($currentUserCompanyId == 85767){
			// Dushanbe / Yilia Amirova
			$userid = 702;
		} else if ($currentUserCompanyId == 85769){
			// Kiev / Julia Misevra
			$userid = 1490;								
		} else if ($currentUserCompanyId == 205751){
			// Erevan / Margaryan
			$userid = 182;								
		} else if ($currentUserCompanyId == 258701){
			// Tbilisi / Zurab
			$userid = 1191;
		} else if ($currentUserCompanyId == 85761){
			// Baku / Alla Dagadaeva
			$userid = 242;
		} else if ($currentUserCompanyId == 85764){
			// Bishkek / Gulzat Zhumakadyrova
			$userid = 1252;
		}
		return $userid;
	}


	public function updateStatus(Vtiger_Request $request){
		
		global $adb;
		$adb = PearDatabase::getInstance();
		$APPROVAL_ROUTE_HISTORY = 'ApprovalRouteHistory';
		$CEO_APPROVER = 60;
		$LEGAl_HEAD_APPROVER = 1122; // Timur
		$SENIOR_LAWYER_APPROVER = 788; // Ayana
		$LEGAl_APPROVER = 789; // Ana
		$LEGAl_ASSISTANT_APPROVER = 1542; // Eldana

		$FINANCE_APPROVER = 1410; // Khadisha
		$FINANCE_VENDOR_APPROVER = 1493; // Zhanat
		$CSM_AGENT_APPROVER = 66; // Zhanna Kaynarbayeva
		$CSM_HEAD_AGENT_APPROVER = 6; // Aftab Ahmed
		$MOSCOW_SUPERVISOR_APPROVER = 705; // Turbold

		// Branches
		$BRANCH_ATYRAY_MANAGER = 1038;

		$isVendorApproverAdded = false;
		
		// Allowed Accounts for Tahskent branch. In this case local accountant will be added in approval route
		$allowedAccounts = array(103036, 2112761);

		$recordId = $request->get('record');
		$recordCreatorId = $request->get('assigned_user_id');

		$AgreementType = $request->get('cf_6366');
		$companyType = $request->get('cf_7346');
		$coordinatorLocationId = $request->get('cf_6766');
		$accountId = $request->get('cf_6362');

				
		// Getting User Info
		$currentUserModel = Users_Record_Model::getCurrentUserModel();		
		$currentUserId = $currentUserModel->get('id');
		$CONTRACT_OWNER_ID = $currentUserId;

		$currentUserCompanyId = $currentUserModel->get('company_id');
		// echo 'currentUserCompanyId = ' . $currentUserCompanyId; exit;
		$currentUserEmail = $currentUserModel->get('email1');
		
		// Getting current user info
		$sql = "SELECT  cf_3385 FROM vtiger_userlistcf WHERE cf_3355='".$currentUserEmail."'";
		$result = $adb->pquery($sql);
		$rows = $adb->num_rows($result);
		$currentUserHeadId = $adb->query_result($result,0,'cf_3385');

		// Getting current user head info
		$sql = "SELECT cf_3355 FROM vtiger_userlistcf WHERE userlistid = ?";
		$result = $adb->pquery($sql, array($currentUserHeadId));
		$userHeadEmail = $adb->query_result($result,0,'cf_3355');

		// Getting user head id from navite module
		$sql = "SELECT id FROM vtiger_users WHERE email1 = ?";
		$result = $adb->pquery($sql, array($userHeadEmail));
		$userHeadNaviteId=$adb->query_result($result,0,'id');
	

		// Get current contract approval route parties
		$sql = "SELECT  * FROM vtiger_approvalroutehistorycf 
						INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_approvalroutehistorycf.approvalroutehistoryid 
						WHERE vtiger_approvalroutehistorycf.cf_6784 = ? AND vtiger_crmentity.deleted = 0";
		$result = $adb->pquery($sql, array($recordId));
		$rows = $adb->num_rows($result);
		$approvalroutehistoryid = $adb->query_result($result,0,'approvalroutehistoryid');
			
		// If approval route is not defined
		if($rows < 2){
				
			// 1122 - Timur Makarov
			// 788  - Ayana
			// 1410 - Khadisha
			// 1493 - Zhanat
			// 419 - Gaukhar

			// Define THE Approval Route

				$n = 2;	
				$date_var = date("Y-m-d H:i:s");
				$currentDateTime = $adb->formatDate($date_var, true);
				$accountantID = $this->getAccountant($currentUserCompanyId);

				// Exception for Uzbekistan. Use Kazakhstan accountant manager
				$KazakhstanAccountantHeadId = $this->getAccountant(85757);
				$APPROVAL_ROUTES = array();

				// Standard
				if ($AgreementType == 'Glk standard contracts without changes and any booking orders, appendices, additional agreements, amendments'){
					$APPROVAL_ROUTES[] = array("type" => "Legal Assistant", "value" => $LEGAl_ASSISTANT_APPROVER);
				}

				// Customer Format Contract | Vendor Format Contract | Partner Format Contract
				if ($AgreementType == 'Customer Format Contract' || $AgreementType == 'Vendor Format Contract' || $AgreementType == 'Partner Format Contract'){

					$APPROVAL_ROUTES[] = array("type" => "LegalGM", "value" => $LEGAl_HEAD_APPROVER);
					
				} else if ($AgreementType == 'GLK Format Contract with Changes'){

					$APPROVAL_ROUTES[] = array("type" => "SeniorLawer", "value" => $SENIOR_LAWYER_APPROVER);	

				} else if ($AgreementType == 'Signed,Scanned and Non-Negotiable Contract'){

					if (!in_array($userHeadNaviteId, array($LEGAl_HEAD_APPROVER, $CEO_APPROVER))){
						if ($userHeadNaviteId == 123) $userHeadNaviteId = $BRANCH_ATYRAY_MANAGER;
						$APPROVAL_ROUTES[] = array("type" => "GM", "value" => $userHeadNaviteId);
					}
					$APPROVAL_ROUTES[] = array("type" => "LegalGM", "value" => $LEGAl_HEAD_APPROVER);

				}
							
				
				if ($companyType == 'Customer'){
					$APPROVAL_ROUTES[] = array("type" => "FD", "value" => $FINANCE_APPROVER);

				} else if ($companyType == 'Vendor'){
					$APPROVAL_ROUTES[] = array("type" => "FD", "value" => $FINANCE_VENDOR_APPROVER);
					$isVendorApproverAdded = true;
				}/*  else {
					$APPROVAL_ROUTES[] = array("type" => "FD", "value" => $FINANCE_VENDOR_APPROVER);
				} */
				

				if ($AgreementType != 'Glk standard contracts without changes and any booking orders, appendices, additional agreements, amendments'){
					// For Tashkent branch
					if ($coordinatorLocationId == 85831 && (in_array($accountId, $allowedAccounts))){
						$APPROVAL_ROUTES[] = array("type" => "Accountant", "value" => $accountantID);
					} else if ($coordinatorLocationId == 85831 && (!in_array($accountId, $allowedAccounts))){
						$APPROVAL_ROUTES[] = array("type" => "Accountant", "value" => $KazakhstanAccountantHeadId);
					} else if ($coordinatorLocationId != 85831) {
						$APPROVAL_ROUTES[] = array("type" => "Accountant", "value" => $accountantID);

						if ($companyType == 'Agency (Bilateral Partnership)'){
							$APPROVAL_ROUTES[] = array("type" => "CSM", "value" => $CSM_AGENT_APPROVER);
							$APPROVAL_ROUTES[] = array("type" => "CSM", "value" => $CSM_HEAD_AGENT_APPROVER);
							$APPROVAL_ROUTES[] = array("type" => "FD", "value" => $FINANCE_VENDOR_APPROVER);
							
						} else if ($companyType == 'Vendor') {
							if ($isVendorApproverAdded == false){
								$APPROVAL_ROUTES[] = array("type" => "FD", "value" => $FINANCE_VENDOR_APPROVER);
							}
						}

					}
					// Adding Coordinator and Legal Asistant to each approval route
					$APPROVAL_ROUTES[] = array("type" => "Coordinator", "value" => $CONTRACT_OWNER_ID);
					$APPROVAL_ROUTES[] = array("type" => "Legal Assistant", "value" => $LEGAl_ASSISTANT_APPROVER);
				}

				// Add Turbod for MOSCOW relevant companies
				if ($currentUserCompanyId == 85766 || $currentUserCompanyId == 1763699){
					$APPROVAL_ROUTES[] = array("type" => "Moscow Supervisor", "value" => $MOSCOW_SUPERVISOR_APPROVER);	
				}

				// For standard always add LD Assistant
				if ($AgreementType == 'Glk standard contracts without changes and any booking orders, appendices, additional agreements, amendments'){

					if ($companyType == 'Agency (Bilateral Partnership)'){
						$APPROVAL_ROUTES[] = array("type" => "CSM", "value" => $CSM_AGENT_APPROVER);
						$APPROVAL_ROUTES[] = array("type" => "CSM", "value" => $CSM_HEAD_AGENT_APPROVER);
						$APPROVAL_ROUTES[] = array("type" => "FD", "value" => $FINANCE_VENDOR_APPROVER);

					} /* else if ($companyType == 'Vendor') {
						$APPROVAL_ROUTES[] = array("type" => "FD", "value" => $FINANCE_VENDOR_APPROVER);
					} */

					$APPROVAL_ROUTES[] = array("type" => "Coordinator", "value" => $CONTRACT_OWNER_ID);
					$APPROVAL_ROUTES[] = array("type" => "Legal Assistant", "value" => $LEGAl_ASSISTANT_APPROVER);

				}
				

				$sql3 = "UPDATE vtiger_approvalroutehistory u
								 INNER JOIN vtiger_approvalroutehistorycf s on u.approvalroutehistoryid = s.approvalroutehistoryid
								 SET u.name = '$AgreementType', s.cf_6786 = '$AgreementType'				
								 WHERE u.approvalroutehistoryid = ?";
				$result3 = $adb->pquery($sql3, array($approvalroutehistoryid));
			
				// echo "<pre>"; print_r($APPROVAL_ROUTES); exit;


				foreach ($APPROVAL_ROUTES as $key => $routePartie){

					// Add new approval routes
					$userid = $routePartie['value'];
					$recordModel2 = Vtiger_Record_Model::getCleanInstance($APPROVAL_ROUTE_HISTORY);								
					$recordModel2->set('assigned_user_id', $currentUserId); // $curid= Assigned User id
					$recordModel2->set('mode', 'create');
					$recordModel2->set("name", $AgreementType);
					$recordModel2->set('cf_6784', $recordId);
					$recordModel2->set("cf_6786", $AgreementType);
					$recordModel2->set("cf_6788", $userid);
					$recordModel2->set("cf_6790", $n);
					$recordModel2->set("cf_6792", 'pending');
					$recordModel2->set("cf_6794", $currentDateTime);
					$recordModel2->save();

					$n ++;
				}

		}
	}

	function notifyUsers($request){
		global $adb, $current_user;
		$currentUserId = $current_user->id;
		
		
  	$creatorUserModel = Vtiger_Record_Model::getInstanceById($currentUserId, 'Users');
		$contractCreatorName = $creatorUserModel->get('first_name').' '.$creatorUserModel->get('last_name');
		$contractCreatorEmail = strtolower(trim($creatorUserModel->get('email1')));
		
  	$accountModel = Vtiger_Record_Model::getInstanceById($request->get('cf_6362'), 'Accounts');
		$accountname = $accountModel->get('accountname');		
		$contractType = $request->get('cf_7346');	
		
		// Fetch current user's head in userlist table
		$queryUser = $adb->pquery("Select vtiger_userlistcf.cf_3385
																FROM vtiger_userlistcf
																WHERE cf_3355 = ?", array($contractCreatorEmail));
		$userHeadId = $adb->query_result($queryUser, 0, 'cf_3385');

		$queryHead = $adb->pquery("Select vtiger_userlistcf.cf_3355, vtiger_userlist.name
															FROM vtiger_userlistcf
															INNER JOIN vtiger_userlist ON vtiger_userlist.userlistid = vtiger_userlistcf.userlistid
															WHERE vtiger_userlistcf.userlistid = ?", array($userHeadId));
		$headEmail = strtolower(trim($adb->query_result($queryHead, 0, 'cf_3355')));
		$headName = $adb->query_result($queryHead, 0, 'name');

		$details = array();
		$details['fromName'] = $contractCreatorName;
		$details['fromEmail'] = $contractCreatorEmail;
		$details['creatorHeadEmail'] = $headEmail;
		$details['creatorHeadName'] = $headName;
		$details['agentName'] = $accountname;
		$details['contractRefNo'] = $request->get('name');
		$details['recordId'] = $request->get('record');

/* 		if ($contractType == 'Agency (Bilateral Partnership)'){
			$instanceCMS = new MOTIW();
			$instanceCMS->sendEmailNotification($details);
			$details = [];
		}
 */
	}

}
