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

$_SESSION['invoice_instruction_no'] = '';
$s_generate_invoice_instruction_flag = true;
$invoice_instruction = '';
$job_id = $_REQUEST['job_id'];

//$jrer_selling_lock_count = $this->db->where('s_generate_invoice_instruction', 1)->where('id', $jrer_selling_id)->where('job_id', $id)->count_all_results('job_jrer_selling');
$selling_arr = explode(',', $sellingids);
foreach($selling_arr as $key => $selling)
{
	if(!empty($selling))
	{
	//vtiger_jobexpencereportcf.cf_1248="1"	AND	
	$jrer_selling_lock_count = $adb->pquery('select COUNT(*) as total_invoice, vtiger_jobexpencereportcf.cf_1250 from vtiger_jobexpencereportcf 
										INNER JOIN 	vtiger_jobexpencereport on vtiger_jobexpencereport.jobexpencereportid = vtiger_jobexpencereportcf.jobexpencereportid
										where 
										vtiger_jobexpencereportcf.cf_1457="Selling" 
										AND vtiger_jobexpencereport.job_id="'.$job_id.'" 
										AND vtiger_jobexpencereportcf.jobexpencereportid="'.$selling.'"');

		$data=$adb->fetch_array($jrer_selling_lock_count);
	
		if($data['total_invoice']==1 && $data['cf_1250']!='Approved')
		{
			
			$adb->pquery("update vtiger_jobexpencereport set 
						vtiger_jobexpencereport.accept_generate_invoice=0, 
						vtiger_jobexpencereport.invoice_no='', 
						vtiger_jobexpencereport.serial_number='', 
						vtiger_jobexpencereport.year_no='', 
						vtiger_jobexpencereport.invoice_instruction_no='',
						vtiger_jobexpencereport.preview_instruction_no='' 
						where vtiger_jobexpencereport.jobexpencereportid='".$selling."' ");
			$adb->pquery("update vtiger_jobexpencereportcf set cf_2439='', cf_1248='', cf_1250='' where jobexpencereportid='".$selling."' ");
				
		}
	}
		
}

return true;
?>