<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

function leave_msg_handler($module,$record,$current_user, $recordModel){
		
    //$message_status;
    $adb = PearDatabase::getInstance();
    //Vtiger_Record_Model::getInstanceById($record, $moduleName);
   
    //$creator_id = get_crmentity_details($record,'smownerid');
    $creator_id = $recordModel->get('assigned_user_id');
    $updatedby_id = $current_user;
    $updatedby_user = Users_Record_Model::getInstanceById($updatedby_id, 'Users');
    //$updatedby_name = get_user_details($updatedby_id,2);
    $updatedby_name = $updatedby_user->get('first_name').' '.$updatedby_user->get('last_name');
    
    $creator_user = Users_Record_Model::getInstanceById($creator_id, 'Users');
    //$creator_email = get_user_details($creator_id,1);	
    $creator_email= $creator_user->get('email1');
    //$creator_name = get_user_details($creator_id,2);
    $creator_name = $creator_user->get('first_name').' '.$creator_user->get('last_name');   
    //$creator_position = get_user_details($creator_id,4);
    $creator_position = $creator_user->get('title');
    //$creator_department = get_user_details($creator_id,6);
    $creator_department = $creator_user->get('department');
    //$current_username = get_user_details($current_user, 3);
    $current_username = $updatedby_user->get('user_name');
    //$current_user_email = get_user_details($current_user,1);
    $current_user_email = $updatedby_user->get('email1');
    //$current_user_name = get_user_details($current_user,2);
    $current_user_name = $updatedby_name;
    
    $to = [];
    $cc = [];
    
    //$to[] = '';	
    $to[] = $creator_email;
    //if ($current_user_email!=='f.doszhanova@globalinklogistics.com') $to[] = $current_user_email;
    
    //$leave_info = get_leave_details($record);
    $leave_info = $recordModel;
    $department_id = $leave_info->get('cf_3467');
    $branch_name = $leave_info->get('cf_4801');
    $createdtime = $leave_info->get('CreatedTime');
    $requested_by_id = $leave_info->get('cf_3423');
    
    //$user_info = get_userlist_details($requested_by_id);		
    $user_info = Vtiger_Record_Model::getInstanceById($requested_by_id, 'UserList');
    $general_manager_id = $user_info->get('cf_3385');
    $requester_nm = $user_info->get('name');			

    //$s = '8/29/2011 11:16:12 AM';
    //$dt = new DateTime($createdtime);

    //$cdate = $dt->format('d/m/Y');
    $cdate = $createdtime;
    //$time = $dt->format('H:i:s');

        
    //$department_name = get_dep_details($department_id);
    
    //if($branch_name != ''){
    //	$department_name = $branch_name;
    //}
    
    //$head_dep_login = get_branch_details($department_name, 'manager_login');
    //if($current_username == $head_dep_login){
    //	$head_dep_login = 'r.gusseinov';
    //}
    
    //$head_user_name = get_user_name_by_username($head_dep_login);
    //$head_email = $head_dep_login.'@globalinklogistics.com';
    //Mehtab Code :: To get Head of User through profile field (General Manager)
    //$user_head_info = get_userlist_details($general_manager_id);
    $user_head_info = Vtiger_Record_Model::getInstanceById($general_manager_id, 'UserList');
    //$rs_query = mysql_query("SELECT * FROM vtiger_userlistcf WHERE userlistid='".$general_manager_id."'");
    //$row_head = mysql_fetch_array($rs_query);

    $head_email = $user_head_info->get('cf_3355'); //Head Department Email
    $head_user_name = $user_head_info->get('name');
    //End Mehtab Code
    
    // No need to add in copy Mr.Khan, if leave less than 1 day
    //leave_info
    /*
        From: cf_3391
        Till: cf_3393
        time_from: cf_3395
        time_till: cf_3397
    
    */
    $date_from = $leave_info->get('cf_3391');
    $date_till = $leave_info->get('cf_3393');
    $hr_approval 	  = $leave_info->get('cf_3415');
    $hr_approval_date = $leave_info->get('cf_3417');
    $till_from_time = $leave_info->get('cf_5355');
    // Before sending to CEO, need to check was request approved by GM and HR
    if ($head_email == "s.khan@globalinklogistics.com"){
        if (!empty($hr_approval)){
            
            if ($head_email == "s.khan@globalinklogistics.com"){
                if (($date_till > $date_from)){
                    $to[] = $head_email;
                } else
                if ($date_from == $date_till){
                    $till_from_time = $leave_info->get('cf_5355');
                    if ($till_from_time == "09:00 - 18:00"){
                        $to[] = $head_email;
                    }
                }
            } else {
                $to[] = $head_email;
            }
        }
    } else $cc[] = $head_email;		

    //$to[]='';
    //$hr_username = get_branch_details('HR', 'manager_login');
    //$hr_email = $hr_username.'@globalinklogistics.com';
    $hr_email = 'hr@globalinklogistics.com';	
    //$hr_email = 'erp.support@globalinklogistics.com';
    $to[] = $hr_email;
    //$to[] = 'erp.support@globalinklogistics.com';
    //$to[] = 'reception@globalinklogistics.com';
    
    // For Lubov Belyakova
    $requester_email1 = $user_info->get('cf_3355');
    $branch_fd_users = array();
    $res_users = $adb->pquery("SELECT vtiger_users.email1
                              FROM `vtiger_users2group` 
                              INNER JOIN vtiger_users ON vtiger_users.id = vtiger_users2group.userid		
                              WHERE vtiger_users2group.`groupid` = 1298");
    while ($row_user = $adb->fetch_array($res_users)){
        $branch_fd_users[] = $row_user['email1'];
    }
    
    if (in_array($requester_email1, $branch_fd_users)){
        $to[] = 'l.belyakova@globalinklogistics.com';
    }
    // END
    
    $head_signature = $leave_info->get('cf_3411');
    $head_signature_date = $leave_info->get('cf_3413');
            
    $hr_signature = $leave_info->get('cf_3415');
    $hr_signature_date = $leave_info->get('cf_3417');

    $fd_signature = $leave_info->get('cf_6618');
    $fd_signature_date = $leave_info->get('cf_6620');
    
    $location_id = $leave_info->get('cf_3469');		
    

    
    //$subject = $leave_info['name'];
    $subject = "Request for leave from: ".$creator_name." ID: ".$record;
    //$who_updated_email = get_user_details($updatedby_id,1);	
    $who_updated_email = $updatedby_user->get('email1');
    //$who_updated_name = get_user_details($updatedby_id,2);
    $who_updated_name = $updatedby_user->get('first_name').' '.$updatedby_user->get('last_name');
    
    $event_status = detect_message_status_leave($record);
    //if ($event_status == 1) {
    //	$message_status = "" . $updatedby_name . " has created a new ".$subject;
    //}else if ($event_status > 1) {
    $message_status = $updatedby_name." has updated ".$subject;
    //}
    
    //remove current User Email from To Emails list
    // $remove_arr = array($current_user_email);
    // $final_arr = array_diff($to, $remove_arr);
    // $to = implode(",", $final_arr);
    //$cc[] = $current_user_email;
    
    /**
        Tbilisi branch
    */
    if ($location_id == 85832) $cc[] = "l.atamova@globalinklogistics.com";
    if ($location_id == 85947) $cc[] = "l.atamova@globalinklogistics.com";
    
    $cc[] = $who_updated_email;
            
    /** 
        If requester from Almaty / CTD then
        notify head of this requester 
    */
    //cf_3421 -  85805 Almaty
    //cf_3349 -  85837 CTD

    $requester_location = $user_info->get('cf_3421');
    $requester_department = $user_info->get('cf_3349');
    if (($requester_location == 85805) && ($requester_department == 85837)){
        $r_head_id = $user_info->get('cf_3387');
        //$requester_headinfo = get_userlist_details($r_head_id);
       
        $requester_headinfo =  Vtiger_Record_Model::getInstanceById($r_head_id, 'UserList');
        $cc[] = trim($requester_headinfo->get('cf_3355'));			 
    }
    
    /* End */
            
    $user_info = "Request for Leave ID: ".$record." from: $creator_name / $creator_position / $creator_department";
    
    //$to = array_unique($to);
    $cc = array_unique($cc);
    leaverequest_email_notification($to, $cc, $record, $cdate, $subject, $user_info, 
                                    $current_user_email, $current_user_name, 
                                    $message_status, 
                                    $head_signature, $head_signature_date, 
                                    $hr_signature, $hr_signature_date, 
                                    $who_updated_email, $fd_signature, $fd_signature_date);
                                    
                                    
                                    
    
    /**
        If head and HR approved, then send autoreply text to Dossur
    */
    if (($head_signature) && ($hr_signature)){		

        //$leave_info = get_leave_details($record);	
        $to[] = 'erp.support@globalinklogistics.com';
        $to[] = 'reception@globalinklogistics.com';
        $creator_name = $requester_nm;
        $autoreply_info['requester_name'] = $creator_name;
        $autoreply_info['requester_email'] = $creator_email;		
        $autoreply_info['start_date'] = $date_from;
        $autoreply_info['end_date'] = $date_till;
        $autoreply_info['leave_hours'] =  $leave_info->get('cf_5355');
        $autoreply_info['comments'] =  $leave_info->get('cf_4677');
        $autoreply_info['mobile_number'] = $leave_info->get('cf_3401');
        $autoreply_info['leave_type'] = $leave_info->get('cf_3409');
        
        $autoreply_info['forward_to'] = $leave_info->get('cf_3405');
        $autoreply_info['autoreply_text'] = $leave_info->get('cf_3407'); 
 
        send_autoreply($to, $record, $subject, 	$current_user_email, $current_user_name,$autoreply_info);
        
    }                   
    
}


    function leaverequest_email_notification($to, $cc, $record, $cdate, $subject, $user_info, 
                $current_user_email, $current_user_name, 
                $message_status, 
                $head_signature, $head_signature_date, 
                $hr_signature, $hr_signature_date, 
                $who_updated_email, $fd_signature, $fd_signature_date) {
                $to = array_unique($to);
                $cc = array_unique($cc);
                //$link = domain_name(); 
                global $site_URL;
                $link = $site_URL;
                $link .= 'index.php?module=Leaverequest&view=Detail&record='.$record;
                //$to_emails = '';
                //$cc_emails = '';
                //$to_emails = $to_email[0];

                //$from = 'From: '.$current_user_name.' <'.$current_user_email.'>';
                $from = $current_user_email;

                $body = '';
                $body .= "<html>
                <head>
                <style> 
                #calendar_notification tr td{ margin:3px;}
                .edited {font-weight: bold; color:green;}
                </style> 
                </head>
                <body><table id='calendar_notification'> ";	
                $body .= "<tr><td colspan=2> $message_status </td></tr>";	
                $body .= "<tr><td colspan=2> </td></tr>";	
                $body .= "<tr><td colspan=2>$user_info</td></tr>";
                $body .= "<tr><td colspan=2> Created: ".$cdate." </td></tr>";
                //Created: 2017-Dec-13; 05:52

                $body .= "<tr><td colspan=2>HR Signature:- ". (!empty($hr_signature) ? $hr_signature.", Date: ".$hr_signature_date : '') ."</td></tr>";
                $body .= "<tr><td colspan=2>Head Signature:- ".(!empty($head_signature) ? $head_signature.", Date: ".$head_signature_date : '')."</td></tr>";
                $body .= "<tr><td colspan=2>FD Signature:- ".(!empty($fd_signature) ? $fd_signature.", Date: ".$fd_signature_date : '')."</td></tr>";

                $body .= "<tr><td colspan=2 >Please see details on this link: <a href='$link'> $link </a></td></tr>";	
                $body .= "</table> </body> </html> ";

                // Set content-type when sending HTML email
                $headers = "MIME-Version: 1.0" . "\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\n";
                $headers .= $from . "\n";
                $headers .= 'Reply-To: '.$to.'' . "\n";
              
                foreach ($cc as $person) $s_cc .= $person.",";
                foreach ($to as $person) $s_to .= $person.",";
                //$s_cc= '';
                //echo $headers;
                //echo "<br>";
               // echo $body;
               // echo "<br>";
               // echo $s_to;
               // echo "<br>";
                //echo $s_cc;
               // exit;
               //global $current_user,$HELPDESK_SUPPORT_EMAIL_ID, $HELPDESK_SUPPORT_NAME;
               // require_once("modules/Emails/mail.php");
                //send_mail('Leaverequest', $s_to, $from, $who_updated_email, $subject, $body,$s_cc,'','','','',true); 
                // Set content-type when sending HTML email
                $from = 'From: '.$who_updated_email.' <'.$who_updated_email.'>';
                $headers = "MIME-Version: 1.0" . "\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\n";

                $headers .= $from . "\n";
                $headers .= 'Reply-To: '.$s_to.'' . "\n";      
                $headers .= "CC:" . $s_cc . "\n";
		//$body .= "b = $conflict_baku<br> e = $conflict_erevan";
				$result = mail($s_to,$subject,$body,$headers);
              
              //$body= decode_html($body);
              // $subject= decode_html($subject);
               //send_mail('Leaverequest', $s_to, $HELPDESK_SUPPORT_NAME, $HELPDESK_SUPPORT_EMAIL_ID, $subject, $body,'','','','','',true); 
               //control_emails_sending($s_to,$s_cc,$who_updated_email,$subject,$body,$headers,$link,$record);
               //require_once('data/CRMEntity.php');
              // $crmentity = new CRMEntity();
              // $crmentity->controlEmailSending($s_to,$s_cc,$who_updated_email,$subject,$body,$headers,$link,$record);
    }


    function send_autoreply($to, $record, $subject, $current_user_email, $current_user_name,$autoreply_info){
        global $site_URL;
        $link = $site_URL;
					   
		//$link = domain_name();
		$requester_name = $autoreply_info['requester_name'];
		$requester_email = $autoreply_info['requester_email'];	
		
		$link .= 'index.php?module=Leaverequest&view=Detail&record='.$record;

		$from = 'From: '.$requester_name.' <'.$requester_email.'>';
		
		$body = "<html>
				  <head>
					<style> 
					  #calendar_notification tr td { margin:3px;}
					  .edited {font-weight: bold; color:green;}
					</style> 
				 </head>
				<body><table id='calendar_notification'> ";	
		$body .= "<tr><td colspan=2>Out of the office details:</td></tr>";

		if ($autoreply_info['requester_name']) $body .= "<tr><td colspan=2> ".$autoreply_info['requester_name']." </td></tr>";
		
		if ($autoreply_info['start_date']) $body .= "<tr><td>Start date: </td> <td> ".$autoreply_info['start_date']." </td></tr>";
		
		if ($autoreply_info['end_date']) $body .= "<tr><td>End date: </td> <td> ".$autoreply_info['end_date']." </td></tr>";

		if ($autoreply_info['leave_hours']) $body .= "<tr><td>Leave hours: </td> <td> ".$autoreply_info['leave_hours']." </td></tr>";
		
		if ($autoreply_info['comments']) $body .= "<tr><td> Comments: </td> <td> ".$autoreply_info['comments']." </td></tr>";

		if ($autoreply_info['mobile_number']) $body .= "<tr><td> Mobile number: </td> <td> ".$autoreply_info['mobile_number']." </td></tr>";
		
		if ($autoreply_info['leave_type']) $body .= "<tr><td> Reason: </td> <td> ".$autoreply_info['leave_type']." </td></tr>";
		
		
		if ($autoreply_info['leave_type']) $body .= "<tr><td colspan=2> Dear IT Team,<br>Please put forwarding and autoreply t Dear IT Team,<br>Please put forwarding and autoreply text. </td></tr>";
		
		if ($autoreply_info['forward_to']) $body .= "<tr><td> Forward to: </td> <td> ".$autoreply_info['forward_to']." </td></tr>";
		
		if ($autoreply_info['autoreply_text']){
			$body .= "<tr><td colspan=2>  Autoreply text:  </td></tr>";
			$body .= "<tr><td colspan=2> ".$autoreply_info['autoreply_text']." </td></tr>";
		}
				
		
		$body .= "<tr><td colspan=2 >Please see details on this link: <a href='$link'> $link </a></td></tr>";	
		$body .= "</table> </body> </html> ";
									
		// Set content-type when sending HTML email
		$headers = "MIME-Version: 1.0" . "\n";
		$headers .= "Content-type:text/html;charset=UTF-8" . "\n";
		$headers .= $from . "\n";
		$headers .= 'Reply-To: '.$to.'' . "\n";
		// $to = array();
		$to[] = "d.bashayev@globalinklogistics.com";
		$to[] = "reception@globalinklogistics.com";
		//$to[] = "r.gusseinov@globalinklogistics.com";	
		$to_emails = implode(",", $to);	
		$cc_emails = implode(",", $cc);
		$subject = "Out of the office: " . $requester_name;		
        //control_emails_sending($to_emails,$cc_emails,$who_updated_email,$subject,$body,$headers,$link,$record);
        require_once("modules/Emails/mail.php");
        send_mail('Leaverequest', $to_emails, $from, $who_updated_email, $subject, $body,$cc_emails,'','','','',true);    
	}	


function detect_message_status_leave($record){
    $adb = PearDatabase::getInstance();
    $s_modtracker_basic = $adb->pquery("SELECT * FROM `vtiger_modtracker_basic` where `crmid` = $record");
    $count = $adb->num_rows($s_modtracker_basic);
    return $count;
}

?>