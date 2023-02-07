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
$expenseids = $_REQUEST['expenseids'];

$_SESSION['payment_voucher_no'] = '';
$b_generate_payment_voucher_flag = true;
$payment_voucher = '';
//$job_id = $_REQUEST['job_id'];
//$head_role = $_REQUEST['role'];
//$status = $_REQUEST['status'];

$web1C = 'http://89.218.38.221/ws/ws/CreateExpense?wsdl';
//$web1C = 'http://10.196.1.241/glws/ws/CreateInvoiseT?wsdl';
//$web1C = 'http://10.1.1.60/glws/ws/CreateInvoiseT?wsdl';

$con1C = array( 'login' => 'AdmWS',
                        'password' => 'tesT123',
                        'soap_version' => SOAP_1_2,
                        'cache_wsdl' => WSDL_CACHE_NONE, //WSDL_CACHE_MEMORY, //, WSDL_CACHE_NONE, WSDL_CACHE_DISK or WSDL_CACHE_BOTH
                        'exceptions' => true,
                        'trace' => 1);

function Connect1C(){
	global $web1C, $con1C;
    if (!function_exists('is_soap_fault')) {
    print 'Не настроен web сервер. Не найден модуль php-soap.';
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
      if (is_object($idc)){
 
        try {
          $ret1c = $idc->CreateExpense($par);
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

	$expense_arr = explode(',', $expenseids);
	foreach($expense_arr as $key => $expense)
	{
		if(!empty($expense))
		{			
			$jrer_expense_lock_count = $adb->pquery('select vtiger_crmentityrel.crmid as jobid, vtiger_jobexpencereportcf.*, vtiger_jobexpencereport.*  from vtiger_jobexpencereportcf 
													INNER JOIN 	vtiger_jobexpencereport ON vtiger_jobexpencereport.jobexpencereportid = vtiger_jobexpencereportcf.jobexpencereportid
													INNER JOIN vtiger_crmentity ON vtiger_jobexpencereport.jobexpencereportid = vtiger_crmentity.crmid
													INNER JOIN vtiger_crmentityrel ON vtiger_crmentity.crmid = vtiger_crmentityrel.relcrmid 
													where vtiger_jobexpencereportcf.cf_1973="Approved" AND vtiger_jobexpencereportcf.cf_1975="Approved" 
														  AND vtiger_jobexpencereportcf.cf_1457="Expence" 
														  AND vtiger_jobexpencereportcf.jobexpencereportid="'.$expense.'" limit 1');
			$data=$adb->fetch_array($jrer_expense_lock_count);	
			
			//$job_info_detail = get_job_details($data['jobid']);
			$job_info_detail = Vtiger_Record_Model::getInstanceById($data['jobid'], 'Job');
			
			$CustomerId = $data['cf_1367'];
			$CustomerName = '';
			if ($CustomerId) {
				$q_customer= $adb->pquery('select * from vtiger_account where accountid = '.$CustomerId);
				$r_customer = $adb->fetch_array($q_customer);
				$CustomerName = $r_customer['accountname'];
			}
			
			$LocId = $data['cf_1477'];
			if ($LocId) {
			  $q_loc = $adb->pquery('select * from vtiger_locationcf where locationid='.$LocId);
			  $row_loc = $adb->fetch_array($q_loc);
			  $jcr_office = $row_loc['cf_1559'];
			}
			$DepId = $data['cf_1479'];
			if ($DepId) {
			  $q_dep = $adb->pquery('select * from vtiger_departmentcf where departmentid='.$DepId);
			  $row_dep = $adb->fetch_array($q_dep);
			  $jcr_dep = $row_dep['cf_1542'];
			}
			
			$currency_id = $data['cf_1345'];
			$q_comp = $adb->pquery('select currency_code from vtiger_currency_info where id = '.$currency_id);
			$row_comp = $adb->fetch_array($q_comp);
			$buying_cur = $row_comp['currency_code'];
			
			$ServId = $data['cf_1453'];
			if ($ServId) {
			 /* $q_serv = $adb->pquery('select c.* from vtiger_chartofaccount c 
			  						 inner join  vtiger_companyaccountcf v on v.companyaccountid = '.$ServId.' 
									 where c.chartofaccountid = v.cf_1501');
			  $row_serv = $adb->fetch_array($q_serv);
			  $Serv = $row_serv['name'];*/
			   $q_serv = $adb->pquery('SELECT vtiger_chartofaccount.name as char_of_account FROM vtiger_companyaccountcf 
									  INNER JOIN vtiger_chartofaccount ON vtiger_chartofaccount.chartofaccountid=vtiger_companyaccountcf.cf_1501
									  WHERE vtiger_chartofaccount.chartofaccountid ='.$ServId.'');
			  $row_serv = $adb->fetch_array($q_serv);
			  $Serv = $row_serv['char_of_account'];
			}
			
			$invoice_no = $data['cf_1212'];
			
			$account_type = $data['cf_1214'];
			$pv_AccTypeId = $data['cf_1214'];
			$pv_AccType = '';
			if ($pv_AccTypeId) {
			  $q_acctype = $adb->pquery('select * from vtiger_companyaccounttype where companyaccounttypeid = '.$pv_AccTypeId);
			  $row_acctype = $adb->fetch_array($q_acctype);
			  $pv_AccType = $row_acctype['name'];
			}
			
			 if ($pv_AccType == 'Bank R' || $pv_AccType == 'Cash R') {
                if ($idc) { // if connection exist

                $invoice_date = DateTime::createFromFormat('Y-m-d', $data['cf_1216']);
                $s_invoice_date = date_format($invoice_date, 'Ymd');
                $today_date = date('Ymd');

                    $payable_arr_to_1c = array('InvoiceNumber'=>$invoice_no, 
                        'InvoiceDate'=>$s_invoice_date, 
                        'DateDoc'=>$today_date, 
                        'CustomerID'=>$data['cf_1367'], 
                        'CustomerName'=>$CustomerName, 
                        'Currency'=>$buying_cur, 
                        'CurrencyRate'=>$data['cf_1222'], 
                        'JobRefNo'=>$job_info_detail->get('cf_1198'), 
                        'Department'=>$jcr_dep, 
                        'Location'=>$jcr_office, 
                        'NomenclatureID'=>$data['cf_1453'], 
                        'NomenclatureName'=>$Serv, 
                        'NQuantity'=>'1', 
                        'NPrice'=>$data['cf_1343'], 
                        'NSum'=>$data['cf_1343'], 
                        'IsBank'=>'1'
                    );
    
                $ret1c = GetData($idc, $payable_arr_to_1c);
                $InvNum = $ret1c->return;
                if ($InvNum != -1) {
                    $adb->pquery("update vtiger_jobexpencereport set b_payables_approval_status='Approved', b_confirmed_send_to_accounting_software='1' where jobexpencereportid='".$expense."' ");
                    $adb->pquery("update vtiger_jobexpencereportcf set  cf_1975='Posted' where  jobexpencereportid='".$expense."' ");					 
                }
				 
                } // if connection exist
			 }
			 else{
				$adb->pquery("update vtiger_jobexpencereport set b_payables_approval_status='Approved', b_confirmed_send_to_accounting_software='1' where jobexpencereportid='".$expense."' ");
				$adb->pquery("update vtiger_jobexpencereportcf set  cf_1975='Posted' where  jobexpencereportid='".$expense."' ");	 
			 }	
		}
	}