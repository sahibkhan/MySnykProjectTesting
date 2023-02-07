<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Jobexpencereport_Save_Action extends Vtiger_Save_Action {

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
		
		$type = $request->get('cf_1457');
		$recordId = $request->get('record');
		$job_id = $this->get_job_id($recordId);
		
		if($type=='Expence')
		{
			$recordModel =  $this->buying_edit($request);
			
		}
		else{
			//$this->selling_edit($request);
			//$recordModel = $this->saveRecord($request);
			$recordModel =  $this->selling_edit($request);
		}
		
		//$recordModel = $this->saveRecord($request);
		if($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			$parentRecordId = $request->get('sourceRecord');
			$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentRecordId, $parentModuleName);
			//TODO : Url should load the related list instead of detail view of record
			$loadUrl = $parentRecordModel->getDetailViewUrl();
		} else if ($request->get('returnToList')) {
			$loadUrl = $recordModel->getModule()->getListViewUrl();
		} else {
			
			//$loadUrl = $recordModel->getDetailViewUrl();
			$loadUrl = 'index.php?module=Job&relatedModule='.$request->get('module').
				'&view=Detail&record='.$job_id.'&mode=showRelatedList&tab_label=Expences';
		}
		
		header("Location: $loadUrl");
	}
	
	public function selling_edit($request)
	{
		$recordId = $request->get('record');
		$job_id = $this->get_job_id($recordId);
		
		$sourceModule = 'Job';	
		$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule);
				
		$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info->get('cf_1186'));
		$file_title_currency = $reporting_currency;
		include("include/Exchangerate/exchange_rate_class.php");
		
		$assigned_extra_selling = array();
		$s_generate_invoice_instruction_flag = true;
		$invoice_instruction = '';
		
		$current_user = Users_Record_Model::getCurrentUserModel();
		$current_user_office_id = $current_user->get('location_id');
		
		$s_job_charges_id = $request->get('cf_1455');
		if($job_info->get('assigned_user_id')!=$current_user->getId())
		{		
			$s_office_id	  = $current_user->get('location_id');
			$s_department_id  = $current_user->get('department_id');
		}else{
			$s_office_id	  = $request->get('cf_1477');
			$s_department_id  = $request->get('cf_1479');
		}
		$s_bill_to_id 		  = $request->get('cf_1445');
		$s_bill_to_address 	  = $request->get('cf_1359');
		$s_ship_to 			  = $request->get('cf_1361');
		$s_ship_to_address 	  = $request->get('cf_1363');
		$s_remarks 			  = $request->get('cf_1365');
		$s_invoice_date 	  = $request->get('cf_1355');
		$s_selling_customer_currency_net = $request->get('cf_1357');
		$s_vat_rate 		  = $request->get('cf_1228');
		
		$s_vat = '0.00';
		if(!empty($s_vat_rate) && $s_vat_rate>0)
		{
			$s_vat_rate_cal = $s_vat_rate/100; 
			$s_vat = 	$s_selling_customer_currency_net * $s_vat_rate_cal;
		}
		
		$s_sell_customer_currency_gross = $s_selling_customer_currency_net + $s_vat;
		
		$s_customer_currency = $request->get('cf_1234');
		
		//$job_id 			  = $request->get('sourceRecord');
		//$sourceModule 		  = $request->get('sourceModule');	
		//$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule);
		
		$job_office_id = $job_info->get('cf_1188');
		$job_department_id = $job_info->get('cf_1190');
		
		$s_invoice_date_format = date('Y-m-d', strtotime($s_invoice_date));
		$s_customer_currency_code = Vtiger_CurrencyList_UIType::getDisplayValue($s_customer_currency);
		
		if($file_title_currency =='KZT')
		{			
			$s_exchange_rate  		= exchange_rate_currency($s_invoice_date_format, $s_customer_currency_code);
		}
		elseif($file_title_currency =='USD')
		{
			$s_exchange_rate = currency_rate_convert($s_customer_currency_code, $file_title_currency, 1, $s_invoice_date_format);
		}
		else{			
			$s_exchange_rate = currency_rate_convert_others($s_customer_currency_code, $file_title_currency, 1, $s_invoice_date_format);
		}
		
		if($file_title_currency !='USD')
		{	
			$s_sell_local_currency_gross = $s_sell_customer_currency_gross * $s_exchange_rate;
		}else{
			$s_sell_local_currency_gross = exchange_rate_convert($s_customer_currency_code, $file_title_currency, $s_sell_customer_currency_gross, $s_invoice_date_format);
		}
		
		if($file_title_currency->currency !='USD')
		{	
			$s_sell_local_currency_net = $s_selling_customer_currency_net * $s_exchange_rate;
		}else{
			$s_sell_local_currency_net = exchange_rate_convert($s_customer_currency_code, $file_title_currency,$s_selling_customer_currency_net, $s_invoice_date_format);
		}
		
		$s_expected_sell_local_currency_net = $request->get('cf_1242');				
		$ss_expected_sell_local_currency_net = $request->get('cf_1242');
		
		//$s_variation_expected_and_actual_sellling  = $s_sell_local_currency_net - $s_expected_sell_local_currency_net;
		//$ss_variation_expected_and_actual_sellling = $s_sell_local_currency_net - $ss_expected_sell_local_currency_net;
		
		$job_jrer_selling = array();
		if($recordId!=0)
		{
			$db_selling = PearDatabase::getInstance();						
			$jrer_selling = 'SELECT * FROM vtiger_jobexpencereport
						  			   INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid	
						 			   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid
						 			   where vtiger_crmentityrel.crmid=? and vtiger_jobexpencereport.owner_id=? and vtiger_jobexpencereport.jobexpencereportid=? 
						 	   		   and vtiger_jobexpencereportcf.cf_1457="Selling" ORDER BY vtiger_jobexpencereportcf.cf_1477, vtiger_jobexpencereportcf.cf_1479 ASC limit 1';
			//jobid as record_id						   
			$params_selling = array(@$job_info->get('record_id'), $current_user->getId(), $recordId);
			
			$result_selling = $db_selling->pquery($jrer_selling, $params_selling);
			$job_jrer_selling = $db_selling->fetch_array($result_selling);
			
			$job_costing_id = @$job_jrer_selling['jerid'];
			if(!empty($job_costing_id))
			{
													   
			$db_costing_1 = PearDatabase::getInstance();		
			$query_costing = 'SELECT * FROM vtiger_jer
								   INNER JOIN vtiger_jercf ON vtiger_jercf.jerid = vtiger_jer.jerid	
								   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jer.jerid
								   where vtiger_crmentityrel.crmid=? and vtiger_jer.jerid=? AND vtiger_jercf.cf_1451=? AND vtiger_jercf.cf_1433=? AND vtiger_jercf.cf_1435=?';
			$params_costing_1 = array(@$job_info->get('record_id'), $job_costing_id, $s_job_charges_id, $s_office_id, $s_department_id);
			
			$result_costing_1 = $db_costing_1->pquery($query_costing, $params_costing_1);
			$sell_of_local_currency = $db_costing_1->fetch_array($result_costing_1);  	
			//sell_local_currency
			$s_expected_sell_local_currency_net = @$sell_of_local_currency['cf_1168'];
			}
		}
		
		$s_variation_expected_and_actual_sellling = $s_sell_local_currency_net - $s_expected_sell_local_currency_net;
		$ss_variation_expected_and_actual_sellling = $s_sell_local_currency_net - $ss_expected_sell_local_currency_net;
		
		if($job_info->get('assigned_user_id')!=$current_user->getId())
		{			
			$db_buying_exp = PearDatabase::getInstance();
			$jrer_buying_exp = 'SELECT * FROM vtiger_jobexpencereport
								   INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid	
								   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid
								   where vtiger_crmentityrel.crmid=? and vtiger_jobexpencereportcf.cf_1455=? and vtiger_jobexpencereportcf.cf_1477=? 
								   and vtiger_jobexpencereportcf.cf_1479=?  and vtiger_jobexpencereport.owner_id =? and vtiger_jobexpencereport.selling_expense="no"
								   and vtiger_jobexpencereportcf.cf_1457="Expence" limit 1';
			$params_buying_exp = array($job_id, 
									   $s_job_charges_id, 
									   $s_office_id, 
									   $s_department_id,
									   $current_user->getId()								  
									  );
			$result_buying_exp = $db_buying_exp->pquery($jrer_buying_exp,$params_buying_exp);
			$b_variation_expected_and_actual_buying_arr = $db_buying_exp->fetch_array($result_buying_exp);	
		}
		else{			
			$db_buying_exp = PearDatabase::getInstance();
			$jrer_buying_exp = 'SELECT * FROM vtiger_jobexpencereport
								   INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid	
								   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid
								   where vtiger_crmentityrel.crmid=? and vtiger_jobexpencereportcf.cf_1455=? and vtiger_jobexpencereportcf.cf_1477=? 
								   and vtiger_jobexpencereportcf.cf_1479=?  and vtiger_jobexpencereport.owner_id =? and vtiger_jobexpencereport.user_id =?
								   and vtiger_jobexpencereportcf.cf_1457="Expence" limit 1';
			$params_buying_exp = array($job_id, 
									   $s_job_charges_id, 
									   $s_office_id, 
									   $s_department_id,
									   $current_user->getId(),
									   $current_user->getId(),
									  );
									  
			$result_buying_exp = $db_buying_exp->pquery($jrer_buying_exp,$params_buying_exp);
			$b_variation_expected_and_actual_buying_arr = $db_buying_exp->fetch_array($result_buying_exp);	
		}
		
		$s_variation_expect_and_actual_profit = $s_variation_expected_and_actual_sellling + @$b_variation_expected_and_actual_buying_arr['cf_1353'];				
		$ss_variation_expect_and_actual_profit = $ss_variation_expected_and_actual_sellling + @$b_variation_expected_and_actual_buying_arr['cf_1353'];
		
		$db_revenue = PearDatabase::getInstance();
		$query_revenue = 'SELECT * FROM vtiger_chartofaccount where name=?';
		$params_revenue = array('Revenue');
		$result_revenue = $db_revenue->pquery($query_revenue,$params_revenue);
		$gl_job_charges_id = $db_revenue->fetch_array($result_revenue);
		
		$db_coa = PearDatabase::getInstance();
		$query_coa = 'SELECT * FROM vtiger_companyaccount
					  INNER JOIN vtiger_companyaccountcf ON vtiger_companyaccountcf.companyaccountid = vtiger_companyaccount.companyaccountid
					  INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_companyaccount.companyaccountid
					  where vtiger_crmentityrel.crmid=? and vtiger_companyaccountcf.cf_1501=?';
		$params_coa = array($job_info->get('cf_1186'), $gl_job_charges_id['chartofaccountid']);
		$result_coa = $db_coa->pquery($query_coa, $params_coa);
		$coa_info = $db_coa->fetch_array($result_coa);
		
		if($job_office_id==$current_user_office_id){
			$file_title_id = $job_info->get('cf_1186');
		}
		else{
			$file_title_id = $current_user->get('company_id');
		}
		
		//Revenue
		if($job_info->get('assigned_user_id')!=$current_user->getId())
		{
			$office = Vtiger_LocationList_UIType::getDisplayValue($current_user->get('location_id'));
			$department = Vtiger_DepartmentList_UIType::getDepartment($current_user->get('department_id'));
		}
		else{			
			$office = Vtiger_LocationList_UIType::getDisplayValue($job_office_id);
			$department = Vtiger_DepartmentList_UIType::getDepartment($job_department_id);
		}		
		$gl_account = $coa_info['name'].'-'.$office.'-'.$department;
		
		$db_ar = PearDatabase::getInstance();
		$query_ar = 'SELECT * FROM vtiger_chartofaccount where name=?';
		$params_ar = array('Trade Debtors');
		$result_ar = $db_ar->pquery($query_ar,$params_ar);
		$ar_job_charges_id = $db_ar->fetch_array($result_ar);
		
		if($job_info->get('assigned_user_id')!=$current_user->getId() && $job_office_id!=$current_user->get('location_id'))
		{
			$db_coa = PearDatabase::getInstance();
			$query_coa = 'SELECT * FROM vtiger_companyaccount
						  INNER JOIN vtiger_companyaccountcf ON vtiger_companyaccountcf.companyaccountid = vtiger_companyaccount.companyaccountid
						  INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_companyaccount.companyaccountid
						  where vtiger_crmentityrel.crmid=? and vtiger_companyaccountcf.cf_1501=?';
			$params_coa = array($file_title_id, $ar_job_charges_id['chartofaccountid']);
			$result_coa = $db_coa->pquery($query_coa, $params_coa);
			$ar_coa_info = $db_coa->fetch_array($result_coa);
		}
		else{
			$db_coa = PearDatabase::getInstance();
			$query_coa = 'SELECT * FROM vtiger_companyaccount
						  INNER JOIN vtiger_companyaccountcf ON vtiger_companyaccountcf.companyaccountid = vtiger_companyaccount.companyaccountid
						  INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_companyaccount.companyaccountid
						  where vtiger_crmentityrel.crmid=? and vtiger_companyaccountcf.cf_1501=?';
			$params_coa = array($file_title_id, $ar_job_charges_id['chartofaccountid']);
			$result_coa = $db_coa->pquery($query_coa, $params_coa);
			$ar_coa_info = $db_coa->fetch_array($result_coa);
		}					
		$s_ar_gl_account = $ar_coa_info['name'].'-'.$office.'-'.$department;
		
		$extra_selling['s_generate_invoice_instruction'] = 0;
		$extra_selling_normal['s_generate_invoice_instruction'] = 0;
		
		if($job_info->get('assigned_user_id')!=$current_user->getId())
		{
			
			if($job_office_id==$current_user_office_id){
				$file_title_id = $job_info->get('cf_1186');
			}
			else{
				$file_title_id = $current_user->get('company_id');
				//$file_title_id = $this->input->post('file_title');
			}
			$assigned_file_title_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency($file_title_id);
			
			$ss_customer_currency_code = Vtiger_CurrencyList_UIType::getDisplayValue($s_customer_currency);
			if($assigned_file_title_currency == 'KZT')
			{						
				$ss_exchange_rate  		= exchange_rate_currency($s_invoice_date_format, $ss_customer_currency_code);					
			}
			elseif($assigned_file_title_currency =='USD')
			{
				$ss_exchange_rate = currency_rate_convert($ss_customer_currency_code, $assigned_file_title_currency, 1, $s_invoice_date_format);
			}
			else{
				$ss_exchange_rate = currency_rate_convert_others($ss_customer_currency_code, $assigned_file_title_currency, 1, $s_invoice_date_format);
			}
						
			if($assigned_file_title_currency !='USD')
			{						
				$ss_sell_local_currency_gross = $s_sell_customer_currency_gross * $ss_exchange_rate;
			}else{
				$ss_sell_local_currency_gross = exchange_rate_convert($ss_customer_currency_code, $assigned_file_title_currency,$s_sell_customer_currency_gross,$s_invoice_date_format);			
			}
			
			if($assigned_file_title_currency !='USD')
			{	
				$ss_sell_local_currency_net = $s_selling_customer_currency_net * $ss_exchange_rate;
			}else{
				$ss_sell_local_currency_net = exchange_rate_convert($ss_customer_currency_code, $assigned_file_title_currency, $s_selling_customer_currency_net, $s_invoice_date_format);
			}
			
			$extra_selling = array(
										//'user_id' => $this->current_user->id,
										'job_id'  		   => $job_id,
										'date_added'	   => date('Y-m-d H:i:s'),
										's_job_charges_id' => $s_job_charges_id,
										's_department_id'  => $s_department_id,
										's_office_id' 	   => $s_office_id,
										's_customer_id'    => $s_bill_to_id,
										's_bill_to_id'     => $s_bill_to_id,
										's_bill_to_address'=> $s_bill_to_address,
										's_ship_to'  	   => $s_ship_to,
										's_ship_to_address'=> $s_ship_to_address,
										's_remarks'   	   => $s_remarks,
										's_invoice_date'   => $s_invoice_date,
										's_selling_customer_currency_net' => $s_selling_customer_currency_net,
										's_vat_rate'  => $s_vat_rate,
										's_vat'       => $s_vat,
										's_sell_customer_currency_gross' => $s_sell_customer_currency_gross,
										's_customer_currency'           => $s_customer_currency,
										's_exchange_rate'           => $ss_exchange_rate,
										's_sell_local_currency_gross' => $ss_sell_local_currency_gross,
										's_sell_local_currency_net'      => $ss_sell_local_currency_net,
										's_expected_sell_local_currency_net' => (isset($ss_expected_sell_local_currency_net) ? $ss_expected_sell_local_currency_net : '0.00'),
										's_variation_expected_and_actual_sellling' => (isset($ss_variation_expected_and_actual_sellling) ? $ss_variation_expected_and_actual_sellling :'0.00'),
										's_variation_expect_and_actual_profit' => $ss_variation_expect_and_actual_profit,
										//'s_generate_invoice_instruction' => ((isset($s_generate_invoice_instruction) && $s_generate_invoice_instruction==1)  ? 1 : 0 ),
										'gl_account'	=> $gl_account,
										'ar_gl_account' => $s_ar_gl_account,
									  );
							
		
		}
		
		$extra_selling_normal = array(
										//'user_id' => $this->current_user->id,
										'job_id'  		   => $job_id,
										'date_added'	   => date('Y-m-d H:i:s'),
										's_job_charges_id' => $s_job_charges_id,
										's_department_id'  => $s_department_id,
										's_office_id' => $s_office_id,
										's_customer_id' => $s_bill_to_id,
										's_bill_to_id' => $s_bill_to_id,
										's_bill_to_address' => $s_bill_to_address,
										's_ship_to'  => $s_ship_to,
										's_ship_to_address' => $s_ship_to_address,
										's_remarks'   => $s_remarks,
										's_invoice_date' => $s_invoice_date,
										's_selling_customer_currency_net' => $s_selling_customer_currency_net,
										's_vat_rate'  => $s_vat_rate,
										's_vat'       => $s_vat,
										's_sell_customer_currency_gross' => $s_sell_customer_currency_gross,
										's_customer_currency'            => $s_customer_currency,
										's_exchange_rate'           	 => $s_exchange_rate,
										's_sell_local_currency_gross' 	 => $s_sell_local_currency_gross,
										's_sell_local_currency_net'      => $s_sell_local_currency_net,
										's_expected_sell_local_currency_net' => (isset($s_expected_sell_local_currency_net) ? $s_expected_sell_local_currency_net : '0.00'),
										's_variation_expected_and_actual_sellling' => (isset($s_variation_expected_and_actual_sellling) ? $s_variation_expected_and_actual_sellling :'0.00'),
										's_variation_expect_and_actual_profit' => $s_variation_expect_and_actual_profit,
										//'s_generate_invoice_instruction' => ((isset($s_generate_invoice_instruction) && $s_generate_invoice_instruction==1)  ? 1 : 0 ),
										'gl_account'	=> $gl_account,
										'ar_gl_account' => $s_ar_gl_account,
									  );
			
		if($job_info->get('assigned_user_id')!=$current_user->getId())
		{		
			$extra_selling['company_id'] = $file_title_id;
			//$extra_selling['s_jrer_buying_id'] = 0;
						
			$request->set('cf_1455', $extra_selling['s_job_charges_id']);
			$request->set('cf_1477', $extra_selling['s_office_id']);
			$request->set('cf_1479', $extra_selling['s_department_id']);
			$request->set('cf_1445', $extra_selling['s_bill_to_id']);
			$request->set('cf_1359', $extra_selling['s_bill_to_address']);
			$request->set('cf_1361', $extra_selling['s_ship_to']);
			$request->set('cf_1363', $extra_selling['s_ship_to_address']);
			$request->set('cf_1365', $extra_selling['s_remarks']);
			$request->set('cf_1355', $extra_selling['s_invoice_date']);			
			$request->set('cf_1357', $extra_selling['s_selling_customer_currency_net']);
			$request->set('cf_1228', $extra_selling['s_vat_rate']);
			$request->set('cf_1230', $extra_selling['s_vat']);
			$request->set('cf_1232', $extra_selling['s_sell_customer_currency_gross']);
			$request->set('cf_1234', $extra_selling['s_customer_currency']);
			$request->set('cf_1236', $extra_selling['s_exchange_rate']);
			$request->set('cf_1238', $extra_selling['s_sell_local_currency_gross']);
			$request->set('cf_1240', $extra_selling['s_sell_local_currency_net']);
			$request->set('cf_1242', $extra_selling['s_expected_sell_local_currency_net']);
			$request->set('cf_1244', $extra_selling['s_variation_expected_and_actual_sellling']);
			$request->set('cf_1246', $extra_selling['s_variation_expect_and_actual_profit']);
			
			$request->set('gl_account', $extra_selling['gl_account']);
			$request->set('ar_gl_account', $extra_selling['ar_gl_account']);
			//$request->set('company_id', $extra_selling['company_id']);
			//$request->set('user_id', $extra_selling['user_id']);
			//$request->set('owner_id', $extra_selling['owner_id']);
			//$request->set('s_jrer_buying_id', $extra_selling['s_jrer_buying_id']);
			
			$recordModel = $this->saveRecord($request);
			$jrer_selling_id_assigned = $recordModel->getId();
			
		}
		else{
			$extra_selling_normal['company_id'] = $file_title_id;	
			$extra_selling_normal['user_id'] = $current_user->getId();
			$extra_selling_normal['owner_id'] = $current_user->getId();
			
			$request->set('cf_1455', $extra_selling_normal['s_job_charges_id']);
			$request->set('cf_1477', $extra_selling_normal['s_office_id']);
			$request->set('cf_1479', $extra_selling_normal['s_department_id']);
			$request->set('cf_1445', $extra_selling_normal['s_bill_to_id']);
			$request->set('cf_1359', $extra_selling_normal['s_bill_to_address']);
			$request->set('cf_1361', $extra_selling_normal['s_ship_to']);
			$request->set('cf_1363', $extra_selling_normal['s_ship_to_address']);
			$request->set('cf_1365', $extra_selling_normal['s_remarks']);
			$request->set('cf_1355', $extra_selling_normal['s_invoice_date']);			
			$request->set('cf_1357', $extra_selling_normal['s_selling_customer_currency_net']);
			$request->set('cf_1228', $extra_selling_normal['s_vat_rate']);
			$request->set('cf_1230', $extra_selling_normal['s_vat']);
			$request->set('cf_1232', $extra_selling_normal['s_sell_customer_currency_gross']);
			$request->set('cf_1234', $extra_selling_normal['s_customer_currency']);
			$request->set('cf_1236', $extra_selling_normal['s_exchange_rate']);
			$request->set('cf_1238', $extra_selling_normal['s_sell_local_currency_gross']);
			$request->set('cf_1240', $extra_selling_normal['s_sell_local_currency_net']);
			$request->set('cf_1242', $extra_selling_normal['s_expected_sell_local_currency_net']);
			$request->set('cf_1244', $extra_selling_normal['s_variation_expected_and_actual_sellling']);
			$request->set('cf_1246', $extra_selling_normal['s_variation_expect_and_actual_profit']);
			
			$request->set('gl_account', $extra_selling_normal['gl_account']);
			$request->set('ar_gl_account', $extra_selling_normal['ar_gl_account']);
			$request->set('company_id', $extra_selling_normal['company_id']);
			$request->set('user_id', $extra_selling_normal['user_id']);
			$request->set('owner_id', $extra_selling_normal['owner_id']);
			///$request->set('s_jrer_buying_id', $extra_selling_normal['s_jrer_buying_id']);
				
			$recordModel = $this->saveRecord($request);
			$jobexpencereportid = $recordModel->getId();
			
			$db_up = PearDatabase::getInstance();
			$query_up = 'UPDATE vtiger_jobexpencereport set job_id=?, gl_account=?, ar_gl_account=?, company_id=?, user_id=?, owner_id=? where jobexpencereportid=?';
			$params_up = array($job_id, $extra_selling_normal['b_gl_account'], $extra_selling_normal['b_ar_gl_account'], $extra_selling_normal['company_id'], $extra_selling_normal['user_id'], $extra_selling_normal['owner_id'], $jobexpencereportid);
			$result_up = $db_up->pquery($query_up, $params_up);	
		}	
		
		if($job_info->get('assigned_user_id')!=$current_user->getId())
		{		
			$assigned_extra_selling[] = $extra_selling_normal;
		}
		
		if(!empty($assigned_extra_selling))
			{
				foreach($assigned_extra_selling as $selling)
				{
					$db_customer = PearDatabase::getInstance();				
					$query_pay_to_info = 'SELECT * FROM vtiger_accountscf where cf_1703=?';
					$params_pay_to_info = array($file_title_id);
					$result_pay_to_info = $db_customer->pquery($query_pay_to_info,$params_pay_to_info);
					$pay_to_info = $db_customer->fetch_array($result_pay_to_info);	
					
					if($job_info->get('assigned_user_id')!=$current_user->getId())
					{	
						//$b_type_id = $post->file_title.'-bank';
						$db_company_type = PearDatabase::getInstance();
						$query_company_type = 'select * from vtiger_CompanyAccountType 
												INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_companyaccounttype.companyaccounttypeid
												where vtiger_crmentityrel.crmid=40854 and vtiger_CompanyAccountType.name like "Bank%"';
						$params_company_type = array($job_info->get('cf_1186'));
						$result_company_type = $db_company_type->pquery($query_company_type, $params_company_type);
						$company_type_info = $db_company_type->fetch_array($result_company_type);
						$b_type_id = $company_type_info['companyaccounttypeid'];
						// need to discuss
					}	
					
					$db_assigned_jrer_buying_count = PearDatabase::getInstance();
					$assigned_jrer_buying_count = 'SELECT * FROM vtiger_jobexpencereport
										   INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid	
										   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid
										   where vtiger_crmentityrel.crmid=? and vtiger_jobexpencereportcf.cf_1455=? and vtiger_jobexpencereportcf.cf_1477=? 
										   and vtiger_jobexpencereportcf.cf_1479=? and vtiger_jobexpencereport.selling_expense="yes" 
										   and vtiger_jobexpencereportcf.cf_1457="Expence" ';
					$params_assigned_jrer_buying_count = array($selling['job_id'], 
										  $selling['s_job_charges_id'], 
										  $selling['s_office_id'], 
										  $selling['s_department_id'],								      
										  );
					$result_assigned_jrer_buying_count = $db_assigned_jrer_buying_count->pquery($jrer_assigned_jrer_buying_count,$params_assigned_jrer_buying_count);
					
					$assigned_extra_buying_cost = array(
														'date_added'	   => date('Y-m-d H:i:s'),
														'user_id'		  => $current_user->getId(),
														'job_id'  		   => $selling['job_id'],
														'date_added'	   => date('Y-m-d H:i:s'),
														'b_job_charges_id' => $selling['s_job_charges_id'],
														'b_office_id' 	  => $selling['s_office_id'],
														'b_department_id'  => $selling['s_department_id'],															
														'b_pay_to_id' 	  => $pay_to_info->id,														
														'b_invoice_date'   => $selling['s_invoice_date'],
														'b_invoice_due_date'=> date('Y-m-d'),
														'company_id' 	   => $job_info->get('cf_1186'),
														'owner_id' 		 => $job_info->get('assigned_user_id'),													
														'b_buy_vendor_currency_net' => $selling['s_selling_customer_currency_net'],
														'b_vat_rate'       => $selling['s_vat_rate'],
														'b_vat'            => $selling['s_vat'],
														'b_buy_vendor_currency_gross' => $selling['s_sell_customer_currency_gross'],
														'b_vendor_currency'    => $selling['s_customer_currency'],
														'b_exchange_rate'      => $selling['s_exchange_rate'],
														'b_buy_local_currency_gross' => $selling['s_sell_local_currency_gross'],
														'b_buy_local_currency_net'   => $selling['s_sell_local_currency_net'],
														'b_expected_buy_local_currency_net' => $selling['s_expected_sell_local_currency_net'],
														'b_variation_expected_and_actual_buying' => $selling['s_variation_expected_and_actual_sellling'],														
														'b_send_to_head_of_department_for_approval' => 0,
														'b_send_to_payables_and_generate_payment_voucher' => 0,
														'selling_expense' => 'yes'
														);
						
						if($db_assigned_jrer_buying_count->num_rows($result_assigned_jrer_buying_count)==0)
						{
							$assigned_extra_buying_cost['b_type_id'] 		= $b_type_id;
							$assigned_extra_buying_cost['b_gl_account']		= $selling['gl_account'];
							$assigned_extra_buying_cost['b_ar_gl_account']	= $selling['ar_gl_account'];
							//$this->db->insert('job_jrer_buying',$assigned_extra_buying_cost);
							
							$request->set('cf_1453', $assigned_extra_buying_cost['b_job_charges_id']);
							$request->set('cf_1477', $assigned_extra_buying_cost['b_office_id']);
							$request->set('cf_1479', $assigned_extra_buying_cost['b_department_id']);
							$request->set('cf_1367', $assigned_extra_buying_cost['b_pay_to_id']);
							//$request->set('cf_1212', $assigned_extra_buying_cost['b_invoice_number']);
							$request->set('cf_1216', $assigned_extra_buying_cost['b_invoice_date']);
							$request->set('cf_1210', $assigned_extra_buying_cost['b_invoice_due_date']);
							$request->set('cf_1214', $assigned_extra_buying_cost['b_type_id']);
							$request->set('cf_1337', $assigned_extra_buying_cost['b_buy_vendor_currency_net']);			
							$request->set('cf_1339', $assigned_extra_buying_cost['b_vat_rate']);
							$request->set('cf_1341', $assigned_extra_buying_cost['b_vat']);
							$request->set('cf_1343', $assigned_extra_buying_cost['b_buy_vendor_currency_gross']);
							$request->set('cf_1345', $assigned_extra_buying_cost['b_vendor_currency']);
							$request->set('cf_1222', $assigned_extra_buying_cost['b_exchange_rate']);
							$request->set('cf_1347', $assigned_extra_buying_cost['b_buy_local_currency_gross']);
							$request->set('cf_1349', $assigned_extra_buying_cost['b_buy_local_currency_net']);
							$request->set('cf_1351', $assigned_extra_buying_cost['b_expected_buy_local_currency_net']);
							$request->set('cf_1353', $assigned_extra_buying_cost['b_variation_expected_and_actual_buying']);			
							$request->set('cf_1457', 'Expence');
							
							$request->set('record','');
							$recordModel->set('mode', '');
						
							$recordModel = $this->saveRecord($request);
							$jobexpencereportid = $recordModel->getId();
							
							$db_up = PearDatabase::getInstance();
							$query_up = 'UPDATE vtiger_jobexpencereport set job_id=?, b_gl_account=?, b_ar_gl_account=?, company_id=?, user_id=?, owner_id=?, selling_expense=?,  where jobexpencereportid=?';
							$params_up = array($assigned_extra_buying_cost['job_id'], $assigned_extra_buying_cost['b_gl_account'], $assigned_extra_buying_cost['b_ar_gl_account'], $assigned_extra_buying_cost['company_id'], $assigned_extra_buying_cost['user_id'], $assigned_extra_buying_cost['owner_id'], $assigned_extra_buying_cost['selling_expense'], $jobexpencereportid);
							$result_up = $db_up->pquery($query_up, $params_up);		
						}
						else{
						$assigned_jrer_buying_info = $db_assigned_jrer_buying_count->fetch_array($result_assigned_jrer_buying_count);
						$request->set('id',$assigned_jrer_buying_info['jobexpencereportid']);
						$request->set('record',$assigned_jrer_buying_info['jobexpencereportid']);
						$recordModel->set('mode', 'edit');						
						
						$request->set('cf_1453', $assigned_extra_buying_cost['b_job_charges_id']);
						$request->set('cf_1477', $assigned_extra_buying_cost['b_office_id']);
						$request->set('cf_1479', $assigned_extra_buying_cost['b_department_id']);
						$request->set('cf_1367', $assigned_extra_buying_cost['b_pay_to_id']);
						//$request->set('cf_1212', $assigned_extra_buying_cost['b_invoice_number']);
						$request->set('cf_1216', $assigned_extra_buying_cost['b_invoice_date']);
						$request->set('cf_1210', $assigned_extra_buying_cost['b_invoice_due_date']);
						$request->set('cf_1214', $assigned_extra_buying_cost['b_type_id']);
						$request->set('cf_1337', $assigned_extra_buying_cost['b_buy_vendor_currency_net']);			
						$request->set('cf_1339', $assigned_extra_buying_cost['b_vat_rate']);
						$request->set('cf_1341', $assigned_extra_buying_cost['b_vat']);
						$request->set('cf_1343', $assigned_extra_buying_cost['b_buy_vendor_currency_gross']);
						$request->set('cf_1345', $assigned_extra_buying_cost['b_vendor_currency']);
						$request->set('cf_1222', $assigned_extra_buying_cost['b_exchange_rate']);
						$request->set('cf_1347', $assigned_extra_buying_cost['b_buy_local_currency_gross']);
						$request->set('cf_1349', $assigned_extra_buying_cost['b_buy_local_currency_net']);
						$request->set('cf_1351', $assigned_extra_buying_cost['b_expected_buy_local_currency_net']);
						$request->set('cf_1353', $assigned_extra_buying_cost['b_variation_expected_and_actual_buying']);			
						$request->set('cf_1457', 'Expence');
						
						$recordModel = $this->saveRecord($request);
						$jobexpencereportid = $recordModel->getId();
						}
					
					
				}
			}
		
		
	}
	
	public function buying_edit($request)
	{
		$recordId = $request->get('record');
		
		$job_id = $this->get_job_id($recordId);
		
		$sourceModule = 'Job';	
		$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule);
		
		
		$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info->get('cf_1186'));
		$file_title_currency = $reporting_currency;
		
		include("include/Exchangerate/exchange_rate_class.php");
		
		$extra_expense = array();			
		$assigned_extra_expense = array();		
		$assigned_extra_selling = array();
		
		$current_user = Users_Record_Model::getCurrentUserModel();
		$current_user_office_id = $current_user->get('location_id');
		
		$job_jrer_buying = array();
		if($recordId!=0)
		{
		  	$db_buying = PearDatabase::getInstance();						
			$jrer_buying = 'SELECT * FROM vtiger_jobexpencereport
						  			   INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid	
						 			   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid
						 			   where vtiger_crmentityrel.crmid=? and vtiger_jobexpencereport.owner_id=? and vtiger_jobexpencereport.jobexpencereportid=? 
						 	   		   and vtiger_jobexpencereportcf.cf_1457="Expence" ';
			//jobid as record_id						   
			$params_buying = array(@$job_info->get('record_id'), $current_user->getId(), $recordId);
			$result_buying = $db_buying->pquery($jrer_buying,$params_buying);
			$job_jrer_buying = $db_buying->fetch_array($result_buying);	
			
			
			//First Time expense editing SUBJCR user
			if($db_buying->num_rows($result_buying)==0)
			{
				$db_buying_2 = PearDatabase::getInstance();		
				$jrer_buying_2 = 'SELECT * FROM vtiger_jobexpencereport
						  			   INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid	
						 			   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid
						 			   where vtiger_crmentityrel.crmid=? and vtiger_jobexpencereport.jobexpencereportid=? 
						 	   		   and vtiger_jobexpencereportcf.cf_1457="Expence" ';
				$params_buying_2 = array(@$job_info->get('record_id'), $recordId);
				$result_buying_2 = $db_buying_2->pquery($jrer_buying_2,$params_buying_2);
				$job_jrer_buying = $db_buying_2->fetch_array($result_buying_2);				
			}									
		}
		
		
		$b_job_charges_id = $request->get('cf_1453');
		$b_office_id	  = $request->get('cf_1477');
		$b_department_id  = $request->get('cf_1479');
		if($job_info->get('assigned_user_id')!=$current_user->getId())
		{
		$b_office_id	  = $current_user->get('location_id');
		$b_department_id  = $current_user->get('department_id');
		}else{
		$b_office_id	  = $request->get('cf_1477');
		$b_department_id  = $request->get('cf_1479');
		}
		
		$b_pay_to_id	  = $request->get('cf_1367');
		$b_invoice_number = $request->get('cf_1212');
		$b_invoice_date   = $request->get('cf_1216');
		$b_invoice_due_date = $request->get('cf_1210');
		$b_type_id	  	  = $request->get('cf_1214');
		$b_buy_vendor_currency_net	= $request->get('cf_1337');
		if(empty($b_buy_vendor_currency_net)) 
		{ 
			$b_buy_vendor_currency_net = 0;
		}
		$b_vat_rate	  	  = $request->get('cf_1339');
		
		$b_vat = '0.00';
		if(!empty($b_vat_rate) && $b_vat_rate>0)
		{
			$b_vat_rate_cal  = $b_vat_rate/100; 
			$b_vat 		     = $b_buy_vendor_currency_net * $b_vat_rate_cal;
		}
		$b_buy_vendor_currency_gross = $b_buy_vendor_currency_net + $b_vat;
		
		$b_vendor_currency	  = $request->get('cf_1345');
		$b_exchange_rate 	  = $request->get('cf_1222');
		
		$b_invoice_date_format = date('Y-m-d', strtotime($b_invoice_date));
		
		$b_vendor_currency_code = Vtiger_CurrencyList_UIType::getDisplayValue($b_vendor_currency);		
		if($file_title_currency =='KZT')
		{			
			$b_exchange_rate  		= exchange_rate_currency($b_invoice_date_format, $b_vendor_currency_code);
		}
		elseif($file_title_currency =='USD')
		{
			$b_exchange_rate = currency_rate_convert($b_vendor_currency_code, $file_title_currency, 1, $b_invoice_date_format);
		}
		else{			
			$b_exchange_rate = currency_rate_convert_others($b_vendor_currency_code, $file_title_currency, 1, $b_invoice_date_format);
		}
		
		
		if($file_title_currency !='USD')
		{						
			$b_buy_local_currency_gross = $b_buy_vendor_currency_gross * $b_exchange_rate;
		}else{
			$b_buy_local_currency_gross = exchange_rate_convert($b_vendor_currency_code, $file_title_currency, $b_buy_vendor_currency_gross, $b_invoice_date_format);
		}
		
				
		if($file_title_currency !='USD')
		{						
			$b_buy_local_currency_net = $b_buy_vendor_currency_net * $b_exchange_rate;
		}else{
			$b_buy_local_currency_net = exchange_rate_convert($b_vendor_currency_code, $file_title_currency, $b_buy_vendor_currency_net, $b_invoice_date_format);
		}
		
		$b_expected_buy_local_currency_net	  = $request->get('cf_1351');
		
		if(!empty($job_jrer_buying['jerid']))
		{
			$job_costing_id = $job_jrer_buying['jerid'];
			if(!empty($job_costing_id))
			{
													   
			$db_costing_1 = PearDatabase::getInstance();		
			$query_costing = 'SELECT * FROM vtiger_jer
								   INNER JOIN vtiger_jercf ON vtiger_jercf.jerid = vtiger_jer.jerid	
								   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jer.jerid
								   where vtiger_crmentityrel.crmid=? and vtiger_jer.jerid=?';
			$params_costing_1 = array(@$job_info->get('record_id'), $job_costing_id);
			
			$result_costing_1 = $db_costing_1->pquery($query_costing, $params_costing_1);
			$cost_of_local_currency = $db_costing_1->fetch_array($result_costing_1);								   	
			//cost_local_currency
			$b_expected_buy_local_currency_net = @$cost_of_local_currency['cf_1160'];
			}
		}
		
		$b_variation_expected_and_actual_buying = $b_expected_buy_local_currency_net - $b_buy_local_currency_net;
		
		/*
		if($post->office_id==$this->current_user->office_id){
			$file_title_id = $post->file_title;
		}
		else{
			$file_title_id = $this->input->post('file_title');
		}*/
		
		$job_office_id = $job_info->get('cf_1188');
		$job_department_id = $job_info->get('cf_1190');
		if($job_office_id==$current_user_office_id){
			$file_title_id = $job_info->get('cf_1186');
		}
		else{
			$file_title_id = $current_user->get('company_id');
		}
		
		if($job_info->get('assigned_user_id')!=$current_user->getId())
		{	
			if($job_office_id==$current_user_office_id){
				$file_title_id = $job_info->get('cf_1186');
			}
			else{
				$file_title_id = $current_user->get('company_id');
				$db = PearDatabase::getInstance();
				$query = 'UPDATE vtiger_jobtask SET sub_jrer_file_title=? WHERE job_id=? and user_id=?';
				$params = array($file_title_id, $job_id, $current_user->getId());
				$db->pquery($query,$params);
				//$this->db->where('job_id', $post->id)->where('user_id', $this->current_user->id)->update('job_user_assigned', array('sub_jrer_file_title' => $file_title_id));
			}
			
			$office = Vtiger_LocationList_UIType::getDisplayValue($current_user->get('location_id'));
			$department = Vtiger_DepartmentList_UIType::getDepartment($current_user->get('department_id'));		
			
			if($b_type_id=='46116') //For JZ company
			{
				$db_revenue = PearDatabase::getInstance();
				$query_revenue = 'SELECT * FROM vtiger_chartofaccount where name=?';
				$params_revenue = array('Revenue');
				$result_revenue = $db_revenue->pquery($query_revenue,$params_revenue);
				$r_job_charges_id = $db_revenue->fetch_array($result_revenue);
				
				$db_coa = PearDatabase::getInstance();
				$query_coa = 'SELECT * FROM vtiger_companyaccount
							  INNER JOIN vtiger_companyaccountcf ON vtiger_companyaccountcf.companyaccountid = vtiger_companyaccount.companyaccountid
						  	  INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_companyaccount.companyaccountid							  
							  where vtiger_crmentityrel.crmid=? and vtiger_companyaccountcf.cf_1501=?';
				$params_coa = array('45628', $r_job_charges_id['chartofaccountid']);
				$result_coa = $db_coa->pquery($query_coa, $params_coa);
				$coa_info = $db_coa->fetch_array($result_coa);
			}
			elseif($b_type_id=='46118') // For DLC company
			{
				$db_revenue = PearDatabase::getInstance();
				$query_revenue = 'SELECT * FROM vtiger_chartofaccount where name=?';
				$params_revenue = array('Revenue');
				$result_revenue = $db_revenue->pquery($query_revenue,$params_revenue);
				$r_job_charges_id = $db_revenue->fetch_array($result_revenue);
				
				$db_coa = PearDatabase::getInstance();
				$query_coa = 'SELECT * FROM vtiger_companyaccount
							  INNER JOIN vtiger_companyaccountcf ON vtiger_companyaccountcf.companyaccountid = vtiger_companyaccount.companyaccountid
							  INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_companyaccount.companyaccountid
							  where vtiger_crmentityrel.crmid=? and vtiger_companyaccountcf.cf_1501=?';
				$params_coa = array('40853', $r_job_charges_id['chartofaccountid']);
				$result_coa = $db_coa->pquery($query_coa, $params_coa);
				$coa_info = $db_coa->fetch_array($result_coa);
			}
			else{														
				$db_revenue = PearDatabase::getInstance();
				$query_revenue = 'SELECT * FROM vtiger_chartofaccount where name=?';
				$params_revenue = array('Revenue');
				$result_revenue = $db_revenue->pquery($query_revenue,$params_revenue);
				$r_job_charges_id = $db_revenue->fetch_array($result_revenue);
				
				$db_coa = PearDatabase::getInstance();
				$query_coa = 'SELECT * FROM vtiger_companyaccount
							  INNER JOIN vtiger_companyaccountcf ON vtiger_companyaccountcf.companyaccountid = vtiger_companyaccount.companyaccountid
							  INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_companyaccount.companyaccountid								  
							  where vtiger_crmentityrel.crmid=? and vtiger_companyaccountcf.cf_1501=?';
				$params_coa = array($file_title_id, $r_job_charges_id['chartofaccountid']);
				$result_coa = $db_coa->pquery($query_coa, $params_coa);
				$coa_info = $db_coa->fetch_array($result_coa);
			}		
			//Revenue				
			$b_gl_account = @$coa_info['name'].'-'.$office.'-'.$department;
			
			if($b_type_id=='46116')
			{
				$db_ap = PearDatabase::getInstance();
				$query_ap = 'SELECT * FROM vtiger_chartofaccount where name=?';
				$params_ap = array('Trade Creditors');
				$result_ap = $db_ap->pquery($query_ap,$params_ap);
				$ap_job_charges_id = $db_ap->fetch_array($result_ap);
				
				$db_coa = PearDatabase::getInstance();
				$query_coa = 'SELECT * FROM vtiger_companyaccount
							  INNER JOIN vtiger_companyaccountcf ON vtiger_companyaccountcf.companyaccountid = vtiger_companyaccount.companyaccountid
						  	  INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_companyaccount.companyaccountid
							  where vtiger_crmentityrel.crmid=? and vtiger_companyaccountcf.cf_1501=?';
				$params_coa = array('45628', $ap_job_charges_id['chartofaccountid']);
				$result_coa = $db_coa->pquery($query_coa, $params_coa);
				$ap_coa_info = $db_coa->fetch_array($result_coa);			
				
			}
			elseif($b_type_id=='46118')
			{
				$db_ap = PearDatabase::getInstance();
				$query_ap = 'SELECT * FROM vtiger_chartofaccount where name=?';
				$params_ap = array('Trade Creditors');
				$result_ap = $db_ap->pquery($query_ap,$params_ap);
				$ap_job_charges_id = $db_ap->fetch_array($result_ap);
				
				$db_coa = PearDatabase::getInstance();
				$query_coa = 'SELECT * FROM vtiger_companyaccount
							  INNER JOIN vtiger_companyaccountcf ON vtiger_companyaccountcf.companyaccountid = vtiger_companyaccount.companyaccountid
						  	  INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_companyaccount.companyaccountid
							  where vtiger_crmentityrel.crmid=? and vtiger_companyaccountcf.cf_1501=?';
				$params_coa = array('40853', $ap_job_charges_id['chartofaccountid']);
				$result_coa = $db_coa->pquery($query_coa, $params_coa);
				$ap_coa_info = $db_coa->fetch_array($result_coa);
			}
			else{
				
				$db_ap = PearDatabase::getInstance();
				$query_ap = 'SELECT * FROM vtiger_chartofaccount where name=?';
				$params_ap = array('Trade Creditors');
				$result_ap = $db_ap->pquery($query_ap,$params_ap);
				$ap_job_charges_id = $db_ap->fetch_array($result_ap);
				
				$db_coa = PearDatabase::getInstance();
				$query_coa = 'SELECT * FROM vtiger_companyaccount
							  INNER JOIN vtiger_companyaccountcf ON vtiger_companyaccountcf.companyaccountid = vtiger_companyaccount.companyaccountid
						  	  INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_companyaccount.companyaccountid								  
							  where vtiger_crmentityrel.crmid=? and vtiger_companyaccountcf.cf_1501=?';
				$params_coa = array($file_title_id, $ap_job_charges_id['chartofaccountid']);
				$result_coa = $db_coa->pquery($query_coa, $params_coa);
				$ap_coa_info = $db_coa->fetch_array($result_coa);
			}
			//Trade Creditors					
			$b_ap_gl_account = @$ap_coa_info['name'].'-'.$office.'-'.$department;
			
			$assigned_file_title_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency($file_title_id);		
			
			if($assigned_file_title_currency->currency =='KZT')
			{	
				$exchrates = $this->db->where('currency_code', $b_vendor_currency)->where('date', $b_invoice_date)->get('exchangerate')->row();					
				$bb_exchange_rate = $exchrates->rate;
			}
			elseif($assigned_file_title_currency->currency =='USD')
			{
				$bb_exchange_rate = $this->currency_rate_convert($b_vendor_currency, $assigned_file_title_currency->currency, 1, $b_invoice_date);
			}
			else{			
				$bb_exchange_rate = $this->currency_rate_convert_others($b_vendor_currency, $assigned_file_title_currency->currency, 1, $b_invoice_date);
			}	
			
			$b_vendor_currency_code = Vtiger_CurrencyList_UIType::getDisplayValue($b_vendor_currency);
			if($assigned_file_title_currency =='KZT')
			{	
				$bb_exchange_rate = exchange_rate_currency($b_invoice_date_format, $b_vendor_currency_code);
			}
			elseif($assigned_file_title_currency =='USD')
			{
				$bb_exchange_rate = currency_rate_convert($b_vendor_currency_code, $assigned_file_title_currency, 1, $b_invoice_date_format);
			}
			else{			
				$bb_exchange_rate = currency_rate_convert_others($b_vendor_currency_code, $assigned_file_title_currency, 1, $b_invoice_date_format);
			}
			
			if($assigned_file_title_currency !='USD')
			{						
				$bb_buy_local_currency_gross = $b_buy_vendor_currency_gross * $bb_exchange_rate;
			}else{
				$bb_buy_local_currency_gross = exchange_rate_convert($b_vendor_currency_code, $assigned_file_title_currency, $b_buy_vendor_currency_gross, $b_invoice_date_format);
			}
			
			if($assigned_file_title_currency !='USD')
			{						
				$bb_buy_local_currency_net = $b_buy_vendor_currency_net * $bb_exchange_rate;
			}else{
				$bb_buy_local_currency_net = exchange_rate_convert($b_vendor_currency_code, $assigned_file_title_currency,$b_buy_vendor_currency_net, $b_invoice_date_format);
			}
			$bb_expected_buy_local_currency_net	  = $request->get('cf_1351');	
			
			$bb_variation_expected_and_actual_buying = $bb_expected_buy_local_currency_net - $bb_buy_local_currency_net;	
			
			$assigned_extra_buying = array(
											'job_id'  			=> $job_id,
											'date_added'		=> date('Y-m-d H:i:s'),
											'b_job_charges_id' 	=> $b_job_charges_id,
											'b_office_id' 		=> $b_office_id,
											'b_department_id' 	=> $b_department_id,
											'b_pay_to_id' 		=> $b_pay_to_id,
											'b_invoice_number' 	=> $b_invoice_number,
											'b_invoice_date' 	=> $b_invoice_date,
											'b_invoice_due_date'=> $b_invoice_due_date,
											'b_type_id'			=> $b_type_id,			
											'b_buy_vendor_currency_net' => $b_buy_vendor_currency_net,
											'b_vat_rate'        => $b_vat_rate,
											'b_vat'             => $b_vat,
											'b_buy_vendor_currency_gross' => $b_buy_vendor_currency_gross,
											'b_vendor_currency' => $b_vendor_currency,
											'b_exchange_rate'   => $bb_exchange_rate,
											'b_buy_local_currency_gross' => $bb_buy_local_currency_gross,
											'b_buy_local_currency_net'   => $bb_buy_local_currency_net,
											'b_expected_buy_local_currency_net' => (isset($bb_expected_buy_local_currency_net) ? $bb_expected_buy_local_currency_net :'0.00'),
											'b_variation_expected_and_actual_buying' => (isset($bb_variation_expected_and_actual_buying) ? $bb_variation_expected_and_actual_buying: '0.00'),
											'b_gl_account'		=> $b_gl_account,
											'b_ar_gl_account'	=> $b_ap_gl_account,												
										 );	
		}
		
		/*
		if($this->current_user->id!=$post->author_id)
		{	
			$b_type_id_arr = explode('-',$b_type_id);
			if($b_type_id_arr[0]==1)
			{
				$b_type_id = $b_type_id;
			}
			elseif($b_type_id_arr[0]!=1 && $post->file_title!=1)
			{
				$b_type_id = $post->file_title.'-'.$b_type_id_arr[1];
			}
			//elseif($b_type_id_arr[0]==1 && $post->file_title!=1)
			//{
			//	$b_type_id = $b_type_id_arr[0].'-'.@$b_type_id_arr[1];
			//}
			else{
				//Its JZ file because we need file title for bank
				$user_file_title_ = $this->user_companies_m->get_user_file_title_byuserid($post->author_id);
				$b_type_id = @$user_file_title_[0]->company_id.'-'.@$b_type_id_arr[1];						
			}
		}*/
		$office = Vtiger_LocationList_UIType::getDisplayValue($job_office_id);
		$department = Vtiger_DepartmentList_UIType::getDepartment($job_department_id);	
		
		if($b_type_id=='46116')
		{			
			$db_revenue = PearDatabase::getInstance();
			$query_revenue = 'SELECT * FROM vtiger_chartofaccount where name=?';
			$params_revenue = array('Revenue');
			$result_revenue = $db_revenue->pquery($query_revenue,$params_revenue);
			$r_job_charges_id = $db_revenue->fetch_array($result_revenue);
			
			$db_coa = PearDatabase::getInstance();
			$query_coa = 'SELECT * FROM vtiger_companyaccount
						  INNER JOIN vtiger_companyaccountcf ON vtiger_companyaccountcf.companyaccountid = vtiger_companyaccount.companyaccountid
						  INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_companyaccount.companyaccountid						  
						  where vtiger_crmentityrel.crmid=? and vtiger_companyaccountcf.cf_1501=?';
			$params_coa = array('45628', $r_job_charges_id['chartofaccountid']);
			$result_coa = $db_coa->pquery($query_coa, $params_coa);
			$coa_info = $db_coa->fetch_array($result_coa);
		}
		elseif($b_type_id=='46118')
		{				
			$db_revenue = PearDatabase::getInstance();
			$query_revenue = 'SELECT * FROM vtiger_chartofaccount where name=?';
			$params_revenue = array('Revenue');
			$result_revenue = $db_revenue->pquery($query_revenue,$params_revenue);
			$r_job_charges_id = $db_revenue->fetch_array($result_revenue);
			
			$db_coa = PearDatabase::getInstance();
			$query_coa = 'SELECT * FROM vtiger_companyaccount
						  INNER JOIN vtiger_companyaccountcf ON vtiger_companyaccountcf.companyaccountid = vtiger_companyaccount.companyaccountid
						  INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_companyaccount.companyaccountid
						  where vtiger_crmentityrel.crmid=? and vtiger_companyaccountcf.cf_1501=?';
			$params_coa = array('40853', $r_job_charges_id['chartofaccountid']);
			$result_coa = $db_coa->pquery($query_coa, $params_coa);
			$coa_info = $db_coa->fetch_array($result_coa);	
		}
		else{													
			$db_revenue = PearDatabase::getInstance();
			$query_revenue = 'SELECT * FROM vtiger_chartofaccount where name=?';
			
			$params_revenue = array('Revenue');
			$result_revenue = $db_revenue->pquery($query_revenue,$params_revenue);
			$r_job_charges_id = $db_revenue->fetch_array($result_revenue);
			
			$db_coa = PearDatabase::getInstance();
			$query_coa = 'SELECT * FROM vtiger_companyaccount
						  INNER JOIN vtiger_companyaccountcf ON vtiger_companyaccountcf.companyaccountid = vtiger_companyaccount.companyaccountid
						  INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_companyaccount.companyaccountid						  
						  where vtiger_crmentityrel.crmid=? and vtiger_companyaccountcf.cf_1501=?';
			$params_coa = array($file_title_id, $r_job_charges_id['chartofaccountid']);				
			$result_coa = $db_coa->pquery($query_coa, $params_coa);
			$coa_info = $db_coa->fetch_array($result_coa);		
		}		
		//For revenue
		$b_gl_account = @$coa_info['name'].'-'.$office.'-'.$department;
		
		if($b_type_id=='46116')
		{
			$db_ap = PearDatabase::getInstance();
			$query_ap = 'SELECT * FROM vtiger_chartofaccount where name=?';
			$params_ap = array('Trade Creditors');
			$result_ap = $db_ap->pquery($query_ap,$params_ap);
			$ap_job_charges_id = $db_ap->fetch_array($result_ap);
			
			$db_coa = PearDatabase::getInstance();
			$query_coa = 'SELECT * FROM vtiger_companyaccount
			        	  INNER JOIN vtiger_companyaccountcf ON vtiger_companyaccountcf.companyaccountid = vtiger_companyaccount.companyaccountid
						  INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_companyaccount.companyaccountid
						  where vtiger_crmentityrel.crmid=? and vtiger_companyaccountcf.cf_1501=?';
			$params_coa = array('45628', $ap_job_charges_id['chartofaccountid']);
			$result_coa = $db_coa->pquery($query_coa, $params_coa);
			$ap_coa_info = $db_coa->fetch_array($result_coa);			
			
		}
		elseif($b_type_id=='46118')
		{
			$db_ap = PearDatabase::getInstance();
			$query_ap = 'SELECT * FROM vtiger_chartofaccount where name=?';
			$params_ap = array('Trade Creditors');
			$result_ap = $db_ap->pquery($query_ap,$params_ap);
			$ap_job_charges_id = $db_ap->fetch_array($result_ap);
			
			$db_coa = PearDatabase::getInstance();
			$query_coa = 'SELECT * FROM vtiger_companyaccount
						  INNER JOIN vtiger_companyaccountcf ON vtiger_companyaccountcf.companyaccountid = vtiger_companyaccount.companyaccountid
						  INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_companyaccount.companyaccountid
						  where vtiger_crmentityrel.crmid=? and vtiger_companyaccountcf.cf_1501=?';
			$params_coa = array('40853', $ap_job_charges_id['chartofaccountid']);
			$result_coa = $db_coa->pquery($query_coa, $params_coa);
			$ap_coa_info = $db_coa->fetch_array($result_coa);
		}
		else{			
			$db_ap = PearDatabase::getInstance();
			$query_ap = 'SELECT * FROM vtiger_chartofaccount where name=?';
			$params_ap = array('Trade Creditors');
			$result_ap = $db_ap->pquery($query_ap,$params_ap);
			$ap_job_charges_id = $db_ap->fetch_array($result_ap);
			
			$db_coa = PearDatabase::getInstance();
			
			$query_coa = 'SELECT * FROM vtiger_companyaccount
			  			  INNER JOIN vtiger_companyaccountcf ON vtiger_companyaccountcf.companyaccountid = vtiger_companyaccount.companyaccountid
						  INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_companyaccount.companyaccountid
						  where vtiger_crmentityrel.crmid=? and vtiger_companyaccountcf.cf_1501=?';
			$params_coa = array($file_title_id, $ap_job_charges_id['chartofaccountid']);
			$result_coa = $db_coa->pquery($query_coa, $params_coa);
			$ap_coa_info = $db_coa->fetch_array($result_coa);
		}
		//Trade Creditors					
		$b_ap_gl_account = @$ap_coa_info['name'].'-'.$office.'-'.$department;	
		
		$extra_buying = array(
								//'user_id' => $this->current_user->id,
								'job_id'  => $job_id,
								'date_added'	=> date('Y-m-d H:i:s'),
								'b_job_charges_id' => $b_job_charges_id,
								'b_office_id' => $b_office_id,
								'b_department_id' => $b_department_id,
								'b_pay_to_id' => $b_pay_to_id,
								'b_invoice_number' => $b_invoice_number,
								'b_invoice_date' => $b_invoice_date,
								'b_invoice_due_date' => $b_invoice_due_date,
								'b_type_id'			=> $b_type_id,
								'b_buy_vendor_currency_net' => $b_buy_vendor_currency_net,
								'b_vat_rate'        => $b_vat_rate,
								'b_vat'             => $b_vat,
								'b_buy_vendor_currency_gross' => $b_buy_vendor_currency_gross,
								'b_vendor_currency'    => $b_vendor_currency,
								'b_exchange_rate'      => $b_exchange_rate,
								'b_buy_local_currency_gross' => $b_buy_local_currency_gross,
								'b_buy_local_currency_net'   => $b_buy_local_currency_net,
								'b_expected_buy_local_currency_net' => (isset($b_expected_buy_local_currency_net) ? $b_expected_buy_local_currency_net :'0.00'),
								'b_variation_expected_and_actual_buying' => (isset($b_variation_expected_and_actual_buying) ? $b_variation_expected_and_actual_buying: '0.00'),
								'b_gl_account'		=> $b_gl_account,
								'b_ar_gl_account'	=> $b_ap_gl_account,
								);
			
			
								
			if($job_info->get('assigned_user_id')!=$current_user->getId())
				{
				
				$extra_buying['company_id'] = $job_info->get('cf_1186');
				$extra_buying['owner_id'] = $job_info->get('assigned_user_id');
				
				
				$request->set('cf_1453', $extra_buying['b_job_charges_id']);
				$request->set('cf_1477', $extra_buying['b_office_id']);
				$request->set('cf_1479', $extra_buying['b_department_id']);
				$request->set('cf_1367', $extra_buying['b_pay_to_id']);
				$request->set('cf_1212', $extra_buying['b_invoice_number']);
				$request->set('cf_1216', $extra_buying['b_invoice_date']);
				$request->set('cf_1210', $extra_buying['b_invoice_due_date']);
				$request->set('cf_1214', $extra_buying['b_type_id']);
				$request->set('cf_1337', $extra_buying['b_buy_vendor_currency_net']);			
				$request->set('cf_1339', $extra_buying['b_vat_rate']);
				$request->set('cf_1341', $extra_buying['b_vat']);
				$request->set('cf_1343', $extra_buying['b_buy_vendor_currency_gross']);
				$request->set('cf_1345', $extra_buying['b_vendor_currency']);
				$request->set('cf_1222', $extra_buying['b_exchange_rate']);
				$request->set('cf_1347', $extra_buying['b_buy_local_currency_gross']);
				$request->set('cf_1349', $extra_buying['b_buy_local_currency_net']);
				$request->set('cf_1351', $extra_buying['b_expected_buy_local_currency_net']);
				$request->set('cf_1353', $extra_buying['b_variation_expected_and_actual_buying']);
				
				$request->set('b_gl_account', $extra_buying['b_gl_account']);
				$request->set('b_ar_gl_account', $extra_buying['b_ar_gl_account']);
				$request->set('company_id', $extra_buying['company_id']);
				//$request->set('user_id', $extra_buying['user_id']);
				$request->set('owner_id', $extra_buying['owner_id']);
				//$request->set('jrer_buying_id', $extra_buying['jrer_buying_id']);
				
				$recordModel = $this->saveRecord($request);
				
					
				$assigned_extra_buying['company_id'] = @$file_title_id;
				$assigned_extra_buying['user_id'] 	 = $current_user->getId();
				$assigned_extra_buying['owner_id']   = $current_user->getId();
				//$assigned_extra_buying['jrer_buying_id'] = 0;
				
				$request->set('cf_1453', $assigned_extra_buying['b_job_charges_id']);
				$request->set('cf_1477', $assigned_extra_buying['b_office_id']);
				$request->set('cf_1479', $assigned_extra_buying['b_department_id']);
				$request->set('cf_1367', $assigned_extra_buying['b_pay_to_id']);
				$request->set('cf_1212', $assigned_extra_buying['b_invoice_number']);
				$request->set('cf_1216', $assigned_extra_buying['b_invoice_date']);
				$request->set('cf_1210', $assigned_extra_buying['b_invoice_due_date']);
				$request->set('cf_1214', $assigned_extra_buying['b_type_id']);
				$request->set('cf_1337', $assigned_extra_buying['b_buy_vendor_currency_net']);			
				$request->set('cf_1339', $assigned_extra_buying['b_vat_rate']);
				$request->set('cf_1341', $assigned_extra_buying['b_vat']);
				$request->set('cf_1343', $assigned_extra_buying['b_buy_vendor_currency_gross']);
				$request->set('cf_1345', $assigned_extra_buying['b_vendor_currency']);
				$request->set('cf_1222', $assigned_extra_buying['b_exchange_rate']);
				$request->set('cf_1347', $assigned_extra_buying['b_buy_local_currency_gross']);
				$request->set('cf_1349', $assigned_extra_buying['b_buy_local_currency_net']);
				$request->set('cf_1351', $assigned_extra_buying['b_expected_buy_local_currency_net']);
				$request->set('cf_1353', $assigned_extra_buying['b_variation_expected_and_actual_buying']);
				
				$request->set('b_gl_account', $assigned_extra_buying['b_gl_account']);
				$request->set('b_ar_gl_account', $assigned_extra_buying['b_ar_gl_account']);
				$request->set('company_id', $assigned_extra_buying['company_id']);
				$request->set('user_id', $assigned_extra_buying['user_id']);
				$request->set('owner_id', $assigned_extra_buying['owner_id']);
				//$request->set('jrer_buying_id', $assigned_extra_buying['jrer_buying_id']);
				
				$recordModel = $this->saveRecord($request);
				//$jrer_buying_id_assigned = $recordModel->getId();
			}
			else{
				
				$extra_buying['company_id'] = $job_info->get('cf_1186');
				$extra_buying['owner_id'] = $job_info->get('assigned_user_id');
				
				$request->set('cf_1453', $extra_buying['b_job_charges_id']);
				$request->set('cf_1477', $extra_buying['b_office_id']);
				$request->set('cf_1479', $extra_buying['b_department_id']);
				$request->set('cf_1367', $extra_buying['b_pay_to_id']);
				$request->set('cf_1212', $extra_buying['b_invoice_number']);
				$request->set('cf_1216', $extra_buying['b_invoice_date']);
				$request->set('cf_1210', $extra_buying['b_invoice_due_date']);
				$request->set('cf_1214', $extra_buying['b_type_id']);
				$request->set('cf_1337', $extra_buying['b_buy_vendor_currency_net']);				
				$request->set('cf_1339', $extra_buying['b_vat_rate']);
				$request->set('cf_1341', $extra_buying['b_vat']);
				$request->set('cf_1343', $extra_buying['b_buy_vendor_currency_gross']);
				$request->set('cf_1345', $extra_buying['b_vendor_currency']);
				$request->set('cf_1222', $extra_buying['b_exchange_rate']);
				$request->set('cf_1347', $extra_buying['b_buy_local_currency_gross']);
				$request->set('cf_1349', $extra_buying['b_buy_local_currency_net']);
				$request->set('cf_1351', $extra_buying['b_expected_buy_local_currency_net']);
				$request->set('cf_1353', $extra_buying['b_variation_expected_and_actual_buying']);
				
				$request->set('b_gl_account', $extra_buying['b_gl_account']);
				$request->set('b_ar_gl_account', $extra_buying['b_ar_gl_account']);
				$request->set('company_id', $extra_buying['company_id']);
				//$request->set('user_id', $extra_buying['user_id']);
				$request->set('owner_id', $extra_buying['owner_id']);
				//$request->set('jrer_buying_id', $extra_buying['jrer_buying_id']);
				
				$recordModel = $this->saveRecord($request);			
			}
					
			
			$extra_expense[] = $extra_buying;	
			if($job_info->get('assigned_user_id')!=$current_user->getId())
			{	
				$assigned_extra_expense[] = $assigned_extra_buying;
			}
			
			
			if(!empty($extra_expense))
			{
				foreach($extra_expense as $expense)
				{
					$db_selling_count = PearDatabase::getInstance();
					$jrer_selling_count = 'SELECT * FROM vtiger_jobexpencereport
										   INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid	
										   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid
										   where vtiger_crmentityrel.crmid=? and vtiger_jobexpencereportcf.cf_1455=? and vtiger_jobexpencereportcf.cf_1477=? 
										   and vtiger_jobexpencereportcf.cf_1479=? and vtiger_jobexpencereportcf.cf_1445=? 
										   and vtiger_jobexpencereportcf.cf_1457="Selling" ';
					$params_selling_count = array($expense['job_id'], 
										  $expense['b_job_charges_id'], 
										  $expense['b_office_id'], 
										  $expense['b_department_id'],
										  $job_info->get('cf_1441')
										  );
										  
					$result_selling_count = $db_selling_count->pquery($jrer_selling_count,$params_selling_count);
					
					if($db_selling_count->num_rows($result_selling_count)==0)
					{
						 $extra_selling_cost = array(													
													'user_id'		   => $current_user->getId(),
													'job_id'  		   => $expense['job_id'],
													'date_added'	   => date('Y-m-d H:i:s'),
													's_job_charges_id' => $expense['b_job_charges_id'],
													's_office_id' 	   => $expense['b_office_id'],
													's_department_id'  => $expense['b_department_id'],
													's_bill_to_id' 	   => $job_info->get('cf_1441'),
													's_invoice_date'   => $expense['b_invoice_date'],
													's_customer_currency' => $expense['b_vendor_currency'],
													'company_id' 	   => @$expense['company_id'],
													'owner_id' 		   => @$expense['owner_id'],												
													's_variation_expect_and_actual_profit' => @$expense['b_variation_expected_and_actual_buying'],
													);
						
						$request->set('cf_1453', '');
						$request->set('cf_1367', '');
						$request->set('cf_1212', '');
						$request->set('cf_1216', '');
						$request->set('cf_1210', '');
						$request->set('cf_1214', '');
						$request->set('cf_1337', '');
						$request->set('cf_1339', '');
						$request->set('cf_1341', '');
						$request->set('cf_1343', '');
						$request->set('cf_1345', '');
						$request->set('cf_1222', '');
						$request->set('cf_1347', '');
						$request->set('cf_1349', '');
						$request->set('cf_1351', '');
						$request->set('cf_1353', '');							
													
						$request->set('cf_1455', $extra_selling_cost['s_job_charges_id']);
						$request->set('cf_1477', $extra_selling_cost['s_office_id']);
						$request->set('cf_1479', $extra_selling_cost['s_department_id']);
						$request->set('cf_1445', $extra_selling_cost['s_bill_to_id']);						
						$request->set('cf_1355', $extra_selling_cost['s_invoice_date']);							
						$request->set('cf_1234', $extra_selling_cost['s_customer_currency']);						
						$request->set('cf_1246', $extra_selling_cost['s_variation_expect_and_actual_profit']);
						$request->set('cf_1457', 'Selling');
						
						$request->set('record','');
						$request->set('id','');
						$recordModel->set('mode', '');
						
						$recordModel = $this->saveRecord($request);
						$jobexpencereportid = $recordModel->getId();
						
						$db_sell_up = PearDatabase::getInstance();
						$query_sell_up = 'UPDATE vtiger_jobexpencereport set job_id=?, company_id=?, user_id=?, owner_id=? where jobexpencereportid=?';
						$params_sell_up = array($extra_selling_cost['job_id'], $extra_selling_cost['company_id'], $extra_selling_cost['user_id'], $extra_selling_cost['owner_id'], $jobexpencereportid);
						$result_sell_up = $db_sell_up->pquery($query_sell_up, $params_sell_up);
					}
				}
			}
				
			if(!empty($assigned_extra_expense))
			{
				foreach($assigned_extra_expense as $expense)
				{
					$db_customer = PearDatabase::getInstance();				
					$query_bill_to_info = 'SELECT * FROM vtiger_accountscf where cf_1703=?';
					$params_bill_to_info = array($job_info->get('cf_1186'));
					$result_bill_to_info = $db_customer->pquery($query_bill_to_info, $params_bill_to_info);
					$bill_to_info = $db_customer->fetch_array($result_bill_to_info);
					
					$db_user_selling_count = PearDatabase::getInstance();
				$jrer_selling_count = 'SELECT * FROM vtiger_jobexpencereport
						  			   INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid	
						 			   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid
						 			   where vtiger_crmentityrel.crmid=? and vtiger_jobexpencereportcf.cf_1455=? and vtiger_jobexpencereportcf.cf_1477=? 
						 	   		   and vtiger_jobexpencereportcf.cf_1479=? and vtiger_jobexpencereportcf.cf_1445=? 
									   and vtiger_jobexpencereportcf.cf_1457="Selling" and vtiger_jobexpencereport.user_id=?';
				$params_selling_count = array($expense['job_id'], 
			  					      $expense['b_job_charges_id'], 
								      $expense['b_office_id'], 
								      $expense['b_department_id'],
								      $bill_to_info['accountid'],
									  $current_user->getId()
								      );
				$result_selling_count = $db_user_selling_count->pquery($jrer_selling_count,$params_selling_count);
				
					if($db_user_selling_count->num_rows($result_selling_count)==0)
					{
						$assigned_extra_selling_cost = array(
															'date_added'	   => date('Y-m-d H:i:s'),
															'user_id'		   => $current_user->getId(),
															'job_id'  		   => $expense['job_id'],
															'date_added'	   => date('Y-m-d H:i:s'),
															's_job_charges_id' => $expense['b_job_charges_id'],
															's_office_id' 	   => $expense['b_office_id'],
															's_department_id'  => $expense['b_department_id'],															
															's_bill_to_id' 	   => $bill_to_info['accountid'],
															's_invoice_date'   => $expense['b_invoice_date'],
															's_customer_currency' => $expense['b_vendor_currency'],
															'company_id' 	   => $expense['company_id'],
															'owner_id' 		   => $expense['owner_id'],
															's_variation_expect_and_actual_profit' => $expense['b_variation_expected_and_actual_buying'],	
															);
															
						$request->set('cf_1453', '');
						$request->set('cf_1367', '');
						$request->set('cf_1212', '');
						$request->set('cf_1216', '');
						$request->set('cf_1210', '');
						$request->set('cf_1214', '');
						$request->set('cf_1337', '');
						$request->set('cf_1339', '');
						$request->set('cf_1341', '');
						$request->set('cf_1343', '');
						$request->set('cf_1345', '');
						$request->set('cf_1222', '');
						$request->set('cf_1347', '');
						$request->set('cf_1349', '');
						$request->set('cf_1351', '');
						$request->set('cf_1353', '');							
													
						$request->set('cf_1455', $assigned_extra_selling_cost['s_job_charges_id']);
						$request->set('cf_1477', $assigned_extra_selling_cost['s_office_id']);
						$request->set('cf_1479', $assigned_extra_selling_cost['s_department_id']);
						$request->set('cf_1445', $assigned_extra_selling_cost['s_bill_to_id']);						
						$request->set('cf_1355', $assigned_extra_selling_cost['s_invoice_date']);							
						$request->set('cf_1234', $assigned_extra_selling_cost['s_customer_currency']);						
						$request->set('cf_1246', $assigned_extra_selling_cost['s_variation_expect_and_actual_profit']);
						$request->set('cf_1457', 'Selling');
						
						$request->set('record','');
						$request->set('id','');
						$recordModel->set('mode', '');
						
						$recordModel = $this->saveRecord($request);
						$jobexpencereportid = $recordModel->getId();
						
						$db_sell_up = PearDatabase::getInstance();
						$query_sell_up = 'UPDATE vtiger_jobexpencereport set job_id=?, company_id=?, user_id=?, owner_id=? where jobexpencereportid=?';
						$params_sell_up = array($assigned_extra_selling_cost['job_id'], $assigned_extra_selling_cost['company_id'], $assigned_extra_selling_cost['user_id'], $assigned_extra_selling_cost['owner_id'], $jobexpencereportid);
						$result_sell_up = $db_sell_up->pquery($query_sell_up, $params_sell_up);
					}
					
				}
			}
			
			
				
	}
	
	public function get_job_id($recordId=0)
	{
		$adb = PearDatabase::getInstance();
		
		$query_job_expense =  'SELECT * from vtiger_crmentityrel where vtiger_crmentityrel.relcrmid=? AND vtiger_crmentityrel.module="Job" AND vtiger_crmentityrel.relmodule="Jobexpencereport"';
		$check_params = array($recordId);
		$result       = $adb->pquery($query_job_expense, $check_params);
		$row          = $adb->fetch_array($result);
		$job_id 	   = $row['crmid'];
		//$sourceModule = $row['module'];
		return $job_id;
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
