<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
// ini_set('display_errors','on'); error_reporting(E_ALL); // STRICT DEVELOPMENT

Class BO_Edit_View extends Vtiger_Edit_View {
	protected $record = false;

	const RUSSIA_OFFICE = array(85766, 1763699);
  const UKRAINE_OFFICE = 85769;

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
		global $adb;
		//$adb->setDebug(true);
		$viewer = $this->getViewer ($request);
		$moduleName = $request->getModule();

		$sourceRecord = $request->get('sourceRecord');
		$agent_customer_id = $request->get('cbo');
		$result = $adb->pquery("SELECT * FROM `vtiger_bo` 
				INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_bo.boid
				where vtiger_bo.accountid = ? and vtiger_crmentityrel.crmid=?", array($agent_customer_id, $sourceRecord));
		$numRows = $adb->num_rows($result);
		if($numRows>0)
		{
			$row_bo_agent_customer = $adb->fetch_array($result);
			$request->set('record', $row_bo_agent_customer['boid']);
		}

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
            //General Country and states code
            $GEN_COUNTRY_CODE = $recordModel->get('cf_1281');
            $GEN_STATE_CODE = $recordModel->get('bo_origin_state');
            $viewer->assign('GEN_COUNTRY_CODE',$GEN_COUNTRY_CODE);
			$viewer->assign('GEN_STATE_CODE',$GEN_STATE_CODE);
            //General Destination Country and Destination states code

            $GEN_DEST_COUNTRY_CODE = $recordModel->get('cf_1283');
			$GEN_DEST_STATE_CODE = $recordModel->get('bo_destination_state');
			$viewer->assign('GEN_DEST_COUNTRY_CODE',$GEN_DEST_COUNTRY_CODE);
			$viewer->assign('GEN_DEST_STATE_CODE',$GEN_DEST_STATE_CODE);
				

		} else {
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$viewer->assign('MODE', '');

			$sourceModule =  $request->get('sourceModule');
			$sourceRecord =  $request->get('sourceRecord');
			// $job_costing_info = Vtiger_Record_Model::getInstanceById($sourceRecord, $sourceModule);

			//$sourceModule =  $request->get('sourceModule');
/* 			$sourceRecord =  $request->get('sourceRecord');
			$sql_crmentityrel =  $adb->pquery("SELECT * FROM `vtiger_crmentityrel` WHERE `relcrmid`=$sourceRecord and `relmodule`='JER'");
			$r_crmentityrel = $adb->fetch_array($sql_crmentityrel);
			$job_id = $r_crmentityrel['crmid'];
			
			$job_info = Vtiger_Record_Model::getInstanceById($job_id, 'Job');
			
			$request->set('name', $job_info->get('name')); //Job subject
			$request->set('cf_1295', $job_info->get('cf_1198')); //Job ref no
			$request->set('cf_1581', $job_info->get('cf_1186')); //Job file title
			
			$request->set('cf_1275', $job_info->get('cf_1072')); //shipper
			$request->set('cf_1277', $job_info->get('cf_1074')); //consignee
			$request->set('cf_1301', $job_info->getDisplayValue('cf_1516')); //expected_date_of_loading
			$request->set('cf_1587', $job_info->getDisplayValue('cf_1583')); //expected_date_of_delivery
			$request->set('cf_1593', $job_info->getDisplayValue('cf_1589')); //etd
			$request->set('cf_1595', $job_info->getDisplayValue('cf_1591')); //eta
			$request->set('cf_1573', $job_info->get('cf_1711')); //mode
			$request->set('cf_1309', $job_info->get('cf_1429')); //number_of_items
			$request->set('cf_1801', $job_info->get('cf_1585')); //special_instructions
			$request->set('cf_1287', $job_info->get('cf_1084')); //weight
			$recordModel->set('cf_1473', $job_info->get('cf_1520')); //weight unit
			$request->set('cf_1289', $job_info->get('cf_1086')); //Volumne
			$recordModel->set('cf_1475', $job_info->get('cf_1522')); //Volume unit
			$request->set('cf_1469', $job_info->get('cf_1524')); //Cargo Value	
			$recordModel->set('cf_1727', $job_info->get('cf_1721')); //Cargo unit
			$request->set('cf_1291', $job_info->get('cf_1092')); //transport_type
			
			$request->set('cf_1549', $job_info->get('cf_1547')); //Cargo description
			$request->set('cf_1471', $job_info->get('cf_1518')); //commodity
			//$request->set('cf_2219', $job_info->get('cf_1188')); //office
			//$request->set('cf_2219', $job_info->get('cf_1188')); //department
			$request->set('cf_1297', $job_info->get('cf_1098')); //terms_of_delivery
			$request->set('cf_1303', $job_info->get('cf_1102')); //remarks
			$request->set('cf_1331', $job_info->get('cf_1526')); //services
			$request->set('cf_1481', $job_info->get('cf_1526')); //services
			
			//Customer Info
			// $account_id = $job_costing_info->get('cf_1443');
			$account_id = 0;
			$account_info = Vtiger_Record_Model::getInstanceById($account_id, 'Accounts');
			
			$payment_terms = $account_info->get('cf_1855');
			// $request->set('account_id', $job_costing_info->get('cf_1443')); //customer		
			$request->set('account_id', 0); //customer		
			$request->set('cf_1855', $payment_terms);	

			$request->set('cf_1571', $job_info->get('cf_1569')); //insurance_currency
			$request->set('cf_1563', $job_info->get('cf_1534')); //premium_quoted
			$request->set('cf_1561', $job_info->get('cf_1532')); //insurance_value
			$request->set('cf_1565', $job_info->get('cf_1528')); //deductible
			$request->set('cf_1863', $job_info->get('cf_1585')); //special_conditions
			$request->set('cf_1305', $job_info->get('cf_1100')); //insurance_required			
		
			$request->set('cf_1281', $job_info->get('cf_1504')); //origin_country
			$request->set('cf_1283', $job_info->get('cf_1506')); //destination_country
			$request->set('cf_1461', $job_info->get('cf_1508')); //origin_city
			$request->set('cf_1463', $job_info->get('cf_1510')); //destination_city
			$request->set('cf_1465', $job_info->get('cf_1512')); //pickup_address
			$request->set('cf_1467', $job_info->get('cf_1514')); //delivery_address	 */

/* 			
			$sql_crmentityrel = $adb->pquery("SELECT * FROM `vtiger_crmentityrel` WHERE `relcrmid`=$job_id and `relmodule`='Job'");
			$r_crmentityrel =  $adb->fetch_array($sql_crmentityrel);
			$quote_id = $r_crmentityrel['crmid'];	 */
			$quote_id = 0;
			$quotation_ref = 'GL/QT-'.$quote_id;
			$request->set('cf_1793', $quotation_ref); //quotation_ref
			
			$agreed_freight_rate = '';
			$job_costing_agreed_freight_rate = $adb->pquery("SELECT * FROM `vtiger_job` as job
															INNER JOIN vtiger_crmentityrel as crmentityrel ON job.jobid=crmentityrel.crmid and module='Job'
															INNER JOIN vtiger_jercf as jercf ON jercf.jerid=crmentityrel.relcrmid and relmodule='JER'
															WHERE jercf.cf_1443 = '".$account_id."' and crmentityrel.crmid = '".$job_id."'
															");
			while ($r = $adb->fetch_array($job_costing_agreed_freight_rate)) {
	
				$ServId = $r['cf_1451'];
				if ($ServId) {
				  $q_serv = $adb->pquery('SELECT vtiger_chartofaccount.name as char_of_account FROM vtiger_chartofaccount 
				  						  WHERE chartofaccountid = "'.$ServId.'"');						   
				  $row_serv = $adb->fetch_array($q_serv);
				  //$Serv = $row_serv['name'];
				   $Serv = $row_serv['char_of_account'];
				}
				
				$CurId = $r['cf_1164'];
				if ($CurId) {
				  $q_cur = $adb->pquery('select * from vtiger_currency_info where id = '.$CurId);
				  $row_cur = $adb->fetch_array($q_cur);
				  $Cur = $row_cur['currency_code'];
				}
				
				
			   if($r['cf_1162']>0)
				{
					$agreed_freight_rate .= $Serv.': '.$Cur.' '.$r['cf_1162'].','."\n";
				}
			}
			$agreed_freight_rate = substr($agreed_freight_rate, 0, -2);	
			$request->set('cf_1311', $agreed_freight_rate); //agreed_freight_rate
			


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
		
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE',Vtiger_Functions::jsonEncode($picklistDependencyDatasource));
		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
		$viewer->assign('RECORD_STRUCTURE', $recordStructureInstance->getStructure());
		$viewer->assign('MODULE', $moduleName);
		
		$UKRAINE_AND_RUSSIA_OFFICES = array(...array(self::UKRAINE_OFFICE), ...self::RUSSIA_OFFICE);
		$viewer->assign('USER_FROM_UKRAINE_OR_RUSSIA', (in_array($currentUserModel->get('company_id'), $UKRAINE_AND_RUSSIA_OFFICES) ) ? true : false);

		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());

		$isRelationOperation = $request->get('relationOperation');

		//if it is relation edit
		$viewer->assign('IS_RELATION_OPERATION', $isRelationOperation);
		if($isRelationOperation) {
			$viewer->assign('SOURCE_MODULE', $request->get('sourceModule'));
			$viewer->assign('SOURCE_RECORD', $request->get('sourceRecord'));
		}

		$salutationFieldModel = Vtiger_Field_Model::getInstance('cf_1473', $recordModel->getModule());
		$salutationFieldModel->set('fieldvalue', $recordModel->get('cf_1473'));
		$viewer->assign('SALUTATION_FIELD_MODEL', $salutationFieldModel);
		
		$salutationFieldModelVolume = Vtiger_Field_Model::getInstance('cf_1475', $recordModel->getModule());
		$salutationFieldModelVolume->set('fieldvalue', $recordModel->get('cf_1475'));
		$viewer->assign('SALUTATION_FIELD_MODEL_VOLUME', $salutationFieldModelVolume);
		
		$salutationFieldModelCargoValue = Vtiger_Field_Model::getInstance('cf_1727', $recordModel->getModule());
		$salutationFieldModelCargoValue->set('fieldvalue', $recordModel->get('cf_1727'));
		$viewer->assign('SALUTATION_FIELD_MODEL_CARGO_VALUE', $salutationFieldModelCargoValue);

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
