<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Job_ExportData_Action extends Vtiger_ExportData_Action {

	var $moduleCall = false;
	public function requiresPermission(\Vtiger_Request $request) {
		/*
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'Export');
        if (!empty($request->get('source_module'))) {
            $permissions[] = array('module_parameter' => 'source_module', 'action' => 'Export');
		}
		*/
		return $permissions=true;
	}

	/**
	 * Function is called by the controller
	 * @param Vtiger_Request $request
	 */
	function process(Vtiger_Request $request) {
		$this->ExportData($request);
	}

	function ProfitShare(Vtiger_Request $request,$entries) {
		//echo "<pre>"; print_r($entries); exit;
		foreach($entries as $entry)
		{
			$job_ref_no = $entry['cf_1198'];
			$job_ref = "";
			$db = PearDatabase::getInstance();
			$db1 = PearDatabase::getInstance();
			$query = "SELECT * FROM vtiger_jobtask
					inner join vtiger_jobcf on vtiger_jobtask.job_id = vtiger_jobcf.jobid
					inner join vtiger_crmentity on vtiger_jobcf.jobid = vtiger_crmentity.crmid
					where vtiger_crmentity.deleted = 0 and vtiger_jobcf.cf_1198 = '".$job_ref_no."' and job_owner = '0'"; //job with crmentity can be made
					$result = $db->pquery($query, array());
					//echo $query;
					for($j=0; $j<$db->num_rows($result); $j++) {
						$db_job = PearDatabase::getInstance();
						$actual_expense_cost_usd = "";
						$actual_selling_cost_usd = "";
						$net_profit = "";
						$ps_owner_id = "";
						$ps_cost = "";
						$ps_profit = "";
						$ps_location = '';
						$ps_department = '';
						$coordinator_loc = '';
						$coordinator_dep = '';
						$query_profit = "Select * from job_profit where job_id = '".$db->query_result($result,$j,'jobid')."' and ps_owner_id = ".$db->query_result($result,$j,'user_id');
						$result_profit = $db->pquery($query_profit, array());
								$profit_count  = $db->num_rows($result_profit);
								if($profit_count>0)
								{
									$job_profit_detail = $db->fetch_array($result_profit);
									$ps_cost = $job_profit_detail['cost'];
									$ps_profit = $job_profit_detail['profit_share_received'];
									$ps_location = Vtiger_LocationList_UIType::getDisplayValue($job_profit_detail['location_id']);
									$ps_department = Vtiger_DepartmentList_UIType::getDisplayValue($job_profit_detail['department_id']);
								}
								$ps_owner_id = Vtiger_GLKUserList_UIType::getDisplayValue($db->query_result($result,$j,'user_id'));
								$sql1 = $db1->pquery("SELECT * FROM `vtiger_users` WHERE `id`=".$db->query_result($result,$j,'user_id')." LIMIT 1");
								$sql2 = $db1->pquery("SELECT * FROM `vtiger_users` WHERE `id`=".$db->query_result($result,$j,'user_id')." LIMIT 1");
								$coordinator_loc = Vtiger_LocationList_UIType::getDisplayValue($db1->fetch_array($sql1)['location_id']);
								$coordinator_dep = Vtiger_DepartmentList_UIType::getDisplayValue($db1->fetch_array($sql2)['department_id']);

						$query_job = "SELECT * from job_report where job_id =?";
								$params = array($db->query_result($result,$j,'jobid'));
								$result1 = $db->pquery($query_job, $params);
								$report_count  = $db_job->num_rows($result1);
								if($report_count>0)
								{
									$job_report_detail = $db_job->fetch_array($result1);
									$actual_expense_cost_usd = number_format($job_report_detail['actual_expense_cost_usd'] , 2 ,  "." , "," );
									$actual_selling_cost_usd = number_format($job_report_detail['actual_selling_cost_usd'] , 2 ,  "." , "," );
									$net_profit = number_format($job_report_detail['net_profit'] , 2 ,  "." , "," );
								}
						if($job_ref == $db->query_result($result,$j,'cf_1198'))
						{
							$actual_expense_cost_usd = 0;
							$actual_selling_cost_usd = 0;
							$net_profit = 0;
						}
						else
						{
							$job_ref = $db->query_result($result,$j,'cf_1198');
						}
						//$entries_vendor[] = $db->fetchByAssoc($result, $j);

                 		$entries1[] = Array
							(
								'job_ref_no' => $db->query_result($result,$j,'cf_1198'),
								'file_title' => $entry['cf_1186'],
								'customer' => $entry['cf_1441'],
								'department_paying' => $entry['cf_1190'],
								'location' => $entry['cf_1188'],
								'revenue' => $actual_selling_cost_usd,
								'cost_usd' => $actual_expense_cost_usd,
								'netprofit' => $net_profit,
								'location_id' => $ps_location,
								'department_id' => $ps_department,
								'cost' => $ps_cost,
								'profit_share_received' => $ps_profit,
								'ps_owner_id' => $ps_owner_id,
								'owner_location_id' => $coordinator_loc,
								'owner_department_id' => $coordinator_dep
							);
						//$entries[] = $db->fetchByAssoc($result, $j);
					}
		}
		$MIS_report_header = array('Job Ref No.', 'File Title', 'Customer', 'Department Paying', 'Branch Paying', 'Revenue USD', 'Cost of Sales (USD)', 'Net Profit USD', 'Branch Receiving','Department','Cost', 'Profit Share', 'Coordonator', 'Coordinator Location', 'Coordinator Department');
		$this->getReportXLS($request, $MIS_report_header, $entries1);
		//echo "<pre>"; print_r($entries1); exit;
	}

	private $moduleInstance;
	private $focus;

	/**
	 * Function exports the data based on the mode
	 * @param Vtiger_Request $request
	 */
	function ExportData(Vtiger_Request $request) {

		$db = PearDatabase::getInstance();
		$moduleName = $request->get('source_module');

		$this->moduleInstance = Vtiger_Module_Model::getInstance($moduleName);
		$this->moduleFieldInstances = $this->moduleFieldInstances($moduleName);
		$this->focus = CRMEntity::getInstance($moduleName);

		$query = $this->getExportQuery($request);
		$result = $db->pquery($query, array());

		$redirectedModules = array('Users', 'Calendar');
		if($request->getModule() != $moduleName && in_array($moduleName, $redirectedModules) && !$this->moduleCall){
			$handlerClass = Vtiger_Loader::getComponentClassName('Action', 'ExportData', $moduleName);
			$handler = new $handlerClass();
			$handler->ExportData($request);
			return;
		}
		$translatedHeaders = $this->getHeaders();
		$entries = array();
		for ($j = 0; $j < $db->num_rows($result); $j++) {
			$entries[] = $this->sanitizeValues($db->fetchByAssoc($result, $j));
		}


		$Exporttype = $request->get('Exporttype');
		if($Exporttype=='profit_share')
			{
				$this->ProfitShare($request,$entries);
				exit;
			}
			if($Exporttype=='finance_format' || $Exporttype=='kerry_format' )
			{
				//include("include/Exchangerate/exchange_rate_class.php");
				$profit_number = 0;
				//error_reporting(E_ALL);
				foreach($entries as $key => $entry)
				{
					$job_ref_no = $entry['cf_1198'];

					$db_first = PearDatabase::getInstance();
					$query_first_jobid = "SELECT jobid from vtiger_jobcf
										  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobcf.jobid
								   		  where vtiger_crmentity.deleted=0 AND vtiger_jobcf.cf_1198=?";

					$params_jobid = array($job_ref_no);
					$result_job = $db_first->pquery($query_first_jobid, $params_jobid);
					$row_job_id_info = $db_first->fetch_array($result_job);
					$job_id = $row_job_id_info['jobid'];

					if(!empty($job_id))
					{
					$final_number = 0;
					$db_job = PearDatabase::getInstance();
					$query_job = "SELECT * from job_report where job_id =?";

					$params = array($job_id);
					$result = $db_job->pquery($query_job, $params);
					$report_count  = $db_job->num_rows($result);
					if($report_count>0)
					{
					$job_report_detail = $db_job->fetch_array($result);

					$entries[$key]['expected_cost_usd'] = number_format($job_report_detail['expected_cost_usd'], 2 ,  "." , ",");
					$entries[$key]['expected_profit_usd'] = number_format($job_report_detail['expected_profit_usd'], 2 ,  "." , ",");
					$entries[$key]['expected_revenue_usd'] = number_format($job_report_detail['expected_revenue_usd'], 2 ,  "." , ",");

					$entries[$key]['actual_expense_cost_usd'] = number_format($job_report_detail['actual_expense_cost_usd'] , 2 ,  "." , "," );
					$entries[$key]['actual_selling_cost_usd'] = number_format($job_report_detail['actual_selling_cost_usd'] , 2 ,  "." , "," );
					$entries[$key]['net_profit'] = number_format($job_report_detail['net_profit'] , 2 ,  "." , "," );
					$entries[$key]['expense_deviation']   = $job_report_detail['expense_deviation'];
					$entries[$key]['selling_deviation']   = $job_report_detail['selling_deviation'];
					$entries[$key]['cash'] = number_format($job_report_detail['cash'] , 2 ,  "." , "," );
					$entries[$key]['cash_n'] =number_format($job_report_detail['cash_n'] , 2 ,  "." , "," );
					$entries[$key]['bank'] =number_format($job_report_detail['bank'] , 2 ,  "." , "," );
					$entries[$key]['bank_n'] =number_format($job_report_detail['bank_n'] , 2 ,  "." , "," );
					$entries[$key]['packing_material_own'] = number_format($job_report_detail['packing_material_own'] , 2 ,  "." , "," );
					$entries[$key]['GlkInvoice'] = $job_report_detail['glk_invoice_no'];

					$db_selling_profit = PearDatabase::getInstance();
					$jrer_selling_profit = "SELECT * FROM job_profit WHERE job_id=? ";
					$params_selling_profit = array($job_id);
					$result_selling_profit = $db_selling_profit->pquery($jrer_selling_profit, $params_selling_profit);

					$profit_share_data = array();

					for($i=0; $i< $db_selling_profit->num_rows($result_selling_profit); $i++ ) {
						$row_selling_profit = $db_selling_profit->fetch_row($result_selling_profit,$i);

						$profit_share_data[] = array(
								                      'location_id' => $row_selling_profit['location_id'],
													  'department_id' => $row_selling_profit['department_id'],
													  'user_id' => $row_selling_profit['ps_owner_id'],
													  'cost' => number_format($row_selling_profit['cost'] , 2 ,  "." , "," ),
													  'net_profit' => number_format($row_selling_profit['profit_share_received'] , 2 ,  "." , "," ),
													);

					}

					//echo "<pre>";
					//print_r($profit_share_data);
					//exit;
					if(!empty($profit_share_data))
					{
						foreach($profit_share_data as $p_key => $data_profit)
						{
							$department = '';
							$location = '';
							if($data_profit['department_id']!=0){
								$department = Vtiger_DepartmentList_UIType::getDisplayValue($data_profit['department_id']);
							}
							if($data_profit['location_id']!=0){
								$location = Vtiger_LocationList_UIType::getDisplayValue($data_profit['location_id']);
							}

							$user_info = Users_Record_Model::getInstanceById($data_profit['user_id'], 'Users');

							$entries[$key]['location_'.$p_key] =$location;
							$entries[$key]['department_'.$p_key] =$department;
							$entries[$key]['cost_'.$p_key] =$data_profit['cost'];
							$entries[$key]['profit_'.$p_key] =$data_profit['net_profit'];
							$entries[$key]['coordinator_'.$p_key] =$user_info->get('first_name').' '.$user_info->get('last_name');
							//$entries[$key]['coordinator'] ='';

							$final_number++;
							$count_task_inuser = count($profit_share_data);
							if($count_task_inuser > $profit_number)
							{
								$profit_number = $count_task_inuser;
							}
							/*if($final_number > $profit_number)
							{
								$profit_number = $final_number;
							}*/
						}
					}
					else{
						$entries[$key]['location'] ='';
						$entries[$key]['department'] ='';
						$entries[$key]['cost'] ='';
						$entries[$key]['profit'] ='';
						$entries[$key]['coordinator'] ='';
					}


					//End of Profit Share

					$entries[$key]['total'] = '';
					 }
					}
					else{

					$entries[$key]['expected_cost_usd'] = '';
					$entries[$key]['expected_profit_usd'] = '';
					$entries[$key]['expected_revenue_usd'] = '';

					$entries[$key]['actual_expense_cost_usd'] ='';
					$entries[$key]['actual_selling_cost_usd'] = '';
					$entries[$key]['net_profit']   = '';
					$entries[$key]['expense_deviation']   = '';
					$entries[$key]['selling_deviation']   = '';
					$entries[$key]['cash']   = '';
					$entries[$key]['cash_n'] = '';
					$entries[$key]['bank']   = '';
					$entries[$key]['bank_n'] = '' ;
					$entries[$key]['packing_material_own'] = '' ;
					$entries[$key]['GlkInvoice'] ='';
					$entries[$key]['location'] ='';
					$entries[$key]['department'] ='';
					$entries[$key]['cost'] ='';
					$entries[$key]['profit'] ='';
					$entries[$key]['coordinator'] ='';

					$entries[$key]['total'] = '';

					}


				}

				$translatedHeaders[] = 'Exp.Cost';
				$translatedHeaders[] = 'Exp.Profit';
				$translatedHeaders[] = 'Exp.Revenue';

				$translatedHeaders[] = 'Act.Cost';
				$translatedHeaders[] = 'Act.Revenue';
				$translatedHeaders[] = 'Net Profit';
				$translatedHeaders[] = 'Deviation Cost';
				$translatedHeaders[] = 'Deviation Revenue';
				$translatedHeaders[] = 'Cash';
				$translatedHeaders[] = 'Cash N';
				$translatedHeaders[] = 'Bank';
				$translatedHeaders[] = 'Bank N';
				$translatedHeaders[] = 'Packing Material Cost';
				$translatedHeaders[] = 'Invoice';

				for($j=0;$j<$profit_number;$j++)
				{
					$translatedHeaders[] = 'Loc';
					$translatedHeaders[] = 'Srv';
					$translatedHeaders[] = 'Cost';
					$translatedHeaders[] = 'Profit';
					$translatedHeaders[] = 'Coordinator';
				}


				$translatedHeaders[] = '';

				//echo "<pre>";
				//print_r($entries);
				//exit;
			}
			elseif ($Exporttype == 'vendor')
			{
				include('include/Exchangerate/exchange_rate_class.php');
				global $adb;
				$out = [];
					$i = 0;
					/*
					{"name":"235-70409975 Cazador-5","smownerid":"d.akhmetova","createdtime":"2018-04-23 08:50:57","modifiedtime":"2018-08-09 06:00:12","cf_1186":"KZ","cf_1188":"ALA","cf_1200":"Import","cf_1190":"CTD","cf_1198":"IMP-C-06033\/18","cf_1441":"TOO Apis","cf_1879":"409\/2016\/0156","cf_2197":"Completed","cf_3527":"Dinara Akhmetova","cf_4805":"2018-08-09","cf_5417":"NA","cf_1072":"Cazador","cf_1074":"APIS","cf_1504":"TR","cf_1506":"KZ","cf_1508":"Istanbul, Turkey","cf_1510":"Almaty, Kazakhstan","cf_5435":"","cf_5437":"","cf_1512":"","cf_1514":"","cf_1516":"","cf_1583":"","cf_1589":"","cf_1591":"","cf_4933":"","cf_4935":"","cf_1082":"","cf_4925":"","cf_1711":"","cf_1429":"245","cf_1084":"4760","cf_1086":"0","cf_1520":"KG","cf_4945":"0.00","cf_1092":"","cf_1522":"CBM","cf_1524":"","cf_1547":"textil","cf_1518":"textil","cf_4921":"Not applicable","cf_1721":"","cf_4939":"PREPAID","cf_4931":"Not applicable","cf_4927":"Non coload business","cf_4929":"NA","cf_1098":"","cf_1102":"","cf_1096":"235-70409975","cf_4937":"CIP","cf_2387":"180004927","cf_1526":"","cf_4923":"","cf_3523":"","cf_4943":"Not applicable","cf_4941":"Not applicable","cf_1100":"0","cf_1569":"KZT","cf_1532":"0.00","cf_1534":"0","cf_1528":"0.00","cf_1585":""}
					*/

					/*

					$db_selling_profit = PearDatabase::getInstance();
					$jrer_selling_profit = "SELECT * FROM job_profit WHERE job_id=? ";
					$params_selling_profit = array($job_id);
					$result_selling_profit = $db_selling_profit->pquery($jrer_selling_profit, $params_selling_profit);

					*/

					/*
					// $air_headers = array('title','Job Ref #','File Title','Location','Department','Customer','Job status','Charge','Pay To','Buy (Local Cur NET)','Vendor Curr');

					$air_headers = array('title','Job Ref #','File Title','Location','Department','Customer','Job status','Charge','Pay To','VAT Rate','VAT','Buy (Vendor Cur Gross)','Vendor Curr','Exch Rate','Buy (Local Curr Gross)','Buy (Local Cur NET)','Cost in ($)');
					*/

					$db = PearDatabase::getInstance();
					foreach ($entries as $key => $value) {
						$info = [];
						$info['title'] = 'Job';
						$info['Job Ref #'] = $value['cf_1198'];
						$info['File Title'] = $value['cf_1186'];
						$info['Location'] = $value['cf_1188'];
						$info['Department'] = $value['cf_1190'];
						$info['Customer'] = $value['cf_1441'];
						$info['Job status'] = $value['cf_2197'];
						$info['Charge'] = '';
						$info['Pay To'] = '';
						$info['Buy (Vendor Cur Net)'] = '';
						$info['VAT Rate'] = '';
						$info['VAT'] = '';
						$info['Buy (Vendor Cur Gross)'] = '';
						$info['Vendor Curr'] = '';
						$info['Exch Rate'] = '';
						$info['Buy (Local Curr Gross)'] = '';
						$info['Buy (Local Cur NET)'] = '';
						$info['Cost in ($)'] = '';
						$out[] = $info;
						$result_job = $db->pquery("SELECT jobid,smownerid from vtiger_jobcf INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobcf.jobid where vtiger_crmentity.deleted=0 AND vtiger_jobcf.cf_1198=?", array($value['cf_1198']));
						$row_job_id_info = $db->fetch_array($result_job);
						$job_id = $row_job_id_info['jobid'];
						$owner_id = $row_job_id_info['smownerid'];
						$sql = $db->pquery("SELECT  * FROM `vtiger_jobexpencereport` INNER JOIN `vtiger_crmentity` ON `vtiger_crmentity`.crmid=`vtiger_jobexpencereport`.`jobexpencereportid` INNER JOIN `vtiger_crmentityrel` ON (`vtiger_crmentityrel`.`relcrmid` = `vtiger_crmentity`.`crmid`) LEFT JOIN `vtiger_jobexpencereportcf` AS `vtiger_jobexpencereportcf` ON `vtiger_jobexpencereportcf`.`jobexpencereportid`=`vtiger_jobexpencereport`.`jobexpencereportid` INNER JOIN `vtiger_locationcf` AS `vtiger_locationcf` ON `vtiger_locationcf`.`locationid`=`vtiger_jobexpencereportcf`.`cf_1477` INNER JOIN `vtiger_departmentcf` AS `vtiger_departmentcf` ON `vtiger_departmentcf`.`departmentid`=`vtiger_jobexpencereportcf`.`cf_1479` INNER JOIN `vtiger_currency_info` AS `vtiger_currency_info` ON `vtiger_currency_info`.`id`=`vtiger_jobexpencereportcf`.`cf_1345` WHERE `vtiger_crmentity`.`deleted`=0 AND `vtiger_crmentityrel`.`crmid`=? and `vtiger_crmentityrel`.`module`='Job' AND `vtiger_jobexpencereport`.`owner_id`=".$owner_id." AND `vtiger_crmentityrel`.`relmodule`='Jobexpencereport' and `vtiger_jobexpencereportcf`.`cf_1457`='Expence'", [$job_id]);

						$num = $db->num_rows($sql);

						for ($i=0;$i<$num;$i++) {
							$res = $db->fetch_array($sql);
							//usd rate
			                 $invoice_date = $res['cf_1216'];
			                 $b_exchange_rate = 0;
			                 if($invoice_date == ''){
			                 		$invoice_date = date('Y-m-d', strtotime($res['createdtime']));
			                 }
			                 	$job_info = Vtiger_Record_Model::getInstanceById($job_id,'Job');
                 				$company_id = $job_info->get('cf_1186');//$value['cf_1186'];
								$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency($company_id);
								$file_title_currency = $reporting_currency;
			                 if($file_title_currency!='USD')
								{
									$b_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $invoice_date);
								}else{
									$b_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $invoice_date);
								}
								$value_in_usd_normal = $res['cf_1349'];
								if($file_title_currency!='USD')
								{
									$value_in_usd_normal = $res['cf_1349']/$b_exchange_rate;
								}

								$value_in_usd = number_format($value_in_usd_normal,2,'.','');
								//echo $value_in_usd." , ".$b_exchange_rate."<br>";
			                 //usd rate
							$info = [];
							$info['title'] = 'Expense';
							$info['Job Ref #'] = $value['cf_1198'];
							$info['File Title'] = '';
							$info['Location'] = $res['cf_1559'];//$res['cf_1477'];
							$info['Department'] = $res['cf_1542'];//$res['cf_1479'];
							$info['Customer'] = '';
							$info['Job status'] = '';
							$sql1 = $db->pquery("SELECT `name` FROM `vtiger_chartofaccount` WHERE `chartofaccountid`='".$res['cf_1453']."' LIMIT 1");
							$info['Charge'] = $db->fetch_array($sql1)['name'];
							$sql1 = $db->pquery("SELECT `accountname` FROM `vtiger_account` WHERE `accountid`='".$res['cf_1367']."' LIMIT 1");
							$info['Pay To'] = $db->fetch_array($sql1)['accountname'];
							$info['Invoice No'] = $res['cf_1212'];
                 			$info['Invoice Date'] = $res['cf_1216'];
							$info['Buy (Vendor Cur Net)'] = $res['cf_1337'];
							$info['VAT Rate'] = $res['cf_1339'];
							$info['VAT'] = $res['cf_1341'];
							$info['Buy (Vendor Cur Gross)'] = $res['cf_1343'];
							$info['Vendor Curr'] = $res['currency_code'];//$res['cf_1345'];
							$info['Exch Rate'] = $res['cf_1222'];
							$info['Buy (Local Curr Gross)'] = $res['cf_1347'];
							$info['Buy (Local Cur NET)'] = $res['cf_1349'];
							//$info['cost in ($)'] = '';
							$info['Exchange Rate'] = $b_exchange_rate;
                 			$info['Value in USD'] = $value_in_usd;
							$out[] = $info;
						}

						//INNER JOIN `vtiger_locationcf` AS `vtiger_locationcf`=`vtiger_jobexpencereportcf`.`cf_1477`
						/*
						{"0":"1030982","jobexpencereportid":"1030982","1":"1013766","name":"1013766","2":"1030981","jerid":"1030981","3":"85757","company_id":"85757","4":"57","user_id":"57","5":"57","owner_id":"57","6":null,"jrer_buying_id":null,"7":"6010-000008-ALA-C","b_gl_account":"6010-000008-ALA-C","8":"1610-ALA-C","b_ar_gl_account":"1610-ALA-C","9":"1013766","job_id":"1013766","10":"no","selling_expence":"no","11":null,"jrer_selling_id":null,"12":null,"gl_account":null,"13":null,"ar_gl_account":null,"14":null,"s_jrer_buying_id":null,"15":"Expense","job_costing_type":"Expense","16":null,"invoice_instruction_no":null,"17":null,"serial_number":null,"18":null,"year_no":null,"19":null,"invoice_no":null,"20":null,"accept_generate_invoice":null,"21":null,"b_send_to_head_of_department_for_approval":null,"22":null,"b_head_of_department_approval_status":null,"23":null,"b_send_to_payables_and_generate_payment_voucher":null,"24":null,"b_payables_approval_status":null,"25":null,"b_confirmed_send_to_accounting_software":null,"26":null,"head_role":null,"27":null,"preview_instruction_no":null,"28":null,"fleet_trip_id":null,"29":null,"fleettrip_id":null,"30":null,"roundtrip_id":null,"31":"1030982","crmid":"1013766","32":"57","smcreatorid":"57","33":"57","smownerid":"57","34":"57","modifiedby":"57","35":"Jobexpencereport","setype":"Jobexpencereport","36":"NULL","description":"NULL","37":"2018-05-03 03:28:26","createdtime":"2018-05-03 03:28:26","38":"2018-05-03 04:21:08","modifiedtime":"2018-05-03 04:21:08","39":null,"viewedtime":null,"40":null,"status":null,"41":"0","version":"0","42":"1","presence":"1","43":"0","deleted":"0","44":"1013766","label":"1013766","45":"1013766","46":"Job","module":"Job","47":"1030982","relcrmid":"1030982","48":"Jobexpencereport","relmodule":"Jobexpencereport","49":"1030982","50":"2018-05-03","cf_1210":"2018-05-03","51":"","cf_1212":"","52":"85794","cf_1214":"85794","53":"2018-05-03","cf_1216":"2018-05-03","54":"1.0000","cf_1222":"1.0000","55":"0.00","cf_1228":"0.00","56":"0.0000","cf_1230":"0.0000","57":"0.0000","cf_1232":"0.0000","58":"","cf_1234":"","59":"0.0000","cf_1236":"0.0000","60":"0.0000","cf_1238":"0.0000","61":"0.0000","cf_1240":"0.0000","62":"0.0000","cf_1242":"0.0000","63":"0","cf_1244":"0","64":"0","cf_1246":"0","65":"0","cf_1248":"0","66":"","cf_1250":"","67":"450.0000","cf_1337":"450.0000","68":"0.00","cf_1339":"0.00","69":"0.00","cf_1341":"0.00","70":"450.0000","cf_1343":"450.0000","71":"1","cf_1345":"1","72":"450.0000","cf_1347":"450.0000","73":"450.0000","cf_1349":"450.0000","74":"450.0000","cf_1351":"450.0000","75":"0.0000","cf_1353":"0.0000","76":null,"cf_1355":null,"77":"0.0000","cf_1357":"0.0000","78":"","cf_1359":"","79":"","cf_1361":"","80":"","cf_1363":"","81":"","cf_1365":"","82":"","cf_1367":"","83":"Mira Karatalova Overtime","cf_1369":"Mira Karatalova Overtime","84":"","cf_1445":"","85":"","cf_1447":"","86":"","cf_1449":"","87":"85867","cf_1453":"85867","88":"","cf_1455":"","89":"Expence","cf_1457":"Expence","90":"85805","cf_1477":"85805","91":"85837","cf_1479":"85837","92":"","cf_1973":"","93":"","cf_1975":"","94":"85757","cf_2191":"85757","95":"","cf_2193":"","96":"","cf_2195":"","97":"","cf_2325":"","98":"0","cf_2439":"0","99":"","cf_2691":"","100":"","cf_2695":"","101":"","cf_3293":"","102":"0","cf_4091":"0","103":"","cf_5287":"","104":"0","cf_5289":"0"}
						*/


					}

					$entries = $out;
			}
			elseif($Exporttype=='kpi')
			{
				$out = [];
				$i = 0;
				foreach ($entries as $key => $value) {
					$info = [];
					$info['id'] = ++$i;
					$info['Tem'] = '';
					$info['JS No'] = $value['cf_1198'];
					$info['File Title'] = $value['cf_1186'];
					$info['Destination'] = $value['cf_1510'].' - '.$value['cf_1506'];


					$job_ref_no = $value['cf_1198'];

					$db_first = PearDatabase::getInstance();
					$query_first_jobid = "SELECT jobid from vtiger_jobcf
										  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobcf.jobid
								   		  where vtiger_crmentity.deleted=0 AND vtiger_jobcf.cf_1198=?";

					$params_jobid = array($job_ref_no);
					$result_job = $db_first->pquery($query_first_jobid, $params_jobid);
					$row_job_id_info = $db_first->fetch_array($result_job);
					$job_id = $row_job_id_info['jobid'];

					$actual_selling_cost_usd = '';
					if(!empty($job_id))
					{
						$final_number = 0;
						$db_job = PearDatabase::getInstance();
						$query_job = "SELECT * from job_report where job_id =?";

						$params = array($job_id);
						$result = $db_job->pquery($query_job, $params);
						$report_count  = $db_job->num_rows($result);
						if($report_count>0)
						{
						$job_report_detail = $db_job->fetch_array($result);
						$actual_selling_cost_usd = number_format($job_report_detail['actual_selling_cost_usd'] , 2 ,  "." , "," );
						}
					}


					//$account = Vtiger_Record_Model::getInstanceById($account_id, 'Accounts');

					/*
					{"name":"CarreFour KZ to TBS","smownerid":"i.meskhi","createdtime":"2017-06-29 06:18:39","modifiedtime":"2018-08-08 05:47:18","cf_1186":"GE","cf_1188":"TBS","cf_1200":"Import","cf_1190":"RRS","cf_1198":"IMP-R-09633\/17","cf_1441":"Carrefour \/ MAF (Majid Al Futtaim Group) (UAE)","cf_1879":"","cf_2197":"Revision","cf_3527":"Ia Meskhi","cf_4805":"","cf_5417":"","cf_1072":"Carrefour Kazakhstan","cf_1074":"Carrefour Tbilisi","cf_1504":"KZ","cf_1506":"GE","cf_1508":"Almaty, Almaty Province, Kazakhstan","cf_1510":"Tbilisi, Georgia","cf_5435":"","cf_5437":"","cf_1512":"","cf_1514":"","cf_1516":"","cf_1583":"","cf_1589":"","cf_1591":"","cf_4933":"","cf_4935":"","cf_1082":"","cf_4925":"","cf_1711":"Road","cf_1429":"1","cf_1084":"3000","cf_1086":"0","cf_1520":"KG","cf_4945":"0.00","cf_1092":"","cf_1522":"CBM","cf_1524":"","cf_1547":"Furniture\/IT equipment","cf_1518":"Furniture\/IT equipment","cf_4921":"Not applicable","cf_1721":"","cf_4939":"COLLECT","cf_4931":"Not applicable","cf_4927":"Non coload business","cf_4929":"n\/a","cf_1098":"DTD","cf_1102":"","cf_1096":"","cf_4937":"EXW","cf_2387":"","cf_1526":"","cf_4923":"","cf_3523":"","cf_4943":"Not applicable","cf_4941":"Not applicable","cf_1100":"0","cf_1569":"KZT","cf_1532":"0.00","cf_1534":"0","cf_1528":"0.00","cf_1585":""}
					*/

					//$info['Account #'] = '';//$account->get('account_no');
					$info['Coordinator'] =  $value['smownerid'];
					$info['Customer'] = $value['cf_1441'];
					$info['Branch'] = $value['cf_1188'];
					$info['Department'] = $value['cf_1190'];


					$expected_pick_up = explode('-',$value['cf_1516']);
					$info['Expected pick up'] = ($expected_pick_up[2]!='')?$expected_pick_up[2].'/'.$expected_pick_up[1].'/'.$expected_pick_up[0]:'';
					$actual_pick_up = explode('-',$value['cf_1589']);
					$info['Actual pick up'] = ($actual_pick_up[2]!='')?$actual_pick_up[2].'/'.$actual_pick_up[1].'/'.$actual_pick_up[0]:'';
					$info['Difference in days (pick up)'] = '';
					$info['Late or Not (pick up)'] = '';
					$expected_delivery = explode('-',$value['cf_1583']);
					$info['Expected delivery date'] = ($expected_delivery[2]!='')?$expected_delivery[2].'/'.$expected_delivery[1].'/'.$expected_delivery[0]:'';
					$actual_delivery = explode('-',$value['cf_1591']);
					$info['Actual Delivery date'] = ($actual_delivery[2]!='')?$actual_delivery[2].'/'.$actual_delivery[1].'/'.$actual_delivery[0]:'';
					$info['Difference in days (delivery)'] = '';
					$info['Later or Not (delivery)'] = '';
					$info['Total transit time'] = '';
					$info['amount'] = $actual_selling_cost_usd;
					$out[] = $info;
				}

				//$air_headers =   array('id','Tem','JF No','File Title','Destination','Expected pick up', 'Actual pick up','Difference in days','Later or Not', 'Expected dlivery date', 'Actual delivery date','Difference in days','Later or Not','Total transit time','Amount');


				$entries = $out;
			}elseif($Exporttype=='dxb'){

				///// excel spread sheet integration ////
				spl_autoload_register(function ($class_name) {
					$path = str_replace('\\', '/', $class_name);
						include getcwd().'/libraries/'.$path . '.php';
				});
				///// excel spread sheet integration  end here////
				$newhtml = '<table>
											<thead>
												<tr>
												<th colspan="2" style="background:#CCCCFF !important">Ref No</th>
												<th colspan="5" style="background:#CCCCFF !important">Customer</th>
												<th colspan="5" style="background:#CCCCFF !important">Origin City</th>
												<th colspan="5" style="background:#CCCCFF !important">Destination City</th>
												<th style="background:#CCCCFF !important">Exp.Cost</th>
												<th style="background:#CCCCFF !important">Exp.Profit</th>
												<th colspan="2" style="background:#CCCCFF !important">Exp.Revenue</th>
												<th style="background:#CCCCFF !important">Act.Cost</th>
												<th colspan="2" style="background:#CCCCFF !important">Act.Revenue</th>
												<th style="background:#CCCCFF !important">Net Profit</th>
												<th style="background:#CCCCFF !important">Mode</th>
												<th style="background:#CCCCFF !important">Type</th>
												<th colspan="2" style="background:#CCCCFF !important">Job Status</th>
												</tr>
											</thead>
											<tbody>
				';

				if($entries>0){
					for($ex=0;$ex<count($entries);$ex++){


								// $accounts_info = Vtiger_Record_Model::getInstanceById($entries[$ex]['account_id'], "Accounts");
								$newhtml .= '<tr>
															<td colspan="2">'.$entries[$ex]['cf_1198'].'</td>
															<td colspan="5">'.$entries[$ex]['cf_1441'].'</td>
															<td colspan="5">'.$entries[$ex]['cf_1508'].'</td>
															<td colspan="5">'.$entries[$ex]['cf_1510'].'</td>';
								$job_ref_no = $entries[$ex]['cf_1198'];

								$db_first = PearDatabase::getInstance();
								$query_first_jobid = "SELECT jobid from vtiger_jobcf
														INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobcf.jobid
															where vtiger_crmentity.deleted=0 AND vtiger_jobcf.cf_1198=?";

								$params_jobid = array($job_ref_no);
								$result_job = $db_first->pquery($query_first_jobid, $params_jobid);
								$row_job_id_info = $db_first->fetch_array($result_job);
								$job_id = $row_job_id_info['jobid'];

								if(!empty($job_id))
								{
								$db_job = PearDatabase::getInstance();
								$query_job = "SELECT * from job_report where job_id =?";
								$params = array($job_id);
								$result = $db_job->pquery($query_job, $params);
								$report_count  = $db_job->num_rows($result);
									if($report_count>0)
									{
										$job_report_detail = $db_job->fetch_array($result);

										$entries[$key]['expected_cost_usd'] = number_format($job_report_detail['expected_cost_usd'], 2 ,  "." , ",");
										$entries[$key]['expected_profit_usd'] = number_format($job_report_detail['expected_profit_usd'], 2 ,  "." , ",");
										$entries[$key]['expected_revenue_usd'] = number_format($job_report_detail['expected_revenue_usd'], 2 ,  "." , ",");

										$entries[$key]['actual_expense_cost_usd'] = number_format($job_report_detail['actual_expense_cost_usd'] , 2 ,  "." , "," );
										$entries[$key]['actual_selling_cost_usd'] = number_format($job_report_detail['actual_selling_cost_usd'] , 2 ,  "." , "," );
										$entries[$key]['net_profit'] = number_format($job_report_detail['net_profit'] , 2 ,  "." , "," );
								$newhtml .= '	<td>'.$entries[$key]['expected_cost_usd'].'</td>
															<td>'.$entries[$key]['expected_profit_usd'].'</td>
															<td colspan="2">'.$entries[$key]['expected_revenue_usd'].'</td>
															<td>'.$entries[$key]['actual_expense_cost_usd'].'</td>
															<td colspan="2">'.$entries[$key]['actual_selling_cost_usd'].'</td>
															<td>'.$entries[$key]['net_profit'].'</td>';
									}else{
									$newhtml .= '	<td></td>
																	<td></td>
																	<td></td>
																	<td></td>
																	<td></td>
																	<td></td>';
									}
								}else{
									$newhtml .= '	<td></td>
																<td></td>
																<td></td>
																<td></td>
																<td></td>
																<td></td>';
								}
								$newhtml .=  '<td>'.$entries[$ex]['cf_1711'].'</td>
														  <td>'.$entries[$ex]['cf_1200'].'</td>
														  <td>'.$entries[$ex]['cf_2197'].'</td>
														</tr>';


					}//// export for loop end here
				}
				$newhtml .='/tobody>
								</table>';

				//// convert data into excel file using spread sheet ///////
				$reader = new \PhpOffice\PhpSpreadsheet\Reader\Html();
				$spreadsheet = $reader->loadFromString($newhtml);
				$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');
				ob_clean();
				$fileName = 'write--2--html.xls';
						header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
						header('Content-Disposition: attachment; filename="'. urlencode($fileName).'"');
						$writer->save('php://output');
				//// convert data into excel file using spread sheet end here///////
				exit;
			}

			//echo "<pre>";
			//print_r($entries);
			//exit;
			$current_user = Users_Record_Model::getCurrentUserModel();
			$user_name = $current_user->get('user_name');
			if($Exporttype=='kerry_format')
			{
				// && $user_name=='s.mehtab'
				$this->getReportXLS_XLSX($request, $translatedHeaders, $entries, $moduleName);
			} elseif ($Exporttype=='kpi') {
				$this->getReportXLS_XLSX($request, $translatedHeaders, $entries, $moduleName, $Exporttype);
			} elseif ($Exporttype == 'vendor') {
				$this->getReportXLS_XLSX($request, $translatedHeaders, $entries, $moduleName, $Exporttype);
			}
			else{

				$this->getReportXLS($request, $translatedHeaders, $entries);
			}



		//$this->output($request, $translatedHeaders, $entries);
	}


	function getReportXLS_XLSX($request, $headers, $entries, $moduleName, $format = 'kerry_format') {

		$rootDirectory = vglobal('root_directory');
		$tmpDir = vglobal('tmp_dir');

		$tempFileName = tempnam($rootDirectory.$tmpDir, 'xlsx');

		$moduleName = $request->get('source_module');

		//$fileName = $this->getName().'.xls';
		$fileName = $moduleName.'.xlsx';
		$Exporttype = $request->get('Exporttype');



		if($moduleName=='Job' && $format == 'kerry_format'){
			$this->writeReportToExcelFile_Job($tempFileName, $headers, $entries, false);
		} else if ($moduleName=='Job' && $format == 'kpi') {
			$this->writeReportToExcelFile_kpi_Job($tempFileName, $headers, $entries, false);
		} elseif ($moduleName == 'Job' && $format == 'vendor') {
			$this->writeReportToExcelFile_Vendor($tempFileName, $headers, $entries, false);
		}


		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="'.$fileName.'"');
		header('Cache-Control: max-age=0');
		/*
		if(isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
			header('Pragma: public');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		}

		header('Content-Type: application/x-msexcel');
		header('Content-Length: '.@filesize($tempFileName));
		header('Content-disposition: attachment; filename="'.$fileName.'"');
		*/
		$fp = fopen($tempFileName, 'rb');
		fpassthru($fp);
		//unlink($tempFileName);

	}

	function getReportXLS($request, $headers, $entries) {

		$rootDirectory = vglobal('root_directory');
		$tmpDir = vglobal('tmp_dir');

		$tempFileName = tempnam($rootDirectory.$tmpDir, 'xls');

		$moduleName = $request->get('source_module');

		//$fileName = $this->getName().'.xls';
		$fileName = $moduleName.'.xls';



		$this->writeReportToExcelFile($tempFileName, $headers, $entries, false);

		if(isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
			header('Pragma: public');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		}

		header('Content-Type: application/x-msexcel');
		header('Content-Length: '.@filesize($tempFileName));
		header('Content-disposition: attachment; filename="'.$fileName.'"');

		$fp = fopen($tempFileName, 'rb');
		fpassthru($fp);
		//unlink($tempFileName);

	}


	function writeReportToExcelFile_Job($fileName, $headers, $entries, $filterlist='') {

		global $currentModule, $current_language;
		global $adb;
		$mod_strings = return_module_language($current_language, $currentModule);


		require_once 'libraries/PHPExcel/PHPExcel.php';

		//echo date('H:i:s') . " Create new PHPExcel object\n";
		$objPHPExcel = new PHPExcel();

		// Set properties
		//echo date('H:i:s') . " Set properties\n";
		$current_user = Users_Record_Model::getCurrentUserModel();

		$full_name = $current_user->get('first_name')." ".$current_user->get('last_name');
		$objPHPExcel->getProperties()->setCreator($full_name)
									 ->setLastModifiedBy($full_name)
									 ->setTitle($fileName)
									 ->setSubject($fileName)
									 ->setDescription($fileName)
									 ->setKeywords($fileName)
									 ->setCategory($fileName);



		//echo date('H:i:s') . " Add data\n";
		$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getActiveSheet()->setTitle("AIR");
		$air_headers =   array('STATION', 'SHPIMENT MODE', 'XMDH', 'HAWB NO', 'MAWB NO', 'RLJ', 'COLOAD', 'COLOADER', 'JOBDATE',
							   'CARRIER', 'FLT NO', 'FLT DATE', 'ETA', 'CUSTOMER', 'SHIPPER', 'CONSIGNEE', 'OAGENT', 'ORIGIN COUNTRY',
							   'ORIGIN PORT', 'DESTINATION COUNTRY', 'DESTINATION PORT', 'INCO TERM', 'FREIGHT CHARGE', 'PCS', 'G.W.(KGs)',
							   'C.W.(KGs)', 'COMMODITY','CURRENCY', 'REVENUE excl Tax', 'COST excl Tax', 'GROSS PROFIT excl Tax');

		$objPHPExcel->getActiveSheet()->fromArray($air_headers, null, 'A1');
		$objPHPExcel->getActiveSheet()->freezePane('A2');
		$objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);

		$entries_new = array();
		foreach($entries as $key => $entry)
		{
			if($entry['cf_1190']=="AXG") {

			$sql = $adb->pquery("SELECT * FROM vtiger_companycf
						INNER JOIN vtiger_currency_info on vtiger_currency_info.id= vtiger_companycf.cf_1459
						where vtiger_companycf.cf_996='".$entry['cf_1186']."'");
			$row_currency = $adb->fetch_array($sql);
			$currency_code = $row_currency['currency_code'];

			$entries_new[] = array('station' => $entry['cf_1188'], 'type' => $entry['cf_1200'], 'xmdh' => $entry['cf_4921'], 'hawb_no' => $entry['cf_2387'],
								   'mawb_no' => $entry['cf_4923'], 'RLJ' => $entry['cf_4925'], 'coload' => $entry['cf_4927'], 'coloader' => $entry['cf_4929'],
								   'jobdate' => $entry['createdtime'], 'carrier' => $entry['cf_4931'], 'flt_no' => $entry['cf_4933'],
								   'flat_date' => $entry['cf_4935'], 'eta' => $entry['cf_1591'], 'customer' => html_entity_decode($entry['cf_1441']),
								   'shipper'=> $entry['cf_1072'], 'consignee' => $entry['cf_1074'], 'oagent' => html_entity_decode($entry['cf_1082']),
								   'origin_country' => $entry['cf_1504'], 'origin_port' => $entry['cf_1508'], 'destination_country' => $entry['cf_1506'],
								   'destination_port' => $entry['cf_1510'], 'inco_term' => $entry['cf_4937'], 'freight_charge' => $entry['cf_4939'],
								   'pcs' => $entry['cf_1429'], 'gross_weight' => $entry['cf_1084'], 'charge_weight' => $entry['cf_4945'],
								   'commodity' => $entry['cf_1518'], 'currency' => $currency_code, 'revenue' => $entry['actual_selling_cost_usd'], 'cost' => $entry['actual_expense_cost_usd'],
								   'gross_profit' => $entry['net_profit']
								   );
			}
		}
		$objPHPExcel->getActiveSheet()->fromArray($entries_new, null, 'A2');

		//Create Sea Sheet Job Files
		$objPHPExcel->createSheet();
		$objPHPExcel->setActiveSheetIndex(1);
		$objPHPExcel->getActiveSheet()->setTitle("SEA");
		$sea_headers =   array('STATION', 'SHPIMENT MODE', 'XMDH', 'HAWB NO', 'MAWB NO', 'RLJ', 'COLOAD', 'COLOADER', 'JOBDATE',
							   'CARRIER', 'FLT NO', 'FLT DATE', 'ETA', 'CUSTOMER', 'SHIPPER', 'CONSIGNEE', 'OAGENT', 'ORIGIN COUNTRY',
							   'ORIGIN PORT', 'DESTINATION COUNTRY', 'DESTINATION PORT', 'INCO TERM', 'FREIGHT CHARGE', 'PCS',
							   'CBM', 'TEUs', "10'", "20'", "40'", "40HQ'", "45HQ'", 'LOAD TERM', 'SERVICE TYPE',
							   'G.W.(KGs)','C.W.(KGs)', 'COMMODITY','CURRENCY', 'REVENUE excl Tax', 'COST excl Tax', 'GROSS PROFIT excl Tax');

		$objPHPExcel->getActiveSheet()->fromArray($sea_headers, null, 'A1');
		$objPHPExcel->getActiveSheet()->freezePane('A2');
		$objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);

		$entries_new_sea = array();
		foreach($entries as $key => $entry)
		{
			if($entry['cf_1190']=="PRO") {

			$sql = $adb->pquery("SELECT * FROM vtiger_companycf
						INNER JOIN vtiger_currency_info on vtiger_currency_info.id= vtiger_companycf.cf_1459
						where vtiger_companycf.cf_996='".$entry['cf_1186']."'");
			$row_currency = $adb->fetch_array($sql);
			$currency_code = $row_currency['currency_code'];

			$entries_new_sea[] = array('station' => $entry['cf_1188'], 'type' => $entry['cf_1200'], 'xmdh' => $entry['cf_4921'], 'hawb_no' => $entry['cf_2387'],
								   'mawb_no' => $entry['cf_4923'], 'RLJ' => $entry['cf_4925'], 'coload' => $entry['cf_4927'], 'coloader' => $entry['cf_4929'],
								   'jobdate' => $entry['createdtime'], 'carrier' => $entry['cf_4931'], 'flt_no' => $entry['cf_4933'],
								   'flat_date' => $entry['cf_4935'], 'eta' => $entry['cf_1591'], 'customer' => html_entity_decode($entry['cf_1441']),
								   'shipper'=> $entry['cf_1072'], 'consignee' => $entry['cf_1074'], 'oagent' => html_entity_decode($entry['cf_1082']),
								   'origin_country' => $entry['cf_1504'], 'origin_port' => $entry['cf_1508'], 'destination_country' => $entry['cf_1506'],
								   'destination_port' => $entry['cf_1510'], 'inco_term' => $entry['cf_4937'], 'freight_charge' => $entry['cf_4939'],
								   'pcs' => $entry['cf_1429'], 'cbm' => $entry['cf_1086'], 'teu' => '', 'cntr_10' => '', 'cntr_20' => '',  'cntr_40' => '',  'cntr_40hq' => '',
								   'cntr_45hq' => '',  'load_term' => $entry['cf_4941'], 'services_type' => $entry['cf_4943'],
								   'gross_weight' => $entry['cf_1084'], 'charge_weight' => $entry['cf_4945'],
								   'commodity' => $entry['cf_1518'], 'currency' => $currency_code, 'revenue' => $entry['actual_selling_cost_usd'], 'cost' => $entry['actual_expense_cost_usd'],
								   'gross_profit' => $entry['net_profit']
								   );
			}
		}
		$objPHPExcel->getActiveSheet()->fromArray($entries_new_sea, null, 'A2');


		$objPHPExcel->setActiveSheetIndex(0);
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		//$objWriter->setUseBOM(true);
		ob_end_clean();
		$objWriter->save($fileName);

	}


	function writeReportToExcelFile_kpi_Job($fileName, $headers, $entries, $filterlist='') {
		global $currentModule, $current_language;
		$mod_strings = return_module_language($current_language, $currentModule);
		require_once 'libraries/PHPExcel/PHPExcel.php';
		$objPHPExcel = new PHPExcel();
		$current_user = Users_Record_Model::getCurrentUserModel();
		$full_name = $current_user->get('first_name')." ".$current_user->get('last_name');
		$objPHPExcel->getProperties()->setCreator($full_name)
									 ->setLastModifiedBy($full_name)
									 ->setTitle($fileName)
									 ->setSubject($fileName)
									 ->setDescription($fileName)
									 ->setKeywords($fileName)
									 ->setCategory($fileName);
		$objPHPExcel->setActiveSheetIndex(0);

		$objPHPExcel->getActiveSheet()->setTitle("KPI Report");
		$air_headers =   array('id','Tem','JF No','File Title','Destination','Coordinator','Customer','Branch','Department','Expected pick up', 'Actual pick up','Difference in days (pick up)','Later or Not (pick up)', 'Expected delivery date', 'Actual delivery date','Difference in days (delivery)','Later or Not (delivery)','Total transit time','Amount (USD)');
		$objPHPExcel->getActiveSheet()->fromArray($air_headers, null, 'A1');
		$objPHPExcel->getActiveSheet()->freezePane('E3');
		$objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);
		$length = sizeof($entries)+3;

		$sharedStyle1 = new PHPExcel_Style();
		 $sharedStyle1->applyFromArray(
			array('fill' 	=> array(
										'type'		=> PHPExcel_Style_Fill::FILL_SOLID,
										'color'		=> array('argb' => 'FFCCFFCC')
									),
				  'borders' => array( 'allborders' =>array(
										  'style' => PHPExcel_Style_Border::BORDER_THIN
										//'bottom'	=> array('style' => PHPExcel_Style_Border::BORDER_THIN),
										//'right'		=> array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
									)),
					'alignment' => array(
							           'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
									   'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
       								   'wrap' => true

        							)
				 ));
		$sharedStyle2 = new PHPExcel_Style();
		$sharedStyle2->applyFromArray(
			array('fill' 	=> array(
										'type'		=> PHPExcel_Style_Fill::FILL_SOLID,
										'color'		=> array('argb' => 'f2f2f2')
									),
				  'borders' => array( 'allborders' =>array(
										  'style' => PHPExcel_Style_Border::BORDER_THIN
										//'bottom'	=> array('style' => PHPExcel_Style_Border::BORDER_THIN),
										//'right'		=> array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
									)),
					'alignment' => array(
							            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
										'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        							)
				 ));
		$style = new PHPExcel_Style();
		$style->applyFromArray(
			array('fill' 	=> array(
										'type'		=> PHPExcel_Style_Fill::FILL_SOLID,
										'color'		=> array('argb' => 'FFCCFFCC')
									),
				  'borders' => array( 'allborders' =>array(
										  'style' => PHPExcel_Style_Border::BORDER_THIN
										//'bottom'	=> array('style' => PHPExcel_Style_Border::BORDER_THIN),
										//'right'		=> array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
									)),
					'alignment' => array(
							           'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
									   'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
       								   'wrap' => true

        							)
				 ));

		 $objPHPExcel->getActiveSheet()->setSharedStyle($sharedStyle1, "A1:S1");

		$objPHPExcel->getActiveSheet()->fromArray($entries, null, 'A3');
		//$col = 'A';
		for ($i=3;$i<$length;$i++) {
			//$tempCol = $col++;
			//$objPHPExcel->getActiveSheet()->getColumnDimension($tempCol)->setAutoSize(true);

			$objPHPExcel->getActiveSheet()->setSharedStyle($sharedStyle2, "A".$i.":S".$i."");
			$objPHPExcel->getActiveSheet()->setCellValue('L'.$i, '=K'.$i.'-J'.$i);
			$objPHPExcel->getActiveSheet()->setCellValue('M'.$i, '=IF(L'.$i.'>0,1,0)');
			$objPHPExcel->getActiveSheet()->setCellValue('P'.$i, '=O'.$i.'-N'.$i);
			$objPHPExcel->getActiveSheet()->setCellValue('Q'.$i, '=IF(P'.$i.'>0,1,0)');
			$objPHPExcel->getActiveSheet()->setCellValue('R'.$i, '=O'.$i.'-K'.$i);

			// $objPHPExcel->getActiveSheet()->setCellValue('H'.$i, '=G'.$i.'-F'.$i);
			// $objPHPExcel->getActiveSheet()->setCellValue('I'.$i, '=IF(H'.$i.'>0,1,0)');
			// $objPHPExcel->getActiveSheet()->setCellValue('L'.$i, '=K'.$i.'-J'.$i);
			// $objPHPExcel->getActiveSheet()->setCellValue('M'.$i, '=IF(L'.$i.'>0,1,0)');
			// $objPHPExcel->getActiveSheet()->setCellValue('N'.$i, '=K'.$i.'-G'.$i);
		}

		$objPHPExcel->getActiveSheet()->setCellValue('L'.$length, '=COUNT(L3:L'.($length-1).')');
		$objPHPExcel->getActiveSheet()->setCellValue('M'.$length, '=SUM(M3:M'.($length-1).')');
		$objPHPExcel->getActiveSheet()->setCellValue('P'.$length, '=COUNT(P3:P'.($length-1).')');
		$objPHPExcel->getActiveSheet()->setCellValue('Q'.$length, '=SUM(Q3:Q'.($length-1).')');

		// $objPHPExcel->getActiveSheet()->setCellValue('H'.$length, '=COUNT(H3:H'.($length-1).')');
		// $objPHPExcel->getActiveSheet()->setCellValue('I'.$length, '=SUM(I3:I'.($length-1).')');
		// $objPHPExcel->getActiveSheet()->setCellValue('L'.$length, '=COUNT(L3:L'.($length-1).')');
		// $objPHPExcel->getActiveSheet()->setCellValue('M'.$length, '=SUM(M3:M'.($length-1).')');

		$objPHPExcel->getActiveSheet()->setCellValue('C'.($length+2), 'On time pick up');
		$objPHPExcel->getActiveSheet()->setCellValue('C'.($length+3), 'On time delivery');
		$objPHPExcel->getActiveSheet()->setCellValue('L'.($length+2), "=(L".$length."-M".$length.")/L".$length);
		$objPHPExcel->getActiveSheet()->setCellValue('L'.($length+3), "=(P".$length."-Q".$length.")/P".$length);

		// $objPHPExcel->getActiveSheet()->setCellValue('C'.($length+2), 'On time pick up');
		// $objPHPExcel->getActiveSheet()->setCellValue('C'.($length+3), 'On time delivery');
		// $objPHPExcel->getActiveSheet()->setCellValue('H'.($length+2), "=(H".$length."-I".$length.")/H".$length);
		// $objPHPExcel->getActiveSheet()->setCellValue('H'.($length+3), "=(L".$length."-M".$length.")/L".$length);

		PHPExcel_Shared_Font::setAutoSizeMethod(PHPExcel_Shared_Font::AUTOSIZE_METHOD_EXACT);
		$objPHPExcel->getActiveSheet()
    	->getStyle('L'.($length+2))->getNumberFormat()->applyFromArray(
        								array(
							            'code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE_00
        								)
    									);

    	// $objPHPExcel->getActiveSheet()
    	// ->getStyle('H'.($length+2))->getNumberFormat()->applyFromArray(
     //    								array(
					// 		            'code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE_00
     //    								)
    	// 								);

		$objPHPExcel->getActiveSheet()
    	->getStyle('L'.($length+3))->getNumberFormat()->applyFromArray(
        								array(
							            'code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE_00
        								)
    									);

    	// $objPHPExcel->getActiveSheet()
    	// ->getStyle('H'.($length+3))->getNumberFormat()->applyFromArray(
     //    								array(
					// 		            'code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE_00
     //    								)
    	// 								);

		$objPHPExcel->getActiveSheet()->setSharedStyle($style, "L3:L".$length);
		$objPHPExcel->getActiveSheet()->setSharedStyle($style, "M3:M".$length);
		$objPHPExcel->getActiveSheet()->setSharedStyle($style, "P3:P".$length);
		$objPHPExcel->getActiveSheet()->setSharedStyle($style, "Q3:Q".$length);

		// $objPHPExcel->getActiveSheet()->setSharedStyle($style, "H3:H".$length);
		// $objPHPExcel->getActiveSheet()->setSharedStyle($style, "I3:I".$length);
		// $objPHPExcel->getActiveSheet()->setSharedStyle($style, "L3:L".$length);
		// $objPHPExcel->getActiveSheet()->setSharedStyle($style, "M3:M".$length);

		$objPHPExcel->getActiveSheet()->setSharedStyle($style, 'C'.($length+2));
		$objPHPExcel->getActiveSheet()->setSharedStyle($style, 'C'.($length+3));
		$objPHPExcel->getActiveSheet()->setSharedStyle($style, 'L'.($length+2));
		$objPHPExcel->getActiveSheet()->setSharedStyle($style, 'L'.($length+3));

		// $objPHPExcel->getActiveSheet()->setSharedStyle($style, 'C'.($length+2));
		// $objPHPExcel->getActiveSheet()->setSharedStyle($style, 'C'.($length+3));
		// $objPHPExcel->getActiveSheet()->setSharedStyle($style, 'H'.($length+2));
		// $objPHPExcel->getActiveSheet()->setSharedStyle($style, 'H'.($length+3));

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		ob_end_clean();
		$objWriter->save($fileName);

	}

	function writeReportToExcelFile_Vendor($fileName, $headers, $entries, $filterlist='') {
		global $currentModule, $current_language;
		$mod_strings = return_module_language($current_language, $currentModule);
		require_once 'libraries/PHPExcel/PHPExcel.php';
		$objPHPExcel = new PHPExcel();
		$current_user = Users_Record_Model::getCurrentUserModel();
		$full_name = $current_user->get('first_name')." ".$current_user->get('last_name');
		$objPHPExcel->getProperties()->setCreator($full_name)
									 ->setLastModifiedBy($full_name)
									 ->setTitle($fileName)
									 ->setSubject($fileName)
									 ->setDescription($fileName)
									 ->setKeywords($fileName)
									 ->setCategory($fileName);
		$objPHPExcel->setActiveSheetIndex(0);

		$objPHPExcel->getActiveSheet()->setTitle("Vendor JCR Report");

		// $air_headers = array('title','Job Ref #','File Title','Location','Department','Customer','Job status','Charge','Pay To','Buy (Local Cur NET)','Vendor Curr');

		$air_headers = array('title','Job Ref #','File Title','Location','Department','Customer','Job status','Charge','Pay To', 'Invoice No', 'Invoice Date','Buy (Vendor Cur Net)','VAT Rate','VAT','Buy (Vendor Cur Gross)','Vendor Curr','Exch Rate','Buy (Local Curr Gross)','Buy (Local Cur NET)', 'Exchange Rate', 'Value in USD');


		$objPHPExcel->getActiveSheet()->fromArray($air_headers, null, 'A1');
		$objPHPExcel->getActiveSheet()->freezePane('G3');
		$objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);
		$length = sizeof($entries)+3;

		$sharedStyle1 = new PHPExcel_Style();
		$sharedStyle1->applyFromArray(
			array('fill' 	=> array(
										'type'		=> PHPExcel_Style_Fill::FILL_SOLID,
										'color'		=> array('argb' => 'FFCCFFCC')
									),
				  'borders' => array( 'allborders' =>array(
										  'style' => PHPExcel_Style_Border::BORDER_THIN
										//'bottom'	=> array('style' => PHPExcel_Style_Border::BORDER_THIN),
										//'right'		=> array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
									)),
					'alignment' => array(
							           'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
									   'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
       								   'wrap' => true

        							)
				 ));
		$sharedStyle2 = new PHPExcel_Style();
		$sharedStyle2->applyFromArray(
			array('fill' 	=> array(
										'type'		=> PHPExcel_Style_Fill::FILL_SOLID,
										'color'		=> array('argb' => 'f2f2f2')
									),
				  'borders' => array( 'allborders' =>array(
										  'style' => PHPExcel_Style_Border::BORDER_THIN
										//'bottom'	=> array('style' => PHPExcel_Style_Border::BORDER_THIN),
										//'right'		=> array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
									)),
					'alignment' => array(
							            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
										'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        							)
				 ));

		$style = new PHPExcel_Style();
		$style->applyFromArray(
			array('fill' 	=> array(
										'type'		=> PHPExcel_Style_Fill::FILL_SOLID,
										'color'		=> array('argb' => 'FFCCFFCC')
									),
				  'borders' => array('allborders' =>array(
										  'style' => PHPExcel_Style_Border::BORDER_THIN
										//'bottom'	=> array('style' => PHPExcel_Style_Border::BORDER_THIN),
										//'right'		=> array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
									)),
					'alignment' => array(
							           'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
									   'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
       								   'wrap' => true

        							)
				 ));

		$objPHPExcel->getActiveSheet()->setSharedStyle($sharedStyle1, "A1:U1");
		$objPHPExcel->getActiveSheet()->fromArray($entries, null, 'A3');
		PHPExcel_Shared_Font::setAutoSizeMethod(PHPExcel_Shared_Font::AUTOSIZE_METHOD_EXACT);
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		ob_end_clean();
		$objWriter->save($fileName);

	}


	function writeReportToExcelFile($fileName, $headers, $entries, $filterlist='') {
		global $currentModule, $current_language;
		$mod_strings = return_module_language($current_language, $currentModule);

		require_once("libraries/PHPExcel/PHPExcel.php");

		$workbook = new PHPExcel();
		$worksheet = $workbook->setActiveSheetIndex(0);

		$header_styles = array(
			//'fill' => array( 'type' => PHPExcel_Style_Fill::FILL_NONE, 'color' => array('rgb'=>'E1E0F7') ),
			'fill' => array( 'type' => PHPExcel_Style_Fill::FILL_NONE ),

			//'font' => array( 'bold' => true )
		);


		if(isset($headers)) {
			$count = 0;
			$rowcount = 1;

			$arrayFirstRowValues = $headers;
			//array_pop($arrayFirstRowValues);

			// removed action link in details
			foreach($arrayFirstRowValues as $key=>$value) {
				$worksheet->setCellValueExplicitByColumnAndRow($count, $rowcount, $key, true);
				$worksheet->getStyleByColumnAndRow($count, $rowcount)->applyFromArray($header_styles);

				// NOTE Performance overhead: http://stackoverflow.com/questions/9965476/phpexcel-column-size-issues
				//$worksheet->getColumnDimensionByColumn($count)->setAutoSize(true);

				$count = $count + 1;
			}

			$rowcount++;

			$count = 0;
			//array_pop($array_value);	// removed action link in details
			foreach($headers as $hdr => $value) {
				$value = decode_html($value);
				// TODO Determine data-type based on field-type.
				// String type helps having numbers prefixed with 0 intact.
				$worksheet->setCellValueExplicitByColumnAndRow($count, $rowcount, $value, PHPExcel_Cell_DataType::TYPE_STRING);
				$count = $count + 1;
			}
			//$rowcount++;

			$rowcount++;
			foreach($entries as $key => $array_value) {
				$count = 0;
				//array_pop($array_value);	// removed action link in details
				foreach($array_value as $hdr => $value_excel) {
					if(is_array($value_excel))
					{
						$value = '';
					}
					else{
					$value = decode_html($value_excel);
					}
					// TODO Determine data-type based on field-type.
					// String type helps having numbers prefixed with 0 intact.
					$worksheet->setCellValueExplicitByColumnAndRow($count, $rowcount, $value, PHPExcel_Cell_DataType::TYPE_STRING);
					$count = $count + 1;
				}
				$rowcount++;
			}


		}


		$workbookWriter = PHPExcel_IOFactory::createWriter($workbook, 'Excel5');
		ob_end_clean();
		$workbookWriter->save($fileName);

	}


	public function getHeaders() {
		$headers = array();
		//Query generator set this when generating the query
		if(!empty($this->accessibleFields)) {
			$accessiblePresenceValue = array(0,2);
			foreach($this->accessibleFields as $fieldName) {
				$fieldModel = $this->moduleFieldInstances[$fieldName];
				// Check added as querygenerator is not checking this for admin users
				$presence = $fieldModel->get('presence');
				if(in_array($presence, $accessiblePresenceValue) && $fieldModel->get('displaytype') != '6') {
					$headers[] = $fieldModel->get('label');
				}
			}
		} else {
			foreach($this->moduleFieldInstances as $field) {
				$headers[] = $field->get('label');
			}
		}

		$translatedHeaders = array();
		foreach($headers as $header) {
			$translatedHeaders[] = vtranslate(html_entity_decode($header, ENT_QUOTES), $this->moduleInstance->getName());
		}

		$translatedHeaders = array_map('decode_html', $translatedHeaders);
		return $translatedHeaders;
	}

	function getAdditionalQueryModules(){
		return array_merge(getInventoryModules(), array('Products', 'Services', 'PriceBooks'));
	}

	/**
	 * Function that generates Export Query based on the mode
	 * @param Vtiger_Request $request
	 * @return <String> export query
	 */
	function getExportQuery(Vtiger_Request $request) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$mode = $request->getMode();
		$cvId = $request->get('viewname');
		$moduleName = $request->get('source_module');

		$queryGenerator = new EnhancedQueryGenerator($moduleName, $currentUser);
		$queryGenerator->initForCustomViewById($cvId);
		$fieldInstances = $this->moduleFieldInstances;

		$orderBy = $request->get('orderby');
		$orderByFieldModel = $fieldInstances[$orderBy];
		$sortOrder = $request->get('sortorder');

		if ($mode !== 'ExportAllData') {
			$operator = $request->get('operator');
			$searchKey = $request->get('search_key');
			$searchValue = $request->get('search_value');

			$tagParams = $request->get('tag_params');
			if (!$tagParams) {
				$tagParams = array();
			}

			$searchParams = $request->get('search_params');
			if (!$searchParams) {
				$searchParams = array();
			}

			$glue = '';
			if($searchParams && count($queryGenerator->getWhereFields())) {
				$glue = QueryGenerator::$AND;
			}
			$searchParams = array_merge($searchParams, $tagParams);
			$searchParams = Vtiger_Util_Helper::transferListSearchParamsToFilterCondition($searchParams, $this->moduleInstance);
			$queryGenerator->parseAdvFilterList($searchParams, $glue);

			if($searchKey) {
				$queryGenerator->addUserSearchConditions(array('search_field' => $searchKey, 'search_text' => $searchValue, 'operator' => $operator));
			}

			if ($orderBy && $orderByFieldModel) {
				if ($orderByFieldModel->getFieldDataType() == Vtiger_Field_Model::REFERENCE_TYPE || $orderByFieldModel->getFieldDataType() == Vtiger_Field_Model::OWNER_TYPE) {
					$queryGenerator->addWhereField($orderBy);
				}
			}
		}

		/**
		 *  For Documents if we select any document folder and mass deleted it should delete documents related to that
		 *  particular folder only
		 */
		if($moduleName == 'Documents'){
			$folderValue = $request->get('folder_value');
			if(!empty($folderValue)){
				 $queryGenerator->addCondition($request->get('folder_id'),$folderValue,'e');
			}
		}

		$accessiblePresenceValue = array(0,2);
		foreach($fieldInstances as $field) {
			// Check added as querygenerator is not checking this for admin users
			$presence = $field->get('presence');
			if(in_array($presence, $accessiblePresenceValue) && $field->get('displaytype') != '6') {
				$fields[] = $field->getName();
			}
		}
		$queryGenerator->setFields($fields);
		$query = $queryGenerator->getQuery();

		$additionalModules = $this->getAdditionalQueryModules();
		if(in_array($moduleName, $additionalModules)) {
			$query = $this->moduleInstance->getExportQuery($this->focus, $query);
		}

		$this->accessibleFields = $queryGenerator->getFields();

		switch($mode) {
			case 'ExportAllData'	:	if ($orderBy && $orderByFieldModel) {
											$query .= ' ORDER BY '.$queryGenerator->getOrderByColumn($orderBy).' '.$sortOrder;
										}
										break;

			case 'ExportCurrentPage' :	$pagingModel = new Vtiger_Paging_Model();
										$limit = $pagingModel->getPageLimit();

										$currentPage = $request->get('page');
										if(empty($currentPage)) $currentPage = 1;

										$currentPageStart = ($currentPage - 1) * $limit;
										if ($currentPageStart < 0) $currentPageStart = 0;

										if ($orderBy && $orderByFieldModel) {
											$query .= ' ORDER BY '.$queryGenerator->getOrderByColumn($orderBy).' '.$sortOrder;
										}
										$query .= ' LIMIT '.$currentPageStart.','.$limit;
										break;

			case 'ExportSelectedRecords' :	$idList = $this->getRecordsListFromRequest($request);
											$baseTable = $this->moduleInstance->get('basetable');
											$baseTableColumnId = $this->moduleInstance->get('basetableid');
											if(!empty($idList)) {
												if(!empty($baseTable) && !empty($baseTableColumnId)) {
													$idList = implode(',' , $idList);
													$query .= ' AND '.$baseTable.'.'.$baseTableColumnId.' IN ('.$idList.')';
												}
											} else {
												$query .= ' AND '.$baseTable.'.'.$baseTableColumnId.' NOT IN ('.implode(',',$request->get('excluded_ids')).')';
											}

											if ($orderBy && $orderByFieldModel) {
												$query .= ' ORDER BY '.$queryGenerator->getOrderByColumn($orderBy).' '.$sortOrder;
											}
											break;


			default :	break;
		}

		return $query;
	}

	/**
	 * Function returns the export type - This can be extended to support different file exports
	 * @param Vtiger_Request $request
	 * @return <String>
	 */
	function getExportContentType(Vtiger_Request $request) {
		$type = $request->get('export_type');
		if(empty($type)) {
			return 'text/csv';
		}
	}

	/**
	 * Function that create the exported file
	 * @param Vtiger_Request $request
	 * @param <Array> $headers - output file header
	 * @param <Array> $entries - outfput file data
	 */
	function output($request, $headers, $entries) {
		$moduleName = $request->get('source_module');
		$fileName = str_replace(' ','_',decode_html(vtranslate($moduleName, $moduleName)));
		// for content disposition header comma should not be there in filename
		$fileName = str_replace(',', '_', $fileName);
		$exportType = $this->getExportContentType($request);

		header("Content-Disposition:attachment;filename=$fileName.csv");
		header("Content-Type:$exportType;charset=UTF-8");
		header("Expires: Mon, 31 Dec 2000 00:00:00 GMT" );
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
		header("Cache-Control: post-check=0, pre-check=0", false );

		$header = implode("\", \"", $headers);
		$header = "\"" .$header;
		$header .= "\"\r\n";
		echo $header;

		foreach($entries as $row) {
			foreach ($row as $key => $value) {
				/* To support double quotations in CSV format
				 * To review: http://creativyst.com/Doc/Articles/CSV/CSV01.htm#EmbedBRs
				 */
				$row[$key] = str_replace('"', '""', $value);
			}
			$line = implode("\",\"",$row);
			$line = "\"" .$line;
			$line .= "\"\r\n";
			echo $line;
		}
	}

	private $picklistValues;
	private $fieldArray;
	private $fieldDataTypeCache = array();
	/**
	 * this function takes in an array of values for an user and sanitizes it for export
	 * @param array $arr - the array of values
	 */
	function sanitizeValues($arr){

		$db = PearDatabase::getInstance();
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$roleid = $currentUser->get('roleid');
		if(empty ($this->fieldArray)){
			$this->fieldArray = $this->moduleFieldInstances;
			foreach($this->fieldArray as $fieldName => $fieldObj){
				//In database we have same column name in two tables. - inventory modules only
				if($fieldObj->get('table') == 'vtiger_inventoryproductrel' && ($fieldName == 'discount_amount' || $fieldName == 'discount_percent')){
					$fieldName = 'item_'.$fieldName;
					$this->fieldArray[$fieldName] = $fieldObj;
				} else {
					$columnName = $fieldObj->get('column');
					$this->fieldArray[$columnName] = $fieldObj;
				}
			}
		}

		$moduleName = $this->moduleInstance->getName();
		foreach($arr as $fieldName=>&$value){
			if(isset($this->fieldArray[$fieldName])){
				$fieldInfo = $this->fieldArray[$fieldName];
			}else {
				unset($arr[$fieldName]);
				continue;
			}
			//Track if the value had quotes at beginning
			$beginsWithDoubleQuote = strpos($value, '"') === 0;
			$endsWithDoubleQuote = substr($value,-1) === '"'?1:0;

			$value = trim($value,"\"");
			$uitype = $fieldInfo->get('uitype');
			$fieldname = $fieldInfo->get('name');

			if(!$this->fieldDataTypeCache[$fieldName]) {
				$this->fieldDataTypeCache[$fieldName] = $fieldInfo->getFieldDataType();
			}
			$type = $this->fieldDataTypeCache[$fieldName];

			//Restore double quote now.
			if ($beginsWithDoubleQuote) $value = "\"{$value}";
			if($endsWithDoubleQuote) $value = "{$value}\"";
			if($fieldname != 'hdnTaxType' && ($uitype == 15 || $uitype == 16 || $uitype == 33)){
				if(empty($this->picklistValues[$fieldname])){
					$this->picklistValues[$fieldname] = $this->fieldArray[$fieldname]->getPicklistValues();
				}
				// If the value being exported is accessible to current user
				// or the picklist is multiselect type.
				if($uitype == 33 || $uitype == 16 || array_key_exists($value,$this->picklistValues[$fieldname])){
					// NOTE: multipicklist (uitype=33) values will be concatenated with |# delim
					$value = trim($value);
				} else {
					$value = '';
				}
			} elseif($uitype == 52 || $type == 'owner') {
				//$value = Vtiger_Util_Helper::getOwnerName($value);
				$value = Vtiger_Util_Helper::getFullOwnerName($value);
			}elseif($type == 'reference'){
				$value = trim($value);
				if(!empty($value)) {
					$parent_module = getSalesEntityType($value);
					$displayValueArray = getEntityName($parent_module, $value);
					if(!empty($displayValueArray)){
						foreach($displayValueArray as $k=>$v){
							$displayValue = $v;
						}
					}
					if(!empty($parent_module) && !empty($displayValue)){
						//$value = $parent_module."::::".$displayValue;
						$value = $displayValue;
					}else{
						$value = "";
					}
				} else {
					$value = '';
				}
			} elseif($uitype == 72 || $uitype == 71) {
                $value = CurrencyField::convertToUserFormat($value, null, true, true);
			} elseif($uitype == 7 && $fieldInfo->get('typeofdata') == 'N~O' || $uitype == 9){
				$value = decimalFormat($value);
			}
			elseif($type == 'date') {
				if ($value && $value != '0000-00-00') {
					$value = DateTimeField::convertToUserFormat($value);
				}
			} elseif($type == 'datetime') {
				if ($moduleName == 'Calendar' && in_array($fieldName, array('date_start', 'due_date'))) {
					$timeField = 'time_start';
					if ($fieldName === 'due_date') {
						$timeField = 'time_end';
					}
					$value = $value.' '.$arr[$timeField];
				}
				if (trim($value) && $value != '0000-00-00 00:00:00') {
					$value = Vtiger_Datetime_UIType::getDisplayDateTimeValue($value);
				}
			}
			elseif($uitype ==999)
			{
				$value = Vtiger_CompanyList_UIType::getDisplayValue($value);
			}
			elseif($uitype==898)
			{
				$value = Vtiger_LocationList_UIType::getDisplayValue($value);
			}
			elseif($uitype==899)
			{
				$value = Vtiger_DepartmentList_UIType::getDisplayValue($value);
			}
			elseif($uitype==117)
			{
				$value = Vtiger_CurrencyList_UIType::getDisplayValue($value);
			}
			elseif($uitype==55)
			{
				if($fieldname!='cf_1084' && $fieldname!='cf_1086')
				{
					$value = Vtiger_CurrencyList_UIType::getDisplayValue($value);
				}
				else{
					$value = $value;
				}
			}
			elseif($uitype==699)
			{
				$value = Vtiger_InsuranceRateList_UIType::getDisplayValue($value);
			}
			elseif($uitype==768)
			{
				$value = Vtiger_TruckList_UIType::getDisplayValue($value);
			}
			elseif($uitype==599)
			{
				$value = Vtiger_DriverList_UIType::getDisplayValue($value);
			}
			elseif($uitype==698)
			{
				$value = Vtiger_CommodityTypeList_UIType::getDisplayValue($value);
			}
			elseif($uitype==697)
			{
				$value = Vtiger_SpecialRangeList_UIType::getDisplayValue($value);
			}
			elseif($uitype==597)
			{
				$value = Vtiger_GLKUserList_UIType::getDisplayValue($value);
			}
			elseif($uitype==994)
			{
				$value = Vtiger_PackerList_UIType::getDisplayValue($value);
			}
			elseif($uitype==995)
			{
				Vtiger_CompanyAccountTypeList_UIType::getDisplayValue($value);
			}
			elseif($uitype==601)
			{
				Vtiger_UsersList_UIType::getDisplayValue($value);
			}
			elseif($uitype=='11010')
			{
				Vtiger_WarehouseList_UIType::getDisplayValue($value);
			}
			elseif($uitype==11011)
			{
				Vtiger_WHItemMasterList_UIType::getDisplayValue($value);
			}
			elseif($uitype==695)
			{
				Vtiger_InsuranceTypeList_UIType::getDisplayValue($value);
			}
			elseif($uitype==766)
			{
				Vtiger_TruckTypeList_UIType::getDisplayValue($value);
			}

			if($moduleName == 'Documents' && $fieldname == 'description'){
				$value = strip_tags($value);
				$value = str_replace('&nbsp;','',$value);
				array_push($new_arr,$value);
			}
		}
		return $arr;
	}

	public function moduleFieldInstances($moduleName) {
		return $this->moduleInstance->getFields();
	}
}
