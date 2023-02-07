<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class InvoiceGLK_Print_View extends Vtiger_Print_View {
	
	
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
		$tpl = $request->get('tpl');	
		
		$current_user = Users_Record_Model::getCurrentUserModel();

		$current_user = Users_Record_Model::getCurrentUserModel();
		$appendix_info = Vtiger_Record_Model::getInstanceById($record, 'InvoiceGLK');
		
		//$qt_owner_user_info = Users_Record_Model::getInstanceById($quote_info->get('assigned_user_id'), 'Users');
 
 
		/*
		$to = '';
		// Field agent in QT
		if ($quote_info->get('cf_1827') != 0){
		  $account_info2 = Users_Record_Model::getInstanceById($quote_info->get('cf_1827'), 'Accounts');
		  $Agent = $account_info2->get('cf_2395');
		  $to = $Agent;
		} else $Agent = "";
		
		// Field account in QT
		$account_id = $quote_info->get('account_id');
		if ($account_id != 0){
	 	  $account_info = Users_Record_Model::getInstanceById($quote_info->get('account_id'), 'Accounts');		
		  $account = $account_info->get('cf_2395');
		  if (empty($to)){
		    $to = $account;
		  }
		}
		else{
			$account ='';
		}
		*/



	$document = $this->loadTemplate('printtemplates/Appendix/appendix.html');
	$appendix_info = $appendix_info->get('cf_3425');	
	$s = html_entity_decode($appendix_info);

	
		// Custom fields for template
	$this->setValue('appendix_info',$s, ENT_QUOTES, "UTF-8");

 
	
					// Signature
	/*				
	$user_city = $qt_owner_user_info->get('address_city');
    $adb = PearDatabase::getInstance();							
    $sql_branch = $adb->pquery("SELECT b.tel as tel FROM `vtiger_branch_details` as b 
									where b.city = '".$user_city."'", array());
    $branch_tel = $adb->query_result($sql_branch, 0, 'tel'); 
					
					
	$creator_name = $qt_owner_user_info->get('first_name').' '.$qt_owner_user_info->get('last_name');
	$creator_title =  $qt_owner_user_info->get('title');
	$email = $qt_owner_user_info->get('email1');
	*/
 
	
		//$filename = 'fleet_expense.txt';
		//$this->save('fleet_expense.txt');	
		
		include('include/mpdf60/mpdf.php');

  		$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10); /*задаем формат, отступы и.т.д.*/
  		$mpdf->charset_in = 'utf8';
		
		//$mpdf->list_indent_first_level = 0; 

		//$mpdf->SetDefaultFontSize(12);
		$mpdf->list_indent_first_level = 0;
		$mpdf->WriteHTML($this->_documentXML,2); /*формируем pdf*/

		
		$pdf_name = "pdf_docs/appendix_".$record.".pdf";				
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
