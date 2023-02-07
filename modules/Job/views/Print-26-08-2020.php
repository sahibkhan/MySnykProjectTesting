<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Job_Print_View extends Vtiger_Print_View {
	
	
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
		$rtype = $request->get('rtype');	
		
		if(!empty($rtype))
		{
			$this->print_qsr($request);
		}
		else
		{
			
			$this->print_coverletter($request);
		}
		
	}
	
	public function print_coverletter($request)
	{		
		//error_reporting(E_ALL);
		$moduleName = $request->getModule();
		$record = $request->get('record');
		
		$current_user = Users_Record_Model::getCurrentUserModel();		
		$job_info_detail = Vtiger_Record_Model::getInstanceById($record, 'Job');
		
				
		$document = $this->loadTemplate('printtemplates/Job/job_cover.html');
		
		$this->setValue('ref_no',$job_info_detail->get('cf_1198'));
		$this->setValue('CreatedTime',date('d/m/Y',strtotime($job_info_detail->get('CreatedTime'))));		
		
		$this->setValue('type',strtoupper($job_info_detail->get('cf_1200')));
		
		$this->setValue('shipper',$job_info_detail->get('cf_1072'));
		$this->setValue('consignee',$job_info_detail->get('cf_1074'));
		
		$this->setValue('origin_agent',$job_info_detail->getDisplayValue('cf_1082'));
		$this->setValue('destination_agent','');
		
		$this->setValue('waybill',$job_info_detail->get('cf_1096'));
		$this->setValue('pieces',$job_info_detail->get('cf_1429'));
		
		$this->setValue('weight',$job_info_detail->get('cf_1084').' '.$job_info_detail->get('cf_1520'));
		$this->setValue('volume',$job_info_detail->get('cf_1086').' '.$job_info_detail->get('cf_1522'));
		
		$this->setValue('commodity',$job_info_detail->get('cf_1518'));
		
		$job_user_info = Users_Record_Model::getInstanceById($job_info_detail->get('assigned_user_id'), 'Users');
		
		$this->setValue('coordinator',htmlentities($job_user_info->get('first_name').' '.$job_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));
		
		$this->setValue('booker',$job_info_detail->getDisplayValue('cf_1441'));
		
		$this->setValue('remarks',$job_info_detail->get('cf_1102'));
		
		include('include/mpdf60/mpdf.php');

  		$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10); /*задаем формат, отступы и.т.д.*/
  		$mpdf->charset_in = 'utf8';
		
		$mpdf->list_indent_first_level = 0; 

		$mpdf->SetDefaultFontSize(14);
		$mpdf->list_indent_first_level = 0;
		$mpdf->WriteHTML($this->_documentXML,2); /*формируем pdf*/

		//echo $subject;
		//exit;
		//$subject = 'Ruslan';
		
		$pdf_name = "pdf_docs/cover_letter_".$record.".pdf";
		
		
		$mpdf->Output($pdf_name, 'F');
		
		
		header('Location:'.$pdf_name);
		exit;	  
		
	}
	
	public function print_qsr($request)
	{
		$moduleName = $request->getModule();
		$record = $request->get('record');	
		$rtype = $request->get('rtype');	
		
		$current_user = Users_Record_Model::getCurrentUserModel();
		$job_details = Vtiger_Record_Model::getInstanceById($record, 'Job');
		

	
		// Basic information
		$owner_user_info = Users_Record_Model::getInstanceById($job_details->get('assigned_user_id'), 'Users');
		$assigned_to = $owner_user_info->get('first_name').' '.$owner_user_info->get('last_name');
		
		// Get Job file details
		$job_ref = $job_details->get('cf_1198');		
		$account_info = Users_Record_Model::getInstanceById($job_details->get('cf_1441'), 'Accounts');
		$customer = $account_info->get('cf_2395');
		
		
		//$subject = $job_details->get('name');
		//$created_date = date('d.m.Y', strtotime($job_details->get('createdtime')));
		
		//$customer_account = $job_details->get('cf_3489');
		
		$shipper = $job_details->get('cf_1072');
		$consignee = $job_details->get('cf_1074');
		$origin = $job_details->get('cf_1508');
		$destination = $job_details->get('cf_1510');
		
		// cf_1072 shipper
 
		$to = '';
		if ($job_details->get('cf_1082') != 0){
		  $account_info2 = Users_Record_Model::getInstanceById($job_details->get('cf_1082'), 'Accounts');
		  $booking_agent = $account_info2->get('cf_2395');
		} else $booking_agent = "";
 
		$fname = '';
		
		if ($rtype == 'origin') $fname = 'QSR_origin'; else if ($rtype == 'destination') $fname = 'QSR_destination'; 
		$document = $this->loadTemplate('printtemplates/Job/'.$fname.'.html');
		
		// Basic information		
		$this->setValue('assigned_to',$assigned_to, ENT_QUOTES, "UTF-8");
		$this->setValue('job_ref',$job_ref, ENT_QUOTES, "UTF-8");
		$this->setValue('customer',$customer, ENT_QUOTES, "UTF-8");

		//Shipping Details
		$this->setValue('shipper',$shipper, ENT_QUOTES, "UTF-8");
		$this->setValue('consignee',$consignee, ENT_QUOTES, "UTF-8");
		$this->setValue('origin',$origin, ENT_QUOTES, "UTF-8");
		$this->setValue('destination',$destination, ENT_QUOTES, "UTF-8");
		$this->setValue('booking_agent',$booking_agent, ENT_QUOTES, "UTF-8");

	
		include('include/mpdf60/mpdf.php');

  		$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10); /*задаем формат, отступы и.т.д.*/
  		$mpdf->charset_in = 'utf8';
		
		//$mpdf->list_indent_first_level = 0; 

		//$mpdf->SetDefaultFontSize(12);
		$mpdf->list_indent_first_level = 0;
		$mpdf->WriteHTML($this->_documentXML,2); /*формируем pdf*/

		//$account_name = html_entity_decode($to);
		//$account = str_replace("/", "", $account_name);
		
		if ($rtype == 'origin') $fname = 'qsro_'.$record; else if ($rtype == 'destination') $fname = 'qsrd_'.$record; 
		$pdf_name = "pdf/".$fname.".pdf";
				
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
