<?php

	chdir(dirname(__FILE__) . '/../..');
	include_once 'vtlib/Vtiger/Module.php';
	include_once 'includes/main/WebUI.php';
	include_once 'include/Webservices/Utils.php';
    set_time_limit(0);
	date_default_timezone_set("UTC");	
	ini_set('memory_limit','64M');
	global $adb;



	ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);




$local_tax = $_REQUEST['local_tax'];

$job_id = (int) $_REQUEST['job_id'];

//print_r($_REQUEST);
//$selling_arr = explode(',', $sellingids);
//print_r($selling_arr);


foreach($local_tax as $rec_ind => $manual_invoice_tax_arr)
{
	foreach($manual_invoice_tax_arr as $rec_id => $manual_invoice_tax)
	{

		// check if manual tax id already exist
		$valid_tax = validate_manual_tax_id($adb,$manual_invoice_tax);

		if ($valid_tax) {

		$jrer_selling_invoice_count = $adb->pquery('select vtiger_jobexpencereport.invoice_tax,vtiger_jobexpencereport.invoice_no from vtiger_jobexpencereportcf 
											INNER JOIN 	vtiger_jobexpencereport on vtiger_jobexpencereport.jobexpencereportid = vtiger_jobexpencereportcf.jobexpencereportid
											where 
											vtiger_jobexpencereportcf.cf_1457="Selling" 
											AND vtiger_jobexpencereport.job_id="'.$job_id.'" 
											AND vtiger_jobexpencereportcf.jobexpencereportid="'.(int) $rec_id.'"');

			$data=$adb->fetch_array($jrer_selling_invoice_count);
			
			$invoice = $data['invoice_no'];
			$where_invoice = 'invoice_no';

			if (isset($data['invoice_tax']) and strlen($data['invoice_tax']) > 1) {
				$invoice = $data['invoice_tax'];
				$where_invoice = 'invoice_tax';
			}
	//print_r($data);
	//die();
			set_tax_invoice($adb,$invoice,$where_invoice,$manual_invoice_tax);
		} else {
			echo "already";//please add correct tax invoice no
		}
	}
				
}
		

/*
if ( count($update_selling_ids) > 0 ) {
	set_tax_invoice($adb,$update_selling_ids);
	echo count($update_selling_ids);
}
*/

function set_tax_invoice($adb,$invoice,$where_invoice,$manual_invoice_tax) {
/*
	echo "update vtiger_jobexpencereport set 						
					vtiger_jobexpencereport.invoice_tax='".$manual_invoice_tax."'						
					where vtiger_jobexpencereport.".$where_invoice." = '".$invoice."' and vtiger_jobexpencereport.invoice_no != ''";
					*/

		$adb->pquery("update vtiger_jobexpencereport set 						
					vtiger_jobexpencereport.invoice_tax='".$adb->sql_escape_string(trim($manual_invoice_tax))."'						
					where vtiger_jobexpencereport.".$where_invoice." = '".$invoice."' and vtiger_jobexpencereport.invoice_no != ''");
					
}

function validate_manual_tax_id($adb,$manual_invoice) {
	//validate_manual_tax_id();
	$tax_invoice = $adb->pquery("select vtiger_jobexpencereport.invoice_tax from vtiger_jobexpencereport where vtiger_jobexpencereport.invoice_tax = '".$adb->sql_escape_string(trim($manual_invoice))."'");

	$tax_invoice_count = $adb->num_rows($tax_invoice);

	if ($tax_invoice_count > 0) {
		return false; // manual_invoice already exist
	} else {
		return true;
	}
}


return true;
?>