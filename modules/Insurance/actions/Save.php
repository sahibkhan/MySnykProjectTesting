<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Insurance_Save_Action extends Vtiger_Save_Action {

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
		
		$invoice_sum = $request->get('cf_2289');
		$transportat_cost_for_inv = $request->get('cf_2293');
		$other_charges = $request->get('cf_2297');
		$other_charges_a = $request->get('cf_2303');
		$other_charges_b = $request->get('cf_2307');
		
		$rate_id = $request->get('cf_2285');
		//$desc_of_goods_rate_list = Vtiger_InsuranceRateList_UIType::getDisplayValue($rate_id);
		$desc_of_goods_rate_list = Vtiger_Record_Model::getInstanceById($rate_id, 'InsuranceRate');
		
		$fp_rate = $desc_of_goods_rate_list->get('cf_2229');
		$centras_rate = $desc_of_goods_rate_list->get('cf_2231');
		$agent_rate = $desc_of_goods_rate_list->get('cf_2233');
		$globalink_rate = $desc_of_goods_rate_list->get('cf_2235');
		$discounted_glk_rate = $request->get('cf_2693');
		
		$total_insured_sum = $invoice_sum + $transportat_cost_for_inv + $other_charges + $other_charges_a + $other_charges_b;
		$fp_premium = ($fp_rate/100*$total_insured_sum);
		$centras_premium = ($centras_rate/100*$total_insured_sum);
		
		 if($discounted_glk_rate>0)
	  	{
			$globalink_premium = ($discounted_glk_rate/100*$total_insured_sum);
			$agent_rate = 0;	
		}
		else{
			$globalink_premium = ($globalink_rate/100*$total_insured_sum);
		}
		//$globalink_premium = round($globalink_premium,2);
		//Check globalink premium rate not less than
		$alto_date = $request->get('cf_2281');
		if(!empty($alto_date))
		{
			$alto_date = date('Y-m-d', strtotime($alto_date));
			$currency_code = 'USD';
		
			
			$exchrates = mysql_query('SELECT * FROM vtiger_exchangerate rate
									   INNER JOIN vtiger_exchangeratecf cf
									   ON rate.exchangerateid = cf.exchangerateid
									   where cf.cf_1108 = "'.$alto_date.'" and cf.cf_1106="'.$currency_code.'"
									  ');
			$exchrate = mysql_fetch_object($exchrates);
			$_exchrates = $exchrate->name;
			$check_premium = 11 * $_exchrates;
			if($globalink_premium < $check_premium)
			{
				$globalink_premium = $check_premium;
				$agent_rate = $desc_of_goods_rate_list->get('cf_2233');
			}
			
			$check_fp_premium = 10 * $_exchrates;
			if($fp_premium < $check_fp_premium)
			{
				$fp_premium = $check_fp_premium;				
			}
			
		}
		$globalink_premium = round($globalink_premium);
		$agent_premium = $globalink_premium - $centras_premium;
		//$agent_premium = ($agent_rate/100*$total_insured_sum);
		
		
		$request->set('cf_2313', $total_insured_sum);
		
		$request->set('cf_2299', $globalink_rate);
		$request->set('cf_2291', $fp_rate);
		$request->set('cf_2317', $agent_rate);
		$request->set('cf_2321', $centras_rate);
		
		$request->set('cf_2295', $globalink_premium);
		$request->set('cf_2315', $fp_premium);
		$request->set('cf_2319', $agent_premium);
		$request->set('cf_2323', $centras_premium);	
		
		$recordModel = $this->saveRecord($request);
		$insurance_id = $recordModel->getId();
		
		//Code for Insurance Expense
		//To Main JRER
		$recordId = $request->get('record');
		
		
		$adb = PearDatabase::getInstance();
		//cf_1453 = charge/service = 47007
		$query_count_job_jrer_buying = "SELECT * FROM vtiger_jobexpencereportcf
										INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereportcf.jobexpencereportid
										INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid
										WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid=? 
										AND crmentityrel.module='Job' AND crmentityrel.relmodule='Jobexpencereport'
										AND vtiger_jobexpencereportcf.cf_1453=? AND vtiger_jobexpencereportcf.cf_1457='Expence'
										 ";
		
		if(!empty($recordId)) 
		{
		$moduleName = $request->getModule();				
		//$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
		//$modelData = $recordModel->getData();	
		$adb_r = PearDatabase::getInstance();
		$query_rel = "select crmid from vtiger_crmentityrel where relcrmid =? AND relmodule='Insurance' AND module='Job'";
		$params_rel = array($recordId);	
		$result_rel = $adb_r->pquery($query_rel, $params_rel);
		$row_rel = $adb_r->fetch_array($result_rel);
		$job_id = $row_rel['crmid'];
		$query_count_job_jrer_buying .=' AND vtiger_jobexpencereportcf.cf_2325=?';
		$params_insurance_jrer = array($job_id, '85875', $insurance_id);
		}
		else{
		$job_id = $request->get('sourceRecord');
		$query_count_job_jrer_buying .=' AND vtiger_jobexpencereportcf.cf_2325=?';	
		$params_insurance_jrer = array($job_id, '85875', $insurance_id);
		}		
		
		$result_insurance_jrer = $adb->pquery($query_count_job_jrer_buying, $params_insurance_jrer);
				
		$count_job_jrer_buying = $adb->num_rows($result_insurance_jrer);
		
		$row_insurance_jrer = $adb->fetch_array($result_insurance_jrer);
		
		if($count_job_jrer_buying==0)
		{
			$job_id 			  = $request->get('sourceRecord');
			if(empty($job_id))
			{
				$query_rel = "select crmid from vtiger_crmentityrel where relcrmid =? AND relmodule='Insurance' AND module='Job'";
				$params_rel = array($insurance_id);	
				$result_rel = $adb_r->pquery($query_rel, $params_rel);
				$row_rel = $adb_r->fetch_array($result_rel);
				$job_id = $row_rel['crmid'];				
			}
		    $sourceModule_job 	= 'Job';	
	 	    $job_info_detail = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule_job);
			
			$insurance_expenses = array(
								  'b_job_charges_id' => '85875',
								  'b_invoice_date'   => date('Y-m-d'),//cf_1216
								  'b_due_date'       => date('Y-m-d'),//cf_1210
								  'b_vat_rate'       => 0,//cf_1339
								  'b_vat'		    => 0, //cf_1341
								  'b_buy_vendor_currency_net'   => $globalink_premium, //cf_1337
								  'b_buy_vendor_currency_gross' => $globalink_premium,  //cf_1343
								  'b_vendor_currency'	=> 1, //cf_1345
								  'b_exchange_rate'      => 1, //cf_1222
								  'b_buy_local_currency_gross' => $globalink_premium, //cf_1347
								  'b_buy_local_currency_net'   => $globalink_premium, //cf_1349
								  'b_variation_expected_actual_buying' => (0-$globalink_premium), //cf_1353
								  'b_type_id'    => '85785',  //cf_1214 KZ Bank R
								  'b_pay_to_id'  => '',     //cf_1367
								  'insurance_id' => $insurance_id, //cf_2325
								  'label'	    => 'Main JRER Insurance Expense',
								  'parentmodule' => 'Job'								  
								  );
			 					  
			$this->saveInsuranceExpense($recordModel, $request, $insurance_expenses, $job_info_detail);
			
			$insurance_selling = array(
								  's_job_charges_id' => '85875',
								  's_invoice_date'   => date('Y-m-d'), //cf_1355
								  's_selling_customer_currency_net' => $globalink_premium, //cf_1357
								  's_vat_rate'	=> 0, //cf_1228
								  's_vat'		 => 0, //cf_1230
								  's_sell_customer_currency_gross' => $globalink_premium, //cf_1232
								  's_customer_currency' => 1, //cf_1234
								  's_exchange_rate'     => 1, //cf_1236
								  's_sell_local_currency_gross' => $globalink_premium, //cf_1238
								  's_sell_local_currency_net'   => $globalink_premium, //cf_1240
								  's_expected_sell_local_currency_net' => 0, //cf_1242
								  's_variation_expected_and_actual_selling' => '', //cf_1244	 
								  's_variation_expect_and_actual_profit' => '', //cf_1246 
								  's_bill_to_id' => $request->get('cf_2239'),
								  'insurance_id' => $insurance_id, //cf_2325
								  'label'		=> 'Selling Insurance Expense',
								  'parentmodule' => 'Job',
								  );	
			$this->saveInsuranceSelling($recordModel, $request, $insurance_selling, $job_info_detail);			  			  								 
		}
		else{
			$adb = PearDatabase::getInstance();
			$query_insurance_expense_after = "UPDATE vtiger_jobexpencereportcf SET cf_1337=?, cf_1343=?, cf_1347=?, cf_1349=?, cf_1353=?, cf_2325=? WHERE jobexpencereportid=? AND cf_2325=?";
			//$variation_expected_and_actual_buying = $row_insurance_jrer['cf_1351'] - $globalink_premium;
			$variation_expected_and_actual_buying = 0;
			$check_params_after = array($globalink_premium, $globalink_premium, $globalink_premium, $globalink_premium, $variation_expected_and_actual_buying, $insurance_id, $row_insurance_jrer['jobexpencereportid'], $insurance_id);
			
			$result_insurance_expense_after = $adb->pquery($query_insurance_expense_after, $check_params_after);
			
			
			$adb_s = PearDatabase::getInstance();
			//cf_1453 = charge/service = 47007
			$query_count_job_jrer_selling = "SELECT * FROM vtiger_jobexpencereportcf
											INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereportcf.jobexpencereportid
											INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid
											WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid=? 
											AND crmentityrel.module='Job' AND crmentityrel.relmodule='Jobexpencereport'
											AND vtiger_jobexpencereportcf.cf_1455=? AND vtiger_jobexpencereportcf.cf_1457='Selling'
											 ";
			//$job_id = $request->get('sourceRecord');
			if(!empty($recordId)) 
			{
			$moduleName = $request->getModule();				
			//$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
			//$modelData = $recordModel->getData();	
			$adb_r = PearDatabase::getInstance();
			$query_rel = "select crmid from vtiger_crmentityrel where relcrmid =? AND relmodule='Insurance' AND module='Job'";
			$params_rel = array($recordId);	
			$result_rel = $adb_r->pquery($query_rel, $params_rel);
			$row_rel = $adb_r->fetch_array($result_rel);
			$job_id = $row_rel['crmid'];
			$query_count_job_jrer_selling .=' AND vtiger_jobexpencereportcf.cf_2325=?';
			$params_insurance_jrer_s = array($job_id, '85875', $insurance_id);
			}
			else{
			$job_id = $request->get('sourceRecord');	
			$query_count_job_jrer_selling .=' AND vtiger_jobexpencereportcf.cf_2325=?';
			$params_insurance_jrer_s = array($job_id, '85875', $insurance_id);
			}
			//$params_insurance_jrer_s = array($job_id, '85875');
			$result_insurance_jrer_s = $adb_s->pquery($query_count_job_jrer_selling, $params_insurance_jrer_s);
			$row_insurance_jrer_s = $adb_s->fetch_array($result_insurance_jrer_s);
			
			$adb_ss = PearDatabase::getInstance();
			$query_insurance_selling_after = "UPDATE vtiger_jobexpencereportcf SET cf_1355=?, cf_1357=?, cf_1232=?, cf_1238=?, cf_1240=?, cf_2325=?  WHERE jobexpencereportid=? AND cf_2325=?";
			
			$check_params_after_ss = array(date('Y-m-d'), $globalink_premium, $globalink_premium, $globalink_premium, $globalink_premium, $insurance_id, $row_insurance_jrer_s['jobexpencereportid'], $insurance_id);
			$result_insurance_selling_after = $adb_ss->pquery($query_insurance_selling_after, $check_params_after_ss);	
			
		}
		
		
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
		$loadUrl = $recordModel->getDetailViewUrl();
		
		header("Location: $loadUrl");
	}
	
	public function saveInsuranceSelling($recordModel, $request, $insurance_selling=array(), $job_info_detail)
	{
		$adb = PearDatabase::getInstance();
		$current_user = Users_Record_Model::getCurrentUserModel();
		$ownerId = $recordModel->get('assigned_user_id');
		$date_var = date("Y-m-d H:i:s");
		$usetime = $adb->formatDate($date_var, true);
		$job_insurance_id = $recordModel->getId();
		
		$current_id = $adb->getUniqueId('vtiger_crmentity');
		$source_id = $request->get('sourceRecord');
		if(empty($source_id))
		{
			$adb_r = PearDatabase::getInstance();
			$query_rel = "select crmid from vtiger_crmentityrel where relcrmid =? AND relmodule='Insurance' AND module='Job'";
			$params_rel = array($job_insurance_id);	
			$result_rel = $adb_r->pquery($query_rel, $params_rel);
			$row_rel = $adb_r->fetch_array($result_rel);
			$source_id = $row_rel['crmid'];				
		}
		
		//INSERT data in JRER expense module from job costing
		$adb->pquery("INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid,
			 setype, description, createdtime, modifiedtime, presence, deleted, label)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
			array($current_id, $job_info_detail->get('assigned_user_id'), $job_info_detail->get('assigned_user_id'), 'Jobexpencereport', 'NULL', $date_var, $date_var, 1, 0, $insurance_selling['label']));
		
		//INSERT data in jobexpencereport module from Fleet
		$adb_e = PearDatabase::getInstance();
		$jobexpencereport_insert_query = "INSERT INTO vtiger_jobexpencereport(jobexpencereportid, name, user_id, owner_id, job_id) VALUES(?,?,?,?,?)";
		$params_jobexpencereport= array($current_id, $recordModel->getId(), $job_info_detail->get('assigned_user_id'), $job_info_detail->get('assigned_user_id'), $source_id);
		print_r($params_jobexpencereport);			
		$adb_e->pquery($jobexpencereport_insert_query, $params_jobexpencereport);			
		$jobexpencereportid = $adb_e->getLastInsertID();
		
					
		//cf_1477 = Office
		//cf_1479 = Department
		//cf_1445 = bill_to
		//cf_1234 = selling currency
		//cf_1236 = exchange rate
		//cf_1242 = Expected Sell (Local Currency Net)
		$adb_ecf = PearDatabase::getInstance();
		$jobexpencereportcf_insert_query = "INSERT INTO vtiger_jobexpencereportcf(jobexpencereportid, cf_1479, cf_1477, cf_1455, cf_1242, cf_1457, cf_1355, cf_1357, cf_1228, cf_1230, cf_1232, cf_1234, cf_1236, cf_1238, cf_1240, cf_1244, cf_1246, cf_2325) 
											VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$params_jobexpencereportcf = array($current_id, $job_info_detail->get('cf_1190'), $job_info_detail->get('cf_1188'), $insurance_selling['s_job_charges_id'],  
										   $insurance_selling['s_expected_sell_local_currency_net'], 'Selling', 
										   $insurance_selling['s_invoice_date'], $insurance_selling['s_selling_customer_currency_net'],
										   $insurance_selling['s_vat_rate'], $insurance_selling['s_vat'], 
										   $insurance_selling['s_sell_customer_currency_gross'], $insurance_selling['s_customer_currency'],
										   $insurance_selling['s_exchange_rate'], $insurance_selling['s_sell_local_currency_gross'], 
										   $insurance_selling['s_sell_local_currency_net'], $insurance_selling['s_variation_expected_and_actual_selling'],
										   $insurance_selling['s_variation_expect_and_actual_profit'],
										   $insurance_selling['insurance_id']
										    );
								   
		$adb_ecf->pquery($jobexpencereportcf_insert_query, $params_jobexpencereportcf);
		$jobexpencereportcfid = $adb_ecf->getLastInsertID();
		
		$adb_rel = PearDatabase::getInstance();
		$crmentityrel_insert_query = "INSERT INTO vtiger_crmentityrel(crmid, module, relcrmid, relmodule) VALUES(?, ?, ?, ?)";
		$params_crmentityrel = array($source_id, 'Job', $jobexpencereportcfid, 'Jobexpencereport');
		$adb_rel->pquery($crmentityrel_insert_query, $params_crmentityrel);
		
		
	}
	
	public function saveInsuranceExpense($recordModel, $request, $insurance_expence=array(), $job_info_detail)
	{   
		
		$adb = PearDatabase::getInstance();
		$current_user = Users_Record_Model::getCurrentUserModel();
		$ownerId = $recordModel->get('assigned_user_id');
		$date_var = date("Y-m-d H:i:s");
		$usetime = $adb->formatDate($date_var, true);
		$job_insurance_id = $recordModel->getId();
		
		$current_id = $adb->getUniqueId('vtiger_crmentity');
		$source_id = $request->get('sourceRecord');
		if(empty($source_id))
		{
			$adb_r = PearDatabase::getInstance();
			$query_rel = "select crmid from vtiger_crmentityrel where relcrmid =? AND relmodule='Insurance' AND module='Job'";
			$params_rel = array($job_insurance_id);	
			$result_rel = $adb_r->pquery($query_rel, $params_rel);
			$row_rel = $adb_r->fetch_array($result_rel);
			$source_id = $row_rel['crmid'];				
		}
		
		
		//INSERT data in JRER expense module from job costing
		$adb->pquery("INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid,
			 setype, description, createdtime, modifiedtime, presence, deleted, label)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
			array($current_id, $job_info_detail->get('assigned_user_id'), $job_info_detail->get('assigned_user_id'), 'Jobexpencereport', 'NULL', $date_var, $date_var, 1, 0, $insurance_expence['label']));
		
		
		//INSERT data in jobexpencereport module from Fleet
		$adb_e = PearDatabase::getInstance();
		$jobexpencereport_insert_query = "INSERT INTO vtiger_jobexpencereport(jobexpencereportid, name, user_id, owner_id, job_id) VALUES(?,?,?,?,?)";
		$params_jobexpencereport= array($current_id, $recordModel->getId(), $job_info_detail->get('assigned_user_id'), $job_info_detail->get('assigned_user_id'), $source_id);			
		$adb_e->pquery($jobexpencereport_insert_query, $params_jobexpencereport);			
		$jobexpencereportid = $adb_e->getLastInsertID();
		
		//cf_1477 = Office
		//cf_1479 = Department
		//cf_1367 = pay_to
		//cf_1345 = vendor currency
		//cf_1222 = exchange rate
		//cf_1351 = Expected Buy (Local Currency NET)
		$adb_ecf = PearDatabase::getInstance();
		$jobexpencereportcf_insert_query = "INSERT INTO vtiger_jobexpencereportcf(jobexpencereportid, cf_1479, cf_1477, cf_1453, cf_1214, cf_1351, cf_1457, cf_1337, cf_1343, cf_1347, cf_1349, cf_1353, cf_1216, cf_1210, cf_1345, cf_1222, cf_1339, cf_1341, cf_1367, cf_2325) 
											VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$params_jobexpencereportcf = array($current_id, $job_info_detail->get('cf_1190'), $job_info_detail->get('cf_1188'), $insurance_expence['b_job_charges_id'], $insurance_expence['b_type_id'], 
										   $insurance_expence['b_expected_buy_local_currency_net'], 'Expence', $insurance_expence['b_buy_vendor_currency_net'], 
										   $insurance_expence['b_buy_vendor_currency_gross'], $insurance_expence['b_buy_local_currency_gross'], 
										   $insurance_expence['b_buy_local_currency_net'], $insurance_expence['b_variation_expected_actual_buying'], 
										   $insurance_expence['b_invoice_date'], $insurance_expence['b_due_date'], $insurance_expence['b_vendor_currency'], 
										   $insurance_expence['b_exchange_rate'], $insurance_expence['b_vat_rate'], $insurance_expence['b_vat'], 
										   $insurance_expence['b_pay_to_id'], $insurance_expence['insurance_id']);
		$adb_ecf->pquery($jobexpencereportcf_insert_query, $params_jobexpencereportcf);
		$jobexpencereportcfid = $adb_ecf->getLastInsertID();
		
		$adb_rel = PearDatabase::getInstance();
		$crmentityrel_insert_query = "INSERT INTO vtiger_crmentityrel(crmid, module, relcrmid, relmodule) VALUES(?, ?, ?, ?)";
		$params_crmentityrel = array($source_id, $insurance_expence['parentmodule'], $jobexpencereportcfid, 'Jobexpencereport');
		$adb_rel->pquery($crmentityrel_insert_query, $params_crmentityrel);
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
