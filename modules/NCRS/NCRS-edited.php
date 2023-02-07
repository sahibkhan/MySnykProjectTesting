<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

include_once 'modules/Vtiger/CRMEntity.php';

class NCRS extends Vtiger_CRMEntity {
	var $table_name = 'vtiger_ncrs';
	var $table_index= 'ncrsid';

	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('vtiger_ncrscf', 'ncrsid');

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array('vtiger_crmentity', 'vtiger_ncrs', 'vtiger_ncrscf');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_ncrs' => 'ncrsid',
		'vtiger_ncrscf'=>'ncrsid');

	/**
	 * Mandatory for Listing (Related listview)
	 */
	var $list_fields = Array (
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'Name' => Array('ncrs', 'name'),
		'Assigned To' => Array('crmentity','smownerid')
	);
	var $list_fields_name = Array (
		/* Format: Field Label => fieldname */
		'Name' => 'name',
		'Assigned To' => 'assigned_user_id',
	);

	// Make the field link to detail view
	var $list_link_field = 'name';

	// For Popup listview and UI type support
	var $search_fields = Array(
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'Name' => Array('ncrs', 'name'),
		'Assigned To' => Array('vtiger_crmentity','assigned_user_id'),
	);
	var $search_fields_name = Array (
		/* Format: Field Label => fieldname */
		'Name' => 'name',
		'Assigned To' => 'assigned_user_id',
	);

	// For Popup window record selection
	var $popup_fields = Array ('name');

	// For Alphabetical search
	var $def_basicsearch_col = 'name';

	// Column value to use on detail view record text display
	var $def_detailview_recname = 'name';

	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vtiger_field.fieldname values.
	var $mandatory_fields = Array('name','assigned_user_id');

	var $default_order_by = 'name';
	var $default_sort_order='ASC';
	
	public function saveUniqueID($module,$column,$table_id,$id) {
		global $adb;
		$sql = $adb->pquery("SELECT `$column` FROM `$module` ORDER BY `$column` DESC LIMIT 1", array());
		$arr = $adb->fetch_array($sql);
		$arr = $arr[$column];
		$date = date('y');
		if ($arr != '') {
			$arr = explode('/',$arr);
			if ($arr[1] == $date) {
				$num = (int)$arr[0];
				$num++;
				$res = sprintf("%05s", $num).'/'.$date;
			} else $res = '00001/'.$date;
		} else $res = '00001/'.$date;
		$adb->pquery("UPDATE `$module` SET `$column`=?, cf_6426=? WHERE `$table_id`='".$id."' LIMIT 1", array($res,'Pending'));
	}
	
	function save_module($module) {
		global $adb;
		$recordId = $this->id;
		$recordModel = Vtiger_Record_model::getInstanceById($recordId, $module);
		$sql = $adb->pquery("SELECT cf_1963 FROM vtiger_ncrscf WHERE ncrsid=? LIMIT 1", array($recordId));
		if ($adb->num_rows($sql)>0) {
			$arr_n = $adb->fetch_array($sql);
			if (trim($arr_n['cf_1963']) == '') {
				//$sql1 = mysql_query("SELECT `smownerid` FROM `vtiger_crmentity` WHERE `crmid`='".$recordId."' LIMIT 1");
				//$userid = mysql_fetch_array($sql1)['smownerid'];
				$this->saveUniqueID('vtiger_ncrscf','cf_1963','ncrsid',$recordId);
				$this->notification_NCR($recordId,$module,'Pending');
			}
		}
	}

	/* Send notification to initiator and ncr raised for if NCR status is pending for approval */
	function notification_NCR($recordId,$ncrraisedid,$userlistid, $action){
	
		global $adb;
		$recordId = $recordId;
		$recordModel = Vtiger_Record_model::getInstanceById($recordId, "NCRS");
		$ncr_status = $recordModel->get('cf_6416');

		//$raised_for_id 				= $recordModel->get('cf_6420');
		$raised_for_info 			= Vtiger_Record_model::getInstanceById($ncrraisedid, "NCRRaised");
		$raised_for_person_info 	= Vtiger_Record_model::getInstanceById($userlistid, "UserList");
		$raised_for_email			= $raised_for_person_info->get('cf_3355');
		//$raised_for_email   		= $raised_for_info->get('cf_3355');
		$raised_for_first_name 		= $raised_for_person_info->get('name');
		//$raised_for_last_time  		= $raised_for_info->get('last_name');
		$for_department 			= $raised_for_info->get('cf_6508');
		$for_location 				= $raised_for_info->get('cf_6510');

		$loadUrl = $recordModel->getDetailViewUrl();
		$link  = "https://erp.globalink.net/".$loadUrl;
		
		if($action=='Pending'){			
		
			$smcreator_id 				= $recordModel->get('assigned_user_id');
			$ncr_created_by_info    	= Users_Record_Model::getInstanceById($smcreator_id, 'Users');
			$created_by_email      		= $ncr_created_by_info->get('email1');	
			$created_by_first_name  	= $ncr_created_by_info->get('first_name');
			$created_by_last_name   	= $ncr_created_by_info->get('last_name');
			$created_by_name   			= $created_by_first_name.' '.$created_by_last_name;

			$initiator_id            	= $recordModel->get('cf_1961');
			$initiator_info          	= Users_Record_Model::getInstanceById($initiator_id, 'Users');
			$initiator_email   		 	= $initiator_info->get('email1');
			$initiator_first_name    	= $initiator_info->get('first_name');
			$initiator_last_time     	= $initiator_info->get('last_name');

			$from  = "From: ".$created_by_name." <".$created_by_email.">";
			$to_arr[] = $initiator_email;

			$body  = '';
			$body .="<p>Dear&nbsp;".$initiator_first_name.",</p>";
			$body .="<p>As per your request, we initiated NCR against ".$raised_for_first_name." / ".$for_department." ".$for_location.".<br />";
			$body .="Please check and confirm general information of Non-Conformance for further corrective action.";
			$body .= "<br>Please see details on this link: <a href='$link'> Click To Follow Link </a></p>";
			$body .="<p>Regards,</p>";
			$body .="<p><strong>".$created_by_name."</strong></p>";
			$body .="<p><strong>Globalink Logistics - </strong></p>";

		}
		elseif($action=='Approved')
		{
			$initiator_id            	= $recordModel->get('cf_1961');
			$initiator_info          	= Users_Record_Model::getInstanceById($initiator_id, 'Users');
			$initiator_email   		 	= $initiator_info->get('email1');
			$initiator_first_name    	= $initiator_info->get('first_name');
			$initiator_last_time     	= $initiator_info->get('last_name');
			$initiator_name   			= $initiator_first_name.' '.$initiator_last_time;

			$from  = "From: ".$initiator_name." <".$initiator_email.">";

			$smcreator_id 				= $recordModel->get('assigned_user_id');
			$ncr_created_by_info    	= Users_Record_Model::getInstanceById($smcreator_id, 'Users');
			$created_by_email      		= $ncr_created_by_info->get('email1');	
			$created_by_first_name  	= $ncr_created_by_info->get('first_name');
			$created_by_last_name   	= $ncr_created_by_info->get('last_name');
			$created_by_name   			= $created_by_first_name.' '.$created_by_last_name;
			$to_arr[] = $initiator_email;

			$body  = '';
			$body .="<p>Dear&nbsp;".$created_by_first_name.",</p>";
			$body .="<p>Thanks for initiating NCR against ".$raised_for_first_name."  ".$for_department." / ".$for_location.".<br />";
			$body .="Please proceed for further corrective action.";
			$body .= "<br>Please see details on this link: <a href='$link'> Click To Follow Link </a></p>";
			$body .="<p>Regards,</p>";
			$body .="<p><strong>".$initiator_name."</strong></p>";
			$body .="<p><strong>Globalink Logistics - </strong></p>";
		}	
			
			
			
			$to_arr[] = $raised_for_email;
			$to = implode(',',$to_arr);

			$cc_arr[] = $created_by_email;
			
			$ncr_type = $recordModel->get('cf_6406');
			switch($ncr_type){
				case 'Job File Related':
				//$cc_arr[]='s.mansoor@globalinklogistics.com';
				//$cc_arr[]='d.israilov@globalinklogistics.com';
				$cc_arr[]='s.mehtab@globalinklogistics.com';
				break;
				case 'HR Related':
				//$cc_arr[]='hr@globalinklogistics.com';
				$cc_arr[]='s.mehtab@globalinklogistics.com';
				break;
				case 'QHSE Related':
				//$cc_arr[]='d.israilov@globalinklogistics.com';
				$cc_arr[]='s.mehtab@globalinklogistics.com';
				break;
				default:
			}
			$cc_arr[]='t.malik@globalinklogistics.com';
			$cc = implode(',',$cc_arr);

			$headers  = "MIME-Version: 1.0" . "\n";
			$headers .= "Content-type:text/html;charset=UTF-8" . "\n";
			$headers .= $from . "\n";
			$headers .= 'Reply-To: '.$to.'' . "\n";
			$headers .= "CC:" . $cc . "\r\n";
			$subject  = "NCR For ".$recordModel->get('cf_6406');
			mail($to,$subject,$body,$headers);

		}
	

	/**
	* Invoked when special actions are performed on the module.
	* @param String Module name
	* @param String Event Type
	*/
	function vtlib_handler($moduleName, $eventType) {
		global $adb;
 		if($eventType == 'module.postinstall') {
			// TODO Handle actions after this module is installed.
		} else if($eventType == 'module.disabled') {
			// TODO Handle actions before this module is being uninstalled.
		} else if($eventType == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
		} else if($eventType == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} else if($eventType == 'module.postupdate') {
			// TODO Handle actions after this module is updated.
		}
 	}
}