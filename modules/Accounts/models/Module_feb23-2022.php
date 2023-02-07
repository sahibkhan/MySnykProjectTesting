<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ************************************************************************************/

class Accounts_Module_Model extends Vtiger_Module_Model {

	/**
	 * Function to get the Quick Links for the module
	 * @param <Array> $linkParams
	 * @return <Array> List of Vtiger_Link_Model instances
	 */
	public function getSideBarLinks($linkParams) {
		$parentQuickLinks = parent::getSideBarLinks($linkParams);

		$quickLink = array(
			'linktype' => 'SIDEBARLINK',
			'linklabel' => 'LBL_DASHBOARD',
			'linkurl' => $this->getDashBoardUrl(),
			'linkicon' => '',
		);

		//Check profile permissions for Dashboards
		$moduleModel = Vtiger_Module_Model::getInstance('Dashboard');
		$userPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($moduleModel->getId());
		if($permission) {
			$parentQuickLinks['SIDEBARLINK'][] = Vtiger_Link_Model::getInstanceFromValues($quickLink);
		}
		
		return $parentQuickLinks;
	}

	/**
	 * Function to get list view query for popup window
	 * @param <String> $sourceModule Parent module
	 * @param <String> $field parent fieldname
	 * @param <Integer> $record parent id
	 * @param <String> $listQuery
	 * @return <String> Listview Query
	 */
	public function getQueryByModuleField($sourceModule, $field, $record, $listQuery) {

		if (($sourceModule == 'Accounts' && $field == 'account_id' && $record)
				|| in_array($sourceModule, array('Campaigns', 'Products', 'Services', 'Emails'))) {

		    	$db = PearDatabase::getInstance();
		    	$params = array($record);
			if ($sourceModule === 'Campaigns') {
				$condition = " vtiger_account.accountid NOT IN (SELECT accountid FROM vtiger_campaignaccountrel WHERE campaignid = ?)";
			} elseif ($sourceModule === 'Products') {
				$condition = " vtiger_account.accountid NOT IN (SELECT crmid FROM vtiger_seproductsrel WHERE productid = ?)";
			} elseif ($sourceModule === 'Services') {
				$condition = " vtiger_account.accountid NOT IN (SELECT relcrmid FROM vtiger_crmentityrel WHERE crmid = ? UNION SELECT crmid FROM vtiger_crmentityrel WHERE relcrmid = ?) ";
                		$params = array($record, $record);
            		} elseif ($sourceModule === 'Emails') {
				$condition = ' vtiger_account.emailoptout = 0';
                		$params = array();
			} else {
				$condition = " vtiger_account.accountid != ?";
			}
            $condition = $db->convert2Sql($condition, $params);

			$position = stripos($listQuery, 'where');
			if($position) {
				$split = preg_split('/where/i', $listQuery);
				$overRideQuery = $split[0] . ' WHERE ' . $split[1] . ' AND ' . $condition;
			} else {
				$overRideQuery = $listQuery. ' WHERE ' . $condition;
			}
			return $overRideQuery;
		}
		else if( in_array($sourceModule, array('Job', 'JER', 'Jobexpencereport', 'Potentials', 'Quotes', 'BO','VPO')))	{	
			//$condition = '';	
			$db = PearDatabase::getInstance();
			$params = array($record);
			
			if (($field == 'cf_1441' || $field == 'cf_1443' || $field=='cf_1443[]' || $field=='cf_1445' || $field=='related_to' || $field=='account_id') ) {
				$condition = " vtiger_crmentity.setype='Accounts' AND (vtiger_account.account_type='Customer' || vtiger_account.account_type='Agent') AND vtiger_accountscf.cf_2403 IN('Approved', 'Confirmed') ";
			}
			elseif(($field == 'cf_1082' || $field=='cf_1176' || $field=='cf_1176[]' || $field=='cf_1367' || $field=='cf_1377' || $field=='campaignid' || $field=='cf_1827')) {
				//For claims customer
				if($field=='cf_1367') {
					$condition = " vtiger_crmentity.setype='Accounts' AND (vtiger_account.account_type='Vendor' || vtiger_account.account_type='Agent' || vtiger_accountscf.cf_7814='Yes') AND vtiger_accountscf.cf_2403 IN('Approved', 'Confirmed') ";
				}
				else{
					$condition = " vtiger_crmentity.setype='Accounts' AND (vtiger_account.account_type='Vendor' || vtiger_account.account_type='Agent') AND vtiger_accountscf.cf_2403 IN('Approved', 'Confirmed') ";
					
				}

			}
			
			$condition = $db->convert2Sql($condition, $params);

			$position = stripos($listQuery, 'where');
			if($position) {
				$split = preg_split('/where/i', $listQuery);
				$overRideQuery = $split[0] . ' WHERE ' . $split[1] . ' AND ' . $condition;
			} else {
				$overRideQuery = $listQuery. ' WHERE ' . $condition;
			}
			
			return $overRideQuery;
		}
		else if( in_array($sourceModule, array('ItemTRXMaster', 'WHItemQTYMaster')))	{	
			$db = PearDatabase::getInstance();
			$params = array($record);

			if(($field=='cf_5597' || $field='cf_5636'))
			{
				$condition = "  vtiger_crmentity.setype='Accounts' AND (vtiger_accountscf.cf_5758='1')  ";
			}

			$condition = $db->convert2Sql($condition, $params);

			$position = stripos($listQuery, 'where');
			if($position) {
				$split = preg_split('/where/i', $listQuery);
				$overRideQuery = $split[0] . '  LEFT JOIN vtiger_accountscf ON vtiger_accountscf.accountid = vtiger_account.accountid  WHERE ' . $split[1] . ' AND ' . $condition;
			} else {
				$overRideQuery = $listQuery. ' WHERE ' . $condition;
			}
			
			return $overRideQuery;
		}
		else if( in_array($sourceModule, array('Procurement')))	{	
			$db = PearDatabase::getInstance();
			$params = array($record);
			if (($field == 'proc_supplier') ) {
				$condition = " vtiger_crmentity.setype='Accounts' AND (vtiger_account.account_type='Vendor' || vtiger_account.account_type='Agent' || vtiger_accountscf.cf_7814='Yes') 
				AND vtiger_accountscf.cf_2403 IN('Approved', 'Confirmed') AND  vtiger_serviceagreementcf.cf_6068 = 'Administrative'
				
				"; //AND '".date('Y-m-d')."' between vtiger_serviceagreementcf.cf_6018 AND vtiger_serviceagreementcf.cf_6020
			}
			$condition = $db->convert2Sql($condition, $params);

			$position = stripos($listQuery, 'where');
			if($position) {
				$split = preg_split('/where/i', $listQuery);
				$overRideQuery = $split[0] . ' INNER JOIN vtiger_serviceagreementcf ON vtiger_serviceagreementcf.cf_6094 = vtiger_account.accountid WHERE ' . $split[1] . ' AND ' . $condition;
			} else {
				$overRideQuery = $listQuery. ' WHERE ' . $condition;
			}
			
			//echo $overRideQuery;exit;
			return $overRideQuery;
		}
	}

