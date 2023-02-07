<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Potentials_Print_View extends Vtiger_Print_View {
	
	
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
		$adb = PearDatabase::getInstance();
		//$adb->setDebug(true);
		include 'libraries/tcpdf/tcpdf.php';

		$moduleName = $request->getModule();
		$record = $request->get('record');
			
		$rfq_id = $record;
		$current_user = Users_Record_Model::getCurrentUserModel();
		$rfq_info = Vtiger_Record_Model::getInstanceById($rfq_id, 'Potentials');
		
		$rfq_owner_user_info = Users_Record_Model::getInstanceById($rfq_info->get('assigned_user_id'), 'Users');
		if ($rfq_info->get('contact_id') != ''){
		  $contact_info = Vtiger_Record_Model::getInstanceById($rfq_info->get('contact_id'), 'Contacts');
		  $attn = $contact_info->get('firstname').' '.$contact_info->get('lastname');
		}

		
		$to = '';
		// Field agent in QT
		if ($rfq_info->get('cf_1827') != 0){
		  $account_info2 = Users_Record_Model::getInstanceById($rfq_info->get('cf_1827'), 'Accounts');
		  $Agent = $account_info2->get('cf_2395');
		  $to = $Agent;
		} else $Agent = "";
		
		// Field account in QT
		$account_id = $rfq_info->get('account_id');
		if ($account_id != 0){
	 	  $account_info = Users_Record_Model::getInstanceById($rfq_info->get('account_id'), 'Accounts');		
		  $account = $account_info->get('cf_2395');
		  if (empty($to)){
		    $to = $account;
		  }
		}
		else{
			$account ='';
		}
		
		
		$qt_tpl_name = "general_en";
		$qt_type = 0;
		
				// General
		$general_lbl = "General";
	    $shipper_lbl = 'Shipper';
	    $consignee_lbl = 'Consignee';
	    $o_country_lbl = 'Origin Country';
	    $d_country_lbl = 'Destination Country';
	    $o_city_lbl = 'Origin City';
	    $d_city_lbl = 'Destination City';
	    $p_address_lbl = 'Pickup address';
	    $d_address_lbl = 'Delivery address';
	    $edp_lbl = 'Expected date of Pickup';
	    $edd_lbl = 'Expected Date Of Delivery';
	    $etd_lbl = 'ETD';
	    $eta_lbl = 'ETA';
	    // $poe_lbl = 'POE';
	    $account_lbl = 'Account';
		$agent_lbl = 'Agent';
		
		// Cargo value
		$cargo_details_lbl = "Cargo details";
	    $mode_lbl = 'Mode';
	    $nop_lbl = 'No of Pieces';
	    $weight_lbl = 'Weight';
	    $volume_lbl = 'Volume';
	    $cv_lbl = 'Cargo Value';
	    $ctt_lbl = 'Cntr or Transport Type';
	    $commodity_lbl = 'Commodity';
	    $c_d_lbl = 'Cargo Description';
		
		$remarks_lbl = 'Rate details';
		$terms_lbl = 'Terms and Conditions';

  
		
		$document = $this->loadTemplate('printtemplates/RFQ/'.$qt_tpl_name.'.html');
		
		
		
		$this->setValue('to',$to);
		$this->setValue('from',htmlentities($rfq_owner_user_info->get('first_name').' '.$rfq_owner_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));
		// $user_city = trim($r_users['address_city']);
		
		
		$adb = PearDatabase::getInstance();				
/* 		$sql_branch_tel = $adb->pquery("SELECT b.tel as tel FROM `vtiger_branch_details` as b
									 where b.city = '".$rfq_owner_user_info->get('address_city')."'", array());
	    $branch_tel = $adb->query_result($sql_branch_tel, 0, 'tel');
		$this->setValue('tel',$branch_tel); */
		
		$subject = $rfq_info->get('potentialname');		
/* 		echo 'department = ' . $rfq_owner_user_info->get('department').'<br>';
		exit;  */
		$this->setValue('date',date('d.m.Y', strtotime($rfq_info->get('createdtime'))));
		if ($email == '') $email = $rfq_owner_user_info->get('email1'); 
		$this->setValue('email',$email, ENT_QUOTES, "UTF-8");
		$this->setValue('ref', htmlentities('GL/RFQ - '.$rfq_id, ENT_QUOTES, "UTF-8"));
			
		$this->setValue('station', htmlentities($rfq_owner_user_info->get('address_city'), ENT_QUOTES, "UTF-8"));	
		$this->setValue('dep', htmlentities($rfq_owner_user_info->get('department'), ENT_QUOTES, "UTF-8"));	
		$this->setValue('subject', $subject, ENT_QUOTES, "UTF-8");	
		
		
					// General
	$shipper = $rfq_info->get('cf_717');
	$consignee = $rfq_info->get('cf_1699');

	$o_country = $rfq_info->get('cf_1657');
	$d_country =$rfq_info->get('cf_1661');

	$o_city = $rfq_info->get('cf_1659');
	$d_city = $rfq_info->get('cf_1663');

	$p_address = $rfq_info->get('cf_1665');
	$d_address = $rfq_info->get('cf_1667');

	$edp = $rfq_info->get('cf_1671');
	$edd = $rfq_info->get('cf_1673');

	$etd = $rfq_info->get('cf_1675');
	$eta = $rfq_info->get('cf_1677');
	// $poe = $rfq_info->get('cf_2689');	
		
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
			// "$poe_lbl" => $poe,
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
		
	
	
					// Cargo details
	$mode = $rfq_info->get('cf_1707');
	$nop = $rfq_info->get('cf_1681');

	$weight = $rfq_info->get('cf_1683');
	$weight_unit = $rfq_info->get('cf_1685');

	$volume = $rfq_info->get('cf_1687');
	$volume_unit = $rfq_info->get('cf_1689');

	$CargoValue = $rfq_info->get('cf_1691');
	$ctt = $rfq_info->get('cf_1693');

	$Commodity = $rfq_info->get('cf_1695');
	$CargoDescription = $rfq_info->get('cf_1697');
	$CurId = $rfq_info->get('cf_1723');
		
	
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


	
/* 	
	$description = $rfq_info->get('description');
	$s = '';
	if ($description != ''){
	  $s = html_entity_decode($description);		
	  $s = "<div class=remarks-block>
	    <h3>$remarks_lbl:</h3>
		$s
	  </div>";
	 
	  		
	}
	$this->setValue('description',$s); */
	
/* 	
	$t_and_c = $rfq_info->get('terms_conditions');
	$a = '';
	if ($t_and_c != ''){
		
	  $s = html_entity_decode($t_and_c);
	  $a = str_replace('<br>', '###', $s);
	  $a = '
		<h3>'.$terms_lbl.':</h3>		 
			'.$a.'
	  ';
	  		
	}
	$this->setValue('terms_and_conditions',$a); */
	

	/*
		For PRO Olga Stefanidi
	*/

/* 	$user_id = $current_user->get('id');

		if ($user_id == 266 || $user_id == 1){
		
			$pro_general_cargo_details = "
				<h3> Rate quotation for:</h3>
				<table width='730' border='1' cellspacing='0' cellpadding='4' class='route-block'>
					<tbody>
					<tr valign='top'>
						<td> Origin </td>
						<td>$o_country, $o_city </td>
						
						<td> Mode </td>
						<td>  $Mode </td>
					</tr>
					<tr valign='top'>
						<td> Destination </td>
						<td> $d_country,$d_city </td>
						
						<td> Delivery </td>
						<td>$ctt </td>
					</tr>

					";
			$pro_general_cargo_details .= '
					</tbody>
				</table> <div style="margin-bottom:3px;"></div>';

		$this->setValue('pro_general_cargo_details',$pro_general_cargo_details);

			$attn_details = $Commodity. "  from ".$o_country.', '.$o_city ."  to ".$d_country.', '.$d_city." as follows:";
			$this->setValue('attn_details',$attn_details);
			
		} */

		

	/*
		End of PRO template
	*/


					// Signature
					
	$user_city = $rfq_owner_user_info->get('address_city');
    $adb = PearDatabase::getInstance();							
    $sql_branch = $adb->pquery("SELECT b.tel as tel FROM `vtiger_branch_details` as b 
									where b.city = '".$user_city."'", array());
    $branch_tel = $adb->query_result($sql_branch, 0, 'tel'); 
					
					
	$creator_name = $rfq_owner_user_info->get('first_name').' '.$rfq_owner_user_info->get('last_name');
	$creator_title =  $rfq_owner_user_info->get('title');
	$email = $rfq_owner_user_info->get('email1');


	  $quote_html_footer = '
	  <div class="best-regards-block">
	  <span>----</span>
	  <p>Thank you and Best Wishes,<br />
	  '.$creator_name.'<br />
	  '.$creator_title.'<br />
	  Globalink Logistics
	  <p>
	  Tel:'.$branch_tel.'<br />
	  E-mail: '.$email.'<br />
	  Web Site: www.globalinklogistics.com</p>
	  </div>
	';
	

	
	
	 
	$this->setValue('signature',$quote_html_footer);	
	
		//$filename = 'fleet_expense.txt';
		//$this->save('fleet_expense.txt');	
		
		include('include/mpdf60/mpdf.php');

  		$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10); /*задаем формат, отступы и.т.д.*/
  		$mpdf->charset_in = 'utf8';
		
		//$mpdf->list_indent_first_level = 0; 

		//$mpdf->SetDefaultFontSize(12);
		$mpdf->list_indent_first_level = 0;
		$mpdf->WriteHTML($this->_documentXML,2); /*формируем pdf*/
		//$mpdf->WriteHTML('Hello World');

		$subject = $rfq_info->get('potentialname');		
		
		if ($subject != ''){
		  $subname = $to . "(".$subject.")";
		} else $subname = $to;
		
		
		$subname = preg_replace("~/~","",$subname);
		$subname = str_replace("&#039;", "'", $subname);
		$subname = str_replace("«", '"', $subname);
		$subname = str_replace("»", '"', $subname);
		$subname = html_entity_decode($subname);
		$pdf_name = "pdf_docs/RFQ for ".$subname.".pdf";		
		$mpdf->Output($pdf_name, 'F');
		
		
		header('Location:'.$pdf_name);
		exit;
				
		

	}


	 // Function arranging routing details
	 function arrange_route_block_v2($route){
		$n = substr_count($route,'#');
		$details = array();
		$buffer = '';

		$j = 1;
		$i = 0;

		for($c = 0; $c <=strlen($route); $c++){
			if ($route[$c] == '#') { $j++; $i = 0;}
			if ($route[$c] == "|"){
				$i ++;
				$details[$i][$j] = $buffer;
				$buffer = '';
			} else if ($route[$c] != '#') $buffer = $buffer . $route[$c];
		}

		for ($i=1;$i<=$n;$i++){

			$details[1][$i] = str_replace('From:','',$details[1][$i]);
			$details[2][$i] = str_replace('Mode:','',$details[2][$i]);

			$details[3][$i] = str_replace('To:','',$details[3][$i]);
			$details[4][$i] = str_replace('Inco:','',$details[4][$i]);

			$details[5][$i] = str_replace('Wt:','',$details[5][$i]);
			$details[6][$i] = str_replace('Vol:','',$details[6][$i]);

			$details[7][$i] = str_replace('Dim:','',$details[7][$i]);
			$details[8][$i] = str_replace('Comm:','',$details[8][$i]);

			$details[9][$i] = str_replace('Trans:','',$details[9][$i]);
			$details[10][$i] = str_replace('Qty:','', $details[10][$i]);

			$details[11][$i] = str_replace('Rate:','', $details[11][$i]);
		}

		return $details;

	}

	function get_branch_details($city,$field){
		$adb = PearDatabase::getInstance();
		$sql_branch = $adb->pquery("SELECT * FROM `vtiger_branch_details` where `city`='".$city."'");
		$r_branch = $adb->fetch_array($sql_branch);
		return $r_branch["$field"];
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
		  // $replace = utf8_encode($replace);
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
		//$this->_objZip->extractTo('Fleettrip.txt', $this->_documentXML);
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
