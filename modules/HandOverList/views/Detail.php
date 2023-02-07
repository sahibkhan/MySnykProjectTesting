<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class HandOverList_Detail_View extends Vtiger_Detail_View {
	protected $record = false;
	protected $isAjaxEnabled = null;

	function __construct() {
		parent::__construct();
		$this->exposeMethod('doApprove');
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
		$recordHandOverList = Vtiger_Record_Model::getInstanceById($recordId, 'HandOverList');
		$currentUserEmail = strtolower($currentUserModel->get('email1'));
		$currentUserName = $currentUserModel->get('first_name').' '.$currentUserModel->get('last_name');
		$approvalHistory = array();
		$nextApprovalPartieName = '';
		$refNo = $recordHandOverList->get('cf_7528');
		$requestedById = $recordHandOverList->get('name');
		$takeOverBy = $recordHandOverList->get('cf_7510');

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

		// Fetch takeover by	
		$queryUser = $adb->pquery("
								SELECT vtiger_userlistcf.cf_3355, vtiger_userlist.name
								FROM vtiger_userlistcf
								INNER JOIN vtiger_userlist ON vtiger_userlist.userlistid = vtiger_userlistcf.userlistid
								WHERE vtiger_userlistcf.userlistid = ?",array($takeOverBy));
		$takeOverByEmail = trim(strtolower($adb->query_result($queryUser, 0, 'cf_3355')));
		// $currentEmployeeName = trim($adb->query_result($queryUser, 0, 'name'));

		$approvedBy = '';
		// HEAD Approval
		if ($currentUserEmail == $userHeadEmail){
			$sqlUpdate = "UPDATE vtiger_handoverlistcf SET cf_7524 = ?, cf_7526 = ? WHERE handoverlistid = ?";
			$sqlResult = $adb->pquery($sqlUpdate, array($currentUserName, $date, $recordId));
			$approvedBy = $currentUserName;
		}

		// Handover (Employee) Approval
		if ($currentUserEmail == $recordCreatorEmail){
			$sqlUpdate = "UPDATE vtiger_handoverlistcf SET cf_7516 = ?, cf_7518 = ? WHERE handoverlistid = ?";
			$sqlResult = $adb->pquery($sqlUpdate, array($currentUserName, $date, $recordId));
			$approvedBy = $currentUserName;
		}
		
		// Takeover by Approval
		if ($currentUserEmail == $takeOverByEmail){
			$sqlUpdate = "UPDATE vtiger_handoverlistcf SET cf_7520 = ?, cf_7522 = ? WHERE handoverlistid = ?";
			$sqlResult = $adb->pquery($sqlUpdate, array($currentUserName, $date, $recordId));
			$approvedBy = $currentUserName;
		}
		
		// Add in CC
		$cc[] = $userHeadEmail;
		$cc[] = $recordCreatorEmail;
		$cc[] = $takeOverByEmail;
		$cc[] = 'hr@globalinklogistics.com';
	
		/* 

		// Head
		if (empty($recordHandOverList->get('cf_7336'))) {
			$nextApprovalPartieName = $userHeadName;
		} else {
			$approvalHistory[] = array("signature" => "Head of department Signature", "name" => $nextApprovalPartieName, "date" => $recordHandOverList->get('cf_7344'));
		}

		// Employee
		if (empty($recordHandOverList->get('cf_7334'))) {
			$nextApprovalPartieName = $currentEmployeeName;			
		} else {			
			$approvalHistory[] = array("signature" => "Employee Signature", "name" => $nextApprovalPartieName, "date" => $recordHandOverList->get('cf_7342'));
		}
		
		// HR
		if (empty($recordHandOverList->get('cf_7332'))){
			$nextApprovalPartieName = $HRMangerName;
		} else {			
			$approvalHistory[] = array("signature" => "HR Signature", "name" => $nextApprovalPartieName, "date" => $recordHandOverList->get('cf_7340'));
		}
 */
			//Gathering email info
			$details = array();
			$details['name'] = $currentEmployeeName;
			$details['fromEmail'] = $currentUserEmail;
			$details['to'] = $recordCreatorEmail;
			$details['cc'] = $cc;
			$details['approvedBy'] = $approvedBy;
			$details['recordId'] = $recordId;
			$details['refNo'] = $refNo;
			// $details['approvalHistory'] = $approvalHistory;
			$approvalHistory = [];
			$this->sendApproveEmail($details);

		$loadUrl = "index.php?module=HandOverList&view=Detail&record=".$recordId;
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

		$link = $_SERVER['SERVER_NAME'];
		$link .= "/index.php?module=HandOverList&view=Detail&record=".$recordId;
		$date_time = date('Y-m-d H:i:s');
		$from = trim($details['fromEmail']);
		$to = trim($details['to']);
		// $to = 'r.gusseinov@globalinklogistics.com';
		$approvedBy = trim($details['approvedBy']);
		$cc = implode(',', $details['cc']);

		$body = '';
		$message_status = "Handover & Takeover Report for $userName <br> Please follow below link and approve request $refNo";
		$message_footer = '';
		

/* 		if ($approvalHistory){
			$message_footer = 'Approved by:<br/>';
			foreach ($approvalHistory as $value){
				$message_footer .= $value['signature'].': '.$value['name'].' Date: '.$value['date'].', ';
			}
		} */
		
		$message_footer = 'Approved By: '.$approvedBy;

		
/* 		$body  = '';
		$body .="<p>".$recordModel->getDisplayValue('name')."</p>";
		$body .="<p>Hand Over and Take over for ".$recordModel->getDisplayValue('name')."<br/>";
		$body .="Employee Signature: ".$recordModel->getDisplayValue('name')." (".$recordModel->get('cf_6672').")<br/>";
		$body .="Receiver Signature: ".$recordModel->getDisplayValue('cf_6674')." (".$recordModel->get('cf_6676').")<br/>";
		$body .="Head of Department Signature: ".$recordModel->getDisplayValue('cf_6678')." (".$recordModel->get('cf_6680').")<br/>";
		$body .= "<br>Please see details on this link: <a href='$link'> Click To Follow Link </a></p>";
		$body .="<p>Regards,</p>";
		$body .="<p><strong>".$created_by_name."</strong></p>";
		$body .="<p><strong>Globalink Logistics - </strong></p>"; */


		$subject = 'Handover & Takeover Report: '.$refNo;
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
		$r = send_mail('HandOverList', $to, $from, $from, $subject, $body, $cc,'','','','',true);		
	}
	
}
