<?php
/*+***********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************/
class FurnitureRequest_Print_View extends Vtiger_Print_View {
	
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
		$furniturerequest_info = Vtiger_Record_Model::getInstanceById($record, 'FurnitureRequest');
		$qt_owner_user_info = Users_Record_Model::getInstanceById($furniturerequest_info->get('assigned_user_id') , 'Users');
		/*if ($furniturerequest_info->get('contact_id') != ''){
		$contact_info = Vtiger_Record_Model::getInstanceById($furniturerequest_info->get('contact_id'), 'Contacts');
		$attn = $contact_info->get('firstname').' '.$contact_info->get('lastname');
		}*/

		// echo "<pre>";
		// print_r($furniturerequest_info);
		// echo "</pre>";
		// exit;
		$document = $this->loadTemplate('printtemplates/FurnitureRequest/pdf.html');

		$created_time = date("Y-m-d", strtotime($furniturerequest_info->get('createdtime')));
		$modified_time = date("Y-m-d", strtotime($furniturerequest_info->get('modifiedtime')));

		$this->setValue('office', $furniturerequest_info->getDisplayValue('cf_4683'), ENT_QUOTES, "UTF-8");
		$this->setValue('equipment_type', $furniturerequest_info->getDisplayValue('cf_4685'), ENT_QUOTES, "UTF-8");
		$this->setValue('item_name', $furniturerequest_info->getDisplayValue('name'), ENT_QUOTES, "UTF-8");
		$this->setValue('equipment_description', $furniturerequest_info->getDisplayValue('cf_4687'), ENT_QUOTES, "UTF-8");
		$this->setValue('price_usd', $furniturerequest_info->getDisplayValue('cf_4689'), ENT_QUOTES, "UTF-8");
		$this->setValue('count', $furniturerequest_info->getDisplayValue('cf_4691'), ENT_QUOTES, "UTF-8");
		$this->setValue('total_amount_usd', $furniturerequest_info->getDisplayValue('cf_4693'), ENT_QUOTES, "UTF-8");
		$this->setValue('purpose', $furniturerequest_info->getDisplayValue('cf_4695'), ENT_QUOTES, "UTF-8");
		$this->setValue('department_only_for_ala', $furniturerequest_info->getDisplayValue('cf_4697'), ENT_QUOTES, "UTF-8");
		$this->setValue('branch_all_except_the_almaty', $furniturerequest_info->getDisplayValue('cf_4795'), ENT_QUOTES, "UTF-8");
		$this->setValue('send_to_ceo', $furniturerequest_info->getDisplayValue('cf_4701'), ENT_QUOTES, "UTF-8");
		$this->setValue('creator', $qt_owner_user_info->getDisplayValue('first_name') . ' ' . $qt_owner_user_info->getDisplayValue('last_name'), ENT_QUOTES, "UTF-8");
		$this->setValue('serial_id', $furniturerequest_info->getDisplayValue('cf_4703'), ENT_QUOTES, "UTF-8");
		$this->setValue('brief_comment', $furniturerequest_info->getDisplayValue('cf_4699'), ENT_QUOTES, "UTF-8");
		$this->setValue('modified_time', $modified_time, ENT_QUOTES, "UTF-8");
		$this->setValue('created_time', $created_time, ENT_QUOTES, "UTF-8");
		$this->setValue('head_approval', $furniturerequest_info->getDisplayValue('cf_4705'), ENT_QUOTES, "UTF-8");
		$this->setValue('head_approval_date', $furniturerequest_info->getDisplayValue('cf_4707'), ENT_QUOTES, "UTF-8");
		$this->setValue('cfo_approval', $furniturerequest_info->getDisplayValue('cf_4709'), ENT_QUOTES, "UTF-8");
		$this->setValue('cfo_approval_date', $furniturerequest_info->getDisplayValue('cf_4711'), ENT_QUOTES, "UTF-8");
		$this->setValue('ceo_approval', $furniturerequest_info->getDisplayValue('cf_4713'), ENT_QUOTES, "UTF-8");
		$this->setValue('ceo_approval_date', $furniturerequest_info->getDisplayValue('cf_4715'), ENT_QUOTES, "UTF-8");

		include ('include/mpdf60/mpdf.php');

		$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10); /*Ð·Ð°Ð´Ð°ÐµÐ¼ Ñ„Ð¾Ñ€Ð¼Ð°Ñ‚, Ð¾Ñ‚ÑÑ‚ÑƒÐ¿Ñ‹ Ð¸.Ñ‚.Ð´.*/
		$mpdf->charset_in = 'utf8';

		// $mpdf->list_indent_first_level = 0;
		// $mpdf->SetDefaultFontSize(12);

		$mpdf->list_indent_first_level = 0;
		$mpdf->SetHTMLHeader('
			<table width="100%" cellpadding="0" cellspacing="0">
				<tr>
					<td align="right" style="font-size:9;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
						Furniture Request, GLOBALINK
					</td>
				</tr>
				<tr>
					<td align="right">
						<img src="printtemplates/glklogo.jpg"/ width="160" height="30">
					</td>
				</tr>
			</table>');
		
		$mpdf->SetHTMLFooter('
			<table width="80%" align="center" cellpadding="0" cellspacing="0">
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
		$mpdf->WriteHTML($stylesheet,1);	// The parameter 1 tells that this is css/style only and no body/html/text
		
		$mpdf->WriteHTML($this->_documentXML, 2); /*Ñ„Ð¾Ñ€Ð¼Ð¸Ñ€ÑƒÐµÐ¼ pdf*/
		$pdf_name = "pdf_docs/furniturerequestform_" . $record . ".pdf";
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