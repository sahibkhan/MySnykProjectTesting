<?php
/*+***********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************/
class HandOverList_Print_View extends Vtiger_Print_View {
	
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
		$module_data_info = Vtiger_Record_Model::getInstanceById($record, 'HandOverList');
		//$module_data_info = Users_Record_Model::getInstanceById($module_data_info->get('assigned_user_id') , 'Users');

		// echo "<pre>";
		// print_r($module_data_info);
		// echo "</pre>";
		// exit;
		$document = $this->loadTemplate('printtemplates/HandOverList/pdf.html');

		$request_date = date("Y-m-d", strtotime($module_data_info->get('CreatedTime')));
		$request_edit_date = date("Y-m-d", strtotime($module_data_info->get('ModifiedTime')));

		// feedback user info
		$this->setValue('employeename', $module_data_info->getDisplayValue('name'), ENT_QUOTES, "UTF-8");
		$this->setValue('position', $module_data_info->getDisplayValue('cf_7512'), ENT_QUOTES, "UTF-8");
		$this->setValue('toname', $module_data_info->getDisplayValue('cf_7510'), ENT_QUOTES, "UTF-8");
		$this->setValue('toposition', $module_data_info->getDisplayValue('cf_7514'), ENT_QUOTES, "UTF-8");

		$this->setValue('list', str_replace(",","<br/>",$module_data_info->getDisplayValue('cf_7506')), ENT_QUOTES, "UTF-8");
		
		$this->setValue('location', $module_data_info->getDisplayValue('cf_7500'), ENT_QUOTES, "UTF-8");
		$this->setValue('department', $module_data_info->getDisplayValue('cf_7502'), ENT_QUOTES, "UTF-8");
		
		$this->setValue('date', $module_data_info->getDisplayValue('cf_7504'), ENT_QUOTES, "UTF-8");
		
		$this->setValue('fromsignature', $module_data_info->getDisplayValue('cf_7516'), ENT_QUOTES, "UTF-8");
		$this->setValue('fromdate', $module_data_info->getDisplayValue('cf_7518'), ENT_QUOTES, "UTF-8");
		
		$this->setValue('tosignature', $module_data_info->getDisplayValue('cf_7520'), ENT_QUOTES, "UTF-8");
		$this->setValue('todate', $module_data_info->getDisplayValue('cf_7522'), ENT_QUOTES, "UTF-8");
		
		$this->setValue('headsignature', $module_data_info->getDisplayValue('cf_7524'), ENT_QUOTES, "UTF-8");
		$this->setValue('headdate', $module_data_info->getDisplayValue('cf_7526'), ENT_QUOTES, "UTF-8");
		
		

		include ('include/mpdf60/mpdf.php');

		$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 30, 15, 10, 5);
  		$mpdf->charset_in = 'utf8';


		// $mpdf->list_indent_first_level = 0;
		// $mpdf->SetDefaultFontSize(12);
		//$mpdf->autoPageBreak = true;
		//$mpdf->setAutoTopMargin = 20;
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
		
		$mpdf->WriteHTML($this->_documentXML); /*??????????????????????????????????????? pdf*/
		$pdf_name = "pdf_docs/handoverlist_" . $record . ".pdf";
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