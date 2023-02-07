<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Class Job_Edit_View extends Vtiger_Edit_View {
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

		$viewer->assign('FLAG_COSTING', false);

		$current_user = Users_Record_Model::getCurrentUserModel(); 		
		$viewer->assign('CURRENT_USER_ID', $current_user->getId());

		if(!empty($record) && $request->get('isDuplicate') == true) {
			$recordModel = $this->record?$this->record:Vtiger_Record_Model::getInstanceById($record, $moduleName);
			$viewer->assign('MODE', '');

			$department_id = $current_user->get('department_id');
			
			$viewer->assign('USER_DEPARTMENT', $department_id);
			$recordModel->set('cf_2197', 'No Costing');
			$recordModel->set('cf_1200','');
			$recordModel->set('cf_1190','');
			$recordModel->set('cf_1198','');
			$recordModel->set('cf_4805','');
			$recordModel->set('cf_5986','');
			

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

			$COUNTRY_CODE = $recordModel->get('job_air_origin_country');
			//echo "<br>";
			 $CITY_CODE = $recordModel->get('job_air_origin_city');
			 $AIRPORT_CODE = $recordModel->get('job_air_origin_unlocode');
            //exit;
			$viewer->assign('COUNTRY_CODE',$COUNTRY_CODE);
			$viewer->assign('CITY_CODE',$CITY_CODE);
			$viewer->assign('AIRPORT_CODE',$AIRPORT_CODE);
			//print_r($recordModel->get('cf_6934')); exit;
			//For Status Check
			$viewer->assign('STATUS_MESSAGE','');
			if(isset($_SESSION['new_job_status_'.$record])) {
				unset($_SESSION['new_job_status_'.$record]);
				$viewer->assign('STATUS_MESSAGE', 'Your request was sent to Job File Control & Audit team(Bakytgul Umtaliyeva | Maral Malikova). After approval status will be changed.<br> Note, please add details in comment section');
			}
			//echo "yes here".$_SESSION['job_status_'.$recordId];

			if(isset($_SESSION['job_status_'.$record]))
			{				
				switch($_SESSION['job_status_'.$record])
				{
					case "1": //to check actual expense and revenue
					$viewer->assign('STATUS_MESSAGE','Job actual revenue and expenses must be booked');
					break;
					case "2":  //to check job in loss
					$viewer->assign('STATUS_MESSAGE','Job in loss, please contact Job File Control & Audit team(Bakytgul Umtaliyeva), <br>Note, please add details in remarks field');
					break;
					case "3":  //check if actual revenue and expense exist.. must be zero
					$viewer->assign('STATUS_MESSAGE','Actual expense and revenue exist, Please create Credit Memo before sending request.');
					break;
					default:
					break;
				}
				unset($_SESSION['job_status_'.$record]);
			}

			//“Deviations Cost” and “Deviation Revenue”.
			$deviation = $this->deviation($record, $recordModel);

			//city list”.
			//$city = $this->getCity($record);
			
			$deviation_cost = ($deviation['actual_expense_cost_usd']>0 ? (($deviation['actual_expense_cost_usd'] - $deviation['expected_cost_usd'])/$deviation['actual_expense_cost_usd']) : '0' );
			$deviation_revenue = ($deviation['actual_selling_cost_usd']>0 ? (($deviation['actual_selling_cost_usd'] - $deviation['expected_revenue_usd'])/$deviation['actual_selling_cost_usd']) : '0');
			$expense_deviation = $deviation_cost*100;			
			$selling_deviation = $deviation_revenue*100;
			
			if($expense_deviation > 10 || $expense_deviation < (-10))
			{
				$_SESSION['job_deviation_cost_'.$record] = '1';
			}
			
			if($selling_deviation > 10 || $selling_deviation < (-10))
			{
				$_SESSION['job_deviation_revenue_'.$record] = '1';
			}
			//////
						
			if(isset($_SESSION['job_deviation_cost_'.$record]))
			{
				switch($_SESSION['job_deviation_cost_'.$record])
				{				
					case "1":
					$viewer->assign('COST_DEVIATION_MESSAGE','The deviation between Expected and Actual Costs is more than 10%, please check and update.');
					break;					
					default:
					break;
				}
				unset($_SESSION['job_deviation_cost_'.$record]);
			}
			if(isset($_SESSION['job_deviation_revenue_'.$record]))
			{
				switch($_SESSION['job_deviation_revenue_'.$record])
				{				
					case "1":
					$viewer->assign('REVENUE_DEVIATION_MESSAGE','The deviation between Expected and Actual Revenue is more than 10%, please check and update.');
					break;					
					default:
					break;
				}
				unset($_SESSION['job_deviation_revenue_'.$record]);
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
			$job_id = $record;
				 
			$params = array($job_id);
			$result_last = $adb_job_costing->pquery($jer_last_sql, $params);
			$row_costing_last = $adb_job_costing->fetch_array($result_last);
			//$count_last_modified = $adb_job_costing->num_rows($result_last);
			
			if($row_costing_last['total_costing']!=0 && $current_user->getId()!=405)
			{
				 $viewer->assign('FLAG_COSTING', true);
			}
			//End for job costing check
			
			$viewer->assign('FLAG_DEPARTMENT', '1');
			if($current_user->getId()==405 && $recordModel->get('cf_1188')!='85805') // check department not equal to ALA
			{
				$viewer->assign('FLAG_DEPARTMENT', '0');
			}


		} else {
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$viewer->assign('MODE', '');
			$recordModel->set('cf_2197', 'No Costing');
			
			if(empty($record))
			{
				$viewer->assign('JOB_TYPE_MESSAGE','');
				if(isset($_SESSION['job_type']))
				{
					$viewer->assign('JOB_TYPE_MESSAGE','Please select job type option');
					unset($_SESSION['job_type']);
				}
				
				$isRelationOperation = $request->get('relationOperation');
				if($isRelationOperation) {
				  $sourceRecord = $request->get('sourceRecord');
				  $sourceModule = $request->get('sourceModule');
				  $quote_info = Vtiger_Record_Model::getInstanceById($sourceRecord, $sourceModule);
				  $request->set('cf_3527', $quote_info->get('assigned_user_id')); // assigned to
				}
				elseif($request->get('returnmodule') == 'Quotes' && $request->get('returnrecord')) {
					$parentModuleName = $request->get('returnmodule');
					$parentRecordId = $request->get('returnrecord');
					$quote_info = Vtiger_Record_Model::getInstanceById($parentRecordId, $parentModuleName);
					$request->set('cf_3527', $quote_info->get('assigned_user_id')); // assigned to
					$request->set('name', $quote_info->get('subject')); //Quote subject
					
					$request->set('cf_1441', $quote_info->get('account_id')); //Customer
					$request->set('cf_1441_display', $quote_info->getDisplayValue('account_id')); //Customer Label
					
					$request->set('cf_1072', $quote_info->get('cf_777')); //shipper
					$request->set('cf_1074', $quote_info->get('cf_1611')); //consignee
					$request->set('cf_1504', $quote_info->get('cf_1613')); //origin_country
					$request->set('cf_1508', $quote_info->get('cf_1615')); //origin_city
					$request->set('cf_1506', $quote_info->get('cf_1617')); //destination_country
					$request->set('cf_1510', $quote_info->get('cf_1619')); //destination_city
					$request->set('cf_1512', $quote_info->get('cf_1621')); //pickup_address
					$request->set('cf_1514', $quote_info->get('cf_1623')); //delivery_address
					$request->set('cf_1516', $quote_info->getDisplayValue('cf_1625')); //expected_date_of_loading
					$request->set('cf_1583', $quote_info->getDisplayValue('cf_1627')); //expected_date_of_delivery
					$request->set('cf_1589', $quote_info->getDisplayValue('cf_1629')); //etd
					$request->set('cf_1591', $quote_info->getDisplayValue('cf_1631')); //eta
					$request->set('cf_1082', $quote_info->get('cf_1633')); //vendor
					// Cargo Details
					$request->set('cf_1429', $quote_info->get('cf_1637')); //noofpieces
					$request->set('cf_1084', $quote_info->get('cf_1639')); //weight
					$request->set('cf_1086', $quote_info->get('cf_1643')); //volume
					$request->set('cf_1524', $quote_info->get('cf_1647')); //cargo_value
					$recordModel->set('cf_1721', $quote_info->get('cf_1725')); //cargo_unit
					//$request->set('cf_1721', $quote_info->get('cf_1725')); //cargo_unit
					$request->set('cf_1518', $quote_info->get('cf_1651')); //commodity
					$request->set('cf_1547', $quote_info->get('cf_1653')); //cargo_description
					$recordModel->set('cf_1520', $quote_info->get('cf_1641')); //weight_units
					$recordModel->set('cf_1522', $quote_info->get('cf_1645')); //volume_units
					//$request->set('cf_1520', $quote_info->get('cf_1641')); //weight_units
					//$request->set('cf_1522', $quote_info->get('cf_1645')); //volume_units
					$request->set('cf_1092', $quote_info->get('cf_1649')); //cntr_transport_types
					$request->set('cf_1711', $quote_info->get('cf_1709')); //mode
					$request->set('cf_1098', $quote_info->get('cf_1791')); //terms_of_delivery
					$request->set('cf_1098', $quote_info->get('cf_1791')); //terms_of_delivery

					$agent_is_payer = $quote_info->get('cf_4027');
					if($agent_is_payer=='1')
					{
						$request->set('cf_1441', $quote_info->get('cf_1827')); //Agent as Customer
						$request->set('cf_1441_display', $quote_info->getDisplayValue('cf_1827')); //Agent as Customer Label

						$request->set('cf_1082', ''); //
						$request->set('cf_1082_display', ''); //		
					}
					else{					
						$request->set('cf_1082', $quote_info->get('cf_1827')); //Agent / Vendor
						$request->set('cf_1082_display', $quote_info->getDisplayValue('cf_1827')); //Agent/Vendor Label
					}
					
					
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


		//Mehtab Code :: 11-11-2015
		if(empty($record)) {
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
			
			$viewer->assign('ACCESS_USER_COMPANY', $access_company_id);
			
			}
			else{
				$current_user = Users_Record_Model::getCurrentUserModel();
				$access_company_id = explode(" |##| ",$current_user->get('access_company_id'));
				$viewer->assign('ACCESS_USER_COMPANY', $access_company_id);
			}
			
			
			
		$sourceModule = $request->get('sourceModule');
		$viewer->assign('SOURCE_MODULE', $sourceModule);
		//Mehtab Code

		// added to set the return values
		if($request->get('returnview')) {
			$request->setViewerReturnValues($viewer);
		}

		$salutationFieldModel = Vtiger_Field_Model::getInstance('cf_1520', $recordModel->getModule());
		$salutationFieldModel->set('fieldvalue', $recordModel->get('cf_1520'));
		$viewer->assign('SALUTATION_FIELD_MODEL', $salutationFieldModel);

		$salutationFieldModelVolume = Vtiger_Field_Model::getInstance('cf_1522', $recordModel->getModule());
		$salutationFieldModelVolume->set('fieldvalue', $recordModel->get('cf_1522'));
		$viewer->assign('SALUTATION_FIELD_MODEL_VOLUME', $salutationFieldModelVolume);

		$salutationFieldModelCargoValue = Vtiger_Field_Model::getInstance('cf_1721', $recordModel->getModule());
		$salutationFieldModelCargoValue->set('fieldvalue', $recordModel->get('cf_1721'));
		$viewer->assign('SALUTATION_FIELD_MODEL_CARGO_VALUE', $salutationFieldModelCargoValue);	


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

	public function deviation($job_id, $recordModel_old)
	{
		 $entries = array();	
		//“Deviations Cost” and “Deviation Revenue”.
		include("include/Exchangerate/exchange_rate_class.php");
		//For JER
		 $db = PearDatabase::getInstance();
		 $jer_sum_sql =  $db->pquery("SELECT sum(jercf.cf_1160) as total_cost_local_currency , sum(jercf.cf_1168) as total_revenue_local_currency 
						   FROM `vtiger_jercf` as jercf 
						   INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = jercf.jerid
						   INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid 
						   WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid='".$job_id."' 
						   AND crmentityrel.module='Job' AND crmentityrel.relmodule='JER' ");
		 $row_job_costing = $db->fetch_array($jer_sum_sql);				   
		 $total_cost_local_currency = $row_job_costing['total_cost_local_currency'];
		 $total_revenue_local_currency = $row_job_costing['total_revenue_local_currency'];
		 
		 $file_title = $recordModel_old->get('cf_1186');
		 $file_title_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$file_title);
		 
		 $jer_last_sql =  $db->pquery("SELECT vtiger_crmentity.modifiedtime, vtiger_crmentity.createdtime FROM `vtiger_jercf` as jercf 
									   INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = jercf.jerid
									   INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid 
									   WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid='".$job_id."'  AND crmentityrel.module='Job' 
									   AND crmentityrel.relmodule='JER' order by vtiger_crmentity.modifiedtime DESC limit 1 ");
			
		 $count_last_modified = $db->num_rows($jer_last_sql);	
		 $exchange_rate_date  = date('Y-m-d');
		 if($count_last_modified>0)
		 {
			 $row_costing_last = $db->fetch_array($jer_last_sql);
			 $modifiedtime = $row_costing_last['modifiedtime'];
			 if($modifiedtime=='0000-00-00 00:00:00')
			 {
				 $createdtime = strtotime($row_costing_last['createdtime']);
				 $exchange_rate_date = date('Y-m-d', $createdtime);
			 }
			 else{
				$modifiedtime = strtotime($row_costing_last['modifiedtime']);
				$exchange_rate_date = date('Y-m-d', $modifiedtime);
			 }
		 }
		 if($file_title_currency!='USD')
		{			
			$final_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $exchange_rate_date);			
		}else{
			$final_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $exchange_rate_date);
		}					   
		
		$TOTAL_COST_USD = 0;
		$TOTAL_REVENUE_USD = 0;
				
		if($final_exchange_rate>0)
		{
			$total_cost_usd = $total_cost_local_currency/$final_exchange_rate;
			$total_revenue_usd = $total_revenue_local_currency/$final_exchange_rate;
			
			$TOTAL_COST_USD = number_format ( (empty($total_cost_usd) ? 0 : $total_cost_usd) , 2 ,  "." , "" );
			$TOTAL_REVENUE_USD = number_format ( (empty($total_revenue_usd) ? 0 : $total_revenue_usd) , 2 ,  "." , "" );
		}
		
		$entries['expected_cost_usd'] = $TOTAL_COST_USD;
		$entries['expected_revenue_usd'] = $TOTAL_REVENUE_USD;
		
		//For Actual Cost
		//For JRER Expense
		//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
		$jrer_sql_expense =  "SELECT vtiger_jobexpencereportcf.cf_1347 as buy_local_currency_gross, 
								     vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
									 vtiger_jobexpencereportcf.cf_1351 as expected_buy_local_currency_net, 
									 vtiger_jobexpencereportcf.cf_1353 as variation_expected_and_actual_buying,
									 vtiger_jobexpencereportcf.cf_1216 as expense_invoice_date,
									 vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
							  FROM `vtiger_jobexpencereport` 
							  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
 							  INNER JOIN vtiger_crmentityrel ON 
							  (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid ) 
 							  Left join vtiger_jobexpencereportcf as vtiger_jobexpencereportcf ON
							  vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid 
		 					  WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid='".$job_id."' AND vtiger_crmentityrel.module='Job' 
							  AND vtiger_crmentityrel.relmodule='Jobexpencereport' AND vtiger_jobexpencereportcf.cf_1457='Expence'
							  AND vtiger_jobexpencereport.owner_id = '".$recordModel_old->get('assigned_user_id')."' 
							  ORDER BY vtiger_jobexpencereportcf.cf_1345 DESC";
		$result_expense = $db->pquery($jrer_sql_expense, array());
		$numRows_expnese = $db->num_rows($result_expense);		
		
		$total_cost_in_usd_net = 0;
				
		if($numRows_expnese>0)
		{	
			for($jj=0; $jj< $db->num_rows($result_expense); $jj++ ) {
				$row_job_jrer_expense = $db->fetch_row($result_expense,$jj);
		
				$expense_invoice_date = $row_job_jrer_expense['expense_invoice_date'];
						
				$CurId = $row_job_jrer_expense['buy_currency_id'];
				if ($CurId) {				
				 	$q_cur = 'select * from vtiger_currency_info where id = "'.$CurId.'"';
					$result_q_cur = $db->pquery($q_cur, array());
					$row_cur = $db->fetch_array($result_q_cur);
					$Cur = $row_cur['currency_code'];
				}
				
				$b_exchange_rate = $final_exchange_rate;					
				if(!empty($expense_invoice_date))
				{
					if($file_title_currency!='USD')
					{
						$b_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $expense_invoice_date);
					}else{
						$b_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $expense_invoice_date);
					}
				}
				
				if($file_title_currency!='USD')
				{					
					$total_cost_in_usd_net += $row_job_jrer_expense['buy_local_currency_net']/$b_exchange_rate;					
				}
				else{					
					$total_cost_in_usd_net += $row_job_jrer_expense['buy_local_currency_net'];
				}
			}
			
		}
		$entries['actual_expense_cost_usd'] = number_format ( $total_cost_in_usd_net , 2 ,  "." , "" );
		
		
		//For JRER Selling
		//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
		$jrer_selling_sql_selling =  "SELECT vtiger_jobexpencereportcf.cf_1232 as sell_customer_currency_gross, 
								      vtiger_jobexpencereportcf.cf_1238 as sell_local_currency_gross,
									  vtiger_jobexpencereportcf.cf_1240 as sell_local_currency_net, 
									  vtiger_jobexpencereportcf.cf_1242 as expected_sell_local_currency_net,
									  vtiger_jobexpencereportcf.cf_1244 as variation_expected_and_actual_selling,
									  vtiger_jobexpencereportcf.cf_1246 as variation_expect_and_actual_profit,
									  vtiger_jobexpencereportcf.cf_1355 as sell_invoice_date,
									  vtiger_jobexpencereportcf.cf_1234 as currency_id
									  FROM `vtiger_jobexpencereport` 
									  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
									  INNER JOIN vtiger_crmentityrel ON 
									  (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid ) 
									  Left JOIN vtiger_jobexpencereportcf as vtiger_jobexpencereportcf ON
									  vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid						  
									  WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid='".$job_id."' AND vtiger_crmentityrel.module='Job' 
									  AND vtiger_crmentityrel.relmodule='Jobexpencereport' AND vtiger_jobexpencereportcf.cf_1457='Selling'
									  AND vtiger_jobexpencereport.owner_id = '".$recordModel_old->get('assigned_user_id')."' 
									  ORDER BY vtiger_jobexpencereportcf.cf_1234 DESC";
		
		$result_invoice = $db->pquery($jrer_selling_sql_selling, array());
		$numRows_invoice = $db->num_rows($result_invoice);
		$total_cost_in_usd_sell_net = 0;		
		if($numRows_invoice>0)
			{	
				for($jj=0; $jj< $db->num_rows($result_invoice); $jj++ ) {
							
					$row_job_jrer_invoice = $db->fetch_row($result_invoice,$jj);				
					$sell_invoice_date = $row_job_jrer_invoice['sell_invoice_date'];
					$exchange_rate_date_invoice =$sell_invoice_date;
					
					$CurId = $row_job_jrer_invoice['currency_id'];
					if ($CurId) {					 
					  $q_cur = 'select * from vtiger_currency_info where id = "'.$CurId.'"';
					  $result_q_cur = $db->pquery($q_cur, array());
					  $row_cur = $db->fetch_array($result_q_cur);
					  $Cur = $row_cur['currency_code'];
					}						
					
					$s_exchange_rate = $final_exchange_rate;
					if(!empty($exchange_rate_date_invoice))
					{
						if($file_title_currency!='USD')
						{
							$s_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $exchange_rate_date_invoice);
						}else{
							$s_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $exchange_rate_date_invoice);
						}
					}					
									
					$new_rate = $s_exchange_rate;								
					if($file_title_currency!='USD')
					{											
						$total_cost_in_usd_sell_net += $row_job_jrer_invoice['sell_local_currency_net']/$s_exchange_rate;
					}
					else{
						$total_cost_in_usd_sell_net += $row_job_jrer_invoice['sell_local_currency_net'];
					}						
				}
			}
			$entries['actual_selling_cost_usd'] = number_format ( $total_cost_in_usd_sell_net , 2 ,  "." , "" );
			
			return $entries;
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
