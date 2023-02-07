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
		$nul='ноль';
		$ten=array(
			array('','один','два','три','четыре','пять','шесть','семь', 'восемь','девять'),
			array('','одна','две','три','четыре','пять','шесть','семь', 'восемь','девять'),
		);
		$a20=array('десять','одиннадцать','двенадцать','тринадцать','четырнадцать' ,'пятнадцать','шестнадцать','семнадцать','восемнадцать','девятнадцать');
		$tens=array(2=>'двадцать','тридцать','сорок','пятьдесят','шестьдесят','семьдесят' ,'восемьдесят','девяносто');
		$hundred=array('','сто','двести','триста','четыреста','пятьсот','шестьсот', 'семьсот','восемьсот','девятьсот');
		$unit=array( // Units
			array('тиын' ,'тиыны' ,'тиын',	 1),
			array('тенге'   ,'тенге'   ,'тенге'    ,0),
			array('тысяча'  ,'тысячи'  ,'тысяч'     ,1),
			array('миллион' ,'миллиона','миллионов' ,0),
			array('миллиард','милиарда','миллиардов',0),
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
	 * Склоняем словоформу
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
  	$month = [0,'Января','Февраля','Марта','Апреля','Мая','Июня','Июля','Августа','Сентября','Октября','Ноября','Декабря'];
  	$b = explode('-',$a);
  	$c = '«'.$b[2].'» '.$month[(int)$b[1]].' '.$b[0];
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
			vtiger_jobexpencereportcf.cf_1455

			FROM `vtiger_jobexpencereport` 
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
			INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid ) 
			Left JOIN vtiger_jobexpencereportcf as vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid	
			Left JOIN vtiger_cf_1234 ON vtiger_cf_1234.cf_1234id = vtiger_jobexpencereportcf.cf_1234	

			WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid = ? AND vtiger_crmentityrel.module='Job' 
			AND vtiger_crmentityrel.relmodule='Jobexpencereport' AND vtiger_jobexpencereportcf.cf_1457='Selling'", array($jobFileId));
		$nRows = $adb->num_rows($queryCharges);
		for ($i=0; $i<$nRows; $i++){
			$chargeId = $adb->query_result($queryCharges, $i, 'cf_1455');
			$chargeCost = $adb->query_result($queryCharges, $i, 'sell_local_currency_net');
			$currencyCode = $adb->query_result($queryCharges, $i, 'currencycode');
			$description = $adb->query_result($queryCharges, $i, 'description');
			$invoiceDate = $adb->query_result($queryCharges, $i, 'sell_invoice_date');
 
			$chargeName = $this->getChartOfAccount($chargeId);			
			$charges[] = array('chargeName' => $chargeName, 'currencyCode' => $currencyCode, 'chargeCost' => $chargeCost, 'description' => $description, "invoiceDate" => $invoiceDate);
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
		$invoiceDateFromAppendix = $appendixInfo->get('cf_1439');
		$invoiceDate = $this->dateConvert($invoiceDateFromAppendix);

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
				$weight = $jobFileInfo->get('cf_4945') . 'кг.';

			} else if ($jobFileInfo->get('cf_1084') >= $jobFileInfo->get('cf_1086')){

				// Weight				
				if ($jobFileInfo->get('cf_1520') == 'KG') {
					$weightUnit = 'кг.';
				} else if ($jobFileInfo->get('cf_1520') == 'LBS') {
					$weightUnit = 'фунт';
				} else if ($jobFileInfo->get('cf_1520') == 'TON') {
					$weightUnit = 'тонн';
				}
				$weight = $jobFileInfo->get('cf_1084').' '.$weightUnit;
				
			} else if ($jobFileInfo->get('cf_1084') <= $jobFileInfo->get('cf_1086')){
				
				// Volume 
				if ($jobFileInfo->get('cf_1522') == 'KG') {
					$weightUnit = 'кг.';
				} else if ($jobFileInfo->get('cf_1522') == 'CBM') {
					$weightUnit = 'фунт';			
				}
				$weight = $jobFileInfo->get('cf_1086').' '.$weightUnit;

			}
			
			if ($jobFileInfo->get('cf_1441')){
				$customer_name = strip_tags($jobFileInfo->getDisplayValue('cf_1441'));
				$accountInfo = Vtiger_Record_Model::getInstanceById($jobFileInfo->get('cf_1441'), 'Accounts');
				$accountLocation = $accountInfo->get('bill_country').', '.$accountInfo->get('bill_city');
			}

		} else {
			$jobFileRef = '';
		}

		// Getting Agreement Info

		$agreementInfo = $this->getServiceAgreement($jobFileInfo->get('cf_1441'));	
		if ($agreementInfo){
			$agreement = $agreementInfo['agency_agreement'].' от '.$agreementInfo['agency_agreement_date'];
		} else {
			$agreement = $accountInfo->get('cf_1853').' от '.$this->dateConvert($accountInfo->get('cf_1859'));	
		}


		// Getting job file info
		$contactType = 'Директора';
		$appType = 'устава';

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
				$ax_Contact = 'Финансовго Директора '.$ax_Contact.', действующего на основании Генеральной Доверенности от '.$a;
				$owner_status = 'Финансовый Директор';
			} elseif ($contactPosition == 'Director') {			
				$owner_status = 'Директор';
				$ax_Contact = 'Директора '.$ax_Contact.', действующего на основании Устава';
			} elseif ($contactPosition == 'President') {
				$owner_status = 'Президент';
				$ax_Contact = 'Президента '.$ax_Contact.', действующего на основании Устава';
			} elseif ($contactPosition == 'CEO') {
				$owner_status = 'Генеральный директор';
				$ax_Contact = 'Генерального Директора '.$ax_Contact.', действующего на основании Устава';
			} elseif ($contactPosition == 'Regional Representative'){

				$owner_status = 'Региональный представитель';
				$ax_Contact = 'Регионального представителя '.$ax_Contact.', действующего на основании Устава';
				
			} elseif ($contactPosition == 'Attorney'){

				$owner_status = 'Поверенное лицо';
				$ax_Contact = 'Поверенного лица '.$ax_Contact.', действующего на основании Устава';

		  } elseif ($contactPosition == 'Managing Partner'){

				$owner_status = 'Управляющий партнер';
				$ax_Contact = 'Управляющего партнера '.$ax_Contact.', действующего на основании Устава';
		  } elseif ($contactPosition == 'Manager'){

				$owner_status = 'Управляющий';
				$ax_Contact = 'Управляющего '.$ax_Contact.', действующего на основании Устава';
		  } elseif ($contactPosition == 'Leader'){

				$owner_status = 'Руководитель ';
				$ax_Contact = 'Руководителя '.$ax_Contact.', действующего на основании Устава';

			} elseif ($contactPosition == 'Other') {
				$a = explode('-',$powerOfAttorney);
				$a = $a[2].'.'.$a[1].'.'.$a[0];
				$ax_Contact = $ax_Contact.', действующего на основании Генеральной Доверенности от '.$a;
				$owner_status = '';
			}


			$invoiceAmount = 0;
			$chargeList = $this->getJobFileCharges($jobFileId);	
			foreach ($chargeList as $charge){
				$invoiceAmount += $charge['chargeCost'];
				if (!empty($charge['invoiceDate'])) $invoiceDateFromExpense = $charge['invoiceDate'];
			}

			$invoiceAmountFormat = number_format($invoiceAmount);
			// $invoiceDate = $this->dateConvert($invoiceDateFromExpense);

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
		$section->addText("Приложение $job", $boldText, $textCenter);
		$section->addText("к Договору на транспортно-экспедиторское обслуживание", $boldText, $textCenter);
		$section->addText($agreement, $boldText, $textCenter);
		$section->addTextBreak();
		$section->addTextBreak();		
		$textRun = $section->createTextRun();
		
		$textRun->addText("г. Алматы", $boldText);
		$textRun->addText("                                                                                                                         ".$invoiceDate."  г.", $boldText);
				
		$textRun1 = $section->createTextRun();
		$textRun1->addText($customer_name, $boldText);
		$textRun1->addText(" именуемое в дальнейшем ");
		$textRun1->addText("«Клиент», ", $boldText);
		$textRun1->addText("в лице $ax_Contact, с одной стороны, и ");
		$textRun1->addText("ТОО «Глобалинк Транспортэйшн энд Лоджистикс Ворлдвайд»,", $boldText);
		$textRun1->addText("именуемое в дальнейшем ");
		$textRun1->addText("«Экспедитор», ", $boldText);
		$textRun1->addText("в лице Директора Балаева Р.О., действующего на основании Устава, с другой стороны, совместно именуемые ");
		$textRun1->addText("«Стороны», ", $boldText);
		$textRun1->addText("заключили настоящее приложение о нижеследующем:");
		

		$textRun1 = $section->createTextRun();
		$textRun1->addText("1. Экспедитор принимает на себя обязательство организовать и произвести  следующие услуги: \n");
		if ($n_insuranceRel > 0) $additionalService = ",страхование";
		$textRun1->addText("Транспортировка груза ".$additionalService."                                        ", array('bold' => true));
		$textRun1->addText("                                                                         а Клиент обязуется оплатить выполненные Экспедитором услуги. ");


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
		$table->addCell(2000, $cellVCentered)->addText('Номер работы', array('bold' => false), $cellHCentered);
		$table->addCell(2200, $cellVCentered)->addText('Наименование товара', array('bold' => false), $cellHCentered);
		$table->addCell(2000, $cellVCentered)->addText('Кол-во мест', array('bold' => false), $cellHCentered);
		$table->addCell(1800, $cellColSpan2)->addText('Оплачиваемый вес, кг', array('bold' => false), $cellHCentered);
		$table->addCell(2000, $cellColSpan2)->addText('Прочие условия', array('bold' => false), $cellHCentered);
		
		$table->addRow(null, array('tblHeader' => true));
		$table->addCell(2000, $cellColSpan2)->addText($job, array('bold' => true), $cellHCentered);
		$table->addCell(2000, $cellColSpan2)->addText($commodity, array('bold' => true), $cellHCentered);
		$table->addCell(2000, $cellColSpan2)->addText($noOfPieces, array('bold' => true), $cellHCentered);
		$table->addCell(2000, $cellColSpan2)->addText($weight, array('bold' => true), $cellHCentered);
		$table->addCell(2000, $cellColSpan2)->addText('Нет', array('bold' => true), $cellHCentered);
		
		$section->addTextBreak();
		

		$PHPWord->addTableStyle('Colspan Rowspan', $styleTable);
		$table = $section->addTable('Colspan Rowspan');

		$table->addRow(null, array('tblHeader' => true));		
		$table->addCell(8000, $cellColSpan2)->addText('', array('bold' => false), $cellHCentered);
		$table->addCell(2000, $cellColSpan2)->addText('Стоимость услуг, тенге', array('bold' => false), $cellHCentered);

		

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

