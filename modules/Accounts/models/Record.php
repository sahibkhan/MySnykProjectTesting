<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Accounts_Record_Model extends Vtiger_Record_Model {

	/**
	 * Function returns the details of Accounts Hierarchy
	 * @return <Array>
	 */
	function getAccountHierarchy() {
		$focus = CRMEntity::getInstance($this->getModuleName());
		$hierarchy = $focus->getAccountHierarchy($this->getId());
		$i=0;
		foreach($hierarchy['entries'] as $accountId => $accountInfo) {
			preg_match('/<a href="+/', $accountInfo[0], $matches);
			if($matches != null) {
				preg_match('/[.\s]+/', $accountInfo[0], $dashes);
				preg_match("/<a(.*)>(.*)<\/a>/i",$accountInfo[0], $name);

				$recordModel = Vtiger_Record_Model::getCleanInstance('Accounts');
				$recordModel->setId($accountId);
				$hierarchy['entries'][$accountId][0] = $dashes[0]."<a href=".$recordModel->getDetailViewUrl().">".$name[2]."</a>";
			}
		}
		return $hierarchy;
	}

	function getMyCustomMod(){
		return 'Hello from records';
	}

	/**
	 * Function returns the url for create event
	 * @return <String>
	 */
	function getCreateEventUrl() {
		$calendarModuleModel = Vtiger_Module_Model::getInstance('Calendar');
		return $calendarModuleModel->getCreateEventRecordUrl().'&parent_id='.$this->getId();
	}

	/**
	 * Function returns the url for create todo
	 * @retun <String>
	 */
	function getCreateTaskUrl() {
		$calendarModuleModel = Vtiger_Module_Model::getInstance('Calendar');
		return $calendarModuleModel->getCreateTaskRecordUrl().'&parent_id='.$this->getId();
	}

	/**
	 * Function to check duplicate exists or not
	 * @return <boolean>
	 */
	public function checkDuplicate() {
		$db = PearDatabase::getInstance();

		$query = "SELECT 1 FROM vtiger_crmentity WHERE setype = ? AND label = ? AND deleted = 0";
                $params = array($this->getModule()->getName(), decode_html($this->getName())); 

		$record = $this->getId();
		if ($record) {
			$query .= " AND crmid != ?";
			array_push($params, $record);
		}

		$result = $db->pquery($query, $params);
		if ($db->num_rows($result)) {
			return true;
		}
		return false;
	}

	/**
	 * Function to get List of Fields which are related from Accounts to Inventory Record.
	 * @return <array>
	 */
	public function getInventoryMappingFields() {
		return array(
				//Billing Address Fields
				array('parentField'=>'bill_city', 'inventoryField'=>'bill_city', 'defaultValue'=>''),
				array('parentField'=>'bill_street', 'inventoryField'=>'bill_street', 'defaultValue'=>''),
				array('parentField'=>'bill_state', 'inventoryField'=>'bill_state', 'defaultValue'=>''),
				array('parentField'=>'bill_code', 'inventoryField'=>'bill_code', 'defaultValue'=>''),
				array('parentField'=>'bill_country', 'inventoryField'=>'bill_country', 'defaultValue'=>''),
				array('parentField'=>'bill_pobox', 'inventoryField'=>'bill_pobox', 'defaultValue'=>''),

				//Shipping Address Fields
				array('parentField'=>'ship_city', 'inventoryField'=>'ship_city', 'defaultValue'=>''),
				array('parentField'=>'ship_street', 'inventoryField'=>'ship_street', 'defaultValue'=>''),
				array('parentField'=>'ship_state', 'inventoryField'=>'ship_state', 'defaultValue'=>''),
				array('parentField'=>'ship_code', 'inventoryField'=>'ship_code', 'defaultValue'=>''),
				array('parentField'=>'ship_country', 'inventoryField'=>'ship_country', 'defaultValue'=>''),
				array('parentField'=>'ship_pobox', 'inventoryField'=>'ship_pobox', 'defaultValue'=>'')
		);
	}

	public function crm_own_invitedformat($id){
		$adb = PearDatabase::getInstance();
		$res_users = $adb->pquery("Select * From `vtiger_users` where `id`=".$id);
		$row_user = $adb->fetch_array($res_users);
		$value = $this->arrange_user_format($row_user['user_name'],1);
	
		return $value;
	}

	public function arrange_user_format($users,$mode){
		// Вывод данных пользователей
		$adb = PearDatabase::getInstance();
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

	public function getInvities() {
		$adb = PearDatabase::getInstance();
		$sql = "select vtiger_invitees.* from vtiger_invitees where activityid=?";
		$result = $adb->pquery($sql,array($this->getId()));
		$invitiesId = array();

		$num_rows = $adb->num_rows($result);

		for($i=0; $i<$num_rows; $i++) {
			$invitiesId[] = $adb->query_result($result, $i,'inviteeid');
		}
		//var_dump($invitiesId); exit;
		return $invitiesId;
	}

	/*
		Get Account details
	*/
	public function getAccountCompanyStatus(){
	
		//$acc_info = array();
		$acc_tree = Vtiger_Record_Model::getInstanceById($this->getId(), 'Accounts');
	    $company_status = $acc_tree->get('cf_2403');
	   // $acc_info['company_status'] = $company_status;


	  return $company_status;	
	
	}
	/*
		Get Account created time
	*/
	public function getAccountCreatedTime(){
	
		//$acc_info = array();
		$acc_tree = Vtiger_Record_Model::getInstanceById($this->getId(), 'Accounts');
	    $acc_createdtime = $acc_tree->get('createdtime');		
		$date_start = date('Y-m-d', strtotime($acc_createdtime));
		$date_status = 0;
		if ($date_start >= "2019-02-22") $date_status = 1; else if ($date_start <= "2019-02-22") $date_status = 0;
		
	   // $acc_info['company_status'] = $company_status;


	  return $date_status;	
	
	}

	/*
	  Get account type
	*/
	public function getAccountTypeName() {
		global $adb;
		/*
			 Fetch request user email
		*/
		$request_q = $adb->pquery("
			SELECT account_type FROM `vtiger_account` WHERE `accountid` = ?",array($this->getId())
	   );
		$account_type = $adb->query_result($request_q, 0, 'account_type');
		return $account_type;
		}
		

		public function getEventInvitees(){
			$invites = parent::getRecordInvitees();
			return $invites;
		}
		
		public function getEventInvitee($userId){
			$invites = parent::getInviteeDetails($userId);
			return $invites;
		}		


/* 	public function getAccountInviteeUsers() {
		global $adb;
			$inviteUsers = '';
			$q_invitees = $adb->pquery("SELECT vtiger_users.first_name, vtiger_users.last_name
																	FROM vtiger_users
																	LEFT JOIN vtiger_invitees ON  vtiger_invitees.inviteeid = vtiger_users.id
																	WHERE vtiger_invitees.activityid = ? AND vtiger_users.status = ?", array($this->getId(), 'Active')
			);
			$num_rows = $adb->num_rows($q_invitees);

			for($i=0; $i<$num_rows; $i++) {
				$inviteUsers .= "<tr><td>".$adb->query_result($q_invitees, $i,'first_name').'</td><td>'. $adb->query_result($q_invitees, $i,'last_name').'</td></tr>';
			}

			return $inviteUsers;
		} */

}

// echo 'ok';
