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
  $cost_local_currency =array();
  
  $quantity = $_REQUEST['quantity']; 
  $price_per_unit = $_REQUEST['price_per_unit']; 
  $price_per_line  = ($quantity * $price_per_unit);
  $pay_to_currency = $_REQUEST['pay_to_currency'];
  $pay_to_currency = Vtiger_CurrencyList_UIType::getDisplayValue($pay_to_currency); 
  //$pay_to_currency = get_currency_code($pay_to_currency, 'currency_code');
  
  
 
  $file_title_currency = 'KZT';
  
  
  $createdtime = $_REQUEST['date'];	
  $createdtime_ex = date('Y-m-d', strtotime($createdtime));
		
  if($file_title_currency =='KZT')
	{	
		$_exchrates = exchange_rate($createdtime_ex);			
		$new_cost_exchange_rate = @$_exchrates[trim($pay_to_currency)];
	}
	elseif($file_title_currency =='USD')
	{
		$new_cost_exchange_rate = currency_rate_convert($pay_to_currency, $file_title_currency, 1, $createdtime_ex);
	}
	else{			
		$new_cost_exchange_rate = currency_rate_convert_others($pay_to_currency, $file_title_currency, 1, $createdtime_ex);
	}
	$cost_exchange_rate = $new_cost_exchange_rate;
	
	 if(!empty($price_per_line))
	{
		
		$price_per_line = $price_per_line;	
		
		if($file_title_currency !='USD')
		{
			$final_amount = $price_per_line * $cost_exchange_rate;				
			
		}else{
			$final_amount = exchange_rate_convert($pay_to_currency, $file_title_currency,$price_per_line, $createdtime_ex);	
			
		}
		
		if(!empty($createdtime_ex))
		{
			if($file_title_currency!='USD')
			{
				$b_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $createdtime_ex);
			}else{
				$b_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $createdtime_ex);
			}
		}
		
		
		
		
		//$value_in_usd_normal = $costlocalcurrency;	
		$value_in_usd_normal = $final_amount;
		if($file_title_currency!='USD')
		{
			//$value_in_usd_normal = $costlocalcurrency/$b_exchange_rate;
			$value_in_usd_normal = $final_amount/$b_exchange_rate;
		}
		
		
				
		$cost_local_currency['price_per_line']  = number_format($price_per_line, 2, '.', '');
		$cost_local_currency['final_amount']  = number_format($final_amount, 2, '.', '');		
		$cost_local_currency['cost_exchange_rate']	 = number_format($cost_exchange_rate, 2, '.', '');
		//$cost_local_currency['value_in_usd'] =  number_format($value_in_usd_normal,2,'.','');
		
		echo json_encode($cost_local_currency);
	}		
	echo FALSE; 
  
?>