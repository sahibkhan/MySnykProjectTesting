<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class ExitList_Detail_View extends Vtiger_Detail_View {
	protected $record = false;
	protected $isAjaxEnabled = null;

	function __construct() {
		parent::__construct();
		$this->exposeMethod('handleReminder');
		$this->exposeMethod('myTest');
	}

	
	public function myTest(Vtiger_Request $request){

/* 		require_once('modules/ExitList/ExitList.php');
		$component = new ExitList();
		$result = $component->getListOfApprovers();

		$data['status'] = $result;
		return json_encode($data); */

	}


	public function handleReminder(Vtiger_Request $request){
		global $adb;
		$data = array();
		$details = array();
		$recordId = $request->get('record');
		require_once('modules/ExitList/ExitList.php');
		$pendingApprovers = new ExitList();

		foreach ($pendingApprovers as $pendingApprover){
			$approverName = $pendingApprover['approverName'];
			$approverEmail = $pendingApprover['approverEmail'];
			$exitListEntryId = $pendingApprover['exitListEntryId'];

			$recordExitList = Vtiger_Record_Model::getInstanceById($recordId, 'ExitList');
			$currentRefNo = $recordExitList->get('cf_7538');
			$recordCreatorId = $recordExitList->get('assigned_user_id');
			$employeeName = $recordExitList->getDisplayValue('name');

			$creatorRecord = Vtiger_Record_Model::getInstanceById($recordCreatorId, 'Users');
			$requestedByEmail = $creatorRecord->get('email1');
			$requestedByName = $creatorRecord->get('first_name').' '.$creatorRecord->get('last_name');
							
			$details['employeeName'] = $employeeName;
			$details['fromEmail'] = $requestedByEmail;
			$details['fromName'] = $requestedByName;
			$details['approverName'] = $approverName;
			$details['forwardToEmail'] = $approverEmail;

			$details['recordId'] = $recordId;
			$details['exitListId'] = $exitListEntryId;
			$details['refNo'] = $currentRefNo;
			$details['type'] = 'exitListReminder';			

			// echo "exitListEntryId = " . $exitListEntryId.'<br>';
			// $this->sendEmailNotification($details);
		}

		 $data['status'] = 'success';
		 return json_encode($data);
		
	}


	public function sendEmailNotification($details){

		$userName = $details['employeeName'];
		$recordId = $details['recordId'];
		$exitListId = $details['exitListId'];
		$approverName = $details['approverName'];
		$refNo = $details['refNo'];
		$forwardTo = $details['forwardTo'];
		$forwardToEmail = trim($details['forwardToEmail']);
		$type = $details['type'];
		$link = $_SERVER['SERVER_NAME'];
		$date_time = date('Y-m-d H:i:s');
		$from = trim($details['fromEmail']);
		
		$body = '';
		if ($type == 'exitListReminder'){
			$to = $forwardToEmail;
			$link .= "/live-gems/index.php?module=ExitList&view=Detail&record=".$recordId;
			$exitListLink = $_SERVER['SERVER_NAME'] . "/live-gems/index.php?module=ExitListEntries&view=Detail&record=".$exitListId;

			$message_status .= "<tr><td colspan=2> <b> Dear $approverName,</b> </td></tr>";
			$message_status .= "<tr><td colspan=2> </td></tr>";
			$message_status .= "<tr><td colspan=2> Please note your approval is missing in exit list of $userName, $userPosition. Kindly follow below link and approve request.</td></tr>";
			$message_status .= "<tr><td colspan=2> <a href='$exitListLink'> Exit list approval  </a> </td></tr>";
		}

		// if ($type == 'IT'){
			$subject = 'Exit List: '.$refNo;
			$body .= "<html><head> <style> #tableBody tr td{ margin:3px; } </style> </head>
								<body><table id='tableBody'> ";
			$body .= $message_status;
	

			$body .= "<tr><td colspan=2>Link: <a href='$link'> Link to Exit List </a></td></tr>";
			$body .= "</table> </body> </html> ";
																		
			// Set content-type when sending HTML email
			$headers = "MIME-Version: 1.0" . "\n";
			$headers .= "Content-type:text/html;charset=UTF-8" . "\n";
		
			$headers .= $from . "\n";
			$headers .= 'Reply-To: '.$to.'' . "\n";
			
			require_once("modules/Emails/mail.php");
			$r = send_mail('ExitList', 'r.gusseinov@globalinklogistics.com', $from, $from, $subject, $body, '','','','','',true);
		// }
		
	}


	public function getPendingApprovers($recordId){
		global $adb;
		$approverList = array();
		$queryApprovers = $adb->pquery("SELECT vtiger_userlist.name, vtiger_userlistcf.cf_3355, vtiger_crmentityrel.relcrmid

																FROM vtiger_crmentityrel
																INNER JOIN vtiger_exitlistentriescf ON vtiger_exitlistentriescf.exitlistentriesid = vtiger_crmentityrel.relcrmid
																INNER JOIN vtiger_exitlistentries ON vtiger_exitlistentries.exitlistentriesid = vtiger_exitlistentriescf.exitlistentriesid
																LEFT JOIN vtiger_userlist ON vtiger_userlist.userlistid = vtiger_exitlistentries.name
																LEFT JOIN vtiger_userlistcf ON vtiger_userlistcf.userlistid = vtiger_exitlistentries.name
																
																WHERE vtiger_crmentityrel.module = 'ExitList' AND vtiger_crmentityrel.crmid = ? 
																AND vtiger_exitlistentriescf.cf_7640 = ?", array($recordId, ''));
		$nRows = $adb->num_rows($queryApprovers);
		for ($i = 0; $i< $nRows; $i++){
			$approverEmail = strtolower($adb->query_result($queryApprovers, $i, 'cf_3355'));
			$approverName = $adb->query_result($queryApprovers, $i, 'name');
			$exitListEntryId = $adb->query_result($queryApprovers, $i, 'relcrmid');

			$approverList[] = array('exitListEntryId' => $exitListEntryId, 'approverName' => $approverName, 'approverEmail' => $approverEmail);
		}
		return $approverList;
	}

 

}
