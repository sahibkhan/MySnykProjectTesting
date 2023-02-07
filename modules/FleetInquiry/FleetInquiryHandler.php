<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

require_once 'modules/Emails/mail.php';
class FleetInquiryHandler extends VTEventHandler {

	function handleEvent($eventName, $entityData) {
		global $log, $adb;
		$moduleName = $entityData->getModuleName();
		if ($moduleName == 'FleetInquiry') {
			$isRequestNew = $entityData->isNew();
		  $requestStatus = $isRequestNew ? "created" : "updated";
			if($eventName == 'vtiger.entity.aftersave.final') {
				if ($requestStatus == "created") {
					$this->notifyOnNewFleetInquiryCreation($entityData);
				}	else {
					/* $entityDelta = new VTEntityDelta();
					$entityId = $entityData->getId();
					$current_fleet_status = $entityData->get('cf_3295'); */
					// echo "current updated status". $current_fleet_status;
					// $old_fleetInquiryStatus = $entityDelta->hasChanged($entityData->getModuleName(), $entityId, 'cf_3295');
					// echo "old fleetInquiryStatus = " . $old_fleetInquiryStatus;
				}		
				// $this->notifyOnFleetInquiryReject($entityData);			
			}
		}
		//exit;
	}

	function notifyOnNewFleetInquiryCreation($entityData){
		global $site_URL;
		$ccUserList = array();
		
		$moduleName = $entityData->getModuleName();
		$recordId = $entityData->getId();;
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$currentUserId = $currentUserModel->getId();
		$user_info = Vtiger_Record_Model::getInstanceById($currentUserId, 'Users');
 
		$assignedToId = $entityData->get('cf_3303');
		$fleetInquiryStatus = $entityData->get('cf_3295');
		$jobFileTitle = $entityData->get('cf_3297');
		$fleetInquiryNo = $entityData->get('cf_3301');
		$specialInstruction = $entityData->get('cf_2143');
 
		$loadUrl = "index.php?module=$moduleName&view=Detail&record=$recordId&app=FLEET";
		$link = $site_URL."".$loadUrl;

		$fleetInquiryRecordModel = Vtiger_Record_Model::getInstanceById($recordId, 'FleetInquiry');
	 
		$assignedUserModel = Vtiger_Record_Model::getInstanceById($assignedToId, 'Users');
		$toEmail = $assignedUserModel->get('email1');
		$toName = $assignedUserModel->get('first_name');

		$ownerUserModel = Vtiger_Record_Model::getInstanceById($fleetInquiryRecordModel->get('assigned_user_id'), 'Users');

		if ($fleetInquiryStatus == 'Accepted') {
			$ccUserList[] = array("email" => "a.zorin@globalinklogistics.com", "");
			$ccUserList[] = array("email" => "dispatcher@globalinklogistics.com", "");
		}
		$ccUserList[] = array("email" => "s.mehtab@globalinklogistics.com", "name"=> "");

		$subject = '[From Fleet Inquiry]  [ Inquiry Id : '.$recordId.' ] ' .$jobFileTitle;
		$contents = 'Fleet Inquiry No : '.$fleetInquiryNo. 
					'<br> Job Title : '.$jobFileTitle.
					'<br> Status :'.$fleetInquiryStatus.
					'<br><br> Special Instructions : '.$specialInstruction.
					'<br>Please see details on this link: <a href='.$link.'> Click To Follow Link </a>';
		
		$contactEmail = $ownerUserModel->get('email1');
		$name = $ownerUserModel->get('first_name');
		$fromEmail = $contactEmail;

		//$fromEmail = "erp.support@globalinklogistics.com";	
		// function send_mail($module, $to_email, $from_name, $from_email, $subject, $contents, $cc = '', $bcc = '', $attachment = '', $emailid = '', $logo = '', $useGivenFromEmailAddress = false, $useSignature = 'Yes', $inReplyToMessageId = '')
		//send mail to assigned to user
		// $mail_status = send_mail('FleetInquiry',$to_email,$name,$from_email,$subject,$contents,$cc);

		$options = [];
		$options['creatorName'] = $name;
		$options['fromEmail'] = $fromEmail;
		$options['toEmail'] = $toEmail;
		$options['subject'] = $subject;
		$options['recordId'] = $recordId;
		$options['bodyTitle'] = "";
		$options['body'] = $contents;
		$options['cc'] = $ccUserList;

		// send_mail('FleetInquiry',$fromEmail,$HELPDESK_SUPPORT_NAME,$HELPDESK_SUPPORT_EMAIL_ID,$subject,$email_body);

		/* $toEmail = "r.gusseinov@globalinklogistics.com";
		$mail_status = send_mail('FleetInquiry',$toEmail,$name,$fromEmail,$subject,$contents, $ccUserList);
		if ($mail_status != '') {
			$mail_status_str = $toEmail;
			echo $mail_error_status = getMailErrorString($mail_status_str);
		} */

		$this->sendEmail($options);
		//send mail to the coordinator who created the fleet inquiry)
		// $mail_status = send_mail('FleetInquiry',$contact_email,$to_name,$to_email,$subject,$contents);

 		$options = array();
		$options['creatorName'] = $toName;
		$options['fromEmail'] = $contactEmail;
		$options['toEmail'] = $toEmail;
		$options['subject'] = $subject;
		$options['recordId'] = $recordId;
		$options['bodyTitle'] = "";
		$options['body'] = $contents;
		$options['cc'] = array();
		$this->sendEmail($options);
	}

