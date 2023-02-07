<?php
/*+***********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************/
class Promotion_Print_View extends Vtiger_Print_View {
	
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
		$module_data_info = Vtiger_Record_Model::getInstanceById($record, 'Promotion');
		$document = $this->loadTemplate('printtemplates/Promotion/pdf_'.$request->get('language').'.html');

		// $request_date = date("d-m-Y", strtotime($module_data_info->get('createdtime')));
		$request_date = date("Y-m-d");
		$request_edit_date = date("Y-m-d", strtotime($module_data_info->get('ModifiedTime')));
		
		// feedback user info
		$this->setValue('name', $module_data_info->getDisplayValue('name'), ENT_QUOTES, "UTF-8");
		$this->setValue('refNo', $module_data_info->getDisplayValue('cf_7414'), ENT_QUOTES, "UTF-8");

		$this->setValue('position', $module_data_info->getDisplayValue('cf_7418'), ENT_QUOTES, "UTF-8");
		$this->setValue('department', $module_data_info->getDisplayValue('cf_7420'), ENT_QUOTES, "UTF-8");		
		$this->setValue('date', $request_date, ENT_QUOTES, "UTF-8");
		$this->setValue('promotiondate', $module_data_info->getDisplayValue('cf_7416'), ENT_QUOTES, "UTF-8");

		$this->setValue('director', $module_data_info->getDisplayValue('cf_7424'), ENT_QUOTES, "UTF-8");
		$this->setValue('directorsignature', $module_data_info->getDisplayValue('cf_7426'), ENT_QUOTES, "UTF-8");

		$this->setValue('CreatedBy', $module_data_info->getDisplayValue('assigned_user_id'));

		$this->setValue('generalmanager', $module_data_info->getDisplayValue('cf_7430'));
		$this->setValue('gmsignature', $module_data_info->getDisplayValue('cf_7428'), ENT_QUOTES, "UTF-8");

		$this->setValue('hrmanager', $module_data_info->getDisplayValue('cf_7432'), ENT_QUOTES, "UTF-8");
		$this->setValue('hrsignature', $module_data_info->getDisplayValue('cf_7434'), ENT_QUOTES, "UTF-8");

		$this->setValue('understood', $module_data_info->getDisplayValue('cf_7436'), ENT_QUOTES, "UTF-8");

		// $this->setValue('agree', $module_data_info->getDisplayValue('cf_7438'), ENT_QUOTES, "UTF-8");
		$this->setValue('salary_details', $module_data_info->getDisplayValue('cf_7422'), ENT_QUOTES, "UTF-8");	
	
		include ('include/mpdf60/mpdf.php');

		$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 30, 15, 10, 5);
  	$mpdf->charset_in = 'utf8';
		$mpdf->list_indent_first_level = 0;
		$mpdf->SetHTMLHeader('
		<div width="100%" align="center">
		<img
		
			height="56"
			src="printtemplates/glklogo.jpg"
			align="center"
			hspace="12"
		/>
	</div>');
		
		$mpdf->SetHTMLFooter('
			<table width="80%" align="left" border="1" cellpadding="0" cellspacing="1">
				<tr>
					<td width="40%" align="left" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
						Revision No. 
					</td>
	  				<td width="40%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
	  					02
	  				</td>
	  				<td width="40%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
	  					Date: '.date("Y-m-d").'
	  				</td>
	  				<td width="40%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
	  					Document control No.
	  				</td>
	  				<td width="20%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
	  					GLK/KZ/HR/SOP-01/F-03
	  				</td>
  				</tr>
  				<tr>
  					<td align="right" colspan="5"> Page {PAGENO} of {nbpg} </td>
  				</tr>
			</table>');

		$stylesheet = file_get_contents('include/mpdf60/examples/mpdfstyletables.css');
		$mpdf->WriteHTML($stylesheet,1);	// The parameter 1 tells that this is css/style only and no body/html/text
		
		$mpdf->WriteHTML($this->_documentXML); /*Ñ„Ð¾Ñ€Ð¼Ð¸Ñ€ÑƒÐµÐ¼ pdf*/
		$pdf_name = "pdf_docs/promotion_".$request->get('language')."_" . $record . ".pdf";
		$mpdf->Output($pdf_name, 'F');

		// header('Location:http://mb.globalink.net/vt60/'.$pdf_name);

		header('Location:' . $pdf_name);
		exit;

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