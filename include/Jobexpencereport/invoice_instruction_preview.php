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

$_SESSION['preview_instruction_no'] = '';
$s_generate_invoice_instruction_flag = true;
$invoice_instruction = '';
$job_id = $_REQUEST['job_id'];

//$jrer_selling_lock_count = $this->db->where('s_generate_invoice_instruction', 1)->where('id', $jrer_selling_id)->where('job_id', $id)->count_all_results('job_jrer_selling');
$selling_arr = explode(',', $sellingids);
foreach($selling_arr as $key => $selling)
{
	if(!empty($selling))
	{
		//AND vtiger_jobexpencereportcf.cf_1248="1"
	$jrer_selling_lock_count = $adb->pquery('select COUNT(*) as total_invoice from vtiger_jobexpencereportcf 
										INNER JOIN 	vtiger_jobexpencereport on vtiger_jobexpencereport.jobexpencereportid = vtiger_jobexpencereportcf.jobexpencereportid
										where 
										vtiger_jobexpencereportcf.cf_2439="1"										
										AND vtiger_jobexpencereportcf.cf_1457="Selling" 
										AND vtiger_jobexpencereport.job_id="'.$job_id.'" 
										AND vtiger_jobexpencereportcf.jobexpencereportid="'.$selling.'" ');

	$data=$adb->fetch_array($jrer_selling_lock_count);	
	if($data['total_invoice']==0)
	{
		if(empty($_SESSION['preview_instruction_no']) && $s_generate_invoice_instruction_flag==true)
		{	
		$s_generate_invoice_instruction_flag = false;
										 				
		$db_obj = $adb->pquery('select MAX(preview_instruction_no) as max_preview_instruction_no from vtiger_jobexpencereport
						   INNER JOIN vtiger_jobexpencereportcf on vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid
	 					   where vtiger_jobexpencereport.job_id="'.$job_id.'" 
						   		 AND vtiger_jobexpencereportcf.cf_1457="Selling" ');

			if ($adb->num_rows($db_obj) == 0 or !$db_obj)
			{
				$preview_instruction_ordering = 0;
			}
			else
			{
				$order_row = $adb->fetch_array($db_obj);
				//$order_row = $db_obj->row();		
				if ( ! is_numeric($order_row['max_preview_instruction_no']))
				{
					$preview_instruction_ordering = 0;
				}
				else
				{
					$preview_instruction_ordering = $order_row['max_preview_instruction_no'];
				}
				$preview_instruction_no = $preview_instruction_ordering+1;
				$_SESSION['preview_instruction_no'] = $preview_instruction_no;	
			}
		
		}
		
		$adb->pquery("update vtiger_jobexpencereport set preview_instruction_no='".$preview_instruction_no."' where jobexpencereportid='".$selling."' ");
		//$adb->pquery("update vtiger_jobexpencereportcf set cf_2439='1', cf_1248='1', cf_1250='Preview' where jobexpencereportid='".$selling."' and cf_1250 NOT IN('Submitted','Approved','Declined') ");
		//$adb->pquery("update vtiger_jobexpencereportcf set cf_2439='1', cf_1250='Preview' where jobexpencereportid='".$selling."' and cf_1250 NOT IN('Submitted','Approved','Declined') ");
		$adb->pquery("update vtiger_jobexpencereportcf set cf_2439='1', cf_1250='Preview' where jobexpencereportid='".$selling."' and cf_1250 NOT IN('Submitted','Approved') ");
				
		}
	}
		
}

return true;
?>