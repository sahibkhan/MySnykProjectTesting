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
  $pm_vat_rate	  = $_REQUEST['pm_vat_rate'];
  
  if(!isset($_REQUEST['pmrequisitions_id']))
   {	  
	   $pm_items_id = $_REQUEST['record'];
	   $check_pm_item =  $adb->pquery("SELECT crmid FROM `vtiger_crmentityrel` where relcrmid='".$pm_items_id."' and relmodule='PMItems' and module='PMRequisitions' limit 1");
	   $relation_pm_item = $adb->fetch_array($check_pm_item);
	   $pmrequisitions_id = $relation_pm_item['crmid'];
   }
   else{
  	$pmrequisitions_id = $_REQUEST['pmrequisitions_id'];
   }
   
  $pmrequisitions_info = get_PMRequisitions_details($pmrequisitions_id);
  
  $company_reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$pmrequisitions_info['cf_4271']);
  //$company_reporting_currency = get_company_details(@$pmrequisitions_info['cf_4271'], 'currency_code');  
  $file_title_currency = $company_reporting_currency;
  
  
  $createdtime = @$pmrequisitions_info['createdtime'];	
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
		
		$pm_vat = '0.00';
		if(!empty($pm_vat_rate) && $pm_vat_rate>0)
		{
			$pm_vat_rate_cal = $pm_vat_rate/100; 
			$pm_vat          = $price_per_line * $pm_vat_rate_cal;
		}		
		$price_per_line_gross = $price_per_line + $pm_vat;	
		
		if($file_title_currency !='USD')
		{
			$final_amount_gross = $price_per_line_gross * $cost_exchange_rate;				
			$costlocalcurrency = $price_per_line * $cost_exchange_rate;
			
		}else{
			$final_amount_gross = exchange_rate_convert($pay_to_currency, $file_title_currency,$price_per_line_gross, $createdtime_ex);
			
			$costlocalcurrency = exchange_rate_convert($pay_to_currency, $file_title_currency,$price_per_line, $createdtime_ex);
			
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
		$value_in_usd_normal = $final_amount_gross;
		if($file_title_currency!='USD')
		{
			//$value_in_usd_normal = $costlocalcurrency/$b_exchange_rate;
			$value_in_usd_normal = $final_amount_gross/$b_exchange_rate;
		}
		
		
		
		$cost_local_currency['pm_vat']  = number_format($pm_vat, 2, '.', '');
		$cost_local_currency['price_per_line_gross']  = number_format($price_per_line_gross, 2, '.', '');
		$cost_local_currency['final_amount_gross']  = number_format($final_amount_gross, 2, '.', '');		
		$cost_local_currency['cost_local_currecny']  = number_format($costlocalcurrency, 2, '.', '');
		$cost_local_currency['cost_exchange_rate']	 = number_format($cost_exchange_rate, 2, '.', '');
		$cost_local_currency['value_in_usd'] =  number_format($value_in_usd_normal,2,'.','');
		
		echo json_encode($cost_local_currency);
	}		
	echo FALSE;
	
	
  
  
  
  function get_PMRequisitions_details($record)
{
	global $adb;
	$sql = $adb->pquery("
	SELECT * FROM `vtiger_pmrequisitions` pmrequisitions
	INNER JOIN  `vtiger_pmrequisitionscf` pmrequisitionscf ON pmrequisitions.pmrequisitionsid=pmrequisitionscf.pmrequisitionsid	
	INNER JOIN vtiger_crmentity crmentity ON crmentity.crmid= pmrequisitions.pmrequisitionsid
	where pmrequisitions.pmrequisitionsid='".$record."'"
	);
    if (empty($sql) === false) {
	$row = $adb->fetch_array($sql);
    return $row;
	}
	else {
		return $row;
	}
}
?>