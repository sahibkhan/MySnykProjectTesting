<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Fleet_Print_View extends Vtiger_Print_View {
	
	
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
		
		$fleet_id = $record;
		$current_user = Users_Record_Model::getCurrentUserModel();
		$fleet_info_detail = Vtiger_Record_Model::getInstanceById($fleet_id, 'Fleet');
		
		$fleet_expense  = $this->get_job_id_from_fleet($fleet_id);
		$job_id = $fleet_expense;
		$sourceModule_job 	= 'Job';	
		$job_info_detail = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule_job);
		
		$document = $this->loadTemplate('printtemplates/fleet.html');
		//$this->setValue('mehtab',htmlentities("mehtab", ENT_QUOTES, "UTF-8"));
		
		$driver_user_info = Users_Record_Model::getInstanceById($fleet_info_detail->get('cf_2003'), 'Users');
		
		$owner_fleet_user_info = Users_Record_Model::getInstanceById($fleet_info_detail->get('assigned_user_id'), 'Users');
		
		$pay_to_info = Users_Record_Model::getInstanceById($job_info_detail->get('cf_1441'), 'Accounts');
		
		$this->setValue('mobile',$owner_fleet_user_info->get('phone_mobile'));
		$this->setValue('fax',$owner_fleet_user_info->get('phone_fax'));
		$this->setValue('email',htmlentities($owner_fleet_user_info->get('email1'), ENT_QUOTES, "UTF-8"));
		$this->setValue('cityname',htmlentities($owner_fleet_user_info->getDisplayValue('location_id'), ENT_QUOTES, "UTF-8"));
		$this->setValue('countryname',htmlentities($owner_fleet_user_info->get('address_country'), ENT_QUOTES, "UTF-8"));
		$this->setValue('departmentcode',htmlentities($owner_fleet_user_info->getDisplayValue('department_id'), ENT_QUOTES, "UTF-8"));	
		$this->setValue('dateadded',date('d.m.Y', strtotime($fleet_info_detail->get('CreatedTime'))));				
		$this->setValue('billingto', $pay_to_info->get('accountname'));
		$this->setValue('from', htmlentities($owner_fleet_user_info->get('first_name').' '.$owner_fleet_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));
		
		$this->setValue('refno', $job_info_detail->get('cf_1198'));		
		$this->setValue('grossweight', htmlentities($fleet_info_detail->get('cf_1989').' '.$fleet_info_detail->get('cf_2039'), ENT_QUOTES, "UTF-8"));		
		$this->setValue('volumeweight', htmlentities($fleet_info_detail->get('cf_1991').' '.$fleet_info_detail->get('cf_2041'), ENT_QUOTES, "UTF-8"));
		
		$this->setValue('pieces', htmlentities($fleet_info_detail->get('cf_1987'), ENT_QUOTES, "UTF-8"));
		$this->setValue('truckno', htmlentities($fleet_info_detail->getDisplayValue('cf_2001'), ENT_QUOTES, "UTF-8"));
		$this->setValue('driver', htmlentities($driver_user_info->get('first_name').' '.$driver_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));
		
		$this->setValue('fromdate',date('d.m.Y', strtotime($fleet_info_detail->get('cf_2005'))));			
		$this->setValue('todate',date('d.m.Y', strtotime($fleet_info_detail->get('cf_2007'))));			
		$this->setValue('expectedfromdate',date('d.m.Y', strtotime($fleet_info_detail->get('cf_2009'))));			
		$this->setValue('expectedtodate',date('d.m.Y', strtotime($fleet_info_detail->get('cf_2011'))));			
		$this->setValue('standarddays', htmlentities($fleet_info_detail->get('cf_2013'), ENT_QUOTES, "UTF-8"));
		$this->setValue('alloweddays', htmlentities($fleet_info_detail->get('cf_2015'), ENT_QUOTES, "UTF-8"));	
		
		$this->setValue('origincountry',htmlentities($fleet_info_detail->get('cf_1993'), ENT_QUOTES, "UTF-8"));
		$this->setValue('origincity',htmlentities($fleet_info_detail->get('cf_1997'), ENT_QUOTES, "UTF-8"));		
		$this->setValue('destinationcountry',htmlentities($fleet_info_detail->get('cf_1995'), ENT_QUOTES, "UTF-8"));
		$this->setValue('destinationcity',htmlentities($fleet_info_detail->get('cf_1999'), ENT_QUOTES, "UTF-8"));
		
		$this->setValue('dueleavekm',htmlentities($fleet_info_detail->get('cf_2017'), ENT_QUOTES, "UTF-8"));
		$this->setValue('duearrivalkm',htmlentities($fleet_info_detail->get('cf_2019'), ENT_QUOTES, "UTF-8"));
		$this->setValue('standarddistancekm',htmlentities($fleet_info_detail->get('cf_2021'), ENT_QUOTES, "UTF-8"));
		$this->setValue('kmtravelledduringtrip',htmlentities($fleet_info_detail->get('cf_2023'), ENT_QUOTES, "UTF-8"));
		//$this->setValue('petrolfillingl',htmlentities($fleet_info_detail->get('cf_2025'), ENT_QUOTES, "UTF-8"));
		//$this->setValue('fuelatthebegin',htmlentities($fleet_info_detail->get('cf_2027'), ENT_QUOTES, "UTF-8"));
		//$this->setValue('fuelattheend',htmlentities($fleet_info_detail->get('cf_2029'), ENT_QUOTES, "UTF-8"));
		$this->setValue('standardfuel',htmlentities($fleet_info_detail->get('cf_2031'), ENT_QUOTES, "UTF-8"));
		$this->setValue('fuelusedduringtrip',htmlentities($fleet_info_detail->get('cf_2033'), ENT_QUOTES, "UTF-8"));
		$this->setValue('averageconsumption',htmlentities($fleet_info_detail->get('cf_2035'), ENT_QUOTES, "UTF-8"));
		$this->setValue('averageallowedconsumption',htmlentities($fleet_info_detail->get('cf_2037'), ENT_QUOTES, "UTF-8"));
		
		
		$adb = PearDatabase::getInstance();
				
		$origin_country_id	  = $fleet_info_detail->get('cf_1993');
		$origin_city_id		 = $fleet_info_detail->get('cf_1997');
		$destination_country_id = $fleet_info_detail->get('cf_1995');
		$destination_city_id	= $fleet_info_detail->get('cf_1999');
			
		$truck_info = Vtiger_Record_Model::getInstanceById($fleet_info_detail->get('cf_2001'), 'Truck');
		
		$truck_type_id = $truck_info->get('cf_1911');
												
		$query_trip_expense = "SELECT * from vtiger_triptemplatescf 
							   INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_triptemplatescf.triptemplatesid
							   WHERE vtiger_triptemplatescf.cf_2047 = ? AND 
							         vtiger_triptemplatescf.cf_2053 = ? AND
									 vtiger_triptemplatescf.cf_2057 = ? AND
									 vtiger_triptemplatescf.cf_2055 = ? AND
									 vtiger_triptemplatescf.cf_2059 = ?	AND
									 vtiger_crmentity.deleted = 0
									 Limit 1
								";
		$check_params_trip_expense = array($truck_type_id, $origin_country_id, $origin_city_id, $destination_country_id, $destination_city_id);
		$result_trip_expense = $adb->pquery($query_trip_expense, $check_params_trip_expense);
		$row_truck_type_trip_expense = $adb->fetch_array($result_trip_expense);										
												
		
		//$total_allowance = (@$row_truck_type_trip_expense['cf_2067'] * @$row_truck_type_trip_expense['cf_2069']);//standard_days * daily_allowance
		$total_allowance = @$row_truck_type_trip_expense['cf_2071']; //total allowance
		$parking = @$row_truck_type_trip_expense['cf_2073'];//parking
		$guesthouse = @$row_truck_type_trip_expense['cf_2075']; //guest_house
		$others =  @$row_truck_type_trip_expense['cf_2081']; //others
		
		$total_trip_expense = $total_allowance + $parking + $quest_house + $others;	
		
		$this->setValue('totalallowance', number_format($total_allowance, 2, '.', ','));
		$this->setValue('parking',number_format($parking, 2, '.', ','));
		$this->setValue('guesthouse',number_format($guesthouse, 2, '.', ','));
		$this->setValue('others',number_format($others, 2, '.', ','));
		
		
		//Calculate per litre price of petrol against truck filling latest
		
		$query_truck_fuel_latest = "select vtiger_fuelcf.cf_2101 as petrol_price_l from vtiger_fuelcf 
							INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_fuelcf.fuelid
							INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_fuelcf.fuelid 
							AND vtiger_crmentityrel.module='Truck' AND vtiger_crmentityrel.relmodule='Fuel'
							where vtiger_crmentity.deleted = 0 AND vtiger_crmentityrel.crmid=? 
							order by vtiger_fuelcf.cf_2093 DESC limit 1
							";
									
		$check_params_latest = array($truck_info->get('record_id'));
		$result_latest = $adb->pquery($query_truck_fuel_latest, $check_params_latest);
		$row_truck_fuel_latest = $adb->fetch_array($result_latest);
		
		//$petrol_price_l = $row_truck_fuel_end['cf_2101'];
		//$total_fuel_expense_costing = 	@$row_truck_type_trip_expense['cf_2061'] * $petrol_price_l;	
		$petrol_price_l = @$row_truck_fuel_latest['petrol_price_l'];
		$total_fuel_expense_costing = 	@$row_truck_type_trip_expense['cf_2061'] * $petrol_price_l; //standard_fuel * petrol_price_l
		
		$this->setValue('totalfuelexpense', number_format($total_fuel_expense_costing, 2, '.', ','));
		$totalfuelexpense = $total_fuel_expense_costing;
		$total_cash_required = 	$total_allowance + $parking + $guesthouse + $others + $totalfuelexpense ; 	
		$this->setValue('totalcashrequired', number_format($total_cash_required, 2, '.', ','));
		
		
		//$filename = 'fleet_expense.txt';
		//$this->save('fleet_expense.txt');	
		
		include('include/mpdf60/mpdf.php');

  		$mpdf = new mPDF('utf-8', 'A4-L', '10', '', 10, 10, 7, 7, 10, 10); /*задаем формат, отступы и.т.д.*/
  		$mpdf->charset_in = 'utf8';
		
		$mpdf->list_indent_first_level = 0; 

		//$mpdf->SetDefaultFontSize(12);
		
		$mpdf->WriteHTML($this->_documentXML); /*формируем pdf*/
		
				
		$pdf_name = 'fleet_expense.pdf';
		
		$mpdf->Output($pdf_name, 'F');
		//header('Location:http://mb.globalink.net/vt60/'.$pdf_name);
		header('Location:'.$pdf_name);
		exit;	
		/*//ob_start();
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
		exit;	*/
		/*
		$response = new Vtiger_Response();
		$response->setResult(array('success'=>false,'message'=>  vtranslate('NO_DATA')));
		$response->emit();
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
}