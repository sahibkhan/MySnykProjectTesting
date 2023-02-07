<?php
/*+***********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************/
class ProbationAssessment_Print_View extends Vtiger_Print_View {
	
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
/* 
		ini_set('display_errors', 1);
		error_reporting(E_ALL); */

		$moduleName = $request->getModule();
		$record = $request->get('record');
		$module_data_info = Vtiger_Record_Model::getInstanceById($record, 'ProbationAssessment');
		//$module_data_info = Users_Record_Model::getInstanceById($module_data_info->get('assigned_user_id') , 'Users');

		// echo "<pre>";
		// print_r($module_data_info);
		// echo "</pre>";
		// exit;
		$document = $this->loadTemplate('printtemplates/ProbationAssessment/pdf.html');

		$request_date = date("Y-m-d", strtotime($module_data_info->get('CreatedTime')));
		$request_edit_date = date("Y-m-d", strtotime($module_data_info->get('ModifiedTime')));

		// feedback user info
		$requestedBy = $module_data_info->getDisplayValue('name');
		$this->setValue('employee_name', $requestedBy, ENT_QUOTES, "UTF-8");
		$this->setValue('position', $module_data_info->getDisplayValue('cf_7296'), ENT_QUOTES, "UTF-8");
		$this->setValue('department', $module_data_info->getDisplayValue('cf_7298'), ENT_QUOTES, "UTF-8");
		$this->setValue('hiring_date', $module_data_info->getDisplayValue('cf_7300'), ENT_QUOTES, "UTF-8");
		
		$this->setValue('probation_end_date', $module_data_info->getDisplayValue('cf_7302'), ENT_QUOTES, "UTF-8");
		$this->setValue('knowledge_rating', $module_data_info->getDisplayValue('cf_7306'), ENT_QUOTES, "UTF-8");
		$this->setValue('productivity_rating', $module_data_info->getDisplayValue('cf_7314'), ENT_QUOTES, "UTF-8");
		$this->setValue('quality_work_rating', $module_data_info->getDisplayValue('cf_7308'), ENT_QUOTES, "UTF-8");

		$this->setValue('attendance_rating', $module_data_info->getDisplayValue('cf_7316'), ENT_QUOTES, "UTF-8");
		$this->setValue('aptitude_rating', $module_data_info->getDisplayValue('cf_7310'), ENT_QUOTES, "UTF-8");
		$this->setValue('initiative_rating', $module_data_info->getDisplayValue('cf_7318'), ENT_QUOTES, "UTF-8");
		$this->setValue('teamwork_rating', $module_data_info->getDisplayValue('cf_7312'), ENT_QUOTES, "UTF-8");

		$this->setValue('overall_rating', $module_data_info->getDisplayValue('cf_7320'), ENT_QUOTES, "UTF-8");
		$this->setValue('comments', $module_data_info->getDisplayValue('cf_7322'), ENT_QUOTES, "UTF-8");
		$recommendation = ""; 
		if($module_data_info->getDisplayValue('cf_7324') == "Terminate Employment Immediately")
		{
			$recommendation = "&#183; Terminate employment
			immediately/&#1055;&#1088;&#1077;&#1088;&#1074;&#1072;&#1090;&#1100;
			&#1090;&#1088;&#1091;&#1076;&#1086;&#1074;&#1099;&#1077;
			&#1086;&#1090;&#1085;&#1086;&#1096;&#1077;&#1085;&#1080;&#1103;
			&#1085;&#1077;&#1084;&#1077;&#1076;&#1083;&#1077;&#1085;&#1085;&#1086;";
		}
		elseif($module_data_info->getDisplayValue('cf_7324') == "Change Employement Status to Permanent")
		{
			$recommendation = "&#183; Change employment status to
			permanent/&#1057;&#1095;&#1080;&#1090;&#1072;&#1090;&#1100;
			&#1080;&#1089;&#1087;&#1099;&#1090;&#1072;&#1090;&#1077;&#1083;&#1100;&#1085;&#1099;&#1081;
			&#1089;&#1088;&#1086;&#1082;
			&#1087;&#1088;&#1086;&#1081;&#1076;&#1077;&#1085;&#1085;&#1099;&#1084;
			&#1091;&#1089;&#1087;&#1077;&#1096;&#1085;&#1086;";
		}
		elseif($module_data_info->getDisplayValue('cf_7324') == "Salary Raise")
		{
			$recommendation = "&#183; If salary raise is applicable, indicate new salary level: ".$module_data_info->getDisplayValue('cf_6558')."
			and effective date of increase ".$module_data_info->getDisplayValue('cf_7328')." / &#1045;&#1089;&#1083;&#1080;
			&#1087;&#1086;&#1083;&#1072;&#1075;&#1072;&#1077;&#1090;&#1089;&#1103;
			&#1087;&#1086;&#1074;&#1099;&#1096;&#1077;&#1085;&#1080;&#1077;
			&#1086;&#1082;&#1083;&#1072;&#1076;&#1072;,
			&#1091;&#1082;&#1072;&#1078;&#1080;&#1090;&#1077;
			&#1088;&#1072;&#1079;&#1084;&#1077;&#1088;
			&#1085;&#1086;&#1074;&#1086;&#1075;&#1086;
			&#1086;&#1082;&#1083;&#1072;&#1076;&#1072; ".$module_data_info->getDisplayValue('cf_7326')." &#1080;
			&#1076;&#1072;&#1090;&#1091;, &#1089;
			&#1082;&#1086;&#1090;&#1086;&#1088;&#1086;&#1081;
			&#1087;&#1086;&#1074;&#1099;&#1096;&#1077;&#1085;&#1080;&#1077;
			&#1086;&#1082;&#1083;&#1072;&#1076;&#1072;
			&#1074;&#1089;&#1090;&#1091;&#1087;&#1072;&#1077;&#1090; &#1074;
			&#1089;&#1080;&#1083;&#1091; ".$module_data_info->getDisplayValue('cf_7326').".";
		}
		$this->setValue('recommendation', $recommendation, ENT_QUOTES, "UTF-8");
		
