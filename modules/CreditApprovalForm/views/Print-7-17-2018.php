<?php
/*+***********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************/
class CreditApprovalForm_Print_View extends Vtiger_Print_View {
	
	/**
	 * Temporary Filename
	 *
	 * @var string
	 */
	private $_tempFileName;
	function __construct()
	{
		parent::__construct();
		ob_start();
	}

	function checkPermission (Vtiger_Request $request)	{
		return true;
	}

	function process (Vtiger_Request $request)	{
		$moduleName = $request->getModule();
		$record = $request->get('record');
		$current_user = Users_Record_Model::getCurrentUserModel();
		$caf_info = Vtiger_Record_Model::getInstanceById($record, 'CreditApprovalForm');
		$qt_owner_user_info = Users_Record_Model::getInstanceById($caf_info->get('assigned_user_id') , 'Users');
		/*if ($caf_info->get('contact_id') != ''){
		$contact_info = Vtiger_Record_Model::getInstanceById($caf_info->get('contact_id'), 'Contacts');
		$attn = $contact_info->get('firstname').' '.$contact_info->get('lastname');
		}*/
		$document = $this->loadTemplate('printtemplates/CreditApprovalForm/pdf.html');
		$adb = PearDatabase::getInstance();
	
		// getting branch / department name
		// $q = "SELECT name
		// 			FROM vtiger_location
		// 			WHERE locationid = ?";


		// $branch_id = $caf_info->get('cf_4819');
		// $branch = $adb->pquery($q, array($branch_id));
		// $branch_name = $adb->query_result($branch, $i, "name")); 

		$prepared_by = Users_Record_Model::getInstanceById($caf_info->get('cf_4851'), 'Users');
		$approved_by = Users_Record_Model::getInstanceById($caf_info->get('cf_4853'), 'Users');
		$endorsed_by = Users_Record_Model::getInstanceById($caf_info->get('cf_4855'), 'Users');

		// Credit Approval Form Details
		if ($branch_name != "") {
			$this->setValue('branch_name', $caf_info->get('cf_4815'), ENT_QUOTES, "UTF-8");
		} else {
			$this->setValue('branch_name', '', ENT_QUOTES, "UTF-8");
		}
		
		$this->setValue('company_name', $caf_info->get('cf_4815'), ENT_QUOTES, "UTF-8");
		$this->setValue('customer_name', $caf_info->get('cf_4817'), ENT_QUOTES, "UTF-8");
		$this->setValue('branch_no', $caf_info->get('cf_4819'), ENT_QUOTES, "UTF-8");
		$this->setValue('customer_code', $caf_info->get('cf_4821'), ENT_QUOTES, "UTF-8");
		$this->setValue('company_type', $caf_info->get('cf_4823'), ENT_QUOTES, "UTF-8");
		$this->setValue('date_of_incorporation', $caf_info->get('cf_4825'), ENT_QUOTES, "UTF-8");
		$this->setValue('place_of_operation', $caf_info->get('cf_4827'), ENT_QUOTES, "UTF-8");
		$this->setValue('number_of_staff', $caf_info->get('cf_4829'), ENT_QUOTES, "UTF-8");
		$this->setValue('authorized_or_paid_in_capital', $caf_info->get('cf_4831'), ENT_QUOTES, "UTF-8");
		$this->setValue('relationship_with_company', $caf_info->get('cf_4833'), ENT_QUOTES, "UTF-8");
		$this->setValue('local_address_contact', $caf_info->get('cf_4835'), ENT_QUOTES, "UTF-8");
		$this->setValue('general_description', $caf_info->get('cf_4837'), ENT_QUOTES, "UTF-8");
		$this->setValue('type_of_glk_services_required', $caf_info->get('cf_4839'), ENT_QUOTES, "UTF-8");
		$this->setValue('credit_limit', $caf_info->get('cf_4847'), ENT_QUOTES, "UTF-8");
		$this->setValue('payment_terms', $caf_info->get('cf_4849'), ENT_QUOTES, "UTF-8");
		$this->setValue('other_remarks_and_attachments', $caf_info->get('cf_4841'), ENT_QUOTES, "UTF-8");
		$this->setValue('prepared_by', $prepared_by->get('first_name') . ' ' . $prepared_by->get('last_name'), ENT_QUOTES, "UTF-8");
		$this->setValue('approved_by', $approved_by->get('first_name') . ' ' . $approved_by->get('last_name'), ENT_QUOTES, "UTF-8");
		$this->setValue('endorsed_by', $endorsed_by->get('first_name') . ' ' . $endorsed_by->get('last_name'), ENT_QUOTES, "UTF-8");

		// decided to not to use these fields
		// $this->setValue('credit_limit_required', $caf_info->get('cf_4843'), ENT_QUOTES, "UTF-8");
		// $this->setValue('credit_terms_days', $caf_info->get('cf_4845'), ENT_QUOTES, "UTF-8");
		

		include ('include/mpdf60/mpdf.php');

		$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10); /*Ð·Ð°Ð´Ð°ÐµÐ¼ Ñ„Ð¾Ñ€Ð¼Ð°Ñ‚, Ð¾Ñ‚ÑÑ‚ÑƒÐ¿Ñ‹ Ð¸.Ñ‚.Ð´.*/
		$mpdf->charset_in = 'utf8';

		// $mpdf->list_indent_first_level = 0;
		// $mpdf->SetDefaultFontSize(12);

		$mpdf->list_indent_first_level = 0;
		$mpdf->WriteHTML($this->_documentXML, 2); /*Ñ„Ð¾Ñ€Ð¼Ð¸Ñ€ÑƒÐµÐ¼ pdf*/
		$pdf_name = "pdf_docs/caf_" . $record . ".pdf";
		$mpdf->Output($pdf_name, 'F');

		// header('Location:http://mb.globalink.net/vt60/'.$pdf_name);

		header('Location:' . $pdf_name);
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

	public function template ($strFilename) {
		$path = dirname($strFilename);

		// $this->_tempFileName = $path.time().'.docx';
		// $this->_tempFileName = $path.'/'.time().'.txt';

		$this->_tempFileName = $strFilename;

		// copy($strFilename, $this->_tempFileName); // Copy the source File to the temp File

		$this->_documentXML = file_get_contents($this->_tempFileName);
	}

	/**
	 * Set a Template value
	 *
	 * @param mixed $search
	 * @param mixed $replace
	 */
	public function setValue ($search, $replace)	{
		if (substr($search, 0, 2) !== '${' && substr($search, -1) !== '}') {
			$search = '${' . $search . '}';
		}

		// $replace =  htmlentities($replace, ENT_QUOTES, "UTF-8");

		if (!is_array($replace)) {

			// $replace = utf8_encode($replace);

			$replace = iconv('utf-8', 'utf-8', $replace);
		}

		$this->_documentXML = str_replace($search, $replace, $this->_documentXML);
	}

	/**
	 * Save Template
	 *
	 * @param string $strFilename
	 */
	public function save ($strFilename) {
		if (file_exists($strFilename)) {
			unlink($strFilename);
		}

		// $this->_objZip->extractTo('fleet.txt', $this->_documentXML);

		file_put_contents($this->_tempFileName, $this->_documentXML);

		// Close zip file

		/* if($this->_objZip->close() === false) {
		throw new Exception('Could not close zip file.');
		}*/
		rename($this->_tempFileName, $strFilename);
	}

	public function loadTemplate ($strFilename) {
		if (file_exists($strFilename)) {
			$template = $this->template($strFilename);
			return $template;
		} else {
			trigger_error('Template file ' . $strFilename . ' not found.', E_ERROR);
		}
	}
}