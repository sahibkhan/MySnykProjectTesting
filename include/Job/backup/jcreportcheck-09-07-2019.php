<?php
  //require_once('../custom_connectdb.php'); // Подключение к базе данных
  $link_1 = mysql_connect("192.168.100.191", "theuseroferp", "k0ItFGGzdKTSo"); 
  mysql_select_db('erp', $link_1);
  date_default_timezone_set('UTC');
	  
  //require_once(dirname(__FILE__).'/../Vtiger/crm_data_arrange.php');
  include(dirname(__FILE__)."/../Exchangerate/exchange_rate_class.php");
  
  function modifyArray($a, $b)
	{
		if (!empty($a) && !empty($b)) {
			return array_merge($a, $b);
		} else if (!empty($a) && empty($b)) {
			return $a;
		}  else if (empty($a) && !empty($b)) {
			return $b;
		}
	}
	
function array_merge_defaults (array &$array1, array &$array2, $keyField)
{
    $merged = $array1;
    foreach ($array2 as $key => &$value)
    {
        $valueMerged = false;
        foreach ($merged as $mergedKey => &$item)
        {
            if (is_array($item) && array_key_exists($keyField, $item) && $item[$keyField] == $value[$keyField])
            {
                $item = array_merge($item, $value);
                $valueMerged = true;
                break;
            }
            else if ($mergedKey == $key)
            {
                if (is_numeric($mergedKey))
                {
                    $merged[] = $value;
                }
                else
                {
                    $item = $value;
                }
                $valueMerged = true;
                break;
            }
        }
        if (!$valueMerged)
        {
            $merged[$key] = $value;
        }
    }
    return $merged;
}	
  
  $entries = array();
 /* $query_jobcf = "SELECT jobid, cf_1186 as file_title, cf_1188 as location, cf_1190 as department, cf_1198 as ref_no, smcreatorid, smownerid from vtiger_jobcf
						INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobcf.jobid
						where vtiger_crmentity.deleted=0 AND vtiger_jobcf.jobid='1548567' ";*/
 $query_jobcf = "SELECT vtiger_jobcf.jobid, cf_1186 as file_title, cf_1188 as location, cf_1190 as department, cf_1198 as ref_no, smcreatorid, smownerid 
 				 FROM vtiger_jobcf 
				 INNER JOIN vtiger_job ON vtiger_job.jobid = vtiger_jobcf.jobid 
				 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobcf.jobid 
				 WHERE vtiger_crmentity.deleted=0 AND vtiger_job.year_no IN('2018','2019') AND vtiger_jobcf.jobid='918327'
				 ORDER BY vtiger_jobcf.jobid ASC
                 ";						
						
  //and vtiger_jobcf.jobid='169897'						
  $rs_job = mysql_query($query_jobcf);	
  
  $entries = array();				
  while($job_info_detail = mysql_fetch_array($rs_job))
  {
	  $link = mysql_connect("192.168.100.191", "theuseroferp", "k0ItFGGzdKTSo"); 
	  mysql_select_db('erp', $link);
	  
	  $entries = array();
	  $job_id = $job_info_detail['jobid'];
	  //For JER
	  $jer_sum_sql =  "SELECT sum(jercf.cf_1160) as total_cost_local_currency , sum(jercf.cf_1168) as total_revenue_local_currency 
	  				   FROM `vtiger_jercf` as jercf 
					   INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = jercf.jerid
					   INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid 
					   WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid='".$job_id."' 
					   AND crmentityrel.module='Job' AND crmentityrel.relmodule='JER'";
	 $rs_jer = mysql_query($jer_sum_sql);
	 $row_job_costing = mysql_fetch_array($rs_jer);
	 
	 $total_cost_local_currency = $row_job_costing['total_cost_local_currency'];
 	 $total_revenue_local_currency = $row_job_costing['total_revenue_local_currency'];
	 
	 $query_reporting_currency = 'SELECT currency.currency_code FROM vtiger_companycf as company 
								  INNER JOIN vtiger_currency_info as currency ON currency.id = company.cf_1459 
						    	  WHERE company.companyid = "'.@$job_info_detail['file_title'].'" ';
	 $rs_reporting_currency = mysql_query($query_reporting_currency);
	 $row_reporting_currency = mysql_fetch_array($rs_reporting_currency);						
	 $file_title_currency   = $row_reporting_currency['currency_code'];
	  	
	 //0000-00-00 00:00:00
	 $jer_last_sql =  "SELECT vtiger_crmentity.modifiedtime, vtiger_crmentity.createdtime FROM `vtiger_jercf` as jercf 
							 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = jercf.jerid
							 INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid 
							 WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid='".$job_id."'  AND crmentityrel.module='Job' 
							 AND crmentityrel.relmodule='JER' order by vtiger_crmentity.modifiedtime DESC limit 1";
							 
	 $jer_last = mysql_query($jer_last_sql);
	 $count_last_modified = mysql_num_rows($jer_last);
	 $exchange_rate_date  = date('Y-m-d');
	 if($count_last_modified>0)
	 {
		 $row_costing_last = mysql_fetch_array($jer_last);
		 $modifiedtime = $row_costing_last['modifiedtime'];
		 if($modifiedtime=='0000-00-00 00:00:00')
		 {
			 $createdtime = strtotime($row_costing_last['createdtime']);
			 $exchange_rate_date = date('Y-m-d', $createdtime);
		 }
		 else{
		 	$modifiedtime = strtotime($row_costing_last['modifiedtime']);
		 	$exchange_rate_date = date('Y-m-d', $modifiedtime);
		 }
	 }
	 
	 		if($file_title_currency!='USD')
			{			
				$final_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $exchange_rate_date);			
			}else{
				$final_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $exchange_rate_date);
			}
			
		$TOTAL_COST_USD = 0;
		$TOTAL_REVENUE_USD = 0;
		$expected_profit_usd = 0;
		
		
		if($final_exchange_rate>0)
		{
			$total_cost_usd = $total_cost_local_currency/$final_exchange_rate;
			$total_revenue_usd = $total_revenue_local_currency/$final_exchange_rate;
			
			$TOTAL_COST_USD = number_format ( (empty($total_cost_usd) ? 0 : $total_cost_usd) , 2 ,  "." , "" );
			$TOTAL_REVENUE_USD = number_format ( (empty($total_revenue_usd) ? 0 : $total_revenue_usd) , 2 ,  "." , "" );
			$expected_profit_usd = number_format($total_revenue_usd - $total_cost_usd, 2 ,  "." , "");
		}
		
		$entries['expected_cost_usd'] = $TOTAL_COST_USD;
		$entries['expected_profit_usd'] = $expected_profit_usd;	
		$entries['expected_revenue_usd'] = $TOTAL_REVENUE_USD;	
		
		//For Deviation Formula
		//For selling deviation
		 $jer_sum_deviation_sql =  "SELECT  sum(jercf.cf_1162) as total_revenue_local_currency , jercf.cf_1164 as currency 
								   FROM `vtiger_jercf` as jercf 
								   INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = jercf.jerid
								   INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid 
								   WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid='".$job_id."' 
								   AND crmentityrel.module='Job' AND crmentityrel.relmodule='JER'
								   GROUP BY jercf.cf_1164
								   ORDER BY jercf.cf_1164 DESC";
		 $rs_jer_deviation = mysql_query($jer_sum_deviation_sql);
		 //$row_job_costing_deviation = mysql_fetch_array($rs_jer_deviation);
		 $sum_of_expected_sellings_net_in_currency = array();
		 while($row_job_costing_deviation = mysql_fetch_array($rs_jer_deviation))
		 {
			$sum_of_expected_sellings_net_in_currency[$row_job_costing_deviation['currency']] = $row_job_costing_deviation;
		 }
		 
		 //For Expense deviation
		 $jer_expense_deviation_sql =  "SELECT  sum(jercf.cf_1154) as total_cost_local_currency, jercf.cf_1156 as currency 
								   FROM `vtiger_jercf` as jercf 
								   INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = jercf.jerid
								   INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid 
								   WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid='".$job_id."' 
								   AND crmentityrel.module='Job' AND crmentityrel.relmodule='JER'
								   GROUP BY jercf.cf_1156
								   ORDER BY jercf.cf_1156 DESC";
		 $rs_jer_expense_deviation = mysql_query($jer_expense_deviation_sql);
		 //$row_job_costing_deviation = mysql_fetch_array($rs_jer_deviation);
		 $sum_of_expected_expense_net_in_currency = array();
		 while($row_job_costing_expense_deviation = mysql_fetch_array($rs_jer_expense_deviation))
		 {
			$sum_of_expected_expense_net_in_currency[$row_job_costing_expense_deviation['currency']] = $row_job_costing_expense_deviation;
		 }
		
		
		$jrer_selling_sql_selling_deviation =  "SELECT SUM(vtiger_jobexpencereportcf.cf_1357) as actual_sellings_net_in_currency , 
											    vtiger_jobexpencereportcf.cf_1234 as currency
											    FROM `vtiger_jobexpencereport` 
											    INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
											    INNER JOIN vtiger_crmentityrel ON 
											    (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid ) 
											    Left JOIN vtiger_jobexpencereportcf as vtiger_jobexpencereportcf ON
											    vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid						  
											    WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid='".$job_id."' 
												AND vtiger_crmentityrel.module='Job' 
											    AND vtiger_crmentityrel.relmodule='Jobexpencereport' AND vtiger_jobexpencereportcf.cf_1457='Selling'
											    AND vtiger_jobexpencereport.owner_id = '".$job_info_detail['smownerid']."' 
												GROUP BY vtiger_jobexpencereportcf.cf_1234
												ORDER BY vtiger_jobexpencereportcf.cf_1234 DESC";
						  
		$result_invoice_deviation = mysql_query($jrer_selling_sql_selling_deviation);
		$numRows_invoice_deviation = mysql_num_rows($result_invoice_deviation);
		$sum_of_actual_sellings_net_in_currency = array();
		while($row_job_jrer_selling_deviation = mysql_fetch_array($result_invoice_deviation))
		{
			$sum_of_actual_sellings_net_in_currency[$row_job_jrer_selling_deviation['currency']] = $row_job_jrer_selling_deviation;
		}
		$final_array_deviation = array_merge_defaults($sum_of_actual_sellings_net_in_currency, $sum_of_expected_sellings_net_in_currency, 'currency');
		//$final_array_deviation = array_map("modifyArray", $sum_of_expected_sellings_net_in_currency, $sum_of_actual_sellings_net_in_currency);
		//echo "<pre>";
		//print_r($final_array_deviation);
		
		$jrer_sql_expense_deviation =  "SELECT SUM(vtiger_jobexpencereportcf.cf_1337) as actual_buying_net_in_currency,
									 	vtiger_jobexpencereportcf.cf_1345 as currency
									    FROM `vtiger_jobexpencereport` 
									    INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
									    INNER JOIN vtiger_crmentityrel ON 
									    (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
									    Left join vtiger_jobexpencereportcf as vtiger_jobexpencereportcf ON
									    vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid 
									    WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid='".$job_id."' AND vtiger_crmentityrel.module='Job' 
									    AND vtiger_crmentityrel.relmodule='Jobexpencereport' AND vtiger_jobexpencereportcf.cf_1457='Expence'
									    AND vtiger_jobexpencereport.owner_id = '".$job_info_detail['smownerid']."' 
									    GROUP BY vtiger_jobexpencereportcf.cf_1345
									    ORDER BY vtiger_jobexpencereportcf.cf_1345 DESC";
		$result_expense_deviation = mysql_query($jrer_sql_expense_deviation);
		$numRows_expnese_deviation = mysql_num_rows($result_expense_deviation);
		$sum_of_actual_expense_net_in_currency = array();
		while($row_job_jrer_expense_deviation = mysql_fetch_array($result_expense_deviation))
		{
			$sum_of_actual_expense_net_in_currency[$row_job_jrer_expense_deviation['currency']] = $row_job_jrer_expense_deviation;			
		}
		
		//echo "<pre>";
		//print_r($sum_of_expected_expense_net_in_currency);
		//print_r($sum_of_actual_expense_net_in_currency);
		
		//$expense_array_deviation = [];

		//foreach($sum_of_actual_expense_net_in_currency as $key => $arr){
		// $expense_array_deviation[$key] = [$arr,$sum_of_expected_expense_net_in_currency[$key]];
		//}
		//print_r($final_array);
		
		$expense_array_deviation = array_merge_defaults($sum_of_actual_expense_net_in_currency, $sum_of_expected_expense_net_in_currency, 'currency');

		//print_r($merged);
		//$expense_array_deviation = array_map("modifyArray", $sum_of_expected_expense_net_in_currency, $sum_of_actual_expense_net_in_currency);
		//$expense_array_deviation = array_merge_recursive($sum_of_expected_expense_net_in_currency, $sum_of_actual_expense_net_in_currency);
		//print_r($expense_array_deviation);
		//exit;
		//End Deviation
		
		//For JRER Expense
		$jrer_sql_expense =  "SELECT vtiger_jobexpencereportcf.cf_1347 as buy_local_currency_gross, 
								     vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
									 vtiger_jobexpencereportcf.cf_1351 as expected_buy_local_currency_net, 
									 vtiger_jobexpencereportcf.cf_1353 as variation_expected_and_actual_buying,
									 vtiger_jobexpencereportcf.cf_1216 as expense_invoice_date,
									 vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
							  FROM `vtiger_jobexpencereport` 
							  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
 							  INNER JOIN vtiger_crmentityrel ON 
							  (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
 							  Left join vtiger_jobexpencereportcf as vtiger_jobexpencereportcf ON
							  vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid 
		 					  WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid='".$job_id."' AND vtiger_crmentityrel.module='Job' 
							  AND vtiger_crmentityrel.relmodule='Jobexpencereport' AND vtiger_jobexpencereportcf.cf_1457='Expence'
							  AND vtiger_jobexpencereport.owner_id = '".$job_info_detail['smownerid']."' 
							  ORDER BY vtiger_jobexpencereportcf.cf_1345 DESC";
		$result_expense = mysql_query($jrer_sql_expense);
		$numRows_expnese = mysql_num_rows($result_expense);
		
		$total_cost_in_usd_gross = 0;
		$total_cost_in_usd_net = 0;
		$total_expected_cost_in_usd_net = 0;
		$total_variation_expected_and_actual_buying_cost_in_usd = 0;
		
		$usd_denominated_expense_in_currency = array();
		
		if($numRows_expnese>0)
		{	
			while($row_job_jrer_expense = mysql_fetch_array($result_expense))
			{
				$expense_invoice_date = $row_job_jrer_expense['expense_invoice_date'];
						
				$CurId = $row_job_jrer_expense['buy_currency_id'];
				if ($CurId) {
				  $q_cur = mysql_query('select * from vtiger_currency_info where id = "'.$CurId.'"');
				  $row_cur = mysql_fetch_array($q_cur);
				  $Cur = $row_cur['currency_code'];
				}
				
				$b_exchange_rate = $final_exchange_rate;					
				if(!empty($expense_invoice_date))
				{
					if($file_title_currency!='USD')
					{
						$b_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $expense_invoice_date);
					}else{
						$b_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $expense_invoice_date);
					}
				}
				
				if($file_title_currency!='USD')
				{
					$total_cost_in_usd_gross += $row_job_jrer_expense['buy_local_currency_gross']/$b_exchange_rate;
					$total_cost_in_usd_net += $row_job_jrer_expense['buy_local_currency_net']/$b_exchange_rate;
					$total_expected_cost_in_usd_net += $row_job_jrer_expense['expected_buy_local_currency_net']/$b_exchange_rate;
					$total_variation_expected_and_actual_buying_cost_in_usd += $row_job_jrer_expense['variation_expected_and_actual_buying']/$b_exchange_rate;
					
					$usd_expense_denominated = $row_job_jrer_expense['buy_local_currency_net']/$b_exchange_rate;	
				}
				else{
					$total_cost_in_usd_gross += $row_job_jrer_expense['buy_local_currency_gross'];
					$total_cost_in_usd_net += $row_job_jrer_expense['buy_local_currency_net'];
					$total_expected_cost_in_usd_net += $row_job_jrer_expense['expected_buy_local_currency_net'];
					$total_variation_expected_and_actual_buying_cost_in_usd += $row_job_jrer_expense['variation_expected_and_actual_buying'];
					
					$usd_expense_denominated = $row_job_jrer_expense['buy_local_currency_net'];	
				}
				
				//if($usd_expense_denominated!=0){
					$usd_denominated_expense_in_currency[] = array('currency_denominated' => $CurId, 'usd_denominated' => $usd_expense_denominated);
				//}
			}
			
		}
		$entries['actual_expense_cost_usd'] = number_format ( $total_cost_in_usd_net , 2 ,  "." , "" );
		
		
		$usd_denominated_expense_in_currency_new = array();
		foreach($usd_denominated_expense_in_currency as $key => $usd_denominated_expense_in_currency_value)
		{
			$currency_id = $usd_denominated_expense_in_currency_value['currency_denominated'];
			if ( !array_key_exists( $currency_id, $usd_denominated_expense_in_currency_new )) {
			$usd_denominated_expense_in_currency_new[$currency_id] = array('currency_denominated' => $usd_denominated_expense_in_currency_value['currency_denominated'], 'usd_denominated' => $usd_denominated_expense_in_currency_value['usd_denominated'], 'currency' => $usd_denominated_expense_in_currency_value['currency_denominated']);
			 }
			 else{
			$usd_denominated_expense_in_currency_new[$currency_id]['usd_denominated'] += $usd_denominated_expense_in_currency_value['usd_denominated'];
			 }
		}
		//echo "<pre>";
		//print_r($usd_denominated_expense_in_currency_new);
		//print_r($expense_array_deviation);
		
		//Percent Deviation for buying
		//$percent_expense_array_deviation = array_map("modifyArray", $expense_array_deviation, $usd_denominated_expense_in_currency_new);
		$percent_expense_array_deviation = array_merge_defaults($expense_array_deviation, $usd_denominated_expense_in_currency_new, 'currency');
		//echo "<pre>";
	    //print_r($percent_expense_array_deviation);
		//exit;
		//echo "expense deviation=> <br>";
		$percent_expense_deviation_expense = 0;
		$expense_deviation = 0;
		foreach($percent_expense_array_deviation as $percent_expense_deviation_value)
		{
			//echo "actual=> ".$percent_expense_deviation_value['actual_buying_net_in_currency'];
			//echo "<br>";
			$A = number_format (@$percent_expense_deviation_value['actual_buying_net_in_currency'], 4 ,  "." , "" );
			$A = (!empty($A)?$A:0);
			$B = number_format (@$percent_expense_deviation_value['total_cost_local_currency'], 4 ,  "." , "");
			$B = (!empty($B)?$B:0);
			$usd_denominated = number_format (@$percent_expense_deviation_value['usd_denominated'], 4 ,  "." , "");
			$usd_denominated = (!empty($usd_denominated)?$usd_denominated:0);
			$total_cost_in_usd = number_format ($total_cost_in_usd_net, 4 ,  "." , "");
			$total_cost_in_usd = (!empty($total_cost_in_usd)?$total_cost_in_usd:0);
			$C = $usd_denominated/$total_cost_in_usd;
			$percent_expense_deviation_expense =(($A - $B)/$A*round($C));
			$expense_deviation = $expense_deviation + $percent_expense_deviation_expense;
			//echo '('.$A .'-'. $B.')/'.$A.'*('.$usd_denominated.'/'.$total_cost_in_usd.')';
			//echo "<br>";
			//echo $expense_deviation;
			//echo "<br>";
		}
		$entries['expense_deviation'] =number_format ( $expense_deviation , 4 ,  "." , "" );
		//echo $expense_deviation;
		//echo "<br>";
		//END For JRER Expense
		
		//For JRER Selling
		//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
		$jrer_selling_sql_selling =  "SELECT vtiger_jobexpencereportcf.cf_1232 as sell_customer_currency_gross, 
								      vtiger_jobexpencereportcf.cf_1238 as sell_local_currency_gross,
									  vtiger_jobexpencereportcf.cf_1240 as sell_local_currency_net, 
									  vtiger_jobexpencereportcf.cf_1242 as expected_sell_local_currency_net,
									  vtiger_jobexpencereportcf.cf_1244 as variation_expected_and_actual_selling,
									  vtiger_jobexpencereportcf.cf_1246 as variation_expect_and_actual_profit,
									  vtiger_jobexpencereportcf.cf_1355 as sell_invoice_date,
									  vtiger_jobexpencereportcf.cf_1234 as currency_id
									  FROM `vtiger_jobexpencereport` 
									  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
									  INNER JOIN vtiger_crmentityrel ON 
									  (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid ) 
									  Left JOIN vtiger_jobexpencereportcf as vtiger_jobexpencereportcf ON
									  vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid						  
									  WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid='".$job_id."' AND vtiger_crmentityrel.module='Job' 
									  AND vtiger_crmentityrel.relmodule='Jobexpencereport' AND vtiger_jobexpencereportcf.cf_1457='Selling'
									  AND vtiger_jobexpencereport.owner_id = '".$job_info_detail['smownerid']."' 
									  ORDER BY vtiger_jobexpencereportcf.cf_1234 DESC";
									  
		$result_invoice = mysql_query($jrer_selling_sql_selling);
		$numRows_invoice = mysql_num_rows($result_invoice);
		
		$total_cost_in_usd_customer = 0;
		$total_cost_in_usd_sell_gross = 0;	
		$total_cost_in_usd_sell_net = 0;
		$total_expected_sell_in_usd_net = 0;
		$total_variation_expected_and_actual_selling_cost_in_usd = 0;
		$total_variation_expect_and_actual_profit_cost_in_usd = 0;
		
		$usd_denominated_in_currency = array();
		
		if($numRows_invoice>0)
			{	
				
				while($row_job_jrer_invoice = mysql_fetch_array($result_invoice))
				{
					$sell_invoice_date = $row_job_jrer_invoice['sell_invoice_date'];
					$exchange_rate_date_invoice =$sell_invoice_date;
					
					$CurId = $row_job_jrer_invoice['currency_id'];
					if ($CurId) {
					  $q_cur = mysql_query('select * from vtiger_currency_info where id = "'.$CurId.'"');
					  $row_cur = mysql_fetch_array($q_cur);
					  $Cur = $row_cur['currency_code'];
					}						
					
					$s_exchange_rate = $final_exchange_rate;
					if(!empty($exchange_rate_date_invoice))
					{
						if($file_title_currency!='USD')
						{
							$s_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $exchange_rate_date_invoice);
						}else{
							$s_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $exchange_rate_date_invoice);
						}
					}
					
									
					$new_rate = $s_exchange_rate;
								
					if($file_title_currency!='USD')
					{	
						//$selling_value_in_usd_normal = $r['cf_1232']/$s_exchange_rate;
						
						$total_cost_in_usd_customer += $row_job_jrer_invoice['sell_customer_currency_gross']/$s_exchange_rate;
						$total_cost_in_usd_sell_gross += $row_job_jrer_invoice['sell_local_currency_gross']/$s_exchange_rate;
						
						$total_cost_in_usd_sell_net += $row_job_jrer_invoice['sell_local_currency_net']/$s_exchange_rate;
						
						$total_expected_sell_in_usd_net += $row_job_jrer_invoice['expected_sell_local_currency_net']/$s_exchange_rate;
						
						$total_variation_expected_and_actual_selling_cost_in_usd += $row_job_jrer_invoice['variation_expected_and_actual_selling']/$s_exchange_rate;
						$total_variation_expect_and_actual_profit_cost_in_usd += $row_job_jrer_invoice['variation_expect_and_actual_profit']/$s_exchange_rate;
						
						$usd_denominated = $row_job_jrer_invoice['sell_local_currency_net']/$s_exchange_rate;
					}
					else{
						$total_cost_in_usd_customer += $row_job_jrer_invoice['sell_customer_currency_gross'];
						$total_cost_in_usd_sell_gross += $row_job_jrer_invoice['sell_local_currency_gross'];
						
						$total_cost_in_usd_sell_net += $row_job_jrer_invoice['sell_local_currency_net'];
						$total_expected_sell_in_usd_net += $row_job_jrer_invoice['expected_sell_local_currency_net'];
						
						$total_variation_expected_and_actual_selling_cost_in_usd += $row_job_jrer_invoice['variation_expected_and_actual_selling'];
						$total_variation_expect_and_actual_profit_cost_in_usd += $row_job_jrer_invoice['variation_expect_and_actual_profit'];
						
						$usd_denominated = $row_job_jrer_invoice['sell_local_currency_net'];
					}
					
					
					//if($usd_denominated!=0){
						$usd_denominated_in_currency[] = array('currency_denominated' => $CurId, 'usd_denominated' => $usd_denominated);
					//}
					
				}
			}
			$entries['actual_selling_cost_usd'] = number_format ( $total_cost_in_usd_sell_net , 2 ,  "." , "" );
			
			//echo "<pre>";
			//print_r($usd_denominated_in_currency);	
			$usd_denominated_in_currency_new = array();
			foreach($usd_denominated_in_currency as $key => $usd_denominated_in_currency_value)
			{
				$currency_id =  $usd_denominated_in_currency_value['currency_denominated'];
			if ( !array_key_exists( $currency_id, $usd_denominated_in_currency_new )) {
			$usd_denominated_in_currency_new[$currency_id] = array('currency_denominated' => $usd_denominated_in_currency_value['currency_denominated'], 'usd_denominated' => $usd_denominated_in_currency_value['usd_denominated'], 'currency' => $usd_denominated_in_currency_value['currency_denominated']);
			 }
			 else{
			$usd_denominated_in_currency_new[$currency_id]['usd_denominated'] += $usd_denominated_in_currency_value['usd_denominated'];
			 }
			}
		//echo "<pre>";
		//print_r($usd_denominated_in_currency_new);
		//End For JRER Selling
		//echo "selling<br>";
		//Percent Deviation for Selling
		$percent_array_deviation = array_merge_defaults($final_array_deviation, $usd_denominated_in_currency_new, 'currency');
		//$percent_array_deviation = array_map("modifyArray", $final_array_deviation, $usd_denominated_in_currency_new);
	    //print_r($percent_array_deviation);
		$percent_deviation_selling = 0;
		$selling_deviation = 0;
		//echo "selling deviation=> <br>";
		foreach($percent_array_deviation as $percent_deviation_value)
		{
			$percent_deviation_selling =((number_format ( @$percent_deviation_value['actual_sellings_net_in_currency'], 4 ,  "." , "") - number_format (@$percent_deviation_value['total_revenue_local_currency'] , 4 ,  "." , ""))/number_format(@$percent_deviation_value['actual_sellings_net_in_currency'], 4 ,  "." , "")*(number_format (@$percent_deviation_value['usd_denominated'], 4 ,  "." , "" )/number_format ($total_cost_in_usd_sell_net, 4 ,  "." , "" )));
			$selling_deviation = $selling_deviation + $percent_deviation_selling;
			/*echo '('.@$percent_deviation_value['actual_sellings_net_in_currency'] .'-'. @$percent_deviation_value['total_revenue_local_currency'].')/'.@$percent_deviation_value['actual_sellings_net_in_currency'].'*('.@$percent_deviation_value['usd_denominated'].'/'.$total_cost_in_usd_sell_net.')';
			echo "<br>";
			echo $selling_deviation;
			echo "<br>";*/
		}
		//echo "<br>";
		//echo "final".$final_new_value;
		//End Percent Deviation for Selling
		$entries['selling_deviation'] =number_format ( $selling_deviation , 4 ,  "." , "" );
		
		
		//For Cash R
		//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
		$jrer_cash_r_sum_sql =  'SELECT vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
										vtiger_jobexpencereportcf.cf_1216 as expense_invoice_date,
										vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
								 FROM `vtiger_jobexpencereport` 
								 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
								 INNER JOIN vtiger_crmentityrel ON 
								 (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid ) 
								 LEFT JOIN  vtiger_jobexpencereportcf as vtiger_jobexpencereportcf 
								 ON vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid 
								 where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid="'.$job_id.'" AND 
								 vtiger_crmentityrel.module="Job" AND vtiger_crmentityrel.relmodule="Jobexpencereport" AND
								 vtiger_jobexpencereportcf.cf_1457="Expence"
								 AND vtiger_jobexpencereport.owner_id = "'.$job_info_detail['smownerid'].'" 
								 AND vtiger_jobexpencereportcf.cf_1214 IN (85776, 85778,85780,85782, 85784, 85786, 85788, 85790, 85792, 85798, 85800, 85802, 85804, 258706, 205753, 420293, 1763707)';
		
		
		$result_jrer_cash_r = mysql_query($jrer_cash_r_sum_sql);
		$numRows_cash_r = mysql_num_rows($result_jrer_cash_r);
		$total_cash_r_in_usd_net = 0;
		if($numRows_cash_r>0)
		{
			while($row_job_jrer_cash_r = mysql_fetch_array($result_jrer_cash_r))
			{
				$expense_invoice_date = $row_job_jrer_cash_r['expense_invoice_date'];
						
				$CurId = $row_job_jrer_cash_r['buy_currency_id'];
				if ($CurId) {
				  $q_cur = mysql_query('select * from vtiger_currency_info where id = "'.$CurId.'"');
				  $row_cur = mysql_fetch_array($q_cur);
				  $Cur = $row_cur['currency_code'];
				}
				
				$b_exchange_rate_r = $final_exchange_rate;					
				if(!empty($expense_invoice_date))
				{
					if($file_title_currency!='USD')
					{
						$b_exchange_rate_r = currency_rate_convert_kz($file_title_currency, 'USD',  1, $expense_invoice_date);
					}else{
						$b_exchange_rate_r = currency_rate_convert($file_title_currency, 'USD',  1, $expense_invoice_date);
					}
				}
				
				if($file_title_currency!='USD')
				{
				$total_cash_r_in_usd_net += $row_job_jrer_cash_r['buy_local_currency_net']/$b_exchange_rate_r;
				}
				else{
				$total_cash_r_in_usd_net += $row_job_jrer_cash_r['buy_local_currency_net'];	
				}
			}
		}
		$entries['cash'] =number_format ( $total_cash_r_in_usd_net , 2 ,  "." , "" );
		//End of Cash R
		
		//Cash N = 85794
		//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
		$jrer_cash_n_sum_sql =  'SELECT vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
										vtiger_jobexpencereportcf.cf_1216 as expense_invoice_date,
										vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
								 FROM `vtiger_jobexpencereport` 
								 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
								 INNER JOIN vtiger_crmentityrel ON 
								 (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid ) 
								 LEFT JOIN  vtiger_jobexpencereportcf as vtiger_jobexpencereportcf 
								 ON vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid 
								 where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid="'.$job_id.'" AND 
								 vtiger_crmentityrel.module="Job" AND vtiger_crmentityrel.relmodule="Jobexpencereport" AND
								 vtiger_jobexpencereportcf.cf_1457="Expence"
								 AND vtiger_jobexpencereport.owner_id = "'.$job_info_detail['smownerid'].'" 
								 AND vtiger_jobexpencereportcf.cf_1214 IN("85794","85774")   ';
		
		
		$result_jrer_cash_n = mysql_query($jrer_cash_n_sum_sql);
		$numRows_cash_n = mysql_num_rows($result_jrer_cash_n);
		
		$total_cash_n_in_usd_net = 0;
		if($numRows_cash_n>0)
		{
			while($row_job_jrer_cash_n = mysql_fetch_array($result_jrer_cash_n))
			{
				$expense_invoice_date = $row_job_jrer_cash_n['expense_invoice_date'];
						
				$CurId = $row_job_jrer_cash_n['buy_currency_id'];
				if ($CurId) {
				  $q_cur = mysql_query('select * from vtiger_currency_info where id = "'.$CurId.'"');
				  $row_cur = mysql_fetch_array($q_cur);
				  $Cur = $row_cur['currency_code'];
				}
				
				$b_exchange_rate_n = $final_exchange_rate;					
				if(!empty($expense_invoice_date))
				{
					if($file_title_currency!='USD')
					{
						$b_exchange_rate_n = currency_rate_convert_kz($file_title_currency, 'USD',  1, $expense_invoice_date);
					}else{
						$b_exchange_rate_n = currency_rate_convert($file_title_currency, 'USD',  1, $expense_invoice_date);
					}
				}
				
				if($file_title_currency!='USD')
				{
				$total_cash_n_in_usd_net += $row_job_jrer_cash_n['buy_local_currency_net']/$b_exchange_rate_n;
				}
				else{
				$total_cash_n_in_usd_net += $row_job_jrer_cash_n['buy_local_currency_net'];	
				}
			}
		}
		$entries['cash_n'] =number_format ( $total_cash_n_in_usd_net , 2 ,  "." , "" );
		//End of Cash N
		
		//Bank R
		//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
		$jrer_bank_r_sum_sql =  'SELECT vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
										vtiger_jobexpencereportcf.cf_1216 as expense_invoice_date,
										vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
								 FROM `vtiger_jobexpencereport` 
								 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
								 INNER JOIN vtiger_crmentityrel ON 
								 (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid ) 
								 LEFT JOIN  vtiger_jobexpencereportcf as vtiger_jobexpencereportcf 
								 ON vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid 
								 where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid="'.$job_id.'" AND 
								 vtiger_crmentityrel.module="Job" AND vtiger_crmentityrel.relmodule="Jobexpencereport" AND
								 vtiger_jobexpencereportcf.cf_1457="Expence"
								 AND vtiger_jobexpencereport.owner_id = "'.$job_info_detail['smownerid'].'" 
								 AND vtiger_jobexpencereportcf.cf_1214 IN (85775, 85777, 85779, 85781, 85783, 85785, 85787, 85789, 85791, 85797, 85799, 85801, 85803, 258702, 205752, 420287, 1763704)';
		
		$result_jrer_bank_r = mysql_query($jrer_bank_r_sum_sql);
		$numRows_bank_r = mysql_num_rows($result_jrer_bank_r);
		$total_bank_r_in_usd_net = 0;
		if($numRows_bank_r>0)
		{
			while($row_job_jrer_bank_r = mysql_fetch_array($result_jrer_bank_r))
			{
				$expense_invoice_date = $row_job_jrer_bank_r['expense_invoice_date'];
						
				$CurId = $row_job_jrer_bank_r['buy_currency_id'];
				if ($CurId) {
				  $q_cur = mysql_query('select * from vtiger_currency_info where id = "'.$CurId.'"');
				  $row_cur = mysql_fetch_array($q_cur);
				  $Cur = $row_cur['currency_code'];
				}
				
				$b_exchange_rate_n = $final_exchange_rate;					
				if(!empty($expense_invoice_date))
				{
					if($file_title_currency!='USD')
					{
						$b_exchange_rate_n = currency_rate_convert_kz($file_title_currency, 'USD',  1, $expense_invoice_date);
					}else{
						$b_exchange_rate_n = currency_rate_convert($file_title_currency, 'USD',  1, $expense_invoice_date);
					}
				}
				
				if($file_title_currency!='USD')
				{
				$total_bank_r_in_usd_net += $row_job_jrer_bank_r['buy_local_currency_net']/$b_exchange_rate_n;
				}
				else{
				$total_bank_r_in_usd_net += $row_job_jrer_bank_r['buy_local_currency_net'];	
				}
			}
		}
		$entries['bank'] =number_format ( $total_bank_r_in_usd_net , 2 ,  "." , "" );
		//End of Bank R
		
		//Bank N = 85793
		//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
		//sum(vtiger_jobexpencereportcf.cf_1349)
		$jrer_bank_n_sum_sql =  'SELECT vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
										vtiger_jobexpencereportcf.cf_1216 as expense_invoice_date,
										vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
								 FROM `vtiger_jobexpencereport` 
								 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
								 INNER JOIN vtiger_crmentityrel ON 
								 (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid ) 
								 LEFT JOIN  vtiger_jobexpencereportcf as vtiger_jobexpencereportcf 
								 ON vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid 
								 where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid="'.$job_id.'" AND 
								 vtiger_crmentityrel.module="Job" AND vtiger_crmentityrel.relmodule="Jobexpencereport" AND
								 vtiger_jobexpencereportcf.cf_1457="Expence"
								 AND vtiger_jobexpencereport.owner_id = "'.$job_info_detail['smownerid'].'" 
								 AND vtiger_jobexpencereportcf.cf_1214 IN("85793","85773")  ';
		
		$result_jrer_bank_n = mysql_query($jrer_bank_n_sum_sql);
		$numRows_bank_n = mysql_num_rows($result_jrer_bank_n);
		$total_bank_n_in_usd_net = 0;
		if($numRows_bank_n>0)
		{
			while($row_job_jrer_bank_n = mysql_fetch_array($result_jrer_bank_n))
			{
				$expense_invoice_date = $row_job_jrer_bank_n['expense_invoice_date'];
						
				$CurId = $row_job_jrer_bank_n['buy_currency_id'];
				if ($CurId) {
				  $q_cur = mysql_query('select * from vtiger_currency_info where id = "'.$CurId.'"');
				  $row_cur = mysql_fetch_array($q_cur);
				  $Cur = $row_cur['currency_code'];
				}
				
				$b_exchange_rate_n = $final_exchange_rate;					
				if(!empty($expense_invoice_date))
				{
					if($file_title_currency!='USD')
					{
						$b_exchange_rate_n = currency_rate_convert_kz($file_title_currency, 'USD',  1, $expense_invoice_date);
					}else{
						$b_exchange_rate_n = currency_rate_convert($file_title_currency, 'USD',  1, $expense_invoice_date);
					}
				}
				
				if($file_title_currency!='USD')
				{
				$total_bank_n_in_usd_net += $row_job_jrer_bank_n['buy_local_currency_net']/$b_exchange_rate_n;
				}
				else{
				$total_bank_n_in_usd_net += $row_job_jrer_bank_n['buy_local_currency_net'];	
				}
			}
		}
		$entries['bank_n'] =number_format ( $total_bank_n_in_usd_net , 2 ,  "." , "" );	
		//End of Bank N
		
		//For invoice no
		$invoice_query = mysql_query('select * from vtiger_jobexpencereport 
									  INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid
									  where vtiger_jobexpencereport.job_id="'.$job_id.'" AND
									  vtiger_jobexpencereport.owner_id = "'.$job_info_detail['smownerid'].'" AND
									  vtiger_jobexpencereport.invoice_no !=""	AND vtiger_jobexpencereportcf.cf_1457="Selling" 
									  GROUP BY vtiger_jobexpencereport.invoice_no	
									  ORDER BY vtiger_jobexpencereportcf.cf_1477, vtiger_jobexpencereportcf.cf_1479 ASC	
									  ');
		$invoice_no = array();						  
		while($row = mysql_fetch_array($invoice_query))
		{
			$invoice_no[] =$row['invoice_no'];
		}
		$pv_GlkInvoice = implode(';', $invoice_no);	
		$entries['GlkInvoice'] = $pv_GlkInvoice;
		// end invoice no 
		
		
		
		//For Profit share
		//get job task user
		$profit_share_e = array();
		$profit_share_check_new_e = array();
				
		$query_job_task = "SELECT vtiger_jobtask.user_id, vtiger_jobtask.job_owner FROM vtiger_jobtask 
						   INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobtask.jobtaskid
						   WHERE vtiger_crmentity.deleted=0 AND vtiger_jobtask.job_id='".$job_id."'
						   GROUP BY vtiger_jobtask.user_id
						   ORDER BY `vtiger_jobtask`.`job_owner` ASC
						  ";
		$rs_jobtask = mysql_query($query_job_task);
		while($row_jobtask = mysql_fetch_array($rs_jobtask))
		{
			if($row_jobtask['job_owner']==1)
			{
				$where = " (vtiger_jobexpencereportcf.cf_1457='Expence' || vtiger_jobexpencereportcf.cf_1457='Selling')";
			}
			else{
				$where = " (vtiger_jobexpencereportcf.cf_1457='Expence')";
			}
			//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
			/*
			$jrer_selling_profit =  "SELECT * FROM `vtiger_jobexpencereport` 
								 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
								 INNER JOIN vtiger_crmentityrel ON 
								 (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid ) 
								 LEFT JOIN vtiger_jobexpencereportcf as vtiger_jobexpencereportcf ON
								 vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid 
								 WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid='".$job_id."' AND
								 vtiger_crmentityrel.module='Job' AND vtiger_crmentityrel.relmodule='Jobexpencereport' AND
								 ".$where."
								 AND vtiger_crmentity.smownerid='".$row_jobtask['user_id']."' AND vtiger_jobexpencereport.user_id='".$row_jobtask['user_id']."' limit 1";
			*/
			$jrer_selling_profit =  "SELECT DISTINCT  vtiger_jobexpencereportcf.cf_1477, vtiger_jobexpencereportcf.cf_1479  FROM `vtiger_jobexpencereport` 
								 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
								 INNER JOIN vtiger_crmentityrel ON 
								 (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid ) 
								 LEFT JOIN vtiger_jobexpencereportcf as vtiger_jobexpencereportcf ON
								 vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid 
								 WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid='".$job_id."' AND
								 vtiger_crmentityrel.module='Job' AND vtiger_crmentityrel.relmodule='Jobexpencereport' AND
								 ".$where."
								 AND vtiger_crmentity.smownerid='".$row_jobtask['user_id']."' AND vtiger_jobexpencereport.user_id='".$row_jobtask['user_id']."' ";
								 
			$result_selling_profit = mysql_query($jrer_selling_profit);
			
			while($row_selling_profit = mysql_fetch_array($result_selling_profit))
			{
			 $dept_branch_new_e = $row_selling_profit['cf_1477'].'-'.$row_selling_profit['cf_1479'];
			 
			 if(!in_array($dept_branch_new_e, $profit_share_check_new_e))
				 {
					$profit_share_check_new_e[] = $row_selling_profit['cf_1477'].'-'.$row_selling_profit['cf_1479'];		
					
					$rs_loc = mysql_query('SELECT cf_1559 FROM vtiger_locationcf WHERE locationid = "'.$row_selling_profit['cf_1477'].'" ');
					$row_loc = mysql_fetch_array($rs_loc);
					$col_data_P_e['cf_1477'] = $row_loc['cf_1559'];
					
					$rs_dep = mysql_query('SELECT cf_1542 FROM vtiger_departmentcf WHERE departmentid = "'.$row_selling_profit['cf_1479'].'" ');	
					$row_dep = mysql_fetch_array($rs_dep);
					$col_data_P_e['cf_1479'] = $row_dep['cf_1542'];					
					
					$col_data_P_e['cf_1477_location_id'] = $row_selling_profit['cf_1477'];
					$col_data_P_e['cf_1479_department_id'] = $row_selling_profit['cf_1479'];
					
					//$col_data_P_e['user_id'] = $row_selling_profit['user_id'];
					//$col_data_P_e['owner_id'] = $row_selling_profit['owner_id'];		
					//$col_data_P_e['sm_owner_id'] = $row_selling_profit['smownerid'];	
					$col_data_P_e['user_id'] = $row_jobtask['user_id'];
					$col_data_P_e['owner_id'] = $row_jobtask['user_id'];		
					$col_data_P_e['sm_owner_id'] = $row_jobtask['user_id'];	
					
					$profit_share_e[] = $col_data_P_e;	
				 }	
		}
		}
		
		
		$sum_of_net_profit = 0;		
		$profit_share_data = array();
		 if(!empty($profit_share_e))
			 {
				 foreach($profit_share_e as $key => $p_share)
				 {
					
					//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
					 $sum_buy_local_currency_net =  "SELECT vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
															   vtiger_jobexpencereportcf.cf_1216 as buy_invoice_date,
															   vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
									 					FROM `vtiger_jobexpencereport` 
							  							INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
 														INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid ) 
 														left join vtiger_jobexpencereportcf as vtiger_jobexpencereportcf on vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid 
							 							WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid='".$job_id."' AND vtiger_crmentityrel.module='Job' 
							 	   						AND vtiger_crmentityrel.relmodule='Jobexpencereport' AND vtiger_jobexpencereportcf.cf_1457='Expence'
								   						AND vtiger_jobexpencereportcf.cf_1477='".$p_share['cf_1477_location_id']."' 
														AND vtiger_jobexpencereportcf.cf_1479='".$p_share['cf_1479_department_id']."' 
														AND vtiger_jobexpencereport.owner_id = '".$job_info_detail['smownerid']."'
								   						";
												
					$result_buy_locall = mysql_query($sum_buy_local_currency_net);
					$numRows_buy_profit = mysql_num_rows($result_buy_locall);
					$cost = 0;
					while($row_jrer_buy_local_currency_net = mysql_fetch_array($result_buy_locall))
					{
							$cost_local = @$row_jrer_buy_local_currency_net['buy_local_currency_net'];	
							
							$buy_invoice_date = @$row_jrer_buy_local_currency_net['buy_invoice_date'];
							
							$CurId = $row_jrer_buy_local_currency_net['buy_currency_id'];
							if ($CurId) {
							  $q_cur = mysql_query('select * from vtiger_currency_info where id = "'.$CurId.'"');
							  $row_cur = mysql_fetch_array($q_cur);
							  $Cur = $row_cur['currency_code'];
							}
							$b_exchange_rate = 1;						
							if(!empty($buy_invoice_date))
							{
								if($file_title_currency!='USD')
								{
									$b_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $buy_invoice_date);
								}else{
									$b_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $buy_invoice_date);
								}
							}
							
							if($file_title_currency!='USD')
							{
							$cost += $cost_local/$b_exchange_rate;
							}
							else{
							$cost += $cost_local;	
							}						
					}
					
					//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
					$sum_sell_local_currency_net =  "SELECT vtiger_jobexpencereportcf.cf_1240 as sell_local_currency_net,
																vtiger_jobexpencereportcf.cf_1355 as sell_invoice_date,
																vtiger_jobexpencereportcf.cf_1234 as currency_id
									 					FROM `vtiger_jobexpencereport` 
							  							INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
 														INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid ) 
 														left join vtiger_jobexpencereportcf as vtiger_jobexpencereportcf on vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid 
		 					  							WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid='".$job_id."' AND vtiger_crmentityrel.module='Job' 
														AND vtiger_crmentityrel.relmodule='Jobexpencereport' AND vtiger_jobexpencereportcf.cf_1457='Selling'
														AND vtiger_jobexpencereportcf.cf_1477='".$p_share['cf_1477_location_id']."' 
														AND vtiger_jobexpencereportcf.cf_1479='".$p_share['cf_1479_department_id']."' 
														
														";
					$result_sell_locall = mysql_query($sum_sell_local_currency_net);						
					$numRows_sell_profit = mysql_num_rows($result_buy_locall);	
					$external_selling = 0;
					while($row_jrer_sell_local_currency_net = mysql_fetch_array($result_sell_locall))
					{
						$s_sell_local_currency_net = @$row_jrer_sell_local_currency_net['sell_local_currency_net'];	
						$sell_invoice_date = @$row_jrer_sell_local_currency_net['sell_invoice_date'];
						
						$CurId = $row_jrer_sell_local_currency_net['currency_id'];
						if ($CurId) {
						  $q_cur = mysql_query('select * from vtiger_currency_info where id = "'.$CurId.'"');
						  $row_cur = mysql_fetch_array($q_cur);
						  $Cur = $row_cur['currency_code'];
						}						
						
						$s_exchange_rate = 1;
						if(!empty($sell_invoice_date))
						{
							if($file_title_currency!='USD')
							{
								$s_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $sell_invoice_date);
							}else{
								$s_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $sell_invoice_date);
							}
						}
						
						$new_rate = $s_exchange_rate;						
						if($file_title_currency!='USD')
						{
							$external_selling += $s_sell_local_currency_net/$s_exchange_rate;
						}
						else{
							$external_selling += $s_sell_local_currency_net;
						}
					}
					
					//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
					$internal_selling_arr = "SELECT vtiger_jobexpcf.cf_1263 as internal_selling
												FROM vtiger_jobexp 
												INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexp.jobexpid 
 												INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid ) 
 												left join vtiger_jobexpcf as vtiger_jobexpcf on vtiger_jobexpcf.jobexpid=vtiger_jobexp.jobexpid 
		 					  					WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid='".$job_id."' 
												AND vtiger_crmentityrel.module='Job' AND vtiger_crmentityrel.relmodule='Jobexp' 
												AND vtiger_jobexpcf.cf_1257='".$p_share['cf_1477_location_id']."' AND vtiger_jobexpcf.cf_1259='".$p_share['cf_1479_department_id']."'	
												";				   
						
					$result_internal = mysql_query($internal_selling_arr);
					$row_jrer_internal_selling = mysql_fetch_array($result_internal);
					
					if($job_info_detail['smownerid']==$p_share['sm_owner_id'])
					{
						$job_profit = $external_selling - $cost;
					}
					else{
						//$job_profit = @$row_jrer_internal_selling['internal_selling'] - $cost;
						$job_profit = $external_selling - $cost;
						
					}
					
					
					$brach_department = $p_share['cf_1479_department_id'].'-'.$p_share['cf_1477_location_id'];
					$job_branch_department = $job_info_detail['department'].'-'.$job_info_detail['location'];
					
							
					if(trim($brach_department)==trim($job_branch_department))
					{
						//$profit_share_col = 0;
						//$profit_share_col = $job_profit;
						$profit_share_col = 0;
						$net_profit = $job_profit - $profit_share_col;
						$profit_share_col = $job_profit;
					}
					else{
						//$profit_share_col = @$row_jrer_internal_selling['internal_selling'] - $cost;
						if(empty($row_jrer_internal_selling['internal_selling']) || $row_jrer_internal_selling['internal_selling']<=0)
						{
							$profit_share_col =  0;
						}
						else{
							$profit_share_col = @$row_jrer_internal_selling['internal_selling'] - $cost;
						}
						$net_profit = $job_profit - $profit_share_col;
					}
					
					//$net_profit = $job_profit - $profit_share_col;
					$sum_of_net_profit +=$net_profit;			
					
					
					$profit_share_data[] = array(
												 'location_id' => $p_share['cf_1477_location_id'], 
												 'department_id' => $p_share['cf_1479_department_id'],
												 'branch' => $p_share['cf_1477'], 
												 'department' => $p_share['cf_1479'],
												 'cost' => number_format ( $cost , 2 ,  "." , "" ),
												 'external_selling' => number_format ( $external_selling , 2 ,  "." , "" ),
												 'job_profit'  =>  number_format ( $job_profit , 2 ,  "." , "" ),
												// 'office_id' => $p_share['cf_1477'], 
												 //'department_id' => $p_share['cf_1479'], 
												 'job_id' => $job_id,
												 'profit_share_received' => number_format($profit_share_col, 2 ,  "." , "" ), // to show in profit column of excel
												 'net_profit' => number_format ( $net_profit , 2 ,  "." , "" ),
												 'internal_selling' => @$row_jrer_internal_selling['internal_selling'],
												 'job_user_id' => $job_info_detail['smownerid'],
												 'owner_id' => $p_share['owner_id'],
												 'user_id' => $p_share['user_id'],
												 'ps_owner_id' => $p_share['sm_owner_id']															
												 );
					
				 }			 
			 }
		
		$entries['net_profit'] = $sum_of_net_profit;
		
		$query_check = "select job_id from job_report where job_id='".$job_id."'";
		$rs_check = mysql_query($query_check);
		$count_check = mysql_num_rows($rs_check);
		
		//echo "<pre>";
		//print_r($entries);
		//exit;
		if($count_check==0)
		{
			$query_job_report = mysql_query("INSERT INTO job_report set 
											 job_id = '".$job_id."',
											 expected_cost_usd = '".$entries['expected_cost_usd']."',
											 expected_profit_usd = '".$entries['expected_profit_usd']."',
											 expected_revenue_usd = '".$entries['expected_revenue_usd']."',
											 actual_expense_cost_usd = '".$entries['actual_expense_cost_usd']."',
											 actual_selling_cost_usd = '".$entries['actual_selling_cost_usd']."',
											 net_profit = '".$entries['net_profit']."',
											 cash = '".$entries['cash']."',
											 cash_n = '".$entries['cash_n']."',
											 bank = '".$entries['bank']."',
											 bank_n = '".$entries['bank_n']."',
											 glk_invoice_no ='".$entries['GlkInvoice']."',
											 expense_deviation = '".$entries['expense_deviation']."',
											 selling_deviation = '".$entries['selling_deviation']."'
								  			");
		}
		else{
			$query_job_report = mysql_query("UPDATE job_report set 
											 expected_cost_usd = '".$entries['expected_cost_usd']."',
											 expected_profit_usd = '".$entries['expected_profit_usd']."',
											 expected_revenue_usd = '".$entries['expected_revenue_usd']."',
											 actual_expense_cost_usd = '".$entries['actual_expense_cost_usd']."',
											 actual_selling_cost_usd = '".$entries['actual_selling_cost_usd']."',
											 net_profit = '".$entries['net_profit']."',
											 cash = '".$entries['cash']."',
											 cash_n = '".$entries['cash_n']."',
											 bank = '".$entries['bank']."',
											 bank_n = '".$entries['bank_n']."',
											 glk_invoice_no ='".$entries['GlkInvoice']."',
											 expense_deviation = '".$entries['expense_deviation']."',
											 selling_deviation = '".$entries['selling_deviation']."'
											 WHERE job_id = '".$job_id."' 
											 ");
		}
		//End profit share
		if(!empty($profit_share_data))
		{
			foreach($profit_share_data as $p_key => $data_profit)
			{
				$query_ps_check = "SELECT * FROM job_profit WHERE job_id='".$job_id."' AND location_id='".$data_profit['location_id']."' 
									AND department_id='".$data_profit['department_id']."' ";
				$rs_ps_check = mysql_query($query_ps_check);
				$count_ps = mysql_num_rows($rs_ps_check);
				if($count_ps==0)
				{
					$query_job_profit = mysql_query("INSERT INTO job_profit set 
													 job_id = '".$job_id."',
													 location_id = '".$data_profit['location_id']."',
													 department_id = '".$data_profit['department_id']."',
													 branch = '".$data_profit['branch']."',
													 department = '".$data_profit['department']."',
													 cost = '".$data_profit['cost']."',
													 profit_share_received = '".$data_profit['profit_share_received']."',
													 ps_owner_id = '".$data_profit['ps_owner_id']."'
													");
				}
				else{
					$query_job_profit = mysql_query("UPDATE job_profit set 
													 branch = '".$data_profit['branch']."',
													 department = '".$data_profit['department']."',
													 cost = '".$data_profit['cost']."',
													 profit_share_received = '".$data_profit['profit_share_received']."',
													 ps_owner_id = '".$data_profit['ps_owner_id']."'
													 where job_id = '".$job_id."' AND
													 location_id = '".$data_profit['location_id']."' AND
													 department_id = '".$data_profit['department_id']."'
													");
				}
					     

			}
		}
		
		
		
	mysql_close($link); 	
   }//end of all job
   mysql_close($link_1); 	
   //echo "<pre>";
   //print_r($entries);
   //print_r($profit_share_data);
  
?>