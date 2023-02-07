<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Job_Print_View extends Vtiger_Print_View {
	
	
	/**
     * Temporary Filename
     * 
     * @var string
     */
    private $_tempFileName;
	
	function __construct() {
		parent::__construct();
		ob_start();			
	}
	
	function checkPermission(Vtiger_Request $request) {
		return true;
	}
		
		
	

	function process(Vtiger_Request $request) {
		$rtype = $request->get('rtype');	
		$type = $request->get('type');	
		
		if(!empty($rtype))
		{
			$this->print_qsr($request);
		}
		elseif(!empty($type))
		{
			$this->print_pod($request);
		}
		else
		{
			
			$this->print_coverletter($request);
		}
		
	}
	
	public function print_coverletter($request)
	{		
		//error_reporting(E_ALL);
		$moduleName = $request->getModule();
		$record = $request->get('record');
		
		$current_user = Users_Record_Model::getCurrentUserModel();		
		$job_info_detail = Vtiger_Record_Model::getInstanceById($record, 'Job');
		
				
		$document = $this->loadTemplate('printtemplates/Job/job_cover.html');
		
		$this->setValue('ref_no',$job_info_detail->get('cf_1198'));
		$this->setValue('CreatedTime',date('d/m/Y',strtotime($job_info_detail->get('CreatedTime'))));		
		
		$this->setValue('type',strtoupper($job_info_detail->get('cf_1200')));
		
		$this->setValue('shipper',$job_info_detail->get('cf_1072'));
		$this->setValue('consignee',$job_info_detail->get('cf_1074'));
		
		$this->setValue('origin_agent',$job_info_detail->getDisplayValue('cf_1082'));
		$this->setValue('destination_agent','');
		
		$this->setValue('waybill',$job_info_detail->get('cf_1096'));
		$this->setValue('pieces',$job_info_detail->get('cf_1429'));
		
		$this->setValue('weight',$job_info_detail->get('cf_1084').' '.$job_info_detail->get('cf_1520'));
		$this->setValue('volume',$job_info_detail->get('cf_1086').' '.$job_info_detail->get('cf_1522'));
		
		$this->setValue('commodity',$job_info_detail->get('cf_1518'));
		
		$job_user_info = Users_Record_Model::getInstanceById($job_info_detail->get('assigned_user_id'), 'Users');
		
		$this->setValue('coordinator',htmlentities($job_user_info->get('first_name').' '.$job_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));
		
		$this->setValue('booker',$job_info_detail->getDisplayValue('cf_1441'));
		
		$this->setValue('remarks',$job_info_detail->get('cf_1102'));
		
		include('include/mpdf60/mpdf.php');

  		$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10); /*задаем формат, отступы и.т.д.*/
  		$mpdf->charset_in = 'utf8';
		
		$mpdf->list_indent_first_level = 0; 

		$mpdf->SetDefaultFontSize(14);
		$mpdf->list_indent_first_level = 0;
		$mpdf->WriteHTML($this->_documentXML,2); /*формируем pdf*/

		//echo $subject;
		//exit;
		//$subject = 'Ruslan';
		
		$pdf_name = "pdf_docs/cover_letter_".$record.".pdf";
		
		
		$mpdf->Output($pdf_name, 'F');
		
		
		header('Location:'.$pdf_name);
		exit;	  
		
	}
	
	public function print_qsr($request)
	{
		$moduleName = $request->getModule();
		$record = $request->get('record');	
		$rtype = $request->get('rtype');	
		
		$current_user = Users_Record_Model::getCurrentUserModel();
		$job_details = Vtiger_Record_Model::getInstanceById($record, 'Job');
		

	
		// Basic information
		$owner_user_info = Users_Record_Model::getInstanceById($job_details->get('assigned_user_id'), 'Users');
		$assigned_to = $owner_user_info->get('first_name').' '.$owner_user_info->get('last_name');
		
		// Get Job file details
		$job_ref = $job_details->get('cf_1198');		
		$account_info = Users_Record_Model::getInstanceById($job_details->get('cf_1441'), 'Accounts');
		$customer = $account_info->get('cf_2395');
		
		
		//$subject = $job_details->get('name');
		//$created_date = date('d.m.Y', strtotime($job_details->get('createdtime')));
		
		//$customer_account = $job_details->get('cf_3489');
		
		$shipper = $job_details->get('cf_1072');
		$consignee = $job_details->get('cf_1074');
		$origin = $job_details->get('cf_1508');
		$destination = $job_details->get('cf_1510');
		
		// cf_1072 shipper
 
		$to = '';
		if ($job_details->get('cf_1082') != 0){
		  $account_info2 = Users_Record_Model::getInstanceById($job_details->get('cf_1082'), 'Accounts');
		  $booking_agent = $account_info2->get('cf_2395');
		} else $booking_agent = "";
 
		$fname = '';
		
		if ($rtype == 'origin') $fname = 'QSR_origin'; else if ($rtype == 'destination') $fname = 'QSR_destination'; 
		$document = $this->loadTemplate('printtemplates/Job/'.$fname.'.html');
		
		// Basic information		
		$this->setValue('assigned_to',$assigned_to, ENT_QUOTES, "UTF-8");
		$this->setValue('job_ref',$job_ref, ENT_QUOTES, "UTF-8");
		$this->setValue('customer',$customer, ENT_QUOTES, "UTF-8");

		//Shipping Details
		$this->setValue('shipper',$shipper, ENT_QUOTES, "UTF-8");
		$this->setValue('consignee',$consignee, ENT_QUOTES, "UTF-8");
		$this->setValue('origin',$origin, ENT_QUOTES, "UTF-8");
		$this->setValue('destination',$destination, ENT_QUOTES, "UTF-8");
		$this->setValue('booking_agent',$booking_agent, ENT_QUOTES, "UTF-8");

	
		include('include/mpdf60/mpdf.php');

  		$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10); /*задаем формат, отступы и.т.д.*/
  		$mpdf->charset_in = 'utf8';
		
		//$mpdf->list_indent_first_level = 0; 

		//$mpdf->SetDefaultFontSize(12);
		$mpdf->list_indent_first_level = 0;
		$mpdf->WriteHTML($this->_documentXML,2); /*формируем pdf*/

		//$account_name = html_entity_decode($to);
		//$account = str_replace("/", "", $account_name);
		
		if ($rtype == 'origin') $fname = 'qsro_'.$record; else if ($rtype == 'destination') $fname = 'qsrd_'.$record; 
		$pdf_name = "pdf/".$fname.".pdf";
				
		$mpdf->Output($pdf_name, 'F');		
		header('Location:'.$pdf_name);
		exit;		
	}
	
	public function template($strFilename)
	{
		$path = dirname($strFilename);
        //$this->_tempFileName = $path.time().'.docx';
       // $this->_tempFileName = $path.'/'.time().'.txt';
		$this->_tempFileName = $strFilename;
		//copy($strFilename, $this->_tempFileName); // Copy the source File to the temp File
		
		$this->_documentXML = file_get_contents($this->_tempFileName);
		
	}
	
	 /**
     * Set a Template value
     * 
     * @param mixed $search
     * @param mixed $replace
     */
    public function setValue($search, $replace) {
		
        if(substr($search, 0, 2) !== '${' && substr($search, -1) !== '}') {
            $search = '${'.$search.'}';
        }
      // $replace =  htmlentities($replace, ENT_QUOTES, "UTF-8");
        if(!is_array($replace)) {
			//$replace = utf8_encode($replace);
		   $replace =iconv('utf-8', 'utf-8', $replace);
        }
       
        $this->_documentXML = str_replace($search, $replace, $this->_documentXML);
		
    }
	
	 /**
     * Save Template
     * 
     * @param string $strFilename
     */
    public function save($strFilename) {
        if(file_exists($strFilename)) {
            unlink($strFilename);
        }
        
        //$this->_objZip->extractTo('fleet.txt', $this->_documentXML);
		
		file_put_contents($this->_tempFileName, $this->_documentXML);
        
        // Close zip file
       /* if($this->_objZip->close() === false) {
            throw new Exception('Could not close zip file.');
        }*/
        
        rename($this->_tempFileName, $strFilename);
    }
	
	public function loadTemplate($strFilename) {
        if(file_exists($strFilename)) {
            $template = $this->template($strFilename);
            return $template;
        } else {
            trigger_error('Template file '.$strFilename.' not found.', E_ERROR);
        }
    }

    private function splitText($a) {
		$d = [];
		$c = strlen($a[0]);
		$e = sizeof($a[1]);
		for ($b=0;$b<$e;$b++) {
			if ($c<$a[1][$b][1]) {
				$d[$b] = substr($a[0],$a[1][$b][0],$c);
			} else $d[$b] = substr($a[0],$a[1][$b][0],$a[1][$b][1]);
		}
		return $d;
	}

	public function print_pod($request)
	{
		$moduleName = $request->getModule();
		$record = $request->get('record');	
		//$rtype = $request->get('rtype');	
		
		//$current_user = Users_Record_Model::getCurrentUserModel();
		$job_details = Vtiger_Record_Model::getInstanceById($record, 'Job');
		$shipper = $this->splitText([$job_details->get('cf_1072'),[[0,35],[35,50]]]);
		$PICK_UP = $job_details->get('cf_1516');
		$ETA = $job_details->get('cf_1591');
		$CONSIGNEE = $this->splitText([$job_details->get('cf_1074'),[[0,30],[30,50]]]);;
		$DELIVERY = $job_details->get('cf_1583');
		$FULL_COLLECTION_ADDRESS = $this->splitText([$job_details->get('cf_1512'),[[0,50],[50,50],[100,50]]]);
		$FULL_DELIVERY_ADDRESS = $this->splitText([$job_details->get('cf_1514'),[[0,50],[50,50],[100,50]]]);
		$WAYBILL = $job_details->get('cf_1096');
		$NOOFPIECES = $job_details->get('cf_1429');
		$WEIGHT_AND_VOLUME = $this->splitText([$job_details->get('cf_1084').' '.$job_details->get('cf_1520').' / '.$job_details->get('cf_1086').' '.$job_details->get('cf_1522'),[[0,24],[24,50]]]);
		$DESCRIPTION = $this->splitText([$job_details->get('cf_1547'),[[0,50],[50,50],[100,50],[150,50]]]);

		$document = $this->loadTemplate('printtemplates/Job/job_pod.html');
		$this->setValue('shipper0',$shipper[0]);
		$this->setValue('shipper1',$shipper[1]);
		$this->setValue('PICK_UP',$PICK_UP);
		$this->setValue('ETA',$ETA);
		$this->setValue('CONSIGNEE0',$CONSIGNEE[0]);
		$this->setValue('CONSIGNEE1',$CONSIGNEE[1]);
		$this->setValue('DELIVERY',$DELIVERY);
		$this->setValue('FULL_COLLECTION_ADDRESS0',$FULL_COLLECTION_ADDRESS[0]);
		$this->setValue('FULL_COLLECTION_ADDRESS1',$FULL_COLLECTION_ADDRESS[1]);
		$this->setValue('FULL_COLLECTION_ADDRESS2',$FULL_COLLECTION_ADDRESS[2]);
		$this->setValue('FULL_DELIVERY_ADDRESS0',$FULL_DELIVERY_ADDRESS[0]);
		$this->setValue('FULL_DELIVERY_ADDRESS1',$FULL_DELIVERY_ADDRESS[1]);
		$this->setValue('FULL_DELIVERY_ADDRESS2',$FULL_DELIVERY_ADDRESS[2]);
		$this->setValue('WAYBILL',$WAYBILL);
		$this->setValue('NOOFPIECES',$NOOFPIECES);
		$this->setValue('WEIGHT_AND_VOLUME0',$WEIGHT_AND_VOLUME[0]);
		$this->setValue('WEIGHT_AND_VOLUME1',$WEIGHT_AND_VOLUME[1]);
		$this->setValue('DESCRIPTION0',$DESCRIPTION[0]);
		$this->setValue('DESCRIPTION1',$DESCRIPTION[1]);
		$this->setValue('DESCRIPTION2',$DESCRIPTION[2]);
		$this->setValue('DESCRIPTION3',$DESCRIPTION[3]);
		$HTML = '<!doctype html>
		<html>
		<head>
		<meta charset="UTF-8">
		
		</head>
		<body>
		<div>
			<div class="b1"><img src="include/logo_doc.jpg" style="float: left;"></div>
			<div class="b2">
				60 Nutsubidze street, Tbilisi, Georgia 0186<br>
				Tel.: +995 32 2000238; 2208147<br>
				Fax.: +995 32 2208147<br>
				E-mail: tbilisi@globalinklogistics.com<br>
				Web Site: <span class="b6">www.globalinklogistics.com</span>
			</div>
			<div class="b3">
			ტვირთის ჩაბარების საბუთი:<br>
			<div class="b4">DELIVERY REPORT</div>
			</div>
			<div class="b5">
				<div class="b7">
					<div class="b8">
						<div class="b10">გამომგზავნი:</div>
						<div class="b11">SHIPPER:</div>
						<div class="b12"></div>
						<div class="info-1">'.$shipper[0].'</div>
						<div class="b14"></div>
						<div class="info-2">'.$shipper[1].'</div>
						<div class="b10 b13">გამომგზავნი:</div>
						<div class="b11">CONSIGNEE:</div>
						<div class="b15"></div>
						<div class="info-4">'.$CONSIGNEE[0].'</div>
						<div class="b16"></div>
						<div class="info-2">'.$CONSIGNEE[1].'</div>
					</div>
					<div class="b9">
						<div class="b10">ტვირთის ჩამოსვლის თარიღი:</div>
						<div class="b11">CARGO ARRIVAL DATE:</div>
						<div class="b12 b17"></div>
						<div class="info-3">'.$ETA.'</div>
						<div class="b18">აღების თარიღი:</div>
						<div class="b11">PICK UP TIME:</div>
						<div class="b12 b19"></div>
						<div class="info-5">'.$PICK_UP.'</div>
						<div class="b18">მიტანის თარიღი:</div>
						<div class="b11">DELIVERY TIME:</div>
						<div class="b12 b19"></div>
						<div class="info-5">'.$DELIVERY.'</div>
					</div>
				</div>
				<div class="b7">
					<div class="b8">
						<div class="b10">აღების ადგილი:</div>
						<div class="b11">FULL COLLECTION ADDRESS:</div>
						<div class="b14 b20"></div>
						<div class="info-2">'.$FULL_COLLECTION_ADDRESS[0].'</div>
						<div class="b14 b20"></div>
						<div class="info-2">'.$FULL_COLLECTION_ADDRESS[1].'</div>
						<div class="b14 b20"></div>
						<div class="info-2">'.$FULL_COLLECTION_ADDRESS[2].'</div>
					</div>
					<div class="b9">
						<div class="b10">საბოლოო დანიშნულების ადგილი:</div>
						<div class="b11">FULL DELIVERY ADDRESS:</div>
						<div class="b14 b20"></div>
						<div class="info-2">'.$FULL_DELIVERY_ADDRESS[0].'</div>
						<div class="b14 b20"></div>
						<div class="info-2">'.$FULL_DELIVERY_ADDRESS[1].'</div>
						<div class="b14 b20"></div>
						<div class="info-2">'.$FULL_DELIVERY_ADDRESS[2].'</div>
					</div>
				</div>
				<div class="b7 b21">
					<div class="b8 b22">
						<div class="b10">ტვირთის დეტალები:</div>
						<div class="b11">SHIPPING DETAILS:</div>
						<div class="b10 b23">ავიაზედდებულის <strong>№</strong></div>
						<div class="b11">WAYBILL №:</div>
						<div class="b15 b24"></div>
						<div class="info-6">'.$WAYBILL.'</div>
						<div class="b10">ადგილების რაოდენობა:</div>
						<div class="b11">NO. OF PIECES:</div>
						<div class="b15 b24"></div>
						<div class="info-6">'.$NOOFPIECES.'</div>
						<div class="b10">ტვირთის წონა:</div>
						<div class="b11">WEIGHT/VOLUME:</div>
						<div class="b15 b24 b34"></div>
						<div class="info-7">'.$WEIGHT_AND_VOLUME[0].'</div>
						<div class="b14 b20"></div>
						<div class="info-2">'.$WEIGHT_AND_VOLUME[1].'</div>
					</div>
					<div class="b9 b22">
						<div class="b10">ტვირთის დასახელება/აღწერილობა:</div>
						<div class="b11">DESCRIPTION:</div>
						<div class="b14 b25"></div>
						<div class="info-2">'.$DESCRIPTION[0].'</div>
						<div class="b14 b20"></div>
						<div class="info-2">'.$DESCRIPTION[1].'</div>
						<div class="b14 b20"></div>
						<div class="info-2">'.$DESCRIPTION[2].'</div>
						<div class="b14 b20"></div>
						<div class="info-2">'.$DESCRIPTION[3].'</div>
					</div>
				</div>
			</div>
			<div class="b26">ამ მიღება ჩაბარების დოკუმენტის (POD) ხელმომწერი, ადასტურებს მიღებული საქონელის ადგილების რაოდენობის სისწორეს და საქონლის მიღების შემდგომ, იღებს სრულ პასუხისმგებლობას ადგილების რაოდენობის შეუსაბამობაზე.</div>
			<div class="b26">By signing this Proof Of Delivery (POD)document, Consignee/Signee bears full responsibility for any discrepancies in the
		number of boxes that may arise after inspection during the Delivery Phase/Completion of Delivery.</div>
			<div class="b26 b27">შენიშვნა:</div>
			<div class="b26 b27 b28"><strong>COMMENTS:</strong></div>
			<div class="b29"></div>
			<div class="b29"></div>
			<div class="b29"></div>
			<div class="b29"></div>
			<div class="b26 b27 b33">მიმღების სახელი/გვარი & ხელმოწერა:</div>
			<div class="b26 b27 b28"><strong>RECEIVERS NAME & SIGNATURE:</strong></div>
			<div class="b32"></div>
			<div class="b30"><strong>Date/</strong>თარიღი:</div>
			<div class="b31"></div>
		</div>

		</body>
		</html>';
		$HTML = mb_convert_encoding($HTML, 'UTF-8', 'UTF-8');
		include('include/mpdf60/mpdf.php');
		$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10); /*задаем формат, отступы и.т.д.*/
  		$mpdf->charset_in = 'utf8';
		
		$mpdf->list_indent_first_level = 0; 

		$mpdf->SetDefaultFontSize(14);
		$mpdf->list_indent_first_level = 0;
		$stylesheet = file_get_contents('printtemplates/Job/style_pod.css');
		$mpdf->WriteHTML($stylesheet,1);
		//$mpdf->WriteHTML($HTML);
		$mpdf->WriteHTML($this->_documentXML,2); /*формируем pdf*/


		$pdf_name = 'pdf_docs/proof_of_delivery.pdf';


		//$mpdf->WriteHTML($html_2); /*формируем pdf*/
		ob_clean();
		$mpdf->Output($pdf_name, 'F');

		//header('Location:http://mb.globalink.net/vt60/'.$pdf_name);
		header('Location:'.$pdf_name);



		exit;
	}
}
