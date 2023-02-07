<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
require_once('include/diff.php');
require_once('include/finediff.php');

    function lead_msg_handler($moduleName,$record){
            
        $adb = PearDatabase::getInstance();
        $leads_info = Vtiger_Record_Model::getInstanceById($record, $moduleName);

       // $subject = $leads_info->get('cf_833');
       // Event Creator Details
       $creator_id = $leads_info->get('assigned_user_id');
       $creator_user = Users_Record_Model::getInstanceById($creator_id, 'Users');
       $creator_email= $creator_user->get('email1');
       $creator_name = $creator_user->get('first_name').' '.$creator_user->get('last_name');  

       // Event Update Details	
       $current_user = Users_Record_Model::getCurrentUserModel();
       $updatedby_id = $current_user->getId();
       $updatedby_user = Users_Record_Model::getInstanceById($updatedby_id, 'Users');
       $who_updated_email = $updatedby_user->get('email1');
       $who_updated_name = $updatedby_user->get('first_name').' '.$updatedby_user->get('last_name');

        if (!empty($leads_info->get('cf_833'))){
            $subject .= $leads_info->get('cf_833');
        } else {
            if ($leads_info->get('company')) $subject .= $leads_info->get('company');
        }

        // #2   Gathering Message Body Details
		// Gathering Assigned Person to Event
        $assigned_users = get_lead_history($record,'cf_851');
        // Gathering history details
        $all_field_details = get_lead_history($record,'');

        // #3   Gathering people to put in CC					
		// Gathering Assigned Person to Event
        //$users = get_leadscf_details($record,'cf_851');		  
        //$assigned_cc = arrange_muptiple_users($users,3);	 
        //$cc .= arrange_people_cc($assigned_cc);
        $cc .= $creator_email.',';

        $users = $leads_info->get('cf_853');
        $assigned_cc = arrange_muptiple_users($users,3);
        
        $users = $creator_email;
        $assigned_cc = arrange_muptiple_users($users,3);	 
        $cc .= arrange_people_cc($assigned_cc);

        $event_status = detect_message_status($record);
        if ($event_status == 1) 
            $message_status = "Lead for $subject has been created";
        else
        if ($event_status > 1) 
            $message_status = "Lead from $subject has been updated";

        $arranged_details = arrange_leadfetch_details($record,$assigned_users,$all_field_details,$event_status);

        send_lead_notification($creator_email,$creator_name,$who_updated_email,$who_updated_name,$module,$record,$subject,$message_status,$arranged_details,$cc,$event_status);
		

    }

    function send_lead_notification($creator_email,$creator_name,$who_updated_email,$who_updated_name,$module,$record,$subject,$message_status,$sum,$cc,$event_status){

        global $site_URL;
        $link = $site_URL;
        $link .= "index.php?module=Leads&view=Detail&record=".$record;
        $to = $creator_email;
      
        $from = 'From: '.$who_updated_name.' <'.$who_updated_email.'>';  
        $body = '';
        $body .= "<html><head> <style> #calendar_notification tr td{ margin:3px; } </style> </head>
                  <body><table id='calendar_notification'> ";
        $body .= "<tr><td colspan=2>Dear <b>".$creator_name.",</b> </td></tr>";
        $body .= "<tr><td colspan=2> $message_status </td></tr>
                   $sum	
                  <tr><td colspan=2>Link: <a href='$link'> Link to CRM </a></td></tr>";
        $body .= "</table> </body> </html> ";
                                      
          // Set content-type when sending HTML email
        $headers = "MIME-Version: 1.0" . "\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\n";
      
        $headers .= $from . "\n";
        $headers .= 'Reply-To: '.$to.'' . "\n";
        control_emails_sending($to,$cc,$who_updated_email,$subject,$body,$headers,$link,$record);
        
        
      }


      function control_emails_sending($to,$cc,$from,$subject,$body,$headers,$link,$record){

        $conflict_baku = 0;
        $conflict_erevan = 0;
        $branch_conflict = [];
        $value = '';
        $cc_user_city = '';
        $from_user_login = '';
        //$to = 'r.gusseinov@globalinkllc.com';
    
                            // Checking in FROM section
        
        $trimed_from = trim($from);
        $from_user_city = get_vtiger_user_details($trimed_from,'email1','address_city');
        if ($from_user_city == 'Baku')    $conflict_baku = 1;
        if ($from_user_city == 'Yerevan') $conflict_erevan = 1;
        
        // Checking in specific person
        $from_user_login = get_vtiger_user_details($trimed_from,'email1','user_name');
        //if ($from_user_login == 'r.tumanyan') $conflict_erevan = 1;
        //if ($from_user_login == 'p.karim') $conflict_erevan = 1;
            
    
                            // Checking in TO section
        //$from_user_login = '';
        //$trimed_to = trim($to);
        //$to_user_city = get_vtiger_user_details($trimed_to,'email1','address_city');
        //if ($to_user_city == 'Baku')    $conflict_baku = 1;
        //if ($to_user_city == 'Yerevan') $conflict_erevan = 1;
        
                                            // Checking in specific person
        $from_user_login = get_vtiger_user_details($trimed_to,'email1','user_name');
        //if ($from_user_login == 'r.tumanyan') $conflict_erevan = 1;
        //if ($from_user_login == 'p.karim') $conflict_erevan = 1;
        
    
        
                            // Checking in CC section
        
        $explode_cc  = explode(';',$cc);
        $n = count($explode_cc);
    
        if ($n > 0){
            for($i = 0; $i <= $n; $i++){
                $user_email = $explode_cc[$i];
                $trimed_to = trim($user_email);
                
                $cc_user_city = get_vtiger_user_details($trimed_to,'email1','address_city');
                if ($cc_user_city == 'Baku') $conflict_baku++;
                if ($cc_user_city == 'Yerevan') $conflict_erevan++;
                
                            // Checking in CC section
                            
                $from_user_login = get_vtiger_user_details($trimed_to,'email1','user_name');
               // if ($from_user_login == 'r.tumanyan') $conflict_erevan = 1;
                //if ($from_user_login == 'p.karim') $conflict_erevan = 1;
                
            }
        }
    
        
        
          //Checking, is there Pasha Karim in copy like Head of Department, if yes then remove him
          $leads_info = Vtiger_Record_Model::getInstanceById($record, 'Leads');
          $creator_id = $leads_info->get('assigned_user_id');
          $creator_user = Users_Record_Model::getInstanceById($creator_id, 'Users');
          $creator_city= $creator_user->get('address_city');
          $creator_department = $creator_user->get('department');

          // If branch then choose city, if Almaty then choose Dep.
          $city_dep_detail = '';				
          //if ($creator_city == 'Almaty') 
          //$city_dep_detail = $creator_city; else 
          $city_dep_detail = $creator_department;
          
          $manager_login = get_branch_details($city_dep_detail,'manager_login');
          //$manager_email = arrange_user_format($manager_login,2);
              
              
        //if (($conflict_baku > 0) && ($manager_login == 'p.karim')){
          //$conflict_erevan = 0;
          //$cc = str_replace('p.karim@globalinkllc.com',"",$cc);
          //$cc = str_replace(';;',"",$cc);
        //}
            
            
        if (($conflict_baku > 0) && ($conflict_erevan > 0)){
            $branch_conflict[1] = 1;
    
            $conflict_text = "
            Dear Sender,
            <br><br>
            Email notification on your recent action in ERP system was not delivered to the recipients, because both GLK Armenia and GLK Azerbaijan colleagues were expected to get in copy which is prohibited.
            <br><br>
            Please follow below link and have list of recipients fixed accordingly (AZ profile for int'l/corporate clients is to be created separately from the rest).
            <br><br>
            In case of any questions please feel free to contact erp.support@globalinklogistics.com
            <br><br>
              <a href=$link> Link to ERP </a>";
    
    
            $branch_conflict[2] = $conflict_text;
        }
    
        // If CC contain conflict email addresses.
        if ($branch_conflict[1] == 1){
            $body = '';
            $body = $branch_conflict[2];
            $result = mail($from,$subject,$body,$headers);
        } else {            
            $headers .= "CC:" . $cc . "\n";
            //$body .= "b = $conflict_baku<br> e = $conflict_erevan";
            $result = mail($to,$subject,$body,$headers);    
           
        }
    
    
    }

    function get_branch_details($city,$field){
        $adb = PearDatabase::getInstance();
        $sql_branch = $adb->pquery("SELECT * FROM `vtiger_branch_details` where `city`='".$city."'");
        $r_branch = $adb->fetch_array($sql_branch);
        return $r_branch["$field"];
    }

    function get_vtiger_user_details($value,$require_field,$field){
        $adb = PearDatabase::getInstance();
        $sql = $adb->pquery("SELECT * FROM `vtiger_users` where `$require_field`='$value'");
        $row = $adb->fetch_array($sql);
        return $row["$field"];
    }

    function arrange_leadfetch_details($record,$assigned_users,$all_field_details,$event_status){
        $table = ''; 
        
                              // Arranging event details in table    
        $n = count($assigned_users);
        if ($n > 0){
          $table .= "<tr><td colspan='2'> <b>Assigned person(s) </b> </td> </tr>";
          for($i = 1; $i <= $n; $i++){
            $user = $assigned_users[$i];
            $table .= "<tr><td colspan=2> $user </td></tr>"; 
          }
        }
        
      
                          // Gathering Event history details	
        $n = count($all_field_details);
        if ($n > 0){
          if ($event_status == 1) $lbl = 'Lead'; else $lbl = 'Updated';
          $table .= "<tr><td colspan='2'> <b> $lbl details </b> </td></tr>";	
          for($i = 1; $i <= $n; $i++){
            $label = ucfirst($all_field_details[$i][1]);
            $value = $all_field_details[$i][2];	  
            $prevalue = $all_field_details[$i][3];	  
            
                  
            if ($label == 'Modifiedby'){
               if ($event_status == 1) 
                   $label = "Created by"; 
               else 
                   $label = "Updated by";
              // $login = get_user_details($value,3);
                $value_user_info = Users_Record_Model::getInstanceById($value, 'Users');
                $login = $value_user_info->get('user_name');
                $value = arrange_user_format($login,1);
            }
          
            if ($label == 'Salutationtype'){
               $label = 'Salutation';
            }
            if ($label == 'Lane'){
               $label = 'Street';
            }
            if ($label == 'Cf_831'){
               $label = 'Deadline';
            }
            if ($label == 'Cf_833'){
               $label = 'Lead name';
            }
            if ($label == 'Cf_853'){
              $label = 'Updates are provided to ';
      
              $value = explode("|", $value);
              
              $user = get_user_name($value);
              $table .= "<tr> <td> $label </td> <td> $user </td></tr>";
              
            }
            
            
            if ($label == 'Cf_4047'){
               $label = 'Account';
               $value = get_account_details($value,'accountname');
            }   
            
            
                   
            if ($label == 'Description'){
              //$value = str_replace("\n","<br>",$value);
              $value = hightlight_updated_description($prevalue,$value);
            }
              if ($label != 'Updates are provided to ') {
            $table .= "<tr> <td> $label </td> <td> $value </td></tr>";
              }
          }
        }
        return $table;
    }


    function hightlight_updated_description($before,$after){
        $diff = new diff_class;
        $difference = new stdClass;
        $difference->mode = 'w';
        $difference->patch = true;
        $after_patch = new stdClass;
        if($diff->FormatDiffAsHtml($before, $after, $difference) && $diff->Patch($before, $difference->difference, $after_patch)){
            $text = $difference->html;
            $a = html_entity_decode($text);
            //$text = str_replace("\n","<br />",$a);
        }
        return $a;
    }


    function get_account_details($record,$field){
        $adb = PearDatabase::getInstance();
        $s_account = $adb->pquery("SELECT * FROM `vtiger_account` where `accountid`=$record");
        if (empty($s_account) == false) {
            $r_account = $adb->fetch_array($s_account);
            return $r_account["$field"];
        }
    }


    function get_user_name($username) {
        $adb = PearDatabase::getInstance();
        $counter = count($username);
        $full_name = '';
        for ($i = 0; $i < $counter - 1; $i++) {
            $usern = $adb->pquery('SELECT * FROM `vtiger_users` WHERE `user_name`="'.$username[$i].'"');
            $userna = $adb->fetch_array($usern);
            $first_name = $userna['first_name'];
            $last_name = $userna['last_name'];
            $full_name .= $first_name.' '.$last_name.', ';
        }
        return $full_name;
    }

    // Mentioning full user format:  first name, last name, Department;
    function arrange_user_format($users,$mode){
        $adb = PearDatabase::getInstance();
        // Вывод данных пользователей
        $user_login = trim($users);
        $res_users = $adb->pquery("Select * From `vtiger_users` where `user_name` = '$user_login' ");
        $row_user = $adb->fetch_array($res_users);
        if ($mode == 1){
            $title = $row_user['department'];
            $location = $row_user['address_city'];
            $str = '';
            if ($location == 'Almaty'){
                $str = $title.', Almaty';
            } else {
                $str = $location;
            }
            $output_detail = $row_user['first_name'] . ' ' . $row_user['last_name'].' / '.$str;
        }

        else
            if ($mode == 2){
                $output_detail = $row_user['email1'].';';
            }
            else
                if ($mode == 3){
                    $output_detail = $row_user['user_name'];
                }
                else
                    if ($mode == 4){
                        $output_detail = $row_user['first_name'] . ' ' . $row_user['last_name'];
                    }
        return $output_detail;
    }  


    function detect_message_status($record){
        $adb = PearDatabase::getInstance();
        $s_modtracker_basic = $adb->pquery("SELECT * FROM `vtiger_modtracker_basic` where `crmid` = $record");
        $count = $adb->num_rows($s_modtracker_basic);
        return $count;
    }


    // Gathering People in CC
    function arrange_people_cc($cc){
        $n = count($cc);
        if ($n > 0){
            for($i = 1; $i <= $n; $i++) $value .= $cc[$i];
        }
        return $value;
    }


    /*
      This is module which pullout all last history for specific event 
	   module       - Module name
	   record       - Record in database
	   field_array  - Pullout data by required fields
   */   
   
    function get_lead_history($record,$history_field){

        $adb = PearDatabase::getInstance();    
            // Excluding already mentioned fields
        $exclude_field = "'cf_851','record_id'";

        $s_modtracker_basic =  $adb->pquery("SELECT max(`id`) as `maxid` FROM `vtiger_modtracker_basic` where `crmid` = ".$record);
        $r_modtracker_basic =  $adb->fetch_array($s_modtracker_basic);  
        $max_id = $r_modtracker_basic['maxid'];
        $history = '';

        $vendor_value = '';
        $val = '';

        if ($history_field != ''){
            $query_details =  $adb->pquery("SELECT * FROM `vtiger_modtracker_detail` where `id`=$max_id and (`fieldname` not in ($exclude_field))");
            
                        // Assigned Users: cf_851
            if ($history_field == 'cf_851'){
                $query_details = $adb->pquery("SELECT * FROM `vtiger_modtracker_detail` where `id` = $max_id and `fieldname`='$history_field'");
                $details = $adb->fetch_array($query_details);
                $history = arrange_muptiple_users($details['postvalue'],1);
            }
        }
        else  
        if ($history_field == ''){
            $query_details = $adb->pquery("SELECT * FROM `vtiger_modtracker_detail` where `id`=$max_id and (`fieldname` not in ($exclude_field))");
            $array_history = array();      
            $i = 0; 		
                While ($r_list = $adb->fetch_array($query_details)){
                    $i ++;
                    $array_history[$i][1] = $r_list['fieldname'];
                    $array_history[$i][2] = $r_list['postvalue']; 	
                    $array_history[$i][3] = $r_list['prevalue'];
                }
            $history = $array_history;
        }
        return $history; 
    }


    // Arranging multiple assigned to field:
    /*
    users - users one by one, split by delimiter |
    format
        1 - first name, last name, Department;
        2 - just user login;
        3 - E-mail
    */
    function arrange_muptiple_users($users,$format){

        $adb = PearDatabase::getInstance();

        $person_array = array();
        $buffer = '';
        $n = 0;
    
        // Search count of person
        for($i = 0; $i <= strlen($users); $i++){
            if ($users[$i] == '|'){
                $n ++;
                $buffer = trim($buffer);
                $sql_user = $adb->pquery("SELECT * FROM `vtiger_users` where `user_name` = '$buffer' ");
                $r_user = $adb->fetch_array($sql_user);
                if ($format == 1){
                    $person_array[$n] = arrange_user_format($r_user['user_name'],1);
                }
                else
                    if ($format == 2){
                        $person_array[$n] = $r_user['user_name'];
                    }
                    else
                        if ($format == 3){
                            $person_array[$n] = $r_user['email1'].';';
                        }
                $buffer = '';
            } else $buffer = $buffer . $users[$i];
        }
        return $person_array;
    }


?>

