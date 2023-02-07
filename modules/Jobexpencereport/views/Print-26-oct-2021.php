<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Jobexpencereport_Print_View extends Vtiger_Print_View {

	/**
     * Temporary Filename
     *
     * @var string
     */
    private $_tempFileName;

	function __construct() {
		parent::__construct();
		ob_start();
	}

	function checkPermission(Vtiger_Request $request) {
		return true;
	}

	public function get_job_id_from_fleet($recordId=0)
	{
		 $adb = PearDatabase::getInstance();

		 $checkjob = $adb->pquery("SELECT rel1.crmid as job_id FROM `vtiger_crmentityrel` as rel1
				  							where rel1.relcrmid='".$recordId."'", array());
		 $crmId = $adb->query_result($checkjob, 0, 'job_id');
		 $job_id = $crmId;
		 return $job_id;
	}

	function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$record = $request->get('record');

		$expnese_type = $request->get('expense');

		if($expnese_type=='JPV')
		{
			$this->print_job_pv($request);
		}
		elseif($expnese_type=='GII') //Generate Invoice Instruction
		{
			$this->print_job_invoice($request);
		}
		elseif($expnese_type=='Fleet')
		{
			$this->print_fleet_expense($request);
		}
		elseif($expnese_type=='PV') //PV=payment voucher
		{
			$this->print_fleet_pv($request);
		}
		elseif($expnese_type=='WPV')  //WPV=Wagon Payment Vouhcer
		{
			$this->print_wagon_pv($request);
		}
		elseif($expnese_type=='Fleettrip')
		{
			$this->print_round_trip_expense($request);
		}
		elseif($expnese_type=='WagonTrip')
		{
			$this->print_wagon_trip_expense($request);
		}
		elseif($expnese_type=='WagonTripBreakdown')
		{
			$this->print_wagon_trip_expense_breakdown($request);
		}
		elseif($expnese_type=='JCR')
		{
			$this->print_JCR($request);
		}
		elseif($expnese_type=='SUBJCR')
		{
			$this->print_SUBJCR($request);
		}
		elseif($expnese_type=='GITE')
		{
			$this->invoice_tax_excel($request);
		}
		elseif($expnese_type=='GITP')
		{
			$this->invoice_tax_pdf($request);
		}
	}

	public function print_SUBJCR($request)
	{
		include('include/Exchangerate/exchange_rate_class.php');
		global $adb;
		//$adb->setDebug(true);
		$moduleName = $request->getModule();
		$record = $request->get('record');
		$type=$request->get('type');
		$job_assigned_user_id = $request->get('userid');

		$job_id = $record;
		$current_user = Users_Record_Model::getCurrentUserModel();
		$job_info_detail = Vtiger_Record_Model::getInstanceById($job_id, 'Job');
		$document = $this->loadTemplate('printtemplates/Job/jcr.html');

		$owner_job_user_info = Users_Record_Model::getInstanceById($job_info_detail->get('assigned_user_id'), 'Users');

		$this->setValue('glk_useroffice',$owner_job_user_info->getDisplayValue('location_id'));

		$assigned_job_user_info = Users_Record_Model::getInstanceById($job_assigned_user_id, 'Users');
		$assigned_user_location_id = $assigned_job_user_info->get('location_id');
		$assigned_user_department_id = $assigned_job_user_info->get('department_id');


		$this->setValue('useroffice',$assigned_job_user_info->getDisplayValue('location_id'));
		$this->setValue('userdepartment',$assigned_job_user_info->getDisplayValue('department_id'));
		$this->setValue('mobile',$assigned_job_user_info->get('phone_mobile'));
		$this->setValue('fax',$assigned_job_user_info->get('phone_fax'));
		$this->setValue('email',htmlentities($assigned_job_user_info->get('email1'), ENT_QUOTES, "UTF-8"));
		$this->setValue('cityname',htmlentities($job_info_detail->getDisplayValue('cf_1188'), ENT_QUOTES, "UTF-8"));
		//$this->setValue('countryname',htmlentities($owner_job_user_info->get('address_country'), ENT_QUOTES, "UTF-8"));
		$this->setValue('departmentcode',htmlentities($job_info_detail->getDisplayValue('cf_1190'), ENT_QUOTES, "UTF-8"));
		$this->setValue('dateadded',date('d.m.Y', strtotime($job_info_detail->get('createdtime'))));
		$this->setValue('from', htmlentities($assigned_job_user_info->get('first_name').' '.$assigned_job_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));

		$this->setValue('job_ref_no', $job_info_detail->get('cf_1198'));
		$this->setValue('type', $job_info_detail->getDisplayValue('cf_1200'));

		$this->setValue('shipper', htmlentities($job_info_detail->getDisplayValue('cf_1072'), ENT_QUOTES, "UTF-8"));

		$this->setValue('consignee', htmlentities($job_info_detail->getDisplayValue('cf_1074'), ENT_QUOTES, "UTF-8"));
		$this->setValue('origin', htmlentities($job_info_detail->getDisplayValue('cf_1508').'/'.$job_info_detail->getDisplayValue('cf_1504'), ENT_QUOTES, "UTF-8"));
		$this->setValue('destination', htmlentities($job_info_detail->getDisplayValue('cf_1510').'/'.$job_info_detail->getDisplayValue('cf_1506'), ENT_QUOTES, "UTF-8"));

		$this->setValue('pieces', htmlentities($job_info_detail->getDisplayValue('cf_1429'), ENT_QUOTES, "UTF-8"));
		$this->setValue('weightvol', htmlentities($job_info_detail->getDisplayValue('cf_1084').' '.$job_info_detail->getDisplayValue('cf_1520').' /  '.$job_info_detail->getDisplayValue('cf_1086').' '.$job_info_detail->getDisplayValue('cf_1522'), ENT_QUOTES, "UTF-8"));
		$this->setValue('waybill', htmlentities($job_info_detail->getDisplayValue('cf_1096'), ENT_QUOTES, "UTF-8"));
		$this->setValue('cargo', htmlspecialchars_decode($job_info_detail->getDisplayValue('cf_1547')));
		$this->setValue('remark', htmlentities($job_info_detail->getDisplayValue('cf_1102'), ENT_QUOTES, "UTF-8"));

		$client_id = $job_info_detail->get('cf_1441');
		$client_accountname = '';
		if(!empty($client_id))
		{
			$clientinfo = Vtiger_Record_Model::getInstanceById($client_id, 'Accounts');
			$client_accountname = @$clientinfo->get('cf_2395');
		}

		$this->setValue('client', htmlentities($client_accountname, ENT_QUOTES, "UTF-8"));

		//For GLK Invoice Number
		$invoice_query = $adb->pquery('select * from vtiger_jobexpencereport
							  INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid
							  where vtiger_jobexpencereport.job_id="'.$job_id.'" AND vtiger_jobexpencereport.owner_id = "'.$job_info_detail->get('assigned_user_id').'" AND vtiger_jobexpencereport.invoice_no !=""
							  		AND vtiger_jobexpencereportcf.cf_1457="Selling"
							  GROUP BY vtiger_jobexpencereport.invoice_no
							  ORDER BY vtiger_jobexpencereportcf.cf_1477, vtiger_jobexpencereportcf.cf_1479 ASC
							  ');
		$invoice_no = array();
		while($row = $adb->fetch_array($invoice_query))
		{
			$invoice_no[] =$row['invoice_no'];
		}
		$pv_GlkInvoice = implode(';', $invoice_no);
		$this->setValue('pv_GlkInvoice', htmlentities($pv_GlkInvoice, ENT_QUOTES, "UTF-8"));

		$company_id = $job_info_detail->get('cf_1186');
		$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency($company_id);
		$file_title_currency = $reporting_currency;
		$job_office_id =  $job_info_detail->get('cf_1188');

		if($job_info_detail->get('assigned_user_id')!=$job_assigned_user_id)
		{
			if($job_office_id==$assigned_user_location_id){
				$file_title_currency = $file_title_currency;
			}
			else{
				$query_jobtask= $adb->pquery("SELECT sub_jrer_file_title from vtiger_jobtask
										  WHERE job_id='".$job_id."' and user_id='".$job_assigned_user_id."' limit 1");
				$row_jobtask = $adb->fetch_array($query_jobtask);
				$user_file_title = $row_jobtask['sub_jrer_file_title']; //Assigned user File Title
				$file_title_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency($user_file_title);
			}
		}

		$jrer_last_sql =  "SELECT vtiger_crmentity.createdtime FROM vtiger_jobexpencereportcf
							 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereportcf.jobexpencereportid
							 INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid
							 WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid=? AND crmentityrel.module='Job'
							 AND crmentityrel.relmodule='Jobexpencereport' order by vtiger_crmentity.createdtime DESC limit 1";
		// parentId = Fleet Id
		$parentId = $job_id;
		$params = array($parentId);
		$result_last = $adb->pquery($jrer_last_sql, $params);
		$row_jrer_last = $adb->fetch_array($result_last);
		$count_last_modified = $adb->num_rows($result_last);

		$exchange_rate_date  = date('Y-m-d');
		if($count_last_modified>0)
		{
			$modifiedtime = $row_jrer_last['createdtime'];
			$modifiedtime = strtotime($row_jrer_last['createdtime']);
			$exchange_rate_date = date('Y-m-d', $modifiedtime);
		}

		if($file_title_currency!='USD')
		{
			$final_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $exchange_rate_date);
		}else{
			$final_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $exchange_rate_date);
		}

		//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
		$jrer_sum_sql =  "SELECT sum(vtiger_jobexpencereportcf.cf_1347) as buy_local_currency_gross,
			sum(vtiger_jobexpencereportcf.cf_1349) as buy_local_currency_net,
			sum(vtiger_jobexpencereportcf.cf_1351) as expected_buy_local_currency_net,
			sum(vtiger_jobexpencereportcf.cf_1353) as variation_expected_and_actual_buying
			FROM `vtiger_jobexpencereport`
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid
			INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
			LEFT JOIN vtiger_jobexpencereportcf as vtiger_jobexpencereportcf on vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid
			WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=?
			AND vtiger_crmentityrel.module='Job'
			AND vtiger_crmentityrel.relmodule='Jobexpencereport'
			AND vtiger_jobexpencereportcf.cf_1457='Expence'
			AND vtiger_jobexpencereport.owner_id='".$job_assigned_user_id."'
			AND vtiger_jobexpencereport.user_id='".$job_assigned_user_id."'
			";
			$parentId = $job_id;
			$params = array($parentId);
			$result = $adb->pquery($jrer_sum_sql, $params);
			$row_job_jrer = $adb->fetch_array($result);

			$BUY_LOCAL_CURRENCY_GROSS = number_format ( $row_job_jrer['buy_local_currency_gross'] , 2 ,  "." , ",");
			$BUY_LOCAL_CURRENCY_NET   = number_format ( $row_job_jrer['buy_local_currency_net'] , 2 ,  "." , "," );
			$EXPECTED_BUY_LOCAL_CURRENCY_NET  = number_format ( $row_job_jrer['expected_buy_local_currency_net'] , 2 ,  "." , "," );
			$VARIATION_EXPECTED_AND_ACTUAL_BUYING = number_format ( $row_job_jrer['variation_expected_and_actual_buying'] , 2 ,  "." , "," );

			//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
			$jrer_sql_expense =  "SELECT vtiger_jobexpencereportcf.cf_1347 as buy_local_currency_gross,
			vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
			vtiger_jobexpencereportcf.cf_1351 as expected_buy_local_currency_net,
			vtiger_jobexpencereportcf.cf_1353 as variation_expected_and_actual_buying,
			vtiger_jobexpencereportcf.cf_1216 as expense_invoice_date,
			vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
			FROM `vtiger_jobexpencereport`
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid
			INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
			LEFT JOIN vtiger_jobexpencereportcf as vtiger_jobexpencereportcf on vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid
			WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=?
			AND vtiger_crmentityrel.module='Job'
			AND vtiger_crmentityrel.relmodule='Jobexpencereport'
			AND vtiger_jobexpencereportcf.cf_1457='Expence'
			AND vtiger_jobexpencereport.owner_id='".$job_assigned_user_id."'
			AND vtiger_jobexpencereport.user_id='".$job_assigned_user_id."'
			";

			$parentId = $job_id;
			$params = array($parentId);
			$result_expense = $adb->pquery($jrer_sql_expense, $params);
			$numRows_expnese = $adb->num_rows($result_expense);

			$total_cost_in_usd_gross = 0;
			$total_cost_in_usd_net = 0;
			$total_expected_cost_in_usd_net = 0;
			$total_variation_expected_and_actual_buying_cost_in_usd = 0;

			if($numRows_expnese>0)
			{
				for($ii=0; $ii< $adb->num_rows($result_expense); $ii++ ) {
					$row_job_jrer_expense = $adb->fetch_row($result_expense,$ii);
					$expense_invoice_date = $row_job_jrer_expense['expense_invoice_date'];

					$CurId = $row_job_jrer_expense['buy_currency_id'];
					if ($CurId) {
						$q_cur = $adb->pquery('select * from vtiger_currency_info where id = "'.$CurId.'"');
						$row_cur = $adb->fetch_array($q_cur);
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
					}else{
						if($b_exchange_rate==0)
						{
							$b_exchange_rate = 1;
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

			$TOTAL_COST_USD_GROSS = number_format ( $total_cost_in_usd_gross , 2 ,  "." , "," );
			$TOTAL_COST_IN_USD_NET = number_format ( $total_cost_in_usd_net , 2 ,  "." , "," );
			$TOTAL_EXPECTED_COST_USD_NET = number_format ( $total_expected_cost_in_usd_net , 2 ,  "." , "," );
			$TOTAL_VARIATION_EXPECTED_AND_ACTUAL_BUYING_COST_IN_USD = number_format ( $total_variation_expected_and_actual_buying_cost_in_usd , 2 ,  "." , "," );

			$pagingModel = new Vtiger_Paging_Model();
			$pagingModel->set('page','1');
			$pagingModel->set('limit',500);

			$relatedModuleName = 'Jobexpencereport';
			$parentRecordModel = $job_info_detail;
			$relationListView = Job_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName, $label);
			$whereCondition['cf_1457'] = array('', 'e', 'Expence', '');//array('fieldname'=>'cf_1457', 'value'=> $Expence,'operator'=>'e');
			//$whereCondition['owner_id'] = array('', 'e', '$job_assigned_user_id', '');
			//$whereCondition['user_id'] = array('', 'e', '$job_assigned_user_id', '');
			//if($type=='LJCR')
			//{
			//	$whereCondition['cf_1214'] = array('', 'n', array(85773, 85774, 85793, 85794), '');
			//}

			$relationListView->set('whereCondition',$whereCondition);
			$models = $relationListView->getEntries($pagingModel, $JRER_TYPE='0', $QRY_GROUB_BY='0', $type, $job_assigned_user_id);

			$i=0;
			$total_usd = 0;
			$buyingsubtotalR = 0;
			$buyingsubtotalN = 0;
			$buying = '';
			foreach($models as $key => $model)
			{
				//$expense_record= $model->getInstanceById($model->getId());
				$expense_record= $model;
				//if($model->getDisplayValue('cf_1457') == 'Selling'){
				//	continue;
				//}
				$i++;

				$Cur = $expense_record->getDisplayValue('cf_1345');
				$invoice_date = $expense_record->get('cf_1216');
				$b_exchange_rate = $final_exchange_rate;
				if(!empty($invoice_date))
				{
					if($file_title_currency!='USD')
					{
						$b_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $invoice_date);
					}else{
						$b_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $invoice_date);
					}
				}
				$value_in_usd_normal = $expense_record->getDisplayValue('cf_1349');
				if($file_title_currency!='USD')
				{
					$value_in_usd_normal = $expense_record->getDisplayValue('cf_1349')/$b_exchange_rate;
				}

				$value_in_usd = number_format($value_in_usd_normal,2,'.','');

				$AccType = $expense_record->getDisplayValue('cf_1214');
				if (substr($AccType, -1) == 'R' || substr($AccType, -1) == 'K' || substr($AccType, -1) == 'C') {
				$buyingsubtotalR = $buyingsubtotalR + $value_in_usd_normal;}
				if ((substr($AccType, -1) == 'N') or (substr($AccType, -1) == 'D')) {
				$buyingsubtotalN = $buyingsubtotalN + $value_in_usd_normal; }

				$pay_to_id = $expense_record->get('cf_1367');
				$company_accountname = '';
				if(!empty($pay_to_id))
				{
					$crmentity_check_ =  "SELECT vtiger_crmentity.crmid as crmid,  vtiger_crmentity.label  as label, vtiger_crmentity.deleted  as deleted from vtiger_crmentity where crmid=?  ";
					$params = array($pay_to_id);
					$result_crmentity_check_ = $adb->pquery($crmentity_check_, $params);
					$numRows_crmentity_check_ = $adb->num_rows($result_crmentity_check_);
					$row_crmentity_check_ = $adb->fetch_array($result_crmentity_check_);

					if($row_crmentity_check_['deleted']==0)
					{
						$paytoinfo = Vtiger_Record_Model::getInstanceById($pay_to_id, 'Accounts');
						$company_accountname = @$paytoinfo->get('accountname');
					}
					else{
						$company_accountname =$row_crmentity_check_['label'].' -Deleted';
					}
				}

				$total_usd += $value_in_usd;

				$buying .='<tr>
				<td valign="top" width="19">
					'.$i.'
				</td>
				<td valign="top" width="62">
					'.$company_accountname.'
				</td>
				<td valign="top" width="66">
					'.$expense_record->getDisplayValue('cf_1453').'
				</td>
				<td valign="top" width="57">
					'.$expense_record->getDisplayValue('cf_1212').'
				</td>
				<td valign="top" width="47" align="center">
					'.$expense_record->getDisplayValue('cf_1216').'
				</td>
				<td valign="top" width="47" align="center">
				'.$expense_record->getDisplayValue('cf_1477').' '.$expense_record->getDisplayValue('cf_1479').'
				</td>
				<td valign="top" width="57" align="center">
					'.$expense_record->getDisplayValue('cf_1214').'
				</td>
				<td valign="top" width="57" align="right">
					'.number_format ( $expense_record->getDisplayValue('cf_1337') , 2 ,  "." , "," ).'
				</td>
				<td valign="top" width="41" align="center">
					'.$expense_record->getDisplayValue('cf_1339').'
				</td>
				<td valign="top" width="42" align="right">
					'.$expense_record->getDisplayValue('cf_1341').'
				</td>
				<td valign="top" width="72" align="right">
					'.number_format ( $expense_record->getDisplayValue('cf_1343') , 2 ,  "." , "," ).'
				</td>
				<td valign="top" width="36" align="center">
					'.$expense_record->getDisplayValue('cf_1345').'
				</td>
				<td valign="top" width="48" align="right">
					'.number_format($expense_record->getDisplayValue('cf_1222'), 2).'
				</td>
				<td valign="top" width="60" align="right">
					'.number_format ( $expense_record->getDisplayValue('cf_1347') , 2 ,  "." , "," ).'
				</td>
				<td valign="top" width="60" align="right">
					'.number_format ( $expense_record->getDisplayValue('cf_1349') , 2 ,  "." , "," ).'
				</td>
				<td valign="top" width="66" align="right">
					'.$expense_record->getDisplayValue('cf_1351').'
				</td>
				<td valign="top" width="44" align="right">
					'.$expense_record->getDisplayValue('cf_1353').'
				</td>
				<td valign="top" width="60" align="right">
				'.$b_exchange_rate.'
				</td>

					<td valign="top" width="60" align="right">
						'.$value_in_usd.'
					</td>
				</tr>';

			}

			$this->setValue('buying_table', $buying);

			$this->setValue('buyingsubtotalR', number_format ( $buyingsubtotalR , 2 ,  "." , "," ));
			$this->setValue('buyingsubtotalN', number_format ( $buyingsubtotalN , 2 ,  "." , "," ));
			$this->setValue('buyingtotal', $total_usd);
			$this->setValue('BUY_LOCAL_CURRENCY_GROSS', $BUY_LOCAL_CURRENCY_GROSS);
			$this->setValue('BUY_LOCAL_CURRENCY_NET', $BUY_LOCAL_CURRENCY_NET);
			$this->setValue('EXPECTED_BUY_LOCAL_CURRENCY_NET', $EXPECTED_BUY_LOCAL_CURRENCY_NET);
			$this->setValue('VARIATION_EXPECTED_AND_ACTUAL_BUYING', $VARIATION_EXPECTED_AND_ACTUAL_BUYING);

			$this->setValue('TOTAL_COST_USD_GROSS', $TOTAL_COST_USD_GROSS);
			$this->setValue('TOTAL_COST_IN_USD_NET', $TOTAL_COST_IN_USD_NET);
			$this->setValue('TOTAL_EXPECTED_COST_USD_NET', $TOTAL_EXPECTED_COST_USD_NET);
			$this->setValue('TOTAL_VARIATION_EXPECTED_AND_ACTUAL_BUYING_COST_IN_USD', $TOTAL_VARIATION_EXPECTED_AND_ACTUAL_BUYING_COST_IN_USD);


			$this->setValue('selling_table', '');
			$this->setValue('sellingtotal', '');

			$this->setValue('SELL_LOCAL_CURRENCY_GROSS', '');
			$this->setValue('SELL_LOCAL_CURRENCY_NET', '');
			$this->setValue('EXPECTED_SELL_LOCAL_CURRENCY_NET', '');
			$this->setValue('VARIATION_EXPECTED_AND_ACTUAL_SELLING', '');
			$this->setValue('VARIATION_EXPECTED_AND_ACTUAL_SELLING_PROFIT', '');


			$this->setValue('TOTAL_REVENUE_USD_GROSS', '');
			$this->setValue('TOTAL_REVENUE_USD_NET', '');
			$this->setValue('TOTAL_EXPECTED_REVENUE_USD_NET', '');
			$this->setValue('TOTAL_VARIATION_EXPECTED_AND_ACTUAL_REVENUE_USD', '');
			$this->setValue('TOTAL_VARIATION_EXPECTED_AND_ACTUAL_PROFIT_REVENUE_USD', '');

			$pagingModel_p = new Vtiger_Paging_Model();
			$pagingModel_p->set('page','1');

			$relatedModuleName = 'Jobexpencereport';
			$parentRecordModel = $job_info_detail;
			$relationListView = Job_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName, $label);
			$whereCondition['cf_1457'] = array('', 'e', 'Expence', '');//array('fieldname'=>'cf_1457', 'value'=> $Expence,'operator'=>'e');
			$relationListView->set('whereCondition',$whereCondition);

			//$models_p = $relationListView->getEntries($pagingModel_p);
			$models_p = $relationListView->getEntries($pagingModel_p, $JRER_TYPE='0', $QRY_GROUB_BY='0', $type, $job_assigned_user_id);
			$profit_share = array();
			$profit_share_check_new = array();
				foreach($models_p as $key => $model){
					$expense_record= $model;
					$dept_branch_new = $expense_record->get('cf_1477').'-'.$expense_record->get('cf_1479');
					if(!in_array($dept_branch_new, $profit_share_check_new))
					{
					$profit_share_check_new[] = $expense_record->get('cf_1477').'-'.$expense_record->get('cf_1479');

					$col_data_P['cf_1477'] = $expense_record->getDisplayValue('cf_1477');
					$col_data_P['cf_1479'] = $expense_record->getDisplayValue('cf_1479');
					$col_data_P['cf_1477_location_id'] = $expense_record->get('cf_1477');
					$col_data_P['cf_1479_department_id'] = $expense_record->get('cf_1479');

					$profit_share[] = $col_data_P;
					}

				}


				$profit_share_check = array();
				$profit_share_data = array();

				 $sum_of_cost = 0;
				 $sum_of_external_selling = 0;
				 $sum_of_job_profit = 0;
				 $sum_of_internal_selling = 0;
				 $sum_of_profit_share = 0;
				 $sum_of_net_profit = 0;

				 if(!empty($profit_share))
				 {
					 foreach($profit_share as $key => $p_share)
					 {
						 $dept_branch = $p_share['cf_1477_location_id'].'-'.$p_share['cf_1479_department_id'];
						 if(!in_array($dept_branch, $profit_share_check))
						 {
							$profit_share_check[] = $p_share['cf_1477_location_id'].'-'.$p_share['cf_1479_department_id'];
							$brach_department_name = $p_share['cf_1477'].' '.$p_share['cf_1479'];

							$adb_buy_local = PearDatabase::getInstance();
							//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid 06-15-2017
							$sum_buy_local_currency_net =  "SELECT vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
																   vtiger_jobexpencereportcf.cf_1216 as buy_invoice_date,
																   vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
															 FROM `vtiger_jobexpencereport`
															  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid
															 INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
															 left join vtiger_jobexpencereportcf as vtiger_jobexpencereportcf on vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid
															 where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? and vtiger_crmentityrel.module='Job'
																and vtiger_crmentityrel.relmodule='Jobexpencereport' and vtiger_jobexpencereportcf.cf_1457='Expence'
															   and vtiger_jobexpencereportcf.cf_1477=? and vtiger_jobexpencereportcf.cf_1479=?
															";
							if($type=='LJCR')
							{
								$sum_buy_local_currency_net .=  " AND vtiger_jobexpencereportcf.cf_1214 NOT IN('85773', '85774', '85793', '85794') ";
							}

							$sum_buy_local_currency_net .= " AND vtiger_jobexpencereport.owner_id=? ";
							$params_buy_local = array($parentId, $p_share['cf_1477_location_id'], $p_share['cf_1479_department_id'], $job_assigned_user_id);


							//$params_buy_local = array($parentId, $p_share['cf_1477_location_id'], $p_share['cf_1479_department_id'], $current_user->getId());
							$result_buy_locall = $adb_buy_local->pquery($sum_buy_local_currency_net, $params_buy_local);
							$numRows_buy_profit = $adb_buy_local->num_rows($result_buy_locall);

							$cost = 0;
							for($jj=0; $jj< $adb_buy_local->num_rows($result_buy_locall); $jj++ ) {

								$row_jrer_buy_local_currency_net = $adb_buy_local->fetch_row($result_buy_locall,$jj);
								//$row_jrer_buy_local_currency_net = $adb_buy_local->fetch_array($result_buy_locall);

								$cost_local = @$row_jrer_buy_local_currency_net['buy_local_currency_net'];

								$buy_invoice_date = @$row_jrer_buy_local_currency_net['buy_invoice_date'];

								$CurId = $row_jrer_buy_local_currency_net['buy_currency_id'];
								if ($CurId) {
								  $q_cur = 'select * from vtiger_currency_info where id = "'.$CurId.'"';
								  //$row_cur = mysql_fetch_array($q_cur);
								  $result_q_cur = $adb->pquery($q_cur, array());
								  $row_cur = $adb->fetch_array($result_q_cur);
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


							$adb_sell_local = PearDatabase::getInstance();
							//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
							$sum_sell_local_currency_net =  "SELECT vtiger_jobexpencereportcf.cf_1240 as sell_local_currency_net,
																	vtiger_jobexpencereportcf.cf_1355 as sell_invoice_date,
																	vtiger_jobexpencereportcf.cf_1234 as currency_id
															 FROM `vtiger_jobexpencereport`
															  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid
															 INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
															 LEFT JOIN vtiger_jobexpencereportcf as vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid
															   WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? AND vtiger_crmentityrel.module='Job'
															AND vtiger_crmentityrel.relmodule='Jobexpencereport' AND vtiger_jobexpencereportcf.cf_1457='Selling'
															AND vtiger_jobexpencereportcf.cf_1477=? AND vtiger_jobexpencereportcf.cf_1479=?

															";

							$sum_sell_local_currency_net .=" AND vtiger_jobexpencereport.owner_id = ? ";
							$params_sell_local = array($parentId, $p_share['cf_1477_location_id'], $p_share['cf_1479_department_id'], $job_assigned_user_id);

							//$params_sell_local = array($parentId, $p_share['cf_1477_location_id'], $p_share['cf_1479_department_id'], $current_user->getId());
							$result_sell_locall = $adb_sell_local->pquery($sum_sell_local_currency_net, $params_sell_local);

							$numRows_sell_profit = $adb_sell_local->num_rows($result_buy_locall);

							$external_selling = 0;
							for($ji=0; $ji< $adb_sell_local->num_rows($result_sell_locall); $ji++ ) {
								$row_jrer_sell_local_currency_net = $adb_sell_local->fetch_row($result_sell_locall,$ji);
								//$row_jrer_sell_local_currency_net = $adb_sell_local->fetch_array($result_sell_locall);

								$s_sell_local_currency_net = @$row_jrer_sell_local_currency_net['sell_local_currency_net'];
								$sell_invoice_date = @$row_jrer_sell_local_currency_net['sell_invoice_date'];

								$CurId = $row_jrer_sell_local_currency_net['currency_id'];
								if ($CurId) {
								 // $q_cur = mysql_query('select * from vtiger_currency_info where id = "'.$CurId.'"');
								//  $row_cur = mysql_fetch_array($q_cur);

								  $q_cur = 'select * from vtiger_currency_info where id = "'.$CurId.'"';
								  //$row_cur = mysql_fetch_array($q_cur);
								  $result_q_cur = $adb->pquery($q_cur, array());
								  $row_cur = $adb->fetch_array($result_q_cur);
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


							$adb_internal = PearDatabase::getInstance();
							//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
							$internal_selling_arr = "SELECT vtiger_jobexpcf.cf_1263 as internal_selling
													FROM vtiger_jobexp
													INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexp.jobexpid
													 INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
													 LEFT JOIN vtiger_jobexpcf as vtiger_jobexpcf on vtiger_jobexpcf.jobexpid=vtiger_jobexp.jobexpid
													   WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? AND vtiger_crmentityrel.module='Job'
													AND vtiger_crmentityrel.relmodule='Jobexp' AND vtiger_jobexpcf.cf_1257=? AND vtiger_jobexpcf.cf_1259=?
													";

							$params_internal = array($parentId, $p_share['cf_1477_location_id'], $p_share['cf_1479_department_id']);

							$result_internal = $adb_internal->pquery($internal_selling_arr, $params_internal);
							$row_jrer_internal_selling = $adb_internal->fetch_array($result_internal);



							$job_profit = 0;
							$job_profit = $external_selling - $cost;

							$brach_department = $p_share['cf_1479_department_id'].' '.$p_share['cf_1477_location_id'];
							$job_branch_department = $job_info_detail->get('cf_1190').' '.$job_info_detail->get('cf_1188');


							//$profit_share_col = @$row_jrer_internal_selling['internal_selling'] - $cost;
							if(empty($row_jrer_internal_selling['internal_selling']) || $row_jrer_internal_selling['internal_selling']<=0)
							{
								$profit_share_col =  0;
							}
							else{
								$profit_share_col = @$row_jrer_internal_selling['internal_selling'] - $cost;
							}


							$net_profit = $job_profit - $profit_share_col;

							$profit_share_data[] = array('brach_department' => $brach_department_name,
														 'cost' => number_format ( $cost , 2 ,  "." , "," ),
														 'external_selling' => number_format ( $external_selling , 2 ,  "." , "," ),
														 'job_profit'  =>  number_format ( $job_profit , 2 ,  "." , "," ),
														 'office_id' => $p_share['cf_1477_location_id'],
														 'department_id' => $p_share['cf_1479_department_id'],
														 'job_id' => $parentId,
														 'profit_share_col' =>  number_format($profit_share_col, 2 ,  "." , "," ),
														 'net_profit' =>  number_format ( $net_profit , 2 ,  "." , "," ),
														 'internal_selling' => @$row_jrer_internal_selling['internal_selling'],
														 );

							$sum_of_cost += $cost;
							$sum_of_external_selling +=$external_selling;
							$sum_of_job_profit +=$job_profit;
							$sum_of_internal_selling +=@$row_jrer_internal_selling['internal_selling'];
							$sum_of_profit_share +=$profit_share_col;
							$sum_of_net_profit +=$net_profit;

						 }
					 }
				 }


				 $profit_data = '';
				 $i=0;
				 foreach($profit_share_data as $key => $PROFIT)
					 {
						$i++;
						$profit_data .= '<tr>
						<td valign="top" width="19">'.$i.'</td>
						<td valign="top" width="100">'.$PROFIT['brach_department'].'</td>
						<td valign="top" width="100" align="right">'.$PROFIT['cost'].'</td>
						<td valign="top" width="150" align="right">'.$PROFIT['external_selling'].'</td>
						<td valign="top" width="100" align="right">'.$PROFIT['job_profit'].'</td>
						<td valign="top" width="150" align="right" >'.$PROFIT['internal_selling'].' </td>
						<td valign="top" width="150" align="right" >'.$PROFIT['profit_share_col'].'</td>
						<td valign="top" width="100" align="right">'.$PROFIT['net_profit'].'</td>
						</tr>';
					 }

				 $this->setValue('PROFIT_TABLE' ,$profit_data);
				 $this->setValue('SUM_OF_COST' , number_format($sum_of_cost , 2 ,  "." , "," ));
				 $this->setValue('SUM_OF_EXTERNAL_SELLING' , number_format($sum_of_external_selling , 2 ,  "." , "," ));
				 $this->setValue('SUM_OF_JOB_PROFIT' , number_format($sum_of_job_profit , 2 ,  "." , "," ));
				 $this->setValue('SUM_OF_INTERNAL_SELLING' , number_format($sum_of_internal_selling , 2 ,  "." , "," ));
				 $this->setValue('SUM_OF_PROFIT_SHARE' , number_format($sum_of_profit_share , 2 ,  "." , "," ));
				 $this->setValue('SUM_OF_NET_PROFIT' , number_format($sum_of_net_profit , 2 ,  "." , "," ));
				 $this->setValue('NET_PROFIT_LABEL' , 'Net profit');
				 $this->setValue('PROFIT_SHARE_LABEL' ,  'Profit Share');




			include('include/mpdf60/mpdf.php');
			@date_default_timezone_set($current_user->get('time_zone'));
			$mpdf = new mPDF('utf-8', 'A4-L', '10', '', 10, 10, 30, 15, 10, 5); /*задаем формат, отступы и.т.д.*/
			$mpdf->charset_in = 'utf8';

			$mpdf->list_indent_first_level = 0;

			//$mpdf->SetDefaultFontSize(12);
			//$mpdf->setAutoTopMargin(2);
			$mpdf->SetHTMLHeader('<table width="100%" cellpadding="0" cellspacing="0">
				<tr><td align="right" style="font-size:9;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">JCR Form, GLOBALINK, designed: March, 2010</td></tr>
				<tr><td align="right"><img src="include/calendar_logo.jpg"/></td></tr></table>');
			$mpdf->SetHTMLFooter('<table width="100%" cellpadding="0" cellspacing="0">
				<tr><td width="40%" align="left" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">Printed: '.date('d.m.Y; H:i').' by '.$current_user->get('user_name').'</td>
				<td width="20%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">Page {PAGENO} of {nbpg}</td>
				<td width="40%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">&nbsp;</td>
				</table>');
			$stylesheet = file_get_contents('include/mpdf60/examples/mpdfstyletables.css');
			$mpdf->WriteHTML($stylesheet,1);	// The parameter 1 tells that this is css/style only and no body/html/text
			$mpdf->WriteHTML($this->_documentXML); /*формируем pdf*/


			$pdf_name = 'pdf_docs/subjcr_'.$job_id.'.pdf';

			$mpdf->Output($pdf_name, 'F');
			//header('Location:http://mb.globalink.net/vt60/'.$pdf_name);
			header('Location:'.$pdf_name);
			exit;


	}

	public function print_JCR($request)
	{
		include('include/Exchangerate/exchange_rate_class.php');
		global $adb;
		//$adb->setDebug(true);
		$moduleName = $request->getModule();
		$record = $request->get('record');
		$type=$request->get('type');

		$job_id = $record;
		$current_user = Users_Record_Model::getCurrentUserModel();

		$job_info_detail = Vtiger_Record_Model::getInstanceById($job_id, 'Job');

		$document = $this->loadTemplate('printtemplates/Job/jcr.html');

		$owner_job_user_info = Users_Record_Model::getInstanceById($job_info_detail->get('assigned_user_id'), 'Users');

		$this->setValue('glk_useroffice',$owner_job_user_info->getDisplayValue('location_id'));

		$this->setValue('useroffice',$owner_job_user_info->getDisplayValue('location_id'));
		$this->setValue('userdepartment',$owner_job_user_info->getDisplayValue('department_id'));
		$this->setValue('mobile',$owner_job_user_info->get('phone_mobile'));
		$this->setValue('fax',$owner_job_user_info->get('phone_fax'));
		$this->setValue('email',htmlentities($owner_job_user_info->get('email1'), ENT_QUOTES, "UTF-8"));
		$this->setValue('cityname',htmlentities($job_info_detail->getDisplayValue('cf_1188'), ENT_QUOTES, "UTF-8"));
		//$this->setValue('countryname',htmlentities($owner_job_user_info->get('address_country'), ENT_QUOTES, "UTF-8"));
		$this->setValue('departmentcode',htmlentities($job_info_detail->getDisplayValue('cf_1190'), ENT_QUOTES, "UTF-8"));
		$this->setValue('dateadded',date('d.m.Y', strtotime($job_info_detail->get('createdtime'))));
		$this->setValue('from', htmlentities($owner_job_user_info->get('first_name').' '.$owner_job_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));

		$this->setValue('job_ref_no', $job_info_detail->get('cf_1198'));
		$this->setValue('type', $job_info_detail->getDisplayValue('cf_1200'));

		$this->setValue('shipper', htmlentities($job_info_detail->getDisplayValue('cf_1072'), ENT_QUOTES, "UTF-8"));

		$this->setValue('consignee', htmlentities($job_info_detail->getDisplayValue('cf_1074'), ENT_QUOTES, "UTF-8"));
		$this->setValue('origin', htmlentities($job_info_detail->getDisplayValue('cf_1508').'/'.$job_info_detail->getDisplayValue('cf_1504'), ENT_QUOTES, "UTF-8"));
		$this->setValue('destination', htmlentities($job_info_detail->getDisplayValue('cf_1510').'/'.$job_info_detail->getDisplayValue('cf_1506'), ENT_QUOTES, "UTF-8"));

		$this->setValue('pieces', htmlentities($job_info_detail->getDisplayValue('cf_1429'), ENT_QUOTES, "UTF-8"));
		$this->setValue('weightvol', htmlentities($job_info_detail->getDisplayValue('cf_1084').' '.$job_info_detail->getDisplayValue('cf_1520').' /  '.$job_info_detail->getDisplayValue('cf_1086').' '.$job_info_detail->getDisplayValue('cf_1522'), ENT_QUOTES, "UTF-8"));
		$this->setValue('waybill', htmlentities($job_info_detail->getDisplayValue('cf_1096'), ENT_QUOTES, "UTF-8"));
		$this->setValue('cargo', htmlspecialchars_decode($job_info_detail->getDisplayValue('cf_1547')));
		$this->setValue('remark', htmlentities($job_info_detail->getDisplayValue('cf_1102'), ENT_QUOTES, "UTF-8"));

		$client_id = $job_info_detail->get('cf_1441');
		$client_accountname = '';
		if(!empty($client_id))
		{
			//$clientinfo = Vtiger_Record_Model::getInstanceById($client_id, 'Accounts');
			//$client_accountname = @$clientinfo->get('cf_2395');

			//$clientinfo = Vtiger_Record_Model::getInstanceById($client_id, 'Accounts');
			//$client_accountname = @$clientinfo->get('cf_2395');
			$crmentity_check_ =  "SELECT vtiger_crmentity.crmid as crmid,  vtiger_crmentity.label  as label, vtiger_crmentity.deleted  as deleted from vtiger_crmentity where crmid=?  ";
			$params = array($client_id);
			$result_crmentity_check_ = $adb->pquery($crmentity_check_, $params);
			$numRows_crmentity_check_ = $adb->num_rows($result_crmentity_check_);
			$row_crmentity_check_ = $adb->fetch_array($result_crmentity_check_);

			if($row_crmentity_check_['deleted']==0)
			{
				$clientinfo = Vtiger_Record_Model::getInstanceById($client_id, 'Accounts');
				$client_accountname = @$clientinfo->get('cf_2395');
			}
			else{
				$client_accountname = $row_crmentity_check_['label'].' -Deleted';
			}


		}

		$this->setValue('client', htmlentities($client_accountname, ENT_QUOTES, "UTF-8"));

		//For GLK Invoice Number
		$invoice_query = $adb->pquery('select * from vtiger_jobexpencereport
							  INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid
							  where vtiger_jobexpencereport.job_id="'.$job_id.'" AND vtiger_jobexpencereport.owner_id = "'.$job_info_detail->get('assigned_user_id').'" AND vtiger_jobexpencereport.invoice_no !=""
							  		AND vtiger_jobexpencereportcf.cf_1457="Selling"
							  GROUP BY vtiger_jobexpencereport.invoice_no
							  ORDER BY vtiger_jobexpencereportcf.cf_1477, vtiger_jobexpencereportcf.cf_1479 ASC
							  ');
		$invoice_no = array();
		while($row = $adb->fetch_array($invoice_query))
		{
			$invoice_no[] =$row['invoice_no'];
		}
		$pv_GlkInvoice = implode(';', $invoice_no);
		$this->setValue('pv_GlkInvoice', htmlentities($pv_GlkInvoice, ENT_QUOTES, "UTF-8"));

		$company_id = $job_info_detail->get('cf_1186');
		$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency($company_id);
		$file_title_currency = $reporting_currency;

		$jrer_last_sql =  "SELECT vtiger_crmentity.createdtime FROM vtiger_jobexpencereportcf
							 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereportcf.jobexpencereportid
							 INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid
							 WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid=? AND crmentityrel.module='Job'
							 AND crmentityrel.relmodule='Jobexpencereport' order by vtiger_crmentity.createdtime DESC limit 1";
		// parentId = Fleet Id
		$parentId = $job_id;
		$params = array($parentId);
		$result_last = $adb->pquery($jrer_last_sql, $params);
		$row_jrer_last = $adb->fetch_array($result_last);
		$count_last_modified = $adb->num_rows($result_last);

		$exchange_rate_date  = date('Y-m-d');
		if($count_last_modified>0)
		{
			$modifiedtime = $row_jrer_last['createdtime'];
			$modifiedtime = strtotime($row_jrer_last['createdtime']);
			$exchange_rate_date = date('Y-m-d', $modifiedtime);
		}

		if($file_title_currency!='USD')
		{
			$final_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $exchange_rate_date);
		}else{
			$final_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $exchange_rate_date);
		}
			//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
			$jrer_sum_sql =  "SELECT sum(vtiger_jobexpencereportcf.cf_1347) as buy_local_currency_gross,
			sum(vtiger_jobexpencereportcf.cf_1349) as buy_local_currency_net,
			sum(vtiger_jobexpencereportcf.cf_1351) as expected_buy_local_currency_net,
			sum(vtiger_jobexpencereportcf.cf_1353) as variation_expected_and_actual_buying
			FROM `vtiger_jobexpencereport`
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid
			INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
			LEFT JOIN vtiger_jobexpencereportcf as vtiger_jobexpencereportcf on vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid
			WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=?
			AND vtiger_crmentityrel.module='Job'
			AND vtiger_crmentityrel.relmodule='Jobexpencereport'
			AND vtiger_jobexpencereportcf.cf_1457='Expence' AND vtiger_jobexpencereport.owner_id='".$job_info_detail->get('assigned_user_id')."'";

			if($type=='LJCR')
			{
				$jrer_sum_sql .=  " AND vtiger_jobexpencereportcf.cf_1214 NOT IN('85773', '85774', '85793', '85794') ";
			}

			$parentId = $job_id;
			$params = array($parentId);
			$result = $adb->pquery($jrer_sum_sql, $params);
			$row_job_jrer = $adb->fetch_array($result);

			$BUY_LOCAL_CURRENCY_GROSS = number_format ( $row_job_jrer['buy_local_currency_gross'] , 2 ,  "." , ",");
			$BUY_LOCAL_CURRENCY_NET   = number_format ( $row_job_jrer['buy_local_currency_net'] , 2 ,  "." , "," );
			$EXPECTED_BUY_LOCAL_CURRENCY_NET  = number_format ( $row_job_jrer['expected_buy_local_currency_net'] , 2 ,  "." , "," );
			$VARIATION_EXPECTED_AND_ACTUAL_BUYING = number_format ( $row_job_jrer['variation_expected_and_actual_buying'] , 2 ,  "." , "," );

			//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
			$jrer_sql_expense =  "SELECT vtiger_jobexpencereportcf.cf_1347 as buy_local_currency_gross,
			vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
			vtiger_jobexpencereportcf.cf_1351 as expected_buy_local_currency_net,
			vtiger_jobexpencereportcf.cf_1353 as variation_expected_and_actual_buying,
			vtiger_jobexpencereportcf.cf_1216 as expense_invoice_date,
			vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
			FROM `vtiger_jobexpencereport`
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid
			INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
			LEFT JOIN vtiger_jobexpencereportcf as vtiger_jobexpencereportcf on vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid
			WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=?
			AND vtiger_crmentityrel.module='Job'
			AND vtiger_crmentityrel.relmodule='Jobexpencereport'
			AND vtiger_jobexpencereportcf.cf_1457='Expence' AND vtiger_jobexpencereport.owner_id='".$job_info_detail->get('assigned_user_id')."'";
			if($type=='LJCR')
			{
				$jrer_sql_expense .=  " AND vtiger_jobexpencereportcf.cf_1214 NOT IN('85773', '85774', '85793', '85794') ";
			}

			$parentId = $job_id;
			$params = array($parentId);
			$result_expense = $adb->pquery($jrer_sql_expense, $params);
			$numRows_expnese = $adb->num_rows($result_expense);

			$total_cost_in_usd_gross = 0;
			$total_cost_in_usd_net = 0;
			$total_expected_cost_in_usd_net = 0;
			$total_variation_expected_and_actual_buying_cost_in_usd = 0;

			if($numRows_expnese>0)
			{
				for($ii=0; $ii< $adb->num_rows($result_expense); $ii++ ) {
					$row_job_jrer_expense = $adb->fetch_row($result_expense,$ii);
					$expense_invoice_date = $row_job_jrer_expense['expense_invoice_date'];

					$CurId = $row_job_jrer_expense['buy_currency_id'];
					if ($CurId) {
						$q_cur = $adb->pquery('select * from vtiger_currency_info where id = "'.$CurId.'"');
						$row_cur = $adb->fetch_array($q_cur);
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
					}else{
						if($b_exchange_rate==0)
						{
						$b_exchange_rate = 1;
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

			/*$total_cost_in_usd_gross = $row_job_jrer['buy_local_currency_gross']/$final_exchange_rate;
			$total_cost_in_usd_net = $row_job_jrer['buy_local_currency_net']/$final_exchange_rate;
			$total_expected_cost_in_usd_net = $row_job_jrer['expected_buy_local_currency_net']/$final_exchange_rate;
			$total_variation_expected_and_actual_buying_cost_in_usd = $row_job_jrer['variation_expected_and_actual_buying']/$final_exchange_rate;*/

			$TOTAL_COST_USD_GROSS = number_format ( $total_cost_in_usd_gross , 2 ,  "." , "," );
			$TOTAL_COST_IN_USD_NET = number_format ( $total_cost_in_usd_net , 2 ,  "." , "," );
			$TOTAL_EXPECTED_COST_USD_NET = number_format ( $total_expected_cost_in_usd_net , 2 ,  "." , "," );
			$TOTAL_VARIATION_EXPECTED_AND_ACTUAL_BUYING_COST_IN_USD = number_format ( $total_variation_expected_and_actual_buying_cost_in_usd , 2 ,  "." , "," );


		$pagingModel = new Vtiger_Paging_Model();
		$pagingModel->set('page','1');
		$pagingModel->set('limit',500);

		$relatedModuleName = 'Jobexpencereport';
		$parentRecordModel = $job_info_detail;
		$relationListView = Job_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName, $label);
		$whereCondition['cf_1457'] = array('', 'e', 'Expence', '');//array('fieldname'=>'cf_1457', 'value'=> $Expence,'operator'=>'e');
		//if($type=='LJCR')
		//{
		//	$whereCondition['cf_1214'] = array('', 'n', array(85773, 85774, 85793, 85794), '');
		//}

		$relationListView->set('whereCondition',$whereCondition);
		$models = $relationListView->getEntries($pagingModel, $JRER_TYPE='0', $QRY_GROUB_BY='0', $type);


		$i=0;
		$total_usd = 0;
		$buyingsubtotalR = 0;
		$buyingsubtotalN = 0;
		$buying = '';
		foreach($models as $key => $model)
		{
			//$expense_record= $model->getInstanceById($model->getId());
			$expense_record= $model;
			//if($model->getDisplayValue('cf_1457') == 'Selling'){
			//	continue;
			//}
			$i++;

			$Cur = $expense_record->getDisplayValue('cf_1345');
			$invoice_date = $expense_record->get('cf_1216');
			$b_exchange_rate = $final_exchange_rate;
			if(!empty($invoice_date))
			{
				if($file_title_currency!='USD')
				{
					$b_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $invoice_date);
				}else{
					$b_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $invoice_date);
				}
			}
			$value_in_usd_normal = $expense_record->getDisplayValue('cf_1349');
			if($file_title_currency!='USD')
			{
				$value_in_usd_normal = $expense_record->getDisplayValue('cf_1349')/$b_exchange_rate;
			}

			$value_in_usd = number_format($value_in_usd_normal,2,'.','');

			$AccType = $expense_record->getDisplayValue('cf_1214');
			if (substr($AccType, -1) == 'R' || substr($AccType, -1) == 'K' || substr($AccType, -1) == 'C') {
			$buyingsubtotalR = $buyingsubtotalR + $value_in_usd_normal;}
			if ((substr($AccType, -1) == 'N') or (substr($AccType, -1) == 'D')) {
			$buyingsubtotalN = $buyingsubtotalN + $value_in_usd_normal; }

			$pay_to_id = $expense_record->get('cf_1367');
			$company_accountname = '';
			if(!empty($pay_to_id))
			{
				$crmentity_check_ =  "SELECT vtiger_crmentity.crmid as crmid,  vtiger_crmentity.label  as label, vtiger_crmentity.deleted  as deleted from vtiger_crmentity where crmid=?  ";
				$params = array($pay_to_id);
				$result_crmentity_check_ = $adb->pquery($crmentity_check_, $params);
				$numRows_crmentity_check_ = $adb->num_rows($result_crmentity_check_);
				$row_crmentity_check_ = $adb->fetch_array($result_crmentity_check_);

				if($row_crmentity_check_['deleted']==0)
				{
					$paytoinfo = Vtiger_Record_Model::getInstanceById($pay_to_id, 'Accounts');
					$company_accountname = @$paytoinfo->get('accountname');
				}
				else{
					$company_accountname =$row_crmentity_check_['label'].' -Deleted';
				}
			}

			$total_usd += $value_in_usd;

			$buying .='<tr>
            <td valign="top" width="19">
                '.$i.'
            </td>
            <td valign="top" width="62">
                '.$company_accountname.'
            </td>
            <td valign="top" width="66">
                '.$expense_record->getDisplayValue('cf_1453').'
            </td>
            <td valign="top" width="57">
                '.$expense_record->getDisplayValue('cf_1212').'
            </td>
            <td valign="top" width="47" align="center">
                '.$expense_record->getDisplayValue('cf_1216').'
            </td>
            <td valign="top" width="47" align="center">
              '.$expense_record->getDisplayValue('cf_1477').' '.$expense_record->getDisplayValue('cf_1479').'
            </td>
            <td valign="top" width="57" align="center">
                '.$expense_record->getDisplayValue('cf_1214').'
            </td>
            <td valign="top" width="57" align="right">
                 '.number_format ( $expense_record->getDisplayValue('cf_1337') , 2 ,  "." , "," ).'
            </td>
            <td valign="top" width="41" align="center">
                 '.$expense_record->getDisplayValue('cf_1339').'
            </td>
            <td valign="top" width="42" align="right">
                '.$expense_record->getDisplayValue('cf_1341').'
            </td>
            <td valign="top" width="72" align="right">
                '.number_format ( $expense_record->getDisplayValue('cf_1343') , 2 ,  "." , "," ).'
            </td>
            <td valign="top" width="36" align="center">
                '.$expense_record->getDisplayValue('cf_1345').'
            </td>
            <td valign="top" width="48" align="right">
                '.number_format($expense_record->getDisplayValue('cf_1222'), 2).'
            </td>
            <td valign="top" width="60" align="right">
                '.number_format ( $expense_record->getDisplayValue('cf_1347') , 2 ,  "." , "," ).'
            </td>
            <td valign="top" width="60" align="right">
                '.number_format ( $expense_record->getDisplayValue('cf_1349') , 2 ,  "." , "," ).'
            </td>
            <td valign="top" width="66" align="right">
                '.$expense_record->getDisplayValue('cf_1351').'
            </td>
            <td valign="top" width="44" align="right">
                '.$expense_record->getDisplayValue('cf_1353').'
			</td>
			<td valign="top" width="60" align="right">
			'.$b_exchange_rate.'
			</td>

				<td valign="top" width="60" align="right">
					'.$value_in_usd.'
				</td>
			</tr>';

		}

		$this->setValue('buying_table', $buying);

		$this->setValue('buyingsubtotalR', number_format ( $buyingsubtotalR , 2 ,  "." , "," ));
		$this->setValue('buyingsubtotalN', number_format ( $buyingsubtotalN , 2 ,  "." , "," ));
		$this->setValue('buyingtotal', $total_usd);
		$this->setValue('BUY_LOCAL_CURRENCY_GROSS', $BUY_LOCAL_CURRENCY_GROSS);
		$this->setValue('BUY_LOCAL_CURRENCY_NET', $BUY_LOCAL_CURRENCY_NET);
		$this->setValue('EXPECTED_BUY_LOCAL_CURRENCY_NET', $EXPECTED_BUY_LOCAL_CURRENCY_NET);
		$this->setValue('VARIATION_EXPECTED_AND_ACTUAL_BUYING', $VARIATION_EXPECTED_AND_ACTUAL_BUYING);

		$this->setValue('TOTAL_COST_USD_GROSS', $TOTAL_COST_USD_GROSS);
		$this->setValue('TOTAL_COST_IN_USD_NET', $TOTAL_COST_IN_USD_NET);
		$this->setValue('TOTAL_EXPECTED_COST_USD_NET', $TOTAL_EXPECTED_COST_USD_NET);
		$this->setValue('TOTAL_VARIATION_EXPECTED_AND_ACTUAL_BUYING_COST_IN_USD', $TOTAL_VARIATION_EXPECTED_AND_ACTUAL_BUYING_COST_IN_USD);



		$pagingModel_s = new Vtiger_Paging_Model();
		$pagingModel_s->set('page','1');
		$pagingModel_s->set('limit',500);

		$relatedModuleName = 'Jobexpencereport';
		$parentRecordModel = $job_info_detail;
		$relationListView = Job_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName, $label);
		$whereCondition['cf_1457'] = array('', 'e', 'Selling', '');//array('fieldname'=>'cf_1457', 'value'=> 'Selling','operator'=>'e');

		$relationListView->set('whereCondition',$whereCondition);
		$models_s = $relationListView->getEntries($pagingModel_s);


		$i=0;
		$total_usd_selling = 0;
		$total_cost_in_usd_customer = 0;
		$total_cost_in_sell_gross = $total_revenue_usd_gross = 0;
		$total_cost_in_sell_net = $total_revenue_usd_net = 0;
		$total_expected_sell_in_net = $total_expected_revenue_usd_net = 0;
		$total_variation_expected_and_actual_selling = $total_variation_expected_and_actual_revenue_usd = 0;
		$total_variation_expect_and_actual_selling_profit = $total_variation_expect_and_actual_profit_revenue_usd = 0;
		$selling = '';
		foreach($models_s as $key => $model)
		{
			//$selling_record= $model->getInstanceById($model->getId());
			$selling_record= $model;
			//if($model->getDisplayValue('cf_1457') == 'Selling'){
			//	continue;
			//}
			$i++;

			$Cur = $selling_record->getDisplayValue('cf_1234');
			$invoice_date = $selling_record->get('cf_1355');
			$s_exchange_rate = $final_exchange_rate;
			if(!empty($invoice_date))
			{
				if($file_title_currency!='USD')
				{
					$s_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $invoice_date);
				}else{
					$s_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $invoice_date);
				}
			}
			$value_in_usd_normal = $selling_record->getDisplayValue('cf_1240');
			if($file_title_currency!='USD')
			{
				$value_in_usd_normal = $selling_record->getDisplayValue('cf_1240')/$s_exchange_rate;

				$total_cost_in_usd_customer += $selling_record->getDisplayValue('cf_1232')/$s_exchange_rate;
				$total_revenue_usd_gross += $selling_record->getDisplayValue('cf_1238')/$s_exchange_rate;

				$total_revenue_usd_net += $selling_record->getDisplayValue('cf_1240')/$s_exchange_rate;
				$total_expected_revenue_usd_net += $selling_record->getDisplayValue('cf_1242')/$s_exchange_rate;

				$total_variation_expected_and_actual_revenue_usd +=$selling_record->getDisplayValue('cf_1244')/$s_exchange_rate;
				$total_variation_expect_and_actual_profit_revenue_usd += $selling_record->getDisplayValue('cf_1246')/$s_exchange_rate;
			}
			else{
				$total_cost_in_usd_customer += $selling_record->getDisplayValue('cf_1232');
				$total_revenue_usd_gross += $selling_record->getDisplayValue('cf_1238');

				$total_revenue_usd_net += $selling_record->getDisplayValue('cf_1240');
				$total_expected_revenue_usd_net += $selling_record->getDisplayValue('cf_1242');

				$total_variation_expected_and_actual_revenue_usd += $selling_record->getDisplayValue('cf_1244');
				$total_variation_expect_and_actual_profit_revenue_usd += $selling_record->getDisplayValue('cf_1246');
			}
			$value_in_usd = number_format($value_in_usd_normal,2,'.','');

			//Sum value
			$total_cost_in_sell_gross += $selling_record->getDisplayValue('cf_1238');
			$total_cost_in_sell_net += $selling_record->getDisplayValue('cf_1240');
			$total_expected_sell_in_net += $selling_record->getDisplayValue('cf_1242');

			$total_variation_expected_and_actual_selling += $selling_record->getDisplayValue('cf_1244');
			$total_variation_expect_and_actual_selling_profit += $selling_record->getDisplayValue('cf_1246');

			$bill_to_id = $selling_record->get('cf_1445');
			$company_accountname = '';
			if(!empty($bill_to_id))
			{
				$crmentity_check_ =  "SELECT vtiger_crmentity.crmid as crmid,  vtiger_crmentity.label  as label, vtiger_crmentity.deleted  as deleted from vtiger_crmentity where crmid=?  ";
				$params = array($bill_to_id);
				$result_crmentity_check_ = $adb->pquery($crmentity_check_, $params);
				$numRows_crmentity_check_ = $adb->num_rows($result_crmentity_check_);
				$row_crmentity_check_ = $adb->fetch_array($result_crmentity_check_);

				if($row_crmentity_check_['deleted']==0)
				{
					$billtoinfo = Vtiger_Record_Model::getInstanceById($bill_to_id, 'Accounts');
					$company_accountname = @$billtoinfo->get('cf_2395');
				}
				else{
					$company_accountname =$row_crmentity_check_['label'].' -Deleted';
				}
			}

			$total_usd_selling += $value_in_usd;

			$selling .='<tr>
            <td valign="top" width="19">
                '.$i.'
            </td>
            <td valign="top" width="62">
                '.$company_accountname.'
            </td>
            <td valign="top" width="66">
                '.$selling_record->getDisplayValue('cf_1455').'
            </td>
            <td valign="top" width="57">
                '.$selling_record->getDisplayValue('cf_1355').'
            </td>
            <td valign="top" width="47" align="center">
			'.$selling_record->getDisplayValue('cf_1477').' '.$selling_record->getDisplayValue('cf_1479').'
            </td>
            <td valign="top" width="47" align="center">
			'.number_format ( $selling_record->getDisplayValue('cf_1357') , 2 ,  "." , "," ).'
            </td>
            <td valign="top" width="57" align="center">
			'.number_format ( $selling_record->getDisplayValue('cf_1228') , 2 ,  "." , "," ).'
            </td>
            <td valign="top" width="57" align="right">
                 '.number_format ( $selling_record->getDisplayValue('cf_1230') , 2 ,  "." , "," ).'
            </td>
            <td valign="top" width="41" align="center">
			'.number_format ( $selling_record->getDisplayValue('cf_1232') , 2 ,  "." , "," ).'
            </td>
            <td valign="top" width="42" align="right">
                '.$selling_record->getDisplayValue('cf_1234').'
            </td>
            <td valign="top" width="72" align="right">
                '.number_format ( $selling_record->getDisplayValue('cf_1236') , 2 ,  "." , "," ).'
            </td>
			<td valign="top" width="36" align="center">
			'.number_format ( $selling_record->getDisplayValue('cf_1238') , 2 ,  "." , "," ).'
            </td>
            <td valign="top" width="48" align="right">
                '.number_format($selling_record->getDisplayValue('cf_1240'), 2).'
            </td>
            <td valign="top" width="60" align="right">
                '.number_format ( $selling_record->getDisplayValue('cf_1242') , 2 ,  "." , "," ).'
            </td>
            <td valign="top" width="60" align="right">
                '.number_format ( $selling_record->getDisplayValue('cf_1244') , 2 ,  "." , "," ).'
            </td>
			<td valign="top" width="66" align="right">
			'.number_format ( $selling_record->getDisplayValue('cf_1246') , 2 ,  "." , "," ).'
            </td>
			<td valign="top" width="60" align="right">
			'.$s_exchange_rate.'
			</td>

            <td valign="top" width="60" align="right">
                '.$value_in_usd.'
            </td>
        </tr>';

		}

		$this->setValue('selling_table', $selling);
		$this->setValue('sellingtotal', $total_usd_selling);

		$SELL_LOCAL_CURRENCY_GROSS = number_format ( $total_cost_in_sell_gross , 2 ,  "." , ",");
		$SELL_LOCAL_CURRENCY_NET   = number_format ( $total_cost_in_sell_net , 2 ,  "." , "," );
		$EXPECTED_SELL_LOCAL_CURRENCY_NET  = number_format ( $total_expected_sell_in_net , 2 ,  "." , "," );
		$VARIATION_EXPECTED_AND_ACTUAL_SELLING = number_format ( $total_variation_expected_and_actual_selling , 2 ,  "." , "," );
		$VARIATION_EXPECTED_AND_ACTUAL_SELLING_PROFIT = number_format ( $total_variation_expect_and_actual_selling_profit , 2 ,  "." , "," );

		$this->setValue('SELL_LOCAL_CURRENCY_GROSS', $SELL_LOCAL_CURRENCY_GROSS);
		$this->setValue('SELL_LOCAL_CURRENCY_NET', $SELL_LOCAL_CURRENCY_NET);
		$this->setValue('EXPECTED_SELL_LOCAL_CURRENCY_NET', $EXPECTED_SELL_LOCAL_CURRENCY_NET);
		$this->setValue('VARIATION_EXPECTED_AND_ACTUAL_SELLING', $VARIATION_EXPECTED_AND_ACTUAL_SELLING);
		$this->setValue('VARIATION_EXPECTED_AND_ACTUAL_SELLING_PROFIT', $VARIATION_EXPECTED_AND_ACTUAL_SELLING_PROFIT);


		$TOTAL_REVENUE_USD_GROSS = number_format ( $total_revenue_usd_gross , 2 ,  "." , "," );
		$TOTAL_REVENUE_USD_NET = number_format ( $total_revenue_usd_net , 2 ,  "." , "," );
		$TOTAL_EXPECTED_REVENUE_USD_NET = number_format ( $total_expected_revenue_usd_net , 2 ,  "." , "," );
		$TOTAL_VARIATION_EXPECTED_AND_ACTUAL_REVENUE_USD = number_format ( $total_variation_expected_and_actual_revenue_usd , 2 ,  "." , "," );
		$TOTAL_VARIATION_EXPECTED_AND_ACTUAL_PROFIT_REVENUE_USD = number_format ( $total_variation_expect_and_actual_profit_revenue_usd , 2 ,  "." , "," );


		$this->setValue('TOTAL_REVENUE_USD_GROSS', $TOTAL_REVENUE_USD_GROSS);
		$this->setValue('TOTAL_REVENUE_USD_NET', $TOTAL_REVENUE_USD_NET);
		$this->setValue('TOTAL_EXPECTED_REVENUE_USD_NET', $TOTAL_EXPECTED_REVENUE_USD_NET);
		$this->setValue('TOTAL_VARIATION_EXPECTED_AND_ACTUAL_REVENUE_USD', $TOTAL_VARIATION_EXPECTED_AND_ACTUAL_REVENUE_USD);
		$this->setValue('TOTAL_VARIATION_EXPECTED_AND_ACTUAL_PROFIT_REVENUE_USD', $TOTAL_VARIATION_EXPECTED_AND_ACTUAL_PROFIT_REVENUE_USD);


		$current_user = Users_Record_Model::getCurrentUserModel();

		$count_parent_role = 4;
		if($current_user->get('is_admin')!='on')
		{
			$privileges   = $current_user->get('privileges');
			$parent_roles_arr = $privileges->parent_roles;
			$count_parent_role = count($parent_roles_arr);

			if($_REQUEST['module']=='Jobexpencereport' && $count_parent_role==0)
			{
				$role_id =  $current_user->get('roleid');
				$depth_role = "SELECT * FROM vtiger_role where roleid='".$role_id."' ";
				//$row_depth = mysql_fetch_array($depth_role);
				$result_role = $adb->pquery($depth_role, array());
				$row_depth = $adb->fetch_array($result_role);
				$count_parent_role = $row_depth['depth'];
			}
		}

		$pagingModel_p = new Vtiger_Paging_Model();
		$pagingModel_p->set('page','1');

		$relatedModuleName = 'Jobexpencereport';
		$parentRecordModel = $job_info_detail;
		$relationListView = Job_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName, $label);
		//$whereCondition['cf_1457'] = array('', 'e', 'Selling', '');//array('fieldname'=>'cf_1457', 'value'=> 'Selling','operator'=>'e');
		//$relationListView->set('whereCondition',$whereCondition);
		$models_p = $relationListView->getEntries($pagingModel_p);
		$profit_share = array();
		$profit_share_check_new = array();
			foreach($models_p as $key => $model){
				$selling_record= $model;
				$dept_branch_new = $selling_record->get('cf_1477').'-'.$selling_record->get('cf_1479');
				if(!in_array($dept_branch_new, $profit_share_check_new))
				{
				$profit_share_check_new[] = $selling_record->get('cf_1477').'-'.$selling_record->get('cf_1479');

				$col_data_P['cf_1477'] = $selling_record->getDisplayValue('cf_1477');
				$col_data_P['cf_1479'] = $selling_record->getDisplayValue('cf_1479');
				$col_data_P['cf_1477_location_id'] = $selling_record->get('cf_1477');
				$col_data_P['cf_1479_department_id'] = $selling_record->get('cf_1479');

				$profit_share[] = $col_data_P;
				}

			}

			$profit_share_check = array();
			$profit_share_data = array();

			 $sum_of_cost = 0;
			 $sum_of_external_selling = 0;
			 $sum_of_job_profit = 0;
			 $sum_of_internal_selling = 0;
			 $sum_of_profit_share = 0;
			 $sum_of_net_profit = 0;

			 if(!empty($profit_share))
			 {
				 foreach($profit_share as $key => $p_share)
				 {
					 $dept_branch = $p_share['cf_1477_location_id'].'-'.$p_share['cf_1479_department_id'];
					 if(!in_array($dept_branch, $profit_share_check))
					 {
						$profit_share_check[] = $p_share['cf_1477_location_id'].'-'.$p_share['cf_1479_department_id'];
						$brach_department_name = $p_share['cf_1477'].' '.$p_share['cf_1479'];

						$adb_buy_local = PearDatabase::getInstance();
						//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid 06-15-2017
						$sum_buy_local_currency_net =  "SELECT vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
															   vtiger_jobexpencereportcf.cf_1216 as buy_invoice_date,
															   vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
									 					FROM `vtiger_jobexpencereport`
							  							INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid
 														INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
 														left join vtiger_jobexpencereportcf as vtiger_jobexpencereportcf on vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid
							 							where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? and vtiger_crmentityrel.module='Job'
							 	   						and vtiger_crmentityrel.relmodule='Jobexpencereport' and vtiger_jobexpencereportcf.cf_1457='Expence'
								   						and vtiger_jobexpencereportcf.cf_1477=? and vtiger_jobexpencereportcf.cf_1479=?
														";
						if($type=='LJCR')
						{
							$sum_buy_local_currency_net .=  " AND vtiger_jobexpencereportcf.cf_1214 NOT IN('85773', '85774', '85793', '85794') ";
						}


						if($current_user->get('roleid')=='H3'  || $count_parent_role <= 3 || $current_user->get('roleid')=='H2')
						{
							$params_buy_local = array($parentId, $p_share['cf_1477_location_id'], $p_share['cf_1479_department_id']);
							$sum_buy_local_currency_net .=' AND vtiger_jobexpencereport.owner_id = "'.$job_info_detail->get('assigned_user_id').'" ' ;
						}
						else{
							$sum_buy_local_currency_net .= " AND vtiger_jobexpencereport.owner_id=? ";
							$params_buy_local = array($parentId, $p_share['cf_1477_location_id'], $p_share['cf_1479_department_id'], $current_user->getId());
						}

						//$params_buy_local = array($parentId, $p_share['cf_1477_location_id'], $p_share['cf_1479_department_id'], $current_user->getId());
						$result_buy_locall = $adb_buy_local->pquery($sum_buy_local_currency_net, $params_buy_local);
						$numRows_buy_profit = $adb_buy_local->num_rows($result_buy_locall);

						$cost = 0;
						for($jj=0; $jj< $adb_buy_local->num_rows($result_buy_locall); $jj++ ) {

							$row_jrer_buy_local_currency_net = $adb_buy_local->fetch_row($result_buy_locall,$jj);
							//$row_jrer_buy_local_currency_net = $adb_buy_local->fetch_array($result_buy_locall);

							$cost_local = @$row_jrer_buy_local_currency_net['buy_local_currency_net'];

							$buy_invoice_date = @$row_jrer_buy_local_currency_net['buy_invoice_date'];

							$CurId = $row_jrer_buy_local_currency_net['buy_currency_id'];
							if ($CurId) {
							  $q_cur = 'select * from vtiger_currency_info where id = "'.$CurId.'"';
							  //$row_cur = mysql_fetch_array($q_cur);
							  $result_q_cur = $adb->pquery($q_cur, array());
							  $row_cur = $adb->fetch_array($result_q_cur);
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


						$adb_sell_local = PearDatabase::getInstance();
						//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
						$sum_sell_local_currency_net =  "SELECT vtiger_jobexpencereportcf.cf_1240 as sell_local_currency_net,
																vtiger_jobexpencereportcf.cf_1355 as sell_invoice_date,
																vtiger_jobexpencereportcf.cf_1234 as currency_id
									 					FROM `vtiger_jobexpencereport`
							  							INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid
 														INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
 														LEFT JOIN vtiger_jobexpencereportcf as vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid
		 					  							WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? AND vtiger_crmentityrel.module='Job'
														AND vtiger_crmentityrel.relmodule='Jobexpencereport' AND vtiger_jobexpencereportcf.cf_1457='Selling'
														AND vtiger_jobexpencereportcf.cf_1477=? AND vtiger_jobexpencereportcf.cf_1479=?

														";
						if($current_user->get('roleid')=='H3'  || $count_parent_role <= 3 || $current_user->get('roleid')=='H2')
						{
						$params_sell_local = array($parentId, $p_share['cf_1477_location_id'], $p_share['cf_1479_department_id']);
						}
						else{
						$sum_sell_local_currency_net .=" AND vtiger_jobexpencereport.owner_id = ? ";
						$params_sell_local = array($parentId, $p_share['cf_1477_location_id'], $p_share['cf_1479_department_id'], $current_user->getId());
						}

						//$params_sell_local = array($parentId, $p_share['cf_1477_location_id'], $p_share['cf_1479_department_id'], $current_user->getId());
						$result_sell_locall = $adb_sell_local->pquery($sum_sell_local_currency_net, $params_sell_local);

						$numRows_sell_profit = $adb_sell_local->num_rows($result_buy_locall);

						$external_selling = 0;
						for($ji=0; $ji< $adb_sell_local->num_rows($result_sell_locall); $ji++ ) {
							$row_jrer_sell_local_currency_net = $adb_sell_local->fetch_row($result_sell_locall,$ji);
							//$row_jrer_sell_local_currency_net = $adb_sell_local->fetch_array($result_sell_locall);

							$s_sell_local_currency_net = @$row_jrer_sell_local_currency_net['sell_local_currency_net'];
							$sell_invoice_date = @$row_jrer_sell_local_currency_net['sell_invoice_date'];

							$CurId = $row_jrer_sell_local_currency_net['currency_id'];
							if ($CurId) {
							 // $q_cur = mysql_query('select * from vtiger_currency_info where id = "'.$CurId.'"');
							//  $row_cur = mysql_fetch_array($q_cur);

							  $q_cur = 'select * from vtiger_currency_info where id = "'.$CurId.'"';
							  //$row_cur = mysql_fetch_array($q_cur);
							  $result_q_cur = $adb->pquery($q_cur, array());
							  $row_cur = $adb->fetch_array($result_q_cur);
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


						$adb_internal = PearDatabase::getInstance();
						//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
						$internal_selling_arr = "SELECT vtiger_jobexpcf.cf_1263 as internal_selling
												FROM vtiger_jobexp
												INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexp.jobexpid
 												INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
 												LEFT JOIN vtiger_jobexpcf as vtiger_jobexpcf on vtiger_jobexpcf.jobexpid=vtiger_jobexp.jobexpid
		 					  					WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? AND vtiger_crmentityrel.module='Job'
												AND vtiger_crmentityrel.relmodule='Jobexp' AND vtiger_jobexpcf.cf_1257=? AND vtiger_jobexpcf.cf_1259=?
												";

						$params_internal = array($parentId, $p_share['cf_1477_location_id'], $p_share['cf_1479_department_id']);

						$result_internal = $adb_internal->pquery($internal_selling_arr, $params_internal);
						$row_jrer_internal_selling = $adb_internal->fetch_array($result_internal);

						/*
						$cost_local = @$row_jrer_buy_local_currency_net['buy_local_currency_net'];
						$cost = $cost_local/$final_exchange_rate;

						$s_sell_local_currency_net = @$row_jrer_sell_local_currency_net['sell_local_currency_net'];
						$external_selling = $s_sell_local_currency_net/$final_exchange_rate;
						*/

						$job_profit = 0;
						if($job_info_detail->get('assigned_user_id')==$current_user->getId() || $current_user->get('roleid')=='H3'  || $count_parent_role <= 3 || $current_user->get('roleid')=='H2')
						{
							$job_profit = $external_selling - $cost;
						}
						else{
							if($s_sell_local_currency_net<=0)
							{
								$job_profit = @$row_jrer_internal_selling['internal_selling'] - $cost;
							}
							else{
								$job_profit = $external_selling - $cost;
							}
						}
						$brach_department = $p_share['cf_1479_department_id'].' '.$p_share['cf_1477_location_id'];
						$job_branch_department = $job_info_detail->get('cf_1190').' '.$job_info_detail->get('cf_1188');

						if(trim($brach_department)==trim($job_branch_department))
						{
							$profit_share_col = 0;
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

						}
						$net_profit = $job_profit - $profit_share_col;

						$profit_share_data[] = array('brach_department' => $brach_department_name,
													 'cost' => number_format ( $cost , 2 ,  "." , "," ),
													 'external_selling' => number_format ( $external_selling , 2 ,  "." , "," ),
													 'job_profit'  =>  number_format ( $job_profit , 2 ,  "." , "," ),
													 'office_id' => $p_share['cf_1477_location_id'],
													 'department_id' => $p_share['cf_1479_department_id'],
													 'job_id' => $parentId,
													 'profit_share_col' => ((trim($brach_department)!=trim($job_branch_department)) ? number_format($profit_share_col, 2 ,  "." , "," ) :''),
													 'net_profit' => (($job_info_detail->get('assigned_user_id')==$current_user->getId() || $current_user->get('roleid')=='H3' || $count_parent_role <= 3  || $current_user->get('roleid')=='H2') ? number_format ( $net_profit , 2 ,  "." , "," ):''),
													 'internal_selling' => @$row_jrer_internal_selling['internal_selling'],
													 'internal_selling_type' => ((trim($brach_department)!=trim($job_branch_department)) ? 'text' : 'hidden' ),
													 //'fleet_field_readonly' => (($p_share['cf_1479_department_id']=='85844') ? 'readonly="readonly"' : '')
													 'fleet_field_readonly' => (($p_share['cf_1479_department_id']=='85844') ? '' : '')
													 );

						$sum_of_cost += $cost;
						$sum_of_external_selling +=$external_selling;
						$sum_of_job_profit +=$job_profit;
						$sum_of_internal_selling +=@$row_jrer_internal_selling['internal_selling'];
						$sum_of_profit_share +=$profit_share_col;
						$sum_of_net_profit +=$net_profit;

					 }
				 }
			 }


			 $profit_data = '';
			 $i=0;
			 foreach($profit_share_data as $key => $PROFIT)
				 {
					$i++;
					$profit_data .= '<tr>
					<td valign="top" width="19">'.$i.'</td>
					<td valign="top" width="100">'.$PROFIT['brach_department'].'</td>
					<td valign="top" width="100" align="right">'.$PROFIT['cost'].'</td>
					<td valign="top" width="150" align="right">'.$PROFIT['external_selling'].'</td>
					<td valign="top" width="100" align="right">'.$PROFIT['job_profit'].'</td>
					<td valign="top" width="150" align="right" >'.$PROFIT['internal_selling'].' </td>
					<td valign="top" width="150" align="right" >'.$PROFIT['profit_share_col'].'</td>
					<td valign="top" width="100" align="right">'.$PROFIT['net_profit'].'</td>
					</tr>';
				 }

			 $this->setValue('PROFIT_TABLE' ,$profit_data);
			 $this->setValue('SUM_OF_COST' , number_format($sum_of_cost , 2 ,  "." , "," ));
			 $this->setValue('SUM_OF_EXTERNAL_SELLING' , number_format($sum_of_external_selling , 2 ,  "." , "," ));
			 $this->setValue('SUM_OF_JOB_PROFIT' , number_format($sum_of_job_profit , 2 ,  "." , "," ));
			 $this->setValue('SUM_OF_INTERNAL_SELLING' , number_format($sum_of_internal_selling , 2 ,  "." , "," ));
			 $this->setValue('SUM_OF_PROFIT_SHARE' , number_format($sum_of_profit_share , 2 ,  "." , "," ));
			 $this->setValue('SUM_OF_NET_PROFIT' , (($job_info_detail->get('assigned_user_id')==$current_user->getId() || $current_user->get('roleid')=='H3'  || $count_parent_role <= 3 || $current_user->get('roleid')=='H2')? number_format($sum_of_net_profit , 2 ,  "." , "," ) : ''));
			 $this->setValue('NET_PROFIT_LABEL' , (($job_info_detail->get('assigned_user_id')==$current_user->getId() || $current_user->get('roleid')=='H3'  || $count_parent_role <= 3 || $current_user->get('roleid')=='H2') ? 'Net profit' : ''));
			 $this->setValue('PROFIT_SHARE_LABEL' , (($job_info_detail->get('assigned_user_id')==$current_user->getId() || $current_user->get('roleid')=='H3'  || $count_parent_role <= 3 || $current_user->get('roleid')=='H2') ? 'Profit Share Received' : 'Profit Share'));



		include('include/mpdf60/mpdf.php');
		 @date_default_timezone_set($current_user->get('time_zone'));
		$mpdf = new mPDF('utf-8', 'A4-L', '10', '', 10, 10, 30, 15, 10, 5); /*задаем формат, отступы и.т.д.*/
  		$mpdf->charset_in = 'utf8';

		$mpdf->list_indent_first_level = 0;

		//$mpdf->SetDefaultFontSize(12);
		//$mpdf->setAutoTopMargin(2);
		$mpdf->SetHTMLHeader('<table width="100%" cellpadding="0" cellspacing="0">
			<tr><td align="right" style="font-size:9;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">JCR Form, GLOBALINK, designed: March, 2010</td></tr>
			<tr><td align="right"><img src="include/calendar_logo.jpg"/></td></tr></table>');
		$mpdf->SetHTMLFooter('<table width="100%" cellpadding="0" cellspacing="0">
			<tr><td width="40%" align="left" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">Printed: '.date('d.m.Y; H:i').' by '.$current_user->get('user_name').'</td>
			<td width="20%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">Page {PAGENO} of {nbpg}</td>
			<td width="40%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">&nbsp;</td>
			</table>');
		$stylesheet = file_get_contents('include/mpdf60/examples/mpdfstyletables.css');
		$mpdf->WriteHTML($stylesheet,1);	// The parameter 1 tells that this is css/style only and no body/html/text
		$mpdf->WriteHTML($this->_documentXML); /*формируем pdf*/


		$pdf_name = 'pdf_docs/jcr_'.$job_id.'.pdf';

		$mpdf->Output($pdf_name, 'F');
		//header('Location:http://mb.globalink.net/vt60/'.$pdf_name);
		header('Location:'.$pdf_name);
		exit;

	}

	public function print_job_invoice($request){
		include('include/Exchangerate/exchange_rate_class.php');
		global $adb;
		//$adb->setDebug(true);
		$moduleName = $request->getModule();
		$record = $request->get('record');
		$jobid = $request->get('jobid');

		$invoice_instruction_no = $request->get('invoice_instruction_no');
		$bill_to = $request->get('bill_to');
		$bill_to_id = $request->get('bill_to');

		$JobexpencereportId = $record;
		$current_user = Users_Record_Model::getCurrentUserModel();

		$job_info_detail = Vtiger_Record_Model::getInstanceById($jobid, 'Job');
		$CompnId = $job_info_detail->get('cf_1186');
    $ClientId = $job_info_detail->get('cf_1441');
    $Client_Account_detail = Vtiger_Record_Model::getInstanceById($ClientId, 'Accounts');
    $client_network_name = $Client_Account_detail->get('cf_6250');



		$job_reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info_detail->get('cf_1186'));
		$file_title_currency = $job_reporting_currency;

		$job_expense_info_detail = Vtiger_Record_Model::getInstanceById($JobexpencereportId, 'Jobexpencereport');
     $jobexpense_query = "SELECT *
    FROM vtiger_jobexpencereportcf
    INNER JOIN vtiger_jobexpencereport ON vtiger_jobexpencereportcf.jobexpencereportid =  vtiger_jobexpencereport.jobexpencereportid
    WHERE  vtiger_jobexpencereport.invoice_instruction_no = '$invoice_instruction_no' AND vtiger_jobexpencereport.job_id = '$jobid' AND vtiger_jobexpencereportcf.cf_7914!=''";
    $jobexpense_query_result =  $adb->pquery($jobexpense_query);

    $agreement_id = $adb->query_result($jobexpense_query_result,0, 'cf_7914');
    $agreement_invoice_date = $adb->query_result($jobexpense_query_result,0, 'cf_1355');
    if($agreement_id){
      $agreement_info_detail = Vtiger_Record_Model::getInstanceById($agreement_id, 'ServiceAgreement');
      $client_agreement_number = $agreement_info_detail->get('name');
      $client_agreement_date = $agreement_info_detail->get('cf_6018');
    }else{
      $client_agreement_number = "";
      $client_agreement_date = "";
    }

    //// check the agreement status (previous or current)///
    $agreement_checker = '';
    $DepIds = $job_info_detail->get('cf_1190');
    $file_title = $job_info_detail->get('cf_1186');
    $department_info_detail = Vtiger_Record_Model::getInstanceById($DepIds, 'Department');
    $Dep_code = $department_info_detail->get('cf_1542');

    if($Dep_code=='CTD')
    {
    $agreement = "Customs Brokerage";
    }else{
    $agreement = "Freight Forwarding";
    }
     $query = "SELECT *
   FROM vtiger_serviceagreementcf
   INNER JOIN vtiger_serviceagreement ON vtiger_serviceagreementcf.serviceagreementid =  vtiger_serviceagreement.serviceagreementid
   INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid =  vtiger_serviceagreementcf.serviceagreementid
   WHERE ('$agreement_invoice_date' <= vtiger_serviceagreementcf.cf_6020)
   AND  vtiger_crmentity.deleted = 0
   AND vtiger_serviceagreementcf.cf_6094 = '$ClientId'
   AND vtiger_serviceagreementcf.cf_6068='$agreement'
   AND vtiger_serviceagreementcf.cf_6026='$file_title'";
   $query_rate =  $adb->pquery($query);

   if($adb->num_rows($query_rate)>0){

   for($sa=0;$sa<$adb->num_rows($query_rate);$sa++){
        $agreement_original_id = $adb->query_result($query_rate, $sa, 'serviceagreementid');
      //echo $agreement_id.'!='.$agreement_original_id."<br>";
     if($agreement_id == $agreement_original_id){
        $agreement_checker = 'yes';
     }
   }
 }
 //echo $agreement_checker;exit;
   /// //// check the agreement status (previous or current) code end here////

		$DepId = $job_expense_info_detail->get('cf_1479');

		$document = $this->loadTemplate('printtemplates/Job/invoice_instruction.html');
		//$stationbranch = $row_company['name'];
		$file_title_id = $job_info_detail->get('cf_1186');
		$glk_company_info_detail = Vtiger_Record_Model::getInstanceById($file_title_id, 'Company');


		if($file_title_id=='85758')
		{
			$this->setValue('companylogo', 'kl_logo.jpg');
		}
		else{
			$this->setValue('companylogo', 'logo_doc.jpg');
		}

		$this->setValue('stationbranch',htmlentities($glk_company_info_detail->get('name'), ENT_QUOTES, "UTF-8"));
		$this->setValue('mode_transport_info', str_replace(' |##| ',', ',$job_info_detail->get('cf_1711')));
		$this->setValue('reference_no',$job_info_detail->get('cf_1198'));
		$this->setValue('noofpcs',$job_info_detail->get('cf_1429'));
		$this->setValue('weight',$job_info_detail->get('cf_1084'));
		$this->setValue('weightstandard',$job_info_detail->get('cf_1520'));
		$this->setValue('volume',$job_info_detail->get('cf_1086'));
		$this->setValue('volumestandard',$job_info_detail->get('cf_1522'));
		$this->setValue('cw_kg',$job_info_detail->get('cf_4945').'KG');
		$this->setValue('awb_no',$job_info_detail->get('cf_1096'));
		$this->setValue('hawb_no',$job_info_detail->get('cf_2387'));
		$this->setValue('commodity',$job_info_detail->get('cf_1518'));
		$this->setValue('job_agreement_no',$client_agreement_number);
    if($agreement_checker!='yes'){
    $this->setValue('job_agreement_expire','(expired)');
  }else{
    $this->setValue('job_agreement_expire','');
  }
    $this->setValue('network',$client_network_name);
		$this->setValue('agreement_date',$client_agreement_date);
		$this->setValue('consignee',$job_expense_info_detail->get('cf_1361'));
		$this->setValue('consigneeaddress',$job_expense_info_detail->get('cf_1363'));
		$this->setValue('bill_to_address',$job_expense_info_detail->get('cf_1359'));

		$d_cr_date = DateTime::createFromFormat('Y-m-d', $job_expense_info_detail->get('cf_1355'));
		$dateadded = date_format($d_cr_date, 'd.m.Y');
		$this->setValue('dateadded',$dateadded);
		$this->setValue('selling_dep',htmlentities($job_expense_info_detail->getDisplayValue('cf_1479'), ENT_QUOTES, "UTF-8"));

		$owner_expense_user_info = Users_Record_Model::getInstanceById($job_expense_info_detail->get('assigned_user_id'), 'Users');

		$selling_from = $owner_expense_user_info->get('first_name').' '.$owner_expense_user_info->get('last_name');
		$this->setValue('selling_from',htmlentities($selling_from, ENT_QUOTES, "UTF-8"));
		$creator_name = $selling_from ;

			$bill_to = '';
			$bill_to = $job_expense_info_detail->get('cf_1445');
			$c_account_type ='';
			$BankDetails ='';
			$payment_terms='';
			if(!empty($bill_to))
			{
				$billtoinfo = Vtiger_Record_Model::getInstanceById($bill_to, 'Accounts');
				$c_account_type = @$billtoinfo->get('accounttype');

				$BankDetails = @$billtoinfo->get('cf_1833').'  '.@$billtoinfo->get('cf_1835').' '.@$billtoinfo->get('cf_1837').' '.@$billtoinfo->get('cf_1841').' '.@$billtoinfo->get('cf_1845').''.@$billtoinfo->get('cf_1849').' BIC:'.@$billtoinfo->get('cf_2397');
				$bill_to = @$billtoinfo->get('cf_2395');

				$payment_terms = $billtoinfo->get('cf_1855');
			}
			$this->setValue('c_account_type',htmlentities($c_account_type, ENT_QUOTES, "UTF-8"));
			$this->setValue('BankDetails',htmlentities($BankDetails, ENT_QUOTES, "UTF-8"));
			$this->setValue('bill_to',$bill_to);
			$this->setValue('payment_terms',$payment_terms);

			$agreement_where = " AND vtiger_serviceagreementcf.cf_6068='Freight Forwarding' ";
			if($DepId=='85837')
			{
				$agreement_where = " AND vtiger_serviceagreementcf.cf_6068='Customs Brokerage' ";
			}

			$job_agreement_no = '';
			$service_agreement_sql = 	$adb->pquery("SELECT vtiger_serviceagreement.name as agreement_no, vtiger_serviceagreementcf.cf_6018 as agreement_date, vtiger_serviceagreementcf.cf_6020 as expiry_date, vtiger_serviceagreementcf.cf_6026 as globalink_company FROM vtiger_serviceagreement
													INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_serviceagreement.serviceagreementid
													INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid
													INNER JOIN vtiger_serviceagreementcf ON vtiger_serviceagreementcf.serviceagreementid = vtiger_serviceagreement.serviceagreementid
													WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid='".$job_expense_info_detail->get('cf_1445')."'
													AND crmentityrel.module='Accounts'
													AND crmentityrel.relmodule='ServiceAgreement'  AND  vtiger_serviceagreementcf.cf_6026 ='".$job_info_detail->get('cf_1186')."'
													AND  vtiger_serviceagreementcf.cf_6066='".$c_account_type."'  ".$agreement_where."
													AND '".$job_expense_info_detail->get('cf_1355')."' between vtiger_serviceagreementcf.cf_6018 AND vtiger_serviceagreementcf.cf_6020
													order by vtiger_crmentity.createdtime DESC limit 1");
			$r_service_agreement = $adb->fetch_array($service_agreement_sql);
			$job_agreement_no = $r_service_agreement['agreement_no'];
			$d_cr_date_service_date = '';
			if(!empty($r_service_agreement['agreement_date'])){
				$d_cr_date_service_date = date('d.m.Y', strtotime($r_service_agreement['agreement_date']));
			}
			$agreement_date = $d_cr_date_service_date;

			$this->setValue('job_agreement_no',htmlentities($job_agreement_no, ENT_QUOTES, "UTF-8"));
			$this->setValue('agreement_date',$agreement_date);

			//ORDER BY `jrercf`.`cf_1359` DESC
			$jrer_selling = "SELECT *
								FROM `vtiger_jobexpencereportcf` as jrercf
								INNER JOIN `vtiger_jobexpencereport` ON `vtiger_jobexpencereport`.jobexpencereportid=jrercf.jobexpencereportid
								INNER JOIN vtiger_crmentityrel as crmentityrel ON jrercf.jobexpencereportid= crmentityrel.relcrmid
								INNER JOIN vtiger_crmentity as crmentity ON crmentityrel.relcrmid= crmentity.crmid
								where crmentity.deleted=0 AND crmentityrel.crmid='".$jobid."' and crmentityrel.module='Job'
								AND crmentityrel.relmodule='Jobexpencereport' and jrercf.cf_1457='Selling'
								AND vtiger_jobexpencereport.invoice_instruction_no='".$invoice_instruction_no."'
								ORDER BY `crmentity`.`createdtime` ASC";
			$selling_query = $adb->pquery($jrer_selling);

			$jrer_selling_remarks = '';
			$sell_customer_currency_gross = '';
			$total_gross = 0;
			$without_vat = 0;
			$vat_rate = 0;
			$invoice_currency = '';
			$invoice_arr = array();
			while($row_selling = $adb->fetch_array($selling_query))
			{
				$selling_currency = $adb->pquery('SELECT * from vtiger_currency_info WHERE id="'.$row_selling['cf_1234'].'"');
				$row_currency = $adb->fetch_array($selling_currency);
				if($row_selling['cf_1357'] != 0)
				{
					$invoice_arr[] = array('selling_customer_currency_net' => $row_selling['cf_1357'],
									'description' => $row_selling['cf_1365'],
									'customer_currency' => $row_currency['currency_code'],
									'sell_customer_currency_gross' => $row_currency['currency_code']." ".number_format($row_selling['cf_1232'],2,'.',','),
									'vat' => $row_selling['cf_1230'] );

					$invoice_currency = $row_currency['currency_code'];
					$without_vat +=$row_selling['cf_1357'];
					$vat_rate +=$row_selling['cf_1230'];
					$total_gross += $row_selling['cf_1232'];
				}
			}

			$invoice_html = '';
			foreach($invoice_arr as $key => $invoice)
			{
				$invoice_html .=' <tr>
								<td width="345" valign="top"><p>'.$invoice['description'].'</p></td>
								<td width="98" valign="top"><p>'.number_format($invoice['selling_customer_currency_net'],2,'.',',').'</p></td>
								<td width="71" valign="top"><p>'.$invoice['vat'].'</p></td>
								<td width="127" valign="top"><p>'.$invoice['sell_customer_currency_gross'].'</p></td>
								</tr>';
			}
			$this->setValue('invoice_html',$invoice_html);
			$this->setValue('without_vat',number_format($without_vat,2,'.',','));
			$this->setValue('vat_rate',$vat_rate);
			$this->setValue('invoice_currency',$invoice_currency);
			$this->setValue('total_gross',number_format($total_gross,2,'.',','));

			 //For additional remarks
			$jrer_selling_remarks = "SELECT *
									FROM `vtiger_jobexpencereportcf` as jrercf
									INNER JOIN `vtiger_jobexpencereport` ON `vtiger_jobexpencereport`.jobexpencereportid=jrercf.jobexpencereportid
									INNER JOIN vtiger_crmentityrel as crmentityrel ON jrercf.jobexpencereportid= crmentityrel.relcrmid
									INNER JOIN vtiger_crmentity as crmentity ON crmentityrel.relcrmid= crmentity.crmid
									where crmentity.deleted=0 AND crmentityrel.crmid='".$jobid."' AND crmentityrel.module='Job'
									AND crmentityrel.relmodule='Jobexpencereport' AND jrercf.cf_1457='Selling'
									AND vtiger_jobexpencereport.invoice_instruction_no='".$invoice_instruction_no."'
									ORDER BY `jrercf`.`cf_2691` DESC  limit 1";
			$selling_query_remarks = $adb->pquery($jrer_selling_remarks);
			$row_selling_remark = $adb->fetch_array($selling_query_remarks);
			$selling_additional_remark = $row_selling_remark['cf_2691'];

			$this->setValue('selling_additional_remark',$selling_additional_remark);

			$invoice_title = "INVOICING INSTRUCTIONS/DETAILS";
			if($row_selling_single['cf_4091']==1)
			{
				$invoice_title = "Credit Note INSTRUCTIONS/DETAILS";
			}
			$this->setValue('invoice_title',htmlentities($invoice_title, ENT_QUOTES, "UTF-8"));

			include('include/mpdf60/mpdf.php');
			@date_default_timezone_set($current_user->get('time_zone'));
			$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10); /*задаем формат, отступы и.т.д.*/
			$mpdf->charset_in = 'utf8';

			$mpdf->list_indent_first_level = 0;

			//$mpdf->SetDefaultFontSize(12);
			//$mpdf->setAutoTopMargin(2);
			$mpdf->SetHTMLFooter('<table width="100%" cellpadding="0" cellspacing="0">
			<tr><td width="40%" align="left" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">Printed: '.date('d.m.Y; H:i').' by '.$creator_name.'</td>
			<td width="20%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">Page {PAGENO} of {nbpg}</td>
			<td width="40%" align="right" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;"></td>
			</table>');
			$stylesheet = file_get_contents('include/mpdf60/examples/mpdfstyletables.css');
			$mpdf->WriteHTML($stylesheet,1);	// The parameter 1 tells that this is css/style only and no body/html/text
			$mpdf->WriteHTML($this->_documentXML); /*формируем pdf*/

			//$mpdf->Output('pdf_docs/invoice for '.$job_id.'.pdf', 'D');
			$pdf_name = 'pdf_docs/invoice for '.$jobid.'.pdf';
			ob_clean();
			$mpdf->Output($pdf_name, 'F');
			//header('Location:http://mb.globalink.net/vt60/'.$pdf_name);
			header('Location:'.$pdf_name);
			exit;

	}

	public function print_job_pv($request)
	{
		include('include/Exchangerate/exchange_rate_class.php');
		global $adb;
		//$adb->setDebug(true);
		$moduleName = $request->getModule();
		$record = $request->get('record');

		$jobid = $request->get('jobid');

		$JobexpencereportId = $record;
		$current_user = Users_Record_Model::getCurrentUserModel();

		$job_info_detail = Vtiger_Record_Model::getInstanceById($jobid, 'Job');
		$CompnId = $job_info_detail->get('cf_1186');
		$job_smcreatorid = $job_info_detail->get('assigned_user_id');
		$job_location_id = $job_info_detail->get('cf_1188');

		$glk_company_info_detail = Vtiger_Record_Model::getInstanceById($CompnId, 'Company');
		$CompName = $glk_company_info_detail->get('name');

		$job_expense_info_detail = Vtiger_Record_Model::getInstanceById($JobexpencereportId, 'Jobexpencereport');
		$pv_AccType = $job_expense_info_detail->getDisplayValue('cf_1214');
		$LocId =  $job_expense_info_detail->get('cf_1477');
		$smcreatorid = $job_expense_info_detail->get('assigned_user_id');

		if($job_smcreatorid!=$smcreatorid)
		{
			if($LocId!=$job_location_id)
			{
				$row_jobtask = $adb->pquery('SELECT * FROM vtiger_jobtask where user_id="'.$smcreatorid.'" and job_id="'.$jobid.'" ');
				$row_jobtask_info = $adb->fetch_array($row_jobtask);
				$CompnId = $row_jobtask_info['sub_jrer_file_title'];

				//22.10.2020:Mehtab:After getting message: the record you are trying to access is not found.
				if(!empty($CompnId))
				{
					$glk_company_info_detail = Vtiger_Record_Model::getInstanceById($CompnId, 'Company');
					$CompName = $glk_company_info_detail->get('name');
				}
			}
		}

		$job_reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info_detail->get('cf_1186'));
		$file_title_currency = $job_reporting_currency;

		$pv_file_invCurrency = $job_reporting_currency;
		$pv_file_invCurrency_JER = $pv_file_invCurrency;
		$expected_file_cur = $job_reporting_currency;

		$eng_company = array('85759', '85763');
		$eng_location = array('85832', '85947', '85820', '85808', '85948');


			$pdf_name = 'bank_r.pdf';
			if (($pv_AccType == 'Bank N') or ($pv_AccType == 'Bank D') )
			{
				$document = $this->loadTemplate('printtemplates/Job/bank_d_expense.html');
				$pdf_name = 'bank_d.pdf';
			}
			else if ($pv_AccType == 'Bank R' || $pv_AccType == 'Bank OR' || $pv_AccType == 'Bank RK') {
				if(in_array($CompnId,$eng_company) || in_array($LocId, $eng_location))
				{
					$document = $this->loadTemplate('printtemplates/Job/bank_r_expense_en.html');
					$pdf_name = 'bank_r.pdf';
				}
				else{

					if(!empty($CompnId))
					{
						$glk_company_info_detail_ru = Vtiger_Record_Model::getInstanceById($CompnId, 'Company');
						$CompName_ru = $glk_company_info_detail_ru->get('cf_7902');
						if(!empty($CompName_ru))
						{
							$CompName = $CompName_ru;
						}
						else{
							$CompName ="ТОО «Глобалинк Транспортейшн энд Лоджистикс Ворлдвайд»";
						}
					}

					$document = $this->loadTemplate('printtemplates/Job/bank_r_expense_ru.html');
					$pdf_name = 'bank_r.pdf';
				}
			}
			else if (($pv_AccType == 'Cash N') or ($pv_AccType == 'Cash D') ) {
				$document = $this->loadTemplate('printtemplates/Job/cash_n_d_expense.html');
				$pdf_name = 'cash_n_d.pdf';
			}
			else if ($pv_AccType == 'Cash R' || $pv_AccType == 'Cash OR' || $pv_AccType == 'Cash RK') {
				if(in_array($CompnId,$eng_company) || in_array($LocId, $eng_location) )
				{
					$document = $this->loadTemplate('printtemplates/Job/cash_r_expense_en.html');
					$pdf_name = 'cash_r.pdf';
				}
				else{
					$document = $this->loadTemplate('printtemplates/Job/cash_r_expense_ru.html');
					$pdf_name = 'cash_r.pdf';

				}
			}else if ($pv_AccType == 'Bank K') {
				$document = $this->loadTemplate('printtemplates/Job/bank_k_expense.html');
				$pdf_name = 'bank_k.pdf';

			}else if ($pv_AccType == 'Cash K') {
				$document = $this->loadTemplate('printtemplates/Job/cash_k_expense.html');
				$pdf_name = 'cash_k.pdf';
			}

			$owner_expense_user_info = Users_Record_Model::getInstanceById($job_expense_info_detail->get('assigned_user_id'), 'Users');

			$creator_name = $owner_expense_user_info->get('first_name').' '.$owner_expense_user_info->get('last_name');
			$this->setValue('pv_date',date('d.m.Y'));
			$this->setValue('creator_name', htmlentities($owner_expense_user_info->get('first_name').' '.$owner_expense_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));
			$this->setValue('pv_office',$job_expense_info_detail->getDisplayValue('cf_1477'));
			$this->setValue('pv_dep',$job_expense_info_detail->getDisplayValue('cf_1479'));

			$pv_AccTypeId = $job_expense_info_detail->get('cf_1214');
			$file_title_company_id =  $job_expense_info_detail->get('cf_2191');
			$expense_created_time =  $job_expense_info_detail->get('createdtime');
			$expense_created_date = date('Y-m-d',strtotime($expense_created_time));


			$pay_to_id = $job_expense_info_detail->get('cf_1367');
			$company_accountname = '';
			$pv_companyDate = '';
			$pv_companyNumber = '';
			$pv_accStatus = '';
			$pv_companyAssociation = ' ';
			$pv_companyAssociationID = '';
			$payment_terms = '';
			if(!empty($pay_to_id))
			{
				$paytoinfo = Vtiger_Record_Model::getInstanceById($pay_to_id, 'Accounts');
				$company_accountname = @$paytoinfo->get('cf_2395');

				$companydate = @$paytoinfo->get('cf_1859');
				if(!empty($companydate))
				{
				$d_cr_date = DateTime::createFromFormat('Y-m-d', $paytoinfo->get('cf_1859'));
				$pv_companyDate = date_format($d_cr_date, 'd.m.Y');
				}

				//$DepId = $r['cf_1479'];
				$DepId = $job_expense_info_detail->get('cf_1479');
				$c_account_type = $paytoinfo->get('accounttype');
				$agreement_where = " AND vtiger_serviceagreementcf.cf_6068='Freight Forwarding' ";
					if($DepId=='85837')
					{
						$agreement_where = " AND vtiger_serviceagreementcf.cf_6068='Customs Brokerage' ";
					}

					if($c_account_type =='Agent' || $c_account_type =='Vendor')
					{
						$c_account_type = "'Agent','Vendor'";
					}
					else{
						$c_account_type = "'Customer'";
					}

					$service_agreement_sql = 	$adb->pquery("SELECT vtiger_serviceagreement.name as agreement_no, vtiger_serviceagreementcf.cf_6018 as agreement_date, vtiger_serviceagreementcf.cf_6020 as expiry_date, vtiger_serviceagreementcf.cf_6026 as globalink_company, vtiger_serviceagreementcf.cf_6028 as company_account_type FROM vtiger_serviceagreement
					INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_serviceagreement.serviceagreementid
					INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid
					 INNER JOIN vtiger_serviceagreementcf ON vtiger_serviceagreementcf.serviceagreementid = vtiger_serviceagreement.serviceagreementid
					WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid='".$pay_to_id."' AND crmentityrel.module='Accounts'
					AND crmentityrel.relmodule='ServiceAgreement'  AND  vtiger_serviceagreementcf.cf_6026 ='".$file_title_company_id."'
					AND vtiger_serviceagreementcf.cf_6028 ='".$pv_AccTypeId."'
					AND  vtiger_serviceagreementcf.cf_6066 IN(".$c_account_type.")  ".$agreement_where."
					AND '".$expense_created_date."' between vtiger_serviceagreementcf.cf_6018 AND vtiger_serviceagreementcf.cf_6020
					order by vtiger_crmentity.createdtime DESC limit 1");
					$r_service_agreement = $adb->fetch_array($service_agreement_sql);
					$pv_companyNumber = $r_service_agreement['agreement_no'];
					$d_cr_date_service_date = '';
					if(!empty($r_service_agreement['agreement_date'])){
					$d_cr_date_service_date = date('d.m.Y', strtotime($r_service_agreement['agreement_date']));
					}
					$pv_companyDate = $d_cr_date_service_date;
				//$pv_companyNumber = $paytoinfo->get('cf_1857');


				$pv_accStatus = $paytoinfo->get('cf_2403');

				$pv_companyAssociation = $paytoinfo->get('cf_6250');
				$pv_companyAssociationID = $paytoinfo->get('cf_1829');

				$payment_terms = $paytoinfo->get('cf_1855');


			}
			$this->setValue('payment_terms',htmlentities($payment_terms, ENT_QUOTES, "UTF-8"));
			$this->setValue('pv_companyNumber',htmlentities($pv_companyNumber, ENT_QUOTES, "UTF-8"));
			$this->setValue('pv_companyname',html_entity_decode($company_accountname, ENT_QUOTES, "UTF-8"));
			$this->setValue('pv_companyDate',$pv_companyDate);
			//$this->setValue('acc_status',htmlentities($pv_accStatus, ENT_QUOTES, "UTF-8"));
			$this->setValue('pv_companyAssociation',htmlentities($pv_companyAssociation, ENT_QUOTES, "UTF-8"));
			$this->setValue('pv_companyAssociationID',htmlentities($pv_companyAssociationID, ENT_QUOTES, "UTF-8"));

			$this->setValue('pv_invNo',$job_expense_info_detail->get('cf_1212'));
			$d_cr_date = DateTime::createFromFormat('Y-m-d', $job_expense_info_detail->get('cf_1216'));
			$pv_invDate = date_format($d_cr_date, 'd.m.Y');
			$this->setValue('pv_invDate',$pv_invDate);

			$pv_invAmount =  number_format ( $job_expense_info_detail->get('cf_1343'), 2 ,  "." , "," );
			$this->setValue('pv_invAmount',$pv_invAmount);
			$this->setValue('pv_invCurrency',$job_expense_info_detail->getDisplayValue('cf_1345'));

			$ServId = $job_expense_info_detail->get('cf_1453');
			$service_description = $job_expense_info_detail->get('cf_1369');
			$pv_Service = $job_expense_info_detail->getDisplayValue('cf_1453');
			if(!empty($service_description))
			{
				$pv_Service .= ' ( '.$service_description.' )';
			}
			$this->setValue('pv_Service',htmlentities($pv_Service, ENT_QUOTES, "UTF-8"));
			$this->setValue('pv_JobRef',$job_info_detail->get('cf_1198'));
			//$this->setValue('pv_Client',$job_info_detail->getDisplayValue('cf_1441'));

			$client_id = $job_info_detail->get('cf_1441');
			$client_accountname = '';
			if(!empty($client_id))
			{
				$clientinfo = Vtiger_Record_Model::getInstanceById($client_id, 'Accounts');
				$client_accountname = @$clientinfo->get('cf_2395');
			}

			$this->setValue('pv_Client', html_entity_decode($client_accountname, ENT_QUOTES, "UTF-8"));

			//For GLK Invoice Number
			$invoice_query = $adb->pquery('select * from vtiger_jobexpencereport
										INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid
										where vtiger_jobexpencereport.job_id="'.$jobid.'" AND vtiger_jobexpencereport.owner_id = "'.$job_info_detail->get('assigned_user_id').'"
										AND vtiger_jobexpencereport.invoice_no !=""
										AND vtiger_jobexpencereportcf.cf_1457="Selling"
										GROUP BY vtiger_jobexpencereport.invoice_no
										ORDER BY vtiger_jobexpencereportcf.cf_1477, vtiger_jobexpencereportcf.cf_1479 ASC
										');
			$invoice_no = array();
			while($row = $adb->fetch_array($invoice_query))
			{
				$invoice_no[] =$row['invoice_no'];
			}
			$pv_GlkInvoice = implode(';', $invoice_no);
			$this->setValue('pv_GlkInvoice', htmlentities($pv_GlkInvoice, ENT_QUOTES, "UTF-8"));


			$jer_last_sql =  $adb->pquery("SELECT vtiger_crmentity.modifiedtime FROM `vtiger_jercf` as jercf
				 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = jercf.jerid
				 INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid
				 where vtiger_crmentity.deleted=0 AND crmentityrel.crmid='".$jobid."' AND crmentityrel.module='Job'
				 AND crmentityrel.relmodule='JER' order by vtiger_crmentity.modifiedtime DESC limit 1");
			$row_costing_last = $adb->fetch_array($jer_last_sql);
			$count_last_modified = $adb->num_rows($jer_last_sql);
			$exchange_rate_date  = date('Y-m-d');
			if($count_last_modified>0)
			{
				$modifiedtime = $row_costing_last['modifiedtime'];
				$modifiedtime = strtotime($row_costing_last['modifiedtime']);
				$exchange_rate_date = date('Y-m-d', $modifiedtime);
			}

			if($file_title_currency!='USD')
			{
				$final_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $exchange_rate_date);
			}else{
				$final_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $exchange_rate_date);
			}
			$Exchrate_JER = $final_exchange_rate;

			$job_costing_sum= $adb->pquery('select sum(j.cf_1160) as cost, sum(cf_1168) as revenue from vtiger_jercf j
											INNER JOIN vtiger_crmentityrel v on v.crmid = '.$jobid.'
											AND v.relmodule = "JER" and j.jerid = v.relcrmid');
			$job_costing_sum_row = $adb->fetch_array($job_costing_sum);

			if($pv_file_invCurrency_JER!='USD')
			{
				$pv_revenue = number_format($job_costing_sum_row['revenue']/$Exchrate_JER, 2);
				$pv_cost = number_format($job_costing_sum_row['cost']/$Exchrate_JER, 2);
			}
			else{
				$pv_revenue = number_format($job_costing_sum_row['revenue'], 2);
				$pv_cost = number_format($job_costing_sum_row['cost'], 2);
			}
			$this->setValue('pv_revenue',$pv_revenue);
			$this->setValue('pv_cost',$pv_cost);

			$jrer_invoice_sql =  $adb->pquery("SELECT vtiger_jobexpencereportcf.cf_1355, vtiger_jobexpencereportcf.jobexpencereportid FROM vtiger_jobexpencereportcf
						 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereportcf.jobexpencereportid
						 INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid
						 where vtiger_crmentity.deleted=0 AND crmentityrel.crmid='".$jobid."' AND crmentityrel.module='Job'
						 AND crmentityrel.relmodule='Jobexpencereport' AND vtiger_jobexpencereportcf.cf_1250 IN('Submitted', 'Approved')
						 order by vtiger_crmentity.modifiedtime DESC ");

			$pv_GlkInvoiceAmmount =0;
			$exchange_rate_date_JRER  = date('Y-m-d');
			$count_invoice =  $adb->num_rows($jrer_invoice_sql);
			while($row_jrer_invoice =  $adb->fetch_array($jrer_invoice_sql))
			{
					if($count_invoice>0)
					{
						$exchange_rate_date_JRER = $row_jrer_invoice['cf_1355'];
					}

					if($file_title_currency!='USD')
					{
						$final_exchange_rate_JRER = currency_rate_convert_kz($file_title_currency, 'USD',  1, $exchange_rate_date_JRER);
					}else{
						$final_exchange_rate_JRER = currency_rate_convert($file_title_currency, 'USD',  1, $exchange_rate_date_JRER);
					}
					$Exchrate_JRER = $final_exchange_rate_JRER;

					$sell_query = $adb->pquery('select sum(vtiger_jobexpencereportcf.cf_1242) as s_expected_sell_local_currency_net, sum(vtiger_jobexpencereportcf.cf_1238) as s_sell_local_currency_gross,  vtiger_jobexpencereportcf.cf_1250 as pstatus from vtiger_jobexpencereport
											INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid
											where vtiger_jobexpencereport.job_id="'.$jobid.'"
											AND vtiger_jobexpencereport.owner_id = "'.$job_info_detail->get('assigned_user_id').'"
											AND vtiger_jobexpencereportcf.cf_1457="Selling"
											AND vtiger_jobexpencereportcf.jobexpencereportid="'.$row_jrer_invoice['jobexpencereportid'].'" ');
					$row_sell =  $adb->fetch_array($sell_query);


					if($pv_file_invCurrency!='USD')
					{
						$sell_local_currency_gross= $row_sell['s_sell_local_currency_gross']/ $Exchrate_JRER;
					}else{
						$sell_local_currency_gross= $row_sell['s_sell_local_currency_gross'];
					}


				if($row_sell['pstatus'] == 'Approved' || $row_sell['pstatus']=='Submitted'){
					$pv_GlkInvoiceAmmount += $sell_local_currency_gross;
				}else{
					$pv_GlkInvoiceAmmount += 0;
				}


			}
			$pv_GlkInvoiceAmmount = number_format ( $pv_GlkInvoiceAmmount, 2 ,  "." , "," );
			$this->setValue('pv_GlkInvoiceAmmount',$pv_GlkInvoiceAmmount);

			//For Actual Expense AND Revenue
			//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
			$jrer_sql_expense =  "SELECT vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
			vtiger_jobexpencereportcf.cf_1216 as expense_invoice_date,
			vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
			FROM `vtiger_jobexpencereport`
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid
			INNER JOIN vtiger_crmentityrel ON
			(vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
			Left join vtiger_jobexpencereportcf as vtiger_jobexpencereportcf ON
			vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid
			WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid='".$jobid."' AND vtiger_crmentityrel.module='Job'
			AND vtiger_crmentityrel.relmodule='Jobexpencereport' AND vtiger_jobexpencereportcf.cf_1457='Expence'
			AND vtiger_jobexpencereport.owner_id = '".$job_info_detail->get('assigned_user_id')."' ";

			$result_expense = $adb->pquery($jrer_sql_expense);
			$numRows_expnese = $adb->num_rows($result_expense);

			$total_cost_in_usd_net = 0;
			if($numRows_expnese>0)
			{
				while($row_job_jrer_expense = $adb->fetch_array($result_expense))
				{
					$expense_invoice_date = $row_job_jrer_expense['expense_invoice_date'];
					$CurId = $row_job_jrer_expense['buy_currency_id'];
					if ($CurId) {
						$q_cur = $adb->pquery('select * from vtiger_currency_info where id = "'.$CurId.'"');
						$row_cur = $adb->fetch_array($q_cur);
						$Cur = $row_cur['currency_code'];
					}

					$b_exchange_rate = $final_exchange_rate;
					if(!empty($expense_invoice_date))
					{
						if($expected_file_cur!='USD')
						{
							$b_exchange_rate = currency_rate_convert_kz($expected_file_cur, 'USD',  1, $expense_invoice_date);
						}else{
							$b_exchange_rate = currency_rate_convert($expected_file_cur, 'USD',  1, $expense_invoice_date);
						}
					}


					if($expected_file_cur!='USD')
					{
						$total_cost_in_usd_net += $row_job_jrer_expense['buy_local_currency_net']/$b_exchange_rate;
					}
					else{
						$total_cost_in_usd_net += $row_job_jrer_expense['buy_local_currency_net'];
					}
				}
			}
			$actual_expense_cost_usd = number_format ( $total_cost_in_usd_net , 2 ,  "." , "," );
			$this->setValue('actual_expense_cost_usd',$actual_expense_cost_usd);


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
			WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid='".$jobid."' AND vtiger_crmentityrel.module='Job'
			AND vtiger_crmentityrel.relmodule='Jobexpencereport' AND vtiger_jobexpencereportcf.cf_1457='Selling'
			AND vtiger_jobexpencereport.owner_id = '".$job_info_detail->get('assigned_user_id')."' AND vtiger_jobexpencereportcf.cf_1250 IN('Submitted', 'Approved') ";

			$result_invoice = $adb->pquery($jrer_selling_sql_selling);
			$numRows_invoice = $adb->num_rows($result_invoice);
			$total_cost_in_usd_sell_net = 0;

			if($numRows_invoice>0)
			{
				while($row_job_jrer_invoice = $adb->fetch_array($result_invoice))
				{
					$sell_invoice_date = $row_job_jrer_invoice['sell_invoice_date'];
					$exchange_rate_date_invoice =$sell_invoice_date;

					$CurId = $row_job_jrer_invoice['currency_id'];
					if ($CurId) {
						$q_cur = $adb->pquery('select * from vtiger_currency_info where id = "'.$CurId.'"');
						$row_cur = $adb->fetch_array($q_cur);
						$Cur = $row_cur['currency_code'];
					}

					$s_exchange_rate = $final_exchange_rate;
					if(!empty($exchange_rate_date_invoice))
					{
						if($expected_file_cur!='USD')
						{
							$s_exchange_rate = currency_rate_convert_kz($expected_file_cur, 'USD',  1, $exchange_rate_date_invoice);
						}else{
							$s_exchange_rate = currency_rate_convert($expected_file_cur, 'USD',  1, $exchange_rate_date_invoice);
						}
					}


					$new_rate = $s_exchange_rate;
					if($expected_file_cur!='USD')
					{
						$total_cost_in_usd_sell_net += $row_job_jrer_invoice['sell_local_currency_net']/$s_exchange_rate;
					}
					else{
					$total_cost_in_usd_sell_net += $row_job_jrer_invoice['sell_local_currency_net'];
					}

				}
			}
			$actual_selling_cost_usd = number_format ( $total_cost_in_usd_sell_net , 2 ,  "." , "," );
			$this->setValue('actual_selling_cost_usd',$actual_selling_cost_usd);



			$fleet_inquiry_status = 'No Inquiry';
			$fleet_inquiry_status_ru = 'Нет запроса';
			$fi_rel= $adb->pquery('SELECT * FROM `vtiger_crmentityrel` where module ="Job" AND relmodule="FleetInquiry" and crmid="'.$jobid.'"');
			if($adb->num_rows($fi_rel) > 0)
			{
				$fi_row = $adb->fetch_array($fi_rel);
				$fleet_inquiry_id = $fi_row['relcrmid'];
				$fleet_inquiry_sql = $adb->pquery("SELECT * FROM vtiger_fleetinquirycf WHERE fleetinquiryid='".$fleet_inquiry_id."'");
				$fleet_inquiry_row = $adb->fetch_array($fleet_inquiry_sql);
				$fleet_status = $fleet_inquiry_row['cf_3295'];
				switch($fleet_status)
				{
					case 'Accepted':
					case 'Completed':
					$fleet_inquiry_status = 'Accepted';
					$fleet_inquiry_status_ru = 'Принято';
					break;
					case 'Rejected':
					$fleet_inquiry_status = 'Rejected';
					$fleet_inquiry_status_ru = 'Отклонено';
					break;
					case 'Pending':
					$fleet_inquiry_status = 'Pending';
					$fleet_inquiry_status_ru = 'В работе';
					break;
					default:
					$fleet_inquiry_status = 'No Inquiry';
					$fleet_inquiry_status_ru = 'Нет запроса';
				}
			}
			$this->setValue('fleet_inquiry_status',htmlentities($fleet_inquiry_status, ENT_QUOTES, "UTF-8"));
			$this->setValue('fleet_inquiry_status_ru',htmlentities($fleet_inquiry_status_ru, ENT_QUOTES, "UTF-8"));

			switch($pv_accStatus)
			{
				case 'Confirmed':
				$acc_status = 'Confirmed';
				$acc_status_ru = 'Предварительно согласовано';
				break;
				case 'Approved':
				$acc_status = 'Approved';
				$acc_status_ru = 'Утвержден';
				break;
				case 'Rejected':
				$acc_status = 'Rejected';
				$acc_status_ru = 'Отклонен';
				break;
				case 'Pending':
				$acc_status = 'Pending';
				$acc_status_ru = 'В процессе';
				break;
				case 'Blacklist':
				$acc_status = 'Blacklist';
				$acc_status_ru = 'В черном списке';
				break;
			}
			$this->setValue('acc_status',htmlentities($acc_status, ENT_QUOTES, "UTF-8"));
			$this->setValue('acc_status_ru',htmlentities($acc_status_ru, ENT_QUOTES, "UTF-8"));



			if($pv_AccType=='Bank D')
			{
				$CompName = 'Globalink Logistics DWC LLC';
			}
			//$this->setValue('CompName',htmlentities($CompName, ENT_QUOTES, "UTF-8"));
			$this->setValue('CompName',$CompName);

		include('include/mpdf60/mpdf.php');
		@date_default_timezone_set($current_user->get('time_zone'));
		$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10); /*задаем формат, отступы и.т.д.*/
  		$mpdf->charset_in = 'utf8';

		$mpdf->list_indent_first_level = 0;

		//$mpdf->SetDefaultFontSize(12);
		//$mpdf->setAutoTopMargin(2);
		$mpdf->SetHTMLFooter('<table width="100%" cellpadding="0" cellspacing="0">
		<tr><td width="40%" align="left" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">Printed: '.date('d.m.Y; H:i').' by '.$creator_name.'</td>
		<td width="20%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">Page {PAGENO} of {nbpg}</td>
		<td width="40%" align="right" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">PV #: '.$JobexpencereportId.'</td>
		</table>');
		$stylesheet = file_get_contents('include/mpdf60/examples/mpdfstyletables.css');
		$mpdf->WriteHTML($stylesheet,1);	// The parameter 1 tells that this is css/style only and no body/html/text
		$mpdf->WriteHTML($this->_documentXML); /*формируем pdf*/


		$pdf_name = 'pdf_docs/PV_'.$JobexpencereportId.'.pdf';

		$mpdf->Output($pdf_name, 'F');
		//header('Location:http://mb.globalink.net/vt60/'.$pdf_name);
		header('Location:'.$pdf_name);
		exit;


	}

	public function print_wagon_pv($request)
	{

		global $adb;
		$moduleName = $request->getModule();
		$record = $request->get('record');

		$wagontripid = $request->get('wagontripid');

		$JobexpencereportId = $record;
		$current_user = Users_Record_Model::getCurrentUserModel();

		$wagon_trip_info_detail = Vtiger_Record_Model::getInstanceById($wagontripid, 'WagonTrip');

		$wagon_info = Vtiger_Record_Model::getInstanceById($wagon_trip_info_detail->get('cf_5800'), 'Wagon');
		//$truck_location_id = $wagon_info->get('cf_1913');
		$wagon_location_id = '85805';

		$wagon_expense_info_detail = Vtiger_Record_Model::getInstanceById($JobexpencereportId, 'Jobexpencereport');


		$pv_AccType = $wagon_expense_info_detail->getDisplayValue('cf_1214');

		$pdf_name = 'bank_r.pdf';
		if($pv_AccType == 'Bank R')
		{
			$document = $this->loadTemplate('printtemplates/Wagon/bank_r_expense.html');
			$pdf_name = 'bank_r.pdf';
		}
		elseif(($pv_AccType == 'Cash N') or ($pv_AccType == 'Cash D')){
			$document = $this->loadTemplate('printtemplates/Wagon/cash_n_d_expense.html');
			$pdf_name = 'cash_n_d.pdf';
		}
		else if ($pv_AccType == 'Cash R'){
			$document = $this->loadTemplate('printtemplates/Wagon/cash_r_expense.html');
			$pdf_name = 'cash_r.pdf';
		}
		else if($pv_AccType == 'Bank D')
		{
			$document = $this->loadTemplate('printtemplates/Wagon/bank_d_expense.html');
			$pdf_name = 'bank_d.pdf';
		}

		$owner_expense_user_info = Users_Record_Model::getInstanceById($wagon_expense_info_detail->get('assigned_user_id'), 'Users');

		$this->setValue('pv_date',date('d.m.Y'));
		$this->setValue('creator_name', htmlentities($owner_expense_user_info->get('first_name').' '.$owner_expense_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));
		$this->setValue('pv_office',$wagon_expense_info_detail->getDisplayValue('cf_1477'));
		$this->setValue('pv_dep',$wagon_expense_info_detail->getDisplayValue('cf_1479'));

		$pay_to_id = $wagon_expense_info_detail->get('cf_1367');
		$company_accountname = '';
		$pv_companyDate = '';
		$pv_companyNumber = '';
		$pv_accStatus = '';
		if(!empty($pay_to_id))
		{
			$paytoinfo = Vtiger_Record_Model::getInstanceById($pay_to_id, 'Accounts');
			$company_accountname = @$paytoinfo->get('cf_2395');

			$companydate = @$paytoinfo->get('cf_1859');
			if(!empty($companydate))
			{
			$d_cr_date = DateTime::createFromFormat('Y-m-d', $paytoinfo->get('cf_1859'));
			$pv_companyDate = date_format($d_cr_date, 'd.m.Y');
			}

			$pv_companyNumber = $paytoinfo->get('cf_1857');
			$pv_accStatus = $paytoinfo->get('cf_2403');
		}
		$this->setValue('pv_companyNumber',htmlentities($pv_companyNumber, ENT_QUOTES, "UTF-8"));
		$this->setValue('pv_companyname',htmlentities($company_accountname, ENT_QUOTES, "UTF-8"));
		$this->setValue('pv_companyDate',$pv_companyDate);
		$this->setValue('acc_status',htmlentities($pv_accStatus, ENT_QUOTES, "UTF-8"));

		$this->setValue('pv_invNo',$wagon_expense_info_detail->get('cf_1212'));
		$d_cr_date = DateTime::createFromFormat('Y-m-d', $wagon_expense_info_detail->get('cf_1216'));
		$pv_invDate = date_format($d_cr_date, 'd.m.Y');
		$this->setValue('pv_invDate',$pv_invDate);

		$pv_invAmount =  number_format ( $wagon_expense_info_detail->get('cf_1343'), 2 ,  "." , "," );
		$this->setValue('pv_invAmount',$pv_invAmount);
		$this->setValue('pv_invCurrency',$wagon_expense_info_detail->getDisplayValue('cf_1345'));

		$ServId = $wagon_expense_info_detail->get('cf_1453');
		$service_description = $wagon_expense_info_detail->get('cf_1369');
		$pv_Service = $wagon_expense_info_detail->getDisplayValue('cf_1453');
		if(!empty($service_description))
		{
			$pv_Service = $service_description;
		}
		$this->setValue('pv_Service',htmlentities($pv_Service, ENT_QUOTES, "UTF-8"));
		$this->setValue('pv_WagonRef',$wagon_trip_info_detail->get('cf_5790'));


		//FOR RED
		//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
		$adb_railway_RED = PearDatabase::getInstance();
		$railway_fleet_sql_RED =  "SELECT sum(vtiger_railwayfleetcf.cf_5826) as total_internal_selling FROM `vtiger_railwayfleet`
							INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_railwayfleet.railwayfleetid
							INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
 							LEFT JOIN vtiger_railwayfleetcf as vtiger_railwayfleetcf on vtiger_railwayfleetcf.railwayfleetid=vtiger_railwayfleet.railwayfleetid
		 				    WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=?
								  AND vtiger_crmentityrel.module='WagonTrip' AND vtiger_crmentityrel.relmodule='RailwayFleet'
								  ";

		$params_fleet_railway_RED = array($wagontripid);
		$result_railway_RED = $adb_railway_RED->pquery($railway_fleet_sql_RED, $params_fleet_railway_RED);
		$total_internal_selling_RED = $adb_railway_RED->query_result($result_railway_RED, '0', 'total_internal_selling');

		include('include/Exchangerate/exchange_rate_class.php');
		$current_user = Users_Record_Model::getCurrentUserModel();
		$total_revenew = 0;
		$sum_of_cost = 0;
		$sum_of_job_profit = 0;
		$sum_of_internal_selling = 0;
		$profit_share_data = array();

		$pagingModel_1 = new Vtiger_Paging_Model();
		$pagingModel_1->set('page','1');

		$relatedModuleName_1 = 'RailwayFleet';
		$parentRecordModel_1 = $wagon_trip_info_detail;
		$relationListView_1 = Vtiger_RelationListView_Model::getInstance($parentRecordModel_1, $relatedModuleName_1, $label);
		$models_1 = $relationListView_1->getEntries($pagingModel_1);


		foreach($models_1 as $key => $model){

				$railway_fleet_id  = $model->getId();

				$round_trip_id = $model->getId();
				$sourceModule 		= 'RailwayFleet';
				$roundtrip_info = Vtiger_Record_Model::getInstanceById($round_trip_id, $sourceModule);

				$total_revenew +=$model->get('cf_5826');

				$job_id 			  = $model->get('cf_5820');

				if($job_id=='REDJOB')
				{
					$file_title_currency_RED ='KZT';
					$pagingModel_RED = new Vtiger_Paging_Model();
					$pagingModel_RED->set('page','1');

					$relatedModuleName_RED = 'Jobexpencereport';
					$parentRecordModel_RED = $wagon_trip_info_detail;
					$relationListView_RED = Vtiger_RelationListView_Model::getInstance($parentRecordModel_RED, $relatedModuleName_RED, $label);
					$models_RED = $relationListView_RED->getEntries($pagingModel_RED);

					foreach($models_RED as $key => $model_RED)
					{
						$expense_record_RED= $model_RED->getInstanceById($model_RED->getId());
						if($model_RED->getDisplayValue('cf_1457') == 'Selling'){
							continue;
						}
						$i++;

						$Cur = $expense_record_RED->getDisplayValue('cf_1345');
						//$invoice_date_RED = $expense_record_RED->get('cf_1216');
						$timestamp_RED = strtotime($model->getDisplayValue('cf_5818'));
						$invoice_date_RED = date('Y-m-d', $timestamp_RED);
						$b_exchange_rate_RED =1;
						if(!empty($invoice_date_RED))
						{
							if($file_title_currency_RED!='USD')
							{
								$b_exchange_rate_RED = currency_rate_convert_kz($file_title_currency_RED, 'USD',  1, $invoice_date_RED);
							}else{
								$b_exchange_rate_RED = currency_rate_convert($file_title_currency_RED, 'USD',  1, $invoice_date_RED);
							}
						}
						$value_in_usd_normal_RED = $expense_record_RED->getDisplayValue('cf_1349');
						if($file_title_currency_RED!='USD')
						{
							$value_in_usd_normal_RED = $expense_record_RED->getDisplayValue('cf_1349')/$b_exchange_rate_RED;
						}

						$value_in_usd_RED = number_format($value_in_usd_normal_RED, 2, '.', '');

						$total_usd_RED += $value_in_usd_RED;
					}

					$internal_selling_forpercent = $model->get('cf_5826');
					$percentage_per_job_RED = ($internal_selling_forpercent*100)/$total_internal_selling_RED;
					$invoice_cost_breakdown_RED = ($total_usd_RED*$percentage_per_job_RED)/100;

					$job_profit_RED = $internal_selling_forpercent - $invoice_cost_breakdown_RED;

					$profit_share_data[] = array('cost' => number_format ( $invoice_cost_breakdown_RED , 2 ,  "." , "," ),
												 'job_profit'  =>  number_format ( $job_profit_RED , 2 ,  "." , "," ),
												 'job_ref_no' => $model->get('cf_5884'),
												 'job_id' => '',
												 //'internal_selling' => @$row_jrer_internal_selling['internal_selling'],
												 'internal_selling' => $model->get('cf_5826'),
												// 'user_id' => $current_user->getId()
												'user_id' => $roundtrip_info->get('assigned_user_id')
												 );

					$sum_of_cost += $invoice_cost_breakdown_RED;
					$sum_of_job_profit += $job_profit_RED;
					//$sum_of_internal_selling +=@$row_jrer_internal_selling['internal_selling'];
					$sum_of_internal_selling +=$model->get('cf_5826');
					continue;
				}

				$sourceModule_job 	= 'Job';
				$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule_job);

				$job_reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info->get('cf_1186'));
				$file_title_currency = $job_reporting_currency;

				$adb_buy_local = PearDatabase::getInstance();
				//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
				$sum_buy_local_currency_net =  "SELECT vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
													   vtiger_jobexpencereportcf.cf_1216 as buy_invoice_date,
													   vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
												FROM `vtiger_jobexpencereport`
												INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid
												INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
												left join vtiger_jobexpencereportcf as vtiger_jobexpencereportcf on vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid
												where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? and vtiger_crmentityrel.module='Job'
												and vtiger_crmentityrel.relmodule='Jobexpencereport' and vtiger_jobexpencereportcf.cf_1457='Expence'
												and vtiger_jobexpencereportcf.cf_1477=? and vtiger_jobexpencereportcf.cf_1479=?
												AND vtiger_jobexpencereport.wagontrip_id=?
												AND vtiger_jobexpencereport.railwayfleet_id =?
												AND vtiger_jobexpencereport.owner_id=?
												";

				//$params_buy_local = array($model->get('cf_3175'), '85805', '85844', $fleettripid, $roundtrip_info->get('assigned_user_id'));
				//$params_buy_local = array($model->get('cf_3175'), $truck_location_id, '85844', $fleettripid, $roundtrip_info->get('assigned_user_id'));
				$params_buy_local = array($model->get('cf_5820'), $wagon_location_id, '1414775', $wagontripid, $railway_fleet_id, $roundtrip_info->get('assigned_user_id'));


				$result_buy_locall = $adb_buy_local->pquery($sum_buy_local_currency_net, $params_buy_local);
				$numRows_buy_profit = $adb_buy_local->num_rows($result_buy_locall);
				$cost = 0;
						for($jj=0; $jj< $adb_buy_local->num_rows($result_buy_locall); $jj++ ) {
							$row_jrer_buy_local_currency_net = $adb_buy_local->fetch_row($result_buy_locall,$jj);
							//$row_jrer_buy_local_currency_net = $adb_buy_local->fetch_array($result_buy_locall);

							$cost_local = @$row_jrer_buy_local_currency_net['buy_local_currency_net'];

							$buy_invoice_date = @$row_jrer_buy_local_currency_net['buy_invoice_date'];

							$CurId = $row_jrer_buy_local_currency_net['buy_currency_id'];
							if ($CurId) {
							  $q_cur = $adb->pquery('select * from vtiger_currency_info where id = "'.$CurId.'"');
							  $row_cur = $adb->fetch_array($q_cur);
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

						$adb_internal = PearDatabase::getInstance();
						//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
						$internal_selling_arr = "SELECT vtiger_jobexpcf.cf_1263 as internal_selling
												FROM vtiger_jobexp
												INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexp.jobexpid
 												INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
 												left join vtiger_jobexpcf as vtiger_jobexpcf on vtiger_jobexpcf.jobexpid=vtiger_jobexp.jobexpid
		 					  					where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? and vtiger_crmentityrel.module='Job'
												and vtiger_crmentityrel.relmodule='Jobexp' and vtiger_jobexpcf.cf_1257=? and vtiger_jobexpcf.cf_1259=?
												";

						//$params_internal = array($model->get('cf_3175'), '85805', '85844');
						//$params_internal = array($model->get('cf_3175'), $truck_location_id, '85844');
						$params_internal = array($model->get('cf_5820'), $wagon_location_id, '1414775');

						$result_internal = $adb_internal->pquery($internal_selling_arr, $params_internal);
						$row_jrer_internal_selling = $adb_internal->fetch_array($result_internal);

						//$job_profit = @$row_jrer_internal_selling['internal_selling'] - $cost;
						$job_profit = $model->get('cf_5826') - $cost;

						$profit_share_data[] = array('cost' => number_format ( $cost , 2 ,  "." , "," ),
													 'job_profit'  =>  number_format ( $job_profit , 2 ,  "." , "," ),
													 'job_ref_no' => $model->getDisplayValue('cf_5820'),
													 'job_id' => $model->get('cf_5820'),
												 	// 'internal_selling' => @$row_jrer_internal_selling['internal_selling'],
													 'internal_selling' =>  $model->get('cf_5826'),
													 //'user_id' => $current_user->getId()
													 'user_id' => $roundtrip_info->get('assigned_user_id')
													 );


						$sum_of_cost += $cost;
						$sum_of_job_profit +=$job_profit;
						//$sum_of_internal_selling +=@$row_jrer_internal_selling['internal_selling'];
						$sum_of_internal_selling +=$model->get('cf_5826');

			}

			$allocated_job_no_costing_breakdown = '';
			$invoice_amount = $wagon_expense_info_detail->get('cf_1343');
			$sum_invoice_cost_breakdown = 0;


			if(!empty($profit_share_data))
			{
				foreach($profit_share_data as $costing_breakdown)
				{

				$percentage_per_job = ($costing_breakdown['internal_selling']*100)/$sum_of_internal_selling;
				$invoice_cost_breakdown = ($invoice_amount*$percentage_per_job)/100;

				$sum_invoice_cost_breakdown+=$invoice_cost_breakdown;

				$allocated_job_no_costing_breakdown .= '<tr>
														<td width="216">
														'.$costing_breakdown['job_ref_no'].'
														</td>
														<td width="186">
														'.number_format ($costing_breakdown['internal_selling'] , 2 ,  "." , "," ).'
														</td>
														<td width="174">
														'.number_format ($costing_breakdown['cost'], 2 ,  "." , "," ).'
														</td>
														<td width="105">
														'.number_format ($costing_breakdown['job_profit'], 2 ,  "." , "," ).'
														</td>
														<td width="135">
														'.number_format ( $invoice_cost_breakdown , 2 ,  "." , "," ).'
														</td>
														</tr>';
				}

			}

		$this->setValue('allocated_job_no_costing_breakdown', $allocated_job_no_costing_breakdown);
		$this->setValue('total_internal_selling', number_format ( $sum_of_internal_selling , 2 ,  "." , "," ));
		$this->setValue('total_costing', number_format ( $sum_of_cost , 2 ,  "." , "," ));
		$this->setValue('total_profit', number_format ( $sum_of_job_profit , 2 ,  "." , "," ));
		$this->setValue('sum_invoice_cost_breakdown', number_format ( $sum_invoice_cost_breakdown , 2 ,  "." , "," ));



		include('include/mpdf60/mpdf.php');
		 @date_default_timezone_set($current_user->get('time_zone'));
  		 $mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10); /*задаем формат, отступы и.т.д.*/
  		$mpdf->charset_in = 'utf8';

		$mpdf->list_indent_first_level = 0;

		//$mpdf->SetDefaultFontSize(12);
		//$mpdf->setAutoTopMargin(2);
			$mpdf->SetHTMLFooter('<table width="100%" cellpadding="0" cellspacing="0">
				<tr><td width="40%" align="left" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">Printed: '.date('d.m.Y; H:i').' by '.@$current_user->get('user_name').'</td>
			<td width="20%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">Page {PAGENO} of {nbpg}</td>
			<td width="40%" align="right" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">PV #: '.$JobexpencereportId.'</td>
			</table>');

		$stylesheet = file_get_contents('include/Quote/pdf_styles.css');
		$mpdf->WriteHTML($stylesheet,1);	// The parameter 1 tells that this is css/style only and no body/html/text
		$mpdf->WriteHTML($this->_documentXML); /*формируем pdf*/


		$pdf_name_final = 'pdf_docs/pv_'.$JobexpencereportId.$pdf_name;

		$mpdf->Output($pdf_name_final, 'F');
		//header('Location:http://mb.globalink.net/vt60/'.$pdf_name);
		header('Location:'.$pdf_name_final);
		exit;


	}

	public function print_fleet_pv($request)
	{
		global $adb;
		$moduleName = $request->getModule();
		$record = $request->get('record');

		$fleettripid = $request->get('fleettripid');

		$JobexpencereportId = $record;
		$current_user = Users_Record_Model::getCurrentUserModel();

		$fleet_trip_info_detail = Vtiger_Record_Model::getInstanceById($fleettripid, 'Fleettrip');

		$truck_info = Vtiger_Record_Model::getInstanceById($fleet_trip_info_detail->get('cf_3165'), 'Truck');
		$truck_location_id = $truck_info->get('cf_1913');

		$fleet_expense_info_detail = Vtiger_Record_Model::getInstanceById($JobexpencereportId, 'Jobexpencereport');


		$pv_AccType = $fleet_expense_info_detail->getDisplayValue('cf_1214');

		$pdf_name = 'bank_r.pdf';
		if($pv_AccType == 'Bank R')
		{
			$document = $this->loadTemplate('printtemplates/bank_r_expense.html');
			$pdf_name = 'bank_r.pdf';
		}
		elseif(($pv_AccType == 'Cash N') or ($pv_AccType == 'Cash D')){
			$document = $this->loadTemplate('printtemplates/cash_n_d_expense.html');
			$pdf_name = 'cash_n_d.pdf';
		}
		else if ($pv_AccType == 'Cash R'){
			$document = $this->loadTemplate('printtemplates/cash_r_expense.html');
			$pdf_name = 'cash_r.pdf';
		}

		$owner_expense_user_info = Users_Record_Model::getInstanceById($fleet_expense_info_detail->get('assigned_user_id'), 'Users');

		$this->setValue('pv_date',date('d.m.Y'));
		$this->setValue('creator_name', htmlentities($owner_expense_user_info->get('first_name').' '.$owner_expense_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));
		$this->setValue('pv_office',$fleet_expense_info_detail->getDisplayValue('cf_1477'));
		$this->setValue('pv_dep',$fleet_expense_info_detail->getDisplayValue('cf_1479'));

		$pay_to_id = $fleet_expense_info_detail->get('cf_1367');
		$company_accountname = '';
		$pv_companyDate = '';
		$pv_companyNumber = '';
		if(!empty($pay_to_id))
		{
			$paytoinfo = Vtiger_Record_Model::getInstanceById($pay_to_id, 'Accounts');
			$company_accountname = @$paytoinfo->get('cf_2395');

			$companydate = @$paytoinfo->get('cf_1859');
			if(!empty($companydate))
			{
			$d_cr_date = DateTime::createFromFormat('Y-m-d', $paytoinfo->get('cf_1859'));
			$pv_companyDate = date_format($d_cr_date, 'd.m.Y');
			}

			$pv_companyNumber = $paytoinfo->get('cf_1857');
		}
		$this->setValue('pv_companyNumber',htmlentities($pv_companyNumber, ENT_QUOTES, "UTF-8"));
		$this->setValue('pv_companyname',htmlentities($company_accountname, ENT_QUOTES, "UTF-8"));
		$this->setValue('pv_companyDate',$pv_companyDate);


		$this->setValue('pv_invNo',$fleet_expense_info_detail->get('cf_1212'));
		$d_cr_date = DateTime::createFromFormat('Y-m-d', $fleet_expense_info_detail->get('cf_1216'));
		$pv_invDate = date_format($d_cr_date, 'd.m.Y');
		$this->setValue('pv_invDate',$pv_invDate);

		$pv_invAmount =  number_format ( $fleet_expense_info_detail->get('cf_1343'), 2 ,  "." , "," );
		$this->setValue('pv_invAmount',$pv_invAmount);
		$this->setValue('pv_invCurrency',$fleet_expense_info_detail->getDisplayValue('cf_1345'));

		$ServId = $fleet_expense_info_detail->get('cf_1453');
		$service_description = $fleet_expense_info_detail->get('cf_1369');
		$pv_Service = $fleet_expense_info_detail->getDisplayValue('cf_1453');
		if(!empty($service_description))
		{
			$pv_Service = $service_description;
		}
		$this->setValue('pv_Service',htmlentities($pv_Service, ENT_QUOTES, "UTF-8"));
		$this->setValue('pv_FleetRef',$fleet_trip_info_detail->get('cf_3283'));

		include('include/Exchangerate/exchange_rate_class.php');
		$current_user = Users_Record_Model::getCurrentUserModel();
		$total_revenew = 0;
		$sum_of_cost = 0;
		$sum_of_job_profit = 0;
		$sum_of_internal_selling = 0;
		$profit_share_data = array();

		$pagingModel_1 = new Vtiger_Paging_Model();
		$pagingModel_1->set('page','1');

		$relatedModuleName_1 = 'Roundtrip';
		$parentRecordModel_1 = $fleet_trip_info_detail;
		$relationListView_1 = Vtiger_RelationListView_Model::getInstance($parentRecordModel_1, $relatedModuleName_1, $label);
		$models_1 = $relationListView_1->getEntries($pagingModel_1);


		foreach($models_1 as $key => $model){

				$round_trip_id = $model->getId();
				$sourceModule 		= 'Roundtrip';
				$roundtrip_info = Vtiger_Record_Model::getInstanceById($round_trip_id, $sourceModule);

				$total_revenew +=$model->get('cf_3173');

				$job_id 			  = $model->get('cf_3175');

				$sourceModule_job 	= 'Job';
				$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule_job);

				$job_reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info->get('cf_1186'));
				$file_title_currency = $job_reporting_currency;

				$adb_buy_local = PearDatabase::getInstance();
				//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
				$sum_buy_local_currency_net =  "SELECT vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
													   vtiger_jobexpencereportcf.cf_1216 as buy_invoice_date,
													   vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
												FROM `vtiger_jobexpencereport`
												INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid
												INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
												left join vtiger_jobexpencereportcf as vtiger_jobexpencereportcf on vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid
												where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? and vtiger_crmentityrel.module='Job'
												and vtiger_crmentityrel.relmodule='Jobexpencereport' and vtiger_jobexpencereportcf.cf_1457='Expence'
												and vtiger_jobexpencereportcf.cf_1477=? and vtiger_jobexpencereportcf.cf_1479=?
												AND vtiger_jobexpencereport.fleettrip_id=?
												AND vtiger_jobexpencereport.owner_id=?
												";

				//$params_buy_local = array($model->get('cf_3175'), '85805', '85844', $fleettripid, $roundtrip_info->get('assigned_user_id'));
				$params_buy_local = array($model->get('cf_3175'), $truck_location_id, '85844', $fleettripid, $roundtrip_info->get('assigned_user_id'));


				$result_buy_locall = $adb_buy_local->pquery($sum_buy_local_currency_net, $params_buy_local);
				$numRows_buy_profit = $adb_buy_local->num_rows($result_buy_locall);
				$cost = 0;
						for($jj=0; $jj< $adb_buy_local->num_rows($result_buy_locall); $jj++ ) {
							$row_jrer_buy_local_currency_net = $adb_buy_local->fetch_row($result_buy_locall,$jj);
							//$row_jrer_buy_local_currency_net = $adb_buy_local->fetch_array($result_buy_locall);

							$cost_local = @$row_jrer_buy_local_currency_net['buy_local_currency_net'];

							$buy_invoice_date = @$row_jrer_buy_local_currency_net['buy_invoice_date'];

							$CurId = $row_jrer_buy_local_currency_net['buy_currency_id'];
							if ($CurId) {
							  $q_cur = $adb->pquery('select * from vtiger_currency_info where id = "'.$CurId.'"');
							  $row_cur = $adb->fetch_array($q_cur);
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

						$adb_internal = PearDatabase::getInstance();
						//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
						$internal_selling_arr = "SELECT vtiger_jobexpcf.cf_1263 as internal_selling
												FROM vtiger_jobexp
												INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexp.jobexpid
 												INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
 												left join vtiger_jobexpcf as vtiger_jobexpcf on vtiger_jobexpcf.jobexpid=vtiger_jobexp.jobexpid
		 					  					where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? and vtiger_crmentityrel.module='Job'
												and vtiger_crmentityrel.relmodule='Jobexp' and vtiger_jobexpcf.cf_1257=? and vtiger_jobexpcf.cf_1259=?
												";

						//$params_internal = array($model->get('cf_3175'), '85805', '85844');
						$params_internal = array($model->get('cf_3175'), $truck_location_id, '85844');

						$result_internal = $adb_internal->pquery($internal_selling_arr, $params_internal);
						$row_jrer_internal_selling = $adb_internal->fetch_array($result_internal);

						//$job_profit = @$row_jrer_internal_selling['internal_selling'] - $cost;
						$job_profit = $model->get('cf_3173') -$cost;

						$profit_share_data[] = array('cost' => number_format ( $cost , 2 ,  "." , "," ),
													 'job_profit'  =>  number_format ( $job_profit , 2 ,  "." , "," ),
													 'job_ref_no' => $model->getDisplayValue('cf_3175'),
													 'job_id' => $model->get('cf_3175'),
												 	 //'internal_selling' => @$row_jrer_internal_selling['internal_selling'],
													 'internal_selling' => $model->get('cf_3173'),
													 //'user_id' => $current_user->getId()
													 'user_id' => $roundtrip_info->get('assigned_user_id')
													 );


						$sum_of_cost += $cost;
						$sum_of_job_profit +=$job_profit;
						//$sum_of_internal_selling +=@$row_jrer_internal_selling['internal_selling'];
						$sum_of_internal_selling +=$model->get('cf_3173');

			}


			$allocated_job_no_costing_breakdown = '';
			$invoice_amount = $fleet_expense_info_detail->get('cf_1343');
			$sum_invoice_cost_breakdown = 0;

			if(!empty($profit_share_data))
			{
				foreach($profit_share_data as $costing_breakdown)
				{

				$percentage_per_job = ($costing_breakdown['internal_selling']*100)/$sum_of_internal_selling;
				$invoice_cost_breakdown = ($invoice_amount*$percentage_per_job)/100;

				$sum_invoice_cost_breakdown+=$invoice_cost_breakdown;

				$allocated_job_no_costing_breakdown .= '<tr>
														<td width="216">
														'.$costing_breakdown['job_ref_no'].'
														</td>
														<td width="186">
														'.$costing_breakdown['internal_selling'].'
														</td>
														<td width="174">
														'.$costing_breakdown['cost'].'
														</td>
														<td width="105">
														'.$costing_breakdown['job_profit'].'
														</td>
														<td width="135">
														'.number_format ( $invoice_cost_breakdown , 2 ,  "." , "," ).'
														</td>
														</tr>';
				}

			}

		$this->setValue('allocated_job_no_costing_breakdown', $allocated_job_no_costing_breakdown);
		$this->setValue('total_internal_selling', number_format ( $sum_of_internal_selling , 2 ,  "." , "," ));
		$this->setValue('total_costing', number_format ( $sum_of_cost , 2 ,  "." , "," ));
		$this->setValue('total_profit', number_format ( $sum_of_job_profit , 2 ,  "." , "," ));
		$this->setValue('sum_invoice_cost_breakdown', number_format ( $sum_invoice_cost_breakdown , 2 ,  "." , "," ));



		include('include/mpdf60/mpdf.php');
		 @date_default_timezone_set($current_user->get('time_zone'));
  		 $mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10); /*задаем формат, отступы и.т.д.*/
  		$mpdf->charset_in = 'utf8';

		$mpdf->list_indent_first_level = 0;

		//$mpdf->SetDefaultFontSize(12);
		//$mpdf->setAutoTopMargin(2);
		$mpdf->SetHTMLFooter('<table width="100%" cellpadding="0" cellspacing="0">
		<tr><td width="40%" align="left" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">Printed: '.date('d.m.Y; H:i').' by '.@$current_user->get('user_name').'</td>
		<td width="20%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">Page {PAGENO} of {nbpg}</td>
		<td width="40%" align="right" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">PV #: '.$JobexpencereportId.'</td>
		</table>');

		$stylesheet = file_get_contents('include/Quote/pdf_styles.css');
		$mpdf->WriteHTML($stylesheet,1);	// The parameter 1 tells that this is css/style only and no body/html/text
		$mpdf->WriteHTML($this->_documentXML); /*формируем pdf*/



		$pdf_name_final = 'pdf_docs/pv_'.$JobexpencereportId.$pdf_name;

		$mpdf->Output($pdf_name_final, 'F');
		//header('Location:http://mb.globalink.net/vt60/'.$pdf_name);
		header('Location:'.$pdf_name_final);
		exit;
		//$mpdf->Output($pdf_name, 'F');
		//header('Location:http://mb.globalink.net/vt60/'.$pdf_name);
		//header('Location:'.$pdf_name);
		//exit;

	}

	public function print_wagon_trip_expense($request)
	{
		global $adb;
		$moduleName = $request->getModule();
		$record = $request->get('record');

		$wagontrip_id = $record;
		$current_user = Users_Record_Model::getCurrentUserModel();
		$wagontrip_info_detail = Vtiger_Record_Model::getInstanceById($wagontrip_id, 'WagonTrip');


		$document = $this->loadTemplate('printtemplates/wagontrip_expense.html');

		//$driver_user_info = Users_Record_Model::getInstanceById($wagontrip_info_detail->get('cf_3167'), 'Users');
		$owner_wagontrip_user_info = Users_Record_Model::getInstanceById($wagontrip_info_detail->get('assigned_user_id'), 'Users');

		$this->setValue('useroffice',$owner_wagontrip_user_info->getDisplayValue('location_id'));
		$this->setValue('userdepartment',$owner_wagontrip_user_info->getDisplayValue('department_id'));
		$this->setValue('mobile',$owner_wagontrip_user_info->get('phone_mobile'));
		$this->setValue('fax',$owner_wagontrip_user_info->get('phone_fax'));
		$this->setValue('email',htmlentities($owner_wagontrip_user_info->get('email1'), ENT_QUOTES, "UTF-8"));
		$this->setValue('cityname',htmlentities($owner_wagontrip_user_info->getDisplayValue('location_id'), ENT_QUOTES, "UTF-8"));
		$this->setValue('countryname',htmlentities($owner_wagontrip_user_info->get('address_country'), ENT_QUOTES, "UTF-8"));
		$this->setValue('departmentcode',htmlentities($owner_wagontrip_user_info->getDisplayValue('department_id'), ENT_QUOTES, "UTF-8"));
		$this->setValue('dateadded',date('d.m.Y', strtotime($wagontrip_info_detail->get('createdtime'))));
		//$this->setValue('billingto', $pay_to_info->get('accountname'));
		$this->setValue('from', htmlentities($owner_wagontrip_user_info->get('first_name').' '.$owner_wagontrip_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));

		//$this->setValue('refno', $job_info_detail->get('cf_1198'));
		$this->setValue('wagon_ref_no', $wagontrip_info_detail->get('cf_5790'));
		$this->setValue('wagonno', htmlentities($wagontrip_info_detail->getDisplayValue('cf_5800'), ENT_QUOTES, "UTF-8"));

		$this->setValue('grossweight', htmlentities($wagontrip_info_detail->get('cf_5796'), ENT_QUOTES, "UTF-8"));
		$this->setValue('volumeweight', htmlentities($wagontrip_info_detail->get('cf_5798'), ENT_QUOTES, "UTF-8"));

		$this->setValue('pieces', htmlentities($wagontrip_info_detail->get('cf_5794'), ENT_QUOTES, "UTF-8"));

		//cf_6304:: origin station
		//cf_6308 :: destination statiosn
		$this->setValue('origin_station', htmlentities($wagontrip_info_detail->get('cf_6304'), ENT_QUOTES, "UTF-8"));
		$this->setValue('destination_station', htmlentities($wagontrip_info_detail->get('cf_6308'), ENT_QUOTES, "UTF-8"));

		$from_to_date='';
		$from_date = $wagontrip_info_detail->get('cf_5806');
		$start = strtotime($wagontrip_info_detail->get('cf_5806'));
		$end = strtotime($wagontrip_info_detail->get('cf_5808'));
		if(!empty($from_date) && $from_date!='0000-00-00')
		{
			$from_to_date = date('d.m.Y', $start).' - '.date('d.m.Y', $end);
		}
		//cf_5806 :: from date
		//cf_5808 :: to date
		$this->setValue('from_to_date',$from_to_date);


		$days_between = ceil(abs($end - $start) / 86400)+1;
		$this->setValue('total_days', $days_between);


		$wagon_info = Vtiger_Record_Model::getInstanceById($wagontrip_info_detail->get('cf_5800'), 'Wagon');

		$wagon_location_id = $owner_wagontrip_user_info->get('location_id');
		//$this->setValue('origincountry',htmlentities($wagontrip_info_detail->get('cf_3237'), ENT_QUOTES, "UTF-8"));
		//$this->setValue('origincity',htmlentities($wagontrip_info_detail->get('cf_3241'), ENT_QUOTES, "UTF-8"));
		//$this->setValue('destinationcountry',htmlentities($wagontrip_info_detail->get('cf_3239'), ENT_QUOTES, "UTF-8"));
		//$this->setValue('destinationcity',htmlentities($wagontrip_info_detail->get('cf_3243'), ENT_QUOTES, "UTF-8"));

		$adb_railway = PearDatabase::getInstance();
		//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
		$railway_fleet_sql =  "SELECT * FROM `vtiger_railwayfleet`
							INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_railwayfleet.railwayfleetid
							INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
 							LEFT JOIN vtiger_railwayfleetcf as vtiger_railwayfleetcf on vtiger_railwayfleetcf.railwayfleetid=vtiger_railwayfleet.railwayfleetid
		 				    WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=?
								  AND vtiger_crmentityrel.module='WagonTrip' AND vtiger_crmentityrel.relmodule='RailwayFleet'
								  ";

		$params_fleet_railway = array($wagontrip_id);
		$result_railway = $adb_railway->pquery($railway_fleet_sql, $params_fleet_railway);
		$allocated_job_no = '';
		for($ij=0; $ij<$adb_railway->num_rows($result_railway); $ij++) {
			$job_ref_id = $adb_railway->query_result($result_railway, $ij, 'cf_5820');
			if($job_ref_id=='RED')
			{
				$job_ref_no = $adb_railway->query_result($result_railway, $ij, 'cf_5884');
			}else{
			$job_ref_no = Vtiger_JobFileList_UIType::getDisplayValue($job_ref_id);
			}
			$internal_selling_local = $adb_railway->query_result($result_railway, $ij, 'cf_5822');
			$CurId = $adb_railway->query_result($result_railway, $ij, 'cf_5824');
			$internal_selling = $adb_railway->query_result($result_railway, $ij, 'cf_5826');
			$railway_fleet_date = $adb_railway->query_result($result_railway, $ij, 'cf_5818');

			if ($CurId) {
			  $q_cur = $adb->pquery('select * from vtiger_currency_info where id = "'.$CurId.'"');
			  $row_cur = $adb->fetch_array($q_cur);
			  $Cur = $row_cur['currency_code'];
			}

			$allocated_job_no .='<tr>
								<td width="216">
								'.date('d-m-Y',strtotime($railway_fleet_date)).'
								</td>
								<td width="216">
								'.$job_ref_no.'
								</td>
								<td>'.$internal_selling_local.'</td>
								<td>'.$Cur.'</td>
								<td width="186">
								'.$origin_city.'
								</td>
								<td width="174">
								'.$destination_city.'
								</td>
								<td width="120">
								'.$internal_selling.'
								</td>
								</tr>';
		}

		$this->setValue('allocated_job_no', $allocated_job_no);

		//FOR RED
		$adb_railway_RED = PearDatabase::getInstance();
		// OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
		$railway_fleet_sql_RED =  "SELECT sum(vtiger_railwayfleetcf.cf_5826) as total_internal_selling FROM `vtiger_railwayfleet`
							INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_railwayfleet.railwayfleetid
							INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid)
 							LEFT JOIN vtiger_railwayfleetcf as vtiger_railwayfleetcf on vtiger_railwayfleetcf.railwayfleetid=vtiger_railwayfleet.railwayfleetid
		 				    WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=?
								  AND vtiger_crmentityrel.module='WagonTrip' AND vtiger_crmentityrel.relmodule='RailwayFleet'
								  ";

		$params_fleet_railway_RED = array($wagontrip_id);
		$result_railway_RED = $adb_railway_RED->pquery($railway_fleet_sql_RED, $params_fleet_railway_RED);
		$total_internal_selling_RED = $adb_railway_RED->query_result($result_railway_RED, '0', 'total_internal_selling');


		include('include/Exchangerate/exchange_rate_class.php');
		$current_user = Users_Record_Model::getCurrentUserModel();
			$total_revenew = 0;
			$sum_of_cost = 0;
			$sum_of_job_profit = 0;
			$sum_of_internal_selling = 0;
			$profit_share_data = array();

			$pagingModel_1 = new Vtiger_Paging_Model();
			$pagingModel_1->set('page','1');

		$relatedModuleName_1 = 'RailwayFleet';
		$parentRecordModel_1 = $wagontrip_info_detail;
		$relationListView_1 = Vtiger_RelationListView_Model::getInstance($parentRecordModel_1, $relatedModuleName_1, $label);
		$models_1 = $relationListView_1->getEntries($pagingModel_1);


			foreach($models_1 as $key => $model){
				$total_revenew +=$model->get('cf_5826');

				$railway_fleet_id  = $model->getId();
				$sourceModule   = 'RailwayFleet';
				$railwayfleet_info = Vtiger_Record_Model::getInstanceById($railway_fleet_id, $sourceModule);

				$job_id 			  = $model->get('cf_5820');

				if($job_id=='REDJOB')
				{

					$file_title_currency_RED ='KZT';
					$pagingModel_RED = new Vtiger_Paging_Model();
					$pagingModel_RED->set('page','1');

					$relatedModuleName_RED = 'Jobexpencereport';
					$parentRecordModel_RED = $wagontrip_info_detail;
					$relationListView_RED = Vtiger_RelationListView_Model::getInstance($parentRecordModel_RED, $relatedModuleName_RED, $label);
					$models_RED = $relationListView_RED->getEntries($pagingModel_RED);

					foreach($models_RED as $key => $model_RED)
					{
						$expense_record_RED= $model_RED->getInstanceById($model_RED->getId());
						if($model_RED->getDisplayValue('cf_1457') == 'Selling'){
							continue;
						}
						$i++;

						$Cur = $expense_record_RED->getDisplayValue('cf_1345');
						//$invoice_date_RED = $expense_record_RED->get('cf_1216');
						$timestamp_RED = strtotime($model->getDisplayValue('cf_5818'));
						$invoice_date_RED = date('Y-m-d', $timestamp_RED);
						$b_exchange_rate_RED =1;
						if(!empty($invoice_date_RED))
						{
							if($file_title_currency_RED!='USD')
							{
								$b_exchange_rate_RED = currency_rate_convert_kz($file_title_currency_RED, 'USD',  1, $invoice_date_RED);
							}else{
								$b_exchange_rate_RED = currency_rate_convert($file_title_currency_RED, 'USD',  1, $invoice_date_RED);
							}
						}
						$value_in_usd_normal_RED = $expense_record_RED->getDisplayValue('cf_1349');
						if($file_title_currency_RED!='USD')
						{
							$value_in_usd_normal_RED = $expense_record_RED->getDisplayValue('cf_1349')/$b_exchange_rate_RED;
						}

						$value_in_usd_RED = number_format($value_in_usd_normal_RED, 2, '.', '');

						$total_usd_RED += $value_in_usd_RED;
					}

					$internal_selling_forpercent = $model->get('cf_5826');
					$percentage_per_job_RED = ($internal_selling_forpercent*100)/$total_internal_selling_RED;
					$invoice_cost_breakdown_RED = ($total_usd_RED*$percentage_per_job_RED)/100;

					$job_profit_RED = $internal_selling_forpercent - $invoice_cost_breakdown_RED;

					$profit_share_data[] = array('cost' => number_format ( $invoice_cost_breakdown_RED , 2 ,  "." , "," ),
												 'job_profit'  =>  number_format ( $job_profit_RED , 2 ,  "." , "," ),
												 'job_ref_no' => $model->get('cf_5884'),
												 'job_id' => '',
												// 'internal_selling' => @$row_jrer_internal_selling['internal_selling'],
												 'internal_selling' => number_format ( $model->get('cf_5826') , 2 ,  "." , "," ),
												 'internal_selling_forpercent' => $model->get('cf_5826'),
												 'user_id' => $current_user->getId(),
												 'origin_city' => $model->get('cf_5822'),
												 'destination_city' => $model->get('cf_5824'),
												 'selling_created_time' => $model->getDisplayValue('createdtime'),
												 'railway_trip_date' => date('d.m.Y', strtotime($model->getDisplayValue('cf_5818')))
												 );


						$sum_of_cost += $invoice_cost_breakdown_RED;
						$sum_of_job_profit +=$job_profit_RED;
						//$sum_of_internal_selling +=@$row_jrer_internal_selling['internal_selling'];
						$sum_of_internal_selling +=$model->get('cf_5826');
					continue;
				}

				$sourceModule_job 	= 'Job';
				$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule_job);

				$job_reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info->get('cf_1186'));
				$file_title_currency = $job_reporting_currency;

				if($job_info->get('assigned_user_id')!=$railwayfleet_info->get('assigned_user_id'))
				{
					$db = PearDatabase::getInstance();

					$rs_query  = $adb->pquery("select * from vtiger_jobtask
											  where job_id='".$job_id."' and user_id='".$railwayfleet_info->get('assigned_user_id')."' limit 1");
					$row_task = $adb->fetch_array($rs_query);

					if($adb->num_rows($rs_query)>0)
					{
						$file_title_id = $row_task['sub_jrer_file_title'];
						if(empty($file_title_id))
						{
							$job_office_id = $job_info->get('cf_1188');
							$railwayfleet_user_info = Users_Record_Model::getInstanceById($railwayfleet_info->get('assigned_user_id'), 'Users');
							$railwayfleet_user_office_id = $railwayfleet_user_info->get('location_id');

							//if same office then job file title must apply
							if($job_office_id==$railwayfleet_user_office_id){
								$file_title_id = $job_info->get('cf_1186');
							}
							else{
								//by default KZ file title
								$file_title_id = '85757';
							}
						}
						$job_reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$file_title_id);
						$file_title_currency = $job_reporting_currency;
					}
				}

				$adb_buy_local = PearDatabase::getInstance();
				//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
				$sum_buy_local_currency_net =  "SELECT vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
													   vtiger_jobexpencereportcf.cf_1216 as buy_invoice_date,
													   vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
												FROM `vtiger_jobexpencereport`
												INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid
												INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
												left join vtiger_jobexpencereportcf as vtiger_jobexpencereportcf on vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid
												where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? and vtiger_crmentityrel.module='Job'
												and vtiger_crmentityrel.relmodule='Jobexpencereport' and vtiger_jobexpencereportcf.cf_1457='Expence'
												and vtiger_jobexpencereportcf.cf_1477=? and vtiger_jobexpencereportcf.cf_1479=?
												AND vtiger_jobexpencereport.wagontrip_id=?
												AND vtiger_jobexpencereport.railwayfleet_id =?
												AND vtiger_jobexpencereport.owner_id=?
												";

				//$params_buy_local = array($model->get('cf_3175'), '85805', '85844', $fleet_id, $round_trip_id, $roundtrip_info->get('assigned_user_id'));
				$params_buy_local = array($model->get('cf_5820'), $wagon_location_id, '1414775', $wagontrip_id, $railway_fleet_id, $railwayfleet_info->get('assigned_user_id'));
				//$truck_location_id
				$result_buy_locall = $adb_buy_local->pquery($sum_buy_local_currency_net, $params_buy_local);
				$numRows_buy_profit = $adb_buy_local->num_rows($result_buy_locall);
				$cost = 0;
						for($jj=0; $jj< $adb_buy_local->num_rows($result_buy_locall); $jj++ ) {
							$row_jrer_buy_local_currency_net = $adb_buy_local->fetch_row($result_buy_locall,$jj);
							//$row_jrer_buy_local_currency_net = $adb_buy_local->fetch_array($result_buy_locall);

							$cost_local = @$row_jrer_buy_local_currency_net['buy_local_currency_net'];

							$buy_invoice_date = @$row_jrer_buy_local_currency_net['buy_invoice_date'];

							$CurId = $row_jrer_buy_local_currency_net['buy_currency_id'];
							if ($CurId) {
							  $q_cur = $adb->pquery('select * from vtiger_currency_info where id = "'.$CurId.'"');
							  $row_cur = $adb->fetch_array($q_cur);
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

						$adb_internal = PearDatabase::getInstance();
						//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
						$internal_selling_arr = "SELECT vtiger_jobexpcf.cf_1263 as internal_selling
												FROM vtiger_jobexp
												INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexp.jobexpid
 												INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
 												left join vtiger_jobexpcf as vtiger_jobexpcf on vtiger_jobexpcf.jobexpid=vtiger_jobexp.jobexpid
		 					  					where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? and vtiger_crmentityrel.module='Job'
												and vtiger_crmentityrel.relmodule='Jobexp' and vtiger_jobexpcf.cf_1257=? and vtiger_jobexpcf.cf_1259=?
												";

						//$params_internal = array($model->get('cf_3175'), '85805', '85844');
						$params_internal = array($model->get('cf_5820'), $wagon_location_id, '1414775');

						$result_internal = $adb_internal->pquery($internal_selling_arr, $params_internal);
						$row_jrer_internal_selling = $adb_internal->fetch_array($result_internal);

						//$job_profit = @$row_jrer_internal_selling['internal_selling'] - $cost;
						$job_profit = $model->get('cf_5826') - $cost;

						$profit_share_data[] = array('cost' => number_format ( $cost , 2 ,  "." , "," ),
													 'job_profit'  =>  number_format ( $job_profit , 2 ,  "." , "," ),
													 'job_ref_no' => $model->getDisplayValue('cf_5820'),
													 'job_id' => $model->get('cf_5820'),
												 	// 'internal_selling' => @$row_jrer_internal_selling['internal_selling'],
													 'internal_selling' => number_format ( $model->get('cf_5826') , 2 ,  "." , "," ),
													 'internal_selling_forpercent' => $model->get('cf_5826'),
													 'user_id' => $current_user->getId(),
													 'origin_city' => $model->get('cf_5822'),
													 'destination_city' => $model->get('cf_5824'),
													 'selling_created_time' => $model->getDisplayValue('createdtime'),
													 'railway_trip_date' => date('d.m.Y', strtotime($model->getDisplayValue('cf_5818')))
													 );


						$sum_of_cost += $cost;
						$sum_of_job_profit +=$job_profit;
						//$sum_of_internal_selling +=@$row_jrer_internal_selling['internal_selling'];
						$sum_of_internal_selling +=$model->get('cf_5826');

			}


			$allocated_job_no_costing_breakdown = '';
			$total_internal_selling_per_job_kz= 0;
			if(!empty($profit_share_data))
			{
				foreach($profit_share_data as $costing_breakdown)
				{


				$percentage_per_job = ($costing_breakdown['internal_selling_forpercent']*100)/$sum_of_internal_selling;

				//$created_time_internal_selling = $costing_breakdown['selling_created_time'];

				//$timestamp = strtotime($created_time_internal_selling);
				$timestamp = strtotime($costing_breakdown['railway_trip_date']);
				$selling_date = date('Y-m-d', $timestamp);

				$b_exchange_rate_selling = $b_exchange_rate = currency_rate_convert('KZT', 'USD',  1, $selling_date);

				$internal_selling_per_job_kz = $costing_breakdown['internal_selling_forpercent']*$b_exchange_rate_selling;

				$total_internal_selling_per_job_kz +=$internal_selling_per_job_kz;

				$CurId = $costing_breakdown['destination_city'];
				if ($CurId) {
				  $q_cur = $adb->pquery('select * from vtiger_currency_info where id = "'.$CurId.'"');
				  $row_cur = $adb->fetch_array($q_cur);
				  $Cur = $row_cur['currency_code'];
				}

				$allocated_job_no_costing_breakdown .= '<tr>
														<td width="216">
														'.$costing_breakdown['railway_trip_date'].'
														</td>
														<td width="120">
														'.$costing_breakdown['job_ref_no'].'
														</td>
														<td width="150">'.$costing_breakdown['origin_city'].'</td>
														<td width="150">'.$Cur.'</td>
														<td width="120">
														'.$costing_breakdown['internal_selling'].'
														</td>
														<td width="120">
														'.number_format ($percentage_per_job,4).' %
														</td>
														<td width="100">'.number_format ( $internal_selling_per_job_kz , 2 ,  "." , "," ).'</td>
														<td width="100">
														'.$costing_breakdown['cost'].'
														</td>
														<td width="100">
														'.$costing_breakdown['job_profit'].'
														</td>
														</tr>';
				}

			}

		$this->setValue('allocated_job_no_costing_breakdown', $allocated_job_no_costing_breakdown);
		$this->setValue('total_internal_selling', number_format ( $sum_of_internal_selling , 2 ,  "." , "," ));
		$this->setValue('total_internal_selling_KZT', number_format ( $total_internal_selling_per_job_kz , 2 ,  "." , "," ));
		$this->setValue('total_costing', number_format ( $sum_of_cost , 2 ,  "." , "," ));
		$this->setValue('total_profit', number_format ( $sum_of_job_profit , 2 ,  "." , "," ));



		$pagingModel = new Vtiger_Paging_Model();
		$pagingModel->set('page','1');

		$relatedModuleName = 'Jobexpencereport';
		$parentRecordModel = $wagontrip_info_detail;
		$relationListView = Vtiger_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName, $label);
		$models = $relationListView->getEntries($pagingModel);



		//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
		$jrer_sum_sql =  "SELECT sum(vtiger_jobexpencereportcf.cf_1347) as buy_local_currency_gross,
								     sum(vtiger_jobexpencereportcf.cf_1349) as buy_local_currency_net,
									 sum(vtiger_jobexpencereportcf.cf_1351) as expected_buy_local_currency_net,
									 sum(vtiger_jobexpencereportcf.cf_1353) as variation_expected_and_actual_buying
									 FROM `vtiger_jobexpencereport`
							  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid
 			INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
 			LEFT JOIN vtiger_jobexpencereportcf as vtiger_jobexpencereportcf on vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid
		 					 WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=?
							 AND vtiger_crmentityrel.module='WagonTrip'
							 AND vtiger_crmentityrel.relmodule='Jobexpencereport'
							 AND vtiger_jobexpencereportcf.cf_1457='Expence' AND vtiger_jobexpencereport.owner_id='".$wagontrip_info_detail->get('assigned_user_id')."'";
		$parentId = $wagontrip_id;
		$params = array($parentId);
		$result = $adb->pquery($jrer_sum_sql, $params);
		$row_job_jrer = $adb->fetch_array($result);

		$BUY_LOCAL_CURRENCY_GROSS = number_format ( $row_job_jrer['buy_local_currency_gross'] , 2 ,  "." , ",");
		$BUY_LOCAL_CURRENCY_NET   = number_format ( $row_job_jrer['buy_local_currency_net'] , 2 ,  "." , "," );
		$EXPECTED_BUY_LOCAL_CURRENCY_NET  = number_format ( $row_job_jrer['expected_buy_local_currency_net'] , 2 ,  "." , "," );
		$VARIATION_EXPECTED_AND_ACTUAL_BUYING = number_format ( $row_job_jrer['variation_expected_and_actual_buying'] , 2 ,  "." , "," );

		$assigned_user_info =  Users_Record_Model::getInstanceById($wagontrip_info_detail->get('assigned_user_id'), 'Users');
		$current_user_office_id = $assigned_user_info->get('location_id');
		//For checking user is main owner or sub user
		$company_id = $assigned_user_info->get('company_id');

		$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency($company_id);

		$file_title_currency = $reporting_currency;

		//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
		 $jrer_sql_expense =  "SELECT vtiger_jobexpencereportcf.cf_1347 as buy_local_currency_gross,
								     vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
									 vtiger_jobexpencereportcf.cf_1351 as expected_buy_local_currency_net,
									 vtiger_jobexpencereportcf.cf_1353 as variation_expected_and_actual_buying,
									 vtiger_jobexpencereportcf.cf_1216 as expense_invoice_date,
									 vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
									 FROM `vtiger_jobexpencereport`
							  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid
 			INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
 			LEFT JOIN vtiger_jobexpencereportcf as vtiger_jobexpencereportcf on vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid
		 					 WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=?
							 AND vtiger_crmentityrel.module='WagonTrip'
							 AND vtiger_crmentityrel.relmodule='Jobexpencereport'
							 AND vtiger_jobexpencereportcf.cf_1457='Expence' AND vtiger_jobexpencereport.owner_id='".$wagontrip_info_detail->get('assigned_user_id')."'";

				$parentId = $wagontrip_id;

				$params = array($parentId);
				$result_expense = $adb->pquery($jrer_sql_expense, $params);
				$numRows_expnese = $adb->num_rows($result_expense);

				$total_cost_in_usd_gross = 0;
				$total_cost_in_usd_net = 0;
				$total_expected_cost_in_usd_net = 0;
				$total_variation_expected_and_actual_buying_cost_in_usd = 0;

				if($numRows_expnese>0)
				{
					for($ii=0; $ii< $adb->num_rows($result_expense); $ii++ ) {
						$row_job_jrer_expense = $adb->fetch_row($result_expense,$ii);
						$expense_invoice_date = $row_job_jrer_expense['expense_invoice_date'];

						$CurId = $row_job_jrer_expense['buy_currency_id'];
						if ($CurId) {
						  $q_cur = $adb->pquery('select * from vtiger_currency_info where id = "'.$CurId.'"');
						  $row_cur = $adb->fetch_array($q_cur);
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
						}else{
							if($b_exchange_rate==0)
							{
								$b_exchange_rate = 1;
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

		/*$total_cost_in_usd_gross = $row_job_jrer['buy_local_currency_gross']/$final_exchange_rate;
		$total_cost_in_usd_net = $row_job_jrer['buy_local_currency_net']/$final_exchange_rate;
		$total_expected_cost_in_usd_net = $row_job_jrer['expected_buy_local_currency_net']/$final_exchange_rate;
		$total_variation_expected_and_actual_buying_cost_in_usd = $row_job_jrer['variation_expected_and_actual_buying']/$final_exchange_rate;*/

		$TOTAL_COST_USD_GROSS = number_format ( $total_cost_in_usd_gross , 2 ,  "." , "," );
		$TOTAL_COST_IN_USD_NET = number_format ( $total_cost_in_usd_net , 2 ,  "." , "," );
		$TOTAL_EXPECTED_COST_USD_NET = number_format ( $total_expected_cost_in_usd_net , 2 ,  "." , "," );
		$TOTAL_VARIATION_EXPECTED_AND_ACTUAL_BUYING_COST_IN_USD = number_format ( $total_variation_expected_and_actual_buying_cost_in_usd , 2 ,  "." , "," );


		$i=0;
		$total_usd = 0;
		$buyingsubtotalR = 0;
		$buyingsubtotalN = 0;
		$buying = '<table border="1" cellspacing="0" cellpadding="0" width="100%" >
			<tbody>
				<tr>
					<td width="19" align="center">

						<strong><u>#</u></strong>
					</td>
					<td width="62" align="center">
						<h6><u>Name</u></h6>
					</td>
					<td width="66" align="center">
						<h6><u>Service</u></h6>
					</td>
					<td width="57" align="center">
						<h6><u>Invoice</u></h6>
					</td>
					<td width="47" align="center">
						<h6><u>Date</u></h6>
					</td>
					<td width="47" align="center">
						<h6><u>Dept</u></h6>
					</td>
					<td width="57" align="center">
						<h6><u>Type</u></h6>
					</td>
					<td width="57" align="center">
						<h6><u>Buy (Vendor cur net)</u></h6>
					</td>
					<td width="41" align="center">
						<h6><u>VAT Rate</u></h6>
					</td>
					<td width="42" align="center">
						<h6><u>VAT</u></h6>
					</td>
					<td width="72" align="center">
						<h6><u>Buy (cur gross)</u></h6>
					</td>
					<td width="36" align="center">
						<h6><u>Cur</u></h6>
					</td>
					<td width="48" align="center">
						<h6><u>Exch. Rate</u></h6>
					</td>
					<td width="60" align="center">
						<h6><u>Buy (loc cur gross)</u></h6>
					</td>
					<td width="60" align="center">
						<h6><u>Buy (loc cur net)</u></h6>
					</td>
					<td width="66" align="center">
						<h6><u>Expected buy (cur net)</u></h6>
					</td>
					<td width="44" align="center">
						<h6><u>Var exp and act buying</u></h6>
					</td>
					<td width="60" align="center">
						<h6><u>Total, $</u></h6>
					</td>
				</tr>
				<tr>
					<td valign="top" width="19">
					</td>
					<td valign="top" width="62">
					</td>
					<td valign="top" width="66">
					</td>
					<td valign="top" width="57">
					</td>
					<td valign="top" width="47">
					</td>
					<td valign="top" width="47">
					</td>
					<td valign="top" width="57">
					</td>
					<td valign="top" width="57">
					</td>
					<td valign="top" width="41">
					</td>
					<td valign="top" width="42">
					</td>
					<td valign="top" width="72">
					</td>
					<td valign="top" width="36">
					</td>
					<td valign="top" width="48">
					</td>
					<td valign="top" width="60">
					</td>
					<td valign="top" width="60">
					</td>
					<td valign="top" width="66">
					</td>
					<td valign="top" width="44">
					</td>
					<td valign="top" width="60">
					</td>
				</tr>
				';

		foreach($models as $key => $model)
		{
			$expense_record= $model->getInstanceById($model->getId());
			if($model->getDisplayValue('cf_1457') == 'Selling'){
				continue;
			}
			$i++;

			$Cur = $expense_record->getDisplayValue('cf_1345');
			$invoice_date = $expense_record->get('cf_1216');
			$b_exchange_rate = $final_exchange_rate;
			if(!empty($invoice_date))
			{
				if($file_title_currency!='USD')
				{
					$b_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $invoice_date);
				}else{
					$b_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $invoice_date);
				}
			}
			$value_in_usd_normal = $expense_record->getDisplayValue('cf_1349');
			if($file_title_currency!='USD')
			{
				$value_in_usd_normal = $expense_record->getDisplayValue('cf_1349')/$b_exchange_rate;
			}

			$value_in_usd = number_format($value_in_usd_normal,2,'.','');

			$AccType = $expense_record->getDisplayValue('cf_1214');
			if (substr($AccType, -1) == 'R') {
			$buyingsubtotalR = $buyingsubtotalR + $value_in_usd_normal;}
			if ((substr($AccType, -1) == 'N') or (substr($AccType, -1) == 'D')) {
			$buyingsubtotalN = $buyingsubtotalN + $value_in_usd_normal; }

			$pay_to_id = $expense_record->get('cf_1367');
			$company_accountname = '';
			if(!empty($pay_to_id))
			{
				$crmentity_check_ =  "SELECT vtiger_crmentity.crmid as crmid,  vtiger_crmentity.label  as label, vtiger_crmentity.deleted  as deleted from vtiger_crmentity where crmid=?  ";
				$params = array($pay_to_id);
				$result_crmentity_check_ = $adb->pquery($crmentity_check_, $params);
				$numRows_crmentity_check_ = $adb->num_rows($result_crmentity_check_);
				$row_crmentity_check_ = $adb->fetch_array($result_crmentity_check_);

				if($row_crmentity_check_['deleted']==0)
				{
				$paytoinfo = Vtiger_Record_Model::getInstanceById($pay_to_id, 'Accounts');
				$company_accountname = @$paytoinfo->get('accountname');
				}
				else{
					$company_accountname =$row_crmentity_check_['label'].' -Deleted';
				}
			}

			$total_usd += $value_in_usd;

			$buying .='<tr>
            <td valign="top" width="19">
                '.$i.'
            </td>
            <td valign="top" width="66">
                '.$company_accountname.'
            </td>
            <td valign="top" width="66">
                '.$expense_record->getDisplayValue('cf_1453').'
            </td>
            <td valign="top" width="57">
                '.$expense_record->getDisplayValue('cf_1212').'
            </td>
            <td valign="top" width="47" align="center">
                '.$expense_record->getDisplayValue('cf_1216').'
            </td>
            <td valign="top" width="47" align="center">
              '.$expense_record->getDisplayValue('cf_1477').' '.$expense_record->getDisplayValue('cf_1479').'
            </td>
            <td valign="top" width="57" align="center">
                '.$expense_record->getDisplayValue('cf_1214').'
            </td>
            <td valign="top" width="57" align="right">
                 '.number_format ( $expense_record->getDisplayValue('cf_1337') , 2 ,  "." , "," ).'
            </td>
            <td valign="top" width="41" align="center">
                 '.$expense_record->getDisplayValue('cf_1339').'
            </td>
            <td valign="top" width="42" align="right">
                '.$expense_record->getDisplayValue('cf_1341').'
            </td>
            <td valign="top" width="72" align="right">
                '.number_format ( $expense_record->getDisplayValue('cf_1343') , 2 ,  "." , "," ).'
            </td>
            <td valign="top" width="36" align="center">
                '.$expense_record->getDisplayValue('cf_1345').'
            </td>
            <td valign="top" width="48" align="right">
                '.number_format($expense_record->getDisplayValue('cf_1222'), 2).'
            </td>
            <td valign="top" width="60" align="right">
                '.number_format ( $expense_record->getDisplayValue('cf_1347') , 2 ,  "." , "," ).'
            </td>
            <td valign="top" width="60" align="right">
                '.number_format ( $expense_record->getDisplayValue('cf_1349') , 2 ,  "." , "," ).'
            </td>
            <td valign="top" width="66" align="right">
                '.$expense_record->getDisplayValue('cf_1351').'
            </td>
            <td valign="top" width="44" align="right">
                '.$expense_record->getDisplayValue('cf_1353').'
            </td>
            <td valign="top" width="60" align="right">
                '.$value_in_usd.'
            </td>
        </tr>';

		}
		$buying .='<tr>
            <td valign="top" width="19">
            </td>
            <td valign="top" width="62">
            </td>
            <td valign="top" width="66">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="47">
            </td>
            <td valign="top" width="47">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="41">
            </td>
            <td valign="top" width="42">
            </td>
            <td valign="top" width="72">
            </td>
            <td valign="top" width="60">
            </td>
            <td valign="top" width="36">
            </td>
            <td valign="top" width="48">
            </td>
            <td valign="top" width="60">
            </td>
            <td valign="top" width="60">
            </td>
            <td valign="top" width="66">
            </td>
            <td valign="top" width="44">
            </td>
        </tr>
        <tr>
            <td valign="top" width="19">
            </td>
            <td valign="top" width="62">
            </td>
            <td valign="top" width="66" align="right" colspan="3">
                <h6><u>Sub Total</u></h6>
            </td>
            <td valign="top" width="47">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="41">
            </td>
            <td valign="top" width="42">
            </td>
            <td valign="top" width="72">
            </td>
            <td valign="top" width="36">
            </td>
            <td valign="top" width="48">
            </td>
            <td valign="top" width="60">
            </td>
            <td valign="top" width="60">
            </td>
            <td valign="top" width="66">
            </td>
            <td valign="top" width="44">
            </td>
            <td valign="top" width="60" align="right">
                <h6><u>'.$total_usd.'</u></h6>
            </td>
        </tr>
        <tr>
            <td valign="top" width="19">
            </td>
            <td valign="top" width="62">
            </td>
            <td valign="top" width="66" align="right" colspan="3">
                <h6><u>Total Local Currency</u></h6>
            </td>

            <td valign="top" width="47">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="41">
            </td>
            <td valign="top" width="42">
            </td>
            <td valign="top" width="72">
            </td>
            <td valign="top" width="36">
            </td>
            <td valign="top" width="48">
            </td>
            <td valign="top" width="60" align="right">
                <h6><u>'.$BUY_LOCAL_CURRENCY_GROSS.'</u></h6>
            </td>
            <td valign="top" width="60" align="right">
                <h6><u>'.$BUY_LOCAL_CURRENCY_NET.'</u></h6>
            </td>
            <td valign="top" width="66" align="right">
                <h6><u>'.$EXPECTED_BUY_LOCAL_CURRENCY_NET.'</u></h6>
            </td>
            <td valign="top" width="44" align="right">
                <h6><u>'.$VARIATION_EXPECTED_AND_ACTUAL_BUYING.'</u></h6>
            </td>
            <td valign="top" width="60">
            </td>
        </tr>

        <tr>
            <td valign="top" width="19">
            </td>
            <td valign="top" width="62">
            </td>
            <td valign="top" width="66" align="right" colspan="3">
                <h6><u>Total Cost USD</u></h6>
            </td>

            <td valign="top" width="47">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="41">
            </td>
            <td valign="top" width="42">
            </td>
            <td valign="top" width="72">
            </td>
            <td valign="top" width="36">
            </td>
            <td valign="top" width="48">
            </td>
            <td valign="top" width="60" align="right">
                <h6><u>'.$TOTAL_COST_USD_GROSS.'</u></h6>
            </td>
            <td valign="top" width="60" align="right">
                <h6><u>'.$TOTAL_COST_IN_USD_NET.'</u></h6>
            </td>
            <td valign="top" width="66" align="right">
                <h6><u>'.$TOTAL_EXPECTED_COST_USD_NET.'</u></h6>
            </td>
            <td valign="top" width="44" align="right">
                <h6><u>'.$TOTAL_VARIATION_EXPECTED_AND_ACTUAL_BUYING_COST_IN_USD.'</u></h6>
            </td>
            <td valign="top" width="60">
            </td>
        </tr></tbody></table>';

		$this->setValue('railway_expense_table', $buying);

		$this->setValue('buyingsubtotalR', number_format ( $buyingsubtotalR , 2 ,  "." , "," ));
		$this->setValue('buyingsubtotalN', number_format ( $buyingsubtotalN , 2 ,  "." , "," ));
		$this->setValue('buyingtotal', $total_usd);

		include('include/mpdf60/mpdf.php');
		 @date_default_timezone_set($current_user->get('time_zone'));
  		$mpdf = new mPDF('utf-8', 'A4-L', '10', '', 10, 10, 30, 15, 10, 5); /*задаем формат, отступы и.т.д.*/
  		$mpdf->charset_in = 'utf8';

		$mpdf->list_indent_first_level = 0;

		//$mpdf->SetDefaultFontSize(12);
		//$mpdf->setAutoTopMargin(2);
				$mpdf->SetHTMLHeader('<table width="100%" cellpadding="0" cellspacing="0">
		<tr><td align="right" style="font-size:9;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">WCR Form, GLOBALINK, designed: March, 2010</td></tr>
		<tr><td align="right"><img src="include/calendar_logo.jpg"/></td></tr></table>');
				$mpdf->SetHTMLFooter('<table width="100%" cellpadding="0" cellspacing="0">
		<tr><td width="40%" align="left" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">Printed: '.date('d.m.Y; H:i').' by '.$current_user->get('user_name').'</td>
		<td width="20%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">Page {PAGENO} of {nbpg}</td>
		<td width="40%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">&nbsp;</td>
		</table>');
		$stylesheet = file_get_contents('include/mpdf60/examples/mpdfstyletables.css');
		$mpdf->WriteHTML($stylesheet,1);	// The parameter 1 tells that this is css/style only and no body/html/text
		$mpdf->WriteHTML($this->_documentXML); /*формируем pdf*/


		$pdf_name = 'pdf_docs/wagon_expense.pdf';

		$mpdf->Output($pdf_name, 'F');
		//header('Location:http://mb.globalink.net/vt60/'.$pdf_name);
		header('Location:'.$pdf_name);
		exit;

	}

	public function print_wagon_trip_expense_breakdown($request)
	{
		global $adb;
		$moduleName = $request->getModule();
		$record = $request->get('record');

		$wagontrip_id = $record;
		$current_user = Users_Record_Model::getCurrentUserModel();
		$wagontrip_info_detail = Vtiger_Record_Model::getInstanceById($wagontrip_id, 'WagonTrip');


		$document = $this->loadTemplate('printtemplates/Wagon/wagontrip_expense.html');

		//$driver_user_info = Users_Record_Model::getInstanceById($wagontrip_info_detail->get('cf_3167'), 'Users');
		$owner_wagontrip_user_info = Users_Record_Model::getInstanceById($wagontrip_info_detail->get('assigned_user_id'), 'Users');

		$this->setValue('useroffice',$owner_wagontrip_user_info->getDisplayValue('location_id'));
		$this->setValue('userdepartment',$owner_wagontrip_user_info->getDisplayValue('department_id'));
		$this->setValue('mobile',$owner_wagontrip_user_info->get('phone_mobile'));
		$this->setValue('fax',$owner_wagontrip_user_info->get('phone_fax'));
		$this->setValue('email',htmlentities($owner_wagontrip_user_info->get('email1'), ENT_QUOTES, "UTF-8"));
		$this->setValue('cityname',htmlentities($owner_wagontrip_user_info->getDisplayValue('location_id'), ENT_QUOTES, "UTF-8"));
		$this->setValue('countryname',htmlentities($owner_wagontrip_user_info->get('address_country'), ENT_QUOTES, "UTF-8"));
		$this->setValue('departmentcode',htmlentities($owner_wagontrip_user_info->getDisplayValue('department_id'), ENT_QUOTES, "UTF-8"));
		$this->setValue('dateadded',date('d.m.Y', strtotime($wagontrip_info_detail->get('createdtime'))));
		//$this->setValue('billingto', $pay_to_info->get('accountname'));
		$this->setValue('from', htmlentities($owner_wagontrip_user_info->get('first_name').' '.$owner_wagontrip_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));

		//$this->setValue('refno', $job_info_detail->get('cf_1198'));
		$this->setValue('wagon_ref_no', $wagontrip_info_detail->get('cf_5790'));
		$this->setValue('wagonno', htmlentities($wagontrip_info_detail->getDisplayValue('cf_5800'), ENT_QUOTES, "UTF-8"));

		$this->setValue('grossweight', htmlentities($wagontrip_info_detail->get('cf_5796'), ENT_QUOTES, "UTF-8"));
		$this->setValue('volumeweight', htmlentities($wagontrip_info_detail->get('cf_5798'), ENT_QUOTES, "UTF-8"));

		$this->setValue('pieces', htmlentities($wagontrip_info_detail->get('cf_5794'), ENT_QUOTES, "UTF-8"));

		//cf_6304:: origin station
		//cf_6308 :: destination statiosn
		$this->setValue('origin_station', htmlentities($wagontrip_info_detail->get('cf_6304'), ENT_QUOTES, "UTF-8"));
		$this->setValue('destination_station', htmlentities($wagontrip_info_detail->get('cf_6308'), ENT_QUOTES, "UTF-8"));

		$from_to_date='';
		$from_date = $wagontrip_info_detail->get('cf_5806');
		$start = strtotime($wagontrip_info_detail->get('cf_5806'));
		$end = strtotime($wagontrip_info_detail->get('cf_5808'));
		if(!empty($from_date) && $from_date!='0000-00-00')
		{
			$from_to_date = date('d.m.Y', $start).' - '.date('d.m.Y', $end);
		}
		//cf_5806 :: from date
		//cf_5808 :: to date
		$this->setValue('from_to_date',$from_to_date);

		$days_between = ceil(abs($end - $start) / 86400)+1;
		$this->setValue('total_days', $days_between);

		$wagon_info = Vtiger_Record_Model::getInstanceById($wagontrip_info_detail->get('cf_5800'), 'Wagon');

		$wagon_location_id = $owner_wagontrip_user_info->get('location_id');
		//$this->setValue('origincountry',htmlentities($wagontrip_info_detail->get('cf_3237'), ENT_QUOTES, "UTF-8"));
		//$this->setValue('origincity',htmlentities($wagontrip_info_detail->get('cf_3241'), ENT_QUOTES, "UTF-8"));
		//$this->setValue('destinationcountry',htmlentities($wagontrip_info_detail->get('cf_3239'), ENT_QUOTES, "UTF-8"));
		//$this->setValue('destinationcity',htmlentities($wagontrip_info_detail->get('cf_3243'), ENT_QUOTES, "UTF-8"));

		$adb_railway = PearDatabase::getInstance();
		// OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
		$railway_fleet_sql =  "SELECT * FROM `vtiger_railwayfleet`
							INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_railwayfleet.railwayfleetid
							INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid)
 							LEFT JOIN vtiger_railwayfleetcf as vtiger_railwayfleetcf on vtiger_railwayfleetcf.railwayfleetid=vtiger_railwayfleet.railwayfleetid
		 				    WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=?
								  AND vtiger_crmentityrel.module='WagonTrip' AND vtiger_crmentityrel.relmodule='RailwayFleet'
								  ";

		$params_fleet_railway = array($wagontrip_id);
		$result_railway = $adb_railway->pquery($railway_fleet_sql, $params_fleet_railway);
		$allocated_job_no = '';
		for($ij=0; $ij<$adb_railway->num_rows($result_railway); $ij++) {
			$job_ref_id = $adb_railway->query_result($result_railway, $ij, 'cf_5820');
			if($job_ref_id=='RED')
			{
				$job_ref_no = $adb_railway->query_result($result_railway, $ij, 'cf_5884');
			}else{
			$job_ref_no = Vtiger_JobFileList_UIType::getDisplayValue($job_ref_id);
			}
			$internal_selling_local = $adb_railway->query_result($result_railway, $ij, 'cf_5822');
			$CurId = $adb_railway->query_result($result_railway, $ij, 'cf_5824');
			$internal_selling = $adb_railway->query_result($result_railway, $ij, 'cf_5826');
			$railway_fleet_date = $adb_railway->query_result($result_railway, $ij, 'cf_5818');

			if ($CurId) {
			  $q_cur = $adb->pquery('select * from vtiger_currency_info where id = "'.$CurId.'"');
			  $row_cur = $adb->fetch_array($q_cur);
			  $Cur = $row_cur['currency_code'];
			}

			$allocated_job_no .='<tr>
								<td width="216">
								'.date('d-m-Y',strtotime($railway_fleet_date)).'
								</td>
								<td width="216">
								'.$job_ref_no.'
								</td>
								<td>'.$internal_selling_local.'</td>
								<td>'.$Cur.'</td>
								<td width="186">
								'.$origin_city.'
								</td>
								<td width="174">
								'.$destination_city.'
								</td>
								<td width="120">
								'.$internal_selling.'
								</td>
								</tr>';
		}

		$this->setValue('allocated_job_no', $allocated_job_no);


		$adb_railway_RED = PearDatabase::getInstance();
		//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
		$railway_fleet_sql_RED =  "SELECT sum(vtiger_railwayfleetcf.cf_5826) as total_internal_selling FROM `vtiger_railwayfleet`
							INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_railwayfleet.railwayfleetid
							INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
 							LEFT JOIN vtiger_railwayfleetcf as vtiger_railwayfleetcf on vtiger_railwayfleetcf.railwayfleetid=vtiger_railwayfleet.railwayfleetid
		 				    WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=?
								  AND vtiger_crmentityrel.module='WagonTrip' AND vtiger_crmentityrel.relmodule='RailwayFleet'
								  ";


		$params_fleet_railway_RED = array($wagontrip_id);
		$result_railway_RED = $adb_railway_RED->pquery($railway_fleet_sql_RED, $params_fleet_railway_RED);
		$total_internal_selling_RED = $adb_railway_RED->query_result($result_railway_RED, '0', 'total_internal_selling');

		include('include/Exchangerate/exchange_rate_class.php');
		$current_user = Users_Record_Model::getCurrentUserModel();
			$total_revenew = 0;
			$sum_of_cost = 0;
			$sum_of_job_profit = 0;
			$sum_of_internal_selling = 0;
			$profit_share_data = array();

			$pagingModel_1 = new Vtiger_Paging_Model();
			$pagingModel_1->set('page','1');

		$relatedModuleName_1 = 'RailwayFleet';
		$parentRecordModel_1 = $wagontrip_info_detail;
		$relationListView_1 = Vtiger_RelationListView_Model::getInstance($parentRecordModel_1, $relatedModuleName_1, $label);
		$models_1 = $relationListView_1->getEntries($pagingModel_1);


			foreach($models_1 as $key => $model){
				$total_revenew +=$model->get('cf_5826');

				$railway_fleet_id  = $model->getId();
				$sourceModule   = 'RailwayFleet';
				$railwayfleet_info = Vtiger_Record_Model::getInstanceById($railway_fleet_id, $sourceModule);

				$job_id 			  = $model->get('cf_5820');

				if($job_id=='REDJOB')
				{
					$file_title_currency_RED ='KZT';
					$pagingModel_RED = new Vtiger_Paging_Model();
					$pagingModel_RED->set('page','1');

					$relatedModuleName_RED = 'Jobexpencereport';
					$parentRecordModel_RED = $wagontrip_info_detail;
					$relationListView_RED = Vtiger_RelationListView_Model::getInstance($parentRecordModel_RED, $relatedModuleName_RED, $label);
					$models_RED = $relationListView_RED->getEntries($pagingModel_RED);

					foreach($models_RED as $key => $model_RED)
					{
						$expense_record_RED= $model_RED->getInstanceById($model_RED->getId());
						if($model_RED->getDisplayValue('cf_1457') == 'Selling'){
							continue;
						}
						$i++;

						$Cur = $expense_record_RED->getDisplayValue('cf_1345');
						//$invoice_date_RED = $expense_record_RED->get('cf_1216');
						$timestamp_RED = strtotime($model->getDisplayValue('cf_5818'));
						$invoice_date_RED = date('Y-m-d', $timestamp_RED);
						$b_exchange_rate_RED =1;
						if(!empty($invoice_date_RED))
						{
							if($file_title_currency_RED!='USD')
							{
								$b_exchange_rate_RED = currency_rate_convert_kz($file_title_currency_RED, 'USD',  1, $invoice_date_RED);
							}else{
								$b_exchange_rate_RED = currency_rate_convert($file_title_currency_RED, 'USD',  1, $invoice_date_RED);
							}
						}
						$value_in_usd_normal_RED = $expense_record_RED->getDisplayValue('cf_1349');
						if($file_title_currency_RED!='USD')
						{
							$value_in_usd_normal_RED = $expense_record_RED->getDisplayValue('cf_1349')/$b_exchange_rate_RED;
						}

						$value_in_usd_RED = number_format($value_in_usd_normal_RED, 2, '.', '');

						$total_usd_RED += $value_in_usd_RED;
					}

					$internal_selling_forpercent = $model->get('cf_5826');
					$percentage_per_job_RED = ($internal_selling_forpercent*100)/$total_internal_selling_RED;
					$invoice_cost_breakdown_RED = ($total_usd_RED*$percentage_per_job_RED)/100;

					$job_profit_RED = $internal_selling_forpercent - $invoice_cost_breakdown_RED;

					$profit_share_data[] = array('cost' => number_format ( $invoice_cost_breakdown_RED , 2 ,  "." , "," ),
												 'job_profit'  =>  number_format ( $job_profit_RED , 2 ,  "." , "," ),
												 'job_ref_no' => $model->get('cf_5884'),
												 'job_id' => '',
												// 'internal_selling' => @$row_jrer_internal_selling['internal_selling'],
												 'internal_selling' => number_format ( $model->get('cf_5826') , 2 ,  "." , "," ),
												 'internal_selling_forpercent' => $model->get('cf_5826'),
												 'user_id' => $current_user->getId(),
												 'origin_city' => $model->get('cf_5822'),
												 'destination_city' => $model->get('cf_5824'),
												 'selling_created_time' => $model->getDisplayValue('createdtime'),
												 'railway_trip_date' => date('d.m.Y', strtotime($model->getDisplayValue('cf_5818')))
												 );


						$sum_of_cost += $invoice_cost_breakdown_RED;
						$sum_of_job_profit +=$job_profit_RED;
						//$sum_of_internal_selling +=@$row_jrer_internal_selling['internal_selling'];
						$sum_of_internal_selling +=$model->get('cf_5826');
					continue;
				}

				$sourceModule_job 	= 'Job';
				$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule_job);

				$job_reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info->get('cf_1186'));
				$file_title_currency = $job_reporting_currency;

				if($job_info->get('assigned_user_id')!=$railwayfleet_info->get('assigned_user_id'))
				{
					$db = PearDatabase::getInstance();

					$rs_query  = $adb->pquery("select * from vtiger_jobtask
											  where job_id='".$job_id."' and user_id='".$railwayfleet_info->get('assigned_user_id')."' limit 1");
					$row_task = $adb->fetch_array($rs_query);

					if($adb->num_rows($rs_query)>0)
					{
						$file_title_id = $row_task['sub_jrer_file_title'];
						if(empty($file_title_id))
						{
							$job_office_id = $job_info->get('cf_1188');
							$railwayfleet_user_info = Users_Record_Model::getInstanceById($railwayfleet_info->get('assigned_user_id'), 'Users');
							$railwayfleet_user_office_id = $railwayfleet_user_info->get('location_id');

							//if same office then job file title must apply
							if($job_office_id==$railwayfleet_user_office_id){
								$file_title_id = $job_info->get('cf_1186');
							}
							else{
								//by default KZ file title
								$file_title_id = '85757';
							}
						}
						$job_reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$file_title_id);
						$file_title_currency = $job_reporting_currency;
					}
				}

				$adb_buy_local = PearDatabase::getInstance();
				//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
				$sum_buy_local_currency_net =  "SELECT vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
													   vtiger_jobexpencereportcf.cf_1216 as buy_invoice_date,
													   vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
												FROM `vtiger_jobexpencereport`
												INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid
												INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
												left join vtiger_jobexpencereportcf as vtiger_jobexpencereportcf on vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid
												where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? and vtiger_crmentityrel.module='Job'
												and vtiger_crmentityrel.relmodule='Jobexpencereport' and vtiger_jobexpencereportcf.cf_1457='Expence'
												and vtiger_jobexpencereportcf.cf_1477=? and vtiger_jobexpencereportcf.cf_1479=?
												AND vtiger_jobexpencereport.wagontrip_id=?
												AND vtiger_jobexpencereport.railwayfleet_id =?
												AND vtiger_jobexpencereport.owner_id=?
												";

				//$params_buy_local = array($model->get('cf_3175'), '85805', '85844', $fleet_id, $round_trip_id, $roundtrip_info->get('assigned_user_id'));
				$params_buy_local = array($model->get('cf_5820'), $wagon_location_id, '1414775', $wagontrip_id, $railway_fleet_id, $railwayfleet_info->get('assigned_user_id'));
				//$truck_location_id
				$result_buy_locall = $adb_buy_local->pquery($sum_buy_local_currency_net, $params_buy_local);
				$numRows_buy_profit = $adb_buy_local->num_rows($result_buy_locall);
				$cost = 0;
						for($jj=0; $jj< $adb_buy_local->num_rows($result_buy_locall); $jj++ ) {
							$row_jrer_buy_local_currency_net = $adb_buy_local->fetch_row($result_buy_locall,$jj);
							//$row_jrer_buy_local_currency_net = $adb_buy_local->fetch_array($result_buy_locall);

							$cost_local = @$row_jrer_buy_local_currency_net['buy_local_currency_net'];

							$buy_invoice_date = @$row_jrer_buy_local_currency_net['buy_invoice_date'];

							$CurId = $row_jrer_buy_local_currency_net['buy_currency_id'];
							if ($CurId) {
							  $q_cur = $adb->pquery('select * from vtiger_currency_info where id = "'.$CurId.'"');
							  $row_cur = $adb->fetch_array($q_cur);
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

						$adb_internal = PearDatabase::getInstance();
						//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
						$internal_selling_arr = "SELECT vtiger_jobexpcf.cf_1263 as internal_selling
												FROM vtiger_jobexp
												INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexp.jobexpid
 												INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
 												left join vtiger_jobexpcf as vtiger_jobexpcf on vtiger_jobexpcf.jobexpid=vtiger_jobexp.jobexpid
		 					  					where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? and vtiger_crmentityrel.module='Job'
												and vtiger_crmentityrel.relmodule='Jobexp' and vtiger_jobexpcf.cf_1257=? and vtiger_jobexpcf.cf_1259=?
												";

						//$params_internal = array($model->get('cf_3175'), '85805', '85844');
						$params_internal = array($model->get('cf_5820'), $wagon_location_id, '1414775');

						$result_internal = $adb_internal->pquery($internal_selling_arr, $params_internal);
						$row_jrer_internal_selling = $adb_internal->fetch_array($result_internal);

						//$job_profit = @$row_jrer_internal_selling['internal_selling'] - $cost;
						$job_profit = $model->get('cf_5826') - $cost;

						$profit_share_data[] = array('cost' => number_format ( $cost , 2 ,  "." , "," ),
													 'job_profit'  =>  number_format ( $job_profit , 2 ,  "." , "," ),
													 'job_ref_no' => $model->getDisplayValue('cf_5820'),
													 'job_id' => $model->get('cf_5820'),
												 	// 'internal_selling' => @$row_jrer_internal_selling['internal_selling'],
													 'internal_selling' => number_format ( $model->get('cf_5826') , 2 ,  "." , "," ),
													 'internal_selling_forpercent' => $model->get('cf_5826'),
													 'user_id' => $current_user->getId(),
													 'origin_city' => $model->get('cf_5822'),
													 'destination_city' => $model->get('cf_5824'),
													 'selling_created_time' => $model->getDisplayValue('createdtime'),
													 'railway_trip_date' => date('d.m.Y', strtotime($model->getDisplayValue('cf_5818')))
													 );


						$sum_of_cost += $cost;
						$sum_of_job_profit +=$job_profit;
						//$sum_of_internal_selling +=@$row_jrer_internal_selling['internal_selling'];
						$sum_of_internal_selling +=$model->get('cf_5826');

			}


			$allocated_job_no_costing_breakdown = '';
			$total_internal_selling_per_job_kz= 0;

			if(!empty($profit_share_data))
			{
				foreach($profit_share_data as $costing_breakdown)
				{

				$percentage_per_job = ($costing_breakdown['internal_selling_forpercent']*100)/$sum_of_internal_selling;

				//$created_time_internal_selling = $costing_breakdown['selling_created_time'];

				//$timestamp = strtotime($created_time_internal_selling);
				$timestamp = strtotime($costing_breakdown['railway_trip_date']);
				$selling_date = date('Y-m-d', $timestamp);

				$b_exchange_rate_selling = $b_exchange_rate = currency_rate_convert('KZT', 'USD',  1, $selling_date);

				$internal_selling_per_job_kz = $costing_breakdown['internal_selling_forpercent']*$b_exchange_rate_selling;

				$total_internal_selling_per_job_kz +=$internal_selling_per_job_kz;

				$CurId = $costing_breakdown['destination_city'];
				if ($CurId) {
				  $q_cur = $adb->pquery('select * from vtiger_currency_info where id = "'.$CurId.'"');
				  $row_cur = $adb->fetch_array($q_cur);
				  $Cur = $row_cur['currency_code'];
				}

				$allocated_job_no_costing_breakdown .= '<tr>
														<td width="216">
														'.$costing_breakdown['railway_trip_date'].'
														</td>
														<td width="120">
														'.$costing_breakdown['job_ref_no'].'
														</td>
														<td width="150">'.$costing_breakdown['origin_city'].'</td>
														<td width="150">'.$Cur.'</td>
														<td width="120">
														'.$costing_breakdown['internal_selling'].'
														</td>
														<td width="120">
														'.number_format ($percentage_per_job,4).' %
														</td>
														<td width="100">'.number_format ( $internal_selling_per_job_kz , 2 ,  "." , "," ).'</td>
														<td width="100">
														'.$costing_breakdown['cost'].'
														</td>
														<td width="100">
														'.$costing_breakdown['job_profit'].'
														</td>
														</tr>';
				}

			}

		$this->setValue('allocated_job_no_costing_breakdown', $allocated_job_no_costing_breakdown);
		$this->setValue('total_internal_selling', number_format ( $sum_of_internal_selling , 2 ,  "." , "," ));
		$this->setValue('total_internal_selling_KZT', number_format ( $total_internal_selling_per_job_kz , 2 ,  "." , "," ));
		$this->setValue('total_costing', number_format ( $sum_of_cost , 2 ,  "." , "," ));
		$this->setValue('total_profit', number_format ( $sum_of_job_profit , 2 ,  "." , "," ));



		$pagingModel = new Vtiger_Paging_Model();
		$pagingModel->set('page','1');

		$relatedModuleName = 'Jobexpencereport';
		$parentRecordModel = $wagontrip_info_detail;
		$relationListView = Vtiger_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName, $label);
		$models = $relationListView->getEntries($pagingModel);



		//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
		$jrer_sum_sql =  "SELECT sum(vtiger_jobexpencereportcf.cf_1347) as buy_local_currency_gross,
								     sum(vtiger_jobexpencereportcf.cf_1349) as buy_local_currency_net,
									 sum(vtiger_jobexpencereportcf.cf_1351) as expected_buy_local_currency_net,
									 sum(vtiger_jobexpencereportcf.cf_1353) as variation_expected_and_actual_buying
									 FROM `vtiger_jobexpencereport`
							  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid
 		INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
 		LEFT JOIN vtiger_jobexpencereportcf as vtiger_jobexpencereportcf on vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid
		 					 WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=?
							 AND vtiger_crmentityrel.module='WagonTrip'
							 AND vtiger_crmentityrel.relmodule='Jobexpencereport'
							 AND vtiger_jobexpencereportcf.cf_1457='Expence' AND vtiger_jobexpencereport.owner_id='".$wagontrip_info_detail->get('assigned_user_id')."'";
		$parentId = $wagontrip_id;
		$params = array($parentId);
		$result = $adb->pquery($jrer_sum_sql, $params);
		$row_job_jrer = $adb->fetch_array($result);

		$BUY_LOCAL_CURRENCY_GROSS = number_format ( $row_job_jrer['buy_local_currency_gross'] , 2 ,  "." , ",");
		$BUY_LOCAL_CURRENCY_NET   = number_format ( $row_job_jrer['buy_local_currency_net'] , 2 ,  "." , "," );
		$EXPECTED_BUY_LOCAL_CURRENCY_NET  = number_format ( $row_job_jrer['expected_buy_local_currency_net'] , 2 ,  "." , "," );
		$VARIATION_EXPECTED_AND_ACTUAL_BUYING = number_format ( $row_job_jrer['variation_expected_and_actual_buying'] , 2 ,  "." , "," );

		$assigned_user_info =  Users_Record_Model::getInstanceById($wagontrip_info_detail->get('assigned_user_id'), 'Users');
		$current_user_office_id = $assigned_user_info->get('location_id');
		//For checking user is main owner or sub user
		$company_id = $assigned_user_info->get('company_id');

		$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency($company_id);

		$file_title_currency = $reporting_currency;

		//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
		 $jrer_sql_expense =  "SELECT vtiger_jobexpencereportcf.cf_1347 as buy_local_currency_gross,
								     vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
									 vtiger_jobexpencereportcf.cf_1351 as expected_buy_local_currency_net,
									 vtiger_jobexpencereportcf.cf_1353 as variation_expected_and_actual_buying,
									 vtiger_jobexpencereportcf.cf_1216 as expense_invoice_date,
									 vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
									 FROM `vtiger_jobexpencereport`
							  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid
 			INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
 			LEFT JOIN vtiger_jobexpencereportcf as vtiger_jobexpencereportcf on vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid
		 					 WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=?
							 AND vtiger_crmentityrel.module='WagonTrip'
							 AND vtiger_crmentityrel.relmodule='Jobexpencereport'
							 AND vtiger_jobexpencereportcf.cf_1457='Expence' AND vtiger_jobexpencereport.owner_id='".$wagontrip_info_detail->get('assigned_user_id')."'";

				$parentId = $wagontrip_id;

				$params = array($parentId);
				$result_expense = $adb->pquery($jrer_sql_expense, $params);
				$numRows_expnese = $adb->num_rows($result_expense);

				$total_cost_in_usd_gross = 0;
				$total_cost_in_usd_net = 0;
				$total_expected_cost_in_usd_net = 0;
				$total_variation_expected_and_actual_buying_cost_in_usd = 0;

				if($numRows_expnese>0)
				{
					for($ii=0; $ii< $adb->num_rows($result_expense); $ii++ ) {
						$row_job_jrer_expense = $adb->fetch_row($result_expense,$ii);
						$expense_invoice_date = $row_job_jrer_expense['expense_invoice_date'];

						$CurId = $row_job_jrer_expense['buy_currency_id'];
						if ($CurId) {
						  $q_cur = $adb->pquery('select * from vtiger_currency_info where id = "'.$CurId.'"');
						  $row_cur = $adb->fetch_array($q_cur);
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
						}else{
							if($b_exchange_rate==0)
							{
								$b_exchange_rate = 1;
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

		/*$total_cost_in_usd_gross = $row_job_jrer['buy_local_currency_gross']/$final_exchange_rate;
		$total_cost_in_usd_net = $row_job_jrer['buy_local_currency_net']/$final_exchange_rate;
		$total_expected_cost_in_usd_net = $row_job_jrer['expected_buy_local_currency_net']/$final_exchange_rate;
		$total_variation_expected_and_actual_buying_cost_in_usd = $row_job_jrer['variation_expected_and_actual_buying']/$final_exchange_rate;*/

		$TOTAL_COST_USD_GROSS = number_format ( $total_cost_in_usd_gross , 2 ,  "." , "," );
		$TOTAL_COST_IN_USD_NET = number_format ( $total_cost_in_usd_net , 2 ,  "." , "," );
		$TOTAL_EXPECTED_COST_USD_NET = number_format ( $total_expected_cost_in_usd_net , 2 ,  "." , "," );
		$TOTAL_VARIATION_EXPECTED_AND_ACTUAL_BUYING_COST_IN_USD = number_format ( $total_variation_expected_and_actual_buying_cost_in_usd , 2 ,  "." , "," );


		$i=0;
		$total_usd = 0;
		$buyingsubtotalR = 0;
		$buyingsubtotalN = 0;
		$buying = '<table border="1" cellspacing="0" cellpadding="0" width="100%" >
			<tbody>
				<tr>
					<td width="19" align="center">

						<strong><u>#</u></strong>
					</td>
					<td width="62" align="center">
						<h6><u>Name</u></h6>
					</td>
					<td width="66" align="center">
						<h6><u>Service</u></h6>
					</td>
					<td width="57" align="center">
						<h6><u>Invoice</u></h6>
					</td>
					<td width="47" align="center">
						<h6><u>Date</u></h6>
					</td>
					<td width="47" align="center">
						<h6><u>Dept</u></h6>
					</td>
					<td width="57" align="center">
						<h6><u>Type</u></h6>
					</td>
					<td width="57" align="center">
						<h6><u>Buy (Vendor cur net)</u></h6>
					</td>
					<td width="41" align="center">
						<h6><u>VAT Rate</u></h6>
					</td>
					<td width="42" align="center">
						<h6><u>VAT</u></h6>
					</td>
					<td width="72" align="center">
						<h6><u>Buy (cur gross)</u></h6>
					</td>
					<td width="36" align="center">
						<h6><u>Cur</u></h6>
					</td>
					<td width="48" align="center">
						<h6><u>Exch. Rate</u></h6>
					</td>
					<td width="60" align="center">
						<h6><u>Buy (loc cur gross)</u></h6>
					</td>
					<td width="60" align="center">
						<h6><u>Buy (loc cur net)</u></h6>
					</td>
					<td width="66" align="center">
						<h6><u>Expected buy (cur net)</u></h6>
					</td>
					<td width="44" align="center">
						<h6><u>Var exp and act buying</u></h6>
					</td>
					<td width="60" align="center">
						<h6><u>Total, $</u></h6>
					</td>
				</tr>
				<tr>
					<td valign="top" width="19">
					</td>
					<td valign="top" width="62">
					</td>
					<td valign="top" width="66">
					</td>
					<td valign="top" width="57">
					</td>
					<td valign="top" width="47">
					</td>
					<td valign="top" width="47">
					</td>
					<td valign="top" width="57">
					</td>
					<td valign="top" width="57">
					</td>
					<td valign="top" width="41">
					</td>
					<td valign="top" width="42">
					</td>
					<td valign="top" width="72">
					</td>
					<td valign="top" width="36">
					</td>
					<td valign="top" width="48">
					</td>
					<td valign="top" width="60">
					</td>
					<td valign="top" width="60">
					</td>
					<td valign="top" width="66">
					</td>
					<td valign="top" width="44">
					</td>
					<td valign="top" width="60">
					</td>
				</tr>
				';

		foreach($models as $key => $model)
		{
			$expense_record= $model->getInstanceById($model->getId());
			if($model->getDisplayValue('cf_1457') == 'Selling'){
				continue;
			}
			$i++;

			$Cur = $expense_record->getDisplayValue('cf_1345');
			$invoice_date = $expense_record->get('cf_1216');
			$b_exchange_rate = $final_exchange_rate;
			if(!empty($invoice_date))
			{
				if($file_title_currency!='USD')
				{
					$b_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $invoice_date);
				}else{
					$b_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $invoice_date);
				}
			}
			$value_in_usd_normal = $expense_record->getDisplayValue('cf_1349');
			if($file_title_currency!='USD')
			{
				$value_in_usd_normal = $expense_record->getDisplayValue('cf_1349')/$b_exchange_rate;
			}

			$value_in_usd = number_format($value_in_usd_normal,2,'.','');

			$AccType = $expense_record->getDisplayValue('cf_1214');
			if (substr($AccType, -1) == 'R') {
			$buyingsubtotalR = $buyingsubtotalR + $value_in_usd_normal;}
			if ((substr($AccType, -1) == 'N') or (substr($AccType, -1) == 'D')) {
			$buyingsubtotalN = $buyingsubtotalN + $value_in_usd_normal; }

			$pay_to_id = $expense_record->get('cf_1367');
			$company_accountname = '';
			if(!empty($pay_to_id))
			{
				$crmentity_check_ =  "SELECT vtiger_crmentity.crmid as crmid,  vtiger_crmentity.label  as label, vtiger_crmentity.deleted  as deleted from vtiger_crmentity where crmid=?  ";
				$params = array($bill_to_id);
				$result_crmentity_check_ = $adb->pquery($crmentity_check_, $params);
				$numRows_crmentity_check_ = $adb->num_rows($result_crmentity_check_);
				$row_crmentity_check_ = $adb->fetch_array($result_crmentity_check_);

				if($row_crmentity_check_['deleted']==0)
				{
				$paytoinfo = Vtiger_Record_Model::getInstanceById($pay_to_id, 'Accounts');
				$company_accountname = @$paytoinfo->get('accountname');
				}
				else{
					$company_accountname =$row_crmentity_check_['label'].' -Deleted';
				}
			}

			$total_usd += $value_in_usd;

			$invoice_amount = $expense_record->get('cf_1343');

			$buying .='<tr>
            <td valign="top" width="19">
                '.$i.'
            </td>
            <td valign="top" width="66">
                '.$company_accountname.'
            </td>
            <td valign="top" width="66">
                '.$expense_record->getDisplayValue('cf_1453').'
            </td>
            <td valign="top" width="57">
                '.$expense_record->getDisplayValue('cf_1212').'
            </td>
            <td valign="top" width="47" align="center">
                '.$expense_record->getDisplayValue('cf_1216').'
            </td>
            <td valign="top" width="47" align="center">
              '.$expense_record->getDisplayValue('cf_1477').' '.$expense_record->getDisplayValue('cf_1479').'
            </td>
            <td valign="top" width="57" align="center">
                '.$expense_record->getDisplayValue('cf_1214').'
            </td>
            <td valign="top" width="57" align="right">
                 '.number_format ( $expense_record->getDisplayValue('cf_1337') , 2 ,  "." , "," ).'
            </td>
            <td valign="top" width="41" align="center">
                 '.$expense_record->getDisplayValue('cf_1339').'
            </td>
            <td valign="top" width="42" align="right">
                '.$expense_record->getDisplayValue('cf_1341').'
            </td>
            <td valign="top" width="72" align="right">
                '.number_format ( $expense_record->getDisplayValue('cf_1343') , 2 ,  "." , "," ).'
            </td>
            <td valign="top" width="36" align="center">
                '.$expense_record->getDisplayValue('cf_1345').'
            </td>
            <td valign="top" width="48" align="right">
                '.number_format($expense_record->getDisplayValue('cf_1222'), 2).'
            </td>
            <td valign="top" width="60" align="right">
                '.number_format ( $expense_record->getDisplayValue('cf_1347') , 2 ,  "." , "," ).'
            </td>
            <td valign="top" width="60" align="right">
                '.number_format ( $expense_record->getDisplayValue('cf_1349') , 2 ,  "." , "," ).'
            </td>
            <td valign="top" width="66" align="right">
                '.$expense_record->getDisplayValue('cf_1351').'
            </td>
            <td valign="top" width="44" align="right">
                '.$expense_record->getDisplayValue('cf_1353').'
            </td>
            <td valign="top" width="60" align="right">
                '.$value_in_usd.'
            </td>
        </tr>';
		//For Costing breakdown
		$sum_invoice_cost_breakdown = 0;
		$pv_invCurrency = $expense_record->getDisplayValue('cf_1345');
		if(!empty($profit_share_data))
			{
				$buying .='<tr> <td  colspan="18" align="center"><table border=1 cellspacing=0 cellpadding=5  width="100%"><tbody>
                <tr>
                <td width="216">
                <strong>Job File </strong>
                </td>

                <td width="135">
                <strong>Costing Breakdown </strong> '.$pv_invCurrency.'
                </td>
                </tr> ';
				$allocated_job_no_costing_breakdown='';
				foreach($profit_share_data as $costing_breakdown)
				{

				$percentage_per_job = ($costing_breakdown['internal_selling_forpercent']*100)/$sum_of_internal_selling;
				$invoice_cost_breakdown = ($invoice_amount*$percentage_per_job)/100;

				$sum_invoice_cost_breakdown+=$invoice_cost_breakdown;

				$allocated_job_no_costing_breakdown .= '<tr>
														<td width="216">
														'.$costing_breakdown['job_ref_no'].'
														</td>

														<td width="135">
														'.number_format ( $invoice_cost_breakdown , 2 ,  "." , "," ).'
														</td>
														</tr>';


				}
				$buying .=$allocated_job_no_costing_breakdown;
				$buying .=' <tr>
							<td width="216">
							<strong></strong>
							</td>

							<td width="135">
							<strong>'.number_format ( $sum_invoice_cost_breakdown , 2 ,  "." , "," ).'</strong> '.$pv_invCurrency.'
							</td>
							</tr>
							</tbody></table></td></tr>';
			}



		}
		$buying .='<tr>
            <td valign="top" width="19">
            </td>
            <td valign="top" width="62">
            </td>
            <td valign="top" width="66">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="47">
            </td>
            <td valign="top" width="47">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="41">
            </td>
            <td valign="top" width="42">
            </td>
            <td valign="top" width="72">
            </td>
            <td valign="top" width="60">
            </td>
            <td valign="top" width="36">
            </td>
            <td valign="top" width="48">
            </td>
            <td valign="top" width="60">
            </td>
            <td valign="top" width="60">
            </td>
            <td valign="top" width="66">
            </td>
            <td valign="top" width="44">
            </td>
        </tr>
        <tr>
            <td valign="top" width="19">
            </td>
            <td valign="top" width="62">
            </td>
            <td valign="top" width="66" align="right" colspan="3">
                <h6><u>Sub Total</u></h6>
            </td>
            <td valign="top" width="47">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="41">
            </td>
            <td valign="top" width="42">
            </td>
            <td valign="top" width="72">
            </td>
            <td valign="top" width="36">
            </td>
            <td valign="top" width="48">
            </td>
            <td valign="top" width="60">
            </td>
            <td valign="top" width="60">
            </td>
            <td valign="top" width="66">
            </td>
            <td valign="top" width="44">
            </td>
            <td valign="top" width="60" align="right">
                <h6><u>'.$total_usd.'</u></h6>
            </td>
        </tr>
        <tr>
            <td valign="top" width="19">
            </td>
            <td valign="top" width="62">
            </td>
            <td valign="top" width="66" align="right" colspan="3">
                <h6><u>Total Local Currency</u></h6>
            </td>

            <td valign="top" width="47">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="41">
            </td>
            <td valign="top" width="42">
            </td>
            <td valign="top" width="72">
            </td>
            <td valign="top" width="36">
            </td>
            <td valign="top" width="48">
            </td>
            <td valign="top" width="60" align="right">
                <h6><u>'.$BUY_LOCAL_CURRENCY_GROSS.'</u></h6>
            </td>
            <td valign="top" width="60" align="right">
                <h6><u>'.$BUY_LOCAL_CURRENCY_NET.'</u></h6>
            </td>
            <td valign="top" width="66" align="right">
                <h6><u>'.$EXPECTED_BUY_LOCAL_CURRENCY_NET.'</u></h6>
            </td>
            <td valign="top" width="44" align="right">
                <h6><u>'.$VARIATION_EXPECTED_AND_ACTUAL_BUYING.'</u></h6>
            </td>
            <td valign="top" width="60">
            </td>
        </tr>

        <tr>
            <td valign="top" width="19">
            </td>
            <td valign="top" width="62">
            </td>
            <td valign="top" width="66" align="right" colspan="3">
                <h6><u>Total Cost USD</u></h6>
            </td>

            <td valign="top" width="47">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="41">
            </td>
            <td valign="top" width="42">
            </td>
            <td valign="top" width="72">
            </td>
            <td valign="top" width="36">
            </td>
            <td valign="top" width="48">
            </td>
            <td valign="top" width="60" align="right">
                <h6><u>'.$TOTAL_COST_USD_GROSS.'</u></h6>
            </td>
            <td valign="top" width="60" align="right">
                <h6><u>'.$TOTAL_COST_IN_USD_NET.'</u></h6>
            </td>
            <td valign="top" width="66" align="right">
                <h6><u>'.$TOTAL_EXPECTED_COST_USD_NET.'</u></h6>
            </td>
            <td valign="top" width="44" align="right">
                <h6><u>'.$TOTAL_VARIATION_EXPECTED_AND_ACTUAL_BUYING_COST_IN_USD.'</u></h6>
            </td>
            <td valign="top" width="60">
            </td>
        </tr></tbody></table>';

		$this->setValue('railway_expense_table', $buying);

		$this->setValue('buyingsubtotalR', number_format ( $buyingsubtotalR , 2 ,  "." , "," ));
		$this->setValue('buyingsubtotalN', number_format ( $buyingsubtotalN , 2 ,  "." , "," ));
		$this->setValue('buyingtotal', $total_usd);

		include('include/mpdf60/mpdf.php');
		 @date_default_timezone_set($current_user->get('time_zone'));
  		$mpdf = new mPDF('utf-8', 'A4-L', '10', '', 10, 10, 30, 15, 10, 5); /*задаем формат, отступы и.т.д.*/
  		$mpdf->charset_in = 'utf8';

		$mpdf->list_indent_first_level = 0;

		//$mpdf->SetDefaultFontSize(12);
		//$mpdf->setAutoTopMargin(2);
		$mpdf->SetHTMLHeader('<table width="100%" cellpadding="0" cellspacing="0">
		<tr><td align="right" style="font-size:9;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">WCR Form, GLOBALINK, designed: March, 2010</td></tr>
		<tr><td align="right"><img src="include/calendar_logo.jpg"/></td></tr></table>');
				$mpdf->SetHTMLFooter('<table width="100%" cellpadding="0" cellspacing="0">
		<tr><td width="40%" align="left" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">Printed: '.date('d.m.Y; H:i').' by '.$current_user->get('user_name').'</td>
		<td width="20%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">Page {PAGENO} of {nbpg}</td>
		<td width="40%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">&nbsp;</td>
		</table>');
		$stylesheet = file_get_contents('include/mpdf60/examples/mpdfstyletables.css');
		$mpdf->WriteHTML($stylesheet,1);	// The parameter 1 tells that this is css/style only and no body/html/text
		$mpdf->WriteHTML($this->_documentXML); /*формируем pdf*/


		$pdf_name = 'pdf_docs/wagon_expense.pdf';

		$mpdf->Output($pdf_name, 'F');
		//header('Location:http://mb.globalink.net/vt60/'.$pdf_name);
		header('Location:'.$pdf_name);
		exit;

	}

	public function print_round_trip_expense($request)
	{
		global $adb;
		$moduleName = $request->getModule();
		$record = $request->get('record');

		$fleet_id = $record;
		$current_user = Users_Record_Model::getCurrentUserModel();

		$fleet_info_detail = Vtiger_Record_Model::getInstanceById($fleet_id, 'Fleettrip');

		$document = $this->loadTemplate('printtemplates/fleettrip_expense.html');

		$driver_user_info = Users_Record_Model::getInstanceById($fleet_info_detail->get('cf_3167'), 'Users');

		$owner_fleet_user_info = Users_Record_Model::getInstanceById($fleet_info_detail->get('assigned_user_id'), 'Users');
		$fleet_trip_user_company_id = $owner_fleet_user_info->get('company_id');
		$fleet_trip_user_local_currency_code = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$fleet_trip_user_company_id);

		$this->setValue('user_local_currency_code', $fleet_trip_user_local_currency_code);

		$this->setValue('useroffice',$owner_fleet_user_info->getDisplayValue('location_id'));
		$this->setValue('userdepartment',$owner_fleet_user_info->getDisplayValue('department_id'));
		$this->setValue('mobile',$owner_fleet_user_info->get('phone_mobile'));
		$this->setValue('fax',$owner_fleet_user_info->get('phone_fax'));
		$this->setValue('email',htmlentities($owner_fleet_user_info->get('email1'), ENT_QUOTES, "UTF-8"));
		$this->setValue('cityname',htmlentities($owner_fleet_user_info->getDisplayValue('location_id'), ENT_QUOTES, "UTF-8"));
		$this->setValue('countryname',htmlentities($owner_fleet_user_info->get('address_country'), ENT_QUOTES, "UTF-8"));
		$this->setValue('departmentcode',htmlentities($owner_fleet_user_info->getDisplayValue('department_id'), ENT_QUOTES, "UTF-8"));
		$this->setValue('dateadded',date('d.m.Y', strtotime($fleet_info_detail->get('createdtime'))));
		//$this->setValue('billingto', $pay_to_info->get('accountname'));
		$this->setValue('from', htmlentities($owner_fleet_user_info->get('first_name').' '.$owner_fleet_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));

		//$this->setValue('refno', $job_info_detail->get('cf_1198'));
		$this->setValue('fleet_ref_no', $fleet_info_detail->get('cf_3283'));

		$this->setValue('truckno', htmlentities($fleet_info_detail->getDisplayValue('cf_3165'), ENT_QUOTES, "UTF-8"));
		$this->setValue('driver', htmlentities($driver_user_info->get('first_name').' '.$driver_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));
		$this->setValue('grossweight', htmlentities($fleet_info_detail->get('cf_3229'), ENT_QUOTES, "UTF-8"));
		$this->setValue('volumeweight', htmlentities($fleet_info_detail->get('cf_3231'), ENT_QUOTES, "UTF-8"));

		$this->setValue('pieces', htmlentities($fleet_info_detail->get('cf_3227'), ENT_QUOTES, "UTF-8"));

		$truck_info = Vtiger_Record_Model::getInstanceById($fleet_info_detail->get('cf_3165'), 'Truck');
		$truck_location_id = $truck_info->get('cf_1913');
		$this->setValue('trucktype',htmlentities($truck_info->getDisplayValue('cf_1911'), ENT_QUOTES, "UTF-8"));

		$this->setValue('origincountry',htmlentities($fleet_info_detail->get('cf_3237'), ENT_QUOTES, "UTF-8"));
		$this->setValue('origincity',htmlentities($fleet_info_detail->get('cf_3241'), ENT_QUOTES, "UTF-8"));
		$this->setValue('destinationcountry',htmlentities($fleet_info_detail->get('cf_3239'), ENT_QUOTES, "UTF-8"));
		$this->setValue('destinationcity',htmlentities($fleet_info_detail->get('cf_3243'), ENT_QUOTES, "UTF-8"));

		$adb_round = PearDatabase::getInstance();
		// OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
		$fleet_round_sql =  "SELECT * FROM `vtiger_roundtrip`
							INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_roundtrip.roundtripid
							INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid)
 							LEFT JOIN vtiger_roundtripcf as vtiger_roundtripcf on vtiger_roundtripcf.roundtripid=vtiger_roundtrip.roundtripid
		 				    WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=?
								  AND vtiger_crmentityrel.module='Fleettrip' AND vtiger_crmentityrel.relmodule='Roundtrip'
								  ";

		$params_fleet_roundtrip = array($fleet_id);
		$result_round = $adb_round->pquery($fleet_round_sql, $params_fleet_roundtrip);
		$allocated_job_no = '';
		for($ij=0; $ij<$adb_round->num_rows($result_round); $ij++) {
			$job_ref_id = $adb_round->query_result($result_round, $ij, 'cf_3175');
			$job_ref_no = Vtiger_JobFileList_UIType::getDisplayValue($job_ref_id);
			$origin_city = $adb_round->query_result($result_round, $ij, 'cf_3169');
			$destination_city = $adb_round->query_result($result_round, $ij, 'cf_3171');
			$internal_selling = $adb_round->query_result($result_round, $ij, 'cf_3173');
			$round_trip_date = $adb_round->query_result($result_round, $ij, 'cf_5766');



			$allocated_job_no .='<tr>
								<td width="216">
								'.date('d-m-Y',strtotime($round_trip_date)).'
								</td>
								<td width="216">
								'.$job_ref_no.'
								</td>
								<td>'.$origin_city.'</td>
								<td>'.$destination_city.'</td>
								<td width="186">
								'.$origin_city.'
								</td>
								<td width="174">
								'.$destination_city.'
								</td>
								<td width="120">
								'.$internal_selling.'
								</td>
								</tr>';
		}

		$this->setValue('allocated_job_no', $allocated_job_no);

		include('include/Exchangerate/exchange_rate_class.php');
		$current_user = Users_Record_Model::getCurrentUserModel();
			$total_revenew = 0;
			$sum_of_cost = 0;
			$sum_of_job_profit = 0;
			$sum_of_internal_selling = 0;
			$profit_share_data = array();

			$pagingModel_1 = new Vtiger_Paging_Model();
			$pagingModel_1->set('page','1');

		$relatedModuleName_1 = 'Roundtrip';
		$parentRecordModel_1 = $fleet_info_detail;
		$relationListView_1 = Vtiger_RelationListView_Model::getInstance($parentRecordModel_1, $relatedModuleName_1, $label);
		$models_1 = $relationListView_1->getEntries($pagingModel_1);


			foreach($models_1 as $key => $model){
				$total_revenew +=$model->get('cf_3173');

				$round_trip_id  = $model->getId();
				$sourceModule   = 'Roundtrip';
				$roundtrip_info = Vtiger_Record_Model::getInstanceById($round_trip_id, $sourceModule);

				$job_id 			  = $model->get('cf_3175');

				$sourceModule_job 	= 'Job';
				$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule_job);

				$job_reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info->get('cf_1186'));
				$file_title_currency = $job_reporting_currency;

				if($job_info->get('assigned_user_id')!=$roundtrip_info->get('assigned_user_id'))
				{
					$db = PearDatabase::getInstance();

					$rs_query  = $adb->pquery("select * from vtiger_jobtask
											  where job_id='".$job_id."' and user_id='".$roundtrip_info->get('assigned_user_id')."' limit 1");
					$row_task = $adb->fetch_array($rs_query);

					if($adb->num_rows($rs_query)>0)
					{
						$file_title_id = $row_task['sub_jrer_file_title'];
						if(empty($file_title_id))
						{
							$job_office_id = $job_info->get('cf_1188');
							$roundtrip_user_info = Users_Record_Model::getInstanceById($roundtrip_info->get('assigned_user_id'), 'Users');
							$roundtrip_user_office_id = $roundtrip_user_info->get('location_id');

							//if same office then job file title must apply
							if($job_office_id==$roundtrip_user_office_id){
								$file_title_id = $job_info->get('cf_1186');
							}
							else{
								//by default KZ file title
								//$file_title_id = '85757';
								$file_title_id = $fleet_trip_user_company_id;
							}
						}
						$job_reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$file_title_id);

						$file_title_currency = $job_reporting_currency;
					}
				}

				$adb_buy_local = PearDatabase::getInstance();
				//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
				$sum_buy_local_currency_net =  "SELECT vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
													   vtiger_jobexpencereportcf.cf_1216 as buy_invoice_date,
													   vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
												FROM `vtiger_jobexpencereport`
												INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid
												INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
												left join vtiger_jobexpencereportcf as vtiger_jobexpencereportcf on vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid
												where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? and vtiger_crmentityrel.module='Job'
												and vtiger_crmentityrel.relmodule='Jobexpencereport' and vtiger_jobexpencereportcf.cf_1457='Expence'
												and vtiger_jobexpencereportcf.cf_1477=? and vtiger_jobexpencereportcf.cf_1479=?
												AND vtiger_jobexpencereport.fleettrip_id=?
												AND vtiger_jobexpencereport.roundtrip_id =?
												AND vtiger_jobexpencereport.owner_id=?
												";

				//$params_buy_local = array($model->get('cf_3175'), '85805', '85844', $fleet_id, $round_trip_id, $roundtrip_info->get('assigned_user_id'));
				$params_buy_local = array($model->get('cf_3175'), $truck_location_id, '85844', $fleet_id, $round_trip_id, $roundtrip_info->get('assigned_user_id'));
				//$truck_location_id
				$result_buy_locall = $adb_buy_local->pquery($sum_buy_local_currency_net, $params_buy_local);
				$numRows_buy_profit = $adb_buy_local->num_rows($result_buy_locall);
				$cost = 0;
						for($jj=0; $jj< $adb_buy_local->num_rows($result_buy_locall); $jj++ ) {
							$row_jrer_buy_local_currency_net = $adb_buy_local->fetch_row($result_buy_locall,$jj);
							//$row_jrer_buy_local_currency_net = $adb_buy_local->fetch_array($result_buy_locall);

							$cost_local = @$row_jrer_buy_local_currency_net['buy_local_currency_net'];

							$buy_invoice_date = @$row_jrer_buy_local_currency_net['buy_invoice_date'];

							$CurId = $row_jrer_buy_local_currency_net['buy_currency_id'];
							if ($CurId) {
							  $q_cur = $adb->pquery('select * from vtiger_currency_info where id = "'.$CurId.'"');
							  $row_cur = $adb->fetch_array($q_cur);
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

						$adb_internal = PearDatabase::getInstance();
						//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
						$internal_selling_arr = "SELECT vtiger_jobexpcf.cf_1263 as internal_selling
												FROM vtiger_jobexp
												INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexp.jobexpid
 												INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
 												left join vtiger_jobexpcf as vtiger_jobexpcf on vtiger_jobexpcf.jobexpid=vtiger_jobexp.jobexpid
		 					  					where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? and vtiger_crmentityrel.module='Job'
												and vtiger_crmentityrel.relmodule='Jobexp' and vtiger_jobexpcf.cf_1257=? and vtiger_jobexpcf.cf_1259=?
												";

						//$params_internal = array($model->get('cf_3175'), '85805', '85844');
						$params_internal = array($model->get('cf_3175'), $truck_location_id, '85844');

						$result_internal = $adb_internal->pquery($internal_selling_arr, $params_internal);
						$row_jrer_internal_selling = $adb_internal->fetch_array($result_internal);

						//$job_profit = @$row_jrer_internal_selling['internal_selling'] - $cost;
						$job_profit = $model->get('cf_3173') - $cost;

						$profit_share_data[] = array('cost' => number_format ( $cost , 2 ,  "." , "," ),
													 'job_profit'  =>  number_format ( $job_profit , 2 ,  "." , "," ),
													 'job_ref_no' => $model->getDisplayValue('cf_3175'),
													 'job_id' => $model->get('cf_3175'),
												 	// 'internal_selling' => @$row_jrer_internal_selling['internal_selling'],
													 'internal_selling' => number_format ( $model->get('cf_3173') , 2 ,  "." , "," ),
													 'internal_selling_forpercent' => $model->get('cf_3173'),
													 'user_id' => $current_user->getId(),
													 'origin_city' => $model->get('cf_3169'),
													 'destination_city' => $model->get('cf_3171'),
													 'selling_created_time' => $model->getDisplayValue('createdtime'),
													 'round_trip_date' => date('d.m.Y', strtotime($model->getDisplayValue('cf_5766')))
													 );


						$sum_of_cost += $cost;
						$sum_of_job_profit +=$job_profit;
						//$sum_of_internal_selling +=@$row_jrer_internal_selling['internal_selling'];
						$sum_of_internal_selling +=$model->get('cf_3173');

			}



			$allocated_job_no_costing_breakdown = '';
			$total_internal_selling_per_job_kz= 0;
			if(!empty($profit_share_data))
			{
				foreach($profit_share_data as $costing_breakdown)
				{


				$percentage_per_job = ($costing_breakdown['internal_selling_forpercent']*100)/$sum_of_internal_selling;

				//$created_time_internal_selling = $costing_breakdown['selling_created_time'];

				//$timestamp = strtotime($created_time_internal_selling);
				$timestamp = strtotime($costing_breakdown['round_trip_date']);
				$selling_date = date('Y-m-d', $timestamp);

				//$b_exchange_rate_selling = $b_exchange_rate = currency_rate_convert('KZT', 'USD',  1, $selling_date);
				$b_exchange_rate_selling = $b_exchange_rate = currency_rate_convert($fleet_trip_user_local_currency_code, 'USD',  1, $selling_date);

				$internal_selling_per_job_kz = $costing_breakdown['internal_selling_forpercent']*$b_exchange_rate_selling;

				$total_internal_selling_per_job_kz +=$internal_selling_per_job_kz;

				$allocated_job_no_costing_breakdown .= '<tr>
														<td width="216">
														'.$costing_breakdown['round_trip_date'].'
														</td>
														<td width="120">
														'.$costing_breakdown['job_ref_no'].'
														</td>
														<td width="150">'.$costing_breakdown['origin_city'].'</td>
														<td width="150">'.$costing_breakdown['destination_city'].'</td>
														<td width="120">
														'.$costing_breakdown['internal_selling'].'
														</td>
														<td width="120">
														'.number_format ($percentage_per_job,4).' %
														</td>
														<td width="100">'.number_format ( $internal_selling_per_job_kz , 2 ,  "." , "," ).'</td>
														<td width="100">
														'.$costing_breakdown['cost'].'
														</td>
														<td width="100">
														'.$costing_breakdown['job_profit'].'
														</td>
														</tr>';
				}

			}


		$this->setValue('allocated_job_no_costing_breakdown', $allocated_job_no_costing_breakdown);
		$this->setValue('total_internal_selling', number_format ( $sum_of_internal_selling , 2 ,  "." , "," ));
		$this->setValue('total_internal_selling_KZT', number_format ( $total_internal_selling_per_job_kz , 2 ,  "." , "," ));
		$this->setValue('total_costing', number_format ( $sum_of_cost , 2 ,  "." , "," ));
		$this->setValue('total_profit', number_format ( $sum_of_job_profit , 2 ,  "." , "," ));



		$pagingModel = new Vtiger_Paging_Model();
		$pagingModel->set('page','1');

		$relatedModuleName = 'Jobexpencereport';
		$parentRecordModel = $fleet_info_detail;
		$relationListView = Vtiger_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName, $label);
		$models = $relationListView->getEntries($pagingModel);



		//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
		$jrer_sum_sql =  "SELECT sum(vtiger_jobexpencereportcf.cf_1347) as buy_local_currency_gross,
								     sum(vtiger_jobexpencereportcf.cf_1349) as buy_local_currency_net,
									 sum(vtiger_jobexpencereportcf.cf_1351) as expected_buy_local_currency_net,
									 sum(vtiger_jobexpencereportcf.cf_1353) as variation_expected_and_actual_buying
									 FROM `vtiger_jobexpencereport`
							  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid
 		INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
 		LEFT JOIN vtiger_jobexpencereportcf as vtiger_jobexpencereportcf on vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid
		 					 WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=?
							 AND vtiger_crmentityrel.module='Fleettrip'
							 AND vtiger_crmentityrel.relmodule='Jobexpencereport'
							 AND vtiger_jobexpencereportcf.cf_1457='Expence' AND vtiger_jobexpencereport.owner_id='".$fleet_info_detail->get('assigned_user_id')."'";
		$parentId = $fleet_id;
		$params = array($parentId);
		$result = $adb->pquery($jrer_sum_sql, $params);
		$row_job_jrer = $adb->fetch_array($result);

		$BUY_LOCAL_CURRENCY_GROSS = number_format ( $row_job_jrer['buy_local_currency_gross'] , 2 ,  "." , ",");
		$BUY_LOCAL_CURRENCY_NET   = number_format ( $row_job_jrer['buy_local_currency_net'] , 2 ,  "." , "," );
		$EXPECTED_BUY_LOCAL_CURRENCY_NET  = number_format ( $row_job_jrer['expected_buy_local_currency_net'] , 2 ,  "." , "," );
		$VARIATION_EXPECTED_AND_ACTUAL_BUYING = number_format ( $row_job_jrer['variation_expected_and_actual_buying'] , 2 ,  "." , "," );

		$assigned_user_info =  Users_Record_Model::getInstanceById($fleet_info_detail->get('assigned_user_id'), 'Users');
		$current_user_office_id = $assigned_user_info->get('location_id');
		//For checking user is main owner or sub user
		$company_id = $assigned_user_info->get('company_id');

		$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency($company_id);

		$file_title_currency = $reporting_currency;

		//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
		 $jrer_sql_expense =  "SELECT vtiger_jobexpencereportcf.cf_1347 as buy_local_currency_gross,
								     vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
									 vtiger_jobexpencereportcf.cf_1351 as expected_buy_local_currency_net,
									 vtiger_jobexpencereportcf.cf_1353 as variation_expected_and_actual_buying,
									 vtiger_jobexpencereportcf.cf_1216 as expense_invoice_date,
									 vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
									 FROM `vtiger_jobexpencereport`
							  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid
 			INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
 			LEFT JOIN vtiger_jobexpencereportcf as vtiger_jobexpencereportcf on vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid
		 					 WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=?
							 AND vtiger_crmentityrel.module='Fleettrip'
							 AND vtiger_crmentityrel.relmodule='Jobexpencereport'
							 AND vtiger_jobexpencereportcf.cf_1457='Expence' AND vtiger_jobexpencereport.owner_id='".$fleet_info_detail->get('assigned_user_id')."'";

				$parentId = $fleet_id;

				$params = array($parentId);
				$result_expense = $adb->pquery($jrer_sql_expense, $params);
				$numRows_expnese = $adb->num_rows($result_expense);

				$total_cost_in_usd_gross = 0;
				$total_cost_in_usd_net = 0;
				$total_expected_cost_in_usd_net = 0;
				$total_variation_expected_and_actual_buying_cost_in_usd = 0;

				if($numRows_expnese>0)
				{
					for($ii=0; $ii< $adb->num_rows($result_expense); $ii++ ) {
						$row_job_jrer_expense = $adb->fetch_row($result_expense,$ii);
						$expense_invoice_date = $row_job_jrer_expense['expense_invoice_date'];

						$CurId = $row_job_jrer_expense['buy_currency_id'];
						if ($CurId) {
						  $q_cur = $adb->pquery('select * from vtiger_currency_info where id = "'.$CurId.'"');
						  $row_cur = $adb->fetch_array($q_cur);
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
						}else{
							if($b_exchange_rate==0)
							{
								$b_exchange_rate = 1;
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

		/*$total_cost_in_usd_gross = $row_job_jrer['buy_local_currency_gross']/$final_exchange_rate;
		$total_cost_in_usd_net = $row_job_jrer['buy_local_currency_net']/$final_exchange_rate;
		$total_expected_cost_in_usd_net = $row_job_jrer['expected_buy_local_currency_net']/$final_exchange_rate;
		$total_variation_expected_and_actual_buying_cost_in_usd = $row_job_jrer['variation_expected_and_actual_buying']/$final_exchange_rate;*/

		$TOTAL_COST_USD_GROSS = number_format ( $total_cost_in_usd_gross , 2 ,  "." , "," );
		$TOTAL_COST_IN_USD_NET = number_format ( $total_cost_in_usd_net , 2 ,  "." , "," );
		$TOTAL_EXPECTED_COST_USD_NET = number_format ( $total_expected_cost_in_usd_net , 2 ,  "." , "," );
		$TOTAL_VARIATION_EXPECTED_AND_ACTUAL_BUYING_COST_IN_USD = number_format ( $total_variation_expected_and_actual_buying_cost_in_usd , 2 ,  "." , "," );


		$i=0;
		$total_usd = 0;
		$buyingsubtotalR = 0;
		$buyingsubtotalN = 0;
		$buying = '<table border="1" cellspacing="0" cellpadding="0" width="100%" >
			<tbody>
				<tr>
					<td width="19" align="center">

						<strong><u>#</u></strong>
					</td>
					<td width="62" align="center">
						<h6><u>Name</u></h6>
					</td>
					<td width="66" align="center">
						<h6><u>Service</u></h6>
					</td>
					<td width="57" align="center">
						<h6><u>Invoice</u></h6>
					</td>
					<td width="47" align="center">
						<h6><u>Date</u></h6>
					</td>
					<td width="47" align="center">
						<h6><u>Dept</u></h6>
					</td>
					<td width="57" align="center">
						<h6><u>Type</u></h6>
					</td>
					<td width="57" align="center">
						<h6><u>Buy (Vendor cur net)</u></h6>
					</td>
					<td width="41" align="center">
						<h6><u>VAT Rate</u></h6>
					</td>
					<td width="42" align="center">
						<h6><u>VAT</u></h6>
					</td>
					<td width="72" align="center">
						<h6><u>Buy (cur gross)</u></h6>
					</td>
					<td width="36" align="center">
						<h6><u>Cur</u></h6>
					</td>
					<td width="48" align="center">
						<h6><u>Exch. Rate</u></h6>
					</td>
					<td width="60" align="center">
						<h6><u>Buy (loc cur gross)</u></h6>
					</td>
					<td width="60" align="center">
						<h6><u>Buy (loc cur net)</u></h6>
					</td>
					<td width="66" align="center">
						<h6><u>Expected buy (cur net)</u></h6>
					</td>
					<td width="44" align="center">
						<h6><u>Var exp and act buying</u></h6>
					</td>
					<td width="60" align="center">
						<h6><u>Total, $</u></h6>
					</td>
				</tr>
				<tr>
					<td valign="top" width="19">
					</td>
					<td valign="top" width="62">
					</td>
					<td valign="top" width="66">
					</td>
					<td valign="top" width="57">
					</td>
					<td valign="top" width="47">
					</td>
					<td valign="top" width="47">
					</td>
					<td valign="top" width="57">
					</td>
					<td valign="top" width="57">
					</td>
					<td valign="top" width="41">
					</td>
					<td valign="top" width="42">
					</td>
					<td valign="top" width="72">
					</td>
					<td valign="top" width="36">
					</td>
					<td valign="top" width="48">
					</td>
					<td valign="top" width="60">
					</td>
					<td valign="top" width="60">
					</td>
					<td valign="top" width="66">
					</td>
					<td valign="top" width="44">
					</td>
					<td valign="top" width="60">
					</td>
				</tr>
				';

		foreach($models as $key => $model)
		{
			$expense_record= $model->getInstanceById($model->getId());
			if($model->getDisplayValue('cf_1457') == 'Selling'){
				continue;
			}
			$i++;

			$Cur = $expense_record->getDisplayValue('cf_1345');
			$invoice_date = $expense_record->get('cf_1216');
			$b_exchange_rate = $final_exchange_rate;
			if(!empty($invoice_date))
			{
				if($file_title_currency!='USD')
				{
					$b_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $invoice_date);
				}else{
					$b_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $invoice_date);
				}
			}
			$value_in_usd_normal = $expense_record->getDisplayValue('cf_1349');
			if($file_title_currency!='USD')
			{
				$value_in_usd_normal = $expense_record->getDisplayValue('cf_1349')/$b_exchange_rate;
			}

			$value_in_usd = number_format($value_in_usd_normal,2,'.','');

			$AccType = $expense_record->getDisplayValue('cf_1214');
			if (substr($AccType, -1) == 'R') {
			$buyingsubtotalR = $buyingsubtotalR + $value_in_usd_normal;}
			if ((substr($AccType, -1) == 'N') or (substr($AccType, -1) == 'D')) {
			$buyingsubtotalN = $buyingsubtotalN + $value_in_usd_normal; }

			$pay_to_id = $expense_record->get('cf_1367');
			$company_accountname = '';
			if(!empty($pay_to_id))
			{
				$crmentity_check_ =  "SELECT vtiger_crmentity.crmid as crmid,  vtiger_crmentity.label  as label, vtiger_crmentity.deleted  as deleted from vtiger_crmentity where crmid=?  ";
				$params = array($pay_to_id);
				$result_crmentity_check_ = $adb->pquery($crmentity_check_, $params);
				$numRows_crmentity_check_ = $adb->num_rows($result_crmentity_check_);
				$row_crmentity_check_ = $adb->fetch_array($result_crmentity_check_);

				if($row_crmentity_check_['deleted']==0)
				{
					$paytoinfo = Vtiger_Record_Model::getInstanceById($pay_to_id, 'Accounts');
					$company_accountname = @$paytoinfo->get('accountname');
				}
				else{
					$company_accountname =$row_crmentity_check_['label'].' -Deleted';
				}
			}

			$total_usd += $value_in_usd;

			$buying .='<tr>
            <td valign="top" width="19">
                '.$i.'
            </td>
            <td valign="top" width="66">
                '.$company_accountname.'
            </td>
            <td valign="top" width="66">
                '.$expense_record->getDisplayValue('cf_1453').'
            </td>
            <td valign="top" width="57">
                '.$expense_record->getDisplayValue('cf_1212').'
            </td>
            <td valign="top" width="47" align="center">
                '.$expense_record->getDisplayValue('cf_1216').'
            </td>
            <td valign="top" width="47" align="center">
              '.$expense_record->getDisplayValue('cf_1477').' '.$expense_record->getDisplayValue('cf_1479').'
            </td>
            <td valign="top" width="57" align="center">
                '.$expense_record->getDisplayValue('cf_1214').'
            </td>
            <td valign="top" width="57" align="right">
                 '.number_format ( $expense_record->getDisplayValue('cf_1337') , 2 ,  "." , "," ).'
            </td>
            <td valign="top" width="41" align="center">
                 '.$expense_record->getDisplayValue('cf_1339').'
            </td>
            <td valign="top" width="42" align="right">
                '.$expense_record->getDisplayValue('cf_1341').'
            </td>
            <td valign="top" width="72" align="right">
                '.number_format ( $expense_record->getDisplayValue('cf_1343') , 2 ,  "." , "," ).'
            </td>
            <td valign="top" width="36" align="center">
                '.$expense_record->getDisplayValue('cf_1345').'
            </td>
            <td valign="top" width="48" align="right">
                '.number_format($expense_record->getDisplayValue('cf_1222'), 2).'
            </td>
            <td valign="top" width="60" align="right">
                '.number_format ( $expense_record->getDisplayValue('cf_1347') , 2 ,  "." , "," ).'
            </td>
            <td valign="top" width="60" align="right">
                '.number_format ( $expense_record->getDisplayValue('cf_1349') , 2 ,  "." , "," ).'
            </td>
            <td valign="top" width="66" align="right">
                '.$expense_record->getDisplayValue('cf_1351').'
            </td>
            <td valign="top" width="44" align="right">
                '.$expense_record->getDisplayValue('cf_1353').'
            </td>
            <td valign="top" width="60" align="right">
                '.$value_in_usd.'
            </td>
        </tr>';

		}
		$buying .='<tr>
            <td valign="top" width="19">
            </td>
            <td valign="top" width="62">
            </td>
            <td valign="top" width="66">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="47">
            </td>
            <td valign="top" width="47">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="41">
            </td>
            <td valign="top" width="42">
            </td>
            <td valign="top" width="72">
            </td>
            <td valign="top" width="60">
            </td>
            <td valign="top" width="36">
            </td>
            <td valign="top" width="48">
            </td>
            <td valign="top" width="60">
            </td>
            <td valign="top" width="60">
            </td>
            <td valign="top" width="66">
            </td>
            <td valign="top" width="44">
            </td>
        </tr>
        <tr>
            <td valign="top" width="19">
            </td>
            <td valign="top" width="62">
            </td>
            <td valign="top" width="66" align="right" colspan="3">
                <h6><u>Sub Total</u></h6>
            </td>

            <td valign="top" width="47">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="41">
            </td>
            <td valign="top" width="42">
            </td>
            <td valign="top" width="72">
            </td>
            <td valign="top" width="36">
            </td>
            <td valign="top" width="48">
            </td>
            <td valign="top" width="60">
            </td>
            <td valign="top" width="60">
            </td>
            <td valign="top" width="66">
            </td>
            <td valign="top" width="44">
            </td>
            <td valign="top" width="60" align="right">
                <h6><u>'.$total_usd.'</u></h6>
            </td>
        </tr>
        <tr>
            <td valign="top" width="19">
            </td>
            <td valign="top" width="62">
            </td>
            <td valign="top" width="66" align="right" colspan="3">
                <h6><u>Total Local Currency</u></h6>
            </td>
            <td valign="top" width="47">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="41">
            </td>
            <td valign="top" width="42">
            </td>
            <td valign="top" width="72">
            </td>
            <td valign="top" width="36">
            </td>
            <td valign="top" width="48">
            </td>
            <td valign="top" width="60" align="right">
                <h6><u>'.$BUY_LOCAL_CURRENCY_GROSS.'</u></h6>
            </td>
            <td valign="top" width="60" align="right">
                <h6><u>'.$BUY_LOCAL_CURRENCY_NET.'</u></h6>
            </td>
            <td valign="top" width="66" align="right">
                <h6><u>'.$EXPECTED_BUY_LOCAL_CURRENCY_NET.'</u></h6>
            </td>
            <td valign="top" width="44" align="right">
                <h6><u>'.$VARIATION_EXPECTED_AND_ACTUAL_BUYING.'</u></h6>
            </td>
            <td valign="top" width="60">
            </td>
        </tr>

        <tr>
            <td valign="top" width="19">
            </td>
            <td valign="top" width="62">
            </td>
            <td valign="top" width="66" align="right" colspan="3">
                <h6><u>Total Cost USD</u></h6>
            </td>
            <td valign="top" width="47">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="41">
            </td>
            <td valign="top" width="42">
            </td>
            <td valign="top" width="72">
            </td>
            <td valign="top" width="36">
            </td>
            <td valign="top" width="48">
            </td>
            <td valign="top" width="60" align="right">
                <h6><u>'.$TOTAL_COST_USD_GROSS.'</u></h6>
            </td>
            <td valign="top" width="60" align="right">
                <h6><u>'.$TOTAL_COST_IN_USD_NET.'</u></h6>
            </td>
            <td valign="top" width="66" align="right">
                <h6><u>'.$TOTAL_EXPECTED_COST_USD_NET.'</u></h6>
            </td>
            <td valign="top" width="44" align="right">
                <h6><u>'.$TOTAL_VARIATION_EXPECTED_AND_ACTUAL_BUYING_COST_IN_USD.'</u></h6>
            </td>
            <td valign="top" width="60">
            </td>
        </tr></tbody></table>';

		$this->setValue('fleet_expense_table', $buying);

		$this->setValue('buyingsubtotalR', number_format ( $buyingsubtotalR , 2 ,  "." , "," ));
		$this->setValue('buyingsubtotalN', number_format ( $buyingsubtotalN , 2 ,  "." , "," ));
		$this->setValue('buyingtotal', $total_usd);

		include('include/mpdf60/mpdf.php');
		 @date_default_timezone_set($current_user->get('time_zone'));
  		$mpdf = new mPDF('utf-8', 'A4-L', '10', '', 10, 10, 30, 15, 10, 5); /*задаем формат, отступы и.т.д.*/
  		$mpdf->charset_in = 'utf8';

		$mpdf->list_indent_first_level = 0;

		//$mpdf->SetDefaultFontSize(12);
		//$mpdf->setAutoTopMargin(2);
		$mpdf->SetHTMLHeader('<table width="100%" cellpadding="0" cellspacing="0">
		<tr><td align="right" style="font-size:9;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">JCR Form, GLOBALINK, designed: March, 2010</td></tr>
		<tr><td align="right"><img src="include/calendar_logo.jpg"/></td></tr></table>');
				$mpdf->SetHTMLFooter('<table width="100%" cellpadding="0" cellspacing="0">
		<tr><td width="40%" align="left" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">Printed: '.date('d.m.Y; H:i').' by '.$current_user->get('user_name').'</td>
		<td width="20%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">Page {PAGENO} of {nbpg}</td>
		<td width="40%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">&nbsp;</td>
		</table>');
		$stylesheet = file_get_contents('include/mpdf60/examples/mpdfstyletables.css');
		$mpdf->WriteHTML($stylesheet,1);	// The parameter 1 tells that this is css/style only and no body/html/text
		$mpdf->WriteHTML($this->_documentXML); /*формируем pdf*/


		$pdf_name = 'pdf_docs/truck_expense.pdf';

		$mpdf->Output($pdf_name, 'F');
		//header('Location:http://mb.globalink.net/vt60/'.$pdf_name);
		header('Location:'.$pdf_name);
		exit;

	}

	public function print_fleet_expense($request)
	{
		global $adb;
		$moduleName = $request->getModule();
		$record = $request->get('record');

		$fleet_id = $record;
		$current_user = Users_Record_Model::getCurrentUserModel();

		$fleet_info_detail = Vtiger_Record_Model::getInstanceById($fleet_id, 'Fleet');

		$job_id  = $this->get_job_id_from_fleet($fleet_id);
		$sourceModule_job = 'Job';
		$job_info_detail  = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule_job);

		$document = $this->loadTemplate('printtemplates/fleet_expense.html');

		$driver_user_info = Users_Record_Model::getInstanceById($fleet_info_detail->get('cf_2003'), 'Users');

		$owner_fleet_user_info = Users_Record_Model::getInstanceById($fleet_info_detail->get('assigned_user_id'), 'Users');

		$pay_to_info = Vtiger_Record_Model::getInstanceById($job_info_detail->get('cf_1441'), 'Accounts');

		$this->setValue('useroffice',$owner_fleet_user_info->getDisplayValue('location_id'));
		$this->setValue('userdepartment',$owner_fleet_user_info->getDisplayValue('department_id'));
		$this->setValue('mobile',$owner_fleet_user_info->get('phone_mobile'));
		$this->setValue('fax',$owner_fleet_user_info->get('phone_fax'));
		$this->setValue('email',htmlentities($owner_fleet_user_info->get('email1'), ENT_QUOTES, "UTF-8"));
		$this->setValue('cityname',htmlentities($owner_fleet_user_info->getDisplayValue('location_id'), ENT_QUOTES, "UTF-8"));
		$this->setValue('countryname',htmlentities($owner_fleet_user_info->get('address_country'), ENT_QUOTES, "UTF-8"));
		$this->setValue('departmentcode',htmlentities($owner_fleet_user_info->getDisplayValue('department_id'), ENT_QUOTES, "UTF-8"));
		$this->setValue('dateadded',date('d.m.Y', strtotime($fleet_info_detail->get('createdtime'))));
		//$this->setValue('billingto', $pay_to_info->get('accountname'));
		$this->setValue('from', htmlentities($owner_fleet_user_info->get('first_name').' '.$owner_fleet_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));

		$this->setValue('refno', $job_info_detail->get('cf_1198'));

		$this->setValue('truckno', htmlentities($fleet_info_detail->getDisplayValue('cf_2001'), ENT_QUOTES, "UTF-8"));
		$this->setValue('driver', htmlentities($driver_user_info->get('first_name').' '.$driver_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));
		$this->setValue('grossweight', htmlentities($fleet_info_detail->get('cf_1989').' '.$fleet_info_detail->get('cf_2039'), ENT_QUOTES, "UTF-8"));
		$this->setValue('volumeweight', htmlentities($fleet_info_detail->get('cf_1991').' '.$fleet_info_detail->get('cf_2041'), ENT_QUOTES, "UTF-8"));

		$this->setValue('pieces', htmlentities($fleet_info_detail->get('cf_1987'), ENT_QUOTES, "UTF-8"));

		$truck_info = Vtiger_Record_Model::getInstanceById($fleet_info_detail->get('cf_2001'), 'Truck');
		$this->setValue('trucktype',htmlentities($truck_info->getDisplayValue('cf_1911'), ENT_QUOTES, "UTF-8"));

		$this->setValue('origincountry',htmlentities($fleet_info_detail->get('cf_1993'), ENT_QUOTES, "UTF-8"));
		$this->setValue('origincity',htmlentities($fleet_info_detail->get('cf_1997'), ENT_QUOTES, "UTF-8"));
		$this->setValue('destinationcountry',htmlentities($fleet_info_detail->get('cf_1995'), ENT_QUOTES, "UTF-8"));
		$this->setValue('destinationcity',htmlentities($fleet_info_detail->get('cf_1999'), ENT_QUOTES, "UTF-8"));

		$this->setValue('masteroffice',htmlentities($job_info_detail->getDisplayValue('cf_1188'), ENT_QUOTES, "UTF-8"));
		$this->setValue('masterdepartment',htmlentities($job_info_detail->getDisplayValue('cf_1190'), ENT_QUOTES, "UTF-8"));

		$pagingModel = new Vtiger_Paging_Model();
		$pagingModel->set('page','1');

		$relatedModuleName = 'Jobexpencereport';
		$parentRecordModel = $fleet_info_detail;
		$relationListView = Vtiger_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName, $label);
		$models = $relationListView->getEntries($pagingModel);

		include('include/Exchangerate/exchange_rate_class.php');

		//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
		$jrer_sum_sql =  "SELECT sum(vtiger_jobexpencereportcf.cf_1347) as buy_local_currency_gross,
								     sum(vtiger_jobexpencereportcf.cf_1349) as buy_local_currency_net,
									 sum(vtiger_jobexpencereportcf.cf_1351) as expected_buy_local_currency_net,
									 sum(vtiger_jobexpencereportcf.cf_1353) as variation_expected_and_actual_buying
									 FROM `vtiger_jobexpencereport`
							  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid
 		INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
 		LEFT JOIN vtiger_jobexpencereportcf as vtiger_jobexpencereportcf on vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid
		 					 WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=?
							 AND vtiger_crmentityrel.module='Fleet'
							 AND vtiger_crmentityrel.relmodule='Jobexpencereport'
							 AND vtiger_jobexpencereportcf.cf_1457='Expence' AND vtiger_jobexpencereport.owner_id='".$fleet_info_detail->get('assigned_user_id')."'";
		$parentId = $fleet_id;
		$params = array($parentId);
		$result = $adb->pquery($jrer_sum_sql, $params);
		$row_job_jrer = $adb->fetch_array($result);

		$BUY_LOCAL_CURRENCY_GROSS = number_format ( $row_job_jrer['buy_local_currency_gross'] , 2 ,  "." , ",");
		$BUY_LOCAL_CURRENCY_NET   = number_format ( $row_job_jrer['buy_local_currency_net'] , 2 ,  "." , "," );
		$EXPECTED_BUY_LOCAL_CURRENCY_NET  = number_format ( $row_job_jrer['expected_buy_local_currency_net'] , 2 ,  "." , "," );
		$VARIATION_EXPECTED_AND_ACTUAL_BUYING = number_format ( $row_job_jrer['variation_expected_and_actual_buying'] , 2 ,  "." , "," );

		$job_office_id = $job_info_detail->get('cf_1188');

		$assigned_user_info =  Users_Record_Model::getInstanceById($fleet_info_detail->get('assigned_user_id'), 'Users');
		$current_user_office_id = $assigned_user_info->get('location_id');
		//For checking user is main owner or sub user
		$company_id = $assigned_user_info->get('company_id');
		if($job_info_detail->get('assigned_user_id')!=$fleet_info_detail->get('assigned_user_id'))
		{
			if($job_office_id==$current_user_office_id){
				$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info_detail->get('cf_1186'));
			}
			else{
				$db_sub = PearDatabase::getInstance();
				$query_sub = 'SELECT sub_jrer_file_title from vtiger_jobtask WHERE job_id=? and user_id=? limit 1';
				//$job_info->get('record_id') = jobid
				$params_sub = array($job_info_detail->get('record_id'), $fleet_info_detail->get('assigned_user_id'));
				$result_sub = $db_sub->pquery($query_sub,$params_sub);
				$file_title_info = $db_sub->fetch_array($result_sub);
				$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency((empty($file_title_info['sub_jrer_file_title']) ? $company_id : $file_title_info['sub_jrer_file_title']));
			}
		}
		else{
		$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info_detail->get('cf_1186'));
		}

		/*
		if($current_user->get('is_admin')=='on' || $current_user->get('roleid')=='H3'  || $count_parent_role <= 3 )
		{
			$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info['cf_1186']);
		}
		*/

		$file_title_currency = $reporting_currency;

		$jrer_last_sql =  "SELECT vtiger_crmentity.createdtime FROM vtiger_jobexpencereportcf
							 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereportcf.jobexpencereportid
							 INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid
							 WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid=? AND crmentityrel.module='Fleet'
							 AND crmentityrel.relmodule='Jobexpencereport' order by vtiger_crmentity.createdtime DESC limit 1";
		// parentId = Fleet Id
		$parentId = $fleet_id;
		$params = array($parentId);
		$result_last = $adb->pquery($jrer_last_sql, $params);
		$row_jrer_last = $adb->fetch_array($result_last);
		$count_last_modified = $adb->num_rows($result_last);

		$exchange_rate_date  = date('Y-m-d');
		if($count_last_modified>0)
		{
			$modifiedtime = $row_jrer_last['createdtime'];
			$modifiedtime = strtotime($row_jrer_last['createdtime']);
			$exchange_rate_date = date('Y-m-d', $modifiedtime);
		}

		if($file_title_currency!='USD')
		{
			$final_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $exchange_rate_date);
		}else{
			$final_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $exchange_rate_date);
		}

			//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
		   $jrer_sql_expense =  "SELECT vtiger_jobexpencereportcf.cf_1347 as buy_local_currency_gross,
								     vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
									 vtiger_jobexpencereportcf.cf_1351 as expected_buy_local_currency_net,
									 vtiger_jobexpencereportcf.cf_1353 as variation_expected_and_actual_buying,
									 vtiger_jobexpencereportcf.cf_1216 as expense_invoice_date,
									 vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
									 FROM `vtiger_jobexpencereport`
							  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid
 			INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
 			LEFT JOIN vtiger_jobexpencereportcf as vtiger_jobexpencereportcf on vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid
		 					 WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=?
							 AND vtiger_crmentityrel.module='Fleet'
							 AND vtiger_crmentityrel.relmodule='Jobexpencereport'
							 AND vtiger_jobexpencereportcf.cf_1457='Expence' AND vtiger_jobexpencereport.owner_id='".$fleet_info_detail->get('assigned_user_id')."'";

				$parentId = $fleet_id;

				$params = array($parentId);
				$result_expense = $adb->pquery($jrer_sql_expense, $params);
				$numRows_expnese = $adb->num_rows($result_expense);

				$total_cost_in_usd_gross = 0;
				$total_cost_in_usd_net = 0;
				$total_expected_cost_in_usd_net = 0;
				$total_variation_expected_and_actual_buying_cost_in_usd = 0;

				if($numRows_expnese>0)
				{
					for($ii=0; $ii< $adb->num_rows($result_expense); $ii++ ) {
						$row_job_jrer_expense = $adb->fetch_row($result_expense,$ii);
						$expense_invoice_date = $row_job_jrer_expense['expense_invoice_date'];

						$CurId = $row_job_jrer_expense['buy_currency_id'];
						if ($CurId) {
						  $q_cur = $adb->pquery('select * from vtiger_currency_info where id = "'.$CurId.'"');
						  $row_cur = $adb->fetch_array($q_cur);
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
						}else{
							if($b_exchange_rate==0)
							{
								$b_exchange_rate = 1;
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

		/*$total_cost_in_usd_gross = $row_job_jrer['buy_local_currency_gross']/$final_exchange_rate;
		$total_cost_in_usd_net = $row_job_jrer['buy_local_currency_net']/$final_exchange_rate;
		$total_expected_cost_in_usd_net = $row_job_jrer['expected_buy_local_currency_net']/$final_exchange_rate;
		$total_variation_expected_and_actual_buying_cost_in_usd = $row_job_jrer['variation_expected_and_actual_buying']/$final_exchange_rate;*/

		$TOTAL_COST_USD_GROSS = number_format ( $total_cost_in_usd_gross , 2 ,  "." , "," );
		$TOTAL_COST_IN_USD_NET = number_format ( $total_cost_in_usd_net , 2 ,  "." , "," );
		$TOTAL_EXPECTED_COST_USD_NET = number_format ( $total_expected_cost_in_usd_net , 2 ,  "." , "," );
		$TOTAL_VARIATION_EXPECTED_AND_ACTUAL_BUYING_COST_IN_USD = number_format ( $total_variation_expected_and_actual_buying_cost_in_usd , 2 ,  "." , "," );


		$i=0;
		$total_usd = 0;
		$buyingsubtotalR = 0;
		$buyingsubtotalN = 0;
		$buying = '<table border="1" cellspacing="0" cellpadding="0" width="100%" >
			<tbody>
				<tr>
					<td width="19" align="center">
						<strong><u>#</u></strong>
					</td>
					<td width="62" align="center">
						<h6><u>Name</u></h6>
					</td>
					<td width="66" align="center">
						<h6><u>Service</u></h6>
					</td>
					<td width="57" align="center">
						<h6><u>Invoice</u></h6>
					</td>
					<td width="47" align="center">
						<h6><u>Date</u></h6>
					</td>
					<td width="47" align="center">
						<h6><u>Dept</u></h6>
					</td>
					<td width="57" align="center">
						<h6><u>Type</u></h6>
					</td>
					<td width="57" align="center">
						<h6><u>Buy (Vendor cur net)</u></h6>
					</td>
					<td width="41" align="center">
						<h6><u>VAT Rate</u></h6>
					</td>
					<td width="42" align="center">
						<h6><u>VAT</u></h6>
					</td>
					<td width="72" align="center">
						<h6><u>Buy (cur gross)</u></h6>
					</td>
					<td width="36" align="center">
						<h6><u>Cur</u></h6>
					</td>
					<td width="48" align="center">
						<h6><u>Exch. Rate</u></h6>
					</td>
					<td width="60" align="center">
						<h6><u>Buy (loc cur gross)</u></h6>
					</td>
					<td width="60" align="center">
						<h6><u>Buy (loc cur net)</u></h6>
					</td>
					<td width="66" align="center">
						<h6><u>Expected buy (cur net)</u></h6>
					</td>
					<td width="44" align="center">
						<h6><u>Var exp and act buying</u></h6>
					</td>
					<td width="60" align="center">
						<h6><u>Total, $</u></h6>
					</td>
				</tr>
				<tr>
					<td valign="top" width="19">
					</td>
					<td valign="top" width="62">
					</td>
					<td valign="top" width="66">
					</td>
					<td valign="top" width="57">
					</td>
					<td valign="top" width="47">
					</td>
					<td valign="top" width="47">
					</td>
					<td valign="top" width="57">
					</td>
					<td valign="top" width="57">
					</td>
					<td valign="top" width="41">
					</td>
					<td valign="top" width="42">
					</td>
					<td valign="top" width="72">
					</td>
					<td valign="top" width="36">
					</td>
					<td valign="top" width="48">
					</td>
					<td valign="top" width="60">
					</td>
					<td valign="top" width="60">
					</td>
					<td valign="top" width="66">
					</td>
					<td valign="top" width="44">
					</td>
					<td valign="top" width="60">
					</td>
				</tr>
				';

		foreach($models as $key => $model)
		{
			$expense_record= $model->getInstanceById($model->getId());
			if($model->getDisplayValue('cf_1457') == 'Selling'){
				continue;
			}
			$i++;

			$Cur = $expense_record->getDisplayValue('cf_1345');
			$invoice_date = $expense_record->get('cf_1216');
			$b_exchange_rate = $final_exchange_rate;
			if(!empty($invoice_date))
			{
				if($file_title_currency!='USD')
				{
					$b_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $invoice_date);
				}else{
					$b_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $invoice_date);
				}
			}
			$value_in_usd_normal = $expense_record->getDisplayValue('cf_1349');
			if($file_title_currency!='USD')
			{
				$value_in_usd_normal = $expense_record->getDisplayValue('cf_1349')/$b_exchange_rate;
			}

			$value_in_usd = number_format($value_in_usd_normal,2,'.','');

			$AccType = $expense_record->getDisplayValue('cf_1214');
			if (substr($AccType, -1) == 'R') {
			$buyingsubtotalR = $buyingsubtotalR + $value_in_usd_normal;}
			if ((substr($AccType, -1) == 'N') or (substr($AccType, -1) == 'D')) {
			$buyingsubtotalN = $buyingsubtotalN + $value_in_usd_normal; }

			$pay_to_id = $expense_record->get('cf_1367');
			$company_accountname = '';
			if(!empty($pay_to_id))
			{
				$crmentity_check_ =  "SELECT vtiger_crmentity.crmid as crmid,  vtiger_crmentity.label  as label, vtiger_crmentity.deleted  as deleted from vtiger_crmentity where crmid=?  ";
				$params = array($pay_to_id);
				$result_crmentity_check_ = $adb->pquery($crmentity_check_, $params);
				$numRows_crmentity_check_ = $adb->num_rows($result_crmentity_check_);
				$row_crmentity_check_ = $adb->fetch_array($result_crmentity_check_);

				if($row_crmentity_check_['deleted']==0)
				{
					$paytoinfo = Vtiger_Record_Model::getInstanceById($pay_to_id, 'Accounts');
					$company_accountname = @$paytoinfo->get('accountname');
				}else{
					$company_accountname =$row_crmentity_check_['label'].' -Deleted';
				}
			}

			$total_usd += $value_in_usd;

			$buying .='<tr>
            <td valign="top" width="19">
                '.$i.'
            </td>
            <td valign="top" width="66">
                '.$company_accountname.'
            </td>
            <td valign="top" width="66">
                '.$expense_record->getDisplayValue('cf_1453').'
            </td>
            <td valign="top" width="57">
                '.$expense_record->getDisplayValue('cf_1212').'
            </td>
            <td valign="top" width="47" align="center">
                '.$expense_record->getDisplayValue('cf_1216').'
            </td>
            <td valign="top" width="47" align="center">
              '.$expense_record->getDisplayValue('cf_1477').' '.$expense_record->getDisplayValue('cf_1479').'
            </td>
            <td valign="top" width="57" align="center">
                '.$expense_record->getDisplayValue('cf_1214').'
            </td>
            <td valign="top" width="57" align="right">
                 '.$expense_record->getDisplayValue('cf_1337').'
            </td>
            <td valign="top" width="41" align="center">
                 '.$expense_record->getDisplayValue('cf_1339').'
            </td>
            <td valign="top" width="42" align="right">
                '.$expense_record->getDisplayValue('cf_1341').'
            </td>
            <td valign="top" width="72" align="right">
                '.$expense_record->getDisplayValue('cf_1343').'
            </td>
            <td valign="top" width="36" align="center">
                '.$expense_record->getDisplayValue('cf_1345').'
            </td>
            <td valign="top" width="48" align="right">
                '.number_format($expense_record->getDisplayValue('cf_1222'), 2).'
            </td>
            <td valign="top" width="60" align="right">
                '.$expense_record->getDisplayValue('cf_1347').'
            </td>
            <td valign="top" width="60" align="right">
                '.$expense_record->getDisplayValue('cf_1349').'
            </td>
            <td valign="top" width="66" align="right">
                '.$expense_record->getDisplayValue('cf_1351').'
            </td>
            <td valign="top" width="44" align="right">
                '.$expense_record->getDisplayValue('cf_1353').'
            </td>
            <td valign="top" width="60" align="right">
                '.$value_in_usd.'
            </td>
        </tr>';

		}
		$buying .='<tr>
            <td valign="top" width="19">
            </td>
            <td valign="top" width="62">
            </td>
            <td valign="top" width="66">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="47">
            </td>
            <td valign="top" width="47">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="41">
            </td>
            <td valign="top" width="42">
            </td>
            <td valign="top" width="72">
            </td>
            <td valign="top" width="60">
            </td>
            <td valign="top" width="36">
            </td>
            <td valign="top" width="48">
            </td>
            <td valign="top" width="60">
            </td>
            <td valign="top" width="60">
            </td>
            <td valign="top" width="66">
            </td>
            <td valign="top" width="44">
            </td>
        </tr>
        <tr>
            <td valign="top" width="19">
            </td>
            <td valign="top" width="62">
            </td>
            <td valign="top" width="66" align="right">
                <h6><u>Sub Total</u></h6>
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="47">
            </td>
            <td valign="top" width="47">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="41">
            </td>
            <td valign="top" width="42">
            </td>
            <td valign="top" width="72">
            </td>
            <td valign="top" width="36">
            </td>
            <td valign="top" width="48">
            </td>
            <td valign="top" width="60">
            </td>
            <td valign="top" width="60">
            </td>
            <td valign="top" width="66">
            </td>
            <td valign="top" width="44">
            </td>
            <td valign="top" width="60" align="right">
                <h6><u>'.$total_usd.'</u></h6>
            </td>
        </tr>
        <tr>
            <td valign="top" width="19">
            </td>
            <td valign="top" width="62">
            </td>
            <td valign="top" width="66" align="right">
                <h6><u>Total Local Currency</u></h6>
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="47">
            </td>
            <td valign="top" width="47">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="41">
            </td>
            <td valign="top" width="42">
            </td>
            <td valign="top" width="72">
            </td>
            <td valign="top" width="36">
            </td>
            <td valign="top" width="48">
            </td>
            <td valign="top" width="60" align="right">
                <h6><u>'.$BUY_LOCAL_CURRENCY_GROSS.'</u></h6>
            </td>
            <td valign="top" width="60" align="right">
                <h6><u>'.$BUY_LOCAL_CURRENCY_NET.'</u></h6>
            </td>
            <td valign="top" width="66" align="right">
                <h6><u>'.$EXPECTED_BUY_LOCAL_CURRENCY_NET.'</u></h6>
            </td>
            <td valign="top" width="44" align="right">
                <h6><u>'.$VARIATION_EXPECTED_AND_ACTUAL_BUYING.'</u></h6>
            </td>
            <td valign="top" width="60">
            </td>
        </tr>
        <tr>
            <td valign="top" width="19">
            </td>
            <td valign="top" width="62">
            </td>
            <td valign="top" width="66" align="right">
                <h6><u>Exchange Rate</u></h6>
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="47">
            </td>
            <td valign="top" width="47">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="41">
            </td>
            <td valign="top" width="42">
            </td>
            <td valign="top" width="72">
            </td>
            <td valign="top" width="36">
            </td>
            <td valign="top" width="48">
            </td>
            <td valign="top" width="60" align="right">
                <h6><u>'.number_format($final_exchange_rate, 2).'</u></h6>
            </td>
            <td valign="top" width="60" align="right">
                <h6><u>'.number_format($final_exchange_rate, 2).'</u></h6>
            </td>
            <td valign="top" width="66" align="right">
                <h6><u>'.number_format($final_exchange_rate, 2).'</u></h6>
            </td>
            <td valign="top" width="44" align="right">
                <h6><u>'.number_format($final_exchange_rate, 2).'</u></h6>
            </td>
            <td valign="top" width="60">
            </td>
        </tr>
        <tr>
            <td valign="top" width="19">
            </td>
            <td valign="top" width="62">
            </td>
            <td valign="top" width="66" align="right">
                <h6><u>Total Cost USD</u></h6>
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="47">
            </td>
            <td valign="top" width="47">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="57">
            </td>
            <td valign="top" width="41">
            </td>
            <td valign="top" width="42">
            </td>
            <td valign="top" width="72">
            </td>
            <td valign="top" width="36">
            </td>
            <td valign="top" width="48">
            </td>
            <td valign="top" width="60" align="right">
                <h6><u>'.$TOTAL_COST_USD_GROSS.'</u></h6>
            </td>
            <td valign="top" width="60" align="right">
                <h6><u>'.$TOTAL_COST_IN_USD_NET.'</u></h6>
            </td>
            <td valign="top" width="66" align="right">
                <h6><u>'.$TOTAL_EXPECTED_COST_USD_NET.'</u></h6>
            </td>
            <td valign="top" width="44" align="right">
                <h6><u>'.$TOTAL_VARIATION_EXPECTED_AND_ACTUAL_BUYING_COST_IN_USD.'</u></h6>
            </td>
            <td valign="top" width="60">
            </td>
        </tr></tbody></table>';

		$this->setValue('fleet_expense_table', $buying);

		$this->setValue('buyingsubtotalR', number_format ( $buyingsubtotalR , 2 ,  "." , "," ));
		$this->setValue('buyingsubtotalN', number_format ( $buyingsubtotalN , 2 ,  "." , "," ));
		$this->setValue('buyingtotal', $total_usd);


		//For Profit Share
		$j=0;
		$profit_share = array();

		foreach($models as $key => $model){
			//$selling_record= $model->getInstanceById($model->getId());
			$expense_record= $model->getInstanceById($model->getId());
			if($expense_record->getDisplayValue('cf_1457') == 'Selling')
			{
				continue;
			}

			$dept_branch_new = $expense_record->get('cf_1477').'-'.$expense_record->get('cf_1479');
			if(!in_array($dept_branch_new, $profit_share_check_new))
			{
				$profit_share_check_new[] = $expense_record->get('cf_1477').'-'.$expense_record->get('cf_1479');

				$col_data_P['cf_1477'] = $expense_record->getDisplayValue('cf_1477');
				$col_data_P['cf_1479'] = $expense_record->getDisplayValue('cf_1479');

				$col_data_P['cf_1477_location_id'] = $expense_record->get('cf_1477');
				$col_data_P['cf_1479_department_id'] = $expense_record->get('cf_1479');

				$profit_share[] = $col_data_P;
			}
		 }


		 $profit_share_check = array();
		 $profit_share_data = array();

		  $sum_of_cost = 0;
		  $sum_of_external_selling = 0;
		  $sum_of_job_profit = 0;
		  $sum_of_internal_selling = 0;
		  $sum_of_profit_share = 0;
		  $sum_of_net_profit = 0;

	 if(!empty($profit_share))
		 {
			 foreach($profit_share as $key => $p_share)
			 {
				 $dept_branch = $p_share['cf_1477_location_id'].'-'.$p_share['cf_1479_department_id'];
				 if(!in_array($dept_branch, $profit_share_check))
				 {
					$profit_share_check[] = $p_share['cf_1477_location_id'].'-'.$p_share['cf_1479_department_id'];
					$brach_department_name = $p_share['cf_1477'].' '.$p_share['cf_1479'];

					$adb_buy_local = PearDatabase::getInstance();
					$parentId = $fleet_id;
					//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
					$sum_buy_local_currency_net =  "SELECT vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
														   vtiger_jobexpencereportcf.cf_1216 as buy_invoice_date,
														   vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
													FROM `vtiger_jobexpencereport`
													INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid
													INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
													left join vtiger_jobexpencereportcf as vtiger_jobexpencereportcf on vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid
													where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? and vtiger_crmentityrel.module='Fleet'
													and vtiger_crmentityrel.relmodule='Jobexpencereport' and vtiger_jobexpencereportcf.cf_1457='Expence'
													and vtiger_jobexpencereportcf.cf_1477=? and vtiger_jobexpencereportcf.cf_1479=? AND vtiger_jobexpencereport.owner_id=?
													";
										//AND vtiger_jobexpencereportcf.cf_2195=? (for truck id)
					//$params_buy_local = array($parentId, $p_share['cf_1477_location_id'], $p_share['cf_1479_department_id'], $fleet_info_detail->get('assigned_user_id'), $fleet_info_detail->get('cf_2001'));//cf_2001=truck id
					$params_buy_local = array($parentId, $p_share['cf_1477_location_id'], $p_share['cf_1479_department_id'], $fleet_info_detail->get('assigned_user_id'));//cf_2001=truck id

					$result_buy_locall = $adb_buy_local->pquery($sum_buy_local_currency_net, $params_buy_local);
					$numRows_buy_profit = $adb_buy_local->num_rows($result_buy_locall);
					$cost = 0;
					for($jj=0; $jj< $adb_buy_local->num_rows($result_buy_locall); $jj++ ) {

						$row_jrer_buy_local_currency_net = $adb_buy_local->fetch_row($result_buy_locall,$jj);
						//$row_jrer_buy_local_currency_net = $adb_buy_local->fetch_array($result_buy_locall);

						$cost_local = @$row_jrer_buy_local_currency_net['buy_local_currency_net'];

						$buy_invoice_date = @$row_jrer_buy_local_currency_net['buy_invoice_date'];

						$CurId = $row_jrer_buy_local_currency_net['buy_currency_id'];
						if ($CurId) {
						  $q_cur = $adb->pquery('select * from vtiger_currency_info where id = "'.$CurId.'"');
						  $row_cur = $adb->fetch_array($q_cur);
						  $Cur = $row_cur['currency_code'];
						}
						$b_exchange_rate = $final_exchange_rate;
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




					$adb_sell_local = PearDatabase::getInstance();
					//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
					$sum_sell_local_currency_net =  "SELECT sum(vtiger_jobexpencereportcf.cf_1240) as sell_local_currency_net
													FROM `vtiger_jobexpencereport`
													INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid
													INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
													left join vtiger_jobexpencereportcf as vtiger_jobexpencereportcf on vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid
													where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? and vtiger_crmentityrel.module='Fleet'
													and vtiger_crmentityrel.relmodule='Jobexpencereport' and vtiger_jobexpencereportcf.cf_1457='Selling'
													and vtiger_jobexpencereportcf.cf_1477=? and vtiger_jobexpencereportcf.cf_1479=? AND vtiger_jobexpencereport.owner_id = ? AND vtiger_jobexpencereportcf.cf_2195=?
													";
					$params_sell_local = array($parentId, $p_share['cf_1477_location_id'], $p_share['cf_1479_department_id'], $fleet_info_detail->get('assigned_user_id'), $fleet_info_detail->get('cf_2001')); //parentid=truck id
					$result_sell_locall = $adb_sell_local->pquery($sum_sell_local_currency_net, $params_sell_local);
					$row_jrer_sell_local_currency_net = $adb_sell_local->fetch_array($result_sell_locall);


					$adb_internal = PearDatabase::getInstance();
					//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
					$internal_selling_arr = "SELECT vtiger_jobexpcf.cf_1263 as internal_selling
											FROM vtiger_jobexp
											INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexp.jobexpid
											INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid )
											left join vtiger_jobexpcf as vtiger_jobexpcf on vtiger_jobexpcf.jobexpid=vtiger_jobexp.jobexpid
											where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? and vtiger_crmentityrel.module='Fleet'
											and vtiger_crmentityrel.relmodule='Jobexp' and vtiger_jobexpcf.cf_1257=? and vtiger_jobexpcf.cf_1259=?
											";

					$params_internal = array($parentId, $p_share['cf_1477_location_id'], $p_share['cf_1479_department_id']);

					$result_internal = $adb_internal->pquery($internal_selling_arr, $params_internal);
					$row_jrer_internal_selling = $adb_internal->fetch_array($result_internal);


					//$cost_local = @$row_jrer_buy_local_currency_net['buy_local_currency_net'];
					//$cost = $cost_local/$final_exchange_rate;

					$s_sell_local_currency_net = @$row_jrer_sell_local_currency_net['sell_local_currency_net'];
					$external_selling = $s_sell_local_currency_net/$final_exchange_rate;

					$job_profit = 0;
					if($job_info_detail->get('assigned_user_id')==$fleet_info_detail->get('assigned_user_id'))
					{
						$job_profit = $external_selling - $cost;
					}
					else{
						if($s_sell_local_currency_net<=0)
						{
							$job_profit = @$row_jrer_internal_selling['internal_selling'] - $cost;
						}
						else{
							$job_profit = $external_selling - $cost;
						}
					}
					$brach_department = $p_share['cf_1479_department_id'].' '.$p_share['cf_1477_location_id'];
					$job_branch_department = $job_info_detail->get('cf_1190').' '.$job_info_detail->get('cf_1188');

					if(trim($brach_department)==trim($job_branch_department))
					{
						$profit_share_col = 0;
					}
					else{
						$profit_share_col = @$row_jrer_internal_selling['internal_selling'] - $cost;
					}
					$net_profit = $job_profit - $profit_share_col;

					$profit_share_data[] = array('brach_department' => $brach_department_name,
												 'cost' => number_format ( $cost , 2 ,  "." , "," ),
												 'external_selling' => number_format ( $external_selling , 2 ,  "." , "," ),
												 'job_profit'  =>  number_format ( $job_profit , 2 ,  "." , "," ),
												 'office_id' => $p_share['cf_1477_location_id'],
												 'department_id' => $p_share['cf_1479_department_id'],
												 'job_id' => $parentId,
												 'profit_share_col' => ((trim($brach_department)!=trim($job_branch_department)) ? number_format($profit_share_col, 2 ,  "." , "," ) :''),
												 'net_profit' => ($job_info_detail->get('assigned_user_id')==$fleet_info_detail->get('assigned_user_id') ? number_format ( $net_profit , 2 ,  "." , "," ):''),
												 'internal_selling' => @$row_jrer_internal_selling['internal_selling'],
												 'internal_selling_type' => ((trim($brach_department)!=trim($job_branch_department)) ? 'text' : 'hidden' )
												 );

					$sum_of_cost += $cost;
					$sum_of_external_selling +=$external_selling;
					$sum_of_job_profit +=$job_profit;
					$sum_of_internal_selling +=@$row_jrer_internal_selling['internal_selling'];
					$sum_of_profit_share +=$profit_share_col;
					$sum_of_net_profit +=$net_profit;

				 }
			 }
		 }


		 $i=0;
		 $profitshare = '';
		 foreach($profit_share_data as $profit)
		 {
			 $i++;
			 $profitshare .= '<tr>
							<td valign="top" width="19">
								'.$i.'
							</td>
							<td valign="top" width="150">
								'.$profit['brach_department'].'
							</td>
							<td valign="top" width="170" align="right">
							'.$profit['cost'].'
							</td>
							<td valign="top" width="140" align="right">
							'.$profit['external_selling'].'
							</td>
							<td valign="top" width="103" align="right">
							'.$profit['job_profit'].'
							</td>
							<td valign="top" width="140" align="right">
							'.@$profit['internal_selling'].'
							</td>
							<td valign="top" width="103" align="right">
							'.$profit['profit_share_col'].'
							</td>
							<td valign="top" width="103" align="right">
							'.$profit['net_profit'].'
							</td>
						</tr>';
		 }
		 $this->setValue('profitshare', $profitshare);
		 $SUM_OF_COST = number_format($sum_of_cost , 2 ,  "." , "," );
		 $SUM_OF_EXTERNAL_SELLING = number_format($sum_of_external_selling , 2 ,  "." , "," );
		 $SUM_OF_JOB_PROFIT = number_format($sum_of_job_profit , 2 ,  "." , "," );
		 $SUM_OF_INTERNAL_SELLING = number_format($sum_of_internal_selling , 2 ,  "." , "," );
		 $SUM_OF_PROFIT_SHARE = number_format($sum_of_profit_share , 2 ,  "." , "," );
		 $SUM_OF_NET_PROFIT = ($job_info_detail->get('assigned_user_id')==$fleet_info_detail->get('assigned_user_id') ? number_format($sum_of_net_profit , 2 ,  "." , "," ) : '');
		 $NET_PROFIT_LABEL  = ($job_info_detail->get('assigned_user_id')==$fleet_info_detail->get('assigned_user_id') ? 'Net profit' : '');
		 $PROFIT_SHARE_LABEL = ($job_info_detail->get('assigned_user_id')==$fleet_info_detail->get('assigned_user_id') ? 'Profit Share Received' : 'Profit Share');

		  $this->setValue('sumofcost', $SUM_OF_COST);
		  $this->setValue('sumofexternalselling', $SUM_OF_EXTERNAL_SELLING);
		  $this->setValue('sumofjobprofit', $SUM_OF_JOB_PROFIT);
		  $this->setValue('sumofinternalselling', $SUM_OF_INTERNAL_SELLING);
		  $this->setValue('sumofprofitshare', $SUM_OF_PROFIT_SHARE);
		  $this->setValue('sumofnetprofit', $SUM_OF_NET_PROFIT);

		include('include/mpdf60/mpdf.php');
		 @date_default_timezone_set($current_user->get('time_zone'));
  		$mpdf = new mPDF('utf-8', 'A4-L', '10', '', 10, 10, 30, 15, 10, 5); /*задаем формат, отступы и.т.д.*/
  		$mpdf->charset_in = 'utf8';

		$mpdf->list_indent_first_level = 0;


		//$mpdf->SetDefaultFontSize(12);
		//$mpdf->setAutoTopMargin(2);
		$mpdf->SetHTMLHeader('<table width="100%" cellpadding="0" cellspacing="0">
		<tr><td align="right" style="font-size:9;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">JCR Form, GLOBALINK, designed: March, 2010</td></tr>
		<tr><td align="right"><img src="printtemplates/glklogo.jpg"/ width="160" height="60"></td></tr></table>');
				$mpdf->SetHTMLFooter('<table width="100%" cellpadding="0" cellspacing="0">
		<tr><td width="40%" align="left" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">Printed: '.date('d.m.Y; H:i').' by '.$current_user->get('user_name').'</td>
		<td width="20%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">Page {PAGENO} of {nbpg}</td>
		<td width="40%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">&nbsp;</td>
		</table>');
		$stylesheet = file_get_contents('include/mpdf60/examples/mpdfstyletables.css');
		$mpdf->WriteHTML($stylesheet,1);	// The parameter 1 tells that this is css/style only and no body/html/text
		$mpdf->WriteHTML($this->_documentXML); /*формируем pdf*/


		$pdf_name = 'pdf_docs/truck_expense.pdf';

		$mpdf->Output($pdf_name, 'F');
		//header('Location:http://mb.globalink.net/vt60/'.$pdf_name);
		header('Location:'.$pdf_name);
		exit;


	}

	public function invoice_tax_excel($request){

/*
		ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/
		$firstHtmlString = $this->get_invoice_tax_body($request);


		spl_autoload_register(function ($class_name) {
			$path = str_replace('\\', '/', $class_name);
		    include getcwd().'/libraries/'.$path . '.php';
		});

		$reader = new \PhpOffice\PhpSpreadsheet\Reader\Html();
		$spreadsheet = $reader->loadFromString($firstHtmlString);

		$drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
		//$drawing->setName('Paid');
		//$drawing->setDescription('Paid');
		$drawing->setPath('include/logo_invoice.png'); // put your path and image here
		$drawing->setCoordinates('G1');
		$drawing->setWidth(250);
		$drawing->setHeight(90);

		$drawing->setOffsetX(110);
		$drawing->setOffsetY(20);

		$drawing->setWorksheet($spreadsheet->getActiveSheet());

		//$reader->setSheetIndex(1);
		//$spreadhseet = $reader->loadFromString($secondHtmlString, $spreadsheet);

		$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');

		$spreadsheet->getActiveSheet()->setShowGridlines(false);

		//$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

		ob_clean();
		$fileName = 'Tax_Invoice_'.$request->get('invoice_tax').'.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'. urlencode($fileName).'"');
        $writer->save('php://output');
		exit();


	}

	public function invoice_tax_pdf($request){
		global $adb;

		$document = $this->loadTemplate('printtemplates/Job/invoice_tax.html');

		include('include/mpdf60/mpdf.php');
		//@date_default_timezone_set($current_user->get('time_zone'));
		//$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 0, 7, 10, 10);


		//$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 86, 7, 10, 10);
		$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 90, 120, 10, 10);

		//$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 90, 105, 10, 10);

		//$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 0, 7, 10, 10);
		$mpdf->charset_in = 'utf8';
		$mpdf->autoPageBreak = true;
		//$mpdf->setTestTdInOnePage(false);



		//$mpdf=new mPDF('c','A4','','',32,25,27,25,16,13);

$mpdf->SetDisplayMode('fullpage');

$mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

		$jobexpencereportid = (int) $request->get('record');
    	$job_id = (int) $request->get('jobid');
    	$job_info_detail = Vtiger_Record_Model::getInstanceById($job_id, 'Job');


		$jobexpencereport_query = $adb->pquery('select * from vtiger_jobexpencereport INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid
							  where vtiger_jobexpencereport.job_id="'.$job_id.'" AND vtiger_jobexpencereport.jobexpencereportid = "'.$jobexpencereportid.'"
							  ');

		if ( $adb->num_rows($jobexpencereport_query) ) {

			$jobexpencereport = $adb->fetch_array($jobexpencereport_query);


			$jobexpencereportTax_query = $adb->pquery('select * from vtiger_jobexpencereport
							  INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid
							  where vtiger_jobexpencereport.job_id="'.$job_id.'" AND vtiger_jobexpencereport.invoice_tax = "'.$jobexpencereport['invoice_tax'].'"
							  ');



			/* test query for multiple pages*/
			/*
			$jobexpencereportTax_query = $adb->pquery('select * from vtiger_jobexpencereport
							  INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid
							  where vtiger_jobexpencereport.job_id="'.$job_id.'"
							  ');
			*/

			//$jobexpencereport = $adb->fetch_array($jobexpencereport_query);

			$bill_to_cf = $jobexpencereport['cf_1445']; // account id
			$billtoinfo = Vtiger_Record_Model::getInstanceById($bill_to_cf, 'Accounts');

			$bill_to = "";
			$company_address = "";
			$company_pobox = "";
			$company_postal = "";
			$company_city = "";
			$company_state = "";
			$company_country = "";

			if (@$billtoinfo->get('cf_2395') ) {
				$bill_to = $billtoinfo->get('cf_2395'); //Customer ID
			}
			if (@$billtoinfo->get('bill_street') ) {
				$company_address = $billtoinfo->get('bill_street'); //company_address
			}
			if (@$billtoinfo->get('bill_pobox') ) {
				$company_pobox = $billtoinfo->get('bill_pobox');
			}
			if (@$billtoinfo->get('bill_code') ) {
				$company_postal = $billtoinfo->get('bill_code');
			}
			if (@$billtoinfo->get('bill_city') ) {
				$company_city = $billtoinfo->get('bill_city');
			}
			if (@$billtoinfo->get('bill_state') ) {
				$company_state = $billtoinfo->get('bill_state');
			}
			if (@$billtoinfo->get('bill_country') ) {
				$company_country = $billtoinfo->get('bill_country');
			}

			$invoice_number = $jobexpencereport['invoice_no'];

			$invoice_date = date("M j, Y",strtotime($jobexpencereport['cf_1355'])); // invoice date

			$due_date = $jobexpencereport['cf_1355']; // invoice date
			$days = 0;
			$net_days = 30;

			if (@$billtoinfo->get('cf_1847') ) {
				$days = $billtoinfo->get('cf_1847'); //Credit Terms (days)
				$due_date = date('Y-m-d', strtotime($due_date. ' + '.$days.' days'));
			}

			if ($days > 0) {
				$net_days = $net_days+$days;
			}

			$payment_term_text = "Net $net_days Days";

			if (date('Y-m-d', strtotime($due_date)) == $jobexpencereport['cf_1355']) { // if due date and invoice date are same
				$payment_term_text = "100% Prepayment";
			}

			$reference_no = $jobexpencereport['invoice_tax'];

			//AED ACCOUNT
			$bank_account = "036-827244-001";
			$iban = "AE680200000036827244001";
			$cur_sym = 'AED';

			if ($jobexpencereport['cf_1234'] == 2) {
				//usd ACCOUNT
				$bank_account = "036-827244-100";
				$iban = "AE140200000036827244100";
				$cur_sym = 'USD';
			} else if ($jobexpencereport['cf_1234'] == 13) {
				//euro ACCOUNT
				$bank_account = "036-827244-101";
				$iban = "AE840200000036827244101";
				$cur_sym = 'EURO';
			}


			$this->setValue('bill_to', $bill_to);
			$this->setValue('company_address', $company_address);
			$this->setValue('company_pobox', $company_pobox);
			$this->setValue('company_postal', $company_postal);
			$this->setValue('company_city', $company_city);
			$this->setValue('company_state', $company_state);
			$this->setValue('company_country', $company_country);
			$this->setValue('invoice_number', $invoice_number);
			$this->setValue('invoice_date', $invoice_date);

			$this->setValue('reference_no', $reference_no);
			$this->setValue('shipping_mode', str_replace("|##|",",",$job_info_detail->get('cf_1711')));

			$this->setValue('due_date', $due_date);
			$this->setValue('payment_term_text', $payment_term_text);

			$owner_expense_user_info = Users_Record_Model::getInstanceById($job_info_detail->get('assigned_user_id'), 'Users');

			$coordinator = $owner_expense_user_info->get('first_name').' '.$owner_expense_user_info->get('last_name');
			$this->setValue('coordinator',htmlentities($coordinator, ENT_QUOTES, "UTF-8"));

			//$this->setValue('bank_account', $bank_account);
			//$this->setValue('iban', $iban);
			//$this->setValue('cur_sym', $cur_sym);

			//AED ACCOUNT
			$bank_account = "036-827244-001";
			$iban = "AE680200000036827244001";
			$cur_sym = 'AED';
			$ex_rate = 1;

			if ($jobexpencereport['cf_1234'] == 2) {
				//usd ACCOUNT
				$bank_account = "036-827244-100";
				$iban = "AE140200000036827244100";
				$cur_sym = 'USD';
				$ex_rate = 3.6725;

			} else if ($jobexpencereport['cf_1234'] == 13) {
				//euro ACCOUNT
				$bank_account = "036-827244-101";
				$iban = "AE840200000036827244101";
				$cur_sym = 'EURO';

				include('include/Exchangerate/exchange_rate_class.php');
				// get exchange rate from exchange rate table against DWC and invoice date
				$invoice_date_ex = $jobexpencereport['cf_1355'];
				$ex_rate = exchange_rate_currency($invoice_date_ex, 'DWC');
			}

			if ($ex_rate < 1) {
				echo "<div class='alert alert-warning'>No exchange rate found.</div>";
				die();
			}

			$this->setValue('ex_rate', $ex_rate);
			$this->setValue('bank_account', $bank_account);
			$this->setValue('iban', $iban);
			$this->setValue('cur_sym', $cur_sym);


			$total_amount = 0;
			$total_vat = 0;
			$total_vat_amt = 0;
			$total_gross_aed = 0;
			$total_gross_usd = 0;

			$loop_html = "";
			$loop_ind = 1;
			$des_height = 0;

			$descriptionArr = array();

			$total_rec = $adb->num_rows($jobexpencereportTax_query);

			while ($jobexpencereportTax = $adb->fetch_array($jobexpencereportTax_query)) {


				$description = wordwrap($jobexpencereportTax['cf_1365'],60,"<br>\n"); // description

				if (strpos($description, '<br>') !== false) {
				//if (strpos($description, '<br>') !== false) {
					$substr_count = substr_count($description, '<br>');
					$des_height = $des_height+$substr_count;
				}

				$job_number = $job_info_detail->get('cf_1198'); // job reference
				$customer_currency = $jobexpencereportTax['cf_1234'];

				$taxable_amount = $jobexpencereportTax['cf_1357'];
				//$taxable_amount_aed = $this->numberFormat($taxable_amount * 3.6725 , 2 ,  "." , "," ); // for DW file type for now , convert usd to aed
				$taxable_amount_aed = $this->numberFormat($taxable_amount * $ex_rate , 2 ,  "." , "" );
				//$taxable_amount_aed = $this->numberFormat($taxable_amount * 3.6725 , 2 ,  "." , "" ); // for DW file type for now , convert usd to aed
				$vat_persent = $jobexpencereportTax['cf_1228'];

				$vat_amount = $jobexpencereportTax['cf_1230'];
				//$vat_amount_aed = $this->numberFormat($vat_amount * 3.6725 , 2 ,  "." , ",");
				$vat_amount_aed = $this->numberFormat($vat_amount * $ex_rate , 2 ,  "." , ",");

				$gross_amount = $this->numberFormat($jobexpencereportTax['cf_1232'] , 2 ,  "." , "," );

				$gross_amount_aed = str_replace(",","",$taxable_amount_aed) + str_replace(",","",$vat_amount_aed);


				$total_amount = $total_amount + str_replace(",","",$taxable_amount_aed);
				//$total_vat = $total_vat + $vat_persent;
				if ($vat_persent > 0 and $total_vat < 1) {
					$total_vat = $vat_persent;
				}
				$total_vat_amt = $total_vat_amt + str_replace(",","",$vat_amount_aed);
				$total_gross_aed = $total_gross_aed + $gross_amount_aed;
				$total_gross_usd = $total_gross_usd + str_replace(",","",$gross_amount);

				/*
				$loop_html .= '<tr>
								<td style="border-left: 1px solid #000000; border-right: 1px solid #000000;" height="19" align="left" valign=bottom><font face="Arial" color="#000000">'.$description.'</font></td>
								<td style="border-right: 1px solid #000000;" align="center" valign=bottom>'.$job_number.'</td>
								<td style="border-right: 1px solid #000000;" align="center" valign=bottom>'.$taxable_amount_aed.'</td>
								<td style="border-right: 1px solid #000000;" align="center" valign=bottom>'.$vat_persent.'</td>
								<td style="border-right: 1px solid #000000;" align="center" valign=bottom>'.$vat_amount_aed.'</td>
								<td style="border-right: 1px solid #000000;" align="center" valign=bottom>'.$gross_amount_aed.'</td>
							</tr>';
				*/

				if ($substr_count > 0) {
					for($i=0; $i<$substr_count; $i++) {

						$descriptionArr = explode("<br>", $description);

						$loop_htmlArr[] = '<tr>
									<td style="border-left: 1px solid #000000; border-right: 1px solid #000000;" height="19" align="left" valign=bottom><font face="Arial" color="#000000">'.$descriptionArr[$i].'</font></td>
									<td style="border-right: 1px solid #000000;" align="center" valign=bottom></td>
									<td style="border-right: 1px solid #000000;" align="center" valign=bottom></td>
									<td style="border-right: 1px solid #000000;" align="center" valign=bottom></td>
									<td style="border-right: 1px solid #000000;" align="center" valign=bottom></td>
									<td style="border-right: 1px solid #000000;" align="center" valign=bottom></td>
								  </tr>';
					}
				}

				if (count($descriptionArr) > 0) {
					$description = $descriptionArr[$substr_count]; // last part of description
				}

				$loop_htmlArr[] = '<tr>
								<td style="border-left: 1px solid #000000; border-right: 1px solid #000000;" height="19" align="left" valign=bottom><font face="Arial" color="#000000">'.$description.'</font></td>
								<td style="border-right: 1px solid #000000;" align="center" valign=bottom>'.$job_number.'</td>
								<td style="border-right: 1px solid #000000;" align="center" valign=bottom>'.$taxable_amount_aed.'</td>
								<td style="border-right: 1px solid #000000;" align="center" valign=bottom>'.$vat_persent.'</td>
								<td style="border-right: 1px solid #000000;" align="center" valign=bottom>'.$vat_amount_aed.'</td>
								<td style="border-right: 1px solid #000000;" align="center" valign=bottom>'.$gross_amount_aed.'</td>
							  </tr>';

				$loop_ind++;
				$descriptionArr = array();
				$substr_count = 0;

			} //end of while

			//if ($loop_ind == $total_rec) {
				//$height_td = "200px;";
				//$height_td = $this->getcellheight($description,$total_rec);
				////$loop_html .= $this->gettrs($des_height,$total_rec);

				//$trsArr = $this->gettrs($des_height,$total_rec);
				$trsArr = $this->gettrs(count($loop_htmlArr));

				$loop_html_allArr = array_merge($loop_htmlArr,$trsArr);
				/*
				$loop_html .= '<tr>
                            <td style="border-left: 1px solid #000000;border-left: 1px solid #000000; border-right: 1px solid #000000; height:'.$height_td.'" height="19" align="left" valign=bottom><font face="Arial" color="#000000"></font></td>
                            <td style="border-right: 1px solid #000000;" align="center" valign=bottom></td>
							<td style="border-right: 1px solid #000000;" align="center" valign=bottom></td>
							<td style="border-right: 1px solid #000000;" align="center" valign=bottom></td>
							<td style="border-right: 1px solid #000000;" align="center" valign=bottom></td>
                            <td style="border-right: 1px solid #000000;" align="center" valign=bottom></td>
                        </tr>';
				*/
			//}

			foreach ($loop_html_allArr as $key=>$html) {
				//if () {}

				$rows_per_page = 14;

				$loop_html .= $html;

				if ($key == count($loop_html_allArr)-1) {
				$loop_html .= '<tr>
                            <td  style="border-top: 1px solid #000000; border-right: 1px solid #000000;"></td>
                            <td style="border: 1px solid #000000; border-left: 0px;" align="center" valign=bottom>Total</td>
                            <td style="border: 1px solid #000000; border-left: 0px;" align="center" valign=bottom>${total_amount}</td>
                            <td style="border: 1px solid #000000; border-left: 0px;" align="center" valign=bottom>${total_vat}</td>
                            <td style="border: 1px solid #000000; border-left: 0px;" align="center" valign=bottom>${total_vat_amt}</td>
                            <td style="border: 1px solid #000000; border-left: 0px;" align="center" valign=bottom>${total_gross_aed}</td>
                        </tr>';
				} else if (($key+1)%$rows_per_page == 0) {
					$loop_html .= '<tr>
                            <td  style="border-top: 1px solid #000000; border-right: 1px solid #000000;"></td>
                            <td style="border: 1px solid #000000; border-left: 0px;" align="center" valign=bottom>Continued</td>
                            <td style="border: 1px solid #000000; border-left: 0px;" align="center" valign=bottom>Continued</td>
                            <td style="border: 1px solid #000000; border-left: 0px;" align="center" valign=bottom>Continued</td>
                            <td style="border: 1px solid #000000; border-left: 0px;" align="center" valign=bottom>Continued</td>
                            <td style="border: 1px solid #000000; border-left: 0px;" align="center" valign=bottom>Continued</td>
                        </tr>';
				}
			}

			$this->setValue('loop_html', $loop_html);

			$this->setValue('total_amount', $total_amount);
			$this->setValue('total_vat', $total_vat);
			$this->setValue('total_vat_amt', $total_vat_amt);
			$this->setValue('total_gross_aed', $total_gross_aed);
			$this->setValue('amountInWords', $this->numToword($total_gross_aed));
			$this->setValue('total_gross_usd', $total_gross_usd);






		$mpdf->WriteHTML($this->_documentXML);

		$pdf_name = 'pdf_docs/invoice_tax_'.str_replace("/", "", $reference_no).'.pdf';

		$mpdf->Output($pdf_name, 'F');
		//header('Location:http://mb.globalink.net/vt60/'.$pdf_name);
		header('Location:'.$pdf_name);

		} // end of if

		/*
		$mpdf->WriteHTML($pdf_body, \Mpdf\HTMLParserMode::HTML_BODY);

		$mpdf->Output();
		*/


	}

	public function template($strFilename)
	{
		$path = dirname($strFilename);
        //$this->_tempFileName = $path.time().'.docx';
       // $this->_tempFileName = $path.'/'.time().'.txt';
		$this->_tempFileName = $strFilename;
		//copy($strFilename, $this->_tempFileName); // Copy the source File to the temp File

		$this->_documentXML = file_get_contents($this->_tempFileName);

	}

	 /**
     * Set a Template value
     *
     * @param mixed $search
     * @param mixed $replace
     */
    public function setValue($search, $replace) {

        if(substr($search, 0, 2) !== '${' && substr($search, -1) !== '}') {
            $search = '${'.$search.'}';
        }
      // $replace =  htmlentities($replace, ENT_QUOTES, "UTF-8");
        if(!is_array($replace)) {
           // $replace = utf8_encode($replace);
		   $replace =iconv('utf-8', 'utf-8', $replace);
        }

        $this->_documentXML = str_replace($search, $replace, $this->_documentXML);

    }

	 /**
     * Save Template
     *
     * @param string $strFilename
     */
    public function save($strFilename) {
        if(file_exists($strFilename)) {
            unlink($strFilename);
        }

        //$this->_objZip->extractTo('fleet.txt', $this->_documentXML);

		file_put_contents($this->_tempFileName, $this->_documentXML);

        // Close zip file
       /* if($this->_objZip->close() === false) {
            throw new Exception('Could not close zip file.');
        }*/

        rename($this->_tempFileName, $strFilename);
    }

	public function loadTemplate($strFilename) {
        if(file_exists($strFilename)) {
            $template = $this->template($strFilename);
            return $template;
        } else {
            trigger_error('Template file '.$strFilename.' not found.', E_ERROR);
        }
    }

    public function get_invoice_tax_body ($request) {

		global $adb;

		$jobexpencereportid = (int) $request->get('record');
    	$job_id = (int) $request->get('jobid');
    	$job_info_detail = Vtiger_Record_Model::getInstanceById($job_id, 'Job');


		$jobexpencereport_query = $adb->pquery('select * from vtiger_jobexpencereport INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid
							  where vtiger_jobexpencereport.job_id="'.$job_id.'" AND vtiger_jobexpencereport.jobexpencereportid = "'.$jobexpencereportid.'"
							  ');

		if ( $adb->num_rows($jobexpencereport_query) ) {

			$jobexpencereport = $adb->fetch_array($jobexpencereport_query);

			$jobexpencereportTax_query = $adb->pquery('select * from vtiger_jobexpencereport
							  INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid
							  where vtiger_jobexpencereport.job_id="'.$job_id.'" AND vtiger_jobexpencereport.invoice_tax = "'.$jobexpencereport['invoice_tax'].'"
							  ');
			//$jobexpencereport = $adb->fetch_array($jobexpencereport_query);

			$bill_to_cf = $jobexpencereport['cf_1445']; // account id
			$billtoinfo = Vtiger_Record_Model::getInstanceById($bill_to_cf, 'Accounts');

			$bill_to = "";
			$bill_to2 = "";
			$bill_to3 = "";
			$bill_to4 = "";
			$company_address = "";
			$company_pobox = "";
			$company_postal = "";
			$company_city = "";
			$company_state = "";
			$company_country = "";

			if (@$billtoinfo->get('cf_2395') ) {
				$bill_to = $billtoinfo->get('cf_2395'); //Customer ID
				$bill_to_str = wordwrap($bill_to,38,"<br>");
				$bill_to_arr = explode("<br>",$bill_to_str);
				if (count($bill_to_arr) > 0) {
					$bill_to = $bill_to_arr[0];
					$bill_to2 = $bill_to_arr[1];
					if ( isset($bill_to_arr[2]) ) {
						$bill_to3 = $bill_to_arr[2];
					}
					if ( isset($bill_to_arr[3]) ) {
						$bill_to4 = $bill_to_arr[3];
					}

				}
			}
			if (@$billtoinfo->get('bill_street') ) {
				$company_address = $billtoinfo->get('bill_street'); //company_address
			}
			if (@$billtoinfo->get('bill_pobox') ) {
				$company_pobox = $billtoinfo->get('bill_pobox');
			}
			if (@$billtoinfo->get('bill_code') ) {
				$company_postal = $billtoinfo->get('bill_code');
			}
			if (@$billtoinfo->get('bill_city') ) {
				$company_city = $billtoinfo->get('bill_city');
			}
			if (@$billtoinfo->get('bill_state') ) {
				$company_state = $billtoinfo->get('bill_state');
			}
			if (@$billtoinfo->get('bill_country') ) {
				$company_country = $billtoinfo->get('bill_country');
			}

			$invoice_number = $jobexpencereport['invoice_no'];

			$due_date = $jobexpencereport['cf_1355']; // invoice date

			$days = 0;
			$net_days = 30;

			if (@$billtoinfo->get('cf_1847') ) {
				$days = $billtoinfo->get('cf_1847'); //Credit Terms (days)
				$due_date = date('Y-m-d', strtotime($due_date. ' + '.$days.' days'));
			}

			if ($days > 0) {
				$net_days = $net_days+$days;
			}

			$payment_term_text = "Net $net_days Days";

			if (date('Y-m-d', strtotime($due_date)) == $jobexpencereport['cf_1355']) { // if due date and invoice date are same
				$payment_term_text = "100% Prepayment";
			}

			$reference_no = $jobexpencereport['invoice_tax'];

			//AED ACCOUNT
			$bank_account = "036-827244-001";
			$iban = "AE680200000036827244001";
			$cur_sym = 'AED';
			$ex_rate = 1;

			if ($jobexpencereport['cf_1234'] == 2) {
				//usd ACCOUNT
				$bank_account = "036-827244-100";
				$iban = "AE140200000036827244100";
				$cur_sym = 'USD';
				$ex_rate = 3.6725;

			} else if ($jobexpencereport['cf_1234'] == 13) {
				//euro ACCOUNT
				$bank_account = "036-827244-101";
				$iban = "AE840200000036827244101";
				$cur_sym = 'EURO';

				include('include/Exchangerate/exchange_rate_class.php');
				// get exchange rate from exchange rate table against DWC and invoice date
				$invoice_date_ex = $jobexpencereport['cf_1355'];
				$ex_rate = exchange_rate_currency($invoice_date_ex, 'DWC');

				//$owner_expense_user_info = Users_Record_Model::getInstanceById($job_info_detail->get('assigned_user_id'), 'Users');
			}

			if ($ex_rate < 1) {
				echo "<div class='alert alert-warning'>No exchange rate found.</div>";
				die();
			}

			$owner_expense_user_info = Users_Record_Model::getInstanceById($job_info_detail->get('assigned_user_id'), 'Users');

			$coordinator = $owner_expense_user_info->get('first_name').' '.$owner_expense_user_info->get('last_name');
			//$this->setValue('selling_from',htmlentities($selling_from, ENT_QUOTES, "UTF-8"));

		$htmlString = '

		<table cellspacing="0" border="0" style="font-size: 11px;">
			<!--
			<colgroup width="19"></colgroup>
			<colgroup width="84"></colgroup>
			<colgroup width="68"></colgroup>
			<colgroup width="84"></colgroup>
			<colgroup width="163"></colgroup>
			<colgroup width="78"></colgroup>
			<colgroup width="108"></colgroup>
			<colgroup width="105"></colgroup>
			<colgroup width="50"></colgroup>
			<colgroup width="94"></colgroup>
			<colgroup width="132"></colgroup>
			-->
			<!--
			<tr>
				<td height="12" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				</tr>
			-->
			<tr>
				<td height="57" align="left" valign=bottom style="color: #969696; font-size: 28.0pt; font-family: Arial Black;" class="arial_black"><b><font face="Arial Black" size=6 color="#969696">TAX INVOICE</font></b></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td colspan="" align="left" valign=bottom><font face="Arial" color="#000000"><br><!--<img src="include/logo_invoice.png" width=188 height=70>--></font></td>
				</tr>
			<tr>
				<td height="12" align="left" valign=bottom><font face="Arial" color="#000000">Invoice Number: '.$invoice_number.'</font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" vali gn=bottom><font face="Arial" color="#000000"><br></font></td>
				</tr>
			<tr>
				<td height="12" align="left" valign=bottom><font face="Arial" color="#000000">Invoice Date: '.date("M j, Y",strtotime($jobexpencereport['cf_1355'])).'</font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				</tr>
			<!--
			<tr>
				<td height="12" align="left" valign=bottom><font face="Arial" color="#000000">Customer ID: '.$bill_to.'</font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				</tr>
			-->
			<!--
			<tr>
				<td height="12" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font  face="Arial" color ="#000000"><br></font></td>
				</tr>
			-->
			<tr>
				<td height="12" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="center" valign=bottom><b><font face="Arial" color="#000000">Globalink Logistics DWC LLC</font></b></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
			</tr>
			<tr>
				<td height="12" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><b><font face="Arial" color="#000000"><br></font></b></td>
				<td align="center" valign=bottom><b><font face="Arial" color="#000000"> Office 107 & 108, Building A5, Business Park,</font></b></td>
				<td align="right" valign=bottom><b><font face="Arial" color="#000000"><br></font></b></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
			</tr>
			<tr>
				<td height="12" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><b><font face="Arial" color="#000000"><br></font></b></td>
				<td align="center" valign=bottom><b><font face="Arial" color="#000000">Dubai World Central, P.O.Box 712343, Dubai, UAE</font></b></td>
				<td align="right" valign=bottom><b><font face="Arial" color="#000000"><br></font></b></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
			</tr>

			<tr>
				<td height="12" style="border-bottom: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="right" valign=bottom><b><font face="Arial" color="#000000"><br></font></b></td>
				<td align="right" valign=bottom><b><font face="Arial" color="#000000"><br></font></b></td>
				<td align="right" valign=bottom><b><font face="Arial" color="#000000"><br></font></b></td>
				<td align="right" valign=bottom><b><font face="Arial" color="#000000"><br></font></b></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
			</tr>
			<tr>
				<td height="12" style="border-bottom: 1px solid #000000; border-left: 1px solid #000000" align="left" valign=bottom><b><font face="Arial" color="#000000">Company Name:</font></b></td>
				<td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-right: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-top: 1px solid #000000; border-left: 1px solid #000000" align="left" valign=bottom><b><font face="Arial" color="#000000">Company Address</font></b></td>
				<td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-right: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
			</tr>
			<tr>
				<td height="12" style="border-top: 1px solid #000000; border-left: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000">'.$bill_to.'</font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-top: 1px solid #000000; border-right: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-top: 1px solid #000000; border-left: 1px solid #000000;" align="left" valign=bottom><font face="Arial" color="#000000">'.$company_address.'</font></td>

				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-top: 1px solid #000000; border-right: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>

			</tr>
			<tr>
				<td height="12" style="border-left: 1px solid #000000" align="left" valign=bottom><font face="Microsoft YaHei" color="#000000">'.$bill_to2.'</font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-right: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-left: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000">'.$company_pobox.' '.$company_city.'</font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-right: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
			</tr>
			<tr>
				<td height="12" style="border-left: 1px solid #000000" align="left" valign=bottom><u><font face="Arial" color="#0000FF">'.$bill_to3.'</font></u></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-right: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-left: 1px solid #000000" align="left" valign=bottom><font face="Microsoft YaHei" color="#000000">'.$company_postal.' '.$company_state.' </font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-right: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
			</tr>
			<tr>
				<td height="12" style="border-left: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000">'.$bill_to4.'</font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-right: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-left: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000">'.$company_country.'</font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-right: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
			</tr>
			<tr>
				<td height="12" style="border-left: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-right: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-left: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-right: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
			</tr>
			<tr>
				<td height="12" style="border-bottom: 1px solid #000000; border-left: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-bottom: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-bottom: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-bottom: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-bottom: 1px solid #000000; border-right: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-bottom: 1px solid #000000; border-left: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-bottom: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-bottom: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-bottom: 1px solid #000000; border-right: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
			</tr>
			<tr>
				<td height="12" style="border-left: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
			</tr>
			<tr>
				<td height="12" style="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=4 align="center" valign=bottom><b><font face="Arial" color="#000000">Reference</font></b></td>
				<td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=2 align="center" valign=bottom><b><font face="Arial" color="#000000">Shipping Mode</font></b></td>
				<td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=2 align="center" valign=bottom><b><font face="Arial" color="#000000">Due Date</font></b></td>
				<td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=3 align="center" valign=bottom><b><font face="Arial" color="#000000">Coordinator</font></b></td>
				</tr>
			<tr>
				<td height="12" style="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=4 align="center" valign=bottom><font face="Arial" color="#000000">'.$reference_no.'</font></td>
				<td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=2 align="center" valign=middle><font face="Arial" color="#000000">'.str_replace("|##|", ",", $job_info_detail->get('cf_1711')).'</font></td>
				<td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=2 align="center" valign=bottom sdval="43677" sdnum="1033;1033;M/D/YYYY"><font face="Arial" color="#000000">'.$due_date.'</font></td>
				<td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=3 align="center" valign=bottom><font face="Arial" color="#000000">'.$coordinator.'</font></td>
				</tr>
			<tr>
				<td height="12" style="border-left: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
			</tr>

			<tr>
				<td rowspan="2" colspan="6" height="11" style="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000;" align="left" valign=bottom><b><font face="Arial" color="#000000">Description</font></b></td>

				<td style="width:120px; border-top: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000" align="center" valign=bottom><b><font face="Arial" color="#000000"></font></b></td>
				<td style="line-height: 1; width:85px; border-top: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000" align="center" valign=bottom><b><font face="Arial" color="#000000">Taxable</font></b></td>
				<td style="border-top: 1px solid #000000; border-left: 1px solid #000000" align="center" valign=bottom><b><font face="Arial" color="#000000"></font></b></td>
				<td style="width:85px; border-top: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000" align="left" valign=bottom><b><font face="Arial" color="#000000"></font></b></td>
				<td style="line-height: 1.6; width:85px; border-top: 1px solid #000000; border-right: 1px solid #000000" align="center" valign=bottom><b><font face="Arial" color="#000000">Gross</font></b></td>
			</tr>
			<tr>
			<!--
				<td height="24" style="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000;" align="left" valign=bottom><b><font face="Arial" color="#000000">Description</font></b></td>

				<td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000" align="left" valign=bottom><b><font face="Arial" color="#000000"><br></font></b></td>
				<td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000" align="left" valign=bottom><b><font face="Arial" color="#000000"><br></font></b></td>
				<td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000" align="left" valign=bottom><b><font face="Arial" color="#000000"><br></font></b></td>
				<td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000" align="left" valign=bottom><b><font face="Arial" color="#000000"><br></font></b></td>
				<td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000" align="left" valign=bottom><b><font face="Arial" color="#000000"><br></font></b></td>
			-->
				<td height="11" style="width:120px; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000" align="center" valign=bottom><b><font face="Arial" color="#000000">Job Number</font></b></td>
				<td style="line-height: 1; width:85px; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000" align="center" valign=bottom><b><font face="Arial" color="#000000">Amount AED</font></b></td>
				<td style="border-bottom: 1px solid #000000; border-left: 1px solid #000000" align="center" valign=bottom><b><font face="Arial" color="#000000">Vat %</font></b></td>
				<td style="width:85px; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000" align="left" valign=bottom><b><font face="Arial" color="#000000">VAT Amount</font></b></td>
				<td style="line-height: 1.6; width:85px; border-bottom: 1px solid #000000; border-right: 1px solid #000000" align="center" valign=bottom><b><font face="Arial" color="#000000">Amount AED</font></b></td>
			</tr>

			<!--
			<tr>
				<td style="border-top: 1px solid #000000; border-left: 1px solid #000000" height="18" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-top: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-left: 1px solid #000000; border-right: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-left: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-left: 1px solid #000000; border-right: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-top: 1px solid #000000; border-right: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
			</tr>
			-->
			';

			$total_amount = 0;
			$total_vat = 0;
			$total_vat_amt = 0;
			$total_gross_aed = 0;
			$total_gross_usd = 0;

			while ($jobexpencereportTax = $adb->fetch_array($jobexpencereportTax_query)) {

			//$total_amount = $total_amount+$jobexpencereportTax['cf_1357'];
			//$total_gross = $total_gross+$jobexpencereportTax['cf_1232'];

			$description = $jobexpencereportTax['cf_1365']; // description
			$job_number = $job_info_detail->get('cf_1198'); // job reference
			$customer_currency = $jobexpencereportTax['cf_1234'];

			//$taxable_amount = $this->numberFormat($jobexpencereportTax['cf_1357'] , 2 ,  "." , "," );
			$taxable_amount = $jobexpencereportTax['cf_1357'];
			$taxable_amount_aed = $this->numberFormat($taxable_amount * $ex_rate , 2 ,  "." , "" );
			//$taxable_amount_aed = $this->numberFormat($taxable_amount * 3.6725 , 2 ,  "." , "" ); // for DW file type for now , convert usd to aed
			$vat_persent = $jobexpencereportTax['cf_1228'];
			//$vat_amount = $this->numberFormat($jobexpencereportTax['cf_1230'] , 2 ,  "." , "," );
			$vat_amount = $jobexpencereportTax['cf_1230'];
			//$vat_amount_aed = $this->numberFormat($vat_amount * 3.6725 , 2 ,  "." , "");
			$vat_amount_aed = $this->numberFormat($vat_amount * $ex_rate , 2 ,  "." , "");
			//$gross_amount = $this->numberFormat($jobexpencereportTax['cf_1232'] , 2 ,  "." , "," );
			$gross_amount = $this->numberFormat($jobexpencereportTax['cf_1232'] , 2 ,  "." , "" );
			//$gross_amount_aed = $this->numberFormat($taxable_amount_aed + $vat_amount_aed); // for DW file type for now , convert usd to aed
			$gross_amount_aed = $taxable_amount_aed + $vat_amount_aed;



			$total_amount = $total_amount + $taxable_amount_aed;
			//$total_vat = $total_vat + $vat_persent;
			if ($vat_persent > 0 and $total_vat < 1) {
				$total_vat = $vat_persent;
			}
			$total_vat_amt = $total_vat_amt + $vat_amount_aed;
			$total_gross_aed = $total_gross_aed + $gross_amount_aed;
			$total_gross_usd = $total_gross_usd + $gross_amount;
			$total_rec_excel = $adb->num_rows($jobexpencereportTax_query);

			$htmlString .= '
			<tr>
				<td width="5%" height="12" style="border-left: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000">'.$description.'</font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-left: 1px solid #000000; border-right: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000">'.$job_number.'</font></td>
				<td style="border-right: 1px solid #000000" align="right" valign=bottom><font face="Arial" color="#000000"> '.$taxable_amount_aed.'   </font></td>
				<td style="border-left: 1px solid #000000" align="center" valign=bottom><font face="Arial" color="#000000">'.$vat_persent.' %</font></td>
				<td style="border-left: 1px solid #000000; border-right: 1px solid #000000" align="right" valign=bottom><font face="Arial" color="#000000">'.$vat_amount_aed.'</font></td>
				<td style="border-right: 1px solid #000000" align="right" valign=bottom><font face="Arial" color="#000000"> '.$gross_amount_aed.' </font></td>
			</tr>';
			}


			/*set height*/

			$min_trs = 13;
			//$total_rec_excel
			if ($total_rec_excel < $min_trs) {
				$limit = $min_trs - $total_rec_excel;
				for($i=0; $i<=$limit; $i++) {
					$htmlString .= '
					<tr>
						<td height="12" style="border-left: 1px solid #000000" align="left"></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td style="border-left: 1px solid #000000; border-right: 1px solid #000000"></td>
						<td style="border-right: 1px solid #000000"></td>
						<td style="border-left: 1px solid #000000"></td>
						<td style="border-left: 1px solid #000000; border-right: 1px solid #000000"></td>
						<td style="border-right: 1px solid #000000"></td>
					</tr>';
				}
			}

			$htmlString .= '
			<!--
			<tr>
				<td height="12" style="border-bottom: 1px solid #000000; border-left: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-bottom: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-bottom: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-bottom: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-bottom: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-bottom: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-bottom: 1px solid #000000; border-left: 1px solid #000000" align="center" valign=middle><font face="Arial" color="#000000"><br></font></td>
				<td style="border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-bottom: 1px solid #000000; border-right: 1px solid #000000" align="left" valign=bottom ><font face="Arial" color="#000000"><br></font></td>
			</tr>
			-->
			<tr>
				<td height="12" style="border-top: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-top: 1px solid #000000;" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-top: 1px solid #000000;" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-top: 1px solid #000000;" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-top: 1px solid #000000;" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-top: 1px solid #000000;" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000">Total</font></td>
				<td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000" align="right" valign=bottom ><b><font face="Arial" color="#000000"> '.$total_amount.' </font></b></td>
				<td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000" align="center" valign=bottom ><b><font face="Arial" color="#000000">'.$total_vat.' %</font></b></td>
				<td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000" align="right" valign=bottom ><b><font face="Arial" color="#000000">'.$total_vat_amt.' </font></b></td>
				<td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000" align="right" valign=bottom ><b><font face="Arial" color="#000000"> '.$total_gross_aed.' </font></b></td>
			</tr>
			<tr>
				<td height="12" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom ><b><font face="Arial" color="#000000"><br></font></b></td>
				<td align="left" valign=bottom ><b><font face="Arial" color="#000000"><br></font></b></td>
				<td align="left" valign=bottom ><b><font face="Arial" color="#000000"><br></font></b></td>
				<td align="left" valign=bottom ><b><font face="Arial" color="#000000"><br></font></b></td>
			</tr>
			<tr>
				<td height="12" align="left" valign=bottom><font face="Arial" color="#000000">Total: '.$this->numToword($total_gross_aed).'<!--Forty one thousand Three Hundred Seventy two Dirhams and 51 fils--></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
			</tr>
			<tr>
				<td height="12" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
			</tr>
			<tr>
				<td height="12" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000">Amount in '.$cur_sym.'</font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="right" valign=bottom><font face="Arial" color="#000000"> '.$total_gross_usd.' </font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
			</tr>

			<tr>
				<td height="12" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
			</tr>
			<tr>
				<td height="12" style="border-left: 1px solid #000000" align="left" valign=bottom><b><font face="Arial" color="#000000"><!--EXCHANGE RATE : 1 USD = 3.6725 AED-->
									EXCHANGE RATE : 1 '.$cur_sym.' = '.$ex_rate.' AED
				</font></b></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
			</tr>
			<tr>
				<td height="12" style="border-left: 1px solid #000000" align="left" valign=bottom><b><font face="Arial" color="#000000">Payment Terms:</font></b></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
			</tr>
			<tr>
				<td height="12" style="border-left: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000">a)</font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000">'.$payment_term_text.'</font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
			</tr>
			<tr>
				<td height="12" style="border-left: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000">b)</font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000">Interest charge of 2% could be applied if the payment is not made within the specified credit period.</font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
			</tr>
			<tr>
				<td height="12" style="border-left: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000">c)</font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000">Please mention our invoice number(s) on your remittance instructions.</font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
			</tr>
			<tr>
				<td height="12" style="border-left: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000">d)</font></td>
				<td align="left" valign=bottom><i><font face="Arial" color="#000000">Please note all the bank charges shall be borne by the customer &amp; the remittance should be as per an amount equal to the above invoice value</font></i></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
			</tr>


			<tr>
				<td height="12" colspan=11 align="center" valign=bottom><b><font face="Arial" color="#339966"><br></font></b></td>
			</tr>
			<tr>
				<td height="12" colspan=11 align="center" valign=bottom><b><font face="Arial" color="#339966">This invoice is system generated and does not require any stamp or signature</font></b></td>
			</tr>
			<tr>
				<td height="8" style="border-bottom: 1px solid #000000; border-left: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-bottom: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-bottom: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-bottom: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-bottom: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-bottom: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-bottom: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-bottom: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-bottom: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-bottom: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-bottom: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
			</tr>
			<tr>
				<td height="12" style="border-left: 1px solid #000000" align="left" valign=bottom><b><font face="Arial" color="#000000">Please remit to the following bank details:</font></b></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-left: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000">  Beneficiary: Globalink Logistics DWC-LLC</font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
			</tr>
			<tr>
				<td height="12" style="border-left: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000">Beneficiary Bank: HSBC BANK MIDDLE EAST LIMITED</font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-left: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000">  SWIFT CODE: BBMEAEAD </font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
			</tr>
			<tr>
				<td height="12" style="border-left: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000">HSBC Bank Middle East Limited Ltd, P O Box 66, Dubai, U.A.E</font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-left: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000">IBAN: '.$iban.'</font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
			</tr>
			<tr>
				<td height="12" style="border-left: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000">TRN: 100478115700003</font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td style="border-left: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000"> Account # ('.$cur_sym.'): '.$bank_account.'</font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
			</tr>

			<tr>
				<td height="12" style="border-left: 1px solid #000000" align="left" valign=bottom><font face="Arial" color="#000000">License Number: 3805</font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
			</tr>
			<tr>
				<td height="12" style="border-left: 1px solid #000000" align="left" valign=bottom><b><font face="Arial" color="#000000"></font></b></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
				<td align="left" valign=bottom><font face="Arial" color="#000000"><br></font></td>
			</tr>
		</table>
		              ';

		} else {
			$htmlString = "";
		}

	    return $htmlString;

    }

	function numToword($num) {
		//$num = 88.51;
		require_once 'Numbers/Words.php';
		$number = $num;
		$decimal = "";
		$words = "";



		if ( is_float( $num ) ) {
	        $num_arr = explode(".",$num);
	        $number = $num_arr[0];
	        $decimal = $num_arr[1];
	        $words .= Numbers_Words::toWords($num) . " Dirham(s)";
	        $words .= " and ". Numbers_Words::toWords($decimal) ." fil(s)";
	    } else {
	    	$words .= Numbers_Words::toWords($num);
	    }

		return ucwords($words);
	}

	/*
	function numberFormat($number, $decimals = 2, $sep = ".", $k = ","){
	    $number = bcdiv($number, 1, $decimals); // Truncate decimals without rounding
	    return number_format($number, $decimals, $sep, $k); // Format the number
	}
	*/

	function numberFormat($number, $decimals = 0, $decPoint = '.' , $thousandsSep = ',') {
	    $negation = ($number < 0) ? (-1) : 1;
	    $coefficient = pow(10, $decimals);
	    $number = $negation * floor((string)(abs($number) * $coefficient)) / $coefficient;
	    return number_format($number, $decimals, $decPoint, $thousandsSep);
	}

	function getcellheight($description,$total_rec) {
		$cell_height = "";

		if ($total_rec == 1) {
			//$cell_height = '250px';
			$cell_height = '400px';
		}

		return $cell_height;
	}

	//function gettrs($des_height,$total_rec) {
	function gettrs($total_rec) {
		$trs = "";
		$trsArr = array();
		//$rows_per_page = 12;
		$rows_per_page = 14;
		//$rows_per_page = 15;
		//$new_total_rec = $total_rec+$des_height;
		$new_total_rec = $total_rec;

		if ($new_total_rec > $rows_per_page) {
			$quotient = (int) ($new_total_rec / $rows_per_page);

			$multiple = $quotient * $rows_per_page;
			$last_page_rec = $new_total_rec - $multiple;

			$limit = $rows_per_page - $last_page_rec; // multiple page

		} else {
			$limit = $rows_per_page - $new_total_rec; // single page
		}




		//$rem = $total_rec%3;

		//for($i=0; $i<=$limit; $i++) {
		for($i=1; $i<=$limit; $i++) {
			//$trs .= "<tr><td></td></tr>";
			$trsArr[] = '<tr>
                            <td style="border-left: 1px solid #000000;border-left: 1px solid #000000; border-right: 1px solid #000000; " height="19"></td>
                            <td style="border-right: 1px solid #000000;" ></td>
							<td style="border-right: 1px solid #000000;" ></td>
							<td style="border-right: 1px solid #000000;" ></td>
							<td style="border-right: 1px solid #000000;" ></td>
                            <td style="border-right: 1px solid #000000;" ></td>
                        </tr>';
		}

		return $trsArr;
	}

}
