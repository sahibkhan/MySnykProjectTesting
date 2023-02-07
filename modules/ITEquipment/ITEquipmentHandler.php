<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

function itequipment_msg_handler($module,$recordId,$current_user, $recordModel){
    $adb = PearDatabase::getInstance();

    $creator_id = $recordModel->get('assigned_user_id');
    $creator_user_info = Users_Record_Model::getInstanceById($creator_id, 'Users');

    
    $creator_name = $creator_user_info->get('first_name').' '.$creator_user_info->get('last_name');
    $creator_email = $creator_user_info->get('email1');

    $updatedby_id = $current_user;
    $updatedby_user = Users_Record_Model::getInstanceById($updatedby_id, 'Users');
    $updatedby_name = $updatedby_user->get('first_name').' '.$updatedby_user->get('last_name');
    
    $to = [];
    $cc = [];
    $to_email = "";

    if ($creator_email != 'd.bashayev@globalinklogistics.com') $to[] = $creator_email;
    $to[] = 'a.aristov@globalinklogistics.com';
    $to[] = 's.aftab@globalinklogistics.com';
    $to[] = 's.mansoor@globalinklogistics.com';
    $to[] = 'm.makhmutov@globalinklogistics.com';
    // $to[] = 'r.gusseinov@globalinklogistics.com';

    if ($module==='ITEquipment') {
        $sql = $adb->pquery("SELECT `cf`.*,`user`.`location_id` 
                            FROM `vtiger_userlistcf` AS `cf` 
                            INNER JOIN `vtiger_users` AS `user` ON `cf`.`cf_3355`=`user`.`email1` 
                            INNER JOIN `vtiger_crmentity` AS `crm` ON `crm`.`crmid` = `cf`.`userlistid` 
                            WHERE `user`.`id`='".$creator_id."' AND `crm`.`deleted` = 0
                            LIMIT 1");
        if ($adb->num_rows($sql)>0) {
            $arr = $adb->fetch_array($sql);
            if ((int)$arr['cf_3385']!==412373&&(int)$arr['location_id']!==85805) {
                $sql1 = $adb->pquery("SELECT `cf_3355` FROM `vtiger_userlistcf` WHERE `userlistid`='".$arr['cf_3385']."' LIMIT 1");
                if ($adb->num_rows($sql1)>0) {
                    $arr1 = $adb->fetch_array($sql1);
                    $to[] = $arr1['cf_3355'];
                }
            }
        }
    }

    $itequip_info = Vtiger_Record_Model::getInstanceById($recordId, 'ITEquipment');
    $ceo_signature_checking = $itequip_info->get('cf_4539');
    $it_signature = $itequip_info->get('cf_4541');
    $it_signature_date = $itequip_info->get('cf_4543');
    
    $fd_signature = $itequip_info->get('cf_4545');
    $fd_signature_date = $itequip_info->get('cf_4547');
    
    $ceo_signature = $itequip_info->get('cf_4549');
    $ceo_signature_date = $itequip_info->get('cf_4551');

    $GPM = $itequip_info->get('cf_6444');
    $GPM_signature_date = $itequip_info->get('cf_6446');
    
    $subject = $itequip_info->get('name');
    $subject = 'IT equipment request '.$subject;

    $who_updated_email =  $updatedby_user->get('email1');	
	$who_updated_name = $updatedby_user->get('first_name').' '.$updatedby_user->get('last_name');


    $event_status = detect_message_status($recordId);
		if ($event_status == 1) {
			$message_status = "" . $updatedby_name . " has created a new ".$subject;
		}else if ($event_status > 1) {
			$message_status = "".$updatedby_name . " has updated ".$subject."";
		}
		if ($ceo_signature_checking == 0 && $itequip_info->get('cf_4533') < 500){
			foreach ($to as $email) {
				$to_email .= $email.',';
			}
			// $ceoapp = 'No need to confirm the CEO';
			$ceoapp = "";
			send_itequipments_notification($ceoapp,$creator_email,$creator_name,$who_updated_email,$who_updated_name,$module,$recordId,$subject,$message_status,$sum,$cc,$to_email,$cc_email, $it_signature, $it_signature_date, $fd_signature, $fd_signature_date, '', '', $GPM, $GPM_signature_date);
		}else if($ceo_signature_checking == 1 || $itequip_info->get('cf_4533') >= 500){
			$to[] = 's.khan@globalinklogistics.com';
			foreach ($to as $email) {
				$to_email .= $email.',';
			}
			// $ceoapp = 'Need to confirm the CEO ✔';
			$ceoapp = "";
			send_itequipments_notification($ceoapp, $creator_email,$creator_name,$who_updated_email,$who_updated_name,$module,$recordId,$subject,$message_status,$sum,$cc,$to_email,$cc_email, $it_signature, $it_signature_date, $fd_signature, $fd_signature_date, $ceo_signature,$ceo_signature_date, $GPM, $GPM_signature_date);
		}	 

    }


    function send_itequipments_notification($ceoapp, $creator_email,$creator_name,$who_updated_email,$who_updated_name,$module,$recordId,$subject,$message_status,$sum,$cc,$to_email,$cc_email, $it_signature, $it_signature_date, $fd_signature, $fd_signature_date,$ceo_signature,$ceo_signature_date, $GPM, $GPM_signature_date){
        global $site_URL;
        $link = $site_URL;
        $link .= 'index.php?module=ITEquipment&view=Detail&record='.$recordId;
        $to_emails = '';
        $cc_emails = '';
        $to_emails = $to_email;
        // $to_emails = 'r.gusseinov@globalinklogistics.com';

        //$from = 'From: '.$who_updated_name.' <'.$who_updated_email.'>';   
        $from = $who_updated_email;

        $body = '';
        $body .= "<html>
                  <head>
                    <style> 
                      #calendar_notification tr td{ margin:3px;}
                      .edited {font-weight: bold; color:green;}
                    </style> 
                 </head>
                <body><table id='calendar_notification'> ";
        $body .= "<tr><td colspan=2>IT Manager Signature: $it_signature, date:$it_signature_date</td></tr>";
        $body .= "<tr><td colspan=2>CFO Signature: $fd_signature, date:$fd_signature_date</td></tr>";
        $body .= "<tr><td colspan=2>CEO Signature: $ceo_signature, date:$ceo_signature_date</td></tr>";
        $body .= "<tr><td colspan=2>Group Procurement Manager Signature: $GPM, date:$GPM_signature_date</td></tr>";
        $body .= "<tr><td colspan=2> $message_status </td></tr>";
        $body .= "<tr><td colspan=2 >Please see details on this link: <a href='$link'> ITEquipment Request </a></td></tr>";
        $body .= "<tr><td colspan=2 style='font-family:Arial, Tahoma, Verdana; font-size:12px;font-weight:bold;'>$ceoapp</td></tr><br/>";
        
        $body .= "</table> </body> </html> ";
                                    
        // Set content-type when sending HTML email
        $headers = "MIME-Version: 1.0" . "\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\n";
        $headers .= $from . "\n";
        $headers .= 'Reply-To: '.$to.'' . "\n";
       
        
		require_once("modules/Emails/mail.php");
        $r = send_mail('ITEquipment', $to_emails, $from, $from, $subject, $body, $cc ,'','','','',true);
        
        //mail($to_email,$subject,$body,$headers);
        //control_emails_sending($to_email,$cc_email,$who_updated_email,$subject,$body,$headers,$link,$record);
    } 


    function detect_message_status($record){
        $adb = PearDatabase::getInstance();
        $s_modtracker_basic = $adb->pquery("SELECT * FROM `vtiger_modtracker_basic` where `crmid` = $record");
        $count = $adb->num_rows($s_modtracker_basic);
        return $count;
    }
?>