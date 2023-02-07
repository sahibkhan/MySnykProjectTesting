<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class JER_Save_Action extends Vtiger_Save_Action {

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$record = $request->get('record');
		
		$module_get = $_GET['module'];
		$record_get = $_GET['record'];
		$custom_permission_check = custom_access_rules($record_get,$module_get);
		
		if ((!Users_Privileges_Model::isPermitted($moduleName, 'Save', $record)) && ($custom_permission_check == 'yes')) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
		
	}

	public function process(Vtiger_Request $request) {
		
		$adb = PearDatabase::getInstance();
		
		$job_costing_id = $request->get('record');
			
		$query_job_costing =  'SELECT * from vtiger_crmentityrel where vtiger_crmentityrel.relcrmid=? AND vtiger_crmentityrel.module="Job"';
		$check_params = array($job_costing_id);
		$result = $adb->pquery($query_job_costing, $check_params);
		$row = $adb->fetch_array($result);
		$job_id 	  = $row['crmid'];
		$sourceModule = $row['module'];	
		
		$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule);
		$job_department_id = $job_info->get('cf_1190');
		$job_office_id = $job_info->get('cf_1188');
		
		$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info->get('cf_1186'));
		$file_title_currency = $reporting_currency;
		include("include/Exchangerate/exchange_rate_class.php");
		
		
		$cf_1451 = $request->get('cf_1451');
		$cf_1433 = $request->get('cf_1433');
		$cf_1435 = $request->get('cf_1435');
		$cf_1176 = $request->get('cf_1176');
		$cf_1154 = $request->get('cf_1154');
		$cf_6350 = $request->get('cf_6350');//Buy VAT Rate
		
		$cf_1156 = $request->get('cf_1156');
		$cf_1158 = $request->get('cf_1158');
		$cf_6352 = $request->get('cf_6352');//Buy(Local Curr Gross)	
		
		$cf_1160 = $request->get('cf_1160');
		$cf_1443 = $request->get('cf_1443');
		$cf_1162 = $request->get('cf_1162');
		$cf_6354 = $request->get('cf_6354'); //Sell VAT
		$cf_1164 = $request->get('cf_1164');
		$cf_1166 = $request->get('cf_1166');
		$cf_6356 = $request->get('cf_6356'); //Sell(Local Curr Gross)	
		$cf_1168 = $request->get('cf_1168');
		
		//Buy: cf_1154
		$cost_vendor = $cf_1154;
		if(empty($cost_vendor)) { $cost_vendor = 0; }
		$request->set('cf_1154', $cf_1154);
		
		$cost_vat_rate = $cf_6350;
		$request->set('cf_6350', $cf_6350); //cost vat rate
		//buy currency: cf_1156
		$pay_to_currency_code = $cf_1156;
		$request->set('cf_1156', $cf_1156);
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
		//Buy local currency: cf_1160
		$cost_local_currecny = '0.00';
		$cost_local_currecny_gross='0.00';
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
				$cost_local_currecny_gross = $cost_currency_gross * $cost_exchange_rate;
				//currency net				
				$costlocalcurrency = $cost_vendor * $cost_exchange_rate;
			}else{
				//currency gross
				$cost_local_currecny_gross = exchange_rate_convert($pay_to_currency, $file_title_currency,$cost_currency_gross, date('Y-m-d'));
				//currency net
				$costlocalcurrency = exchange_rate_convert($pay_to_currency, $file_title_currency,$cost_vendor, date('Y-m-d'));
			}
			
			$cost_local_currecny  = number_format($costlocalcurrency, 2, '.', '');
			$cost_exchange_rate  = number_format($cost_exchange_rate, 2, '.', '');
			
			$cost_local_currecny_gross = number_format($cost_local_currecny_gross, 2, '.', '');
		}
		$request->set('cf_1158', $cost_exchange_rate);
		$request->set('cf_6352',$cost_local_currecny_gross);
		$request->set('cf_1160', $cost_local_currecny);		
		
							
		$request->set('cf_1451', $cf_1451);
		$request->set('cf_1433', $cf_1433);
		$request->set('cf_1435', $cf_1435);
		$request->set('cf_1176', $cf_1176);		
		
		$request->set('cf_1443', $cf_1443);
		//sell_customer: cf_1162
		$sell_customer = $cf_1162;
		if(empty($sell_customer)) { $sell_customer = 0;}
		$request->set('cf_1162', $sell_customer);
		
		$sell_vat_rate = $cf_6354; //Sell VAT Rate
		$request->set('cf_6354', $cf_6354); //sell vat rate
		//customer_currency: cf_1164
		$customer_currency_code = $cf_1164;
		$request->set('cf_1164', $customer_currency_code);
		$customer_currency = Vtiger_CurrencyList_UIType::getDisplayValue($customer_currency_code);
		if($file_title_currency=='KZT')
		{
			//$customer_currency_code = Vtiger_CurrencyList_UIType::getDisplayValue($customer_currency);
			$revenue_exchange_rate   = exchange_rate_currency(date('Y-m-d'), $customer_currency);				
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
		
		$sell_local_currency = '0.00';
		$sell_local_currecny_gross='0.00';
		if($file_title_currency !='USD')
		{
			//currency gross	
			$sell_local_currecny_gross = $sell_currency_gross * $revenue_exchange_rate;
			//currency net		
			$sell_local_currency = $sell_customer * $revenue_exchange_rate;
		}
		else{
			//currency gross
			$sell_local_currecny_gross = exchange_rate_convert($customer_currency, $file_title_currency,$sell_currency_gross, date('Y-m-d'));
			//currency net	
			$sell_local_currency = exchange_rate_convert($customer_currency, $file_title_currency,$sell_customer, date('Y-m-d'));
		}
		$request->set('cf_6356',$sell_local_currecny_gross);
		$request->set('cf_1168', $sell_local_currency);	
									
		$recordModel = $this->saveRecord($request);
		
		$adb_jrer_e = PearDatabase::getInstance();		
		
		$query_jrer_e =  'SELECT * from vtiger_jobexpencereport where jerid=? and job_costing_type=?';
		$jrer_params_e = array($job_costing_id, 'Expense');
		$result_jrer_e = $adb_jrer_e->pquery($query_jrer_e, $jrer_params_e);
		$row_jrer_e = $adb_jrer_e->fetch_array($result_jrer_e);
		
					
		$adb_exp = PearDatabase::getInstance();
		//$jobexpencereportcf_update_query = "update vtiger_jobexpencereportcf set cf_1453=?, cf_1477=?, cf_1479=?, cf_1367=?, cf_1345=?, cf_1222=?, cf_1351=?, cf_1457=? where jobexpencereportid=?";
		//$params_jobexpencereportcf = array($cf_1451, $cf_1433, $cf_1435, $cf_1176, $cf_1156, $cost_exchange_rate, $cost_local_currecny, 'Expence', $row_jrer_e['jobexpencereportid']);
		$jobexpencereportcf_update_query = "update vtiger_jobexpencereportcf set cf_1351=?, cf_1457=? where jobexpencereportid=?";
		$params_jobexpencereportcf = array($cost_local_currecny, 'Expence', $row_jrer_e['jobexpencereportid']);
		$adb_exp->pquery($jobexpencereportcf_update_query, $params_jobexpencereportcf);
		
		//Update selling
		$adb_jrer_s = PearDatabase::getInstance();				
		$query_jrer_s =  'SELECT * from vtiger_jobexpencereport where jerid=? and job_costing_type=?';
		$jrer_params_s = array($job_costing_id, 'Selling');
		$result_jrer_s = $adb_jrer_s->pquery($query_jrer_s, $jrer_params_s);
		$row_jrer_s = $adb_jrer_s->fetch_array($result_jrer_s);
		
		$adb_sell = PearDatabase::getInstance();
		//$s_jobexpencereportcf_update_query = "update vtiger_jobexpencereportcf set cf_1455=?, cf_1477=?, cf_1479=?, cf_1445=?, cf_1234=?, cf_1236=?, cf_1242=?, cf_1457=? where jobexpencereportid=?";
		//$s_params_jobexpencereportcf = array($cf_1451, $job_office_id, $job_department_id, $cf_1443, $cf_1164, $revenue_exchange_rate, $sell_local_currency, 'Selling', $row_jrer_s['jobexpencereportid']);
		$s_jobexpencereportcf_update_query = "update vtiger_jobexpencereportcf set cf_1242=?, cf_1457=? where jobexpencereportid=?";
		$s_params_jobexpencereportcf = array($sell_local_currency, 'Selling', $row_jrer_s['jobexpencereportid']);
		$adb_sell->pquery($s_jobexpencereportcf_update_query, $s_params_jobexpencereportcf);
		
		
		
		if($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			$parentRecordId = $request->get('sourceRecord');
			$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentRecordId, $parentModuleName);
			//TODO : Url should load the related list instead of detail view of record
			$loadUrl = $parentRecordModel->getDetailViewUrl();
		} else if ($request->get('returnToList')) {
			$loadUrl = $recordModel->getModule()->getListViewUrl();
		} else {
			$loadUrl = $recordModel->getDetailViewUrl();
		}
		
		$loadUrl = 'index.php?module=Job&relatedModule='.$request->get('module').
				'&view=Detail&record='.$job_id.'&mode=showRelatedList';
		
		header("Location: $loadUrl");
	}
	
	

	/**
	 * Function to save record
	 * @param <Vtiger_Request> $request - values of the record
	 * @return <RecordModel> - record Model of saved record
	 */
	public function saveRecord($request) {
		
		$recordModel = $this->getRecordModelFromRequest($request);
		$_SESSION['sendmsg_repeat'] = $request->getModule();
		
		$recordModel->save();
		if($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			$parentModuleModel = Vtiger_Module_Model::getInstance($parentModuleName);
			$parentRecordId = $request->get('sourceRecord');
			$relatedModule = $recordModel->getModule();
			$relatedRecordId = $recordModel->getId();

			$relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relatedModule);
			$relationModel->addRelation($parentRecordId, $relatedRecordId);
		}
		
		$recordId = $request->get('record');
		/*eliminate this step
		if(empty($recordId)) {
			$adb = PearDatabase::getInstance();
			
			$current_user = Users_Record_Model::getCurrentUserModel();
			$ownerId = $recordModel->get('assigned_user_id');			
			
			$date_var = date("Y-m-d H:i:s");
			$usetime = $adb->formatDate($date_var, true);
			
			$check_dept_office_job_jobexp =  'SELECT * from vtiger_jobexpcf as jobexpcf
											  INNER JOIN vtiger_crmentityrel as crmentityrel ON crmentityrel.relcrmid=jobexpcf.jobexpid
											  where jobexpcf.cf_1259=? AND jobexpcf.cf_1257=? AND crmentityrel.crmid=? and crmentityrel.module="Job"';
			$check_params = array($_POST['cf_1435'],$_POST['cf_1433'], $_POST['sourceRecord']);
			$result = $adb->pquery($check_dept_office_job_jobexp, $check_params);
			$row = $adb->fetch_array($result);						  
			if($adb->num_rows($result)==0)
			{	
			   $current_id = $adb->getUniqueId('vtiger_crmentity');
			    // Below is profit share data before entering expense against department and office		
			   $adb->pquery("INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid,
					 setype, description, createdtime, modifiedtime, presence, deleted, label)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
					array($current_id, $current_user->getId(), $ownerId, 'Jobexp', 'NULL', $date_var, $date_var, 1, 0, $_POST['name']));
				
				//INSERT data in jobexp module from job costing
				$jobexp_insert_query = "INSERT INTO vtiger_jobexp(jobexpid, name) VALUES(?,?)";			
				$params= array($current_id, $_POST['name']);			
				$adb->pquery($jobexp_insert_query, $params);			
				$jobexpid = $adb->getLastInsertID();
				
				$jobexpcf_insert_query = "INSERT INTO vtiger_jobexpcf(jobexpid, cf_1259, cf_1257) VALUES(?, ?, ?)";
				$params_jobexpcf = array($current_id, $_POST['cf_1435'], $_POST['cf_1433']);
				$adb->pquery($jobexpcf_insert_query, $params_jobexpcf);	
				$jobexpcfid = $adb->getLastInsertID();
				
				$crmentityrel_insert_query = "INSERT INTO vtiger_crmentityrel(crmid, module, relcrmid, relmodule) VALUES(?, ?, ?, ?)";
				$params_crmentityrel = array($_POST['sourceRecord'], 'Job', $jobexpid, 'Jobexp');
				$adb->pquery($crmentityrel_insert_query, $params_crmentityrel);
				$source_id = $jobexpcfid;
			}
			else{
				$source_id = $row["relcrmid"];
			}
			
			$current_id = $adb->getUniqueId('vtiger_crmentity');
			$adb->pquery("INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid,
					 setype, description, createdtime, modifiedtime, presence, deleted, label)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
					array($current_id, $current_user->getId(), $ownerId, 'Jobexpencereport', 'NULL', $date_var, $date_var, 1, 0, $_POST['name']));
				
			//INSERT data in jobexpencereport module from job costing
			$jobexpencereport_insert_query = "INSERT INTO vtiger_jobexpencereport(jobexpencereportid, name) VALUES(?,?)";			
			$params_jobexpencereport= array($current_id, $_POST['name']);			
			$adb->pquery($jobexpencereport_insert_query, $params_jobexpencereport);			
			$jobexpencereportid = $adb->getLastInsertID();
			//cf_1345 = vendor currency
			//cf_1222 = exchange rate
			//cf_1351 = Expected Buy (Local Currency NET)
			$jobexpencereportcf_insert_query = "INSERT INTO vtiger_jobexpencereportcf(jobexpencereportid, cf_1345, cf_1222, cf_1351, cf_1234, cf_1236, cf_1242) VALUES(?, ?, ?, ?, ?, ?, ?)";
			$params_jobexpencereportcf = array($current_id, $_POST['cf_1156'], $_POST['cf_1158'], $_POST['cf_1160'], $_POST['cf_1164'], $_POST['cf_1166'], $_POST['cf_1168']);
			$adb->pquery($jobexpencereportcf_insert_query, $params_jobexpencereportcf);
			$jobexpencereportcfid = $adb->getLastInsertID();
			
			$crmentityrel_insert_query = "INSERT INTO vtiger_crmentityrel(crmid, module, relcrmid, relmodule) VALUES(?, ?, ?, ?)";
			$params_crmentityrel = array($source_id, 'Jobexp', $jobexpencereportcfid, 'Jobexpencereport');
			$adb->pquery($crmentityrel_insert_query, $params_crmentityrel);
			
		}
		*/
		return $recordModel;
	}

	/**
	 * Function to get the record model based on the request parameters
	 * @param Vtiger_Request $request
	 * @return Vtiger_Record_Model or Module specific Record Model instance
	 */
	protected function getRecordModelFromRequest(Vtiger_Request $request) {

		$moduleName = $request->getModule();
		$recordId = $request->get('record');

		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

		if(!empty($recordId)) {
			$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
			$modelData = $recordModel->getData();
			$recordModel->set('id', $recordId);
			$recordModel->set('mode', 'edit');
		} else {
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$modelData = $recordModel->getData();
			$recordModel->set('mode', '');
		}

		$fieldModelList = $moduleModel->getFields();
		foreach ($fieldModelList as $fieldName => $fieldModel) {
			$fieldValue = $request->get($fieldName, null);
			$fieldDataType = $fieldModel->getFieldDataType();
			if($fieldDataType == 'time'){
				$fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
			}
			if($fieldValue !== null) {
				if(!is_array($fieldValue)) {
					$fieldValue = trim($fieldValue);
				}
				$recordModel->set($fieldName, $fieldValue);
			}
		}
		return $recordModel;
	}
}
