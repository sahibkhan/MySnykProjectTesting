<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

function furniture_msg_handler($module,$recordId,$current_user_id, $recordModel){
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
		
	$to[] = '';	
    
    $furniture_info = Vtiger_Record_Model::getInstanceById($recordId, 'FurnitureRequest');
    $department_id = $furniture_info->get('cf_4697');
	$branch_name = $furniture_info->get('cf_4795');
    $department_name = $furniture_info->getDisplayValue('cf_4697');
    
    if($branch_name != ''){
        $department_name = $branch_name;
    }

    $head_dep_login = get_branch_details($department_name, 'manager_login');
    $head_user_name = get_user_name_by_username($head_dep_login);
    $head_email = $head_dep_login.'@globalinklogistics.com';
    
    /*
		Get request creator head of department 
    */
    $creator_email2 = trim($creator_email);
    $s_userdata = $adb->pquery("
                            SELECT vtiger_userlistcf.* 
                            FROM `vtiger_userlist`
                            INNER JOIN `vtiger_userlistcf` ON vtiger_userlist.userlistid =  vtiger_userlistcf.userlistid	
                            where vtiger_userlistcf.cf_3355 = '".$creator_email2."'");
    $r_userdata = $adb->fetch_array($s_userdata);

    $general_manager_id = $r_userdata['cf_3385'];
    $user_head_info = get_userlist_details($general_manager_id);
    $head_email = $user_head_info['cf_3355']; //Head Department Email
    
    $to[] = "s.mansoor@globalinklogistics.com";
	// $to[] = "r.gusseinov@globalinklogistics.com";
	$to[] = "z.kazlykov@globalinklogistics.com";
	$to[] = $creator_email;

    $ceo_signature_checking = $furniture_info->get('cf_4701');
    $head_signature = $furniture_info->get('cf_4705');
    $head_signature_date = $furniture_info->get('cf_4707');
    
    $finance_signature = $furniture_info->get('cf_4709');
    $finance_signature_date = $furniture_info->get('cf_4711');
    
    $ceo_signature = $furniture_info->get('cf_4713');
    $ceo_signature_date = $furniture_info->get('cf_4715');
    
    $subject = $furniture_info->get('name');
    $subject = $subject.' Asset Request';
    $who_updated_email =  $updatedby_user->get('email1');	
    $who_updated_name = $updatedby_user->get('first_name').' '.$updatedby_user->get('last_name');
    
    $event_status = detect_message_status_asset($recordId);
    if ($event_status == 1) {
        $message_status = "" . $updatedby_name . " has created a new ".$subject;
    }else if ($event_status > 1) {
        $message_status = "".$updatedby_name . " has updated ".$subject."";
    }
    if ($ceo_signature_checking == 0){
        //foreach ($to as $email){
            //$to_email .= $email.';';
        //}
        $ceoapp = 'No need to confirm the CEO';
        
    }else if($ceo_signature_checking == 1){
        //$to[] = $head_email;
        $to[] = 's.khan@globalinklogistics.com';
        //foreach ($to as $email){
        //	$to_email .= $email.';';
        //}
        $ceoapp = 'Need to confirm the CEO ✔';
    }
        furniture_email_notification($ceoapp, $creator_email,$creator_name,$creator_position,$creator_department,$who_updated_name, $who_updated_email, $module,$recordId,$subject,$message_status,$sum,$cc,$to,$head_signature, $head_signature_date, $finance_signature, $finance_signature_date, $ceo_signature, $ceo_signature_date);
		
    }

    function furniture_email_notification($ceoapp, $creator_email,$creator_name,$creator_position,$creator_department,$who_updated_name, $who_updated_email, $module,$recordId,$subject,$message_status,$sum,$cc,$to_email,$head_signature, $head_signature_date, $finance_signature, $finance_signature_date, $ceo_signature, $ceo_signature_date){
        global $site_URL;
        $link = $site_URL;
		$link .= 'index.php?module=FurnitureRequest&view=Detail&record='.$recordId;
		//$to_emails = '';
		//$cc_emails = '';
		//$to_emails = $to_email[0];
		
		 foreach ($to_email as $p_email){
			$sentto_email .= $p_email.',';
		}		
		
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
		$body .= "<tr><td colspan=2>Head approval : $head_signature, date:$head_signature_date</td></tr>";
		$body .= "<tr><td colspan=2>CFO Approval: $finance_signature, date:$finance_signature_date</td></tr>";
		$body .= "<tr><td colspan=2>CEO Approval: $ceo_signature, date:$ceo_signature_date</td></tr>";
		$body .= "<tr><td colspan=2> $message_status </td></tr>";
		$body .= "<tr><td colspan=2 >Please see details on this link: <a href='$link'> Asset Request </a></td></tr>";
		$body .= "<tr><td colspan=2 style='font-family:Arial, Tahoma, Verdana; font-size:12px;font-weight:bold;'>$ceoapp</td></tr><br/>";		
		$body .= "</table> </body> </html> ";
									
		// Set content-type when sending HTML email
		$headers = "MIME-Version: 1.0" . "\n";
		$headers .= "Content-type:text/html;charset=UTF-8" . "\n";
		$headers .= $from . "\n";
        $headers .= 'Reply-To: '.$to.'' . "\n";
        require_once("modules/Emails/mail.php");
        
        send_mail('FurnitureRequest', $sentto_email, $from, $who_updated_email, $subject, $body,$cc_email,'','','','',true);
		//control_emails_sending($sentto_email,$cc_email,$who_updated_email,$subject,$body,$headers,$link,$record);
	}

    function detect_message_status_asset($record){
        $adb = PearDatabase::getInstance();
        $s_modtracker_basic = $adb->pquery("SELECT * FROM `vtiger_modtracker_basic` where `crmid` = $record");
        $count = $adb->num_rows($s_modtracker_basic);
        return $count;
    }

    function get_branch_details($city,$field){
        $adb = PearDatabase::getInstance();
        $sql_branch = $adb->pquery("SELECT * FROM `vtiger_branch_details` where `city`='".$city."'");
        $r_branch =  $adb->fetch_array($sql_branch);
        return $r_branch["$field"];
    }

    
    function get_user_name_by_username($username) {
        $adb = PearDatabase::getInstance();
        $usern = $adb->pquery("SELECT * FROM `vtiger_users` where `user_name` = '$username' ");
        $userna = $adb->fetch_array($usern);
        $first_name = $userna['first_name'];
        $last_name = $userna['last_name'];
        $full_name .= $first_name.' '.$last_name;
        
        return $full_name;
    }
    
    function getUserEmailByUserName($username) {
        $adb = PearDatabase::getInstance();
        $usern = $adb->pquery("SELECT email1 FROM `vtiger_users` where `user_name` = '$username' ");
        $userna = $adb->fetch_array($usern);
        $email = $userna['email1'];        
        return $email;
    }

    function get_userlist_details($record){
        $adb = PearDatabase::getInstance();
        $sql = $adb->pquery("
                            SELECT * FROM `vtiger_userlist`
                            INNER JOIN `vtiger_userlistcf` ON vtiger_userlist.userlistid =  vtiger_userlistcf.userlistid	
                            where vtiger_userlist.userlistid='".$record."'");
        if (empty($sql) === false) {
          $row = $adb->fetch_array($sql);
          return $row;
        }
        else {
          return $row;
        }
    }
?>