 		$this->setValue('employee', $module_data_info->getDisplayValue('cf_7334'), ENT_QUOTES, "UTF-8");
		$this->setValue('employee_signature', $module_data_info->getDisplayValue('cf_7342'), ENT_QUOTES, "UTF-8");

/* 		$this->setValue('supervisor', $module_data_info->getDisplayValue('cf_7330'), ENT_QUOTES, "UTF-8");
		$this->setValue('supervisor_signature', $module_data_info->getDisplayValue('cf_7338'), ENT_QUOTES, "UTF-8"); */

		$this->setValue('Head', $module_data_info->getDisplayValue('cf_7336'), ENT_QUOTES, "UTF-8");
		$this->setValue('Head_signature', $module_data_info->getDisplayValue('cf_7344'), ENT_QUOTES, "UTF-8");

		$this->setValue('HR', $module_data_info->getDisplayValue('cf_7332'), ENT_QUOTES, "UTF-8");
		$this->setValue('HR_signature', $module_data_info->getDisplayValue('cf_7340'), ENT_QUOTES, "UTF-8");

		// $this->setValue('communication_rating', $module_data_info->getDisplayValue('cf_6486'), ENT_QUOTES, "UTF-8");
		// $this->setValue('language_rating', $module_data_info->getDisplayValue('cf_6494'), ENT_QUOTES, "UTF-8");
		// $this->setValue('leadership_rating', $module_data_info->getDisplayValue('cf_6490'), ENT_QUOTES, "UTF-8");
		
		if ($module_data_info->getDisplayValue('cf_7326') > 0) {
			$newSalaryLevel = $module_data_info->getDisplayValue('cf_7326');
		} else $newSalaryLevel = '';

		if (!empty($module_data_info->getDisplayValue('cf_7328'))) {
			$newSalaryDate = $module_data_info->getDisplayValue('cf_7328');
		} else $newSalaryDate = '';


		$this->setValue('newSalaryLevel', $newSalaryLevel, ENT_QUOTES, "UTF-8");	
		$this->setValue('newSalaryDate', $newSalaryDate, ENT_QUOTES, "UTF-8");	


		include ('include/mpdf60/mpdf.php');

		$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 30, 5, 10, 5);
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

	

		$stylesheet = file_get_contents('include/mpdf60/examples/mpdfstyletables.css');
		$mpdf->WriteHTML($stylesheet,1);	// The parameter 1 tells that this is css/style only and no body/html/text
		
		$mpdf->WriteHTML($this->_documentXML); /*Ñ„Ð¾Ñ€Ð¼Ð¸Ñ€ÑƒÐµÐ¼ pdf*/
		$pdf_name = "pdf_docs/probationassessment_" . $record . ".pdf";
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