	function notifyOnFleetInquiryReject($entityData){
		global $adb, $site_URL;
		$ccUserList = array();
		
		$moduleName = $entityData->getModuleName();
		$recordId = $entityData->getId();;
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$currentUserId = $currentUserModel->getId();
		$user_info = Vtiger_Record_Model::getInstanceById($currentUserId, 'Users');
 
		$old_fleet_inquiry_status = '';
		$assigned_user_id = 0;

		if(!empty($recordId)){
			// $fleet_inquiry_info = Vtiger_Record_Model::getInstanceById($recordId, 'FleetInquiry');

			$old_fleet_inquiry_status = $entityData->get('cf_3295');
			$assigned_user_id = $entityData->get('assigned_user_id');	 
		
			//To Send Notification if Inquiry Status changed to Rejected
			$fleet_inquiry_status = $entityData->get('cf_3295');
			$rejection_reason = $entityData->get('cf_6114');
			/* if($fleet_inquiry_status=='Rejected' && ($old_fleet_inquiry_status=='Pending' || $old_fleet_inquiry_status=='Accepted'))
			{
				$rejection_reason = $entityData->get('cf_6114');
				if(empty($rejection_reason))
				{					
					$_SESSION['rejection_reason'] = '1';			
					//$loadUrl = $recordModel->getEditViewUrl();
					//header("Location: $loadUrl");
					//exit;
				}
				else{ */
					// $request->set('cf_6116','No');//Rejection Confirmed
					
		
				  $adb->pquery('UPDATE vtiger_fleetinquirycf SET cf_6116 = ? WHERE fleetinquiryid = ?', array('No', $recordId));

					$currentUserModel = Users_Record_Model::getCurrentUserModel();
					$currentUserId = $currentUserModel->getId();
					$fleetCoordinator = $currentUserModel->get('first_name').' '.$currentUserModel->get('last_name');
					
					$body  = "<p>Dear Nikolay,</p>";  		
					$body .= "<p>Writing just to let you know that we are rejecting below fleet inquiry due to following reason.</p>";
					$body .="<p>".$rejection_reason."</p>";
					$body .="<pre>Please see details on this link:<a href='/index.php?module=FleetInquiry&view=Edit&record=".$recordId."&app=MARKETING'>Click To Follow Inquiry</a></p>";
					$body .="<p>Regards,</p>";
					$body .="<p><strong>".$fleetCoordinator."</strong>, RTD Coordinator </p>";
					$body .="<p><strong>Globalink Logistics - </strong>52, Kabanbai Batyr Street, 050010, Almaty, Kazakhstan&nbsp;<br />";
					
					$from = $currentUserModel->get('email1');
					$to = "n.semenov@globalinklogistics.com";
					$subject = "Fleet Inquiry Rejection Notification";

					// $to = "n.semenov@globalinklogistics.com;k.bhat@globalinklogistics.com;a.oriyashova@globalinklogistics.com";
					// $email = 's.mehtab@globalinklogistics.com';

					/* $ccUserList[] = array("email" => "n.semenov@globalinklogistics.com", "name"=> "");
					$ccUserList[] = array("email" => "k.bhat@globalinklogistics.com", "name"=> "");
					$ccUserList[] = array("email" => "a.oriyashova@globalinklogistics.com", "name"=> "");
					$ccUserList[] = array("email" => "s.mehtab@globalinklogistics.com", "name"=> ""); */				
		
					/* $from = "From: ".$from." <".$from.">";
					$headers = "MIME-Version: 1.0" . "\n";
					$headers .= "Content-type:text/html;charset=UTF-8" . "\n";
					$headers .= $from . "\n";
					$headers .= 'Reply-To: '.$to.'' . "\n";
					$headers .= "CC:" . $email . "\r\n"; */
					
					// mail($to,"Fleet Inquiry Rejection Notification",$body,$headers);

					$options = array();
					$options['fromEmail'] = $from;
					$options['toEmail'] = $to;
					$options['subject'] = $subject;
					$options['recordId'] = $recordId;
					$options['body'] = $body;
					$options['cc'] = $ccUserList;
					$this->sendEmail($options);
				// }
			// }
		}
	}



	function sendEmail($options){
		require_once 'vtlib/Vtiger/Mailer.php';
		// $creatorName = $options['creatorName'];
		$from = $options['fromEmail'];
		$to = $options['toEmail'];
		$subject = $options['subject'];
		$recordId = $options['recordId'];
		$messageBody = $options['body'];
		$recipients = $options['cc'];

		// $to = "r.gusseinov@globalinklogistics.com";

		$emailTemplate = '';
		$emailTemplate .= "<html><head></head><body>";
		$emailTemplate .= "$messageBody";
		$emailTemplate .= "</body> </html> ";

		global $HELPDESK_SUPPORT_EMAIL_ID;
		$mailer = new Vtiger_Mailer();
		$mailer->IsHTML(true);
		$mailer->ConfigSenderInfo($from);
		$mailer->Subject = $subject;

		$mailer->Body = $emailTemplate;
		$mailer->AddAddress($to);
		$mailer->clearCCs();

		foreach($recipients as $recipient){
			$mailer->AddCC($recipient['email'], $recipient['name']);
		}

		$status = $mailer->Send(true);		
	}

}

?>
