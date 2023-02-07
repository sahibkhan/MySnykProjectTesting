<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class FleetInquiry_Print_View extends Vtiger_Print_View {
	
	
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
		
		$fleetinquiry_id = $record;
		$current_user = Users_Record_Model::getCurrentUserModel();
		$fleetinquiry_info_detail = Vtiger_Record_Model::getInstanceById($fleetinquiry_id, 'FleetInquiry');
		
		//$fleet_expense  = $this->get_job_id_from_fleet($fleet_id);
		//$job_id = $fleet_expense;
		//$sourceModule_job 	= 'Job';	
		//$job_info_detail = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule_job);
		$lang = $request->get('lang');
		if($lang=='eng')
		{
			$document = $this->loadTemplate('printtemplates/fleetinquiry_en.html');
		}
		else{
			$document = $this->loadTemplate('printtemplates/fleetinquiry_ru.html');
		}
				
		$fleetinquiry_user_info = Users_Record_Model::getInstanceById($fleetinquiry_info_detail->get('assigned_user_id'), 'Users');
		
		$this->setValue('coordinator',htmlentities($fleetinquiry_user_info->get('first_name').' '.$fleetinquiry_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));
			
		//$this->setValue('mobile',$owner_fleet_user_info->get('phone_mobile'));
		//$this->setValue('fax',$owner_fleet_user_info->get('phone_fax'));
		//$this->setValue('email',htmlentities($owner_fleet_user_info->get('email1'), ENT_QUOTES, "UTF-8"));
		$this->setValue('job_ref_no', $fleetinquiry_info_detail->get('cf_3297'));
		$this->setValue('fleet_ref_no', $fleetinquiry_info_detail->get('cf_3301'));
		$this->setValue('truck_no', $fleetinquiry_info_detail->get('cf_3299'));	
		$this->setValue('dateadded',date('d.m.Y', strtotime($fleetinquiry_info_detail->get('createdtime'))));				
		$this->setValue('origin_country', $fleetinquiry_info_detail->get('cf_2211'));
		$this->setValue('destination_country', $fleetinquiry_info_detail->get('cf_2213'));
		
		$this->setValue('origin_city', $fleetinquiry_info_detail->get('cf_2215'));
		$this->setValue('destination_city', $fleetinquiry_info_detail->get('cf_2217'));
		
		$this->setValue('pick_address', $fleetinquiry_info_detail->get('cf_2207'));
		$this->setValue('delivery_address', $fleetinquiry_info_detail->get('cf_2209'));
		
		$this->setValue('shipper_contacts', $fleetinquiry_info_detail->get('cf_2145'));
		$this->setValue('consignee_contacts', $fleetinquiry_info_detail->get('cf_2147'));
		
		$expectedfromdate = $fleetinquiry_info_detail->get('cf_2165');
		$expectedfromdate = ($expectedfromdate!='' ? date('d.m.Y', strtotime($expectedfromdate)) : '');
		$this->setValue('expectedfromdate', $expectedfromdate);		
		$expectedtodate = $fleetinquiry_info_detail->get('cf_2167');
		$expectedtodate = ($expectedtodate!='' ? date('d.m.Y', strtotime($expectedtodate)) : '');
		$this->setValue('expectedtodate', $expectedtodate);
		
		$eta = $fleetinquiry_info_detail->get('cf_2171');
		$eta = ($eta!='' ? date('d.m.Y', strtotime($eta)) : '');
		$this->setValue('eta', $eta);
		
		$etd = $fleetinquiry_info_detail->get('cf_2169');
		$etd = ($etd!='' ? date('d.m.Y', strtotime($etd)) : '');
		$this->setValue('etd', $etd);				
		
				
			
		$this->setValue('loading_time', $fleetinquiry_info_detail->get('cf_2133'));
		$this->setValue('svh_address', $fleetinquiry_info_detail->get('cf_2161'));
		$this->setValue('comodity', $fleetinquiry_info_detail->get('cf_2135'));		
		$this->setValue('no_of_items', $fleetinquiry_info_detail->get('cf_2137'));
		
		$this->setValue('dimensions_lwh', $fleetinquiry_info_detail->get('cf_2139'));
		$this->setValue('weight', $fleetinquiry_info_detail->get('cf_2141'));
		
		$this->setValue('stackable',$fleetinquiry_info_detail->get('cf_2149'));
		$this->setValue('max_levels', $fleetinquiry_info_detail->get('cf_2151'));
		
		$this->setValue('dgr', $fleetinquiry_info_detail->get('cf_2153'));
		$this->setValue('class', $fleetinquiry_info_detail->get('cf_2155'));
		
		$this->setValue('odc', $fleetinquiry_info_detail->get('cf_2157'));
		$this->setValue('temp_regime', $fleetinquiry_info_detail->get('cf_2159'));
		
		$this->setValue('special_instruction', $fleetinquiry_info_detail->get('cf_2143'));
		
		
			
			
		//$filename = 'fleet_expense.txt';
		//$this->save('fleet_expense.txt');	
		
		include('include/mpdf60/mpdf.php');

  		$mpdf = new mPDF('utf-8', 'A4-L', '10', '', 10, 10, 7, 7, 10, 10); /*задаем формат, отступы и.т.д.*/
  		$mpdf->charset_in = 'utf8';
		
	    //$stylesheet = file_get_contents('include/Quote/pdf_styles.css'); /*подключаем css*/
  		//$mpdf->WriteHTML($stylesheet, 1);
		
		$mpdf->list_indent_first_level = 0; 

		//$mpdf->SetDefaultFontSize(12);
		
		$mpdf->WriteHTML($this->_documentXML,2); /*формируем pdf*/
		
			ob_clean();	
		$pdf_name = 'pdf_docs/fleet_inquiry.pdf';
		
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