/* 		$textRun2->addText("2. Стоимость услуг, осуществляемых в рамках Договора ");
		$textRun2->addText($agreement, $boldText);
		$textRun2->addText(" тенге ______ 00 тиын ", $boldText);
		$textRun2->addText("(включая страхование). Клиент производит предоплату в размере 100 % от общей стоимости услуг  по настоящему Приложению, что составляет сумму в размере: ");
		$textRun2->addText("тенге ______ 00 тиын ", $boldText); */

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
 
		$textRun2->addText(" путем перечисления денежных средств на расчетный счет Экспедитора в течение 3 (трех) банковских дней с даты подписания соответствующего Приложения и получения счета на предоплату.");
		
		// $section->addTextBreak();
		$section->addText("Для взаиморасчетов учитывается курс Национального Банка/KASE/Народного Банка на дату:", ['bold' => true, 'italic' => true]);
		$section->addListItem('Выставления счета на оплату, при условии оплаты на предоплатной основе;', 0, ['bold' => true, 'italic' => true]);
		$section->addListItem('Совершения оборота по реализации (дата акта выполненных работ , при условии оплаты по факту оказания услуг.', 0, ['bold' => true, 'italic' => true]);
		
		// $section->addTextBreak();
		$section->addText("3. Грузополучателем груза по настоящему Приложению ".$jobFileRef." является:");
		$section->addText($customer_name, $boldText);
		if ($accountLocation) $section->addText($accountLocation, $boldText);
		
		// $section->addTextBreak();
		$section->addText("4. Прочие условия и пункты, не оговоренные в настоящем Приложении ".$jobFileRef.", действуют в соответствии с Договором ".$agreement);
		// $section->addTextBreak();
		$section->addText("5. Данное Приложение ".$jobFileRef." является неотъемлемой частью Договора ".$agreement);
		// $section->addTextBreak();
		$section->addText("6. Данное Приложение ".$jobFileRef." к  Договору ".$agreement."  года вступает в силу с момента его подписания сторонами. Срок действия данного Приложения истекает вместе со сроком действия Договора ".$agreement);
		// $section->addTextBreak();
		$section->addText("7. Услуги Экспедитора регламентируются генеральными условиями, которые могут ограничить ответственность Экспедитора в случае утраты или порчи Груза. Ознакомиться с генеральными условиями можно на веб-сайте: http://globalinklogistics.com/Trading-Terms-and-Conditions. В случае частичной или полной утраты или повреждения Груза, произошедшей в процессе транспортировки, Экспедитор содействует в возмещении Клиенту стоимости нанесенного материального ущерба страховой компанией. В случае отказа страховой компании от выплаты возмещения Клиенту, а также если страховой случай произошел по доказанной вине Экспедитора, Экспедитор возмещает утрату или повреждение груза в соответствии с  применимыми международными Конвенциями и Соглашениями в сфере транспорта, включая, но не ограничиваясь КДПГ, СМГС, Варшавская Конвенция 1929 г., Монтреальская Конвенция, и.т.д. Экспедитор освобождается от любой ответственности в случае, если Клиенту было отказано в возмещении по правилам/договору страхования. Ни при каких обстоятельствах, Экспедитор не несет ответственность за косвенные убытки, задержки, потерю прибыли, потерю рынка и ликвидные убытки.");
		// $section->addTextBreak();
		$section->addText("8. В случае, девальвации тенге к доллару США / Евро более чем на 5% в период между датой выдачи счета-фактуры до даты получения платежа в банковский счет экспедитора, экспедитор имеет право произвести перерасчёт суммы счет-фактуры с применением коэффициента индексации девальвации.");
		// $section->addTextBreak();
		$section->addText("Примечание* Дата электронной счет-фактуры не является основанием для пересчета суммы по курсу.", ['underline' => 'signle']);
		
		$section->addText("9. Настоящим Заказчик отказывается от права суброгации в отношении ЭКСПЕДИТОРА, его руководства, участников, агентов и сотрудников, возникающего вследствие утраты или причинения ущерба грузу или имуществу в пределах размера такой утраты или ущерба, вне зависимости от наличия страхования и от того, кто является Страхователем.");
		$section->addText("10. ОГРАНИЧЕНИЕ ОТВЕТСТВЕННОСТИ: ЭКСПЕДИТОР НЕСЕТ ОТВЕТСТВЕННОСТЬ ИСКЛЮЧИТЕЛЬНО ЗА УТРАТУ ИЛИ УЩЕРБ ПРИЧИНЕННЫЕ ВСЛЕДСТВИЕ ЕГО ХАЛАТНОСТИ ИЛИ НАРУШЕНИЯ ДОГОВОРНЫХ ОБЯЗАТЕЛЬСТВ В ПРЕДЕЛАХ НАИМЕНЬШЕГО ИЗ СЛЕДУЮЩИХ ЗНАЧЕНИЙ: I) 20 ДОЛЛ. США ЗА КИЛОГРАММ ГРУЗА В УПАКОВКЕ (БРУТТО), ИЛИ II) СТОИМОСТЬ УСЛУГ ЭКСПЕДИТОРА, ОПЛАЧИВАЕМАЯ ЗАКАЗЧИКОМ ЗА СООТВЕТСТВУЮЩУЮ ПЕРЕВОЗКУ. НИ ПРИ КАКИХ ОБСТОЯТЕЛЬСТВАХ ЭКСПЕДИТОР НЕ НЕСЕТ ОТВЕТСТВЕННОСТЬ ЗА КАКОЙ-ЛИБО КОСВЕННЫЙ, НЕПРЕДНАМЕРЕННЫЙ УЩЕРБ И/ИЛИ ЗА УПУЩЕННУЮ ВЫГОДУ");
		$section->addText("Наше назначение в качестве экспедитора или перевозчика для выполнения перевозки будет рассматриваться как принятие данных условий.");
		
		// $section->addTextBreak();
		// $section->addTextBreak();
		$section->addText("11. Юридические адреса и банковские реквизиты сторон:", $boldText);


		// requisites table
