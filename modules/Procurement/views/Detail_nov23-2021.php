<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Procurement_Detail_View extends Vtiger_Detail_View {
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
		global $adb;
		$db=$adb;
		//$adb->setDebug(true);
		// $currentUser_CompanyID = $currentUser->get('company_id');
		$totalusdamount = 0;
		$send_status_checker = '';
		$recordId = $request->get('record');
		$procurement_data = Vtiger_Record_Model::getInstanceById($recordId, "Procurement");
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$id = $currentUser->get('id');
		$creatorID = $procurement_data->get('assigned_user_id');
		$ProcurementType_data = Vtiger_Record_Model::getInstanceById($procurement_data->get('proc_proctype'), 'ProcurementTypes');
		$ProcurementType_code = $ProcurementType_data->get('proctype_shortcode');
		if ($id == $creatorID)
		{
			$send_status_checker = 'yes';
		}
		
		$creator_result = $db->pquery("SELECT * FROM `vtiger_send_approval` where procurement_id = $recordId AND status=0 order by id ASC limit 1");
		if($db->num_rows($creator_result)>0){
		$send_approval_creatorID = $db->query_result($creator_result, 0, 'creator_id');
		}
		if($id == $send_approval_creatorID){
			$send_status_checker = 'no';
		}
		
		//print_r($procurement_data);exit;
		// $assign_department_id = $procurement_data->get('cf_7454');///////////////sss//////
		// $department_data = Vtiger_Record_Model::getInstanceById($assign_department_id, "Department");
		// // echo $department;exit;
		// $department = $department_data->get('cf_1542');
		// echo $department;exit;
		// echo "SELECT * FROM `vtiger_send_approval` where procurement_id = $record_id order by id DESC limit 1";
		$send_approval_result = $db->pquery("SELECT * FROM `vtiger_send_approval` where procurement_id = $recordId AND status=0 order by id DESC limit 1");
		$send_approval_value_array = array();
		if($db->num_rows($send_approval_result)>0){
				for($send=0; $send<$db->num_rows($send_approval_result); $send++) {
					$send_approval_value_array[$send]['who_approve_id'] = $db->query_result($send_approval_result, $send, 'who_approve_id');
					$send_approval_value_array[$send]['approval_status'] = $db->query_result($send_approval_result, $send, 'approval_status');
				}
		}else{
			$send_approval_value_array[] = 0;
		}
		 // print_r($send_approval_value_array);exit;
		// echo "SELECT DISTINCT(procurementitemsid),procitem_qty,procitem_unit_price,procitem_line_price,procitem_vat_unit,cf_7538,procitem_gross_finalamount,procitem_net_finalamount,
		// procitem_currency,procitem_total_usd,procitem_proctypeitem_id,procitem_procid,procitem_proctype FROM `vtiger_procurement` as p INNER JOIN vtiger_procurementitemscf
		//  as pi ON p.procurementid= pi.procitem_procid INNER JOIN vtiger_crmentity as c ON c.crmid=pi.procurementitemsid
		//   where c.deleted=0 and p.procurementid=$recordId";
		// echo "SELECT pi.* FROM `vtiger_procurement` as p INNER JOIN vtiger_procurementitemscf
		//  as pi ON p.procurementid= pi.procitem_procid INNER JOIN vtiger_crmentity as c ON c.crmid=pi.procurementitemsid
		//   where c.deleted=0 and p.procurementid=$recordId";
		// 	exit;
		$unit_price_total = 0;
		$local_price_total = 0;
		$vat_price_total = 0;
		$gross_local = 0;
		$gross_local_total = 0;
		$total_net = 0;
		$total_usd = 0;
		//procitem_proctypeitem_id this is the vtiger_ProcurementTypeItems ID in vtiger_procurementitemscf
		$result=$db->query("SELECT pi.* FROM `vtiger_procurement` as p INNER JOIN vtiger_procurementitemscf
		 as pi ON p.procurementid= pi.procitem_procid INNER JOIN vtiger_crmentity as c ON c.crmid=pi.procurementitemsid
		  where c.deleted=0 and p.procurementid=$recordId");
		 
		 // echo "<pre>"; print_r($procurement_data);echo "</pre>";exit;
			$Procureitem=array();
			$expence_type_data_code = '';
			if($db->num_rows($result)>0){
				for($i=0; $i<$db->num_rows($result); $i++) {
					$Procureitem[$i]['procurementitemsid'] = $db->query_result($result, $i, 'procurementitemsid');
					$Procureitem[$i]['procitem_qty'] = $db->query_result($result, $i, 'procitem_qty');
					$Procureitem[$i]['procitem_unit_price'] = $db->query_result($result, $i, 'procitem_unit_price');
					$Procureitem[$i]['procitem_line_price'] = $db->query_result($result, $i, 'procitem_line_price');
					$Procureitem[$i]['procitem_vat_unit'] = $db->query_result($result, $i, 'procitem_vat_unit');
					$Procureitem[$i]['procitem_vat_amount'] = $db->query_result($result, $i, 'procitem_vat_amount');
					$Procureitem[$i]['procitem_gross_finalamount'] = $db->query_result($result, $i, 'procitem_gross_finalamount');
					$Procureitem[$i]['procitem_net_finalamount'] = $db->query_result($result, $i, 'procitem_net_finalamount');
					$Procureitem[$i]['procitem_gross_amount'] = $db->query_result($result, $i, 'procitem_gross_amount');
					$Procureitem[$i]['procitem_gross_local'] = $db->query_result($result, $i, 'procitem_gross_local');

					$Procureitem[$i]['procitem_current_qty'] = $db->query_result($result, $i, 'procitem_current_qty');
					$Procureitem[$i]['procitem_avg_consumption'] = $db->query_result($result, $i, 'procitem_avg_consumption');
					$Procureitem[$i]['procitem_lastpurchase_price'] = $db->query_result($result, $i, 'procitem_lastpurchase_price');
					$Procureitem[$i]['procitem_lastpurchase_qty'] = $db->query_result($result, $i, 'procitem_lastpurchase_qty');
					$currency = $db->query_result($result, $i, 'procitem_currency');
					$query = $adb->pquery("SELECT `currency_code` FROM `vtiger_currency_info`  WHERE `id`=$currency");
					$currency_code = $adb->query_result($query, 0, 'currency_code');
					$Procureitem[$i]['procitem_currency'] = $currency_code;
					$Procureitem[$i]['procitem_total_usd'] = $db->query_result($result, $i, 'procitem_total_usd');
					$totalusdamount+=$db->query_result($result, $i, 'procitem_total_usd'); //usman
					$expence_type_id = $db->query_result($result, $i, 'procitem_proctypeitem_id');
				    $expence_type_data = Vtiger_Record_Model::getInstanceById($expence_type_id, 'ProcurementTypeItems');
					if($ProcurementType_code=='PM') //show code value for packaging material
					{
						$expence_type_data_code = '['.$expence_type_data->get('proctypeitem_code').'] : ';
					}
					$Procureitem[$i]['procitem_proctypeitem_id'] = $expence_type_data_code.$expence_type_data->get('name');
					$Procureitem[$i]['procitem_procid'] = $db->query_result($result, $i, 'procitem_procid');
					$Procureitem[$i]['procitem_proctype'] = $db->query_result($result, $i, 'procitem_proctype');
					$Procureitem[$i]['procitem_description'] = $db->query_result($result, $i, 'procitem_description');					
					$unit_price_total = $unit_price_total+$Procureitem[$i]['procitem_unit_price'];
					$local_price_total = $local_price_total+$Procureitem[$i]['procitem_line_price'];
					$vat_price_total = $vat_price_total+$Procureitem[$i]['procitem_vat_amount'];
					$gross_local = $gross_local+$Procureitem[$i]['procitem_gross_amount'];
					$gross_local_total = $gross_local_total+$Procureitem[$i]['procitem_gross_local'];
					$total_net = $total_net+$Procureitem[$i]['procitem_gross_finalamount'];
					$total_usd = $total_usd+$Procureitem[$i]['procitem_total_usd'];
			}
		}else{
			$Procureitem[$i]='';
		}
		
		//	$rec=$db->query_result($result);
			//print_r($Procureitem);exit;
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
    //$recordModel->set();
		$viewer = $this->getViewer($request);
		
		$total_usd = $procurement_data->get('proc_total_amount');
		$viewer->assign('unit_price_total', number_format($unit_price_total , 2 ,  "." , ","));
		$viewer->assign('local_price_total', number_format($local_price_total , 2 ,  "." , ","));
		$viewer->assign('vat_price_total', number_format($vat_price_total , 2 ,  "." , ","));
		$viewer->assign('gross_local', number_format($gross_local , 2 ,  "." , ","));
		$viewer->assign('gross_local_total',number_format($gross_local_total , 2 ,  "." , ","));
		$viewer->assign('total_net', number_format($total_net , 2 ,  "." , ","));
		$viewer->assign('total_usd', number_format($total_usd , 2 ,  "." , ","));
		
		$viewer->assign('sending_approvals', $send_approval_value_array);
		$viewer->assign('send_status', $send_status_checker);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('USDAMOUNT', $totalusdamount);
		$viewer->assign('Procurementitemsdata', $Procureitem);
		$viewer->assign('NAVIGATION', $navigationInfo);
		
	//arif code: show approval information if creator has sent the procurement for approval
		$approval_authorities = array();//declare array for approval authorities
		$approval_array_id = 0;
		$viewer->assign('Show_Approvals', 0);
		if($db->num_rows($send_approval_result)>0){
			$viewer->assign('Show_Approvals', 1);
			//get user detail who has created the request
			$creator_user_data = Vtiger_Record_Model::getInstanceById($creatorID, "Users");
			//echo "<pre>"; print_r($creator_user_data); echo "</pre>";exit;
			 $creator_email = $creator_user_data->get('email1');
			 $creator_name = $creator_user_data->get('first_name').' '.$creator_user_data->get('last_name');
			//get location and if not ALA then get GM information
			$office_location = $procurement_data->get('cf_7452');
			$sql_location = $adb->pquery("SELECT cf_1559 FROM `vtiger_locationcf` WHERE `locationid` = '$office_location'");
			$location_detail = $adb->fetch_array($sql_location);
			$location_status = $location_detail['cf_1559'];
			$sql_userprofile = $adb->pquery("SELECT `userlistid`, `cf_3355`, `cf_3385`, `cf_3421`
									FROM `vtiger_userlistcf`
									WHERE `cf_3355` = '$creator_email'");
			$userprofile = $adb->fetch_array($sql_userprofile);
			$gm_id = $userprofile['cf_3385'];
			$sql_gm_email = $adb->pquery("SELECT uf.cf_3355 as email, u.name as name FROM `vtiger_userlistcf` as uf inner join `vtiger_userlist` as u on uf.userlistid = u.userlistid  WHERE uf.`userlistid` = '$gm_id'");
			$gm = $adb->fetch_array($sql_gm_email);
			$gm_email = $gm['email'];
			if($gm_email=='s.khan@globalinklogistics.com') //add approval entry as Siddique Khan is the GM of request creator
			{
				$procurement_gm_approval_result = $adb->pquery("SELECT * FROM `vtiger_send_approval` where who_approve_id = '$creatorID' and procurement_id='".$recordId."'");
				if($adb->num_rows($procurement_gm_approval_result)>0){
					$row = $adb->query_result_rowdata($procurement_gm_approval_result, 0);
					$approval_authorities[$approval_array_id]['authority_name'] = $creator_name;
					$approval_authorities[$approval_array_id]['designation'] = 'GM';
					$approval_authorities[$approval_array_id]['approval_status'] = $row['approval_status'].(trim($row['reject_reason'])!=""?("<br><br> <u>Reason:</u> ".$row['reject_reason']):"");
					$approval_authorities[$approval_array_id]['approval_date'] = $row['date_time_of_approval'];
					$approval_array_id++; //auto increment to use in next loop also
				}
			}
			elseif($location_status != 'ALA'){
				
				//now get gm approval status from vtiger_send_approval
				$get_gm_userid = $adb->pquery("SELECT * FROM `vtiger_users` where email1 = '".$gm_email."' limit 1"); //get gm userid from users table
				$gm_userid = $adb->query_result($get_gm_userid, 0, 'id');
				$gm_name = $adb->query_result($get_gm_userid, 0, 'first_name')." ".$adb->query_result($get_gm_userid, 0, 'last_name');;
				$procurement_gm_approval_result = $adb->pquery("SELECT * FROM `vtiger_send_approval` where who_approve_id = '$gm_userid' and procurement_id='$recordId'");
				if($adb->num_rows($procurement_gm_approval_result)>0){
					$row = $adb->query_result_rowdata($procurement_gm_approval_result, 0);
					//echo "<pre>"; print_r($row); echo "</pre>";
					$approval_authorities[$approval_array_id]['authority_name'] = $gm_name;
					$approval_authorities[$approval_array_id]['designation'] = 'GM';
					$approval_authorities[$approval_array_id]['approval_status'] = $row['approval_status'].(trim($row['reject_reason'])!=""?("<br><br> <u>Reason:</u> ".$row['reject_reason']):"");
					$approval_authorities[$approval_array_id]['approval_date'] = $row['date_time_of_approval'];
					$approval_array_id++; //auto increment to use in next loop also
				}
				
			} //end location check
			
			$proc_proctypeID =  $procurement_data->get('proc_proctype');
			$creatorUser_name = $creator_user_data->get('first_name').' '.$creator_user_data->get('last_name');
			//below make approval authorities list dynamically
			//get all approval authorities from vtiger_procurementapprovalcf for requested item, left join with vtiger_send_approval & get approval status			
			$procurementauthorities_result = $adb->pquery("SELECT * FROM `vtiger_procurementapprovalcf` 
			inner join  `vtiger_crmentity` on vtiger_crmentity.`crmid` = `vtiger_procurementapprovalcf`.procurementapprovalid
			inner join vtiger_procurementapproval on vtiger_procurementapprovalcf.procurementapprovalid=vtiger_procurementapproval.procurementapprovalid 
			left join vtiger_send_approval on vtiger_procurementapprovalcf.procapproval_person = vtiger_send_approval.who_approve_id and vtiger_send_approval.procurement_id='$recordId' 
			where procapproval_proctype = $proc_proctypeID AND vtiger_crmentity.deleted=0 order by procapproval_sequence  ASC");
			if($adb->num_rows($procurementauthorities_result)>0){
				//echo "<pre>"; print_r($procurementauthorities_result); echo "</pre>";
				for($j=0; $j<$adb->num_rows($procurementauthorities_result); $j++) { //loop to build array for approval details  
					//echo "user_id: ".$adb->query_result($procurementauthorities_result, $j, 'procapproval_person')." sequence: ".$adb->query_result($procurementauthorities_result, $j, 'procapproval_sequence')." usd_limit: ".$adb->query_result($procurementauthorities_result, $j, 'cf_7602')." <br>";
					$row = $adb->query_result_rowdata($procurementauthorities_result, $j);
					$approval_user_data = Vtiger_Record_Model::getInstanceById($row['procapproval_person'], "Users");
					$approval_user_name  = $approval_user_data->get('first_name')." ".$approval_user_data->get('last_name');
					$approval_authorities[$approval_array_id]['authority_name'] = $approval_user_name;
					$approval_authorities[$approval_array_id]['designation'] = $row['name'];
					$approval_authorities[$approval_array_id]['approval_status'] = $row['approval_status'].(trim($row['reject_reason'])!=""?("<br><br> <u>Reason:</u> ".$row['reject_reason']):"");
					$approval_authorities[$approval_array_id]['approval_date'] = $row['date_time_of_approval'];
					$approval_array_id++;
					//echo "<pre>"; print_r($row); echo "</pre>";
				}
			}
			//echo "<pre>"; print_r($approval_authorities); echo "</pre>";
			$viewer->assign('approval_authorities_list', $approval_authorities);
		}
	//arif code ends
	
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
		//print_r( $viewer->get_template_vars() );exit;
		if($display) {
			
			$this->preProcessDisplay($request);
		}
	}

	function preProcessTplName(Vtiger_Request $request) {
		//echo "good 1";exit;
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
		//print_r(asasasasasas);exit;
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
		$viewer->assign('MODULE', $moduleName);

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
		$viewer->assign('CURRENT_USER_MODEL', Users_Record_Model::getCurrentUserModel());
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
		//return $this->isAjaxEnabled;
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

}
