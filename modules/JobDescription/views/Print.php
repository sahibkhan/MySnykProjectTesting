<?php
/*+***********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************/
class JobDescription_Print_View extends Vtiger_Print_View {
	
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

	function preProcessTplName(Vtiger_Request $request) {
		if($request->get('print_type') == 'word')
		{
		$this->word_print($request);
		exit;
		}
	}

	function process (Vtiger_Request $request)	{

		$moduleName = $request->getModule();
		$record = $request->get('record');
		$jobdescription_info = Vtiger_Record_Model::getInstanceById($record, 'JobDescription');
		//$jobdescription_info = Users_Record_Model::getInstanceById($jobdescription_info->get('assigned_user_id') , 'Users');

		// echo "<pre>";
		// print_r($jobdescription_info);
		// echo "</pre>";
		// exit;
		$document = $this->loadTemplate('printtemplates/JobDescription/pdf.html');

		$request_date = date("Y-m-d", strtotime($jobdescription_info->get('CreatedTime')));
		$request_edit_date = date("Y-m-d", strtotime($jobdescription_info->get('ModifiedTime')));

		// feedback user info
/* 		$this->setValue('legalentitiy', $jobdescription_info->getDisplayValue('cf_6792'), ENT_QUOTES, "UTF-8");
		$this->setValue('date', $jobdescription_info->getDisplayValue('cf_6794'), ENT_QUOTES, "UTF-8");
		$this->setValue('positiontitle', $jobdescription_info->getDisplayValue('cf_6796'), ENT_QUOTES, "UTF-8");
		$this->setValue('department', $jobdescription_info->getDisplayValue('cf_6798'), ENT_QUOTES, "UTF-8");
		$this->setValue('location', $jobdescription_info->getDisplayValue('cf_6800'), ENT_QUOTES, "UTF-8");
		$this->setValue('incumbent', $jobdescription_info->getDisplayValue('cf_6802'), ENT_QUOTES, "UTF-8");
		$this->setValue('incumbentsignature', $jobdescription_info->getDisplayValue('cf_6804'), ENT_QUOTES, "UTF-8");
		$this->setValue('supervisorsignature', $jobdescription_info->getDisplayValue('cf_6806'), ENT_QUOTES, "UTF-8");
		$this->setValue('reportsto', $jobdescription_info->getDisplayValue('cf_6808'), ENT_QUOTES, "UTF-8");
		$this->setValue('jobpurpose', $jobdescription_info->getDisplayValue('cf_6810'), ENT_QUOTES, "UTF-8");
		$this->setValue('financial', $jobdescription_info->getDisplayValue('cf_6812'), ENT_QUOTES, "UTF-8");
		$this->setValue('nonfinancial', $jobdescription_info->getDisplayValue('cf_6814'), ENT_QUOTES, "UTF-8");
		$this->setValue('internalcontacts', $jobdescription_info->getDisplayValue('cf_6820'), ENT_QUOTES, "UTF-8");
		$this->setValue('orgchart', $jobdescription_info->getDisplayValue('cf_6822'), ENT_QUOTES, "UTF-8");
		$this->setValue('externalcontacts', $jobdescription_info->getDisplayValue('cf_6824'), ENT_QUOTES, "UTF-8");
		$this->setValue('education', $jobdescription_info->getDisplayValue('cf_6826'), ENT_QUOTES, "UTF-8");
		$this->setValue('workexperience', $jobdescription_info->getDisplayValue('cf_6828'), ENT_QUOTES, "UTF-8");
		$this->setValue('languageskills', $jobdescription_info->getDisplayValue('cf_6830'), ENT_QUOTES, "UTF-8");
		$this->setValue('specificknowledge', $jobdescription_info->getDisplayValue('cf_6832'), ENT_QUOTES, "UTF-8");
		$this->setValue('businessunderstanding', $jobdescription_info->getDisplayValue('cf_6834'), ENT_QUOTES, "UTF-8");
		$this->setValue('keyaccountabilities', $jobdescription_info->getDisplayValue('cf_6836'), ENT_QUOTES, "UTF-8");
		$this->setValue('signature', $jobdescription_info->getDisplayValue('cf_6946'), ENT_QUOTES, "UTF-8"); */

		include ('include/mpdf60/mpdf.php');

		$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 55, 15, 10, 5);
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
	</div>
	<p>
		<strong></strong>
	</p>
	<table border="1" cellspacing="0" cellpadding="0" width="100%">
		<tbody>
			<tr>
				<td width="300" valign="top">
					<p>
						<strong>Location:</strong>
					</p>
				</td>
				<td width="144" rowspan="2" valign="top">
					<p align="right">
						<strong></strong>
					</p>
					<p align="right">
						<strong>Approved:</strong>
						<strong></strong>
					</p>
				</td>
				<td width="217" rowspan="2" valign="top">
				</td>
			</tr>
			<tr>
				<td width="300" valign="top">
					'.$jobdescription_info->getDisplayValue('cf_6800').'
				</td>
			</tr>
			<tr>
				<td width="300" valign="top">
					<p>
						<strong>Job Description</strong>
						<strong></strong>
					</p>
				</td>
				<td width="144" valign="top">
					<p align="right">
						<strong>Version</strong>
					</p>
				</td>
				<td width="217" valign="top">
				</td>
			</tr>
			<tr>
				<td width="300" valign="top">
					<p>'.
					$jobdescription_info->getDisplayValue("name").'
					</p>
				</td>
				<td width="144" valign="top">
					<p align="right">
						<strong>Revision </strong>
						<strong>Date:</strong>
					</p>
				</td>
				<td width="217" valign="top">
				</td>
			</tr>
		</tbody>
	</table>');
		
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
		$pdf_name = "pdf_docs/jobdescription_" . $record . ".pdf";
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

