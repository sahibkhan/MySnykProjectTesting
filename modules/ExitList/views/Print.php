<?php
/*+***********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************/
class ExitList_Print_View extends Vtiger_Print_View {
	
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
		global $adb;
		$moduleName = $request->getModule();
		$record = $request->get('record');
		$module_data_info = Vtiger_Record_Model::getInstanceById($record, 'ExitList');		
		$document = $this->loadTemplate('printtemplates/ExitList/pdf.html');

		// $request_date = date("Y-m-d", strtotime($module_data_info->get('CreatedTime')));
		// $request_edit_date = date("Y-m-d", strtotime($module_data_info->get('ModifiedTime')));

		// feedback user info
		$this->setValue('name', $module_data_info->getDisplayValue('name'), ENT_QUOTES, "UTF-8");
		$this->setValue('position', $module_data_info->getDisplayValue('cf_7540'), ENT_QUOTES, "UTF-8");
		$this->setValue('department', $module_data_info->getDisplayValue('cf_7532'), ENT_QUOTES, "UTF-8");
		$this->setValue('date', $module_data_info->getDisplayValue('cf_7536'), ENT_QUOTES, "UTF-8");
		if ($module_data_info->get('cf_7534')){
			$userList = Users_Record_Model::getInstanceById($module_data_info->get('cf_7534'), 'UserList');
			$emailForward = $userList->getDisplayValue('cf_3355');
		} else {
			$emailForward = '';
		}
		
		$this->setValue('email', $emailForward, ENT_QUOTES, "UTF-8");

		$employeePositionName = strtolower($module_data_info->getDisplayValue('cf_7540'));
		$searchKeyword = 'driver';
		$pos = strpos($employeePositionName, $searchKeyword);
		if ($pos === false) $isDriver = false; else $isDriver = true;

		$exitListInstance = new ExitList();
		$exitListUsers = $exitListInstance->getExitListUsers($isDriver);
		
		foreach ($exitListUsers as $exitListUser){
			$approverName = $exitListUser['name'];
			$title = $exitListUser['title'];
			$userId = $exitListUser['id'];

			$sqlAdd = "SELECT vtiger_exitlistentriescf.*
			FROM `vtiger_crmentityrel` 
			INNER JOIN vtiger_exitlistentries ON vtiger_exitlistentries.exitlistentriesid = vtiger_crmentityrel.relcrmid
			INNER JOIN vtiger_exitlistentriescf ON vtiger_exitlistentriescf.exitlistentriesid = vtiger_crmentityrel.relcrmid
			WHERE vtiger_crmentityrel.`relmodule` = 'ExitListEntries' AND vtiger_exitlistentries.name = ?
			AND vtiger_crmentityrel.crmid = ?";

			$result_m = $adb->pquery($sqlAdd, array($userId, $record));
			$itemName = trim($adb->query_result($result_m, 0, 'cf_7632'));
			$count = trim($adb->query_result($result_m, 0, 'cf_7638'));
			$itemCost = trim($adb->query_result($result_m, 0, 'cf_7634'));
			$requstStatus = $adb->query_result($result_m, 0, 'cf_7640');
			$signatureDate = trim($adb->query_result($result_m, 0, 'cf_7628'));
			$signature = '';
			
			if ($requstStatus == 'Approve') $signature .= 'Approved'; else if ($requstStatus == 'Decline') $signature .= 'Declined';

			if ($signatureDate){
				$signature .= '/'.$signatureDate;
			}

			$exitlist_details .= "<tr>
				<td width='276' valign='top'>".$title.' - <b>'.$approverName."</b></td>
				<td width='108' valign='top'>	".$itemName."</td>
				<td width='64' valign='top'>$count</td>
				<td width='134' valign='top'>$signature	</td>
				<td width='111' valign='top'>	$itemCost </td>
			</tr>";

		}
		$this->setValue('exitlist_details', $exitlist_details, ENT_QUOTES, "UTF-8");

		//$raised_for_details .= "";

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
		
		$mpdf->WriteHTML($this->_documentXML); /*Ñ„Ð¾Ñ€Ð¼Ð¸Ñ€ÑƒÐµÐ¼ pdf*/
		$pdf_name = "pdf_docs/exitlist_" . $record . ".pdf";
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