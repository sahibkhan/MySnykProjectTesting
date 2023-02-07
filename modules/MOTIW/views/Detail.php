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

		// $this->exposeMethod('doArchive');
		// $this->exposeMethod('revertRevision');
		$this->exposeMethod('cancelContract');

		$this->exposeMethod('addApprovalParties');
		$this->exposeMethod('removeApprovalPartie');
		$this->exposeMethod('contractAgreementNumber');
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
						

						// Exception for Baku office.
						// Asim can approve instead of Junaid

						if (($arh_user == $current_user && $crr_staus == 'inline') || ($arh_user == 238 && $current_user == 534 && $crr_staus == 'inline')
								 || ($arh_user == 789 && $current_user == 1542 && $crr_staus == 'inline')){
							// echo 'arh_user'.$arh_user.' current_user_id'.$current_user_id; exit;
						// if($arh_user == $current_user && $crr_staus == 'inline'){
							// echo '111'; exit;
							$arh_staus=$adb->query_result($result,$n,'cf_6792');
							$arhid=$adb->query_result($result,$n,'approvalroutehistoryid');
							$spproveduserid=$adb->query_result($result,$n,'cf_6788');
							break;
						}
					}
					
				}

			
			
		$sqla = "SELECT cf_7046 FROM vtiger_motiwcf WHERE motiwid='".$recordId."'";
		$resulta = $adb->pquery($sqla); 
		// $checkArchive = $adb->query_result($resulta,0,'cf_6976'); 
		$currentStatus = $adb->query_result($resulta,0,'cf_7046'); 
		
		$sqlb = "SELECT * FROM vtiger_approvalroutehistorycf INNER JOIN vtiger_motiwcf ON vtiger_motiwcf.motiwid = vtiger_approvalroutehistorycf.cf_6784 WHERE vtiger_approvalroutehistorycf.cf_6784='".$recordId."' AND vtiger_approvalroutehistorycf.cf_6788='".$current_user."' AND ((vtiger_approvalroutehistorycf.cf_6790 = 1 AND vtiger_approvalroutehistorycf.cf_6792 = 'inline') OR vtiger_motiwcf.cf_7046='Approved')";
		$resultb = $adb->pquery($sqlb);
		$usersequence = $adb->num_rows($resultb);
		//$usersequence = $adb->query_result($resultb,0,'cf_6790');
		
