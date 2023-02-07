<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class NCRS_Detail_View extends Vtiger_Detail_View {
	protected $record = false;

	function __construct() {
		
		parent::__construct();
		$this->exposeMethod('showDetailViewByMode');
		$this->exposeMethod('showModuleDetailView');
		$this->exposeMethod('showModuleSummaryView');
		$this->exposeMethod('showModuleBasicView');
		$this->exposeMethod('showRecentActivities');
		$this->exposeMethod('showRecentComments');
		//$this->exposeMethod('showRelatedList');
		$this->exposeMethod('showChildComments');
		$this->exposeMethod('showAllComments');
		$this->exposeMethod('getActivities');
	}

	function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		
		$recordPermission = Users_Privileges_Model::isPermitted($moduleName, 'DetailView', $recordId);
		
		if(!$recordPermission) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}	
		
		return true;
	}

	function preProcess(Vtiger_Request $request, $display=true) {
		parent::preProcess($request, false);

		$recordId = $request->get('record');
		$moduleName = $request->getModule();		
		
		if(!$this->record){
			$this->record = Vtiger_DetailView_Model::getInstance($moduleName, $recordId);
			//echo "<pre>";
			//print_r($this->record);
			//exit;
		}
		$recordModel = $this->record->getRecord();
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

		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$viewer->assign('DEFAULT_RECORD_VIEW', $currentUserModel->get('default_record_view'));
		
		if(trim($moduleName)=='Job')
		{			
			if(isset($_SESSION['job_deviation_cost_'.$recordId]))
			{
				switch($_SESSION['job_deviation_cost_'.$recordId])
				{				
					case "1":
					$viewer->assign('COST_DEVIATION_MESSAGE','The deviation between Expected and Actual Costs is more than 10%, please check and update.');
					break;					
					default:
					break;
				}
				unset($_SESSION['job_deviation_cost_'.$recordId]);
			}
			if(isset($_SESSION['job_deviation_revenue_'.$recordId]))
			{
				switch($_SESSION['job_deviation_revenue_'.$recordId])
				{				
					case "1":
					$viewer->assign('REVENUE_DEVIATION_MESSAGE','The deviation between Expected and Actual Revenue is more than 10%, please check and update.');
					break;					
					default:
					break;
				}
				unset($_SESSION['job_deviation_revenue_'.$recordId]);
			}
		}
		
		if($display) {
			$this->preProcessDisplay($request);
		}
	}

	function preProcessTplName(Vtiger_Request $request) {
		
		$moduleName = $request->getModule();
		$relatedModuleName = $request->get('relatedModule');
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('RELATED_MODULE', $relatedModuleName);
		//Mehtab Code		
		if(trim($moduleName)=='Job')
		{
			$recordId = $request->get('record');
			$job_id = $recordId;
			$current_user = Users_Record_Model::getCurrentUserModel();
			//$job_info_detail = Vtiger_Record_Model::getInstanceById($job_id, $moduleName);
			if(!$this->record){
				$job_info_detail = Vtiger_Record_Model::getInstanceById($job_id, $moduleName);
			}
			else{				
				$job_info_detail = $this->record->getRecord();
			}
			$viewer->assign('JRER_USER_ID_FLAG', 'yes');
			
			$access_company_id = explode(" |##| ",$current_user->get('access_company_id'));
			$viewer->assign('ACCESS_USER_COMPANY', $access_company_id);
			
			
			if($current_user->get('is_admin')!='on')
			{
				$privileges   = $current_user->get('privileges');
				$parent_roles_arr = $privileges->parent_roles;				
				$count_parent_role = count($parent_roles_arr);
				
				if($_REQUEST['module']=='Job' && $count_parent_role==0)
				{
					$role_id =  $current_user->get('roleid');
					$depth_role = mysql_query("SELECT * FROM vtiger_role where roleid='".$role_id."' ");
					$row_depth = mysql_fetch_array($depth_role);
					$count_parent_role = $row_depth['depth'];
				}
				
				if($job_info_detail->get('assigned_user_id')==$current_user->getId() || $current_user->get('roleid')=='H3'  
				|| $count_parent_role <= 3 || $current_user->get('roleid')=='H2' || $current_user->get('roleid')=='H185' || $current_user->get('roleid')=='H184')
				{
					$viewer->assign('JRER_USER_ID_FLAG', 'yes');
				}
				else{
					$viewer->assign('JRER_USER_ID_FLAG', 'no');
					$viewer->assign('SUB_JRER_USER_ID', $current_user->getId());	
				}
			}			
			
			/*
			if($job_info_detail->get('assigned_user_id')!=$current_user->getId() && $current_user->get('roleid')!='H3')
			{
				$viewer->assign('JRER_USER_ID_FLAG', 'no');
				$viewer->assign('SUB_JRER_USER_ID', $current_user->getId());	
			}
			*/
			
			//For job costing check
			$adb_job_costing = PearDatabase::getInstance();
			$jer_last_sql =  "SELECT COUNT(*) as total_costing FROM `vtiger_job` as job 
							 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = job.jobid
							 INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.crmid 
							 WHERE vtiger_crmentity.deleted=0 
							 AND crmentityrel.crmid=? 
							 AND crmentityrel.module='Job' 
							 AND (crmentityrel.relmodule='JER' || crmentityrel.relmodule='Jobexpencereport')";
			// parentId = Job Id	
			$job_id = $recordId;
				 
			$params = array($job_id);
			$result_last = $adb_job_costing->pquery($jer_last_sql, $params);
			$row_costing_last = $adb_job_costing->fetch_array($result_last);
			//$count_last_modified = $adb_job_costing->num_rows($result_last);
			
			if($row_costing_last['total_costing']!=0)
			{
				 $viewer->assign('FLAG_COSTING', true);
			}
			
			
		}
		
		if(trim($moduleName)=='BO')
		{
			$recordId = $request->get('record');
			
			$adb_job = PearDatabase::getInstance();	
			/*$params_job = array($recordId);
			$query_job = 'SELECT *, rel_2.crmid as jobid FROM vtiger_bo, vtiger_bocf, vtiger_crmentityrel as rel_1, vtiger_crmentityrel as rel_2 
						  where vtiger_bo.boid = vtiger_bocf.boid 
								and rel_1.relcrmid = vtiger_bo.boid 
								and rel_2.relcrmid = rel_1.crmid 
								and rel_1.relcrmid =?';
			$result_job = $adb_job->pquery($query_job, $params_job);
			$row_job_info = $adb_job->fetch_array($result_job);	*/
			$params_bo = array($recordId);
			$query_rel_jer = "select crmid as jerid from vtiger_crmentityrel where relcrmid=? AND relmodule='BO' AND module='JER'";					
			$result_rel_jer = $adb_job->pquery($query_rel_jer, $params_bo);			
			$row_jer_info = $adb_job->fetch_array($result_rel_jer);
			
			$query_rel_job = "select crmid as jobid from vtiger_crmentityrel where relcrmid=? AND relmodule='JER' AND module='Job'";	
			$params_jer = array($row_jer_info['jerid']);				
			$result_rel_job = $adb_job->pquery($query_rel_job, $params_jer);			
			$row_job_info = $adb_job->fetch_array($result_rel_job);
			$viewer->assign('HISTORY_JOB_ID', $row_job_info['jobid']);
		}
		
		if(trim($moduleName)=='VPO')
		{
			$recordId = $request->get('record');
			
			$adb_job = PearDatabase::getInstance();	
			/*$params_job = array($recordId);
			$query_job = 'SELECT *, rel_2.crmid as jobid FROM vtiger_vpo, vtiger_vpocf, vtiger_crmentityrel as rel_1, vtiger_crmentityrel as rel_2 
						  where vtiger_vpo.vpoid = vtiger_vpocf.vpoid 
								and rel_1.relcrmid = vtiger_vpo.vpoid 
								and rel_2.relcrmid = rel_1.crmid 
								and rel_1.relcrmid =?';
			$result_job = $adb_job->pquery($query_job, $params_job);
			$row_job_info = $adb_job->fetch_array($result_job);	*/
			$params_vpo = array($recordId);
			$query_rel_jer = "select crmid as jerid from vtiger_crmentityrel where relcrmid=? AND relmodule='VPO' AND module='JER'";					
			$result_rel_jer = $adb_job->pquery($query_rel_jer, $params_vpo);			
			$row_jer_info = $adb_job->fetch_array($result_rel_jer);
			
			$query_rel_job = "select crmid as jobid from vtiger_crmentityrel where relcrmid=? AND relmodule='JER' AND module='Job'";	
			$params_jer = array($row_jer_info['jerid']);				
			$result_rel_job = $adb_job->pquery($query_rel_job, $params_jer);			
			$row_job_info = $adb_job->fetch_array($result_rel_job);
			$viewer->assign('HISTORY_JOB_ID', $row_job_info['jobid']);
		}
		
		if(trim($moduleName)=='JER')
		{
			$recordId = $request->get('record');
			
			$adb_job = PearDatabase::getInstance();	
			$params_job = array($recordId);
			$query_job = 'SELECT *, rel_1.crmid as jobid FROM vtiger_jer, vtiger_jercf, vtiger_crmentityrel as rel_1
						  where vtiger_jer.jerid = vtiger_jercf.jerid 
								and rel_1.relcrmid = vtiger_jer.jerid 
								and rel_1.relcrmid =?';
			$result_job = $adb_job->pquery($query_job, $params_job);
			$row_job_info = $adb_job->fetch_array($result_job);	
			$viewer->assign('HISTORY_JOB_ID', $row_job_info['jobid']);
		}
		
		if(trim($moduleName)=='Jobexpencereport')
		{
			$recordId = $request->get('record');
			
			$adb_job = PearDatabase::getInstance();	
			$params_job = array($recordId);
			$query_job = 'SELECT *, rel_1.crmid as jobid FROM vtiger_jobexpencereport, vtiger_jobexpencereportcf, vtiger_crmentityrel as rel_1
						  where vtiger_jobexpencereport.jobexpencereportid = vtiger_jobexpencereportcf.jobexpencereportid 
								and rel_1.relcrmid = vtiger_jobexpencereport.jobexpencereportid 
								and rel_1.relcrmid =?';
			$result_job = $adb_job->pquery($query_job, $params_job);
			$row_job_info = $adb_job->fetch_array($result_job);
			$viewer->assign('HISTORY_PARENT_MODULE', $row_job_info['module']);	
			$viewer->assign('HISTORY_JOB_ID', $row_job_info['jobid']);
		}
		
		if(trim($moduleName)=='JobTask')
		{
			$recordId = $request->get('record');
			
			$adb_job = PearDatabase::getInstance();	
			$params_job = array($recordId);
			$query_job = 'SELECT *, vtiger_jobtask.job_id as jobid FROM vtiger_jobtask
						  where vtiger_jobtask.jobtaskid =?';
			$result_job = $adb_job->pquery($query_job, $params_job);
			$row_job_info = $adb_job->fetch_array($result_job);	
			$viewer->assign('HISTORY_JOB_ID', $row_job_info['jobid']);
		}
		
		if(trim($moduleName)=='Insurance')
		{
			$recordId = $request->get('record');	
			
			$adb_r = PearDatabase::getInstance();
			$query_rel = "select crmid from vtiger_crmentityrel where relcrmid=? AND relmodule='Insurance' AND module='Job'";
			$params_rel = array($recordId);	
			$result_rel = $adb_r->pquery($query_rel, $params_rel);
			$row_rel = $adb_r->fetch_array($result_rel);
			$job_id = $row_rel['crmid'];
			$viewer->assign('HISTORY_JOB_ID', $job_id);
		}
		
		if(trim($moduleName)=='CargoInsurance')
		{
			$recordId = $request->get('record');	
			
			$adb_r = PearDatabase::getInstance();
			$query_rel = "select crmid from vtiger_crmentityrel where relcrmid=? AND relmodule='CargoInsurance' AND module='Job'";
			$params_rel = array($recordId);	
			$result_rel = $adb_r->pquery($query_rel, $params_rel);
			$row_rel = $adb_r->fetch_array($result_rel);
			$job_id = $row_rel['crmid'];
			$viewer->assign('HISTORY_JOB_ID', $job_id);
		}
		
		if(trim($moduleName)=='Fleet')
		{
			$recordId = $request->get('record');			
			
			$adb_fleet = PearDatabase::getInstance();
			$checkjob_fleet = $adb_fleet->pquery("SELECT rel_1.crmid as job_id FROM vtiger_fleet, vtiger_fleetcf, vtiger_crmentityrel as rel_1
												  where vtiger_fleet.fleetid = vtiger_fleetcf.fleetid 
														and rel_1.relcrmid = vtiger_fleet.fleetid 
														and rel_1.relcrmid =?", array($recordId));
			 $crmId = $adb_fleet->query_result($checkjob_fleet, 0, 'job_id');
			 $job_id = $crmId;			 
			 $viewer->assign('HISTORY_JOB_ID', $job_id);
			 
			}if($moduleName=='Leaverequest'){
				$recordId = $_GET['record'];
				$recordModel = Vtiger_Record_model::getInstanceById($recordId, $moduleName);
				$current_user_role = Users_Record_Model::getCurrentUserModel();
				$recordModel->set('mode', 'edit');
				
				$current_user_id = $current_user_role->roleid;
				$depth_role = mysql_query("SELECT * FROM vtiger_role where roleid='".$current_user_id."' ");
				$row_depth = mysql_fetch_array($depth_role);
				$current_user_depth = $row_depth['depth'];
				if($current_user_depth <= 3){
					$viewer->assign('MANAGER_FLAG',true);
				}
			}
		
		
		
			
		return 'DetailViewPreProcess.tpl';
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

	public function postProcess(Vtiger_Request $request) {parent::postProcess($request);exit;
		
		$recordId = $request->get('record');
		
		$moduleName = $request->getModule();
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		if(!$this->record){
			$this->record = Vtiger_DetailView_Model::getInstance($moduleName, $recordId);
		}
		$detailViewLinkParams = array('MODULE'=>$moduleName,'RECORD'=>$recordId);
		$detailViewLinks = $this->record->getDetailViewLinks($detailViewLinkParams);

		$selectedTabLabel = $request->get('tab_label');

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

		$viewer = $this->getViewer($request);

		$viewer->assign('SELECTED_TAB_LABEL', $selectedTabLabel);
		$viewer->assign('MODULE_MODEL', $this->record->getModule());
		$viewer->assign('DETAILVIEW_LINKS', $detailViewLinks);
		
		
		if($moduleName=='Job')
		{
			$job_id = $recordId;
			$current_user = Users_Record_Model::getCurrentUserModel();
			//$job_info_detail = Vtiger_Record_Model::getInstanceById($job_id, $moduleName);
			if(!$this->record)
			{
				$job_info_detail = Vtiger_Record_Model::getInstanceById($job_id, $moduleName);	
			}
			else{
				$job_info_detail = $this->record->getRecord();
			}
			
			$access_company_id = explode(" |##| ",$current_user->get('access_company_id'));
			$viewer->assign('ACCESS_USER_COMPANY', $access_company_id);
			
			$viewer->assign('KZ_COMPANY_ID', $job_info_detail->get('cf_1186')); //85757
			$viewer->assign('JOB_OWNER_FLAG', TRUE);
			if($job_info_detail->get('assigned_user_id')!=$current_user->getId())
			{
				$viewer->assign('JOB_OWNER_FLAG', FALSE);
			}
			
			$privileges  = $current_user->get('privileges');
			$parent_roles = $privileges->parent_roles;
			$coordinator_department_head_role = @$parent_roles[3];
			$count_parent_role = count($parent_roles);
			
			if($count_parent_role<=3)
			{
				$viewer->assign('JOB_OWNER_FLAG', TRUE);
			}
			
			//For job costing check
			$adb_job_costing = PearDatabase::getInstance();
			$jer_last_sql =  "SELECT COUNT(*) as total_costing FROM `vtiger_job` as job 
							 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = job.jobid
							 INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.crmid 
							 WHERE vtiger_crmentity.deleted=0 
							 AND crmentityrel.crmid=? 
							 AND crmentityrel.module='Job' 
							 AND (crmentityrel.relmodule='JER' || crmentityrel.relmodule='Jobexpencereport')";
			// parentId = Job Id	
			$job_id = $recordId;
				 
			$params = array($job_id);
			$result_last = $adb_job_costing->pquery($jer_last_sql, $params);
			$row_costing_last = $adb_job_costing->fetch_array($result_last);
			//$count_last_modified = $adb_job_costing->num_rows($result_last);
			
			if($row_costing_last['total_costing']!=0)
			{
				 $viewer->assign('FLAG_COSTING', true);
			}
			
		}
		$viewer->view('DetailViewPostProcess.tpl', $moduleName);

		parent::postProcess($request);
	}


	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			'modules.Vtiger.resources.Detail',
			"modules.$moduleName.resources.Detail",
			'modules.Vtiger.resources.RelatedList',
			"modules.$moduleName.resources.RelatedList",
			'libraries.jquery.jquery_windowmsg',
            "libraries.jquery.ckeditor.ckeditor",
			"libraries.jquery.ckeditor.adapters.jquery",
			"modules.Emails.resources.MassEdit",
			"modules.Vtiger.resources.CkEditor",
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	function showDetailViewByMode($request) {
		
		$requestMode = $request->get('requestMode');
		if($requestMode == 'full') {
			return $this->showModuleDetailView($request);
		}
		return $this->showModuleBasicView($request);
	}

	/**
	 * Function shows the entire detail for the record
	 * @param Vtiger_Request $request
	 * @return <type>
	 */
	function showModuleDetailView(Vtiger_Request $request) {
		$recordId = $request->get('record');
		$moduleName = $request->getModule();
		
		if(!$this->record){
		$this->record = Vtiger_DetailView_Model::getInstance($moduleName, $recordId);
		}
		$recordModel = $this->record->getRecord();
		$recordStrucure = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_DETAIL);
		$structuredValues = $recordStrucure->getStructure();

        $moduleModel = $recordModel->getModule();
		
		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('RECORD_STRUCTURE', $structuredValues);
        $viewer->assign('BLOCK_LIST', $moduleModel->getBlocks());
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('IS_AJAX_ENABLED', $this->isAjaxEnabled($recordModel));
		
		
		if(trim($moduleName)=='Jobexpencereport')
		{
			$selling_expense_type = $recordModel->get('cf_1457');
			$jrertype = (($selling_expense_type=='Expence') ? 'expence' : 'selling');
			$request->set('jrertype', $jrertype);
			
			$jrertype = $request->get('jrertype');	
			$viewer->assign('JRER_TYPE', ($jrertype=='expence') ? 'selling' : 'expence' );
		}
		
		return $viewer->view('DetailViewFullContents.tpl',$moduleName,true);
	}

	function showModuleSummaryView($request) {
		$recordId = $request->get('record');
		$moduleName = $request->getModule();

		if(!$this->record){
			$this->record = Vtiger_DetailView_Model::getInstance($moduleName, $recordId);
		}
		$recordModel = $this->record->getRecord();
		$recordStrucure = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_SUMMARY);
		

        $moduleModel = $recordModel->getModule();
		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD', $recordModel);
        $viewer->assign('BLOCK_LIST', $moduleModel->getBlocks());
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());

		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('IS_AJAX_ENABLED', $this->isAjaxEnabled($recordModel));
		$viewer->assign('SUMMARY_RECORD_STRUCTURE', $recordStrucure->getStructure());
		$viewer->assign('RELATED_ACTIVITIES', $this->getActivities($request));
		
		
		
		return $viewer->view('ModuleSummaryView.tpl', $moduleName, true);
	}

	/**
	 * Function shows basic detail for the record
	 * @param <type> $request
	 */
	function showModuleBasicView($request) {

		$recordId = $request->get('record');
		$moduleName = $request->getModule();

		if(!$this->record){
			$this->record = Vtiger_DetailView_Model::getInstance($moduleName, $recordId);
		}
		$recordModel = $this->record->getRecord();

		$detailViewLinkParams = array('MODULE'=>$moduleName,'RECORD'=>$recordId);
		$detailViewLinks = $this->record->getDetailViewLinks($detailViewLinkParams);

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('MODULE_SUMMARY', $this->showModuleSummaryView($request));

		$viewer->assign('DETAILVIEW_LINKS', $detailViewLinks);
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('IS_AJAX_ENABLED', $this->isAjaxEnabled($recordModel));
		$viewer->assign('MODULE_NAME', $moduleName);

		$recordStrucure = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_DETAIL);
		$structuredValues = $recordStrucure->getStructure();

        $moduleModel = $recordModel->getModule();
		$viewer->assign('ncrlist', $this->ncrrecords($recordId));
		$viewer->assign('RECORD_STRUCTURE', $structuredValues);
        $viewer->assign('BLOCK_LIST', $moduleModel->getBlocks());
		
		if(trim($moduleName)=='Jobexpencereport')
		{
			$selling_expense_type = $recordModel->get('cf_1457');
			$jrertype = (($selling_expense_type=='Expence') ? 'expence' : 'selling');
			$request->set('jrertype', $jrertype);
			
			$jrertype = $request->get('jrertype');	
			$viewer->assign('JRER_TYPE', ($jrertype=='expence') ? 'selling' : 'expence' );
		}

		echo $viewer->view('DetailViewSummaryContents.tpl', $moduleName, true);
	}
	
	private function ncrrecords($sourcerecordid) {
		$db = PearDatabase::getInstance();
		//$id = $request->get('record');	
				$query = "SELECT * FROM vtiger_ncrraised INNER JOIN vtiger_ncrraisedcf ON vtiger_ncrraised.ncrraisedid = vtiger_ncrraisedcf.ncrraisedid
						INNER JOIN vtiger_crmentity ON vtiger_ncrraised.ncrraisedid = vtiger_crmentity.crmid
						INNER JOIN vtiger_crmentityrel ON vtiger_ncrraised.ncrraisedid = vtiger_crmentityrel.relcrmid
						WHERE
						vtiger_crmentityrel.crmid = ".$sourcerecordid." AND
						vtiger_crmentity.deleted = 0
						ORDER BY
						vtiger_ncrraised.ncrraisedid ASC"; 
			//echo $query;
        	$result = $db->pquery($query, array());
				for($i=0;$i<$db->num_rows($result);$i++){
					$ncrlist[$i]['relatedrecordid'] = $db->query_result($result,$i,'ncrraisedid'); 
					$ncrlist[$i]['person'] = Vtiger_Multiowner_UIType::getDisplayValue($db->query_result($result,$i,'cf_6512'));
					$ncrlist[$i]['department'] = Vtiger_DepartmentList_UIType::getDisplayValue($db->query_result($result,$i,'cf_6508'));
					$ncrlist[$i]['location'] = Vtiger_LocationList_UIType::getDisplayValue($db->query_result($result,$i,'cf_6510'));
					//$category[$db->query_result($result,$i,'categoryid')] = $db->query_result($result,$i,'name'); 
				}
			return $ncrlist;
	}
	

	/**
	 * Function returns recent changes made on the record
	 * @param Vtiger_Request $request
	 */
	function showRecentActivities (Vtiger_Request $request) {
		$parentRecordId = $request->get('record');
		$pageNumber = $request->get('page');
		$limit = $request->get('limit');
		$moduleName = $request->getModule();

		if(empty($pageNumber)) {
			$pageNumber = 1;
		}

		$pagingModel = new Vtiger_Paging_Model();
		$pagingModel->set('page', $pageNumber);
		if(!empty($limit)) {
			$pagingModel->set('limit', $limit);
		}

		$recentActivities = ModTracker_Record_Model::getUpdates($parentRecordId, $pagingModel);
		$pagingModel->calculatePageRange($recentActivities);

		$viewer = $this->getViewer($request);
		$viewer->assign('RECENT_ACTIVITIES', $recentActivities);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('PAGING_MODEL', $pagingModel);

		echo $viewer->view('RecentActivities.tpl', $moduleName, 'true');
	}

	/**
	 * Function returns latest comments
	 * @param Vtiger_Request $request
	 * @return <type>
	 */
	function showRecentComments(Vtiger_Request $request) {
		$parentId = $request->get('record');
		$pageNumber = $request->get('page');
		$limit = $request->get('limit');
		$moduleName = $request->getModule();

		if(empty($pageNumber)) {
			$pageNumber = 1;
		}

		$pagingModel = new Vtiger_Paging_Model();
		$pagingModel->set('page', $pageNumber);
		if(!empty($limit)) {
			$pagingModel->set('limit', $limit);
		}

		$recentComments = ModComments_Record_Model::getRecentComments($parentId, $pagingModel);
		$pagingModel->calculatePageRange($recentComments);
		$currentUserModel = Users_Record_Model::getCurrentUserModel();

		$viewer = $this->getViewer($request);
		$viewer->assign('COMMENTS', $recentComments);
		$viewer->assign('CURRENTUSER', $currentUserModel);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('PAGING_MODEL', $pagingModel);

		return $viewer->view('RecentComments.tpl', $moduleName, 'true');
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

	/**
	 * Function sends the child comments for a comment
	 * @param Vtiger_Request $request
	 * @return <type>
	 */
	function showChildComments(Vtiger_Request $request) {
		$parentCommentId = $request->get('commentid');
		$parentCommentModel = ModComments_Record_Model::getInstanceById($parentCommentId);
		$childComments = $parentCommentModel->getChildComments();
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		
		$viewer = $this->getViewer($request);
		$viewer->assign('PARENT_COMMENTS', $childComments);
		$viewer->assign('CURRENTUSER', $currentUserModel);

		return $viewer->view('CommentsList.tpl', $moduleName, 'true');
	}

	/**
	 * Function sends all the comments for a parent(Accounts, Contacts etc)
	 * @param Vtiger_Request $request
	 * @return <type>
	 */
	function showAllComments(Vtiger_Request $request) {
		$parentRecordId = $request->get('record');
		$commentRecordId = $request->get('commentid');
		$moduleName = $request->getModule();
		$currentUserModel = Users_Record_Model::getCurrentUserModel();

		$parentCommentModels = ModComments_Record_Model::getAllParentComments($parentRecordId);

		if(!empty($commentRecordId)) {
			$currentCommentModel = ModComments_Record_Model::getInstanceById($commentRecordId);
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('CURRENTUSER', $currentUserModel);
		$viewer->assign('PARENT_COMMENTS', $parentCommentModels);
		$viewer->assign('CURRENT_COMMENT', $currentCommentModel);

		return $viewer->view('ShowAllComments.tpl', $moduleName, 'true');
	}
	/**
	 * Function to get Ajax is enabled or not
	 * @param Vtiger_Record_Model record model
	 * @return <boolean> true/false
	 */
	function isAjaxEnabled($recordModel) {
		return false; //$recordModel->isEditable();
	}

	/**
	 * Function to get activities
	 * @param Vtiger_Request $request
	 * @return <List of activity models>
	 */
	public function getActivities(Vtiger_Request $request) {
		return '';
	}


	/**
	 * Function returns related records based on related moduleName
	 * @param Vtiger_Request $request
	 * @return <type>
	 */
	function showRelatedRecords(Vtiger_Request $request) {
		
		$parentId = $request->get('record');
		$pageNumber = $request->get('page');
		$limit = $request->get('limit');
		$relatedModuleName = $request->get('relatedModule');
		$moduleName = $request->getModule();

		if(empty($pageNumber)) {
			$pageNumber = 1;
		}

		$pagingModel = new Vtiger_Paging_Model();
		$pagingModel->set('page', $pageNumber);
		if(!empty($limit)) {
			$pagingModel->set('limit', $limit);
		}

		$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentId, $moduleName);
		$relationListView = Vtiger_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName);
		$models = $relationListView->getEntries($pagingModel);
		
		$header = $relationListView->getHeaders();

		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE' , $moduleName);
		$viewer->assign('RELATED_RECORDS' , $models);
		$viewer->assign('RELATED_HEADERS', $header);
		$viewer->assign('RELATED_MODULE' , $relatedModuleName);
		$viewer->assign('PAGING_MODEL', $pagingModel);

		return $viewer->view('SummaryWidgets.tpl', $moduleName, 'true');
	}
}
