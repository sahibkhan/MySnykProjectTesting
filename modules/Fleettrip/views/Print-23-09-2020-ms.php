mm<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Fleettrip_Print_View extends Vtiger_Print_View {
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
  
  public function get_job_id_from_fleet($recordId=0) {
    $adb = PearDatabase::getInstance();
                  
    $checkjob = $adb->pquery("SELECT rel1.crmid AS job_id
                              FROM `vtiger_crmentityrel` AS rel1
                              WHERE rel1.relcrmid = '".$recordId."'", array());
    $crmId = $adb->query_result($checkjob, 0, 'job_id');
    $job_id = $crmId;
    return $job_id;
  }

  function process(Vtiger_Request $request) {  
  	
	//$current_user = Users_Record_Model::getCurrentUserModel();
	  
    $moduleName = $request->getModule();
    $record = $request->get('record');
    
    $checklist = $request->get('checklist');
    
    if($checklist == 'PostTrip') {
      $this->print_driver_checklist($request);
    }
    else if($checklist == 'TripExpense') {
      $this->print_tripexpense($request);
    }
    else if($checklist == 'TripListFuel') {
      $this->print_triplistfuel($request);
    }
    else if($checklist == 'TripListLocal') {
      $this->print_triplistlocal($request);
    }
    else if($checklist == 'TripListInternational') {
      $this->print_triplistinternational($request);
    }
    else if($checklist == 'FuelData') {
      $this->print_fueldata($request);
    }
    else {
      $this->print_fleettrip($request);
    }
  }
  
  public function print_fleettrip($request) {
    $moduleName = $request->getModule();
    $record = $request->get('record');
    
    $fleet_id = $record;
    $current_user = Users_Record_Model::getCurrentUserModel();
	
    $fleet_info_detail = Vtiger_Record_Model::getInstanceById($fleet_id, 'Fleettrip');
    
    //$fleet_expense  = $this->get_job_id_from_fleet($fleet_id);
    //$job_id = $fleet_expense;
    //$sourceModule_job   = 'Job';  
    //$job_info_detail = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule_job);
    
    $document = $this->loadTemplate('printtemplates/fleettrip.html');
    //$this->setValue('mehtab',htmlentities("mehtab", ENT_QUOTES, "UTF-8"));
    
    $driver_user_info = Users_Record_Model::getInstanceById($fleet_info_detail->get('cf_3167'), 'Users');
    
    $owner_fleet_user_info = Users_Record_Model::getInstanceById($fleet_info_detail->get('assigned_user_id'), 'Users');
    
    //$pay_to_info = Users_Record_Model::getInstanceById($job_info_detail->get('cf_1441'), 'Accounts');
      
    $this->setValue('mobile',$owner_fleet_user_info->get('phone_mobile'));
    $this->setValue('fax',$owner_fleet_user_info->get('phone_fax'));
    $this->setValue('email',htmlentities($owner_fleet_user_info->get('email1'), ENT_QUOTES, "UTF-8"));
    $this->setValue('cityname',htmlentities($owner_fleet_user_info->getDisplayValue('location_id'), ENT_QUOTES, "UTF-8"));
    $this->setValue('countryname',htmlentities($owner_fleet_user_info->get('address_country'), ENT_QUOTES, "UTF-8"));
    $this->setValue('departmentcode',htmlentities($owner_fleet_user_info->getDisplayValue('department_id'), ENT_QUOTES, "UTF-8"));  
    $this->setValue('dateadded',date('d.m.Y', strtotime($fleet_info_detail->get('CreatedTime'))));        
    //$this->setValue('billingto', $pay_to_info->get('accountname'));
    $this->setValue('from', htmlentities($owner_fleet_user_info->get('first_name').' '.$owner_fleet_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));
    
    $this->setValue('fleet_ref_no', $fleet_info_detail->get('cf_3283'));    
    $this->setValue('grossweight', htmlentities($fleet_info_detail->get('cf_3229'), ENT_QUOTES, "UTF-8"));    
    $this->setValue('volumeweight', htmlentities($fleet_info_detail->get('cf_3231'), ENT_QUOTES, "UTF-8"));
    
    $this->setValue('pieces', htmlentities($fleet_info_detail->get('cf_3229'), ENT_QUOTES, "UTF-8"));
    
    $this->setValue('truckno', htmlentities($fleet_info_detail->getDisplayValue('cf_3165'), ENT_QUOTES, "UTF-8"));
    $this->setValue('driver', htmlentities($driver_user_info->get('first_name').' '.$driver_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));
	$this->setValue('conditionoftransportation', htmlentities($fleet_info_detail->get('cf_5964'), ENT_QUOTES, "UTF-8"));
	$this->setValue('temperaturerangefrom', htmlentities($fleet_info_detail->get('cf_5966'), ENT_QUOTES, "UTF-8"));
	$this->setValue('temperaturerangeto', htmlentities($fleet_info_detail->get('cf_5968'), ENT_QUOTES, "UTF-8"));
	
    
    $fromdate = $fleet_info_detail->get('cf_3245');
    if(!empty($fromdate))
    {
      $this->setValue('fromdate',date('d.m.Y', strtotime($fleet_info_detail->get('cf_3245'))));     
    }
    else {
      $this->setValue('fromdate',''); 
    }
    
    $todate = $fleet_info_detail->get('cf_3247');
    if(!empty($todate))
    {
      $this->setValue('todate',date('d.m.Y', strtotime($fleet_info_detail->get('cf_3247'))));     
    }
    else {
      $this->setValue('todate','');     
    }
    
    $expectedfromdate = $fleet_info_detail->get('cf_3249');
    if(!empty($expectedfromdate))
    {
      $this->setValue('expectedfromdate',date('d.m.Y', strtotime($fleet_info_detail->get('cf_3249'))));     
    }
    else {
      $this->setValue('expectedfromdate','');     
    }
    $expectedtodate = $fleet_info_detail->get('cf_3251');
    if(!empty($expectedtodate))
    {
      $this->setValue('expectedtodate',date('d.m.Y', strtotime($fleet_info_detail->get('cf_3251'))));     
    }
    else {
      $this->setValue('expectedtodate','');     
    }
    $this->setValue('standarddays', htmlentities($fleet_info_detail->get('cf_3253'), ENT_QUOTES, "UTF-8"));
    $this->setValue('alloweddays', htmlentities($fleet_info_detail->get('cf_3255'), ENT_QUOTES, "UTF-8"));  
    
    $this->setValue('origincountry',htmlentities($fleet_info_detail->get('cf_3237'), ENT_QUOTES, "UTF-8"));
    $this->setValue('origincity',htmlentities($fleet_info_detail->get('cf_3241'), ENT_QUOTES, "UTF-8"));    
    $this->setValue('destinationcountry',htmlentities($fleet_info_detail->get('cf_3239'), ENT_QUOTES, "UTF-8"));
    $this->setValue('destinationcity',htmlentities($fleet_info_detail->get('cf_3243'), ENT_QUOTES, "UTF-8"));
    
    $this->setValue('dueleavekm',htmlentities($fleet_info_detail->get('cf_3257'), ENT_QUOTES, "UTF-8"));
    $this->setValue('duearrivalkm',htmlentities($fleet_info_detail->get('cf_3259'), ENT_QUOTES, "UTF-8"));
    $this->setValue('standarddistancekm',htmlentities($fleet_info_detail->get('cf_3261'), ENT_QUOTES, "UTF-8"));
    $this->setValue('kmtravelledduringtrip',htmlentities($fleet_info_detail->get('cf_3263'), ENT_QUOTES, "UTF-8"));
    //$this->setValue('petrolfillingl',htmlentities($fleet_info_detail->get('cf_2025'), ENT_QUOTES, "UTF-8"));
    //$this->setValue('fuelatthebegin',htmlentities($fleet_info_detail->get('cf_2027'), ENT_QUOTES, "UTF-8"));
    //$this->setValue('fuelattheend',htmlentities($fleet_info_detail->get('cf_2029'), ENT_QUOTES, "UTF-8"));
    $this->setValue('standardfuel',htmlentities($fleet_info_detail->get('cf_3271'), ENT_QUOTES, "UTF-8"));
    $this->setValue('fuelusedduringtrip',htmlentities($fleet_info_detail->get('cf_3273'), ENT_QUOTES, "UTF-8"));
    $this->setValue('averageconsumption',htmlentities($fleet_info_detail->get('cf_3275'), ENT_QUOTES, "UTF-8"));
    $this->setValue('averageallowedconsumption',htmlentities($fleet_info_detail->get('cf_3277'), ENT_QUOTES, "UTF-8"));
	$standardindirectcost = $fleet_info_detail->get('cf_5159') * $fleet_info_detail->get('cf_3253'); //standard indirect cost * standard days
	//$this->setValue('standardindirectcost',htmlentities($standardindirectcost, ENT_QUOTES, "UTF-8"));
	
	//trip_addresses
	$adb_addresses = PearDatabase::getInstance();
    $trip_addresses_sql =  "SELECT * FROM `vtiger_tripaddresses` 
                         INNER JOIN vtiger_crmentity
                         ON vtiger_crmentity.crmid = `vtiger_tripaddresses`.tripaddressesid 
                         INNER JOIN vtiger_crmentityrel
                         ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid
                         OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
                         LEFT JOIN vtiger_tripaddressescf AS vtiger_tripaddressescf
                         ON vtiger_tripaddressescf.tripaddressesid = vtiger_tripaddresses.tripaddressesid 
                         WHERE vtiger_crmentity.deleted = 0 AND vtiger_crmentityrel.crmid = ?
                         AND vtiger_crmentityrel.module = 'Fleettrip'
                         AND vtiger_crmentityrel.relmodule = 'TripAddresses'";
                
    $params_trip_addresses = array($fleet_id);
    $result_addresses = $adb_addresses->pquery($trip_addresses_sql, $params_trip_addresses);
    $trip_addresses = '';
	$num_addresses = $adb_addresses->num_rows($result_addresses);
	$_SESSION['tripaddresses'] = '0';
	if($num_addresses==0){
		$_SESSION['tripaddresses'] = '1';
		$loadurl = "index.php?module=Fleettrip&relatedModule=TripAddresses&view=Detail&record=".$fleet_id."&mode=showRelatedList&tab_label=Trip%20Addresses";
		header("Location: $loadurl");
		exit;
	}
    for($ik=0; $ik<$adb_addresses->num_rows($result_addresses); $ik++) {
      $origin_point = $adb_addresses->query_result($result_addresses, $ik, 'cf_5980');
      $destination_point = $adb_addresses->query_result($result_addresses, $ik, 'cf_5982');
      $contact_detail = $adb_addresses->query_result($result_addresses, $ik, 'cf_5984');
      $trip_addresses .='
        <tr>
          <td width="216">'.$origin_point.'</td>
          <td width="186">'.$destination_point.'</td>
          <td width="174">'.$contact_detail.'</td>         
        </tr>';
    }

    $this->setValue('trip_addresses', $trip_addresses);
    
	
    
    $adb_round = PearDatabase::getInstance();
    $fleet_round_sql =  "SELECT * FROM `vtiger_roundtrip` 
                         INNER JOIN vtiger_crmentity
                         ON vtiger_crmentity.crmid = vtiger_roundtrip.roundtripid 
                         INNER JOIN vtiger_crmentityrel
                         ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid
                         OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
                         LEFT JOIN vtiger_roundtripcf AS vtiger_roundtripcf
                         ON vtiger_roundtripcf.roundtripid = vtiger_roundtrip.roundtripid 
                         WHERE vtiger_crmentity.deleted = 0 AND vtiger_crmentityrel.crmid = ?
                         AND vtiger_crmentityrel.module = 'Fleettrip'
                         AND vtiger_crmentityrel.relmodule = 'Roundtrip'";
                
    $params_fleet_roundtrip = array($fleet_id);
    $result_round = $adb_round->pquery($fleet_round_sql, $params_fleet_roundtrip);
    $allocated_job_no = '';
    for($ij=0; $ij<$adb_round->num_rows($result_round); $ij++) {
      $job_ref_id = $adb_round->query_result($result_round, $ij, 'cf_3175');
      $job_ref_no = Vtiger_JobFileList_UIType::getDisplayValue($job_ref_id);
      $origin_city = $adb_round->query_result($result_round, $ij, 'cf_3169');
      $destination_city = $adb_round->query_result($result_round, $ij, 'cf_3171');
      $allocated_job_no .='
        <tr>
          <td width="216">'.$job_ref_no.'</td>
          <td width="186">'.$origin_city.'</td>
          <td width="174">'.$destination_city.'</td>
          <td width="120"><p>&nbsp;</p></td>
        </tr>';
    }

    $this->setValue('allocated_job_no', $allocated_job_no);
    
    $adb = PearDatabase::getInstance();
        
    $origin_country_id    = $fleet_info_detail->get('cf_3237');
    $origin_city_id    = $fleet_info_detail->get('cf_3241');
    $destination_country_id = $fleet_info_detail->get('cf_3239');
    $destination_city_id  = $fleet_info_detail->get('cf_3243');
      
    $truck_info = Vtiger_Record_Model::getInstanceById($fleet_info_detail->get('cf_3165'), 'Truck');
    
    $this->setValue('trucktype',htmlentities($truck_info->getDisplayValue('cf_1911'), ENT_QUOTES, "UTF-8"));
    
    $truck_type_id = $truck_info->get('cf_1911');
    
    /*                    
    $query_trip_expense = "SELECT * from vtiger_triptemplatescf 
                 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_triptemplatescf.triptemplatesid
                 WHERE vtiger_triptemplatescf.cf_2047 = ? AND 
                       vtiger_triptemplatescf.cf_2053 = ? AND
                   vtiger_triptemplatescf.cf_2057 = ? AND
                   vtiger_triptemplatescf.cf_2055 = ? AND
                   vtiger_triptemplatescf.cf_2059 = ? AND
                   vtiger_crmentity.deleted = 0
                   Limit 1
                ";
    $check_params_trip_expense = array($truck_type_id, $origin_country_id, $origin_city_id, $destination_country_id, $destination_city_id);
    */
    $trip_template_id = $fleet_info_detail->get('cf_4517');
    
    $query_trip_expense = "SELECT * FROM vtiger_triptemplatescf 
                           INNER JOIN vtiger_crmentity
                           ON vtiger_crmentity.crmid = vtiger_triptemplatescf.triptemplatesid
                           WHERE vtiger_triptemplatescf.triptemplatesid = ?
                           AND vtiger_crmentity.deleted = 0 LIMIT 1";

    $check_params_trip_expense = array($trip_template_id);
    $result_trip_expense = $adb->pquery($query_trip_expense, $check_params_trip_expense);
    $row_truck_type_trip_expense = $adb->fetch_array($result_trip_expense);
    
    //$total_allowance = (@$row_truck_type_trip_expense['cf_2067'] * @$row_truck_type_trip_expense['cf_2069']);//standard_days * daily_allowance
    $total_allowance = @$row_truck_type_trip_expense['cf_2071']; //total allowance
    $parking = @$row_truck_type_trip_expense['cf_2073'];//parking
    $guesthouse = @$row_truck_type_trip_expense['cf_2075']; //guest_house
    $others =  @$row_truck_type_trip_expense['cf_2081']; //others
    
    $total_trip_expense = $total_allowance + $parking + $quest_house + $others; 
    
    //$this->setValue('totalallowance', number_format($total_allowance, 2, '.', ','));
    //$this->setValue('parking',number_format($parking, 2, '.', ','));
    //$this->setValue('guesthouse',number_format($guesthouse, 2, '.', ','));
    //$this->setValue('others',number_format($others, 2, '.', ','));
    
    
    //Calculate per litre price of petrol against truck filling latest
    
    $query_truck_fuel_latest = "SELECT vtiger_fuelcf.cf_2101 AS petrol_price_l
                                FROM vtiger_fuelcf
                                INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_fuelcf.fuelid
                                INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_fuelcf.fuelid
                                AND vtiger_crmentityrel.module = 'Truck'
                                AND vtiger_crmentityrel.relmodule = 'Fuel'
                                WHERE vtiger_crmentity.deleted = 0
                                AND vtiger_crmentityrel.crmid = ?
                                ORDER BY vtiger_fuelcf.cf_2093 DESC LIMIT 1";
                  
    $check_params_latest = array($truck_info->get('record_id'));
    $result_latest = $adb->pquery($query_truck_fuel_latest, $check_params_latest);
    $row_truck_fuel_latest = $adb->fetch_array($result_latest);
    
    //$petrol_price_l = $row_truck_fuel_end['cf_2101'];
    //$total_fuel_expense_costing =   @$row_truck_type_trip_expense['cf_2061'] * $petrol_price_l; 
    $petrol_price_l = @$row_truck_fuel_latest['petrol_price_l'];
    $total_fuel_expense_costing = @$row_truck_type_trip_expense['cf_2061'] * $petrol_price_l; //standard_fuel * petrol_price_l
    
    //$this->setValue('totalfuelexpense', number_format($total_fuel_expense_costing, 2, '.', ','));
    $totalfuelexpense = $total_fuel_expense_costing;
    $total_cash_required =  $total_allowance + $parking + $guesthouse + $others + $totalfuelexpense;  
    //$this->setValue('totalcashrequired', number_format($total_cash_required, 2, '.', ','));
    
    //For Fleet Actual Expense
    //cf_1351 = Expected Buy(Local Cur NET)
    //OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
    
    //$row_job_jrer_fleet = $adb->fetch_array($result_fleet_expense);
    $fleet_expense_table = '
      <table border=1 cellspacing=0 cellpadding=5  width="100%">
        <tbody>
          <tr>
            <td width="216"><p><strong>&nbsp;</strong></p></td>
            <td width="246"><p><strong>Budget</strong></p></td>
            <td width="234"><p><strong>Actual </strong></p></td>
          </tr>';

    $total_cash_requried_new = 0; 
    $total_actual = 0;
    
    $expense_type = array('Fuel', 'Daily','Other');
    
    foreach($expense_type as $key => $type)
    {
      $query_pretrip_expense = "SELECT SUM(vtiger_pretripcf.cf_4309) AS pretrip_sum
                                FROM vtiger_pretripcf
                                INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_pretripcf.pretripid
                                INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid ) 
                                WHERE vtiger_pretripcf.cf_4313 = ?
                                AND vtiger_crmentityrel.crmid= ?
                                AND vtiger_crmentity.deleted = 0 LIMIT 1";

      $params_pretrip_expense = array($type, $trip_template_id);
      
      $result_pretrip = $adb->pquery($query_pretrip_expense, $params_pretrip_expense);
      
      for($i=0; $i<$adb->num_rows($result_pretrip); $i++) {
        $pretrip_sum = $adb->query_result($result_pretrip, $i, 'pretrip_sum');
        
        $fleet_expense_table .='
          <tr>
            <td width="216"><p><strong>'.$type.'</strong></p></td>
            <td width="246"><p>'.number_format($pretrip_sum,2).'</p></td>
            <td width="234"></td>
          </tr>';
      }
    }
	 $fleet_expense_table .='
          <tr>
            <td width="216"><p><strong>Standard Indirect Cost</strong></p></td>
            <td width="246"><p>'.number_format($standardindirectcost,2).'</p></td>
            <td width="234"></td>
          </tr>';
    
    $jrer_fleet_sql = "SELECT * FROM `vtiger_jobexpencereport` 
                       INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
                       INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid ) 
                       LEFT JOIN vtiger_jobexpencereportcf AS vtiger_jobexpencereportcf
                       ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid 
                       WHERE vtiger_crmentity.deleted = 0
                       AND vtiger_crmentityrel.crmid = ? 
                       AND vtiger_crmentityrel.module = 'Fleettrip'
                       AND vtiger_crmentityrel.relmodule = 'Jobexpencereport' 
                       AND vtiger_jobexpencereportcf.cf_1457 = 'Expence'
                       AND vtiger_jobexpencereport.owner_id= ?";

    $params_fleet_expense = array($fleet_id, $fleet_info_detail->get('assigned_user_id'));
    $result = $adb->pquery($jrer_fleet_sql, $params_fleet_expense);
              
    for($i=0; $i<$adb->num_rows($result); $i++) {
      $charge = $adb->query_result($result, $i, 'cf_1453');
      $cf_1351 = $adb->query_result($result, $i, 'cf_1351');
      $cf_1343 = $adb->query_result($result, $i, 'cf_1343');
      
      $charge_name = Vtiger_CompanyAccountList_UIType::getDisplayValue($charge);
      
      $fleet_expense_table .='
        <tr>
          <td width="216"><p><strong>'.$charge_name.'</strong></p></td>
          <td width="246"><p></p></td><td width="234"><p>'.number_format($cf_1343,2).'</p></td>
        </tr>';
                  
      $total_cash_requried_new +=$cf_1351; 
      $total_actual +=$cf_1343;           
    }

    $fleet_expense_table .='
      <tr>
        <td width="216"><p>&nbsp;</p></td>
        <td width="246"><p>&nbsp;</p></td>
        <td width="234"><p>&nbsp;</p></td>
      </tr>
      <tr>
        <td width="216"><p><strong>&nbsp;</strong></p></td>
        <td width="246"><p>&nbsp;</p></td>
        <td width="234"><p>&nbsp;</p></td>
      </tr>
      <tr>
        <td width="216"><p><strong>&nbsp;</strong></p></td>
        <td width="246"><p>&nbsp;</p></td>
        <td width="234"><p>&nbsp;</p></td>
      </tr>
      <tr>
        <td width="216"><p><strong>&nbsp;</strong></p></td>
        <td width="246"><p>&nbsp;</p></td>
        <td width="234"><p>&nbsp;</p></td>
      </tr>';

    $fleet_expense_table .='
      <tr>
        <td width="216"><p>&nbsp;</p></td>
        <td width="246"><p>&nbsp;</p></td>
        <td width="234"><p>&nbsp;</p></td>
      </tr>
      <tr>
        <td width="216"><p><strong>&nbsp;</strong></p></td>
        <td width="246"><p>&nbsp;</p></td>
        <td width="234"><p>&nbsp;</p></td>
      </tr>
      <tr>
        <td width="216"><p><strong>&nbsp;</strong></p></td>
        <td width="246"><p>&nbsp;</p></td>
        <td width="234"><p>&nbsp;</p></td>
      </tr>
      <tr>
        <td width="216"><p><strong>&nbsp;</strong></p></td>
        <td width="246"><p>&nbsp;</p></td>
        <td width="234"><p>&nbsp;</p></td>
      </tr>';
    
    $total_cash_requried_new = $fleet_info_detail->getDisplayValue('cf_4553') + $standardindirectcost;
    
    $fleet_expense_table .='
      <tr>
        <td width="216"><p><strong>&nbsp;Total:</strong></p></td>
        <td width="246"><p>'.number_format($total_cash_requried_new, 2, '.', ',').'</p></td>
        <td width="234"><p>'.number_format($total_actual, 2, '.', ',').'</p></td>
      </tr>';

    $fleet_expense_table .='</tbody></table>';            
    $this->setValue('fleet_expense_table', $fleet_expense_table);
    //End for Fleet Actual Expense
    
    
    include('include/mpdf60/mpdf.php');
	@date_default_timezone_set($current_user->get('time_zone'));
	
	
    $mpdf = new mPDF('utf-8', 'A4-L', '10', '', 10, 10, 30, 15, 10, 5); /*задаем формат, отступы и.т.д.*/
    $mpdf->charset_in = 'utf8';
    
    $mpdf->list_indent_first_level = 0; 
    //$filename = 'fleet_expense.txt';
    //$this->save('fleet_expense.txt'); 
    $mpdf->SetHTMLHeader('
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td align="right" style="font-size:9;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
            JCR Form, GLOBALINK, designed: March, 2010
          </td>
        </tr>
        <tr>
          <td align="right"><img src="printtemplates/glklogo.jpg"/ width="160" height="30"></td>
        </tr>
      </table>');
	
    $mpdf->SetHTMLFooter('
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td width="40%" align="left" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
            Printed: '.date('d.m.Y; H:i').' by '.$current_user->get('user_name').'
          </td>
          <td width="20%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
            Page {PAGENO} of {nbpg}
          </td>
          <td width="40%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
            &nbsp;
          </td>
        </tr>
      </table>');

    $stylesheet = file_get_contents('include/mpdf60/examples/mpdfstyletables.css');
    $mpdf->WriteHTML($stylesheet,1);  // The parameter 1 tells that this is css/style only and no body/html/text
    $mpdf->WriteHTML($this->_documentXML); /*формируем pdf*/
    
        
    $pdf_name = 'pdf_docs/fleet_expense.pdf';
    
    $mpdf->Output($pdf_name, 'F');
    //header('Location:http://mb.globalink.net/vt60/'.$pdf_name);
    header('Location:'.$pdf_name);
    exit;
    
    /*
    $mpdf = new mPDF('utf-8', 'A4-L', '10', '', 10, 10, 7, 7, 10, 10);
    $mpdf->charset_in = 'utf8';    
    $mpdf->list_indent_first_level = 0; 
    $mpdf->WriteHTML($this->_documentXML);
    $pdf_name = 'fleet_expense.pdf';
    $mpdf->Output($pdf_name, 'F');
    //header('Location:http://mb.globalink.net/vt60/'.$pdf_name);
    header('Location:'.$pdf_name);
    exit;
    */  

    /*
    ob_start();
    header('Content-Description: File Transfer');
    header('Content-Type: text/plain; charset=UTF-8');
    header('Content-Disposition: attachment; filename='.$filename);
    header('Content-Transfer-Encoding: binary');    
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filename));
    flush();
    ob_end_flush();
    readfile($filename);
    unlink($filename); // deletes the temporary file
    exit; */

    /*
    $response = new Vtiger_Response();
    $response->setResult(array('success'=>false,'message'=>  vtranslate('NO_DATA')));
    $response->emit();
    */
  }

  public function print_driver_checklist($request) {
    $moduleName = $request->getModule();
    $record = $request->get('record');
    
    $fleet_id = $record;
    $current_user = Users_Record_Model::getCurrentUserModel();
    $fleet_info_detail = Vtiger_Record_Model::getInstanceById($fleet_id, 'Fleettrip');
    
    $document = $this->loadTemplate('printtemplates/posttrip_checklist.html');
      
    $driver_user_info = Users_Record_Model::getInstanceById($fleet_info_detail->get('cf_3167'), 'Users');
    
    $owner_fleet_user_info = Users_Record_Model::getInstanceById($fleet_info_detail->get('assigned_user_id'), 'Users');
    
    $this->setValue('dateadded',date('d.m.Y', strtotime($fleet_info_detail->get('CreatedTime'))));
    $this->setValue('fleet_ref_no', $fleet_info_detail->get('cf_3283'));
    $this->setValue('truckno', htmlentities($fleet_info_detail->getDisplayValue('cf_3165'), ENT_QUOTES, "UTF-8"));
    $this->setValue('driver', htmlentities($driver_user_info->get('first_name').' '.$driver_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));
    
    $pagingModel_1 = new Vtiger_Paging_Model();
    $pagingModel_1->set('page','1');
    
    $relatedModuleName_1 = 'PostTrip';
    $parentRecordModel_1 = $fleet_info_detail;
    $relationListView_1 = Vtiger_RelationListView_Model::getInstance($parentRecordModel_1, $relatedModuleName_1, $label);
    $models_1 = $relationListView_1->getEntries($pagingModel_1);
    $checklist = '';
    $i = 1;
	$post_sum_tenge = 0;
	$pre_sum_tenge = 0;
    foreach($models_1 as $key => $model) {
      
      $post_trip_id = $model->getId();     
      $sourceModule = 'PostTrip'; 
      $posttrip_info = Vtiger_Record_Model::getInstanceById($post_trip_id, $sourceModule);
      
      $difference  = $posttrip_info->getDisplayValue('cf_4321') - $posttrip_info->getDisplayValue('cf_4327') ;
      
      $checklist .='
        <tr>
        <td>'.$i++.'</td>
        <td>'.$posttrip_info->getDisplayValue('cf_4315').'</td>
        <td align="center">'.$posttrip_info->getDisplayValue('cf_4317').'</td>
        <td align="center">'.$posttrip_info->getDisplayValue('cf_4319').' '.$posttrip_info->getDisplayValue('cf_4329').'</td>
        <td align="center">'.number_format($posttrip_info->getDisplayValue('cf_4321'),2).'</td>
        <td align="center">'.$posttrip_info->getDisplayValue('cf_4323').'</td>
        <td align="center">'.$posttrip_info->getDisplayValue('cf_4325').'</td>
        <td align="center">'.number_format($posttrip_info->getDisplayValue('cf_4327'),2).'</td>
        <td>'.number_format($difference,2).'</td>
        <td>'.$posttrip_info->getDisplayValue('cf_4557').'</td>
      </tr>';
	  if($posttrip_info->get('cf_4315')!='549416')
	  {
	  	$post_sum_tenge +=$posttrip_info->getDisplayValue('cf_4321');
		$pre_sum_tenge  +=$posttrip_info->getDisplayValue('cf_4327');
	  }	  
	  
	  
    }

    $this->setValue('checklist', $checklist);
	
	$this->setValue('post_sum_tenge',  number_format($post_sum_tenge , 2 ,  "." , "," ));
	$this->setValue('pre_sum_tenge', number_format($pre_sum_tenge , 2 ,  "." , "," ));

    include('include/mpdf60/mpdf.php');
	@date_default_timezone_set($current_user->get('time_zone'));
	
    $mpdf = new mPDF('utf-8', 'A4-L', '10', '', 10, 10, 30, 15, 10, 5); /*задаем формат, отступы и.т.д.*/
    $mpdf->charset_in = 'utf8';
  
    $mpdf->list_indent_first_level = 0; 

    //$mpdf->SetDefaultFontSize(12);
    //$mpdf->setAutoTopMargin(2);
    $mpdf->SetHTMLHeader('
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td align="right" style="font-size:9;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
            JCR Form, GLOBALINK, designed: March, 2010
          </td>
        </tr>
        <tr>
          <td align="right"><img src="printtemplates/glklogo.jpg"/ width="160" height="30"></td>
        </tr>
      </table>');

    $mpdf->SetHTMLFooter('
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td width="40%" align="left" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
            Printed: '.date('d.m.Y; H:i').' by '.$current_user->get('user_name').'
          </td>
          <td width="20%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
            Page {PAGENO} of {nbpg}
          </td>
          <td width="40%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
            &nbsp;
          </td>
        </tr>
      </table>');

    $stylesheet = file_get_contents('include/mpdf60/examples/mpdfstyletables.css');
    $mpdf->WriteHTML($stylesheet,1);  // The parameter 1 tells that this is css/style only and no body/html/text
    $mpdf->WriteHTML($this->_documentXML); /*формируем pdf*/

    $pdf_name = 'pdf_docs/posttrip_checklist.pdf';
    
    $mpdf->Output($pdf_name, 'F');
    //header('Location:http://mb.globalink.net/vt60/'.$pdf_name);
    header('Location:'.$pdf_name);
    exit;
  }

  public function print_tripexpense($request) {
    $moduleName = $request->getModule();
    $record = $request->get('record');

    $fleet_id = $record;
    $current_user = Users_Record_Model::getCurrentUserModel();
    $fleet_info_detail = Vtiger_Record_Model::getInstanceById($fleet_id, 'Fleettrip');
    
    $document = $this->loadTemplate('printtemplates/Fleettrip/advance_report.html');

    $this->setValue('fleet_ref_no', $fleet_info_detail->get('cf_3283'));
  
  $driver_user_info = Users_Record_Model::getInstanceById($fleet_info_detail->get('cf_3167'), 'Users');
  $this->setValue('driver', htmlentities($driver_user_info->get('first_name').' '.$driver_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));
   

    $pagingModel_1 = new Vtiger_Paging_Model();
    $pagingModel_1->set('page','1');
    
    $relatedModuleName_1 = 'TripExpense';
    $parentRecordModel_1 = $fleet_info_detail;
    $relationListView_1 = Vtiger_RelationListView_Model::getInstance($parentRecordModel_1, $relatedModuleName_1, $label);
    $models_1 = $relationListView_1->getEntries($pagingModel_1);
    $tripexpense = '';
    $i = 1;
    $total_in_kzt = 0;
    $fleet_final_date = date('Y-m-d');
     
  $advance_report_date = '';
  $index = 0;
    foreach($models_1 as $key => $model){
    
      $service = $model->getDisplayValue('cf_4813');
  
    if($index==0)
    {
      $advance_report_date = $model->getDisplayValue('cf_4959');
    }
  
      if($service=='Fuel' || $service=='AdBlue' || $service=='XOY') {continue;}
      
      $trip_expense_id  = $model->getId();      
      $sourceModule  = 'TripExpense';  
      $tripexpense_info = Vtiger_Record_Model::getInstanceById($trip_expense_id, $sourceModule);
      //$fleet_final_date = $tripexpense_info->getDisplayValue('cf_4809');
      
      $tripexpense .= '
        <tr>
          <td align="center">'.$i++.'</td>
          <td>'.$tripexpense_info->getDisplayValue('cf_4953').' '.$tripexpense_info->getDisplayValue('cf_4955').'</td>
          <td align="center">'.$tripexpense_info->getDisplayValue('cf_4961').' '.$tripexpense_info->getDisplayValue('cf_4975').'</td>
          <td align="center">'.$tripexpense_info->getDisplayValue('cf_4963').'</td>
          <td align="center">'.number_format($tripexpense_info->getDisplayValue('cf_4965'),2).'</td>
          <td align="center">'.$tripexpense_info->getDisplayValue('cf_4967').'</td>
          <td align="center">'.$tripexpense_info->getDisplayValue('cf_4969').'</td>
          <td align="center">'.number_format($tripexpense_info->getDisplayValue('cf_4971'),2).'</td>
        </tr>';
          
      $total_in_kzt +=$tripexpense_info->getDisplayValue('cf_4971');  
    
    $index++;      
    }
  
    $this->setValue('fleet_final_date', $advance_report_date);
    $this->setValue('tripexpense', $tripexpense);
    $this->setValue('total_in_kzt', number_format($total_in_kzt,2));
    
    include('include/mpdf60/mpdf.php');
	@date_default_timezone_set($current_user->get('time_zone'));
    //$mpdf = new mPDF('utf-8', 'A4-L', '10', '', 10, 10, 30, 15, 10, 5); /*задаем формат, отступы и.т.д.*/
  $mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 30, 15, 10, 5); /*задаем формат, отступы и.т.д.*/
    $mpdf->charset_in = 'utf8';
    
    $mpdf->list_indent_first_level = 0; 

    //$mpdf->SetDefaultFontSize(12);
    //$mpdf->setAutoTopMargin(2);
    $mpdf->SetHTMLHeader('
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td align="right" style="font-size:9;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
            Advance Report
          </td>
        </tr>
        <tr>
          <td align="right"><img src="printtemplates/glklogo.jpg"/ width="160" height="30"></td>
        </tr>
      </table>');

    $mpdf->SetHTMLFooter('
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td width="40%" align="left" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
            Printed: '.date('d.m.Y; H:i').' by '.$current_user->get('user_name').'
          </td>
          <td width="20%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
            Page {PAGENO} of {nbpg}
          </td>
          <td width="40%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">&nbsp;</td>
        </tr>
      </table>');

    $stylesheet = file_get_contents('include/mpdf60/examples/mpdfstyletables.css');
    $mpdf->WriteHTML($stylesheet,1);  // The parameter 1 tells that this is css/style only and no body/html/text
    $mpdf->WriteHTML($this->_documentXML); /*формируем pdf*/

    $pdf_name = 'pdf_docs/advance_report.pdf';
    
    $mpdf->Output($pdf_name, 'F');
    header('Location:' .$pdf_name);
    exit;    
  }
  
  public function print_triplistfuel($request) {
    $moduleName = $request->getModule();
    $record = $request->get('record');
    $lang = $request->get('lang');
    
    $fleettrip_id = $record;
    $current_user = Users_Record_Model::getCurrentUserModel();
    $fleettrip_info = Vtiger_Record_Model::getInstanceById($fleettrip_id, 'Fleettrip');
    
    if($lang == 'ru') {
      $document = $this->loadTemplate('printtemplates/Fleettrip/triplistfuel_report_ru.html');
    } else {
      $document = $this->loadTemplate('printtemplates/Fleettrip/triplistfuel_report_eng.html');
    }

    $this->setValue('fleet_ref_no', $fleettrip_info->get('cf_3283'));
    $this->setValue('origin_city', $fleettrip_info->get('cf_3241'));
    $this->setValue('destination_city', $fleettrip_info->get('cf_3243'));
    $this->setValue('driver_name', htmlentities($fleettrip_info->getDisplayValue('cf_3167'), ENT_QUOTES, "UTF-8"));
	$trailer_no =  htmlentities($fleettrip_info->getDisplayValue('cf_5139'), ENT_QUOTES, "UTF-8");
	$truck_no = htmlentities($fleettrip_info->getDisplayValue('cf_3165'), ENT_QUOTES, "UTF-8");
	$truck_trailer_info = $truck_no.' '.($trailer_no!=0 ? "- ".$trailer_no :'');
    $this->setValue('truck_no', $truck_trailer_info);
    
    if ($fleettrip_info->get('cf_3245')) {
      $this->setValue('from_date', date("d.m.Y", strtotime($fleettrip_info->get('cf_3245'))));
    } else {
      $this->setValue('from_date', "");
    }

    if ($fleettrip_info->get('cf_3247')) {
      $this->setValue('to_date', date("d.m.Y", strtotime($fleettrip_info->get('cf_3247'))));
    } else {
      $this->setValue('to_date', "");
    }
    
    $this->setValue('due_leave_km', $fleettrip_info->get('cf_3257'));
    $this->setValue('due_arrival_km', $fleettrip_info->get('cf_3259'));
	
	
	$adb_triplistfuel_begin = PearDatabase::getInstance();
	$fleet_triplistfuel_sql_begin = "SELECT * FROM vtiger_triplistfuelcf where triplistfuelid=(SELECT min(vtiger_triplistfuel.triplistfuelid) as last_triplistfule_id FROM vtiger_triplistfuel 
									 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_triplistfuel.triplistfuelid 
									 INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR 
															   vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
									 LEFT JOIN vtiger_triplistfuelcf as vtiger_triplistfuelcf 
									 ON vtiger_triplistfuelcf.triplistfuelid=vtiger_triplistfuel.triplistfuelid 						   
									 
									 WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? 
									 AND vtiger_crmentityrel.module='Fleettrip' AND vtiger_crmentityrel.relmodule='TripListFuel')
							 		 ";
	//SELECT * FROM vtiger_triplistfuel where triplistfuelid=(select max(triplistfuelid) from vtiger_triplistfuel);					 
	$params_fleet_triplistfuel_begin = array($fleettrip_id);
	$result_last_updated_triplistfuel_begin = $adb_triplistfuel_begin->pquery($fleet_triplistfuel_sql_begin, $params_fleet_triplistfuel_begin);
	$begin_updated_record = $adb_triplistfuel_begin->num_rows($result_last_updated_triplistfuel_begin);	
	
	$due_leave_fuel = 0;
	if($begin_updated_record != 0)
	{
		$row_triplistfuel_begin = $adb_triplistfuel_begin->fetch_array($result_last_updated_triplistfuel_begin);
		$due_leave_fuel = $row_triplistfuel_begin['cf_5025'];
	}	
	
	$this->setValue('due_leave_fuel', $due_leave_fuel);
	
	$adb_triplistfuel = PearDatabase::getInstance();
	$fleet_triplistfuel_sql =  "SELECT * FROM vtiger_triplistfuelcf where triplistfuelid=(SELECT max(vtiger_triplistfuel.triplistfuelid) as last_triplistfule_id FROM vtiger_triplistfuel 
							 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_triplistfuel.triplistfuelid 
							 INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR 
													   vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
							 LEFT JOIN vtiger_triplistfuelcf as vtiger_triplistfuelcf 
							 ON vtiger_triplistfuelcf.triplistfuelid=vtiger_triplistfuel.triplistfuelid 						   
							 
							 WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? 
							 AND vtiger_crmentityrel.module='Fleettrip' AND vtiger_crmentityrel.relmodule='TripListFuel')
							 ";
	//SELECT * FROM vtiger_triplistfuel where triplistfuelid=(select max(triplistfuelid) from vtiger_triplistfuel);					 
	$fleet_id = $sourceRecord;					 
	$params_fleet_triplistfuel = array($fleettrip_id);
	$result_last_updated_triplistfuel = $adb_triplistfuel->pquery($fleet_triplistfuel_sql, $params_fleet_triplistfuel);
	$last_updated_record = $adb_triplistfuel->num_rows($result_last_updated_triplistfuel);	
	
	$due_arrival_fuel = 0;
	if($last_updated_record != 0)
	{
		$row_triplistfuel = $adb_triplistfuel->fetch_array($result_last_updated_triplistfuel);
		$due_arrival_fuel = $row_triplistfuel['cf_5031'];
	}	
	
    $this->setValue('due_arrival_fuel', $due_arrival_fuel);
    
    $triplistfuel = '';
    $triplistxoy = '';
    $triplistadblue = '';
    
    $total_mileage = 0;
    $total_mileage_gps = 0;
	$total_refill  =0;
	$total_consumption = 0;
	
    $mileage_kz = 0;
    $mileage_ru = 0;
    $mileage_eu = 0;

    // Getting TripListFuel data  
    $pagingModel_1 = new Vtiger_Paging_Model();
    $pagingModel_1->set('page','1');

    $relatedModuleName_1 = 'TripListFuel';
    $parentRecordModel_1 = $fleettrip_info;
    $relationListView_1 = Vtiger_RelationListView_Model::getInstance($parentRecordModel_1, $relatedModuleName_1, $label);
    $models_1 = $relationListView_1->getEntries($pagingModel_1);

    foreach($models_1 as $key => $model) {
      
      $trip_listfuel_id  = $model->getId();     
      $sourceModule   = 'TripListFuel'; 
      $triplistfuel_info = Vtiger_Record_Model::getInstanceById($trip_listfuel_id, $sourceModule);
    
      $triplistfuel .= '
        <tr>
          <td class="bordered" width="7%" align="center">
            ' . date("d.m.Y", strtotime($triplistfuel_info->getDisplayValue('cf_5009'))) . '
          </td>
          <td class="bordered" width="7%" align="center">
            ' . date("d.m.Y", strtotime($triplistfuel_info->getDisplayValue('cf_5011'))) . '
          </td>
          <td class="bordered" width="10%" align="center">' . $triplistfuel_info->getDisplayValue('cf_5013') . '</td>
          <td class="bordered" width="10%" align="center">' . $triplistfuel_info->getDisplayValue('cf_5015') . '</td>
          <td class="bordered" width="6%" align="center">' . $triplistfuel_info->getDisplayValue('cf_5017') . '</td>
          <td class="bordered" width="6%" align="center">' . $triplistfuel_info->getDisplayValue('cf_5019') . '</td>
          <td class="bordered" width="6%" align="center">' . $triplistfuel_info->getDisplayValue('cf_5021') . '</td>
          <td class="bordered" width="8%" align="center">' . $triplistfuel_info->getDisplayValue('cf_5023') . '</td>
          <td class="bordered" width="12%" align="center">' . $triplistfuel_info->getDisplayValue('cf_5025') . '</td>
          <td class="bordered" width="8%" align="center">' . $triplistfuel_info->getDisplayValue('cf_5027') . '</td>
          <td class="bordered" width="8%" align="center">' . $triplistfuel_info->getDisplayValue('cf_5029') . '</td>
          <td class="bordered" width="12%" align="center">' . $triplistfuel_info->getDisplayValue('cf_5031') . '</td>
        </tr>
      ';
    
      //For country
      $country = $triplistfuel_info->getDisplayValue('cf_5017');
      switch($country)
      {
        case 'KZ':
        $mileage_kz += $triplistfuel_info->getDisplayValue('cf_5019');
        break;
        case 'RU':
        $mileage_ru += $triplistfuel_info->getDisplayValue('cf_5019');
        break;
        case 'EU':
        $mileage_eu += $triplistfuel_info->getDisplayValue('cf_5019');
        break;
      }

      $total_mileage += $triplistfuel_info->getDisplayValue('cf_5019');
      $total_mileage_gps += $triplistfuel_info->getDisplayValue('cf_5021');
	  
	  $total_refill +=$triplistfuel_info->getDisplayValue('cf_5027');
	  $total_consumption +=$triplistfuel_info->getDisplayValue('cf_5029');
    }
    
    if ($triplistfuel != "" && $lang == "ru") {
      $triplistfuel .= '
        <tr>
          <td class="bordered" width="11%" align="center">&nbsp;</td>
          <td class="bordered" width="11%" align="center">&nbsp;</td>
          <td class="bordered" width="13%" align="center">&nbsp;</td>
          <td class="bordered" width="13%" align="center">&nbsp;</td>
          <td class="bordered" width="6%" align="center">&nbsp;</td>
          <td class="bordered" width="6%" align="center">' . $total_mileage . '</td>
          <td class="bordered" width="6%" align="center">' . $total_mileage_gps . '</td>
          <td class="bordered" width="8%" align="center">&nbsp;</td>
          <td class="bordered" width="6%" align="center">&nbsp;</td>
          <td class="bordered" width="6%" align="center">'.$total_refill.'</td>
          <td class="bordered" width="8%" align="center">'.$total_consumption.'</td>
          <td class="bordered" width="6%" align="center">&nbsp;</td>
        </tr>
      ';
    } else {
      $triplistfuel .= '
        <tr>
          <td class="bordered" width="7%" align="center">&nbsp;</td>
          <td class="bordered" width="7%" align="center">&nbsp;</td>
          <td class="bordered" width="10%" align="center">&nbsp;</td>
          <td class="bordered" width="10%" align="center">&nbsp;</td>
          <td class="bordered" width="6%" align="center">&nbsp;</td>
          <td class="bordered" width="6%" align="center">' . $total_mileage . '</td>
          <td class="bordered" width="6%" align="center">' . $total_mileage_gps . '</td>
          <td class="bordered" width="10%" align="center">&nbsp;</td>
          <td class="bordered" width="12%" align="center">&nbsp;</td>
          <td class="bordered" width="6%" align="center">'.$total_refill.'</td>
          <td class="bordered" width="8%" align="center">'.$total_consumption.'</td>
          <td class="bordered" width="12%" align="center">&nbsp;</td>
        </tr>
      ';
    }
  
    $this->setValue('triplistfuel', $triplistfuel);

    // Getting TripListXOY data
    $pagingModel_2 = new Vtiger_Paging_Model();
    $pagingModel_2->set('page','1');

    $relatedModuleName_2 = 'TripListXOY';
    $parentRecordModel_2 = $fleettrip_info;
    $relationListView_2 = Vtiger_RelationListView_Model::getInstance($parentRecordModel_2, $relatedModuleName_2, $label);
    $models_2 = $relationListView_2->getEntries($pagingModel_2);

    foreach($models_2 as $key => $model) {
      
      $trip_listxoy_id  = $model->getId();     
      $sourceModule   = 'TripListXOY'; 
      $triplistxoy_info = Vtiger_Record_Model::getInstanceById($trip_listxoy_id, $sourceModule);
      
      $triplistxoy .= '
        <tr>
          <td class="bordered">' . $triplistxoy_info->getDisplayValue('cf_5053') . '</td>
          <td class="bordered">' . $triplistxoy_info->getDisplayValue('cf_5055') . '</td>
          <td class="bordered">' . $triplistxoy_info->getDisplayValue('cf_5057') . '</td>
          <td class="bordered">' . $triplistxoy_info->getDisplayValue('cf_5071') . '</td>
          <td class="bordered">' . $triplistxoy_info->getDisplayValue('cf_5059') . '</td>
          <td class="bordered">' . $triplistxoy_info->getDisplayValue('cf_5061') . '</td>
        </tr>
      ';
    }

    if ($triplistxoy == "") {
      $triplistxoy .= '
        <tr>
          <td class="bordered">&nbsp;</td>
          <td class="bordered">&nbsp;</td>
          <td class="bordered">&nbsp;</td>
          <td class="bordered">&nbsp;</td>
          <td class="bordered">&nbsp;</td>
          <td class="bordered">&nbsp;</td>
        </tr>
      ';
    }

    $this->setValue('triplistxoy', $triplistxoy);

    // Getting TripListAdBlue data
    $pagingModel_3 = new Vtiger_Paging_Model();
    $pagingModel_3->set('page','1');

    $relatedModuleName_3 = 'TripListAdBlue';
    $parentRecordModel_3 = $fleettrip_info;
    $relationListView_3 = Vtiger_RelationListView_Model::getInstance($parentRecordModel_3, $relatedModuleName_3, $label);
    $models_3 = $relationListView_3->getEntries($pagingModel_3);

    foreach($models_3 as $key => $model) {
      
      $trip_listadblue_id  = $model->getId();     
      $sourceModule   = 'TripListAdBlue'; 
      $triplistadblue_info = Vtiger_Record_Model::getInstanceById($trip_listadblue_id, $sourceModule);
      
      $triplistadblue .= '
        <tr>
          <td class="bordered">' . $triplistadblue_info->getDisplayValue('cf_5063') . '</td>
          <td class="bordered">' . $triplistadblue_info->getDisplayValue('cf_5065') . '</td>
          <td class="bordered">' . $triplistadblue_info->getDisplayValue('cf_5067') . '</td>
          <td class="bordered">' . $triplistadblue_info->getDisplayValue('cf_5069') . '</td>
        </tr>
      ';
    }

    if ($triplistadblue == "") {
      $triplistadblue .= '
        <tr>
          <td class="bordered">&nbsp;</td>
          <td class="bordered">&nbsp;</td>
          <td class="bordered">&nbsp;</td>
          <td class="bordered">&nbsp;</td>
        </tr>
      ';
    }

    $this->setValue('triplistadblue', $triplistadblue);

    $total_days_in_trip = abs(strtotime($fleettrip_info->get('cf_3247')) - strtotime($fleettrip_info->get('cf_3245')))/(60*60*24)+1;
	
	
	$demurrages_in_kz = $fleettrip_info->get('cf_5073');
	$demurrages_in_ru = $fleettrip_info->get('cf_5075');
	$demurrages_in_eu = $fleettrip_info->get('cf_5077');
	
    $including_demurrages = $demurrages_in_kz + $demurrages_in_ru + $demurrages_in_eu;
    $days_in_trip_without_demurrages = $total_days_in_trip - $including_demurrages;
    $daily_average_mileage = round($total_mileage / $days_in_trip_without_demurrages, 2);

    $this->setValue('total_mileage', $total_mileage);
    $this->setValue('total_days_in_trip', $total_days_in_trip);
    $this->setValue('including_demurrages', $including_demurrages);
    $this->setValue('days_in_trip_without_demurrages', $days_in_trip_without_demurrages);
    $this->setValue('daily_average_mileage', $daily_average_mileage);
  
    //For KZ Country
    $daily_allowance_in_kz = 0.00;
	//mileage_kz
    if($daily_average_mileage <= 600)
    {
      $daily_allowance_in_kz = '11.00';
    }
    elseif($daily_average_mileage >= 601 && $daily_average_mileage <=650)
    {
      $daily_allowance_in_kz = '11.50';
    }
    elseif($daily_average_mileage >= 651)
    {
      $daily_allowance_in_kz = '12.00';
    }
    
    //For RU Country
    $daily_allowance_in_ru = 0.00;
	//mileage_ru
    if($daily_average_mileage <= 600)
    {
      $daily_allowance_in_ru = '14.00';
    }
    elseif($daily_average_mileage >= 601 && $daily_average_mileage <=650)
    {
      $daily_allowance_in_ru = '14.50';
    }
    elseif($daily_average_mileage >= 651)
    {
      $daily_allowance_in_ru = '15.00';
    }
    
    //For EU Country
	$daily_allowance_in_eu = 0.00;
	//mileage_eu
    if($daily_average_mileage <= 600)
    {
      $daily_allowance_in_eu = '17.00';
    }
    elseif($daily_average_mileage >= 601 && $daily_average_mileage <=650)
    {
      $daily_allowance_in_eu = '17.50';
    }
    elseif($daily_average_mileage >= 651)
    {
      $daily_allowance_in_eu = '18.00';
    }
    
    $allowance_in_kz = $mileage_kz * $daily_allowance_in_kz;
    $allowance_in_ru = $mileage_ru * $daily_allowance_in_ru;
    $allowance_in_eu = $mileage_eu * $daily_allowance_in_eu;
    
	
    $this->setValue('allowance_in_kz', $daily_allowance_in_kz ." x ". $mileage_kz." = ".$allowance_in_kz);
    $this->setValue('allowance_in_ru', $daily_allowance_in_ru ." x ". $mileage_ru." = ".$allowance_in_ru);
    $this->setValue('allowance_in_eu', $daily_allowance_in_eu ." x ". $mileage_eu." = ".$allowance_in_eu); 
    
    $this->setValue('daily_allowance_in_kz', $daily_allowance_in_kz);
    $this->setValue('daily_allowance_in_ru', $daily_allowance_in_ru);
    $this->setValue('daily_allowance_in_eu', $daily_allowance_in_eu); 
	
	$demurrage_in_KZ_per_day = '4000.00';
    $demurrage_in_RU_per_day = '6000.00';
    $demurrage_in_EU_per_day = '6500.00';
	
	$allowance_demurrage_in_KZ_per_day = $demurrages_in_kz * $demurrage_in_KZ_per_day;
	$allowance_demurrage_in_RU_per_day = $demurrages_in_ru * $demurrage_in_RU_per_day;
	$allowance_demurrage_in_EU_per_day = $demurrages_in_eu * $demurrage_in_EU_per_day;
	
	$this->setValue('allowance_demurrage_in_KZ_per_day', $demurrages_in_kz ." x ". $demurrage_in_KZ_per_day." = ".$allowance_demurrage_in_KZ_per_day);
    $this->setValue('allowance_demurrage_in_RU_per_day', $demurrages_in_ru ." x ". $demurrage_in_RU_per_day." = ".$allowance_demurrage_in_RU_per_day);
    $this->setValue('allowance_demurrage_in_EU_per_day', $demurrages_in_eu ." x ". $demurrage_in_EU_per_day." = ".$allowance_demurrage_in_EU_per_day); 
	
	$adb_tripexpense = PearDatabase::getInstance();
	$one_time_expense = "SELECT sum(vtiger_tripexpensecf.cf_4971) as total_price_in_kzt FROM `vtiger_tripexpensecf` 
						 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_tripexpensecf.tripexpenseid 
						 INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR 
						 									vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
						WHERE vtiger_tripexpensecf.cf_4953 = 'One Time' AND vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? 
						AND vtiger_crmentityrel.module='Fleettrip' AND vtiger_crmentityrel.relmodule='TripExpense'
						";
	$params_fleet_tripexpense = array($fleettrip_id);
	$result_tripexpense = $adb_tripexpense->pquery($one_time_expense, $params_fleet_tripexpense);	
	$row_tripexpense = $adb_tripexpense->fetch_array($result_tripexpense);
	$one_time_payment = $row_tripexpense['total_price_in_kzt'];				
	$this->setValue('one_time_payment', $one_time_payment);	
	
	
	$adb_load_unload = PearDatabase::getInstance();
	$load_unload = "SELECT sum(vtiger_tripexpensecf.cf_4971) as total_price_in_kzt FROM `vtiger_tripexpensecf` 
						 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_tripexpensecf.tripexpenseid 
						 INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR 
						 									vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) 
						WHERE (vtiger_tripexpensecf.cf_4953 = 'Additional Loading' OR vtiger_tripexpensecf.cf_4953 ='Additional Unloading') 
						AND vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? 
						AND vtiger_crmentityrel.module='Fleettrip' AND vtiger_crmentityrel.relmodule='TripExpense'
						";
	$params_fleet_load_unload = array($fleettrip_id);
	$result_load_unload = $adb_load_unload->pquery($load_unload, $params_fleet_load_unload);	
	$row_load_unload = $adb_load_unload->fetch_array($result_load_unload);
	$additional_load_unload = $row_load_unload['total_price_in_kzt'];				
	$this->setValue('additional_load_unload', $additional_load_unload);				

    include('include/mpdf60/mpdf.php');
	@date_default_timezone_set($current_user->get('time_zone'));
    //$mpdf = new mPDF('utf-8', 'A4-L', '10', '', 10, 10, 30, 15, 10, 5); /*задаем формат, отступы и.т.д.*/
	$mpdf = new mPDF('utf-8', 'A4-L', '10', '', 4, 4, 6, 6, 5, 2); /*задаем формат, отступы и.т.д.*/
    $mpdf->charset_in = 'utf8';
    
    $mpdf->list_indent_first_level = 0; 

    //$mpdf->SetDefaultFontSize(12);
    //$mpdf->setAutoTopMargin(2);
	/*
    $mpdf->SetHTMLHeader('
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td align="right" style="font-size:9; font-family:Verdana, Geneva, sans-serif; font-weight:bold;">
            Trip List Fuel
          </td>
        </tr>
        <tr>
          <td align="right"><img src="printtemplates/glklogo.jpg"/ width="160" height="30">
          </td>
        </tr>
      </table>'
    );
	*/
	
    $mpdf->SetHTMLFooter('
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td width="40%" align="left" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
            Printed: '.date('d.m.Y; H:i').' by '.$current_user->get('user_name').'
          </td>
          <td width="20%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
            Page {PAGENO} of {nbpg}
          </td>
          <td width="40%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
            &nbsp;
          </td>
        </tr>
      </table>'
    );
	

    $stylesheet = file_get_contents('include/mpdf60/examples/mpdfstyletables.css');
    $mpdf->WriteHTML($stylesheet,1);  // The parameter 1 tells that this is css/style only and no body/html/text
    $mpdf->WriteHTML($this->_documentXML); /*формируем pdf*/
    
    $pdf_name = 'pdf_docs/triplistfuel_report.pdf';
    
    $mpdf->Output($pdf_name, 'F');
    header('Location:'.$pdf_name);
    exit;
  }
  
  
 
  public function print_triplistlocal($request) {
    $moduleName = $request->getModule();
    $record = $request->get('record');
    $lang = $request->get('lang');
    
    $fleet_id = $record;
    $current_user = Users_Record_Model::getCurrentUserModel();
    $fleet_info_detail = Vtiger_Record_Model::getInstanceById($fleet_id, 'Fleettrip');    

    $document = $this->loadTemplate('printtemplates/Fleettrip/triplist_local.html');
    
    $pagingModel_1 = new Vtiger_Paging_Model();
    $pagingModel_1->set('page','1');

    $this->setValue('fleet_ref_no', $fleet_info_detail->get('cf_3283'));
    
    include('include/mpdf60/mpdf.php');
	@date_default_timezone_set($current_user->get('time_zone'));
    $mpdf = new mPDF('utf-8', 'A4-L', '10', '', 10, 10, 30, 15, 10, 5); /*задаем формат, отступы и.т.д.*/
    $mpdf->charset_in = 'utf8';
    
    $mpdf->list_indent_first_level = 0; 

    //$mpdf->SetDefaultFontSize(12);
    //$mpdf->setAutoTopMargin(2);
    $mpdf->SetHTMLHeader('
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td align="right" style="font-size:9; font-family:Verdana, Geneva, sans-serif; font-weight:bold;">
            Trip List Local
          </td>
        </tr>
        <tr>
          <td align="right"><img src="printtemplates/glklogo.jpg"/ width="160" height="30">
          </td>
        </tr>
      </table>'
    );

    $mpdf->SetHTMLFooter('
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td width="40%" align="left" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
            Printed: '.date('d.m.Y; H:i').' by '.$current_user->get('user_name').'
          </td>
          <td width="20%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
            Page {PAGENO} of {nbpg}
          </td>
          <td width="40%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
            &nbsp;
          </td>
        </tr>
      </table>'
    );

    $stylesheet = file_get_contents('include/mpdf60/examples/mpdfstyletables.css');
    $mpdf->WriteHTML($stylesheet,1);  // The parameter 1 tells that this is css/style only and no body/html/text
    $mpdf->WriteHTML($this->_documentXML); /*формируем pdf*/
    
    $pdf_name = 'pdf_docs/triplistlocal_report.pdf';
   
    $mpdf->Output($pdf_name, 'F');
    //header('Location: http://192.168.5.43/live/'.$pdf_name);
    header('Location:'.$pdf_name);
    exit;
  }

  public function print_triplistinternational($request) {
    $moduleName = $request->getModule();
    $record = $request->get('record');
    $lang = $request->get('lang');
    
    $fleet_id = $record;
    $current_user = Users_Record_Model::getCurrentUserModel();
    $fleet_info_detail = Vtiger_Record_Model::getInstanceById($fleet_id, 'Fleettrip');
    
    $document = $this->loadTemplate('printtemplates/Fleettrip/triplist_international.html');
    
    $pagingModel_1 = new Vtiger_Paging_Model();
    $pagingModel_1->set('page','1');

    $this->setValue('fleet_ref_no', $fleet_info_detail->get('cf_3283'));
    
    include('include/mpdf60/mpdf.php');
	 @date_default_timezone_set($current_user->get('time_zone'));
    $mpdf = new mPDF('utf-8', 'A4-L', '10', '', 10, 10, 30, 15, 10, 5); /*задаем формат, отступы и.т.д.*/
    $mpdf->charset_in = 'utf8';
    
    $mpdf->list_indent_first_level = 0; 

    //$mpdf->SetDefaultFontSize(12);
    //$mpdf->setAutoTopMargin(2);
    $mpdf->SetHTMLHeader('
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td align="right" style="font-size:9; font-family:Verdana, Geneva, sans-serif; font-weight:bold;">
            Trip List Fuel
          </td>
        </tr>
        <tr>
          <td align="right"><img src="printtemplates/glklogo.jpg"/ width="160" height="30">
          </td>
        </tr>
      </table>'
    );

    $mpdf->SetHTMLFooter('
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td width="40%" align="left" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
            Printed: '.date('d.m.Y; H:i').' by '.$current_user->get('user_name').'
          </td>
          <td width="20%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
            Page {PAGENO} of {nbpg}
          </td>
          <td width="40%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
            &nbsp;
          </td>
        </tr>
      </table>'
    );

    $stylesheet = file_get_contents('include/mpdf60/examples/mpdfstyletables.css');
    $mpdf->WriteHTML($stylesheet,1);  // The parameter 1 tells that this is css/style only and no body/html/text
    $mpdf->WriteHTML($this->_documentXML); /*формируем pdf*/
    
    $pdf_name = 'pdf_docs/triplistinternational_report.pdf';
    
    $mpdf->Output($pdf_name, 'F');
    // header('Location: http://192.168.5.43/live/'.$pdf_name);
    header('Location:'.$pdf_name);
    exit;
  }
  
  public function print_fueldata($request) {
    $moduleName = $request->getModule();
    $record = $request->get('record');
    $lang = $request->get('lang');

    $current_user = Users_Record_Model::getCurrentUserModel();

    $fleettrip_id = $record;
    $fleettrip_info_detail = Vtiger_Record_Model::getInstanceById($fleettrip_id, 'Fleettrip');

    $pagingModel_1 = new Vtiger_Paging_Model();
    $pagingModel_1->set('page','1');

    $relatedModuleName_1 = 'TripExpense';
    $parentRecordModel_1 = $fleettrip_info_detail;
    $relationListView_1 = Vtiger_RelationListView_Model::getInstance($parentRecordModel_1, $relatedModuleName_1, $label);
    $models_1 = $relationListView_1->getEntries($pagingModel_1);

    $fueldata = '';

    foreach($models_1 as $key => $model) {
      $tripexpense_id = $model->getId();     
      $sourceModule = 'TripExpense'; 
      $tripexpense_info = Vtiger_Record_Model::getInstanceById($tripexpense_id, $sourceModule);

      if ($tripexpense_info->getDisplayValue('cf_4953') == "Fuel") {
        $fueldata .= '
          <tr>
            <td class="bordered">' . date("d.m.Y", strtotime($tripexpense_info->getDisplayValue('cf_4959'))) . '</td>
            <td class="bordered">' . $tripexpense_info->getDisplayValue('cf_4957') . '</td>
            <td class="bordered">' . $tripexpense_info->getDisplayValue('cf_4961') . '</td>
            <td class="bordered">' . $tripexpense_info->getDisplayValue('cf_5041') . '</td>
            <td class="bordered">' . $tripexpense_info->getDisplayValue('cf_5043') . '</td>
          </tr>
        ';
      } else {
        continue;
      }
    }

    if ($fueldata == "") {
      $fueldata .= '
        <tr>
          <td class="bordered">&nbsp;</td>
          <td class="bordered">&nbsp;</td>
          <td class="bordered">&nbsp;</td>
          <td class="bordered">&nbsp;</td>
          <td class="bordered">&nbsp;</td>
        </tr>
      ';
    }

    if($lang == 'ru') {
      $document = $this->loadTemplate('printtemplates/Fleettrip/fueldata_report_ru.html');
    } else {
      $document = $this->loadTemplate('printtemplates/Fleettrip/fueldata_report_eng.html');
    }
    
    $this->setValue('fleet_ref_no', $fleettrip_info_detail->getDisplayValue('cf_3283'));
    $this->setValue('route', $fleettrip_info_detail->getDisplayValue('cf_3161'));
    $this->setValue('truck_no', $fleettrip_info_detail->getDisplayValue('cf_3165'));
    $this->setValue('driver_name', $fleettrip_info_detail->getDisplayValue('cf_3167'));
    $this->setValue('fueldata', $fueldata);

    include('include/mpdf60/mpdf.php');
	 @date_default_timezone_set($current_user->get('time_zone'));
    $mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 30, 15, 10, 5); /*задаем формат, отступы и.т.д.*/
    $mpdf->charset_in = 'utf8';
    
    $mpdf->list_indent_first_level = 0; 

    //$mpdf->SetDefaultFontSize(12);
    //$mpdf->setAutoTopMargin(2);
    $mpdf->SetHTMLHeader('
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td align="right" style="font-size:9; font-family:Verdana, Geneva, sans-serif; font-weight:bold;">
            Fuel Data
          </td>
        </tr>
        <tr>
          <td align="right"><img src="printtemplates/glklogo.jpg"/ width="160" height="30">
          </td>
        </tr>
      </table>'
    );

    $mpdf->SetHTMLFooter('
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td width="40%" align="left" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
            Printed: '.date('d.m.Y; H:i').' by '.$current_user->get('user_name').'
          </td>
          <td width="20%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
            Page {PAGENO} of {nbpg}
          </td>
          <td width="40%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
            &nbsp;
          </td>
        </tr>
      </table>'
    );

    $stylesheet = file_get_contents('include/mpdf60/examples/mpdfstyletables.css');
    $mpdf->WriteHTML($stylesheet,1);  // The parameter 1 tells that this is css/style only and no body/html/text
    $mpdf->WriteHTML($this->_documentXML); /*формируем pdf*/
    
    $pdf_name = 'pdf_docs/fueldata_report.pdf';
    
    $mpdf->Output($pdf_name, 'F');
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
    //$this->_objZip->extractTo('Fleettrip.txt', $this->_documentXML);
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