<?php
	chdir(dirname(__FILE__) . '/../..');
	include_once 'vtlib/Vtiger/Module.php';
	include_once 'includes/main/WebUI.php';
	include_once 'include/Webservices/Utils.php';
	require_once('exchange_rate.php'); 
    set_time_limit(0);
	date_default_timezone_set("UTC");	
	ini_set('memory_limit','64M');
	global $adb;
  $buy_vendor_currency_gross =array();
  
  //$file_title = 'KZT';
  $file_title_ = @$_REQUEST['sub_file_title'];
  $jobexp_id = @$_REQUEST['job_id'];
  $job_id = $jobexp_id;
  $row_jrer = array();
  if(empty($jobexp_id))
  {
	  $record_id = @$_REQUEST['record_id'];
	 /*		  
	  $sql = $adb->pquery('SELECT crmentityrel_2.crmid as job_id, crmentityrel_1.crmid as jobexp_id from vtiger_jobexpencereportcf as jobexpencereportcf 
	  		  INNER JOIN vtiger_crmentityrel as crmentityrel_1 ON crmentityrel_1.relcrmid=jobexpencereportcf.jobexpencereportid AND crmentityrel_1.relmodule="Jobexpencereport" 
			  INNER JOIN vtiger_crmentityrel as crmentityrel_2 ON crmentityrel_1.crmid = crmentityrel_2.relcrmid AND crmentityrel_2.relmodule="Jobexpencereport" 
			  where crmentityrel_1.relcrmid="'.$record_id.'" and crmentityrel_2.module="Job" limit 1');
	 */
	  $sql = $adb->pquery("SELECT crmid FROM `vtiger_crmentityrel` where relcrmid='".$record_id."' and relmodule='Jobexpencereport' and module='Job' limit 1");		  
 	  $row = $adb->fetch_array($sql);
	  $job_id = $row['crmid'];
	  
	  if(empty($job_id)) //in case of fleet
   	  {
		  $sql = $adb->pquery("SELECT rel1.crmid as job_id FROM `vtiger_crmentityrel` as rel1 
							  INNER JOIN vtiger_crmentityrel as rel2 ON rel1.relcrmid = rel2.crmid 
							  where rel2.relcrmid='".$record_id."'");
		   $row = $adb->fetch_array($sql);
		   $job_id = $row['job_id'];					  
	  }
	  
	  $sql_jrer = $adb->pquery("select * from vtiger_jobexpencereport where jobexpencereportid='".$record_id."'");
	  $row_jrer = $adb->fetch_array($sql_jrer);	  
  } 

   //$job_info = get_job_details($job_id);
   $job_info = Vtiger_Record_Model::getInstanceById($job_id, 'Job');

   //$company_reporting_currency = get_company_details(@$file_title_, 'currency_code');   
   $company_reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$file_title_);     
   
   $file_title_currency = $company_reporting_currency;
   
  //$file_title_currency = 'KZT';		
  
  	$b_buy_vendor_currency_gross = $_REQUEST['b_buy_vendor_currency_gross']; 
	$b_vendor_currency = $_REQUEST['b_vendor_currency']; 
	$b_vendor_currency = Vtiger_CurrencyList_UIType::getDisplayValue($b_vendor_currency);
	//$b_vendor_currency = get_currency_code($b_vendor_currency, 'currency_code');
	//$b_invoice_date = $_REQUEST['b_invoice_date'];
	
	$b_invoice_date = ($_REQUEST['b_invoice_date']=='' ? date('Y-m-d') : date('Y-m-d', strtotime($_REQUEST['b_invoice_date'])));
	$b_vat_rate	  = $_REQUEST['b_vat_rate'];
	$b_expected_buy_local_currency_net = $_REQUEST['b_expected_buy_local_currency_net'];
	
	
	//if($file_title_!=$job_info['cf_1186'] && (isset($row_jrer['jrer_buying_id']) && $row_jrer['jrer_buying_id']==''))
	if($file_title_!=$job_info->get('cf_1186'))
	{
		// $job_title_currency = get_company_details(@$job_info['cf_1186'], 'currency_code');  
		$job_title_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info->get('cf_1186')); 
		 if($file_title_currency =='KZT')
		 {
			$b_invoice_date = $b_invoice_date;	
			$_exchrates = exchange_rate($b_invoice_date);			
			$currency_exchange_rate_ex = @$_exchrates[trim($job_title_currency)];
		 }
		 elseif($file_title_currency=='USD')
		 {
			$currency_exchange_rate_ex = currency_rate_convert($job_title_currency, $file_title_currency, 1, $b_invoice_date); 
		 }
		 else{
			$currency_exchange_rate_ex = currency_rate_convert_others($job_title_currency, $file_title_currency, 1, $b_invoice_date); 	
		 }
		 
		 //For costing add separate code
		  $previous_jrer_buying_id = @$row_jrer['jrer_buying_id'];
		  $sql_jrer_pre = $adb->pquery("select * from vtiger_jobexpencereport where jobexpencereportid='".$previous_jrer_buying_id."'");
		  $row_jrer_pre = $adb->fetch_array($sql_jrer_pre);
		  $job_costing_id = @$row_jrer_pre['jerid'];
		
		 if(!empty($job_costing_id))
		 {
			 $sql_jer = $adb->pquery("select vtiger_jercf.cf_1160 as cost_local_currency from vtiger_jercf where jerid='".$job_costing_id."'");
	 		 $row_jer = $adb->fetch_array($sql_jer);
			 $b_expected_buy_local_currency_net = @$row_jer['cost_local_currency'];
		 }
		 
		 // end for costing		 
		 if($file_title_currency !='USD')
		 {	
			$b_expected_buy_local_currency_net = $b_expected_buy_local_currency_net * $currency_exchange_rate_ex;
		 }
		 else{
			$b_expected_buy_local_currency_net = exchange_rate_convert($job_title_currency, $file_title_currency,$b_expected_buy_local_currency_net, $b_invoice_date);
		 } 		 
	}
	
 
	if(!empty($b_buy_vendor_currency_gross))
	{
	    if($file_title_currency =='KZT')
		{	
			$b_invoice_date = $b_invoice_date;	
			$_exchrates = exchange_rate($b_invoice_date);			
			$currency_exchange_rate = @$_exchrates[trim($b_vendor_currency)];
			
		}
		elseif($file_title_currency =='USD')
		{
			$currency_exchange_rate = currency_rate_convert($b_vendor_currency, $file_title_currency, 1, $b_invoice_date);
		}
		else{			
			$currency_exchange_rate = currency_rate_convert_others($b_vendor_currency, $file_title_currency, 1, $b_invoice_date);
		}
		$b_exchange_rate = $currency_exchange_rate;				
		
		$b_vat = '0.00';
		$b_buy_vendor_currency_net = $b_buy_vendor_currency_gross;
		
		if(!empty($b_vat_rate) && $b_vat_rate>0)
		{
			$b_vat_rate_ = $b_vat_rate + 100;
			$b_vat_rate_cal = $b_vat_rate_/100; 
			//$b_vat          = $b_buy_vendor_currency_net * $b_vat_rate_cal;
			$b_buy_vendor_currency_net = 	$b_buy_vendor_currency_gross / $b_vat_rate_cal;
				//$s_selling_customer_currency_net = 
			
			$b_vat = $b_buy_vendor_currency_gross - $b_buy_vendor_currency_net;
			
		}				
		//$b_buy_vendor_currency_gross = $b_buy_vendor_currency_net + $b_vat;	
		$b_buy_vendor_currency_net = $b_buy_vendor_currency_net;
		$b_buy_vendor_currency_gross = $b_buy_vendor_currency_gross;
		
		if($file_title_currency !='USD')
		{	
			//currency gross	
			$b_buy_local_currency_gross = $b_buy_vendor_currency_gross * $b_exchange_rate;
			//currency net
			$b_buy_local_currency_net = $b_buy_vendor_currency_net * $b_exchange_rate;			
		}else{
			//currency gross
			$b_buy_local_currency_gross = exchange_rate_convert($b_vendor_currency, $file_title_currency,$b_buy_vendor_currency_gross, $b_invoice_date);
			//currency net	
			$b_buy_local_currency_net = exchange_rate_convert($b_vendor_currency, $file_title_currency,$b_buy_vendor_currency_net, $b_invoice_date);
		}
		$b_variation_expected_and_actual_buying = $b_expected_buy_local_currency_net - $b_buy_local_currency_net;
		
		$buy_vendor_currency_gross['b_vat']  = number_format($b_vat, 2, '.', '');
		$buy_vendor_currency_gross['b_buy_vendor_currency_net']  = number_format($b_buy_vendor_currency_net, 2, '.', '');			
		$buy_vendor_currency_gross['b_exchange_rate']	 = number_format($b_exchange_rate, 2, '.', '');
		$buy_vendor_currency_gross['b_buy_local_currency_gross']  = number_format($b_buy_local_currency_gross, 2, '.', '');
		$buy_vendor_currency_gross['b_buy_local_currency_net']  = number_format($b_buy_local_currency_net, 2, '.', '');			
		$buy_vendor_currency_gross['b_variation_expected_and_actual_buying']  = (empty($b_expected_buy_local_currency_net)?0:number_format($b_variation_expected_and_actual_buying, 2, '.', ''));
		$buy_vendor_currency_gross['b_expected_buy_local_currency_net'] = (empty($b_expected_buy_local_currency_net)?0:number_format($b_expected_buy_local_currency_net,2,'.',''));
		
	}
   echo json_encode($buy_vendor_currency_gross);
?>