<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class DamageReport_Print_View extends Vtiger_Print_View {
	
	
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
		
		
		
	public function get_job_id_from($recordId=0)
	{
		 $adb = PearDatabase::getInstance();
										
		 $checkjob = $adb->pquery("SELECT rel1.crmid as job_id FROM `vtiger_crmentityrel` as rel1 
				  							where rel1.relcrmid='".$recordId."'", array());
		 $crmId = $adb->query_result($checkjob, 0, 'job_id');
		 $job_id = $crmId;
		 return $job_id;		  
	}
		

	function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$record = $request->get('record');	
		$current_user = Users_Record_Model::getCurrentUserModel();
		$deliveryreport = Vtiger_Record_Model::getInstanceById($record, 'DamageReport');
		

	
		// Basic information
		$owner_user_info = Users_Record_Model::getInstanceById($deliveryreport->get('assigned_user_id'), 'Users');
		$coordinator_in_charge = $owner_user_info->get('first_name').' '.$owner_user_info->get('last_name');
		
		// Get Job file details
		$job_id  = $this->get_job_id_from($record);
		$job_info = Vtiger_Record_Model::getInstanceById($job_id, 'Job');
		$job_ref = $job_info->get('cf_1198');
		
		$account_info = Users_Record_Model::getInstanceById($job_info->get('cf_1441'), 'Accounts');
		$customer = $account_info->get('accountname');
		
		$subject = $deliveryreport->get('name');
		$created_date = date('d.m.Y', strtotime($deliveryreport->get('createdtime')));
		
		$customer_account = $deliveryreport->get('cf_3489');
		$local_address = $deliveryreport->get('cf_3491');
				
		$contact_details = $deliveryreport->get('cf_3493'); //tel
		$contact_details .= $deliveryreport->get('cf_3495'); // email
		
		$weight = $deliveryreport->get('cf_3497');
		$volume = $deliveryreport->get('cf_3499');
		$no_ofpieces = $deliveryreport->get('cf_3501');
		$mode = $deliveryreport->get('cf_3503');
		
		$origin = $deliveryreport->get('cf_3505');
		$destination = $deliveryreport->get('cf_3507');
		
		$arival_date = $deliveryreport->get('cf_3509');
	    $delivery_date = $deliveryreport->get('cf_3511');
		
		$insured_value = $deliveryreport->get('cf_3513');
	    $booking_agent = $deliveryreport->get('cf_3515');
		$origin_agent = $deliveryreport->get('cf_3515');
	    $insured_by = $deliveryreport->get('cf_3517');	
	
		$description = $deliveryreport->get('cf_3519');
		
		/*
		$qt_owner_user_info = Users_Record_Model::getInstanceById($quote_info->get('assigned_user_id'), 'Users');
		if ($quote_info->get('contact_id') != ''){
		  $contact_info = Vtiger_Record_Model::getInstanceById($quote_info->get('contact_id'), 'Contacts');
		  $attn = $contact_info->get('firstname').' '.$contact_info->get('lastname');
		}

		
		$to = '';
		if ($quote_info->get('cf_1827') != 0){
		  $account_info2 = Users_Record_Model::getInstanceById($quote_info->get('cf_1827'), 'Accounts');
		  $Agent = $account_info2->get('accountname');
		  $to = $Agent;
		} else $Agent = "";
		
		$account_id = $quote_info->get('account_id');
		
		if ($account_id != 0){
	 	  $account_info = Users_Record_Model::getInstanceById($quote_info->get('account_id'), 'Accounts');		
		  $account = $account_info->get('cf_2395');
		  if (empty($to)){
		    $to = $account;
		  }
		}
		else{
			$account ='';
		}
		*/
		
		$document = $this->loadTemplate('printtemplates/DamageReport/damagereport.html');
		
		// Basic information
		
		$this->setValue('coordinator_in_charge',$coordinator_in_charge, ENT_QUOTES, "UTF-8");
		$this->setValue('job_ref',$job_ref, ENT_QUOTES, "UTF-8");
		$this->setValue('customer',$customer, ENT_QUOTES, "UTF-8");
		$this->setValue('subject',$subject, ENT_QUOTES, "UTF-8");
		$this->setValue('date',date('d.m.Y', strtotime($deliveryreport->get('createdtime'))));
		$this->setValue('dep',htmlentities($owner_user_info->get('department'), ENT_QUOTES, "UTF-8"));	
				
				
				
				
		$this->setValue('customer_account',$customer_account, ENT_QUOTES, "UTF-8");
		$this->setValue('local_address',$local_address, ENT_QUOTES, "UTF-8");
 
					
		// Address Details		
		$this->setValue('contact_details',$contact_details, ENT_QUOTES, "UTF-8");
		$this->setValue('weight',$weight, ENT_QUOTES, "UTF-8");
		$this->setValue('volume',$volume, ENT_QUOTES, "UTF-8");

		$this->setValue('no_ofpieces',$no_ofpieces, ENT_QUOTES, "UTF-8");
		$this->setValue('mode',$mode, ENT_QUOTES, "UTF-8");
 		
		
		
		//Shipping Details
		$this->setValue('origin',$origin, ENT_QUOTES, "UTF-8");
		$this->setValue('destination',$destination, ENT_QUOTES, "UTF-8");
		$this->setValue('arival_date',$arival_date, ENT_QUOTES, "UTF-8");
		$this->setValue('delivery_date',$delivery_date, ENT_QUOTES, "UTF-8");

		$this->setValue('insured_value',$insured_value, ENT_QUOTES, "UTF-8");
		$this->setValue('booking_agent',$booking_agent, ENT_QUOTES, "UTF-8");
		$this->setValue('origin_agent',$origin_agent, ENT_QUOTES, "UTF-8");
		$this->setValue('insured_by',$insured_by, ENT_QUOTES, "UTF-8");
			
		
		$this->setValue('description',$description, ENT_QUOTES, "UTF-8");

		/*
		$this->setValue('pc_tel',$pc_tel, ENT_QUOTES, "UTF-8");
		$this->setValue('move_coordinator',$move_coordinator, ENT_QUOTES, "UTF-8");
		$this->setValue('mc_tel',$mc_tel, ENT_QUOTES, "UTF-8");	
		
		
		$this->setValue('to',htmlentities($to, ENT_QUOTES, "UTF-8"));
		$this->setValue('from',htmlentities($qt_owner_user_info->get('first_name').' '.$qt_owner_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));
		$this->setValue('attn',htmlentities($attn, ENT_QUOTES, "UTF-8"));
		$user_city = trim($r_users['address_city']);
		
		
		$adb = PearDatabase::getInstance();				
		$sql_branch_tel = $adb->pquery("SELECT b.tel as tel FROM `vtiger_branch_details` as b
									 where b.city = '".$qt_owner_user_info->get('address_city')."'", array());
	    $branch_tel = $adb->query_result($sql_branch_tel, 0, 'tel');
		$this->setValue('tel',$branch_tel);
		
		$subject = htmlentities($quote_info->get('subject'), ENT_QUOTES, "UTF-8");		
		$this->setValue('date',date('d.m.Y', strtotime($quote_info->get('createdtime'))));
		if ($email == '') $email = $qt_owner_user_info->get('email1'); 
		$this->setValue('email',$email, ENT_QUOTES, "UTF-8");
		$this->setValue('ref',htmlentities('GL/QT - '.$quote_id, ENT_QUOTES, "UTF-8"));
		$this->setValue('dep',htmlentities($qt_owner_user_info->get('department'), ENT_QUOTES, "UTF-8"));		
		$this->setValue('subject',htmlentities($quote_info->get('subject'), ENT_QUOTES, "UTF-8"));
		
		
		/*
		
					// General
	$shipper = $quote_info->get('cf_777');
	$consignee = $quote_info->get('cf_1611');
	$o_country = $quote_info->get('cf_1613');
	$d_country =$quote_info->get('cf_1617');
	$o_city = $quote_info->get('cf_1615');
	$d_city = $quote_info->get('cf_1619');
	$p_address = $quote_info->get('cf_1621');
	$d_address = $quote_info->get('cf_1623');
	$edp = $quote_info->get('cf_1625');
	$edd = $quote_info->get('cf_1627');
	$etd = $quote_info->get('cf_1629');
	$eta = $quote_info->get('cf_1631');
	$poe = $quote_info->get('cf_2689');	
		
    $route_block_g = '';
    $route_block_c = '';
   // $Agent = get_account_details($qt_arr['cf_1827'],'accountname');
    
	
    if (($shipper != '') || ($consignee != '')  || ($o_country != '') || ($d_country != '')  || 
        ($o_city != '') || ($d_city != '')  || ($p_address != '') || ($d_address != '')  || 
        ($edp != '') || ($edd != '')  || ($etd != '') || ($eta != '')  || 
        ($Agent != '')) {
		  $adb = PearDatabase::getInstance();				
		  $sql_country = $adb->pquery("SELECT c.country_name as country_name FROM `countries` as c 
										where c.country_code = '$o_country'", array());
		  $o_country = $adb->query_result($sql_country, 0, 'country_name'); 
			
		  $adb = PearDatabase::getInstance();				
		  $sql_country = $adb->pquery("SELECT c.country_name as country_name FROM `countries` as c 
										where c.country_code = '$d_country'", array());
		  $d_country = $adb->query_result($sql_country, 0, 'country_name'); 
			
			
        $CheckRouteGArray = array(
            "$shipper_lbl" => $shipper,
            "$consignee_lbl" => $consignee,
            "$o_country_lbl" => $o_country,
            "$d_country_lbl" => $d_country,
            "$o_city_lbl" => $o_city,
            "$d_city_lbl" => $d_city,
            "$p_address_lbl" => $p_address,
            "$d_address_lbl" => $d_address,
            "$edp_lbl" => $edp,
            "$edd_lbl" => $edd,
            "$etd_lbl" => $etd,
            "$eta_lbl" => $eta,
			"$poe_lbl" => $poe,
			"$account_lbl" => $account
        );
        $route_block_g = "
            <h3> $general_lbl: </h3>
            <table width=730 border=1 cellspacing=0 cellpadding=4 class=route-block>
                <tbody>";
        $cellcount = 0;
        foreach ($CheckRouteGArray as $key => $value) {
            if ($value != '') {
                if (!$cellcount) {
                    $route_block_g .= '<tr valign="top">';
                }
                $route_block_g .= '<td><strong>'.$key.'</strong></td>';
                $route_block_g .= '<td>'.$value.'</strong></td>';
                if ($cellcount) {
                    $route_block_g .= '</tr>';
                }
                if (++$cellcount > 1) {
                    $cellcount = 0;
                }
            }
        }
        if ($Agent != '') {
            $route_block_g .= '
                <tr valign="top">
                    <td><strong> '.$agent_lbl.' </strong></td>
                    <td colspan="3">'.$Agent.'</td>
                </tr>';
        }
        $route_block_g .= '
                </tbody>
            </table> <div style="margin-bottom:3px;"></div>';
        
    }
	
	$this->setValue('general_details',$route_block_g);
		/*
	
	
					// Cargo details
	$mode = $quote_info->get('cf_1709');
	$nop = $quote_info->get('cf_1637');
	$weight = $quote_info->get('cf_1639');
	$weight_unit = $quote_info->get('cf_1641');
	$volume = $quote_info->get('cf_1643');
	$volume_unit = $quote_info->get('cf_1645');
	$CargoValue = $quote_info->get('cf_1647');
	$ctt = $quote_info->get('cf_1649');
	$Commodity = $quote_info->get('cf_1651');
	$CargoDescription = $quote_info->get('cf_1653');
	$CurId = $quote_info->get('cf_1725');
		
	
    if (($mode != '') || ($nop != '')  || ($weight != '') || ($volume != '')  || 
        ($CargoValue != '') || ($ctt != '')  || ($Commodity != '') || ($Commodity != '')) {
        $Mode = str_replace(' |##|',',',$mode);
        if ($weight != '') {
            $Weight = $weight.' '.$weight_unit;
        }
        if ($volume != '') {
            $Volume = $volume.' '.$volume_unit;
        }
        if ($CargoValue != '') {
            if ($CurId){
				
				$currency_info = Vtiger_CurrencyList_UIType::getDisplayValue($CurId);	
				$Cur = $currency_info;
            }
            $CargoValue = $CargoValue.' '.$Cur;
        }
        $CheckRouteCArray = array(
            "$mode_lbl" => $Mode,
            "$nop_lbl" => $nop,
            "$weight_lbl" => $Weight,
            "$volume_lbl" => $Volume,
            "$cv_lbl" => $CargoValue,
            "$ctt_lbl" => $ctt,
            "$commodity_lbl" => $Commodity,
            "$c_d_lbl" => $CargoDescription,
            "$commodity_lbl" => $Commodity
        ); 
        $route_block_c = "
            <h3> $cargo_details_lbl:</h3>
            <table width='730' border='1' cellspacing='0' cellpadding='4' class='route-block'>
                <tbody>";
        $cellcount = 0;
        foreach ($CheckRouteCArray as $key => $value) {
            if ($value != '') {
                if (!$cellcount) {
                    $route_block_c .= '<tr valign="top">';
                }
                $route_block_c .= '<td><strong>'.$key.'</strong></td>';
                $route_block_c .= '<td>'.$value.'</strong></td>';
                if ($cellcount) {
                    $route_block_c .= '</tr>';
                }
                if (++$cellcount > 1) {
                    $cellcount = 0;
                }
            }
        }
        $route_block_c .= '
                </tbody>
            </table> <div style="margin-bottom:3px;"></div>';
        
    }
    //$route_block .= $route_block_g.$route_block_c;
	$this->setValue('cargo_details',$route_block_c);
	
	
	$description = $quote_info->get('description');
	$s = '';
	if ($description != ''){
	  $s = html_entity_decode($description);		
	  $s = "<div class=remarks-block>
	    <h3>$remarks_lbl:</h3>
		$s
	  </div>";
	 
	  		
	}
	$this->setValue('description',$s);
	
	
	$t_and_c = $quote_info->get('terms_conditions');
	$a = '';
	if ($t_and_c != ''){
		
	  $s = html_entity_decode($t_and_c);
	  $a = str_replace('<br>', '###', $s);
	  $a = '
		<h3>'.$terms_lbl.':</h3>		 
			'.$a.'
	  ';
	  		
	}
	$this->setValue('terms_and_conditions',$a);
	
	 
	$this->setValue('signature',$quote_html_footer);	
	*/
	
	
		//$filename = 'fleet_expense.txt';
		//$this->save('fleet_expense.txt');	
		
		include('include/mpdf60/mpdf.php');

  		$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10); /*задаем формат, отступы и.т.д.*/
  		$mpdf->charset_in = 'utf8';
		
		//$mpdf->list_indent_first_level = 0; 

		//$mpdf->SetDefaultFontSize(12);
		$mpdf->list_indent_first_level = 0;
		$mpdf->WriteHTML($this->_documentXML,2); /*формируем pdf*/

		//$account_name = html_entity_decode($to);
		//$account = str_replace("/", "", $account_name);
		
 
		$pdf_name = "pdf/damageReport_".$record.".pdf";
				
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
}
