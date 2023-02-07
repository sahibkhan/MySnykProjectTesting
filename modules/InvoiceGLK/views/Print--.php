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

  
	function num2str($num) {
		$nul='Ð½Ð¾Ð»ÑŒ';
		$ten=array(
			array('','Ð¾Ð´Ð¸Ð½','Ð´Ð²Ð°','Ñ‚Ñ€Ð¸','Ñ‡ÐµÑ‚Ñ‹Ñ€Ðµ','Ð¿ÑÑ‚ÑŒ','ÑˆÐµÑÑ‚ÑŒ','ÑÐµÐ¼ÑŒ', 'Ð²Ð¾ÑÐµÐ¼ÑŒ','Ð´ÐµÐ²ÑÑ‚ÑŒ'),
			array('','Ð¾Ð´Ð½Ð°','Ð´Ð²Ðµ','Ñ‚Ñ€Ð¸','Ñ‡ÐµÑ‚Ñ‹Ñ€Ðµ','Ð¿ÑÑ‚ÑŒ','ÑˆÐµÑÑ‚ÑŒ','ÑÐµÐ¼ÑŒ', 'Ð²Ð¾ÑÐµÐ¼ÑŒ','Ð´ÐµÐ²ÑÑ‚ÑŒ'),
		);
		$a20=array('Ð´ÐµÑÑÑ‚ÑŒ','Ð¾Ð´Ð¸Ð½Ð½Ð°Ð´Ñ†Ð°Ñ‚ÑŒ','Ð´Ð²ÐµÐ½Ð°Ð´Ñ†Ð°Ñ‚ÑŒ','Ñ‚Ñ€Ð¸Ð½Ð°Ð´Ñ†Ð°Ñ‚ÑŒ','Ñ‡ÐµÑ‚Ñ‹Ñ€Ð½Ð°Ð´Ñ†Ð°Ñ‚ÑŒ' ,'Ð¿ÑÑ‚Ð½Ð°Ð´Ñ†Ð°Ñ‚ÑŒ','ÑˆÐµÑÑ‚Ð½Ð°Ð´Ñ†Ð°Ñ‚ÑŒ','ÑÐµÐ¼Ð½Ð°Ð´Ñ†Ð°Ñ‚ÑŒ','Ð²Ð¾ÑÐµÐ¼Ð½Ð°Ð´Ñ†Ð°Ñ‚ÑŒ','Ð´ÐµÐ²ÑÑ‚Ð½Ð°Ð´Ñ†Ð°Ñ‚ÑŒ');
		$tens=array(2=>'Ð´Ð²Ð°Ð´Ñ†Ð°Ñ‚ÑŒ','Ñ‚Ñ€Ð¸Ð´Ñ†Ð°Ñ‚ÑŒ','ÑÐ¾Ñ€Ð¾Ðº','Ð¿ÑÑ‚ÑŒÐ´ÐµÑÑÑ‚','ÑˆÐµÑÑ‚ÑŒÐ´ÐµÑÑÑ‚','ÑÐµÐ¼ÑŒÐ´ÐµÑÑÑ‚' ,'Ð²Ð¾ÑÐµÐ¼ÑŒÐ´ÐµÑÑÑ‚','Ð´ÐµÐ²ÑÐ½Ð¾ÑÑ‚Ð¾');
		$hundred=array('','ÑÑ‚Ð¾','Ð´Ð²ÐµÑÑ‚Ð¸','Ñ‚Ñ€Ð¸ÑÑ‚Ð°','Ñ‡ÐµÑ‚Ñ‹Ñ€ÐµÑÑ‚Ð°','Ð¿ÑÑ‚ÑŒÑÐ¾Ñ‚','ÑˆÐµÑÑ‚ÑŒÑÐ¾Ñ‚', 'ÑÐµÐ¼ÑŒÑÐ¾Ñ‚','Ð²Ð¾ÑÐµÐ¼ÑŒÑÐ¾Ñ‚','Ð´ÐµÐ²ÑÑ‚ÑŒÑÐ¾Ñ‚');
		$unit=array( // Units
			array('Ñ‚Ð¸Ñ‹Ð½' ,'Ñ‚Ð¸Ñ‹Ð½Ñ‹' ,'Ñ‚Ð¸Ñ‹Ð½',	 1),
			array('Ñ‚ÐµÐ½Ð³Ðµ'   ,'Ñ‚ÐµÐ½Ð³Ðµ'   ,'Ñ‚ÐµÐ½Ð³Ðµ'    ,0),
			array('Ñ‚Ñ‹ÑÑÑ‡Ð°'  ,'Ñ‚Ñ‹ÑÑÑ‡Ð¸'  ,'Ñ‚Ñ‹ÑÑÑ‡'     ,1),
			array('Ð¼Ð¸Ð»Ð»Ð¸Ð¾Ð½' ,'Ð¼Ð¸Ð»Ð»Ð¸Ð¾Ð½Ð°','Ð¼Ð¸Ð»Ð»Ð¸Ð¾Ð½Ð¾Ð²' ,0),
			array('Ð¼Ð¸Ð»Ð»Ð¸Ð°Ñ€Ð´','Ð¼Ð¸Ð»Ð¸Ð°Ñ€Ð´Ð°','Ð¼Ð¸Ð»Ð»Ð¸Ð°Ñ€Ð´Ð¾Ð²',0),
		);
		//
		list($rub,$kop) = explode('.',sprintf("%015.2f", floatval($num)));
		$out = array();
		if (intval($rub)>0) {
			foreach(str_split($rub,3) as $uk=>$v) { // by 3 symbols
				if (!intval($v)) continue;
				$uk = sizeof($unit)-$uk-1; // unit key
				$gender = $unit[$uk][3];
				list($i1,$i2,$i3) = array_map('intval',str_split($v,1));
				// mega-logic
				$out[] = $hundred[$i1]; # 1xx-9xx
				if ($i2>1) $out[]= $tens[$i2].' '.$ten[$gender][$i3]; # 20-99
				else $out[]= $i2>0 ? $a20[$i3] : $ten[$gender][$i3]; # 10-19 | 1-9
				// units without rub & kop
				if ($uk>1) $out[]= $this->morph($v,$unit[$uk][0],$unit[$uk][1],$unit[$uk][2]);
			} //foreach
		}
		else $out[] = $nul;
		$out[] = ') '.$this->morph(intval($rub), $unit[1][0],$unit[1][1],$unit[1][2]); // rub
		$out[] = $kop.' '.$this->morph($kop,$unit[0][0],$unit[0][1],$unit[0][2]); // kop
		return trim(preg_replace('/ {2,}/', ' ', join(' ',$out)));
	}
	
	/**
	 * Ð¡ÐºÐ»Ð¾Ð½ÑÐµÐ¼ ÑÐ»Ð¾Ð²Ð¾Ñ„Ð¾Ñ€Ð¼Ñƒ
	 * @ author runcore
	 */
	function morph($n, $f1, $f2, $f5) {
		$n = abs(intval($n)) % 100;
		if ($n>10 && $n<20) return $f5;
		$n = $n % 10;
		if ($n>1 && $n<5) return $f2;
		if ($n==1) return $f1;
		return $f5;
	}
	
	function getCountry($country){
		global $adb;
		$s_fcountry = $adb->pquery("SELECT * FROM `countries` WHERE `country_code` = ?", array($country));
		$r_fcountry = $adb->query_result($s_fcountry, 0, 'country_name');
		return $r_fcountry;
	}


	function dateConvert($a) {
  	$month = [0,'Ð¯Ð½Ð²Ð°Ñ€Ñ','Ð¤ÐµÐ²Ñ€Ð°Ð»Ñ','ÐœÐ°Ñ€Ñ‚Ð°','ÐÐ¿Ñ€ÐµÐ»Ñ','ÐœÐ°Ñ','Ð˜ÑŽÐ½Ñ','Ð˜ÑŽÐ»Ñ','ÐÐ²Ð³ÑƒÑÑ‚Ð°','Ð¡ÐµÐ½Ñ‚ÑÐ±Ñ€Ñ','ÐžÐºÑ‚ÑÐ±Ñ€Ñ','ÐÐ¾ÑÐ±Ñ€Ñ','Ð”ÐµÐºÐ°Ð±Ñ€Ñ'];
  	$b = explode('-',$a);
  	$c = 'Â«'.$b[2].'Â» '.$month[(int)$b[1]].' '.$b[0];
  	return $c;
  }
	


	function getServiceAgreement($accountId){
		global $adb;
		$options = array();
		$querySA = $adb->pquery("SELECT relcrmid FROM `vtiger_crmentityrel` WHERE module = 'Accounts' AND relmodule = 'ServiceAgreement' AND  crmid = ?", array($accountId));
		$relcrmid = $adb->query_result($querySA, 0, 'relcrmid');

		if ($relcrmid > 0){
			$queryService = $adb->pquery("
				Select vtiger_serviceagreementcf.cf_6018, vtiger_serviceagreement.name
				FROM vtiger_serviceagreement
				INNER JOIN vtiger_serviceagreementcf ON vtiger_serviceagreementcf.serviceagreementid = vtiger_serviceagreement.serviceagreementid
				WHERE vtiger_serviceagreement.serviceagreementid = ?", array($relcrmid));
			$agency_agreement = $adb->query_result($queryService, 0, 'name');
			$agency_agreement_date = $adb->query_result($queryService, 0, 'cf_6018');
			$options['agency_agreement'] = $agency_agreement;
			$options['agency_agreement_date'] = $this->dateConvert($agency_agreement_date);
		}
		
		return $options;
	}

	//function getServiceAgreementNew($accountId){//this account id is billtoid //jobid,id of invoice_approval_date record
	function getServiceAgreementNew($recordid){ // appendix id
		global $adb;
		$options = array();  

			$queryService = $adb->pquery("SELECT vtiger_serviceagreement.name as agreement_no, vtiger_serviceagreementcf.cf_6018 as agreement_date, vtiger_serviceagreementcf.cf_6020 as expiry_date, vtiger_serviceagreementcf.cf_6026 as globalink_company FROM vtiger_serviceagreement
													INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_serviceagreement.serviceagreementid
													INNER JOIN vtiger_serviceagreementcf ON vtiger_serviceagreementcf.serviceagreementid = vtiger_serviceagreement.serviceagreementid
													WHERE vtiger_crmentity.deleted=0 
																									
													AND vtiger_serviceagreementcf.cf_6068='Freight Forwarding'

													AND vtiger_serviceagreementcf.cf_6094 = '".$accountId."'
													
													order by vtiger_crmentity.createdtime DESC limit 1");

			$agency_agreement = $adb->query_result($queryService, 0, 'agreement_no');
			$agency_agreement_date = $adb->query_result($queryService, 0, 'agreement_date');
			$options['agency_agreement'] = $agency_agreement;
			$options['agency_agreement_date'] = $this->dateConvert($agency_agreement_date); 

		return $options;
	}


	function getChartOfAccount($id){
		global $adb;
		$queryCharges = $adb->pquery("SELECT `name` FROM `vtiger_chartofaccount` WHERE `chartofaccountid` = ? LIMIT 1", array($id));
		$name = $adb->query_result($queryCharges, 0, 'name');
		return $name;
	}


	function getJobFileCharges($jobFileId){
		global $adb;
 
		$queryCharges = $adb->pquery("
			SELECT vtiger_jobexpencereportcf.cf_1232 as sell_customer_currency_gross, 
			vtiger_jobexpencereportcf.cf_1238 as sell_local_currency_gross,
			vtiger_jobexpencereportcf.cf_1240 as sell_local_currency_net, 
			vtiger_jobexpencereportcf.cf_1242 as expected_sell_local_currency_net,
			vtiger_jobexpencereportcf.cf_1244 as variation_expected_and_actual_selling,
			vtiger_jobexpencereportcf.cf_1246 as variation_expect_and_actual_profit,
			vtiger_jobexpencereportcf.cf_1355 as sell_invoice_date,
			vtiger_jobexpencereportcf.cf_1234 as currency_id,
			vtiger_jobexpencereportcf.cf_1365 as description,
			vtiger_cf_1234.cf_1234 as currencycode,
			vtiger_jobexpencereport.invoice_approved_date as invoice_approved_date,
			vtiger_jobexpencereportcf.cf_1455,
			vtiger_jobexpencereportcf.cf_1445

			FROM `vtiger_jobexpencereport` 
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
			INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid ) 
			Left JOIN vtiger_jobexpencereportcf as vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid	
			Left JOIN vtiger_cf_1234 ON vtiger_cf_1234.cf_1234id = vtiger_jobexpencereportcf.cf_1234	

			WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid = ? AND vtiger_crmentityrel.module='Job'
			AND  vtiger_jobexpencereportcf.cf_1250='Approved' 
			AND vtiger_crmentityrel.relmodule='Jobexpencereport' AND vtiger_jobexpencereportcf.cf_1457='Selling'", array($jobFileId));

		$nRows = $adb->num_rows($queryCharges);
		for ($i=0; $i<$nRows; $i++){
			$chargeId = $adb->query_result($queryCharges, $i, 'cf_1455');
			//$chargeCost = $adb->query_result($queryCharges, $i, 'sell_local_currency_net');
			$chargeCost = $adb->query_result($queryCharges, $i, 'sell_local_currency_gross');
			$currencyCode = $adb->query_result($queryCharges, $i, 'currencycode');
			$description = $adb->query_result($queryCharges, $i, 'description');
			//$invoiceDate = $adb->query_result($queryCharges, $i, 'sell_invoice_date');
			$invoiceDate = $adb->query_result($queryCharges, $i, 'invoice_approved_date');
 			$billtoid = $adb->query_result($queryCharges, $i, 'cf_1445');
			$chargeName = $this->getChartOfAccount($chargeId);			
			$charges[] = array('chargeName' => $chargeName, 'currencyCode' => $currencyCode, 'chargeCost' => $chargeCost, 'description' => $description, "invoiceDate" => $invoiceDate, "billtoid" => $billtoid);
		}
		return $charges;		
	}


	public function word_print(Vtiger_Request $request){

			global $adb;

	/*  ini_set('display_errors', 1);
			error_reporting(E_ALL);
	*/
		
		require_once 'libraries/PHPWord/PHPWord.php';
		require_once('Petrovich.php');
		$petrovich = new Petrovich(Petrovich::GENDER_MALE); 



		$sectionStyle = array(
			'orientation' => 'portrain',
			'marginTop' => 200,
			'colsNum' => 2,
		);
			
		$PHPWord = new PHPWord();
		$section = $PHPWord->createSection($sectionStyle); // Creating new word page
		$appendixInfo = Vtiger_Record_Model::getInstanceById($request->get('record'), 'InvoiceGLK');
		$jobFileId = $appendixInfo->get('cf_5079');
		$contactId = $appendixInfo->get('cf_3427');
		/*$invoiceDateFromAppendix = $appendixInfo->get('cf_1439');
		$invoiceDate = $this->dateConvert($invoiceDateFromAppendix);*/

		$customer_name = '';

		// Getting job file info
		if ($jobFileId){
			$jobFileInfo = Vtiger_Record_Model::getInstanceById($jobFileId, 'Job');
			$jobFileRef = $jobFileInfo->get('cf_1198');
			$commodity = $jobFileInfo->get('cf_7900') ? $jobFileInfo->get('cf_7900') : $jobFileInfo->get('cf_1518');
			$noOfPieces = $jobFileInfo->get('cf_1429');

			// cf_1520
			// Volume cf_1086
			if ($jobFileInfo->get('cf_4945') > 0){
				$weight = $jobFileInfo->get('cf_4945') . 'ÐºÐ³.';

			} else if ($jobFileInfo->get('cf_1084') >= $jobFileInfo->get('cf_1086')){

				// Weight				
				if ($jobFileInfo->get('cf_1520') == 'KG') {
					$weightUnit = 'ÐºÐ³.';
				} else if ($jobFileInfo->get('cf_1520') == 'LBS') {
					$weightUnit = 'Ñ„ÑƒÐ½Ñ‚';
				} else if ($jobFileInfo->get('cf_1520') == 'TON') {
					$weightUnit = 'Ñ‚Ð¾Ð½Ð½';
				}
				$weight = $jobFileInfo->get('cf_1084').' '.$weightUnit;
				
			} else if ($jobFileInfo->get('cf_1084') <= $jobFileInfo->get('cf_1086')){
				
				// Volume 
				if ($jobFileInfo->get('cf_1522') == 'KG') {
					$weightUnit = 'ÐºÐ³.';
				} else if ($jobFileInfo->get('cf_1522') == 'CBM') {
					$weightUnit = 'Ñ„ÑƒÐ½Ñ‚';			
				}
				$weight = $jobFileInfo->get('cf_1086').' '.$weightUnit;

			}
			
			/*
			if ($jobFileInfo->get('cf_1441')){
				$customer_name = strip_tags($jobFileInfo->getDisplayValue('cf_1441'));
				$accountInfo = Vtiger_Record_Model::getInstanceById($jobFileInfo->get('cf_1441'), 'Accounts');
				$accountLocation = $accountInfo->get('bill_country').', '.$accountInfo->get('bill_city');
			}
			*/
			if ($jobFileInfo->get('cf_1074')){
				$customer_name = strip_tags($jobFileInfo->getDisplayValue('cf_1074'));
				$accountLocation = $jobFileInfo->getDisplayValue('cf_1506').', '.$jobFileInfo->getDisplayValue('cf_1510');
				
				$accountInfo = Vtiger_Record_Model::getInstanceById($jobFileInfo->get('cf_1441'), 'Accounts');
				//$accountLocation = $accountInfo->get('bill_country').', '.$accountInfo->get('bill_city');
				
				$customer_legal_name = strip_tags($jobFileInfo->getDisplayValue('cf_2395'));
			}

		} else {
			$jobFileRef = '';
		}

		// Getting Agreement Info
/*
		$agreementInfo = $this->getServiceAgreement($jobFileInfo->get('cf_1441'));	
		if ($agreementInfo){
			$agreement = $agreementInfo['agency_agreement'].' Ð¾Ñ‚ '.$agreementInfo['agency_agreement_date'];
		} else {
			$agreement = $accountInfo->get('cf_1853').' Ð¾Ñ‚ '.$this->dateConvert($accountInfo->get('cf_1859'));	
		}
*/

		// Getting job file info
		$contactType = 'Ð”Ð¸Ñ€ÐµÐºÑ‚Ð¾Ñ€Ð°';
		$appType = 'ÑƒÑÑ‚Ð°Ð²Ð°';

 		if ($contactId){

			$contactInfo = Vtiger_Record_Model::getInstanceById($contactId, 'Contacts');
			
			$f_name = $contactInfo->get('cf_6050');
			$f_lastname = $contactInfo->get('cf_6052');
			

			if ((mb_strlen($f_name) > 0) && (mb_strlen($f_lastname) > 0)){
				$ax_Contact_person = $petrovich->firstname($f_name, Petrovich::CASE_GENITIVE);
				$ax_Contact_person .= ' '.$petrovich->firstname($f_lastname, Petrovich::CASE_GENITIVE);		
				
				// For other
				$ax_Contact = $ax_Contact_person;

				// For Signature
				$signatureContact = $f_name.' '.$f_lastname;
				
			} else {
				$ax_Contact_person = $contactInfo->get('firstname').' '.$contactInfo->get('firstname');
				// For other
				$ax_Contact = $ax_Contact_person;
				$signatureContact = $ax_Contact_person;
			}


			$sqlContact = $adb->pquery("SELECT `cf_5357`,`cf_5359`,`cf_5513`,`cf_5515`,`cf_5517` FROM `vtiger_contactscf` WHERE `contactid` = ?  LIMIT 1", array($contactId));
			$powerOfAttorney = $adb->query_result($sqlContact, 0, 'cf_5513');
			$contactPosition = $adb->query_result($sqlContact, 0, 'cf_5515');
			$paymentType = $adb->query_result($sqlContact, 0, 'cf_5359');
			$typeOfPayment = $adb->query_result($sqlContact, 0, 'cf_5357');

			if ($contactPosition == 'CFO') {
				$a = explode('-',$powerOfAttorney);
				$a = $a[2].'.'.$a[1].'.'.$a[0];
				//$a = implode('.',explode('-',$arr['cf_5513']));
				$ax_Contact = 'Ð¤Ð¸Ð½Ð°Ð½ÑÐ¾Ð²Ð³Ð¾ Ð”Ð¸Ñ€ÐµÐºÑ‚Ð¾Ñ€Ð° '.$ax_Contact.', Ð´ÐµÐ¹ÑÑ‚Ð²ÑƒÑŽÑ‰ÐµÐ³Ð¾ Ð½Ð° Ð¾ÑÐ½Ð¾Ð²Ð°Ð½Ð¸Ð¸ Ð“ÐµÐ½ÐµÑ€Ð°Ð»ÑŒÐ½Ð¾Ð¹ Ð”Ð¾Ð²ÐµÑ€ÐµÐ½Ð½Ð¾ÑÑ‚Ð¸ Ð¾Ñ‚ '.$a;
				$owner_status = 'Ð¤Ð¸Ð½Ð°Ð½ÑÐ¾Ð²Ñ‹Ð¹ Ð”Ð¸Ñ€ÐµÐºÑ‚Ð¾Ñ€';
			} elseif ($contactPosition == 'Director') {			
				$owner_status = 'Ð”Ð¸Ñ€ÐµÐºÑ‚Ð¾Ñ€';
				$ax_Contact = 'Ð”Ð¸Ñ€ÐµÐºÑ‚Ð¾Ñ€Ð° '.$ax_Contact.', Ð´ÐµÐ¹ÑÑ‚Ð²ÑƒÑŽÑ‰ÐµÐ³Ð¾ Ð½Ð° Ð¾ÑÐ½Ð¾Ð²Ð°Ð½Ð¸Ð¸ Ð£ÑÑ‚Ð°Ð²Ð°';
			} elseif ($contactPosition == 'President') {
				$owner_status = 'ÐŸÑ€ÐµÐ·Ð¸Ð´ÐµÐ½Ñ‚';
				$ax_Contact = 'ÐŸÑ€ÐµÐ·Ð¸Ð´ÐµÐ½Ñ‚Ð° '.$ax_Contact.', Ð´ÐµÐ¹ÑÑ‚Ð²ÑƒÑŽÑ‰ÐµÐ³Ð¾ Ð½Ð° Ð¾ÑÐ½Ð¾Ð²Ð°Ð½Ð¸Ð¸ Ð£ÑÑ‚Ð°Ð²Ð°';
			} elseif ($contactPosition == 'CEO') {
				$owner_status = 'Ð“ÐµÐ½ÐµÑ€Ð°Ð»ÑŒÐ½Ñ‹Ð¹ Ð´Ð¸Ñ€ÐµÐºÑ‚Ð¾Ñ€';
				$ax_Contact = 'Ð“ÐµÐ½ÐµÑ€Ð°Ð»ÑŒÐ½Ð¾Ð³Ð¾ Ð”Ð¸Ñ€ÐµÐºÑ‚Ð¾Ñ€Ð° '.$ax_Contact.', Ð´ÐµÐ¹ÑÑ‚Ð²ÑƒÑŽÑ‰ÐµÐ³Ð¾ Ð½Ð° Ð¾ÑÐ½Ð¾Ð²Ð°Ð½Ð¸Ð¸ Ð£ÑÑ‚Ð°Ð²Ð°';
			} elseif ($contactPosition == 'Regional Representative'){

				$owner_status = 'Ð ÐµÐ³Ð¸Ð¾Ð½Ð°Ð»ÑŒÐ½Ñ‹Ð¹ Ð¿Ñ€ÐµÐ´ÑÑ‚Ð°Ð²Ð¸Ñ‚ÐµÐ»ÑŒ';
				$ax_Contact = 'Ð ÐµÐ³Ð¸Ð¾Ð½Ð°Ð»ÑŒÐ½Ð¾Ð³Ð¾ Ð¿Ñ€ÐµÐ´ÑÑ‚Ð°Ð²Ð¸Ñ‚ÐµÐ»Ñ '.$ax_Contact.', Ð´ÐµÐ¹ÑÑ‚Ð²ÑƒÑŽÑ‰ÐµÐ³Ð¾ Ð½Ð° Ð¾ÑÐ½Ð¾Ð²Ð°Ð½Ð¸Ð¸ Ð£ÑÑ‚Ð°Ð²Ð°';
				
			} elseif ($contactPosition == 'Attorney'){

				$owner_status = 'ÐŸÐ¾Ð²ÐµÑ€ÐµÐ½Ð½Ð¾Ðµ Ð»Ð¸Ñ†Ð¾';
				$ax_Contact = 'ÐŸÐ¾Ð²ÐµÑ€ÐµÐ½Ð½Ð¾Ð³Ð¾ Ð»Ð¸Ñ†Ð° '.$ax_Contact.', Ð´ÐµÐ¹ÑÑ‚Ð²ÑƒÑŽÑ‰ÐµÐ³Ð¾ Ð½Ð° Ð¾ÑÐ½Ð¾Ð²Ð°Ð½Ð¸Ð¸ Ð£ÑÑ‚Ð°Ð²Ð°';

		  } elseif ($contactPosition == 'Managing Partner'){

				$owner_status = 'Ð£Ð¿Ñ€Ð°Ð²Ð»ÑÑŽÑ‰Ð¸Ð¹ Ð¿Ð°Ñ€Ñ‚Ð½ÐµÑ€';
				$ax_Contact = 'Ð£Ð¿Ñ€Ð°Ð²Ð»ÑÑŽÑ‰ÐµÐ³Ð¾ Ð¿Ð°Ñ€Ñ‚Ð½ÐµÑ€Ð° '.$ax_Contact.', Ð´ÐµÐ¹ÑÑ‚Ð²ÑƒÑŽÑ‰ÐµÐ³Ð¾ Ð½Ð° Ð¾ÑÐ½Ð¾Ð²Ð°Ð½Ð¸Ð¸ Ð£ÑÑ‚Ð°Ð²Ð°';
		  } elseif ($contactPosition == 'Manager'){

				$owner_status = 'Ð£Ð¿Ñ€Ð°Ð²Ð»ÑÑŽÑ‰Ð¸Ð¹';
				$ax_Contact = 'Ð£Ð¿Ñ€Ð°Ð²Ð»ÑÑŽÑ‰ÐµÐ³Ð¾ '.$ax_Contact.', Ð´ÐµÐ¹ÑÑ‚Ð²ÑƒÑŽÑ‰ÐµÐ³Ð¾ Ð½Ð° Ð¾ÑÐ½Ð¾Ð²Ð°Ð½Ð¸Ð¸ Ð£ÑÑ‚Ð°Ð²Ð°';
		  } elseif ($contactPosition == 'Leader'){

				$owner_status = 'Ð ÑƒÐºÐ¾Ð²Ð¾Ð´Ð¸Ñ‚ÐµÐ»ÑŒ ';
				$ax_Contact = 'Ð ÑƒÐºÐ¾Ð²Ð¾Ð´Ð¸Ñ‚ÐµÐ»Ñ '.$ax_Contact.', Ð´ÐµÐ¹ÑÑ‚Ð²ÑƒÑŽÑ‰ÐµÐ³Ð¾ Ð½Ð° Ð¾ÑÐ½Ð¾Ð²Ð°Ð½Ð¸Ð¸ Ð£ÑÑ‚Ð°Ð²Ð°';

			} elseif ($contactPosition == 'Other') {
				$a = explode('-',$powerOfAttorney);
				$a = $a[2].'.'.$a[1].'.'.$a[0];
				$ax_Contact = $ax_Contact.', Ð´ÐµÐ¹ÑÑ‚Ð²ÑƒÑŽÑ‰ÐµÐ³Ð¾ Ð½Ð° Ð¾ÑÐ½Ð¾Ð²Ð°Ð½Ð¸Ð¸ Ð“ÐµÐ½ÐµÑ€Ð°Ð»ÑŒÐ½Ð¾Ð¹ Ð”Ð¾Ð²ÐµÑ€ÐµÐ½Ð½Ð¾ÑÑ‚Ð¸ Ð¾Ñ‚ '.$a;
				$owner_status = '';
			}


			$invoiceAmount = 0;
			$chargeList = $this->getJobFileCharges($jobFileId);	
			$invoiceDateFromExpenseArr = array();
			
			foreach ($chargeList as $charge){
				$invoiceAmount += $charge['chargeCost'];
				//if (!empty($charge['invoiceDate'])) $invoiceDateFromExpense = $charge['invoiceDate'];
				if (!empty($charge['invoiceDate'])) $invoiceDateFromExpenseArr[] = $charge['invoiceDate'];
			}
			
			if (count($invoiceDateFromExpenseArr)>0) {
				$invoiceDateFromExpense = current($invoiceDateFromExpenseArr);
			}

			$billtoid = $chargeList[0]['billtoid']; //get first bill to id

			//if ( !$agreementInfo and empty($accountInfo->get('cf_1853')) ) {
				//$agreementInfoNew = $this->getServiceAgreementNew($request->get('record'));
			//echo $appendixInfo->get('cf_7920');
			//die('die');
			//$agreementInfoNew = Vtiger_Record_Model::getInstanceById($appendixInfo->get('cf_7920'), 'ServiceAgreement');
			
			if ( !empty($appendixInfo->get('cf_7920')) ) {
				$agreementInfoNew = Vtiger_Record_Model::getInstanceById($appendixInfo->get('cf_7920'), 'ServiceAgreement');
				//echo $agreementInfoNew->get('name');
				//die('11');
				if ( !empty($agreementInfoNew->get('name')) ) {
					$agreement = $agreementInfoNew->get('name').' Ð¾Ñ‚ '. $this->dateConvert($agreementInfoNew->get('cf_6020'));
				}
			}
				//if ( isset($agreementInfoNew->get('name') ) ) {
					//$agreement = $agreementInfoNew->get('name').' Ð¾Ñ‚ '.$agreementInfoNew->get('cf_6020');
				//}
			//}


			$invoiceAmountFormat = number_format($invoiceAmount);
			$invoiceDate = $this->dateConvert($invoiceDateFromExpense);

			$paymentTypeConditionArray = array();
			$paymentTypeConditionArray = $this->splitTextByCondition($paymentType);

		} // Contact ID


		
		
	  // Get insurance info
	  $sql_insuranceRel = "SELECT * FROM `vtiger_crmentityrel` WHERE `crmid` = ? AND `module` = ? AND `relmodule` = ?";
	  $r_insuranceRel = $adb->pquery($sql_insuranceRel, array($jobFileId, 'Job', 'CargoInsurance'));
	  $insurance_id = $adb->query_result($r_insuranceRel, 0, 'relcrmid');	  
		$n_insuranceRel = $adb->num_rows($r_insuranceRel);
	  $is_insurance = 0;

 	  if ($n_insuranceRel > 0){		  

		  $sql_insuranceInfo = $adb->pquery("SELECT * FROM vtiger_cargoinsurancecf WHERE `cargoinsuranceid` = ?", array($insurance_id));
		  $insuranceAmount = $adb->query_result($sql_insuranceInfo, 0, 'cf_3631');
		  $insurance_amount = number_format($insuranceAmount, 2 , '.' , ' ');		 
			
			$f_country = $adb->query_result($sql_insuranceInfo, 0, 'cf_3605');
			$t_country = $adb->query_result($sql_insuranceInfo, 0, 'cf_3609');

		  // 
		  $fromCity = $adb->query_result($sql_insuranceInfo, 0, 'cf_3607');
		  $toCity = $adb->query_result($sql_insuranceInfo, 0, 'cf_3611');
		  $globalinkPremuim = $adb->query_result($sql_insuranceInfo, 0, 'cf_3645');
		  

		  $globalink_premium = number_format($globalinkPremuim, 2 , '.' , ' ');
		  $is_insurance = 1;
	  }

		$PHPWord->setDefaultFontSize(9);
		// template variables
		$job = $jobFileRef;
		$appType = '';

		$textCenter = array('align' => 'center', 'lineHeight' => 1, 'spaceBefore' => 100,'spaceAfter' => 100);
		$boldText = array('bold' => true);

		// title
		$section->addText("ÐŸÑ€Ð¸Ð»Ð¾Ð¶ÐµÐ½Ð¸Ðµ $job", $boldText, $textCenter);
		$section->addText("Ðº Ð”Ð¾Ð³Ð¾Ð²Ð¾Ñ€Ñƒ Ð½Ð° Ñ‚Ñ€Ð°Ð½ÑÐ¿Ð¾Ñ€Ñ‚Ð½Ð¾-ÑÐºÑÐ¿ÐµÐ´Ð¸Ñ‚Ð¾Ñ€ÑÐºÐ¾Ðµ Ð¾Ð±ÑÐ»ÑƒÐ¶Ð¸Ð²Ð°Ð½Ð¸Ðµ", $boldText, $textCenter);
		$section->addText($agreement, $boldText, $textCenter);
		$section->addTextBreak();
		$section->addTextBreak();		
		$textRun = $section->createTextRun();
		
		$textRun->addText("Ð³. ÐÐ»Ð¼Ð°Ñ‚Ñ‹", $boldText);
		$textRun->addText("                                                                                                                         ".$invoiceDate."  Ð³.", $boldText);
				
		$textRun1 = $section->createTextRun();
		//$textRun1->addText($customer_name, $boldText);
		$textRun1->addText($customer_legal_name, $boldText);
		
		$textRun1->addText(" Ð¸Ð¼ÐµÐ½ÑƒÐµÐ¼Ð¾Ðµ Ð² Ð´Ð°Ð»ÑŒÐ½ÐµÐ¹ÑˆÐµÐ¼ ");
		$textRun1->addText("Â«ÐšÐ»Ð¸ÐµÐ½Ñ‚Â», ", $boldText);
		$textRun1->addText("Ð² Ð»Ð¸Ñ†Ðµ $ax_Contact, Ñ Ð¾Ð´Ð½Ð¾Ð¹ ÑÑ‚Ð¾Ñ€Ð¾Ð½Ñ‹, Ð¸ ");
		$textRun1->addText("Ð¢ÐžÐž Â«Ð“Ð»Ð¾Ð±Ð°Ð»Ð¸Ð½Ðº Ð¢Ñ€Ð°Ð½ÑÐ¿Ð¾Ñ€Ñ‚ÑÐ¹ÑˆÐ½ ÑÐ½Ð´ Ð›Ð¾Ð´Ð¶Ð¸ÑÑ‚Ð¸ÐºÑ Ð’Ð¾Ñ€Ð»Ð´Ð²Ð°Ð¹Ð´Â»,", $boldText);
		$textRun1->addText("Ð¸Ð¼ÐµÐ½ÑƒÐµÐ¼Ð¾Ðµ Ð² Ð´Ð°Ð»ÑŒÐ½ÐµÐ¹ÑˆÐµÐ¼ ");
		$textRun1->addText("Â«Ð­ÐºÑÐ¿ÐµÐ´Ð¸Ñ‚Ð¾Ñ€Â», ", $boldText);
		$textRun1->addText("Ð² Ð»Ð¸Ñ†Ðµ Ð”Ð¸Ñ€ÐµÐºÑ‚Ð¾Ñ€Ð° Ð‘Ð°Ð»Ð°ÐµÐ²Ð° Ð .Ðž., Ð´ÐµÐ¹ÑÑ‚Ð²ÑƒÑŽÑ‰ÐµÐ³Ð¾ Ð½Ð° Ð¾ÑÐ½Ð¾Ð²Ð°Ð½Ð¸Ð¸ Ð£ÑÑ‚Ð°Ð²Ð°, Ñ Ð´Ñ€ÑƒÐ³Ð¾Ð¹ ÑÑ‚Ð¾Ñ€Ð¾Ð½Ñ‹, ÑÐ¾Ð²Ð¼ÐµÑÑ‚Ð½Ð¾ Ð¸Ð¼ÐµÐ½ÑƒÐµÐ¼Ñ‹Ðµ ");
		$textRun1->addText("Â«Ð¡Ñ‚Ð¾Ñ€Ð¾Ð½Ñ‹Â», ", $boldText);
		$textRun1->addText("Ð·Ð°ÐºÐ»ÑŽÑ‡Ð¸Ð»Ð¸ Ð½Ð°ÑÑ‚Ð¾ÑÑ‰ÐµÐµ Ð¿Ñ€Ð¸Ð»Ð¾Ð¶ÐµÐ½Ð¸Ðµ Ð¾ Ð½Ð¸Ð¶ÐµÑÐ»ÐµÐ´ÑƒÑŽÑ‰ÐµÐ¼:");
		

		$textRun1 = $section->createTextRun();
		$textRun1->addText("1. Ð­ÐºÑÐ¿ÐµÐ´Ð¸Ñ‚Ð¾Ñ€ Ð¿Ñ€Ð¸Ð½Ð¸Ð¼Ð°ÐµÑ‚ Ð½Ð° ÑÐµÐ±Ñ Ð¾Ð±ÑÐ·Ð°Ñ‚ÐµÐ»ÑŒÑÑ‚Ð²Ð¾ Ð¾Ñ€Ð³Ð°Ð½Ð¸Ð·Ð¾Ð²Ð°Ñ‚ÑŒ Ð¸ Ð¿Ñ€Ð¾Ð¸Ð·Ð²ÐµÑÑ‚Ð¸  ÑÐ»ÐµÐ´ÑƒÑŽÑ‰Ð¸Ðµ ÑƒÑÐ»ÑƒÐ³Ð¸: \n");
		if ($n_insuranceRel > 0) $additionalService = ",ÑÑ‚Ñ€Ð°Ñ…Ð¾Ð²Ð°Ð½Ð¸Ðµ";
		$textRun1->addText("Ð¢Ñ€Ð°Ð½ÑÐ¿Ð¾Ñ€Ñ‚Ð¸Ñ€Ð¾Ð²ÐºÐ° Ð³Ñ€ÑƒÐ·Ð° ".$additionalService."                                        ", array('bold' => true));
		$textRun1->addText("                                                                         Ð° ÐšÐ»Ð¸ÐµÐ½Ñ‚ Ð¾Ð±ÑÐ·ÑƒÐµÑ‚ÑÑ Ð¾Ð¿Ð»Ð°Ñ‚Ð¸Ñ‚ÑŒ Ð²Ñ‹Ð¿Ð¾Ð»Ð½ÐµÐ½Ð½Ñ‹Ðµ Ð­ÐºÑÐ¿ÐµÐ´Ð¸Ñ‚Ð¾Ñ€Ð¾Ð¼ ÑƒÑÐ»ÑƒÐ³Ð¸. ");


		$styleTable = array('borderSize' => 6, 'borderColor' => '999999');
		$cellRowSpan = array('vMerge' => 'restart', 'valign' => 'center');
		$cellRowContinue = array('vMerge' => 'continue');
		$cellColSpan2 = array('gridSpan' => 2, 'valign' => 'center');
		$cellColSpan3 = array('gridSpan' => 3, 'valign' => 'center');
		 
		$cellHCentered = array('align' => 'center');
		$cellHLeft = array('align' => 'left');
		$cellVCentered = array('valign' => 'center');
	
		$PHPWord->addTableStyle('Colspan Rowspan', $styleTable);
		$table = $section->addTable('Colspan Rowspan');
		$table->addRow(null, array('tblHeader' => true));
		$table->addCell(2000, $cellVCentered)->addText('ÐÐ¾Ð¼ÐµÑ€ Ñ€Ð°Ð±Ð¾Ñ‚Ñ‹', array('bold' => false), $cellHCentered);
		$table->addCell(2200, $cellVCentered)->addText('ÐÐ°Ð¸Ð¼ÐµÐ½Ð¾Ð²Ð°Ð½Ð¸Ðµ Ñ‚Ð¾Ð²Ð°Ñ€Ð°', array('bold' => false), $cellHCentered);
		$table->addCell(2000, $cellVCentered)->addText('ÐšÐ¾Ð»-Ð²Ð¾ Ð¼ÐµÑÑ‚', array('bold' => false), $cellHCentered);
		$table->addCell(1800, $cellColSpan2)->addText('ÐžÐ¿Ð»Ð°Ñ‡Ð¸Ð²Ð°ÐµÐ¼Ñ‹Ð¹ Ð²ÐµÑ, ÐºÐ³', array('bold' => false), $cellHCentered);
		$table->addCell(2000, $cellColSpan2)->addText('ÐŸÑ€Ð¾Ñ‡Ð¸Ðµ ÑƒÑÐ»Ð¾Ð²Ð¸Ñ', array('bold' => false), $cellHCentered);
		
		$table->addRow(null, array('tblHeader' => true));
		$table->addCell(2000, $cellColSpan2)->addText($job, array('bold' => true), $cellHCentered);
		$table->addCell(2000, $cellColSpan2)->addText($commodity, array('bold' => true), $cellHCentered);
		$table->addCell(2000, $cellColSpan2)->addText($noOfPieces, array('bold' => true), $cellHCentered);
		$table->addCell(2000, $cellColSpan2)->addText($weight, array('bold' => true), $cellHCentered);
		$table->addCell(2000, $cellColSpan2)->addText('ÐÐµÑ‚', array('bold' => true), $cellHCentered);
		
		$section->addTextBreak();
		

		$PHPWord->addTableStyle('Colspan Rowspan', $styleTable);
		$table = $section->addTable('Colspan Rowspan');

		$table->addRow(null, array('tblHeader' => true));		
		$table->addCell(8000, $cellColSpan2)->addText('', array('bold' => false), $cellHCentered);
		$table->addCell(2000, $cellColSpan2)->addText('Ð¡Ñ‚Ð¾Ð¸Ð¼Ð¾ÑÑ‚ÑŒ ÑƒÑÐ»ÑƒÐ³, Ñ‚ÐµÐ½Ð³Ðµ', array('bold' => false), $cellHCentered);

		

		$chargeList = $this->getJobFileCharges($jobFileId);

		foreach ($chargeList as $charge){
			$chargeName = $charge['chargeName'];
			$currencyCode = $charge['currencyCode'];
			$description = $charge['description'];

			$chargeCost = number_format($charge['chargeCost'], 2 , '.' , ' ');

			$chargeCostFormatted = $chargeCost.' '.$currencyCode;
			$table->addRow(null, array('tblHeader' => true));		
			$table->addCell(8000, $cellColSpan2)->addText($description, array('bold' => false), $cellHLeft);
			$table->addCell(2000, $cellColSpan2)->addText($chargeCostFormatted, array('bold' => false), $cellHLeft);
		}
								
		// ======				
		$section->addTextBreak();
		$textRun2 = $section->createTextRun();

/* 		$textRun2->addText("2. Ð¡Ñ‚Ð¾Ð¸Ð¼Ð¾ÑÑ‚ÑŒ ÑƒÑÐ»ÑƒÐ³, Ð¾ÑÑƒÑ‰ÐµÑÑ‚Ð²Ð»ÑÐµÐ¼Ñ‹Ñ… Ð² Ñ€Ð°Ð¼ÐºÐ°Ñ… Ð”Ð¾Ð³Ð¾Ð²Ð¾Ñ€Ð° ");
		$textRun2->addText($agreement, $boldText);
		$textRun2->addText(" Ñ‚ÐµÐ½Ð³Ðµ ______ 00 Ñ‚Ð¸Ñ‹Ð½ ", $boldText);
		$textRun2->addText("(Ð²ÐºÐ»ÑŽÑ‡Ð°Ñ ÑÑ‚Ñ€Ð°Ñ…Ð¾Ð²Ð°Ð½Ð¸Ðµ). ÐšÐ»Ð¸ÐµÐ½Ñ‚ Ð¿Ñ€Ð¾Ð¸Ð·Ð²Ð¾Ð´Ð¸Ñ‚ Ð¿Ñ€ÐµÐ´Ð¾Ð¿Ð»Ð°Ñ‚Ñƒ Ð² Ñ€Ð°Ð·Ð¼ÐµÑ€Ðµ 100 % Ð¾Ñ‚ Ð¾Ð±Ñ‰ÐµÐ¹ ÑÑ‚Ð¾Ð¸Ð¼Ð¾ÑÑ‚Ð¸ ÑƒÑÐ»ÑƒÐ³  Ð¿Ð¾ Ð½Ð°ÑÑ‚Ð¾ÑÑ‰ÐµÐ¼Ñƒ ÐŸÑ€Ð¸Ð»Ð¾Ð¶ÐµÐ½Ð¸ÑŽ, Ñ‡Ñ‚Ð¾ ÑÐ¾ÑÑ‚Ð°Ð²Ð»ÑÐµÑ‚ ÑÑƒÐ¼Ð¼Ñƒ Ð² Ñ€Ð°Ð·Ð¼ÐµÑ€Ðµ: ");
		$textRun2->addText("Ñ‚ÐµÐ½Ð³Ðµ ______ 00 Ñ‚Ð¸Ñ‹Ð½ ", $boldText); */

		// $textRun2->addText($paymentTypeCondition);

		// $totalAmountTemplate = $invoiceAmountFormat.' ('.$this->num2str($invoiceAmount).')';
		// $paymentTypeConditionArray
		// $paymentTypeCondition = str_replace("{summ}", $totalAmountTemplate,  $paymentTypeCondition);


		foreach($paymentTypeConditionArray as $key => $item){
			if (isset($item)){
				$sentence = trim($item);

			

				if ($sentence == '{summ70}'){	
					$amountPart = $this->divideAmountByPercentage($invoiceAmount, 70);
					$textRun2->addText($amountPart, $boldText);					
				} else 

				if ($sentence == '{summ50}'){	
					$amountPart = $this->divideAmountByPercentage($invoiceAmount, 50);
					$textRun2->addText($amountPart, $boldText);					
				} else 

				if ($sentence == '{summ30}'){	
					$amountPart = $this->divideAmountByPercentage($invoiceAmount, 30);
					$textRun2->addText($amountPart, $boldText);					
				} else 

				if ($sentence == '{summ100}'){	
					$amountPart = $this->divideAmountByPercentage($invoiceAmount, 100);
					$textRun2->addText($amountPart, $boldText);					
				} else {
					if (strpos($sentence, "{agreement}")){
						$sentence = str_replace("{agreement}", $agreement, $sentence);
					}
					$textRun2->addText($sentence." ");					
				}
				
			}
		}
 
		//$textRun2->addText(" Ð¿ÑƒÑ‚ÐµÐ¼ Ð¿ÐµÑ€ÐµÑ‡Ð¸ÑÐ»ÐµÐ½Ð¸Ñ Ð´ÐµÐ½ÐµÐ¶Ð½Ñ‹Ñ… ÑÑ€ÐµÐ´ÑÑ‚Ð² Ð½Ð° Ñ€Ð°ÑÑ‡ÐµÑ‚Ð½Ñ‹Ð¹ ÑÑ‡ÐµÑ‚ Ð­ÐºÑÐ¿ÐµÐ´Ð¸Ñ‚Ð¾Ñ€Ð° Ð² Ñ‚ÐµÑ‡ÐµÐ½Ð¸Ðµ 3 (Ñ‚Ñ€ÐµÑ…) Ð±Ð°Ð½ÐºÐ¾Ð²ÑÐºÐ¸Ñ… Ð´Ð½ÐµÐ¹ Ñ Ð´Ð°Ñ‚Ñ‹ Ð¿Ð¾Ð´Ð¿Ð¸ÑÐ°Ð½Ð¸Ñ ÑÐ¾Ð¾Ñ‚Ð²ÐµÑ‚ÑÑ‚Ð²ÑƒÑŽÑ‰ÐµÐ³Ð¾ ÐŸÑ€Ð¸Ð»Ð¾Ð¶ÐµÐ½Ð¸Ñ Ð¸ Ð¿Ð¾Ð»ÑƒÑ‡ÐµÐ½Ð¸Ñ ÑÑ‡ÐµÑ‚Ð° Ð½Ð° Ð¿Ñ€ÐµÐ´Ð¾Ð¿Ð»Ð°Ñ‚Ñƒ.");
		
		// $section->addTextBreak();
		$section->addText("Ð”Ð»Ñ Ð²Ð·Ð°Ð¸Ð¼Ð¾Ñ€Ð°ÑÑ‡ÐµÑ‚Ð¾Ð² ÑƒÑ‡Ð¸Ñ‚Ñ‹Ð²Ð°ÐµÑ‚ÑÑ ÐºÑƒÑ€Ñ ÐÐ°Ñ†Ð¸Ð¾Ð½Ð°Ð»ÑŒÐ½Ð¾Ð³Ð¾ Ð‘Ð°Ð½ÐºÐ°/KASE/ÐÐ°Ñ€Ð¾Ð´Ð½Ð¾Ð³Ð¾ Ð‘Ð°Ð½ÐºÐ° Ð½Ð° Ð´Ð°Ñ‚Ñƒ:", ['bold' => true, 'italic' => true]);
		$section->addListItem('Ð’Ñ‹ÑÑ‚Ð°Ð²Ð»ÐµÐ½Ð¸Ñ ÑÑ‡ÐµÑ‚Ð° Ð½Ð° Ð¾Ð¿Ð»Ð°Ñ‚Ñƒ, Ð¿Ñ€Ð¸ ÑƒÑÐ»Ð¾Ð²Ð¸Ð¸ Ð¾Ð¿Ð»Ð°Ñ‚Ñ‹ Ð½Ð° Ð¿Ñ€ÐµÐ´Ð¾Ð¿Ð»Ð°Ñ‚Ð½Ð¾Ð¹ Ð¾ÑÐ½Ð¾Ð²Ðµ;', 0, ['bold' => true, 'italic' => true]);
		$section->addListItem('Ð¡Ð¾Ð²ÐµÑ€ÑˆÐµÐ½Ð¸Ñ Ð¾Ð±Ð¾Ñ€Ð¾Ñ‚Ð° Ð¿Ð¾ Ñ€ÐµÐ°Ð»Ð¸Ð·Ð°Ñ†Ð¸Ð¸ (Ð´Ð°Ñ‚Ð° Ð°ÐºÑ‚Ð° Ð²Ñ‹Ð¿Ð¾Ð»Ð½ÐµÐ½Ð½Ñ‹Ñ… Ñ€Ð°Ð±Ð¾Ñ‚ , Ð¿Ñ€Ð¸ ÑƒÑÐ»Ð¾Ð²Ð¸Ð¸ Ð¾Ð¿Ð»Ð°Ñ‚Ñ‹ Ð¿Ð¾ Ñ„Ð°ÐºÑ‚Ñƒ Ð¾ÐºÐ°Ð·Ð°Ð½Ð¸Ñ ÑƒÑÐ»ÑƒÐ³.', 0, ['bold' => true, 'italic' => true]);
		
		// $section->addTextBreak();
		$section->addText("3. Ð“Ñ€ÑƒÐ·Ð¾Ð¿Ð¾Ð»ÑƒÑ‡Ð°Ñ‚ÐµÐ»ÐµÐ¼ Ð³Ñ€ÑƒÐ·Ð° Ð¿Ð¾ Ð½Ð°ÑÑ‚Ð¾ÑÑ‰ÐµÐ¼Ñƒ ÐŸÑ€Ð¸Ð»Ð¾Ð¶ÐµÐ½Ð¸ÑŽ ".$jobFileRef." ÑÐ²Ð»ÑÐµÑ‚ÑÑ:");
		$section->addText($customer_name, $boldText);
		if ($accountLocation) $section->addText($accountLocation, $boldText);
		
		// $section->addTextBreak();
		$section->addText("4. ÐŸÑ€Ð¾Ñ‡Ð¸Ðµ ÑƒÑÐ»Ð¾Ð²Ð¸Ñ Ð¸ Ð¿ÑƒÐ½ÐºÑ‚Ñ‹, Ð½Ðµ Ð¾Ð³Ð¾Ð²Ð¾Ñ€ÐµÐ½Ð½Ñ‹Ðµ Ð² Ð½Ð°ÑÑ‚Ð¾ÑÑ‰ÐµÐ¼ ÐŸÑ€Ð¸Ð»Ð¾Ð¶ÐµÐ½Ð¸Ð¸ ".$jobFileRef.", Ð´ÐµÐ¹ÑÑ‚Ð²ÑƒÑŽÑ‚ Ð² ÑÐ¾Ð¾Ñ‚Ð²ÐµÑ‚ÑÑ‚Ð²Ð¸Ð¸ Ñ Ð”Ð¾Ð³Ð¾Ð²Ð¾Ñ€Ð¾Ð¼ ".$agreement);
		// $section->addTextBreak();
		$section->addText("5. Ð”Ð°Ð½Ð½Ð¾Ðµ ÐŸÑ€Ð¸Ð»Ð¾Ð¶ÐµÐ½Ð¸Ðµ ".$jobFileRef." ÑÐ²Ð»ÑÐµÑ‚ÑÑ Ð½ÐµÐ¾Ñ‚ÑŠÐµÐ¼Ð»ÐµÐ¼Ð¾Ð¹ Ñ‡Ð°ÑÑ‚ÑŒÑŽ Ð”Ð¾Ð³Ð¾Ð²Ð¾Ñ€Ð° ".$agreement);
		// $section->addTextBreak();
		$section->addText("6. Ð”Ð°Ð½Ð½Ð¾Ðµ ÐŸÑ€Ð¸Ð»Ð¾Ð¶ÐµÐ½Ð¸Ðµ ".$jobFileRef." Ðº  Ð”Ð¾Ð³Ð¾Ð²Ð¾Ñ€Ñƒ ".$agreement."  Ð³Ð¾Ð´Ð° Ð²ÑÑ‚ÑƒÐ¿Ð°ÐµÑ‚ Ð² ÑÐ¸Ð»Ñƒ Ñ Ð¼Ð¾Ð¼ÐµÐ½Ñ‚Ð° ÐµÐ³Ð¾ Ð¿Ð¾Ð´Ð¿Ð¸ÑÐ°Ð½Ð¸Ñ ÑÑ‚Ð¾Ñ€Ð¾Ð½Ð°Ð¼Ð¸. Ð¡Ñ€Ð¾Ðº Ð´ÐµÐ¹ÑÑ‚Ð²Ð¸Ñ Ð´Ð°Ð½Ð½Ð¾Ð³Ð¾ ÐŸÑ€Ð¸Ð»Ð¾Ð¶ÐµÐ½Ð¸Ñ Ð¸ÑÑ‚ÐµÐºÐ°ÐµÑ‚ Ð²Ð¼ÐµÑÑ‚Ðµ ÑÐ¾ ÑÑ€Ð¾ÐºÐ¾Ð¼ Ð´ÐµÐ¹ÑÑ‚Ð²Ð¸Ñ Ð”Ð¾Ð³Ð¾Ð²Ð¾Ñ€Ð° ".$agreement);
		// $section->addTextBreak();
		$section->addText("7. Ð£ÑÐ»ÑƒÐ³Ð¸ Ð­ÐºÑÐ¿ÐµÐ´Ð¸Ñ‚Ð¾Ñ€Ð° Ñ€ÐµÐ³Ð»Ð°Ð¼ÐµÐ½Ñ‚Ð¸Ñ€ÑƒÑŽÑ‚ÑÑ Ð³ÐµÐ½ÐµÑ€Ð°Ð»ÑŒÐ½Ñ‹Ð¼Ð¸ ÑƒÑÐ»Ð¾Ð²Ð¸ÑÐ¼Ð¸, ÐºÐ¾Ñ‚Ð¾Ñ€Ñ‹Ðµ Ð¼Ð¾Ð³ÑƒÑ‚ Ð¾Ð³Ñ€Ð°Ð½Ð¸Ñ‡Ð¸Ñ‚ÑŒ Ð¾Ñ‚Ð²ÐµÑ‚ÑÑ‚Ð²ÐµÐ½Ð½Ð¾ÑÑ‚ÑŒ Ð­ÐºÑÐ¿ÐµÐ´Ð¸Ñ‚Ð¾Ñ€Ð° Ð² ÑÐ»ÑƒÑ‡Ð°Ðµ ÑƒÑ‚Ñ€Ð°Ñ‚Ñ‹ Ð¸Ð»Ð¸ Ð¿Ð¾Ñ€Ñ‡Ð¸ Ð“Ñ€ÑƒÐ·Ð°. ÐžÐ·Ð½Ð°ÐºÐ¾Ð¼Ð¸Ñ‚ÑŒÑÑ Ñ Ð³ÐµÐ½ÐµÑ€Ð°Ð»ÑŒÐ½Ñ‹Ð¼Ð¸ ÑƒÑÐ»Ð¾Ð²Ð¸ÑÐ¼Ð¸ Ð¼Ð¾Ð¶Ð½Ð¾ Ð½Ð° Ð²ÐµÐ±-ÑÐ°Ð¹Ñ‚Ðµ: http://globalinklogistics.com/Trading-Terms-and-Conditions. Ð’ ÑÐ»ÑƒÑ‡Ð°Ðµ Ñ‡Ð°ÑÑ‚Ð¸Ñ‡Ð½Ð¾Ð¹ Ð¸Ð»Ð¸ Ð¿Ð¾Ð»Ð½Ð¾Ð¹ ÑƒÑ‚Ñ€Ð°Ñ‚Ñ‹ Ð¸Ð»Ð¸ Ð¿Ð¾Ð²Ñ€ÐµÐ¶Ð´ÐµÐ½Ð¸Ñ Ð“Ñ€ÑƒÐ·Ð°, Ð¿Ñ€Ð¾Ð¸Ð·Ð¾ÑˆÐµÐ´ÑˆÐµÐ¹ Ð² Ð¿Ñ€Ð¾Ñ†ÐµÑÑÐµ Ñ‚Ñ€Ð°Ð½ÑÐ¿Ð¾Ñ€Ñ‚Ð¸Ñ€Ð¾Ð²ÐºÐ¸, Ð­ÐºÑÐ¿ÐµÐ´Ð¸Ñ‚Ð¾Ñ€ ÑÐ¾Ð´ÐµÐ¹ÑÑ‚Ð²ÑƒÐµÑ‚ Ð² Ð²Ð¾Ð·Ð¼ÐµÑ‰ÐµÐ½Ð¸Ð¸ ÐšÐ»Ð¸ÐµÐ½Ñ‚Ñƒ ÑÑ‚Ð¾Ð¸Ð¼Ð¾ÑÑ‚Ð¸ Ð½Ð°Ð½ÐµÑÐµÐ½Ð½Ð¾Ð³Ð¾ Ð¼Ð°Ñ‚ÐµÑ€Ð¸Ð°Ð»ÑŒÐ½Ð¾Ð³Ð¾ ÑƒÑ‰ÐµÑ€Ð±Ð° ÑÑ‚Ñ€Ð°Ñ…Ð¾Ð²Ð¾Ð¹ ÐºÐ¾Ð¼Ð¿Ð°Ð½Ð¸ÐµÐ¹. Ð’ ÑÐ»ÑƒÑ‡Ð°Ðµ Ð¾Ñ‚ÐºÐ°Ð·Ð° ÑÑ‚Ñ€Ð°Ñ…Ð¾Ð²Ð¾Ð¹ ÐºÐ¾Ð¼Ð¿Ð°Ð½Ð¸Ð¸ Ð¾Ñ‚ Ð²Ñ‹Ð¿Ð»Ð°Ñ‚Ñ‹ Ð²Ð¾Ð·Ð¼ÐµÑ‰ÐµÐ½Ð¸Ñ ÐšÐ»Ð¸ÐµÐ½Ñ‚Ñƒ, Ð° Ñ‚Ð°ÐºÐ¶Ðµ ÐµÑÐ»Ð¸ ÑÑ‚Ñ€Ð°Ñ…Ð¾Ð²Ð¾Ð¹ ÑÐ»ÑƒÑ‡Ð°Ð¹ Ð¿Ñ€Ð¾Ð¸Ð·Ð¾ÑˆÐµÐ» Ð¿Ð¾ Ð´Ð¾ÐºÐ°Ð·Ð°Ð½Ð½Ð¾Ð¹ Ð²Ð¸Ð½Ðµ Ð­ÐºÑÐ¿ÐµÐ´Ð¸Ñ‚Ð¾Ñ€Ð°, Ð­ÐºÑÐ¿ÐµÐ´Ð¸Ñ‚Ð¾Ñ€ Ð²Ð¾Ð·Ð¼ÐµÑ‰Ð°ÐµÑ‚ ÑƒÑ‚Ñ€Ð°Ñ‚Ñƒ Ð¸Ð»Ð¸ Ð¿Ð¾Ð²Ñ€ÐµÐ¶Ð´ÐµÐ½Ð¸Ðµ Ð³Ñ€ÑƒÐ·Ð° Ð² ÑÐ¾Ð¾Ñ‚Ð²ÐµÑ‚ÑÑ‚Ð²Ð¸Ð¸ Ñ  Ð¿Ñ€Ð¸Ð¼ÐµÐ½Ð¸Ð¼Ñ‹Ð¼Ð¸ Ð¼ÐµÐ¶Ð´ÑƒÐ½Ð°Ñ€Ð¾Ð´Ð½Ñ‹Ð¼Ð¸ ÐšÐ¾Ð½Ð²ÐµÐ½Ñ†Ð¸ÑÐ¼Ð¸ Ð¸ Ð¡Ð¾Ð³Ð»Ð°ÑˆÐµÐ½Ð¸ÑÐ¼Ð¸ Ð² ÑÑ„ÐµÑ€Ðµ Ñ‚Ñ€Ð°Ð½ÑÐ¿Ð¾Ñ€Ñ‚Ð°, Ð²ÐºÐ»ÑŽÑ‡Ð°Ñ, Ð½Ð¾ Ð½Ðµ ÐÑÑÑŒ ÐšÐ”ÐŸÐ“, Ð¡ÐœÐ“Ð¡, Ð’Ð°Ñ€ÑˆÐ°Ð²ÑÐºÐ°Ñ ÐšÐ¾Ð½Ð²ÐµÐ½Ñ†Ð¸Ñ 1929 Ð³., ÐœÐ¾Ð½Ñ‚Ñ€ÐµÐ°Ð»ÑŒÑÐºÐ°Ñ ÐšÐ¾Ð½Ð²ÐµÐ½Ñ†Ð¸Ñ, Ð¸.Ñ‚.Ð´. Ð­ÐºÑÐ¿ÐµÐ´Ð¸Ñ‚Ð¾Ñ€ Ð¾ÑÐ²Ð¾Ð±Ð¾Ð¶Ð´Ð°ÐµÑ‚ÑÑ Ð¾Ñ‚ Ð»ÑŽÐ±Ð¾Ð¹ Ð¾Ñ‚Ð²ÐµÑ‚ÑÑ‚Ð²ÐµÐ½Ð½Ð¾ÑÑ‚Ð¸ Ð² ÑÐ»ÑƒÑ‡Ð°Ðµ, ÐµÑÐ»Ð¸ ÐšÐ»Ð¸ÐµÐ½Ñ‚Ñƒ Ð±Ñ‹Ð»Ð¾ Ð¾Ñ‚ÐºÐ°Ð·Ð°Ð½Ð¾ Ð² Ð²Ð¾Ð·Ð¼ÐµÑ‰ÐµÐ½Ð¸Ð¸ Ð¿Ð¾ Ð¿Ñ€Ð°Ð²Ð¸Ð»Ð°Ð¼/Ð´Ð¾Ð³Ð¾Ð²Ð¾Ñ€Ñƒ ÑÑ‚Ñ€Ð°Ñ…Ð¾Ð²Ð°Ð½Ð¸Ñ. ÐÐ¸ Ð¿Ñ€Ð¸ ÐºÐ°ÐºÐ¸Ñ… Ð¾Ð±ÑÑ‚Ð¾ÑÑ‚ÐµÐ»ÑŒÑÑ‚Ð²Ð°Ñ…, Ð­ÐºÑÐ¿ÐµÐ´Ð¸Ñ‚Ð¾Ñ€ Ð½Ðµ Ð½ÐµÑÐµÑ‚ Ð¾Ñ‚Ð²ÐµÑ‚ÑÑ‚Ð²ÐµÐ½Ð½Ð¾ÑÑ‚ÑŒ Ð·Ð° ÐºÐ¾ÑÐ²ÐµÐ½Ð½Ñ‹Ðµ ÑƒÐ±Ñ‹Ñ‚ÐºÐ¸, Ð·Ð°Ð´ÐµÑ€Ð¶ÐºÐ¸, Ð¿Ð¾Ñ‚ÐµÑ€ÑŽ Ð¿Ñ€Ð¸Ð±Ñ‹Ð»Ð¸, Ð¿Ð¾Ñ‚ÐµÑ€ÑŽ Ñ€Ñ‹Ð½ÐºÐ° Ð¸ Ð»Ð¸ÐºÐ²Ð¸Ð´Ð½Ñ‹Ðµ ÑƒÐ±Ñ‹Ñ‚ÐºÐ¸.");
		// $section->addTextBreak();
		$section->addText("8. Ð’ ÑÐ»ÑƒÑ‡Ð°Ðµ, Ð´ÐµÐ²Ð°Ð»ÑŒÐ²Ð°Ñ†Ð¸Ð¸ Ñ‚ÐµÐ½Ð³Ðµ Ðº Ð´Ð¾Ð»Ð»Ð°Ñ€Ñƒ Ð¡Ð¨Ð / Ð•Ð²Ñ€Ð¾ Ð±Ð¾Ð»ÐµÐµ Ñ‡ÐµÐ¼ Ð½Ð° 5% Ð² Ð¿ÐµÑ€Ð¸Ð¾Ð´ Ð¼ÐµÐ¶Ð´Ñƒ Ð´Ð°Ñ‚Ð¾Ð¹ Ð²Ñ‹Ð´Ð°Ñ‡Ð¸ ÑÑ‡ÐµÑ‚Ð°-Ñ„Ð°ÐºÑ‚ÑƒÑ€Ñ‹ Ð´Ð¾ Ð´Ð°Ñ‚Ñ‹ Ð¿Ð¾Ð»ÑƒÑ‡ÐµÐ½Ð¸Ñ Ð¿Ð»Ð°Ñ‚ÐµÐ¶Ð° Ð² Ð±Ð°Ð½ÐºÐ¾Ð²ÑÐºÐ¸Ð¹ ÑÑ‡ÐµÑ‚ ÑÐºÑÐ¿ÐµÐ´Ð¸Ñ‚Ð¾Ñ€Ð°, ÑÐºÑÐ¿ÐµÐ´Ð¸Ñ‚Ð¾Ñ€ Ð¸Ð¼ÐµÐµÑ‚ Ð¿Ñ€Ð°Ð²Ð¾ Ð¿Ñ€Ð¾Ð¸Ð·Ð²ÐµÑÑ‚Ð¸ Ð¿ÐµÑ€ÐµÑ€Ð°ÑÑ‡Ñ‘Ñ‚ ÑÑƒÐ¼Ð¼Ñ‹ ÑÑ‡ÐµÑ‚-Ñ„Ð°ÐºÑ‚ÑƒÑ€Ñ‹ Ñ Ð¿Ñ€Ð¸Ð¼ÐµÐ½ÐµÐ½Ð¸ÐµÐ¼ ÐºÐ¾ÑÑ„Ñ„Ð¸Ñ†Ð¸ÐµÐ½Ñ‚Ð° Ð¸Ð½Ð´ÐµÐºÑÐ°Ñ†Ð¸Ð¸ Ð´ÐµÐ²Ð°Ð»ÑŒÐ²Ð°Ñ†Ð¸Ð¸.");
		// $section->addTextBreak();
		$section->addText("ÐŸÑ€Ð¸Ð¼ÐµÑ‡Ð°Ð½Ð¸Ðµ* Ð”Ð°Ñ‚Ð° ÑÐ»ÐµÐºÑ‚Ñ€Ð¾Ð½Ð½Ð¾Ð¹ ÑÑ‡ÐµÑ‚-Ñ„Ð°ÐºÑ‚ÑƒÑ€Ñ‹ Ð½Ðµ ÑÐ²Ð»ÑÐµÑ‚ÑÑ Ð¾ÑÐ½Ð¾Ð²Ð°Ð½Ð¸ÐµÐ¼ Ð´Ð»Ñ Ð¿ÐµÑ€ÐµÑÑ‡ÐµÑ‚Ð° ÑÑƒÐ¼Ð¼Ñ‹ Ð¿Ð¾ ÐºÑƒÑ€ÑÑƒ.", ['underline' => 'signle']);
		
		$section->addText("9. ÐÐ°ÑÑ‚Ð¾ÑÑ‰Ð¸Ð¼ Ð—Ð°ÐºÐ°Ð·Ñ‡Ð¸Ðº Ð¾Ñ‚ÐºÐ°Ð·Ñ‹Ð²Ð°ÐµÑ‚ÑÑ Ð¾Ñ‚ Ð¿Ñ€Ð°Ð²Ð° ÑÑƒÐ±Ñ€Ð¾Ð³Ð°Ñ†Ð¸Ð¸ Ð² Ð¾Ñ‚Ð½Ð¾ÑˆÐµÐ½Ð¸Ð¸ Ð­ÐšÐ¡ÐŸÐ•Ð”Ð˜Ð¢ÐžÐ Ð, ÐµÐ³Ð¾ Ñ€ÑƒÐºÐ¾Ð²Ð¾Ð´ÑÑ‚Ð²Ð°, ÑƒÑ‡Ð°ÑÑ‚Ð½Ð¸ÐºÐ¾Ð², Ð°Ð³ÐµÐ½Ñ‚Ð¾Ð² Ð¸ ÑÐ¾Ñ‚Ñ€ÑƒÐ´Ð½Ð¸ÐºÐ¾Ð², Ð²Ð¾Ð·Ð½Ð¸ÐºÐ°ÑŽÑ‰ÐµÐ³Ð¾ Ð²ÑÐ»ÐµÐ´ÑÑ‚Ð²Ð¸Ðµ ÑƒÑ‚Ñ€Ð°Ñ‚Ñ‹ Ð¸Ð»Ð¸ Ð¿Ñ€Ð¸Ñ‡Ð¸Ð½ÐµÐ½Ð¸Ñ ÑƒÑ‰ÐµÑ€Ð±Ð° Ð³Ñ€ÑƒÐ·Ñƒ Ð¸Ð»Ð¸ Ð¸Ð¼ÑƒÑ‰ÐµÑÑ‚Ð²Ñƒ Ð² Ð¿Ñ€ÐµÐ´ÐµÐ»Ð°Ñ… Ñ€Ð°Ð·Ð¼ÐµÑ€Ð° Ñ‚Ð°ÐºÐ¾Ð¹ ÑƒÑ‚Ñ€Ð°Ñ‚Ñ‹ Ð¸Ð»Ð¸ ÑƒÑ‰ÐµÑ€Ð±Ð°, Ð²Ð½Ðµ Ð·Ð°Ð²Ð¸ÑÐ¸Ð¼Ð¾ÑÑ‚Ð¸ Ð¾Ñ‚ Ð½Ð°Ð»Ð¸Ñ‡Ð¸Ñ ÑÑ‚Ñ€Ð°Ñ…Ð¾Ð²Ð°Ð½Ð¸Ñ Ð¸ Ð¾Ñ‚ Ñ‚Ð¾Ð³Ð¾, ÐºÑ‚Ð¾ ÑÐ²Ð»ÑÐµÑ‚ÑÑ Ð¡Ñ‚Ñ€Ð°Ñ…Ð¾Ð²Ð°Ñ‚ÐµÐ»ÐµÐ¼.");
		$section->addText("10. ÐžÐ“Ð ÐÐÐ˜Ð§Ð•ÐÐ˜Ð• ÐžÐ¢Ð’Ð•Ð¢Ð¡Ð¢Ð’Ð•ÐÐÐžÐ¡Ð¢Ð˜: Ð­ÐšÐ¡ÐŸÐ•Ð”Ð˜Ð¢ÐžÐ  ÐÐ•Ð¡Ð•Ð¢ ÐžÐ¢Ð’Ð•Ð¢Ð¡Ð¢Ð’Ð•ÐÐÐžÐ¡Ð¢Ð¬ Ð˜Ð¡ÐšÐ›Ð®Ð§Ð˜Ð¢Ð•Ð›Ð¬ÐÐž Ð—Ð Ð£Ð¢Ð ÐÐ¢Ð£ Ð˜Ð›Ð˜ Ð£Ð©Ð•Ð Ð‘ ÐŸÐ Ð˜Ð§Ð˜ÐÐ•ÐÐÐ«Ð• Ð’Ð¡Ð›Ð•Ð”Ð¡Ð¢Ð’Ð˜Ð• Ð•Ð“Ðž Ð¥ÐÐ›ÐÐ¢ÐÐžÐ¡Ð¢Ð˜ Ð˜Ð›Ð˜ ÐÐÐ Ð£Ð¨Ð•ÐÐ˜Ð¯ Ð”ÐžÐ“ÐžÐ’ÐžÐ ÐÐ«Ð¥ ÐžÐ‘Ð¯Ð—ÐÐ¢Ð•Ð›Ð¬Ð¡Ð¢Ð’ Ð’ ÐŸÐ Ð•Ð”Ð•Ð›ÐÐ¥ ÐÐÐ˜ÐœÐ•ÐÐ¬Ð¨Ð•Ð“Ðž Ð˜Ð— Ð¡Ð›Ð•Ð”Ð£Ð®Ð©Ð˜Ð¥ Ð—ÐÐÐ§Ð•ÐÐ˜Ð™: I) 20 Ð”ÐžÐ›Ð›. Ð¡Ð¨Ð Ð—Ð ÐšÐ˜Ð›ÐžÐ“Ð ÐÐœÐœ Ð“Ð Ð£Ð—Ð Ð’ Ð£ÐŸÐÐšÐžÐ’ÐšÐ• (Ð‘Ð Ð£Ð¢Ð¢Ðž), Ð˜Ð›Ð˜ II) Ð¡Ð¢ÐžÐ˜ÐœÐžÐ¡Ð¢Ð¬ Ð£Ð¡Ð›Ð£Ð“ Ð­ÐšÐ¡ÐŸÐ•Ð”Ð˜Ð¢ÐžÐ Ð, ÐžÐŸÐ›ÐÐ§Ð˜Ð’ÐÐ•ÐœÐÐ¯ Ð—ÐÐšÐÐ—Ð§Ð˜ÐšÐžÐœ Ð—Ð Ð¡ÐžÐžÐ¢Ð’Ð•Ð¢Ð¡Ð¢Ð’Ð£Ð®Ð©Ð£Ð® ÐŸÐ•Ð Ð•Ð’ÐžÐ—ÐšÐ£. ÐÐ˜ ÐŸÐ Ð˜ ÐšÐÐšÐ˜Ð¥ ÐžÐ‘Ð¡Ð¢ÐžÐ¯Ð¢Ð•Ð›Ð¬Ð¡Ð¢Ð’ÐÐ¥ Ð­ÐšÐ¡ÐŸÐ•Ð”Ð˜Ð¢ÐžÐ  ÐÐ• ÐÐ•Ð¡Ð•Ð¢ ÐžÐ¢Ð’Ð•Ð¢Ð¡Ð¢Ð’Ð•ÐÐÐžÐ¡Ð¢Ð¬ Ð—Ð ÐšÐÐšÐžÐ™-Ð›Ð˜Ð‘Ðž ÐšÐžÐ¡Ð’Ð•ÐÐÐ«Ð™, ÐÐ•ÐŸÐ Ð•Ð”ÐÐÐœÐ•Ð Ð•ÐÐÐ«Ð™ Ð£Ð©Ð•Ð Ð‘ Ð˜/Ð˜Ð›Ð˜ Ð—Ð Ð£ÐŸÐ£Ð©Ð•ÐÐÐ£Ð® Ð’Ð«Ð“ÐžÐ”Ð£");
		$section->addText("ÐÐ°ÑˆÐµ Ð½Ð°Ð·Ð½Ð°Ñ‡ÐµÐ½Ð¸Ðµ Ð² ÐºÐ°Ñ‡ÐµÑÑ‚Ð²Ðµ ÑÐºÑÐ¿ÐµÐ´Ð¸Ñ‚Ð¾Ñ€Ð° Ð¸Ð»Ð¸ Ð¿ÐµÑ€ÐµÐ²Ð¾Ð·Ñ‡Ð¸ÐºÐ° Ð´Ð»Ñ Ð²Ñ‹Ð¿Ð¾Ð»Ð½ÐµÐ½Ð¸Ñ Ð¿ÐµÑ€ÐµÐ²Ð¾Ð·ÐºÐ¸ Ð±ÑƒÐ´ÐµÑ‚ Ñ€Ð°ÑÑÐ¼Ð°Ñ‚Ñ€Ð¸Ð²Ð°Ñ‚ÑŒÑÑ ÐºÐ°Ðº Ð¿Ñ€Ð¸Ð½ÑÑ‚Ð¸Ðµ Ð´Ð°Ð½Ð½Ñ‹Ñ… ÑƒÑÐ»Ð¾Ð²Ð¸Ð¹.");
		
		// $section->addTextBreak();
		// $section->addTextBreak();
		$section->addText("11. Ð®Ñ€Ð¸Ð´Ð¸Ñ‡ÐµÑÐºÐ¸Ðµ Ð°Ð´Ñ€ÐµÑÐ° Ð¸ Ð±Ð°Ð½ÐºÐ¾Ð²ÑÐºÐ¸Ðµ Ñ€ÐµÐºÐ²Ð¸Ð·Ð¸Ñ‚Ñ‹ ÑÑ‚Ð¾Ñ€Ð¾Ð½:", $boldText);


		// requisites table
/* 		$requisitesTable = $section->addTable('tableStyle');
		$requisitesTable->addRow(600);
		$clientColumnCell = $requisitesTable->addCell(0, ['borderSize' => 6, 'borderColor' => '000000']);
		$clientColumnCellTextRun = $clientColumnCell->createTextRun();
		$clientColumnCellTextRun->addText("ÐšÐ»Ð¸ÐµÐ½Ñ‚: ", $boldText);

		
		$forwarderColumnCell = $requisitesTable->addCell(0, ['borderSize' => 6, 'borderColor' => '000000']);
		$forwarderColumnCellTextRun = $forwarderColumnCell->createTextRun();
		$forwarderColumnCellTextRun->addText("ÐšÐ»Ð¸ÐµÐ½Ñ‚: ", $boldText); */


		$table = $section->addTable('Colspan Rowspan');
		$table->addRow(null, array('tblHeader' => true));
		$table->addCell(4000, )->addText('ÐšÐ»Ð¸ÐµÐ½Ñ‚', array('bold' => true), $cellHCentered);
		$table->addCell(5000, $cellVCentered)->addText('Ð­ÐºÑÐ¿ÐµÐ´Ð¸Ñ‚Ð¾Ñ€', array('bold' => true), $cellHCentered);
		
		$table->addRow(null, array('tblHeader' => true));
		$c1 = $table->addCell(5000, array('gridSpan' => 2, 'valign' => 'top'));


		if ($jobFileInfo->get('cf_1441')){

			$accountId = $jobFileInfo->get('cf_1441');
			$q_bo = $adb->pquery("select acc.*,acc_b.*,acc_cf.*
													from vtiger_account as acc 
													LEFT JOIN vtiger_accountbillads as acc_b ON acc.accountid = acc_b.accountaddressid
													LEFT JOIN vtiger_accountscf as acc_cf ON acc_cf.accountid = acc.accountid							
													where acc.accountid = ?", array($accountId));
			$CustomerName = strip_tags($adb->query_result($q_bo, 0, 'cf_2395')); // 1
			$customerAddress = $adb->query_result($q_bo, 0, 'bill_street'); // 2
			$bic = $adb->query_result($q_bo, 0, 'cf_2397'); // 3
			$ppn = $adb->query_result($q_bo, 0, 'cf_2399'); // 3.2
			$kbe = $adb->query_result($q_bo, 0, 'cf_2405'); // 4
			$bankName = strip_tags($adb->query_result($q_bo, 0, 'cf_1833')); // 5
			$accountNumber = $adb->query_result($q_bo, 0, 'cf_1835'); // 6

			$BankAddress = strip_tags($adb->query_result($q_bo, 0, 'cf_1837')); // 6.2
			$swift_code = $adb->query_result($q_bo, 0, 'cf_2429'); // 7


 			$bill_pobox = $adb->query_result($q_bo, 0, 'bill_pobox');
			$bill_code = $adb->query_result($q_bo, 0, 'bill_code');
			$bill_city = $adb->query_result($q_bo, 0, 'bill_city');
			$bill_state = $adb->query_result($q_bo, 0, 'bill_state');
			$bill_country = $adb->query_result($q_bo, 0, 'bill_country');
			$BankState = $adb->query_result($q_bo, 0, 'cf_1849');
			$BankCity = $adb->query_result($q_bo, 0, 'cf_1845');
			$BankCountry = $adb->query_result($q_bo, 0, 'cf_1841');

		}


		// Output data to word
		if (!empty($CustomerName)){
			$cName = html_entity_decode($CustomerName);
			$c1->addText($cName, array('bold' => true));
			$section->addTextBreak();
		}

		if (!empty($customerAddress)) $c1->addText('Ð®Ñ€Ð¸Ð´Ð¸Ñ‡ÐµÑÐºÐ¸Ð¹ Ð°Ð´Ñ€ÐµÑ: '.$customerAddress);
		if (!empty($bic)) $c1->addText('Ð‘Ð˜Ð: '.$bic);
		if (!empty($ppn)) $c1->addText('Ð ÐÐ: ' . $ppn);
		if (!empty($kbe)) $c1->addText('ÐšÐ‘Ð•: '. $kbe);

		if (!empty($bankName)){
			$cName = html_entity_decode($bankName);
			$c1->addText('Ð‘Ð°Ð½Ðº: ' . $cName);
		}

		if (!empty($accountNumber)) $c1->addText('Ð˜Ð˜Ðš '.  $accountNumber);
		if (!empty($BankAddress)) $c1->addText('ÐÐ´Ñ€ÐµÑ Ð‘Ð°Ð½ÐºÐ°: ' . $BankAddress);
		if (!empty($swift_code)) $c1->addText('SWIFT ÐºÐ¾Ð´: ' . $swift_code);

/* 
		if (!empty($bill_pobox)) $c1->addText($bill_pobox);
		if (!empty($bill_code)) $c1->addText($bill_code);
		if (!empty($bill_city)) $c1->addText($bill_city);
		if (!empty($bill_state)) $c1->addText($bill_state);
		if (!empty($bill_country)) $c1->addText($bill_country);
		if (!empty($BankState)) $c1->addText($BankState);
		if (!empty($BankCity)) $c1->addText($BankCity);
		if (!empty($BankCountry)) $c1->addText($BankCountry); */

		$section->addTextBreak();
		$c1->addText($owner_status, array('bold' => true));
		$section->addTextBreak();
		$c1->addText("___________________________");
		$c1->addText($signatureContact, array('bold' => true));

		 
		$c2 = $table->addCell(5000, $cellColSpan2);
		$c2->addText("Ð¢ÐžÐž Â«Ð“Ð»Ð¾Ð±Ð°Ð»Ð¸Ð½ÐºÂ» Ð¢Ñ€Ð°Ð½ÑÐ¿Ð¾Ñ€Ñ‚ÑÐ¹ÑˆÐ½ ÑÐ½Ð´ Ð›Ð¾Ð´Ð¶Ð¸ÑÑ‚Ð¸ÐºÑ Ð’Ð¾Ñ€Ð»Ð´Ð²Ð°Ð¹Ð´Â»", array('bold' => true));
		$section->addTextBreak();
		$c2->addText("Ð®Ñ€Ð¸Ð´Ð¸Ñ‡ÐµÑÐºÐ¸Ð¹ Ð°Ð´Ñ€ÐµÑ: ÑƒÐ». ÐšÐ°Ð±Ð°Ð½Ð±Ð°Ð¹ Ð±Ð°Ñ‚Ñ‹Ñ€Ð°, ÑƒÐ³.ÑƒÐ». ÐšÑƒÑ€Ð¼Ð°Ð½Ð³Ð°Ð»Ð¸ÐµÐ²Ð° Ð´.52/1, Ð³. ÐÐ»Ð¼Ð°Ñ‚Ñ‹, Ð ÐµÑÐ¿ÑƒÐ±Ð»Ð¸ÐºÐ° ÐšÐ°Ð·Ð°Ñ…ÑÑ‚Ð°Ð½");
		$c2->addText("Ð‘Ð˜Ð 991140002859");
		$c2->addText("ÐÐž Â«ÐÐ°Ñ€Ð¾Ð´Ð½Ñ‹Ð¹ Ð‘Ð°Ð½Ðº ÐšÐ°Ð·Ð°Ñ…ÑÑ‚Ð°Ð½Ð°Â»");
		$c2->addText("ÐšÐ‘Ð• 17");

		$section->addTextBreak();
		$c2->addText("ÐÐž Â«ÐÐ°Ñ€Ð¾Ð´Ð½Ñ‹Ð¹ Ð‘Ð°Ð½Ðº ÐšÐ°Ð·Ð°Ñ…ÑÑ‚Ð°Ð½Ð°Â»");
		$c2->addText("Ð˜Ð˜Ðš KZ366017131000011267 (KZT)");
		$c2->addText("Ð‘Ð˜Ðš HSBKKZKX");

		$section->addTextBreak();
		$c2->addText("ÐÐž Â«First Heartland JÃ½san BankÂ»");
		$c2->addText("Ð˜Ð˜Ðš KZ52998CTB0000199305 (KZT)");
		$c2->addText("Ð‘Ð˜Ðš TSESKZKA");
		
		$section->addTextBreak();
		$c2->addText("Ð”Ð¸Ñ€ÐµÐºÑ‚Ð¾Ñ€", array('bold' => true));
		$section->addTextBreak();
		$c2->addText("___________________________");
		$c2->addText("Ð‘Ð°Ð»Ð°ÐµÐ² Ð .Ðž.", array('bold' => true));


		
/* 		$Ñ2->addText("Ð®Ñ€Ð¸Ð´Ð¸Ñ‡ÐµÑÐºÐ¸Ð¹ Ð°Ð´Ñ€ÐµÑ: ÑƒÐ». ÐšÐ°Ð±Ð°Ð½Ð±Ð°Ð¹ Ð±Ð°Ñ‚Ñ‹Ñ€Ð°, ÑƒÐ³.ÑƒÐ». ÐšÑƒÑ€Ð¼Ð°Ð½Ð³Ð°Ð»Ð¸ÐµÐ²Ð° Ð´.52/1, Ð³. ÐÐ»Ð¼Ð°Ñ‚Ñ‹, Ð ÐµÑÐ¿ÑƒÐ±Ð»Ð¸ÐºÐ° ÐšÐ°Ð·Ð°Ñ…ÑÑ‚Ð°Ð½");
		$Ñ2->addText("Ð‘Ð˜Ð 991140002859");
		$Ñ2->addText("ÐšÐ‘Ð• 17");
		
		$Ñ2->addText("ÐÐž Â«ÐÐ°Ñ€Ð¾Ð´Ð½Ñ‹Ð¹ Ð‘Ð°Ð½Ðº ÐšÐ°Ð·Ð°Ñ…ÑÑ‚Ð°Ð½Ð°Â»");
		$Ñ2->addText("Ð˜Ð˜Ðš KZ366017131000011267 (KZT)");
		$Ñ2->addText("Ð‘Ð˜Ðš HSBKKZKX");

		$Ñ2->addText("ÐÐž Â«First Heartland JÃ½san BankÂ»");
		$Ñ2->addText("Ð˜Ð˜Ðš KZ52998CTB0000199305 (KZT)");
		$Ñ2->addText("Ð‘Ð˜Ðš TSESKZKA");

		$Ñ2->addText("Ð”Ð¸Ñ€ÐµÐºÑ‚Ð¾Ñ€");
		$Ñ2->addText("___________________________");
		$Ñ2->addText("Ð‘Ð°Ð»Ð°ÐµÐ² Ð .Ðž."); */

		/* $table->addCell(4000, $cellColSpan2)->addText("Ð®Ñ€Ð¸Ð´Ð¸Ñ‡ÐµÑÐºÐ¸Ð¹ Ð°Ð´Ñ€ÐµÑ: ÑƒÐ». ÐšÐ°Ð±Ð°Ð½Ð±Ð°Ð¹ Ð±Ð°Ñ‚Ñ‹Ñ€Ð°, ÑƒÐ³.ÑƒÐ». ÐšÑƒÑ€Ð¼Ð°Ð½Ð³Ð°Ð»Ð¸ÐµÐ²Ð° Ð´.52/1, Ð³. ÐÐ»Ð¼Ð°Ñ‚Ñ‹, Ð ÐµÑÐ¿ÑƒÐ±Ð»Ð¸ÐºÐ° ÐšÐ°Ð·Ð°Ñ…ÑÑ‚Ð°Ð½", array('bold' => false), $cellHCentered);
		$table->addCell(4000, $cellColSpan2)->addText("Ð‘Ð˜Ð 991140002859", array('bold' => false), $cellHCentered);
		$table->addCell(4000, $cellColSpan2)->addText("ÐšÐ‘Ð• 17", array('bold' => false), $cellHCentered);
		
		$table->addCell(4000, $cellColSpan2)->addText("ÐÐž Â«ÐÐ°Ñ€Ð¾Ð´Ð½Ñ‹Ð¹ Ð‘Ð°Ð½Ðº ÐšÐ°Ð·Ð°Ñ…ÑÑ‚Ð°Ð½Ð°Â»", array('bold' => false), $cellHCentered);
		$table->addCell(4000, $cellColSpan2)->addText("Ð˜Ð˜Ðš KZ366017131000011267 (KZT)", array('bold' => false), $cellHCentered);
		$table->addCell(4000, $cellColSpan2)->addText("Ð‘Ð˜Ðš HSBKKZKX", array('bold' => false), $cellHCentered);

		$table->addCell(4000, $cellColSpan2)->addText("ÐÐž Â«First Heartland JÃ½san BankÂ»", array('bold' => false), $cellHCentered);
		$table->addCell(4000, $cellColSpan2)->addText("Ð˜Ð˜Ðš KZ52998CTB0000199305 (KZT)", array('bold' => false), $cellHCentered);
		$table->addCell(4000, $cellColSpan2)->addText("Ð‘Ð˜Ðš TSESKZKA", array('bold' => false), $cellHCentered);

		$table->addCell(4000, $cellColSpan2)->addText("Ð”Ð¸Ñ€ÐµÐºÑ‚Ð¾Ñ€", array('bold' => false), $cellHCentered);
		$table->addCell(4000, $cellColSpan2)->addText("___________________________", array('bold' => false), $cellHCentered);
		$table->addCell(4000, $cellColSpan2)->addText("Ð‘Ð°Ð»Ð°ÐµÐ² Ð .Ðž.", array('bold' => false), $cellHCentered);
 */

/* 		Ð­ÐºÑÐ¿ÐµÐ´Ð¸Ñ‚Ð¾Ñ€:

		Ð¢ÐžÐž Â«Ð“Ð»Ð¾Ð±Ð°Ð»Ð¸Ð½ÐºÂ» Ð¢Ñ€Ð°Ð½ÑÐ¿Ð¾Ñ€Ñ‚ÑÐ¹ÑˆÐ½ ÑÐ½Ð´ Ð›Ð¾Ð´Ð¶Ð¸ÑÑ‚Ð¸ÐºÑ Ð’Ð¾Ñ€Ð»Ð´Ð²Ð°Ð¹Ð´Â»
		Ð®Ñ€Ð¸Ð´Ð¸Ñ‡ÐµÑÐºÐ¸Ð¹ Ð°Ð´Ñ€ÐµÑ: ÑƒÐ». ÐšÐ°Ð±Ð°Ð½Ð±Ð°Ð¹ Ð±Ð°Ñ‚Ñ‹Ñ€Ð°, ÑƒÐ³.ÑƒÐ». ÐšÑƒÑ€Ð¼Ð°Ð½Ð³Ð°Ð»Ð¸ÐµÐ²Ð° Ð´.52/1, Ð³. ÐÐ»Ð¼Ð°Ñ‚Ñ‹, Ð ÐµÑÐ¿ÑƒÐ±Ð»Ð¸ÐºÐ° ÐšÐ°Ð·Ð°Ñ…ÑÑ‚Ð°Ð½
		Ð‘Ð˜Ð 991140002859
		ÐšÐ‘Ð• 17
		
		ÐÐž Â«ÐÐ°Ñ€Ð¾Ð´Ð½Ñ‹Ð¹ Ð‘Ð°Ð½Ðº ÐšÐ°Ð·Ð°Ñ…ÑÑ‚Ð°Ð½Ð°Â»
		Ð˜Ð˜Ðš KZ366017131000011267 (KZT)
		Ð‘Ð˜Ðš HSBKKZKX
		
		ÐÐž Â«First Heartland JÃ½san BankÂ»
		Ð˜Ð˜Ðš KZ52998CTB0000199305 (KZT)
		Ð‘Ð˜Ðš TSESKZKA
		
		Ð”Ð¸Ñ€ÐµÐºÑ‚Ð¾Ñ€ 
		
		___________________________
		Ð‘Ð°Ð»Ð°ÐµÐ² Ð .Ðž. */

		

		// Define table style arrays
		$styleTable = array('borderSize'=> 1, 'borderColor'=>'006699', 'cellMargin'=>120);
		$styleFirstRow = array('borderBottomSize'=>18, 'borderBottomColor'=>'0000FF', 'bgColor'=>'66BBFF');
			
		// Add table style
		$PHPWord->addTableStyle('myOwnTableStyle', $styleTable, $styleFirstRow);



		$filename = "Appendix-Form.docx";
		header("Content-Description: File Transfer");
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
		header('Content-Transfer-Encoding: binary');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Expires: 0');
		ob_clean();
		$objWriter = PHPWord_IOFactory::createWriter($PHPWord, 'Word2007');
		$objWriter->save("php://output");
 

/* 		// Save File
 		$objWriter = PHPWord_IOFactory::createWriter($PHPWord, 'Word2007');
		// header("Content-Type: text/html; charset=UTF-8");
		$filename = "storage/App-Form.docx";
		ob_clean();
		$objWriter->save("php://output");


  		// Ð¡Ñ€Ð°Ð·Ñƒ Ð·Ð°Ð¿ÑƒÑÐºÐ°ÐµÑ‚ Ñ„Ð°Ð¹Ð»
		if(!file_exists($filename)){ // file does not exist
				die('file not found');
		} else {
			header("Content-Description: File Transfer");
			header('Content-Disposition: attachment; filename="' . $filename . '"');
			header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
			header('Content-Transfer-Encoding: binary');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Expires: 0');
		
				// read the file from disk
			readfile($filename);
		} */
	

	 }


	 function splitTextByCondition($text){
		$newText = "";
		$wordsArr = array();
		$i = 0;
		do {
			if ($text[$i] == '{' && $text[$i + 1] == 's' && $text[$i + 2] == 'u' && $text[$i + 3] == 'm' 
													 && $text[$i + 4] == 'm' && $text[$i + 5] == '1' && $text[$i + 6] == '0'
													 && $text[$i + 7] == '0' && (isset($text[$i + 8]) && ($text[$i + 8] == '}'))){
				$wordsArr[] = $newText;
				$wordsArr[] = "{summ100}";
				$newText = "";
				$i = $i + 9;
			}	
			if ($text[$i] == '{' && $text[$i + 1] == 's' && $text[$i + 2] == 'u' && $text[$i + 3] == 'm' 
													 && $text[$i + 4] == 'm' && ($text[$i + 5] == '3' || $text[$i + 5] == '5' ||  $text[$i + 5] == '7') && $text[$i + 6] == '0'
													 && (isset($text[$i + 7]) && ($text[$i + 7] == '}'))){
	 
				$wordsArr[] = $newText;
				if ($text[$i + 5] == '3'){
				 	$wordsArr[] = "{summ30}";
				} else if ($text[$i + 5] == '5'){
					$wordsArr[] = "{summ50}"; 
				} else $wordsArr[] = "{summ70}";
				$newText = "";
				$i = $i + 8;
			}	
			$newText .= $text[$i];
			$i++;
		} while ($i < strlen($text));
			$wordsArr[] = $newText;
			$newText = "";
		
			return $wordsArr;
	}



	 

	 function divideAmountByPercentage($invoiceAmount, $percentage){
		$amountInNumberFormat = number_format((($invoiceAmount / 100) * $percentage), 2 , '.' , ' ');
		$dividedAmount = (($invoiceAmount / 100) * $percentage);
		$amountPart .= $amountInNumberFormat.' ('.$this->num2str($dividedAmount).' ';
		return $amountPart;
	 }


}