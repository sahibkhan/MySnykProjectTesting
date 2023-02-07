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

class ExitList extends Vtiger_CRMEntity {
	var $table_name = 'vtiger_exitlist';
	var $table_index= 'exitlistid';

	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('vtiger_exitlistcf', 'exitlistid');

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array('vtiger_crmentity', 'vtiger_exitlist', 'vtiger_exitlistcf');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_exitlist' => 'exitlistid',
		'vtiger_exitlistcf'=>'exitlistid');

	/**
	 * Mandatory for Listing (Related listview)
	 */
	var $list_fields = Array (
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'Name' => Array('exitlist', 'name'),
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
		'Name' => Array('exitlist', 'name'),
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

	function save_module(){
		global $adb;
		$recordId = $this->id;

		// echo "before create record "; exit;
/* 		$this->generateRefNo();

		$recordExitList = Vtiger_Record_Model::getInstanceById($recordId, 'ExitList');
		$currentRefNo = $recordExitList->get('cf_7538');
		$forwardTo = $recordExitList->getDisplayValue('cf_7534');
		$resigningDate = $recordExitList->get('cf_7536');
		$recordCreatorId = $recordExitList->get('assigned_user_id');
		$employeeName = $recordExitList->getDisplayValue('name');

		$creatorRecord = Vtiger_Record_Model::getInstanceById($recordCreatorId, 'Users');
		$requestedByEmail = $creatorRecord->get('email1');
		$requestedByName = $creatorRecord->get('first_name').' '.$creatorRecord->get('last_name');

		// Fetch request user email		
		$queryUser = $adb->pquery("
								SELECT vtiger_userlistcf.cf_3355
								FROM vtiger_userlistcf
								INNER JOIN vtiger_userlist ON vtiger_userlist.userlistid = vtiger_userlistcf.userlistid
								WHERE vtiger_userlistcf.userlistid = ?",array($recordExitList->get('cf_7534')));
		$forwardToEmail = trim($adb->query_result($queryUser, 0, 'cf_3355'));
 */

	}


/* 
	function getListOfApprovers(){
		return 'User list here!!!';
	} */


	public function sendEmailNotification($details){

		$userName = $details['employeeName'];
		$recordId = $details['recordId'];
		$exitListId = $details['exitListId'];
		$handOverLink = $details['handOverLink'];
		$fromName = $details['fromName'];
		$approverName = $details['approverName'];
		$refNo = $details['refNo'];
		$resigningDate = $details['resigningDate'];
		$forwardTo = $details['forwardTo'];
		$forwardToEmail = trim($details['forwardToEmail']);
		$type = $details['type'];
		$link = $_SERVER['SERVER_NAME'];
		$date_time = date('Y-m-d H:i:s');
		$from = trim($details['fromEmail']);
		
		$body = '';
		if ($type == 'IT'){
			$to = "it@globalinklogistics.com";
			$link .= "/index.php?module=ExitList&view=Detail&record=".$recordId;
			$message_status .= "<tr><td colspan=2> <b> Dear IT team, </b> </td></tr>";
			$message_status .= "<tr><td colspan=2> </td></tr>";
			$message_status .= "<tr><td colspan=2> Please be informed that $userName is resigning from Globalink. Kindly setup the email forwarding to: $forwardTo ($forwardToEmail) </td></tr>";
			$cc = "hr@globalinklogistics.com";

		} else if ($type == 'exitList'){
			$to = $forwardToEmail;
			$link .= "/index.php?module=ExitListEntries&view=Detail&record=".$recordId;
			$exitListLink = $_SERVER['SERVER_NAME'] . "/index.php?module=ExitList&view=Detail&record=".$exitListId;

			$message_status .= "<tr><td colspan=2> <b> Dear $approverName,</b> </td></tr>";
			$message_status .= "<tr><td colspan=2> </td></tr>";
			$message_status .= "<tr><td colspan=2> Please note that $userName, $userPosition is resigning on $resigningDate.</td></tr>";
			$message_status .= "<tr><td colspan=2> Kindly see below link to </td></tr>";
			$message_status .= "<tr><td colspan=2> <a href='$exitListLink'> Exit List </a> </td></tr>";
			$message_status .= $handOverLink;
			$message_status .= "<tr><td colspan=2> Please follow the link to approve Exit List or contact employee directly 
																							if there are items/assets to be returned back to Company and approve after submission done.
															 </td></tr>";
		} else if ($type == 'exitListReminder'){
			$to = $forwardToEmail;
			$link .= "/live-gems/index.php?module=ExitList&view=Detail&record=".$recordId;
			$exitListLink = $_SERVER['SERVER_NAME'] . "/live-gems/index.php?module=ExitListEntries&view=Detail&record=".$exitListId;

			$message_status .= "<tr><td colspan=2> <b> Dear $approverName,</b> </td></tr>";
			$message_status .= "<tr><td colspan=2> </td></tr>";
			$message_status .= "<tr><td colspan=2> Please note your approval is missing in exit list of $userName, $userPosition. Kindly follow below link and approve request.</td></tr>";
			$message_status .= "<tr><td colspan=2> <a href='$exitListLink'> Exit list approval  </a> </td></tr>";
		}

		// if ($type == 'IT'){
			$subject = 'Exit List: '.$refNo;
			$body .= "<html><head> <style> #tableBody tr td{ margin:3px; } </style> </head>
								<body><table id='tableBody'> ";
			$body .= $message_status;
	

			$body .= "<tr><td colspan=2>Link: <a href='$link'> Link to Exit List </a></td></tr>";
			$body .= "</table> </body> </html> ";
																		
			// Set content-type when sending HTML email
			$headers = "MIME-Version: 1.0" . "\n";
			$headers .= "Content-type:text/html;charset=UTF-8" . "\n";
		
			$headers .= $from . "\n";
			$headers .= 'Reply-To: '.$to.'' . "\n";
			
			// require_once("modules/Emails/mail.php");
			// $r = send_mail('ExitList', 'r.gusseinov@globalinklogistics.com', $from, $from, $subject, $body, '','','','','',true);
		// }
		
	}




	function generateRefNo(){
		global $adb;
		// GENERATE Ref. no for agreement	
		// Creator location code
		$recordId = $this->id;
		$recordIA = Vtiger_Record_Model::getInstanceById($recordId, 'ExitList');
		$location_id = $recordIA->get('cf_7530');
		$currentRefNo = $recordIA->get('cf_7538');

		$recordLocation = Vtiger_Record_Model::getInstanceById($location_id, 'Location');
		$locationCode = $recordLocation->get('cf_1559');

		// Number of records relevant to current location and department
		$sql_m =  "SELECT vtiger_crmentity.crmid
			FROM vtiger_exitlistcf
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_exitlistcf.exitlistid
			WHERE vtiger_crmentity.deleted = 0 AND vtiger_exitlistcf.cf_7530 = ?";
	
		$result_m = $adb->pquery($sql_m, array($location_id));
		$nOfRecords = $adb->num_rows($result_m);
		$refNo = trim(sprintf("%'.05d\n", $nOfRecords + 1));		
		$subject = $locationCode.'-'.$refNo.'/'.Date('y');

		// Update ref. no
		if (strlen($currentRefNo) == 0){
			$adb->pquery("UPDATE `vtiger_exitlistcf` SET `cf_7538` = '$subject' WHERE `exitlistid` = ? LIMIT 1", array($recordId));
		}

}


// isDriver
function getExitListApprovers($locationId){
	global $adb;
	// $locationId = 85829;
	$approverList = array();

	$queryApprovers = $adb->pquery("SELECT vtiger_surveypm.name as userId, vtiger_userlist.name as approverName, vtiger_surveypmcf.cf_7904 as positionTitle
																	FROM vtiger_surveypmcf
																	INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_surveypmcf.surveypmid
																	INNER JOIN vtiger_surveypm ON vtiger_surveypm.surveypmid = vtiger_surveypmcf.surveypmid
																	LEFT JOIN vtiger_userlist ON vtiger_userlist.userlistid = vtiger_surveypm.name

																	WHERE vtiger_crmentity.deleted = 0 AND vtiger_surveypmcf.cf_7902 = ? ",array($locationId));
	$nRows = $adb->num_rows($queryApprovers);
	for ($i=0; $i<=$nRows; $i++){

		$userId = $adb->query_result($queryApprovers, $i, 'userId');
		$approverName = $adb->query_result($queryApprovers, $i, 'approverName');
		$positionTitle = $adb->query_result($queryApprovers, $i, 'positionTitle');

		$approverList[] = array('positionTitle' => $positionTitle, 'approverName' => $approverName);
	}

	return $approverList;
}



/* 	function getExitListUsers($isDriver){
		$userList = array();		
		$userList[] = array('id' => 876194, 'name' => 'Полякова Екатерина', 'title' => 'Бухгалтер-архивариус', 'order' => 1);
		$userList[] = array('id' => 703735, 'name' => 'Мирзатбаева Мадина', 'title' => 'Бухгалтер-кассир', 'order' => 2);
 		$userList[] = array('id' => 703749, 'name' => 'Рискаленко Елена', 'title' => 'Бухгалтер по ГСМ', 'order' => 3);
		$userList[] = array('id' => 703813, 'name' => 'Маликова Марал', 'title' => 'Бухгалтер', 'order' => 4);
 		$userList[] = array('id' => 703704, 'name' => 'Назаренко Юлия', 'title' => 'Бухгалтер', 'order' => 5);
		$userList[] = array('id' => 703793, 'name' => 'Алия Еркин', 'title' => 'Бухгалтер', 'order' => 6);
		$userList[] = array('id' => 1562468, 'name' => 'Саркулова Индира', 'title' => 'Бухгалтер', 'order' => 7);
		$userList[] = array('id' => 703597, 'name' => 'Замятина Галина', 'title' => 'Бухгалтер', 'order' => 8);
		$userList[] = array('id' => 2873204, 'name' => 'Марина Акимова', 'title' => 'Бухгалтер', 'order' => 9);
		$userList[] = array('id' => 703520, 'name' => 'Харитонович Ольга', 'title' => 'Заместитель главного бухгалтера', 'order' => 0);
		$userList[] = array('id' => 703478, 'name' => 'Салаева Гаухар', 'title' => 'Главный бухгалтер', 'order' => 0);

		if ($isDriver){
			$userList[] = array('id' => 759671, 'name' => 'Самарцев Александр', 'title' => 'Менеджер автопарка', 'order' => 0);
			$userList[] = array('id' => 759675, 'name' => 'Тохтар Жумагулов', 'title' => 'Диспетчер автопарка', 'order' => 0);
		}

		$userList[] = array('id' => 3323526, 'name' => 'Карпатов Зангар', 'title' => 'Специалист по документации(for drivers)', 'order' => 0);
		if ($isDriver){
			$userList[] = array('id' => 704089, 'name' => 'Аймурат Данабай', 'title' => 'Менеджер по работе с государственными органами', 'order' => 0);
		}

		//$userList[] = array('id' => 704343, 'name' => 'Власов Константин', 'title' => 'Вахтер', 'order' => 0);
		//$userList[] = array('id' => 3323436, 'name' => 'Акболат Серикбол', 'title' => 'Менеджер склада', 'order' => 0);
		$userList[] = array('id' => 876363, 'name' => 'Даурен Исраилов', 'title' => 'Менеджер обеспечения качества, безопасности, охраны труда и окружающей среды', 'order' => 0);
		$userList[] = array('id' => 3071794, 'name' => 'Алексей Аристов', 'title' => 'Менеджер IT отдела', 'order' => 0);
		// $userList[] = array('id' => 916764, 'name' => 'Салман Афтаб', 'title' => 'Генеральный менеджер IT отдела', 'order' => 0);
		$userList[] = array('id' => 720911, 'name' => 'Давыдков Игорь', 'title' => 'Офис - Менеджер', 'order' => 0);
		$userList[] = array('id' => 703467, 'name' => 'Любовь Белякова', 'title' => 'Старший финансовый менеджер группы', 'order' => 0);
		$userList[] = array('id' => 2113825, 'name' => 'Асия Джанчурина', 'title' => 'Специалист по делопроизводству/кассир', 'order' => 0);
		$userList[] = array('id' => 703865, 'name' => 'Дарья Сергеева', 'title' => 'Менеджер по кредиторской задолжности', 'order' => 0);
		$userList[] = array('id' => 703859, 'name' => 'Умталиева Бахытгуль', 'title' => 'Менеджер по контролю и аудиту', 'order' => 0);
		$userList[] = array('id' => 703848, 'name' => 'Балаев Рустам', 'title' => 'Менеджер по финансам и оценке рисков', 'order' => 0);
		$userList[] = array('id' => 1663589, 'name' => 'Тимур Макаров', 'title' => 'Генеральный менеджер юридического отдела', 'order' => 0);
		$userList[] = array('id' => 703290, 'name' => 'Сохаил Мансур', 'title' => 'Финансовый директор', 'order' => 0);
		// $userList[] = array('id' => 704022, 'name' => 'Балаев Руслан', 'title' => 'Директор', 'order' => 0);
		$userList[] = array('id' => 4438501, 'name' => 'Ардак Гайсина', 'title' => 'Генеральный менеджер отдела управления человеческими ресурсами', 'order' => 0);
		
		return $userList;
	} */

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