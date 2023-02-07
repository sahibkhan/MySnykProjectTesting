<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

/**
 * Vtiger ListView Model Class
 */
class Jobexpencereport_ListView_Model extends Vtiger_ListView_Model {



	/**
	 * Function to get the Module Model
	 * @return Vtiger_Module_Model instance
	 */
	public function getModule() {
		return $this->get('module');
	}

	/**
	 * Function to get the Quick Links for the List view of the module
	 * @param <Array> $linkParams
	 * @return <Array> List of Vtiger_Link_Model instances
	 */
	public function getSideBarLinks($linkParams) {
		$linkTypes = array('SIDEBARLINK', 'SIDEBARWIDGET');
		$moduleLinks = $this->getModule()->getSideBarLinks($linkParams);

		$listLinkTypes = array('LISTVIEWSIDEBARLINK', 'LISTVIEWSIDEBARWIDGET');
		$listLinks = Vtiger_Link_Model::getAllByType($this->getModule()->getId(), $listLinkTypes);

		if($listLinks['LISTVIEWSIDEBARLINK']) {
			foreach($listLinks['LISTVIEWSIDEBARLINK'] as $link) {
				$moduleLinks['SIDEBARLINK'][] = $link;
			}
		}

		if($listLinks['LISTVIEWSIDEBARWIDGET']) {
			foreach($listLinks['LISTVIEWSIDEBARWIDGET'] as $link) {
				$moduleLinks['SIDEBARWIDGET'][] = $link;
			}
		}

		return $moduleLinks;
	}

	/**
	 * Function to get the list of listview links for the module
	 * @param <Array> $linkParams
	 * @return <Array> - Associate array of Link Type to List of Vtiger_Link_Model instances
	 */
	public function getListViewLinks($linkParams) {
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$moduleModel = $this->getModule();

		$linkTypes = array('LISTVIEWBASIC', 'LISTVIEW', 'LISTVIEWSETTING');
		$links = Vtiger_Link_Model::getAllByType($moduleModel->getId(), $linkTypes, $linkParams);

		$basicLinks = $this->getBasicLinks();

		foreach($basicLinks as $basicLink) {
			$links['LISTVIEWBASIC'][] = Vtiger_Link_Model::getInstanceFromValues($basicLink);
		}

		$advancedLinks = $this->getAdvancedLinks();

		foreach($advancedLinks as $advancedLink) {
			$links['LISTVIEW'][] = Vtiger_Link_Model::getInstanceFromValues($advancedLink);
		}

		if($currentUserModel->isAdminUser()) {

			$settingsLinks = $this->getSettingLinks();
			foreach($settingsLinks as $settingsLink) {
				$links['LISTVIEWSETTING'][] = Vtiger_Link_Model::getInstanceFromValues($settingsLink);
			}
		}

		return $links;
	}

	/**
	 * Function to get the list of Mass actions for the module
	 * @param <Array> $linkParams
	 * @return <Array> - Associative array of Link type to List of  Vtiger_Link_Model instances for Mass Actions
	 */
	public function getListViewMassActions($linkParams) {
		$currentUserModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		$moduleModel = $this->getModule();

		$linkTypes = array('LISTVIEWMASSACTION');
		$links = Vtiger_Link_Model::getAllByType($moduleModel->getId(), $linkTypes, $linkParams);


		$massActionLinks = array();
		if($currentUserModel->hasModuleActionPermission($moduleModel->getId(), 'EditView')) {
			$massActionLinks[] = array(
				'linktype' => 'LISTVIEWMASSACTION',
				'linklabel' => 'LBL_EDIT',
				'linkurl' => 'javascript:Vtiger_List_Js.triggerMassEdit("index.php?module='.$moduleModel->get('name').'&view=MassActionAjax&mode=showMassEditForm");',
				'linkicon' => ''
			);
		}
		if($currentUserModel->hasModuleActionPermission($moduleModel->getId(), 'Delete')) {
			$massActionLinks[] = array(
				'linktype' => 'LISTVIEWMASSACTION',
				'linklabel' => 'LBL_DELETE',
				'linkurl' => 'javascript:Vtiger_List_Js.massDeleteRecords("index.php?module='.$moduleModel->get('name').'&action=MassDelete");',
				'linkicon' => ''
			);
		}

		if($moduleModel->isCommentEnabled()) {
			$massActionLinks[] = array(
				'linktype' => 'LISTVIEWMASSACTION',
				'linklabel' => 'LBL_ADD_COMMENT',
				'linkurl' => 'index.php?module='.$moduleModel->get('name').'&view=MassActionAjax&mode=showAddCommentForm',
				'linkicon' => ''
			);
		}

		foreach($massActionLinks as $massActionLink) {
			$links['LISTVIEWMASSACTION'][] = Vtiger_Link_Model::getInstanceFromValues($massActionLink);
		}

		return $links;
	}

