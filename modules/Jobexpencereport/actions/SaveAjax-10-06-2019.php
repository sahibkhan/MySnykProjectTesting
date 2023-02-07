<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Jobexpencereport_SaveAjax_Action extends Vtiger_SaveAjax_Action {

	public function process(Vtiger_Request $request) {		
		
		$type = $request->get('cf_1457');
				
		if($type=='Expence')
		{
			$sourceModule = $request->get('sourceModule');
			if(trim($sourceModule)=='Fleet')
			{
				$recordModel = $this->buying_fleet($request);				
			}
			elseif(trim($sourceModule)=='Fleettrip')
			{
				$recordModel = $this->round_trip_buying_fleet($request);	
			}
			elseif(trim($sourceModule)=='WagonTrip')
			{
				$recordModel = $this->railway_expense_buying_wagon($request);	
			}
			else{
				$recordModel = $this->buying($request);
			}
		}
		else{
			$recordModel = $this->selling($request);
			
			/*$loadUrl = 'index.php?module=Job&relatedModule='.$request->get('module').
				'&view=Detail&record='.$job_id.'&mode=showRelatedList&tab_label=Job%20Revenue%20and%20Expence';
			header("Location: $loadUrl");
			exit;*/				
		}
		//$recordModel = $this->saveRecord($request);	
		
		
		$fieldModelList = $recordModel->getModule()->getFields();
		$result = array();
		foreach ($fieldModelList as $fieldName => $fieldModel) {
            $recordFieldValue = $recordModel->get($fieldName);
            if(is_array($recordFieldValue) && $fieldModel->getFieldDataType() == 'multipicklist') {
                $recordFieldValue = implode(' |##| ', $recordFieldValue);
            }
			$fieldValue = $displayValue = Vtiger_Util_Helper::toSafeHTML($recordFieldValue);
			if ($fieldModel->getFieldDataType() !== 'currency' && $fieldModel->getFieldDataType() !== 'datetime' && $fieldModel->getFieldDataType() !== 'date') { 
				$displayValue = $fieldModel->getDisplayValue($fieldValue, $recordModel->getId()); 
			}
			
			$result[$fieldName] = array('value' => $fieldValue, 'display_value' => $displayValue);
		}
		
		//Handling salutation type
		if ($request->get('field') === 'firstname' && in_array($request->getModule(), array('Contacts', 'Leads'))) {
			$salutationType = $recordModel->getDisplayValue('salutationtype');
			$firstNameDetails = $result['firstname'];
			$firstNameDetails['display_value'] = $salutationType. " " .$firstNameDetails['display_value'];
			if ($salutationType != '--None--') $result['firstname'] = $firstNameDetails;
		}
		
		//echo "<pre>";
		//print_r($extra_buying);
		$result['_recordLabel'] = $recordModel->getName();
		$result['_recordId'] = $recordModel->getId();
		//exit;
		
		$response = new Vtiger_Response();
		$response->setEmitType(Vtiger_Response::$EMIT_JSON);
		$response->setResult($result);
		$response->emit();
		
	}
	
	public function get_job_id_from_fleet($recordId=0)
	{
		 $adb = PearDatabase::getInstance();
		 $checkjob = $adb->pquery("SELECT crmid as job_id FROM `vtiger_crmentityrel` where relcrmid='".$recordId."' AND module='Job' AND relmodule='Fleet'", array());
		 $crmId = $adb->query_result($checkjob, 0, 'job_id');
		 $job_id = $crmId;
		 return $job_id;		  
	}
	
	public function railway_expense_buying_wagon($request)
	{
		$wagontrip_id_from_expense = $request->get('sourceRecord');
		
		$sourceModule = 'WagonTrip';	
		$wagon_trip_info = Vtiger_Record_Model::getInstanceById($wagontrip_id_from_expense, $sourceModule);
		
		$extra_expense = array();
		$assigned_extra_expense = array();
		
		$b_job_charges_id = $request->get('cf_1453');
		$b_office_id	  = $request->get('cf_1477');
		$b_department_id  = $request->get('cf_1479');
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
		$vat_included 		= $request->get('cf_3293');
		
		if($vat_included !='VAT Amount')
		{
			$b_vat = '0.00';
			$b_buying_customer_currency_gross = '0.00';
			if(!empty($b_vat_rate) && $b_vat_rate>0)
			{
				if($vat_included=='Yes')
				{
					$b_buying_customer_currency_gross = $request->get('cf_1343');
					$b_vat_rate_ = $b_vat_rate + 100;
					$b_vat_rate_cal = $b_vat_rate_/100; 
					
					$b_buy_vendor_currency_net = 	$b_buying_customer_currency_gross / $b_vat_rate_cal;				
					$b_vat = $b_buying_customer_currency_gross - $b_buy_vendor_currency_net;	
				}
				else
				{
				$b_vat_rate_cal  = $b_vat_rate/100; 
				$b_vat 		     = $b_buy_vendor_currency_net * $b_vat_rate_cal;
				}
			}
		}
		else{
			$b_vat  = $request->get('cf_1341');
		}
		//$b_buy_vendor_currency_gross = $b_buy_vendor_currency_net + $b_vat;
		if($vat_included=='Yes')
		{
			$b_buy_vendor_currency_gross = $b_buying_customer_currency_gross;
			//$b_buy_vendor_currency_gross = $b_buy_vendor_currency_net + $b_vat;
		}
		else{
			$b_buy_vendor_currency_gross = $b_buy_vendor_currency_net + $b_vat;
		}
		
		$b_vendor_currency	= $request->get('cf_1345');
		$b_exchange_rate 	  = $request->get('cf_1222');
		
		$expense_file_title  =$request->get('cf_2191');
		
		$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$expense_file_title);
		$file_title_currency = $reporting_currency;
		
		include("include/Exchangerate/exchange_rate_class.php");
		$current_user = Users_Record_Model::getCurrentUserModel();
		$current_user_office_id = $current_user->get('location_id');
		
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
		
		$b_variation_expected_and_actual_buying = $b_expected_buy_local_currency_net - $b_buy_local_currency_net;
		
		$file_title_id = $request->get('cf_2191');
		$office = Vtiger_LocationList_UIType::getDisplayValue($current_user->get('location_id'));
		$department = Vtiger_DepartmentList_UIType::getDepartment($current_user->get('department_id'));	
		
		if($b_type_id=='85793') //For JZ company
		{
			$chartofaccount_name = 'Revenue';
			$link_company_id = '85772';
			$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
		}
		elseif($b_type_id=='85773') // For DLC company
		{
			$chartofaccount_name = 'Revenue';
			$link_company_id = '85756';
			$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
		}
		else{	
			$chartofaccount_name = 'Revenue';
			$link_company_id = $file_title_id;
			$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
		}		
		//Revenue				
		//$b_gl_account = $coa_info['name'].'-'.$office.'-'.$department;
		$b_gl_account = $coa_info.'-'.$office.'-'.$department;
		
		if($b_type_id=='85793')
		{
			$chartofaccount_name = 'Trade Creditors';
			$link_company_id = '85772';
			$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);				
		}
		elseif($b_type_id=='85773')
		{
			$chartofaccount_name = 'Trade Creditors';
			$link_company_id = '85756';
			$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
		}
		else{
			$chartofaccount_name = 'Trade Creditors';
			$link_company_id = $file_title_id;
			$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
		}
		//Trade Creditors					
		//$b_ap_gl_account = $ap_coa_info['name'].'-'.$office.'-'.$department;
		$b_ap_gl_account = $ap_coa_info.'-'.$office.'-'.$department;
		
		
		$assigned_file_title_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency($file_title_id);
		$b_vendor_currency_code = Vtiger_CurrencyList_UIType::getDisplayValue($b_vendor_currency);
		if($assigned_file_title_currency =='KZT')
		{					
			$bb_exchange_rate  		= exchange_rate_currency($b_invoice_date_format, $b_vendor_currency_code);			
			
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
										'job_id'  			=> 0,
										'date_added'		=> date('Y-m-d H:i:s'),
										'b_job_charges_id' 	=> $b_job_charges_id,
										'b_office_id' 		=> $b_office_id,
										'b_department_id' 	=> $b_department_id,
										'b_pay_to_id' 		=> $b_pay_to_id,
										'b_invoice_number'   => $b_invoice_number,
										'b_invoice_date' 	 => $b_invoice_date,
										'b_invoice_due_date' => $b_invoice_due_date,
										'b_type_id'		  => $b_type_id,			
										'b_buy_vendor_currency_net' => $b_buy_vendor_currency_net,
										'b_vat_rate'         => $b_vat_rate,
										'b_vat'              => $b_vat,
										'b_buy_vendor_currency_gross' => $b_buy_vendor_currency_gross,
										'b_vendor_currency'  => $b_vendor_currency,
										'b_exchange_rate'    => $bb_exchange_rate,
										'b_buy_local_currency_gross' => $bb_buy_local_currency_gross,
										'b_buy_local_currency_net'   => $bb_buy_local_currency_net,
										'b_expected_buy_local_currency_net' => (isset($bb_expected_buy_local_currency_net) ? $bb_expected_buy_local_currency_net :'0.00'),
										'b_variation_expected_and_actual_buying' => (isset($bb_variation_expected_and_actual_buying) ? $bb_variation_expected_and_actual_buying: '0.00'),
										'b_gl_account'		=> $b_gl_account,
										'b_ar_gl_account'	 => $b_ap_gl_account,												
									 );
									 
			$assigned_extra_buying['company_id'] = @$file_title_id;
			$assigned_extra_buying['user_id'] 	 = $current_user->getId();
			$assigned_extra_buying['owner_id']   = $current_user->getId();
			$assigned_extra_buying['jrer_buying_id'] = 0;
			
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
			$request->set('jrer_buying_id', $assigned_extra_buying['jrer_buying_id']);
			
			$request->set('sourceModule', 'WagonTrip'); 		
			$recordModel = $this->saveRecord($request);
			$jrer_buying_id_assigned = $recordModel->getId();
			
			$db_expence_up = PearDatabase::getInstance();
			$query_expence_up = 'UPDATE vtiger_jobexpencereport set job_id=?, company_id=?, user_id=?, owner_id=?, b_gl_account=?, b_ar_gl_account=?, jrer_buying_id=?, wagontrip_id=?  where jobexpencereportid=?';
			$params_expence_up = array($assigned_extra_buying['job_id'], $assigned_extra_buying['company_id'], $assigned_extra_buying['user_id'], $assigned_extra_buying['owner_id'], $assigned_extra_buying['b_gl_account'], $assigned_extra_buying['b_ar_gl_account'], $assigned_extra_buying['jrer_buying_id'], $wagontrip_id_from_expense, $jrer_buying_id_assigned);
			$result_expence_up = $db_expence_up->pquery($query_expence_up, $params_expence_up);
			
			if($wagon_trip_info->get('assigned_user_id')!=$current_user->getId())
			{
				$assigned_extra_buying['user_id'] 	= $current_user->getId();
				$assigned_extra_buying['owner_id']   = $wagon_trip_info->get('assigned_user_id');
				$request->set('user_id', $assigned_extra_buying['user_id']);
				$request->set('owner_id', $assigned_extra_buying['owner_id']);
				$request->set('jrer_buying_id', $jrer_buying_id_assigned);
				//$request->set('record', $fleetrip_jrer_buying['jobexpencereportid']);			
				$recordModel = $this->saveRecord($request);
				$jrer_buying_id_owner = $recordModel->getId();
				
				$db_expence_owner = PearDatabase::getInstance();
				$query_expence_owner = 'UPDATE vtiger_jobexpencereport set job_id=?, company_id=?, user_id=?, owner_id=?, b_gl_account=?, b_ar_gl_account=?, jrer_buying_id=?, wagontrip_id=?  where jobexpencereportid=?';
				$params_expence_owner = array($assigned_extra_buying['job_id'], $assigned_extra_buying['company_id'], $assigned_extra_buying['user_id'], $assigned_extra_buying['owner_id'], $assigned_extra_buying['b_gl_account'], $assigned_extra_buying['b_ar_gl_account'], $jrer_buying_id_assigned, $wagontrip_id_from_expense, $jrer_buying_id_owner);
				$result_expence_owner = $db_expence_owner->pquery($query_expence_owner, $params_expence_owner);
				
			}
									 		
		
	}
	
	public function round_trip_buying_fleet($request)
	{
		$fleet_id_from_expense = $request->get('sourceRecord');
		
		$sourceModule = 'Fleettrip';	
		$fleet_trip_info = Vtiger_Record_Model::getInstanceById($fleet_id_from_expense, $sourceModule);
		
		$extra_expense = array();
		$assigned_extra_expense = array();
		
		$b_job_charges_id = $request->get('cf_1453');
		$b_office_id	  = $request->get('cf_1477');
		$b_department_id  = $request->get('cf_1479');
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
		$vat_included 		= $request->get('cf_3293');
		
		if($vat_included !='VAT Amount')
		{
			$b_vat = '0.00';
			$b_buying_customer_currency_gross = '0.00';
			if(!empty($b_vat_rate) && $b_vat_rate>0)
			{
				if($vat_included=='Yes')
				{
					$b_buying_customer_currency_gross = $request->get('cf_1343');
					$b_vat_rate_ = $b_vat_rate + 100;
					$b_vat_rate_cal = $b_vat_rate_/100; 
					
					$b_buy_vendor_currency_net = 	$b_buying_customer_currency_gross / $b_vat_rate_cal;				
					$b_vat = $b_buying_customer_currency_gross - $b_buy_vendor_currency_net;	
				}
				else
				{
				$b_vat_rate_cal  = $b_vat_rate/100; 
				$b_vat 		     = $b_buy_vendor_currency_net * $b_vat_rate_cal;
				}
			}
		}
		else{
			$b_vat  = $request->get('cf_1341');
		}
		//$b_buy_vendor_currency_gross = $b_buy_vendor_currency_net + $b_vat;
		if($vat_included=='Yes')
		{
			$b_buy_vendor_currency_gross = $b_buying_customer_currency_gross;
			//$b_buy_vendor_currency_gross = $b_buy_vendor_currency_net + $b_vat;
		}
		else{
			$b_buy_vendor_currency_gross = $b_buy_vendor_currency_net + $b_vat;
		}
		
		$b_vendor_currency	= $request->get('cf_1345');
		$b_exchange_rate 	  = $request->get('cf_1222');
		
		$expense_file_title  =$request->get('cf_2191');
		
		$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$expense_file_title);
		$file_title_currency = $reporting_currency;
		
		include("include/Exchangerate/exchange_rate_class.php");
		$current_user = Users_Record_Model::getCurrentUserModel();
		$current_user_office_id = $current_user->get('location_id');
		
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
		
		$b_variation_expected_and_actual_buying = $b_expected_buy_local_currency_net - $b_buy_local_currency_net;
		
		$file_title_id = $request->get('cf_2191');
		$office = Vtiger_LocationList_UIType::getDisplayValue($current_user->get('location_id'));
		$department = Vtiger_DepartmentList_UIType::getDepartment($current_user->get('department_id'));	
		
		if($b_type_id=='85793') //For JZ company
		{
			$chartofaccount_name = 'Revenue';
			$link_company_id = '85772';
			$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
		}
		elseif($b_type_id=='85773') // For DLC company
		{
			$chartofaccount_name = 'Revenue';
			$link_company_id = '85756';
			$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
		}
		else{	
			$chartofaccount_name = 'Revenue';
			$link_company_id = $file_title_id;
			$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
		}		
		//Revenue				
		//$b_gl_account = $coa_info['name'].'-'.$office.'-'.$department;
		$b_gl_account = $coa_info.'-'.$office.'-'.$department;
		
		if($b_type_id=='85793')
		{
			$chartofaccount_name = 'Trade Creditors';
			$link_company_id = '85772';
			$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);				
		}
		elseif($b_type_id=='85773')
		{
			$chartofaccount_name = 'Trade Creditors';
			$link_company_id = '85756';
			$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
		}
		else{
			$chartofaccount_name = 'Trade Creditors';
			$link_company_id = $file_title_id;
			$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
		}
		//Trade Creditors					
		//$b_ap_gl_account = $ap_coa_info['name'].'-'.$office.'-'.$department;
		$b_ap_gl_account = $ap_coa_info.'-'.$office.'-'.$department;
		
		
		$assigned_file_title_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency($file_title_id);
		$b_vendor_currency_code = Vtiger_CurrencyList_UIType::getDisplayValue($b_vendor_currency);
		if($assigned_file_title_currency =='KZT')
		{					
			$bb_exchange_rate  		= exchange_rate_currency($b_invoice_date_format, $b_vendor_currency_code);			
			
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
										'job_id'  			=> 0,
										'date_added'		=> date('Y-m-d H:i:s'),
										'b_job_charges_id' 	=> $b_job_charges_id,
										'b_office_id' 		=> $b_office_id,
										'b_department_id' 	=> $b_department_id,
										'b_pay_to_id' 		=> $b_pay_to_id,
										'b_invoice_number'   => $b_invoice_number,
										'b_invoice_date' 	 => $b_invoice_date,
										'b_invoice_due_date' => $b_invoice_due_date,
										'b_type_id'		  => $b_type_id,			
										'b_buy_vendor_currency_net' => $b_buy_vendor_currency_net,
										'b_vat_rate'         => $b_vat_rate,
										'b_vat'              => $b_vat,
										'b_buy_vendor_currency_gross' => $b_buy_vendor_currency_gross,
										'b_vendor_currency'  => $b_vendor_currency,
										'b_exchange_rate'    => $bb_exchange_rate,
										'b_buy_local_currency_gross' => $bb_buy_local_currency_gross,
										'b_buy_local_currency_net'   => $bb_buy_local_currency_net,
										'b_expected_buy_local_currency_net' => (isset($bb_expected_buy_local_currency_net) ? $bb_expected_buy_local_currency_net :'0.00'),
										'b_variation_expected_and_actual_buying' => (isset($bb_variation_expected_and_actual_buying) ? $bb_variation_expected_and_actual_buying: '0.00'),
										'b_gl_account'		=> $b_gl_account,
										'b_ar_gl_account'	 => $b_ap_gl_account,												
									 );
									 
			$assigned_extra_buying['company_id'] = @$file_title_id;
			$assigned_extra_buying['user_id'] 	 = $current_user->getId();
			$assigned_extra_buying['owner_id']   = $current_user->getId();
			$assigned_extra_buying['jrer_buying_id'] = 0;
			
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
			$request->set('jrer_buying_id', $assigned_extra_buying['jrer_buying_id']);
			
			$request->set('sourceModule', 'Fleettrip'); 		
			$recordModel = $this->saveRecord($request);
			$jrer_buying_id_assigned = $recordModel->getId();
			
			$db_expence_up = PearDatabase::getInstance();
			$query_expence_up = 'UPDATE vtiger_jobexpencereport set job_id=?, company_id=?, user_id=?, owner_id=?, b_gl_account=?, b_ar_gl_account=?, jrer_buying_id=?, fleettrip_id=?  where jobexpencereportid=?';
			$params_expence_up = array($assigned_extra_buying['job_id'], $assigned_extra_buying['company_id'], $assigned_extra_buying['user_id'], $assigned_extra_buying['owner_id'], $assigned_extra_buying['b_gl_account'], $assigned_extra_buying['b_ar_gl_account'], $assigned_extra_buying['jrer_buying_id'], $fleet_id_from_expense, $jrer_buying_id_assigned);
			$result_expence_up = $db_expence_up->pquery($query_expence_up, $params_expence_up);
			
			if($fleet_trip_info->get('assigned_user_id')!=$current_user->getId())
			{
				$assigned_extra_buying['user_id'] 	= $current_user->getId();
				$assigned_extra_buying['owner_id']   = $fleet_trip_info->get('assigned_user_id');
				$request->set('user_id', $assigned_extra_buying['user_id']);
				$request->set('owner_id', $assigned_extra_buying['owner_id']);
				$request->set('jrer_buying_id', $jrer_buying_id_assigned);
				//$request->set('record', $fleetrip_jrer_buying['jobexpencereportid']);			
				$recordModel = $this->saveRecord($request);
				$jrer_buying_id_owner = $recordModel->getId();
				
				$db_expence_owner = PearDatabase::getInstance();
				$query_expence_owner = 'UPDATE vtiger_jobexpencereport set job_id=?, company_id=?, user_id=?, owner_id=?, b_gl_account=?, b_ar_gl_account=?, jrer_buying_id=?, fleettrip_id=?  where jobexpencereportid=?';
				$params_expence_owner = array($assigned_extra_buying['job_id'], $assigned_extra_buying['company_id'], $assigned_extra_buying['user_id'], $assigned_extra_buying['owner_id'], $assigned_extra_buying['b_gl_account'], $assigned_extra_buying['b_ar_gl_account'], $jrer_buying_id_assigned, $fleet_id_from_expense, $jrer_buying_id_owner);
				$result_expence_owner = $db_expence_owner->pquery($query_expence_owner, $params_expence_owner);
				
			}
									 		
		
	}
	
	public function buying_fleet($request)
	{
		$extra_expense = array();
		$assigned_extra_expense = array();
		
		$b_job_charges_id = $request->get('cf_1453');
		$b_office_id	  = $request->get('cf_1477');
		$b_department_id  = $request->get('cf_1479');
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
		$vat_included 		= $request->get('cf_3293');
		
		if($vat_included!='VAT Amount')
		{
			$b_vat = '0.00';
			$b_buying_customer_currency_gross = '0.00';
			if(!empty($b_vat_rate) && $b_vat_rate>0)
			{
				if($vat_included=='Yes')
				{
					$b_buying_customer_currency_gross = $request->get('cf_1343');
					$b_vat_rate_ = $b_vat_rate + 100;
					$b_vat_rate_cal = $b_vat_rate_/100; 
					
					$b_buy_vendor_currency_net = 	$b_buying_customer_currency_gross / $b_vat_rate_cal;				
					$b_vat = $b_buying_customer_currency_gross - $b_buy_vendor_currency_net;	
				}
				else
				{
				$b_vat_rate_cal  = $b_vat_rate/100; 
				$b_vat 		     = $b_buy_vendor_currency_net * $b_vat_rate_cal;
				}
			}
		}
		else{
			$b_vat = $request->get('cf_1341');
		}
		//$b_buy_vendor_currency_gross = $b_buy_vendor_currency_net + $b_vat;
		if($vat_included=='Yes')
		{
			$b_buy_vendor_currency_gross = $b_buying_customer_currency_gross;
			//$b_buy_vendor_currency_gross = $b_buy_vendor_currency_net + $b_vat;
		}
		else{
			$b_buy_vendor_currency_gross = $b_buy_vendor_currency_net + $b_vat;
		}
		
		$b_vendor_currency	= $request->get('cf_1345');
		$b_exchange_rate 	  = $request->get('cf_1222');
		
		$job_id 			  = $request->get('sourceRecord');
		$sourceModule 		= $request->get('sourceModule');
		if($sourceModule=='Fleet')
		{
			$job_id = $this->get_job_id_from_fleet($job_id);
			//$job_info = get_job_details($job_id);
			//$request->set('sourceModule', 'Job');
			$sourceModule = 'Job';
		}		
		$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule);
		
		$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info->get('cf_1186'));
		$file_title_currency = $reporting_currency;
		
		include("include/Exchangerate/exchange_rate_class.php");				
		/*
		$invoice_date_arr = explode('-',$b_invoice_date);
		$b_invoice_date_final = @$invoice_date_arr[2].'-'.@$invoice_date_arr[1].'-'.@$invoice_date_arr[0];
		$b_invoice_date_format = date('Y-m-d', strtotime($b_invoice_date_final));
		*/
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
		
		$b_variation_expected_and_actual_buying = $b_expected_buy_local_currency_net - $b_buy_local_currency_net;
		
		$current_user = Users_Record_Model::getCurrentUserModel();
		$current_user_office_id = $current_user->get('location_id');
		
		$sourceModule = $request->get('sourceModule');	
		if($sourceModule=='Fleet')
		{
			$sourceModule = 'Job';
		}	
		$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule);
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
				//$file_title_id = $current_user->get('company_id');
				$file_title_id = $request->get('cf_2191');
				$db = PearDatabase::getInstance();
				$query = 'UPDATE vtiger_jobtask SET sub_jrer_file_title=? WHERE job_id=? and user_id=?';
				$params = array($file_title_id, $job_id, $current_user->getId());
				$db->pquery($query,$params);
				//$this->db->where('job_id', $post->id)->where('user_id', $this->current_user->id)->update('job_user_assigned', array('sub_jrer_file_title' => $file_title_id));
			}			
			
			$office = Vtiger_LocationList_UIType::getDisplayValue($current_user->get('location_id'));
			$department = Vtiger_DepartmentList_UIType::getDepartment($current_user->get('department_id'));			
			
			if($b_type_id=='85793') //For JZ company
			{
				$chartofaccount_name = 'Revenue';
				$link_company_id = '85772';
				$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
			}
			elseif($b_type_id=='85773') // For DLC company
			{
				$chartofaccount_name = 'Revenue';
				$link_company_id = '85756';
				$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
			}
			else{	
				$chartofaccount_name = 'Revenue';
				$link_company_id = $file_title_id;
				$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
			}		
			//Revenue				
			//$b_gl_account = $coa_info['name'].'-'.$office.'-'.$department;
			$b_gl_account = $coa_info.'-'.$office.'-'.$department;
			
			
			if($b_type_id=='85793')
			{
				$chartofaccount_name = 'Trade Creditors';
				$link_company_id = '85772';
				$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);				
			}
			elseif($b_type_id=='85773')
			{
				$chartofaccount_name = 'Trade Creditors';
				$link_company_id = '85756';
				$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
			}
			else{
				$chartofaccount_name = 'Trade Creditors';
				$link_company_id = $file_title_id;
				$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
			}
			//Trade Creditors					
			//$b_ap_gl_account = $ap_coa_info['name'].'-'.$office.'-'.$department;
			$b_ap_gl_account = $ap_coa_info.'-'.$office.'-'.$department;
			
			$assigned_file_title_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency($file_title_id);
			$b_vendor_currency_code = Vtiger_CurrencyList_UIType::getDisplayValue($b_vendor_currency);
			if($assigned_file_title_currency =='KZT')
			{					
				$bb_exchange_rate  		= exchange_rate_currency($b_invoice_date_format, $b_vendor_currency_code);			
				
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
											'b_invoice_number'   => $b_invoice_number,
											'b_invoice_date' 	 => $b_invoice_date,
											'b_invoice_due_date' => $b_invoice_due_date,
											'b_type_id'		  => $b_type_id,			
											'b_buy_vendor_currency_net' => $b_buy_vendor_currency_net,
											'b_vat_rate'         => $b_vat_rate,
											'b_vat'              => $b_vat,
											'b_buy_vendor_currency_gross' => $b_buy_vendor_currency_gross,
											'b_vendor_currency'  => $b_vendor_currency,
											'b_exchange_rate'    => $bb_exchange_rate,
											'b_buy_local_currency_gross' => $bb_buy_local_currency_gross,
											'b_buy_local_currency_net'   => $bb_buy_local_currency_net,
											'b_expected_buy_local_currency_net' => (isset($bb_expected_buy_local_currency_net) ? $bb_expected_buy_local_currency_net :'0.00'),
											'b_variation_expected_and_actual_buying' => (isset($bb_variation_expected_and_actual_buying) ? $bb_variation_expected_and_actual_buying: '0.00'),
											'b_gl_account'		=> $b_gl_account,
											'b_ar_gl_account'	 => $b_ap_gl_account,												
										 );
			
		}
		
		/*
		if($this->current_user->id!=$post->author_id )
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
			else{
				//Its JZ file because we need file title for bank
				$user_file_title_ = $this->user_companies_m->get_user_file_title_byuserid($post->author_id);
				$b_type_id = @$user_file_title_[0]->company_id.'-'.@$b_type_id_arr[1];
			}
		}
		*/
		
		$office = Vtiger_LocationList_UIType::getDisplayValue($job_office_id);
		$department = Vtiger_DepartmentList_UIType::getDepartment($job_department_id);
		
		
		if($b_type_id=='85793')
		{
			$chartofaccount_name = 'Revenue';
			$link_company_id = '85772';
			$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
		}
		elseif($b_type_id=='85773')
		{
			$chartofaccount_name = 'Revenue';
			$link_company_id = '85756';
			$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);			
		}
		else{			
			$chartofaccount_name = 'Revenue';
			$link_company_id = $file_title_id;
			$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);	
		}		
		//For revenue
		//$b_gl_account = $coa_info['name'].'-'.$office.'-'.$department;	
		$b_gl_account = $coa_info.'-'.$office.'-'.$department;
				
		if($b_type_id=='85793')
		{
			$chartofaccount_name = 'Trade Creditors';
			$link_company_id = '85772';
			$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);							
		}
		elseif($b_type_id=='85773')
		{
			$chartofaccount_name = 'Trade Creditors';
			$link_company_id = '85756';
			$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
		}
		else{
			$chartofaccount_name = 'Trade Creditors';
			$link_company_id = $file_title_id;
			$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
		}
		//Trade Creditors					
		//$b_ap_gl_account = $ap_coa_info['name'].'-'.$office.'-'.$department;
		$b_ap_gl_account = $ap_coa_info.'-'.$office.'-'.$department;
		
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
			$assigned_extra_buying['company_id'] = @$file_title_id;
			$assigned_extra_buying['user_id'] 	 = $current_user->getId();
			$assigned_extra_buying['owner_id']   = $current_user->getId();
			$assigned_extra_buying['jrer_buying_id'] = 0;
			
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
			$request->set('jrer_buying_id', $assigned_extra_buying['jrer_buying_id']);
			
			$request->set('sourceModule', 'Fleet'); 		
			$recordModel = $this->saveRecord($request);
			$jrer_buying_id_assigned = $recordModel->getId();
			
			$db_expence_up = PearDatabase::getInstance();
			$query_expence_up = 'UPDATE vtiger_jobexpencereport set job_id=?, company_id=?, user_id=?, owner_id=?, b_gl_account=?, b_ar_gl_account=?, jrer_buying_id=?  where jobexpencereportid=?';
			$params_expence_up = array($assigned_extra_buying['job_id'], $assigned_extra_buying['company_id'], $assigned_extra_buying['user_id'], $assigned_extra_buying['owner_id'], $assigned_extra_buying['b_gl_account'], $assigned_extra_buying['b_ar_gl_account'], $assigned_extra_buying['jrer_buying_id'], $jrer_buying_id_assigned);
			$result_expence_up = $db_expence_up->pquery($query_expence_up, $params_expence_up);
			
		}
		
		$extra_buying['user_id'] = $current_user->getId();
		$extra_buying['jrer_buying_id'] = 0;						
		if(isset($jrer_buying_id_assigned))
		{
			$extra_buying['jrer_buying_id']   = @$jrer_buying_id_assigned;
		}
		
		if($job_info->get('assigned_user_id')!=$current_user->getId())
		{
			//Update Sub JRER Fleet
			
			//End Update Sub JRER Fleet
			
			//Update Main JRER Fleet
			$fleet_id = $request->get('sourceRecord');
			$db_flold = PearDatabase::getInstance();
			$query_fleet = 'SELECT sum(vtiger_jobexpencereportcf.cf_1337) as b_buy_vendor_currency_net, 
								   sum(vtiger_jobexpencereportcf.cf_1339) as b_vat_rate, 
								   sum(vtiger_jobexpencereportcf.cf_1341) as b_vat, 
								   sum(vtiger_jobexpencereportcf.cf_1343) as b_buy_vendor_currency_gross, 
								   sum(vtiger_jobexpencereportcf.cf_1347) as b_buy_local_currency_gross, 
								   sum(vtiger_jobexpencereportcf.cf_1349) as b_buy_local_currency_net, 
								   sum(vtiger_jobexpencereportcf.cf_1351) as b_expected_buy_local_currency_net, 
								   sum(vtiger_jobexpencereportcf.cf_1353) as b_variation_expected_and_actual_buying 
						    FROM vtiger_jobexpencereport 
							INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid 
							INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid 
							WHERE vtiger_crmentityrel.crmid=? 
							AND vtiger_jobexpencereportcf.cf_1457="Expence" ';
			$params_fleet = array($fleet_id);		
			$result_fleet = $db_flold->pquery($query_fleet,$params_fleet);
			$jrer_buying_fleet = $db_flold->fetch_array($result_fleet);
			
			//charge = cf_1453 = Fleet Expense id = 44515
			$db_plus_sub = PearDatabase::getInstance();
			$b_invoice_date_main = date('Y-m-d',strtotime($assigned_extra_buying['b_invoice_date']));
			$query_sub_fleet_update_sub = "UPDATE vtiger_jobexpencereportcf
												   INNER JOIN vtiger_jobexpencereport ON vtiger_jobexpencereport.jobexpencereportid=vtiger_jobexpencereportcf.jobexpencereportid
												   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereportcf.jobexpencereportid  
												   SET 
												   vtiger_jobexpencereportcf.cf_1337 = '".$jrer_buying_fleet['b_buy_vendor_currency_net']."', 
												   vtiger_jobexpencereportcf.cf_1339 = '".$jrer_buying_fleet['b_vat_rate']."', 
												   vtiger_jobexpencereportcf.cf_1341 = '".$jrer_buying_fleet['b_vat']."', 
												   vtiger_jobexpencereportcf.cf_1343 = '".$jrer_buying_fleet['b_buy_vendor_currency_gross']."', 
												   vtiger_jobexpencereportcf.cf_1347 = '".$jrer_buying_fleet['b_buy_local_currency_gross']."',
												   vtiger_jobexpencereportcf.cf_1349 = '".$jrer_buying_fleet['b_buy_local_currency_net']."',
												   vtiger_jobexpencereportcf.cf_1351 = '".$jrer_buying_fleet['b_expected_buy_local_currency_net']."',
												   vtiger_jobexpencereportcf.cf_1353 = '".$jrer_buying_fleet['b_variation_expected_and_actual_buying']."',
												   vtiger_jobexpencereportcf.cf_1345 = '".$assigned_extra_buying['b_vendor_currency']."',
												   vtiger_jobexpencereportcf.cf_1222 = '".$assigned_extra_buying['b_exchange_rate']."',
												   vtiger_jobexpencereportcf.cf_1216 = '".$b_invoice_date_main."'
												   WHERE vtiger_jobexpencereportcf.cf_1453='85900' AND vtiger_crmentityrel.crmid='".$job_id."'
												   AND vtiger_jobexpencereport.fleet_trip_id='".$fleet_id."' ";
			$db_plus_sub->query($query_sub_fleet_update_sub);
			
			//End Update Main JRER Fleet
			
		}
		else{
			$extra_buying['company_id'] = $job_info->get('cf_1186');
			$extra_buying['owner_id'] = $current_user->getId();
			
			
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
			
			/*
			$request->set('b_gl_account', $extra_buying['b_gl_account']);
			$request->set('b_ar_gl_account', $extra_buying['b_ar_gl_account']);
			$request->set('company_id', $extra_buying['company_id']);
			$request->set('user_id', $extra_buying['user_id']);
			$request->set('owner_id', $extra_buying['owner_id']);
			$request->set('jrer_buying_id', $extra_buying['jrer_buying_id']);
			*/
			$request->set('sourceModule', 'Fleet'); 	
			$recordModel = $this->saveRecord($request);
			$jobexpencereportid = $recordModel->getId();
			
			$db_up = PearDatabase::getInstance();
			$query_up = 'UPDATE vtiger_jobexpencereport set job_id=?, b_gl_account=?, b_ar_gl_account=?, company_id=?, user_id=?, owner_id=?, jrer_buying_id=? where jobexpencereportid=?';
			$params_up = array($job_id, $extra_buying['b_gl_account'], $extra_buying['b_ar_gl_account'], $extra_buying['company_id'], $extra_buying['user_id'], $extra_buying['owner_id'], $extra_buying['jrer_buying_id'], $jobexpencereportid);
			$result_up = $db_up->pquery($query_up, $params_up);
			
			
			//Update Main JRER Fleet
			$fleet_id = $request->get('sourceRecord');
			$db_flold = PearDatabase::getInstance();
			$query_fleet = 'SELECT sum(vtiger_jobexpencereportcf.cf_1337) as b_buy_vendor_currency_net, 
								   sum(vtiger_jobexpencereportcf.cf_1339) as b_vat_rate, 
								   sum(vtiger_jobexpencereportcf.cf_1341) as b_vat, 
								   sum(vtiger_jobexpencereportcf.cf_1343) as b_buy_vendor_currency_gross, 
								   sum(vtiger_jobexpencereportcf.cf_1347) as b_buy_local_currency_gross, 
								   sum(vtiger_jobexpencereportcf.cf_1349) as b_buy_local_currency_net, 
								   sum(vtiger_jobexpencereportcf.cf_1351) as b_expected_buy_local_currency_net, 
								   sum(vtiger_jobexpencereportcf.cf_1353) as b_variation_expected_and_actual_buying 
						    FROM vtiger_jobexpencereport 
							INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid 
							INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid 
							WHERE vtiger_crmentityrel.crmid=? 
							AND vtiger_jobexpencereportcf.cf_1457="Expence" ';
			$params_fleet = array($fleet_id);		
			$result_fleet = $db_flold->pquery($query_fleet,$params_fleet);
			$jrer_buying_fleet = $db_flold->fetch_array($result_fleet);
			
			//charge = cf_1453 = Fleet Expense id = 44515
			$db_plus_sub = PearDatabase::getInstance();
			$b_invoice_date_main = date('Y-m-d',strtotime($extra_buying['b_invoice_date']));
			$query_sub_fleet_update_sub = "UPDATE vtiger_jobexpencereportcf
												   INNER JOIN vtiger_jobexpencereport ON vtiger_jobexpencereport.jobexpencereportid=vtiger_jobexpencereportcf.jobexpencereportid
												   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereportcf.jobexpencereportid  
												   SET 
												   vtiger_jobexpencereportcf.cf_1337 = '".$jrer_buying_fleet['b_buy_vendor_currency_net']."', 
												   vtiger_jobexpencereportcf.cf_1339 = '".$jrer_buying_fleet['b_vat_rate']."', 
												   vtiger_jobexpencereportcf.cf_1341 = '".$jrer_buying_fleet['b_vat']."', 
												   vtiger_jobexpencereportcf.cf_1343 = '".$jrer_buying_fleet['b_buy_vendor_currency_gross']."', 
												   vtiger_jobexpencereportcf.cf_1347 = '".$jrer_buying_fleet['b_buy_local_currency_gross']."',
												   vtiger_jobexpencereportcf.cf_1349 = '".$jrer_buying_fleet['b_buy_local_currency_net']."',
												   vtiger_jobexpencereportcf.cf_1351 = '".$jrer_buying_fleet['b_expected_buy_local_currency_net']."',
												   vtiger_jobexpencereportcf.cf_1353 = '".$jrer_buying_fleet['b_variation_expected_and_actual_buying']."',
												   vtiger_jobexpencereportcf.cf_1345 = '".$extra_buying['b_vendor_currency']."',
												   vtiger_jobexpencereportcf.cf_1222 = '".$extra_buying['b_exchange_rate']."',
												   vtiger_jobexpencereportcf.cf_1216 = '".$b_invoice_date_main."'
												   WHERE vtiger_jobexpencereportcf.cf_1453='85900' AND vtiger_crmentityrel.crmid='".$job_id."'
												   AND vtiger_jobexpencereport.fleet_trip_id='".$fleet_id."'  ";
			$db_plus_sub->query($query_sub_fleet_update_sub);
			
			//End Update Main JRER Fleet
		}
		
		$extra_expense[] = $extra_buying;	
		if($job_info->get('assigned_user_id')!=$current_user->getId())
		{	
			$assigned_extra_expense[] = $assigned_extra_buying;
		}
		
		
	}
	
	public function selling($request)
	{
		$assigned_extra_selling = array();
		$s_generate_invoice_instruction_flag = true;
		$invoice_instruction = '';
		
		$job_id 			  = $request->get('sourceRecord');
		$sourceModule 		  = $request->get('sourceModule');	
		$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule);
		
		$current_user = Users_Record_Model::getCurrentUserModel();
		$current_user_office_id = $current_user->get('location_id');
		
		$s_job_charges_id = $request->get('cf_1455');
		if($job_info->get('assigned_user_id')!=$current_user->getId())
		{		
			$s_office_id	  = $current_user->get('location_id');
			//$s_department_id  = $current_user->get('department_id');
			$s_department_id  = $request->get('cf_1479');
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
		$vat_included 		= $request->get('cf_2695');
		
		if($vat_included != 'VAT Amount')
		{
			$s_vat = '0.00';
			$s_selling_customer_currency_gross = '0.00';
			if(!empty($s_vat_rate) && $s_vat_rate>0)
			{
				if($vat_included=='Yes')
				{
					$s_selling_customer_currency_gross = $request->get('cf_1232');
					$s_vat_rate_ = $s_vat_rate + 100;
					$s_vat_rate_cal = $s_vat_rate_/100; 
					
					$s_selling_customer_currency_net = 	$s_selling_customer_currency_gross / $s_vat_rate_cal;				
					$s_vat = $s_selling_customer_currency_gross - $s_selling_customer_currency_net;
					
				}
				else{
				$s_vat_rate_cal = $s_vat_rate/100; 
				$s_vat = 	$s_selling_customer_currency_net * $s_vat_rate_cal;
				}
			}
		}
		else{
			$s_vat 		  = $request->get('cf_1230'); // VAT Amount
		}
		
		//$s_sell_customer_currency_gross = $s_selling_customer_currency_net + $s_vat;
		if($vat_included=='Yes')
		{
			$s_sell_customer_currency_gross = $s_selling_customer_currency_gross;
			//$s_sell_customer_currency_net = $s_selling_customer_currency_net; 
		}
		else{
			$s_sell_customer_currency_gross = $s_selling_customer_currency_net + $s_vat;
		}
		
		$s_customer_currency = $request->get('cf_1234');
		
		$job_id 			  = $request->get('sourceRecord');
		$sourceModule 		  = $request->get('sourceModule');	
		$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule);
		
		$job_office_id = $job_info->get('cf_1188');
		$job_department_id = $job_info->get('cf_1190');
		
		$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info->get('cf_1186'));
		$file_title_currency = $reporting_currency;
		include("include/Exchangerate/exchange_rate_class.php");
		
		$invoice_date_arr = explode('-',$s_invoice_date);
		$s_invoice_date_final = @$invoice_date_arr[2].'-'.@$invoice_date_arr[1].'-'.@$invoice_date_arr[0];
		$s_invoice_date_format = date('Y-m-d', strtotime($s_invoice_date_final));
		
		//$s_invoice_date_format = date('Y-m-d', strtotime($s_invoice_date));
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
		
		if($file_title_currency !='USD')
		{	
			$s_sell_local_currency_net = $s_selling_customer_currency_net * $s_exchange_rate;
		}else{
			$s_sell_local_currency_net = exchange_rate_convert($s_customer_currency_code, $file_title_currency,$s_selling_customer_currency_net, $s_invoice_date_format);
		}
		
		$s_expected_sell_local_currency_net = $request->get('cf_1242');				
		$ss_expected_sell_local_currency_net = $request->get('cf_1242');
		
		$s_variation_expected_and_actual_sellling  = $s_sell_local_currency_net - $s_expected_sell_local_currency_net;
		$ss_variation_expected_and_actual_sellling = $s_sell_local_currency_net - $ss_expected_sell_local_currency_net;
		
		if($job_info->get('assigned_user_id')!=$current_user->getId())
		{
			
			$db_buying_exp = PearDatabase::getInstance();
			$jrer_buying_exp = 'SELECT * FROM vtiger_jobexpencereport
								   INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid	
								   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid
								   where vtiger_crmentityrel.crmid=? and vtiger_jobexpencereportcf.cf_1455=? and vtiger_jobexpencereportcf.cf_1477=? 
								   and vtiger_jobexpencereportcf.cf_1479=?  and vtiger_jobexpencereport.owner_id =? and vtiger_jobexpencereport.selling_expence="no"
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
		$gl_account = @$coa_info['name'].'-'.$office.'-'.$department;
		
		
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
				//$file_title_id = $current_user->get('company_id');
				$file_title_id = $request->get('cf_2191');
				$db = PearDatabase::getInstance();
				$query = 'UPDATE vtiger_jobtask SET sub_jrer_file_title=? WHERE job_id=? and user_id=?';
				$params = array($file_title_id, $job_id, $current_user->getId());
				$db->pquery($query,$params);
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
		
		
		$extra_selling['user_id'] = $current_user->getId();
		$extra_selling['owner_id'] = $current_user->getId();							  			
		
		if($job_info->get('assigned_user_id')!=$current_user->getId())
		{		
			$extra_selling['company_id'] = $file_title_id;
			$extra_selling['s_jrer_buying_id'] = 0;
						
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
			
			//$request->set('gl_account', $extra_selling['gl_account']);
			//$request->set('ar_gl_account', $extra_selling['ar_gl_account']);
			//$request->set('company_id', $extra_selling['company_id']);
			//$request->set('user_id', $extra_selling['user_id']);
			//$request->set('owner_id', $extra_selling['owner_id']);
			//$request->set('s_jrer_buying_id', $extra_selling['s_jrer_buying_id']);
			
			
			$recordModel = $this->saveRecord($request);
			$jrer_selling_id_assigned = $recordModel->getId();
			
			
			$db_up = PearDatabase::getInstance();
			$query_up = 'UPDATE vtiger_jobexpencereport set job_id=?, gl_account=?, ar_gl_account=?, company_id=?, user_id=?, owner_id=?, s_jrer_buying_id=? where jobexpencereportid=?';
			$params_up = array($job_id, $extra_selling['gl_account'], $extra_selling['ar_gl_account'], $extra_selling['company_id'], $extra_selling['user_id'], $extra_selling['owner_id'], $extra_selling['s_jrer_buying_id'], $jrer_selling_id_assigned);
			$result_up = $db_up->pquery($query_up, $params_up);	
			
			
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
			
			//$request->set('gl_account', $extra_selling_normal['gl_account']);
			//$request->set('ar_gl_account', $extra_selling_normal['ar_gl_account']);
			//$request->set('company_id', $extra_selling_normal['company_id']);
			//$request->set('user_id', $extra_selling_normal['user_id']);
			//$request->set('owner_id', $extra_selling_normal['owner_id']);
			///$request->set('s_jrer_buying_id', $extra_selling_normal['s_jrer_buying_id']);
				
			$recordModel = $this->saveRecord($request);
			$jobexpencereportid = $recordModel->getId();
			
			$db_up = PearDatabase::getInstance();
			$query_up = 'UPDATE vtiger_jobexpencereport set job_id=?, gl_account=?, ar_gl_account=?, company_id=?, user_id=?, owner_id=?, s_jrer_buying_id=? where jobexpencereportid=?';
			$params_up = array($job_id, $extra_selling_normal['gl_account'], $extra_selling_normal['ar_gl_account'], $extra_selling_normal['company_id'], $extra_selling_normal['user_id'], $extra_selling_normal['owner_id'], $extra_selling_normal['s_jrer_buying_id'], $jobexpencereportid);
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
					//$pay_to_info = $this->db->select('id')->where('internal_company_id', @$file_title_id)->get('customer')->row();
					
					$db_customer = PearDatabase::getInstance();				
					$query_pay_to_info = 'SELECT * FROM vtiger_accountscf where cf_1703=?';
					$params_pay_to_info = array($file_title_id);
					$result_pay_to_info = $db_customer->pquery($query_pay_to_info,$params_pay_to_info);
					$pay_to_info = $db_customer->fetch_array($result_pay_to_info);					
					
					if($job_info->get('assigned_user_id')!=$current_user->getId())
					{	
						//$b_type_id = $post->file_title.'-bank';
						$db_company_type = PearDatabase::getInstance();
						$query_company_type = 'select * from vtiger_companyaccounttype 
												INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_companyaccounttype.companyaccounttypeid
												where vtiger_crmentityrel.crmid=? AND vtiger_companyaccounttype.name like "Bank%"';
						$params_company_type = array($job_info->get('cf_1186'));
						$result_company_type = $db_company_type->pquery($query_company_type, $params_company_type);
						$company_type_info = $db_company_type->fetch_array($result_company_type);
						$b_type_id = $company_type_info['companyaccounttypeid'];
						//need to discuss
					}					
				
					$db_assigned_jrer_buying_count = PearDatabase::getInstance();
					$jrer_assigned_jrer_buying_count = 'SELECT * FROM vtiger_jobexpencereport
										   INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid	
										   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid
										   where vtiger_crmentityrel.crmid=? and vtiger_jobexpencereportcf.cf_1455=? and vtiger_jobexpencereportcf.cf_1477=? 
										   and vtiger_jobexpencereportcf.cf_1479=? and vtiger_jobexpencereport.selling_expence="yes" 
										   and vtiger_jobexpencereportcf.cf_1457="Expence" ';
					$params_assigned_jrer_buying_count = array($selling['job_id'], 
										  $selling['s_job_charges_id'], 
										  $selling['s_office_id'], 
										  $selling['s_department_id'],								      
										  );
										  
					$result_assigned_jrer_buying_count = $db_assigned_jrer_buying_count->pquery($jrer_assigned_jrer_buying_count,$params_assigned_jrer_buying_count);
				
					$assigned_extra_buying_cost = array(
														'date_added'	   => date('Y-m-d H:i:s'),
														'user_id'		   => $current_user->getId(),
														'job_id'  		   => $selling['job_id'],
														'date_added'	   => date('Y-m-d H:i:s'),
														'b_job_charges_id' => $selling['s_job_charges_id'],
														'b_office_id' 	   => $selling['s_office_id'],
														'b_department_id'  => $selling['s_department_id'],															
														'b_pay_to_id' 	   => $pay_to_info->id,														
														'b_invoice_date'   => $selling['s_invoice_date'],
														'b_invoice_due_date'=> date('Y-m-d'),
														'company_id' 	   => $job_info->get('cf_1186'),
														'owner_id' 		   => $job_info->get('assigned_user_id'),														
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
						$assigned_extra_buying_cost['b_gl_account']	 = $selling['gl_account'];
						$assigned_extra_buying_cost['b_ar_gl_account']  = $selling['ar_gl_account'];
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
						
						$recordModel = $this->saveRecord($request);
						$jobexpencereportid = $recordModel->getId();
						
						
						$db_up = PearDatabase::getInstance();
						$query_up = 'UPDATE vtiger_jobexpencereport set job_id=?, b_gl_account=?, b_ar_gl_account=?, company_id=?, user_id=?, owner_id=?, selling_expence=?,  where jobexpencereportid=?';
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
	
	public function buying($request)
	{
		$extra_expense = array();
		$assigned_extra_expense = array();
		
		$b_job_charges_id = $request->get('cf_1453');
		$b_office_id	  = $request->get('cf_1477');
		$b_department_id  = $request->get('cf_1479');
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
		$vat_included 		= $request->get('cf_3293');
		
		if($vat_included!='VAT Amount')
		{
			$b_vat = '0.00';
			$b_buying_customer_currency_gross = '0.00';
			if(!empty($b_vat_rate) && $b_vat_rate>0)
			{
				if($vat_included=='Yes')
				{
					$b_buying_customer_currency_gross = $request->get('cf_1343');
					$b_vat_rate_ = $b_vat_rate + 100;
					$b_vat_rate_cal = $b_vat_rate_/100; 
					
					$b_buy_vendor_currency_net = 	$b_buying_customer_currency_gross / $b_vat_rate_cal;				
					$b_vat = $b_buying_customer_currency_gross - $b_buy_vendor_currency_net;	
				}
				else
				{
				$b_vat_rate_cal  = $b_vat_rate/100; 
				$b_vat 		     = $b_buy_vendor_currency_net * $b_vat_rate_cal;
				}
			}
		}
		else
		{
			$b_vat	  	  = $request->get('cf_1341');
		}
		
		//$b_buy_vendor_currency_gross = $b_buy_vendor_currency_net + $b_vat;
		if($vat_included=='Yes')
		{
			$b_buy_vendor_currency_gross = $b_buying_customer_currency_gross;
			//$b_buy_vendor_currency_gross = $b_buy_vendor_currency_net + $b_vat;
		}
		else{
			$b_buy_vendor_currency_gross = $b_buy_vendor_currency_net + $b_vat;
		}
		
		
		
		$b_vendor_currency	= $request->get('cf_1345');
		$b_exchange_rate 	  = $request->get('cf_1222');
		
		$job_id 			  = $request->get('sourceRecord');
		$sourceModule 		  = $request->get('sourceModule');	
		$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule);
		
		$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info->get('cf_1186'));
		$file_title_currency = $reporting_currency;
		include("include/Exchangerate/exchange_rate_class.php");
				
		/*
		$invoice_date_arr = explode('-',$b_invoice_date);
		$b_invoice_date_final = @$invoice_date_arr[2].'-'.@$invoice_date_arr[1].'-'.@$invoice_date_arr[0];
		$b_invoice_date_format = date('Y-m-d', strtotime($b_invoice_date_final));
		*/
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
		
		$b_variation_expected_and_actual_buying = $b_expected_buy_local_currency_net - $b_buy_local_currency_net;
		
		$current_user = Users_Record_Model::getCurrentUserModel();
		$current_user_office_id = $current_user->get('location_id');
		
		$sourceModule = $request->get('sourceModule');		
		$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule);
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
				//$file_title_id = $current_user->get('company_id');
				$file_title_id = $request->get('cf_2191');
				$db = PearDatabase::getInstance();
				$query = 'UPDATE vtiger_jobtask SET sub_jrer_file_title=? WHERE job_id=? and user_id=?';
				$params = array($file_title_id, $job_id, $current_user->getId());
				$db->pquery($query,$params);
				//$this->db->where('job_id', $post->id)->where('user_id', $this->current_user->id)->update('job_user_assigned', array('sub_jrer_file_title' => $file_title_id));
			}			
			
			$office = Vtiger_LocationList_UIType::getDisplayValue($current_user->get('location_id'));
			$department = Vtiger_DepartmentList_UIType::getDepartment($current_user->get('department_id'));			
			
			if($b_type_id=='85793') //For JZ company
			{
				$chartofaccount_name = 'Revenue';
				$link_company_id = '85772';
				$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
			}
			elseif($b_type_id=='85773') // For DLC company
			{
				$chartofaccount_name = 'Revenue';
				$link_company_id = '85756';
				$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
			}
			else{	
				$chartofaccount_name = 'Revenue';
				$link_company_id = $file_title_id;
				$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
			}		
			//Revenue				
			//$b_gl_account = $coa_info['name'].'-'.$office.'-'.$department;
			$b_gl_account = $coa_info.'-'.$office.'-'.$department;
			
			
			if($b_type_id=='85793')
			{
				$chartofaccount_name = 'Trade Creditors';
				$link_company_id = '85772';
				$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);				
			}
			elseif($b_type_id=='85773')
			{
				$chartofaccount_name = 'Trade Creditors';
				$link_company_id = '85756';
				$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
			}
			else{
				$chartofaccount_name = 'Trade Creditors';
				$link_company_id = $file_title_id;
				$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
			}
			//Trade Creditors					
			//$b_ap_gl_account = $ap_coa_info['name'].'-'.$office.'-'.$department;
			$b_ap_gl_account = $ap_coa_info.'-'.$office.'-'.$department;
			
			$assigned_file_title_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency($file_title_id);
			$b_vendor_currency_code = Vtiger_CurrencyList_UIType::getDisplayValue($b_vendor_currency);
			if($assigned_file_title_currency =='KZT')
			{					
				$bb_exchange_rate  		= exchange_rate_currency($b_invoice_date_format, $b_vendor_currency_code);			
				
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
											'b_invoice_number'   => $b_invoice_number,
											'b_invoice_date' 	 => $b_invoice_date,
											'b_invoice_due_date' => $b_invoice_due_date,
											'b_type_id'		  => $b_type_id,			
											'b_buy_vendor_currency_net' => $b_buy_vendor_currency_net,
											'b_vat_rate'         => $b_vat_rate,
											'b_vat'              => $b_vat,
											'b_buy_vendor_currency_gross' => $b_buy_vendor_currency_gross,
											'b_vendor_currency'  => $b_vendor_currency,
											'b_exchange_rate'    => $bb_exchange_rate,
											'b_buy_local_currency_gross' => $bb_buy_local_currency_gross,
											'b_buy_local_currency_net'   => $bb_buy_local_currency_net,
											'b_expected_buy_local_currency_net' => (isset($bb_expected_buy_local_currency_net) ? $bb_expected_buy_local_currency_net :'0.00'),
											'b_variation_expected_and_actual_buying' => (isset($bb_variation_expected_and_actual_buying) ? $bb_variation_expected_and_actual_buying: '0.00'),
											'b_gl_account'		=> $b_gl_account,
											'b_ar_gl_account'	 => $b_ap_gl_account,												
										 );
			
		}
		
		/*
		if($this->current_user->id!=$post->author_id )
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
			else{
				//Its JZ file because we need file title for bank
				$user_file_title_ = $this->user_companies_m->get_user_file_title_byuserid($post->author_id);
				$b_type_id = @$user_file_title_[0]->company_id.'-'.@$b_type_id_arr[1];
			}
		}
		*/
		
		$office = Vtiger_LocationList_UIType::getDisplayValue($job_office_id);
		$department = Vtiger_DepartmentList_UIType::getDepartment($job_department_id);
		
		
		if($b_type_id=='85793')
		{
			$chartofaccount_name = 'Revenue';
			$link_company_id = '85772';
			$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
		}
		elseif($b_type_id=='85773')
		{
			$chartofaccount_name = 'Revenue';
			$link_company_id = '85756';
			$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);			
		}
		else{			
			$chartofaccount_name = 'Revenue';
			$link_company_id = $file_title_id;
			$coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);	
		}		
		//For revenue
		//$b_gl_account = $coa_info['name'].'-'.$office.'-'.$department;	
		$b_gl_account = $coa_info.'-'.$office.'-'.$department;
				
		if($b_type_id=='85793')
		{
			$chartofaccount_name = 'Trade Creditors';
			$link_company_id = '85772';
			$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);							
		}
		elseif($b_type_id=='85773')
		{
			$chartofaccount_name = 'Trade Creditors';
			$link_company_id = '85756';
			$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
		}
		else{
			$chartofaccount_name = 'Trade Creditors';
			$link_company_id = $file_title_id;
			$ap_coa_info = $this->get_company_account_id($chartofaccount_name, $link_company_id);
		}
		//Trade Creditors					
		//$b_ap_gl_account = $ap_coa_info['name'].'-'.$office.'-'.$department;
		$b_ap_gl_account = $ap_coa_info.'-'.$office.'-'.$department;
		
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
			$assigned_extra_buying['company_id'] = @$file_title_id;
			$assigned_extra_buying['user_id'] 	 = $current_user->getId();
			$assigned_extra_buying['owner_id']   = $current_user->getId();
			$assigned_extra_buying['jrer_buying_id'] = 0;
			
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
			$request->set('jrer_buying_id', $assigned_extra_buying['jrer_buying_id']);
			
					
			$recordModel = $this->saveRecord($request);
			$jrer_buying_id_assigned = $recordModel->getId();
			
			$db_expence_up = PearDatabase::getInstance();
			$query_expence_up = 'UPDATE vtiger_jobexpencereport set job_id=?, company_id=?, user_id=?, owner_id=?, b_gl_account=?, b_ar_gl_account=?, jrer_buying_id=?  where jobexpencereportid=?';
			$params_expence_up = array($assigned_extra_buying['job_id'], $assigned_extra_buying['company_id'], $assigned_extra_buying['user_id'], $assigned_extra_buying['owner_id'], $assigned_extra_buying['b_gl_account'], $assigned_extra_buying['b_ar_gl_account'], $assigned_extra_buying['jrer_buying_id'], $jrer_buying_id_assigned);
			$result_expence_up = $db_expence_up->pquery($query_expence_up, $params_expence_up);
			
		}
		
		$extra_buying['user_id'] = $current_user->getId();
		$extra_buying['jrer_buying_id'] = 0;						
		if(isset($jrer_buying_id_assigned))
		{
			$extra_buying['jrer_buying_id']   = @$jrer_buying_id_assigned;
		}
		
		if($job_info->get('assigned_user_id')!=$current_user->getId())
		{
			$db_count = PearDatabase::getInstance();
			$query_count = 'SELECT * FROM vtiger_jobexpencereport
						  INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid	
						 INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid
						 where vtiger_crmentityrel.crmid=? and vtiger_jobexpencereportcf.cf_1453=? and vtiger_jobexpencereportcf.cf_1477=? 
						 	   and vtiger_jobexpencereportcf.cf_1479=? and vtiger_jobexpencereport.owner_id=? and vtiger_jobexpencereport.jrer_buying_id=?';
			$params_count = array($job_id, 
			  					  $extra_buying['b_job_charges_id'], 
								  $extra_buying['b_office_id'], 
								  $extra_buying['b_department_id'],
								  $job_info->get('assigned_user_id'), 
								  $jrer_buying_id_assigned);
			$result_count = $db_count->pquery($query_count,$params_count);
			//$jrer_buying_main_owner_expense_count = $db_count->fetch_array($result_count);
			if($db_count->num_rows($result_count)==0)
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
				$request->set('user_id', $extra_buying['user_id']);
				$request->set('owner_id', $extra_buying['owner_id']);
				$request->set('jrer_buying_id', $extra_buying['jrer_buying_id']);
				
				$recordModel = $this->saveRecord($request);
				$jrer_buying_id_jobexpencereportid = $recordModel->getId();
				
				$db_expence_up_1 = PearDatabase::getInstance();
				$query_expence_up_1 = 'UPDATE vtiger_jobexpencereport set job_id=?, company_id=?, user_id=?, owner_id=?, b_gl_account=?, b_ar_gl_account=?, jrer_buying_id=?  where jobexpencereportid=?';
				$params_expence_up_1 = array($extra_buying['job_id'], $extra_buying['company_id'], $extra_buying['user_id'], $extra_buying['owner_id'], $extra_buying['b_gl_account'], $extra_buying['b_ar_gl_account'], $extra_buying['jrer_buying_id'], $jrer_buying_id_jobexpencereportid);
				$result_expence_up_1 = $db_expence_up_1->pquery($query_expence_up_1, $params_expence_up_1);
				
			}
			else{
				$db_duplicate = PearDatabase::getInstance();
				$query_duplicate = "update vtiger_jobexpencereport, vtiger_jobexpencereportcf set vtiger_jobexpencereportcf.cf_1477=?, vtiger_jobexpencereportcf.cf_1479=?
									vtiger_jobexpencereportcf.cf_1453=?, vtiger_jobexpencereportcf.cf_1367=?, 
									vtiger_jobexpencereportcf.cf_1212=?, vtiger_jobexpencereportcf.cf_1216=?,
									vtiger_jobexpencereportcf.cf_1210=?, vtiger_jobexpencereportcf.cf_1214=?,
									vtiger_jobexpencereportcf.cf_1337=?, vtiger_jobexpencereportcf.cf_1339=?,
									vtiger_jobexpencereportcf.cf_1341=?, vtiger_jobexpencereportcf.cf_1343=?,
									vtiger_jobexpencereportcf.cf_1345=?, vtiger_jobexpencereportcf.cf_1222=?,
									vtiger_jobexpencereportcf.cf_1347=?, vtiger_jobexpencereportcf.cf_1349=?,
									vtiger_jobexpencereportcf.cf_1351=?, vtiger_jobexpencereportcf.cf_1353=?,
									vtiger_jobexpencereport.b_gl_account=?, vtiger_jobexpencereport.b_ar_gl_account=?,
									vtiger_jobexpencereport.user_id=?, vtiger_jobexpencereport.jrer_buying_id=?
								 	where vtiger_jobexpencereport.jobexpencereportid=vtiger_jobexpencereportcf.jobexpencereportid
								 	   AND vtiger_jobexpencereport.job_id=? AND vtiger_jobexpencereportcf.cf_1453=? AND vtiger_jobexpencereportcf.cf_1477=? 
									   AND vtiger_jobexpencereportcf.cf_1479 AND vtiger_jobexpencereport.owner_id=?";
				$params_duplicate = array($extra_buying['b_office_id'],
										  $extra_buying['b_department_id'],
										  $extra_buying['b_job_charges_id'],
										  $extra_buying['b_pay_to_id'],
										  $extra_buying['b_invoice_number'],
										  date('Y-m-d',strtotime($extra_buying['b_invoice_date'])),
									  	  date('Y-m-d',strtotime($extra_buying['b_invoice_due_date'])),
										  $extra_buying['b_type_id'],
										  $extra_buying['b_buy_vendor_currency_net'],
										  $extra_buying['b_vat_rate'],
										  $extra_buying['b_vat'],
										  $extra_buying['b_buy_vendor_currency_gross'], 
										  $extra_buying['b_vendor_currency'], 
										  $extra_buying['b_exchange_rate'], 
										  $extra_buying['b_buy_local_currency_gross'], 
										  $extra_buying['b_buy_local_currency_net'], 
										  $extra_buying['b_expected_buy_local_currency_net'], 
									      $extra_buying['b_variation_expected_and_actual_buying'], 
										  $extra_buying['b_gl_account'], 
										  $extra_buying['b_ar_gl_account'],
										  $extra_buying['user_id'], 
										  $extra_buying['jrer_buying_id'],
										  
										  	$job_id, 
										  	$extra_buying['b_job_charges_id'], 
										  	$extra_buying['b_office_id'], 
										  	$extra_buying['b_department_id'],
										  	$job_info->get('assigned_user_id')
										  );
				$result_duplicate = $db_duplicate->pquery($query_duplicate,$params_duplicate);				
			}
			
		}
		else{
			$extra_buying['company_id'] = $job_info->get('cf_1186');
			$extra_buying['owner_id'] = $current_user->getId();
			
			
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
			
			/*
			$request->set('b_gl_account', $extra_buying['b_gl_account']);
			$request->set('b_ar_gl_account', $extra_buying['b_ar_gl_account']);
			$request->set('company_id', $extra_buying['company_id']);
			$request->set('user_id', $extra_buying['user_id']);
			$request->set('owner_id', $extra_buying['owner_id']);
			$request->set('jrer_buying_id', $extra_buying['jrer_buying_id']);
			*/
			
			$recordModel = $this->saveRecord($request);
			$jobexpencereportid = $recordModel->getId();
			
			$db_up = PearDatabase::getInstance();
			$query_up = 'UPDATE vtiger_jobexpencereport set job_id=?, b_gl_account=?, b_ar_gl_account=?, company_id=?, user_id=?, owner_id=?, jrer_buying_id=? where jobexpencereportid=?';
			$params_up = array($job_id, $extra_buying['b_gl_account'], $extra_buying['b_ar_gl_account'], $extra_buying['company_id'], $extra_buying['user_id'], $extra_buying['owner_id'], $extra_buying['jrer_buying_id'], $jobexpencereportid);
			$result_up = $db_up->pquery($query_up, $params_up);	
		}
		
		//$extra_expense[] = $extra_buying;	
		$extra_expense[] = array();	
		if($job_info->get('assigned_user_id')!=$current_user->getId())
		{	
			//$assigned_extra_expense[] = $assigned_extra_buying;
			$assigned_extra_expense[] = array();
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
						
						$recordModel = $this->saveRecord($request);
						$jobexpencereportid = $recordModel->getId();
						
						$db_sell_owner = PearDatabase::getInstance(); 
						$query_sell_owner = 'UPDATE vtiger_crmentity set smcreatorid=?, smownerid=? where crmid=?';
						$params_sell_owner = array($job_info->get('assigned_user_id'), $job_info->get('assigned_user_id'), $jobexpencereportid);
						$result_sell_owner = $db_sell_owner->pquery($query_sell_owner, $params_sell_owner);
						
						
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
				$result_bill_to_info = $db_customer->pquery($query_bill_to_info,$params_bill_to_info);
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
	
	public function get_company_account_id($chartofaccount_name, $link_company_id)
	{
		$db_revenue_trade = PearDatabase::getInstance();
		$query_revenue_trade = 'SELECT * FROM vtiger_chartofaccount where name=?';
		$params_revenue_trade = array($chartofaccount_name);
		$result_revenue_trade = $db_revenue_trade->pquery($query_revenue_trade,$params_revenue_trade);
		$job_charges_id = $db_revenue_trade->fetch_array($result_revenue_trade);
		
		$db_coa = PearDatabase::getInstance();
		$query_coa = 'SELECT * FROM vtiger_companyaccount
					  INNER JOIN vtiger_companyaccountcf ON vtiger_companyaccountcf.companyaccountid = vtiger_companyaccount.companyaccountid
					  INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_companyaccount.companyaccountid						  
					  where vtiger_crmentityrel.crmid=? and vtiger_companyaccountcf.cf_1501=?';
		$params_coa = array($link_company_id, $job_charges_id['chartofaccountid']);
		$result_coa = $db_coa->pquery($query_coa, $params_coa);
		$coa_info = $db_coa->fetch_array($result_coa);
		return $coa_info['name'];
	}

	/**
	 * Function to get the record model based on the request parameters
	 * @param Vtiger_Request $request
	 * @return Vtiger_Record_Model or Module specific Record Model instance
	 */
	public function getRecordModelFromRequest(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$recordId = $request->get('record');

		if(!empty($recordId)) {
			$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
			$recordModel->set('id', $recordId);
			$recordModel->set('mode', 'edit');

			$fieldModelList = $recordModel->getModule()->getFields();
			foreach ($fieldModelList as $fieldName => $fieldModel) {
				$fieldValue = $fieldModel->getUITypeModel()->getUserRequestValue($recordModel->get($fieldName));

				if ($fieldName === $request->get('field')) {
					$fieldValue = $request->get('value');
				}
                $fieldDataType = $fieldModel->getFieldDataType();
                if ($fieldDataType == 'time') {
					$fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
				}
				if ($fieldValue !== null) {
					if (!is_array($fieldValue)) {
						$fieldValue = trim($fieldValue);
					}
					$recordModel->set($fieldName, $fieldValue);
				}
				$recordModel->set($fieldName, $fieldValue);
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
				$fieldDataType = $fieldModel->getFieldDataType();
				if ($fieldDataType == 'time') {
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
