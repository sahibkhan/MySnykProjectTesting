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
	// 		ini_set('display_errors', 1);
	// ini_set('display_startup_errors', 1);
	// error_reporting(E_ALL);

		if($request->get('print_type') == 'word')
		{
		$this->get_word_file($request);
		exit;
		}
	}

	function process (Vtiger_Request $request)	{
		$db = PearDatabase::getInstance();
		$moduleName = $request->getModule();
		$record = $request->get('record');
		$module_data_info = Vtiger_Record_Model::getInstanceById($record, 'BO');
		$email = $this->get_display_name($module_data_info->get('assigned_user_id') , "users","email1");
		$account_details = Vtiger_Record_Model::getInstanceById($module_data_info->get('account_id'), 'Accounts');
		$account_info = $this->get_display_name($account_details->get('assigned_user_id') , "users","first_name")." ".$this->get_display_name($account_details->get('assigned_user_id') , "users","last_name")." ".$account_details->getDisplayValue('bill_city').", ".$account_details->getDisplayValue('bill_country');
		$breaks = array("<br />","<br>","<br/>");
		$breaks_html = array("&lt;br /&gt;", "&lt;br/&gt;", "&lt;br&gt;"); 
    //$text = str_ireplace($breaks, "\r\n", $text);
		$agent_sign = str_replace($breaks_html, "<br>", $this->get_agent_sign($record,$module_data_info->get('cf_1581')));
		$agentID = $module_data_info->get('cf_1581');
	
	 
		//$sign_details = $this->get_agent_sign($record,$module_data_info->get('cf_1581'));
	//	$sign_details = str_replace($breaks, "\r\n", $sign_details);

	 	//print_r($agent_sign); exit;
		$customer_sign = str_ireplace($breaks, "\r\n", $this->get_customer_sign($module_data_info->get('account_id')));
		$customer_name = $this->get_display_name($module_data_info->get('account_id'),"account","accountname");
		$forwarding_agent = $this->get_display_name($module_data_info->get('cf_1581'),"company","name");
		$destination = $module_data_info->getDisplayValue('cf_1463').", ".$this->get_display_name($module_data_info->get('cf_1283'),"countries","country_name");
		$name = $module_data_info->getDisplayValue('name');
		$request_date = date("Y-m-d", strtotime($module_data_info->get('createdtime')));
		$agreement_data = $this->get_agreement_no($module_data_info->get('cf_1295'),$module_data_info->get('account_id'),$request_date);
		$agrement = explode(',',$agreement_data);

		// Director Title

		$CustomerId = $module_data_info->get('account_id');
		$queryContactTitle = "select vtiger_contactdetails.*,vtiger_contactscf.* from vtiger_contactdetails
		INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_contactdetails.contactid
		INNER JOIN vtiger_contactscf ON vtiger_contactscf.contactid = vtiger_contactdetails.contactid
			where vtiger_crmentityrel.relmodule='Contacts' and
		vtiger_crmentityrel.crmid = ? and vtiger_contactscf.cf_2441 = 1 limit 1";
		$resultContactTitle = $db->pquery($queryContactTitle, array($CustomerId));
		$row_contact = $db->query_result($resultContactTitle,0,'cf_2395');
		 
		$contact_title = $db->query_result($resultContactTitle,0,'title');
		if (empty($contact_title)){
				$contact_title = 'Director';
		}
		// Manucher request: 04.12.2019
		if (in_array($CustomerId, array(3610))){
			 $contact_title = "FAO Representation in Tajikistan";
		}
		// End of Title

		$agreementno = $agrement[0];
		$agreement_date = $agrement[1];
		if(!empty($agreement_date))
		{
			$request_date = $agreement_date;
		}
		if($request->get('template') == 'eng_1')
		{
			$templatePrefix = 'BO_eng_1.html';
			if ($agentID == 85756) $templatePrefix = 'BO_eng_1_DWC.html';
			$document = $this->loadTemplate('printtemplates/BO/'.$templatePrefix);
		}
		elseif($request->get('template') == 'rus_1')
		{
			$templatePrefix = 'BO_rus_1.html';
			if ($agentID == 85756) $templatePrefix = 'BO_rus_1_DWC.html';
			$document = $this->loadTemplate('printtemplates/BO/'.$templatePrefix);
		}
		elseif($request->get('template') == 'eng_2')
		{
			$document = $this->loadTemplate('printtemplates/BO/BO_eng_2.html');
			$agent_sign = ''; //$account_info;
		}
		elseif($request->get('template') == 'rus_2')
		{
			$document = $this->loadTemplate('printtemplates/BO/BO_rus_2.html');
			$agent_sign = ''; //$account_info;
		}
		else{exit;}

		$request_date = date("Y-m-d", strtotime($module_data_info->get('createdtime')));
		$request_edit_date = date("Y-m-d", strtotime($module_data_info->get('modifiedtime')));

		// feedback user info 
		$this->setValue('email', $email, ENT_QUOTES, "UTF-8");
		$this->setValue('bo_ref', $module_data_info->getDisplayValue('cf_2687'), ENT_QUOTES, "UTF-8");
		$this->setValue('agreement_no', $agreementno, ENT_QUOTES, "UTF-8");
		$this->setValue('date_created', $request_date, ENT_QUOTES, "UTF-8");
		$this->setValue('forwarding_agent', $forwarding_agent, ENT_QUOTES, "UTF-8");
		$this->setValue('customer_name', $customer_name, ENT_QUOTES, "UTF-8");
		$this->setValue('quotation_no', $module_data_info->getDisplayValue('cf_1793'), ENT_QUOTES, "UTF-8");
		
		// $this->setValue('shipper_name', $module_data_info->getDisplayValue('cf_1275'), ENT_QUOTES, "UTF-8"); // Talha
		// $this->setValue('consignee_name', $module_data_info->getDisplayValue('cf_1465'), ENT_QUOTES, "UTF-8"); Talha

		$this->setValue('shipper_name', $module_data_info->getDisplayValue('cf_1275').', '.$module_data_info->getDisplayValue('cf_1465'), ENT_QUOTES, "UTF-8");
		$this->setValue('consignee_name', $module_data_info->getDisplayValue('cf_1277'), ENT_QUOTES, "UTF-8");
		$this->setValue('loading_place', $module_data_info->getDisplayValue('cf_6924'), ENT_QUOTES, "UTF-8");
		$this->setValue('order_no', $module_data_info->getDisplayValue('cf_6926'), ENT_QUOTES, "UTF-8");
		$this->setValue('destination', $module_data_info->getDisplayValue('cf_1467'), ENT_QUOTES, "UTF-8");
		$this->setValue('cargo_details', $module_data_info->getDisplayValue('cf_1471').", ".$module_data_info->getDisplayValue('cf_1309')."pc, ".$module_data_info->getDisplayValue('cf_1289')." ".$module_data_info->getDisplayValue('cf_1475').", ".$module_data_info->getDisplayValue('cf_1549').", ".$module_data_info->getDisplayValue('cf_1287')." ".$module_data_info->getDisplayValue('cf_1473'), ENT_QUOTES, "UTF-8");
		$this->setValue('hazard', $module_data_info->getDisplayValue('cf_6928'), ENT_QUOTES, "UTF-8");
		$this->setValue('terms_delivery', $module_data_info->getDisplayValue('cf_1297'), ENT_QUOTES, "UTF-8");
		$this->setValue('mode_transport', $module_data_info->getDisplayValue('cf_1573'), ENT_QUOTES, "UTF-8");
		$this->setValue('export_declaration', $module_data_info->getDisplayValue('cf_6930'), ENT_QUOTES, "UTF-8");
		$this->setValue('value_cargo', $module_data_info->getDisplayValue('cf_1469')." ".$module_data_info->getDisplayValue('cf_1727'), ENT_QUOTES, "UTF-8");
		$this->setValue('cargo_insurance', $module_data_info->getDisplayValue('cf_6932'), ENT_QUOTES, "UTF-8");
		$this->setValue('agreed_freight', $module_data_info->getDisplayValue('cf_1311'), ENT_QUOTES, "UTF-8");
		$this->setValue('transportation_cost', $account_details->getDisplayValue('cf_1855'), ENT_QUOTES, "UTF-8");
		$this->setValue('special_instruction', $module_data_info->getDisplayValue('cf_1801'), ENT_QUOTES, "UTF-8");
		$this->setValue('agent_details', $agent_sign, ENT_QUOTES, "UTF-8");
		$this->setValue('customer_details', $customer_sign, ENT_QUOTES, "UTF-8");
		$this->setValue('director_name', $this->get_director_name($record), ENT_QUOTES, "UTF-8");
		$this->setValue('contact_title', $contact_title, ENT_QUOTES, "UTF-8");
		//$this->setValue('completion_date', $module_data_info->getDisplayValue('cf_6604'), ENT_QUOTES, "UTF-8");
		
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
	// 				'.$module_data_info->getDisplayValue('name').'
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
	// 				$module_data_info->getDisplayValue("name").'
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
		
		// $mpdf->SetHTMLFooter('
		// 	<table width="80%" align="left" border="1" cellpadding="0" cellspacing="1">
		// 		<tr>
		// 			<td width="40%" align="left" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
		// 				Revision No. 
		// 			</td>
	  	// 			<td width="40%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
	  	// 				02
	  	// 			</td>
	  	// 			<td width="40%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
	  	// 				Date: '.date("Y-m-d").'
	  	// 			</td>
	  	// 			<td width="40%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
	  	// 				Document control No.
	  	// 			</td>
	  	// 			<td width="20%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
	  	// 				GLK/KZ/HR/SOP-01/F-03
	  	// 			</td>
  		// 		</tr>
  		// 		<tr>
  		// 			<td align="right" colspan="5"> Page {PAGENO} of {nbpg} </td>
  		// 		</tr>
		// 	</table>');

		$stylesheet = file_get_contents('include/mpdf60/examples/mpdfstyletables.css');
		$mpdf->WriteHTML($stylesheet,1);	// The parameter 1 tells that this is css/style only and no body/html/text
		
		$mpdf->WriteHTML($this->_documentXML); /*Ñ„Ð¾Ñ€Ð¼Ð¸Ñ€ÑƒÐµÐ¼ pdf*/
		$pdf_name = "pdf_docs_new/BO/BO_" . $record . ".pdf";
		$mpdf->Output($pdf_name, 'D');

		// header('Location:http://mb.globalink.net/vt60/'.$pdf_name);

		//header('Location:' . $pdf_name);
		//unlink($pdf_name);
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

	private function get_word_file (Vtiger_Request $request) {
 		ini_set('display_errors', 1);
 error_reporting(E_ALL);
		//fetch data to add in word file



		$moduleName = $request->getModule();
		$record = $request->get('record');
		$module_data_info = Vtiger_Record_Model::getInstanceById($record, 'InvoiceGLK');


		//set data in template file using PHPWord library
		require_once 'libraries/PHPWord/PHPWord.php';

		$PHPWord = new PHPWord();

			$templatePrefix = 'Appendix.docx';
 			$document = $PHPWord->loadTemplate('include/InvoiceGLK/'.$templatePrefix);
	
			
	
		//echo "<pre>"; print_r($document); 
		//exit;
		// $document->setValue('weekday', date('l'));
		// $document->setValue('time', date('H:i'));

		$temp_file = tempnam(sys_get_temp_dir(), 'PHPWord');
		ob_clean();
		$document->save($temp_file);
		
		// Your browser will name the file "myFile.docx"
		// regardless of what it's named on the server 
		header("Content-Disposition: attachment; filename=App".date('h-m-i').".docx");
		readfile($temp_file); // or echo file_get_contents($temp_file);
		unlink($temp_file);  // remove temp file
		exit;
	}

	protected function get_agent_sign($boid,$AgentId){
		$db = PearDatabase::getInstance();
		$query = 'select cc.name, c.* from vtiger_bocf b 
   						  inner join vtiger_company cc on cc.companyid = b.cf_1581 
						  inner join vtiger_companycf c on c.companyid = b.cf_1581 
						  where b.boid = "'.$boid.'"';
		$result = $db->pquery($query, array());

   $director_name = $db->query_result($result,0,'cf_2431');
   
	   
	if ($AgentId == 85757){
		$BOSign = $db->query_result($result,0,'name').'&lt;br/&gt; Legal address: '.$db->query_result($result,0,'cf_1867').'';
	} else {
		$BOSign = $db->query_result($result,0,'name').'&lt;br/&gt;Legal address:&lt;br/&gt; '.$db->query_result($result,0,'cf_1867').'&lt;br/&gt; &lt;br/&gt; TRN '.$db->query_result($result,0,'cf_1869').'&lt;br/&gt;IBAN: '.$db->query_result($result,0,'cf_1871').'&lt;br/&gt;SWIFT: '.$db->query_result($result,0,'cf_1873').'&lt;br/&gt;'.$db->query_result($result,0,'cf_2433').'&lt;br/&gt;BIN '.$db->query_result($result,0,'cf_1875').'&lt;br/&gt;Bank '.$db->query_result($result,0,'cf_1877');
	}
	return strip_tags($BOSign);
	}

	protected function get_customer_sign($CustomerId){
		$db = PearDatabase::getInstance();
		$query = "select acc.*,acc_b.*,acc_cf.*
							from vtiger_account as acc 
							LEFT JOIN vtiger_accountbillads as acc_b ON acc.accountid = acc_b.accountaddressid
							LEFT JOIN vtiger_accountscf as acc_cf ON acc_cf.accountid = acc.accountid		where acc.accountid = ".$CustomerId;
		$result = $db->pquery($query, array());
        //$row_bo = mysql_fetch_array($q_bo);
        $CustomerName = $db->query_result($result,0,'cf_2395');
		$customerAddress = $db->query_result($result,0,'bill_street');
		$bill_pobox = $db->query_result($result,0,'bill_pobox');
		$bill_code = $db->query_result($result,0,'bill_code');
		$bill_city = $db->query_result($result,0,'bill_city');
		$bill_state = $db->query_result($result,0,'bill_state');
		$bill_country = $db->query_result($result,0,'bill_country');
		$swift_code = $db->query_result($result,0,'cf_2429');
		$BankAddress = $db->query_result($result,0,'cf_1837');
		$BankState = $db->query_result($result,0,'cf_1849');
		$BankCity = $db->query_result($result,0,'cf_1845');
		$BankCountry = $db->query_result($result,0,'cf_1841');

        $query = "select * from vtiger_accountscf where accountid=".$CustomerId;
        $result = $db->pquery($query, array());
        $BankName = $db->query_result($result,0,'cf_1833');
        $AccountNo = $db->query_result($result,0,'cf_1835');
       // $BankAddress = $db->query_result($result,0,'cf_1837');
       // $BankCountry = $db->query_result($result,0,'cf_1841');
        //$BankCity = $db->query_result($result,0,'cf_1845');
        //$BankState = $db->query_result($result,0,'cf_1849');
		
		
        if (!empty($CustomerName)) $BOClSign = $CustomerName.'<br>';
		if (!empty($customerAddress)) $BOClSign .= $customerAddress.'<br>';
		if (!empty($bill_pobox)) $BOClSign .= $bill_pobox.'<br>';
		if (!empty($bill_code)) $BOClSign .= $bill_code.'<br>';
		if (!empty($bill_city)) $BOClSign .= $bill_city.'<br>';
		if (!empty($bill_state)) $BOClSign .= $bill_state.'<br>';
		if (!empty($bill_country)) $BOClSign .= $bill_country.'<br>';
		$BOClSign .= '<br>';
		if (!empty($BankName)) $BOClSign .= $BankName.'<br>';
		if (!empty($AccountNo)) $BOClSign .= $AccountNo.'<br>';
		if (!empty($swift_code)) $BOClSign .= $swift_code.'<br>';		
		if (!empty($BankAddress)) $BOClSign .= $BankAddress.'<br>';
		if (!empty($BankState)) $BOClSign .= $BankState.'<br>';		
		if (!empty($BankCity)) $BOClSign .= $BankCity.'<br>';
		if (!empty($BankCountry)) $BOClSign .= $BankCountry.'<br>';	
		return $BOClSign;
	}

	protected function get_display_name($recordid,$tablename,$fieldname)
	{
		$db = PearDatabase::getInstance();
		$name = "";
		if($tablename == "countries")
		{
			$query = "select ".$fieldname." FROM ".$tablename." WHERE country_code='".$recordid."'";
		
		}
		elseif($tablename == "users")
		{
			$query = "select ".$fieldname." FROM vtiger_".$tablename." WHERE vtiger_".$tablename.".id=".$recordid;
		}
		else
		{
			$query = "select ".$fieldname." FROM vtiger_".$tablename." WHERE vtiger_".$tablename.".".$tablename."id=".$recordid;
		}
		//echo $query; //exit;
		$result = $db->pquery($query, array());
		$name = $db->query_result($result,0,$fieldname);
		return ($name != '') ? $name:$recordid;
	}

	protected function get_agreement_no($jobref,$customerid,$bodate)
	{
		$adb = PearDatabase::getInstance();
		$job_query = $adb->pquery('SELECT * from vtiger_jobcf WHERE cf_1198="'.$jobref.'"');
		$row_job = $adb->fetch_array($job_query);
		$DepId = $row_job['cf_1190'];
		$agreement_where = " AND vtiger_serviceagreementcf.cf_6068='Freight Forwarding' ";
		if($DepId=='85837')
		{
			$agreement_where = " AND vtiger_serviceagreementcf.cf_6068='Customs Brokerage' ";
		}
		
	   $service_agreement_sql = 	$adb->pquery("SELECT vtiger_serviceagreement.name as agreement_no, vtiger_serviceagreementcf.cf_6018 as agreement_date, vtiger_serviceagreementcf.cf_6020 as expiry_date, vtiger_serviceagreementcf.cf_6026 as globalink_company FROM vtiger_serviceagreement 
									INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_serviceagreement.serviceagreementid
									INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid 
									INNER JOIN vtiger_serviceagreementcf ON vtiger_serviceagreementcf.serviceagreementid = vtiger_serviceagreement.serviceagreementid
									WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid='".$customerid."' AND crmentityrel.module='Accounts' 
									AND crmentityrel.relmodule='ServiceAgreement'  AND  vtiger_serviceagreementcf.cf_6026 ='".$row_job['cf_1186']."' AND  vtiger_serviceagreementcf.cf_6066='Customer'  ".$agreement_where."
									AND '".$bodate."' between vtiger_serviceagreementcf.cf_6018 AND vtiger_serviceagreementcf.cf_6020
									order by vtiger_crmentity.createdtime DESC limit 1");
	   $r_service_agreement = $adb->fetch_array($service_agreement_sql);							  
	   $job_agreement_no = $r_service_agreement['agreement_no'].",".$r_service_agreement['agreement_date'];
	   return ($job_agreement_no != '') ? $job_agreement_no:'';
	}

	protected function get_director_name($record)
	{
		$adb = PearDatabase::getInstance();
		$q_comp = $adb->pquery('select cc.name, c.* from vtiger_bocf b 
							inner join vtiger_company cc on cc.companyid = b.cf_1581 
							inner join vtiger_companycf c on c.companyid = b.cf_1581 
							where b.boid = "'.$record.'"');
		$row_comp = $adb->fetch_array($q_comp);
		$director_name = $row_comp['cf_2431'];
		return ($director_name != '') ? $director_name:'';
	}
}