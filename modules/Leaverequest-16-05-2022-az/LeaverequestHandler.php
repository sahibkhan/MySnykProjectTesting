<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
	//ini_set('display_errors','on'); version_compare(PHP_VERSION, '5.5.0') <= 0 ? error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED) : error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);   // DEBUGGING
	
	include_once("/var/www/html/live-gems/include/Leaverequest/leaverequestfunc.php");

	function leave_msg_handler($module,$record,$current_user, $recordModel)
	{
		send_email($record);
		return;
	}

	function send_email($RecID)
	{

		$rid = $RecID;
		//getting leaverequest record
		$sqllr = "SELECT * FROM vtiger_leaverequestcf WHERE leaverequestid='".$rid."'";
			
		
		$rslr = getrs($sqllr);
		$rlr = mysqli_fetch_assoc($rslr);
		$pusrid = $rlr['cf_3423'];  // profile userid this will actueul user requested for leave
		$lvcrtdt = $rlr["cf_3413"];
		//$lvfrom = date_format(date_create($rlr["cf_3391"]),"D d F Y");
		//$lvto = date_format(date_create($rlr["cf_3393"]),"D d F Y");
		$lvfrom = date_format(date_create($rlr["cf_3391"]),"D jS F Y");
		$lvto = date_format(date_create($rlr["cf_3393"]),"D jS F Y");
		//days diff count
		$sdt = strtotime($rlr["cf_3391"]);
		$edt = strtotime($rlr["cf_3393"]);
		$lvdays = round(($edt - $sdt)/(60*60*24))+1;
		$locid=$rlr["cf_3469"];
		$locsname = getLocationShortName($locid);
		$locLongName = getLocationLongName($locid);

		$lvtype = $rlr["cf_3409"]; 
		
		//echo "From ".$lvfrom. " to ".$lvto." = ".$lvdays." days";
		//exit;
		
		//getting creator user id
		$sqlusr = "SELECT * FROM vtiger_crmentity where crmid='".$rid."'";
		$rsusr = getrs($sqlusr);
		$rusr = mysqli_fetch_assoc($rsusr);
		$usrid = $rusr["smcreatorid"];
		
		//getting creator user email
		$usrlstid =getUserListId($usrid);
		$creator_user_email = getHeadEmail($usrlstid);
		
		
		//getting request user info
		$sqlulst = "SELECT * FROM vtiger_userlistcf where userlistid='".$pusrid."'";
		//echo $sqlulst;
		$rsulst = getrs($sqlulst);
		$rulst = mysqli_fetch_assoc($rsulst);
		$empemail = $rulst["cf_3355"];
		$usremail = $rulst["cf_3355"];
		$empno = $rulst["cf_4799"];
		$emppaiddays = $rulst["cf_3433"];
		$empunpaiddays = $rulst["cf_3473"];
		$empdept = $rulst["cf_3349"];
		$empdeptname = getDeptName($empdept);
		$empdeptsname = getDeptShortName($empdept);
		$empgmid = $rulst["cf_3385"]; //cf_3385 General Manager
		$emphdid = $rulst["cf_3387"]; // cf_3387 Head
		
		
		
		
		//getting user name
		$sqlusr = "SELECT * FROM vtiger_users where email1='".$usremail."'";
		$rsusr = getrs($sqlusr);
		$rusr = mysqli_fetch_assoc($rsusr);
		//$usremail = $rusr["email1"];
		$username = $rusr["first_name"]." ".$rusr["last_name"];
		
		// getting user manager info
		$gmemail = getHeadEmail($empgmid);		
		$gmname = getHeadName($gmemail);
		
		// getting user head info
		$hdemail = getHeadEmail($emphdid);		
		$hdname = getHeadName($hdemail);
		
		
		//check is useremail not exist (not gems user)
		if($usremail=="")
		{
			$usremail=$creator_user_email;
			$empemail = $creator_user_email;
			
			
			//getting user name
			$sqlusr2 = "SELECT * FROM vtiger_userlist where userlistid='".$pusrid."'";
			$rsusr2 = getrs($sqlusr2);
			$rusr2 = mysqli_fetch_assoc($rsusr2);
			//$usremail = $rusr["email1"];
			$username = $rusr2["name"];
		}
		
		//echo "GM ",$gmname,$gmemail,"<br>";
		//echo "HD ",$hdname,$hdemail,"<br>";
		// getting hr group email
		$hremail = getHREmail();				
		//$hremail = "a.naseem@globalinklogistics.com"; // for testing
		//getting hr head info
		$hrhdid = getHRHeadUserID();
		$hrhdemail = getHeadEmail($hrhdid);		
		$hrhdname = getHeadName($hrhdemail);
		//echo "HR Head ID ".$hrhdid;
		//echo " HD ",$hrhdname,$hrhdemail,"<br>";
		//exit;
		
		//creating email to user
		$from=$empemail;
		$to = $hremail; //temp
		//$empemail = $to; //temp
		//$to = $empemail;
		$heading = "Leave Application";
		$lvunqid = $locsname."-".$empdeptsname."-".$rid."/".date_format(date_create($lvcrtdt),"y");
				
		$subject = "Leave Request From: ".$username." (".$lvunqid.")";
		
		//echo "sub ".$subject;
		//exit;
		
		$record_link = "https://gems.globalink.net/index.php?module=Leaverequest&view=Detail&record=".$rid;

		
		$tofrom = "Head";
		$tocopy = "UserCopy";
		$toname = $username;
		$tobtn = "N";
		
		$employee_name = $username;
		//$txt = "<h1>This is testing email for leave request</h1>";
		
		$txt = "<p><b>".$username."</b> submitted new leave request requiring your approval. </p>";
		//$txt .= "<p>Require your approval.</p>";
		$txt .= '<h3 style="background-color:#f47824;color:white; margin-top: 0; margin-bottom: 0; font-weight: 500; vertical-align: center; font-size: 20px; padding: 3px;" align="left">Leave Application</h3>';
		$txt .= "<table style='width:100%'>";
		$txt .= "<tr  ><td style='border-bottom:1px solid  #abb2b9;'>Leave Ref.#      : </td><td style='border-bottom:1px solid  #abb2b9;'><b>".$lvunqid."</b></td></tr>";
		$txt .= "<tr><td style='border-bottom:1px solid  #abb2b9;'>Employee Name    : </td><td style='border-bottom:1px solid  #abb2b9;'><b>".$employee_name."</b></td></tr>";
		$txt .= "<tr><td style='border-bottom:1px solid  #abb2b9;'>Employee Number  : </td><td style='border-bottom:1px solid  #abb2b9;'><b>".$empno."</b></td></tr>";
		if($locLongName !="Almaty")
		{
			$txt .= "<tr><td style='border-bottom:1px solid  #abb2b9;'>Location         : </td><td style='border-bottom:1px solid  #abb2b9;'><b>".$locLongName."</b></td></tr>";
		}		
		$txt .= "<tr><td style='border-bottom:1px solid  #abb2b9;'>Department       : </td><td style='border-bottom:1px solid  #abb2b9;'><b>".$empdeptname."</b></td></tr>";
		$txt .= "<tr><td style='border-bottom:1px solid  #abb2b9;'>Leave Request Id : </td><td style='border-bottom:1px solid  #abb2b9;'><b>  ".$rid."</b><a href='".$record_link."' target='_blank'> Click here to see details </a></td></tr>";
		$txt .= "<tr><td style='border-bottom:1px solid  #abb2b9;'>Type of Leave    : </td><td style='border-bottom:1px solid  #abb2b9;'><b>".$lvtype."</b></td></tr>";
		$txt .= "<tr><td style='border-bottom:1px solid  #abb2b9;'>Start Date       : </td><td style='border-bottom:1px solid  #abb2b9;'><b>".$lvfrom."</b></td></tr>";
		$txt .= "<tr><td style='border-bottom:1px solid  #abb2b9;'>End Date         : </td><td style='border-bottom:1px solid  #abb2b9;'><b>".$lvto."</b></td></tr>";
		$txt .= "<tr><td style='border-bottom:1px solid  #abb2b9;'>Leave Request For : </td><td style='border-bottom:1px solid  #abb2b9;'><b>".$lvdays." Day(s)</b></td></tr>";
		$txt .= "<tr><td style='border-bottom:1px solid  #abb2b9;'>Status: </td><td style='color:red;border-bottom:1px solid #abb2b9;'><b>Waiting for approval</b></td></tr>";
		//$txt .= "<tr><td>Balance Summary </td><td><b></td></tr>";
		//$txt .= "<tr><td>Approvel For Extention        : </td><td><b></b></td></tr>";
		//$txt .= "<tr><td>Eligiblle Entitile for this year: </td><td><b></b></td></tr>";
		//$txt .= "<tr><td>Leave Approved  : </td><td></td><b></b></tr>";
		$txt .= "</table>";
		
		//$txt .="<br>";
		//$txt .="<a href='".$record_link."' target='_blank'>Please click here to see details </a>";
		
		//$txt .="<br>";
		$to = $empemail;
		//send meail to user
		$txt2 = str_replace("requiring your approval","for approval",$txt);
		//$txt2 = str_replace($username,"You",$txt2);
		$txt2 = str_replace($username."</b> submitted","You</b> submitted",$txt2);

		
		//echo $txt2;
		//exit;
		sendEmailProc($from,$to,$heading,$subject,$txt2,$toname,$tocopy,$tofrom,$tobtn,$rid);

		//exit;
		// Send to User manager/head
		$to = $gmemail; //temp
		//$to = "a.naseem@globalinklogistics.com";
		//$to = $empemail;		
		$tofrom = "Head";
		$tocopy = "HeadCopy";
		$toname = $gmname;
		$tobtn = "Y";

		
		sendEmailProc($from,$to,$heading,$subject,$txt,$toname,$tocopy,$tofrom,$tobtn,$rid);

		

		// Send to HR Group
		$to = $hremail; //temp
		
		/**
			Tbilisi branch
		*/
		//if ($locid == 85832) $cc[] = "l.atamova@globalinklogistics.com";
		//if ($locid == 85947) $cc[] = "l.atamova@globalinklogistics.com";

		
		//$empemail = $to; //temp
		//$to = $empemail;		
		$tofrom = "HR";
		$tocopy = "HRCopy";
		$toname = "HR Department";
		$tobtn = "N";

		$txt2 = str_replace("Require your approval","for approval",$txt);
		sendEmailProc($from,$to,$heading,$subject,$txt2,$toname,$tocopy,$tofrom,$tobtn,$rid);
		
		// Send to HR Head
		$to = $hrhdemail;
		//$to = $hremail; //temp
		
		//$to = $empemail;		
		$tofrom = "HR Head";
		$tocopy = "HRHDCopy";
		$toname = $hrhdname;
		$tobtn = "Y";

		sendEmailProc($from,$to,$heading,$subject,$txt,$toname,$tocopy,$tofrom,$tobtn,$rid);

		// For FD Approval in case of FD user
		$sqlfd = "SELECT vtiger_users.email1
                              FROM `vtiger_users2group` 
                              INNER JOIN vtiger_users ON vtiger_users.id = vtiger_users2group.userid		
                              WHERE vtiger_users2group.`groupid` = 1298 and vtiger_users.email1='".$empemail."'";
		$rsfd = getrs($sqlfd);
		$rfd = mysqli_fetch_assoc($rsfd);
		if($rfd)
		{
			
			// to HD Approval
			$to = getFDEmail();
			//$to = "a.naseem@globalinklogistics.com";
			$tofrom = "FD Head";
			$tocopy = "FDHeadCopy";
			$toname = "FD Head";
			$tobtn = "Y";
			
			sendEmailProc($from,$to,$heading,$subject,$txt,$toname,$tocopy,$tofrom,$tobtn,$rid);
		}
		/*else // temp for testing
		{
			// to HD Approval
			$to = getFDEmail();
			$to = "a.naseem@globalinklogistics.com";
			$tofrom = "FD Head";
			$tocopy = "FDHeadCopy";
			$toname = "FD Head";
			$tobtn = "Y";
			
			sendEmailProc($from,$to,$heading,$subject,$txt,$toname,$tocopy,$tofrom,$tobtn,$rid);
		}*/
		

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
                // $link = $site_URL;
                $link = "https://gems.globalink.net/index.php?module=Leaverequest&view=Detail&record=$record&app=HR";
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
                //$from = 'From: '.$who_updated_email.' <'.$who_updated_email.'>';
                $from = $who_updated_email;
                $headers = "MIME-Version: 1.0" . "\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\n";

                $headers .= $from . "\n";
                $headers .= 'Reply-To: '.$s_to.'' . "\n";      
                $headers .= "CC:" . $s_cc . "\n";
		//$body .= "b = $conflict_baku<br> e = $conflict_erevan";
               // $result = mail($s_to,$subject,$body,$headers);
                require_once("modules/Emails/mail.php");
                send_mail('Leaverequest', $s_to, $from, $from, $subject, $body,$s_cc,'','','','',true);
              
              
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
		
		$link = "https://gems.globalink.net/index.php?module=Leaverequest&view=Detail&record=$record&app=HR";

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