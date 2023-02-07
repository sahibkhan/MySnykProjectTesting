<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

function businesscard_msg_handler($module,$recordId,$current_user_id, $recordModel){
    $adb = PearDatabase::getInstance();
    $message_status='';
    $creator_id = $recordModel->get('assigned_user_id');
    $creator_user_info = Users_Record_Model::getInstanceById($creator_id, 'Users');

    
    $creator_name = $creator_user_info->get('first_name').' '.$creator_user_info->get('last_name');
    $creator_email = $creator_user_info->get('email1');
    $creator_position = $creator_user_info->get('title');
    $creator_department = $creator_user_info->getDisplayValue('department_id');   

    $updatedby_id = $current_user_id;
    $updatedby_user = Users_Record_Model::getInstanceById($updatedby_id, 'Users');
    $updatedby_name = $updatedby_user->get('first_name').' '.$updatedby_user->get('last_name');


    $to = array();
    $cc = array();
    
    $to[] = 'Reception@globalinklogistics.com';
    $to[] = 'a.tyulyubekova@globalinklogistics.com';
    //$cc[] = 'r.gusseinov@globalinklogistics.com';

    $businesscard_info = Vtiger_Record_Model::getInstanceById($recordId, 'BusinessCard');

    $subject = 'BusinessCard request from ';
    $subject = $subject.$creator_name.'/'.$creator_position.'/'.$creator_department;
    $who_updated_email =  $updatedby_user->get('email1');	
	$who_updated_name = $updatedby_user->get('first_name').' '.$updatedby_user->get('last_name');

    $event_status = detect_message_status($recordId);
    if ($event_status == 1) {
        $message_status = "" . $updatedby_name . " has created a new ".$subject;
    }else if ($event_status > 1) {
        $message_status = "".$updatedby_name . " has updated ".$subject."";
    }
    foreach ($to as $email){
        $to_email .= $email.',';
    }
    
    send_businesscard_notification($creator_email,$creator_name,$creator_position,$creator_department,$who_updated_name, $who_updated_email, $module,$recordId,$subject,$message_status,$sum,$cc,$to_email);

    
    }

    function send_businesscard_notification($creator_email,$creator_name,$creator_position,$creator_department,$who_updated_name, $who_updated_email, $module,$recordId,$subject,$message_status,$sum,$cc,$to_email){
        global $site_URL;
        $link = $site_URL;
        $link .= 'index.php?module=BusinessCard&view=Detail&record='.$recordId;
        $to_emails = '';
        $cc_emails = '';
        $to_emails = $to_email[0];
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
        $body .= "<tr><td colspan=2>Business card request from $creator_name / $creator_position</td></tr>";
        $body .= "<tr><td colspan=2> $message_status </td></tr>";
        $body .= "<tr><td colspan=2 >Please see details on this link: <a href='$link'> Business Card Request </a></td></tr>";
        $body .= "</table> </body> </html> ";
                                    
        // Set content-type when sending HTML email
        $headers = "MIME-Version: 1.0" . "\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\n";
        $headers .= $from . "\n";
        $headers .= 'Reply-To: '.$to.'' . "\n";
        //control_emails_sending($to_email,$cc_email,$who_updated_email,$subject,$body,$headers,$link,$record);
        require_once("modules/Emails/mail.php");
        send_mail('QHSERequsition', $to_email, $from, $who_updated_email, $subject, $body,$cc_email,'','','','',true);
    } 

   

    function detect_message_status($record){
        $adb = PearDatabase::getInstance();
        $s_modtracker_basic = $adb->pquery("SELECT * FROM `vtiger_modtracker_basic` where `crmid` = $record");
        $count = $adb->num_rows($s_modtracker_basic);
        return $count;
    }
?>