	/**
	 * Function to get the list view header
	 * @return <Array> - List of Vtiger_Field_Model instances
	 */
	public function getListViewHeaders() {
        $listViewContoller = $this->get('listview_controller');
        $module = $this->getModule();
		
		$headerFieldModels = array();
		$headerFields = $listViewContoller->getListViewHeaderFields($module);
		
		foreach($headerFields as $fieldName => $webserviceField) {
			if($webserviceField && !in_array($webserviceField->getPresence(), array(0,2))) continue;
			$headerFieldModels[$fieldName] = Vtiger_Field_Model::getInstance($fieldName,$module);
		}
		return $headerFieldModels;
	}

	/**
	 * Function to get the list view entries
	 * @param Vtiger_Paging_Model $pagingModel
	 * @return <Array> - Associative array of record id mapped to Vtiger_Record_Model instance.
	 */
	public function getListViewEntries($pagingModel) {
		$db = PearDatabase::getInstance();

		$moduleName = $this->getModule()->get('name');
		$moduleFocus = CRMEntity::getInstance($moduleName);
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

		$queryGenerator = $this->get('query_generator');
		
		$listViewContoller = $this->get('listview_controller');

		$searchKey = $this->get('search_key');
		$searchValue = $this->get('search_value');
		$operator = $this->get('operator');
		if(!empty($searchKey)) {
			$queryGenerator->addUserSearchConditions(array('search_field' => $searchKey, 'search_text' => $searchValue, 'operator' => $operator));
		}

        $orderBy = $this->getForSql('orderby');
		$sortOrder = $this->getForSql('sortorder');

		//List view will be displayed on recently created/modified records
		if(empty($orderBy) && empty($sortOrder) && $moduleName != "Users"){
			$orderBy = 'modifiedtime';
			$sortOrder = 'DESC';
		}

        if(!empty($orderBy)){
            $columnFieldMapping = $moduleModel->getColumnFieldMapping();
            $orderByFieldName = $columnFieldMapping[$orderBy];
            $orderByFieldModel = $moduleModel->getField($orderByFieldName);
            if($orderByFieldModel && $orderByFieldModel->getFieldDataType() == Vtiger_Field_Model::REFERENCE_TYPE){
                //IF it is reference add it in the where fields so that from clause will be having join of the table
                $queryGenerator = $this->get('query_generator');
                $queryGenerator->addWhereField($orderByFieldName);
                //$queryGenerator->whereFields[] = $orderByFieldName;
            }
        }
		$listQuery = $this->getQuery();
		$current_user = Users_Record_Model::getCurrentUserModel();
		
		$count_parent_role = 0;
		if($current_user->get('is_admin')!='on')
		{
			$privileges   = $current_user->get('privileges');
			$parent_roles = $privileges->parent_roles;
			$count_parent_role = count($parent_roles);
		}
		
				
		if($_GET['module'] == 'Jobexpencereport' and $_GET['view'] == 'List' && $count_parent_role>3){
						
			//For invoicing coordinator
			if($current_user->get('roleid')=='H185')
			{
				$position = stripos($listQuery, 'WHERE');
				if($position) {
					$split = spliti('WHERE', $listQuery);
					
					$select_query = "SELECT vtiger_crmentityrel.crmid as jobid, vtiger_crmentity.smownerid, 
								vtiger_jobexpencereportcf.cf_1477, vtiger_jobexpencereportcf.cf_1479, 
								vtiger_jobexpencereportcf.cf_1453, vtiger_jobexpencereportcf.cf_1367, vtiger_jobexpencereportcf.cf_1455,
								vtiger_jobexpencereportcf.cf_1445, vtiger_jobexpencereportcf.cf_1359, vtiger_jobexpencereportcf.cf_1361,
								vtiger_jobexpencereportcf.cf_1365, vtiger_jobexpencereportcf.cf_1355, vtiger_jobexpencereportcf.cf_1357,
								vtiger_jobexpencereportcf.cf_1228, vtiger_jobexpencereportcf.cf_1230, vtiger_jobexpencereportcf.cf_1232,
								vtiger_jobexpencereportcf.cf_1234, vtiger_jobexpencereportcf.cf_1236, vtiger_jobexpencereportcf.cf_1238,
								vtiger_jobexpencereportcf.cf_1240, vtiger_jobexpencereportcf.cf_1242, vtiger_jobexpencereportcf.cf_1244,
								vtiger_jobexpencereportcf.cf_1246, vtiger_jobexpencereportcf.cf_1248, vtiger_jobexpencereportcf.cf_1250,
								vtiger_jobexpencereportcf.cf_1457, 
								vtiger_jobexpencereport.jobexpencereportid, vtiger_jobexpencereport.invoice_instruction_no,
								vtiger_jobexpencereport.gl_account, vtiger_jobexpencereport.ar_gl_account, vtiger_jobexpencereport.invoice_no
								
								FROM vtiger_jobexpencereport 
								INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid
								INNER JOIN vtiger_crmentity ON vtiger_jobexpencereport.jobexpencereportid = vtiger_crmentity.crmid
								INNER JOIN vtiger_crmentityrel ON vtiger_crmentity.crmid = vtiger_crmentityrel.relcrmid 
								LEFT JOIN vtiger_users ON vtiger_crmentity.smownerid = vtiger_users.id 
								LEFT JOIN vtiger_groups ON vtiger_crmentity.smownerid = vtiger_groups.groupid";
					
					$overRideQuery = $select_query . '  WHERE '.$split[1];
					$listQuery = $overRideQuery;
					
				}				
			}
			//For payables coordinator
			else if($current_user->get('roleid')=='H184')
			{				
				$position = stripos($listQuery, 'WHERE');
				if($position) {
					$split = spliti('WHERE', $listQuery);
					
					$select_query = "SELECT vtiger_crmentityrel.crmid as jobid, vtiger_crmentity.smownerid, vtiger_jobexpencereportcf.cf_1477, vtiger_jobexpencereportcf.cf_1479, vtiger_jobexpencereportcf.cf_1453, vtiger_jobexpencereportcf.cf_1367, vtiger_jobexpencereportcf.cf_1212, 
								vtiger_jobexpencereportcf.cf_1216, vtiger_jobexpencereportcf.cf_1210, vtiger_jobexpencereportcf.cf_1214, vtiger_jobexpencereportcf.cf_1337, vtiger_jobexpencereportcf.cf_1339, vtiger_jobexpencereportcf.cf_1341, 
								vtiger_jobexpencereportcf.cf_1343, vtiger_jobexpencereportcf.cf_1345, vtiger_jobexpencereportcf.cf_1222, vtiger_jobexpencereportcf.cf_1347, vtiger_jobexpencereportcf.cf_1349, vtiger_jobexpencereportcf.cf_1351, 
								vtiger_jobexpencereportcf.cf_1353, vtiger_jobexpencereportcf.cf_1453, vtiger_jobexpencereportcf.cf_1445, vtiger_jobexpencereportcf.cf_1359, vtiger_jobexpencereportcf.cf_1361, vtiger_jobexpencereportcf.cf_1363, 
								vtiger_jobexpencereportcf.cf_1365, vtiger_jobexpencereportcf.cf_1355, vtiger_jobexpencereportcf.cf_1357, vtiger_jobexpencereportcf.cf_1228, vtiger_jobexpencereportcf.cf_1230, vtiger_jobexpencereportcf.cf_1232, 
								vtiger_jobexpencereportcf.cf_1234, vtiger_jobexpencereportcf.cf_1222, vtiger_jobexpencereportcf.cf_1238, vtiger_jobexpencereportcf.cf_1240, vtiger_jobexpencereportcf.cf_1242, vtiger_jobexpencereportcf.cf_1244, 
								vtiger_jobexpencereportcf.cf_1246, vtiger_jobexpencereportcf.cf_1248, vtiger_jobexpencereportcf.cf_1250, vtiger_jobexpencereportcf.cf_1457, vtiger_jobexpencereport.jobexpencereportid,
								vtiger_jobexpencereport.b_confirmed_send_to_accounting_software, vtiger_jobexpencereportcf.cf_1975
								FROM vtiger_jobexpencereport 
								INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid
								INNER JOIN vtiger_crmentity ON vtiger_jobexpencereport.jobexpencereportid = vtiger_crmentity.crmid
								INNER JOIN vtiger_crmentityrel ON vtiger_crmentity.crmid = vtiger_crmentityrel.relcrmid 
								LEFT JOIN vtiger_users ON vtiger_crmentity.smownerid = vtiger_users.id 
								LEFT JOIN vtiger_groups ON vtiger_crmentity.smownerid = vtiger_groups.groupid";
					
					$overRideQuery = $select_query . '  WHERE '.$split[1];
					$listQuery = $overRideQuery;
					
				}
				
				/*
				$listQuery = "SELECT vtiger_crmentityrel.crmid as jobid, vtiger_crmentity.smownerid, vtiger_jobexpencereportcf.cf_1477, vtiger_jobexpencereportcf.cf_1479, vtiger_jobexpencereportcf.cf_1453, vtiger_jobexpencereportcf.cf_1367, vtiger_jobexpencereportcf.cf_1212, 
								vtiger_jobexpencereportcf.cf_1216, vtiger_jobexpencereportcf.cf_1210, vtiger_jobexpencereportcf.cf_1214, vtiger_jobexpencereportcf.cf_1337, vtiger_jobexpencereportcf.cf_1339, vtiger_jobexpencereportcf.cf_1341, 
								vtiger_jobexpencereportcf.cf_1343, vtiger_jobexpencereportcf.cf_1345, vtiger_jobexpencereportcf.cf_1222, vtiger_jobexpencereportcf.cf_1347, vtiger_jobexpencereportcf.cf_1349, vtiger_jobexpencereportcf.cf_1351, 
								vtiger_jobexpencereportcf.cf_1353, vtiger_jobexpencereportcf.cf_1453, vtiger_jobexpencereportcf.cf_1445, vtiger_jobexpencereportcf.cf_1359, vtiger_jobexpencereportcf.cf_1361, vtiger_jobexpencereportcf.cf_1363, 
								vtiger_jobexpencereportcf.cf_1365, vtiger_jobexpencereportcf.cf_1355, vtiger_jobexpencereportcf.cf_1357, vtiger_jobexpencereportcf.cf_1228, vtiger_jobexpencereportcf.cf_1230, vtiger_jobexpencereportcf.cf_1232, 
								vtiger_jobexpencereportcf.cf_1234, vtiger_jobexpencereportcf.cf_1222, vtiger_jobexpencereportcf.cf_1238, vtiger_jobexpencereportcf.cf_1240, vtiger_jobexpencereportcf.cf_1242, vtiger_jobexpencereportcf.cf_1244, 
								vtiger_jobexpencereportcf.cf_1246, vtiger_jobexpencereportcf.cf_1248, vtiger_jobexpencereportcf.cf_1250, vtiger_jobexpencereportcf.cf_1457, vtiger_jobexpencereport.jobexpencereportid,
								vtiger_jobexpencereport.b_confirmed_send_to_accounting_software
								FROM vtiger_jobexpencereport 
								INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid
								INNER JOIN vtiger_crmentity ON vtiger_jobexpencereport.jobexpencereportid = vtiger_crmentity.crmid
								INNER JOIN vtiger_crmentityrel ON vtiger_crmentity.crmid = vtiger_crmentityrel.relcrmid 
								LEFT JOIN vtiger_users ON vtiger_crmentity.smownerid = vtiger_users.id 
								LEFT JOIN vtiger_groups ON vtiger_crmentity.smownerid = vtiger_groups.groupid 
								WHERE vtiger_crmentity.deleted=0 AND vtiger_jobexpencereport.jobexpencereportid > 0 AND
								vtiger_jobexpencereport.b_send_to_payables_and_generate_payment_voucher=1 AND vtiger_jobexpencereportcf.cf_1457='Expence' ";
				*/			
				
			}			
		}
		else{
			if($_GET['module'] == 'Jobexpencereport' and $_GET['view'] == 'List')
			{
				if($current_user->get('is_admin')!='on')
				{
						$roles_users_arr  = array();
						$privileges = $current_user->get('privileges');
						$subordinate_roles_users = $privileges->subordinate_roles_users;
						
						foreach($subordinate_roles_users as $roles_users)
						{
							foreach($roles_users as $key => $users)
							{
								$roles_users_arr[] = $users;
							}
						}
						
						$user_ids = implode(',',$roles_users_arr);
						
						$position = stripos($listQuery, 'WHERE');
						if($position) {
							$split = spliti('WHERE', $listQuery);
							
							$select_query = "SELECT vtiger_crmentityrel.crmid as jobid, vtiger_crmentity.smownerid, vtiger_jobexpencereportcf.cf_1477, vtiger_jobexpencereportcf.cf_1479, vtiger_jobexpencereportcf.cf_1453, vtiger_jobexpencereportcf.cf_1367, vtiger_jobexpencereportcf.cf_1212, 
										vtiger_jobexpencereportcf.cf_1216, vtiger_jobexpencereportcf.cf_1210, vtiger_jobexpencereportcf.cf_1214, vtiger_jobexpencereportcf.cf_1337, vtiger_jobexpencereportcf.cf_1339, vtiger_jobexpencereportcf.cf_1341, 
										vtiger_jobexpencereportcf.cf_1343, vtiger_jobexpencereportcf.cf_1345, vtiger_jobexpencereportcf.cf_1222, vtiger_jobexpencereportcf.cf_1347, vtiger_jobexpencereportcf.cf_1349, vtiger_jobexpencereportcf.cf_1351, 
										vtiger_jobexpencereportcf.cf_1353, vtiger_jobexpencereportcf.cf_1453, vtiger_jobexpencereportcf.cf_1445, vtiger_jobexpencereportcf.cf_1359, vtiger_jobexpencereportcf.cf_1361, vtiger_jobexpencereportcf.cf_1363, 
										vtiger_jobexpencereportcf.cf_1365, vtiger_jobexpencereportcf.cf_1355, vtiger_jobexpencereportcf.cf_1357, vtiger_jobexpencereportcf.cf_1228, vtiger_jobexpencereportcf.cf_1230, vtiger_jobexpencereportcf.cf_1232, 
										vtiger_jobexpencereportcf.cf_1234, vtiger_jobexpencereportcf.cf_1222, vtiger_jobexpencereportcf.cf_1238, vtiger_jobexpencereportcf.cf_1240, vtiger_jobexpencereportcf.cf_1242, vtiger_jobexpencereportcf.cf_1244, 
										vtiger_jobexpencereportcf.cf_1246, vtiger_jobexpencereportcf.cf_1248, vtiger_jobexpencereportcf.cf_1250, vtiger_jobexpencereportcf.cf_1457, vtiger_jobexpencereport.jobexpencereportid,
										vtiger_jobexpencereport.b_confirmed_send_to_accounting_software, vtiger_jobexpencereportcf.cf_1975, vtiger_jobexpencereportcf.cf_1973
										FROM vtiger_jobexpencereport 
										INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid
										INNER JOIN vtiger_crmentity ON vtiger_jobexpencereport.jobexpencereportid = vtiger_crmentity.crmid
										INNER JOIN vtiger_crmentityrel ON vtiger_crmentity.crmid = vtiger_crmentityrel.relcrmid 
										LEFT JOIN vtiger_users ON vtiger_crmentity.smownerid = vtiger_users.id 
										LEFT JOIN vtiger_groups ON vtiger_crmentity.smownerid = vtiger_groups.groupid
										
										";
							
							$overRideQuery = $select_query . '  WHERE '.$split[1]. ' AND vtiger_crmentity.smownerid IN ('.$user_ids.')';
							$listQuery = $overRideQuery;
						}
						
						/*
						$query = 'SELECT vtiger_jobcf.cf_1186, vtiger_jobcf.cf_1198, vtiger_crmentity.smownerid, vtiger_jobcf.cf_1190, vtiger_jobcf.cf_1441, vtiger_jobcf.cf_1084, 
								vtiger_job.jobid FROM vtiger_job 
								INNER JOIN vtiger_crmentity ON vtiger_job.jobid = vtiger_crmentity.crmid 
								INNER JOIN vtiger_jobcf ON vtiger_job.jobid = vtiger_jobcf.jobid 
								LEFT JOIN vtiger_users ON vtiger_crmentity.smownerid = vtiger_users.id 
								LEFT JOIN vtiger_groups ON vtiger_crmentity.smownerid = vtiger_groups.groupid 
								LEFT JOIN vtiger_jobtask ON vtiger_jobtask.job_id = vtiger_jobcf.jobid 
								WHERE vtiger_crmentity.deleted=0 AND vtiger_job.jobid > 0 and vtiger_jobtask.user_id IN ('.$user_ids.') 
								';
						*/		
				}
			}
		}
				
		$sourceModule = $this->get('src_module');		
		if(!empty($sourceModule)) {
			if(method_exists($moduleModel, 'getQueryByModuleField')) {
				$overrideQuery = $moduleModel->getQueryByModuleField($sourceModule, $this->get('src_field'), $this->get('src_record'), $listQuery);
				if(!empty($overrideQuery)) {
					$listQuery = $overrideQuery;
				}
			}
		}
		
		$startIndex = $pagingModel->getStartIndex();
		$pageLimit = $pagingModel->getPageLimit();

		if(!empty($orderBy)) {
            if($orderByFieldModel && $orderByFieldModel->isReferenceField()){
                $referenceModules = $orderByFieldModel->getReferenceList();
                $referenceNameFieldOrderBy = array();
                foreach($referenceModules as $referenceModuleName) {
                    $referenceModuleModel = Vtiger_Module_Model::getInstance($referenceModuleName);
                    $referenceNameFields = $referenceModuleModel->getNameFields();
                    $columnList = array();
                    foreach($referenceNameFields as $nameField) {
                        $fieldModel = $referenceModuleModel->getField($nameField);
                        $columnList[] = $fieldModel->get('table').$orderByFieldModel->getName().'.'.$fieldModel->get('column');
                    }
                    if(count($columnList) > 1) {
                        $referenceNameFieldOrderBy[] = getSqlForNameInDisplayFormat(array('first_name'=>$columnList[0],'last_name'=>$columnList[1]),'Users').' '.$sortOrder;
                    } else {
                        $referenceNameFieldOrderBy[] = implode('', $columnList).' '.$sortOrder ;
                    }
                }
                $listQuery .= ' ORDER BY '. implode(',',$referenceNameFieldOrderBy);
            }else{
				if($_GET['module'] == 'Job' and $_GET['view'] == 'List' && $count_parent_role>3){	
                $listQuery .= '  ';
				}
				else if($_GET['module'] == 'Jobexpencereport' and $_GET['view'] == 'List' && $count_parent_role>3){	
                $listQuery .= ' ORDER BY '. $orderBy . ' ' .$sortOrder;
				}
				else{
					 $listQuery .= ' ORDER BY '. $orderBy . ' ' .$sortOrder;
				}
            }
		}
		
		$viewid = ListViewSession::getCurrentView($moduleName);
		ListViewSession::setSessionQuery($moduleName, $listQuery, $viewid);

		$listQuery .= " LIMIT $startIndex,".($pageLimit+1);
		
		$listResult = $db->pquery($listQuery, array());
		
		$listViewRecordModels = array();
		$listViewEntries =  $listViewContoller->getListViewRecords($moduleFocus,$moduleName, $listResult);
		
		$pagingModel->calculatePageRange($listViewEntries);

		if($db->num_rows($listResult) > $pageLimit){
			array_pop($listViewEntries);
			$pagingModel->set('nextPageExists', true);
		}else{
			$pagingModel->set('nextPageExists', false);
		}

		$index = 0;
		$check_vendor = array();
		foreach($listViewEntries as $recordId => $record) {
			$rawData = $db->query_result_rowdata($listResult, $index++);
			
			if($moduleName=='Jobexpencereport')
			{
				//For fetching vpo ids against job id on behalf of jer 
				$result_jer = $db->pquery("select *, rel2.crmid as costing_id from vtiger_crmentityrel as rel1 
							 INNER JOIN vtiger_crmentityrel AS rel2 ON rel2.crmid = rel1.relcrmid and rel2.relmodule='VPO' 
							 where rel1.crmid = ?", array($rawData['jobid']));
				$numRows_jer = $db->num_rows($result_jer);	
				if($numRows_jer>0)
				{
					for($ii=0; $ii< $db->num_rows($result_jer); $ii++ ) {
						$row_jer = $db->fetch_row($result_jer,$ii);	
						
						$result_2 = $db->pquery("SELECT * FROM `vtiger_vpocf` 
						INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_vpocf.vpoid
						where vtiger_vpocf.cf_1377 = ? and vtiger_crmentityrel.crmid=?", array($rawData['cf_1367'], $row_jer['costing_id']));
						$numRows_2 = $db->num_rows($result_2);
						if($numRows_2>0)
						{
						$row_vpo_agent_vendor = $db->fetch_array($result_2);
						$newRow['vpo_view_id'] = $row_vpo_agent_vendor['cf_1379'];
						$newRow['vpoid'] = $row_vpo_agent_vendor['vpoid'];
						}
						else{
						$newRow['vpo_view_id'] = '';	
						$newRow['vpoid'] = '';	
						}
						$rawData['vpoid'] = $newRow['vpoid'];
						
						$newRow['vpo_order_no'] = '';
						if(!in_array($row['cf_1367'], $check_vendor))
						{ 
							$check_vendor[]=$row['cf_1367'];
							if(!empty($newRow['vpo_view_id']))						
							{
								$newRow['vpo_order_no'] = $newRow['vpo_view_id'];
							}
						}
						$rawData['vpo_order_no'] = $newRow['vpo_order_no']; 							
					}
				}
				
				//$newRow['b_confirmed_send_to_accounting_software'] = ($row['b_confirmed_send_to_accounting_software']==1 ? 'Accept':'Select');							
				
			}
			
			$record['id'] = $recordId;
			
			$listViewRecordModels[$recordId] = $moduleModel->getRecordFromArray($record, $rawData);
		}
		//echo "<pre>";
		//print_r($listViewRecordModels);
		//exit;
		return $listViewRecordModels;
	}

	/**
	 * Function to get the list view entries
	 * @param Vtiger_Paging_Model $pagingModel
	 * @return <Array> - Associative array of record id mapped to Vtiger_Record_Model instance.
	 */
	public function getListViewCount() {
		$db = PearDatabase::getInstance();

		$queryGenerator = $this->get('query_generator');

        $searchKey = $this->get('search_key');
		$searchValue = $this->get('search_value');
		$operator = $this->get('operator');
		if(!empty($searchKey)) {
			$queryGenerator->addUserSearchConditions(array('search_field' => $searchKey, 'search_text' => $searchValue, 'operator' => $operator));
		}

		$listQuery = $this->getQuery();


		$sourceModule = $this->get('src_module');
		if(!empty($sourceModule)) {
			$moduleModel = $this->getModule();
			if(method_exists($moduleModel, 'getQueryByModuleField')) {
				$overrideQuery = $moduleModel->getQueryByModuleField($sourceModule, $this->get('src_field'), $this->get('src_record'), $listQuery);
				if(!empty($overrideQuery)) {
					$listQuery = $overrideQuery;
				}
			}
		}
		$position = stripos($listQuery, ' from ');
		if ($position) {
			$split = spliti(' from ', $listQuery);
			$splitCount = count($split);
			$listQuery = 'SELECT count(*) AS count ';
			for ($i=1; $i<$splitCount; $i++) {
				$listQuery = $listQuery. ' FROM ' .$split[$i];
			}
		}

		if($this->getModule()->get('name') == 'Calendar'){
			$listQuery .= ' AND activitytype <> "Emails"';
		}

		$listResult = $db->pquery($listQuery, array());
		return $db->query_result($listResult, 0, 'count');
	}

	function getQuery() {
		$queryGenerator = $this->get('query_generator');
		$listQuery = $queryGenerator->getQuery();
		return $listQuery;
	}
	/**
	 * Static Function to get the Instance of Vtiger ListView model for a given module and custom view
	 * @param <String> $moduleName - Module Name
	 * @param <Number> $viewId - Custom View Id
	 * @return Vtiger_ListView_Model instance
	 */
	public static function getInstance($moduleName, $viewId='0') {
		$db = PearDatabase::getInstance();
		$currentUser = vglobal('current_user');

		$modelClassName = Vtiger_Loader::getComponentClassName('Model', 'ListView', $moduleName);
		$instance = new $modelClassName();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$queryGenerator = new QueryGenerator($moduleModel->get('name'), $currentUser);
		$customView = new CustomView();
		if (!empty($viewId) && $viewId != "0") {
			$queryGenerator->initForCustomViewById($viewId);

			//Used to set the viewid into the session which will be used to load the same filter when you refresh the page
			$viewId = $customView->getViewId($moduleName);
		} else {
			$viewId = $customView->getViewId($moduleName);
			if(!empty($viewId) && $viewId != 0) {
				$queryGenerator->initForDefaultCustomView();
			} else {
				$entityInstance = CRMEntity::getInstance($moduleName);
				$listFields = $entityInstance->list_fields_name;
				$listFields[] = 'id';
				$queryGenerator->setFields($listFields);
			}
		}
		$controller = new ListViewController($db, $currentUser, $queryGenerator);

		return $instance->set('module', $moduleModel)->set('query_generator', $queryGenerator)->set('listview_controller', $controller);
	}

    /**
	 * Static Function to get the Instance of Vtiger ListView model for a given module and custom view
	 * @param <String> $value - Module Name
	 * @param <Number> $viewId - Custom View Id
	 * @return Vtiger_ListView_Model instance
	 */
	public static function getInstanceForPopup($value) {
		$db = PearDatabase::getInstance();
		$currentUser = vglobal('current_user');

		$modelClassName = Vtiger_Loader::getComponentClassName('Model', 'ListView', $value);
		$instance = new $modelClassName();
		$moduleModel = Vtiger_Module_Model::getInstance($value);

		$queryGenerator = new QueryGenerator($moduleModel->get('name'), $currentUser);

        $listFields = $moduleModel->getPopupFields();
        $listFields[] = 'id';
        $queryGenerator->setFields($listFields);

		$controller = new ListViewController($db, $currentUser, $queryGenerator);

		return $instance->set('module', $moduleModel)->set('query_generator', $queryGenerator)->set('listview_controller', $controller);
	}

	/*
	 * Function to give advance links of a module
	 *	@RETURN array of advanced links
	 */
	public function getAdvancedLinks(){
		$moduleModel = $this->getModule();
		$createPermission = Users_Privileges_Model::isPermitted($moduleModel->getName(), 'EditView');
		$advancedLinks = array();
		$importPermission = Users_Privileges_Model::isPermitted($moduleModel->getName(), 'Import');
		if($importPermission && $createPermission) {
			$advancedLinks[] = array(
							'linktype' => 'LISTVIEW',
							'linklabel' => 'LBL_IMPORT',
							'linkurl' => $moduleModel->getImportUrl(),
							'linkicon' => ''
			);
		}

		$exportPermission = Users_Privileges_Model::isPermitted($moduleModel->getName(), 'Export');
		if($exportPermission) {
			$advancedLinks[] = array(
					'linktype' => 'LISTVIEW',
					'linklabel' => 'LBL_EXPORT',
					'linkurl' => 'javascript:Vtiger_List_Js.triggerExportAction("'.$this->getModule()->getExportUrl().'")',
					'linkicon' => ''
				);
		}

		$duplicatePermission = Users_Privileges_Model::isPermitted($moduleModel->getName(), 'DuplicatesHandling');
		if($duplicatePermission) {
			$advancedLinks[] = array(
				'linktype' => 'LISTVIEWMASSACTION',
				'linklabel' => 'LBL_FIND_DUPLICATES',
				'linkurl' => 'Javascript:Vtiger_List_Js.showDuplicateSearchForm("index.php?module='.$moduleModel->getName().
								'&view=MassActionAjax&mode=showDuplicatesSearchForm")',
				'linkicon' => ''
			);
		}

		return $advancedLinks;
	}

	/*
	 * Function to get Setting links
	 * @return array of setting links
	 */
	public function getSettingLinks() {
		return $this->getModule()->getSettingLinks();
	}

	/*
	 * Function to get Basic links
	 * @return array of Basic links
	 */
	public function getBasicLinks(){
		$basicLinks = array();
		$moduleModel = $this->getModule();
		$createPermission = Users_Privileges_Model::isPermitted($moduleModel->getName(), 'EditView');
		if($createPermission) {
			$basicLinks[] = array(
					'linktype' => 'LISTVIEWBASIC',
					'linklabel' => 'LBL_ADD_RECORD',
					'linkurl' => $moduleModel->getCreateRecordUrl(),
					'linkicon' => ''
			);
		}
		return $basicLinks;
	}

	public function extendPopupFields($fieldsList) {
		$moduleModel = $this->get('module');
		$queryGenerator = $this->get('query_generator');
		$listFields = $moduleModel->getPopupFields();
		$listFields[] = 'id';
		$listFields = array_merge($listFields, $fieldsList);
		$queryGenerator->setFields($listFields);
		$this->get('query_generator', $queryGenerator);
	}
}
