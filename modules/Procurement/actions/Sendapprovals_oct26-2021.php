<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Procurement_Sendapprovals_Action extends Vtiger_Action_Controller {

	public function requiresPermission(\Vtiger_Request $request) {

		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView');
		$permissions[] = array('module_parameter' => 'module', 'action' => 'EditView', 'record_parameter' => 'record');
		return $permissions;
	}

	public function checkPermission(Vtiger_Request $request) {
		// parent::checkPermission($request);
		// $recordIds = $this->getRecordIds($request);
		// foreach ($recordIds as $key => $recordId) {
		// 	$moduleName = getSalesEntityType($recordId);
		// 	$permissionStatus  = Users_Privileges_Model::isPermitted($moduleName,  'EditView', $recordId);
		// 	if($permissionStatus){
		// 		$this->transferRecordIds[] = $recordId;
		// 	}
		// 	if(empty($this->transferRecordIds)){
		// 		throw new AppException(vtranslate('LBL_RECORD_PERMISSION_DENIED'));
		// 	}
		// }
		return true;
	}

	public function process(Vtiger_Request $request) {
		global $adb;
		global $site_URL;
		$users_result = '';
		$user_email = '';
		$date = '';
		date_default_timezone_set('Asia/Almaty');
		$page = $request->get('page');
		$moduleName = $request->get('module');
		$recordID = $request->get('recordID');
		$user_id = $request->get('UserID');
		$ProcurementTypeID = $request->get('ProcurementTypeID');
		$procurement_data = Vtiger_Record_Model::getInstanceById($recordID, $moduleName);
		$ProcurementType_data = Vtiger_Record_Model::getInstanceById($procurement_data->get('proc_proctype'), 'ProcurementTypes');
		$ProcurementType_code = $ProcurementType_data->get('proctype_shortcode');
		//load email library
		require_once 'vtlib/Vtiger/Mailer.php';
		$mailer = new Vtiger_Mailer();
		$mailer->IsHTML(true);
		//end email library
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
	  where c.deleted=0 and p.procurementid=".$request->get('recordID'));
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
$procurement_type = $procurement_data->getDisplayValue('proc_proctype');

if($page == 'firstapproval'){
$currentUser = Users_Record_Model::getCurrentUserModel();
$currentUser_email = $currentUser->get('email1');
$currentUser_name = $currentUser->get('first_name').' '.$currentUser->get('last_name');
$currentUser_title = htmlspecialchars_decode($currentUser->get('title'));
$sql_userprofile = $adb->pquery("SELECT `userlistid`, `cf_3355`, `cf_3385`, `cf_3421`
								FROM `vtiger_userlistcf`
								WHERE `cf_3355` = '$currentUser_email'");
$userprofile = $adb->fetch_array($sql_userprofile);
$gm_id = $userprofile['cf_3385'];
$sql_gm_email = $adb->pquery("SELECT uf.cf_3355 as email, u.name as name FROM `vtiger_userlistcf` as uf inner join `vtiger_userlist` as u on uf.userlistid = u.userlistid  WHERE uf.`userlistid` = '$gm_id'");
$gm = $adb->fetch_array($sql_gm_email);
$gm_email = $gm['email'];
$gm_name = $gm['name'];

$currentUser_name = $currentUser->get('first_name').' '.$currentUser->get('last_name');
$message_status = $currentUser_name.' has created a new Procurement Request';
	// echo "INSERT INTO `vtiger_send_approval`(`id`, `procurement_id`, `approval_status`, `creator_id`) VALUES (0, $recordID, 'Pending', $user_id)";
// echo "SELECT * FROM `vtiger_procurementapprovalcf` where procapproval_proctype = $ProcurementTypeID order by procurementapprovalid  ASC limit 1";exit;
$procurementapproval_result = $adb->pquery("SELECT * FROM `vtiger_procurementapprovalcf` inner join  `vtiger_crmentity` on vtiger_crmentity.`crmid` = `vtiger_procurementapprovalcf`.procurementapprovalid where procapproval_proctype = $ProcurementTypeID AND vtiger_crmentity.deleted=0 order by procapproval_sequence  ASC limit 1");
$who_approve_id = $adb->query_result($procurementapproval_result, 0, 'procapproval_person'); //first approval person in vtiger_procurementapprovalcf

$reference_no = $procurement_data->get('proc_request_no');

$office = $procurement_data->get('proc_location');
$sql_location = $adb->pquery("SELECT cf_1559 FROM `vtiger_locationcf` WHERE `locationid` = '$office'");
$location_name = $adb->fetch_array($sql_location);
$location_status = $location_name['cf_1559'];
$approval_information = '';
if($gm_email=='s.khan@globalinklogistics.com') //add approval entry as approved if Siddique Khan is the GM of current user(request creator)
{	
	$date = date('Y-m-d h:i A');
$adb->pquery("INSERT INTO `vtiger_send_approval`(`id`, `procurement_id`, date_time_of_approval, `who_approve_id` , `approval_status`, `creator_id`,`creator_date`,`gm_email`,`gm_name`) VALUES (0, $recordID, '$date', $user_id, 'Approved', $user_id, '$date','$currentUser_email','$currentUser_name')");
$approval_information .= '<tr>
				<td>1</td>
				<td>'.$currentUser_name.'</td>
				<td>GM</td>
				<td>Approved</td>
				<td>'.$date.'</td>
			</tr>';
	$approval_array_id = 1;
	$users_result = $adb->pquery("SELECT * FROM `vtiger_users` where id = $who_approve_id");
	$user_email = $adb->query_result($users_result, 0, 'email1');
	$receiver_name = $adb->query_result($users_result, 0, 'first_name').' '.$adb->query_result($users_result, 0, 'last_name');
	$receiver_email = $adb->query_result($users_result, 0, 'email1');
	$date = date('Y-m-d h:i A');
	$adb->pquery("INSERT INTO `vtiger_send_approval`(`id`, `procurement_id`, `who_approve_id` , `approval_status`, `creator_id`,`creator_date`) VALUES (0, $recordID, $who_approve_id, 'Pending', $user_id, '$date')");
	$adb->pquery("UPDATE `vtiger_procurementcf` SET `proc_order_status`='Under Review' WHERE `procurementid` = $recordID"); //set partial status of procurement
}
elseif($location_status != 'ALA'){
	$approval_array_id = 1;
	$users_result = $adb->pquery("SELECT * FROM `vtiger_users` where email1 = '".$gm_email."'");
	$id = $adb->query_result($users_result, 0, 'id');
	$receiver_name = $adb->query_result($users_result, 0, 'first_name').' '.$adb->query_result($users_result, 0, 'last_name');
	$receiver_email = $adb->query_result($users_result, 0, 'email1');
	$date = date('Y-m-d h:i A');
$adb->pquery("INSERT INTO `vtiger_send_approval`(`id`, `procurement_id`, `who_approve_id` , `approval_status`, `creator_id`,`creator_date`,`gm_email`,`gm_name`) VALUES (0, $recordID, $id, 'Pending', $user_id, '$date','$gm_email','$gm_name')");
$adb->pquery("UPDATE `vtiger_procurementcf` SET `proc_order_status`='Not Approved' WHERE `procurementid` = $recordID"); //set initial status of procurement
$approval_information .= '<tr>
				<td>1</td>
				<td>'.$gm_name.'</td>
				<td>GM</td>
				<td>Pending</td>
				<td>--</td>
			</tr>';
}else{
	$approval_array_id = 0;
	$users_result = $adb->pquery("SELECT * FROM `vtiger_users` where id = $who_approve_id");
	$user_email = $adb->query_result($users_result, 0, 'email1');
	$receiver_name = $adb->query_result($users_result, 0, 'first_name').' '.$adb->query_result($users_result, 0, 'last_name');
	$receiver_email = $adb->query_result($users_result, 0, 'email1');
	$date = date('Y-m-d h:i A');
	$adb->pquery("INSERT INTO `vtiger_send_approval`(`id`, `procurement_id`, `who_approve_id` , `approval_status`, `creator_id`,`creator_date`) VALUES (0, $recordID, $who_approve_id, 'Pending', $user_id, '$date')");
	$adb->pquery("UPDATE `vtiger_procurementcf` SET `proc_order_status`='Not Approved' WHERE `procurementid` = $recordID"); //set initial status of procurement
}

//below generate dynamic list of all approval for email body
$procurementauthorities_result = $adb->pquery("SELECT * FROM `vtiger_procurementapprovalcf` 
	inner join  `vtiger_crmentity` on vtiger_crmentity.`crmid` = `vtiger_procurementapprovalcf`.procurementapprovalid
	inner join vtiger_procurementapproval on vtiger_procurementapprovalcf.procurementapprovalid=vtiger_procurementapproval.procurementapprovalid  
	left join vtiger_send_approval on vtiger_procurementapprovalcf.procapproval_person = vtiger_send_approval.who_approve_id and vtiger_send_approval.procurement_id='$recordId' 
	where procapproval_proctype = $ProcurementTypeID AND vtiger_crmentity.deleted=0 order by procapproval_sequence  ASC");
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
//end dynamic list
	$body_line = 'A new procurement request has been created. Please check details below';
	$order_status = 'Not Approved';
	if($gm_email=='s.khan@globalinklogistics.com')
	{
		$body_line = "A procurement request has been approved by $currentUser_name and has been assigned to you for further approval. Please check details below";
		$order_status = 'Under Review';
	}
	$link = $site_URL;
	$link .= 'index.php?module=Procurement&view=Detail&record='.$recordID;

	//$from = 'From: '.$who_updated_name.' <'.$who_updated_email.'>';
	$from = $currentUser_email;

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
				<tr><td style=\"text-align:left;\"><img src=\"http://tiger.globalink.net/gems/include/logo_doc.jpg\" width=\"200\"></td></tr>
			</table>
			<hr><p>&nbsp;</p>
			<p>Dear $receiver_name,</p>
			<p>".$body_line."</p>
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
					<td>'.$currentUser_name.'</td>
					<td>'.$procurement_data->getDisplayValue('proc_location').'</td>
					<td>'.$procurement_data->getDisplayValue('proc_department').'</td>
					'.$mode_content2.'
					<td>'.$procurement_data->get('createdtime').'</td>
					<td>'.$order_status.'</td>
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
			<tr>
				<td colspan="5"><h3 style="background-color:#f47824;color:white; margin-top: 0; margin-bottom: 0; font-weight: 500; vertical-align: center; font-size: 20px; padding: 3px;" align="left">Approval Information</h3></td>
			</tr>
			<tr>
				<td style="background-color:#bbb; font-weight:bold">NO</td>
				<td style="background-color:#bbb; font-weight:bold">Authority Name</td>
				<td style="background-color:#bbb; font-weight:bold">Designation</td>
				<td style="background-color:#bbb; font-weight:bold">Approval Status</td>
				<td style="background-color:#bbb; font-weight:bold">Approval Date</td>
			</tr>
			
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
	//$headers .= "New Request from Procurement Department\n";

	$to = $receiver_email;
	$subject = $reference_no.'-'.'Procurement';
	
	$mailer->ConfigSenderInfo($from, $currentUser_name);
	$mailer->Subject = $subject;
	$mailer->Body = $body;
	//$mailer->AddAddress($to); 
	$mailer->AddAddress('m.arif@globalinklogistics.com'); $mailer->AddAddress('s.mehtab@globalinklogistics.com'); 
	//$mailer->addcc($currentUser_email); $mailer->addcc('m.arif@globalinklogistics.com'); $mailer->addcc('s.mehtab@globalinklogistics.com'); 
	$mailer->Send(true);
	
	//mail($to,$subject,$body,$headers);
}//// main if close///


if($page == 'nextapproval'){/////
	$Branch_Manager = '';
	$Branch_Manager_date_time = '';
	$supervisor_name = '';
	$supervisor_date_time = '';
	$Procurement_name = '';
	$Procurement_date_time = '';
	$CFO_name = '';
	$CFO_date_time = '';
	$CEO_name = '';
	$CEO_date_time = '';

	$currentUser = Users_Record_Model::getCurrentUserModel();
	$currentUser_email = $currentUser->get('email1');
	$currentUser_name = $currentUser->get('first_name').' '.$currentUser->get('last_name');
	$currentUser_title = htmlspecialchars_decode($currentUser->get('title'));
	$currentUserId = $currentUser->get('id');
	$sendapproval_result = $adb->pquery("SELECT * FROM `vtiger_send_approval` where procurement_id = $recordID and status=0 order by id  ASC");
	$who_create_this_request_id = $adb->query_result($sendapproval_result, 0, 'creator_id'); //get information about the creator of the procurement request
	$creator_date_time = $adb->query_result($sendapproval_result, 0, 'creator_date');
	//$adb->pquery("UPDATE `vtiger_send_approval` SET `approval_status`='Approved',date_time_of_approval='".date('Y-m-d h:i A')."' WHERE `procurement_id` = $recordID and who_approve_id=$currentUserId");//set current status as approved
	$query = "SELECT * FROM `vtiger_procurementapprovalcf` 
	inner join  `vtiger_crmentity` on vtiger_crmentity.`crmid` = `vtiger_procurementapprovalcf`.procurementapprovalid 
	where procapproval_proctype = $ProcurementTypeID"; //get all approval authorities related to requested item
		if($adb->num_rows($sendapproval_result)>0){
			for($j=0; $j<$adb->num_rows($sendapproval_result); $j++) { //loop to build query parameters dynamically
				$who_approve_ids = $adb->query_result($sendapproval_result, $j, 'who_approve_id');
				$approve_status = $adb->query_result($sendapproval_result, $j, 'approval_status');
				if($approve_status=='Approved'){ //there is at least one approved status from first approval
					//$adb->pquery("UPDATE `vtiger_send_approval` SET `approval_status`='Approved' WHERE `procurement_id` = $recordID");
					$query .= " AND procapproval_person !=".$who_approve_ids; //dynamic exclude approval authorities if already have approved
				}
			}
		}
		$query .= " AND procapproval_person !=".$currentUserId." AND vtiger_crmentity.deleted=0 "; //Also exclude current approval person id as it has already been added in next approval list
		//echo "UPDATE `vtiger_send_approval` SET `approval_status`='Approved' WHERE `procurementid` = $recordID";
$query .= " order by procapproval_sequence  ASC limit 1"; // order by approval sequence, this will give the next approval person

$proc_proctype = $procurement_data->get('proc_proctype'); //get id of requested item
$procurement_approval_result = $adb->pquery("SELECT * FROM `vtiger_procurementapprovalcf` where procapproval_proctype = $proc_proctype and procapproval_person = $currentUserId  order by procurementapprovalid  DESC limit 1");
$sequence = $adb->query_result($procurement_approval_result, 0, 'procapproval_sequence'); //current approval sequence
$procurement_max_seq = $adb->pquery("SELECT max(procapproval_sequence) as max_sequence FROM `vtiger_procurementapprovalcf`
inner join  `vtiger_crmentity` on 
vtiger_crmentity.`crmid` = `vtiger_procurementapprovalcf`.procurementapprovalid where procapproval_proctype = $proc_proctype AND vtiger_crmentity.deleted=0 ");
$max_sequence = $adb->query_result($procurement_max_seq, 0, 'max_sequence'); //max value of approval sequence
$procurementapproval_result = $adb->pquery($query);

if($adb->num_rows($procurementapproval_result)>0 || $sequence==$max_sequence){ //if there are still approval authorities, or last approval

$get_user_result = $adb->pquery("SELECT * FROM `vtiger_send_approval` where procurement_id = $recordID and status=0 order by id  DESC limit 1");
$find_user_in_approval = $adb->query_result($get_user_result, 0, 'who_approve_id');
$find_user_in_approval = $currentUserId; //we need userid which is currently setting appproval status

//$approval_usd_amount_limit = $adb->query_result($procurement_approval_result, 0, 'procapproval_usd_limit');
$reference_no = $procurement_data->get('proc_request_no');
$assigned_user_id = $procurement_data->get('assigned_user_id'); //creator of the procurement request
if($sequence<$max_sequence)
{
$who_approve_id = $adb->query_result($procurementapproval_result, 0, 'procapproval_person'); //next id of approval person
$users_result = $adb->pquery("SELECT * FROM `vtiger_users` where id = $who_approve_id"); //get data of next approval person
$user_email = $adb->query_result($users_result, 0, 'email1');
$user_title = htmlspecialchars_decode($adb->query_result($users_result, 0, 'title'));
$receiver_name = $adb->query_result($users_result, 0, 'first_name').' '.$adb->query_result($users_result, 0, 'last_name');
$approval_usd_amount_limit = $adb->query_result($procurementapproval_result, 0, 'procapproval_usd_limit'); //next person approval usd limit 
}
$usdamount = $request->get('usdamount');
$usdamount = $procurement_data->get('proc_total_amount'); //get total usd amount from procurement record

$creator_result = $adb->pquery("SELECT * FROM `vtiger_users` where id = $who_create_this_request_id");
$creator_email = $adb->query_result($creator_result, 0, 'email1');
$creater_name = $adb->query_result($creator_result, 0, 'first_name').' '.$adb->query_result($creator_result, 0, 'last_name');
$message_status = $creater_name.' has created a new Request';
$date = date('Y-m-d h:i A');


//set current approval status below
$adb->pquery("UPDATE `vtiger_send_approval` SET `approval_status`='Approved', `date_time_of_approval`='$date' WHERE `procurement_id` = $recordID and `who_approve_id`=$currentUserId");
$adb->pquery("UPDATE `vtiger_procurementcf` SET `proc_order_status`='Under Review' WHERE `procurementid` = $recordID");//set under review for partial approvals

if($usdamount>$approval_usd_amount_limit && $approval_usd_amount_limit!=''){ //set next approval as amount is greator than defined limit in vtiger_procurementapprovalcf

$adb->pquery("INSERT INTO `vtiger_send_approval`(`id`, `procurement_id`, `who_approve_id` , `approval_status`, `creator_id`) VALUES (0, $recordID, $who_approve_id, 'Pending', $user_id)");
}
if($approval_usd_amount_limit=='' && $sequence<$max_sequence){ //add next approval in case there is no USD limit defined in vtiger_procurementapprovalcf

$adb->pquery("INSERT INTO `vtiger_send_approval`(`id`, `procurement_id`, `who_approve_id` , `approval_status`, `creator_id`) VALUES (0, $recordID, $who_approve_id, 'Pending', $user_id)");
}
$final_approval = false;
// echo "UPDATE `vtiger_send_approval` SET `date_time_of_approval`='$date' WHERE `procurement_id` = $recordID and `who_approve_id`=$user_id";exit;

// echo $usdamount;
// echo $user_title;
// exit;

if($usdamount<$approval_usd_amount_limit && $approval_usd_amount_limit!=''){ //if amount is less than limit then make procurement status approved

	$adb->pquery("UPDATE `vtiger_procurementcf` SET `proc_order_status`='Approved' WHERE `procurementid` = $recordID");
	$receiver_name = $creater_name;
	$final_approval = true;
}
if($sequence == $max_sequence){ // if current approval sequence is last approval sequence

	$adb->pquery("UPDATE `vtiger_procurementcf` SET `proc_order_status`='Approved' WHERE `procurementid` = $recordID");
	$receiver_name = $creater_name;
	$final_approval = true;
}
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
$sql_userprofile = $adb->pquery("SELECT `userlistid`, `cf_3355`, `cf_3385`, `cf_3421`
									FROM `vtiger_userlistcf`
									WHERE `cf_3355` = '$creator_email'");
$userprofile = $adb->fetch_array($sql_userprofile);
$gm_id = $userprofile['cf_3385'];
$sql_gm_email = $adb->pquery("SELECT uf.cf_3355 as email, u.name as name FROM `vtiger_userlistcf` as uf inner join `vtiger_userlist` as u on uf.userlistid = u.userlistid  WHERE uf.`userlistid` = '$gm_id'");
$gm = $adb->fetch_array($sql_gm_email);
$gm_email = $gm['email'];
if($gm_email=='s.khan@globalinklogistics.com') //add approval entry if Siddique Khan is the GM of request creator
{
	$procurement_gm_approval_result = $adb->pquery("SELECT * FROM `vtiger_send_approval` where who_approve_id = '$who_create_this_request_id' and procurement_id='".$request->get('recordID')."'");
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
elseif($location_status != 'ALA'){
	
	//now get gm approval status from vtiger_send_approval
	$get_gm_userid = $adb->pquery("SELECT * FROM `vtiger_users` where email1 = '".$gm_email."' limit 1"); //get gm userid from users table
	$gm_userid = $adb->query_result($get_gm_userid, 0, 'id');
	$gm_name = $adb->query_result($get_gm_userid, 0, 'first_name')." ".$adb->query_result($get_gm_userid, 0, 'last_name');;
	$procurement_gm_approval_result = $adb->pquery("SELECT * FROM `vtiger_send_approval` where who_approve_id = '$gm_userid' and procurement_id='".$request->get('recordID')."'");
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
	left join vtiger_send_approval on vtiger_procurementapprovalcf.procapproval_person = vtiger_send_approval.who_approve_id and vtiger_send_approval.procurement_id='".$request->get('recordID')."' 
	where procapproval_proctype = $ProcurementTypeID AND vtiger_crmentity.deleted=0 order by procapproval_sequence  ASC");
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

	$procurement_detail = Vtiger_Record_Model::getInstanceById($request->get('recordID'), $request->get('module'));
	$link = $site_URL;
	$link .= 'index.php?module=Procurement&view=Detail&record='.$recordID;

	//$from = 'From: '.$who_updated_name.' <'.$who_updated_email.'>';
	$from = $currentUser_email;
	if(!$final_approval){$approval_notice='A procurement request has been approved by '.$currentUser_name.' and has been assigned to you for further approval. Please check details below';}
	else{$approval_notice='Your procurement request has been approved. Please check details below';}
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
				<tr><td style=\"text-align:left;\"><img src=\"http://tiger.globalink.net/gems/include/logo_doc.jpg\" width=\"200\"></td></tr>
			</table>
			<hr><p>&nbsp;</p>
			<p>Dear $receiver_name,</p>
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
					<td>'.$procurement_detail->get('proc_order_status').'</td>
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
	$to = $user_email;//$user_email;
	$subject = $reference_no.'-'.'Procurement';
	
	$mailer->ConfigSenderInfo($from,$currentUser_name);
	$mailer->Subject = $subject;
	$mailer->Body = $body;
	
	if($usdamount>$approval_usd_amount_limit && $approval_usd_amount_limit!=''){
	//mail($to,$subject,$body,$headers); //$to
	//$mailer->AddAddress($to); 
	$mailer->AddAddress('m.arif@globalinklogistics.com'); $mailer->AddAddress('s.mehtab@globalinklogistics.com'); 
	//$mailer->addcc($creator_email); $mailer->addcc('m.arif@globalinklogistics.com'); $mailer->addcc('s.mehtab@globalinklogistics.com'); 
	$mailer->Send(true); 
}
if($approval_usd_amount_limit=='' && $sequence < $max_sequence){
		//mail($to,$subject,$body,$headers);
		//$mailer->AddAddress($to); 
		$mailer->AddAddress('m.arif@globalinklogistics.com'); $mailer->AddAddress('s.mehtab@globalinklogistics.com'); 
		//$mailer->addcc($creator_email); $mailer->addcc('m.arif@globalinklogistics.com'); $mailer->addcc('s.mehtab@globalinklogistics.com'); 
		$mailer->Send(true);
}
if($sequence == $max_sequence){
	$sender_user_result = $adb->pquery("SELECT * FROM `vtiger_users` where id = $assigned_user_id");
	$sender_user_email = $adb->query_result($sender_user_result, 0, 'email1');
	//$subject = $subject." CC to $cc";
	//mail('m.arif@globalinklogistics.com,s.mehtab@globalinklogistics.com',$subject,$body,$headers); //$cc
	//$mailer->AddAddress($creator_email); 
	$mailer->AddAddress('m.arif@globalinklogistics.com'); $mailer->AddAddress('s.mehtab@globalinklogistics.com'); 
	//$mailer->addcc('m.arif@globalinklogistics.com'); $mailer->addcc('s.mehtab@globalinklogistics.com'); 
	$mailer->Send(true);
}
if($usdamount<$approval_usd_amount_limit && $approval_usd_amount_limit!=''){
	$sender_user_result = $adb->pquery("SELECT * FROM `vtiger_users` where id = $assigned_user_id");
	$sender_user_email = $adb->query_result($sender_user_result, 0, 'email1');
	$cc = $sender_user_email;
	//$subject = $subject." CC to $cc";
	//mail('m.arif@globalinklogistics.com,s.mehtab@globalinklogistics.com',$subject,$body,$headers); //$cc
	//$mailer->AddAddress($creator_email); 
	$mailer->AddAddress('m.arif@globalinklogistics.com'); $mailer->AddAddress('s.mehtab@globalinklogistics.com'); 
	//$mailer->addcc('m.arif@globalinklogistics.com'); $mailer->addcc('s.mehtab@globalinklogistics.com'); 
	$mailer->Send(true);
}

}
}

if($page == 'rejected'){/////
	$Branch_Manager = '';
	$Branch_Manager_date_time = '';
	$supervisor_name = '';
	$supervisor_date_time = '';
	$Procurement_name = '';
	$Procurement_date_time = '';
	$CFO_name = '';
	$CFO_date_time = '';
	$CEO_name = '';
	$CEO_date_time = '';
	$currentUser = Users_Record_Model::getCurrentUserModel();
	$currentUser_email = $currentUser->get('email1');
	$currentUser_name = $currentUser->get('first_name').' '.$currentUser->get('last_name');
	$currentUser_title = htmlspecialchars_decode($currentUser->get('title'));
	$date = date('Y-m-d h:i A');
	$sendapprovalemail_result = $adb->pquery("SELECT * FROM `vtiger_send_approval` where procurement_id = $recordID and status=0 order by id  ASC");
$gm_name = $adb->query_result($sendapprovalemail_result, 0, 'gm_name');
$creator_id = $adb->query_result($sendapprovalemail_result, 0, 'creator_id');
$creator_date_time = $adb->query_result($sendapprovalemail_result, 0, 'creator_date');
$creator_result = $adb->pquery("SELECT * FROM `vtiger_users` where id = $creator_id");
$creator_email = $adb->query_result($creator_result, 0, 'email1');
$creater_name = $adb->query_result($creator_result, 0, 'first_name').' '.$adb->query_result($creator_result, 0, 'last_name');
$message_status = $creater_name.' your request has been Cancelled';
$office = $procurement_data->get('proc_location');
$sql_location = $adb->pquery("SELECT cf_1559 FROM `vtiger_locationcf` WHERE `locationid` = '$office'");
$location_name = $adb->fetch_array($sql_location);
$location_status = $location_name['cf_1559'];
$reference_no = $procurement_data->get('proc_request_no');

$adb->pquery("UPDATE `vtiger_procurementcf` SET `proc_order_status`='Cancelled' WHERE `procurementid` = $recordID");
$adb->pquery("UPDATE `vtiger_send_approval` SET status=1, approval_status='Cancelled', date_time_of_approval = '$date' WHERE `procurement_id` = $recordID and who_approve_id=$user_id");


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
	$sql_userprofile = $adb->pquery("SELECT `userlistid`, `cf_3355`, `cf_3385`, `cf_3421`
									FROM `vtiger_userlistcf`
									WHERE `cf_3355` = '$creator_email'");
	$userprofile = $adb->fetch_array($sql_userprofile);
	$gm_id = $userprofile['cf_3385'];
	$sql_gm_email = $adb->pquery("SELECT uf.cf_3355 as email, u.name as name FROM `vtiger_userlistcf` as uf inner join `vtiger_userlist` as u on uf.userlistid = u.userlistid  WHERE uf.`userlistid` = '$gm_id'");
	$gm = $adb->fetch_array($sql_gm_email);
	$gm_email = $gm['email'];
if($gm_email=='s.khan@globalinklogistics.com') //add approval entry if Siddique Khan is the GM of request creator
{
	$procurement_gm_approval_result = $adb->pquery("SELECT * FROM `vtiger_send_approval` where who_approve_id = '$who_create_this_request_id' and procurement_id='".$request->get('recordID')."'");
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
elseif($location_status != 'ALA'){
	

	//now get gm approval status from vtiger_send_approval
	$get_gm_userid = $adb->pquery("SELECT * FROM `vtiger_users` where email1 = '".$gm_email."' limit 1"); //get gm userid from users table
	$gm_userid = $adb->query_result($get_gm_userid, 0, 'id');
	$gm_name = $adb->query_result($get_gm_userid, 0, 'first_name')." ".$adb->query_result($get_gm_userid, 0, 'last_name');
	$procurement_gm_approval_result = $adb->pquery("SELECT * FROM `vtiger_send_approval` where who_approve_id = '$gm_userid' and procurement_id='".$request->get('recordID')."'");
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
	inner join vtiger_procurementapproval on vtiger_procurementapprovalcf.procurementapprovalid=vtiger_procurementapproval.procurementapprovalid 
	left join vtiger_send_approval on vtiger_procurementapprovalcf.procapproval_person = vtiger_send_approval.who_approve_id and vtiger_send_approval.procurement_id='".$request->get('recordID')."' 
	where procapproval_proctype = $ProcurementTypeID AND vtiger_crmentity.deleted=0 order by procapproval_sequence  ASC");
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

	$procurement_detail = Vtiger_Record_Model::getInstanceById($request->get('recordID'), $request->get('module'));
	$link = $site_URL;
	$link .= 'index.php?module=Procurement&view=Detail&record='.$recordID;

	//$from = 'From: '.$who_updated_name.' <'.$who_updated_email.'>';
	$from = $currentUser_email;
	$approval_notice='Your procurement request has been cancelled. Please check details below';
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
				<tr><td style=\"text-align:left;\"><img src=\"http://tiger.globalink.net/gems/include/logo_doc.jpg\" width=\"200\"></td></tr>
			</table>
			<hr><p>&nbsp;</p>
			<p>Dear $creater_name,</p>
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
					<td>Cancelled</td>
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
	//$to = 'm.arif@globalinklogistics.com,s.mehtab@globalinklogistics.com';//$creator_email;
	$subject = $reference_no.'-'.'Procurement has been cancelled';

	//mail($to,$subject,$body,$headers); //$to
	
	$mailer->ConfigSenderInfo($from,$currentUser_name);
	$mailer->Subject = $subject;
	$mailer->Body = $body;
	//$mailer->AddAddress($creator_email); 
	$mailer->AddAddress('m.arif@globalinklogistics.com'); $mailer->AddAddress('s.mehtab@globalinklogistics.com'); 
	//$mailer->addcc('m.arif@globalinklogistics.com'); $mailer->addcc('s.mehtab@globalinklogistics.com'); 
	$mailer->Send(true);
		
	}/// rejected if close////
}/// function close////
}
