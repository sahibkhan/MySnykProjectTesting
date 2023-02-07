<?php
	chdir(dirname(__FILE__) . '/../..');
	include_once 'vtlib/Vtiger/Module.php';
	include_once 'includes/main/WebUI.php';
	include_once 'include/Webservices/Utils.php';
    date_default_timezone_set("UTC");	
    require_once('exchange_rate.php');

      
  set_time_limit(0);
  ini_set('memory_limit','64M');
  $cost_local_currency =array();
  $adb = PearDatabase::getInstance();
  
  $cost_vendor = $_REQUEST['cost_vendor']; 
  $cost_vat_rate = $_REQUEST['cost_vat_rate'];    
  $pay_to_currency = $_REQUEST['pay_to_currency']; 

  $currency_code_query = 'SELECT currency_code FROM vtiger_currency_info where id=? ';
  $result = $adb->pquery($currency_code_query, array($pay_to_currency));
  $result_currency = $adb->fetch_array($result);
   $pay_to_currency = $result_currency['currency_code'];
  //$pay_to_currency = get_currency_code($pay_to_currency, 'currency_code');
 
  if(!isset($_REQUEST['job_id']))
   {	  
	   $job_costing_id = $_REQUEST['record'];
	   $checkjob =  $adb->pquery("SELECT crmid FROM `vtiger_crmentityrel` where relcrmid=? and relmodule='JER' and module='Job' limit 1", array($job_costing_id));
	   $relation_job = $adb->fetch_array($checkjob);	  
	   $job_id = $relation_job['crmid'];
   }
   else{
	   $job_id = $_REQUEST['job_id'];
	  
   }
  
   //Get Item Transaction Master Recrod
	$sourceModule = 'Job';	
	$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule);
	$job_file_title_id = $job_info->get('cf_1186');
	
	$company_reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency($job_file_title_id);
	//$company_reporting_currency = get_company_details(@$job_info['cf_1186'], 'currency_code');  
  	$file_title_currency = $company_reporting_currency;
  
   if($file_title_currency =='KZT')
	{	
		$today_date = date('Y-m-d');	
		$_exchrates = exchange_rate($today_date);	
		$new_cost_exchange_rate = @$_exchrates[trim($pay_to_currency)];
	}
	elseif($file_title_currency =='USD')
	{
		$new_cost_exchange_rate = currency_rate_convert($pay_to_currency, $file_title_currency, 1, date('Y-m-d'));
	}
	else{			
		$new_cost_exchange_rate = currency_rate_convert_others($pay_to_currency, $file_title_currency, 1, date('Y-m-d'));
	}
	  $cost_exchange_rate = $new_cost_exchange_rate;
	
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
				
		$cost_local_currency['cost_local_currecny']  = number_format($costlocalcurrency, 2, '.', '');
		$cost_local_currency['cost_exchange_rate']	 = number_format($cost_exchange_rate, 2, '.', '');
		$cost_local_currency['cost_local_currecny_gross']	 = number_format($cost_local_currecny_gross, 2, '.', '');
		
		echo json_encode($cost_local_currency);
	}		
	echo FALSE;
?>