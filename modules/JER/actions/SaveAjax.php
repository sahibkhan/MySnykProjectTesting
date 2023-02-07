<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class JER_SaveAjax_Action extends Vtiger_SaveAjax_Action {

	public function process(Vtiger_Request $request) {
		$fieldToBeSaved = $request->get('field');
		$response = new Vtiger_Response();
		try {
			vglobal('VTIGER_TIMESTAMP_NO_CHANGE_MODE', $request->get('_timeStampNoChangeMode',false));
			$_SESSION['JER_SAVE'] = true;
			$cf_1451 = $request->get('cf_1451');
			$cf_1433 = $request->get('cf_1433');
			$cf_1435 = $request->get('cf_1435');
			$cf_1176 = $request->get('cf_1176');
			$cf_1154 = $request->get('cf_1154');//Buy
			$cf_6350 = $request->get('cf_6350');//Buy VAT Rate
				
			$cf_1156 = $request->get('cf_1156');//cost currency
			$cf_1158 = $request->get('cf_1158'); //exchange rate
			$cf_6352 = $request->get('cf_6352');//Buy(Local Curr Gross)	
			$cf_1160 = $request->get('cf_1160');//buy local currency
			
			$cf_1443 = $request->get('cf_1443');
			$cf_1162 = $request->get('cf_1162'); //Sell
			$cf_6354 = $request->get('cf_6354'); //Sell VAT
			$cf_1164 = $request->get('cf_1164');
			$cf_1166 = $request->get('cf_1166');
			$cf_6356 = $request->get('cf_6356'); //Sell(Local Curr Gross)	
			$cf_1168 = $request->get('cf_1168'); //Sell Local Currency

			$job_id 			  = $request->get('sourceRecord');
			$sourceModule 		  = $request->get('sourceModule');	
			$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule);
			
			$job_department_id = $job_info->get('cf_1190');
			$job_office_id = $job_info->get('cf_1188');
			
			$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info->get('cf_1186'));
			$file_title_currency = $reporting_currency;
			include("include/Exchangerate/exchange_rate_class.php");			
			$adb = PearDatabase::getInstance();
			$adb->pquery("UPDATE vtiger_jobcf set cf_2197=? WHERE jobid=? AND cf_2197=?",array('In Progress',$job_id,'No Costing'));
			
			//Buy: cf_1154
			$cost_vendor = $cf_1154;
			$cost_vat_rate = $cf_6350;
			
			if(empty($cost_vendor)) { $cost_vendor = 0; }			
			//buy currency: cf_1156
			$pay_to_currency_code = $cf_1156;
			
			//exchange rate: cf_1158
			$pay_to_currency = Vtiger_CurrencyList_UIType::getDisplayValue($pay_to_currency_code);
			if($file_title_currency =='KZT')
			{	
				//$pay_to_currency_code = Vtiger_CurrencyList_UIType::getDisplayValue($pay_to_currency);
				$cost_exchange_rate   = exchange_rate_currency(date('Y-m-d'), $pay_to_currency);			
			}
			elseif($file_title_currency =='USD')
			{
				$cost_exchange_rate = currency_rate_convert($pay_to_currency, $file_title_currency, 1, date('Y-m-d'));
			}
			else{
				$cost_exchange_rate = currency_rate_convert_others($pay_to_currency, $file_title_currency, 1, date('Y-m-d'));
			}			
			//echo $cost_exchange_rate;
			//exit;
			//Buy local currency: cf_1160
			if(!empty($cost_vendor))
			{
				$cost_vat = '0.00';
				if(!empty($cost_vat_rate) && $cost_vat_rate>0)
				{
					$cost_vat_rate_cal = $cost_vat_rate/100; 
					$cost_vat          = $cost_vendor * $cost_vat_rate_cal;
				}
				
				$cost_currency_gross = $cost_vendor + $cost_vat;
				
				
				if($file_title_currency !='USD')
				{
					//currency gross	
					$cost_local_currency_gross = $cost_currency_gross * $cost_exchange_rate;
					//currency net			
					$costlocalcurrency = $cost_vendor * $cost_exchange_rate;
				}else{
					//currency gross
					$cost_local_currency_gross = exchange_rate_convert($pay_to_currency, $file_title_currency,$cost_currency_gross, date('Y-m-d'));
					//currency net
					$costlocalcurrency = exchange_rate_convert($pay_to_currency, $file_title_currency,$cost_vendor, date('Y-m-d'));
				}
				$cost_local_currency  = number_format($costlocalcurrency, 2, '.', '');
				$cost_exchange_rate  = number_format($cost_exchange_rate, 2, '.', '');
				
				$cost_local_currency_gross = number_format($cost_local_currency_gross, 2, '.', '');
				
				
			}
			$request->set('cf_1158', $cost_exchange_rate);
			$request->set('cf_6352',$cost_local_currency_gross);
			$request->set('cf_1160', $cost_local_currency);


			//sell_customer: cf_1162
			$sell_customer = $cf_1162;
			$sell_vat_rate = $cf_6354; //Sell VAT Rate
			
			if(empty($sell_customer)) { $sell_customer = 0;}
			$request->set('cf_1162', $sell_customer);
			//customer_currency: cf_1164
			$customer_currency_code = $cf_1164;
					
			$customer_currency = Vtiger_CurrencyList_UIType::getDisplayValue($customer_currency_code);
			
			if($file_title_currency=='KZT')
			{				
				$revenue_exchange_rate = exchange_rate_currency(date('Y-m-d'), $customer_currency);				
			}
			elseif($file_title_currency=='USD')
			{
				$revenue_exchange_rate = currency_rate_convert($customer_currency, $file_title_currency, 1, date('Y-m-d'));
			}else{
				$revenue_exchange_rate = currency_rate_convert_others($customer_currency, $file_title_currency, 1, date('Y-m-d'));
			}
			
			$request->set('cf_1166', $revenue_exchange_rate);
			
			$sell_vat = '0.00';
			if(!empty($sell_vat_rate) && $sell_vat_rate>0)
			{
				$sell_vat_rate_cal = $sell_vat_rate/100; 
				$sell_vat          = $sell_customer * $sell_vat_rate_cal;
			}
			
			$sell_currency_gross = $sell_customer + $sell_vat;
			
			if($file_title_currency !='USD')
			{
				//currency gross	
				$sell_local_currency_gross = $sell_currency_gross * $revenue_exchange_rate;
				//currency net	
				$sell_local_currency = $sell_customer * $revenue_exchange_rate;
			}
			else{
				//currency gross
				$sell_local_currency_gross = exchange_rate_convert($customer_currency, $file_title_currency,$sell_currency_gross, date('Y-m-d'));
				//currency net	
				$sell_local_currency = exchange_rate_convert($customer_currency, $file_title_currency,$sell_customer, date('Y-m-d'));
			}
			$request->set('cf_6356',$sell_local_currency_gross);
			$request->set('cf_1168', $sell_local_currency);
			
			
			$recordModel = $this->saveRecord($request);

			vglobal('VTIGER_TIMESTAMP_NO_CHANGE_MODE', false);

			$fieldModelList = $recordModel->getModule()->getFields();
			$result = array();
			$picklistColorMap = array();
			foreach ($fieldModelList as $fieldName => $fieldModel) {
				if($fieldModel->isViewable()){
					$recordFieldValue = $recordModel->get($fieldName);
					if(is_array($recordFieldValue) && $fieldModel->getFieldDataType() == 'multipicklist') {
						foreach ($recordFieldValue as $picklistValue) {
							$picklistColorMap[$picklistValue] = Settings_Picklist_Module_Model::getPicklistColorByValue($fieldName, $picklistValue);
						}
						$recordFieldValue = implode(' |##| ', $recordFieldValue);     
					}
					if($fieldModel->getFieldDataType() == 'picklist') {
						$picklistColorMap[$recordFieldValue] = Settings_Picklist_Module_Model::getPicklistColorByValue($fieldName, $recordFieldValue);
					}
					$fieldValue = $displayValue = Vtiger_Util_Helper::toSafeHTML($recordFieldValue);
					if ($fieldModel->getFieldDataType() !== 'currency' && $fieldModel->getFieldDataType() !== 'datetime' && $fieldModel->getFieldDataType() !== 'date' && $fieldModel->getFieldDataType() !== 'double') { 
						$displayValue = $fieldModel->getDisplayValue($fieldValue, $recordModel->getId()); 
					}
					if ($fieldModel->getFieldDataType() == 'currency') {
						$displayValue = Vtiger_Currency_UIType::transformDisplayValue($fieldValue);
					}
					if(!empty($picklistColorMap)) {
						$result[$fieldName] = array('value' => $fieldValue, 'display_value' => $displayValue, 'colormap' => $picklistColorMap);
					} else {
						$result[$fieldName] = array('value' => $fieldValue, 'display_value' => $displayValue);
					}
				}
			}

			//Handling salutation type
			if ($request->get('field') === 'firstname' && in_array($request->getModule(), array('Contacts', 'Leads'))) {
				$salutationType = $recordModel->getDisplayValue('salutationtype');
				$firstNameDetails = $result['firstname'];
				$firstNameDetails['display_value'] = $salutationType. " " .$firstNameDetails['display_value'];
				if ($salutationType != '--None--') $result['firstname'] = $firstNameDetails;
			}


			// Adding job costing data in JRER (Expense and Selling)
			$adb = PearDatabase::getInstance();
			$current_user = Users_Record_Model::getCurrentUserModel();
			$ownerId = $recordModel->get('assigned_user_id');
			$date_var = date("Y-m-d H:i:s");
			$usetime = $adb->formatDate($date_var, true);
			$job_costing_id = $recordModel->getId();


			$current_id = $adb->getUniqueId('vtiger_crmentity');
			$source_id = $request->get('sourceRecord');

			//INSERT data in JRER expense module from job costing
			$adb->pquery("INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid,
			setype, description, createdtime, modifiedtime, presence, deleted, label)
		    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
		    array($current_id, $current_user->getId(), $current_user->getId(), 'Jobexpencereport', 'NULL', $date_var, $date_var, 1, 0, 'Job Costing Buy'));
		   
			//INSERT data in jobexpencereport module from job costing
			$adb_e = PearDatabase::getInstance();
			$jobexpencereport_insert_query = "INSERT INTO vtiger_jobexpencereport(jobexpencereportid, name, jerid, job_costing_type, user_id, owner_id, job_id) VALUES(?,?,?,?,?,?,?)";
			$params_jobexpencereport= array($current_id, $source_id, $job_costing_id, 'Expense', $current_user->getId(), $current_user->getId(), $source_id);			
			$adb_e->pquery($jobexpencereport_insert_query, $params_jobexpencereport);			
			//$jobexpencereportid = $adb_e->getLastInsertID();
			$jobexpencereportid = $current_id;

			//cf_1477 = Office
			//cf_1479 = Department
			//cf_1367 = pay_to
			//cf_1345 = vendor currency
			//cf_1222 = exchange rate
			//cf_1351 = Expected Buy (Local Currency NET)
			$adb_ecf = PearDatabase::getInstance();
			$jobexpencereportcf_insert_query = "INSERT INTO vtiger_jobexpencereportcf(jobexpencereportid, cf_1453, cf_1477, cf_1479, cf_1367, cf_1345, cf_1222, cf_1351, cf_1457) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)";
			$params_jobexpencereportcf = array($current_id, $cf_1451, $cf_1433, $cf_1435, $cf_1176, $cf_1156, $cost_exchange_rate, $cost_local_currency, 'Expence');
			$adb_ecf->pquery($jobexpencereportcf_insert_query, $params_jobexpencereportcf);
			$jobexpencereportcfid = $current_id;

			$adb_rel = PearDatabase::getInstance();
			$crmentityrel_insert_query = "INSERT INTO vtiger_crmentityrel(crmid, module, relcrmid, relmodule) VALUES(?, ?, ?, ?)";
			$params_crmentityrel = array($source_id, 'Job', $jobexpencereportcfid, 'Jobexpencereport');
			$adb_rel->pquery($crmentityrel_insert_query, $params_crmentityrel);


			//INSERT data in JRER selling module from job costing
				$current_id = $adb_rel->getUniqueId('vtiger_crmentity');
				$adb_crm = PearDatabase::getInstance();
				$adb_crm->pquery("INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid,
					 setype, description, createdtime, modifiedtime, presence, deleted, label)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
					array($current_id, $current_user->getId(), $current_user->getId(), 'Jobexpencereport', 'NULL', $date_var, $date_var, 1, 0, 'Job Costing Sell'));
				
				//INSERT data in jobexpencereport module from job costing
				$adb_s = PearDatabase::getInstance();
				$jobexpencereport_insert_query = "INSERT INTO vtiger_jobexpencereport(jobexpencereportid, name, jerid, job_costing_type, user_id, owner_id, job_id) VALUES(?,?,?,?,?,?,?)";			
				$params_jobexpencereport= array($current_id, $source_id, $job_costing_id, 'Selling', $current_user->getId(), $current_user->getId(), $source_id);
				$adb_s->pquery($jobexpencereport_insert_query, $params_jobexpencereport);
				//$jobexpencereportid = $adb_s->getLastInsertID();
				$jobexpencereportid = $current_id;
				//cf_1455 = s_job_charges_id
				//cf_1477 = Office
				//cf_1479 = Department
				//cf_1445 = s_bill_to_id
				//cf_1355 = s_invoice_date				
				//cf_1234 = s_customer_currency
				//cf_1236 = s_exchange_rate
				//cf_1242 = s_expected_sell_local_currency_net
				$adb_scf = PearDatabase::getInstance();
				$jobexpencereportcf_insert_query = "INSERT INTO vtiger_jobexpencereportcf (jobexpencereportid, cf_1455, cf_1477, cf_1479, cf_1445, cf_1234, cf_1236, cf_1242, cf_1457) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)";
				$params_jobexpencereportcf = array($current_id, $cf_1451, $job_office_id, $job_department_id, $cf_1443, $cf_1164, $revenue_exchange_rate, $sell_local_currency, 'Selling');
				$adb_scf->pquery($jobexpencereportcf_insert_query, $params_jobexpencereportcf);
				$jobexpencereportcfid = $current_id;
				
				$adb_srel = PearDatabase::getInstance();
				$crmentityrel_insert_query = "INSERT INTO vtiger_crmentityrel(crmid, module, relcrmid, relmodule) VALUES(?, ?, ?, ?)";
				$params_crmentityrel = array($source_id, 'Job', $jobexpencereportcfid, 'Jobexpencereport');
				$adb_srel->pquery($crmentityrel_insert_query, $params_crmentityrel);
			
				//End of adding job costing data in JRER

			// removed decode_html to eliminate XSS vulnerability
			$result['_recordLabel'] = decode_html($recordModel->getName());
			$result['_recordId'] = $recordModel->getId();
			$response->setEmitType(Vtiger_Response::$EMIT_JSON);
			$response->setResult($result);
		} catch (DuplicateException $e) {
			$response->setError($e->getMessage(), $e->getDuplicationMessage(), $e->getMessage());
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}
		$response->emit();
	}

	/**
	 * Function to get the record model based on the request parameters
	 * @param Vtiger_Request $request
	 * @return Vtiger_Record_Model or Module specific Record Model instance
	 */
	public function getRecordModelFromRequest(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		if($moduleName == 'Calendar') {
			$moduleName = $request->get('calendarModule');
		}
		$recordId = $request->get('record');

		if(!empty($recordId)) {
			$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
			$recordModel->set('id', $recordId);
			$recordModel->set('mode', 'edit');

			$fieldModelList = $recordModel->getModule()->getFields();
			foreach ($fieldModelList as $fieldName => $fieldModel) {
				//For not converting createdtime and modified time to user format
				$uiType = $fieldModel->get('uitype');
				if ($uiType == 70) {
					$fieldValue = $recordModel->get($fieldName);
				} else {
					$fieldValue = $fieldModel->getUITypeModel()->getUserRequestValue($recordModel->get($fieldName));
				}

				// To support Inline Edit in Vtiger7
				if($request->has($fieldName)){
					$fieldValue = $request->get($fieldName,null);
				}else if($fieldName === $request->get('field')){
					$fieldValue = $request->get('value');
				}
				$fieldDataType = $fieldModel->getFieldDataType();
				if ($fieldDataType == 'time' && $fieldValue !== null) {
					$fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
				}
				if ($fieldValue !== null) {
					if (!is_array($fieldValue)) {
						$fieldValue = trim($fieldValue);
					}
					$recordModel->set($fieldName, $fieldValue);
				}
				$recordModel->set($fieldName, $fieldValue);
				if($fieldName === 'contact_id' && isRecordExists($fieldValue)) {
					$contactRecord = Vtiger_Record_Model::getInstanceById($fieldValue, 'Contacts');
					$recordModel->set("relatedContact",$contactRecord);
				}
			}
		} else {
			$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$recordModel->set('mode', '');

			$fieldModelList = $moduleModel->getFields();
			foreach ($fieldModelList as $fieldName => $fieldModel) {
				if ($request->has($fieldName)) {
					$fieldValue = $request->get($fieldName, null);
				} else {
					$fieldValue = $fieldModel->getDefaultFieldValue();
				}
                if($fieldValue){
                    $fieldValue = Vtiger_Util_Helper::validateFieldValue($fieldValue,$fieldModel);
                }
				$fieldDataType = $fieldModel->getFieldDataType();
				if ($fieldDataType == 'time' && $fieldValue !== null) {
					$fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
				}
				if ($fieldValue !== null) {
					if (!is_array($fieldValue)) {
						$fieldValue = trim($fieldValue);
					}
					$recordModel->set($fieldName, $fieldValue);
				}
			} 
		}

		return $recordModel;
	}
}
