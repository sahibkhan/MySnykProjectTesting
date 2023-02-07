<?php
/*+***********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************/
class InterviewAssessment_Print_View extends Vtiger_Print_View {
	
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
		//print_r($record); exit;
		$InterviewAssessment_info = Vtiger_Record_Model::getInstanceById($record, 'InterviewAssessment');
		//$InterviewAssessment_info = Users_Record_Model::getInstanceById($InterviewAssessment_info->get('assigned_user_id') , 'Users');

		// echo "<pre>";
		// print_r($InterviewAssessment_info);
		// echo "</pre>";
		// exit;
		$document = $this->loadTemplate('printtemplates/HR/InterviewAssessment/pdf.html');

		$request_date = date("Y-m-d", strtotime($InterviewAssessment_info->get('CreatedTime')));
		$request_edit_date = date("Y-m-d", strtotime($InterviewAssessment_info->get('ModifiedTime')));

		// feedback user info
		$this->setValue('name', $InterviewAssessment_info->getDisplayValue('name'), ENT_QUOTES, "UTF-8");
		$this->setValue('age', $InterviewAssessment_info->getDisplayValue('cf_7214'), ENT_QUOTES, "UTF-8");
		$this->setValue('position', $InterviewAssessment_info->getDisplayValue('cf_7216'), ENT_QUOTES, "UTF-8");
		$this->setValue('department', $InterviewAssessment_info->getDisplayValue('cf_7220'), ENT_QUOTES, "UTF-8");
		$this->setValue('location', $InterviewAssessment_info->getDisplayValue('cf_7222'), ENT_QUOTES, "UTF-8");
		$this->setValue('type', $InterviewAssessment_info->getDisplayValue('cf_7218'), ENT_QUOTES, "UTF-8");
		$this->setValue('education', $InterviewAssessment_info->getDisplayValue('cf_7226'), ENT_QUOTES, "UTF-8");
		$this->setValue('workexperience', $InterviewAssessment_info->getDisplayValue('cf_7230'), ENT_QUOTES, "UTF-8");
		$this->setValue('communication', $InterviewAssessment_info->getDisplayValue('cf_7236'), ENT_QUOTES, "UTF-8");
		$this->setValue('language', $InterviewAssessment_info->getDisplayValue('cf_7244'), ENT_QUOTES, "UTF-8");
		$this->setValue('leadership', $InterviewAssessment_info->getDisplayValue('cf_7240'), ENT_QUOTES, "UTF-8");
		$this->setValue('strength', $InterviewAssessment_info->getDisplayValue('cf_7246'), ENT_QUOTES, "UTF-8");
		$this->setValue('weekness', $InterviewAssessment_info->getDisplayValue('cf_7248'), ENT_QUOTES, "UTF-8");
		$this->setValue('presentsalary', $InterviewAssessment_info->getDisplayValue('cf_7250'), ENT_QUOTES, "UTF-8");
		$this->setValue('expectedsalary', $InterviewAssessment_info->getDisplayValue('cf_7252'), ENT_QUOTES, "UTF-8");
		$this->setValue('availability', $InterviewAssessment_info->getDisplayValue('cf_7254'), ENT_QUOTES, "UTF-8");
		$interview_result = $InterviewAssessment_info->getDisplayValue('cf_7264');
		if($interview_result == "Satisfactory"){
		$this->setValue('interview_result_satisfactory', "checked='True'", ENT_QUOTES, "UTF-8");}
		elseif($interview_result == "Unsatisfactory"){
		$this->setValue('interview_result_unsatisfactory', "checked='True'", ENT_QUOTES, "UTF-8");}
		else{
			$this->setValue('interview_result_satisfactory', "", ENT_QUOTES, "UTF-8");
			$this->setValue('interview_result_unsatisfactory', "", ENT_QUOTES, "UTF-8");
		}

		$this->setValue('interview_result', $InterviewAssessment_info->getDisplayValue('cf_7264'), ENT_QUOTES, "UTF-8");
		$this->setValue('source', $InterviewAssessment_info->getDisplayValue('cf_7268'), ENT_QUOTES, "UTF-8");
		$this->setValue('interviewers', $InterviewAssessment_info->getDisplayValue('cf_7270'), ENT_QUOTES, "UTF-8");
		$interview_type = $InterviewAssessment_info->getDisplayValue('cf_7274');
		if($interview_type == "Personal"){
		$this->setValue('interview_personal', "checked='True'", ENT_QUOTES, "UTF-8");}
		elseif($interview_type == "Phone"){
		$this->setValue('interview_phone', "checked='True'", ENT_QUOTES, "UTF-8");}
		elseif($interview_type == "Skype Video"){
			$this->setValue('interview_skype', "checked='True'", ENT_QUOTES, "UTF-8");}
		else{
			$this->setValue('interview_personal', "", ENT_QUOTES, "UTF-8");
			$this->setValue('interview_phone', "", ENT_QUOTES, "UTF-8");
			$this->setValue('interview_skype', "", ENT_QUOTES, "UTF-8");
		}
		//$this->setValue('interview', $InterviewAssessment_info->getDisplayValue('cf_6514'), ENT_QUOTES, "UTF-8");
		$this->setValue('date', $InterviewAssessment_info->getDisplayValue('cf_7272'), ENT_QUOTES, "UTF-8");
		$education = $InterviewAssessment_info->getDisplayValue('cf_7272');
		$edu_rating = "";
		for($i=5;$i>=1;$i--)
		{
			$edu_rating .= "<td width='2%' valign='top'>";
			if($education == $i)
			{
				$edu_rating .= "X";
			}
			$edu_rating .= "</td>";
		}
		$this->setValue('education_rating',$edu_rating , ENT_QUOTES, "UTF-8");
		$workexperience = $InterviewAssessment_info->getDisplayValue('cf_7228');
		$work_rating = "";
		for($i=5;$i>=1;$i--)
		{
			$work_rating .= "<td width='2%' valign='top'>";
			if($workexperience == $i)
			{
				$work_rating .= "X";
			}
			$work_rating .= "</td>";
		}
		$this->setValue('workexperience_rating', $work_rating, ENT_QUOTES, "UTF-8");
		$communication = $InterviewAssessment_info->getDisplayValue('cf_7232');
		$communication_rating = "";
		for($i=5;$i>=1;$i--)
		{
			$communication_rating .= "<td width='2%' valign='top'>";
			if($communication == $i)
			{
				$communication_rating .= "X";
			}
			$communication_rating .= "</td>";
		}
		$this->setValue('communication_rating', $communication_rating, ENT_QUOTES, "UTF-8");
		$languages = $InterviewAssessment_info->getDisplayValue('cf_7244');
		$language_rating = '';
		// Russian:5;English:4;
		$langArr = explode(';', $languages);

