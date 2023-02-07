<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class JER_Print_View extends Vtiger_Print_View {
	
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
	
	
	function process(Vtiger_Request $request) {		
		$moduleName = $request->getModule();
		$record = $request->get('record');
		
		//$expnese_type = $request->get('expense');
		
		$this->print_jobcosting($request);
	}
	
	public function print_jobcosting($request)
	{
		include('include/Exchangerate/exchange_rate_class.php');
		global $adb;
		$moduleName = $request->getModule();
		$record = $request->get('record');
		
		$job_id = $record;
		$current_user = Users_Record_Model::getCurrentUserModel();
		
		$job_info_detail = Vtiger_Record_Model::getInstanceById($job_id, 'Job');
		
		$document = $this->loadTemplate('printtemplates/Job/jobcosting.html');
		
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
		$this->setValue('dateadded',date('d.m.Y', strtotime($job_info_detail->get('CreatedTime'))));				
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
			$clientinfo = Vtiger_Record_Model::getInstanceById($client_id, 'Accounts');
			$client_accountname = @$clientinfo->get('cf_2395');
		}
		
		$this->setValue('client', htmlentities($client_accountname, ENT_QUOTES, "UTF-8"));
		
		$job_reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info_detail->get('cf_1186'));
		$file_title_currency = $job_reporting_currency;
		
		$this->setValue('FILE_TITLE_CURRENCY', $file_title_currency);
		
		$pagingModel_1 = new Vtiger_Paging_Model();
		$pagingModel_1->set('page','1');
		$pagingModel_1->set('limit','150');
		
		$relatedModuleName_1 = 'JER';
		$parentRecordModel_1 = $job_info_detail;
		$relationListView_1 = Vtiger_RelationListView_Model::getInstance($parentRecordModel_1, $relatedModuleName_1, $label);
		$models_1 = $relationListView_1->getEntries($pagingModel_1);
		
		$i = 0;
		$expected_revenue_table = '';
		$expected_cost_table = '';
		$total_cost_local_currency = '0.00';
		$total_sell_local_currency = '0.00';
		$total_cost_local_currency_gross = '0.00';
		$total_sell_local_currency_gross = '0.00';
		$TOTAL_USD_COST = '0.00';
		$TOTAL_USD_SELL = '0.00';
		
		foreach($models_1 as $key => $model){
				
				//$total_revenew +=$model->get('cf_3173');
				$i++;
				
				$jer_id  = $model->getId();			
				$sourceModule   = 'JER';	
				$jobcosting_info = Vtiger_Record_Model::getInstanceById($jer_id, $sourceModule);
			
				$CreatedTime = $jobcosting_info->get('ModifiedTime');				
				$d_cr_date = DateTime::createFromFormat('Y-m-d H:i:s', $CreatedTime);
				
  				$created_date = date_format($d_cr_date, 'd.m.Y');
				
				$pay_to_id = $jobcosting_info->get('cf_1176');
				$payto_accountname = '';
				if(!empty($pay_to_id))
				{
					$paytoinfo = Vtiger_Record_Model::getInstanceById($pay_to_id, 'Accounts');
					$payto_accountname = @$paytoinfo->get('cf_2395');
				}
				$cost_vendor = $jobcosting_info->getDisplayValue('cf_1154');
				$cost_vat_rate = $jobcosting_info->getDisplayValue('cf_6350');
				
				$pay_to_currency = $jobcosting_info->getDisplayValue('cf_1156');
				$expected_invoice_date = date_format($d_cr_date, 'Y-m-d');
				if($file_title_currency =='KZT')
				{						
					$cost_exchange_rate   = exchange_rate_currency($expected_invoice_date, $pay_to_currency);	
				}
				elseif($file_title_currency =='USD')
				{
					$cost_exchange_rate = currency_rate_convert($pay_to_currency, $file_title_currency, 1, $expected_invoice_date);
				}
				else{
					$cost_exchange_rate = currency_rate_convert_others($pay_to_currency, $file_title_currency, 1, $expected_invoice_date);
				}
				
				$cost_local_currency = '0.00';
				$cost_local_currency_gross='0.00';
				$cost_currency_gross  ='0.00';
				if(!empty($cost_vendor))
				{
					$cost_vat = '0.00';
					if(!empty($cost_vat_rate) && $cost_vat_rate>0)
					{
						$cost_vat_rate_cal = $cost_vat_rate/100; 
						$cost_vat          = $cost_vendor * $cost_vat_rate_cal;
					}			
					$cost_currency_gross = $cost_vendor + $cost_vat;
					
					if($file_title_currency !='USD')
					{
						//currency gross	
						$cost_local_currency_gross = $cost_currency_gross * $cost_exchange_rate;
						//currency net				
						$costlocalcurrency = $cost_vendor * $cost_exchange_rate;
					}else{
						//currency gross
						$cost_local_currency_gross = exchange_rate_convert($pay_to_currency, $file_title_currency,$cost_currency_gross, $expected_invoice_date);
						//currency net
						$costlocalcurrency = exchange_rate_convert($pay_to_currency, $file_title_currency,$cost_vendor, $expected_invoice_date);
					}
					
					$cost_local_currency  = $costlocalcurrency;
					$cost_local_currency_gross = $cost_local_currency_gross;
				}
				 $total_cost_local_currency_gross +=  $cost_local_currency_gross;
				 $total_cost_local_currency +=  $cost_local_currency;
				 
				 
				 if($file_title_currency!='USD')
				{
					$final_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $expected_invoice_date);
				}else{
					$final_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $expected_invoice_date);
				}
				$total_cost_USD = $cost_local_currency/$final_exchange_rate;		 
				$TOTAL_USD_COST +=$total_cost_USD;
				
				$expected_cost_table .=' <tr>
											<td valign="top" width="19">
											'.$i.'
											</td>
											<td valign="top" width="192">
											'.$payto_accountname.'
											</td>
											<td valign="top" width="172">
											  '.$jobcosting_info->getDisplayValue('cf_1451').'
											</td>
											<td valign="top" width="60">
											'.$created_date.'
											</td>
											<td valign="top" width="72">
											 '.$jobcosting_info->getDisplayValue('cf_1433').'  '.$jobcosting_info->getDisplayValue('cf_1435').'											
											</td>
											<td valign="top" width="132" align="right">
											 '.number_format ( $jobcosting_info->getDisplayValue('cf_1154') , 2 ,  "." , "," ).'
											</td>
											<td valign="top" width="66">
											 '.number_format ( $jobcosting_info->getDisplayValue('cf_6350') , 2 ,  "." , "," ).'
											</td>
											 <td valign="top" width="66">
											 '.number_format ($cost_vat , 2 ,  "." , "," ).'
											</td>
											  <td valign="top" width="132" align="right">
											  '.number_format ($cost_currency_gross, 2 ,  "." , "," ).'
											</td>
											 <td valign="top" width="66">
											  '.$jobcosting_info->getDisplayValue('cf_1156').'	
											</td>
											<td valign="top" width="60">
											'.$jobcosting_info->getDisplayValue('cf_1158').'	
											</td>
											<td valign="top" width="168" align="right">											
											'.number_format ( $cost_local_currency_gross , 2 ,  "." , "," ).'
											</td>
											<td valign="top" width="168" align="right">
											'.number_format ( $cost_local_currency , 2 ,  "." , "," ).'
											</td>
											<td valign="top" width="70" align="right">
											'.number_format ( $total_cost_USD , 2 ,  "." , "," ).'
											
           									 </td>
										</tr>';
										
										
										
				
				$bill_to_id = $jobcosting_info->get('cf_1443');
				$billto_accountname = '';
				if(!empty($bill_to_id))
				{
					$billtoinfo = Vtiger_Record_Model::getInstanceById($bill_to_id, 'Accounts');
					$billto_accountname = @$billtoinfo->get('cf_2395');
				}
				
				$sell_customer = $jobcosting_info->getDisplayValue('cf_1162');
				$sell_vat_rate = $jobcosting_info->getDisplayValue('cf_6354');
							
				$expected_invoice_date = date_format($d_cr_date, 'Y-m-d');
				$customer_currency = $jobcosting_info->getDisplayValue('cf_1164');
				if($file_title_currency=='KZT')
				{				
					$revenue_exchange_rate   = exchange_rate_currency($expected_invoice_date, $customer_currency);				
				}
				elseif($file_title_currency=='USD')
				{
					$revenue_exchange_rate = currency_rate_convert($customer_currency, $file_title_currency, 1, $expected_invoice_date);
				}else{
					$revenue_exchange_rate = currency_rate_convert_others($customer_currency, $file_title_currency, 1, $expected_invoice_date);
				}
				
				$sell_vat = '0.00';
				if(!empty($sell_vat_rate) && $sell_vat_rate>0)
				{
					$sell_vat_rate_cal = $sell_vat_rate/100; 
					$sell_vat          = $sell_customer * $sell_vat_rate_cal;
				}
				$sell_currency_gross = $sell_customer + $sell_vat;
				
				$sell_local_currency = '0.00';
				$sell_local_currency_gross='0.00';
				if($file_title_currency !='USD')
				{
					//currency gross	
					$sell_local_currency_gross = $sell_currency_gross * $revenue_exchange_rate;
					//currency net		
					$sell_local_currency = $sell_customer * $revenue_exchange_rate;
				}
				else{
					//currency gross
					$sell_local_currency_gross = exchange_rate_convert($customer_currency, $file_title_currency,$sell_currency_gross, $expected_invoice_date);
					//currency net	
					$sell_local_currency = exchange_rate_convert($customer_currency, $file_title_currency,$sell_customer, $expected_invoice_date);
				}
				
				 $total_sell_local_currency_gross +=  $sell_local_currency_gross;
				 $total_sell_local_currency 	   +=  $sell_local_currency;
				 
				 $total_sell_USD = $sell_local_currency/$final_exchange_rate;
				 $TOTAL_USD_SELL +=$total_sell_USD;	
				
				$expected_revenue_table .=' <tr>
											<td valign="top" width="19">
											'.$i.'
											</td>
											<td valign="top" width="192">
											'.$billto_accountname.'
											</td>
											<td valign="top" width="172">
											 '.$jobcosting_info->getDisplayValue('cf_1451').'
											</td>
											<td valign="top" width="60">
											'.$created_date.'
											</td>
											<td valign="top" width="72">
											  '.$jobcosting_info->getDisplayValue('cf_1433').' '.$jobcosting_info->getDisplayValue('cf_1435').'											
											</td>
											<td valign="top" width="132" align="right">
											 '.number_format ( $jobcosting_info->getDisplayValue('cf_1162') , 2 ,  "." , "," ).'
											</td>
											<td valign="top" width="66">
											 '.number_format ( $jobcosting_info->getDisplayValue('cf_6354') , 2 ,  "." , "," ).'
											</td>
											 <td valign="top" width="66">
											  '.number_format ( $sell_vat , 2 ,  "." , "," ).'
											</td>
											  <td valign="top" width="132" align="right">
											   '.number_format ( $sell_currency_gross , 2 ,  "." , "," ).'
											</td>
											 <td valign="top" width="66">
											   '.$jobcosting_info->getDisplayValue('cf_1164').'	
											</td>
											<td valign="top" width="60">
											'.$jobcosting_info->getDisplayValue('cf_1166').'	
											</td>
											<td valign="top" width="168" align="right">
											'.number_format ( $sell_local_currency_gross , 2 ,  "." , "," ).'
											</td>
											<td valign="top" width="168" align="right">
											'.number_format ( $sell_local_currency , 2 ,  "." , "," ).'
											</td>
											 <td valign="top" width="70" align="right">
											 '.number_format ( $total_sell_USD , 2 ,  "." , "," ).'											 
           									 </td>
										</tr>';
				
		}
		
		$this->setValue('expected_cost_table', $expected_cost_table);
		
		$this->setValue('total_cost_local_currency_gross', number_format($total_cost_local_currency_gross, 2 ,  "." , "," ));
		$this->setValue('total_cost_local_currency', number_format($total_cost_local_currency, 2 ,  "." , "," ));
		$this->setValue('TOTAL_USD_COST', number_format($TOTAL_USD_COST, 2 ,  "." , "," ));
		
		
		$this->setValue('expected_revenue_table', $expected_revenue_table);
		
		$this->setValue('total_sell_local_currency_gross', number_format($total_sell_local_currency_gross, 2 ,  "." , "," ));
		$this->setValue('total_sell_local_currency', number_format($total_sell_local_currency, 2 ,  "." , "," ));
		$this->setValue('TOTAL_USD_SELL', number_format($TOTAL_USD_SELL, 2 ,  "." , "," ));
		
		$jer_last_sql =  $adb->pquery("SELECT vtiger_crmentity.modifiedtime FROM `vtiger_jercf` as jercf 
				 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = jercf.jerid
				 INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid 
				 where vtiger_crmentity.deleted=0 AND crmentityrel.crmid='".$job_id."' AND crmentityrel.module='Job' 
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
		$Exchrate = $final_exchange_rate;
		$this->setValue('modified_date_exchange_rate', number_format($Exchrate, 2));
		
		$total_cost_in_USD = $total_cost_local_currency/$Exchrate;		 
		$this->setValue('total_cost_in_USD', number_format($total_cost_in_USD, 2));
		$total_cost_in_USD_gross = $total_cost_local_currency_gross/$Exchrate;	
		$this->setValue('total_cost_in_USD_gross', number_format($total_cost_in_USD_gross, 2));
		$total_revenue_in_USD = $total_sell_local_currency/$Exchrate;
		$this->setValue('total_revenue_in_USD', number_format($total_revenue_in_USD, 2));
		$total_revenue_in_USD_gross = $total_sell_local_currency_gross/$Exchrate;
		$this->setValue('total_revenue_in_USD_gross', number_format($total_revenue_in_USD_gross, 2));
		
		
		
		$this->setValue('EXPECTED_PROFIT_LOCAL_CURRENCY', number_format($total_sell_local_currency-$total_cost_local_currency, 2));
		$this->setValue('EXPECTED_PROFIT_USD', number_format($total_revenue_in_USD-$total_cost_in_USD, 2));
		
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
		
				
		$pdf_name = 'pdf_docs/jobcosting.pdf';
		
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
		//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
		$fleet_round_sql =  "SELECT * FROM `vtiger_roundtrip` 
							INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_roundtrip.roundtripid 
							INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid ) 
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
				// OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid								   
				$sum_buy_local_currency_net =  "SELECT vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
													   vtiger_jobexpencereportcf.cf_1216 as buy_invoice_date,
													   vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
												FROM `vtiger_jobexpencereport` 
												INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
												INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid) 
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