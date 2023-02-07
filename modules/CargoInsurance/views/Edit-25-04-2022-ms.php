<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Class CargoInsurance_Edit_View extends Vtiger_Edit_View {
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
		
		$FSL_black_flag  = false;
		$fsl_black = $request->get('fsl_black');
		if(isset($fsl_black) && $fsl_black==true){
			$FSL_black_flag  = true;
		}
		//$viewer = $this->getViewer($request);
		$viewer->assign('FSL_BLACK_FLAG', $FSL_black_flag);

		$Discount_flag  = false;
		$discount = $request->get('discount');
		if(isset($discount) && $discount==true){
			$Discount_flag  = true;
		}
		//$viewer = $this->getViewer($request);
		$viewer->assign('DISCOUNT_FLAG', $Discount_flag);

	
		//$viewer->assign('COMMODITY_ID', $request->get('cf_3625'));
		
		//$viewer->assign('COMMODITY_ID', $request->get('cf_3625'));
		parent::preProcess($request, $display); 
	}

	public function process(Vtiger_Request $request) {
		$viewer = $this->getViewer ($request);
		$moduleName = $request->getModule();
		$record = $request->get('record');

		

		if(!empty($record) && $request->get('isDuplicate') == true) {
			$recordModel = $this->record?$this->record:Vtiger_Record_Model::getInstanceById($record, $moduleName);
			$viewer->assign('MODE', '');

			$viewer->assign('ASSURED_COMPANY_ID', $recordModel->get('cf_3599')); // File title
			$viewer->assign('COMMODITY_ID', 0);
			$viewer->assign('INSURANCE_TYPE', '');
			$viewer->assign('DEPARTURE_DATE', $recordModel->get('cf_3613')); // Cargo Insurance From Date

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

			$viewer->assign('COMMODITY_ID', $recordModel->get('cf_3625'));
			$viewer->assign('INSURANCE_TYPE', $recordModel->get('cf_6298'));
			$viewer->assign('MODE', 'edit');

			$viewer->assign('ASSURED_COMPANY_ID', $recordModel->get('cf_3599')); // File title
			$viewer->assign('DEPARTURE_DATE', $recordModel->get('cf_3613')); // Cargo Insurance From Date
		
			/*
			$viewer->assign('FSL_BLACK_MESSAGE','');
			if(isset($_SESSION['FSL_BLACK'])) {
				unset($_SESSION['FSL_BLACK']);
				$viewer->assign('FSL_BLACK_MESSAGE','-	Please notify the name of customer to insurance team for confirmation<br>
													 -	Then you can start registering insurance in ERP system under FSL Black
										');
			}*/

		} else {
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$viewer->assign('MODE', '');
			$viewer->assign('COMMODITY_ID', 0);
			$viewer->assign('INSURANCE_TYPE', '');
			$viewer->assign('DEPARTURE_DATE', date('Y-m-d')); // Cargo Insurance From Date
			//echo "<pre>";
			//print_r($request->get('cf_3619'));
			//exit;

			if(empty($record))
			{				
				if($request->get('returnmodule') == 'Job' && $request->get('returnrecord')) 
				{
										
					$sourceModule = $request->get('returnmodule');
					$sourceRecord = $request->get('returnrecord');
					$job_info = Vtiger_Record_Model::getInstanceById($sourceRecord, $sourceModule);
					$request->set('name', $job_info->get('cf_1198')); //Job ref no
					$request->set('cf_3599', $job_info->get('cf_1186')); // File title
					
					$viewer->assign('ASSURED_COMPANY_ID', $job_info->get('cf_1186')); // File title
					
					$request->set('cf_3605', $job_info->get('cf_1504'));//From Country
					$request->set('cf_3609', $job_info->get('cf_1506'));//To Country
					$request->set('cf_3607', $job_info->get('cf_1508')); //From City
					$request->set('cf_3611', $job_info->get('cf_1510')); //To City
					$expected_from_date = $job_info->get('cf_1516');
					if(!empty($expected_from_date))
					{
						$request->set('cf_3613', date('d-m-Y', strtotime($job_info->get('cf_1516')))); //Expected from date
						$viewer->assign('DEPARTURE_DATE', date('d-m-Y', strtotime($job_info->get('cf_1516')))); // Cargo Insurance From Date
					}
					$expected_to_date = $job_info->get('cf_1583');
					if(!empty($expected_to_date))
					{
						$request->set('cf_3615', date('d-m-Y', strtotime($job_info->get('cf_1583')))); //Expected to date
					}
					
					$cargo_description = $job_info->get('cf_1547'); //Cargo desc
				
					$pcs = $job_info->get('cf_1429'). 'pcs';
					$weight = $job_info->get('cf_1084').' '.$job_info->get('cf_1520');
					$volume = $job_info->get('cf_1086').' '.$job_info->get('cf_1522');
					$full_details = array('desc' =>$cargo_description, 'pcs' => $pcs, 'weight' => $weight, 'volume' => $volume);
					$request->set('cf_3617', implode(', ', $full_details)); //full details of package
					
					//$request->set('cf_2275', $job_info->get('cf_1518')); //commodity					

					$mode_after_exception = $request->get('cf_3619');
					if(!empty($mode_after_exception))
					{
						$request->set('cf_3619', $mode_after_exception); //Mode
					}
					else{
						$request->set('cf_3619', $job_info->get('cf_1711')); //Mode
					}

					
					$viewer->assign('COMMODITY_ID',  @$request->get('cf_3625'));
					
					$file_title = $job_info->get('cf_1186');
					$request->set('cf_3663', 1); 
					if($file_title !='85757')
					{
						//$request->set('cf_3603', '0'); 
						$request->set('cf_3663', 2); 
					}
					
					//Beneficiary
					$request->set('cf_3601', $job_info->get('cf_1441')); 
					$account_id = $job_info->get('cf_1441');
					if(!empty($account_id))
					{
						$account_module = 'Accounts';
						$accounts_info = Vtiger_Record_Model::getInstanceById($account_id, $account_module);
						$request->set('cf_3603',$accounts_info->get('cf_2397'));
						
						$adb_fsl = PearDatabase::getInstance();
						$query_fsl = "SELECT * FROM vtiger_fslblackcf 
						             INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_fslblackcf.fslblackid
									 WHERE vtiger_crmentity.deleted=0 AND vtiger_fslblackcf.cf_6244=?";									 
						$params_fsl = array($account_id);
						$result_fsl = $adb_fsl->pquery($query_fsl, $params_fsl);
						$count_beneficiary_fsl = $adb_fsl->num_rows($result_fsl);
						//$row_fsl = $adb_fsl->fetch_array($result_fsl);
						
						if($count_beneficiary_fsl!=0 && $file_title=='85757' && $account_id!='1667')
						{							
							$request->set('cf_4559', 'No');
							//$request->set('cf_6260', 'FSL Black Insurance');//Test
							$request->set('cf_6298', 'FSL Black Insurance');//live
							
						}

						//16.10.2020::Mehtab :: For Halyk Insurance if customer fall under Halyk insurance then mark insurance type "Halyk Insurance".
						$halyk_insurance_account = array('2995054', '4943', '2994752', '2994938', '2994957', '2994979');
						if(in_array($account_id, $halyk_insurance_account) && $file_title=='85757' )
						{
							$request->set('cf_6298', 'Halyk Insurance');//live
							$request->set('cf_4559', 'Yes');
						}	

						/*
						$bill_street = $accounts_info->get('bill_street');
						$bill_pobox = $accounts_info->get('bill_pobox');
						$bill_code = $accounts_info->get('bill_code');
						$beneficiary_detail = array($bill_street, $bill_pobox, $bill_code);
						$request->set('cf_2245', implode(',',$beneficiary_detail));		
						*/
					}
					
					/*
					$viewer->assign('FSL_BLACK_MESSAGE','');
					if(isset($_SESSION['FSL_BLACK']))
					{
						$viewer->assign('FSL_BLACK_MESSAGE','-	Please notify the name of customer to insurance team for confirmation<br>
															 -	Then you can start registering insurance in ERP system under FSL Black
										');
						unset($_SESSION['FSL_BLACK']);
					}*/
					
				}
				
				
			}
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
		$recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_EDIT);
		$picklistDependencyDatasource = Vtiger_DependencyPicklist::getPicklistDependencyDatasource($moduleName);

		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE',Vtiger_Functions::jsonEncode($picklistDependencyDatasource));
		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
		$viewer->assign('RECORD_STRUCTURE', $recordStructureInstance->getStructure());
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());

		$isRelationOperation = $request->get('relationOperation');

		//if it is relation edit
		$viewer->assign('IS_RELATION_OPERATION', $isRelationOperation);
		if($isRelationOperation) {
			$viewer->assign('SOURCE_MODULE', $request->get('sourceModule'));
			$viewer->assign('SOURCE_RECORD', $request->get('sourceRecord'));
		}

		$current_user = Users_Record_Model::getCurrentUserModel();
		$access_company_id = explode(" |##| ",$current_user->get('access_company_id'));
		$viewer->assign('ACCESS_USER_COMPANY', $access_company_id);

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