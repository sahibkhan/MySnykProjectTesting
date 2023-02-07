<?php
chdir(dirname(__FILE__) . '/../..');
include_once 'vtlib/Vtiger/Module.php';
include_once 'includes/main/WebUI.php';
include_once 'include/Webservices/Utils.php';
//echo getcwd() . "<br>";exit;
//require_once('include/database/PearDatabase.php');
date_default_timezone_set('UTC');
require_once 'vtlib/Vtiger/Mailer.php';
$mailer = new Vtiger_Mailer();
$mailer->IsHTML(true);

global $adb;
global $site_URL;
$today_date = date('Y-m-d');
$next_reminder_notification = date('Y-m-d', strtotime($today_date. ' + 3 days'));
$pending_approvals = $adb->query("SELECT * FROM `vtiger_procurementcf` p 
INNER JOIN vtiger_crmentity c on p.procurementid = c.crmid
INNER JOIN vtiger_send_approval s on p.procurementid = s.procurement_id
where (p.proc_order_status = 'Not Approved' OR p.proc_order_status='Under Review')
AND c.deleted=0 AND s.approval_status='Pending' and s.next_notification='$today_date' group by p.procurementid");

if($adb->num_rows($pending_approvals)>0)
{
	for($i=0; $i<$adb->num_rows($pending_approvals); $i++) 
	{
		
		$pending_approval = $adb->query_result_rowdata($pending_approvals, $i);
		
		$approval_user = Vtiger_Record_Model::getInstanceById($pending_approval['who_approve_id'], "Users");
		
		$procurement_data = Vtiger_Record_Model::getInstanceById($pending_approval['procurementid'], 'Procurement');
		//echo "<pre>"; print_r($procurement_data);echo "</pre>";exit;
		$ProcurementType_data = Vtiger_Record_Model::getInstanceById($procurement_data->get('proc_proctype'), 'ProcurementTypes');
		$ProcurementType_code = $ProcurementType_data->get('proctype_shortcode');
		$reference_no = $procurement_data->get('proc_request_no');
		$procurement_type = $procurement_data->getDisplayValue('proc_proctype');
		//dynamic items information for email body
		$unit_price_total = 0;
		$local_price_total = 0;
		$vat_price_total = 0;
		$gross_local = 0;
		$gross_local_total = 0;
		$total_net = 0;
		$total_usd = 0;
		$colspan = 12;
		if($ProcurementType_code=='PM' && $procurement_data->get('proc_purchase_type_pm')=='Own-Stock')
		{
			$colspan = 16;
		}
		$procurement_items = '<tr>
					<td colspan="'.$colspan.'"><h3 style="background-color:#f47824;color:white; margin-top: 0; margin-bottom: 0; font-weight: 500; vertical-align: center; font-size: 20px; padding: 3px;" align="left">Items Detail</h3></td>
				</tr>
				<tr>
					<td style="background-color:#bbb; font-weight:bold">Expense Type</td>
					<td style="background-color:#bbb; font-weight:bold">Description</td>
					<td style="background-color:#bbb; font-weight:bold">Qty</td>
					<td style="background-color:#bbb; font-weight:bold">Price/Unit</td>
					<td style="background-color:#bbb; font-weight:bold">Local Price</td>
					<td style="background-color:#bbb; font-weight:bold">VAT (%)</td>
					<td style="background-color:#bbb; font-weight:bold">Price VAT</td>
					<td style="background-color:#bbb; font-weight:bold">Gross (Local)</td>
					<td style="background-color:#bbb; font-weight:bold">Currency</td>
					<td style="background-color:#bbb; font-weight:bold">Gross (Local)</td>
					<td style="background-color:#bbb; font-weight:bold">Total (Local Currency)</td>
					<td style="background-color:#bbb; font-weight:bold">Total USD</td>';
					if($ProcurementType_code=='PM' && $procurement_data->get('proc_purchase_type_pm')=='Own-Stock')
					{
					$procurement_items .= '<td style="background-color:#bbb; font-weight:bold">Current Qty</td>
					<td style="background-color:#bbb; font-weight:bold">Last Purchase Price</td>
					<td style="background-color:#bbb; font-weight:bold">Last QTY Purchased</td>
					<td style="background-color:#bbb; font-weight:bold">Last 12 Month Consumption</td>';
					}
				$procurement_items .= '</tr>';
		$result=$adb->query("SELECT pi.* FROM `vtiger_procurement` as p INNER JOIN vtiger_procurementitemscf
		as pi ON p.procurementid= pi.procitem_procid INNER JOIN vtiger_crmentity as c ON c.crmid=pi.procurementitemsid
		where c.deleted=0 and p.procurementid=".$pending_approval['procurementid']);
		$Procureitem=array();
		$expence_type_data_code = '';
		if($adb->num_rows($result)>0){
			for($i=0; $i<$adb->num_rows($result); $i++) {
				//$Procureitem[$i]['procurementitemsid'] = $adb->query_result($result, $i, 'procurementitemsid');
				//$Procureitem[$i]['procitem_net_finalamount'] = $adb->query_result($result, $i, 'procitem_net_finalamount');
				$currency = $adb->query_result($result, $i, 'procitem_currency');
				$query = $adb->pquery("SELECT `currency_code` FROM `vtiger_currency_info`  WHERE `id`=$currency");
				$currency_code = $adb->query_result($query, 0, 'currency_code');
				//$totalusdamount+=$adb->query_result($result, $i, 'procitem_total_usd'); //usman
				$expence_type_id = $adb->query_result($result, $i, 'procitem_proctypeitem_id');
				$expence_type_data = Vtiger_Record_Model::getInstanceById($expence_type_id, 'ProcurementTypeItems');
				//$Procureitem[$i]['procitem_procid'] = $adb->query_result($result, $i, 'procitem_procid');
				//$Procureitem[$i]['procitem_proctype'] = $adb->query_result($result, $i, 'procitem_proctype');
				if($ProcurementType_code=='PM') //show code value for packaging material
				{
					$expence_type_data_code = '['.$expence_type_data->get('proctypeitem_code').'] : ';
				}
				$procurement_items .= '<tr>
										<td>'.$expence_type_data_code.$expence_type_data->get('name').'</td>
										<td>'.$adb->query_result($result, $i, 'procitem_description').'</td>
										<td>'.$adb->query_result($result, $i, 'procitem_qty').'</td>
										<td>'.number_format($adb->query_result($result, $i, 'procitem_unit_price') , 2 ,  "." , ",").'</td>
										<td>'.number_format($adb->query_result($result, $i, 'procitem_line_price') , 2 ,  "." , ",").'</td>
										<td>'.number_format($adb->query_result($result, $i, 'procitem_vat_unit') , 2 ,  "." , ",").'</td>
										<td>'.number_format($adb->query_result($result, $i, 'procitem_vat_amount') , 2 ,  "." , ",").'</td>
										<td>'.number_format($adb->query_result($result, $i, 'procitem_gross_amount') , 2 ,  "." , ",").'</td>
										<td>'.$currency_code.'</td>
										<td>'.number_format($adb->query_result($result, $i, 'procitem_gross_local') , 2 ,  "." , ",").'</td>
										<td>'.number_format($adb->query_result($result, $i, 'procitem_gross_finalamount') , 2 ,  "." , ",").'</td>
										<td>'.number_format($adb->query_result($result, $i, 'procitem_total_usd') , 2 ,  "." , ",").'</td>';
				if($ProcurementType_code=='PM' && $procurement_data->get('proc_purchase_type_pm')=='Own-Stock')	//show last purchasing detail for packaging material
				{					
				 $procurement_items .= '<td>'.$adb->query_result($result, $i, 'procitem_current_qty').'</td>
										<td>'.number_format($adb->query_result($result, $i, 'procitem_lastpurchase_price') , 2 ,  "." , ",").'</td>
										<td>'.$adb->query_result($result, $i, 'procitem_lastpurchase_qty').'</td>
										<td>'.number_format($adb->query_result($result, $i, 'procitem_avg_consumption') , 2 ,  "." , ",").'</td>';
				}
									
				$procurement_items .=	'</tr>';
				$unit_price_total = $unit_price_total+$adb->query_result($result, $i, 'procitem_unit_price');
				$local_price_total = $local_price_total+$adb->query_result($result, $i, 'procitem_line_price');
				$vat_price_total = $vat_price_total+$adb->query_result($result, $i, 'procitem_vat_amount');
				$gross_local = $gross_local+$adb->query_result($result, $i, 'procitem_gross_amount');
				$gross_local_total = $gross_local_total+$adb->query_result($result, $i, 'procitem_gross_local');
				$total_net = $total_net+$adb->query_result($result, $i, 'procitem_gross_finalamount');
				$total_usd = $total_usd+$adb->query_result($result, $i, 'procitem_total_usd');
		
		}
	}

		$total_usd = $procurement_data->get('proc_total_amount');
		
		$procurement_items .= '
			<tr>
						<td colspan="3" style="background-color:#666;color:#fff;text-align:center;font-weight:bold;">TOTAL AMOUNT</td>
						<td style="background-color:#eee; font-weight:bold">'.number_format($unit_price_total , 2 ,  "." , ",").'</td>
						<td style="background-color:#eee; font-weight:bold">'.number_format($local_price_total , 2 ,  "." , ",").'</td>
						<td style="background-color:#eee; font-weight:bold">-</td>
						<td style="background-color:#eee; font-weight:bold">'.number_format($vat_price_total , 2 ,  "." , ",").'</td>
						<td style="background-color:#eee; font-weight:bold">'.number_format($gross_local , 2 ,  "." , ",").'</td>
						<td style="background-color:#eee; font-weight:bold">-</td>
						<td style="background-color:#eee; font-weight:bold">'.number_format($gross_local_total , 2 ,  "." , ",").'</td>
						<td style="background-color:#eee; font-weight:bold">'.number_format($total_net , 2 ,  "." , ",").'</td>
						<td style="background-color:#eee; font-weight:bold">'.number_format($total_usd , 2 ,  "." , ",").'</td>';
		if($ProcurementType_code=='PM' && $procurement_data->get('proc_purchase_type_pm')=='Own-Stock')
		{		
		$procurement_items .= '<td style="background-color:#eee; font-weight:bold">-</td>
						<td style="background-color:#eee; font-weight:bold">-</td>
						<td style="background-color:#eee; font-weight:bold">-</td>
						<td style="background-color:#eee; font-weight:bold">-</td>';
		}
		$procurement_items .= '</tr>
		';
//end items information
		
		
		$office = $procurement_data->get('proc_location');
		$sql_location = $adb->pquery("SELECT cf_1559 FROM `vtiger_locationcf` WHERE `locationid` = '$office'");
		$location_name = $adb->fetch_array($sql_location);
		$location_status = $location_name['cf_1559'];
		$approval_information = '<tr>
						<td colspan="5"><h3 style="background-color:#f47824;color:white; margin-top: 0; margin-bottom: 0; font-weight: 500; vertical-align: center; font-size: 20px; padding: 3px;" align="left">Approval Information</h3></td>
					</tr>
					<tr>
						<td style="background-color:#bbb; font-weight:bold">NO</td>
						<td style="background-color:#bbb; font-weight:bold">Authority Name</td>
						<td style="background-color:#bbb; font-weight:bold">Designation</td>
						<td style="background-color:#bbb; font-weight:bold">Approval Status</td>
						<td style="background-color:#bbb; font-weight:bold">Approval Date</td>
					</tr>';
				
		$approval_array_id = 0;
		$who_create_this_request_id = $procurement_data->get('assigned_user_id');
		$creator_result = $adb->pquery("SELECT * FROM `vtiger_users` where id = $who_create_this_request_id");
		$creator_email = $adb->query_result($creator_result, 0, 'email1');
		$creater_name = $adb->query_result($creator_result, 0, 'first_name').' '.$adb->query_result($creator_result, 0, 'last_name');
		
		//$message_status = $creater_name.' has created a new Request';
		$sql_userprofile = $adb->pquery("SELECT `userlistid`, `cf_3355`, `cf_3385`, `cf_3421`
											FROM `vtiger_userlistcf`
											WHERE `cf_3355` = '$creator_email'");
		$userprofile = $adb->fetch_array($sql_userprofile);
		$gm_id = $userprofile['cf_3385'];
		$sql_gm_email = $adb->pquery("SELECT uf.cf_3355 as email, u.name as name FROM `vtiger_userlistcf` as uf inner join `vtiger_userlist` as u on uf.userlistid = u.userlistid  WHERE uf.`userlistid` = '$gm_id'");
		$gm = $adb->fetch_array($sql_gm_email);
		$gm_email = $gm['email'];
		
		/*Check if creator exists in approval list*/
		$approval_exists = 0;
		$if_approval_exists = $adb->pquery("SELECT procapproval_person FROM `vtiger_procurementapprovalcf` 
		inner join  `vtiger_crmentity` on vtiger_crmentity.`crmid` = `vtiger_procurementapprovalcf`.procurementapprovalid 
		where procapproval_person = $who_create_this_request_id AND vtiger_crmentity.deleted=0 limit 1");
		$approval_exists = $adb->num_rows($if_approval_exists);
		/*Ends creator exists check*/
		
		if($gm_email=='s.khan@globalinklogistics.com' && $approval_exists==0) //add approval entry if Siddique Khan is the GM of request creator
		{
			$procurement_gm_approval_result = $adb->pquery("SELECT * FROM `vtiger_send_approval` where who_approve_id = '$who_create_this_request_id' and procurement_id='".$pending_approval['procurementid']."'");
			if($adb->num_rows($procurement_gm_approval_result)>0){
				$row = $adb->query_result_rowdata($procurement_gm_approval_result, 0);
			$approval_array_id++; //auto increment to use in next loop also
			$approval_information .= '<tr>
											<td>'.$approval_array_id.'</td>
											<td>'.$creater_name.'</td>
											<td>GM</td>
											<td>'.$row['approval_status'].'</td>
											<td>'.$row['date_time_of_approval'].'</td>
										</tr>';
			}
		}
		elseif($location_status != 'ALA' && $approval_exists==0){
			
			//now get gm approval status from vtiger_send_approval
			
			$get_gm_userid = $adb->pquery("SELECT * FROM `vtiger_users` where email1 = '".$gm_email."' limit 1"); //get gm userid from users table
			$gm_userid = $adb->query_result($get_gm_userid, 0, 'id');
			$gm_name = $adb->query_result($get_gm_userid, 0, 'first_name')." ".$adb->query_result($get_gm_userid, 0, 'last_name');;
			$procurement_gm_approval_result = $adb->pquery("SELECT * FROM `vtiger_send_approval` where who_approve_id = '$gm_userid' and procurement_id='".$pending_approval['procurementid']."'");
			
			if($adb->num_rows($procurement_gm_approval_result)>0){
				$row = $adb->query_result_rowdata($procurement_gm_approval_result, 0);
				//echo "<pre>"; print_r($row); echo "</pre>";
				$approval_array_id++; //auto increment to use in next loop also
				$approval_information .= '<tr>
												<td>'.$approval_array_id.'</td>
												<td>'.$gm_name.'</td>
												<td>GM</td>
												<td>'.$row['approval_status'].'</td>
												<td>'.$row['date_time_of_approval'].'</td>
											</tr>';
			}
		}
		
			//below make approval authorities list dynamically
			//get all approval authorities from vtiger_procurementapprovalcf for requested item, left join with vtiger_send_approval & get approval status			
			$procurementauthorities_result = $adb->pquery("SELECT * FROM `vtiger_procurementapprovalcf` 
			inner join  `vtiger_crmentity` on vtiger_crmentity.`crmid` = `vtiger_procurementapprovalcf`.procurementapprovalid
			inner join vtiger_procurementapproval on vtiger_procurementapprovalcf.procurementapprovalid=vtiger_procurementapproval.procurementapprovalid 
			left join vtiger_send_approval on vtiger_procurementapprovalcf.procapproval_person = vtiger_send_approval.who_approve_id and vtiger_send_approval.procurement_id='".$pending_approval['procurementid']."' 
			where procapproval_proctype = ".$procurement_data->get('proc_proctype')." AND vtiger_crmentity.deleted=0 order by procapproval_sequence  ASC");
			
			if($adb->num_rows($procurementauthorities_result)>0){
				//echo "<pre>"; print_r($procurementauthorities_result); echo "</pre>";
				for($j=0; $j<$adb->num_rows($procurementauthorities_result); $j++) { //loop to build array for approval details  
					//echo "user_id: ".$adb->query_result($procurementauthorities_result, $j, 'procapproval_person')." sequence: ".$adb->query_result($procurementauthorities_result, $j, 'procapproval_sequence')." usd_limit: ".$adb->query_result($procurementauthorities_result, $j, 'procapproval_usd_limit')." <br>";
					$row = $adb->query_result_rowdata($procurementauthorities_result, $j);
					$approval_user_data = Vtiger_Record_Model::getInstanceById($row['procapproval_person'], "Users");
					$approval_user_name  = $approval_user_data->get('first_name')." ".$approval_user_data->get('last_name');
					$approval_array_id++;
					$approval_information .= '<tr>
													<td>'.$approval_array_id.'</td>
													<td>'.$approval_user_name.'</td>
													<td>'.$row['name'].'</td>
													<td>'.$row['approval_status'].'</td>
													<td>'.$row['date_time_of_approval'].'</td>
												</tr>';
					
					//echo "<pre>"; print_r($row); echo "</pre>";
				}
			}
			//approval information ends 

			
			$link = $site_URL;
			$link .= 'index.php?module=Procurement&view=Detail&record='.$pending_approval['procurementid'];

			//$from = 'From: '.$who_updated_name.' <'.$who_updated_email.'>';
			$from = $creator_email;
			$approval_notice='This is an auto reminder email of the procurement request that is pending for approval from Your end. Please check details below';
			$body = '';
			$body .= "<html>
						<head>
						<style>
							#calendar_notification tr td{ margin:3px;}
							.edited {font-weight: bold; color:green;}
						</style>
					 </head>
					<body> 
					<table border=\"0\" cellspacing=\"0\" cellpadding=\"3\" width=\"100%\">
						<tr><td style=\"text-align:left;\"><img src=\"https://gems.globalink.net/include/logo_doc.jpg\" width=\"200\"></td></tr>
					</table>
					<hr><p>&nbsp;</p>
					<p>Dear ".$approval_user->get('first_name')." ".$approval_user->get('last_name').",</p>
					<p>".$approval_notice."</p>
					";
			//$tolist = implode(" ",$to);
			//$cclist = implode(" ",$cc);
			$body .= '<h3 style="background-color:#f47824;color:white; margin-top: 0; margin-bottom: 0; font-weight: 500; vertical-align: center; font-size: 20px; padding: 3px;" align="left">Procurement Information</h3>';
			$mode_content1 = '';
			$mode_content2 = '';
			if($ProcurementType_code=='FL')
			{
				$mode_content1 = '<td style="background-color:#bbb; font-weight:bold">Type of Purchase</td>';
				$mode_content2 = '<td>'.$procurement_data->get('proc_purchase_type_fleet').'</td>';
				if($procurement_data->get('proc_purchase_type_fleet')=='Direct To Truck' || $procurement_data->get('proc_purchase_type_fleet')!='Inventory')
				{
					$mode_content1 .= '<td style="background-color:#bbb; font-weight:bold">Vehicle Number</td><td style="background-color:#bbb; font-weight:bold">Vehicle Milage</td>';
					$mode_content2 .= '<td>'.$procurement_data->getDisplayValue('proc_vehicle_no').'</td><td>'.$procurement_data->get('proc_vehicle_mileage').'</td>';
				}
			}
			if($ProcurementType_code=='PM')
			{
				$mode_content1 = '<td style="background-color:#bbb; font-weight:bold">Type of Purchase</td>';
				$mode_content2 = '<td>'.$procurement_data->get('proc_purchase_type_pm').'</td>';
				if($procurement_data->get('proc_purchase_type_pm')=='Direct Pack-Out' || $procurement_data->get('proc_purchase_type_pm')!='Own-Stock')
				{
					$mode_content1 .= '<td style="background-color:#bbb; font-weight:bold">Job Number</td>';
					$mode_content2 .= '<td>'.$procurement_data->get('proc_job_no').'</td>';
				}
			}
			$body .= '
				<table border="1" cellspacing="0" cellpadding="3" width="100%">
					<tbody>
						<tr>
							<td style="background-color:#bbb; font-weight:bold">Request Number</td>
							<td style="background-color:#bbb; font-weight:bold">Procurement Type</td>
							<td style="background-color:#bbb; font-weight:bold">Creator Name</td>
							<td style="background-color:#bbb; font-weight:bold">Location</td>
							<td style="background-color:#bbb; font-weight:bold">DPRT</td>
							'.$mode_content1.'
							<td style="background-color:#bbb; font-weight:bold">Creation Date</td>
							<td style="background-color:#bbb; font-weight:bold">Status</td>
						</tr>
						<tr>
							<td>'.$reference_no.'</td>
							<td>'.$procurement_type.'</td>
							<td>'.$creater_name.'</td>
							<td>'.$procurement_data->getDisplayValue('proc_location').'</td>
							<td>'.$procurement_data->getDisplayValue('proc_department').'</td>
							'.$mode_content2.'
							<td>'.$procurement_data->get('createdtime').'</td>
							<td>'.$procurement_data->get('proc_order_status').'</td>
						</tr>
					</tbody>
				</table>
			';
			$body .= '<br><hr><br>';
			$body .= '
				<table border="1" cellspacing="0" cellpadding="3" width="100%">
				<tbody>
								
				'.$procurement_items;
			
			$body .= '
				</tbody></table>		
				';
			$body .= '<br><hr><br>';
			$body .= '
				<table border="1" cellspacing="0" cellpadding="3" width="100%">
				<tbody>			
				'.$approval_information;
			
			$body .= '
				</tbody></table>		
				';
			
			$body .= "<p>Please see details on this link: <a href='$link'> Click To Follow Link </a></p>";
			$body .= "<p>&nbsp;</p><p>&nbsp;</p>";
			$body .= "</body> </html> ";

			// Set content-type when sending HTML email
			$headers = "MIME-Version: 1.0" . "\n";
			$headers .= "Content-type:text/html;charset=UTF-8" . "\n";
			//$headers .= "Cc: s.bhatti@globalinklogistics.com";
			$to = $approval_user->get('email1');//$user_email;
			$subject = 'Procurement '.$reference_no.' : Reminder Notification';
			//echo $approval_user->get('email1').$body;exit;
			$mailer->ConfigSenderInfo($from,'Globalink : Procurement Notification');
			$mailer->Subject = $subject;
			$mailer->Body = $body;
			
			//mail($to,$subject,$body,$headers); //$to
			$mailer->AddAddress('m.arif@globalinklogistics.com'); //$mailer->AddAddress($to); 
			//$mailer->AddAddress('m.arif@globalinklogistics.com'); $mailer->AddAddress('s.mehtab@globalinklogistics.com'); 
			//$mailer->addcc($creator_email); $mailer->addcc($currentUser_email); 
			//$mailer->addcc('m.arif@globalinklogistics.com'); //$mailer->addcc('s.mehtab@globalinklogistics.com'); 
			if($mailer->Send(true))
			{
				$adb->pquery("update vtiger_send_approval set next_notification = '".$next_reminder_notification."' where vtiger_send_approval.id='".$pending_approval['id']."'");
			}				
		
	}
}
 
?>