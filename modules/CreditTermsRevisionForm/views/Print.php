<?php
/*+***********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************/
class CreditTermsRevisionForm_Print_View extends Vtiger_Print_View {
	
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
		$ctrf_info = Vtiger_Record_Model::getInstanceById($record, 'CreditTermsRevisionForm');
		$qt_owner_user_info = Users_Record_Model::getInstanceById($ctrf_info->get('assigned_user_id') , 'Users');
		/*if ($ctrf_info->get('contact_id') != ''){
		$contact_info = Vtiger_Record_Model::getInstanceById($ctrf_info->get('contact_id'), 'Contacts');
		$attn = $contact_info->get('firstname').' '.$contact_info->get('lastname');
		}*/
		$document = $this->loadTemplate('printtemplates/CreditTermsRevisionForm/pdf.html');
		$adb = PearDatabase::getInstance();

		$prepared_by = $ctrf_info->get('cf_4907');//Users_Record_Model::getInstanceById($ctrf_info->get('cf_4907'), 'Users');
		$approved_by = $ctrf_info->get('cf_4909');//Users_Record_Model::getInstanceById($ctrf_info->get('cf_4909'), 'Users');
		$endorsed_by = $ctrf_info->get('cf_4911');//Users_Record_Model::getInstanceById($ctrf_info->get('cf_4911'), 'Users');

		//UI TYPE for vtiger_field cf_4907 597 OLD

		// Credit Approval Form Details
		if ($branch_name != "") {
			$this->setValue('branch_name', $ctrf_info->get('cf_4815'), ENT_QUOTES, "UTF-8");
		} else {
			$this->setValue('branch_name', '', ENT_QUOTES, "UTF-8");
		}
		$this->setValue('company_name', $ctrf_info->get('cf_4857'), ENT_QUOTES, "UTF-8");
		$this->setValue('customer_name', $ctrf_info->get('cf_4859'), ENT_QUOTES, "UTF-8");
		$this->setValue('customer_code', $ctrf_info->get('cf_4861'), ENT_QUOTES, "UTF-8");
		$this->setValue('type_of_glk_services_required', $ctrf_info->get('cf_4863'), ENT_QUOTES, "UTF-8");
		
		$this->setValue('credit_limit_existing', $ctrf_info->get('cf_4869'), ENT_QUOTES, "UTF-8");
		$this->setValue('payment_terms_existing', $ctrf_info->get('cf_4871'), ENT_QUOTES, "UTF-8");
		$this->setValue('credit_limit_revise_to', $ctrf_info->get('cf_4873'), ENT_QUOTES, "UTF-8");
		$this->setValue('payment_terms_revise_to', $ctrf_info->get('cf_4875'), ENT_QUOTES, "UTF-8");

		$this->setValue('mth1_currency', $ctrf_info->get('cf_4877'), ENT_QUOTES, "UTF-8");
		$this->setValue('mth2_currency', $ctrf_info->get('cf_4879'), ENT_QUOTES, "UTF-8");
		$this->setValue('mth3_currency', $ctrf_info->get('cf_4881'), ENT_QUOTES, "UTF-8");
		$this->setValue('mth4_currency', $ctrf_info->get('cf_4883'), ENT_QUOTES, "UTF-8");
		$this->setValue('mth5_currency', $ctrf_info->get('cf_4885'), ENT_QUOTES, "UTF-8");
		$this->setValue('mth6_currency', $ctrf_info->get('cf_4887'), ENT_QUOTES, "UTF-8");
		$this->setValue('total_currency', $ctrf_info->get('cf_4889'), ENT_QUOTES, "UTF-8");

		$this->setValue('15_days', $ctrf_info->get('cf_4891'), ENT_QUOTES, "UTF-8");
		$this->setValue('30_days', $ctrf_info->get('cf_4893'), ENT_QUOTES, "UTF-8");
		$this->setValue('30_60_days', $ctrf_info->get('cf_4895'), ENT_QUOTES, "UTF-8");
		$this->setValue('over_60_days', $ctrf_info->get('cf_4897'), ENT_QUOTES, "UTF-8");
		$this->setValue('15_days_currency', $ctrf_info->get('cf_4899'), ENT_QUOTES, "UTF-8");
		$this->setValue('30_days_currency', $ctrf_info->get('cf_4901'), ENT_QUOTES, "UTF-8");
		$this->setValue('30_60_days_currency', $ctrf_info->get('cf_4903'), ENT_QUOTES, "UTF-8");
		$this->setValue('over_60_days_currency', $ctrf_info->get('cf_4905'), ENT_QUOTES, "UTF-8");
		
		$this->setValue('current_outstanding_balance', $ctrf_info->get('cf_4865'), ENT_QUOTES, "UTF-8");

		$this->setValue('other_remarks_and_attachments', $ctrf_info->get('cf_4867'), ENT_QUOTES, "UTF-8");
		$this->setValue('prepared_by', $prepared_by/*$prepared_by->get('first_name') . ' ' . $prepared_by->get('last_name')*/, ENT_QUOTES, "UTF-8");
		$this->setValue('approved_by', $approved_by/*$approved_by->get('first_name') . ' ' . $approved_by->get('last_name')*/, ENT_QUOTES, "UTF-8");
		$this->setValue('endorsed_by', $endorsed_by/*$endorsed_by->get('first_name') . ' ' . $endorsed_by->get('last_name')*/, ENT_QUOTES, "UTF-8");

		include ('include/mpdf60/mpdf.php');

		$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10); /*Ð·Ð°Ð´Ð°ÐµÐ¼ Ñ„Ð¾Ñ€Ð¼Ð°Ñ‚, Ð¾Ñ‚ÑÑ‚ÑƒÐ¿Ñ‹ Ð¸.Ñ‚.Ð´.*/
		$mpdf->charset_in = 'utf8';

		// $mpdf->list_indent_first_level = 0;
		// $mpdf->SetDefaultFontSize(12);

		$mpdf->list_indent_first_level = 0;
		$mpdf->WriteHTML($this->_documentXML, 2); /*Ñ„Ð¾Ñ€Ð¼Ð¸Ñ€ÑƒÐµÐ¼ pdf*/
		$pdf_name = "pdf_docs/ctrf_" . $record . ".pdf";
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