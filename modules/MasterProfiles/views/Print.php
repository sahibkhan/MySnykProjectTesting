<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class MasterProfiles_Print_View extends Vtiger_Print_View {
	
	
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
		$current_user = Users_Record_Model::getCurrentUserModel();
		$mp_info = Vtiger_Record_Model::getInstanceById($record, 'MasterProfiles');
		
		$qt_owner_user_info = Users_Record_Model::getInstanceById($mp_info->get('assigned_user_id'), 'Users');
		
		/*if ($mp_info->get('contact_id') != ''){
		  $contact_info = Vtiger_Record_Model::getInstanceById($mp_info->get('contact_id'), 'Contacts');
		  $attn = $contact_info->get('firstname').' '.$contact_info->get('lastname');
		}*/		
		
		$document = $this->loadTemplate('printtemplates/MasterProfiles/pdf.html');
		$adb = PearDatabase::getInstance();
		
		// Header
		$station = $qt_owner_user_info->get('address_city');
		$this->setValue('station',$station, ENT_QUOTES, "UTF-8");
		$this->setValue('from',htmlentities($qt_owner_user_info->get('first_name').' '.$qt_owner_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));
		$this->setValue('date',date('d.m.Y', strtotime($mp_info->get('CreatedTime'))));
		
		$this->setValue('ref','GL/Master Profile - '.$record);
		
		$persons = $mp_info->get('cf_3209');
	  if ($persons != ''){
	   $buffer = '';
	   $person_count = 0; 
	   $mas_persons = array();  
								// Search count of person 						
		for($i = 0; $i <= strlen($persons); $i++){
		  if ($persons[$i] == '|'){
			$person_count ++;
			$mas_persons[$person_count] = $buffer;
			$buffer = ''; 
		  } else $buffer = $buffer . $persons[$i];
		}	
								// Sending each of person an email
		$person = '';	
		$sent_name = '';	
		for($u_i=1; $u_i<=$person_count; $u_i++){
		  $person = trim($mas_persons[$u_i]);
		  
		  $sql_user = "SELECT * FROM `vtiger_users` where `user_name` = '$person'";
	      $r_consol = $adb->pquery($sql_user);		  
		  $sent_name .= $adb->query_result($r_consol,0,"first_name").' ';
		  $sent_name .= $adb->query_result($r_consol,0,"last_name").', ';
		  
		}

	  }
  
  
		
		
		$this->setValue('recipients',$sent_name);		
		
		// Master Profile Details
		$this->setValue('legal_name',$mp_info->get('cf_3181'), ENT_QUOTES, "UTF-8");
		$this->setValue('loopup_name',$mp_info->get('name'), ENT_QUOTES, "UTF-8");
		$this->setValue('business_type',$mp_info->get('cf_3183'), ENT_QUOTES, "UTF-8");
		$this->setValue('p_email',$mp_info->get('cf_3185'), ENT_QUOTES, "UTF-8");
		$this->setValue('country',$mp_info->get('cf_3187'), ENT_QUOTES, "UTF-8");
		$this->setValue('website',$mp_info->get('cf_3191'), ENT_QUOTES, "UTF-8");

		$this->setValue('global_account',$mp_info->get('cf_3193'), ENT_QUOTES, "UTF-8");
		$this->setValue('city',$mp_info->get('cf_3189'), ENT_QUOTES, "UTF-8");
		$this->setValue('industry',$mp_info->get('cf_3195'), ENT_QUOTES, "UTF-8");
									
		
		//  Vendor Registration
		if ($mp_info->get('cf_3197')) $vendor_r = 'Yes'; else $vendor_r = 'No';
		$this->setValue('vendor_r',$vendor_r, ENT_QUOTES, "UTF-8");
		$this->setValue('vendor_registration_v',$mp_info->get('cf_3199'), ENT_QUOTES, "UTF-8");
		$this->setValue('vendor_n',$mp_info->get('cf_3201'), ENT_QUOTES, "UTF-8");
		
		
  
		$descriptions = '';
		  $q = "SELECT vtiger_crmentity.description,vtiger_accountscf.cf_2395
				FROM `vtiger_crmentity` 
				INNER JOIN vtiger_accountscf ON vtiger_accountscf.accountid = vtiger_crmentity.crmid
				WHERE vtiger_crmentity.deleted = 0 and vtiger_accountscf.cf_3207 = ?";
	      $r_consol = $adb->pquery($q, array($record));
		  $numRows = $adb->num_rows($r_consol);
		  
		  $c_desc = ''; 
		  for($i=0; $i < $numRows;$i++){
		    $descriptions .= '<br/>'.$adb->query_result($r_consol,$i,"cf_2395").'<br>';
			$descriptions .= $adb->query_result($r_consol,$i,"description").'<br/><br/>';
				
		  }
		  

		$this->setValue('consolidated_descriptions',$descriptions, ENT_QUOTES, "UTF-8");
				
		
		//$this->setValue('to',htmlentities($to, ENT_QUOTES, "UTF-8"));
		//$this->setValue('from',htmlentities($qt_owner_user_info->get('first_name').' '.$qt_owner_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));
		//$this->setValue('attn',htmlentities($attn, ENT_QUOTES, "UTF-8"));
		//$user_city = trim($r_users['address_city']);
		
		/*
		$adb = PearDatabase::getInstance();				
		$sql_branch_tel = $adb->pquery("SELECT b.tel as tel FROM `vtiger_branch_details` as b
									 where b.city = '".$qt_owner_user_info->get('address_city')."'", array());
	    $branch_tel = $adb->query_result($sql_branch_tel, 0, 'tel');
		$this->setValue('tel',$branch_tel);
		*/
		
		//$subject = htmlentities($mp_info->get('subject'), ENT_QUOTES, "UTF-8");		
		
		//if ($email == '') $email = $qt_owner_user_info->get('email1'); 
		//$this->setValue('email',$email, ENT_QUOTES, "UTF-8");
		//$this->setValue('ref',htmlentities('GL/QT - '.$record, ENT_QUOTES, "UTF-8"));
		//$this->setValue('dep',htmlentities($qt_owner_user_info->get('department'), ENT_QUOTES, "UTF-8"));		
		//$this->setValue('subject',htmlentities($mp_info->get('subject'), ENT_QUOTES, "UTF-8"));
		
		
					// General
		//$shipper = $mp_info->get('cf_777');

		
	
		//$filename = 'fleet_expense.txt';
		//$this->save('fleet_expense.txt');	
		
		include('include/mpdf60/mpdf.php');

  		$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10); /*задаем формат, отступы и.т.д.*/
  		$mpdf->charset_in = 'utf8';
		
		//$mpdf->list_indent_first_level = 0; 

		//$mpdf->SetDefaultFontSize(12);
		$mpdf->list_indent_first_level = 0;
		$mpdf->WriteHTML($this->_documentXML,2); /*формируем pdf*/

		
		$pdf_name = "pdf_docs/mp_".$record.".pdf";
		
		
		$mpdf->Output($pdf_name, 'F');
		//header('Location:http://mb.globalink.net/vt60/'.$pdf_name);
		
		header('Location:'.$pdf_name);
		exit;
		  
		  
		  /*
		if ($type == 1) {
		  header('Location:'.$pdf_name);
		  exit;
		} else
		if ($type == 2) {
		  header('Location:'.$pdf_name); 
		  exit;
		}
			*/
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