		foreach ($langArr as $lang){
			$language_rating .= $lang . ' ';
		}
		
		$this->setValue('language_rating', $language_rating, ENT_QUOTES, "UTF-8");
		$leadership = $InterviewAssessment_info->getDisplayValue('cf_6490');
		$leadership_rating = "";
		for($i=5;$i>=1;$i--)
		{
			$leadership_rating .= "<td width='2%' valign='top'>";
			if($leadership == $i)
			{
				$leadership_rating .= "X";
			}
			$leadership_rating .= "</td>";
		}
		$this->setValue('leadership_rating', $leadership_rating, ENT_QUOTES, "UTF-8");
		//$this->setValue('education_rating', $InterviewAssessment_info->getDisplayValue('cf_6478'), ENT_QUOTES, "UTF-8");
		//$this->setValue('workexperience_rating', $InterviewAssessment_info->getDisplayValue('cf_6482'), ENT_QUOTES, "UTF-8");
		//$this->setValue('communication_rating', $InterviewAssessment_info->getDisplayValue('cf_6486'), ENT_QUOTES, "UTF-8");
		//$this->setValue('language_rating', $InterviewAssessment_info->getDisplayValue('cf_7232'), ENT_QUOTES, "UTF-8");
		//$this->setValue('leadership_rating', $InterviewAssessment_info->getDisplayValue('cf_6490'), ENT_QUOTES, "UTF-8");
		
		//print_r('expression'); exit;
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
	// <p>
	// 	<strong></strong>
	// </p>
	// <table border="1" cellspacing="0" cellpadding="0" width="100%">
	// 	<tbody>
	// 		<tr>
	// 			<td width="300" valign="top">
	// 				<p>
	// 					<strong>Location:</strong>
	// 				</p>
	// 			</td>
	// 			<td width="144" rowspan="2" valign="top">
	// 				<p align="right">
	// 					<strong></strong>
	// 				</p>
	// 				<p align="right">
	// 					<strong>Approved:</strong>
	// 					<strong></strong>
	// 				</p>
	// 			</td>
	// 			<td width="217" rowspan="2" valign="top">
	// 			</td>
	// 		</tr>
	// 		<tr>
	// 			<td width="300" valign="top">
	// 				'.$InterviewAssessment_info->getDisplayValue('name').'
	// 			</td>
	// 		</tr>
	// 		<tr>
	// 			<td width="300" valign="top">
	// 				<p>
	// 					<strong>Job Description</strong>
	// 					<strong></strong>
	// 				</p>
	// 			</td>
	// 			<td width="144" valign="top">
	// 				<p align="right">
	// 					<strong>Version</strong>
	// 				</p>
	// 			</td>
	// 			<td width="217" valign="top">
	// 			</td>
	// 		</tr>
	// 		<tr>
	// 			<td width="300" valign="top">
	// 				<p>'.
	// 				$InterviewAssessment_info->getDisplayValue("name").'
	// 				</p>
	// 			</td>
	// 			<td width="144" valign="top">
	// 				<p align="right">
	// 					<strong>Revision </strong>
	// 					<strong>Date:</strong>
	// 				</p>
	// 			</td>
	// 			<td width="217" valign="top">
	// 			</td>
	// 		</tr>
	// 	</tbody>
	// </table>');
		
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
  					<td align="center" colspan="5"><img width="100%"
					  src="printtemplates/glkfooter.jpg"
					  align="center"
				  /></td>
  				</tr>
			</table>');

		$stylesheet = file_get_contents('include/mpdf60/examples/mpdfstyletables.css');
		$mpdf->WriteHTML($stylesheet,1);	// The parameter 1 tells that this is css/style only and no body/html/text
		
		$mpdf->WriteHTML($this->_documentXML,2); /*Ñ„Ð¾Ñ€Ð¼Ð¸Ñ€ÑƒÐµÐ¼ pdf*/
		$pdf_name = "pdf_docs/interviewassessment" . $record . ".pdf";
		//$pdf_name = "pdf_docs/interviewassessment.pdf";
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