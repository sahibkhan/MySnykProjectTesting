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
    $sell_customer_currency_gross =array();
		
	//$id = $_REQUEST['job_id'];
	//$file_title = $_REQUEST['file_title'];	
	
	//$file_title_currency = $this->db->select('currency')->where('id', $file_title)->get('user_companies')->row();
	 //$job_id = $_REQUEST['job_id'];
	  $file_title_ = @$_REQUEST['sub_file_title'];
	  
	  $jobexp_id = @$_REQUEST['job_id'];
	  if(!empty($jobexp_id))
	  {
		  /*
		  $sql = mysql_query("SELECT * FROM vtiger_crmentityrel where relcrmid='".$jobexp_id."' and module='Job' and relmodule='Jobexpencereport' limit 1");
		  $row = mysql_fetch_array($sql);
		  $job_id = $row['crmid'];
		  */
		  $job_id = $jobexp_id;
	  }
	  else{
		  $record_id = @$_REQUEST['record_id'];
		 /* $sql = mysql_query('SELECT crmentityrel_2.crmid as job_id, crmentityrel_1.crmid as jobexp_id from vtiger_jobexpencereportcf as jobexpencereportcf 
				  INNER JOIN vtiger_crmentityrel as crmentityrel_1 ON crmentityrel_1.relcrmid=jobexpencereportcf.jobexpencereportid AND crmentityrel_1.relmodule="Jobexpencereport" 
				  INNER JOIN vtiger_crmentityrel as crmentityrel_2 ON crmentityrel_1.crmid = crmentityrel_2.relcrmid AND crmentityrel_2.relmodule="Jobexpencereport" 
				  where crmentityrel_1.relcrmid="'.$record_id.'" and crmentityrel_2.module="Job" limit 1');
		  $row = mysql_fetch_array($sql);
		  $job_id = $row['job_id'];
		  */
		  $sql = $adb->pquery("SELECT crmid FROM `vtiger_crmentityrel` where relcrmid='".$record_id."' and relmodule='Jobexpencereport' and module='Job' limit 1");		  
		  $row = $adb->fetch_array($sql);
		  $job_id = $row['crmid'];
	  }
	 
	//$job_info = get_job_details($job_id);
	$job_info = Vtiger_Record_Model::getInstanceById($job_id, 'Job');	
  
	$company_reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$file_title_);
    //$company_reporting_currency = get_company_details(@$file_title_, 'currency_code'); 
   
    $file_title_currency = $company_reporting_currency;
	//$file_title_currency = 'KZT';
	
	$s_selling_customer_currency_gross = $_REQUEST['s_selling_customer_currency_gross']; 
	$s_customer_currency = $_REQUEST['s_customer_currency']; 
	$s_customer_currency = Vtiger_CurrencyList_UIType::getDisplayValue($s_customer_currency);
	//$s_customer_currency = get_currency_code($s_customer_currency, 'currency_code');
	//$b_invoice_date = $_REQUEST['b_invoice_date'];
	$s_invoice_date = ($_REQUEST['s_invoice_date']=='' ? date('Y-m-d') : date('Y-m-d', strtotime($_REQUEST['s_invoice_date'])));
	$s_vat_rate	  = $_REQUEST['s_vat_rate'];
	$s_expected_sell_local_currency_net = $_REQUEST['s_expected_sell_local_currency_net'];
		
	if(!empty($s_selling_customer_currency_gross))
	{
			if($file_title_currency =='KZT')
			{					
				$s_invoice_date = $s_invoice_date;	
				$_exchrates = exchange_rate($s_invoice_date);			
				$currency_exchange_rate = @$_exchrates[trim($s_customer_currency)];
				
			}
			elseif($file_title_currency =='USD')
			{
				$currency_exchange_rate = currency_rate_convert($s_customer_currency, $file_title_currency, 1, $s_invoice_date);
			}
			else{			
				$currency_exchange_rate = currency_rate_convert_others($s_customer_currency, $file_title_currency, 1, $s_invoice_date);
			}
			$s_exchange_rate = $currency_exchange_rate;	
			
			$s_vat = '0.00';
			$s_selling_customer_currency_net = $s_selling_customer_currency_gross;
			if(!empty($s_vat_rate) && $s_vat_rate>0)
			{
				$s_vat_rate_ = $s_vat_rate + 100;
				$s_vat_rate_cal = $s_vat_rate_/100; 
				$s_selling_customer_currency_net = 	$s_selling_customer_currency_gross / $s_vat_rate_cal;
				//$s_selling_customer_currency_net = 
				$s_vat = $s_selling_customer_currency_gross - $s_selling_customer_currency_net;
			}
								
			//$s_sell_customer_currency_gross = $s_selling_customer_currency_net + $s_vat;
			$s_sell_customer_currency_net = $s_selling_customer_currency_net;
			$s_sell_customer_currency_gross = $s_selling_customer_currency_gross;
			
			 if($file_title_currency !='USD')
			 {	
				$s_sell_local_currency_gross = $s_sell_customer_currency_gross * $s_exchange_rate;
				
				$s_sell_local_currency_net = $s_selling_customer_currency_net * $s_exchange_rate;
			 }else{
				$s_sell_local_currency_gross = exchange_rate_convert($s_customer_currency, $file_title_currency,$s_sell_customer_currency_gross,$s_invoice_date);
				
				$s_sell_local_currency_net = exchange_rate_convert($s_customer_currency, $file_title_currency,$s_selling_customer_currency_net, $s_invoice_date);
			 }
			
			$s_variation_expected_and_actual_selling = $s_sell_local_currency_net - $s_expected_sell_local_currency_net;
			
			$sell_customer_currency_gross['s_vat']  = number_format($s_vat, 2, '.', '');
			$sell_customer_currency_gross['s_selll_customer_currency_net']  = number_format($s_sell_customer_currency_net, 2, '.', '');			
			$sell_customer_currency_gross['s_exchange_rate']	 = number_format($s_exchange_rate, 2, '.', '');
			$sell_customer_currency_gross['s_sell_local_currency_gross']  = number_format($s_sell_local_currency_gross, 2, '.', '');
			$sell_customer_currency_gross['s_sell_local_currency_net']  = number_format($s_sell_local_currency_net, 2, '.', '');			
			$sell_customer_currency_gross['s_variation_expected_and_actual_selling']  = number_format($s_variation_expected_and_actual_selling, 2, '.', '');
			echo json_encode($sell_customer_currency_gross);
	}
	 echo FALSE;
  
?>  