	public function word_print(Vtiger_Request $request){
		//set data in template file using PHPWord library
		require_once 'libraries/PHPWord/PHPWord.php';
		$PHPWord = new PHPWord();
		$document = $PHPWord->loadTemplate('include/JobDescription/JobDescription.docx');
		$moduleName = $request->getModule();
		$record = $request->get('record');
		$jobdescription_info = Vtiger_Record_Model::getInstanceById($record, 'JobDescription');
		//$jobdescription_info = Users_Record_Model::getInstanceById($jobdescription_info->get('assigned_user_id') , 'Users');

/*  		 echo "<pre>";
		 print_r($document);
		 echo "</pre>";
		 exit; */
 
		//$document = $this->loadTemplate('printtemplates/JobDescription/pdf.html');

		$request_date = date("Y-m-d", strtotime($jobdescription_info->get('CreatedTime')));
		$request_edit_date = date("Y-m-d", strtotime($jobdescription_info->get('ModifiedTime')));

 		// feedback user info
		$document->setValue('locationheader', $jobdescription_info->getDisplayValue('cf_7548'));


		$document->setValue('positionheader', $jobdescription_info->getDisplayValue('cf_7546'));
		$document->setValue('legalentitiy', $jobdescription_info->getDisplayValue('name'));
		$document->setValue('date', $jobdescription_info->getDisplayValue('cf_7544'));
		$document->setValue('positiontitle', $jobdescription_info->getDisplayValue('cf_7546'));
		$document->setValue('department', $jobdescription_info->getDisplayValue('cf_7550'));


		$document->setValue('incumbentsignature', $jobdescription_info->getDisplayValue('cf_7554'));
		$document->setValue('location', $jobdescription_info->getDisplayValue('cf_7548'));
		$document->setValue('incumbent', $jobdescription_info->getDisplayValue('cf_7552'));
		
		$document->setValue('supervisorsignature', $jobdescription_info->getDisplayValue('cf_7556'));
		$document->setValue('reportsto', $jobdescription_info->getDisplayValue('cf_7558'));
		$document->setValue('jobpurpose', $jobdescription_info->getDisplayValue('cf_7560'));
		$document->setValue('financial', $jobdescription_info->getDisplayValue('cf_7562'));

		$document->setValue('nonfinancial', $jobdescription_info->getDisplayValue('cf_7564'));
		$document->setValue('orgchart', $jobdescription_info->getDisplayValue('cf_7568'));
		$document->setValue('internalcontacts', $jobdescription_info->getDisplayValue('cf_7566'));
		$document->setValue('externalcontacts', $jobdescription_info->getDisplayValue('cf_7570'));
		$document->setValue('education', $jobdescription_info->getDisplayValue('cf_7572'));

		$document->setValue('workexperience', $jobdescription_info->getDisplayValue('cf_7574'));
		$document->setValue('languageskills', $jobdescription_info->getDisplayValue('cf_7576'));
		$document->setValue('specificknowledge', $jobdescription_info->getDisplayValue('cf_7578'));
		$document->setValue('businessunderstanding', $jobdescription_info->getDisplayValue('cf_7580'));
		$document->setValue('keyaccountabilities', $jobdescription_info->getDisplayValue('cf_7582'));
		$document->setValue('mainresponsibilities', $jobdescription_info->getDisplayValue('cf_7784'));
		$document->setValue('signature', $jobdescription_info->getDisplayValue('cf_7584'));

		$temp_file = tempnam(sys_get_temp_dir(), 'PHPWord');
		ob_clean();
		$document->save($temp_file);



		// Your browser will name the file "myFile.docx"
		// regardless of what it's named on the server 
		
		header("Content-Disposition: attachment; filename=JobDescription.docx");
		// header('Location: pdf_docs/1.docx')
		readfile($temp_file); // or echo file_get_contents($temp_file);
		unlink($temp_file);  // remove temp file
	}
}