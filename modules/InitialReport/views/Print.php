<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
//error_reporting(E_ALL);
//ini_set('display_errors', 'on');
class InitialReport_Print_View extends Vtiger_Print_View {
	
	
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
		
		$initialReport_id = $record;
		$current_user = Users_Record_Model::getCurrentUserModel();
		$initialReport_info_detail = Vtiger_Record_Model::getInstanceById($initialReport_id, 'InitialReport');
		
		//echo "<pre>";
		//print_r($initialReport_info_detail);
		//exit;
		//$fleet_expense  = $this->get_job_id_from_fleet($fleet_id);
		//$job_id = $fleet_expense;
		//$sourceModule_job 	= 'Job';	
		//$job_info_detail = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule_job);
		//$lang = $request->get('lang');
		$document = $this->loadTemplate('printtemplates/initialReport_en.html');
		
		$checkedGeneric = $initialReport_info_detail->get('cf_4117');
		
		if($checkedGeneric == "Incident"){$incident = "checked";}
		if($checkedGeneric == "Hazard"){$hazard = "checked";}
		
			
		
		$this->setValue('generic',htmlentities($initialReport_info_detail->get('cf_4117'), ENT_QUOTES, "UTF-8"));
		$this->setValue('incident',htmlentities($incident, ENT_QUOTES, "UTF-8"));
		$this->setValue('hazard',htmlentities($hazard, ENT_QUOTES, "UTF-8"));
		$this->setValue('site',htmlentities($initialReport_info_detail->get('name'), ENT_QUOTES, "UTF-8"));
		$this->setValue('country',htmlentities($initialReport_info_detail->getDisplayValue('cf_4119'), ENT_QUOTES, "UTF-8"));
		$this->setValue('department',htmlentities($initialReport_info_detail->getDisplayValue('cf_4125'), ENT_QUOTES, "UTF-8"));
		$this->setValue('incident_date',date('d.m.Y', strtotime($initialReport_info_detail->get('cf_4123'))));
		$this->setValue('incident_time',htmlentities($initialReport_info_detail->get('cf_4129'), ENT_QUOTES, "UTF-8"));	
		$this->setValue('reported_by', htmlentities($initialReport_info_detail->get('cf_4127'), ENT_QUOTES, "UTF-8"));
		$this->setValue('telphone_no', htmlentities($initialReport_info_detail->get('cf_4133'), ENT_QUOTES, "UTF-8"));	
		$this->setValue('email', htmlentities($initialReport_info_detail->get('cf_4131'), ENT_QUOTES, "UTF-8"));
		$this->setValue('detailed_location', htmlentities($initialReport_info_detail->get('cf_4121'), ENT_QUOTES, "UTF-8"));
		$this->setValue('brief_description', htmlentities($initialReport_info_detail->get('cf_4135'), ENT_QUOTES, "UTF-8"));
		$this->setValue('immediate_action_taken', htmlentities($initialReport_info_detail->get('cf_4137'), ENT_QUOTES, "UTF-8"));
		$this->setValue('equipment_involved', htmlentities($initialReport_info_detail->get('cf_4139'), ENT_QUOTES, "UTF-8"));
		$this->setValue('other_actions', htmlentities($initialReport_info_detail->get('cf_4141'), ENT_QUOTES, "UTF-8"));
		$this->setValue('any_comments',htmlentities($initialReport_info_detail->get('cf_4143'), ENT_QUOTES, "UTF-8"));
		$this->setValue('department_manager',htmlentities($initialReport_info_detail->get('cf_4147'), ENT_QUOTES, "UTF-8"));		
		
			
		//$filename = 'fleet_expense.txt';
		//$this->save('fleet_expense.txt');	
		
		include('include/mpdf60/mpdf.php');

  		$mpdf = new mPDF('utf-8', 'A4-L', '10', '', 10, 10, 7, 7, 10, 10); /*задаем формат, отступы и.т.д.*/
  		$mpdf->charset_in = 'utf8';
		
	    //$stylesheet = file_get_contents('include/Quote/pdf_styles.css'); /*подключаем css*/
  		//$mpdf->WriteHTML($stylesheet, 1);
		
		$mpdf->list_indent_first_level = 0; 

		//$mpdf->SetDefaultFontSize(12);

		$mpdf->list_indent_first_level = 0;
		$mpdf->SetHTMLHeader('<table width="100%" cellpadding="0" cellspacing="0">
		<tr><td align="right" style="font-size:9;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">Initial Report, GLOBALINK</td></tr>
		<tr><td align="right"><img src="printtemplates/glklogo.jpg"/ width="160" height="30"></td></tr></table>');
				$mpdf->SetHTMLFooter('<table width="100%" cellpadding="0" cellspacing="0">
		<tr><td width="40%" align="left" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">Printed: '.date('d.m.Y; H:i').' by '.$current_user->get('user_name').'</td>
		<td width="20%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">Page {PAGENO} of {nbpg}</td>
		<td width="40%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">&nbsp;</td>
		</table>');
		$stylesheet = file_get_contents('include/mpdf60/examples/mpdfstyletables.css');
		$mpdf->WriteHTML($stylesheet,1);	// The parameter 1 tells that this is css/style only and no body/html/text
		
		$mpdf->WriteHTML($this->_documentXML); /*формируем pdf*/
		
				
		$pdf_name = 'pdf_docs/InitialReport_'.$record.'.pdf';
		
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