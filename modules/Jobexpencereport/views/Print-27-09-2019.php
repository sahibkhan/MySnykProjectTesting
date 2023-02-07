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
		
		if($expnese_type=='Fleet')
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
					$profit_share_data[] = array('cost' => number_format ( 0 , 2 ,  "." , "," ),
												 'job_profit'  =>  number_format ( 0 , 2 ,  "." , "," ),
												 'job_ref_no' => $model->get('cf_5884'),
												 'job_id' => '',
												 //'internal_selling' => @$row_jrer_internal_selling['internal_selling'],
												 'internal_selling' => $model->get('cf_5826'),
												// 'user_id' => $current_user->getId()
												'user_id' => $roundtrip_info->get('assigned_user_id')
												 );
													 
					$sum_of_cost += 0;
					$sum_of_job_profit +=0;
					//$sum_of_internal_selling +=@$row_jrer_internal_selling['internal_selling'];
					$sum_of_internal_selling +=$model->get('cf_5826');
					continue;
				}
				
				$sourceModule_job 	= 'Job';	
				$job_info = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule_job);
				
				$job_reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info->get('cf_1186'));
				$file_title_currency = $job_reporting_currency;
				
				$adb_buy_local = PearDatabase::getInstance();										   
				$sum_buy_local_currency_net =  "SELECT vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
													   vtiger_jobexpencereportcf.cf_1216 as buy_invoice_date,
													   vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
												FROM `vtiger_jobexpencereport` 
												INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
												INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
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
						
						$adb_internal = PearDatabase::getInstance();	
						$internal_selling_arr = "SELECT vtiger_jobexpcf.cf_1263 as internal_selling
												FROM vtiger_jobexp 
												INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexp.jobexpid 
 												INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
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
		
				
		
		
		$mpdf->Output($pdf_name, 'F');
		//header('Location:http://mb.globalink.net/vt60/'.$pdf_name);
		header('Location:'.$pdf_name);
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
				$sum_buy_local_currency_net =  "SELECT vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
													   vtiger_jobexpencereportcf.cf_1216 as buy_invoice_date,
													   vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
												FROM `vtiger_jobexpencereport` 
												INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
												INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
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
						
						$adb_internal = PearDatabase::getInstance();	
						$internal_selling_arr = "SELECT vtiger_jobexpcf.cf_1263 as internal_selling
												FROM vtiger_jobexp 
												INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexp.jobexpid 
 												INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
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
		
				
		
		
		$mpdf->Output($pdf_name, 'F');
		//header('Location:http://mb.globalink.net/vt60/'.$pdf_name);
		header('Location:'.$pdf_name);
		exit;		
		
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
		$this->setValue('dateadded',date('d.m.Y', strtotime($wagontrip_info_detail->get('CreatedTime'))));				
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
		$railway_fleet_sql =  "SELECT * FROM `vtiger_railwayfleet` 
							INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_railwayfleet.railwayfleetid 
							INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR 
															   vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
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
			  $q_cur = mysql_query('select * from vtiger_currency_info where id = "'.$CurId.'"');
			  $row_cur = mysql_fetch_array($q_cur);
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
					$profit_share_data[] = array('cost' => number_format ( 0 , 2 ,  "." , "," ),
												 'job_profit'  =>  number_format ( 0 , 2 ,  "." , "," ),
												 'job_ref_no' => $model->get('cf_5884'),
												 'job_id' => '',
												// 'internal_selling' => @$row_jrer_internal_selling['internal_selling'],
												 'internal_selling' => number_format ( $model->get('cf_5826') , 2 ,  "." , "," ),
												 'internal_selling_forpercent' => $model->get('cf_5826'),
												 'user_id' => $current_user->getId(),
												 'origin_city' => $model->get('cf_5822'),
												 'destination_city' => $model->get('cf_5824'),
												 'selling_created_time' => $model->getDisplayValue('CreatedTime'),
												 'railway_trip_date' => date('d.m.Y', strtotime($model->getDisplayValue('cf_5818')))
												 );	
					
					
						$sum_of_cost += 0;
						$sum_of_job_profit +=0;
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
							
					$rs_query  = mysql_query("select * from vtiger_jobtask 
											  where job_id='".$job_id."' and user_id='".$railwayfleet_info->get('assigned_user_id')."' limit 1");
					$row_task = mysql_fetch_array($rs_query);
										  
					if(mysql_num_rows($rs_query)>0)
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
				$sum_buy_local_currency_net =  "SELECT vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
													   vtiger_jobexpencereportcf.cf_1216 as buy_invoice_date,
													   vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
												FROM `vtiger_jobexpencereport` 
												INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
												INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
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
						
						$adb_internal = PearDatabase::getInstance();	
						$internal_selling_arr = "SELECT vtiger_jobexpcf.cf_1263 as internal_selling
												FROM vtiger_jobexp 
												INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexp.jobexpid 
 												INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
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
													 'selling_created_time' => $model->getDisplayValue('CreatedTime'),
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
				  $q_cur = mysql_query('select * from vtiger_currency_info where id = "'.$CurId.'"');
				  $row_cur = mysql_fetch_array($q_cur);
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
		
		
		
		
		$jrer_sum_sql =  "SELECT sum(vtiger_jobexpencereportcf.cf_1347) as buy_local_currency_gross, 
								     sum(vtiger_jobexpencereportcf.cf_1349) as buy_local_currency_net,
									 sum(vtiger_jobexpencereportcf.cf_1351) as expected_buy_local_currency_net, 
									 sum(vtiger_jobexpencereportcf.cf_1353) as variation_expected_and_actual_buying
									 FROM `vtiger_jobexpencereport` 
							  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
 INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
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
		
		 $jrer_sql_expense =  "SELECT vtiger_jobexpencereportcf.cf_1347 as buy_local_currency_gross, 
								     vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
									 vtiger_jobexpencereportcf.cf_1351 as expected_buy_local_currency_net, 
									 vtiger_jobexpencereportcf.cf_1353 as variation_expected_and_actual_buying,
									 vtiger_jobexpencereportcf.cf_1216 as expense_invoice_date,
									 vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
									 FROM `vtiger_jobexpencereport` 
							  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
 INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
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
				$paytoinfo = Vtiger_Record_Model::getInstanceById($pay_to_id, 'Accounts');
				$company_accountname = @$paytoinfo->get('accountname');
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
		$this->setValue('dateadded',date('d.m.Y', strtotime($wagontrip_info_detail->get('CreatedTime'))));				
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
		$railway_fleet_sql =  "SELECT * FROM `vtiger_railwayfleet` 
							INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_railwayfleet.railwayfleetid 
							INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR 
															   vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
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
			  $q_cur = mysql_query('select * from vtiger_currency_info where id = "'.$CurId.'"');
			  $row_cur = mysql_fetch_array($q_cur);
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
					$profit_share_data[] = array('cost' => number_format ( 0 , 2 ,  "." , "," ),
												 'job_profit'  =>  number_format ( 0 , 2 ,  "." , "," ),
												 'job_ref_no' => $model->get('cf_5884'),
												 'job_id' => '',
												// 'internal_selling' => @$row_jrer_internal_selling['internal_selling'],
												 'internal_selling' => number_format ( $model->get('cf_5826') , 2 ,  "." , "," ),
												 'internal_selling_forpercent' => $model->get('cf_5826'),
												 'user_id' => $current_user->getId(),
												 'origin_city' => $model->get('cf_5822'),
												 'destination_city' => $model->get('cf_5824'),
												 'selling_created_time' => $model->getDisplayValue('CreatedTime'),
												 'railway_trip_date' => date('d.m.Y', strtotime($model->getDisplayValue('cf_5818')))
												 );	
					
					
						$sum_of_cost += 0;
						$sum_of_job_profit +=0;
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
							
					$rs_query  = mysql_query("select * from vtiger_jobtask 
											  where job_id='".$job_id."' and user_id='".$railwayfleet_info->get('assigned_user_id')."' limit 1");
					$row_task = mysql_fetch_array($rs_query);
										  
					if(mysql_num_rows($rs_query)>0)
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
				$sum_buy_local_currency_net =  "SELECT vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
													   vtiger_jobexpencereportcf.cf_1216 as buy_invoice_date,
													   vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
												FROM `vtiger_jobexpencereport` 
												INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
												INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
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
						
						$adb_internal = PearDatabase::getInstance();	
						$internal_selling_arr = "SELECT vtiger_jobexpcf.cf_1263 as internal_selling
												FROM vtiger_jobexp 
												INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexp.jobexpid 
 												INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
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
													 'selling_created_time' => $model->getDisplayValue('CreatedTime'),
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
				  $q_cur = mysql_query('select * from vtiger_currency_info where id = "'.$CurId.'"');
				  $row_cur = mysql_fetch_array($q_cur);
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
		
		
		
		
		$jrer_sum_sql =  "SELECT sum(vtiger_jobexpencereportcf.cf_1347) as buy_local_currency_gross, 
								     sum(vtiger_jobexpencereportcf.cf_1349) as buy_local_currency_net,
									 sum(vtiger_jobexpencereportcf.cf_1351) as expected_buy_local_currency_net, 
									 sum(vtiger_jobexpencereportcf.cf_1353) as variation_expected_and_actual_buying
									 FROM `vtiger_jobexpencereport` 
							  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
 INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
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
		
		 $jrer_sql_expense =  "SELECT vtiger_jobexpencereportcf.cf_1347 as buy_local_currency_gross, 
								     vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
									 vtiger_jobexpencereportcf.cf_1351 as expected_buy_local_currency_net, 
									 vtiger_jobexpencereportcf.cf_1353 as variation_expected_and_actual_buying,
									 vtiger_jobexpencereportcf.cf_1216 as expense_invoice_date,
									 vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
									 FROM `vtiger_jobexpencereport` 
							  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
 INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
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
				$paytoinfo = Vtiger_Record_Model::getInstanceById($pay_to_id, 'Accounts');
				$company_accountname = @$paytoinfo->get('accountname');
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
		$this->setValue('dateadded',date('d.m.Y', strtotime($fleet_info_detail->get('CreatedTime'))));				
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
		$fleet_round_sql =  "SELECT * FROM `vtiger_roundtrip` 
							INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_roundtrip.roundtripid 
							INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR 
															   vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
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
							
					$rs_query  = mysql_query("select * from vtiger_jobtask 
											  where job_id='".$job_id."' and user_id='".$roundtrip_info->get('assigned_user_id')."' limit 1");
					$row_task = mysql_fetch_array($rs_query);
										  
					if(mysql_num_rows($rs_query)>0)
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
				$sum_buy_local_currency_net =  "SELECT vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
													   vtiger_jobexpencereportcf.cf_1216 as buy_invoice_date,
													   vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
												FROM `vtiger_jobexpencereport` 
												INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
												INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
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
						
						$adb_internal = PearDatabase::getInstance();	
						$internal_selling_arr = "SELECT vtiger_jobexpcf.cf_1263 as internal_selling
												FROM vtiger_jobexp 
												INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexp.jobexpid 
 												INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
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
													 'selling_created_time' => $model->getDisplayValue('CreatedTime'),
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
		
		
		
		
		$jrer_sum_sql =  "SELECT sum(vtiger_jobexpencereportcf.cf_1347) as buy_local_currency_gross, 
								     sum(vtiger_jobexpencereportcf.cf_1349) as buy_local_currency_net,
									 sum(vtiger_jobexpencereportcf.cf_1351) as expected_buy_local_currency_net, 
									 sum(vtiger_jobexpencereportcf.cf_1353) as variation_expected_and_actual_buying
									 FROM `vtiger_jobexpencereport` 
							  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
 INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
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
		
		 $jrer_sql_expense =  "SELECT vtiger_jobexpencereportcf.cf_1347 as buy_local_currency_gross, 
								     vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
									 vtiger_jobexpencereportcf.cf_1351 as expected_buy_local_currency_net, 
									 vtiger_jobexpencereportcf.cf_1353 as variation_expected_and_actual_buying,
									 vtiger_jobexpencereportcf.cf_1216 as expense_invoice_date,
									 vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
									 FROM `vtiger_jobexpencereport` 
							  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
 INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
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
				$paytoinfo = Vtiger_Record_Model::getInstanceById($pay_to_id, 'Accounts');
				$company_accountname = @$paytoinfo->get('accountname');
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
		$this->setValue('dateadded',date('d.m.Y', strtotime($fleet_info_detail->get('CreatedTime'))));				
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
		
		$jrer_sum_sql =  "SELECT sum(vtiger_jobexpencereportcf.cf_1347) as buy_local_currency_gross, 
								     sum(vtiger_jobexpencereportcf.cf_1349) as buy_local_currency_net,
									 sum(vtiger_jobexpencereportcf.cf_1351) as expected_buy_local_currency_net, 
									 sum(vtiger_jobexpencereportcf.cf_1353) as variation_expected_and_actual_buying
									 FROM `vtiger_jobexpencereport` 
							  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
 INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
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
		
		
		   $jrer_sql_expense =  "SELECT vtiger_jobexpencereportcf.cf_1347 as buy_local_currency_gross, 
								     vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
									 vtiger_jobexpencereportcf.cf_1351 as expected_buy_local_currency_net, 
									 vtiger_jobexpencereportcf.cf_1353 as variation_expected_and_actual_buying,
									 vtiger_jobexpencereportcf.cf_1216 as expense_invoice_date,
									 vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
									 FROM `vtiger_jobexpencereport` 
							  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
 INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
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
				$paytoinfo = Vtiger_Record_Model::getInstanceById($pay_to_id, 'Accounts');
				$company_accountname = @$paytoinfo->get('accountname');
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
					$sum_buy_local_currency_net =  "SELECT vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
														   vtiger_jobexpencereportcf.cf_1216 as buy_invoice_date,
														   vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
													FROM `vtiger_jobexpencereport` 
													INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
													INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
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
						  $q_cur = mysql_query('select * from vtiger_currency_info where id = "'.$CurId.'"');
						  $row_cur = mysql_fetch_array($q_cur);
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
					$sum_sell_local_currency_net =  "SELECT sum(vtiger_jobexpencereportcf.cf_1240) as sell_local_currency_net
													FROM `vtiger_jobexpencereport` 
													INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
													INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
													left join vtiger_jobexpencereportcf as vtiger_jobexpencereportcf on vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid 
													where vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? and vtiger_crmentityrel.module='Fleet' 
													and vtiger_crmentityrel.relmodule='Jobexpencereport' and vtiger_jobexpencereportcf.cf_1457='Selling'
													and vtiger_jobexpencereportcf.cf_1477=? and vtiger_jobexpencereportcf.cf_1479=? AND vtiger_jobexpencereport.owner_id = ? AND vtiger_jobexpencereportcf.cf_2195=?
													";
					$params_sell_local = array($parentId, $p_share['cf_1477_location_id'], $p_share['cf_1479_department_id'], $fleet_info_detail->get('assigned_user_id'), $fleet_info_detail->get('cf_2001')); //parentid=truck id
					$result_sell_locall = $adb_sell_local->pquery($sum_sell_local_currency_net, $params_sell_local);
					$row_jrer_sell_local_currency_net = $adb_sell_local->fetch_array($result_sell_locall);				   
					
					
					$adb_internal = PearDatabase::getInstance();	
					$internal_selling_arr = "SELECT vtiger_jobexpcf.cf_1263 as internal_selling
											FROM vtiger_jobexp 
											INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexp.jobexpid 
											INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
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
}