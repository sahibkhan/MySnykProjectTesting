<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

    function inquiry_msg_handler($moduleName,$record){
            
        $adb = PearDatabase::getInstance();

        $inquiry_info = Vtiger_Record_Model::getInstanceById($record, $moduleName);

        $subject = $inquiry_info->get('potentialname');

        // Event Creator Details
        $creator_id = $inquiry_info->get('assigned_user_id');
        $creator_user = Users_Record_Model::getInstanceById($creator_id, 'Users');
        $creator_email= $creator_user->get('email1');
        $creator_name = $creator_user->get('first_name').' '.$creator_user->get('last_name');  
        
        // Event Update Details	
        $current_user = Users_Record_Model::getCurrentUserModel();
        $updatedby_id = $current_user->getId();
        $updatedby_user = Users_Record_Model::getInstanceById($updatedby_id, 'Users');
        $who_updated_email = $updatedby_user->get('email1');
        $who_updated_name = $updatedby_user->get('first_name').' '.$updatedby_user->get('last_name');

        // Customer Details
        $related_customer_toinquiry = $inquiry_info->get('related_to');
        $customer = $inquiry_info->getDisplayValue('related_to');

        // Gathering Assigned Person to Event
        $assigned_users = '';
      
        // Gathering Route Details from history
        $route_details = get_inquiry_history11($record,'cf_717');
      
        // Gathering Cargo Details from history 
        $cargo_details = get_inquiry_history($record,'cargo_details');	  

        // Gathering history details
        $all_field_details = get_inquiry_history1($record,'');
       // $adb->pquery("UPDATE `a_test` SET `user_name` = 's1' WHERE `a_test`.`record_id` = 990679");

        /*
		Adding Dennis Ruiter in copy if his accounts is linked with current RFQ 
		*/
        $d_ruiter = 882;		
        $s_invitee = $adb->pquery("SELECT * FROM `vtiger_invitees` 
                                            WHERE `activityid`=$related_customer_toinquiry
                                            AND inviteeid = $d_ruiter");
        $n_invitee = $adb->num_rows($s_invitee);
        if ($n_invitee == 1){
            $cc .= "d.ruiter@globalinklogistics.com;";
        }	 

        // Add assigned person to CC
        $cc .= $creator_email.';';

        // Gathering Invited People
        $users = $inquiry_info->get('cf_757');
        $assigned_cc = arrange_muptiple_users1($users,3);
        $cc .= arrange_people_cc1($assigned_cc);

        // Gathering Event Status (Just created or updated);
        $event_status = detect_message_status1($record);
        if ($event_status == 1) $message_status = "Inquiry from $subject has been created";
        else
        if ($event_status > 1) $message_status = "Inquiry from $subject has been updated"; 

        $arranged_details = arrange_inqfetch_details1($record,$assigned_users,$route_details,$cargo_details,$all_field_details,$event_status);	
        
        $customer_subject = get_account_details1($related_customer_toinquiry,'accountname');

        $sub_title = "INQ_$subject/".$customer_subject;
        $subject = $sub_title;


        send_inq_notification1($creator_email,$creator_name,$who_updated_email,$who_updated_name,$module,$record,$subject,$message_status,$arranged_details,$cc,$event_status);
		

    }


    function send_inq_notification1($creator_email,$creator_name,$who_updated_email,$who_updated_name,$module,$record,$subject,$message_status,$sum,$cc,$event_status){

        global $site_URL;
        $link = $site_URL;
        $link .= "index.php?module=Potentials&view=Detail&record=".$record;
        $to = $creator_email;
        $date_time = date('Y-m-d H:i:s'); 
        
        $from = 'From: '.$who_updated_name.' <'.$who_updated_email.'>';  
        $body = '';
        $body .= "<html><head> <style> #calendar_notification tr td{ margin:3px; } </style> </head>
                  <body><table id='calendar_notification'> ";
        $body .= "<tr><td colspan=2>Dear <b>".$creator_name.",</b> </td></tr>";
        $body .= "<tr><td colspan=2> $message_status </td></tr>
                   $sum	
                  <tr><td colspan=2>Link: <a href='$link'> Link to ERP </a></td></tr>";
        $body .= "</table> </body> </html> ";
                                      
        // Set content-type when sending HTML email
        $headers = "MIME-Version: 1.0" . "\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\n";
      
        $headers .= $from . "\n";
        $headers .= 'Reply-To: '.$to.'' . "\n";
        control_emails_sending1($to,$cc,$who_updated_email,$subject,$body,$headers,$link,$record);
        
        /*if($result == true){
          write_notification_logs($record,$date_time,'notification',$event_status,$cc);
          echo 'sent<br>';
        } else if($result == false){
          echo 'error<br>';
        }*/
      }



      function control_emails_sending1($to,$cc,$from,$subject,$body,$headers,$link,$record){

        $conflict_baku = 0;
        $conflict_erevan = 0;
        $branch_conflict = [];
        $value = '';
        $cc_user_city = '';
        $from_user_login = '';
        //$to = 'r.gusseinov@globalinkllc.com';
    
                            // Checking in FROM section
        
        $trimed_from = trim($from);
        $from_user_city = get_vtiger_user_detail1s($trimed_from,'email1','address_city');
        if ($from_user_city == 'Baku')    $conflict_baku = 1;
        if ($from_user_city == 'Yerevan') $conflict_erevan = 1;
        
                                            // Checking in specific person
        $from_user_login = get_vtiger_user_details1($trimed_from,'email1','user_name');
        //if ($from_user_login == 'r.tumanyan') $conflict_erevan = 1;
        //if ($from_user_login == 'p.karim') $conflict_erevan = 1;
            
    
                            // Checking in TO section
        //$from_user_login = '';
        //$trimed_to = trim($to);
        //$to_user_city = get_vtiger_user_details($trimed_to,'email1','address_city');
        //if ($to_user_city == 'Baku')    $conflict_baku = 1;
        //if ($to_user_city == 'Yerevan') $conflict_erevan = 1;
        
                                            // Checking in specific person
        $from_user_login = get_vtiger_user_details1($trimed_to,'email1','user_name');
        //if ($from_user_login == 'r.tumanyan') $conflict_erevan = 1;
        //if ($from_user_login == 'p.karim') $conflict_erevan = 1;
        
    
        
                            // Checking in CC section
        
        $explode_cc  = explode(';',$cc);
        $n = count($explode_cc);
    
        if ($n > 0){
            for($i = 0; $i <= $n; $i++){
                $user_email = $explode_cc[$i];
                $trimed_to = trim($user_email);
                
                $cc_user_city = get_vtiger_user_details1($trimed_to,'email1','address_city');
                if ($cc_user_city == 'Baku') $conflict_baku++;
                if ($cc_user_city == 'Yerevan') $conflict_erevan++;
                
                            // Checking in CC section
                            
                $from_user_login = get_vtiger_user_details1($trimed_to,'email1','user_name');
               // if ($from_user_login == 'r.tumanyan') $conflict_erevan = 1;
                //if ($from_user_login == 'p.karim') $conflict_erevan = 1;
                
            }
        }
    
        
        
          //Checking, is there Pasha Karim in copy like Head of Department, if yes then remove him
          $inquiry_info = Vtiger_Record_Model::getInstanceById($record, 'Potentials');
          $creator_id = $inquiry_info->get('assigned_user_id');
          $creator_user = Users_Record_Model::getInstanceById($creator_id, 'Users');
          $creator_city= $creator_user->get('address_city');
          $creator_department = $creator_user->get('department');  

          // If branch then choose city, if Almaty then choose Dep.
          $city_dep_detail = '';				
          //if ($creator_city == 'Almaty') 
          //$city_dep_detail = $creator_city; else 
          $city_dep_detail = $creator_department;
          
          $manager_login = get_branch_details1($city_dep_detail,'manager_login');
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



    function arrange_inqfetch_details1($record,$assigned_users,$route_details,$cargo_details,$all_field_details,$event_status){
            $table = ''; 
            
            $adb = PearDatabase::getInstance();
                                // Arranging event details in table    
            $n = count($assigned_users);
            if ($n > 0){
            $table .= "<tr><td colspan='2'> <b>Assigned person(s) </b></td></tr>";
            for($i = 1; $i <= $n; $i++){
                $user = $assigned_users[$i];
                $table .= "<tr><td colspan=2> $user </td></tr>"; 
            }
            }

            $inquiry_info = Vtiger_Record_Model::getInstanceById($record, 'Potentials');        
            // Arranging route details in table  
            $route_block = $inquiry_info->get('cf_717');
            $n = substr_count($route_block,'#');
       
      
            if ($n > 0){
            $table .= "<tr><td colspan='2'> <b> Routing details </b> </td> </tr>";
            for ($i=1;$i<=$n;$i++){
                $table .= '<tr><td width="60px">Origin: </td><td width="365px">'.$route_details[1][$i].'</td>';
                $table .= '<td width="60px">Transport mode: </td><td width="50px">'.$route_details[2][$i].'</td></tr>';
                
                $table .= '<tr><td>Destination: </td><td width="365px">'.$route_details[3][$i].'</td>';
                $table .= '<td width="60px">Incoterms: </td><td width="50px">'.$route_details[4][$i].'</td></tr>';
                
                $table .= '<tr><td>Weight:</td><td width="365px">'.$route_details[5][$i].'</td>';
                $table .= '<td width="60px">Volume: </td><td width="50px">'.$route_details[6][$i].'</td></tr>';
        
                $table .= '<tr><td>Dimensions: </td><td width="365px">'.$route_details[7][$i].'</td>';
                $table .= '<td width="60px">Commodity: </td><td width="50px">'.$route_details[8][$i].'</td></tr>';
                
                $table .= '<tr><td>Transport Type: </td><td width="365px">'.$route_details[9][$i].'</td>';
                $table .= '<td width="60px">Quantity: </td><td width="50px">'.$route_details[10][$i].'</td></tr>';	    
            } 
            } 
            else if ($n == 0){
            //$table .= "<tr><td colspan='2'> <b> Routing details </b> </td> </tr>";
            //$table .= '<tr><td> <b> - </b> </td>';
            }
        
                              // Arranging route details in table  
        /*
            $cargo_weight = $cargo_details[1];
            $cargo_volume = $cargo_details[2];
            $cargo_dimension = $cargo_details[3];
            
            $cargo_commodity = $cargo_details[4];
            $cargo_transport_type = $cargo_details[5];
            $cargo_quantity = $cargo_details[6];
            
            $n = count($cargo_weight);
            if ($n > 0){
            $table .= "<tr><td colspan='2'> <b> Cargo details </b> </td> </tr>";
            for ($i=1;$i<=$n;$i++){
                $table .= '<tr><td>Weight:' . $cargo_weight[$i].'</td>';
                $table .= '<td>Volume:' . $cargo_volume[$i].'</td></tr>';
                
                $table .= '<tr><td>Dimensions: ' . $cargo_dimension[$i].'</td>';	  
                $table .= '<td>Commodity: ' . $cargo_commodity[$i].'</td></tr>';
                
                $table .= '<tr><td>Transport Type: ' . $cargo_transport_type[$i] . '</td>';
                $table .= '<td>Quantity: ' . $cargo_quantity[$i] . '</td></tr>';	  
                $table .= '<tr><td></td><td></td></tr>';
                $table .= '<tr><td></td><td></td></tr>';
                $table .= '<tr><td></td><td></td></tr>';	  
            }
            } */
       
                          // Gathering Event history details	
                $n = count($all_field_details);
                if ($n > 0){
                if ($event_status == 1) $lbl = 'Inquiry'; else $lbl = 'Updated';
                $table .= "<tr><td colspan='2'> <b> $lbl details </b> </td></tr>";	
                for($i = 1; $i <= $n; $i++){
                    $label = ucfirst($all_field_details[$i][1]);
                    $value = $all_field_details[$i][2];	
                    $prevalue = $all_field_details[$i][3];
                    
                    if ($label == 'Related_to'){
                        $value = $inquiry_info->getDisplayValue('related_to');
                   // $sql_account = mysql_query("SELECT * From `vtiger_account` where `accountid` = ".$value);
                   // $r_account = mysql_fetch_array($sql_account);
                   // $value = $r_account['accountname'];
                    $label = 'Customer';
                    }  
            
                    if ($label == 'Contact_id'){
                    $sql_contactdetails = $adb->pquery("SELECT * From `vtiger_contactdetails` where `contactid` = $value");
                    $row = $adb->fetch_array($sql_contactdetails);
                    $value = $row['salutation']. ' ' . $row['firstname'] . ' ' . $row['lastname'];
                    $label = 'Contact Name';
                    }
                    
                    if ($label == 'Closingdate'){
                        $deadline = $inquiry_info->get('closingdate');
                        $value = $deadline;
                        $label = 'Deadline';
                    }
                    
                    if ($label == 'Modifiedby'){
                        if ($event_status == 1) $label = "Created by"; else $label = "Updated by";                        
                        //$login = get_user_details($value,3);
                        $value_user_info = Users_Record_Model::getInstanceById($value, 'Users');
                        $login = $value_user_info->get('user_name');
                        $value = arrange_user_format1($login,1);
                    }
                
                    if ($label == 'Potentialname') $label = 'Inquiry Name';	 
                    //if ($label == 'Campaignid') $label = 'Agent id';
                    
                    
                    if ($label == 'Cf_757') {
                        $label = 'Updates are provided to ';
                        
                        $value = explode("|", $value);
                        
                        $user = get_user_name1($value);
                        
                        $table .= "<tr> <td> $label </td> <td> $user </td></tr>";
                        
                    }                   
                    
                    
                    if ($label == 'Cf_1659') $label = 'Origin City';
                    if ($label == 'Cf_1663') $label = 'Destination City';
                    if ($label == 'Cf_1665') $label = 'Pick up address';
                    if ($label == 'Cf_1667') $label = 'Delivery address';
                    
                    if ($label == 'Cf_1671') $label = 'Expected date of pick up';
                    if ($label == 'Cf_1673') $label = 'Expected Date Of Delivery';
                    if ($label == 'Cf_1675') $label = 'ETD';
                    if ($label == 'Cf_1677') $label = 'ETA';
                    
                    if ($label == 'Cf_1681') $label = 'No of Pieces';
                    if ($label == 'Cf_1683') $label = 'Weight';
                    if ($label == 'Cf_1685') $label = 'KG';
                    if ($label == 'Cf_1687') $label = 'Volume';
            
                    if ($label == 'Cf_1689') $label = 'CBM';
                    if ($label == 'Cf_1691') $label = 'Cargo Value';
                    if ($label == 'Cf_1693') $label = 'Cntr or Transport Type';
                    if ($label == 'Cf_1695') $label = 'Commodity';
                    
                    if ($label == 'Cf_1697') $label = 'Cargo Description';
                    if ($label == 'Cf_1699') $label = 'Consignee';
                    if ($label == 'Cf_1707') $label = 'Mode';
                    if ($label == 'Cf_1723') $label = '---';
                    
                    if ($label == 'Cf_1789') $label = 'Terms of delivery ';
                    
                    if ($label == 'Cf_1657') $label = 'Origin Country';
                    if ($label == 'Cf_1661') $label = 'Consignee';       
                    
                    
                    //Cf_1657 	KZ 
                    //Cf_1661 	KZ                   
                    if ($label == 'Description'){
                        //$value = str_replace("\n","<br>",$value);
                        //$value = hightlight_updated_description($prevalue,$value);	 
                        $value = html_entity_decode ($value);
                    }
                    if ($label != 'Updates are provided to ') {
                        $table .= "<tr> <td> $label </td> <td> $value </td></tr>";
                    }
                }
                }
                return $table;
      }

      function get_account_details1($record,$field){
        $adb = PearDatabase::getInstance();
        $s_account = $adb->pquery("SELECT * FROM `vtiger_account` where `accountid`=$record");
        if (empty($s_account) == false) {
        $r_account = $adb->fetch_array($s_account);
        return $r_account["$field"];
        }
    }

      function get_branch_details1($city,$field){
            $adb = PearDatabase::getInstance();
            $sql_branch = $adb->pquery("SELECT * FROM `vtiger_branch_details` where `city`='".$city."'");
            $r_branch = $adb->fetch_array($sql_branch);
            return $r_branch["$field"];
        }


      function get_user_name1($username) {
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

    function get_vtiger_user_details1($value,$require_field,$field){
        $adb = PearDatabase::getInstance();
        $sql = $adb->pquery("SELECT * FROM `vtiger_users` where `$require_field`='$value'");
        $row = $adb->fetch_array($sql);
        return $row["$field"];
    }

      
       


    function detect_message_status1($record){
        $adb = PearDatabase::getInstance();
        $s_modtracker_basic = $adb->pquery("SELECT * FROM `vtiger_modtracker_basic` where `crmid` = $record");
        $count = $adb->num_rows($s_modtracker_basic);
        return $count;
    }


    // Gathering People in CC
    function arrange_people_cc1($cc){
        $n = count($cc);
        if ($n > 0){
            for($i = 1; $i <= $n; $i++) $value .= $cc[$i];
        }
        return $value;
    }



    function get_inquiry_history1($record,$history_field){

        $adb = PearDatabase::getInstance();

		//Excluding already mentioned fields
        $exclude_field = "'potential_no','record_id','record_module','assigned_user_id','cf_717','cf_703','cf_705','cf_707','cf_709','cf_715','cf_813','cf_755','createdtime','campaignid'";   
        $s_modtracker_basic =  $adb->pquery("SELECT max(`id`) as `maxid` FROM `vtiger_modtracker_basic` where `crmid` = ".$record);
        $r_modtracker_basic =  $adb->fetch_array($s_modtracker_basic);  
        $max_id = $r_modtracker_basic['maxid'];
        $history = '';

        $vendor_value = '';
        $val = '';
        
        if ($history_field != ''){
                        // Assigned Users: cf_755
            if ($history_field == 'cf_755'){
            $query_details = $adb->pquery("SELECT * FROM `vtiger_modtracker_detail` where `id` = $max_id and `fieldname`='$history_field'");
            $details = $adb->fetch_array($query_details);
            $history = arrange_muptiple_users1($details['postvalue'],1);
            } 
            else
            // Route: cf_717
            if ($history_field == 'cf_717'){
            //$details = get_potentialscf_details($record,'cf_717');
            $inquiry_info = Vtiger_Record_Model::getInstanceById($record, 'Potentials');
            $details = $inquiry_info->get('cf_717');
            $history = arrange_route_block_v2($details);
            }
            
           // Cargo details
            /* if ($history_field == 'cargo_details'){
                $query_details = mysql_query("SELECT * FROM `vtiger_modtracker_detail` where `id` = $max_id and `fieldname`='cf_703' ");
                $details = mysql_fetch_array($query_details);
                $weight = $details['postvalue'];
                
                $query_details = mysql_query("SELECT * FROM `vtiger_modtracker_detail` where `id` = $max_id and `fieldname`='cf_705' ");
                $details = mysql_fetch_array($query_details);
                $volume = $details['postvalue'];
                
                $query_details = mysql_query("SELECT * FROM `vtiger_modtracker_detail` where `id` = $max_id and `fieldname`='cf_707' ");
                $details = mysql_fetch_array($query_details);	  
                $dimensions = $details['postvalue'];
                
                $query_details = mysql_query("SELECT * FROM `vtiger_modtracker_detail` where `id` = $max_id and `fieldname`='cf_709' ");
                $details = mysql_fetch_array($query_details);	  
                $commodity = $details['postvalue'];
                
                $query_details = mysql_query("SELECT * FROM `vtiger_modtracker_detail` where `id` = $max_id and `fieldname`='cf_715' ");
                $details = mysql_fetch_array($query_details);	  
                $transport_type = $details['postvalue'];
                
                $query_details = mysql_query("SELECT * FROM `vtiger_modtracker_detail` where `id` = $max_id and `fieldname`='cf_813' ");
                $details = mysql_fetch_array($query_details);	  
                $quantity = $details['postvalue'];	  
                
                $history = arrange_cargo_block($weight,$volume,$dimensions,$commodity,$transport_type,$quantity);
            }*/
            
            
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
    function arrange_muptiple_users1($users,$format){

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
                    $person_array[$n] = arrange_user_format1($r_user['user_name'],1);
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



        // Mentioning full user format:  first name, last name, Department;
        function arrange_user_format1($users,$mode){
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


        // Function arranging routing details
        function arrange_route_block_v2($route){
            $n = substr_count($route,'#');
            $details = array();
            $buffer = '';

            $j = 1;
            $i = 0;

            for($c = 0; $c <=strlen($route); $c++){
                if ($route[$c] == '#') { $j++; $i = 0;}
                if ($route[$c] == "|"){
                    $i ++;
                    $details[$i][$j] = $buffer;
                    $buffer = '';
                } else if ($route[$c] != '#') $buffer = $buffer . $route[$c];
            }

            for ($i=1;$i<=$n;$i++){

                $details[1][$i] = str_replace('From:','',$details[1][$i]);
                $details[2][$i] = str_replace('Mode:','',$details[2][$i]);

                $details[3][$i] = str_replace('To:','',$details[3][$i]);
                $details[4][$i] = str_replace('Inco:','',$details[4][$i]);

                $details[5][$i] = str_replace('Wt:','',$details[5][$i]);
                $details[6][$i] = str_replace('Vol:','',$details[6][$i]);

                $details[7][$i] = str_replace('Dim:','',$details[7][$i]);
                $details[8][$i] = str_replace('Comm:','',$details[8][$i]);

                $details[9][$i] = str_replace('Trans:','',$details[9][$i]);
                $details[10][$i] = str_replace('Qty:','', $details[10][$i]);

                $details[11][$i] = str_replace('Rate:','', $details[11][$i]);
            }

            return $details;

        }


?>