/* 		$sql_1 = "SELECT * FROM vtiger_approvalroutehistorycf WHERE cf_6784='".$recordId."' AND cf_7042=1";
		$result_1 = $adb->pquery($sql_1);
		$rows_1 = $adb->num_rows($result_1); */

		//  echo 'recordId = ' . $recordId;  exit;
		// echo 'arh_staus = ' . $arh_staus . ' <br> rows_1 = ' . $rows_1; exit;

		$viewer->assign('MODULE_MODEL', $this->record->getModule());
		$viewer->assign('DETAILVIEW_LINKS', $detailViewLinks);
		$viewer->assign('ROUTEARRAY', $routarray);
		$viewer->assign('APPROVEROUTESTATUS', $arh_staus);
		$viewer->assign('APPROVEROUTEID', $arhid);
		$viewer->assign('APPROVEDUSERID', $spproveduserid);
		// $viewer->assign('CHECKARCHIVE', $checkArchive);
		$viewer->assign('CURRENT_STATUS', $currentStatus);

		$viewer->assign('USERSEQUENCE', $usersequence);
		$viewer->assign('CURRENTUSER', $current_user);
		// $viewer->assign('CHECKREVISION', $rows_1);

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
	
		$sql = $this->getApprovalParties($recordId);

		$result = $adb->pquery($sql);
		$noofrows = $adb->num_rows($result);
		$approvalroutehistoryid=$adb->query_result($result,$noofrows-1,'approvalroutehistoryid');
		    if($noofrows > 0){
				$approvalroutes = array();
				for($n=0; $n<$noofrows ; $n++) {
					

					$approvalroutes[$n]['name']=$adb->query_result($result,$n,'name');
					$approvalroutes[$n]['routeid'] = $adb->query_result($result,$n,'approvalroutehistoryid');
					$approvalroutes[$n]['whoAddedApprovalPartie'] = $adb->query_result($result,$n,'cf_7118');
					$usersRecord = Vtiger_Record_Model::getInstanceById($adb->query_result($result,$n,'cf_6788'), 'Users');

					$first_name = $usersRecord->get('first_name');
					$last_name = $usersRecord->get('last_name');
					$approvalroutes[$n]['username']=$first_name." ".$last_name;
					$approvalroutes[$n]['sequence']=$adb->query_result($result,$n,'cf_6790');
					$approvalroutes[$n]['status']=ucfirst($adb->query_result($result,$n,'cf_6792'));
					$approvalroutes[$n]['uDate']=$adb->query_result($result,$n,'cf_6794');
					$approvalroutes[$n]['uNotes']=$adb->query_result($result,$n,'cf_7060');


					$approvalPartieEmail = strtolower(trim($adb->query_result($result,$n,'email1')));

					$sqlUser = "SELECT cf_3341, cf_3349, cf_3421
											FROM `vtiger_userlistcf`
											WHERE cf_3355 = ?";
					$sqlUserRes = $adb->pquery($sqlUser, array($approvalPartieEmail));
					$approvalPartiePosition = $adb->query_result($sqlUserRes, 0, 'cf_3341');
					$department_id = $adb->query_result($sqlUserRes, 0, 'cf_3349');
					$location_id = $adb->query_result($sqlUserRes, 0, 'cf_3421');
 

					$sqlDepartment = "SELECT cf_1542
														FROM `vtiger_departmentcf`
														WHERE departmentid = ?";
					$departmentResult = $adb->pquery($sqlDepartment, array($department_id));
					$partieDepartmentName = $adb->query_result($departmentResult, 0, 'cf_1542');


					$sqlLocation = "SELECT cf_1559
														FROM `vtiger_locationcf`
														WHERE locationid = ?";
					$locationResult = $adb->pquery($sqlLocation, array($location_id));
					$partieLocationName = $adb->query_result($locationResult, 0, 'cf_1559');


/* 					$recordDepartment = Vtiger_Record_Model::getInstanceById($department_id, 'Department');
					$departmentCode = $recordDepartment->get('cf_1542');

					$recordLocation = Vtiger_Record_Model::getInstanceById($location_id, 'Location');
					$locationCode = $recordLocation->get('cf_1559'); */

					$approvalPartiePosition .= ", ".$partieLocationName.", ".$partieDepartmentName;

					$approvalroutes[$n]['userDesignation'] = $approvalPartiePosition;
					// $approvalroutes[$n]['userDesignation']=$adb->query_result($result,$n,'rolename');
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
		$viewer->assign('currentUserId', $current_user_id);
		$viewer->assign('APPROVEROUTESTATUS', $arh_staus);
		$viewer->assign('APPROVALROUTESGROUP', $approvalroutes[0]['name']);
		$viewer->assign('APPROVALROUTES', $approvalroutes);
		$viewer->assign('approvalroutehistoryid', $approvalroutehistoryid);
		$viewer->assign('userInArray', $userInArray);
		$viewer->assign('RECORDID', $recordId);
		$viewer->assign('FINALVERSION', $finalversion);
		$viewer->assign('MODULE', $moduleName);


 		$sqlUsers = "SELECT id, first_name, last_name FROM vtiger_users WHERE status = ? "; 
		$resultUsers = $adb->pquery($sqlUsers, array('Active'));
		$noofrows = $adb->num_rows($resultUsers);

		for($u=0; $u<$noofrows; $u++) {
			$userId = $adb->query_result($resultUsers, $u,'id');
			$userName = $adb->query_result($resultUsers, $u,'first_name').' ' .$adb->query_result($resultUsers, $u,'last_name');
			$USER_LIST[] = array("id" => $userId, "name" => $userName);
		}		
		$viewer->assign('USER_LIST', $USER_LIST);
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
		 
		$sqlApproveRouteInfo = $this->getApprovalParties($recordId);// 

		$result = $adb->pquery($sqlApproveRouteInfo);
		$noofrows = $adb->num_rows($result);
		$approvalroutehistoryid=$adb->query_result($result,$noofrows-1,'approvalroutehistoryid');
		    if($noofrows > 0){
				$approvalroutes = array();
				for($n=0; $n<$noofrows ; $n++) {

					$approvalroutes[$n]['routeid'] = $adb->query_result($result,$n,'approvalroutehistoryid');
					$approvalroutes[$n]['whoAddedApprovalPartie'] = $adb->query_result($result,$n,'cf_7118');
					
					$approvalroutes[$n]['name']=$adb->query_result($result,$n,'name');
					$usersRecord = Vtiger_Record_Model::getInstanceById($adb->query_result($result,$n,'cf_6788'), 'Users');

					$first_name = $usersRecord->get('first_name');
					$last_name = $usersRecord->get('last_name');
					$approvalroutes[$n]['username']=$first_name." ".$last_name;
					$approvalroutes[$n]['sequence']=$adb->query_result($result,$n,'cf_6790');
					$approvalroutes[$n]['status']=ucfirst($adb->query_result($result,$n,'cf_6792'));
					$approvalroutes[$n]['uDate']=$adb->query_result($result,$n,'cf_6794');
					$approvalroutes[$n]['uNotes']=$adb->query_result($result,$n,'cf_7060');


					$approvalPartieEmail = strtolower(trim($adb->query_result($result,$n,'email1')));

					$sqlUser = "SELECT cf_3341, cf_3349, cf_3421
											FROM `vtiger_userlistcf`
											WHERE cf_3355 = ?";
					$sqlUserRes = $adb->pquery($sqlUser, array($approvalPartieEmail));
					$approvalPartiePosition = $adb->query_result($sqlUserRes, 0, 'cf_3341');
					$department_id = $adb->query_result($sqlUserRes, 0, 'cf_3349');
					$location_id = $adb->query_result($sqlUserRes, 0, 'cf_3421');
 

					$sqlDepartment = "SELECT cf_1542
														FROM `vtiger_departmentcf`
														WHERE departmentid = ?";
					$departmentResult = $adb->pquery($sqlDepartment, array($department_id));
					$partieDepartmentName = $adb->query_result($departmentResult, 0, 'cf_1542');


					$sqlLocation = "SELECT cf_1559
														FROM `vtiger_locationcf`
														WHERE locationid = ?";
					$locationResult = $adb->pquery($sqlLocation, array($location_id));
					$partieLocationName = $adb->query_result($locationResult, 0, 'cf_1559');

/* 
					
					$recordDepartment = Vtiger_Record_Model::getInstanceById($department_id, 'Department');
					$departmentCode = $recordDepartment->get('cf_1542');

					$recordLocation = Vtiger_Record_Model::getInstanceById($location_id, 'Location');
					$locationCode = $recordLocation->get('cf_1559');
					*/


					$approvalPartiePosition .= ", ".$partieLocationName.", ".$partieDepartmentName; 


					$approvalroutes[$n]['userDesignation'] = $approvalPartiePosition;
					// $approvalroutes[$n]['userDesignation']=$adb->query_result($result,$n,'rolename');
					$approvaluser[] = $adb->query_result($result,$n,'cf_6788');
							
				}
				
				$arh_staus = 'pending';
				for($n=0; $n<$noofrows ; $n++) {
					$crr_staus=$adb->query_result($result,$n,'cf_6792');
					$arh_user=$adb->query_result($result,$n,'cf_6788');

						// Exception for Baku office.
						// Asim can approve instead of Junaid					
					if (($arh_user == $current_user_id && $crr_staus == 'inline') || ($arh_user == 238 && $current_user_id == 534 && $crr_staus == 'inline')
							|| ($arh_user == 789 && $current_user == 1542 && $crr_staus == 'inline')){
						// echo 'arh_user'.$arh_user.' current_user_id'.$current_user_id; exit;
					   $arh_staus=$adb->query_result($result,$n,'cf_6792'); 
					   break;
					}
					
				}
				// exit;
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
		// echo 'current_user_id = ' . $current_user_id; exit;
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
		
/* 		if(empty($recordId)) {
		
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
		
		} */

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
		$NO_OF_COMMENTS = 50; 
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
		/* if(!empty($limit)) {
			$pagingModel->set('limit', $limit);
		} */
		$pagingModel->set('limit', $NO_OF_COMMENTS);


		if($request->get('rollup-toggle')) {
			$rollupsettings = ModComments_Module_Model::storeRollupSettingsForUser($currentUserModel, $request);
		} else {
			$rollupsettings = ModComments_Module_Model::getRollupSettingsForUser($currentUserModel, $moduleName);
		}

	
		// if($rollupsettings['rollup_status']) { // If rollup is on
			// $parentRecordModel = Vtiger_Record_Model::getInstanceById($parentId, $moduleName);
			// $recentComments = $parentRecordModel->getRollupCommentsForModule(0, 50);
		/* }else {
			$recentComments = ModComments_Record_Model::getRecentComments($parentId, $pagingModel);
		} */

		$recentComments = ModComments_Record_Model::getRecentComments($parentId, $pagingModel);


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
/* 		if(is_null($this->isAjaxEnabled)){
			$this->isAjaxEnabled = $recordModel->isEditable();
		}
		return $this->isAjaxEnabled; */
		return false;
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
		// $agreementno = $recordMotiw->get('cf_6760');
		$subject = $recordMotiw->get('name');
		$typeOfDocument	= $recordMotiw->get('cf_6758');
						
		$sql = "SELECT cf_6790
						FROM vtiger_approvalroutehistorycf
						WHERE approvalroutehistoryid='".$approverouteid."'";
		$result = $adb->pquery($sql);
		$sequence = $adb->query_result($result,0,'cf_6790') + 1;

		// echo 'typeOfDocument = ' . $typeOfDocument; exit;

		// Once Owner of agreement is approved
		if($sequence == 2){
			$this->generateReferenceNo($request);
		}
		// echo 'currentDateTime = ' . $currentDateTime.' comment = '.$comment.' approverouteid = '.$approverouteid; exit;

		// Adding filter flag as 1. If Legal Head approved. This is required for Legal head filtering 
		$this->setFilterFlagForLegalHead($recordId);		

		$comment = $adb->sql_escape_string($comment);
		$sql = "UPDATE vtiger_approvalroutehistorycf 
						SET cf_6792='approved',
						cf_6794 = ?,
						cf_7060 = ?
						WHERE approvalroutehistoryid = ?";
		$result = $adb->pquery($sql, array($currentDateTime, $comment, $approverouteid));	


				
		$sql_2 = "SELECT vtiger_approvalroutehistorycf.approvalroutehistoryid, vtiger_approvalroutehistorycf.cf_6788
							FROM vtiger_approvalroutehistorycf 
							INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_approvalroutehistorycf.approvalroutehistoryid
							WHERE vtiger_approvalroutehistorycf.cf_6784='".$recordId."' AND vtiger_approvalroutehistorycf.cf_6790='".$sequence."' 
							AND vtiger_crmentity.deleted = 0";
								
			$result_2 = $adb->pquery($sql_2);
			$rows_2 = $adb->num_rows($result_2);
			$aprrovalroutehistorycfid = $adb->query_result($result_2,0,'approvalroutehistoryid');
			$currentApprovalPartie = $adb->query_result($result_2,0,'cf_6788');
					
			$date_var = date("Y-m-d H:i:s");
			$currentDateTime = $adb->formatDate($date_var, true);


			if(!empty($aprrovalroutehistorycfid)){
				$linkModule2='ApprovalRouteHistory';
				$recordModel2 = Vtiger_Record_Model::getInstanceById($aprrovalroutehistorycfid, $linkModule2);
				$recordModel2->set('mode', 'edit');
				$recordModel2->set("cf_6792",'inline');
				$recordModel2->save();

				// Send email to Aftab once Zhanna approved agreement 
				if ($currentApprovalPartie == 6){
					$details['ref'] = $subject;
					$details['recordId'] = $recordId;
					$details['name'] = 'Aftab Ahmed';
					$details['to'] = 'a.ahmed@globalinklogistics.com';
					// $details['cc'] = 'r.gusseinov@globalinklogistics.com';
					$this->sendApproveEmail($details);
				}
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
						


			// Update main status of contract
			$queryApprovalParties = $this->getApprovalParties($recordId);
			$resultParties = $adb->pquery($queryApprovalParties);
			$nParties = $adb->num_rows($resultParties);
			$countApproved = 0;

			for ($p=0;$p<$nParties;$p++){
				$approveStatus = $adb->query_result($resultParties, $p, 'cf_6792');
				if ($approveStatus == 'approved') $countApproved ++;
			}

			if ($countApproved == $nParties && $typeOfDocument == 'Agreement') {
				$appstatus = 'Approved';
				$this->createServiceAgreement($recordMotiw);
			}

			$sql = "UPDATE vtiger_motiwcf SET cf_7046='".$appstatus."' WHERE motiwid='".$recordId."'";
		  $result = $adb->pquery($sql);

			
			

			// Current Reviewer
			$currentApprovalPartieModel = Vtiger_Record_Model::getInstanceById($currentApprovalPartie, 'Users');
			$currentReviewer = $currentApprovalPartieModel->get('first_name').' '. $currentApprovalPartieModel->get('last_name');
			$this->updateCurrentReviewer($currentReviewer, $recordId);
			 
		/*
			1. Get agreement creator head email
			2. Check email in userlist and get his head email
			2. Check if head email is equal to inline person email
			4. Send email notification
		*/

		$recordMotiwUser = Vtiger_Record_Model::getInstanceById($recordMotiw->get('assigned_user_id'), 'Users');
		$creatorEmail = trim($recordMotiwUser->get('email1'));

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


			$sqlHeadApproval = $this->getApprovalRoute($recordId);
			$resHeadApproval = $adb->pquery($sqlHeadApproval);
			$headApprovalUserEmail = trim($adb->query_result($resHeadApproval,0,'email1'));
			
		}

			//Gathering email info
			$details = array();
			if (strlen($recordMotiw->get('name')) > 3) $motiwRef = $recordMotiw->get('name'); else $motiwRef = $recordMotiw->get('cf_6760'); 
			$details['ref'] = $motiwRef;
			$details['recordId'] = $recordId;
			$details['account'] = $recordMotiw->get('cf_6362');
			
			// Sending email to GM/BM 
			if ((strlen($creatorHeadEmail) > 0) && (($creatorHeadEmail != 's.khan@globalinklogistics.com') && ($creatorHeadEmail == $headApprovalUserEmail))){
				$details['name'] = $creatorHeadName;
				$details['to'] = $headApprovalUserEmail;
				$this->sendApproveEmail($details);
			}


			// Sending email to Legal Head (Timur Makarov)
			$sqlHeadApproval = $this->getApprovalRoute($recordId);
			$resHeadApproval = $adb->pquery($sqlHeadApproval);
			$currentInlineUser = $adb->query_result($resHeadApproval,0,'cf_6788');


		$loadUrl = "index.php?module=MOTIW&view=Detail&record=".$recordId;
        echo '<script> 
			var url= "'.$loadUrl.'"; 
			window.location = url; 
		</script>';
	}


	public function getApprovalRoute($recordId){
		return "SELECT vtiger_users.email1, vtiger_approvalroutehistorycf.cf_6788
		FROM `vtiger_approvalroutehistorycf` 
		LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_approvalroutehistorycf.cf_6788
		WHERE vtiger_approvalroutehistorycf.`cf_6784` = $recordId AND vtiger_approvalroutehistorycf.cf_6792 = 'inline'
		";
	}
	
	public function generateReferenceNo(Vtiger_Request $request){

		global $adb;
		$recordId = $request->get('record');
		$recordMotiw = Vtiger_Record_Model::getInstanceById($recordId, 'MOTIW');
		$subject = $recordMotiw->get('name');

		// GENERATE Ref. no for agreement
		// Creator department code
		$department_id = $recordMotiw->get('cf_6768');
		$recordDepartment = Vtiger_Record_Model::getInstanceById($department_id, 'Department');
		$departmentCode = $recordDepartment->get('cf_1542');
		
		// Creator location code
		$location_id = $recordMotiw->get('cf_6766');
		$recordLocation = Vtiger_Record_Model::getInstanceById($location_id, 'Location');
		$locationCode = $recordLocation->get('cf_1559');

		// Current month and year
		$monthYear = Date('m').''.Date('y');
		$curYear = Date('Y');

		$recordsInfo = $this->countOfRecordsByLocationAndDepartment($department_id, $location_id);
		$countOfRecords = $recordsInfo['countOfRecords'];
		$maxAgreementNo = $recordsInfo['maxAgreementNo'];
 

		if ($countOfRecords > 0 && $maxAgreementNo == 0){
			throw new Exception('Incorrect contract number. Please contact IT Support to solve issue');
		}

		if ($subject == '-'){
			$maxAgreementNo ++;
			$refNo = trim(sprintf("%'.03d\n", $maxAgreementNo));
			$subject = $departmentCode.'-'.$refNo.'/'.$monthYear.'/'.$locationCode;
		}		

		$recordModel1 = Vtiger_Record_Model::getInstanceById($recordId, 'MOTIW');
		$recordModel1->set('mode', 'edit');
		$recordModel1->set("name", $subject);
		$recordModel1->set("cf_6840", date('Y'));
		$recordModel1->save();
	}

	public function countOfRecordsByLocationAndDepartment($departmentId, $locationId){
		global $adb;
		$curYear = date('Y');
		$data = array();
		// Number of records relevant to current location and department
		$queryRecords =  "SELECT vtiger_motiw.name, vtiger_crmentity.crmid
								
											FROM vtiger_motiwcf
											INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_motiwcf.motiwid
											INNER JOIN vtiger_motiw ON vtiger_motiw.motiwid = vtiger_crmentity.crmid
											
											WHERE vtiger_motiwcf.cf_6768 = ? AND vtiger_motiwcf.cf_6766 = ? AND YEAR(vtiger_crmentity.createdtime) = ?
											AND vtiger_crmentity.createdtime >= '2021-02-01' AND LENGTH(vtiger_motiw.name) > 12";

		/*
			2021-02-01 - From this date system started to generate proper contract number : DEP-001/dayyear/Location	
			LENGTH(vtiger_motiw.name) > 12 - Means consider only standard type of number
		*/

		$resultRecords = $adb->pquery($queryRecords, array($departmentId, $locationId, $curYear));
		$countOfRecords = $adb->num_rows($resultRecords);

		$maxAgreementNo = 0;
		for ($i=0; $i<$countOfRecords; $i++){
			$agreementNo = $adb->query_result($resultRecords, $i, 'name');
			$crmid = $adb->query_result($resultRecords, $i, 'crmid');

			$agreementNoPart = explode('/', $agreementNo);
			$lastNo = preg_replace('/[^0-9.]+/', '', $agreementNoPart[0]);
			if ($lastNo > $maxAgreementNo) $maxAgreementNo = $lastNo;
				// echo 'lastNo = '.$maxAgreementNo.' crmid = ' . $crmid.' <br>';
		}

		// exit;
		$data['countOfRecords'] = $countOfRecords;		
		$data['maxAgreementNo'] = $maxAgreementNo;
				
		return $data;				
	}

	public function setFilterFlagForLegalHead($recordId){
		global $adb;
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		if ($recordId > 0 && $currentUserModel->get('id') == 1122){
			$sqlFilter = "UPDATE `vtiger_motiwcf` SET `cf_7144` = '1' WHERE `motiwid` = ?";
			$resultFilter = $adb->pquery($sqlFilter, array($recordId));
		}
	}


	public function createServiceAgreement($request){
		global $adb;
		$CMSRecordId = $request->get('record_id');
		$CMSCreatorId = $request->get('assigned_user_id');
		$accountId = $request->get('cf_6362');

		$queryServiceAgreement =  "SELECT crmid FROM `vtiger_crmentityrel` WHERE crmid = ? AND `module` = ? AND `relmodule` = ?";
		$resultServiceAgreement = $adb->pquery($queryServiceAgreement, array($CMSRecordId, 'MOTIW', 'ServiceAgreement'));
		$nOfRecords = $adb->num_rows($resultServiceAgreement);

		if ($nOfRecords == 0){
		
			// SERVICE AGREEMENT
			// $serviceAgreementCreatorId = 1542;
			$recordModelServiceAgreement = Vtiger_Record_Model::getCleanInstance('ServiceAgreement');		
			$recordModelServiceAgreement->set('assigned_user_id', $CMSCreatorId);
			$recordModelServiceAgreement->set('mode', 'create');
			$recordModelServiceAgreement->set("name", $request->get('name'));

			$CMSCreatorModel = Vtiger_Record_Model::getInstanceById($request->get('assigned_user_id'), 'Users');
			

			// Agreement date
			if ($request->get('cf_6772')){
				$recordModelServiceAgreement->set("cf_6018", $request->get('cf_6772'));
			}
			
			// Agreement validity
			if ($request->get('cf_6774')){
				$recordModelServiceAgreement->set("cf_6020", $request->get('cf_6774'));
			}

			// Status
			$recordModelServiceAgreement->set("cf_6024", 'Approved');

			// Globalink Company
			if ($request->get('cf_7940')){
				$recordModelServiceAgreement->set("cf_6026", $request->get('cf_7940'));
			}			
			
			// Account Type
			if ($request->get('cf_6770')){
				$accountTypeId = 0;
				// Bank R 85785
				// Cash R 85786
				if ($request->get('cf_6770') == 'Bank R'){
					$accountTypeId = 85785;
				} else if ($request->get('cf_6770') == 'Cash R'){
					$accountTypeId = 85786;
				}
				
				$recordModelServiceAgreement->set("cf_6028", $accountTypeId);
			}

			// Company Type/ Counterparty Type
			if ($request->get('cf_7346')){
				$recordModelServiceAgreement->set("cf_6066", $request->get('cf_7346'));
			}


			// Agreement Type
			if ($request->get('cf_6366')){
				$recordModelServiceAgreement->set("cf_6068", $request->get('cf_6366'));
			}
			
			// Customer
			if ($request->get('cf_6362')){
				$recordModelServiceAgreement->set("cf_6094", $request->get('cf_6362'));
			}


			// Contracts for Indefinite Duration
			$recordModelServiceAgreement->set("cf_6070", 'No');			
			

			// Initiated By
			if ($request->get('assigned_user_id')){
				$recordModelServiceAgreement->set("cf_6072", $request->get('assigned_user_id'));
			}

			// Location
			$recordModelServiceAgreement->set("cf_6074", $CMSCreatorModel->get('location_id'));
			// Department
			$recordModelServiceAgreement->set("cf_6076", $CMSCreatorModel->get('department_id'));
			$recordModelServiceAgreement->save();
			

			// Adding link beetween modules			
			$parentRecordId = $CMSRecordId;
			$parentModuleModel1 = Vtiger_Module_Model::getInstance("MOTIW");
			$relatedModule1 = $recordModelServiceAgreement->getModule();			
			$relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel1, $relatedModule1);
			$relationModel->addRelation($parentRecordId, $recordModelServiceAgreement->getId());

			$this->addRelationServiceAgreementAndAccount($accountId, $recordModelServiceAgreement->getId());

		}
	}

	public function addRelationServiceAgreementAndAccount($accountId, $serviceAgreementId){
		global $adb;
		$queryServiceAgreement = "INSERT INTO vtiger_crmentityrel(crmid, module, relmodule, relcrmid) VALUES (?, ?, ?, ?)";
		$resultServiceAgreement = $adb->pquery($queryServiceAgreement, array($accountId, 'Accounts', 'ServiceAgreement', $serviceAgreementId));
	}

	public function sendApproveEmail($details){

		if (isset($details['name'])) $creatorHeadName = $details['name']; else $creatorHeadName = 'colleague';
		$recordId = $details['recordId'];
		$to = $details['to'];
		$cc = $details['cc'];
		$ref = $details['ref'];
		$account = $details['account'];

		$link = 'https://gems.globalink.net/';
		$link .= "index.php?module=MOTIW&view=Detail&record=$recordId&mode=showDetailViewByMode&requestMode=full&tab_label=Contract%20Management%20Document%20Details&app=MARKETING";
		
		
		// $to = "r.gusseinov@globalinklogistics.com";
		$date_time = date('Y-m-d H:i:s');
		$from = "erp.support@globalinklogistics.com";
		// $cc = "";
		$body = '';
		$message_status = 'Please note that task '.$ref.' is pending approval.';
		$subject = 'GEMS Contract Agreement: '.$ref;

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
	
	
/* 	public function doArchive(Vtiger_Request $request){
		
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
		
	} */


/* 	public function revertRevision(Vtiger_Request $request){
		
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
		// $rows_1 = $adb->num_rows($result_1);
								
		$newaprrovalroutehistorycfid = $adb->query_result($result_1, 0, 'approvalroutehistoryid');
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
 */

	// Change contract status to cancelled instead of removing from system
	
	public function cancelContract(Vtiger_Request $request){
		global $adb;
		$adb = PearDatabase::getInstance();
		$recordId = $request->get('record'); 
		
		$sql = "UPDATE `vtiger_motiwcf` SET `cf_7046` = 'Cancelled' WHERE `motiwid` = ?";
		$result = $adb->pquery($sql, array($recordId));
		
		$loadUrl = "index.php?module=MOTIW&view=Detail&record=".$recordId;
        echo '<script> 
			var url= "'.$loadUrl.'"; 
			window.location = url; 
		</script>';
		
	}


	public function addApprovalParties(Vtiger_Request $request){
		global $adb;
		$LEGAL_HEAD = 1122; // Timur Makarov
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$recordId = $request->get('record'); 
		$module = $request->get('module');
		$MOTIWRecordModel = Vtiger_Record_Model::getInstanceById($recordId, $module);

		$typeofAgreement = $MOTIWRecordModel->get('cf_6366');
		$dateTime = date('Y-m-d h:i:s');
		$approvalParties = $request->get('parties');
		$routeApprove = $request->get('routeapprove');
		$approvalParties = explode(',', $approvalParties);
		$countOfApprovalParties = count($approvalParties);

		$date_var = date("Y-m-d H:i:s");
		$currentDateTime = $adb->formatDate($date_var, true);

		// Get current inline user
		$currentUserId = $currentUserModel->get('id');				
		$queryApprovalRoute = "SELECT * FROM vtiger_approvalroutehistorycf 
												   INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_approvalroutehistorycf.approvalroutehistoryid 
											     WHERE vtiger_crmentity.deleted=0 AND vtiger_approvalroutehistorycf.cf_6784 = ? 
												   AND vtiger_approvalroutehistorycf.cf_6788 = ? AND cf_6792 = ?"; 
		$resultApprovalRoute = $adb->pquery($queryApprovalRoute, array($recordId, $currentUserId, 'inline'));
		$currentUserSequence = $adb->query_result($resultApprovalRoute, 0, 'cf_6790');	
		$approvalroutehistoryid = $adb->query_result($resultApprovalRoute, 0, 'approvalroutehistoryid');	
		$approvalPartieSequence = ($countOfApprovalParties + $currentUserSequence);
 
 		if ($routeApprove == 'true'){
			
			$saverelatedrecord = new Vtiger_Save_Action();
			$related_request = new Vtiger_Request(array());
			$related_request->set("__vtrftk", $request->get("__vtrftk"));
			$related_request->set("module", 'ApprovalRouteHistory');
			$related_request->set("action","Save");
			$related_request->set("record", $approvalroutehistoryid);

			$related_request->set("cf_6792", 'approved');
			$related_request->set("cf_6794", $currentDateTime);
			$saverelatedrecord->saveRecord($related_request);

			$sequenceForNewParties = $currentUserSequence + 1;
			
		} else {
			
			$sequenceForNewParties = $currentUserSequence;
		}

		// Get next approval users after current
		$queryApprovalRoute = "SELECT *
														FROM vtiger_approvalroutehistorycf 
														INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = approvalroutehistoryid 
														WHERE vtiger_crmentity.deleted=0 AND cf_6784 = ? AND cf_6790 >= ?"; 
		$resultApprovalRoute = $adb->pquery($queryApprovalRoute, array($recordId, $currentUserSequence));
		$noofrow = $adb->num_rows($resultApprovalRoute);

		for($i=0; $i<$noofrow ; $i++) {
			$approvalroutehistoryid=$adb->query_result($resultApprovalRoute,$i,'approvalroutehistoryid');
			$subject = $adb->query_result($resultApprovalRoute,$i,'cf_6786');
			$userid = $adb->query_result($resultApprovalRoute,$i,'cf_6788');
			// $sequence = $adb->query_result($resultApprovalRoute,$i,'cf_6790') + 1;
			$status = $adb->query_result($resultApprovalRoute,$i,'cf_6792');
			$currentDateTime = $adb->query_result($resultApprovalRoute,$i,'cf_6794');

			if ($status != 'approved'){
				$saverelatedrecord = new Vtiger_Save_Action();
				$related_request = new Vtiger_Request(array());
				$related_request->set("__vtrftk", $request->get("__vtrftk"));
				$related_request->set("module", 'ApprovalRouteHistory');
				$related_request->set("appName", $request->get("appName"));
				$related_request->set("action", "Save");
				$related_request->set("record",$approvalroutehistoryid);

				$related_request->set("name", $typeofAgreement);
				$related_request->set("cf_6784", $recordId); // motiw ID
				$related_request->set("cf_6786", $typeofAgreement);
				$related_request->set("cf_6788", $userid); 
				$related_request->set("cf_6790", $approvalPartieSequence);
				$related_request->set("cf_6792", 'pending');
				$related_request->set("cf_6794", $currentDateTime);
				$saverelatedrecord->saveRecord($related_request);
			}
			$approvalPartieSequence ++;

		}
		
		$n = 0;
		foreach ($approvalParties as $approvalPartie){

			if ($n == 0){
				$approvalStatus = "inline";
				$currentApprovalPartieModel = Vtiger_Record_Model::getInstanceById($approvalPartie, 'Users');
				$currentReviewer = $currentApprovalPartieModel->get('first_name').' '. $currentApprovalPartieModel->get('last_name');

				$this->updateCurrentReviewer($currentReviewer, $recordId);
				
			} else {
				$approvalStatus = "pending";
			}

  		$recordModel2 = Vtiger_Record_Model::getCleanInstance('ApprovalRouteHistory');						
			$recordModel2->set('assigned_user_id', $approvalPartie);
			$recordModel2->set('mode', 'create');
			$recordModel2->set("name", $typeofAgreement);
			$recordModel2->set("cf_6786", $typeofAgreement);
			$recordModel2->set("cf_6788", $approvalPartie);
			$recordModel2->set("cf_6790", $sequenceForNewParties);
			$recordModel2->set("cf_6792", $approvalStatus);
			$recordModel2->set("cf_6794", $dateTime);
			$recordModel2->set("cf_6784", $recordId);
			$recordModel2->set("cf_7118", $currentUserId); // who added approval partie
			$recordModel2->save();

			$n ++; 
			$sequenceForNewParties ++;
		}
		

 
		$loadUrl = "index.php?module=MOTIW&view=Detail&record=".$recordId;
        echo '<script> 
			var url= "'.$loadUrl.'"; 
			window.location = url; 
		</script>';
	}



	public function removeApprovalPartie(Vtiger_Request $request){
		global $adb;
		$routeid = $request->get('routeid');
		$recordId = $request->get('recordId'); 
		
 		$result = array();
		// Get current removed parties info				
		$approvalRouteRecord = Vtiger_Record_Model::getInstanceById($routeid, 'ApprovalRouteHistory');
		$removedPartieStatus = $approvalRouteRecord->get('cf_6792');
		$removedPartieSequence = $approvalRouteRecord->get('cf_6790');

		$partieSequence = $approvalRouteRecord->get('cf_6790');

		// Get next approval users after current
		$queryApprovalRoute = "SELECT *
														FROM vtiger_approvalroutehistorycf 
														INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = approvalroutehistoryid 
														WHERE vtiger_crmentity.deleted=0 AND cf_6784 = ? AND cf_6790 > ?
														ORDER BY cf_6790"; 
		$resultApprovalRoute = $adb->pquery($queryApprovalRoute, array($recordId, $removedPartieSequence));
		$noofrow = $adb->num_rows($resultApprovalRoute);

		for($i=0; $i<$noofrow; $i++) {
			
			$approvalroutehistoryid=$adb->query_result($resultApprovalRoute, $i, 'approvalroutehistoryid');
			$subject = $adb->query_result($resultApprovalRoute, $i, 'cf_6786');
			$userid = $adb->query_result($resultApprovalRoute, $i, 'cf_6788');
			$status = $adb->query_result($resultApprovalRoute, $i, 'cf_6792');
			$currentDateTime = $adb->query_result($resultApprovalRoute, $i, 'cf_6794');

 			$saverelatedrecord = new Vtiger_Save_Action();
			$related_request = new Vtiger_Request(array());
			$related_request->set("__vtrftk", $request->get("__vtrftk"));
			$related_request->set("module", 'ApprovalRouteHistory');
			$related_request->set("appName", $request->get("appName"));
			$related_request->set("action","Save");
			$related_request->set("record", $approvalroutehistoryid);
			$related_request->set("cf_6790", $partieSequence);
			if ($removedPartieStatus == 'inline' && $i == 0) {
				$related_request->set("cf_6792", 'inline');

				$currentApprovalPartieModel = Vtiger_Record_Model::getInstanceById($userid, 'Users');
				$currentReviewer = $currentApprovalPartieModel->get('first_name').' '. $currentApprovalPartieModel->get('last_name');

				$this->updateCurrentReviewer($currentReviewer, $recordId);
				
			}
			
			$saverelatedrecord->saveRecord($related_request);
			$partieSequence ++;


		}

		$approvalRouteRecord->delete();

 		$result = array('result' => 'ok');
		return json_encode($result);
 
	}

	public function getApprovalParties($recordId){

		$query = "SELECT *
							FROM vtiger_approvalroutehistory 

							INNER JOIN vtiger_approvalroutehistorycf ON vtiger_approvalroutehistorycf.approvalroutehistoryid = vtiger_approvalroutehistory.approvalroutehistoryid 
							INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_approvalroutehistorycf.approvalroutehistoryid 
							INNER JOIN vtiger_users ON vtiger_users.id = vtiger_approvalroutehistorycf.cf_6788 

							WHERE vtiger_approvalroutehistorycf.cf_6784 = '".$recordId."' AND vtiger_crmentity.deleted = 0 
							ORDER BY vtiger_approvalroutehistorycf.cf_6790 + 0";

		return $query;
	}	


	public function updateCurrentReviewer($currentReviewer, $recordId){
		global $adb;
		$queryReviewer = "UPDATE `vtiger_motiwcf` SET `cf_7040` = ? WHERE `motiwid` = ? LIMIT 1";
		$resultRevisionBy = $adb->pquery($queryReviewer, array($currentReviewer, $recordId));
	}

	public function contractAgreementNumber(Vtiger_Request $request){
		global $adb;
		$customerId = $request->get('accountId'); 
		$contractAgreementNumber = array();
		$invoiceDate = date('Y-m-d'); // Bo created date or CMS created date ???

		$queryAgreement = "SELECT vtiger_serviceagreement.serviceagreementid, vtiger_serviceagreement.name
											 FROM vtiger_serviceagreementcf
											 INNER JOIN vtiger_serviceagreement ON vtiger_serviceagreementcf.serviceagreementid =  vtiger_serviceagreement.serviceagreementid
											 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid =  vtiger_serviceagreementcf.serviceagreementid
											 WHERE (DATE_SUB('$invoiceDate', INTERVAL 1 YEAR) <= vtiger_serviceagreementcf.cf_6020)
											 AND  vtiger_crmentity.deleted = 0 AND vtiger_serviceagreementcf.cf_6094 = ?
											 ORDER BY vtiger_serviceagreementcf.cf_6018 DESC";
	   $result = $adb->pquery($queryAgreement, array($customerId));
	   $num_rows = $adb->num_rows($result);
	   for($i = 0; $i<$num_rows; $i++) {
			$serviceagreementid = $adb->query_result($result, $i, 'serviceagreementid');
			$serviceagreementname = $adb->query_result($result, $i, 'name');
			$contractAgreementNumber[] = array("id" => $serviceagreementid, "name" => $serviceagreementname);
	   }		 

		$details['data'] = $contractAgreementNumber;
		$parseToJson = json_encode($details);
		$contractAgreementNumber = [];
		return $parseToJson;
	}


}
