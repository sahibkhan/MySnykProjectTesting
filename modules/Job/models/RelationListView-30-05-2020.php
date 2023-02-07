<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Job_RelationListView_Model extends Vtiger_RelationListView_Model {

	protected $relationModel = false;
	protected $parentRecordModel = false;
	protected $relatedModuleModel = false;

	public function setRelationModel($relation){
		$this->relationModel = $relation;
		return $this;
	}

	public function getRelationModel() {
		return $this->relationModel;
	}

	public function setParentRecordModel($parentRecord){
		$this->parentRecordModel = $parentRecord;
		return $this;
	}

	public function getParentRecordModel(){
		return $this->parentRecordModel;
	}

	public function setRelatedModuleModel($relatedModuleModel){
		$this->relatedModuleModel = $relatedModuleModel;
		return $this;
	}

	public function getRelatedModuleModel(){
		return $this->relatedModuleModel;
	}

	public function getCreateViewUrl(){
		$relationModel = $this->getRelationModel();
		$relatedModel = $relationModel->getRelationModuleModel();
		$parentRecordModule = $this->getParentRecordModel();
		$parentModule = $parentRecordModule->getModule();

		$createViewUrl = $relatedModel->getCreateRecordUrl().'&returnmode=showRelatedList&returntab_label='.$this->tab_label.
							'&returnrecord='.$parentRecordModule->getId().'&returnmodule='.$parentModule->getName().
							'&returnview=Detail&returnrelatedModuleName='.$this->getRelatedModuleModel()->getName().
							'&returnrelationId='.$relationModel->getId();

		if(in_array($relatedModel->getName(), getInventoryModules())){
			$createViewUrl.='&relationOperation=true';
		}
		//To keep the reference fieldname and record value in the url if it is direct relation
		if($relationModel->isDirectRelation()) {
			$relationField = $relationModel->getRelationField();
			$createViewUrl .='&'.$relationField->getName().'='.$parentRecordModule->getId();
		}

		//if parent module has auto fill data it should be automatically filled
		$autoFillData = $parentModule->getAutoFillModuleAndField($parentModule->getName());
		$relatedAutoFillData = $relatedModel->getAutoFillModuleAndField($parentModule->getName());

		if($autoFillData) {
			//There can be more than one auto-filled field.
			foreach ($autoFillData as $autoFilledField){
				$parentAutoFillField  = $autoFilledField['fieldname'];
				$parentAutoFillModule = $autoFilledField['module'];
				if($parentRecordModule->get($parentAutoFillField)) {
					if($relatedAutoFillData){
						foreach ($relatedAutoFillData as $relatedAutoFilledField){
							$relatedAutoFillFieldName = $relatedAutoFilledField['fieldname'];
							$relatedAutoFillModuleName = $relatedAutoFilledField['module'];
							if($parentAutoFillModule === $relatedAutoFillModuleName) {
								$createViewUrl .= '&'.$relatedAutoFillFieldName.'='.$parentRecordModule->get($parentAutoFillField);
							}
						}
					}
				}
			}
		}

		return $createViewUrl;
	}

	public function getCreateEventRecordUrl(){
		$relationModel = $this->getRelationModel();
		$relatedModel = $relationModel->getRelationModuleModel();
		$parentRecordModule = $this->getParentRecordModel();
		$parentModule = $parentRecordModule->getModule();

		$createViewUrl = $relatedModel->getCreateEventRecordUrl().'&returnmode=showRelatedList&returntab_label='.$relationModel->get('label').
							'&returnrecord='.$parentRecordModule->getId().'&returnmodule='.$parentModule->get('name').
							'&returnview=Detail&returnrelatedModuleName=Calendar'.
							'&returnrelationId='.$relationModel->getId();
		//To keep the reference fieldname and record value in the url if it is direct relation
		if($relationModel->isDirectRelation()) {
			$relationField = $relationModel->getRelationField();
			$createViewUrl .='&'.$relationField->getName().'='.$parentRecordModule->getId();
		}
		return $createViewUrl;
	}

	public function getCreateTaskRecordUrl(){
		$relationModel = $this->getRelationModel();
		$relatedModel = $relationModel->getRelationModuleModel();
		$parentRecordModule = $this->getParentRecordModel();
		$parentModule = $parentRecordModule->getModule();

		$createViewUrl = $relatedModel->getCreateTaskRecordUrl().'&returnmode=showRelatedList&returntab_label='.$relationModel->get('label').
							'&returnrecord='.$parentRecordModule->getId().'&returnmodule='.$parentModule->get('name').
							'&returnview=Detail&returnrelatedModuleName=Calendar'.
							'&returnrelationId='.$relationModel->getId();

		//To keep the reference fieldname and record value in the url if it is direct relation
		if($relationModel->isDirectRelation()) {
			$relationField = $relationModel->getRelationField();
			$createViewUrl .='&'.$relationField->getName().'='.$parentRecordModule->getId();
		}
		return $createViewUrl;
	}

	public function getLinks(){
		$relationModel = $this->getRelationModel();
		$actions = $relationModel->getActions();

		$selectLinks = $this->getSelectRelationLinks();
		foreach($selectLinks as $selectLinkModel) {
			$selectLinkModel->set('_selectRelation',true)->set('_module',$relationModel->getRelationModuleModel());
		}
		$addLinks = $this->getAddRelationLinks();

		$links = array_merge($selectLinks, $addLinks);
		$relatedLink = array();
		$relatedLink['LISTVIEWBASIC'] = $links;
		return $relatedLink;
	}

	public function getSelectRelationLinks() {
		$relationModel = $this->getRelationModel();
		$selectLinkModel = array();

		if(!$relationModel->isSelectActionSupported()) {
			return $selectLinkModel;
		}

		$relatedModel = $relationModel->getRelationModuleModel();

		$selectLinkList = array(
			array(
				'linktype' => 'LISTVIEWBASIC',
				'linklabel' => vtranslate('LBL_SELECT')." ".vtranslate('SINGLE_'.$relatedModel->getName(), $relatedModel->getName()),
				'linkurl' => '',
				'linkicon' => '',
				'linkmodule' => $relatedModel->getName(),
			)
		);


		foreach($selectLinkList as $selectLink) {
			$selectLinkModel[] = Vtiger_Link_Model::getInstanceFromValues($selectLink);
		}
		return $selectLinkModel;
	}

	public function getAddRelationLinks() {
		$relationModel = $this->getRelationModel();
		$addLinkModel = array();
		$addLinkList = array();
		if(!$relationModel->isAddActionSupported()) {
			return $addLinkModel;
		}
		$relatedModel = $relationModel->getRelationModuleModel();

		if($relatedModel->get('label') == 'Calendar'){
			if($relatedModel->isPermitted('CreateView')) {
				$addLinkList[] = array(
					'linktype' => 'LISTVIEWBASIC',
					'linklabel' => vtranslate('LBL_ADD_EVENT'),
					'linkurl' => $this->getCreateEventRecordUrl(),
					'linkicon' => '',
						'_linklabel' => '_add_event'// used in relatedlist.tpl to identify module to open quickcreate popup
				);
				$addLinkList[] = array(
					'linktype' => 'LISTVIEWBASIC',
					'linklabel' => vtranslate('LBL_ADD_TASK'),
					'linkurl' => $this->getCreateTaskRecordUrl(),
					'linkicon' => '',
					'_linklabel' => '_add_task'
				);
			}
		} else if ($relatedModel->get('label') == 'Documents') {
			$parentRecordModule = $this->getParentRecordModel();
			$parentModule = $parentRecordModule->getModule();
			$relationParameters = '&sourceModule='.$parentModule->get('name').'&sourceRecord='.$parentRecordModule->getId().'&relationOperation=true';

			if($relationModel->isDirectRelation()) {
				$relationField = $relationModel->getRelationField();
				$relationParameters .='&'.$relationField->getName().'='.$parentRecordModule->getId();
			}
			$vtigerDocumentTypes = array(
				array(
					'type' => 'I',
					'label' => 'LBL_INTERNAL_DOCUMENT_TYPE',
					'url' => 'index.php?module=Documents&view=EditAjax&type=I'.$relationParameters
				),
				array(
					'type' => 'E',
					'label' => 'LBL_EXTERNAL_DOCUMENT_TYPE',
					'url' => 'index.php?module=Documents&view=EditAjax&type=E'.$relationParameters
				),
				array(
					'type' => 'W',
					'label' => 'LBL_WEBDOCUMENT_TYPE',
					'url' => 'index.php?module=Documents&view=EditAjax&type=W'.$relationParameters
				)
			);
			$addLinkList[] = array(
				'linktype' => 'LISTVIEWBASIC',
				'linklabel' => 'Vtiger',
				'linkurl' => $this->getCreateViewUrl(),
				'linkicon' => 'Vtiger.png',
				'linkdropdowns' => $vtigerDocumentTypes,
				'linkclass' => 'addDocumentToVtiger',
			);
		}else{
			if (Users_Privileges_Model::isPermitted($relatedModel->getName(), 'CreateView')) {
				$addLinkList = array(
					array(
						'linktype' => 'LISTVIEWBASIC',
						// NOTE: $relatedModel->get('label') assuming it to be a module name - we need singular label for Add action.
						'linklabel' => vtranslate('LBL_ADD')." ".vtranslate('SINGLE_'.$relatedModel->getName(), $relatedModel->getName()),
						'linkurl' => $this->getCreateViewUrl(),
						'linkicon' => '',
					)
				);
			}
		}

		foreach($addLinkList as $addLink) {
			$addLinkModel[] = Vtiger_Link_Model::getInstanceFromValues($addLink);
		}
		return $addLinkModel;
	}

	public function getEntries($pagingModel, $JRER_TYPE='0', $QRY_GROUB_BY='0') {
		//echo "yes here in entries";
		
		$db = PearDatabase::getInstance();
		$parentModule = $this->getParentRecordModel()->getModule();
		$main_module = $parentModule->get('name');
		$relationModule = $this->getRelationModel()->getRelationModuleModel();
		$relationModuleName = $relationModule->get('name');

		//Custom Code
		//Mehtab Code
		$relation_module_name = $relationModule->get('name');
		
		if($relation_module_name=='Jobexpencereport' && ($main_module=='Job'))
		{
			$parentRecordModule = $this->getParentRecordModel();
			$parent_record_id =  $parentRecordModule->getId();
			$job_id 			  = $parent_record_id;
			$sourceModule 		= 'Job';	
			$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule);
			
			$current_user = Users_Record_Model::getCurrentUserModel();
			
			//$current_user->getId();
			
			$count_parent_role = 4;
			if($current_user->get('is_admin')!='on')
			{
				$privileges   = $current_user->get('privileges');
				$parent_roles_arr = $privileges->parent_roles;				
				$count_parent_role = count($parent_roles_arr);
				
				if($_REQUEST['module']=='Job' && $count_parent_role==0)
				{
					$role_id =  $current_user->get('roleid');
					$depth_role = "SELECT * FROM vtiger_role where roleid='".$role_id."' ";
					//$row_depth = mysql_fetch_array($depth_role);
					$result_role = $db->pquery($depth_role, array());
					$row_depth = $db->fetch_array($result_role);
					$count_parent_role = $row_depth['depth'];
				}
			}
			
			$user_query = '';	
					
			if($count_parent_role>3)
			{							
				//For Bakhytgul access, right now her role is GM. Later we can change.
				if($current_user->get('roleid')=='H3' || $current_user->get('roleid')=='H2')
				{
					$user_query =' AND vtiger_jobexpencereport.owner_id = "'.$job_info->get('assigned_user_id').'" ' ;
				}
				else if($current_user->get('roleid')=='H185')
				{
					//$user_query = ' AND vtiger_jobexpencereportcf.cf_1248=1 ORDER BY vtiger_jobexpencereport.invoice_instruction_no ASC';
					$user_query = ' AND vtiger_jobexpencereportcf.cf_1248=1 ';
				}
				else if($current_user->get('roleid')=='H184')
				{
					$user_query = ' AND vtiger_jobexpencereport.b_head_of_department_approval_status="Approved" AND b_send_to_payables_and_generate_payment_voucher=1 ';
				}				
				else{
					if($job_info->get('assigned_user_id')!=$current_user->getId())
					{
						$user_query =' AND vtiger_jobexpencereport.owner_id = "'.$current_user->getId().'" AND vtiger_jobexpencereport.user_id = "'.$current_user->getId().'" ' ;
					}
					else{
						$user_query =' AND vtiger_jobexpencereport.owner_id = "'.$current_user->getId().'" ' ;
					}
				}
			}
			else{
				$user_query =' AND vtiger_jobexpencereport.owner_id = "'.$job_info->get('assigned_user_id').'" ' ;
			}
			
			if($JRER_TYPE!='0')
			{				
				$user_query .='  AND vtiger_jobexpencereportcf.cf_1457="'.$JRER_TYPE.'"  ';
			}
			
		}
		

		$relatedColumnFields = $relationModule->getConfigureRelatedListFields();
		if(count($relatedColumnFields) <= 0){
			$relatedColumnFields = $relationModule->getRelatedListFields();
		}

		if($relationModuleName == 'Calendar') {
			//Adding visibility in the related list, showing records based on the visibility
			$relatedColumnFields['visibility'] = 'visibility';
		}

		if($relationModuleName == 'PriceBooks') {
			//Adding fields in the related list
			$relatedColumnFields['unit_price'] = 'unit_price';
			$relatedColumnFields['listprice'] = 'listprice';
			$relatedColumnFields['currency_id'] = 'currency_id';
		}

		$query = $this->getRelationQuery();

		
		if ($this->get('whereCondition') && is_array($this->get('whereCondition'))) {
			$currentUser = Users_Record_Model::getCurrentUserModel();
			$queryGenerator = new QueryGenerator($relationModuleName, $currentUser);
			$queryGenerator->setFields(array_values($relatedColumnFields));
			$whereCondition = $this->get('whereCondition');
			
			foreach ($whereCondition as $fieldName => $fieldValue) {
				if (is_array($fieldValue)) {
					$comparator = $fieldValue[1];
					$searchValue = $fieldValue[2];
					$type = $fieldValue[3];
					if ($type == 'time') {
						$searchValue = Vtiger_Time_UIType::getTimeValueWithSeconds($searchValue);
					}
					
					$queryGenerator->addCondition($fieldName, $searchValue, $comparator, "AND");
				}
			}
			
			$whereQuerySplit = split("WHERE", $queryGenerator->getWhereClause());
			$query.=" AND " . $whereQuerySplit[1];
			
		}		

		$startIndex = $pagingModel->getStartIndex();
		$pageLimit = $pagingModel->getPageLimit();

		$orderBy = $this->getForSql('orderby');
		$sortOrder = $this->getForSql('sortorder');

		if($orderBy) {

			$orderByFieldModuleModel = $relationModule->getFieldByColumn($orderBy);
			if($orderByFieldModuleModel && $orderByFieldModuleModel->isReferenceField()) {
				//If reference field then we need to perform a join with crmentity with the related to field
				$queryComponents = $split = preg_split('/ where /i', $query);
				$selectAndFromClause = $queryComponents[0];
				$whereCondition = $queryComponents[1];
				$qualifiedOrderBy = 'vtiger_crmentity'.$orderByFieldModuleModel->get('column');
				$selectAndFromClause .= ' LEFT JOIN vtiger_crmentity AS '.$qualifiedOrderBy.' ON '.
										$orderByFieldModuleModel->get('table').'.'.$orderByFieldModuleModel->get('column').' = '.
										$qualifiedOrderBy.'.crmid ';
				$query = $selectAndFromClause.' WHERE '.$whereCondition;
				$query .= ' ORDER BY '.$qualifiedOrderBy.'.label '.$sortOrder;
			} elseif($orderByFieldModuleModel && $orderByFieldModuleModel->isOwnerField()) {
				 $query .= ' ORDER BY COALESCE(CONCAT(vtiger_users.first_name,vtiger_users.last_name),vtiger_groups.groupname) '.$sortOrder;
			} else{
				// Qualify the the column name with table to remove ambugity
				$qualifiedOrderBy = $orderBy;
				$orderByField = $relationModule->getFieldByColumn($orderBy);
				if ($orderByField) {
					$qualifiedOrderBy = $relationModule->getOrderBySql($qualifiedOrderBy);
				}
				if($qualifiedOrderBy == 'vtiger_activity.date_start' && ($relationModuleName == 'Calendar' || $relationModuleName == 'Emails')) {
					$qualifiedOrderBy = "str_to_date(concat(vtiger_activity.date_start,vtiger_activity.time_start),'%Y-%m-%d %H:%i:%s')";
				}
				$query = "$query ORDER BY $qualifiedOrderBy $sortOrder";
			}
		} else if($relationModuleName == 'HelpDesk' && empty($orderBy) && empty($sortOrder) && $moduleName != "Users") {
			$query .= ' ORDER BY vtiger_crmentity.modifiedtime DESC';
		}

		if($relation_module_name=='Jobexpencereport' && ($main_module=='Job'))
		{
			//$query = str_replace('OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid',' ', $query);
			//$query = str_replace('OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid',' ', $query);
			
			if($current_user->get('roleid')=='H185')
			{
				$query = str_replace('OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid',' ', $query);
				$limitQuery = $query .' '.$user_query;
				
			}
			else{
				$vt_tring =  stristr($query, 'vt_tmp_u');
				$temp_tbl = preg_split("/[\s]+/", $vt_tring);
				$tbl_tmp_name = $temp_tbl[0];
				
				$query = str_replace('INNER JOIN '.trim($tbl_tmp_name).' '.trim($tbl_tmp_name).' ON '.trim($tbl_tmp_name).'.id = vtiger_crmentity.smownerid', 
							' LEFT JOIN '.trim($tbl_tmp_name).' '.trim($tbl_tmp_name).' ON '.trim($tbl_tmp_name).'.id = vtiger_crmentity.smownerid ',
							$query);
				
				$limitQuery = $query .' '.$user_query;
				
			}
		
			if($QRY_GROUB_BY!=0)
			{
				//group by location , department
				$limitQuery .= '   GROUP BY vtiger_jobexpencereportcf.cf_1477, vtiger_jobexpencereportcf.cf_1479 ';
			}
			
			
		}
		else{
			$limitQuery = $query .' LIMIT '.$startIndex.','.$pageLimit;
		}
		
		//$limitQuery = $query .' LIMIT '.$startIndex.','.$pageLimit;
		$result = $db->pquery($limitQuery, array());
		$relatedRecordList = array();
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$groupsIds = Vtiger_Util_Helper::getGroupsIdsForUsers($currentUser->getId());
		$recordsToUnset = array();
		for($i=0; $i< $db->num_rows($result); $i++ ) {
			$row = $db->fetch_row($result,$i);
			$newRow = array();
			foreach($row as $col=>$val){
				if(array_key_exists($col,$relatedColumnFields)){
					$newRow[$relatedColumnFields[$col]] = $val;
				}
			}
			//To show the value of "Assigned to"
			$ownerId = $row['smownerid'];
			$newRow['assigned_user_id'] = $row['smownerid'];
			if($relationModuleName == 'Calendar') {
				$visibleFields = array('activitytype','date_start','time_start','due_date','time_end','assigned_user_id','visibility','smownerid','parent_id');
				$visibility = true;
				if(in_array($ownerId, $groupsIds)) {
					$visibility = false;
				} else if($ownerId == $currentUser->getId()){
					$visibility = false;
				}
				if(!$currentUser->isAdminUser() && $newRow['activitytype'] != 'Task' && $newRow['visibility'] == 'Private' && $ownerId && $visibility) {
					foreach($newRow as $data => $value) {
						if(in_array($data, $visibleFields) != -1) {
							unset($newRow[$data]);
						}
					}
					$newRow['subject'] = vtranslate('Busy','Events').'*';
				}
				if($newRow['activitytype'] == 'Task') {
					unset($newRow['visibility']);
				}

			}
			
		
			if($relationModule->get('name')=='Jobexpencereport')
			{
				//INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = `vtiger_jobexpencereport`.jobexpencereportid
				$result_jobexp = $db->pquery("SELECT * FROM `vtiger_jobexpencereport` 
						
						where `vtiger_jobexpencereport`.jobexpencereportid = ? ", array($row['crmid']));
				$numRows_jobexp = $db->num_rows($result_jobexp);
				
				if($numRows_jobexp>0)
				{
					$row_jobexp = $db->fetch_array($result_jobexp);
					$newRow['invoice_instruction_no'] = $row_jobexp['invoice_instruction_no'];
					$newRow['preview_instruction_no'] = $row_jobexp['preview_instruction_no'];
				}
				else{
					$newRow['invoice_instruction_no'] = '';
					$newRow['preview_instruction_no'] = '';
				}
				
				$newRow['gl_account'] = $row['gl_account'];
				$newRow['ar_gl_account'] = $row['ar_gl_account'];	
				$newRow['accept_generate_invoice'] = ($row['accept_generate_invoice']==1 ? 'Accept':'Select');
				$newRow['invoice_no'] = $row['invoice_no'];
				$newRow['b_send_to_head_of_department_for_approval'] = ($row['b_send_to_head_of_department_for_approval']==1?'Yes':'No');
				$newRow['b_head_of_department_approval_status'] = $row['b_head_of_department_approval_status'];
				$newRow['b_send_to_payables_and_generate_payment_voucher'] = ($row['b_send_to_payables_and_generate_payment_voucher']==1 ? 'Accept':'Select');
				$newRow['b_payables_approval_status'] = $row['b_payables_approval_status'];	
				
				$newRow['b_confirmed_send_to_accounting_software'] = ($row['b_confirmed_send_to_accounting_software']==1 ? 'Accept':'Select');
				$newRow['fleettrip_id'] = $row['fleettrip_id'];
				$newRow['wagontrip_id'] = $row['wagontrip_id'];
				
				$newRow['vpo_view_id'] = '';	
				$newRow['vpoid'] = '';
				$newRow['vpo_order_no'] ='';				
				
						
			}
			

			$record = Vtiger_Record_Model::getCleanInstance($relationModule->get('name'));
			$record->setData($newRow)->setModuleFromInstance($relationModule)->setRawData($row);
			$record->setId($row['crmid']);
			$relatedRecordList[$row['crmid']] = $record;
			if($relationModuleName == 'Calendar' && !$currentUser->isAdminUser() && $newRow['activitytype'] == 'Task' && isToDoPermittedBySharing($row['crmid']) == 'no') { 
				$recordsToUnset[] = $row['crmid'];
			}
		}
		$pagingModel->calculatePageRange($relatedRecordList);

		$nextLimitQuery = $query. ' LIMIT '.($startIndex+$pageLimit).' , 1';
		$nextPageLimitResult = $db->pquery($nextLimitQuery, array());
		if($db->num_rows($nextPageLimitResult) > 0){
			$pagingModel->set('nextPageExists', true);
		}else{
			$pagingModel->set('nextPageExists', false);
		}
		//setting related list view count before unsetting permission denied records - to make sure paging should not fail
		$pagingModel->set('_relatedlistcount', count($relatedRecordList));
		foreach($recordsToUnset as $record) {
			unset($relatedRecordList[$record]);
		}

		return $relatedRecordList;
	}

	public function getHeaders() {
		$relationModel = $this->getRelationModel();
		$relatedModuleModel = $relationModel->getRelationModuleModel();

		$summaryFieldsList = $relatedModuleModel->getHeaderAndSummaryViewFieldsList();

		$headerFields = array();
		if(count($summaryFieldsList) > 0) {
			foreach($summaryFieldsList as $fieldName => $fieldModel) {
				$headerFields[$fieldName] = $fieldModel;
			}
		} else {
			$headerFieldNames = $relatedModuleModel->getRelatedListFields();
			foreach($headerFieldNames as $fieldName) {
				$headerFields[$fieldName] = $relatedModuleModel->getField($fieldName);
			}
		}

		$nameFields = $relatedModuleModel->getNameFields();
		foreach($nameFields as $fieldName){
			if(!$headerFields[$fieldName]) {
				$headerFields[$fieldName] = $relatedModuleModel->getField($fieldName);
			}
		}

		return $headerFields;
	}

	/**
	 * Function to get Relation query
	 * @return <String>
	 */
	public function getRelationQuery() {
		$relationModel = $this->getRelationModel();

		if(!empty($relationModel) && $relationModel->get('name') != NULL){
			$recordModel = $this->getParentRecordModel();
			$query = $relationModel->getQuery($recordModel);
			return $query;
		}
		$relatedModuleModel = $this->getRelatedModuleModel();
		$relatedModuleName = $relatedModuleModel->getName();

		$relatedModuleBaseTable = $relatedModuleModel->basetable;
		$relatedModuleEntityIdField = $relatedModuleModel->basetableid;

		$parentModuleModel = $relationModel->getParentModuleModel();
		$parentModuleBaseTable = $parentModuleModel->basetable;
		$parentModuleEntityIdField = $parentModuleModel->basetableid;
		$parentRecordId = $this->getParentRecordModel()->getId();
		$parentModuleDirectRelatedField = $parentModuleModel->get('directRelatedFieldName');

		$relatedModuleFields = array_keys($this->getHeaders());
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$queryGenerator = new QueryGenerator($relatedModuleName, $currentUserModel);
		$queryGenerator->setFields($relatedModuleFields);

		$query = $queryGenerator->getQuery();

		$queryComponents = preg_split('/ FROM /i', $query);
		$query = $queryComponents[0].' ,vtiger_crmentity.crmid FROM '.$queryComponents[1];

		$whereSplitQueryComponents = preg_split('/ WHERE /i', $query);
		$joinQuery = ' INNER JOIN '.$parentModuleBaseTable.' ON '.$parentModuleBaseTable.'.'.$parentModuleDirectRelatedField." = ".$relatedModuleBaseTable.'.'.$relatedModuleEntityIdField;

		$query = "$whereSplitQueryComponents[0] $joinQuery WHERE $parentModuleBaseTable.$parentModuleEntityIdField = $parentRecordId AND $whereSplitQueryComponents[1]";
		
		return $query;
	}

	public static function getInstance($parentRecordModel, $relationModuleName, $label=false) {
		$parentModuleName = $parentRecordModel->getModule()->get('name');
		$className = Vtiger_Loader::getComponentClassName('Model', 'RelationListView', $parentModuleName);
		$instance = new $className();

		$parentModuleModel = $parentRecordModel->getModule();
		$relatedModuleModel = Vtiger_Module_Model::getInstance($relationModuleName);
		$instance->setRelatedModuleModel($relatedModuleModel);

		$relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relatedModuleModel, $label);
		$instance->setParentRecordModel($parentRecordModel);

		if(!$relationModel){
			$relatedModuleName = $relatedModuleModel->getName();
			$parentModuleModel = $instance->getParentRecordModel()->getModule();
			$referenceFieldOfParentModule = $parentModuleModel->getFieldsByType('reference');
			foreach ($referenceFieldOfParentModule as $fieldName=>$fieldModel) {
				$refredModulesOfReferenceField = $fieldModel->getReferenceList();
				if(in_array($relatedModuleName, $refredModulesOfReferenceField)){
					$relationModelClassName = Vtiger_Loader::getComponentClassName('Model', 'Relation', $parentModuleModel->getName());
					$relationModel = new $relationModelClassName();
					$relationModel->setParentModuleModel($parentModuleModel)->setRelationModuleModel($relatedModuleModel);
					$parentModuleModel->set('directRelatedFieldName',$fieldModel->get('column'));
				}
			}
		}
		if(!$relationModel){
			$relationModel = false;
		}
		$instance->setRelationModel($relationModel);
		return $instance;
	}

	/**
	 * Function to get Total number of record in this relation
	 * @return <Integer>
	 */
	public function getRelatedEntriesCount() {
		$db = PearDatabase::getInstance();
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$realtedModuleModel = $this->getRelatedModuleModel();
		$relatedModuleName = $realtedModuleModel->getName();
		$relationQuery = $this->getRelationQuery();
		$relationQuery = preg_replace("/[ \t\n\r]+/", " ", $relationQuery);
		$position = stripos($relationQuery,' from ');
		if ($position) {
			$split = preg_split('/ FROM /i', $relationQuery);
			$splitCount = count($split);
			if($relatedModuleName == 'Calendar') {
				$relationQuery = 'SELECT DISTINCT vtiger_crmentity.crmid, vtiger_activity.activitytype ';
			} else {
				$relationQuery = 'SELECT COUNT(DISTINCT vtiger_crmentity.crmid) AS count';
			}
			for ($i=1; $i<$splitCount; $i++) {
				$relationQuery = $relationQuery. ' FROM ' .$split[$i];
			}
		}
		if(strpos($relationQuery,' GROUP BY ') !== false){
			$parts = explode(' GROUP BY ',$relationQuery);
			$relationQuery = $parts[0];
		}
		$result = $db->pquery($relationQuery, array());
		if ($result) {
			if($relatedModuleName == 'Calendar') {
				$count = 0;
				for($i=0;$i<$db->num_rows($result);$i++) {
					$id = $db->query_result($result, $i, 'crmid');
					$activityType = $db->query_result($result, $i, 'activitytype');
					if(!$currentUser->isAdminUser() && $activityType == 'Task' && isToDoPermittedBySharing($id) == 'no') {
						continue;
					} else {
						$count++;
					}
				}
				return $count;
			} else {
				return $db->query_result($result, 0, 'count');
			}
		} else {
			return 0;
		}
	}

	/**
	 * Function to update relation query
	 * @param <String> $relationQuery
	 * @return <String> $updatedQuery
	 */
	public function updateQueryWithWhereCondition($relationQuery) {
		$condition = '';

		$whereCondition = $this->get("whereCondition");
		$count = count($whereCondition);
		if ($count > 1) {
			$appendAndCondition = true;
		}

		$i = 1;
		foreach ($whereCondition as $fieldName => $fieldValue) {
			if(is_array($fieldValue)){
				$fieldColumn = $fieldValue[0];
				$comparator = $fieldValue[1];
				$value = $fieldValue[2];
				if($comparator == "c"){
					$condition .= "$fieldColumn like '%$value%' ";
				}else{
					$condition .= "$fieldColumn = '$value' ";
				}
			}else {
				$condition .= " $fieldName = '$fieldValue' ";
			}
			if ($appendAndCondition && ($i++ != $count)) {
				$condition .= " AND ";
			}
		}

		$pos = stripos($relationQuery, 'where');
		if ($pos) {
			$split = preg_split('/where/i', $relationQuery);
			$updatedQuery = $split[0].' WHERE '.$split[1].' AND '.$condition;
		} else {
			$updatedQuery = $relationQuery.' WHERE '.$condition;
		}
		return $updatedQuery;
	}

	public function getCurrencySymbol($recordId, $fieldModel) {
		$db = PearDatabase::getInstance();
		$moduleName = $fieldModel->getModuleName();
		$fieldName = $fieldModel->get('name');
		$tableName = $fieldModel->get('table');
		$columnName = $fieldModel->get('column');

		if(($fieldName == 'unit_price') && ($moduleName == 'Products' || $moduleName == 'Services')) {
			$query = "SELECT currency_symbol FROM vtiger_currency_info WHERE id = (";
			if($moduleName == 'Products') 
				$query .= "SELECT currency_id FROM vtiger_products WHERE productid = ?)";
			else if($moduleName == 'Services')
				$query .= "SELECT currency_id FROM vtiger_service WHERE serviceid = ?)";

			$result = $db->pquery($query, array($recordId));
			return $db->query_result($result, 0, 'currency_symbol');
		} else if(($tableName == 'vtiger_invoice' || $tableName == 'vtiger_quotes' || $tableName == 'vtiger_purchaseorder' || $tableName == 'vtiger_salesorder') &&
			($columnName == 'total' || $columnName == 'subtotal' || $columnName == 'discount_amount' || $columnName == 's_h_amount' || $columnName == 'paid' ||
			$columnName == 'balance' || $columnName == 'received' || $columnName == 'listprice' || $columnName == 'adjustment' || $columnName == 'pre_tax_total')) {
			$focus = CRMEntity::getInstance($moduleName);
			$query = "SELECT currency_symbol FROM vtiger_currency_info WHERE id = ( SELECT currency_id FROM ".$tableName." WHERE ".$focus->table_index." = ? )";
			$result = $db->pquery($query, array($recordId));
			return $db->query_result($result, 0, 'currency_symbol');
		} else {
			$fieldInfo = $fieldModel->getFieldInfo();
			return $fieldInfo['currency_symbol'];
		}
	}

}