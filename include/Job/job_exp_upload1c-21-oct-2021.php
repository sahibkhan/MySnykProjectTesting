<?php
chdir(dirname(__FILE__) . '/../..');
include_once 'vtlib/Vtiger/Module.php';
include_once 'includes/main/WebUI.php';
include_once 'include/Webservices/Utils.php';
require_once('include/custom_connectdb2.php'); // Подключение к базе данных
set_time_limit(0);
ini_set('memory_limit','64M');


ini_set('display_errors','on'); version_compare(PHP_VERSION, '5.5.0') <= 0 ? error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED) : error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);   // DEBUGGING

$db = PearDatabase::getInstance();
 
 //print_r($db);

//function record_1c_upload(Vtiger_Request $request){
		
		include_once 'include/Exchangerate/exchange_rate_class.php';
		//global $adb;
		//$adb = PearDatabase::getInstance();
		//$recordid = $request->get('record');
		//$recordArray = explode(",",$recordid);
		//$moduleName = $request->getModule();
		$moduleName = "Job";

		/**
			Filters
			0) modified date - previous 24 hours
			1) ALL KZ job files ( file title = KZ)
			2) DW job files with Bank R \ Cash R costs ( job file title = DW,  Bank R <>0 or  Cash R <>0 )
			3) Job files with all statuses except deleted 
		*/

$txt = "user id date";
$myfile = file_put_contents('/var/www/html/live-gems/include/Job/log_1c.txt', $txt.PHP_EOL , FILE_APPEND | LOCK_EX);

 //$fp = fopen("MyFile.txt", "a+");
 //fwrite($fp, "\r\n". $txt);

 die('==test file--');

		$query_job_str = "SELECT vtiger_jobcf.jobid, cf_1186 as file_title, cf_1188 as location, cf_1190 as department,
  						cf_1198 as ref_no, smcreatorid, smownerid, createdtime from vtiger_jobcf
						INNER JOIN vtiger_job ON vtiger_job.jobid = vtiger_jobcf.jobid   
						INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobcf.jobid
						INNER JOIN vtiger_companycf ON vtiger_companycf.companyid = vtiger_jobcf.cf_1186
						where vtiger_crmentity.deleted=0 
						AND vtiger_crmentity.modifiedtime > DATE_SUB(NOW(), INTERVAL 24 HOUR) 
						AND (vtiger_companycf.cf_996 = 'KZ' OR vtiger_companycf.cf_996 = 'DW')

						";