	/**
	 * Function to get relation query for particular module with function name
	 * @param <record> $recordId
	 * @param <String> $functionName
	 * @param Vtiger_Module_Model $relatedModule
	 * @return <String>
	 */
	public function getRelationQuery($recordId, $functionName, $relatedModule, $relationId) {
		if ($functionName === 'get_activities') {
			$focus = CRMEntity::getInstance($this->getName());
			$focus->id = $recordId;
			$entityIds = $focus->getRelatedContactsIds();
			$entityIds = implode(',', $entityIds);

			$userNameSql = getSqlForNameInDisplayFormat(array('first_name' => 'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'), 'Users');

			$query = "SELECT CASE WHEN (vtiger_users.user_name not like '') THEN $userNameSql ELSE vtiger_groups.groupname END AS user_name,
						vtiger_crmentity.*, vtiger_activity.activitytype, vtiger_activity.subject, vtiger_activity.date_start, vtiger_activity.time_start,
						vtiger_activity.recurringtype, vtiger_activity.due_date, vtiger_activity.time_end, vtiger_activity.visibility, vtiger_seactivityrel.crmid AS parent_id,
						CASE WHEN (vtiger_activity.activitytype = 'Task') THEN (vtiger_activity.status) ELSE (vtiger_activity.eventstatus) END AS status
						FROM vtiger_activity
						INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_activity.activityid
						LEFT JOIN vtiger_seactivityrel ON vtiger_seactivityrel.activityid = vtiger_activity.activityid
						LEFT JOIN vtiger_cntactivityrel ON vtiger_cntactivityrel.activityid = vtiger_activity.activityid
						LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid
						LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid
							WHERE vtiger_crmentity.deleted = 0 AND vtiger_activity.activitytype <> 'Emails'
								AND (vtiger_seactivityrel.crmid = ".$recordId;
			if($entityIds) {
				$query .= " OR vtiger_cntactivityrel.contactid IN (".$entityIds."))";
			} else {
				$query .= ")";
			}

			$relatedModuleName = $relatedModule->getName();
			$query .= $this->getSpecificRelationQuery($relatedModuleName);
			$nonAdminQuery = $this->getNonAdminAccessControlQueryForRelation($relatedModuleName);
			if ($nonAdminQuery) {
				$query = appendFromClauseToQuery($query, $nonAdminQuery);

				if(trim($nonAdminQuery)) {
					$relModuleFocus = CRMEntity::getInstance($relatedModuleName);
					$condition = $relModuleFocus->buildWhereClauseConditionForCalendar();
					if($condition) {
						$query .= ' AND '.$condition;
					}
				}
			}

			// There could be more than one contact for an activity.
			$query .= ' GROUP BY vtiger_activity.activityid';
		} else {
			$query = parent::getRelationQuery($recordId, $functionName, $relatedModule, $relationId);
		}

		return $query;
	}
	
	/**
	 * Custom Static Function to get the list of records matching the search key from procurement module only
	 * @param <String> $searchKey
	 * @return <Array> - List of Vtiger_Record_Model or Module Specific Record Model instances
	 */
	public static function getSearchResultForProcurement($searchKey, $module=false) {
		$db = PearDatabase::getInstance();
		
		$query = 'SELECT label, crmid, setype, createdtime FROM vtiger_crmentity WHERE label LIKE ? AND vtiger_crmentity.deleted = 0';
		$params = array("%$searchKey%");

		if($module !== false) {
			$query .= ' AND setype = ?';
			$params[] = $module;
		}

		if($module=="Accounts")
		{
			//11.10.2020::Mehtab Updated due to Azerbaijan and Armenia conflict
			//Location id : 85808 :: Baku
			//Location id: 85820 :: Yerevan

			global $current_user;
			$query_restricted='';
			$tablelist_restricted='';
			//INNER JOIN vtiger_accountbillads ON vtiger_account.accountid = vtiger_accountbillads.accountaddressid 	
			if($current_user->location_id=='85808')
			{
				//Restrict armenia customer/agent/vendor
				$query_restricted = ' AND vtiger_accountscf.cf_2709 != "85820" AND vtiger_accountbillads.bill_country !="Armenia" ';
				//array_push($params, '85820');
				$tablelist_restricted = ' LEFT JOIN vtiger_accountbillads ON vtiger_accountscf.accountid = vtiger_accountbillads.accountaddressid  ';

			}
			else if($current_user->location_id=='85820')
			{
				//Restrict baku customer/agent/vendor
				$query_restricted = ' AND vtiger_accountscf.cf_2709 != "85808" AND vtiger_accountbillads.bill_country !="Azerbaijan" ';
				//array_push($params, '85808');
				$tablelist_restricted = ' LEFT JOIN vtiger_accountbillads ON vtiger_accountscf.accountid = vtiger_accountbillads.accountaddressid  ';

			}
			/*
			$query = $query . " UNION ALL ". 'select vtiger_accountscf.cf_2395 as label, vtiger_accountscf.accountid as crmid, 
											 "Accounts" as setype, vtiger_crmentity.createdtime as createdtime 
											 from vtiger_accountscf , vtiger_crmentity
											  where vtiger_accountscf.accountid = vtiger_crmentity.crmid AND 
											  vtiger_crmentity.deleted = 0 AND (vtiger_accountscf.cf_2395 like ? ) ';
			$params = array_merge($params ,array("%$searchKey%"));	
			*/
			
			//mehtab code
			$new_condition = " AND (vtiger_account.account_type='Vendor' || vtiger_account.account_type='Agent' || vtiger_accountscf.cf_7814='Yes') 
			AND vtiger_accountscf.cf_2403 IN('Approved', 'Confirmed') AND  vtiger_serviceagreementcf.cf_6068 = 'Administrative'
				 "; //AND '".date('Y-m-d')."' between vtiger_serviceagreementcf.cf_6018 AND vtiger_serviceagreementcf.cf_6020
				
			
			$query = 'SELECT CONCAT_WS(vtiger_crmentity.label,vtiger_accountscf.cf_2395) as label , vtiger_accountscf.cf_2403 as status, 
crmid, setype, createdtime
FROM vtiger_account INNER JOIN vtiger_crmentity ON vtiger_account.accountid = vtiger_crmentity.crmid 
INNER JOIN vtiger_accountscf ON vtiger_account.accountid = vtiger_accountscf.accountid LEFT JOIN vtiger_users 
ON vtiger_crmentity.smownerid = vtiger_users.id 
LEFT JOIN vtiger_groups ON vtiger_crmentity.smownerid = vtiger_groups.groupid 
INNER JOIN vtiger_accountbillads ON vtiger_account.accountid = vtiger_accountbillads.accountaddressid 
INNER JOIN vtiger_serviceagreementcf ON vtiger_serviceagreementcf.cf_6094 = vtiger_account.accountid 
'.$tablelist_restricted.'
WHERE (label LIKE "%'.addslashes($searchKey).'%" || vtiger_accountscf.cf_2395 like "%'.addslashes($searchKey).'%") 
AND vtiger_crmentity.deleted = 0 '.$new_condition.'  '.$query_restricted.' ';


			$params = array();
			if($module !== false) {
			$query .= ' AND setype = "'.$module.'" ';
			//$params[] = $module;
			}
			//mehtab code										  
		}

		

		//Remove the ordering for now to improve the speed
		//Mehtab:: active below line due to wagon trip search showing old data in listing
		$query .= ' ORDER BY createdtime DESC';
		//echo $query;exit;
		$result = $db->pquery($query, $params);
		$noOfRows = $db->num_rows($result);

		$moduleModels = $matchingRecords = $leadIdsList = array();
		for($i=0; $i<$noOfRows; ++$i) {
			$row = $db->query_result_rowdata($result, $i);
			if ($row['setype'] === 'Leads') {
				$leadIdsList[] = $row['crmid'];
			}
		}
		$convertedInfo = Leads_Module_Model::getConvertedInfo($leadIdsList);

		for($i=0, $recordsCount = 0; $i<$noOfRows && $recordsCount<100; ++$i) {
			$row = $db->query_result_rowdata($result, $i);
			if ($row['setype'] === 'Leads' && $convertedInfo[$row['crmid']]) {
				continue;
			}
			if(Users_Privileges_Model::isPermitted($row['setype'], 'DetailView', $row['crmid'])) {
				$row['id'] = $row['crmid'];
				$moduleName = $row['setype'];
				if(!array_key_exists($moduleName, $moduleModels)) {
					$moduleModels[$moduleName] = Vtiger_Module_Model::getInstance($moduleName);
				}
				$moduleModel = $moduleModels[$moduleName];
				$modelClassName = Vtiger_Loader::getComponentClassName('Model', 'Record', $moduleName);
				$recordInstance = new $modelClassName();
				$matchingRecords[$moduleName][$row['id']] = $recordInstance->setData($row)->setModuleFromInstance($moduleModel);
				$recordsCount++;
			}
		}
		return $matchingRecords;
	}
	
	
		/**
	 * Function returns query for module record's search
	 * @param <String> $searchValue - part of record name (label column of crmentity table)
	 * @param <Integer> $parentId - parent record id
	 * @param <String> $parentModule - parent module name
	 * @return <String> - query
	 */
	public function getSearchRecordsQuery($searchValue,$searchFields, $parentId=false, $parentModule=false, $searchModule='') {
        $db = PearDatabase::getInstance();
        //$query = $db->convert2Sql("SELECT ".implode(',',$searchFields)." FROM vtiger_crmentity WHERE label LIKE ? AND vtiger_crmentity.deleted = 0", array("%$searchValue%"));
		if(!empty($searchModule))
		{
			$query = $db->convert2Sql("SELECT ".implode(',',$searchFields)." FROM vtiger_crmentity WHERE match(label) AGAINST label (‘?’) AND vtiger_crmentity.setype=? AND vtiger_crmentity.deleted = 0  ", array("$searchValue", "$searchModule"));
		}else{
			$query = $db->convert2Sql("SELECT ".implode(',',$searchFields)." FROM vtiger_crmentity WHERE match(label) AGAINST label (‘?’) AND vtiger_crmentity.deleted = 0", array("$searchValue"));
		}  
		return $query;
	}
	
	/**
	 * Function searches the records in the module, if parentId & parentModule
	 * is given then searches only those records related to them.
	 * @param <String> $searchValue - Search value
	 * @param <Integer> $parentId - parent recordId
	 * @param <String> $parentModule - parent module name
	 * @return <Array of Vtiger_Record_Model>
	 */
	public function searchRecord($searchValue, $parentId=false, $parentModule=false, $relatedModule=false, $searchModule='') {
			$searchFields = array('crmid','label','setype');
		if($relatedModule=='Procurement')
		{
			$matchingRecords = $this->getSearchResultForProcurement($searchValue, $this->getName());
			
		}
		elseif(!empty($searchValue) && empty($parentId) && empty($parentModule)) {
			$matchingRecords = Vtiger_Record_Model::getSearchResult($searchValue, $this->getName());
		} else if($parentId && $parentModule) {
			$db = PearDatabase::getInstance();
			$result = $db->pquery($this->getSearchRecordsQuery($searchValue,$searchFields, $parentId, $parentModule, $searchModule), array());
			$noOfRows = $db->num_rows($result);

			$moduleModels = array();
			$matchingRecords = array();
			for($i=0; $i<$noOfRows; ++$i) {
				$row = $db->query_result_rowdata($result, $i);
				if(Users_Privileges_Model::isPermitted($row['setype'], 'DetailView', $row['crmid'])){
					$row['id'] = $row['crmid'];
					$moduleName = $row['setype'];
					if(!array_key_exists($moduleName, $moduleModels)) {
						$moduleModels[$moduleName] = Vtiger_Module_Model::getInstance($moduleName);
					}
					$moduleModel = $moduleModels[$moduleName];
					$modelClassName = Vtiger_Loader::getComponentClassName('Model', 'Record', $moduleName);
					$recordInstance = new $modelClassName();
					$matchingRecords[$moduleName][$row['id']] = $recordInstance->setData($row)->setModuleFromInstance($moduleModel);
				}
			}
		}

		return $matchingRecords;
	}

	/**
	 * Function returns the Calendar Events for the module
	 * @param <String> $mode - upcoming/overdue mode
	 * @param <Vtiger_Paging_Model> $pagingModel - $pagingModel
	 * @param <String> $user - all/userid
	 * @param <String> $recordId - record id
	 * @return <Array>
	 */
	function getCalendarActivities($mode, $pagingModel, $user, $recordId = false) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$db = PearDatabase::getInstance();

		if (!$user) {
			$user = $currentUser->getId();
		}

		$nowInUserFormat = Vtiger_Datetime_UIType::getDisplayDateTimeValue(date('Y-m-d H:i:s'));
		$nowInDBFormat = Vtiger_Datetime_UIType::getDBDateTimeValue($nowInUserFormat);
		list($currentDate, $currentTime) = explode(' ', $nowInDBFormat);

		$focus = CRMEntity::getInstance($this->getName());
		$focus->id = $recordId;
		$entityIds = $focus->getRelatedContactsIds();
        $params = array();

		$query = "SELECT DISTINCT vtiger_crmentity.crmid, (CASE WHEN (crmentity2.crmid not like '') THEN crmentity2.crmid ELSE crmentity3.crmid END) AS parent_id, 
					(CASE WHEN (crmentity2.setype not like '') then crmentity2.setype ELSE crmentity3.setype END) AS crmentity2module, vtiger_crmentity.smownerid, vtiger_crmentity.setype, vtiger_activity.* FROM vtiger_activity
					INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_activity.activityid
					LEFT JOIN vtiger_seactivityrel ON vtiger_seactivityrel.activityid = vtiger_activity.activityid
					LEFT JOIN vtiger_cntactivityrel ON vtiger_cntactivityrel.activityid = vtiger_activity.activityid
					LEFT JOIN vtiger_crmentity as crmentity2 on (vtiger_seactivityrel.crmid = crmentity2.crmid AND vtiger_seactivityrel.crmid IS NOT NULL AND crmentity2.deleted = 0)
					LEFT JOIN vtiger_crmentity as crmentity3 on (vtiger_cntactivityrel.contactid = crmentity3.crmid AND vtiger_cntactivityrel.contactid IS NOT NULL AND crmentity3.deleted = 0)
					LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid";

		$query .= Users_Privileges_Model::getNonAdminAccessControlQuery('Calendar');

		$query .= " WHERE vtiger_crmentity.deleted=0
					AND (vtiger_activity.activitytype NOT IN ('Emails'))
					AND (vtiger_activity.status is NULL OR vtiger_activity.status NOT IN ('Completed', 'Deferred', 'Cancelled'))
					AND (vtiger_activity.eventstatus is NULL OR vtiger_activity.eventstatus NOT IN ('Held', 'Cancelled'))";

		if(!$currentUser->isAdminUser()) {
			$moduleFocus = CRMEntity::getInstance('Calendar');
			$condition = $moduleFocus->buildWhereClauseConditionForCalendar();
			if($condition) {
				$query .= ' AND '.$condition;
			}
		}

		if ($mode === 'upcoming') {
			$query .= " AND CASE WHEN vtiger_activity.activitytype='Task' THEN due_date >= ? ELSE CONCAT(due_date,' ',time_end) >= ? END";
            $params[] = $currentDate;
            $params[] = $nowInDBFormat;
		} elseif ($mode === 'overdue') {
			$query .= " AND CASE WHEN vtiger_activity.activitytype='Task' THEN due_date < ? ELSE CONCAT(due_date,' ',time_end) < ? END";
            $params[] = $currentDate;
            $params[] = $nowInDBFormat;
		}

		if ($recordId) {
			$query .= " AND (vtiger_seactivityrel.crmid = ?";
			array_push($params, $recordId);
			if ($entityIds) {
				$query .= " OR vtiger_cntactivityrel.contactid IN (" . generateQuestionMarks($entityIds) . "))";
                $params = array_merge($params, $entityIds);
			} else {
				$query .= ")";
			}
		}

		if ($user != 'all' && $user != '') {
			$query .= " AND vtiger_crmentity.smownerid = ?";
			array_push($params, $user);
		}

		$query .= " ORDER BY date_start, time_start LIMIT " . $pagingModel->getStartIndex() . ", " . ($pagingModel->getPageLimit() + 1);

		$result = $db->pquery($query, $params);
		$numOfRows = $db->num_rows($result);

		$groupsIds = Vtiger_Util_Helper::getGroupsIdsForUsers($currentUser->getId());
		$activities = array();
		$recordsToUnset = array();
		for ($i = 0; $i < $numOfRows; $i++) {
			$newRow = $db->query_result_rowdata($result, $i);
			$model = Vtiger_Record_Model::getCleanInstance('Calendar');
			$ownerId = $newRow['smownerid'];
			$currentUser = Users_Record_Model::getCurrentUserModel();
			$visibleFields = array('activitytype', 'date_start', 'time_start', 'due_date', 'time_end', 'assigned_user_id', 'visibility', 'smownerid', 'crmid');
			$visibility = true;
			if (in_array($ownerId, $groupsIds)) {
				$visibility = false;
			} else if ($ownerId == $currentUser->getId()) {
				$visibility = false;
			}
			if (!$currentUser->isAdminUser() && $newRow['activitytype'] != 'Task' && $newRow['visibility'] == 'Private' && $ownerId && $visibility) {
				foreach ($newRow as $data => $value) {
					if (in_array($data, $visibleFields) != -1) {
						unset($newRow[$data]);
					}
				}
				$newRow['subject'] = vtranslate('Busy', 'Events') . '*';
			}
			if ($newRow['activitytype'] == 'Task') {
				unset($newRow['visibility']);

				$due_date = $newRow["due_date"];
				$dayEndTime = "23:59:59";
				$EndDateTime = Vtiger_Datetime_UIType::getDBDateTimeValue($due_date . " " . $dayEndTime);
				$dueDateTimeInDbFormat = explode(' ', $EndDateTime);
				$dueTimeInDbFormat = $dueDateTimeInDbFormat[1];
				$newRow['time_end'] = $dueTimeInDbFormat;
			}

			if ($newRow['crmentity2module'] == 'Contacts') {
				$newRow['contact_id'] = $newRow['parent_id'];
				unset($newRow['parent_id']);
			}
			$model->setData($newRow);
			$model->setId($newRow['crmid']);
			$activities[$newRow['crmid']] = $model;
			if (!$currentUser->isAdminUser() && $newRow['activitytype'] == 'Task' && isToDoPermittedBySharing($newRow['crmid']) == 'no') {
				$recordsToUnset[] = $newRow['crmid'];
			}
		}

		$pagingModel->calculatePageRange($activities);
		if ($numOfRows > $pagingModel->getPageLimit()) {
			array_pop($activities);
			$pagingModel->set('nextPageExists', true);
		} else {
			$pagingModel->set('nextPageExists', false);
		}
		//after setting paging model, unsetting the records which has no permissions
		foreach ($recordsToUnset as $record) {
			unset($activities[$record]);
		}
		return $activities;
	}
}
