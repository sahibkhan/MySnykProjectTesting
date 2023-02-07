<?php
	chdir(dirname(__FILE__) . '/../..');
	include_once 'vtlib/Vtiger/Module.php';
	include_once 'includes/main/WebUI.php';
	include_once 'include/Webservices/Utils.php';
    set_time_limit(0);
	date_default_timezone_set("UTC");	
	ini_set('memory_limit','64M');
	global $adb;	
@session_start();
$sellingids = $_REQUEST['sellingids'];

//$web1C = 'http://89.218.38.221/glws/ws/createinvoiseT?wsdl'; //Live 1C
//$web1C = 'http://89.218.38.221/gl/ws/createinvoiseT?wsdl'; //Test 1C
 $web1C = 'http://89.218.38.197/gl/ws/createinvoiseT?wsdl';
 //$web1C = 'http://10.196.1.202/gl/ws/createinvoiseT?wsdl';
 //login: AdmWS  password:906900


$con1C = array( 'login' => 'AdmWS',   //AdmWS Live
                        'password' => '6fc@t\Vy', //6fc@t\Vy Live //906900 test
                        'soap_version' => SOAP_1_2,
                        'cache_wsdl' => WSDL_CACHE_NONE, //WSDL_CACHE_MEMORY, //, WSDL_CACHE_NONE, WSDL_CACHE_DISK or WSDL_CACHE_BOTH
                        'exceptions' => true,
                        'trace' => 1);

function Connect1C() {
	global $web1C, $con1C;
    if (!function_exists('is_soap_fault')) {
        echo '<br>not found module php-soap.<br>';
        return false;
    }
    try {
        $Client1C = new SoapClient($web1C, $con1C);
    } catch(SoapFault $e) {
        var_dump($e);
        echo '<br>error at connecting to 1C<br>';
        return false;
    }
    if (is_soap_fault($Client1C)){
        echo '<br>inner server error at connecting to 1C<br>';
        return false;
    }
    return $Client1C;
}

