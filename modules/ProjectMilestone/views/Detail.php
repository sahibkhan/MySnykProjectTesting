<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class ProjectMilestone_Detail_View extends Vtiger_Detail_View {
	protected $record = false;
	protected $isAjaxEnabled = null;

	function __construct() {
		parent::__construct();
		$this->exposeMethod('updateStatus');
		// $this->exposeMethod('getLeaveList');
	}

	
	public function updateStatus(Vtiger_Request $request){
		global $adb;
		$data = array();
		$recordId = $request->get('record'); 
		// $module = $request->get('module');
		$status = $request->get('status');

		$currentDate = date("Y-m-d");		
		$recordModel = Vtiger_Record_Model::getInstanceById($recordId, 'ProjectMilestone');
		$taskDeadline = $recordModel->get('projectmilestonedate');
		$taskRevisedDeadline = $recordModel->get('cf_7860');

		// Get current user in session
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$currentUserEmail = strtolower(trim($currentUserModel->get('email1')));

		// Get project creator

		
		
		$recordProjectModel = Vtiger_Record_Model::getInstanceById($recordModel->get('projectid'), 'Project');
		$projectCreatorId = $recordProjectModel->get('assigned_user_id');

		$creatorUserModel = Vtiger_Record_Model::getInstanceById($projectCreatorId, 'Users');
		// $projectCreator = $creatorUserModel->get('first_name').' '.$creatorUserModel->get('last_name');
		$projectCreatorEmail = strtolower(trim($creatorUserModel->get('email1')));

 		
		if ($status == 'start'){

 			$recordModel->set('mode', 'edit');
			$recordModel->set('cf_7838', 'In progress');
		  $recordModel->save();

			$data['status'] = 'start';

		} else if ($status == 'complete'){

			if ($currentDate > $taskDeadline){

				if (empty($taskRevisedDeadline)) {
					$data['status'] = 'noReviseDate';
				} else if (!empty($taskRevisedDeadline)){
					$data['status'] = 'success';
					$recordModel->set('mode', 'edit');
					$recordModel->set('cf_7838', 'Completed');
					$recordModel->save();


				}				
				

			} else if ($currentDate <= $taskDeadline){
				$data['status'] = 'success';

				$recordModel->set('mode', 'edit');
				$recordModel->set('cf_7838', 'Completed');
				$recordModel->save();


				$projectId = $recordModel->get('projectid');
				// Get Project Owner email
				$projectModel = Vtiger_Record_Model::getInstanceById($projectId, 'Project');
				$assigned_user_id = $projectModel->get('assigned_user_id');

				$projectCreatorModel = Vtiger_Record_Model::getInstanceById($assigned_user_id, 'Users');
				$creatorEmail = trim($projectCreatorModel->get('email1'));
				$creatorName = $projectCreatorModel->get('first_name').' '.$projectCreatorModel->get('last_name');

				
				$details = array();
				$details['recordId'] = $projectId;
				$details['fromEmail'] = $currentUserEmail;
				$details['fromName'] = "";
		
				$details['projectName'] = "";
				$details['projectCreator'] = $creatorName;
				$details['projectCreatorEmail'] = $projectCreatorEmail;
				$details['taskStatus'] = "Completed";
				$details['taskDeadline'] = $recordModel->get('projectmilestonedate');
				$details['revisedDeadline'] = $recordModel->get('cf_7860');
				$details['revisedDeadlineReason'] = $recordModel->get('cf_7862');
				$details['taskComments'] = $recordModel->get('description');
				$details['taskName'] = $recordModel->get('projectmilestonename');

				if ($projectId > 0){

					$involvedUserEmails = $this->getTaskInvolvedUsersEmail($projectId);
					$involvedUserEmails .= $creatorEmail.','; 
					$details['involvedUserEmails'] = $involvedUserEmails;
				}
						
				require_once "modules/Project/Project.php";
				$projectInstance = new Project();
				$projectInstance->taskEmailHandler($details);

			}

		}

		
		$parseToJson = json_encode($data);
		return $parseToJson;


/* 		$loadUrl = "index.php?module=ProjectMilestone&view=Detail&record=".$recordId;
        echo '<script> 
			var url= "'.$loadUrl.'"; 
			window.location = url; 
		</script>'; */

		
/* 		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$currentUserEmail = strtolower(trim($currentUserModel->get('email1')));
		$currentUserName = $currentUserModel->get('first_name').' '.$currentUserModel->get('last_name');

		$refNo = $recordProbationAssessment->get('cf_7304');
		$requestedById = $recordProbationAssessment->get('name'); */
		




		/*
		
		$currentEmployeeName = $currentUserName;
		$recordCreatorId = $recordProbationAssessment->get('assigned_user_id');
		$recordCreatorModel = Vtiger_Record_Model::getInstanceById($recordCreatorId, 'Users');
		$recordCreatorEmail = strtolower(trim($recordCreatorModel->get('email1')));		


		$userListRecord = Vtiger_Record_Model::getInstanceById($requestedById, 'UserList');
		$requestedByHeadId = $userListRecord->get('cf_3385');

		$userHeadRecord = Vtiger_Record_Model::getInstanceById($requestedByHeadId, 'UserList');
		$userHeadEmail = strtolower(trim($userHeadRecord->get('cf_3355')));
		$userHeadName = trim($userHeadRecord->get('name'));


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

		// HEAD Approval
		if ($currentUserEmail == $userHeadEmail){
			$sqlUpdate = "UPDATE vtiger_probationassessmentcf SET cf_7336 = ?, cf_7344 = ? WHERE probationassessmentid = ?";
			$sqlResult = $adb->pquery($sqlUpdate, array($currentUserName, $date, $recordId));
			$approvedBy = $currentUserName;
		}

		// Employee Approval
		if ($currentUserEmail == $recordCreatorEmail){
			$sqlUpdate = "UPDATE vtiger_probationassessmentcf SET cf_7334 = ?, cf_7342 = ? WHERE probationassessmentid = ?";
			$sqlResult = $adb->pquery($sqlUpdate, array($currentUserName, $date, $recordId));
			$approvedBy = $currentUserName;
		}
		
		// HR Approval
		if ($currentUserEmail == $HRMangerEmail){
			$sqlUpdate = "UPDATE vtiger_probationassessmentcf SET cf_7332 = ?, cf_7340 = ? WHERE probationassessmentid = ?";
			$sqlResult = $adb->pquery($sqlUpdate, array($currentUserName, $date, $recordId));
			$approvedBy = $currentUserName;
		}
		
		// Add in CC
		$cc[] = $userHeadEmail;
		$cc[] = $recordCreatorEmail;
		$cc[] = $HRMangerEmail;
	
		// Head
		if (empty($recordProbationAssessment->get('cf_7336'))) {
			$nextApprovalPartieName = $userHeadName;
		} else {
			$approvalHistory[] = array("signature" => "Head of department Signature", "name" => $nextApprovalPartieName, "date" => $recordProbationAssessment->get('cf_7344'));
		}

		// Employee
		if (empty($recordProbationAssessment->get('cf_7334'))) {
			$nextApprovalPartieName = $currentEmployeeName;			
		} else {			
			$approvalHistory[] = array("signature" => "Employee Signature", "name" => $nextApprovalPartieName, "date" => $recordProbationAssessment->get('cf_7342'));
		}
		
		// HR
		if (empty($recordProbationAssessment->get('cf_7332'))){
			$nextApprovalPartieName = $HRMangerName;
		} else {			
			$approvalHistory[] = array("signature" => "HR Signature", "name" => $nextApprovalPartieName, "date" => $recordProbationAssessment->get('cf_7340'));
		}

			//Gathering email info
			$details = array();
			$details['name'] = $currentEmployeeName;
			$details['fromEmail'] = $currentUserEmail;
			$details['to'] = $recordCreatorEmail;
			$details['cc'] = $cc;
			$details['approvedBy'] = $approvedBy;
			$details['recordId'] = $recordId;
			$details['refNo'] = $refNo;
			$details['approvalHistory'] = $approvalHistory;
			$approvalHistory = [];
			$this->sendApproveEmail($details);

		$loadUrl = "index.php?module=ProbationAssessment&view=Detail&record=".$recordId;
        echo '<script> 
			var url= "'.$loadUrl.'"; 
			window.location = url; 
		</script>';*/
		
	}


	public function getTaskInvolvedUsersEmail($taskId){

		global $adb;
		$queryUsers = $adb->pquery("SELECT vtiger_users.email1 

																FROM vtiger_project 
																INNER JOIN vtiger_projectmilestone ON vtiger_projectmilestone.projectid = vtiger_project.projectid
																INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_projectmilestone.projectmilestoneid
																INNER JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid 

																WHERE vtiger_crmentity.deleted = 0 AND vtiger_projectmilestone.projectid = ?", array($taskId));
		$nRows = $adb->num_rows($queryUsers);
		for ($i = 0; $i< $nRows; $i++){
			$emails .= trim(strtolower($adb->query_result($queryUsers, $i, 'email1'))).',';
		}
		return $emails;
	}

 


	/*
	
	public function sendApproveEmail($details){

		$userName = $details['name'];
		$recordId = $details['recordId'];
		$refNo = $details['refNo'];
		$approvalHistory = $details['approvalHistory'];

		$link = $_SERVER['SERVER_NAME'];
		$link .= "/index.php?module=ProbationAssessment&view=Detail&record=".$recordId;
		$date_time = date('Y-m-d H:i:s');
		$from = trim($details['fromEmail']);
		$to = trim($details['to']);
	
		$approvedBy = trim($details['approvedBy']);
		$cc = implode(',', $details['cc']);

		$body = '';
		$message_status = "Probation Assessment for $userName <br> Please follow below link and approve probation assesment $refNo";
		$message_footer = '';
		
		$message_footer = 'Approved By: '.$approvedBy;

		
		$subject = 'Probation Assesment '.$refNo;
		$body .= "<html><head> <style> #tableBody tr td{ margin:3px; } </style> </head>
							<body><table id='tableBody'> ";
		$body .= "<tr><td colspan=2> $message_status </td></tr>";
		$body .= "<tr><td colspan=2> $message_footer </td></tr>
							<tr><td colspan=2>Link: <a href='$link'> Link to GEMS </a></td></tr>";
		$body .= "</table> </body> </html> ";
																	
		// Set content-type when sending HTML email
		$headers = "MIME-Version: 1.0" . "\n";
		$headers .= "Content-type:text/html;charset=UTF-8" . "\n";
	
		$headers .= $from . "\n";
		$headers .= 'Reply-To: '.$to.'' . "\n";

		require_once("modules/Emails/mail.php");
		$r = send_mail('ProbationAssessment', $to, $from, $from, $subject, $body, $cc, '','','','',true);		
	}
	*/
		

}
