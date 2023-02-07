<?php
/*+***********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************/
class PackingList_Print_View extends Vtiger_Print_View {
	
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
		// echo 'test'; exit;

		// ini_set('display_errors', 1);
		// error_reporting(E_ALL);

		global $adb;
		$moduleName = $request->getModule();
		$record = $request->get('record');
		$type = $request->get('type');
		$current_user = Users_Record_Model::getCurrentUserModel();
		$pl_info = Vtiger_Record_Model::getInstanceById($record, 'PackingList');
		$qt_owner_user_info = Users_Record_Model::getInstanceById($pl_info->get('assigned_user_id') , 'Users');
		/*if ($pl_info->get('contact_id') != ''){
		$contact_info = Vtiger_Record_Model::getInstanceById($pl_info->get('contact_id'), 'Contacts');
		$attn = $contact_info->get('firstname').' '.$contact_info->get('lastname');
		}*/

		$pdfFile = 'wc.html';
		if ($type == 'wc') $pdfFile = 'wc.html'; else $pdfFile = 'pl.html';			
		
		$document = $this->loadTemplate('printtemplates/PackingList/'.$pdfFile);

		$request_date = date("Y-m-d", strtotime($pl_info->get('createdtime')));
		$request_edit_date = date("Y-m-d", strtotime($pl_info->get('modifiedtime')));

		$this->setValue('record', $record, ENT_QUOTES, "UTF-8");
		$this->setValue('name', $pl_info->get('name'), ENT_QUOTES, "UTF-8");
		$this->setValue('modeOfTransport', $pl_info->get('cf_7000'), ENT_QUOTES, "UTF-8");
		$this->setValue('origin', $pl_info->get('cf_7062'), ENT_QUOTES, "UTF-8");
		$this->setValue('destination', $pl_info->get('cf_6998'), ENT_QUOTES, "UTF-8");
		$this->setValue('packingDate', $pl_info->get('cf_6988'), ENT_QUOTES, "UTF-8");
		$this->setValue('supervisorName', '', ENT_QUOTES, "UTF-8");

		// $this->setValue('employee_name', $qt_owner_user_info->get('last_name').' '.$qt_owner_user_info->get('first_name'), ENT_QUOTES, "UTF-8");


		include ('include/mpdf60/mpdf.php');

	if ($type == 'wc'){

		$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10, 'L'); /*Ð·Ð°Ð´Ð°ÐµÐ¼ Ñ„Ð¾Ñ€Ð¼Ð°Ñ‚, Ð¾Ñ‚ÑÑ‚ÑƒÐ¿Ñ‹ Ð¸.Ñ‚.Ð´.*/
		$mpdf->charset_in = 'utf8';
		$mpdf->list_indent_first_level = 0;
		

