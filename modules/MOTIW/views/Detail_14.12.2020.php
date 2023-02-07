<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class MOTIW_Detail_View extends Vtiger_Detail_View {
	protected $record = false;
	protected $isAjaxEnabled = null;

	function __construct() {
		parent::__construct();
		$this->exposeMethod('showDetailViewByMode');
		$this->exposeMethod('showModuleDetailView');
		$this->exposeMethod('showModuleSummaryView');
		$this->exposeMethod('showModuleBasicView');
		$this->exposeMethod('showRecentActivities');
		$this->exposeMethod('showRecentComments');
		$this->exposeMethod('showRelatedList');
		$this->exposeMethod('showChildComments');
		$this->exposeMethod('getActivities');
		$this->exposeMethod('showRelatedRecords');
		$this->exposeMethod('UpdateStatus');
		$this->exposeMethod('ApproveStatus');
		$this->exposeMethod('doArchive');
		$this->exposeMethod('revertRevision');
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
		
		global $adb;
		$adb = PearDatabase::getInstance();
		
		parent::preProcess($request, false);
        
		$recordId = $request->get('record');
		$moduleName = $request->getModule();
		if(!$this->record){
			$this->record = Vtiger_DetailView_Model::getInstance($moduleName, $recordId);
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
		
		$sql = "SELECT  vtiger_approvalroutepartiescf.cf_6834 as groupid, vtiger_approvalroutepartiescf.cf_6834 as groupname FROM vtiger_approvalroutepartiescf INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_approvalroutepartiescf.approvalroutepartiesid WHERE vtiger_crmentity.deleted=0 GROUP BY vtiger_approvalroutepartiescf.cf_6834";
		$result = $adb->pquery($sql);
		$noofrow = $adb->num_rows($result);
			
			if($noofrow > 0){
			$routarray = array();
			for($i=0; $i<$noofrow ; $i++) {
				
				$groupid=$adb->query_result($result,$i,'groupid');
				$groupname=$adb->query_result($result,$i,'groupname');
				
				$routarray[$i]['groupid'] = $groupid;
				$routarray[$i]['groupname'] = $groupname;
				
			}
		    }
	    
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$current_user = $currentUserModel->get('id');
			
		$sql = "SELECT * FROM vtiger_approvalroutehistorycf WHERE cf_6784='".$recordId."'";
		$result = $adb->pquery($sql);
		$arhrow = $adb->num_rows($result);
				
		    if($arhrow > 0){
					$arh_staus = 'pending'; // Zeeshan code
					// $arh_staus = 'inline';
					for($n=0; $n<$arhrow ; $n++) {
						$crr_staus=$adb->query_result($result,$n,'cf_6792');
						$arh_user=$adb->query_result($result,$n,'cf_6788');
						if($arh_user == $current_user && $crr_staus == 'inline'){
							// echo '111'; exit;
							$arh_staus=$adb->query_result($result,$n,'cf_6792');
							$arhid=$adb->query_result($result,$n,'approvalroutehistoryid');
							$spproveduserid=$adb->query_result($result,$n,'cf_6788');
							break;
						}
					}
				}
				
			
			
		$sqla = "SELECT cf_6976 FROM vtiger_motiwcf WHERE motiwid='".$recordId."'";
		$resulta = $adb->pquery($sqla); 
		$checkArchive = $adb->query_result($resulta,0,'cf_6976'); 
		
		$sqlb = "SELECT * FROM vtiger_approvalroutehistorycf INNER JOIN vtiger_motiwcf ON vtiger_motiwcf.motiwid = vtiger_approvalroutehistorycf.cf_6784 WHERE vtiger_approvalroutehistorycf.cf_6784='".$recordId."' AND vtiger_approvalroutehistorycf.cf_6788='".$current_user."' AND ((vtiger_approvalroutehistorycf.cf_6790 = 1 AND vtiger_approvalroutehistorycf.cf_6792 = 'inline') OR vtiger_motiwcf.cf_7046='Approved')";
		$resultb = $adb->pquery($sqlb);
		$usersequence = $adb->num_rows($resultb);
		//$usersequence = $adb->query_result($resultb,0,'cf_6790');
		
		$sql_1 = "SELECT * FROM vtiger_approvalroutehistorycf WHERE cf_6784='".$recordId."' AND cf_7042=1";
		$result_1 = $adb->pquery($sql_1);
		$rows_1 = $adb->num_rows($result_1);

		//  echo 'recordId = ' . $recordId;  exit;
		// echo 'arh_staus = ' . $arh_staus . ' <br> rows_1 = ' . $rows_1; exit;

		$viewer->assign('MODULE_MODEL', $this->record->getModule());
		$viewer->assign('DETAILVIEW_LINKS', $detailViewLinks);
		$viewer->assign('ROUTEARRAY', $routarray);
		$viewer->assign('APPROVEROUTESTATUS', $arh_staus);
		$viewer->assign('APPROVEROUTEID', $arhid);
		$viewer->assign('APPROVEDUSERID', $spproveduserid);
		$viewer->assign('CHECKARCHIVE', $checkArchive);
		$viewer->assign('USERSEQUENCE', $usersequence);
		$viewer->assign('CURRENTUSER', $current_user);
		$viewer->assign('CHECKREVISION', $rows_1);

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

	function preProcessTplName(Vtiger_Request $request) {
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

	public function postProcess(Vtiger_Request $request) {
		$recordId = $request->get('record');
		$moduleName = $request->getModule();
		
		
		if($moduleName=="Calendar"){
			$recordModel = Vtiger_Record_Model::getInstanceById($recordId);
			$activityType = $recordModel->getType();
			if($activityType=="Events"){
				$moduleName="Events";
			}
		}
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		
		$current_user_id = $currentUserModel->get('id');
		$current_location_id = $currentUserModel->get('location_id');
		$currentRecordLocation = Vtiger_Record_Model::getInstanceById($current_location_id, 'Location');
		$current_user_location = $currentRecordLocation->get('cf_1559');
		 
		$recordMotiw = Vtiger_Record_Model::getInstanceById($recordId, 'MOTIW');
		$motiw_location_id = $recordMotiw->get('cf_6766');
		$motiwRecordLocation = Vtiger_Record_Model::getInstanceById($motiw_location_id, 'Location');
		$motiw_user_location = $motiwRecordLocation->get('cf_1559');
		
		
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		if(!$this->record){
			$this->record = Vtiger_DetailView_Model::getInstance($moduleName, $recordId);
		}
		$detailViewLinkParams = array('MODULE'=>$moduleName,'RECORD'=>$recordId);
		$detailViewLinks = $this->record->getDetailViewLinks($detailViewLinkParams);

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

		$viewer = $this->getViewer($request);

		$viewer->assign('SELECTED_TAB_LABEL', $selectedTabLabel);
		$viewer->assign('SELECTED_RELATION_ID',$relationId);
		$viewer->assign('MODULE_MODEL', $this->record->getModule());
		$viewer->assign('DETAILVIEW_LINKS', $detailViewLinks);
		
		$viewer->assign('CURRENT_USER_ID', $current_user_id);
		
		$viewer->assign('CURRENT_USER_LOCATION', $current_user_location);
		$viewer->assign('MOTIW_USER_LOCATION', $motiw_user_location);
		
		$adb = PearDatabase::getInstance(); 
		$sql = "SELECT vtiger_approvalroutehistorycf.cf_6792 as status FROM vtiger_approvalroutehistory INNER JOIN vtiger_approvalroutehistorycf ON vtiger_approvalroutehistorycf.approvalroutehistoryid = vtiger_approvalroutehistory.approvalroutehistoryid INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_approvalroutehistorycf.approvalroutehistoryid WHERE vtiger_approvalroutehistorycf.cf_6784='".$recordId."' AND vtiger_approvalroutehistorycf.cf_6788=789 AND vtiger_crmentity.deleted=0";
		$result = $adb->pquery($sql);
		$noofrows = $adb->num_rows($result);
		
		    if($noofrows > 0){
				$approvalroutes = array();			
				for($n=0; $n<$noofrows ; $n++) {
					
						 $status=$adb->query_result($result,$n,'status');				
						
						
				}
			
		    }
		
		$viewer->assign('STATUS', $status);

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
			"~/libraries/jquery/twitter-text-js/twitter-text.js",
			"libraries.jquery.multiplefileupload.jquery_MultiFile",
			'~/libraries/jquery/bootstrapswitch/js/bootstrap-switch.min.js',
			'~/libraries/jquery.bxslider/jquery.bxslider.min.js',
			"~layouts/v7/lib/jquery/Lightweight-jQuery-In-page-Filtering-Plugin-instaFilta/instafilta.js",
			'modules.Vtiger.resources.Tag',
			'modules.Google.resources.Map'
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
		
		global $adb;
		$adb = PearDatabase::getInstance();
		
		$recordId = $request->get('record');
		$moduleName = $request->getModule();

		if(!$this->record){
		$this->record = Vtiger_DetailView_Model::getInstance($moduleName, $recordId);
		}
		$recordModel = $this->record->getRecord();
		$recordStrucure = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_DETAIL);
		$structuredValues = $recordStrucure->getStructure();

		$moduleModel = $recordModel->getModule();
		
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$current_user_id = $currentUserModel->get('id');
	
		$sql = "SELECT * FROM vtiger_approvalroutehistory INNER JOIN vtiger_approvalroutehistorycf ON vtiger_approvalroutehistorycf.approvalroutehistoryid = vtiger_approvalroutehistory.approvalroutehistoryid INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_approvalroutehistorycf.approvalroutehistoryid INNER JOIN vtiger_users ON vtiger_users.id = vtiger_approvalroutehistorycf.cf_6788 INNER JOIN vtiger_user2role ON vtiger_user2role.userid=vtiger_users.id INNER JOIN vtiger_role ON vtiger_role.roleid = vtiger_user2role.roleid WHERE vtiger_approvalroutehistorycf.cf_6784='".$recordId."' AND vtiger_crmentity.deleted=0 ORDER BY vtiger_approvalroutehistorycf.cf_6790"; 
		$result = $adb->pquery($sql);
		$noofrows = $adb->num_rows($result);
		$approvalroutehistoryid=$adb->query_result($result,$noofrows-1,'approvalroutehistoryid');
		    if($noofrows > 0){
				$approvalroutes = array();
				for($n=0; $n<$noofrows ; $n++) {
					
					$approvalroutes[$n]['name']=$adb->query_result($result,$n,'name');
					$usersRecord = Vtiger_Record_Model::getInstanceById($adb->query_result($result,$n,'cf_6788'), 'Users');

					$first_name = $usersRecord->get('first_name');
					$last_name = $usersRecord->get('last_name');
					$approvalroutes[$n]['username']=$first_name." ".$last_name;
					$approvalroutes[$n]['sequence']=$adb->query_result($result,$n,'cf_6790');
					$approvalroutes[$n]['status']=ucfirst($adb->query_result($result,$n,'cf_6792'));
					$approvalroutes[$n]['uDate']=$adb->query_result($result,$n,'cf_6794');
					$approvalroutes[$n]['uNotes']=$adb->query_result($result,$n,'cf_7060');
					$approvalroutes[$n]['userDesignation']=$adb->query_result($result,$n,'rolename');
					$approvaluser[] = $adb->query_result($result,$n,'cf_6788');
							
				}
				
				$arh_staus = 'pending';
				for($n=0; $n<$noofrows ; $n++) {
					$crr_staus=$adb->query_result($result,$n,'cf_6792');
					$arh_user=$adb->query_result($result,$n,'cf_6788');
					if($arh_user == $current_user_id && $crr_staus == 'inline'){
					   $arh_staus=$adb->query_result($result,$n,'cf_6792'); 
					   break;
					}
					
				}
		    }

			
		

		if (in_array($current_user_id, $approvaluser))
			  {
			  $userInArray = 1;
			  }
			else
			  {
			  $userInArray = 0;
			  }
		if(!empty($recordId)){	  
	    $recordMotiw = Vtiger_Record_Model::getInstanceById($recordId, 'MOTIW');
		$finalversion = explode(",",$recordMotiw->get('cf_7056'));
		}
		

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('RECORD_STRUCTURE', $structuredValues);
		$viewer->assign('BLOCK_LIST', $moduleModel->getBlocks());
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('IS_AJAX_ENABLED', $this->isAjaxEnabled($recordModel));
		$viewer->assign('NOOFROWS', $noofrows);
		$viewer->assign('APPROVEROUTESTATUS', $arh_staus);
		$viewer->assign('APPROVALROUTESGROUP', $approvalroutes[0]['name']);
		$viewer->assign('APPROVALROUTES', $approvalroutes);
		$viewer->assign('approvalroutehistoryid', $approvalroutehistoryid);
		$viewer->assign('userInArray', $userInArray);
		$viewer->assign('RECORDID', $recordId);
		$viewer->assign('FINALVERSION', $finalversion);
		$viewer->assign('MODULE', $moduleName);

/* 		echo "<pre>";
		print_r($recordModel->getImageDetails());
		exit;
 */
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
	public function getOverlayHeaderScripts(Vtiger_Request $request){
		$moduleName = $request->getModule();
		$jsFileNames = array(
			"modules.$moduleName.resources.Detail",
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return $jsScriptInstances;	
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

		$viewer->assign('CURRENT_USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$pagingModel = new Vtiger_Paging_Model();
		$viewer->assign('PAGING_MODEL', $pagingModel);

		$picklistDependencyDatasource = Vtiger_DependencyPicklist::getPicklistDependencyDatasource($moduleName);
		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE', Vtiger_Functions::jsonEncode($picklistDependencyDatasource));
		
		$viewer->assign('IMAGE_DETAILS', $recordModel->getImageDetails());

		return $viewer->view('ModuleSummaryView.tpl', $moduleName, true);
	}

	/**
	 * Function shows basic detail for the record
	 * @param <type> $request
	 */
	function showModuleBasicView($request) {
		
		global $adb;
		$adb = PearDatabase::getInstance();

		$recordId = $request->get('record');
		$moduleName = $request->getModule();

		if(!$this->record){
			$this->record = Vtiger_DetailView_Model::getInstance($moduleName, $recordId);
		}
		$recordModel = $this->record->getRecord();

		$detailViewLinkParams = array('MODULE'=>$moduleName,'RECORD'=>$recordId);
		$detailViewLinks = $this->record->getDetailViewLinks($detailViewLinkParams);
		
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$current_user_id = $currentUserModel->get('id');
		 
		$sql = "SELECT * FROM vtiger_approvalroutehistory INNER JOIN vtiger_approvalroutehistorycf ON vtiger_approvalroutehistorycf.approvalroutehistoryid = vtiger_approvalroutehistory.approvalroutehistoryid INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_approvalroutehistorycf.approvalroutehistoryid INNER JOIN vtiger_users ON vtiger_users.id = vtiger_approvalroutehistorycf.cf_6788 INNER JOIN vtiger_user2role ON vtiger_user2role.userid=vtiger_users.id INNER JOIN vtiger_role ON vtiger_role.roleid = vtiger_user2role.roleid WHERE vtiger_approvalroutehistorycf.cf_6784='".$recordId."' AND vtiger_crmentity.deleted=0 ORDER BY vtiger_approvalroutehistorycf.cf_6790"; 
		$result = $adb->pquery($sql);
		$noofrows = $adb->num_rows($result);
		$approvalroutehistoryid=$adb->query_result($result,$noofrows-1,'approvalroutehistoryid');
		    if($noofrows > 0){
				$approvalroutes = array();
				for($n=0; $n<$noofrows ; $n++) {
					
					$approvalroutes[$n]['name']=$adb->query_result($result,$n,'name');
					$usersRecord = Vtiger_Record_Model::getInstanceById($adb->query_result($result,$n,'cf_6788'), 'Users');

					$first_name = $usersRecord->get('first_name');
					$last_name = $usersRecord->get('last_name');
					$approvalroutes[$n]['username']=$first_name." ".$last_name;
					$approvalroutes[$n]['sequence']=$adb->query_result($result,$n,'cf_6790');
					$approvalroutes[$n]['status']=ucfirst($adb->query_result($result,$n,'cf_6792'));
					$approvalroutes[$n]['uDate']=$adb->query_result($result,$n,'cf_6794');
					$approvalroutes[$n]['uNotes']=$adb->query_result($result,$n,'cf_7060');
					$approvalroutes[$n]['userDesignation']=$adb->query_result($result,$n,'rolename');
					$approvaluser[] = $adb->query_result($result,$n,'cf_6788');
							
				}
				
				$arh_staus = 'pending';
				for($n=0; $n<$noofrows ; $n++) {
					$crr_staus=$adb->query_result($result,$n,'cf_6792');
					$arh_user=$adb->query_result($result,$n,'cf_6788');
					if($arh_user == $current_user_id && $crr_staus == 'inline'){
					   $arh_staus=$adb->query_result($result,$n,'cf_6792'); 
					   break;
					}
					
				}
		    }

			
		

		if (in_array($current_user_id, $approvaluser))
			  {
			  $userInArray = 1;
			  }
			else
			  {
			  $userInArray = 0;
			  }
         if(!empty($recordId)){	  
	    $recordMotiw = Vtiger_Record_Model::getInstanceById($recordId, 'MOTIW');
		$finalversion = explode(",",$recordMotiw->get('cf_7056'));
		}
					  		
		$sql = "SELECT * FROM vtiger_users INNER JOIN vtiger_user2role ON vtiger_user2role.userid=vtiger_users.id INNER JOIN vtiger_role ON vtiger_role.roleid = vtiger_user2role.roleid WHERE vtiger_users.id='".$current_user_id."'";
		$result = $adb->pquery($sql);
		$currentDesgination = $adb->query_result($result,0,'rolename');

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('MODULE_SUMMARY', $this->showModuleSummaryView($request));
		
		$viewer->assign('NOOFROWS', $noofrows);
		$viewer->assign('APPROVALROUTESGROUP', $approvalroutes[0]['name']);
		$viewer->assign('APPROVALROUTES', $approvalroutes);
		$viewer->assign('APPROVEROUTESTATUS', $arh_staus);
		$viewer->assign('approvalroutehistoryid', $approvalroutehistoryid);
		$viewer->assign('currentDesgination', $currentDesgination);
		$viewer->assign('userInArray', $userInArray);
		$viewer->assign('currentUserId', $current_user_id);
		$viewer->assign('DETAILVIEW_LINKS', $detailViewLinks);
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('IS_AJAX_ENABLED', $this->isAjaxEnabled($recordModel));
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('FINALVERSION', $finalversion);

		$viewer->assign('DETAILVIEW_LINKS', $detailViewLinks);
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('IS_AJAX_ENABLED', $this->isAjaxEnabled($recordModel));
		$viewer->assign('MODULE_NAME', $moduleName);

		$recordStrucure = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_DETAIL);
		$structuredValues = $recordStrucure->getStructure();

		$moduleModel = $recordModel->getModule();
		$viewer->assign('CURRENT_USER_MODEL', Users_Record_Model::getCurrentUserModel());
		
		if(empty($recordId)) {
		
		$current_user = Users_Record_Model::getCurrentUserModel();
		$department_id = $current_user->get('department_id');
		if($department_id=='85844') //for FLT
		{
			$department_id = 85841;
		}
		$location_id = $current_user->get('location_id');
		$company_id = $current_user->get('company_id');
		
		//access company for jz
		$access_company_id = explode(" |##| ",$current_user->get('access_company_id'));
		
		$viewer->assign('USER_COMPANY', $company_id);
		$viewer->assign('USER_DEPARTMENT', $department_id);
		$viewer->assign('USER_LOCATION', $location_id);
		
		}
		
		$viewer->assign('RECORD_STRUCTURE', $structuredValues);
		$viewer->assign('BLOCK_LIST', $moduleModel->getBlocks());
		echo $viewer->view('DetailViewSummaryContents.tpl', $moduleName, true);
	}

	/**
	 * Added to support Engagements view in Vtiger7
	 * @param Vtiger_Request $request
	 */
	function _showRecentActivities(Vtiger_Request $request){
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

		$recentActivities = ModTracker_Record_Model::getUpdates($parentRecordId, $pagingModel,$moduleName);
		$pagingModel->calculatePageRange($recentActivities);

		if($pagingModel->getCurrentPage() == ModTracker_Record_Model::getTotalRecordCount($parentRecordId)/$pagingModel->getPageLimit()) {
			$pagingModel->set('nextPageExists', false);
		}
		$recordModel = Vtiger_Record_Model::getInstanceById($parentRecordId);
		$viewer = $this->getViewer($request);
		$viewer->assign('SOURCE',$recordModel->get('source'));
        $recentActivities = ModTracker_Record_Model::getUpdates($parentRecordId, $pagingModel,$moduleName);

        $totalCount = ModTracker_Record_Model::getTotalRecordCount($parentRecordId);
        $pageLimit = $pagingModel->getPageLimit();
        $pageCount = ceil((int) $totalCount / (int) $pageLimit);
        if($pageCount - $pagingModel->getCurrentPage() == 0) {
            $pagingModel->set('nextPageExists', false);
        } else {
            $pagingModel->set('nextPageExists', true);
        }
		$viewer->assign('RECENT_ACTIVITIES', $recentActivities);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('RECORD_ID',$parentRecordId);
	}

	/**
	 * Function returns recent changes made on the record
	 * @param Vtiger_Request $request
	 */
	function showRecentActivities (Vtiger_Request $request){
		$moduleName = $request->getModule();
		$this->_showRecentActivities($request);

		$viewer = $this->getViewer($request);
		echo $viewer->view('RecentActivities.tpl', $moduleName, true);
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
		$currentUserModel = Users_Record_Model::getCurrentUserModel();

		if(empty($pageNumber)) {
			$pageNumber = 1;
		}

		$pagingModel = new Vtiger_Paging_Model();
		$pagingModel->set('page', $pageNumber);
		if(!empty($limit)) {
			$pagingModel->set('limit', $limit);
		}

		if($request->get('rollup-toggle')) {
			$rollupsettings = ModComments_Module_Model::storeRollupSettingsForUser($currentUserModel, $request);
		} else {
			$rollupsettings = ModComments_Module_Model::getRollupSettingsForUser($currentUserModel, $moduleName);
		}

		if($rollupsettings['rollup_status']) {
			$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentId, $moduleName);
			$recentComments = $parentRecordModel->getRollupCommentsForModule(0, 6);
		}else {
			$recentComments = ModComments_Record_Model::getRecentComments($parentId, $pagingModel);
		}

		$pagingModel->calculatePageRange($recentComments);
		if ($pagingModel->get('limit') < count($recentComments)) {
			array_pop($recentComments);
		}

		$modCommentsModel = Vtiger_Module_Model::getInstance('ModComments');
		$fileNameFieldModel = Vtiger_Field::getInstance("filename", $modCommentsModel);
		$fileFieldModel = Vtiger_Field_Model::getInstanceFromFieldObject($fileNameFieldModel);

		$viewer = $this->getViewer($request);
		$viewer->assign('COMMENTS', $recentComments);
		$viewer->assign('CURRENTUSER', $currentUserModel);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('FIELD_MODEL', $fileFieldModel);
		$viewer->assign('MAX_UPLOAD_LIMIT_MB', Vtiger_Util_Helper::getMaxUploadSize());
		$viewer->assign('MAX_UPLOAD_LIMIT_BYTES', Vtiger_Util_Helper::getMaxUploadSizeInBytes());
		$viewer->assign('COMMENTS_MODULE_MODEL', $modCommentsModel);
		$viewer->assign('ROLLUP_STATUS', $rollupsettings['rollup_status']);
		$viewer->assign('ROLLUPID', $rollupsettings['rollupid']);
		$viewer->assign('PARENT_RECORD', $parentId);

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
				return $targetController->process($request);
			}
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
		$modCommentsModel = Vtiger_Module_Model::getInstance('ModComments');

		$viewer = $this->getViewer($request);
		$viewer->assign('PARENT_COMMENTS', $childComments);
		$viewer->assign('CURRENTUSER', $currentUserModel);
		$viewer->assign('COMMENTS_MODULE_MODEL', $modCommentsModel);

		return $viewer->view('CommentsList.tpl', $moduleName, 'true');
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
		return $this->isAjaxEnabled;
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

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		$cssFileNames = array(
			'~/libraries/jquery/bootstrapswitch/css/bootstrap2/bootstrap-switch.min.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		$headerCssInstances = array_merge($headerCssInstances, $cssInstances);
		return $headerCssInstances;
	}
	
	public function ApproveStatus(Vtiger_Request $request){
		
		// echo 'approve section'; exit;
		global $adb;
		$adb = PearDatabase::getInstance();
		
		$recordId = $request->get('record'); 
		$approverouteid = $request->get('approverouteid');
		$approveduserid = $request->get('approveduserid');
		$comment = $request->get('comment');
		$module = $request->get('module');
		$view = $request->get('view');
		$date_var = date("Y-m-d H:i:s");
	  $currentDateTime = $adb->formatDate($date_var, true);
		$currentUserModel = Users_Record_Model::getCurrentUserModel();

		$recordMotiw = Vtiger_Record_Model::getInstanceById($recordId, 'MOTIW');
		$agreementno = $recordMotiw->get('cf_6760');
		$subject = $recordMotiw->get('name');
						
		$sql = "SELECT  cf_6790  FROM vtiger_approvalroutehistorycf WHERE approvalroutehistoryid='".$approverouteid."'";
		$result = $adb->pquery($sql);
		$sequence=$adb->query_result($result,0,'cf_6790')+1;
		
		if($sequence == 2){
			
			$department_id = $currentUserModel->get('department_id');
			$recordDepartment = Vtiger_Record_Model::getInstanceById($department_id, 'Department');
			$department = $recordDepartment->get('cf_1544');
			$value = date('Y');
			$sql_m =  'SELECT MAX(cf_6838) as max_ordering from vtiger_motiw
					 INNER JOIN vtiger_motiwcf ON vtiger_motiwcf.motiwid = vtiger_motiw.motiwid 
					 where vtiger_motiwcf.cf_6840="'.$value.'"';
		
			$result_m = $adb->pquery($sql_m);
			$row = $adb->fetch_array($result_m);
			
		    if($adb->num_rows($result)==0)
			{
				$ordering = 0;
			}
			else{
				$max_ordering = $row["max_ordering"];
				if ( ! is_numeric($max_ordering))
				{
					$ordering = 0;
				}
				else
				{
					$ordering = $max_ordering;
				}
			}
			
			$serial_number = sprintf("%02d", $ordering+1);
			
			$recordMotiw = Vtiger_Record_Model::getInstanceById($recordId, 'MOTIW');
			$s = $recordMotiw->get("cf_6760");
			if(!empty($s)){
			$subject = $recordMotiw->get("cf_6760");	
			} else {
			$subject = 'GT-'.str_pad($serial_number, 2, "0", STR_PAD_LEFT).'/'.date('Y');
			}
			
			/*$motiwSaveAction = new Vtiger_Save_Action();
			$motiwrequest = new Vtiger_Request();
			$motiwrequest->set("module","MOTIW");
			$motiwrequest->set("action","Save");
			$motiwrequest->set("record",$recordId);
			$motiwrequest->set("name",$subject);
			$motiwrequest->set("cf_6838",$serial_number);
			$motiwrequest->set("cf_6840",date('Y'));
			$motiwrecordModel = $motiwSaveAction->saveRecord($motiwrequest)*/;
			
			$linkModule1='MOTIW';
			$recordModel1 = Vtiger_Record_Model::getInstanceById($recordId, $linkModule1);
			$recordModel1->set('mode', 'edit');
			$recordModel1->set("name",$subject);
			$recordModel1->set("cf_6838",$serial_number);
			$recordModel1->set("cf_6840",date('Y'));
			$recordModel1->save();
			
		}
		
			// Adding 1 if Timur approved. This is required for Legal head filtering 
			if ($recordId > 0 && $currentUserModel->get('id') == 1122){
				// cf_7144 we are using in query generator condition
				// echo "UPDATE `vtiger_motiwcf` SET `cf_7144` = 1 WHERE `motiwid` = $recordId";
				// exit;
				 $sqlFilter = "UPDATE `vtiger_motiwcf` SET `cf_7144` = '1' WHERE `motiwid` = ?";
				 $resultFilter = $adb->pquery($sqlFilter, array($recordId));

			}

										
			$sql = "UPDATE vtiger_approvalroutehistorycf SET cf_6792='approved',cf_6794='".$currentDateTime."',cf_7060='".$comment."',cf_7118='' WHERE approvalroutehistoryid='".$approverouteid."'";
			$result = $adb->pquery($sql);
						
		 			
			$sql_2 = "SELECT approvalroutehistoryid FROM vtiger_approvalroutehistorycf WHERE cf_6784='".$recordId."' AND cf_6790='".$sequence."'";
			$result_2 = $adb->pquery($sql_2);
			$rows_2 = $adb->num_rows($result_2);
			$aprrovalroutehistorycfid=$adb->query_result($result_2,0,'approvalroutehistoryid');
					
			$date_var = date("Y-m-d H:i:s");
			$currentDateTime = $adb->formatDate($date_var, true);
				
			/*$vtigerSaveAction = new Vtiger_Save_Action();
			$request = new Vtiger_Request();
			$request->set("module","ApprovalRouteHistory");
			$request->set("action","Save");
			$request->set("record",$aprrovalroutehistorycfid);
			$request->set("cf_6792",'inline');
			$recordModel = $vtigerSaveAction->saveRecord($request);*/
			
			if(!empty($aprrovalroutehistorycfid)){
			
			$linkModule2='ApprovalRouteHistory';
			$recordModel2 = Vtiger_Record_Model::getInstanceById($aprrovalroutehistorycfid, $linkModule2);
			$recordModel2->set('mode', 'edit');
			$recordModel2->set("cf_6792",'inline');
			$recordModel2->save();
			
			}
					
			$sql_3 = "SELECT MAX(cf_6790) as cf_6790 FROM vtiger_approvalroutehistorycf WHERE cf_6784='".$recordId."'";
			$result_3 = $adb->pquery($sql_3);
			$rows_3 = $adb->num_rows($result_3);
			$maxSequence=$adb->query_result($result_3,0,'cf_6790');
			
			$sql_4 = "SELECT * FROM vtiger_approvalroutehistorycf WHERE cf_6784='".$recordId."' AND cf_6790='".$maxSequence."'";
			$result_4 = $adb->pquery($sql_4);
			$rows_4 = $adb->num_rows($result_4);
			$approStatus=$adb->query_result($result_4,0,'cf_6792');
			$appstatus = ucfirst($approStatus);
						
			$sql = "UPDATE vtiger_motiwcf SET cf_7046='".$appstatus."' WHERE motiwid='".$recordId."'";
		    $result = $adb->pquery($sql);
			
			if($appstatus == 'Approved'){
			
		    $sql = "UPDATE vtiger_motiwcf SET cf_6976='1' WHERE motiwid='".$recordId."'";
		    $result = $adb->pquery($sql);
			
			}
			
			$sql = "SELECT approvalroutehistoryid, cf_6788, cf_6790  FROM vtiger_approvalroutehistorycf WHERE cf_6790 = 1 AND cf_6784='".$recordId."'";
			$result = $adb->pquery($sql);
			$rows = $adb->num_rows($result);
			if($rows > 0 && !empty($comment)){
				
				$userid = $adb->query_result($result,$i,'cf_6788');
				$approvalroutehistoryid = $adb->query_result($result,$i,'approvalroutehistoryid');
				
				$recordUser = Vtiger_Record_Model::getInstanceById($userid, 'Users');
				$email = $recordUser->get('email1');
				$party = $recordUser->get('first_name')." ".$recordUser->get('last_name');
				$sequenceorder = $adb->query_result($result,$i,'cf_6790');
				
					$recordApprovedUser = Vtiger_Record_Model::getInstanceById($approveduserid, 'Users');
					$approvalPerson = $recordApprovedUser->get('first_name')." ".$recordApprovedUser->get('last_name'); 
							
				$message = "<p>Dear $party,</p>
	
				<p>Please note that $approvalPerson has approved MOTIW document $subject.</p>
				
				<p><a href='http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]'  style='text-decoration:underline'>Link to document</a></p>
				----------
				<br>
				### For Comments ###
				<p>$comment</p>
				
				";
			
				$to  = $email;
				$subject = "Approval Notification";
				
				$from = "erp@globalinklogistics.com";
				$cc = "s.mehtab@globalinklogistics.com";
									  
					$from = "From: ".$from." <".$from.">";
					$headers = "MIME-Version: 1.0" . "\n";
					$headers .= "Content-type:text/html;charset=UTF-8" . "\n";
					$headers .= $from . "\n";
					$headers .= 'Reply-To: '.$to.'' . "\n";
					//$headers .= "CC:" . $cc . "\r\n";
					mail($to,$subject,$message,$headers);
			}


		/*
			1. Get agreement creator head email
			2. Check email in userlist and get his head email
			2. Check if head email is equal to inline person email
			4. Send email notification
		*/

		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		// $department_id = $currentUserModel->get('department_id');
		$creatorEmail = trim($currentUserModel->get('email1'));

		// Creator user info
		$sqlCreator = "SELECT cf_3385 FROM `vtiger_userlistcf` WHERE cf_3355 like '%$creatorEmail%'";
		$resCreator = $adb->pquery($sqlCreator);
		$creatorHeadId = $adb->query_result($resCreator,0,'cf_3385');

		// Creator head user info
		if ($creatorHeadId > 0){
			$sqlHead = "SELECT vtiger_userlistcf.cf_3355, vtiger_userlist.name 
									FROM `vtiger_userlistcf`
									INNER JOIN vtiger_userlist ON vtiger_userlist.userlistid = vtiger_userlistcf.userlistid
									WHERE vtiger_userlistcf.userlistid = $creatorHeadId";
			$resHead = $adb->pquery($sqlHead);
			$creatorHeadEmail = $adb->query_result($resHead,0,'cf_3355');
			$creatorHeadName = $adb->query_result($resHead,0,'name');


			$sqlHeadApproval = "SELECT vtiger_users.email1 
			FROM `vtiger_approvalroutehistorycf` 
			LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_approvalroutehistorycf.cf_6788
			WHERE vtiger_approvalroutehistorycf.`cf_6784` = $recordId AND vtiger_approvalroutehistorycf.cf_6792 = 'inline'
			";
			$resHeadApproval = $adb->pquery($sqlHeadApproval);
			$headApprovalUserEmail = $adb->query_result($resHeadApproval,0,'email1'); 
			
		}

/* 			if (strlen($creatorHeadEmail) > 0 && $creatorHeadEmail == $headApprovalUserEmail){
				$this->sendApproveEmail();
			}	
 */

/* 			
 		echo 'creatorHeadEmail='.$creatorHeadEmail.'headApprovalUserEmail='.$headApprovalUserEmail.'-';
		exit;
   */


		$loadUrl = "index.php?module=MOTIW&view=Detail&record=".$recordId;
        echo '<script> 
			var url= "'.$loadUrl.'"; 
			window.location = url; 
		</script>';
	}
	
	public function sendApproveEmail(){



		$link = 'https://gems.globalink.net/';
		$link .= "index.php?module=MOTIW&view=Detail&record=".$recordId;
		
		$to = "r.gusseinov@globalinklogistics.com";
		$date_time = date('Y-m-d H:i:s');
		$from = $to; 
		$body = '';
		$message_status = 'Please approve below request';

		$subject = 'Contract agreement approval';
		$body .= "<html><head> <style> #calendar_notification tr td{ margin:3px; } </style> </head>
							<body><table id='calendar_notification'> ";
		$body .= "<tr><td colspan=2>Dear <b>".$creatorHeadName.",</b> </td></tr>";
		$body .= "<tr><td colspan=2> $message_status </td></tr>
				
							<tr><td colspan=2>Link: <a href='$link'> Link to GEMS </a></td></tr>";
		$body .= "</table> </body> </html> ";
																	
		// Set content-type when sending HTML email
		$headers = "MIME-Version: 1.0" . "\n";
		$headers .= "Content-type:text/html;charset=UTF-8" . "\n";
	
		$headers .= $from . "\n";
		$headers .= 'Reply-To: '.$to.'' . "\n";

		require_once("modules/Emails/mail.php");
		$r = send_mail('MOTIW', $to, $from, $from, $subject, $body, $cc ,'','','','',true);		

	}
	
	public function doArchive(Vtiger_Request $request){
		
		global $adb;
		$adb = PearDatabase::getInstance();
		
		$recordId = $request->get('record'); 
		$status = $request->get('status'); 
		
		$sql = "UPDATE vtiger_motiwcf SET cf_6976='".$status."' WHERE motiwid='".$recordId."'";
		$result = $adb->pquery($sql);
		
		$loadUrl = "index.php?module=MOTIW&view=Detail&record=".$recordId;
        echo '<script> 
			var url= "'.$loadUrl.'"; 
			window.location = url; 
		</script>';
		
	}
	
	public function revertRevision(Vtiger_Request $request){
		
		// echo 'rev'; exit;
		global $adb;
		$adb = PearDatabase::getInstance();
		
		$recordId = $request->get('record'); 
		$approverouteid = $request->get('approverouteid');
		$approveduserid = $request->get('approveduserid');
		$module = $request->get('module');
		$view = $request->get('view');
		
		$saveActionNew = new Vtiger_Save_Action();
		$vtigerrequest = new Vtiger_Request();
		$vtigerrequest->set("module","ApprovalRouteHistory");
		$vtigerrequest->set("action","Save");
		$vtigerrequest->set("record",$approverouteid);
		$vtigerrequest->set("cf_6792",'approved');
		$vtigerrequest->set("cf_6794",$currentDateTime);
		$vtigerrecordModel = $saveActionNew->saveRecord($vtigerrequest);
		
		$sql_1 = "SELECT * FROM vtiger_approvalroutehistorycf WHERE cf_6784='".$recordId."' AND cf_7042=1";
		$result_1 = $adb->pquery($sql_1);
		$rows_1 = $adb->num_rows($result_1);
								
			$newaprrovalroutehistorycfid=$adb->query_result($result_1,0,'approvalroutehistoryid');
			$date_var = date("Y-m-d H:i:s");
			$currentDateTime = $adb->formatDate($date_var, true);
				
			$newvtigerSaveAction = new Vtiger_Save_Action();
			$newrequest = new Vtiger_Request();
			$newrequest->set("module","ApprovalRouteHistory");
			$newrequest->set("action","Save");
			$newrequest->set("record",$newaprrovalroutehistorycfid);
			$newrequest->set("cf_6792",'inline');
			$newrequest->set("cf_7042",'');
			$newrecordModel = $newvtigerSaveAction->saveRecord($newrequest);
	
		$loadUrl = "index.php?module=MOTIW&view=Detail&record=".$recordId;
        echo '<script> 
			var url= "'.$loadUrl.'"; 
			window.location = url; 
		</script>';
		
	}

}
