<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class ExternalTraining_Detail_View extends Vtiger_Detail_View {
	protected $record = false;
	protected $isAjaxEnabled = null;

	function __construct() {
		parent::__construct();
		$this->exposeMethod('showModuleDetailView');
		$this->exposeMethod('showModuleSummaryView');
		$this->exposeMethod('approveExternalTraining');
	}

		/**
	 * Function shows the entire detail for the record
	 * @param Vtiger_Request $request
	 * @return <type>
	 */
	function showModuleDetailView(Vtiger_Request $request) {
		$recordId = $request->get('record');
		$moduleName = $request->getModule();

		if(!$this->record){
		$this->record = Vtiger_DetailView_Model::getInstance($moduleName, $recordId);
		}
		$recordModel = $this->record->getRecord();
		$recordStrucure = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_DETAIL);
		$structuredValues = $recordStrucure->getStructure();

		$moduleModel = $recordModel->getModule();

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('RECORD_STRUCTURE', $structuredValues);
		$viewer->assign('BLOCK_LIST', $moduleModel->getBlocks());
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('IS_AJAX_ENABLED', $this->isAjaxEnabled($recordModel));
		$viewer->assign('MODULE', $moduleName);
		
		$viewer->assign('IMAGE_DETAILS', $recordModel->getImageDetails());

		$picklistDependencyDatasource = Vtiger_DependencyPicklist::getPicklistDependencyDatasource($moduleName);
		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE', Vtiger_Functions::jsonEncode($picklistDependencyDatasource));

		if ($request->get('displayMode') == 'overlay') {
			$viewer->assign('MODULE_MODEL', $moduleModel);
			$this->setModuleInfo($request, $moduleModel);
			$viewer->assign('SCRIPTS',$this->getOverlayHeaderScripts($request));

			$detailViewLinkParams = array('MODULE'=>$moduleName, 'RECORD'=>$recordId);
			$detailViewLinks = $this->record->getDetailViewLinks($detailViewLinkParams);
			$viewer->assign('DETAILVIEW_LINKS', $detailViewLinks);
			return $viewer->view('OverlayDetailView.tpl', $moduleName);
		} else {
			return $viewer->view('DetailViewFullContents.tpl', $moduleName, true);
		}
	}

	
	function showModuleSummaryView($request) {
		$recordId = $request->get('record');
		$moduleName = $request->getModule();

		if(!$this->record){
			$this->record = Vtiger_DetailView_Model::getInstance($moduleName, $recordId);
		}
		$recordModel = $this->record->getRecord();
		$recordStrucure = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_SUMMARY);

		$moduleModel = $recordModel->getModule();
		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('BLOCK_LIST', $moduleModel->getBlocks());
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());

		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('IS_AJAX_ENABLED', $this->isAjaxEnabled($recordModel));
		$viewer->assign('SUMMARY_RECORD_STRUCTURE', $recordStrucure->getStructure());
		$viewer->assign('RELATED_ACTIVITIES', $this->getActivities($request));

		$viewer->assign('IMAGE_DETAILS', $recordModel->getImageDetails());

		$viewer->assign('CURRENT_USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$pagingModel = new Vtiger_Paging_Model();
		$viewer->assign('PAGING_MODEL', $pagingModel);

		$picklistDependencyDatasource = Vtiger_DependencyPicklist::getPicklistDependencyDatasource($moduleName);
		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE', Vtiger_Functions::jsonEncode($picklistDependencyDatasource));

		return $viewer->view('ModuleSummaryView.tpl', $moduleName, true);
	}



		// Approvals for training request by CEO, Line Manager and HR
		public function approveExternalTraining(Vtiger_Request $request) {
			
			global $adb;
			$date = Date('Y-m-d');
			$recordId = $request->get('record');
			$currentUser = Users_Record_Model::getCurrentUserModel();
			$currentUserEmail = $currentUser->get('email1');
			$currentUserName = $currentUser->get('first_name').' '.$currentUser->get('last_name');
			$recordExternalTraining = Vtiger_Record_Model::getInstanceById($recordId, 'ExternalTraining');
			$requestedById = $recordExternalTraining->get('name');
			$refNo = $recordExternalTraining->get('cf_7354');
			$startDate = $recordExternalTraining->get('cf_7364');
			$endDate = $recordExternalTraining->get('cf_7366');
			$totalSum = $recordExternalTraining->get('cf_7374');


			$CEOApproval = $recordExternalTraining->get('cf_7386');
			$CEOApprovalDate = $recordExternalTraining->get('cf_7396');
			
			$CFOApproval = $recordExternalTraining->get('cf_7384');
			$CFOApprovalDate = $recordExternalTraining->get('cf_7394');
	
			$HRApproval = $recordExternalTraining->get('cf_7382');
			$HRApprovalDate = $recordExternalTraining->get('cf_7392');
	
			$lineManagerApproval = $recordExternalTraining->get('cf_7380');
			$lineManagerApprovalDate = $recordExternalTraining->get('cf_7390');			


		// Fetch request user email		
		$queryUser = $adb->pquery("
								SELECT vtiger_userlistcf.cf_3355, vtiger_userlist.name
								FROM vtiger_userlistcf
								INNER JOIN vtiger_userlist ON vtiger_userlist.userlistid = vtiger_userlistcf.userlistid
								WHERE vtiger_userlistcf.userlistid = ?",array($requestedById));
		$recordCreatorEmail = trim($adb->query_result($queryUser, 0, 'cf_3355'));
		$currentEmployeeName = trim($adb->query_result($queryUser, 0, 'name'));

		// Fetch current user's head
		$queryHead = $adb->pquery("SELECT vtiger_userlistcf.cf_3355, vtiger_userlist.name
															FROM vtiger_userlistcf
															INNER JOIN vtiger_userlist ON vtiger_userlist.userlistid = vtiger_userlistcf.userlistid
															WHERE vtiger_userlistcf.userlistid IN (
																SELECT cf_3385
																FROM vtiger_userlistcf
																WHERE userlistid = ?)", array($requestedById));
		$headEmail = $adb->query_result($queryHead, 0, 'cf_3355');

		// Fetch HR Manager
		$queryHRManager = $adb->pquery("SELECT .vtiger_userlistcf.cf_3355
															FROM `vtiger_userlistcf` 
															WHERE cf_3385 = 412373 AND cf_3421 = 85805 
															AND cf_3353 = 85757 AND cf_3349 = 414370 
															AND cf_6206 = 'Active'");
		$HRMangerEmail = $adb->query_result($queryHRManager, 0, 'cf_3355');


			// CEO Approval
			if  ($currentUserEmail == 's.khan@globalinklogistics.com'){
				$sqlUpdate = "UPDATE vtiger_externaltrainingcf SET cf_7386 = ?, cf_7396 = ? WHERE externaltrainingid = ?";
				$sqlResult = $adb->pquery($sqlUpdate, array($currentUserName, $date, $recordId));
			}

			// CFO Approval
			if ($currentUserEmail == 's.mansoor@globalinklogistics.com'){
				$sqlUpdate = "UPDATE vtiger_externaltrainingcf SET cf_7384 = ?, cf_7394 = ? WHERE externaltrainingid = ?";
				$sqlResult = $adb->pquery($sqlUpdate, array($currentUserName, $date, $recordId));
			}

			// HR Approval
			if ($currentUserEmail == $HRMangerEmail){
				$sqlUpdate = "UPDATE vtiger_externaltrainingcf SET cf_7382 = ?, cf_7392 = ? WHERE externaltrainingid = ?";
				$sqlResult = $adb->pquery($sqlUpdate, array($currentUserName, $date, $recordId));
			}

			// Line Manager
			if ($currentUserEmail == $headEmail){
				$sqlUpdate = "UPDATE vtiger_externaltrainingcf SET cf_7380 = ?, cf_7390 = ? WHERE externaltrainingid = ?";
				$sqlResult = $adb->pquery($sqlUpdate, array($currentUserName, $date, $recordId));
			}

			// Adding people in CC
			$cc = $this->getInviteeList($recordId);
			// Add CEO in copy if totalSum more than 500
			if ($totalSum > 500) $cc[] = 's.khan@globalinklogistics.com';

/* 
			echo "<pre>";
			print_r($cc);
			exit; */

			//Gathering email info
			$details = array();
			$details['name'] = $currentEmployeeName;
			$details['fromEmail'] = $currentUserEmail;
			$details['to'] = $recordCreatorEmail;
			$details['cc'] = $cc;
			$details['approvedBy'] = $currentUserName;
			$details['recordId'] = $recordId;
			$details['refNo'] = $refNo;
			$details['startDate'] = $startDate;
			$details['endDate'] = $endDate;

			$this->sendApproveEmail($details);


				$loadUrl = "index.php?module=ExternalTraining&view=Detail&record=".$request->get('record');
						echo '<script> 
					var url= "'.$loadUrl.'"; 
					window.location = url; 
				</script>';
		}


		public function sendApproveEmail($details){

			$userName = $details['name'];
			$recordId = $details['recordId'];
			$refNo = $details['refNo'];
			$startDate = $details['startDate'];
			$endDate = $details['endDate'];
	
			$link = $_SERVER['SERVER_NAME'];
			$link .= "/index.php?module=ProbationAssessment&view=Detail&record=".$recordId;
			$date_time = date('Y-m-d H:i:s');
			$from = trim($details['fromEmail']);
			$to = trim($details['to']);

			$approvedBy = trim($details['approvedBy']);
			$cc = implode(',', $details['cc']);
	
			$body = '';
			$message_status = "The training $refNo has been approved by: $approvedBy";	
	
			$subject = 'External Training '.$refNo;
			$body .= "<html><head> <style> #tableBody tr td{ margin:3px; } </style> </head>
								<body><table id='tableBody'> ";
			$body .= "<tr><td colspan=2> $message_status </td></tr>";
			$body .= "<tr><td colspan=2> Type: External </td></tr>";
			if ($startDate) $body .= "<tr><td colspan=2> Start Date: $startDate  </td></tr>";
			if ($endDate) $body .= "<tr><td colspan=2> End Date: $endDate  </td></tr>";
			$body .= "<tr><td colspan=2> Approved By: $approvedBy </td></tr>
	
								<tr><td colspan=2>Link: <a href='$link'> Link to GEMS </a></td></tr>";
			$body .= "</table> </body> </html> ";
																		
			// Set content-type when sending HTML email
			$headers = "MIME-Version: 1.0" . "\n";
			$headers .= "Content-type:text/html;charset=UTF-8" . "\n";
		
			$headers .= $from . "\n";
			$headers .= 'Reply-To: '.$to.'' . "\n";
	
			require_once("modules/Emails/mail.php");
			$r = send_mail('ExternalTraining', $to, $from, $from, $subject, $body, $cc,'','','','',true);		
		}

		

		function getInviteeList($recordId){
			
			global $adb;
			$inviteUserList = array();
			$result_g = $adb->pquery("
				Select vtiger_users.email1
				FROM vtiger_invitees
				LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_invitees.inviteeid
				WHERE vtiger_invitees.activityid = ?", array($recordId));
			$noofrow = $adb->num_rows($result_g);
			for($i=0; $i<$noofrow ; $i++) $inviteUserList[] = $adb->query_result($result_g, $i, 'email1');
			return $inviteUserList;
			
		}

		



}