		$mpdf->SetHTMLHeader('<table width="100%" cellpadding="0" cellspacing="0">
			<tr>
				<td align="right"><img src="printtemplates/glklogo.jpg"/ width="160" height="30"></td>
			</tr>
		</table>');
		$mpdf->AddPage('L'); // Adds a new page in Landscape orientation
			// Formula
			$m3 = 0;
			$FKG = 167;
			$FLBS = 2.204;
			$FM3 = 35.31;
			$sumWeght = 0;
			$sumTare = 0;

			// Weight KGS Section
			$grossWeight = $pl_info->get('cf_7018');
			$tareWeight = $pl_info->get('cf_7022');

			$i = 0;
			$sumM3 = 0;
			$qItems = $adb->pquery("SELECT relcrmid
																	FROM `vtiger_crmentityrel`
																	WHERE `module` = 'PackingList' AND `crmid` = $record
																	AND `relmodule` = 'PackingListItem'");
			while ($r_tems = $adb->fetch_array($qItems)){
					$m3 = 0;					

					$packingListId = $r_tems['relcrmid'];
					$qPackingList = $adb->pquery("SELECT * FROM `vtiger_packinglistitemcf` WHERE `packinglistitemid` = $packingListId");
					$r_plist = $adb->fetch_array($qPackingList);
					$i++;
					$tare = $r_plist['cf_7032'];
					$weight = $r_plist['cf_7034'];

					$width  = $r_plist['cf_6982'];
					$height  = $r_plist['cf_6984'];
					$length  = $r_plist['cf_6986'];
					$volume  = $r_plist['cf_7066'];
					$m3 = round(($length * $width * $height), 2);
					$sumM3 += $m3;

					$sumTare += $r_plist['cf_7032'];
					$sumWeight += $r_plist['cf_7034'];

					$volWeight = $FKG * $m3;
					$itemsRowInfo .= "<tr>
						<td> $i </td>
						<td> $length </td>
						<td> $width </td>
						<td> $height </td>
						<td> $m3 </td>

						<td> $weight </td>
						<td> $tare </td>
						<td> $weight </td>
						<td> $volWeight </td>
					</tr>";
		

			}
			$this->setValue('itemsRows', $itemsRowInfo);

			// FOOTER
			// Total section
			$totalCuft = round(($sumM3 * $FM3), 2);
			$this->setValue('totalPCS', $pl_info->get('cf_7012'));
			$this->setValue('totalM3', $sumM3);
			$this->setValue('totalCuft', $totalCuft);

			$this->setValue('totalGWeightKGS', $sumWeight);
			$this->setValue('totalVolumeWeightKGS', ($FKG * $sumM3));
			$this->setValue('totalGWeightLBS', ($sumWeight * $FLBS));
			

			$this->setValue('totalTareWeightKGS', $sumTare);
			$this->setValue('totalTareWeightLBS', ($sumTare * $FLBS));

			$this->setValue('totalNetWeightKGs', ($sumWeight - $sumTare));
			$this->setValue('totalNetWeightLbS', (($sumWeight - $sumTare) * $FLBS));

			$stylesheet = file_get_contents('include/mpdf60/examples/mpdfstyletables.css');
			$mpdf->WriteHTML($stylesheet,1);	// The parameter 1 tells that this is css/style only and no body/html/text
			
			$mpdf->WriteHTML($this->_documentXML, 2); /*Ñ„Ð¾Ñ€Ð¼Ð¸Ñ€ÑƒÐµÐ¼ pdf*/
			$pdf_name = "pdf_docs/WeightCertificate_" . $record . ".pdf";
			$mpdf->Output($pdf_name, 'F');
	
			header('Location:' . $pdf_name);
			exit;

		} else if ($type == 'pl'){

		//            mPDF($mode='',$format='A4',$default_font_size=0,$default_font='',$mgl=15,$mgr=15,$mgt=16,$mgb=16,$mgh=9,$mgf=9, $orientation='P') {

			$mpdf = new mPDF('utf-8', 'A4', '10', '', 5, 5, 5, 5, 5, 5); /*Ð·Ð°Ð´Ð°ÐµÐ¼ Ñ„Ð¾Ñ€Ð¼Ð°Ñ‚, Ð¾Ñ‚ÑÑ‚ÑƒÐ¿Ñ‹ Ð¸.Ñ‚.Ð´.*/
			$mpdf->charset_in = 'utf8';
			$mpdf->list_indent_first_level = 0;
			
			$mpdf->SetHTMLHeader('<table width="100%" cellpadding="0" cellspacing="0">
			<tr>
				<td align="left"><img src="printtemplates/glklogo.jpg"/ width="160" height="30"></td>
			</tr>
		</table>');


		$i = 0;
		$itemsRowInfo = '';
		$qItems = $adb->pquery("SELECT relcrmid
																FROM `vtiger_crmentityrel`
																WHERE `module` = 'PackingList' AND `crmid` = $record 
																AND `relmodule` = 'PackingListItem'");
		while ($r_tems = $adb->fetch_array($qItems)){
				$m3 = 0;
				$sumM3 = 0;

				$packingListId = $r_tems['relcrmid'];
				$qPackingList = $adb->pquery("SELECT * FROM `vtiger_packinglistitemcf` WHERE `packinglistitemid` = $packingListId");
				$r_plist = $adb->fetch_array($qPackingList);
				$i++;
				$weight = $r_plist['cf_7034'];

				$width  = $r_plist['cf_6982'];
				$height  = $r_plist['cf_6984'];
				$length  = $r_plist['cf_6986'];

				$sizes = $length.' X '.$width.' X '.$height;
				$items = '';
				
				//Getting Packing List items
				$items = '';
				$qItemsRel = $adb->pquery("
						SELECT * 
						FROM `vtiger_crmentityrel` 
						WHERE `module` = 'PackingListItem' 
						AND crmid = $packingListId
						AND relmodule = 'PackingListDItem'");
				while ($r_itemsRel = $adb->fetch_array($qItemsRel)){ 
					$dItemID = $r_itemsRel['relcrmid'];

					$q_Dictionary = $adb->pquery("SELECT vtiger_packinglistdictionary.name, vtiger_packinglistditemcf.cf_7128
					FROM  vtiger_packinglistditemcf
					LEFT JOIN vtiger_packinglistdictionary ON vtiger_packinglistdictionary.packinglistdictionaryid = vtiger_packinglistditemcf.cf_7130
					WHERE vtiger_packinglistditemcf.packinglistditemid = $dItemID	");
					$r_dictionary = $adb->fetch_array($q_Dictionary);
					$items .= $r_dictionary['name'].' - ' . $r_dictionary['cf_7128'].', ';
				}


				$itemsRowInfo .= "<tr>
				<td class='extraBorder width: 10%'> $i </td> 
				<td class='extraBorder width: 10%'>  </td>
				<td class='extraBorder width: 40%'> $items </td>
				<td class='extraBorder width: 5%'> $weight </td>
				<td class='extraBorder width: 15%'> $sizes </td>
				<td class='extraBorder width: 5%'>  </td>

			</tr>";


		}

		
		$this->setValue('itemsRows', $itemsRowInfo);



/* 		$mpdf->SetHTMLFooter('<table width="100%" cellpadding="0" cellspacing="0">
		<tr><td width="40%" align="left" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">Printed: '.date('d.m.Y; H:i').' by '.$current_user->get('user_name').'</td>
		<td width="20%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">Page {PAGENO} of {nbpg}</td>
		<td width="40%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">&nbsp;</td>
		</table>'); */

		$stylesheet = file_get_contents('include/mpdf60/examples/mpdfstyletables_PL.css');
		$mpdf->WriteHTML($stylesheet,1);	// The parameter 1 tells that this is css/style only and no body/html/text
		
		$mpdf->WriteHTML($this->_documentXML, 2); /*Ñ„Ð¾Ñ€Ð¼Ð¸Ñ€ÑƒÐµÐ¼ pdf*/
		$pdf_name = "pdf_docs/PackingList_" . $record . ".pdf";
		$mpdf->Output($pdf_name, 'F');

		header('Location:' . $pdf_name);
		exit;

	}
}

	public function template ($strFilename) {
		$path = dirname($strFilename);
		$this->_tempFileName = $strFilename;
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

		if (!is_array($replace)) {
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