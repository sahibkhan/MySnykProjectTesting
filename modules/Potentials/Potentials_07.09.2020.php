<?php
/*********************************************************************************
 * The contents of this file are subject to the SugarCRM Public License Version 1.1.2
 * ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an  "AS IS"  basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for
 * the specific language governing rights and limitations under the License.
 * The Original Code is:  SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 ********************************************************************************/
/*********************************************************************************
 * $Header: /advent/projects/wesat/vtiger_crm/sugarcrm/modules/Potentials/Potentials.php,v 1.65 2005/04/28 08:08:27 rank Exp $
 * Description:  TODO: To be written.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/
//ini_set('display_errors','on'); version_compare(PHP_VERSION, '5.5.0') <= 0 ? error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED) : error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);   // DEBUGGING

include_once 'modules/Potentials/PotentialMessageHandler.php';


class Potentials extends CRMEntity {
	var $log;
	var $db;

	var $module_name="Potentials";
	var $table_name = "vtiger_potential";
	var $table_index= 'potentialid';

	var $tab_name = Array('vtiger_crmentity','vtiger_potential','vtiger_potentialscf');
	var $tab_name_index = Array('vtiger_crmentity'=>'crmid','vtiger_potential'=>'potentialid','vtiger_potentialscf'=>'potentialid');
	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('vtiger_potentialscf', 'potentialid');

	var $column_fields = Array();

	var $sortby_fields = Array('potentialname','amount','closingdate','smownerid','accountname');

	// This is the list of vtiger_fields that are in the lists.
	var $list_fields = Array(
			'Potential'=>Array('potential'=>'potentialname'),
			'Organization Name'=>Array('potential'=>'related_to'),
			'Contact Name'=>Array('potential'=>'contact_id'),
			'Sales Stage'=>Array('potential'=>'sales_stage'),
			'Amount'=>Array('potential'=>'amount'),
			'Expected Close Date'=>Array('potential'=>'closingdate'),
			'Assigned To'=>Array('crmentity','smownerid')
			);

	var $list_fields_name = Array(
			'Potential'=>'potentialname',
			'Organization Name'=>'related_to',
			'Contact Name'=>'contact_id',
			'Sales Stage'=>'sales_stage',
			'Amount'=>'amount',
			'Expected Close Date'=>'closingdate',
			'Assigned To'=>'assigned_user_id');

	var $list_link_field= 'potentialname';

	var $search_fields = Array(
			'Potential'=>Array('potential'=>'potentialname'),
			'Related To'=>Array('potential'=>'related_to'),
			'Expected Close Date'=>Array('potential'=>'closedate')
			);

	var $search_fields_name = Array(
			'Potential'=>'potentialname',
			'Related To'=>'related_to',
			'Expected Close Date'=>'closingdate'
			);

	var $required_fields =  array();

	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vtiger_field.fieldname values.
	var $mandatory_fields = Array('assigned_user_id', 'createdtime', 'modifiedtime', 'potentialname');

	//Added these variables which are used as default order by and sortorder in ListView
	var $default_order_by = 'potentialname';
	var $default_sort_order = 'ASC';

	// For Alphabetical search
	var $def_basicsearch_col = 'potentialname';

	var $related_module_table_index = array(
		'Contacts' => array('table_name'=>'vtiger_contactdetails','table_index'=>'contactid','rel_index'=>'contactid')
	);

	var $LBL_POTENTIAL_MAPPING = 'LBL_OPPORTUNITY_MAPPING';
	//var $groupTable = Array('vtiger_potentialgrouprelation','potentialid');
	function Potentials() {
		$this->log = LoggerManager::getLogger('potential');
		$this->db = PearDatabase::getInstance();
		$this->column_fields = getColumnFields('Potentials');
	}

	function save_module($module){
		

/* 		ini_set('display_errors', 1);
		error_reporting(E_ALL);

 */

		// $adb = PearDatabase::getInstance();
		global $adb;
		$record = $this->id;
		$inquiry_info = Vtiger_Record_Model::getInstanceById($record, $module);
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

		// echo 'customer='.$customer; exit;

		// Gathering Assigned Person to Event
		$assigned_users = '';
	
		// Gathering Route Details from history
		 $route_details = $this->get_inquiry_history($record,'cf_717');
		 

		// Gathering Cargo Details from history 
		$cargo_details = $this->get_inquiry_history($record,'cargo_details');	  

		// Gathering history details
	//	$all_field_details = $this->get_inquiry_history($record,'');

		/*
			Adding Dennis Ruiter in copy if his accounts is linked with current RFQ 
		*/

		$d_ruiter = 882;		
		$s_invitee = $adb->pquery("SELECT * FROM `vtiger_invitees` 
																				WHERE `activityid`=$related_customer_toinquiry
																				AND inviteeid = $d_ruiter");
		$n_invitee = $adb->num_rows($s_invitee);
		if ($n_invitee == 1){
			//	$cc .= "d.ruiter@globalinklogistics.com,";
		}

		// Add assigned person to CC
		$cc .= $creator_email.',';

		// Gathering Invited People
		$users = $inquiry_info->get('cf_757');
 		$assigned_cc = $this->arrange_muptiple_users1($users,3);
		$cc .= $this->arrange_people_cc($assigned_cc);
/* 
		echo '$cc='.$cc;
		exit; */
		// Gathering Event Status (Just created or updated);

/* 
		$event_status = 1;
 		$event_status = $this->detect_message_status_p($record);
		if ($event_status == 1) $message_status = "Inquiry from $subject has been created";
		else
		if ($event_status > 1) $message_status = "Inquiry from $subject has been updated";
 */

 		$message_status = "Inquiry from $subject has been updated";
		$event_status = 2;

		$arranged_details = $this->arrange_inqfetch_details($record,$assigned_users,$route_details,$cargo_details,$all_field_details,$event_status);	
		
		// $customer_subject = get_account_details($related_customer_toinquiry,'accountname');
		$customer_subject = $customer;

/* 		$sub_title = "INQ_$subject/".$customer_subject;
		$subject = $sub_title; */
		$subject = "INQ_".$subject;
		$this->send_inq_notification($creator_email,$creator_name,$who_updated_email,$who_updated_name,$module,$record,$subject,$message_status,$arranged_details,$cc,$event_status);
		
	}


	function detect_message_status_p($record){
				global $adb;
        $s_modtracker_basic = $adb->pquery("SELECT * FROM `vtiger_modtracker_basic` where `crmid` = $record");
        $count = $adb->num_rows($s_modtracker_basic);
        return $count;
		}
		


	function send_inq_notification($creator_email,$creator_name,$who_updated_email,$who_updated_name,$module,$record,$subject,$message_status,$sum,$cc,$event_status){
 

		$link = 'https://gems.globalink.net/';
		$link .= "index.php?module=Potentials&view=Detail&record=".$record;
		
		$to = $creator_email;
		$date_time = date('Y-m-d H:i:s'); 
		//$cc = "z.ahmed@globalinklogistics.com";       
		// $from = 'From: '.$who_updated_name.' <'.$who_updated_email.'>';  
		$from = $who_updated_email; 
		$body = '';
		$body .= "<html><head> <style> #calendar_notification tr td{ margin:3px; } </style> </head>
							<body><table id='calendar_notification'> ";
		$body .= "<tr><td colspan=2>Dear <b>".$creator_name.",</b> </td></tr>";
		$body .= "<tr><td colspan=2> $message_status </td></tr>
							 $sum	
							<tr><td colspan=2>Link: <a href='$link'> Link to GEMS </a></td></tr>";
		$body .= "</table> </body> </html> ";
																	
		// Set content-type when sending HTML email
		$headers = "MIME-Version: 1.0" . "\n";
		$headers .= "Content-type:text/html;charset=UTF-8" . "\n";
	
		$headers .= $from . "\n";
		$headers .= 'Reply-To: '.$to.'' . "\n";

		require_once("modules/Emails/mail.php");
		$r = send_mail('Potentials', $to, $from, $from, $subject, $body, $cc ,'','','','',true);
		
		// -------- Working code



/* 
 		$recordId = 1;
		$link .= 'index.php?module=Potentials&view=Detail&record='.$recordId; 
		$body_text = "";
		$body_text .= "<tr><td>Please note $updated_by_name has updated travel form: <b>$travel_number</b></td></tr>";
		$body_text .= "<tr><td class='email_section'>Updated details:</td> </tr>";
		$body_text .= "<tr><td> <a href='$link'>".$link."</td></tr>";
		
		$from = 'r.gusseinov@globalinklogistics.com';
		$to = $creator_email;	
		$cc = "";
		$cc .= 'r.gusseinov@globalinklogistics.com';
		//$cc = "";
		$subject = "RFQ request $creator_name; ID: ".$travel_number;
		$body = '';
		$body .= "<html>
							<head>
								<style>
								#travel_notification{
										font-family:'Open Sans', 'Lucida Grande', Verdana, Tahoma, Arial, sans-serif;
										font-size:14px;
								}
								.email_section {
										padding-top:13px;
								}
								</style> 
						 </head>
						<body><table id='travel_notification'> ";
		$body .= $body_text;
									
		$body .= "</table> </body> </html> ";
		$headers = "MIME-Version: 1.0" . "\n";
		$headers .= "Content-type:text/html;charset=UTF-8" . "\n";
		$headers .= 'From: '.$from . "\n";
		$headers .= 'Reply-To: '.$from.'' . "\n";
		require_once("modules/Emails/mail.php");
		$r = send_mail('Potentials', $to, $from, $from, $subject, $body,$cc,'','','','',true);
 */

	}



	function arrange_inqfetch_details($record,$assigned_users,$route_details,$cargo_details,$all_field_details,$event_status){
		$table = ''; 
		//
		// $adb = PearDatabase::getInstance();
		global $adb;
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
								// $value = arrange_user_format($login,1);
								$value = '';
						}
				
						if ($label == 'Potentialname') $label = 'Inquiry Name';	 
						//if ($label == 'Campaignid') $label = 'Agent id';
						
						if ($label == 'Cf_757') {
								$label = 'Updates are provided to ';
								$value = explode("|", $value);								
								$user = get_user_name($value);								
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





	 /*
		This is module which pullout all last history for specific event 
	 module       - Module name
	 record       - Record in database
	 field_array  - Pullout data by required fields
 */   
 
	public function get_inquiry_history($record,$history_field){
		global $adb;

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



    // Gathering People in CC
    function arrange_people_cc($cc){
			$n = count($cc);
			if ($n > 0){
					for($i = 1; $i <= $n; $i++) $value .= $cc[$i];
			}
			return $value;
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

			// $adb = PearDatabase::getInstance();
			global $adb;

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
													$person_array[$n] = $r_user['email1'].',';
											}
							$buffer = '';
					} else $buffer = $buffer . $users[$i];
			}
			return $person_array;
	}



	/** Function to create list query
	* @param reference variable - where condition is passed when the query is executed
	* Returns Query.
	*/
	function create_list_query($order_by, $where)
	{
		global $log,$current_user;
		require('user_privileges/user_privileges_'.$current_user->id.'.php');
	        require('user_privileges/sharing_privileges_'.$current_user->id.'.php');
        	$tab_id = getTabid("Potentials");
		$log->debug("Entering create_list_query(".$order_by.",". $where.") method ...");
		// Determine if the vtiger_account name is present in the where clause.
		$account_required = preg_match("/accounts\.name/", $where);

		if($account_required)
		{
			$query = "SELECT vtiger_potential.potentialid,  vtiger_potential.potentialname, vtiger_potential.dateclosed FROM vtiger_potential, vtiger_account ";
			$where_auto = "account.accountid = vtiger_potential.related_to AND vtiger_crmentity.deleted=0 ";
		}
		else
		{
			$query = 'SELECT vtiger_potential.potentialid, vtiger_potential.potentialname, vtiger_crmentity.smcreatorid, vtiger_potential.closingdate FROM vtiger_potential inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_potential.potentialid LEFT JOIN vtiger_groups on vtiger_groups.groupid = vtiger_crmentity.smownerid left join vtiger_users on vtiger_users.id = vtiger_crmentity.smownerid ';
			$where_auto = ' AND vtiger_crmentity.deleted=0';
		}

		$query .= $this->getNonAdminAccessControlQuery('Potentials',$current_user);
		if($where != "")
			$query .= " where $where ".$where_auto;
		else
			$query .= " where ".$where_auto;
		if($order_by != "")
			$query .= " ORDER BY $order_by";

		$log->debug("Exiting create_list_query method ...");
		return $query;
	}

	/** Function to export the Opportunities records in CSV Format
	* @param reference variable - order by is passed when the query is executed
	* @param reference variable - where condition is passed when the query is executed
	* Returns Export Potentials Query.
	*/
	function create_export_query($where)
	{
		global $log;
		global $current_user;
		$log->debug("Entering create_export_query(". $where.") method ...");

		include("include/utils/ExportUtils.php");

		//To get the Permitted fields query and the permitted fields list
		$sql = getPermittedFieldsQuery("Potentials", "detail_view");
		$fields_list = getFieldsListFromQuery($sql);

		$userNameSql = getSqlForNameInDisplayFormat(array('first_name'=>
							'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'), 'Users');
		$query = "SELECT $fields_list,case when (vtiger_users.user_name not like '') then $userNameSql else vtiger_groups.groupname end as user_name
				FROM vtiger_potential
				inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_potential.potentialid
				LEFT JOIN vtiger_users ON vtiger_crmentity.smownerid=vtiger_users.id
				LEFT JOIN vtiger_account on vtiger_potential.related_to=vtiger_account.accountid
				LEFT JOIN vtiger_contactdetails on vtiger_potential.contact_id=vtiger_contactdetails.contactid
				LEFT JOIN vtiger_potentialscf on vtiger_potentialscf.potentialid=vtiger_potential.potentialid
                LEFT JOIN vtiger_groups
        	        ON vtiger_groups.groupid = vtiger_crmentity.smownerid
				LEFT JOIN vtiger_campaign
					ON vtiger_campaign.campaignid = vtiger_potential.campaignid";

		$query .= $this->getNonAdminAccessControlQuery('Potentials',$current_user);
		$where_auto = "  vtiger_crmentity.deleted = 0 ";

                if($where != "")
                   $query .= "  WHERE ($where) AND ".$where_auto;
                else
                   $query .= "  WHERE ".$where_auto;

		$log->debug("Exiting create_export_query method ...");
		return $query;

	}



	/** Returns a list of the associated contacts
	 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc..
	 * All Rights Reserved..
	 * Contributor(s): ______________________________________..
	 */
	function get_contacts($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		global $log, $singlepane_view,$currentModule,$current_user;
		$log->debug("Entering get_contacts(".$id.") method ...");
		$this_module = $currentModule;

        $related_module = vtlib_getModuleNameById($rel_tab_id);
		require_once("modules/$related_module/$related_module.php");
		$other = new $related_module();
        vtlib_setup_modulevars($related_module, $other);
		$singular_modname = vtlib_toSingular($related_module);

		$parenttab = getParentTab();

		if($singlepane_view == 'true')
			$returnset = '&return_module='.$this_module.'&return_action=DetailView&return_id='.$id;
		else
			$returnset = '&return_module='.$this_module.'&return_action=CallRelatedList&return_id='.$id;

		$button = '';

		$accountid = $this->column_fields['related_to'];
		$search_string = "&fromPotential=true&acc_id=$accountid";

		if($actions) {
			if(is_string($actions)) $actions = explode(',', strtoupper($actions));
			if(in_array('SELECT', $actions) && isPermitted($related_module,4, '') == 'yes') {
				$button .= "<input title='".getTranslatedString('LBL_SELECT')." ". getTranslatedString($related_module). "' class='crmbutton small edit' type='button' onclick=\"return window.open('index.php?module=$related_module&return_module=$currentModule&action=Popup&popuptype=detailview&select=enable&form=EditView&form_submit=false&recordid=$id&parenttab=$parenttab$search_string','test','width=640,height=602,resizable=0,scrollbars=0');\" value='". getTranslatedString('LBL_SELECT'). " " . getTranslatedString($related_module) ."'>&nbsp;";
			}
			if(in_array('ADD', $actions) && isPermitted($related_module,1, '') == 'yes') {
				$button .= "<input title='".getTranslatedString('LBL_ADD_NEW'). " ". getTranslatedString($singular_modname) ."' class='crmbutton small create'" .
					" onclick='this.form.action.value=\"EditView\";this.form.module.value=\"$related_module\"' type='submit' name='button'" .
					" value='". getTranslatedString('LBL_ADD_NEW'). " " . getTranslatedString($singular_modname) ."'>&nbsp;";
			}
		}

		$userNameSql = getSqlForNameInDisplayFormat(array('first_name'=>
							'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'), 'Users');
		$query = 'select case when (vtiger_users.user_name not like "") then '.$userNameSql.' else vtiger_groups.groupname end as user_name,
					vtiger_contactdetails.accountid,vtiger_potential.potentialid, vtiger_potential.potentialname, vtiger_contactdetails.contactid,
					vtiger_contactdetails.lastname, vtiger_contactdetails.firstname, vtiger_contactdetails.title, vtiger_contactdetails.department,
					vtiger_contactdetails.email, vtiger_contactdetails.phone, vtiger_crmentity.crmid, vtiger_crmentity.smownerid,
					vtiger_crmentity.modifiedtime , vtiger_account.accountname from vtiger_potential
					left join vtiger_contpotentialrel on vtiger_contpotentialrel.potentialid = vtiger_potential.potentialid
					inner join vtiger_contactdetails on ((vtiger_contactdetails.contactid = vtiger_contpotentialrel.contactid) or (vtiger_contactdetails.contactid = vtiger_potential.contact_id))
					INNER JOIN vtiger_contactaddress ON vtiger_contactdetails.contactid = vtiger_contactaddress.contactaddressid
					INNER JOIN vtiger_contactsubdetails ON vtiger_contactdetails.contactid = vtiger_contactsubdetails.contactsubscriptionid
					INNER JOIN vtiger_customerdetails ON vtiger_contactdetails.contactid = vtiger_customerdetails.customerid
					INNER JOIN vtiger_contactscf ON vtiger_contactdetails.contactid = vtiger_contactscf.contactid
					inner join vtiger_crmentity on vtiger_crmentity.crmid = vtiger_contactdetails.contactid
					left join vtiger_account on vtiger_account.accountid = vtiger_contactdetails.accountid
					left join vtiger_groups on vtiger_groups.groupid=vtiger_crmentity.smownerid
					left join vtiger_users on vtiger_crmentity.smownerid=vtiger_users.id
					where vtiger_potential.potentialid = '.$id.' and vtiger_crmentity.deleted=0';

		$return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		$log->debug("Exiting get_contacts method ...");
		return $return_value;
	}

	/** Returns a list of the associated calls
	 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc..
	 * All Rights Reserved..
	 * Contributor(s): ______________________________________..
	 */
	function get_activities($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		global $log, $singlepane_view,$currentModule,$current_user;
		$log->debug("Entering get_activities(".$id.") method ...");
		$this_module = $currentModule;

        $related_module = vtlib_getModuleNameById($rel_tab_id);
		require_once("modules/$related_module/Activity.php");
		$other = new Activity();
        vtlib_setup_modulevars($related_module, $other);
		$singular_modname = vtlib_toSingular($related_module);

		$parenttab = getParentTab();

		if($singlepane_view == 'true')
			$returnset = '&return_module='.$this_module.'&return_action=DetailView&return_id='.$id;
		else
			$returnset = '&return_module='.$this_module.'&return_action=CallRelatedList&return_id='.$id;

		$button = '';

		$button .= '<input type="hidden" name="activity_mode">';

		if($actions) {
			if(is_string($actions)) $actions = explode(',', strtoupper($actions));
			if(in_array('ADD', $actions) && isPermitted($related_module,1, '') == 'yes') {
				if(getFieldVisibilityPermission('Calendar',$current_user->id,'parent_id', 'readwrite') == '0') {
					$button .= "<input title='".getTranslatedString('LBL_NEW'). " ". getTranslatedString('LBL_TODO', $related_module) ."' class='crmbutton small create'" .
						" onclick='this.form.action.value=\"EditView\";this.form.module.value=\"$related_module\";this.form.return_module.value=\"$this_module\";this.form.activity_mode.value=\"Task\";' type='submit' name='button'" .
						" value='". getTranslatedString('LBL_ADD_NEW'). " " . getTranslatedString('LBL_TODO', $related_module) ."'>&nbsp;";
				}
				if(getFieldVisibilityPermission('Events',$current_user->id,'parent_id', 'readwrite') == '0') {
					$button .= "<input title='".getTranslatedString('LBL_NEW'). " ". getTranslatedString('LBL_TODO', $related_module) ."' class='crmbutton small create'" .
						" onclick='this.form.action.value=\"EditView\";this.form.module.value=\"$related_module\";this.form.return_module.value=\"$this_module\";this.form.activity_mode.value=\"Events\";' type='submit' name='button'" .
						" value='". getTranslatedString('LBL_ADD_NEW'). " " . getTranslatedString('LBL_EVENT', $related_module) ."'>";
				}
			}
		}

		$userNameSql = getSqlForNameInDisplayFormat(array('first_name'=>
							'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'), 'Users');
		$query = "SELECT vtiger_activity.activityid as 'tmp_activity_id',vtiger_activity.*,vtiger_seactivityrel.crmid as parent_id, vtiger_contactdetails.lastname,vtiger_contactdetails.firstname,
					vtiger_crmentity.crmid, vtiger_crmentity.smownerid, vtiger_crmentity.modifiedtime,
					case when (vtiger_users.user_name not like '') then $userNameSql else vtiger_groups.groupname end as user_name,
					vtiger_recurringevents.recurringtype from vtiger_activity
					inner join vtiger_seactivityrel on vtiger_seactivityrel.activityid=vtiger_activity.activityid
					inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_activity.activityid
					left join vtiger_cntactivityrel on vtiger_cntactivityrel.activityid = vtiger_activity.activityid
					left join vtiger_contactdetails on vtiger_contactdetails.contactid = vtiger_cntactivityrel.contactid
					inner join vtiger_potential on vtiger_potential.potentialid=vtiger_seactivityrel.crmid
					left join vtiger_users on vtiger_users.id=vtiger_crmentity.smownerid
					left join vtiger_groups on vtiger_groups.groupid=vtiger_crmentity.smownerid
					left outer join vtiger_recurringevents on vtiger_recurringevents.activityid=vtiger_activity.activityid
					where vtiger_seactivityrel.crmid=".$id." and vtiger_crmentity.deleted=0
					and ((vtiger_activity.activitytype='Task' and vtiger_activity.status not in ('Completed','Deferred'))
					or (vtiger_activity.activitytype NOT in ('Emails','Task') and  vtiger_activity.eventstatus not in ('','Held'))) ";

		$return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		$log->debug("Exiting get_activities method ...");
		return $return_value;
	}

	 /**
	 * Function to get Contact related Products
	 * @param  integer   $id  - contactid
	 * returns related Products record in array format
	 */
	function get_products($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		global $log, $singlepane_view,$currentModule,$current_user;
		$log->debug("Entering get_products(".$id.") method ...");
		$this_module = $currentModule;

        $related_module = vtlib_getModuleNameById($rel_tab_id);
		require_once("modules/$related_module/$related_module.php");
		$other = new $related_module();
        vtlib_setup_modulevars($related_module, $other);
		$singular_modname = vtlib_toSingular($related_module);

		$parenttab = getParentTab();

		if($singlepane_view == 'true')
			$returnset = '&return_module='.$this_module.'&return_action=DetailView&return_id='.$id;
		else
			$returnset = '&return_module='.$this_module.'&return_action=CallRelatedList&return_id='.$id;

		$button = '';

		if($actions) {
			if(is_string($actions)) $actions = explode(',', strtoupper($actions));
			if(in_array('SELECT', $actions) && isPermitted($related_module,4, '') == 'yes') {
				$button .= "<input title='".getTranslatedString('LBL_SELECT')." ". getTranslatedString($related_module). "' class='crmbutton small edit' type='button' onclick=\"return window.open('index.php?module=$related_module&return_module=$currentModule&action=Popup&popuptype=detailview&select=enable&form=EditView&form_submit=false&recordid=$id&parenttab=$parenttab','test','width=640,height=602,resizable=0,scrollbars=0');\" value='". getTranslatedString('LBL_SELECT'). " " . getTranslatedString($related_module) ."'>&nbsp;";
			}
			if(in_array('ADD', $actions) && isPermitted($related_module,1, '') == 'yes') {
				$button .= "<input title='".getTranslatedString('LBL_ADD_NEW'). " ". getTranslatedString($singular_modname) ."' class='crmbutton small create'" .
					" onclick='this.form.action.value=\"EditView\";this.form.module.value=\"$related_module\"' type='submit' name='button'" .
					" value='". getTranslatedString('LBL_ADD_NEW'). " " . getTranslatedString($singular_modname) ."'>&nbsp;";
			}
		}

		$query = "SELECT vtiger_products.productid, vtiger_products.productname, vtiger_products.productcode,
				vtiger_products.commissionrate, vtiger_products.qty_per_unit, vtiger_products.unit_price,
				vtiger_crmentity.crmid, vtiger_crmentity.smownerid
				FROM vtiger_products
				INNER JOIN vtiger_seproductsrel ON vtiger_products.productid = vtiger_seproductsrel.productid and vtiger_seproductsrel.setype = 'Potentials'
				INNER JOIN vtiger_productcf
				ON vtiger_products.productid = vtiger_productcf.productid
				INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_products.productid
				INNER JOIN vtiger_potential ON vtiger_potential.potentialid = vtiger_seproductsrel.crmid
				LEFT JOIN vtiger_users
					ON vtiger_users.id=vtiger_crmentity.smownerid
				LEFT JOIN vtiger_groups
					ON vtiger_groups.groupid = vtiger_crmentity.smownerid
				WHERE vtiger_crmentity.deleted = 0 AND vtiger_potential.potentialid = $id";

		$return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		$log->debug("Exiting get_products method ...");
		return $return_value;
	}

	/**	Function used to get the Sales Stage history of the Potential
	 *	@param $id - potentialid
	 *	return $return_data - array with header and the entries in format Array('header'=>$header,'entries'=>$entries_list) where as $header and $entries_list are array which contains all the column values of an row
	 */
	function get_stage_history($id)
	{
		global $log;
		$log->debug("Entering get_stage_history(".$id.") method ...");

		global $adb;
		global $mod_strings;
		global $app_strings;

		$query = 'select vtiger_potstagehistory.*, vtiger_potential.potentialname from vtiger_potstagehistory inner join vtiger_potential on vtiger_potential.potentialid = vtiger_potstagehistory.potentialid inner join vtiger_crmentity on vtiger_crmentity.crmid = vtiger_potential.potentialid where vtiger_crmentity.deleted = 0 and vtiger_potential.potentialid = ?';
		$result=$adb->pquery($query, array($id));
		$noofrows = $adb->num_rows($result);

		$header[] = $app_strings['LBL_AMOUNT'];
		$header[] = $app_strings['LBL_SALES_STAGE'];
		$header[] = $app_strings['LBL_PROBABILITY'];
		$header[] = $app_strings['LBL_CLOSE_DATE'];
		$header[] = $app_strings['LBL_LAST_MODIFIED'];

		//Getting the field permission for the current user. 1 - Not Accessible, 0 - Accessible
		//Sales Stage, Expected Close Dates are mandatory fields. So no need to do security check to these fields.
		global $current_user;

		//If field is accessible then getFieldVisibilityPermission function will return 0 else return 1
		$amount_access = (getFieldVisibilityPermission('Potentials', $current_user->id, 'amount') != '0')? 1 : 0;
		$probability_access = (getFieldVisibilityPermission('Potentials', $current_user->id, 'probability') != '0')? 1 : 0;
		$picklistarray = getAccessPickListValues('Potentials');

		$potential_stage_array = $picklistarray['sales_stage'];
		//- ==> picklist field is not permitted in profile
		//Not Accessible - picklist is permitted in profile but picklist value is not permitted
		$error_msg = 'Not Accessible';

		while($row = $adb->fetch_array($result))
		{
			$entries = Array();

			$entries[] = ($amount_access != 1)? $row['amount'] : 0;
			$entries[] = (in_array($row['stage'], $potential_stage_array))? $row['stage']: $error_msg;
			$entries[] = ($probability_access != 1) ? $row['probability'] : 0;
			$entries[] = DateTimeField::convertToUserFormat($row['closedate']);
			$date = new DateTimeField($row['lastmodified']);
			$entries[] = $date->getDisplayDate();

			$entries_list[] = $entries;
		}

		$return_data = Array('header'=>$header,'entries'=>$entries_list);

	 	$log->debug("Exiting get_stage_history method ...");

		return $return_data;
	}

	/**
	* Function to get Potential related Task & Event which have activity type Held, Completed or Deferred.
	* @param  integer   $id
	* returns related Task or Event record in array format
	*/
	function get_history($id)
	{
			global $log;
			$log->debug("Entering get_history(".$id.") method ...");
			$userNameSql = getSqlForNameInDisplayFormat(array('first_name'=>
							'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'), 'Users');
			$query = "SELECT vtiger_activity.activityid, vtiger_activity.subject, vtiger_activity.status,
		vtiger_activity.eventstatus, vtiger_activity.activitytype,vtiger_activity.date_start,
		vtiger_activity.due_date, vtiger_activity.time_start,vtiger_activity.time_end,
		vtiger_crmentity.modifiedtime, vtiger_crmentity.createdtime,
		vtiger_crmentity.description,case when (vtiger_users.user_name not like '') then $userNameSql else vtiger_groups.groupname end as user_name
				from vtiger_activity
				inner join vtiger_seactivityrel on vtiger_seactivityrel.activityid=vtiger_activity.activityid
				inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_activity.activityid
				left join vtiger_groups on vtiger_groups.groupid=vtiger_crmentity.smownerid
				left join vtiger_users on vtiger_users.id=vtiger_crmentity.smownerid
				where (vtiger_activity.activitytype != 'Emails')
				and (vtiger_activity.status = 'Completed' or vtiger_activity.status = 'Deferred' or (vtiger_activity.eventstatus = 'Held' and vtiger_activity.eventstatus != ''))
				and vtiger_seactivityrel.crmid=".$id."
                                and vtiger_crmentity.deleted = 0";
		//Don't add order by, because, for security, one more condition will be added with this query in include/RelatedListView.php

		$log->debug("Exiting get_history method ...");
		return getHistory('Potentials',$query,$id);
	}


	  /**
	  * Function to get Potential related Quotes
	  * @param  integer   $id  - potentialid
	  * returns related Quotes record in array format
	  */
	function get_quotes($id, $cur_tab_id, $rel_tab_id, $actions=false) {
 
		global $log, $singlepane_view,$currentModule,$current_user;
		$log->debug("Entering get_quotes(".$id.") method ...");
		$this_module = $currentModule;

        $related_module = vtlib_getModuleNameById($rel_tab_id);
		require_once("modules/$related_module/$related_module.php");
		$other = new $related_module();
        vtlib_setup_modulevars($related_module, $other);
		$singular_modname = vtlib_toSingular($related_module);

		$parenttab = getParentTab();

		if($singlepane_view == 'true')
			$returnset = '&return_module='.$this_module.'&return_action=DetailView&return_id='.$id;
		else
			$returnset = '&return_module='.$this_module.'&return_action=CallRelatedList&return_id='.$id;

		$button = '';

		if($actions && getFieldVisibilityPermission($related_module, $current_user->id, 'potential_id', 'readwrite') == '0') {
			if(is_string($actions)) $actions = explode(',', strtoupper($actions));
			if(in_array('SELECT', $actions) && isPermitted($related_module,4, '') == 'yes') {
				$button .= "<input title='".getTranslatedString('LBL_SELECT')." ". getTranslatedString($related_module). "' class='crmbutton small edit' type='button' onclick=\"return window.open('index.php?module=$related_module&return_module=$currentModule&action=Popup&popuptype=detailview&select=enable&form=EditView&form_submit=false&recordid=$id&parenttab=$parenttab','test','width=640,height=602,resizable=0,scrollbars=0');\" value='". getTranslatedString('LBL_SELECT'). " " . getTranslatedString($related_module) ."'>&nbsp;";
			}
			if(in_array('ADD', $actions) && isPermitted($related_module,1, '') == 'yes') {
				$button .= "<input title='".getTranslatedString('LBL_ADD_NEW'). " ". getTranslatedString($singular_modname) ."' class='crmbutton small create'" .
					" onclick='this.form.action.value=\"EditView\";this.form.module.value=\"$related_module\"' type='submit' name='button'" .
					" value='". getTranslatedString('LBL_ADD_NEW'). " " . getTranslatedString($singular_modname) ."'>&nbsp;";
			}
		}

		$userNameSql = getSqlForNameInDisplayFormat(array('first_name'=>
							'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'), 'Users');
		$query = "select case when (vtiger_users.user_name not like '') then $userNameSql else vtiger_groups.groupname end as user_name,
					vtiger_account.accountname, vtiger_crmentity.*, vtiger_quotes.*, vtiger_potential.potentialname from vtiger_quotes
					inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_quotes.quoteid
					left outer join vtiger_potential on vtiger_potential.potentialid=vtiger_quotes.potentialid
					left join vtiger_groups on vtiger_groups.groupid=vtiger_crmentity.smownerid
                    LEFT JOIN vtiger_quotescf ON vtiger_quotescf.quoteid = vtiger_quotes.quoteid
					LEFT JOIN vtiger_quotesbillads ON vtiger_quotesbillads.quotebilladdressid = vtiger_quotes.quoteid
					LEFT JOIN vtiger_quotesshipads ON vtiger_quotesshipads.quoteshipaddressid = vtiger_quotes.quoteid
					left join vtiger_users on vtiger_users.id=vtiger_crmentity.smownerid
					LEFT join vtiger_account on vtiger_account.accountid=vtiger_quotes.accountid
					where vtiger_crmentity.deleted=0 and vtiger_potential.potentialid=".$id;

		$return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		$log->debug("Exiting get_quotes method ...");
		return $return_value;
	}

	/**
	 * Function to get Potential related SalesOrder
 	 * @param  integer   $id  - potentialid
	 * returns related SalesOrder record in array format
	 */
	function get_salesorder($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		global $log, $singlepane_view,$currentModule,$current_user;
		$log->debug("Entering get_salesorder(".$id.") method ...");
		$this_module = $currentModule;

        $related_module = vtlib_getModuleNameById($rel_tab_id);
		require_once("modules/$related_module/$related_module.php");
		$other = new $related_module();
        vtlib_setup_modulevars($related_module, $other);
		$singular_modname = vtlib_toSingular($related_module);

		$parenttab = getParentTab();

		if($singlepane_view == 'true')
			$returnset = '&return_module='.$this_module.'&return_action=DetailView&return_id='.$id;
		else
			$returnset = '&return_module='.$this_module.'&return_action=CallRelatedList&return_id='.$id;

		$button = '';

		if($actions && getFieldVisibilityPermission($related_module, $current_user->id, 'potential_id', 'readwrite') == '0') {
			if(is_string($actions)) $actions = explode(',', strtoupper($actions));
			if(in_array('SELECT', $actions) && isPermitted($related_module,4, '') == 'yes') {
				$button .= "<input title='".getTranslatedString('LBL_SELECT')." ". getTranslatedString($related_module). "' class='crmbutton small edit' type='button' onclick=\"return window.open('index.php?module=$related_module&return_module=$currentModule&action=Popup&popuptype=detailview&select=enable&form=EditView&form_submit=false&recordid=$id&parenttab=$parenttab','test','width=640,height=602,resizable=0,scrollbars=0');\" value='". getTranslatedString('LBL_SELECT'). " " . getTranslatedString($related_module) ."'>&nbsp;";
			}
			if(in_array('ADD', $actions) && isPermitted($related_module,1, '') == 'yes') {
				$button .= "<input title='".getTranslatedString('LBL_ADD_NEW'). " ". getTranslatedString($singular_modname) ."' class='crmbutton small create'" .
					" onclick='this.form.action.value=\"EditView\";this.form.module.value=\"$related_module\"' type='submit' name='button'" .
					" value='". getTranslatedString('LBL_ADD_NEW'). " " . getTranslatedString($singular_modname) ."'>&nbsp;";
			}
		}

		$userNameSql = getSqlForNameInDisplayFormat(array('first_name'=>
							'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'), 'Users');
		$query = "select vtiger_crmentity.*, vtiger_salesorder.*, vtiger_quotes.subject as quotename
			, vtiger_account.accountname, vtiger_potential.potentialname,case when
			(vtiger_users.user_name not like '') then $userNameSql else vtiger_groups.groupname
			end as user_name from vtiger_salesorder
			inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_salesorder.salesorderid
			left outer join vtiger_quotes on vtiger_quotes.quoteid=vtiger_salesorder.quoteid
			left outer join vtiger_account on vtiger_account.accountid=vtiger_salesorder.accountid
			left outer join vtiger_potential on vtiger_potential.potentialid=vtiger_salesorder.potentialid
			left join vtiger_groups on vtiger_groups.groupid=vtiger_crmentity.smownerid
            LEFT JOIN vtiger_salesordercf ON vtiger_salesordercf.salesorderid = vtiger_salesorder.salesorderid
            LEFT JOIN vtiger_invoice_recurring_info ON vtiger_invoice_recurring_info.start_period = vtiger_salesorder.salesorderid
			LEFT JOIN vtiger_sobillads ON vtiger_sobillads.sobilladdressid = vtiger_salesorder.salesorderid
			LEFT JOIN vtiger_soshipads ON vtiger_soshipads.soshipaddressid = vtiger_salesorder.salesorderid
			left join vtiger_users on vtiger_users.id=vtiger_crmentity.smownerid
			 where vtiger_crmentity.deleted=0 and vtiger_potential.potentialid = ".$id;

		$return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		$log->debug("Exiting get_salesorder method ...");
		return $return_value;
	}

	/**
	 * Move the related records of the specified list of id's to the given record.
	 * @param String This module name
	 * @param Array List of Entity Id's from which related records need to be transfered
	 * @param Integer Id of the the Record to which the related records are to be moved
	 */
	function transferRelatedRecords($module, $transferEntityIds, $entityId) {
		global $adb,$log;
		$log->debug("Entering function transferRelatedRecords ($module, $transferEntityIds, $entityId)");

		$rel_table_arr = Array("Activities"=>"vtiger_seactivityrel","Contacts"=>"vtiger_contpotentialrel","Products"=>"vtiger_seproductsrel",
						"Attachments"=>"vtiger_seattachmentsrel","Quotes"=>"vtiger_quotes","SalesOrder"=>"vtiger_salesorder",
						"Documents"=>"vtiger_senotesrel");

		$tbl_field_arr = Array("vtiger_seactivityrel"=>"activityid","vtiger_contpotentialrel"=>"contactid","vtiger_seproductsrel"=>"productid",
						"vtiger_seattachmentsrel"=>"attachmentsid","vtiger_quotes"=>"quoteid","vtiger_salesorder"=>"salesorderid",
						"vtiger_senotesrel"=>"notesid");

		$entity_tbl_field_arr = Array("vtiger_seactivityrel"=>"crmid","vtiger_contpotentialrel"=>"potentialid","vtiger_seproductsrel"=>"crmid",
						"vtiger_seattachmentsrel"=>"crmid","vtiger_quotes"=>"potentialid","vtiger_salesorder"=>"potentialid",
						"vtiger_senotesrel"=>"crmid");

		foreach($transferEntityIds as $transferId) {
			foreach($rel_table_arr as $rel_module=>$rel_table) {
				$id_field = $tbl_field_arr[$rel_table];
				$entity_id_field = $entity_tbl_field_arr[$rel_table];
				// IN clause to avoid duplicate entries
				$sel_result =  $adb->pquery("select $id_field from $rel_table where $entity_id_field=? " .
						" and $id_field not in (select $id_field from $rel_table where $entity_id_field=?)",
						array($transferId,$entityId));
				$res_cnt = $adb->num_rows($sel_result);
				if($res_cnt > 0) {
					for($i=0;$i<$res_cnt;$i++) {
						$id_field_value = $adb->query_result($sel_result,$i,$id_field);
						$adb->pquery("update $rel_table set $entity_id_field=? where $entity_id_field=? and $id_field=?",
							array($entityId,$transferId,$id_field_value));
					}
				}
			}
		}
		parent::transferRelatedRecords($module, $transferEntityIds, $entityId);
		$log->debug("Exiting transferRelatedRecords...");
	}

	/*
	 * Function to get the secondary query part of a report
	 * @param - $module primary module name
	 * @param - $secmodule secondary module name
	 * returns the query string formed on fetching the related data for report for secondary module
	 */
	function generateReportsSecQuery($module,$secmodule,$queryPlanner){
		$matrix = $queryPlanner->newDependencyMatrix();
		$matrix->setDependency('vtiger_crmentityPotentials',array('vtiger_groupsPotentials','vtiger_usersPotentials','vtiger_lastModifiedByPotentials'));

		if (!$queryPlanner->requireTable("vtiger_potential",$matrix)){
			return '';
		}
        $matrix->setDependency('vtiger_potential', array('vtiger_crmentityPotentials','vtiger_accountPotentials',
											'vtiger_contactdetailsPotentials','vtiger_campaignPotentials','vtiger_potentialscf'));

		$query = $this->getRelationQuery($module,$secmodule,"vtiger_potential","potentialid", $queryPlanner);

		if ($queryPlanner->requireTable("vtiger_crmentityPotentials",$matrix)){
			$query .= " left join vtiger_crmentity as vtiger_crmentityPotentials on vtiger_crmentityPotentials.crmid=vtiger_potential.potentialid and vtiger_crmentityPotentials.deleted=0";
		}
		if ($queryPlanner->requireTable("vtiger_accountPotentials")){
			$query .= " left join vtiger_account as vtiger_accountPotentials on vtiger_potential.related_to = vtiger_accountPotentials.accountid";
		}
		if ($queryPlanner->requireTable("vtiger_contactdetailsPotentials")){
			$query .= " left join vtiger_contactdetails as vtiger_contactdetailsPotentials on vtiger_potential.contact_id = vtiger_contactdetailsPotentials.contactid";
		}
		if ($queryPlanner->requireTable("vtiger_potentialscf")){
			$query .= " left join vtiger_potentialscf on vtiger_potentialscf.potentialid = vtiger_potential.potentialid";
		}
		if ($queryPlanner->requireTable("vtiger_groupsPotentials")){
			$query .= " left join vtiger_groups vtiger_groupsPotentials on vtiger_groupsPotentials.groupid = vtiger_crmentityPotentials.smownerid";
		}
		if ($queryPlanner->requireTable("vtiger_usersPotentials")){
			$query .= " left join vtiger_users as vtiger_usersPotentials on vtiger_usersPotentials.id = vtiger_crmentityPotentials.smownerid";
		}
		if ($queryPlanner->requireTable("vtiger_campaignPotentials")){
			$query .= " left join vtiger_campaign as vtiger_campaignPotentials on vtiger_potential.campaignid = vtiger_campaignPotentials.campaignid";
		}
		if ($queryPlanner->requireTable("vtiger_lastModifiedByPotentials")){
			$query .= " left join vtiger_users as vtiger_lastModifiedByPotentials on vtiger_lastModifiedByPotentials.id = vtiger_crmentityPotentials.modifiedby ";
		}
        if ($queryPlanner->requireTable("vtiger_createdbyPotentials")){
			$query .= " left join vtiger_users as vtiger_createdbyPotentials on vtiger_createdbyPotentials.id = vtiger_crmentityPotentials.smcreatorid ";
		}

		//if secondary modules custom reference field is selected
        $query .= parent::getReportsUiType10Query($secmodule, $queryPlanner);
        
		return $query;
	}

	/*
	 * Function to get the relation tables for related modules
	 * @param - $secmodule secondary module name
	 * returns the array with table names and fieldnames storing relations between module and this module
	 */
	function setRelationTables($secmodule){
		$rel_tables = array (
			"Calendar" => array("vtiger_seactivityrel"=>array("crmid","activityid"),"vtiger_potential"=>"potentialid"),
			"Products" => array("vtiger_seproductsrel"=>array("crmid","productid"),"vtiger_potential"=>"potentialid"),
			"Quotes" => array("vtiger_quotes"=>array("potentialid","quoteid"),"vtiger_potential"=>"potentialid"),
			"SalesOrder" => array("vtiger_salesorder"=>array("potentialid","salesorderid"),"vtiger_potential"=>"potentialid"),
			"Documents" => array("vtiger_senotesrel"=>array("crmid","notesid"),"vtiger_potential"=>"potentialid"),
			"Accounts" => array("vtiger_potential"=>array("potentialid","related_to")),
			"Contacts" => array("vtiger_potential"=>array("potentialid","contact_id")),
            "Emails" => array("vtiger_seactivityrel"=>array("crmid","activityid"),"vtiger_potential"=>"potentialid"),
		);
		return $rel_tables[$secmodule];
	}

	// Function to unlink all the dependent entities of the given Entity by Id
	function unlinkDependencies($module, $id) {
		global $log;
		/*//Backup Activity-Potentials Relation
		$act_q = "select activityid from vtiger_seactivityrel where crmid = ?";
		$act_res = $this->db->pquery($act_q, array($id));
		if ($this->db->num_rows($act_res) > 0) {
			for($k=0;$k < $this->db->num_rows($act_res);$k++)
			{
				$act_id = $this->db->query_result($act_res,$k,"activityid");
				$params = array($id, RB_RECORD_DELETED, 'vtiger_seactivityrel', 'crmid', 'activityid', $act_id);
				$this->db->pquery("insert into vtiger_relatedlists_rb values (?,?,?,?,?,?)", $params);
			}
		}
		$sql = 'delete from vtiger_seactivityrel where crmid = ?';
		$this->db->pquery($sql, array($id));*/

		parent::unlinkDependencies($module, $id);
	}

	// Function to unlink an entity with given Id from another entity
	function unlinkRelationship($id, $return_module, $return_id) {
		global $log;
		if(empty($return_module) || empty($return_id)) return;

		if($return_module == 'Accounts') {
			$this->trash($this->module_name, $id);
		} elseif($return_module == 'Campaigns') {
			$sql = 'UPDATE vtiger_potential SET campaignid = ? WHERE potentialid = ?';
			$this->db->pquery($sql, array(null, $id));
		} elseif($return_module == 'Products') {
			$sql = 'DELETE FROM vtiger_seproductsrel WHERE crmid=? AND productid=?';
			$this->db->pquery($sql, array($id, $return_id));
		} elseif($return_module == 'Contacts') {
			$sql = 'DELETE FROM vtiger_contpotentialrel WHERE potentialid=? AND contactid=?';
			$this->db->pquery($sql, array($id, $return_id));
			
			//If contact related to potential through edit of record,that entry will be present in
			//vtiger_potential contact_id column,which should be set to zero
			$sql = 'UPDATE vtiger_potential SET contact_id = ? WHERE potentialid=? AND contact_id=?';
			$this->db->pquery($sql, array(0,$id, $return_id));

			// Potential directly linked with Contact (not through Account - vtiger_contpotentialrel)
			$directRelCheck = $this->db->pquery('SELECT related_to FROM vtiger_potential WHERE potentialid=? AND contact_id=?', array($id, $return_id));
			if($this->db->num_rows($directRelCheck)) {
				$this->trash($this->module_name, $id);
			}
		} elseif($return_module == 'Documents') {
            $sql = 'DELETE FROM vtiger_senotesrel WHERE crmid=? AND notesid=?';
            $this->db->pquery($sql, array($id, $return_id));
        } else {
			parent::unlinkRelationship($id, $return_module, $return_id);
		}
	}

	function save_related_module($module, $crmid, $with_module, $with_crmids, $otherParams = array()) {
		$adb = PearDatabase::getInstance();

		if(!is_array($with_crmids)) $with_crmids = Array($with_crmids);
		foreach($with_crmids as $with_crmid) {
			if($with_module == 'Contacts') { //When we select contact from potential related list
				$sql = "insert into vtiger_contpotentialrel values (?,?)";
				$adb->pquery($sql, array($with_crmid, $crmid));

			} elseif($with_module == 'Products') {//when we select product from potential related list
				$sql = 'INSERT INTO vtiger_seproductsrel VALUES(?,?,?,?)';
				$adb->pquery($sql, array($crmid, $with_crmid,'Potentials', 1));

			} else {
				parent::save_related_module($module, $crmid, $with_module, $with_crmid);
			}
		}
	}
    
    function get_emails($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		global $currentModule;
        $related_module = vtlib_getModuleNameById($rel_tab_id);
		require_once("modules/$related_module/$related_module.php");
		$other = new $related_module();
        vtlib_setup_modulevars($related_module, $other);

        $returnset = '&return_module='.$currentModule.'&return_action=CallRelatedList&return_id='.$id;

		$button = '<input type="hidden" name="email_directing_module"><input type="hidden" name="record">';

		$userNameSql = getSqlForNameInDisplayFormat(array('first_name'=>'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'), 'Users');
		$query = "SELECT CASE WHEN (vtiger_users.user_name NOT LIKE '') THEN $userNameSql ELSE vtiger_groups.groupname END AS user_name,
                vtiger_activity.activityid, vtiger_activity.subject, vtiger_activity.activitytype, vtiger_crmentity.modifiedtime,
                vtiger_crmentity.crmid, vtiger_crmentity.smownerid, vtiger_activity.date_start, vtiger_activity.time_start,
                vtiger_seactivityrel.crmid as parent_id FROM vtiger_activity, vtiger_seactivityrel, vtiger_potential, vtiger_users,
                vtiger_crmentity LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid WHERE 
                vtiger_seactivityrel.activityid = vtiger_activity.activityid AND 
                vtiger_potential.potentialid = vtiger_seactivityrel.crmid AND vtiger_users.id = vtiger_crmentity.smownerid
                AND vtiger_crmentity.crmid = vtiger_activity.activityid  AND vtiger_potential.potentialid = $id AND
                vtiger_activity.activitytype = 'Emails' AND vtiger_crmentity.deleted = 0";

		$return_value = GetRelatedList($currentModule, $related_module, $other, $query, $button, $returnset);

		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		return $return_value;
	}

	/**
	 * Invoked when special actions are to be performed on the module.
	 * @param String Module name
	 * @param String Event Type
	 */
	function vtlib_handler($moduleName, $eventType) {
		if ($moduleName == 'Potentials') {
			$db = PearDatabase::getInstance();
			if ($eventType == 'module.disabled') {
				$db->pquery('UPDATE vtiger_settings_field SET active=1 WHERE name=?', array($this->LBL_POTENTIAL_MAPPING));
			} else if ($eventType == 'module.enabled') {
				$db->pquery('UPDATE vtiger_settings_field SET active=0 WHERE name=?', array($this->LBL_POTENTIAL_MAPPING));
			}
		}
	}
}

?>