<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class CargoInsurance_Print_View extends Vtiger_Print_View {
	
	
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
		
		
	public function get_job_id_from_insurance($recordId=0)
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
		//$tpl = $request->get('tpl');	
		
		$type = $request->get('type');
		
		if($type=='certificate')
		{
			$this->print_certificate($request);
		}
		else
		{
			$this->print_insurance($request);
		}
	}
		
	function print_certificate(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$record = $request->get('record');
		//$tpl = $request->get('tpl');	

		$insurance_id = $record;
		$current_user = Users_Record_Model::getCurrentUserModel();
			
		
		$current_user = Users_Record_Model::getCurrentUserModel();
		$insurance_info = Vtiger_Record_Model::getInstanceById($insurance_id, 'CargoInsurance');
		

		/*
		$qt_owner_user_info = Users_Record_Model::getInstanceById($quote_info->get('assigned_user_id'), 'Users');
		if ($quote_info->get('contact_id') != ''){
		  $contact_info = Vtiger_Record_Model::getInstanceById($quote_info->get('contact_id'), 'Contacts');
		  $attn = $contact_info->get('firstname').' '.$contact_info->get('lastname');
		}
		*/
		require_once 'Numbers/Words.php';
				
		$document = $this->loadTemplate('printtemplates/CargoInsurance/insurance_certificate.html');		
		$this->setValue('ref_no',$insurance_info->get('name'));
		$assured_company = Vtiger_Record_Model::getInstanceById($insurance_info->get('cf_3599'), 'Company');
		
		$this->setValue('assured_company', $insurance_info->getDisplayValue('cf_3599') .' - '. $assured_company->get('name'));
		//$this->setValue('expected_from_date',$insurance_info->get('cf_2263'));
		$account_info = Vtiger_Record_Model::getInstanceById($insurance_info->get('cf_3601'), 'Accounts');		
		//$account_details = $account_info->get('accountname');
		$account_details = $account_info->get('cf_2395');
		if ($account_info->get('bill_country')) $account_details .= ', '. $account_info->get('bill_country');
		if ($account_info->get('bill_street')) $account_details .= ', '. $account_info->get('bill_street');
		if ($account_info->get('phone')) $account_details .= ', '. $account_info->get('phone');		
		$this->setValue('account_details',$account_details);
		$this->setValue('bin',$insurance_info->get('cf_3603'));
		
		$this->setValue('mode',str_replace(' |##| ',', ',$insurance_info->get('cf_3619')));
		$oc_country = $insurance_info->get('cf_3605');
		$oc_country .= ', '.$insurance_info->get('cf_3607');
		$this->setValue('voyage_from',$oc_country);
		
		$wis_ref = $insurance_info->get('cf_3621');
		$this->setValue('wis_ref',$wis_ref);
		
		$d_country =$insurance_info->get('cf_3609');
		$d_country .= ', '.$insurance_info->get('cf_3611');
		$this->setValue('voyage_to',$d_country);
		$this->setValue('description_goods',$insurance_info->get('cf_3617'));	
		$this->setValue('invoice_sum',$insurance_info->get('cf_3629'));
		$this->setValue('transportation_cost_invoice',$insurance_info->get('cf_3631'));
		$this->setValue('other_charges',$insurance_info->get('cf_3633'));
		
		$total_sum_insured = $insurance_info->get('cf_3639');
		$total_sum_insured = explode('.',$total_sum_insured);
		
		$this->setValue('total_sum_insured',@$total_sum_insured[0]);	
		$this->setValue('total_sum_insured_tin',@$total_sum_insured[1]);	
		
		
		$inwords_total_sum_insured_words = Numbers_Words::toWords($insurance_info->get('cf_3639'),"ru");
		$this->setValue('inwords_total_sum_insured_words',$inwords_total_sum_insured_words);	
		
		$this->setValue('globalink_selling_rate',$insurance_info->get('cf_3641')); 
		$this->setValue('discounted_selling_rate',$insurance_info->get('cf_3643'));	
		$this->setValue('globalink_premium',$insurance_info->get('cf_3645'));	
		
		$inwords_globalink_premium = Numbers_Words::toWords($insurance_info->get('cf_3645'),"ru");
		$this->setValue('inwords_globalink_premium',$inwords_globalink_premium);	
		
		$this->setValue('period_of_shipment',$insurance_info->get('cf_3613').' - '.$insurance_info->get('cf_3615'));
		$this->setValue('commodity_type',$insurance_info->getDisplayValue('cf_3625'));
		$this->setValue('special_range',$insurance_info->getDisplayValue('cf_3627'));
		$this->setValue('additional_comments',$insurance_info->get('cf_3659'));	
		$this->setValue('special_instructions',$insurance_info->get('cf_3661'));	
		$this->setValue('temp_control',$insurance_info->get('cf_3647'));	
		$this->setValue('temp_control_instruction',$insurance_info->get('cf_3649'));	
		$this->setValue('storage_required',$insurance_info->get('cf_3651'));	
		$this->setValue('place_of_storage',$insurance_info->get('cf_3653'));
		
		$this->setValue('storage_period_from',$insurance_info->get('cf_3655'));	
		$this->setValue('storage_period_to',$insurance_info->get('cf_3657'));	
		
		$this->setValue('from_date',date('d.m.Y', strtotime($insurance_info->get('cf_3613'))));
		$this->setValue('to_date',date('d.m.Y', strtotime($insurance_info->get('cf_3615'))));
				
		
		include('include/mpdf60/mpdf.php');

  		$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10); /*задаем формат, отступы и.т.д.*/
  		$mpdf->charset_in = 'utf8';
		
		$mpdf->list_indent_first_level = 0; 

		$mpdf->SetDefaultFontSize(12);
		$mpdf->list_indent_first_level = 0;
		$mpdf->WriteHTML($this->_documentXML,2); /*формируем pdf*/

		//echo $subject;
		//exit;
		//$subject = 'Ruslan';
		
		$pdf_name = "pdf_docs/insurance_certificate_".$record.".pdf";
		
		
		$mpdf->Output($pdf_name, 'F');
		//header('Location:http://mb.globalink.net/vt60/'.$pdf_name);
		
		header('Location:'.$pdf_name);
		exit;	  
		
	}	

	function print_insurance(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$record = $request->get('record');
		//$tpl = $request->get('tpl');	

		$insurance_id = $record;
		$current_user = Users_Record_Model::getCurrentUserModel();
			
		
		$current_user = Users_Record_Model::getCurrentUserModel();
		$insurance_info = Vtiger_Record_Model::getInstanceById($insurance_id, 'CargoInsurance');
		

		/*
		$qt_owner_user_info = Users_Record_Model::getInstanceById($quote_info->get('assigned_user_id'), 'Users');
		if ($quote_info->get('contact_id') != ''){
		  $contact_info = Vtiger_Record_Model::getInstanceById($quote_info->get('contact_id'), 'Contacts');
		  $attn = $contact_info->get('firstname').' '.$contact_info->get('lastname');
		}
		*/
				
		$document = $this->loadTemplate('printtemplates/CargoInsurance/cargoinsurance.html');		
		$this->setValue('ref_no',$insurance_info->get('name'));
		$assured_company = Vtiger_Record_Model::getInstanceById($insurance_info->get('cf_3599'), 'Company');
		
		$this->setValue('assured_company', $insurance_info->getDisplayValue('cf_3599') .' - '. $assured_company->get('name'));
		//$this->setValue('expected_from_date',$insurance_info->get('cf_2263'));
		$account_info = Vtiger_Record_Model::getInstanceById($insurance_info->get('cf_3601'), 'Accounts');		
		$account_details = $account_info->get('cf_2395');
		$account_details .= ', '.$account_info->get('bill_country');
		$account_details .= ', '.$account_info->get('bill_street');
		$account_details .= ', '.$account_info->get('phone');
		$this->setValue('account_details',$account_details);
		$this->setValue('bin',$insurance_info->get('cf_3603'));
		
		$this->setValue('mode',str_replace(' |##| ',', ',$insurance_info->get('cf_3619')));
		$oc_country = $insurance_info->get('cf_3605');
		$oc_country .= ', '.$insurance_info->get('cf_3607');
		$this->setValue('voyage_from',$oc_country);
		
		$d_country =$insurance_info->get('cf_3609');
		$d_country .= ', '.$insurance_info->get('cf_3611');
		$this->setValue('voyage_to',$d_country);
		$this->setValue('description_goods',$insurance_info->get('cf_3617'));	
		$this->setValue('invoice_sum',$insurance_info->get('cf_3629'));
		$this->setValue('transportation_cost_invoice',$insurance_info->get('cf_3631'));
		$this->setValue('other_charges',$insurance_info->get('cf_3633'));
		$this->setValue('total_sum_insured',$insurance_info->get('cf_3639'));	
		$this->setValue('globalink_selling_rate',$insurance_info->get('cf_3641')); 
		$this->setValue('discounted_selling_rate',$insurance_info->get('cf_3643'));	
		$this->setValue('globalink_premium',$insurance_info->get('cf_3645'));	
		$this->setValue('period_of_shipment',$insurance_info->get('cf_3613').' - '.$insurance_info->get('cf_3615'));
		$this->setValue('commodity_type',$insurance_info->getDisplayValue('cf_3625'));
		$this->setValue('special_range',$insurance_info->getDisplayValue('cf_3627'));
		$this->setValue('additional_comments',$insurance_info->get('cf_3659'));	
		$this->setValue('special_instructions',$insurance_info->get('cf_3661'));	
		$this->setValue('temp_control',$insurance_info->get('cf_3647'));	
		$this->setValue('temp_control_instruction',$insurance_info->get('cf_3649'));	
		$this->setValue('storage_required',$insurance_info->get('cf_3651'));	
		$this->setValue('place_of_storage',$insurance_info->get('cf_3653'));
		
		$this->setValue('storage_period_from',$insurance_info->get('cf_3655'));	
		$this->setValue('storage_period_to',$insurance_info->get('cf_3657'));	
				
		
		include('include/mpdf60/mpdf.php');

  		$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10); /*задаем формат, отступы и.т.д.*/
  		$mpdf->charset_in = 'utf8';
		
		$mpdf->list_indent_first_level = 0; 

		$mpdf->SetDefaultFontSize(12);
		$mpdf->list_indent_first_level = 0;
		$mpdf->WriteHTML($this->_documentXML,2); /*формируем pdf*/

		//echo $subject;
		//exit;
		//$subject = 'Ruslan';
		
		$pdf_name = "pdf_docs/cargo_insurance_".$record.".pdf";
		
		
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
			//$replace = utf8_encode($replace);
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