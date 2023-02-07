<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Class ProjectCargoCheckList_Edit_View extends Vtiger_Edit_View {
	protected $record = false;
	function __construct() {
		parent::__construct();
	}

	public function requiresPermission(\Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$record = $request->get('record');
		$actionName = 'CreateView';
		if ($record && !$request->get('isDuplicate')) {
			$actionName = 'EditView';
		}
		$permissions[] = array('module_parameter' => 'module', 'action' => $actionName, 'record_parameter' => 'record');
		return $permissions;
	}
	
	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$record = $request->get('record');

		$nonEntityModules = array('Users', 'Events', 'Calendar', 'Portal', 'Reports', 'Rss', 'EmailTemplates');
		if ($record && !in_array($moduleName, $nonEntityModules)) {
			$recordEntityName = getSalesEntityType($record);
			if ($recordEntityName !== $moduleName) {
				throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
			}
		}
		return parent::checkPermission($request);
	}

	public function setModuleInfo($request, $moduleModel) {
		$fieldsInfo = array();
		$basicLinks = array();
		$settingLinks = array();

		$moduleFields = $moduleModel->getFields();
		foreach($moduleFields as $fieldName => $fieldModel){
			$fieldsInfo[$fieldName] = $fieldModel->getFieldInfo();
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('FIELDS_INFO', json_encode($fieldsInfo));
		$viewer->assign('MODULE_BASIC_ACTIONS', $basicLinks);
		$viewer->assign('MODULE_SETTING_ACTIONS', $settingLinks);
	}

	function preProcess(Vtiger_Request $request, $display=true) { 


		//Vtiger7 - TO show custom view name in Module Header
		$viewer = $this->getViewer ($request); 
		$moduleName = $request->getModule(); 
		$viewer->assign('CUSTOM_VIEWS', CustomView_Record_Model::getAllByGroup($moduleName)); 
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$record = $request->get('record'); 

		if(!empty($record) && $moduleModel->isEntityModule()) { 
			$recordModel = $this->record?$this->record:Vtiger_Record_Model::getInstanceById($record, $moduleName); 
			$viewer->assign('RECORD',$recordModel); 
		}  

		$duplicateRecordsList = array();
		$duplicateRecords = $request->get('duplicateRecords');
		if (is_array($duplicateRecords)) {
			$duplicateRecordsList = $duplicateRecords;
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('DUPLICATE_RECORDS', $duplicateRecordsList);
		parent::preProcess($request, $display); 
	}

	public function process(Vtiger_Request $request) {

		$viewer = $this->getViewer ($request);
		$moduleName = $request->getModule();
		$record = $request->get('record');

		
		if(!empty($record) && $request->get('isDuplicate') == true) {
			$recordModel = $this->record?$this->record:Vtiger_Record_Model::getInstanceById($record, $moduleName);
			$viewer->assign('MODE', '');

			//While Duplicating record, If the related record is deleted then we are removing related record info in record model

			$mandatoryFieldModels = $recordModel->getModule()->getMandatoryFieldModels();
			foreach ($mandatoryFieldModels as $fieldModel) {
				if ($fieldModel->isReferenceField()) {
					$fieldName = $fieldModel->get('name');
					if (Vtiger_Util_Helper::checkRecordExistance($recordModel->get($fieldName))) {
						$recordModel->set($fieldName, '');
					}
				}
			}  
		}else if(!empty($record)) {
			$recordModel = $this->record?$this->record:Vtiger_Record_Model::getInstanceById($record, $moduleName);
			$viewer->assign('RECORD_ID', $record);
			$viewer->assign('MODE', 'edit');
		} else {



			if(empty($record))
			{				
				if($request->get('returnmodule') == 'Job' && $request->get('returnrecord')) 
				{
										
					$sourceModule = $request->get('returnmodule');
					$sourceRecord = $request->get('returnrecord');
					$job_info = Vtiger_Record_Model::getInstanceById($sourceRecord, $sourceModule);
					$request->set('name', $job_info->get('name')); //Subject
					$request->set('job_number_crg', $job_info->get('cf_1198')); //Job ref no
					$request->set('department_crg', $job_info->get('cf_1190')); //Department
					$request->set('client_name_crg', $job_info->get('cf_1441')); //Client
					$request->set('shipper_crg', $job_info->get('cf_1072')); //Shipper
					$request->set('consignee_crg', $job_info->get('cf_1074')); //Consignee
					$request->set('origin_service_crg', $job_info->get('cf_1504')); //Origin country
					$request->set('destination_service_crg', $job_info->get('cf_1506')); //Destination
					$request->set('commodity_crg', $job_info->get('cf_1518')); //Commodity
					$request->set('cargo_value_crg', $job_info->get('cf_1524')); //cargo value
					$request->set('cargo_weight_crg', $job_info->get('cf_1084')); //cargo weight 

				}
			}


			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$viewer->assign('MODE', '');
		}



		if(!$this->record){
			$this->record = $recordModel;
		}


		$moduleModel = $recordModel->getModule();
		$fieldList = $moduleModel->getFields();
		$requestFieldList = array_intersect_key($request->getAllPurified(), $fieldList);

		$relContactId = $request->get('contact_id');
		if ($relContactId && $moduleName == 'Calendar') {
			$contactRecordModel = Vtiger_Record_Model::getInstanceById($relContactId);
			$requestFieldList['parent_id'] = $contactRecordModel->get('account_id');
		}	


		foreach($requestFieldList as $fieldName=>$fieldValue){


			$fieldModel = $fieldList[$fieldName];
			$specialField = false;
			// We collate date and time part together in the EditView UI handling 
			// so a bit of special treatment is required if we come from QuickCreate 
			if ($moduleName == 'Calendar' && empty($record) && $fieldName == 'time_start' && !empty($fieldValue)) { 
				$specialField = true; 
				// Convert the incoming user-picked time to GMT time 
				// which will get re-translated based on user-time zone on EditForm 
				$fieldValue = DateTimeField::convertToDBTimeZone($fieldValue)->format("H:i"); 

			}

		
			if ($moduleName == 'Calendar' && empty($record) && $fieldName == 'date_start' && !empty($fieldValue)) { 
				$startTime = Vtiger_Time_UIType::getTimeValueWithSeconds($requestFieldList['time_start']);
				$startDateTime = Vtiger_Datetime_UIType::getDBDateTimeValue($fieldValue." ".$startTime);
				list($startDate, $startTime) = explode(' ', $startDateTime);
				$fieldValue = Vtiger_Date_UIType::getDisplayDateValue($startDate);
			}
			if($fieldModel->isEditable() || $specialField) {
				$recordModel->set($fieldName, $fieldModel->getDBInsertValue($fieldValue));
			}
		}

		global $adb;
		$vendor="select * from vtiger_projectcargochecklist_vendorsrel 
		inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_projectcargochecklist_vendorsrel.id 
		inner join vtiger_account on vtiger_projectcargochecklist_vendorsrel.vendorid=vtiger_account.accountid 
		where vtiger_crmentity.crmid=".$record;
		$vendor = $adb->pquery($vendor,array());
		
		$vendors = array();
		for($j=0; $j<$adb->num_rows($vendor); $j++) {
			$vendors[$j]['id'] = $adb->query_result($vendor, $j, 'id');
			$vendors[$j]['vendorid'] = $adb->query_result($vendor, $j, 'vendorid');
			$vendors[$j]['vendor_type'] = $adb->query_result($vendor, $j, 'vendor_type');
			$vendors[$j]['accountname'] = $adb->query_result($vendor, $j, 'accountname');
			$vendors[$j]['agreement'] = $adb->query_result($vendor, $j, 'agreement');
			$vendors[$j]['comments'] = $adb->query_result($vendor, $j, 'comments');
		}



		global $adb;
		$vendor="select * from vtiger_projectcargochecklist_vendorsrel 
		inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_projectcargochecklist_vendorsrel.id 
		inner join vtiger_account on vtiger_projectcargochecklist_vendorsrel.vendorid=vtiger_account.accountid 
		where vtiger_crmentity.crmid=".$record;
		$vendor = $adb->pquery($vendor,array());
		
		$vendors = array();
		for($j=0; $j<$adb->num_rows($vendor); $j++) {
			$vendors[$j]['id'] = $adb->query_result($vendor, $j, 'id');
			$vendors[$j]['vendorid'] = $adb->query_result($vendor, $j, 'vendorid');
			$vendors[$j]['vendor_type'] = $adb->query_result($vendor, $j, 'vendor_type');
			$vendors[$j]['accountname'] = $adb->query_result($vendor, $j, 'accountname');
			$vendors[$j]['agreement'] = $adb->query_result($vendor, $j, 'agreement');
			$vendors[$j]['comments'] = $adb->query_result($vendor, $j, 'comments');
		}



		global $adb;
		$lashing="SELECT 
					vtiger_projectcargochecklistcf.tipping_crg AS tipping_crg,
					vtiger_projectcargochecklistcf.sliding_crg AS sliding_crg,
					vtiger_projectcargochecklistcf.transversal_rolling_crg AS transversal_rolling_crg,
					vtiger_projectcargochecklistcf.transversal_sliding_crg AS transversal_sliding_crg,
					vtiger_projectcargochecklistcf.longitudional_sliding_crg AS longitudional_sliding_crg,
					vtiger_crmentity.deleted, vtiger_crmentity.label 
					FROM vtiger_crmentity 
					LEFT JOIN vtiger_projectcargochecklist ON (vtiger_projectcargochecklist.projectcargochecklistid = vtiger_crmentity.crmid ) 
					LEFT JOIN vtiger_projectcargochecklistcf ON (vtiger_projectcargochecklistcf.projectcargochecklistid = vtiger_crmentity.crmid ) 
					LEFT JOIN vtiger_crmentity_user_field ON (vtiger_crmentity_user_field.recordid = vtiger_crmentity.crmid 
					AND vtiger_crmentity_user_field.userid = 1 ) 
					WHERE vtiger_crmentity.crmid="."$record"."
					LIMIT 1
					";
				$lashing = $adb->pquery($lashing,array());
				$lashings = array();
				for($j=0; $j<$adb->num_rows($lashing); $j++) {
					//$lashings[$j]['id'] = $adb->query_result($lashing, $j, 'id');
					$lashings[$j]['tipping_crg'] = $adb->query_result($lashing, $j, 'tipping_crg');
					$lashings[$j]['sliding_crg'] = $adb->query_result($lashing, $j, 'sliding_crg');
					$lashings[$j]['transversal_rolling_crg'] = $adb->query_result($lashing, $j, 'transversal_rolling_crg');
					$lashings[$j]['transversal_sliding_crg'] = $adb->query_result($lashing, $j, 'transversal_sliding_crg');
					$lashings[$j]['longitudional_sliding_crg'] = $adb->query_result($lashing, $j, 'longitudional_sliding_crg');
				}

		$recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_EDIT);
		$picklistDependencyDatasource = Vtiger_DependencyPicklist::getPicklistDependencyDatasource($moduleName);
		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE',Vtiger_Functions::jsonEncode($picklistDependencyDatasource));
		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
		$viewer->assign('RECORD_STRUCTURE', $recordStructureInstance->getStructure());
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('RECORDID', $record);
		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('VENDORS', $vendors);
		$viewer->assign('LASHINGS', $lashings);
		$viewer->assign('DOCS', $docs);
		$isRelationOperation = $request->get('relationOperation');


		//if it is relation edit
		$viewer->assign('IS_RELATION_OPERATION', $isRelationOperation);
		if($isRelationOperation) {
			$viewer->assign('SOURCE_MODULE', $request->get('sourceModule'));
			$viewer->assign('SOURCE_RECORD', $request->get('sourceRecord'));
		}

		// added to set the return values
		if($request->get('returnview')) {
			$request->setViewerReturnValues($viewer);
		}
		$viewer->assign('MAX_UPLOAD_LIMIT_MB', Vtiger_Util_Helper::getMaxUploadSize());
		$viewer->assign('MAX_UPLOAD_LIMIT_BYTES', Vtiger_Util_Helper::getMaxUploadSizeInBytes());
		if($request->get('displayMode')=='overlay'){
			$viewer->assign('SCRIPTS',$this->getOverlayHeaderScripts($request));
			$viewer->view('OverlayEditView.tpl', $moduleName);
		}
		else{
			$viewer->view('EditView.tpl', $moduleName);
		}
	}

	public function getOverlayHeaderScripts(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$jsFileNames = array(
			"modules.$moduleName.resources.Edit",
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return $jsScriptInstances;
	}
}
