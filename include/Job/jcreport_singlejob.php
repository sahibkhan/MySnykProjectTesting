<?php
//ini_set('display_errors','on'); version_compare(PHP_VERSION, '5.5.0') <= 0 ? error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED) : error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);   // DEBUGGING
chdir(dirname(__FILE__) . '/../..');
  //require_once('../custom_connectdb.php'); // Подключение к базе данных
  //$link_1 = mysql_connect("192.168.100.191", "theuseroferp", "k0ItFGGzdKTSo"); 
 // mysql_select_db('erp', $link_1);
 $link_1 = mysqli_connect("aurora-db-cluster.cluster-cetdkylp4m5f.us-east-1.rds.amazonaws.com", "salman", "GLK_7143#2020Y","auroraerpdb")or die("Error"); 
 //mysql_select_db('glk_vtiger72', $link_1);
  date_default_timezone_set('UTC');
	  
  //require_once(dirname(__FILE__).'/../Vtiger/crm_data_arrange.php');
 // include(dirname(__FILE__)."/../Exchangerate/exchange_rate_class.php");
  include_once 'include/Exchangerate/exchange_rate_class.php';
  //echo "included";
  $entries = array();
  //vtiger_job.year_no IN('2019','2020')
  $query_jobcf = "SELECT vtiger_jobcf.jobid, cf_1186 as file_title, cf_1188 as location, cf_1190 as department,
  						cf_1198 as ref_no, smcreatorid, smownerid from vtiger_jobcf
						INNER JOIN vtiger_job ON vtiger_job.jobid = vtiger_jobcf.jobid   
						INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobcf.jobid
						where vtiger_crmentity.deleted=0 AND vtiger_job.year_no IN('2021') and vtiger_jobcf.jobid='3642253'	";
  //and vtiger_jobcf.jobid='169897'	
  // AND vtiger_jobcf.cf_1188='85805' AND vtiger_jobcf.cf_1190='85840' 					
  $rs_job = mysqli_query($link_1, $query_jobcf);	
  
  $entries = array();				
  while($job_info_detail = mysqli_fetch_assoc($rs_job))
  {
	 // $link = mysql_connect("192.168.100.191", "theuseroferp", "k0ItFGGzdKTSo"); 
	//  mysql_select_db('erp', $link);
	//$link = mysql_connect("aurora-db-cluster.cluster-cetdkylp4m5f.us-east-1.rds.amazonaws.com", "salman", "GLK_7143#2020Y"); 
	//mysql_select_db('auroraerpdb', $link);
	$link = mysqli_connect("aurora-db-cluster.cluster-cetdkylp4m5f.us-east-1.rds.amazonaws.com", "salman", "GLK_7143#2020Y","auroraerpdb"); 
	  
	  $entries = array();
	  $job_id = $job_info_detail['jobid'];
	  //For JER
	  $jer_sum_sql =  "SELECT sum(jercf.cf_1160) as total_cost_local_currency , sum(jercf.cf_1168) as total_revenue_local_currency 
	  				   FROM `vtiger_jercf` as jercf 
					   INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = jercf.jerid
					   INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid 
					   WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid='".$job_id."' 
					   AND crmentityrel.module='Job' AND crmentityrel.relmodule='JER'";
	 $rs_jer = mysqli_query($link,$jer_sum_sql);
	 $row_job_costing = mysqli_fetch_assoc($rs_jer);
	 
	 $total_cost_local_currency = $row_job_costing['total_cost_local_currency'];
 	 $total_revenue_local_currency = $row_job_costing['total_revenue_local_currency'];
	 
	 $query_reporting_currency = 'SELECT currency.currency_code FROM vtiger_companycf as company 
								  INNER JOIN vtiger_currency_info as currency ON currency.id = company.cf_1459 
						    	  WHERE company.companyid = "'.@$job_info_detail['file_title'].'" ';
	 $rs_reporting_currency = mysqli_query($link,$query_reporting_currency);
	 $row_reporting_currency = mysqli_fetch_assoc($rs_reporting_currency);						
	 $file_title_currency   = $row_reporting_currency['currency_code'];
	  	
	 //0000-00-00 00:00:00
	 $jer_last_sql =  "SELECT vtiger_crmentity.modifiedtime, vtiger_crmentity.createdtime FROM `vtiger_jercf` as jercf 
							 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = jercf.jerid
							 INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid 
							 WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid='".$job_id."'  AND crmentityrel.module='Job' 
							 AND crmentityrel.relmodule='JER' order by vtiger_crmentity.modifiedtime DESC limit 1";
							 
	 $jer_last = mysqli_query($link,$jer_last_sql);
	 $count_last_modified = mysqli_num_rows($jer_last);
	 $exchange_rate_date  = date('Y-m-d');
	 if($count_last_modified>0)
	 {
		 $row_costing_last = mysqli_fetch_assoc($jer_last);
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
		
		
		//For JRER Expense
		//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
		$jrer_sql_expense =  "SELECT vtiger_jobexpencereportcf.cf_1347 as buy_local_currency_gross, 
								     vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
									 vtiger_jobexpencereportcf.cf_1351 as expected_buy_local_currency_net, 
									 vtiger_jobexpencereportcf.cf_1353 as variation_expected_and_actual_buying,
									 vtiger_jobexpencereportcf.cf_1216 as expense_invoice_date,
									 vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
							  FROM `vtiger_jobexpencereport` 
							  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
 							  INNER JOIN vtiger_crmentityrel ON 
							  (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid ) 
 							  Left join vtiger_jobexpencereportcf as vtiger_jobexpencereportcf ON
							  vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid 
		 					  WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid='".$job_id."' AND vtiger_crmentityrel.module='Job' 
							  AND vtiger_crmentityrel.relmodule='Jobexpencereport' AND vtiger_jobexpencereportcf.cf_1457='Expence'
							  AND vtiger_jobexpencereport.owner_id = '".$job_info_detail['smownerid']."' ";
		$result_expense = mysqli_query($link,$jrer_sql_expense);
		$numRows_expnese = mysqli_num_rows($result_expense);
		
		$total_cost_in_usd_gross = 0;
		$total_cost_in_usd_net = 0;
		$total_expected_cost_in_usd_net = 0;
		$total_variation_expected_and_actual_buying_cost_in_usd = 0;
		
		if($numRows_expnese>0)
		{	
			while($row_job_jrer_expense = mysqli_fetch_assoc($result_expense))
			{
				$expense_invoice_date = $row_job_jrer_expense['expense_invoice_date'];
						
				$CurId = $row_job_jrer_expense['buy_currency_id'];
				if ($CurId) {
				  $q_cur = mysqli_query($link,'select * from vtiger_currency_info where id = "'.$CurId.'"');
				  $row_cur = mysqli_fetch_assoc($q_cur);
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
				}
				else{
					$total_cost_in_usd_gross += $row_job_jrer_expense['buy_local_currency_gross'];
					$total_cost_in_usd_net += $row_job_jrer_expense['buy_local_currency_net'];
					$total_expected_cost_in_usd_net += $row_job_jrer_expense['expected_buy_local_currency_net'];
					$total_variation_expected_and_actual_buying_cost_in_usd += $row_job_jrer_expense['variation_expected_and_actual_buying'];	
				}
				
				
			}
			
		}
		$entries['actual_expense_cost_usd'] = number_format ( $total_cost_in_usd_net , 2 ,  "." , "" );
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
									  AND vtiger_jobexpencereport.owner_id = '".$job_info_detail['smownerid']."' ";
									  
		$result_invoice = mysqli_query($link,$jrer_selling_sql_selling);
		$numRows_invoice = mysqli_num_rows($result_invoice);
		
		$total_cost_in_usd_customer = 0;
		$total_cost_in_usd_sell_gross = 0;	
		$total_cost_in_usd_sell_net = 0;
		$total_expected_sell_in_usd_net = 0;
		$total_variation_expected_and_actual_selling_cost_in_usd = 0;
		$total_variation_expect_and_actual_profit_cost_in_usd = 0;
		
		if($numRows_invoice>0)
			{	
				while($row_job_jrer_invoice = mysqli_fetch_assoc($result_invoice))
				{
					$sell_invoice_date = $row_job_jrer_invoice['sell_invoice_date'];
					$exchange_rate_date_invoice =$sell_invoice_date;
					
					$CurId = $row_job_jrer_invoice['currency_id'];
					if ($CurId) {
					  $q_cur = mysqli_query($link,'select * from vtiger_currency_info where id = "'.$CurId.'"');
					  $row_cur = mysqli_fetch_assoc($q_cur);
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
					}
					else{
						$total_cost_in_usd_customer += $row_job_jrer_invoice['sell_customer_currency_gross'];
						$total_cost_in_usd_sell_gross += $row_job_jrer_invoice['sell_local_currency_gross'];
						
						$total_cost_in_usd_sell_net += $row_job_jrer_invoice['sell_local_currency_net'];
						$total_expected_sell_in_usd_net += $row_job_jrer_invoice['expected_sell_local_currency_net'];
						
						$total_variation_expected_and_actual_selling_cost_in_usd += $row_job_jrer_invoice['variation_expected_and_actual_selling'];
						$total_variation_expect_and_actual_profit_cost_in_usd += $row_job_jrer_invoice['variation_expect_and_actual_profit'];
					}
				
				}
			}
			$entries['actual_selling_cost_usd'] = number_format ( $total_cost_in_usd_sell_net , 2 ,  "." , "" );
		//End For JRER Selling
		
		
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
								 AND vtiger_jobexpencereportcf.cf_1214 IN (85776, 85778,85780,85782, 85784, 85786, 85788, 85790, 85792, 85798, 85800, 85802, 85804, 258706, 205753, 420293, 1763707, 85796)';
		
		
		$result_jrer_cash_r = mysqli_query($link,$jrer_cash_r_sum_sql);
		$numRows_cash_r = mysqli_num_rows($result_jrer_cash_r);
		$total_cash_r_in_usd_net = 0;
		if($numRows_cash_r>0)
		{
			while($row_job_jrer_cash_r = mysqli_fetch_assoc($result_jrer_cash_r))
			{
				$expense_invoice_date = $row_job_jrer_cash_r['expense_invoice_date'];
						
				$CurId = $row_job_jrer_cash_r['buy_currency_id'];
				if ($CurId) {
				  $q_cur = mysqli_query($link,'select * from vtiger_currency_info where id = "'.$CurId.'"');
				  $row_cur = mysqli_fetch_assoc($q_cur);
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
		
		
		$result_jrer_cash_n = mysqli_query($link,$jrer_cash_n_sum_sql);
		$numRows_cash_n = mysqli_num_rows($result_jrer_cash_n);
		
		$total_cash_n_in_usd_net = 0;
		if($numRows_cash_n>0)
		{
			while($row_job_jrer_cash_n = mysqli_fetch_assoc($result_jrer_cash_n))
			{
				$expense_invoice_date = $row_job_jrer_cash_n['expense_invoice_date'];
						
				$CurId = $row_job_jrer_cash_n['buy_currency_id'];
				if ($CurId) {
				  $q_cur = mysqli_query($link,'select * from vtiger_currency_info where id = "'.$CurId.'"');
				  $row_cur = mysqli_fetch_assoc($q_cur);
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
								 AND vtiger_jobexpencereportcf.cf_1214 IN (85775, 85777, 85779, 85781, 85783, 85785, 85787, 85789, 85791, 85797, 85799, 85801, 85803, 258702, 205752, 420287, 1763704, 85795)';
		
		$result_jrer_bank_r = mysqli_query($link,$jrer_bank_r_sum_sql);
		$numRows_bank_r = mysqli_num_rows($result_jrer_bank_r);
		$total_bank_r_in_usd_net = 0;
		if($numRows_bank_r>0)
		{
			while($row_job_jrer_bank_r = mysqli_fetch_assoc($result_jrer_bank_r))
			{
				$expense_invoice_date = $row_job_jrer_bank_r['expense_invoice_date'];
						
				$CurId = $row_job_jrer_bank_r['buy_currency_id'];
				if ($CurId) {
				  $q_cur = mysqli_query($link,'select * from vtiger_currency_info where id = "'.$CurId.'"');
				  $row_cur = mysqli_fetch_assoc($q_cur);
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
		
		$result_jrer_bank_n = mysqli_query($link,$jrer_bank_n_sum_sql);
		$numRows_bank_n = mysqli_num_rows($result_jrer_bank_n);
		$total_bank_n_in_usd_net = 0;
		if($numRows_bank_n>0)
		{
			while($row_job_jrer_bank_n = mysqli_fetch_assoc($result_jrer_bank_n))
			{
				$expense_invoice_date = $row_job_jrer_bank_n['expense_invoice_date'];
						
				$CurId = $row_job_jrer_bank_n['buy_currency_id'];
				if ($CurId) {
				  $q_cur = mysqli_query($link,'select * from vtiger_currency_info where id = "'.$CurId.'"');
				  $row_cur = mysqli_fetch_assoc($q_cur);
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


		//For Packing Material Own service
		//Packign Material Own = cf_1453 = 85880
		//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
		//sum(vtiger_jobexpencereportcf.cf_1349)
		$jrer_packing_material_own_sql =  'SELECT vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
										vtiger_jobexpencereportcf.cf_1216 as expense_invoice_date,
										vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
								 FROM `vtiger_jobexpencereport` 
								 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
								 INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid ) 
								 LEFT JOIN  vtiger_jobexpencereportcf as vtiger_jobexpencereportcf 
								 ON vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid 
								 where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid="'.$job_id.'" AND 
								 vtiger_crmentityrel.module="Job" AND vtiger_crmentityrel.relmodule="Jobexpencereport" AND
								 vtiger_jobexpencereportcf.cf_1457="Expence"
								 AND vtiger_jobexpencereport.owner_id = "'.$job_info_detail['smownerid'].'" 
								 AND vtiger_jobexpencereportcf.cf_1453 = "85880"  ';
		
		$result_jrer_packing_material_own = mysqli_query($link,$jrer_packing_material_own_sql);
		$numRows_pm_own = mysqli_num_rows($result_jrer_packing_material_own);
		$total_pm_own_in_usd_net = 0;
		if($numRows_pm_own>0)
		{
			while($row_job_jrer_pm_own = mysqli_fetch_assoc($result_jrer_packing_material_own))
			{
				$expense_invoice_date = $row_job_jrer_pm_own['expense_invoice_date'];
						
				$CurId = $row_job_jrer_pm_own['buy_currency_id'];
				if ($CurId) {
				  $q_cur = mysqli_query($link,'select * from vtiger_currency_info where id = "'.$CurId.'"');
				  $row_cur = mysqli_fetch_assoc($q_cur);
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
				$total_pm_own_in_usd_net += $row_job_jrer_pm_own['buy_local_currency_net']/$b_exchange_rate_n;
				}
				else{
				$total_pm_own_in_usd_net += $row_job_jrer_pm_own['buy_local_currency_net'];	
				}
			}
		}
		$entries['packing_material_own'] =number_format ( $total_pm_own_in_usd_net , 2 ,  "." , "" );
		//End of Packing Material Own Service
		
		//For invoice no
		$invoice_query = mysqli_query($link,'select * from vtiger_jobexpencereport 
									  INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid
									  where vtiger_jobexpencereport.job_id="'.$job_id.'" AND
									  vtiger_jobexpencereport.owner_id = "'.$job_info_detail['smownerid'].'" AND
									  vtiger_jobexpencereport.invoice_no !=""	AND vtiger_jobexpencereportcf.cf_1457="Selling" 
									  GROUP BY vtiger_jobexpencereport.invoice_no	
									  ORDER BY vtiger_jobexpencereportcf.cf_1477, vtiger_jobexpencereportcf.cf_1479 ASC	
									  ');
		$invoice_no = array();						  
		while($row = mysqli_fetch_assoc($invoice_query))
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
		$rs_jobtask = mysqli_query($link,$query_job_task);
		while($row_jobtask = mysqli_fetch_assoc($rs_jobtask))
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
						 
			$result_selling_profit = mysqli_query($link,$jrer_selling_profit);
			
			while($row_selling_profit = mysqli_fetch_assoc($result_selling_profit))
			{
				
			 $dept_branch_new_e = $row_selling_profit['cf_1477'].'-'.$row_selling_profit['cf_1479'];
			 
				if(!in_array($dept_branch_new_e, $profit_share_check_new_e))
					{
						$profit_share_check_new_e[] = $row_selling_profit['cf_1477'].'-'.$row_selling_profit['cf_1479'];		
						
						$rs_loc = mysqli_query($link,'SELECT cf_1559 FROM vtiger_locationcf WHERE locationid = "'.$row_selling_profit['cf_1477'].'" ');
						$row_loc = mysqli_fetch_assoc($rs_loc);
						$col_data_P_e['cf_1477'] = $row_loc['cf_1559'];
						
						$rs_dep = mysqli_query($link,'SELECT cf_1542 FROM vtiger_departmentcf WHERE departmentid = "'.$row_selling_profit['cf_1479'].'" ');	
						$row_dep = mysqli_fetch_assoc($rs_dep);
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
												
					$result_buy_locall = mysqli_query($link,$sum_buy_local_currency_net);
					$numRows_buy_profit = mysqli_num_rows($result_buy_locall);
					$cost = 0;
					while($row_jrer_buy_local_currency_net = mysqli_fetch_assoc($result_buy_locall))
					{
							$cost_local = @$row_jrer_buy_local_currency_net['buy_local_currency_net'];	
							
							$buy_invoice_date = @$row_jrer_buy_local_currency_net['buy_invoice_date'];
							
							$CurId = $row_jrer_buy_local_currency_net['buy_currency_id'];
							if ($CurId) {
							  $q_cur = mysqli_query($link,'select * from vtiger_currency_info where id = "'.$CurId.'"');
							  $row_cur = mysqli_fetch_assoc($q_cur);
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
					$result_sell_locall = mysqli_query($link,$sum_sell_local_currency_net);						
					$numRows_sell_profit = mysqli_num_rows($result_buy_locall);	
					$external_selling = 0;
					while($row_jrer_sell_local_currency_net = mysqli_fetch_assoc($result_sell_locall))
					{
						$s_sell_local_currency_net = @$row_jrer_sell_local_currency_net['sell_local_currency_net'];	
						$sell_invoice_date = @$row_jrer_sell_local_currency_net['sell_invoice_date'];
						
						$CurId = $row_jrer_sell_local_currency_net['currency_id'];
						if ($CurId) {
						  $q_cur = mysqli_query($link,'select * from vtiger_currency_info where id = "'.$CurId.'"');
						  $row_cur = mysqli_fetch_assoc($q_cur);
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
						
					$result_internal = mysqli_query($link,$internal_selling_arr);
					$row_jrer_internal_selling = mysqli_fetch_assoc($result_internal);
					
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
		$rs_check = mysqli_query($link,$query_check);
		$count_check = mysqli_num_rows($rs_check);
		
		$deviation_cost = ($entries['actual_expense_cost_usd']>0 ? (($entries['actual_expense_cost_usd'] - $entries['expected_cost_usd'])/$entries['actual_expense_cost_usd']) : '0' );
		$deviation_revenue = ($entries['actual_selling_cost_usd']>0 ? (($entries['actual_selling_cost_usd'] - $entries['expected_revenue_usd'])/$entries['actual_selling_cost_usd']) : '0');
		$entries['expense_deviation'] = $deviation_cost;
		$entries['selling_deviation'] = $deviation_revenue;
		
		if($count_check==0)
		{
			$query_job_report = mysqli_query($link,"INSERT INTO job_report set 
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
								 selling_deviation = '".$entries['selling_deviation']."',
								 packing_material_own = '".$entries['packing_material_own']."'
								 ");
		}
		else{
			$query_job_report = mysqli_query($link,"UPDATE job_report set 
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
								 selling_deviation = '".$entries['selling_deviation']."',
								 packing_material_own = '".$entries['packing_material_own']."'
								 WHERE job_id = '".$job_id."' ");
		}
		//End profit share
		if(!empty($profit_share_data))
		{
			foreach($profit_share_data as $p_key => $data_profit)
			{
				$query_ps_check = "SELECT * FROM job_profit WHERE job_id='".$job_id."' AND location_id='".$data_profit['location_id']."' 
									AND department_id='".$data_profit['department_id']."' ";
				$rs_ps_check = mysqli_query($link,$query_ps_check);
				$count_ps = mysqli_num_rows($rs_ps_check);
				if($count_ps==0)
				{
					$query_job_profit = mysqli_query($link,"INSERT INTO job_profit set 
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
					$query_job_profit = mysqli_query($link,"UPDATE job_profit set 
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
		
		
		
		mysqli_close($link); 	
   }//end of all job
   mysqli_close($link_1); 	
  // echo "<pre>";
  // print_r($entries);
  // print_r($profit_share_data);
  
?>