/* 		$requisitesTable = $section->addTable('tableStyle');
		$requisitesTable->addRow(600);
		$clientColumnCell = $requisitesTable->addCell(0, ['borderSize' => 6, 'borderColor' => '000000']);
		$clientColumnCellTextRun = $clientColumnCell->createTextRun();
		$clientColumnCellTextRun->addText("Клиент: ", $boldText);

		
		$forwarderColumnCell = $requisitesTable->addCell(0, ['borderSize' => 6, 'borderColor' => '000000']);
		$forwarderColumnCellTextRun = $forwarderColumnCell->createTextRun();
		$forwarderColumnCellTextRun->addText("Клиент: ", $boldText); */


		$table = $section->addTable('Colspan Rowspan');
		$table->addRow(null, array('tblHeader' => true));
		$table->addCell(4000, )->addText('Клиент', array('bold' => true), $cellHCentered);
		$table->addCell(5000, $cellVCentered)->addText('Экспедитор', array('bold' => true), $cellHCentered);
		
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

		if (!empty($customerAddress)) $c1->addText('Юридический адрес: '.$customerAddress);
		if (!empty($bic)) $c1->addText('БИН: '.$bic);
		if (!empty($ppn)) $c1->addText('РНН: ' . $ppn);
		if (!empty($kbe)) $c1->addText('КБЕ: '. $kbe);

		if (!empty($bankName)){
			$cName = html_entity_decode($bankName);
			$c1->addText('Банк: ' . $cName);
		}

		if (!empty($accountNumber)) $c1->addText('ИИК '.  $accountNumber);
		if (!empty($BankAddress)) $c1->addText('Адрес Банка: ' . $BankAddress);
		if (!empty($swift_code)) $c1->addText('SWIFT код: ' . $swift_code);

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
		$c2->addText("ТОО «Глобалинк» Транспортэйшн энд Лоджистикс Ворлдвайд»", array('bold' => true));
		$section->addTextBreak();
		$c2->addText("Юридический адрес: ул. Кабанбай батыра, уг.ул. Курмангалиева д.52/1, г. Алматы, Республика Казахстан");
		$c2->addText("БИН 991140002859");
		$c2->addText("АО «Народный Банк Казахстана»");
		$c2->addText("КБЕ 17");

		$section->addTextBreak();
		$c2->addText("АО «Народный Банк Казахстана»");
		$c2->addText("ИИК KZ366017131000011267 (KZT)");
		$c2->addText("БИК HSBKKZKX");

		$section->addTextBreak();
		$c2->addText("АО «First Heartland Jýsan Bank»");
		$c2->addText("ИИК KZ52998CTB0000199305 (KZT)");
		$c2->addText("БИК TSESKZKA");
		
		$section->addTextBreak();
		$c2->addText("Директор", array('bold' => true));
		$section->addTextBreak();
		$c2->addText("___________________________");
		$c2->addText("Балаев Р.О.", array('bold' => true));


		
