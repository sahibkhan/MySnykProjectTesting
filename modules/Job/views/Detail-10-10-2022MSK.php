<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Job_Detail_View extends Vtiger_Detail_View {
	protected $record = false;
	protected $isAjaxEnabled = null;

	function __construct() {
		parent::__construct();
		
		$this->exposeMethod('showRelatedList');
	}

	public function requiresPermission(Vtiger_Request $request){
		$permissions = parent::requiresPermission($request);
		$mode = $request->getMode();
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView', 'record_parameter' => 'record');
		if(!empty($mode)) {
			switch ($mode) {
				case 'showModuleDetailView':
				case 'showModuleSummaryView':
				case 'showModuleBasicView':
					$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView', 'record_parameter' => 'record');
					break;
				case 'showRecentComments':
				case 'showChildComments':
					$permissions[] = array('module_parameter' => 'custom_module', 'action' => 'DetailView');
					$request->set('custom_module', 'ModComments');
					break;
				case 'showRelatedList':
				case 'showRelatedRecords':
					$permissions[] = array('module_parameter' => 'relatedModule', 'action' => 'DetailView');
					break;
				case 'getActivities':
					$permissions[] = array('module_parameter' => 'custom_module', 'action' => 'DetailView');
					$request->set('custom_module', 'Calendar');
					break;
				default:
					break;
			}
		}
		return $permissions;
	}
	
	function checkPermission(Vtiger_Request $request) {
        parent::checkPermission($request);
		$moduleName = $request->getModule();
		$recordId = $request->get('record');

		$nonEntityModules = array('Users', 'Events', 'Calendar', 'Portal', 'Reports', 'Rss', 'EmailTemplates');
		if ($recordId && !in_array($moduleName, $nonEntityModules)) {
			$recordEntityName = getSalesEntityType($recordId);
			if ($recordEntityName !== $moduleName) {
				throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
			}
		}
		return true;
	}

	function preProcess(Vtiger_Request $request, $display=true) {
		parent::preProcess($request, false);

		$recordId = $request->get('record');
		$moduleName = $request->getModule();
		if(!$this->record){
			$this->record = Vtiger_DetailView_Model::getInstance($moduleName, $recordId);
		}
		$recordModel = $this->record->getRecord();

		//For Job owner or assigned user
		global $adb;
		
		$current_user = Users_Record_Model::getCurrentUserModel();

		$recordStrucure = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_DETAIL);
		$summaryInfo = array();
		// Take first block information as summary information
		$stucturedValues = $recordStrucure->getStructure();
		foreach($stucturedValues as $blockLabel=>$fieldList) {
			$summaryInfo[$blockLabel] = $fieldList;
			break;
		}

		$detailViewLinkParams = array('MODULE'=>$moduleName,'RECORD'=>$recordId);

		$detailViewLinks = $this->record->getDetailViewLinks($detailViewLinkParams);
		$navigationInfo = ListViewSession::getListViewNavigation($recordId);

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('NAVIGATION', $navigationInfo);

		//For job owner or assigned user
		$viewer->assign('JRER_USER_ID_FLAG', 'yes');
		$viewer->assign('JOB_OWNER_FLAG', TRUE);
		$job_info_detail = $recordModel;
		if($current_user->get('is_admin')!='on')
		{
		
			$privileges   = $current_user->get('privileges');
			$parent_roles_arr = $privileges->parent_roles;				
			$count_parent_role = count($parent_roles_arr);
			
			if($count_parent_role==0)
			{
				$role_id =  $current_user->get('roleid');
				$depth_role = $adb->pquery("SELECT * FROM vtiger_role where roleid='".$role_id."' ");
				$row_depth = $adb->fetch_array($depth_role);
				$count_parent_role = $row_depth['depth'];
			}

			if($job_info_detail->get('assigned_user_id')==$current_user->getId() || $current_user->get('roleid')=='H3'  
				|| $count_parent_role <= 3 || $current_user->get('roleid')=='H2' || $current_user->get('roleid')=='H185' || $current_user->get('roleid')=='H184')
				{
					$viewer->assign('JRER_USER_ID_FLAG', 'yes');
					$viewer->assign('JOB_OWNER_FLAG', TRUE);
				}
				else{
					$viewer->assign('JRER_USER_ID_FLAG', 'no');
					$viewer->assign('SUB_JRER_USER_ID', $current_user->id);
					$viewer->assign('JOB_OWNER_FLAG', FALSE);
				}
		}

		//Intially make the prev and next records as null
		$prevRecordId = null;
		$nextRecordId = null;
		$found = false;
		if ($navigationInfo) {
			foreach($navigationInfo as $page=>$pageInfo) {
				foreach($pageInfo as $index=>$record) {
					//If record found then next record in the interation
					//will be next record
					if($found) {
						$nextRecordId = $record;
						break;
					}
					if($record == $recordId) {
						$found = true;
					}
					//If record not found then we are assiging previousRecordId
					//assuming next record will get matched
					if(!$found) {
						$prevRecordId = $record;
					}
				}
				//if record is found and next record is not calculated we need to perform iteration
				if($found && !empty($nextRecordId)) {
					break;
				}
			}
		}

		/*$tab = $request->get('tab_label');
		$job_type = $recordModel->get('cf_1200');
		if (!empty($job_type) && $tab=='EDocuments') {
			$viewer->assign('JOB_TYPE',$job_type);
		}*/
		


		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		if(!empty($prevRecordId)) {
			$viewer->assign('PREVIOUS_RECORD_URL', $moduleModel->getDetailViewUrl($prevRecordId));
		}
		if(!empty($nextRecordId)) {
			$viewer->assign('NEXT_RECORD_URL', $moduleModel->getDetailViewUrl($nextRecordId));
		}

		$viewer->assign('MODULE_MODEL', $this->record->getModule());
		$viewer->assign('DETAILVIEW_LINKS', $detailViewLinks);

		$viewer->assign('IS_EDITABLE', $this->record->getRecord()->isEditable($moduleName));
		$viewer->assign('IS_DELETABLE', $this->record->getRecord()->isDeletable($moduleName));

		$linkParams = array('MODULE'=>$moduleName, 'ACTION'=>$request->get('view'));
		$linkModels = $this->record->getSideBarLinks($linkParams);
		$viewer->assign('QUICK_LINKS', $linkModels);
		$viewer->assign('MODULE_NAME', $moduleName);

		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$viewer->assign('DEFAULT_RECORD_VIEW', $currentUserModel->get('default_record_view'));

		$picklistDependencyDatasource = Vtiger_DependencyPicklist::getPicklistDependencyDatasource($moduleName);
		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE', Vtiger_Functions::jsonEncode($picklistDependencyDatasource));

		$tagsList = Vtiger_Tag_Model::getAllAccessible($currentUserModel->getId(), $moduleName, $recordId);
		$allUserTags = Vtiger_Tag_Model::getAllUserTags($currentUserModel->getId());
		$viewer->assign('TAGS_LIST', $tagsList);
		$viewer->assign('ALL_USER_TAGS', $allUserTags);
		$viewer->assign('SELECTED_MENU_CATEGORY', 'MARKETING');

		$selectedTabLabel = $request->get('tab_label');
		$relationId = $request->get('relationId');

		if(empty($selectedTabLabel)) {
			if($currentUserModel->get('default_record_view') === 'Detail') {
				$selectedTabLabel = vtranslate('SINGLE_'.$moduleName, $moduleName).' '. vtranslate('LBL_DETAILS', $moduleName);
			} else{
				if($moduleModel->isSummaryViewSupported()) {
					$selectedTabLabel = vtranslate('SINGLE_'.$moduleName, $moduleName).' '. vtranslate('LBL_SUMMARY', $moduleName);
				} else {
					$selectedTabLabel = vtranslate('SINGLE_'.$moduleName, $moduleName).' '. vtranslate('LBL_DETAILS', $moduleName);
				}
			}
		}

		$viewer->assign('SELECTED_TAB_LABEL', $selectedTabLabel);
		$viewer->assign('SELECTED_RELATION_ID',$relationId);

		//Vtiger7 - TO show custom view name in Module Header
		$viewer->assign('CUSTOM_VIEWS', CustomView_Record_Model::getAllByGroup($moduleName));

		$viewer->assign('IS_AJAX_ENABLED', $this->isAjaxEnabled($recordModel));
		if($display) {
			$this->preProcessDisplay($request);
		}
	}


	function process(Vtiger_Request $request) {
		$mode = $request->getMode();
		if(!empty($mode)) {
			echo $this->invokeExposedMethod($mode, $request);
			return;
		}

		$currentUserModel = Users_Record_Model::getCurrentUserModel();

		if ($currentUserModel->get('default_record_view') === 'Summary') {
			echo $this->showModuleBasicView($request);
		} else {
			echo $this->showModuleDetailView($request);
		}
	}

	
	/**
	 * Function returns related records
	 * @param Vtiger_Request $request
	 * @return <type>
	 */
	function showRelatedList(Vtiger_Request $request) {

		

		$moduleName = $request->getModule();
		$relatedModuleName = $request->get('relatedModule');
		$targetControllerClass = null;

		if($relatedModuleName == 'ModComments') {
			$currentUserModel = Users_Record_Model::getCurrentUserModel();
			$rollupSettings = ModComments_Module_Model::getRollupSettingsForUser($currentUserModel, $moduleName);
			$request->set('rollup_settings', $rollupSettings);
		}

		// Added to support related list view from the related module, rather than the base module.
		try {
			$targetControllerClass = Vtiger_Loader::getComponentClassName('View', 'In'.$moduleName.'Relation', $relatedModuleName);
		}catch(AppException $e) {
			try {
				// If any module wants to have same view for all the relation, then invoke this.
				$targetControllerClass = Vtiger_Loader::getComponentClassName('View', 'InRelation', $relatedModuleName);
			}catch(AppException $e) {
				// Default related list
				$targetControllerClass = Vtiger_Loader::getComponentClassName('View', 'RelatedList', $moduleName);
			}
		}
		if($targetControllerClass) {
			
			$targetController = new $targetControllerClass();
				if($targetController->checkPermission($request)){
				if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' 
				&& $_REQUEST['grid']==1 && $moduleName=='Job') 
				{											
					return $targetController->process_expense($request);	
				}
				elseif(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' 
					&& $_REQUEST['grid']==4 && $moduleName=='Job') 
				{
					return $targetController->process_expense_head($request);	
				}
				elseif(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' 
					&& $_REQUEST['grid']==5 && $moduleName=='Job')
				{
					return $targetController->process_expense_payable($request);
				}
				elseif(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' 
					&& $_REQUEST['grid']==2 && $moduleName=='Job') 
				{
					return $targetController->process_selling($request);	
				}
				elseif(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' 
					&& $_REQUEST['grid']==3 && $moduleName=='Job') 
				{
					return $targetController->process_selling_invoices($request);	
				}
				else{
					return $targetController->process($request);
				}	
				//return $targetController->process($request);
			}
		}
	}


	/**
	 * Function to get Ajax is enabled or not
	 * @param Vtiger_Record_Model record model
	 * @return <boolean> true/false
	 */
	function isAjaxEnabled($recordModel) {
		if(is_null($this->isAjaxEnabled)){
			$this->isAjaxEnabled = $recordModel->isEditable();
		}
		//return $this->isAjaxEnabled;
		return false;
	}


	

}
