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

/* 	public function word_print(Vtiger_Request $request){

		ini_set('display_errors', 1);
		error_reporting(E_ALL);


		require_once 'libraries/PhpWordNew/PhpWord.php';
		// $PHPWord = new PHPWord();
		// $document = $PHPWord->loadTemplate('modules/InvoiceGLK/AXG Appendix draft_rus.docx');
		$templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor('modules/InvoiceGLK/AXG Appendix draft_rus.docx');
		$title = new TextRun();
		$title->addText('This title has been set ', array('bold' => true, 'italic' => true, 'color' => 'blue'));
		$title->addText('dynamically', array('bold' => true, 'italic' => true, 'color' => 'red', 'underline' => 'single'));
		$templateProcessor->setComplexBlock('title', $title);
	

		$templateProcessor->saveAs('Sample_40_TemplateSetComplexValue.docx');
	} */
	



 	public function word_print(Vtiger_Request $request){

		ini_set('display_errors', 1);
		error_reporting(E_ALL);
		//set data in template file using PHPWord library
		require_once 'libraries/PHPWord/PHPWord.php';
		$PHPWord = new PHPWord();
		$document = $PHPWord->loadTemplate('modules/InvoiceGLK/AXG Appendix draft_rus.docx');
		$moduleName = $request->getModule();
		$record = $request->get('record');
		
		$appendixInfo = Vtiger_Record_Model::getInstanceById($record, 'InvoiceGLK');
		// $appendixInfo = Vtiger_Record_Model::getInstanceById($record, 'InvoiceGLK');
		$agreement = $appendixInfo->get('cf_1819');

	
		$jobNo = '';
		$jobType = '';
		$jobPCS = '';
		$jobWeight = '';
		$transporFromTo = '';
		$price = '';
		$insurance = '';
		$paymentTypeInfo = '';
		$clientName = '';
		$clientBankInfo = ''; 

		if ($appendixInfo->get('cf_5079')){

			$jobfileInfo = Vtiger_Record_Model::getInstanceById($appendixInfo->get('cf_5079'), 'Job');
			$jobNo = $jobfileInfo->get('cf_1198');
			$jobType = $jobfileInfo->get('cf_1518');
			$jobPCS = $jobfileInfo->get('cf_1429');
			$jobWeight = $jobfileInfo->get('cf_1084');
			$transporFromTo = 'From Almaty to Astana';
			$price = $jobfileInfo->get('cf_1524');
			$insurance = 'insurance';
			$paymentTypeInfo = 'paymentTypeInfo';
			$clientName = $jobfileInfo->getDisplayValue('cf_1441');
			$clientBankInfo = 'clientBankInfo'; 
			
		}

 		// feedback user info
		$document->setValue('job', $jobNo);
		$document->setValue('agr', 'GT 158');

 		// $document->setValue('day', Date('d'));
		$document->setValue('date', 'march 2021');

		$document->setValue('items', 'Meshki');
		$document->setValue('count', '35');
		$document->setValue('weight', '160 KG');


		$document->setValue('contact', 'Samir');
		$document->setValue('contacttype', 'Director');
		$document->setValue('apptype', 'Ustav');
		$document->setValue('fromTo', 'Astana to Almaty 55');

		$document->setValue('transpPrice', '100 USD');
		$document->setValue('insuranceCost', '300 USD');

		$document->setValue('contract', 'GT 158');

		$document->setValue('terms', 'Сроки и условия оплаты из карточки контакта');
		$document->setValue('app', 'GT 9999');
		


		
		


		// $document->setValue('numpieces', 16);
		// $document->setValue('weight', '36 KG');

		/*
		$document->setValue('jobType', $jobType);
		$document->setValue('jobPCS', $jobPCS);
		$document->setValue('jobWeight', $jobWeight);
		$document->setValue('transporFromTo', $transporFromTo);

		$document->setValue('price', $price);
		$document->setValue('insurance', $insurance);
		$document->setValue('paymentTypeInfo', $paymentTypeInfo);
		$document->setValue('clientName', $clientName);
		$document->setValue('clientBankInfo', $clientBankInfo); */

/* 		$filename = "111111.docx";
		header("Content-Description: File Transfer");
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
		header('Content-Transfer-Encoding: binary');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Expires: 0');
		$objWriter = PHPWord_IOFactory::createWriter($PHPWord, 'Word2007');
		$objWriter->save("php://output");
 */

$filename = "111111.docx";
		
		$temp_file = tempnam(sys_get_temp_dir(), 'PHPWord');
		ob_clean();
		$document->save($temp_file);
		
		header("Content-Description: File Transfer");
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
		header('Content-Transfer-Encoding: binary');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Expires: 0');

		// header('Location: pdf_docs/1.docx')
		readfile($temp_file); // or echo file_get_contents($temp_file);
		unlink($temp_file);  // remove temp file */

		


	 }


	
}