/* 		$с2->addText("Юридический адрес: ул. Кабанбай батыра, уг.ул. Курмангалиева д.52/1, г. Алматы, Республика Казахстан");
		$с2->addText("БИН 991140002859");
		$с2->addText("КБЕ 17");
		
		$с2->addText("АО «Народный Банк Казахстана»");
		$с2->addText("ИИК KZ366017131000011267 (KZT)");
		$с2->addText("БИК HSBKKZKX");

		$с2->addText("АО «First Heartland Jýsan Bank»");
		$с2->addText("ИИК KZ52998CTB0000199305 (KZT)");
		$с2->addText("БИК TSESKZKA");

		$с2->addText("Директор");
		$с2->addText("___________________________");
		$с2->addText("Балаев Р.О."); */

		/* $table->addCell(4000, $cellColSpan2)->addText("Юридический адрес: ул. Кабанбай батыра, уг.ул. Курмангалиева д.52/1, г. Алматы, Республика Казахстан", array('bold' => false), $cellHCentered);
		$table->addCell(4000, $cellColSpan2)->addText("БИН 991140002859", array('bold' => false), $cellHCentered);
		$table->addCell(4000, $cellColSpan2)->addText("КБЕ 17", array('bold' => false), $cellHCentered);
		
		$table->addCell(4000, $cellColSpan2)->addText("АО «Народный Банк Казахстана»", array('bold' => false), $cellHCentered);
		$table->addCell(4000, $cellColSpan2)->addText("ИИК KZ366017131000011267 (KZT)", array('bold' => false), $cellHCentered);
		$table->addCell(4000, $cellColSpan2)->addText("БИК HSBKKZKX", array('bold' => false), $cellHCentered);

		$table->addCell(4000, $cellColSpan2)->addText("АО «First Heartland Jýsan Bank»", array('bold' => false), $cellHCentered);
		$table->addCell(4000, $cellColSpan2)->addText("ИИК KZ52998CTB0000199305 (KZT)", array('bold' => false), $cellHCentered);
		$table->addCell(4000, $cellColSpan2)->addText("БИК TSESKZKA", array('bold' => false), $cellHCentered);

		$table->addCell(4000, $cellColSpan2)->addText("Директор", array('bold' => false), $cellHCentered);
		$table->addCell(4000, $cellColSpan2)->addText("___________________________", array('bold' => false), $cellHCentered);
		$table->addCell(4000, $cellColSpan2)->addText("Балаев Р.О.", array('bold' => false), $cellHCentered);
 */

/* 		Экспедитор:

		ТОО «Глобалинк» Транспортэйшн энд Лоджистикс Ворлдвайд»
		Юридический адрес: ул. Кабанбай батыра, уг.ул. Курмангалиева д.52/1, г. Алматы, Республика Казахстан
		БИН 991140002859
		КБЕ 17
		
		АО «Народный Банк Казахстана»
		ИИК KZ366017131000011267 (KZT)
		БИК HSBKKZKX
		
		АО «First Heartland Jýsan Bank»
		ИИК KZ52998CTB0000199305 (KZT)
		БИК TSESKZKA
		
		Директор 
		
		___________________________
		Балаев Р.О. */

		

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


  		// Сразу запускает файл
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