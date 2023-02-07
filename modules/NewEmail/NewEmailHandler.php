<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

function new_email_notification($createdtime, $subject, $user_info,
                              $current_user_email, $current_user_name,
                              $ceo_user_email, $gm_user_email, 
                              $it_user_email, $hr_user_email, $recordId, 
                              $assigned_user_email, $assigned_user_name, 
                              $ceo_signature, $ceo_signature_date, 
                              $gm_signature, $gm_signature_date, 
                              $hr_signature, $hr_signature_date)
{
    global $site_URL;
      // $link = 'https://gems.globalink.net/';
   // $link = domain_name(); 
		  $link = "https://gems.globalink.net/index.php?module=NewEmail&view=Detail&record=$recordId&app=HR";
		  $to_arr = array($ceo_user_email, $gm_user_email, $it_user_email, $hr_user_email);
		  if($gm_user_email !=$assigned_user_email)
		  {
				$to_arr = array($ceo_user_email, $gm_user_email, $it_user_email, $hr_user_email, $assigned_user_email);	  
		  }
		  
		  $remove_arr = array($current_user_email);
		  $final_arr = array_diff($to_arr, $remove_arr);
		  //$to = $ceo_user_email.','.$gm_user_email.','.$it_user_email.','.$hr_user_email;
		  $to = implode(",", $final_arr);
		  $cc =  $current_user_email;
		  
		  
      //$from = 'From: '.$current_user_name.' <'.$current_user_email.'>';
      $from = $current_user_email;
		  $body = '';
		  /*	  
		  $body .= "<html>
					  <head>
						<style> 
						  #calendar_notification tr td{ margin:3px;}
						  .edited {font-weight: bold; color:green;}
						</style> 
					 </head>
					<body><table id='calendar_notification'> ";
		  //$body .= "<tr><td colspan=2>New Request For New Email From: $assigned_user_name</td></tr>";
		  $body .= "<tr><td colspan=2>$user_info</td></tr>";
		  $body .= "<tr><td colspan=2>Created: $createdtime</td></tr>";
		  $body .= "<tr><td colspan=2>CEO Signature:- $ceo_signature, Date:$ceo_signature_date</td></tr>";
		  $body .= "<tr><td colspan=2>GM/BM Signature:- $gm_signature, Date:$gm_signature_date</td></tr>";
		  $body .= "<tr><td colspan=2>HR Signature:- $hr_signature, Date:$hr_signature_date</td></tr>";
		  $body .= "<tr><td colspan=2>Please see details on this link: <a href='$link'> Click To Follow Link </a></td></tr>";
		  $body .= "</table> </body> </html> ";
		  */
		  $body .=$user_info."<br><br>";
		  $body .="Created: ".$createdtime."<br>";
		  $body .="CEO Signature:- ".$ceo_signature."  ".$ceo_signature_date."<br>";
		  $body .="GM/BM Signature:- ".$gm_signature."  ".$gm_signature_date."<br>";
		  $body .="HR Signature:- ".$hr_signature."  ".$hr_signature_date."<br><br>";
		  $body .="Please see details on this link: <a href='$link'> Click To Follow Link </a>";
		  $body .="";
										
			// Set content-type when sending HTML email
		  $headers = "MIME-Version: 1.0" . "\n";
		  $headers .= "Content-type:text/html;charset=UTF-8" . "\n";
		  $headers .= $from . "\n";
      $headers .= 'Reply-To: '.$to.'' . "\n";
      
     /* echo $subject;
      echo "<br>";
      echo $headers;
      echo "<br>";
      echo $body;
      echo "<br>";
      echo $to;
      echo "<br>";
      echo $cc;
      exit;*/
	//control_emails_sending($to,$cc,$assigned_user_email,$subject,$body,$headers,$link,$record);
      require_once("modules/Emails/mail.php");
      send_mail('NewEmail', $to, $from, $from, $subject, $body,$cc,'','','','',true);
}

?>