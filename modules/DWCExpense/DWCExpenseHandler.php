<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
function message_Send($module_name,$recordId,$current_userid) {
    $adb = PearDatabase::getInstance();
    
    $dwexpense_info = Vtiger_Record_Model::getInstanceById($recordId, 'DWCExpense');
    $creator_id = $dwexpense_info->get('assigned_user_id');
    $creator_user_info = Users_Record_Model::getInstanceById($creator_id, 'Users');

    $creator_name = $creator_user_info->get('first_name').' '.$creator_user_info->get('last_name');
    $creator_email = $creator_user_info->get('email1');

    	
        $from = 'From: '.$creator_name.' <'.$creator_email.'>';
        global $site_URL;
        $link = $site_URL;

		$link .= 'index.php?module=DWCExpense&view=Detail&record='.$recordId;

		$HTML = '
		<html>
			<head>
			<style>
				b {
					font-weight: bold;
				}
			</style>
			</head>
			<body>
				<table cellpadding="1" cellspacing="1" >
					<tr><td><b>DWCExpense Details</b></td></tr>
					<tr><td>Requester:   <span style="font-weight: bold;">'.$creator_name.'</span></td></tr>
					<tr><td>Subject:     <span style="font-weight: bold;">'.$dwexpense_info->get('name').'</span></td></tr>
					<tr><td>Amount:      <span style="font-weight: bold;">'.$dwexpense_info->get('cf_4741').' '.($dwexpense_info->get('cf_4735')!=''?'('.$dwexpense_info->get('cf_4735').')':'').'</span></td></tr>
					<tr><td>Details:     <span style="font-weight: bold;">'.$dwexpense_info->get('cf_4739').'</span></td></tr>
					<tr><td>Description: <span style="font-weight: bold;">'.$dwexpense_info->get('cf_4737').'</span></td></tr>
					<tr><td><span style="font-weight: bold;">APPROVAL DETAILS</span><td></tr>
					<tr><td>GM Approval: <span style="font-weight: bold;">'.($dwexpense_info->get('cf_4743')!==''?'Aftab Ahmed ('.$dwexpense_info->get('cf_4745').')':'').'</span></td></tr>
					<tr><td>CFO Approval: <span style="font-weight: bold;">'.($dwexpense_info->get('cf_4747')!==''?'Sohail Mansoor ('.$dwexpense_info->get('cf_4749').')':'').'</span></td></tr>
					<tr><td>CEO Approval: <span style="font-weight: bold;">'.($dwexpense_info->get('cf_4751')!==''?'Siddique Khan ('.$dwexpense_info->get('cf_4753').')':'').'</span></td></tr>
					<tr><td>Please see details on this link: '.$link.'</td></tr>
				</table>
			</body>
		</html>';

		$headers = "MIME-Version: 1.0\n";
		$headers .= "Content-type:text/html;charset=UTF-8\n";
		$headers .= $from . "\n";
		$headers .= 'Reply-To: '.$creator_email. "\n";
		$subject = 'DWCExpense from '.$name;

		$to = [];

		if ($creator_id === $current_userid) {
			$to[] = 'a.ahmed@globalinklogistics.com';
		} else if ($current_userid === 277) {
			$to[] = 's.mansoor@globalinklogistics.com';
			$to[] = $creator_email;
			$to[] = 's.khan@globalinklogistics.com';
		} else if ($current_userid === 60) {
			$to[] = 'a.ahmed@globalinklogistics.com';
			$to[] = 's.mansoor@globalinklogistics.com';
			$to[] = $creator_email;
		}

		//$to[] = 'e.tamabay@globalinklogistics.com';
		//$to[] = 'r.gusseinov@globalinklogistics.com';
		$to = implode(',',$to);
		mail($to,$subject,$HTML,$headers);
}



 ?>