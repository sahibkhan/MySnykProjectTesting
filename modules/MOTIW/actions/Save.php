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

	const APPROVAL_ROUTE_HISTORY = 'ApprovalRouteHistory';
	const CEO_APPROVER = 60;
	const LEGAl_HEAD_APPROVER = 1122; // Timur
	const SENIOR_LAWYER_APPROVER = 788; // Ayana
	const LEGAl_ASSISTANT_APPROVER = 1542; // Eldana

	const FINANCE_APPROVER = 1217; // Rimma
	const FINANCE_VENDOR_APPROVER = 1493; // Zhanat
	const MOSCOW_SUPERVISOR_APPROVER = 705; // Turbold

	// Branches
	const BRANCH_ATYRAY_MANAGER = 1038;

	const CSM_AGENT_APPROVER = 66; // Zhanna Kaynarbayeva
	const CSM_HEAD_AGENT_APPROVER = 6; // Aftab Ahmed


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
	public function saveRecord($request){
		
		global $adb, $current_user;
		$currentuserid = $current_user->id;
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$recordModel = $this->getRecordModelFromRequest($request);
		
		$prevCounterpartyType = '';
		if ($request->get('record')){
			$CMSRecordModel = Vtiger_Record_Model::getInstanceById($request->get('record'), 'MOTIW');
			$prevCounterpartyType = $CMSRecordModel->get('cf_7346');
			$prevTypeofAgreement = $CMSRecordModel->get('cf_6366');
		}
				
		if($request->get('imgDeleted')) {
			$imageIds = $request->get('imageid');
			foreach($imageIds as $imageId) {
				$status = $recordModel->deleteImage($imageId);
			}
		}
		
		$recordModel->set('cf_7046','Pending');
		$recordModel->save();
		$sourcerecordid = $recordModel->get('id');
		// echo 'sourcerecordid = ' . $sourcerecordid; exit;


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
		} elseif ($request->get('returnmodule') == 'BO' && $request->get('returnrecord')) {
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
	  $currentDateTime = $adb->formatDate(date("Y-m-d H:i:s"), true);
		// Adding initiator of agreement in approval route

 		if(empty($recordId)) {
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
	 	

		$this->updateStatus($request, $prevCounterpartyType, $prevTypeofAgreement);
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
				// $recordModel->set($fieldName, $fieldValue);
				if ((!empty($recordId) && !in_array($fieldName, array('cf_6766', 'cf_6768'))) || empty($recordId)) {
					$recordModel->set($fieldName, $fieldValue);
				}
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
			// Kiev / Irina Moroz
			$userid = 1788;								
		} else if ($currentUserCompanyId == 205751){
			// Erevan / Karapetyan
			$userid = 1080;								
		} else if ($currentUserCompanyId == 258701){
			// Tbilisi / Zurab
			$userid = 1191;
		} else if ($currentUserCompanyId == 85761){
			// Baku / Alla Dagadaeva
			$userid = 242;
		} else if ($currentUserCompanyId == 85764){ 
			// Bishkek / Gulzat Zhumakadyrova
			$userid = 1252;
		} else if ($currentUserCompanyId == 359044){
			// Riga / Irina Levcenkova
			$userid = 1657;
		}
		return $userid;
	}


	public function updateStatus(Vtiger_Request $request, $prevCounterpartyType, $prevTypeofAgreement){
		global $adb;
 		$recordId = $request->get('record');
   	$CMSRecordModel = Vtiger_Record_Model::getInstanceById($request->get('record'), 'MOTIW');				

 		// Get current contract approval route parties
		$sql = "SELECT * 
						FROM vtiger_approvalroutehistorycf
						INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_approvalroutehistorycf.approvalroutehistoryid 
						WHERE vtiger_approvalroutehistorycf.cf_6784 = ? AND vtiger_crmentity.deleted = 0";
		$result = $adb->pquery($sql, array($recordId));
		$rows = $adb->num_rows($result);
		

		// First approval route creation
		if ($rows == 0){
			$this->createApprovalRoute($request);
		}

		// if Account type changed
   	if ($rows > 0 && ($prevCounterpartyType != $request->get('cf_7346') || $prevTypeofAgreement != $request->get('cf_6366')) ){
			$this->clearApprovalRoute($recordId);
			$this->createApprovalRoute($request);
		}	

	}

	public function createApprovalRoute(Vtiger_Request $request){
		global $adb;
		$contractRecordModel = Vtiger_Record_Model::getInstanceById($request->get('record'), 'MOTIW');
		
		// Allowed Accounts for Tahskent branch. In this case local accountant will be added in approval route
		$allowedAccounts = array(103036, 2112761);

		$currentUserModel = Users_Record_Model::getCurrentUserModel();

		$currentUserId = $contractRecordModel->get('assigned_user_id');
		$CONTRACT_OWNER_ID = $currentUserId;

		$currentUserCompanyId = $currentUserModel->get('company_id');
		$agreementType = $request->get('cf_6366');
		$companyType = $request->get('cf_7346');
		$coordinatorLocationId = $request->get('cf_6766');
		$accountId = $request->get('cf_6362');
		$typeOfDocument = $request->get('cf_6758');
		$recordId = $request->get('record');

		// Getting current user info
		$userHeadNaviteId = $this->getContractCreatorHeadId($recordId);
		
		// Define THE Approval Route
		$n = 1;
		$currentDateTime = $adb->formatDate(date("Y-m-d H:i:s"), true);
		$accountantID = $this->getAccountant($currentUserCompanyId);

						
		// echo "<pre>"; print_r($currentUserCompanyId); exit;

		// Exception for Uzbekistan. Use Kazakhstan accountant manager
		$KazakhstanAccountantHeadId = $this->getAccountant(85757);
		$APPROVAL_ROUTES = array();

		// Adding Initiator of task in approval list
		$APPROVAL_ROUTES[] = array("type" => "Initiator", "value" => $currentUserId);	

		// Standard
		if ($agreementType == 'Glk standard contracts without changes and any booking orders, appendices, additional agreements, amendments'){
			$APPROVAL_ROUTES[] = array("type" => "Legal Assistant", "value" => self::LEGAl_ASSISTANT_APPROVER);
		}		


		if ($agreementType == 'Customer Format Contract' || $agreementType == 'Vendor Format Contract' || $agreementType == 'Partner Format Contract'){
			$APPROVAL_ROUTES[] = array("type" => "LegalGM", "value" => self::LEGAl_HEAD_APPROVER);
			
		} else if ($agreementType == 'GLK Format Contract with Changes'){
			$APPROVAL_ROUTES[] = array("type" => "SeniorLawer", "value" => self::SENIOR_LAWYER_APPROVER);
		} else if (in_array($agreementType, array('Signed,Scanned and Non-Negotiable Contract', 'Rental', 'Consulting', 'Warehousing'))) {

			if (!in_array($userHeadNaviteId, array(self::LEGAl_HEAD_APPROVER, self::CEO_APPROVER))){
				// 123 - Feroz Ahmad
				if ($userHeadNaviteId == 123) $userHeadNaviteId = self::BRANCH_ATYRAY_MANAGER;
				$APPROVAL_ROUTES[] = array("type" => "GM", "value" => $userHeadNaviteId);
			}
			$APPROVAL_ROUTES[] = array("type" => "LegalGM", "value" => self::LEGAl_HEAD_APPROVER);
		}
		
		if ($companyType == 'Customer'){
			$APPROVAL_ROUTES[] = array("type" => "FD", "value" => self::FINANCE_APPROVER);

		} else if ($companyType == 'Vendor'){
			$APPROVAL_ROUTES[] = array("type" => "FD", "value" => self::FINANCE_VENDOR_APPROVER);
			$isVendorApproverAdded = true;
		}

		if ($agreementType != 'Glk standard contracts without changes and any booking orders, appendices, additional agreements, amendments'){

			// For Tashkent branch
			if ($coordinatorLocationId == 85831 && (in_array($accountId, $allowedAccounts))){
				$APPROVAL_ROUTES[] = array("type" => "Accountant", "value" => $accountantID);

				if ($companyType == 'Agency (Bilateral Partnership)'){
					$approvalParties = $this->getAgencyApprovalParties();
					foreach ($approvalParties as $approvalPartie){
						$APPROVAL_ROUTES[] = $approvalPartie;
					}
				}

			} else if ($coordinatorLocationId == 85831 && (!in_array($accountId, $allowedAccounts))){

				$APPROVAL_ROUTES[] = array("type" => "Accountant", "value" => $KazakhstanAccountantHeadId);

				if ($companyType == 'Agency (Bilateral Partnership)'){
					$approvalParties = $this->getAgencyApprovalParties();
					foreach ($approvalParties as $approvalPartie){
						$APPROVAL_ROUTES[] = $approvalPartie;
					}
				}

			} else if ($coordinatorLocationId != 85831) {

				$APPROVAL_ROUTES[] = array("type" => "Accountant", "value" => $accountantID);
				if ($companyType == 'Agency (Bilateral Partnership)'){
					$approvalParties = $this->getAgencyApprovalParties();
					foreach ($approvalParties as $approvalPartie){
						$APPROVAL_ROUTES[] = $approvalPartie;
					}
					
				} else if ($companyType == 'Vendor') {
					if ($isVendorApproverAdded == false){
						$APPROVAL_ROUTES[] = array("type" => "FD", "value" => self::FINANCE_VENDOR_APPROVER);
					}
				}

			}
			// Adding Coordinator and Legal Asistant to each approval route
			if (!in_array($agreementType, array('Rental', 'Consulting', 'Warehousing'))){
				$APPROVAL_ROUTES[] = array("type" => "Coordinator", "value" => $CONTRACT_OWNER_ID);
			}

			if ($typeOfDocument != 'Appendix/Booking Order'){
				$APPROVAL_ROUTES[] = array("type" => "Legal Assistant", "value" => self::LEGAl_ASSISTANT_APPROVER);
			}

		}

		// Add Turbod for MOSCOW relevant companies
		if ($currentUserCompanyId == 85766 || $currentUserCompanyId == 1763699){
			$APPROVAL_ROUTES[] = array("type" => "Moscow Supervisor", "value" => self::MOSCOW_SUPERVISOR_APPROVER);	
		}

		// For standard always add LD Assistant
		if ($agreementType == 'Glk standard contracts without changes and any booking orders, appendices, additional agreements, amendments'){

			if ($companyType == 'Agency (Bilateral Partnership)'){
				$approvalParties = $this->getAgencyApprovalParties();
				foreach ($approvalParties as $approvalPartie){
					$APPROVAL_ROUTES[] = $approvalPartie;
				}
			}

			$APPROVAL_ROUTES[] = array("type" => "Coordinator", "value" => $CONTRACT_OWNER_ID);
			if ($typeOfDocument != 'Appendix/Booking Order'){
				$APPROVAL_ROUTES[] = array("type" => "Legal Assistant", "value" => self::LEGAl_ASSISTANT_APPROVER);
			}

		}

		// Get current contract approval route parties
		$sql = "SELECT *
						FROM vtiger_approvalroutehistorycf 
						INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_approvalroutehistorycf.approvalroutehistoryid 
						WHERE vtiger_approvalroutehistorycf.cf_6784 = ? AND vtiger_crmentity.deleted = 0";
		$result = $adb->pquery($sql, array($recordId));
		$approvalroutehistoryid = $adb->query_result($result,0,'approvalroutehistoryid');


			$sql3 = "UPDATE vtiger_approvalroutehistory u
							 INNER JOIN vtiger_approvalroutehistorycf s on u.approvalroutehistoryid = s.approvalroutehistoryid
							 SET u.name = '$agreementType', s.cf_6786 = '$agreementType'				
							 WHERE u.approvalroutehistoryid = ?";
			$result3 = $adb->pquery($sql3, array($approvalroutehistoryid));			

			foreach ($APPROVAL_ROUTES as $key => $routePartie){
				// Add new approval routes
				$userid = $routePartie['value'];
				$type = $routePartie['type'];

				$recordModel2 = Vtiger_Record_Model::getCleanInstance(self::APPROVAL_ROUTE_HISTORY);								
				$recordModel2->set('assigned_user_id', $currentUserId); // $curid= Assigned User id
				$recordModel2->set('mode', 'create');
				$recordModel2->set("name", $agreementType);
				$recordModel2->set('cf_6784', $recordId);
				$recordModel2->set("cf_6786", $agreementType);
				$recordModel2->set("cf_6788", $userid);
				$recordModel2->set("cf_6790", $n);
				$recordModel2->set("cf_6792", ($type == 'Initiator') ? 'inline' : 'pending');
				$recordModel2->set("cf_6794", $currentDateTime);
				$recordModel2->save();

				$n ++;
			}


			$this->setCurrentReviewer($recordId);
	}

	public function setCurrentReviewer($recordId){
		global $adb;
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$currentReviewer = $currentUserModel->get('first_name').' '. $currentUserModel->get('last_name');
		$resultRevisionBy = $adb->pquery("UPDATE `vtiger_motiwcf` SET `cf_7040` = ? WHERE `motiwid` = ? LIMIT 1", array($currentReviewer, $recordId));
	}

	public function getContractCreatorHeadId($recordId){
		global $adb;
		$currentContractModel = Vtiger_Record_Model::getInstanceById($recordId, 'MOTIW');
		$contractCreatorId = $currentContractModel->get('assigned_user_id');
		
		$contractCreatorModel = Vtiger_Record_Model::getInstanceById($contractCreatorId, 'Users');
		$contractCreatorEmail = trim($contractCreatorModel->get('email1'));	

		$queryCurrentUser = "SELECT cf_3385 FROM vtiger_userlistcf WHERE cf_3355 = ?";
		$resultCurrentUser = $adb->pquery($queryCurrentUser, array($contractCreatorEmail));
		$currentUserHeadId = $adb->query_result($resultCurrentUser, 0, 'cf_3385');

		$contractCreatorHeadModel = Vtiger_Record_Model::getInstanceById($currentUserHeadId, 'UserList');
		$contractCreatorHeadEmail = trim($contractCreatorHeadModel->get('cf_3355'));

		$queryContractCreatorHead = "SELECT id FROM vtiger_users WHERE email1 = ?";
		$resultContractCreatorHead = $adb->pquery($queryContractCreatorHead, array($contractCreatorHeadEmail));
		$currentUserHeadId = $adb->query_result($resultContractCreatorHead, 0, 'id');
		return $currentUserHeadId;
	}


	public function clearApprovalRoute($recordId){
		global $adb;
		// Get current contract approval route parties
		$sqlApprovalParties = "SELECT approvalroutehistoryid, cf_6792
													FROM vtiger_approvalroutehistorycf 
													INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_approvalroutehistorycf.approvalroutehistoryid 
													WHERE vtiger_approvalroutehistorycf.cf_6784 = ? AND vtiger_crmentity.deleted = 0";
		$resultApprovalParties = $adb->pquery($sqlApprovalParties, array($recordId));
		$noOfApprovalParties = $adb->num_rows($resultApprovalParties);

		for ($i=0; $i<$noOfApprovalParties; $i++){
			$routeId = $adb->query_result($resultApprovalParties, $i, 'approvalroutehistoryid');
			$status = $adb->query_result($resultApprovalParties, $i, 'cf_6792');

			// change status from inline to pending. For proper filtering
			if ($status == 'inline'){

				$queryUpdateStatus = "UPDATE vtiger_approvalroutehistorycf
															SET cf_6792 = ?
															WHERE approvalroutehistoryid = ? AND cf_6784 = ?";
				$resultUpdateStatus = $adb->pquery($queryUpdateStatus, array('pending', $routeId, $recordId));

			}

			$approvalRouteRecordModel = Vtiger_Record_Model::getInstanceById($routeId, 'ApprovalRouteHistory');
			$approvalRouteRecordModel->delete();

		}

	}

	function getAgencyApprovalParties(){
		return array(array("type" => "CSM", "value" => self::CSM_AGENT_APPROVER), array("type" => "CSM", "value" => self::CSM_HEAD_AGENT_APPROVER), array("type" => "FD", "value" => self::FINANCE_VENDOR_APPROVER));
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

		if ($contractType == 'Agency (Bilateral Partnership)'){
			$instanceCMS = new MOTIW();
			$instanceCMS->sendEmailNotification($details);
			$details = [];
		}

	}

}
