<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class ExitListEntries_Detail_View extends Vtiger_Detail_View {
	protected $record = false;
	protected $isAjaxEnabled = null;

	function __construct() {
		parent::__construct();
		$this->exposeMethod('updateStatus');
	}

	
	public function updateStatus(Vtiger_Request $request){
		global $adb;
		$adb = PearDatabase::getInstance();
		$recordId = $request->get('record'); 
		$module = $request->get('module');
		$type = $request->get('type');		
		$date = date("Y-m-d");
	
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$recordExitListEntries = Vtiger_Record_Model::getInstanceById($recordId, 'ExitListEntries');
		$currentUserEmail = $currentUserModel->get('email1');
		$currentUserName = $currentUserModel->get('first_name').' '.$currentUserModel->get('last_name');
		$approvalHistory = array();
		$nextApprovalPartieName = '';
		$refNo = $recordExitListEntries->get('cf_7304');
		$requestedById = $recordExitListEntries->get('name');
		$exitListApprovalParties = $recordExitListEntries->getDisplayValue('name');
		$recordRequestedBy = $recordExitListEntries->get('name');


		// Fetch requested by
		$queryUserRequested = $adb->pquery("SELECT cf_3355
																FROM `vtiger_userlistcf`
																WHERE userlistid = ?",array($recordRequestedBy));
		$recordRequestedByEmail = trim($adb->query_result($queryUserRequested, 0, 'cf_3355'));


		// Fetch request user email		
		$queryUser = $adb->pquery("SELECT vtiger_userlist.name, vtiger_userlistcf.cf_3355
																FROM `vtiger_crmentityrel`
																INNER JOIN vtiger_exitlist ON vtiger_exitlist.exitlistid = vtiger_crmentityrel.crmid
																INNER JOIN vtiger_userlist ON vtiger_userlist.userlistid = vtiger_exitlist.name		
																INNER JOIN vtiger_userlistcf ON vtiger_userlistcf.userlistid = vtiger_exitlist.name		
																
																WHERE vtiger_crmentityrel.`relcrmid` = ?
																AND vtiger_crmentityrel.module = 'ExitList' 
																AND vtiger_crmentityrel.relmodule = 'ExitListEntries'",array($recordId));
		$recordCreatorEmail = trim($adb->query_result($queryUser, 0, 'cf_3355'));
		$resignUserName = trim($adb->query_result($queryUser, 0, 'name'));


		if ($currentUserEmail == $recordRequestedByEmail){
			$sqlUpdate = "UPDATE vtiger_exitlistentriescf SET cf_7626 = ?, cf_7628 = ?, cf_7640 = ? WHERE exitlistentriesid = ?";
			$sqlResult = $adb->pquery($sqlUpdate, array($currentUserName, $date, ucfirst($type), $recordId));
		}

		//Gathering email info
		$details = array();
		$details['name'] = $resignUserName;
		$details['exitListApprovalParties'] = $exitListApprovalParties;
		$details['fromEmail'] = $currentUserEmail;
		$details['to'] = $recordCreatorEmail;
		$details['cc'] = $cc;
		$details['recordId'] = $recordId;
		$details['type'] = $type;
		$this->sendUpdateByEmail($details);

		$loadUrl = "index.php?module=ExitListEntries&view=Detail&record=".$recordId;
        echo '<script> 
			var url= "'.$loadUrl.'"; 
			window.location = url; 
		</script>';
	}
	
	
	public function sendUpdateByEmail($details){

		$userName = $details['name'];
		$exitListApprovalParties = $details['exitListApprovalParties'];
		$recordId = $details['recordId'];
		if ($details['type'] == 'approve'){
			$type = 'approved';
		} else {
			$type = 'declined';
		}

		$approvalHistory = $details['approvalHistory'];

		$link = $_SERVER['SERVER_NAME'];
		$link .= "/index.php?module=ExitListEntries&view=Detail&record=".$recordId;
		$date_time = date('Y-m-d H:i:s');
		$from = trim($details['fromEmail']);
		// $to = trim($details['to']);
		$to = "r.gusseinov@globalinklogistics.com";

		$body = '';
		$message_status .= "<tr><td colspan=2> Dear $userName  </td></tr> ";
		$message_status .= "<tr><td colspan=2> Please note that $exitListApprovalParties has $type the exit list.</td></tr>";


		$subject = 'Exit List '.$recordId;
		$body .= "<html><head> <style> #tableBody tr td{ margin:3px; } </style> </head>
							<body><table id='tableBody'> ";
		$body .= $message_status;
		$body .= "<tr><td colspan=2>Link: <a href='$link'> Link to GEMS </a></td></tr>";
		$body .= "</table> </body> </html> ";
																	
		// Set content-type when sending HTML email
		$headers = "MIME-Version: 1.0" . "\n";
		$headers .= "Content-type:text/html;charset=UTF-8" . "\n";
	
		$headers .= $from . "\n";
		$headers .= 'Reply-To: '.$to.'' . "\n";

		require_once("modules/Emails/mail.php");
		$r = send_mail('ExitListEntries', $to, $from, $from, $subject, $body, $cc,'','','','',true);		
	}	
		
	

}
