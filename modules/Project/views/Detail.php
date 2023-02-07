<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Project_Detail_View extends Vtiger_Detail_View {
	
	function __construct() {
		parent::__construct();
		$this->exposeMethod('showRelatedRecords');
		$this->exposeMethod('showDetailViewByMode');
		$this->exposeMethod('showModuleDetailView');
		$this->exposeMethod('showModuleSummaryView');
		$this->exposeMethod('showModuleBasicView');

    $this->exposeMethod('showChart');
	}

	
	function showDetailViewByMode($request) {
		$requestMode = $request->get('requestMode');
		if($requestMode == 'full') {
			return $this->showModuleDetailView($request);
		}
		return $this->showModuleBasicView($request);
	}

	/**
	 * Function shows basic detail for the record
	 * @param <type> $request
	 */
	function showModuleBasicView($request) {
		global $adb;
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


		// Collecting the tasks
		$queryTasks = $this->getTaskAssignmentsQuery();
		$resultTasks = $adb->pquery($queryTasks, array($recordId));

		$nTasks = $adb->num_rows($resultTasks);

 		for ($i = 0; $i < $nTasks; $i++){
			$taskId = $adb->query_result($resultTasks, $i, 'projectmilestoneid');
			$taskDeadline = $adb->query_result($resultTasks, $i, 'projectmilestonedate');
			$taskName = $adb->query_result($resultTasks, $i, 'projectmilestonename');			
			$taskStatus = $adb->query_result($resultTasks, $i, 'cf_7838');
			$taskComment = trim($adb->query_result($resultTasks, $i, 'description'));
			$assignedto = $adb->query_result($resultTasks, $i, 'assignedto');

			$locationId = $adb->query_result($resultTasks, $i, 'cf_7844');
	 		$departmentId = $adb->query_result($resultTasks, $i, 'cf_7846');
			$branchName = $adb->query_result($resultTasks, $i, 'branch');
			$url = "/index.php?module=ProjectMilestone&view=Detail&record=$taskId";
  
		
			$TASK_LIST[] = array("id" => $taskId, 
								 "url" => $url, 
								 "name" => $taskName,
								 "deadline" => $taskDeadline, 
								 "status" => $taskStatus,
								 "comment" => $taskComment,								 
								 "assignedto" => $assignedto,
								 "locationId" => $locationId,
								 "departmentId" => $departmentId,
								 "branchName" => $branchName
								);
		}
		$viewer->assign('TASKS', $TASK_LIST);
		// End of collecting


		$recordStrucure = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_DETAIL);
		$structuredValues = $recordStrucure->getStructure();

		$moduleModel = $recordModel->getModule();
		$viewer->assign('CURRENT_USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('RECORD_STRUCTURE', $structuredValues);
		$viewer->assign('BLOCK_LIST', $moduleModel->getBlocks());
		echo $viewer->view('DetailViewSummaryContents.tpl', $moduleName, true);
	}



	/**
	 * Function shows the entire detail for the record
	 * @param Vtiger_Request $request
	 * @return <type>
	 */
	function showModuleDetailView(Vtiger_Request $request) {
		global $adb;
		$recordId = $request->get('record');
		$moduleName = $request->getModule();
 
/* 
 		echo "n=<pre>";
		print_r($nTasks);
		exit;
 */



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
		$viewer->assign('MODULE', $moduleName);

		// Collecting the tasks

		$queryTasks = $this->getTaskAssignmentsQuery();
		$resultTasks = $adb->pquery($queryTasks, array($recordId));


		$nTasks = $adb->num_rows($resultTasks);

 		for ($i = 0; $i < $nTasks; $i++){
			$taskId = $adb->query_result($resultTasks, $i, 'projectmilestoneid');
			$taskDeadline = $adb->query_result($resultTasks, $i, 'projectmilestonedate');
			$taskName = $adb->query_result($resultTasks, $i, 'projectmilestonename');			
			$taskStatus = $adb->query_result($resultTasks, $i, 'cf_7838');
			$taskComment = trim($adb->query_result($resultTasks, $i, 'description'));
			$assignedto = $adb->query_result($resultTasks, $i, 'assignedto');

			$locationId = $adb->query_result($resultTasks, $i, 'cf_7844');
	 		$departmentId = $adb->query_result($resultTasks, $i, 'cf_7846');
			$branchName = $adb->query_result($resultTasks, $i, 'branch');
			$url = "/index.php?module=ProjectMilestone&view=Detail&record=$taskId";
  
		
			$TASK_LIST[] = array("id" => $taskId, 
								 "url" => $url, 
								 "name" => $taskName,
								 "deadline" => $taskDeadline, 
								 "status" => $taskStatus,
								 "comment" => $taskComment,								 
								 "assignedto" => $assignedto,
								 "locationId" => $locationId,
								 "departmentId" => $departmentId,
								 "branchName" => $branchName
								);
		}
		$viewer->assign('TASKS', $TASK_LIST);
		// End of collecting
		
		$viewer->assign('IMAGE_DETAILS', $recordModel->getImageDetails());

		$picklistDependencyDatasource = Vtiger_DependencyPicklist::getPicklistDependencyDatasource($moduleName);
		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE', Vtiger_Functions::jsonEncode($picklistDependencyDatasource));

		if ($request->get('displayMode') == 'overlay') {
			$viewer->assign('MODULE_MODEL', $moduleModel);
			$this->setModuleInfo($request, $moduleModel);
			$viewer->assign('SCRIPTS',$this->getOverlayHeaderScripts($request));

			$detailViewLinkParams = array('MODULE'=>$moduleName, 'RECORD'=>$recordId);
			$detailViewLinks = $this->record->getDetailViewLinks($detailViewLinkParams);
			$viewer->assign('DETAILVIEW_LINKS', $detailViewLinks);
			return $viewer->view('OverlayDetailView.tpl', $moduleName);
		} else {
			return $viewer->view('DetailViewFullContents.tpl', $moduleName, true);
		}
	}


	public function showModuleSummaryView($request) {
		$recordId = $request->get('record');
		$moduleName = $request->getModule();

		$recordModel = Vtiger_Record_Model::getInstanceById($recordId);
		$recordStrucure = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_SUMMARY);
		
		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD', $recordModel);
        $viewer->assign('IS_AJAX_ENABLED', $this->isAjaxEnabled($recordModel));
		$viewer->assign('SUMMARY_INFORMATION', $recordModel->getSummaryInfo());
		$viewer->assign('SUMMARY_RECORD_STRUCTURE', $recordStrucure->getStructure());
        $viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('MODULE_NAME', $moduleName);

		return $viewer->view('ModuleSummaryView.tpl', $moduleName, true);
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
		$orderBy = $request->get('orderby');
		$sortOrder = $request->get('sortorder');
		$whereCondition = $request->get('whereCondition');
		$moduleName = $request->getModule();
		$relatedModuleInstance = Vtiger_Module_Model::getInstance($relatedModuleName);
		
		if($sortOrder == "ASC") {
			$nextSortOrder = "DESC";
			$sortImage = "icon-chevron-down";
		} else {
			$nextSortOrder = "ASC";
			$sortImage = "icon-chevron-up";
		}
		
		$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentId, $moduleName);
		$relationListView = Vtiger_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName);
		$relatedModuleModel = $relationListView->getRelationModel()->getRelationModuleModel();
		
		if(!empty($orderBy)) {
			$relationListView->set('orderby', $orderBy);
			$relationListView->set('sortorder', $sortOrder);
		}

		if(empty($pageNumber)) {
			$pageNumber = 1;
		}

		$pagingModel = new Vtiger_Paging_Model();
		$pagingModel->set('page', $pageNumber);
		if(!empty($limit)) {
			$pagingModel->set('limit', $limit);
		}
		
		if ($whereCondition) {
			$relationListView->set('whereCondition', $whereCondition);
		}
		
		$models = $relationListView->getEntries($pagingModel);
		$header = $relationListView->getHeaders();
		//ProjectTask Progress and Status should show in Projects summary view 
		if($relatedModuleName == 'ProjectTask') {
			$fieldModel = Vtiger_Field_Model::getInstance('projecttaskstatus', $relatedModuleInstance);
			if($fieldModel && $fieldModel->isViewableInDetailView()) {
				$header['projecttaskstatus'] = $relatedModuleModel->getField('projecttaskstatus');
			}
			$fieldModel = Vtiger_Field_Model::getInstance('projecttaskprogress', $relatedModuleInstance);
			if($fieldModel && $fieldModel->isViewableInDetailView()) {
				$header['projecttaskprogress'] = $relatedModuleModel->getField('projecttaskprogress');
			}
		}
		
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE' , $moduleName);
		$viewer->assign('RELATED_RECORDS' , $models);
		$viewer->assign('RELATED_HEADERS', $header);
		$viewer->assign('RELATED_MODULE' , $relatedModuleName);
		$viewer->assign('RELATED_MODULE_MODEL', $relatedModuleInstance);
		$viewer->assign('PAGING_MODEL', $pagingModel);

		return $viewer->view('SummaryWidgets.tpl', $moduleName, 'true');
	}

	/**
	 * Function to show Gantt chart
	 * @param Vtiger_Request $request
	 */
	public function showChart(Vtiger_Request $request) {
		$parentId = $request->get('record');
		$projectTasks = array();
		$moduleName = $request->getModule();
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentId, $moduleName);
		$projectTaskModel = Vtiger_Module_Model::getInstance('ProjectTask');
		$projectTasks['tasks'] = $parentRecordModel->getProjectTasks();
		$projectTasks["selectedRow"] = 0;
		$projectTasks["canWrite"] = true;
		$projectTasks["canWriteOnParent"] = true;
		$viewer = $this->getViewer($request);
		$viewer->assign('PARENT_ID', $parentId);
		$viewer->assign('MODULE' , $moduleName);
		$viewer->assign('PROJECT_TASKS' , $projectTasks);
		$viewer->assign('SCRIPTS',$this->getHeaderScripts($request));
		$viewer->assign('TASK_STATUS', Vtiger_Util_Helper::getRoleBasedPicklistValues('projecttaskstatus', $currentUserModel->get('roleid')));
		$viewer->assign('TASK_STATUS_COLOR', $parentRecordModel->getStatusColors());
		$viewer->assign('STYLES',$this->getHeaderCss($request));
		$viewer->assign('USER_DATE_FORMAT', $currentUserModel->get('date_format'));
		$viewer->assign('STATUS_FIELD_MODEL', Vtiger_Field_Model::getInstance('projecttaskstatus', $projectTaskModel));
		// $viewer->assign('PROGRESS_FIELD_MODEL', Vtiger_Field_Model::getInstance('projecttaskstatus', $projectTaskModel));

		return $viewer->view('ShowChart.tpl', $moduleName, 'true');
	}

	/**
	 * Function get gantt specific headerscript
	 * @param Vtiger_Request $request
	 */
	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$jsFileNames = array(
			'~/libraries/jquery/gantt/libs/jquery.livequery.min.js',
			'~/libraries/jquery/gantt/libs/jquery.timers.js',
			'~/libraries/jquery/gantt/libs/platform.js',
			'~/libraries/jquery/gantt/libs/date.js',
			'~/libraries/jquery/gantt/libs/i18nJs.js',
			'~/libraries/jquery/gantt/libs/JST/jquery.JST.js',
			'~/libraries/jquery/gantt/libs/jquery.svg.min.js',
			'~/libraries/jquery/gantt/ganttUtilities.js',
			'~/libraries/jquery/gantt/ganttTask.js',
			'~/libraries/jquery/gantt/ganttDrawerSVG.js',
			'~/libraries/jquery/gantt/ganttGridEditor.js',
			'~/libraries/jquery/gantt/ganttMaster.js',
			'~/libraries/jquery/gantt/libs/moment.min.js',
			'~/libraries/jquery/colorpicker/js/colorpicker.js',
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances,$jsScriptInstances);
		return $headerScriptInstances;
	}


	public function getTaskAssignmentsQuery(){

		$queryTask = "SELECT 
									vtiger_projectmilestone.projectmilestoneid, vtiger_projectmilestone.projectmilestonename, 
									vtiger_projectmilestone.projectmilestonedate, vtiger_projectmilestonecf.cf_7838,
									vtiger_crmentity.smownerid, vtiger_crmentity.description,
									vtiger_projectmilestonecf.cf_7844,
									vtiger_projectmilestonecf.cf_7846,
									CONCAT(vtiger_location.name, ' / ', vtiger_departmentcf.cf_1542) as branch, 
									CONCAT(vtiger_users.first_name, ' ', vtiger_users.last_name) as assignedto

									FROM `vtiger_crmentityrel` 
									INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_crmentityrel.relcrmid
									INNER JOIN vtiger_projectmilestone ON vtiger_projectmilestone.projectmilestoneid = vtiger_crmentityrel.relcrmid
									INNER JOIN vtiger_projectmilestonecf ON vtiger_projectmilestonecf.projectmilestoneid = vtiger_crmentityrel.relcrmid
									LEFT JOIN vtiger_location ON vtiger_location.locationid = vtiger_projectmilestonecf.cf_7844
									LEFT JOIN vtiger_departmentcf ON vtiger_departmentcf.departmentid = vtiger_projectmilestonecf.cf_7846
									LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid
									
									WHERE vtiger_crmentity.deleted = 0 AND vtiger_crmentityrel.`module` = 'Project' 
									AND vtiger_crmentityrel.`relmodule` = 'ProjectMilestone' 
									AND vtiger_crmentityrel.crmid = ?
									
									ORDER BY vtiger_crmentity.crmid";
		return $queryTask;
	}



	/**
	 * Function to get the css styles for gantt chart
	 * @param  Vtiger_Request $request
	 */
	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		$cssFileNames = array(
			'~/libraries/jquery/gantt/platform.css',
			'~/libraries/jquery/gantt/gantt.css',
			'~/libraries/jquery/colorpicker/css/colorpicker.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		$headerCssInstances = array_merge($cssInstances, $headerCssInstances);
		return $headerCssInstances;
	}
}
?>
