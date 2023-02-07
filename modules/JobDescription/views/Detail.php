<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class JobDescription_Detail_View extends Vtiger_Detail_View {
	protected $record = false;
	protected $isAjaxEnabled = null;

	function __construct() {
		parent::__construct();
		$this->exposeMethod('showModuleSummaryView');
		// $this->exposeMethod('showModuleBasicView');
		$this->exposeMethod('doApprove');
		$this->exposeMethod('sendEmail');

	}

	/**
	 * Function shows basic detail for the record
	 * @param <type> $request
	 */
/* 	function showModuleBasicView($request) {

		$recordId = $request->get('record');
		$moduleName = $request->getModule();


		if(!$this->record){
			$this->record = Vtiger_DetailView_Model::getInstance($moduleName, $recordId);
		}
		$recordModel = $this->record->getRecord();

		$detailViewLinkParams = array('MODULE'=>$moduleName,'RECORD'=>$recordId);
		$detailViewLinks = $this->record->getDetailViewLinks($detailViewLinkParams);

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('MODULE_SUMMARY', $this->showModuleSummaryView($request));

		$viewer->assign('DETAILVIEW_LINKS', $detailViewLinks);
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('IS_AJAX_ENABLED', $this->isAjaxEnabled($recordModel));
		$viewer->assign('MODULE_NAME', $moduleName);

		$recordStrucure = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_DETAIL);
		$structuredValues = $recordStrucure->getStructure();

		$moduleModel = $recordModel->getModule();
		$viewer->assign('CURRENT_USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('RECORD_STRUCTURE', $structuredValues);
		$viewer->assign('BLOCK_LIST', $moduleModel->getBlocks());
		echo $viewer->view('DetailViewSummaryContents.tpl', $moduleName, true);
	}

 */

	function showModuleSummaryView($request) {
		$recordId = $request->get('record');
		$employeeName = $request->get('name');
		$moduleName = $request->getModule();
		$jobDescriptionModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
		$EMPLOYEE_NAME_POSITION = '';
		if ($jobDescriptionModel->get('cf_7552')){
			$employeeProfile = Vtiger_Record_Model::getInstanceById($jobDescriptionModel->get('cf_7552'), 'UserList');
			$EMPLOYEE_NAME_POSITION = $jobDescriptionModel->getDisplayValue('cf_7552').' / '.$employeeProfile->getDisplayValue('cf_823');
		}

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

		$viewer->assign('CURRENT_USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$pagingModel = new Vtiger_Paging_Model();
		$viewer->assign('PAGING_MODEL', $pagingModel);

		$picklistDependencyDatasource = Vtiger_DependencyPicklist::getPicklistDependencyDatasource($moduleName);
		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE', Vtiger_Functions::jsonEncode($picklistDependencyDatasource));
		$viewer->assign('EMPLOYEE_NAME_POSITION', $EMPLOYEE_NAME_POSITION);	

		return $viewer->view('ModuleSummaryView.tpl', $moduleName, true);
	}

	
	public function doApprove(Vtiger_Request $request){
		global $adb;
		$adb = PearDatabase::getInstance();
		$recordId = $request->get('record'); 
		$module = $request->get('module');
		$view = $request->get('view');
		$date = date("Y-m-d");
		$cc = array();
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$recordJobDescription = Vtiger_Record_Model::getInstanceById($recordId, 'JobDescription');
		$currentUserEmail = $currentUserModel->get('email1');
		$currentUserName = $currentUserModel->get('first_name').' '.$currentUserModel->get('last_name');
		$approvalHistory = array();
		$nextApprovalPartieName = '';
		$refNo = $recordJobDescription->get('cf_7266');
		$requestedById = $recordJobDescription->get('cf_7552');
		$requestedByName = $recordJobDescription->get('name');

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
		$userHeadEmail = $adb->query_result($queryHead, 0, 'cf_3355');
		$userHeadName = $adb->query_result($queryHead, 0, 'name');

		$approvedBy = '';
		// HEAD Approval
		if ($currentUserEmail == $userHeadEmail){
			$sqlUpdate = "UPDATE vtiger_jobdescriptioncf SET cf_7556 = ? WHERE jobdescriptionid = ?";
			$sqlResult = $adb->pquery($sqlUpdate, array($currentUserName, $recordId));
			$approvedBy = $currentUserName;
		}

		// Employee Approval
		if ($currentUserEmail == $recordCreatorEmail){
			$sqlUpdate = "UPDATE vtiger_jobdescriptioncf SET cf_7554 = ? WHERE jobdescriptionid = ?";
			$sqlResult = $adb->pquery($sqlUpdate, array($currentUserName, $recordId));
			$approvedBy = $currentUserName;
		}
		
		// Add in CC
		$cc = $userHeadEmail.',';
		$cc .= $recordCreatorEmail.',';
		$cc .= "r.gusseinov@globalinklogistics.com,";

		//Gathering email info
		$details = array();
		$details['name'] = $currentEmployeeName;
		$details['fromEmail'] = $currentUserEmail;
		$details['toEmails'] = $recordCreatorEmail;
		$details['requestedByName'] = $requestedByName;
		$details['cc'] = $cc;
		$details['approvedBy'] = $approvedBy;
		$details['recordId'] = $recordId;
		$details['refNo'] = $refNo;
		$details['approvalHistory'] = $approvalHistory;
		$details['type'] = 1;
		$approvalHistory = [];
		$this->sendApproveEmail($details);

		$loadUrl = "index.php?module=JobDescription&view=Detail&record=".$recordId;
        echo '<script> 
			var url= "'.$loadUrl.'"; 
			window.location = url; 
		</script>';
	}

	
	public function sendEmail(Vtiger_Request $request){
		global $adb;
 		$adb = PearDatabase::getInstance();
		$recordId = $request->get('record'); 
		$module = $request->get('module');
		$to = $request->get('to');
		$bodytext = json_decode($request->get('bodytext'));	
		$bodytext = str_replace("\n", "<br>", $bodytext);

		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$recordJobDescription = Vtiger_Record_Model::getInstanceById($recordId, 'JobDescription');
		$refNo = $recordJobDescription->get('cf_7266');
		$requestedByName = $recordJobDescription->getDisplayValue('name');
		$currentUserEmail = $currentUserModel->get('email1');
		$currentUserName = $currentUserModel->get('first_name').' '.$currentUserModel->get('last_name');

		
		$userArr = explode(',', $to);

		if ($userArr){
			foreach ($userArr as $userId){			
				$userModel = Vtiger_Record_Model::getInstanceById($userId, 'UserList');
				$toEmails .= trim($userModel->get('cf_3355')).',';
			}
		}
		
		$details = array();
	 

		$details['bodytext'] = $bodytext;
		$details['fromEmail'] = $currentUserEmail;
		$details['fromName'] = $currentUserName;
		$details['toEmails'] = $toEmails;
		$details['refNo'] = $refNo;
		$details['recordId'] = $recordId;
		$details['requestedByName'] = $requestedByName;
		// $details['cc'] = "hr@globalinklogistics.com";
		$details['type'] = 2;
		
		$this->sendApproveEmail($details);

		$loadUrl = "index.php?module=JobDescription&view=Detail&record=".$recordId;
        echo '<script> 
			var url= "'.$loadUrl.'"; 
			window.location = url; 
		</script>';
	}


	
	public function sendApproveEmail($details){

		$userName = $details['name'];
		$recordId = $details['recordId'];
		$bodytext = $details['bodytext'];
		$refNo = $details['refNo'];
		$toEmails = $details['toEmails'];
		$cc = $details['cc'];
		$requestedByName = $details['requestedByName'];
		$type = $details['type'];
		$approvedBy = $details['approvedBy'];

		$link = $_SERVER['SERVER_NAME'];
		$link .= "/index.php?module=JobDescription&view=Detail&record=".$recordId;
		$date_time = date('Y-m-d H:i:s');
		$from = trim($details['fromEmail']);
		// $to = "r.gusseinov@globalinklogistics.com";
		$to = $toEmails;

		if ($type == 1){
			$message_status = $approvedBy.' has approved the job description';
		} else
		if ($type == 2){
			$message_status = $bodytext;
		}
		
		$subject = 'Job description: '.$refNo;
		$body .= "<html><head> <style> #tableBody tr td{ margin:3px; } </style> </head>
							<body><table id='tableBody'> ";
		$body .= "<tr><td colspan=2> $message_status </td></tr>";
		$body .= "<tr><td colspan=2> Employee name: $requestedByName </td></tr>

							<tr><td colspan=2>Link: <a href='$link'> Link to GEMS </a></td></tr>";
		$body .= "</table> </body> </html> ";
																	
		// Set content-type when sending HTML email
		$headers = "MIME-Version: 1.0" . "\n";
		$headers .= "Content-type:text/html;charset=UTF-8" . "\n";
	
		$headers .= $from . "\n";
		$headers .= 'Reply-To: '.$to.'' . "\n";

		require_once("modules/Emails/mail.php");
		$r = send_mail('JobDescription', $to, $from, $from, $subject, $body, $cc,'','','','',true);		
	}
	
		
	

}