/*
						$query_job_str = "SELECT vtiger_jobcf.jobid, cf_1186 as file_title, cf_1188 as location, cf_1190 as department,
  						cf_1198 as ref_no, smcreatorid, smownerid, createdtime from vtiger_jobcf
						INNER JOIN vtiger_job ON vtiger_job.jobid = vtiger_jobcf.jobid   
						INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobcf.jobid
						INNER JOIN vtiger_companycf ON vtiger_companycf.companyid = vtiger_jobcf.cf_1186
						where vtiger_crmentity.deleted=0 
						
						AND vtiger_companycf.cf_996 = 'DW' AND vtiger_job.jobid = '3072851'

						";
*/

						//AND (vtiger_companycf.cf_996 = 'KZ'
						//OR (vtiger_companycf.cf_996 = 'DW' AND ( OR )) )
						//AND vtiger_jobcf.cf_5848 != 'Success'

		$query_job = $db->pquery($query_job_str);


		$response = new Vtiger_Response();
		if ( $db->num_rows($query_job) ) {
		
			//$recordIds  = $request->get('selected_ids');

			while ($job_rs = $db->fetch_array($query_job)) {

//print_r($job_rs);
//die();
				global $adb;
				mysqli_close($adb->database->_connectionID);
				//$adb = PearDatabase::getInstance();
				$adb = new PearDatabase();
				//print_r($ndb);
				$adb->connect();
			
				$record = $job_rs['jobid'];
				$jobcreatedtime = date("YmdHis", strtotime($job_rs['createdtime']));

				$job_info = Vtiger_Record_Model::getInstanceById($record, 'Job');

//print_r($job_info);
//die();

				$jobid = $job_info->get('id');
//echo "jobid====".$jobid;
//die();
				$smownerid = $job_info->get('assigned_user_id'); 
				$userRecord = Vtiger_Record_Model::getInstanceById($smownerid, 'Users');
				$firstName = $userRecord->get('first_name');
				$lastName = $userRecord->get('last_name');
				$userName = $firstName." ".$lastName;
							
				$companyid = $job_info->get('cf_1186');
				if(!empty($companyid)){
				$companyRecord = Vtiger_Record_Model::getInstanceById($companyid, 'Company');
				$fileTitle = $companyRecord->get('cf_996');
				}
				
				$locationid = $job_info->get('cf_1188');
				if(!empty($locationid)){
				$locationRecord = Vtiger_Record_Model::getInstanceById($locationid, 'Location');
				$location = $locationRecord->get('cf_1559');
				}
				
				$departmentid = $job_info->get('cf_1190');
				if(!empty($departmentid)){
				$departmentRecord = Vtiger_Record_Model::getInstanceById($departmentid, 'Department');
				$deparmentTitle = $departmentRecord->get('cf_1542');
				}

				$accountid = $job_info->get('cf_1441');
				/**
					 Check if customer is removed from system, then send with out customer	
				*/

				if(!empty($accountid)){
					$accountRecord = Vtiger_Record_Model::getInstanceById($accountid, 'Accounts');
					$companyStatus = $accountRecord->get('cf_2403');
					if($companyStatus == 'Approved'){
					$creditLimit = $accountRecord->get('cf_1851');
					$paymentTerms = $accountRecord->get('cf_1855');
					} else {
					$creditLimit = '';
					$paymentTerms = '';	
					}
				}

				$customerid = $job_info->get('cf_1441');
				if(!empty($customerid)){
					$customerRecord = Vtiger_Record_Model::getInstanceById($customerid, 'Accounts');
					$customer = $customerRecord->get('accountname');
					$code_1c = $customerRecord->get('cf_2407');
				}

				$jer_sum_sql =  "SELECT sum(jercf.cf_1160) as total_cost_local_currency , sum(jercf.cf_1168) as total_revenue_local_currency 
                       FROM `vtiger_jercf` as jercf 
                       INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = jercf.jerid
                       INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid 
                       WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid='".$jobid."' 
                       AND crmentityrel.module='Job' AND crmentityrel.relmodule='JER'";
				$rs_jer = $adb->pquery($jer_sum_sql);
				$row_job_costing = $adb->fetch_array($rs_jer);
				
				$total_cost_local_currency = $row_job_costing['total_cost_local_currency'];
				$total_revenue_local_currency = $row_job_costing['total_revenue_local_currency'];
				
				$query_reporting_currency = 'SELECT currency.currency_code FROM vtiger_companycf as company 
											INNER JOIN vtiger_currency_info as currency ON currency.id = company.cf_1459 
											WHERE company.companyid = "'.@$companyid.'" ';
				$rs_reporting_currency = $adb->pquery($query_reporting_currency);
				$row_reporting_currency = $adb->fetch_array($rs_reporting_currency);						
				$file_title_currency   = $row_reporting_currency['currency_code'];
				
				$jer_last_sql =  "SELECT vtiger_crmentity.modifiedtime, vtiger_crmentity.createdtime FROM `vtiger_jercf` as jercf 
								INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = jercf.jerid
								INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid 
								WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid='".$jobid."'  AND crmentityrel.module='Job' 
								AND crmentityrel.relmodule='JER' order by vtiger_crmentity.modifiedtime DESC limit 1";
								
				$jer_last = $adb->pquery($jer_last_sql);
				$count_last_modified = $adb->num_rows($jer_last);
				$exchange_rate_date  = date('Y-m-d');

				if($count_last_modified>0)
				{
					$row_costing_last = $adb->fetch_array($jer_last);
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
									where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid="'.$jobid.'" AND 
									vtiger_crmentityrel.module="Job" AND vtiger_crmentityrel.relmodule="Jobexpencereport" AND
									vtiger_jobexpencereportcf.cf_1457="Expence"
									AND vtiger_jobexpencereport.owner_id = "'.$smownerid.'" 
									AND vtiger_jobexpencereportcf.cf_1214 IN (85776, 85778,85780,85782, 85784, 85786, 85788, 85790, 85792, 85798, 85800, 85802, 85804, 258706, 205753, 420293)';
				
				
				$result_jrer_cash_r = $adb->pquery($jrer_cash_r_sum_sql);
				$numRows_cash_r = $adb->num_rows($result_jrer_cash_r);
				$total_cash_r_in_usd_net = 0;
				if($numRows_cash_r>0)
				{
				while($row_job_jrer_cash_r = $adb->fetch_array($result_jrer_cash_r))
					{
						$expense_invoice_date = $row_job_jrer_cash_r['expense_invoice_date'];
								
						$CurId = $row_job_jrer_cash_r['buy_currency_id'];
						if ($CurId) {
						$q_cur = $adb->pquery('select * from vtiger_currency_info where id = "'.$CurId.'"');
						$row_cur = $adb->fetch_array($q_cur);
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
										where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid="'.$jobid.'" AND 
										vtiger_crmentityrel.module="Job" AND vtiger_crmentityrel.relmodule="Jobexpencereport" AND
										vtiger_jobexpencereportcf.cf_1457="Expence"
										AND vtiger_jobexpencereport.owner_id = "'.$smownerid.'" 
										AND vtiger_jobexpencereportcf.cf_1214 IN (85775, 85777, 85779, 85781, 85783, 85785, 85787, 85789, 85791, 85797, 85799, 85801, 85803, 258702, 205752, 420287)';
				
				$result_jrer_bank_r = $adb->pquery($jrer_bank_r_sum_sql);
				$numRows_bank_r = $adb->num_rows($result_jrer_bank_r);
				$total_bank_r_in_usd_net = 0;
				
				if($numRows_bank_r>0)
				{
					while($row_job_jrer_bank_r = $adb->fetch_array($result_jrer_bank_r))
					{
						$expense_invoice_date = $row_job_jrer_bank_r['expense_invoice_date'];
						
						if ($CurId) {
						$q_cur = $adb->pquery('select * from vtiger_currency_info where id = "'.$CurId.'"');
						$row_cur = $adb->fetch_array($q_cur);
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


				if ($fileTitle == 'DW' and $entries['bank'] < 1 and $entries['cash'] < 1) {

					//$adb->disconnect();
					//mysqli_close($adb->database->_connectionID);
					continue;
				}

				
				$ref = $job_info->get('cf_1198');
				$type = $job_info->get('cf_1200');
				$jobStatus = $job_info->get('cf_2197');
				$createdTime = $job_info->get('CreatedTime');
				$createdTime = date("YmdHis", strtotime($createdTime));
				
				$CompletionDateTime = $job_info->get('cf_4805');
				if ($CompletionDateTime != "") {
					$CompletionDate = date("YmdHis", strtotime($CompletionDateTime));
				}

				//echo $userName; exit;
    			$CLoadDate = date('Y-m-d');
				$jobRecord = array('JobRefNo' => $ref,
									'DateDoc'=> $jobcreatedtime,
									'Type'=>$type,													 
									'FileTitle'=>$fileTitle,
									'Department'=>$deparmentTitle,
									'Location'=>$location,
									'CustomerID'=>$code_1c,
									'CustomerName'=>$customer,
									'JobStatus'=>$jobStatus,
									'ExpectRevenue'=>$entries['expected_revenue_usd'],
									'ExpectCost'=>$entries['expected_cost_usd'],
									'Coordinator'=>$userName,
									'CustomerPaymentTerms'=>$paymentTerms,
									'CustomerCreditLimit'=>$creditLimit,			 
									'BankR'=>$entries['bank'],
									'CashR'=>$entries['cash'],
									'CompletionDate'=>$CompletionDate,
									'1CStatus'=>'Success',
									'1CLoadDate'=>$CLoadDate
									);
					 //echo "<pre>"; print_r($jobRecord); exit;
					//print_r($jobRecord); 
					/**
							$web1C = 'http://89.218.38.221/glws/ws/CreateJobFile?wsdl';
							pass:     6fc@t\Vy
							
							// Test
							$web1C = 'http://89.218.38.221/gl/ws/CreateJobFile?wsdl'; 
							pass: 906900
					*/	

					
					//$web1C = 'http://89.218.38.221/glws/ws/CreateJobFile?wsdl';  //Live 1C
					//$web1C = 'http://89.218.38.221/gl/ws/CreateJobFile?wsdl'; 
					$web1C = 'http://89.218.38.197/gl/ws/CreateJobFile?wsdl';  // test
					$con1C = array( 'login' => 'AdmWS', //AdmWS Live
									//'password' => '6fc@t\Vy', //6fc@t\Vy Live //906900 Test
									'password' => '906900',		// test							
									'soap_version' => SOAP_1_2,
									'cache_wsdl' => WSDL_CACHE_NONE, //WSDL_CACHE_MEMORY, //, WSDL_CACHE_NONE, WSDL_CACHE_DISK or WSDL_CACHE_BOTH
									'exceptions' => true,
									'trace' => 1);

						if (!function_exists('is_soap_fault')) {
							echo '<br>not found module php-soap.<br>';
							return false;
						}		
						
						try {
							$Client1C = new SoapClient($web1C, $con1C);
										
						} catch(SoapFault $e) {
							var_dump($e);
							echo '<br>error at connecting to 1C<br>';
							return false;
						}
						if (is_soap_fault($Client1C)){
							echo '<br>inner server error at connecting to 1C<br>';
							return false;
						}

						$idc = $Client1C;
						$par = $jobRecord;

						if (is_object($idc)) {
							try {
							
							  
							  $ret1c = $idc->CreateJobFile($par);
							  //print_r($ret1c);
							  
							 	//stdClass Object
								//(
								    //[return] => EXP-A-00004/21
								//)
							  if ($ret1c->return == $ref) {
								  $sql = "UPDATE vtiger_jobcf SET cf_5848 = 'Success', cf_5846 = '".date('Y-m-d')."' WHERE jobid = '".$jobid."'";
								  $result = $adb->pquery($sql);

								  //echo 'Success_'.date('Y-m-d');
								  $response->setResult(array(vtranslate('LBL_JOBFILE_SUCCESSFULLY_POSTED_TO_1C', $moduleName)));
							  }

							} catch (SoapFault $e) {
								//echo "<pre>";
								//print_r($e);
								//echo 'Failed_';
								$response->setError(vtranslate('LBL_JOBFILE_FAILED', $moduleName));
							}   
						}
						else{
							//var_dump($idc);
							//echo '<br>no connection to 1C<br>';
							$response->setError(vtranslate('LBL_CONNECTION_TO_1C', $moduleName));
						}
						
						
						//$adb->disconnect();
			} // end of while
		
		
		//$response = new Vtiger_Response();
		//$response->setResult(true);
		$response->emit();

	} // end of if
	else {
		echo "No Record Found";
	}

	//$db->disconnect();
	mysqli_close($db->database->_connectionID);

/*
	echo "--after disconnect---";
	//print_r($db);
$ndb = new PearDatabase();
//print_r($ndb);
$ndb->connect();
print_r($ndb);
//$ndb = PearDatabase::getInstance();


	// $query_job_str = "SELECT vtiger_jobcf.jobid, cf_1186 as file_title, cf_1188 as location, cf_1190 as department,
 //  						cf_1198 as ref_no, smcreatorid, smownerid, createdtime from vtiger_jobcf
	// 					INNER JOIN vtiger_job ON vtiger_job.jobid = vtiger_jobcf.jobid   
	// 					INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobcf.jobid
	// 					INNER JOIN vtiger_companycf ON vtiger_companycf.companyid = vtiger_jobcf.cf_1186
	// 					where vtiger_crmentity.deleted=0 
	// 					AND vtiger_crmentity.modifiedtime > DATE_SUB(NOW(), INTERVAL 24 HOUR) 
	// 					AND (vtiger_companycf.cf_996 = 'KZ' OR vtiger_companycf.cf_996 = 'DW')

	// 					";



						$query_job_str = "SELECT vtiger_jobcf.jobid, cf_1186 as file_title, cf_1188 as location, cf_1190 as department,
  						cf_1198 as ref_no, smcreatorid, smownerid, createdtime from vtiger_jobcf
						INNER JOIN vtiger_job ON vtiger_job.jobid = vtiger_jobcf.jobid   
						INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobcf.jobid
						INNER JOIN vtiger_companycf ON vtiger_companycf.companyid = vtiger_jobcf.cf_1186
						where vtiger_crmentity.deleted=0 
						
						AND vtiger_companycf.cf_996 = 'DW' AND vtiger_job.jobid = '3072851'

						";


						//AND (vtiger_companycf.cf_996 = 'KZ'
						//OR (vtiger_companycf.cf_996 = 'DW' AND ( OR )) )
						//AND vtiger_jobcf.cf_5848 != 'Success'

		$query_job = $ndb->pquery($query_job_str);


		$num = $ndb->num_rows($query_job);
echo "num=".$num;


*/


	//} // end of function
?>