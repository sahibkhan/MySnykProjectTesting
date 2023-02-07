<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Insurance_Print_View extends Vtiger_Print_View {
	
	
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
		
		
	public function get_job_id_from_insurance($recordId=0)
	{
		 $adb = PearDatabase::getInstance();
										
		 $checkjob = $adb->pquery("SELECT rel1.crmid as job_id FROM `vtiger_crmentityrel` as rel1 
				  							where rel1.relcrmid='".$recordId."'", array());
		 $crmId = $adb->query_result($checkjob, 0, 'job_id');
		 $job_id = $crmId;
		 return $job_id;		  
	}
	
		/*
function writeReportToExcelFile($fileName, $headers, $entries, $filterlist='') {
  global $currentModule, $current_language;
  $mod_strings = return_module_language($current_language, $currentModule);

  require_once("libraries/PHPExcel/PHPExcel.php");

  $workbook = new PHPExcel();
  $worksheet = $workbook->setActiveSheetIndex(0);
  
  $header_styles = array(
   //'fill' => array( 'type' => PHPExcel_Style_Fill::FILL_NONE, 'color' => array('rgb'=>'E1E0F7') ),
   'fill' => array( 'type' => PHPExcel_Style_Fill::FILL_NONE ),
   
   //'font' => array( 'bold' => true )
  );
  
  //echo "<pre>";
  //print_r($entries);  
  //exit;
  $headers = array('insurance','id'); 
  if(isset($headers)) {
   $count = 0;
   $rowcount = 1;
   
   $arrayFirstRowValues = $headers;
   //array_pop($arrayFirstRowValues);
   
   // removed action link in details
   foreach($arrayFirstRowValues as $key=>$value) {
    $worksheet->setCellValueExplicitByColumnAndRow($count, $rowcount, $key, true);
    $worksheet->getStyleByColumnAndRow($count, $rowcount)->applyFromArray($header_styles);

    // NOTE Performance overhead: http://stackoverflow.com/questions/9965476/phpexcel-column-size-issues
    //$worksheet->getColumnDimensionByColumn($count)->setAutoSize(true);

    $count = $count + 1;
   }
   
   $rowcount++;
   
   $count = 0;
   //array_pop($array_value); // removed action link in details
   foreach($headers as $hdr => $value) {
    $value = decode_html($value);
    // TODO Determine data-type based on field-type.
    // String type helps having numbers prefixed with 0 intact.
    $worksheet->setCellValueExplicitByColumnAndRow($count, $rowcount, $value, PHPExcel_Cell_DataType::TYPE_STRING);
    $count = $count + 1;
   }
   //$rowcount++;
      
   $rowcount++;
  /* foreach($entries as $key => $array_value) {
    $count = 0;
    //array_pop($array_value); // removed action link in details
    foreach($array_value as $hdr => $value_excel) {
     if(is_array($value_excel))
     {
      $value = '';
     }
     else{
     $value = decode_html($value_excel);
     }
     // TODO Determine data-type based on field-type.
     // String type helps having numbers prefixed with 0 intact.
     $worksheet->setCellValueExplicitByColumnAndRow($count, $rowcount, $value, PHPExcel_Cell_DataType::TYPE_STRING);
     $count = $count + 1;
    }
    $rowcount++;
   }*/
   
   /*
  }
  
  
  $workbookWriter = PHPExcel_IOFactory::createWriter($workbook, 'Excel5');
  $workbookWriter->save($fileName);
  
 }
 */

		
		

	function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$record = $request->get('record');
		//$tpl = $request->get('tpl');	

		$insurance_id = $record;
		$current_user = Users_Record_Model::getCurrentUserModel();
			
		
		$current_user = Users_Record_Model::getCurrentUserModel();
		$insurance_info = Vtiger_Record_Model::getInstanceById($insurance_id, 'Insurance');
		

		/*
		$qt_owner_user_info = Users_Record_Model::getInstanceById($quote_info->get('assigned_user_id'), 'Users');
		if ($quote_info->get('contact_id') != ''){
		  $contact_info = Vtiger_Record_Model::getInstanceById($quote_info->get('contact_id'), 'Contacts');
		  $attn = $contact_info->get('firstname').' '.$contact_info->get('lastname');
		}
		*/
				
		$document = $this->loadTemplate('printtemplates/Insurance/insurance.html');		
		$this->setValue('ref_no',$insurance_info->get('name'));
		$this->setValue('expected_from_date',$insurance_info->get('cf_2263'));

		
				// Get Job details
		$insurance_expense  = $this->get_job_id_from_insurance($insurance_id);
		$job_id = $insurance_expense;
		$sourceModule_job 	= 'Job';	
		$job_info_detail = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule_job);
		
		$this->setValue('mode',$job_info_detail->get('cf_1711'));
		$o_country = $job_info_detail->get('cf_1504');
		$d_country = $job_info_detail->get('cf_1506');
		
		$account_info = Users_Record_Model::getInstanceById($job_info_detail->get('cf_1441'), 'Accounts');		
		$account_details = $account_info->get('accountname');
		$account_details .= $account_info->get('bill_country');
		$account_details .= $account_info->get('bill_street');
		$account_details .= $account_info->get('phone');
		$this->setValue('account_details',$account_details);
		
		
							// Get General information
		  $adb = PearDatabase::getInstance();				
		  $sql_country = $adb->pquery("SELECT c.country_name as country_name FROM `countries` as c 
										where c.country_code = '$o_country'", array());
		  $o_country = $adb->query_result($sql_country, 0, 'country_name'); 
		  $o_country .= ', '.$job_info_detail->get('cf_1508');
			
		  $adb = PearDatabase::getInstance();				
		  $sql_country = $adb->pquery("SELECT c.country_name as country_name FROM `countries` as c 
										where c.country_code = '$d_country'", array());
		  $d_country = $adb->query_result($sql_country, 0, 'country_name');
		  $d_country .= $insurance_info->get('cf_2255').', '.$insurance_info->get('cf_2257');
		
		$this->setValue('voyage_from',$o_country);
		$this->setValue('voyage_to',$d_country);		 
		$this->setValue('description_goods',$insurance_info->get('cf_2267'));
		$containerized = $insurance_info->get('cf_2389');
		if ($containerized == 'Non-Containreised') $cont = 'No'; else
		if ($containerized == 'Containreised') $cont = 'Yes';
		$this->setValue('containerized',$cont);
		$this->setValue('group_of_the_goods',$insurance_info->get('cf_2273'));
		$this->setValue('package_or_security_details',$insurance_info->get('cf_2269'));
		$this->setValue('invoice_sum',$insurance_info->get('cf_2289'));
		$this->setValue('transportation_cost_invoice',$insurance_info->get('cf_2293'));
		
		$this->setValue('other_charges',$insurance_info->get('cf_2297'));
		$this->setValue('other_charges_a',$insurance_info->get('cf_2301'));
		$this->setValue('other_charges_b',$insurance_info->get('cf_2305'));
	
		$this->setValue('total_sum_insured',$insurance_info->get('cf_2313'));
		$this->setValue('globalink_selling_rate',$insurance_info->get('cf_2693'));
		$this->setValue('discounted_selling_rate',$insurance_info->get('cf_2693'));		
		$this->setValue('globalink_premium',$insurance_info->get('cf_2295'));
		$this->setValue('period_of_shipment',$insurance_info->get('cf_2263').' - '.$insurance_info->get('cf_2265'));
				
		/*
		// Custom fields for template
		$this->setValue('price_coordinator',$price_coordinator, ENT_QUOTES, "UTF-8");
		$this->setValue('pc_tel',$pc_tel, ENT_QUOTES, "UTF-8");
		$this->setValue('move_coordinator',$move_coordinator, ENT_QUOTES, "UTF-8");
		$this->setValue('mc_tel',$mc_tel, ENT_QUOTES, "UTF-8");	
		
		
		$this->setValue('to',htmlentities($to, ENT_QUOTES, "UTF-8"));
		$this->setValue('from',htmlentities($qt_owner_user_info->get('first_name').' '.$qt_owner_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));
		$this->setValue('attn',htmlentities($attn, ENT_QUOTES, "UTF-8"));
		$this->setValue('tel',htmlentities($qt_owner_user_info->get('phone_mobile'), ENT_QUOTES, "UTF-8"));
		
		$subject = htmlentities($quote_info->get('subject'), ENT_QUOTES, "UTF-8");
		
		$this->setValue('date',date('d.m.Y', strtotime($quote_info->get('CreatedTime'))));
		$this->setValue('email',$email, ENT_QUOTES, "UTF-8");
		$this->setValue('ref',htmlentities('GL/QT - '.$quote_id, ENT_QUOTES, "UTF-8"));
		$this->setValue('dep',$dep, ENT_QUOTES, "UTF-8");		
		$this->setValue('subject',htmlentities($quote_info->get('subject'), ENT_QUOTES, "UTF-8"));
		*/
		
		
		
					// General
	/*$shipper = $quote_info->get('cf_777');
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
        $route_block_g = '
            <h3> General:</h3>
            <table width="730" border=1 cellspacing="0" cellpadding="4" class="route-block">
                <tbody>';
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
                    <td><strong>Agent</strong></td>
                    <td colspan="3">'.$Agent.'</td>
                </tr>';
        }
        $route_block_g .= '
                </tbody>
            </table> <div style="margin-bottom:3px;"></div>';
        
    }
	
	$this->setValue('general_details',$route_block_g);
		
	
	
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
	$Commodity = $quote_info->get('cf_1653');
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
            "$c_d_lbl" => $Commodity
        );
        $route_block_c = '
            <h3> Cargo Details:</h3>
            <table width="730" border="1" cellspacing="0" cellpadding="4" class="route-block">
                <tbody>';
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
	
	$description = $quote_info->get('description');
	$s = '';
	if ($description != ''){
	  $s = html_entity_decode($description);		
	  $s = "<div class=remarks-block>
	    <h3>Remarks:</h3>
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
		<h3>Terms and Conditions:</h3>		 
			'.$a.'
	  ';
	  		
	}
	$this->setValue('terms_and_conditions',$a);
	*/
	/*
					// Signature
					
	$user_city = $qt_owner_user_info->get('address_city');
    $adb = PearDatabase::getInstance();							
    $sql_branch = $adb->pquery("SELECT b.tel as tel FROM `vtiger_branch_details` as b 
									where b.city = '".$user_city."'", array());
    $branch_tel = $adb->query_result($sql_branch, 0, 'tel'); 
					
					
	$creator_name = $qt_owner_user_info->get('first_name').' '.$qt_owner_user_info->get('last_name');
	$creator_title =  $qt_owner_user_info->get('title');
	$email = $qt_owner_user_info->get('email1');
*/
	
	
	
	/*
$rootDirectory = vglobal('root_directory');
  $tmpDir = vglobal('tmp_dir');

  $tempFileName = tempnam($rootDirectory.$tmpDir, 'xls');
  
  $moduleName = 'Insurance';
  
  //$fileName = $this->getName().'.xls';
  $fileName = $moduleName.'.xls';
  
  //$this->writeReportToExcelFile($tempFileName, false);
  $this->writeReportToExcelFile($tempFileName, $headers=array(), $entries=array(), $filterlist='')

  if(isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
   header('Pragma: public');
   header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
  }

  header('Content-Type: application/x-msexcel');
  header('Content-Length: '.@filesize($tempFileName));
  header('Content-disposition: attachment; filename="'.$fileName.'"');

  $fp = fopen($tempFileName, 'rb');
  fpassthru($fp);	
	*/
	


		//$filename = 'fleet_expense.txt';
		//$this->save('fleet_expense.txt');	
		
		include('include/mpdf60/mpdf.php');

  		$mpdf = new mPDF('utf-8', 'A4', '10', '', 10, 10, 7, 7, 10, 10); /*задаем формат, отступы и.т.д.*/
  		$mpdf->charset_in = 'utf8';
		
		$mpdf->list_indent_first_level = 0; 

		$mpdf->SetDefaultFontSize(12);
		$mpdf->list_indent_first_level = 0;
		$mpdf->WriteHTML($this->_documentXML,2); /*формируем pdf*/

		//echo $subject;
		//exit;
		//$subject = 'Ruslan';
		
		$pdf_name = "pdf_docs/insurance_".$record.".pdf";
		
		
		$mpdf->Output($pdf_name, 'F');
		//header('Location:http://mb.globalink.net/vt60/'.$pdf_name);
		
		header('Location:'.$pdf_name);
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