function GetData($idc, $par) {
    $ret1c = null;
    if (is_object($idc)) {
        try {
          $ret1c = $idc->CreateInvoiseT($par);
        } catch (SoapFault $e) {
            var_dump($e);
            echo '<br>error at function execution<br>';
        }   
    }
    else{
        var_dump($idc);
        echo '<br>no connection to 1C<br>';
    }
    return $ret1c;
}
$idc = Connect1C();
if ($idc) {
    $selling_arr = explode(',', $sellingids);
	
	$invoice_submitted = array();
	$invoice_check = array();
	foreach($selling_arr as $key => $selling)
    {
        if(!empty($selling))
        {
			 $jrer_selling = $adb->pquery('select vtiger_crmentityrel.crmid as jobid, vtiger_jobexpencereportcf.*, vtiger_jobexpencereport.*  from vtiger_jobexpencereportcf 
                                            INNER JOIN 	vtiger_jobexpencereport ON vtiger_jobexpencereport.jobexpencereportid = vtiger_jobexpencereportcf.jobexpencereportid
                                            INNER JOIN vtiger_crmentity ON vtiger_jobexpencereport.jobexpencereportid = vtiger_crmentity.crmid
                                            INNER JOIN vtiger_crmentityrel ON vtiger_crmentity.crmid = vtiger_crmentityrel.relcrmid 
                                            where vtiger_jobexpencereportcf.cf_1250="Submitted" 
                                                  AND vtiger_jobexpencereportcf.cf_1457="Selling" 
                                                  AND vtiger_jobexpencereportcf.jobexpencereportid="'.$selling.'" limit 1');
            $data_arr=$adb->fetch_array($jrer_selling);
			
			//if(!in_array($data_arr['invoice_instruction_no'], $invoice_check))
			if($adb->num_rows($jrer_selling) > 0 && !in_array($data_arr['invoice_instruction_no'], $invoice_check))
			{
				$invoice_check[] = $data_arr['invoice_instruction_no'];
				$invoice_submitted[] = array('invoice_instruction_no' => $data_arr['invoice_instruction_no'],
											'selling_id' => $data_arr['jobexpencereportid'],
											'job_id' => $data_arr['jobid']);
			}
			
		}
	}
	
	if(!empty($invoice_submitted))
	{
		
		foreach($invoice_submitted as $key_invoice => $invoice)
		{
			// $job_info_detail = get_job_details($invoice['job_id']);
			$job_info_detail = Vtiger_Record_Model::getInstanceById($invoice['job_id'], 'Job');
			 
			 $jrer_selling = $adb->pquery('select vtiger_crmentityrel.crmid as jobid, vtiger_jobexpencereportcf.*, vtiger_jobexpencereport.*  from vtiger_jobexpencereportcf 
                                            INNER JOIN 	vtiger_jobexpencereport ON vtiger_jobexpencereport.jobexpencereportid = vtiger_jobexpencereportcf.jobexpencereportid
                                            INNER JOIN vtiger_crmentity ON vtiger_jobexpencereport.jobexpencereportid = vtiger_crmentity.crmid
                                            INNER JOIN vtiger_crmentityrel ON vtiger_crmentity.crmid = vtiger_crmentityrel.relcrmid 
                                            where vtiger_jobexpencereportcf.cf_1250="Submitted" 
                                                  AND vtiger_jobexpencereportcf.cf_1457="Selling" 
                                                  AND vtiger_jobexpencereport.invoice_instruction_no="'.$invoice['invoice_instruction_no'].'" 
												  AND vtiger_jobexpencereport.job_id="'.$invoice['job_id'].'" ');
												  
			if($adb->num_rows($jrer_selling)>0)
			{
				$check_flag = true;
				$invoice_arr_to_1c = array();
				$CreateInvoiseT = array();
				while($data=$adb->fetch_array($jrer_selling))
				{
					if(empty($data['invoice_no']))
					{
						if($check_flag)
						{
							$check_flag = false;
							
							$CustomerId = $data['cf_1445'];
							if ($CustomerId) {
								$q_customer= $adb->pquery('select * from vtiger_account where accountid = "'.$CustomerId.'"');
								$r_customer = $adb->fetch_array($q_customer);
								$CustomerName = $r_customer['accountname'];
								
								$q_customer_1c= $adb->pquery('select * from vtiger_accountscf where accountid = "'.$CustomerId.'"');
								$r_customer_1c = $adb->fetch_array($q_customer_1c);
								
								$CustomerId = $r_customer_1c['cf_2407'];
							}
							$LocId = $data['cf_1477'];
							if ($LocId) {
							  $q_loc = $adb->pquery('select * from vtiger_locationcf where locationid="'.$LocId.'"');
							  $row_loc = $adb->fetch_array($q_loc);
							  $jcr_office = $row_loc['cf_1559'];
							}
							$DepId = $data['cf_1479'];
							if ($DepId) {
							  $q_dep = $adb->pquery('select * from vtiger_departmentcf where departmentid="'.$DepId.'"');
							  $row_dep = $adb->fetch_array($q_dep);
							  $jcr_dep = $row_dep['cf_1542'];
							}
							
							$currency_id = $data['cf_1234'];
							$q_comp = $adb->pquery('select currency_code from vtiger_currency_info where id = "'.$currency_id.'"');
							$row_comp = $adb->fetch_array($q_comp);
							$selling_cur = $row_comp['currency_code'];
							
							$CreateInvoiseT = array('DateDoc' => $data['cf_1355'],
													 'CustomerID'=>$CustomerId,
													 'CustomerName'=>$CustomerName,
													 'Currency'=>$selling_cur,
													 'CurrencyRate'=>$data['cf_1236'],
													 'JobRefNo'=>$job_info_detail->get('cf_1198'),
													 'Department'=>$jcr_dep,
													 'Location'=>$jcr_office,
													 'Сommodity' => $job_info_detail->get('cf_1518'),
													 'Weight' => $job_info_detail->get('cf_1084'),
													 'NoOfPieces' => $job_info_detail->get('cf_1429'),
													 'Waybill' => $job_info_detail->get('cf_1096'),
													 'HAWB' => $job_info_detail->get('cf_2387'),
													 );
							
						}
						//check flag
						
						 $ServId_id = $data['cf_1455'];
						if ($ServId_id) {						
						 
						  $q_serv = $adb->pquery('SELECT * FROM vtiger_companyaccount
									   INNER JOIN `vtiger_companyaccountcf` ON vtiger_companyaccountcf.companyaccountid = vtiger_companyaccount.companyaccountid
									   INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_companyaccountcf.companyaccountid 
									   WHERE vtiger_crmentityrel.crmid = 85757 and vtiger_companyaccountcf.cf_1499=85847 and vtiger_companyaccountcf.cf_1501="'.$ServId_id.'"
									  ');
									  
						  $row_serv = $adb->fetch_array($q_serv);
						  $Serv = $row_serv['cf_3485'];	
						  $ServId = $row_serv['name'];
						  
						  if(empty($ServId))
						  {
							
							$q_serv = $adb->pquery('SELECT vtiger_chartofaccount.name as char_of_account FROM vtiger_chartofaccount 
												   WHERE vtiger_chartofaccount.chartofaccountid ="'.$ServId_id.'"');					  
						  $row_serv = $adb->fetch_array($q_serv);
						  if(empty($Serv))
						  {
							$Serv = $row_serv['char_of_account'];
						  }
						  $ServId = $data['cf_1455'];
						  }
						  
								  
						  
						}
						$invoice_date = DateTime::createFromFormat('Y-m-d', $data['cf_1355']);
						$s_invoice_date = date_format($invoice_date, 'Ymd');
						
						$invoice_arr_to_1c['Element'][] = array( 
																'NomenclatureID'=>$ServId,
																'NomenclatureName'=>$Serv,
																'NomenclatureDescription' => $data['cf_1365'],
																'NQuantity'=>'1',
																'NPrice'=>$data['cf_1357'],
																'NVAT'=> ($data['cf_2695']=='Without VAT'?'wo':(float)$data['cf_1228']),
																'NSum'=>$data['cf_1232']);
							
						}//if empty invoice no
					
				} //while end
				
				$CreateInvoiseT['NomenclatureTable'] = $invoice_arr_to_1c;
				//echo "<pre>";
				//print_r($CreateInvoiseT);
				//exit;
				$ret1c = GetData($idc, $CreateInvoiseT);
				$InvNum_Date = $ret1c->return;
				$InvNum_DateArr = explode("_",$InvNum_Date);
				$InvNum = $InvNum_DateArr[0];
				//$InvDate = str_replace(array('.', ' ', ':'), '', $InvNum_DateArr[1]); //26.08.2021 16:43:52
				$DateArr = explode(" ",$InvNum_DateArr[1]); //26.08.2021 16:43:52
				$InvDate = $DateArr[0]; // 26.08.2021

				$invoice_approved_date = date('Y-m-d',strtotime($InvDate));


				
				echo "<pre>";
				print_r($ret1c);
				//print_r($InvNum);

				echo "InvNum==".$InvNum;

				echo "<br>InvDate==".$InvDate;

				echo "<br>invoice_approved_date==".$invoice_approved_date;


				exit;
				

				
				// if ($InvNum != -1) {
				if ($InvNum > 0) {
					 
					$adb->pquery("update vtiger_jobexpencereport 
							set accept_generate_invoice=1, 
								year_no='".date("Y")."' 
							where invoice_instruction_no='".$invoice['invoice_instruction_no']."' 
								  AND job_id='".$invoice['job_id']."' ");
								  
					$adb->pquery("update vtiger_jobexpencereport set invoice_no='".$InvNum."', invoice_approved_date='".$invoice_approved_date."'														
							 where invoice_instruction_no='".$invoice['invoice_instruction_no']."' and job_id='".$invoice['job_id']."' ");
							 
					$adb->pquery("update vtiger_jobexpencereportcf, vtiger_jobexpencereport set vtiger_jobexpencereportcf.cf_1250='Approved'
							where vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid 
								  AND vtiger_jobexpencereport.invoice_instruction_no='".$invoice['invoice_instruction_no']."' 
								  AND vtiger_jobexpencereport.job_id='".$invoice['job_id']."' ");		 		  	
								  
				 }

				 //if ($_SERVER['REMOTE_ADDR'] == '182.191.91.103') {
				 	//echo "<pre>";
				 	//print_r($ret1c);
				 	//exit();
				 //}
				 
			} // if record no >0
		} //foreach for invoice submitted
	}
	
}

?>