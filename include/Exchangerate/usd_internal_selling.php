<?php
	chdir(dirname(__FILE__) . '/../..');
	include_once 'vtlib/Vtiger/Module.php';
	include_once 'includes/main/WebUI.php';
	include_once 'include/Webservices/Utils.php';
    date_default_timezone_set("UTC");	
    require_once('exchange_rate.php');
    
    set_time_limit(0);
  ini_set('memory_limit','64M');
  $usd_internal_selling =array();
  $adb = PearDatabase::getInstance();

  
  $railway_internal_selling_local = $_REQUEST['railway_internal_selling_local']; 
  $pay_to_currency = $_REQUEST['pay_to_currency']; 
  $currency_code_query = 'SELECT currency_code FROM vtiger_currency_info where id=? ';
  $result = $adb->pquery($currency_code_query, array($pay_to_currency));
  $result_currency = $adb->fetch_array($result);
  $pay_to_currency = $result_currency['currency_code'];
 // $pay_to_currency = get_currency_code($pay_to_currency, 'currency_code');
  $internal_selling_date = $_REQUEST['internal_selling_date'];
  
  $b_invoice_date = ($internal_selling_date=='' ? date('Y-m-d') : date('Y-m-d', strtotime($internal_selling_date)));
  
  $file_title_currency = 'USD';
  
   if($file_title_currency =='KZT')
	{	
		$today_date = $b_invoice_date;	
		$_exchrates = exchange_rate($today_date);			
		$new_cost_exchange_rate = @$_exchrates[trim($pay_to_currency)];
	}
	elseif($file_title_currency =='USD')
	{
		$new_cost_exchange_rate = currency_rate_convert($pay_to_currency, $file_title_currency, 1, $b_invoice_date);
	}
	else{			
		$new_cost_exchange_rate = currency_rate_convert_others($pay_to_currency, $file_title_currency, 1, $b_invoice_date);
	}
	$cost_exchange_rate = $new_cost_exchange_rate;
	
	 if(!empty($railway_internal_selling_local))
	{
		if($file_title_currency !='USD')
		{				
			$costlocalcurrency = $railway_internal_selling_local * $cost_exchange_rate;
		}else{
			$costlocalcurrency = exchange_rate_convert($pay_to_currency, $file_title_currency,$railway_internal_selling_local, $b_invoice_date);
		}
		$cost_local_currency['usd_internal_selling']  = number_format($costlocalcurrency, 2, '.', '');
		$cost_local_currency['cost_exchange_rate']	 = number_format($cost_exchange_rate, 2, '.', '');
		
		echo json_encode($cost_local_currency);
	}		
	echo FALSE;
    
?>