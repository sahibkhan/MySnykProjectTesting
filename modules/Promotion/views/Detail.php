<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Promotion_Detail_View extends Vtiger_Detail_View {
	protected $record = false;

	function __construct() {
		
		parent::__construct();
		$this->exposeMethod('showModuleDetailView');
		$this->exposeMethod('showModuleBasicView');
		$this->exposeMethod('doApprove');
	}

	function checkPermission(Vtiger_Request $request) {
		parent::checkPermission($request);
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
	
		$nonEntityModules = array('Users', 'Events', 'Calendar', 'Portal', 'Reports', 'Rss', 'EmailTemplates');
		if ($recordId && !in_array($moduleName, $nonEntityModules)) {
			$recordEntityName = getSalesEntityType($recordId);
			if ($recordEntityName !== $moduleName) {
				throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
			}
		}
		
		return true;
	}


	
	function preProcessTplName(Vtiger_Request $request) {
		global $adb;
		$recordId = $request->get('record');
		$moduleName = $request->getModule();

		$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
		$requestCreator = Vtiger_Record_Model::getInstanceById($recordModel->get('assigned_user_id'), 'Users');
		$userlistModel = Vtiger_Record_Model::getInstanceById($recordModel->get('name'),"UserList");

		
		// Fetch current user's head in userlist table
		$queryUser = $adb->pquery("Select vtiger_userlistcf.cf_3385
																FROM vtiger_userlistcf
																WHERE cf_3355 = ?", array($requestCreator->get('email1')));
		$userHeadId = $adb->query_result($queryUser, 0, 'cf_3385');

		$queryHead = $adb->pquery("Select vtiger_userlistcf.cf_3355
															FROM vtiger_userlistcf
															WHERE userlistid = ?", array($userHeadId));
		$headEmail = $adb->query_result($queryHead, 0, 'cf_3355');


		// Get requested by user

		$requestedById = $recordModel->get('name');
		// getDisplayValue
		// Fetch request user email		
/* 		$queryUser = $adb->pquery("
								SELECT vtiger_userlistcf.cf_3355, vtiger_userlist.name
								FROM vtiger_userlistcf
								INNER JOIN vtiger_userlist ON vtiger_userlist.userlistid = vtiger_userlistcf.userlistid
								WHERE vtiger_userlistcf.userlistid = ?",array($requestedById));
		$recordCreatorEmail = trim($adb->query_result($queryUser, 0, 'cf_3355'));		 */


		$recordCreatorId = $recordModel->get('assigned_user_id');
		$recordCreatorModel = Vtiger_Record_Model::getInstanceById($recordCreatorId, 'Users');
		$recordCreatorEmail = strtolower(trim($recordCreatorModel->get('email1')));		

		
		$head_model = Vtiger_Record_Model::getInstanceById($userlistModel->get('cf_3387'),"UserList"); //imidiate supervisor
		
		// Fetch current user's head
		$queryHead = $adb->pquery("SELECT vtiger_userlistcf.cf_3355, vtiger_userlist.name
		FROM vtiger_userlistcf
		INNER JOIN vtiger_userlist ON vtiger_userlist.userlistid = vtiger_userlistcf.userlistid
		WHERE vtiger_userlistcf.userlistid IN (
			SELECT cf_3385
			FROM vtiger_userlistcf
			WHERE cf_3355 = ?)", array($recordCreatorEmail));
		$userHeadEmail = strtolower (trim($adb->query_result($queryHead, 0, 'cf_3355')));
		// $userHeadName = $adb->query_result($queryHead, 0, 'name');
		
		$viewer = $this->getViewer($request);
		$viewer->assign('head_department_email', $userHeadEmail);
		$viewer->assign('employee_email', $recordCreatorEmail);

		return parent::preProcessTplName($request);
	}

	public function showModuleDetailView(Vtiger_Request $request) {
		$recordId = $request->get('record');
		$moduleName = $request->getModule();

		$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
		$viewer = $this->getViewer($request);	
		//$viewer->assign('IMAGE_DETAILS', $recordModel->getImageDetails());

		return parent::showModuleDetailView($request);
	}

	public function showModuleBasicView(Vtiger_Request $request) {
		return $this->showModuleDetailView($request);
	}
	
	function isAjaxEnabled($recordModel) {
	$currentusermodel = users_record_model::getcurrentusermodel();
		if ($currentusermodel->isadminuser() == true) {
			return true;
		} else {
			return false;
		}
	}
	
	public function doApprove(Vtiger_Request $request) {
		
		global $adb;
		$date = Date('Y-m-d');
		$recordId = $request->get('record');

		$currentUser = Users_Record_Model::getCurrentUserModel();
		$currentUserEmail = strtolower(trim($currentUser->get('email1')));
		$currentUserName = $currentUser->get('first_name').' '.$currentUser->get('last_name');
		$recordPromotion = Vtiger_Record_Model::getInstanceById($recordId, 'Promotion');
		$currentEmployeeName = $recordPromotion->getDisplayValue('name');
		$requestedById = $recordPromotion->get('name');

		$recordCreatorId = $recordPromotion->get('assigned_user_id');
		$recordCreatorModel = Vtiger_Record_Model::getInstanceById($recordCreatorId, 'Users');
		$recordCreatorEmail = strtolower(trim($recordCreatorModel->get('email1')));		
		$requestedById = $recordPromotion->get('name');

		// Fetch request user email		
		$queryUser = $adb->pquery("
								SELECT vtiger_userlistcf.cf_3355, vtiger_userlist.name
								FROM vtiger_userlistcf
								INNER JOIN vtiger_userlist ON vtiger_userlist.userlistid = vtiger_userlistcf.userlistid
								WHERE vtiger_userlistcf.userlistid = ?",array($requestedById));
		$recordCreatorEmailForApproval = strtolower(trim($adb->query_result($queryUser, 0, 'cf_3355')));
		// $currentEmployeeName = trim($adb->query_result($queryUser, 0, 'name'));

		// Fetch current user's head
		$queryHead = $adb->pquery("SELECT vtiger_userlistcf.cf_3355, vtiger_userlist.name
															FROM vtiger_userlistcf
															INNER JOIN vtiger_userlist ON vtiger_userlist.userlistid = vtiger_userlistcf.userlistid
															WHERE vtiger_userlistcf.userlistid IN (
																SELECT cf_3385
																FROM vtiger_userlistcf
																WHERE userlistid = ?)", array($requestedById));
		$userHeadEmail = strtolower(trim($adb->query_result($queryHead, 0, 'cf_3355')));
		$userHeadName = $adb->query_result($queryHead, 0, 'name');

			// Fetch HR Manager
			$queryHRManager = $adb->pquery("SELECT vtiger_userlistcf.cf_3355, vtiger_userlist.name
																			FROM `vtiger_userlistcf` 
																			INNER JOIN vtiger_userlist ON vtiger_userlist.userlistid = vtiger_userlistcf.userlistid
																			WHERE vtiger_userlistcf.cf_3385 = 412373 AND vtiger_userlistcf.cf_3421 = 85805 
																			AND vtiger_userlistcf.cf_3353 = 85757 AND vtiger_userlistcf.cf_3349 = 414370 
																			AND vtiger_userlistcf.cf_6206 = 'Active'");
			$HRMangerEmail = strtolower(trim($adb->query_result($queryHRManager, 0, 'cf_3355')));
			$HRMangerName = $adb->query_result($queryHRManager, 0, 'cf_3355');


		$approvedBy = '';
		$approvalPartiesCount = 0;

		// HEAD Approval
		if ($currentUserEmail == $userHeadEmail){
			$sqlUpdate = "UPDATE vtiger_promotioncf SET cf_7430 = ?, cf_7428 = ? WHERE promotionid = ?";
			$sqlResult = $adb->pquery($sqlUpdate, array($currentUserName, $date, $recordId));
			$approvedBy = $currentUserName;
		}

		// Employee Approval
		if ($currentUserEmail == $recordCreatorEmailForApproval){
			$sqlUpdate = "UPDATE vtiger_promotioncf SET cf_7436 = ? WHERE promotionid = ?";
			$sqlResult = $adb->pquery($sqlUpdate, array($currentUserName, $recordId));
			$approvedBy = $currentUserName;
		}
 

		// HR Approval
		if ($currentUserEmail == $HRMangerEmail){
			$sqlUpdate = "UPDATE vtiger_promotioncf SET cf_7432 = ?, cf_7434 = ? WHERE promotionid = ?";
			$sqlResult = $adb->pquery($sqlUpdate, array($currentUserName, $date, $recordId));
			$approvedBy = $currentUserName;
		}
		
		// Director Approval
		if ($currentUserEmail == 'r.balayev@globalinklogistics.com'){
			$sqlUpdate = "UPDATE vtiger_promotioncf SET cf_7424 = ?, cf_7426 = ? WHERE promotionid = ?";
			$sqlResult = $adb->pquery($sqlUpdate, array($currentUserName, $date, $recordId));
			$approvedBy = $currentUserName;
		}

		// Fetch approved persons
		$approvalHistory = array();

		// Understood
		if (!empty($recordPromotion->get('cf_7436'))) {
			$approvalHistory[] = array("signature" => "Read and Understood", "name" => $recordPromotion->get('cf_7436'), "date" => "");
			$approvalPartiesCount ++;
		}
		
/* 		// Agreed
		if (!empty($recordPromotion->get('cf_7438'))) {
			$approvalHistory[] = array("signature" => "Read and Agreed", "name" => $recordPromotion->get('cf_7438'), "date" => "");
		}
 */
		// HR
		if (!empty($recordPromotion->get('cf_7432'))) {
			$approvalHistory[] = array("signature" => "HR Manager", "name" => $recordPromotion->get('cf_7432'), "date" => $recordPromotion->get('cf_7434'));
			$approvalPartiesCount ++;
		}

		// Director
		if (!empty($recordPromotion->get('cf_7424'))) {
			$approvalHistory[] = array("signature" => "Director", "name" => $recordPromotion->get('cf_7424'), "date" => $recordPromotion->get('cf_7426'));
			$approvalPartiesCount ++;
		}

		// GM
		if (!empty($recordPromotion->get('cf_7430'))) {
			$approvalPartiesCount ++;
		}


		$promotedToPosition = $recordPromotion->get('cf_7418');
		$promotioDate = $recordPromotion->get('cf_7416');
		$refNo = $recordPromotion->get('cf_7414');


		$cc = array();
		$cc[] = $currentUserEmail;
		$cc[] = $recordCreatorEmailForApproval;
 		$cc[] = 'r.balayev@globalinklogistics.com';
		$cc[] = 'a.gaisina@globalinklogistics.com';
		
		// If all approval parties approved, then notify HR team
		if ($approvalPartiesCount == 4){
			$cc[] = 'hr@globalinklogistics.com';

			// Change approval status to approved
/* 			$sqlUpdate = "UPDATE vtiger_promotioncf SET cf_7424 = ?, cf_7426 = ? WHERE promotionid = ?";
			$sqlResult = $adb->pquery($sqlUpdate, array($currentUserName, $date, $recordId)); */
		}

		//Gathering email info
		$details = array();
		$details['name'] = $currentEmployeeName;
		$details['fromEmail'] = $currentUserEmail;
		$details['to'] = $recordCreatorEmail;
		$details['cc'] = $cc;
		$details['approvedBy'] = $currentUserName;
		$details['recordId'] = $recordId;
		$details['refNo'] = $refNo;
		$details['approvalHistory'] = $approvalHistory;
		$details['promotedToPosition'] = $promotedToPosition;
		$details['promotioDate'] = $promotioDate;
		$approvalHistory = [];
		$this->sendApproveEmail($details);

		$loadUrl = "index.php?module=Promotion&view=Detail&record=".$request->get('record');
        echo '<script> 
			var url= "'.$loadUrl.'"; 
			window.location = url; 
		</script>';
	}

	

	public function sendApproveEmail($details){

		$userName = $details['name'];
		$recordId = $details['recordId'];
		$refNo = $details['refNo'];
		$approvalHistory = $details['approvalHistory'];
		$promotedToPosition = $details['promotedToPosition'];
		$promotioDate = $details['promotioDate'];

		$link = $_SERVER['SERVER_NAME'];
		$link .= "/index.php?module=Promotion&view=Detail&record=".$recordId;
		
		$date_time = date('Y-m-d H:i:s');
		$from = trim($details['fromEmail']);
		$to = trim($details['to']);
		$approvedBy = trim($details['approvedBy']);
		$cc = implode(',', $details['cc']);

		$body = '';		
		if ($approvalHistory){			
			$message_details = "<tr><td><b>Signatures:</b></td></tr>";
			foreach ($approvalHistory as $value){
				if (isset($value['date'])) $dateValue = ', Date: '.$value['date']; else $dateValue = ', ';
				$message_details .= "<tr><td>".$value['signature'].': '.$value['name'].$dateValue.'</td></tr>';
			}		
		}

		if ($promotedToPosition || $promotioDate){
			$message_details .= "<tr><td><b>Request details:</b></td></tr>";
			// $message_details .= "<tr><td> Promoted to position: ".$promotedToPosition.'</td></tr>';
			$message_details .= "<tr><td> Promotion date: ".$promotioDate.'</td></tr>';
		}

		// Promoted to Position
		// Promotion Date

		$message_status = "Promotion for $userName has been approved by $approvedBy";
		$subject = 'Promotion: '.$refNo;
		$body .= "<html><head> <style> #tableBody tr td{ margin:3px; } </style> </head>
							<body><table id='tableBody'> ";
		$body .= "<tr><td colspan=2> $message_status </td></tr>";
		$body .= $message_details;

		$body .= "<tr><td colspan=2>Link: <a href='$link'> Link to GEMS </a></td></tr>";
		$body .= "</table> </body> </html> ";
																	
		// Set content-type when sending HTML email
		$headers = "MIME-Version: 1.0" . "\n";
		$headers .= "Content-type:text/html;charset=UTF-8" . "\n";
	
		$headers .= $from . "\n";
		$headers .= 'Reply-To: '.$to.'' . "\n";

		require_once("modules/Emails/mail.php");
		$r = send_mail('Promotion', $to, $from, $from, $subject, $body, $cc,'','','','',true);		
	}

	
}
