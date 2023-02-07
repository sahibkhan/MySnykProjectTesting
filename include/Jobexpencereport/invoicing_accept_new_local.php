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

$job_id = $_REQUEST['job_id'];

$status = $_REQUEST['status'];

$local_invoice_no_arr = $_REQUEST['local_invoice_no'];

$jz_group_arr = array('85772','85768','85761', '85771', '85763', '85762');
$dw_group_arr = array('85756','205751');

if($status=='accept')
{
	$selling_arr = explode(',', $sellingids);
			
	foreach($selling_arr as $key => $selling)
	{
		if(!empty($selling))
		{
			$local_invoice_no = explode(',',$local_invoice_no_arr);
			$local_invoice_no = $local_invoice_no[$key];
			if(!empty($local_invoice_no))
			{
			// AND vtiger_jobexpencereport.job_id="'.$job_id.'" 								  
			$jrer_selling = $adb->pquery('select * from vtiger_jobexpencereportcf 
										INNER JOIN 	vtiger_jobexpencereport ON vtiger_jobexpencereport.jobexpencereportid = vtiger_jobexpencereportcf.jobexpencereportid
										where vtiger_jobexpencereportcf.cf_1248="1" 
											  AND vtiger_jobexpencereportcf.cf_1457="Selling" 											 
											  AND vtiger_jobexpencereportcf.jobexpencereportid="'.$selling.'" limit 1');
			
			$data=$adb->fetch_array($jrer_selling);
			if(!empty($data['invoice_instruction_no']) && $data['cf_1250']=='Submitted')
			{	
				$job_id = $data['job_id'];
				
				$rs_job_info = $adb->pquery("SELECT * FROM `vtiger_jobcf` where jobid='".$job_id."'");
				$job_info = $adb->fetch_array($rs_job_info);	
				
				$department_id = $job_info['cf_1190'];
				$file_title = $job_info['cf_1186'];
				
				$rs_pay_to_info = $adb->pquery("SELECT * FROM `vtiger_accountscf` where accountid='".$data['cf_1445']."'");
				$pay_to_info = $adb->fetch_array($rs_pay_to_info);
							
				$s_invoice_date = date('Y-m-d');
				$s_invoice_date_ =  date('Y-m-d',strtotime($s_invoice_date));
				$due_date = date('Y-m-d',strtotime($s_invoice_date_));
				//Fifteen days from invoice date
				$pay_to_info['cf_1839'] = 15;
				if(is_numeric($pay_to_info['cf_1839']))
				{
					$s_invoice_date_ =  date('Y-m-d',strtotime($s_invoice_date));							
					$due_date = date('Y-m-d', strtotime($s_invoice_date_. ' + '.$pay_to_info['cf_1839'].' days'));
				}
				
				$exra_invoicing = array();
				$accept_generate_invoice_flag = 1;
				
				$exra_invoicing = array(
										'accept_generate_invoice' => 1,
										//'reject' => ((@$invoice_acceptance_flag[$selling_id]=='reject')? 1 : 0 ),
										//'reason_for_reject' => $reason_for_reject[$selling_id],
										'year_no'			=> date('Y'),
										);
				
										
				$adb->pquery("update vtiger_jobexpencereport 
							set accept_generate_invoice=1, 
								year_no='".date("Y")."' 
							where invoice_instruction_no='".$data['invoice_instruction_no']."' 
								  AND job_id='".$job_id."' ");						
										
				
				
				$invoice_status= 'Approved';
				//AND vtiger_jobexpencereport.invoice_instruction_no="'.$data['invoice_instruction_no'].'" 			
				
				
				
				
				//$invoice_no = $ref_no_arr[1].'-'.$ref_no_arr[2].'-'.str_pad($serial_number, 5, "0", STR_PAD_LEFT).'/'.date('y');
				//$invoice_no = $ref_no_arr[1].'-'.str_pad($serial_number, $STR_PAD_LEFT, "0", STR_PAD_LEFT).'/'.date('y');
				//$exra_invoicing['invoice_no'] = $invoice_no;
				$adb->pquery("update vtiger_jobexpencereport set invoice_no='".$local_invoice_no."'															
							  where invoice_instruction_no='".$data['invoice_instruction_no']."' and job_id='".$job_id."' ");
					
				
				$adb->pquery("update vtiger_jobexpencereportcf, vtiger_jobexpencereport set vtiger_jobexpencereportcf.cf_1250='Approved'
							  where vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid 
							  AND vtiger_jobexpencereport.invoice_instruction_no='".$data['invoice_instruction_no']."' 
							  AND vtiger_jobexpencereport.job_id='".$job_id."' ");
								  
				//updateStatus($data['cf_1445'], $s_invoice_date, $department_id); 
			}
				
			}
											
		}
	}
}


if($status=='reject')
{
	$selling_arr = explode(',', $sellingids);
	foreach($selling_arr as $key => $selling)
	{
		if(!empty($selling))
		{
			//AND vtiger_jobexpencereport.job_id="'.$job_id.'" 
			$jrer_selling = $adb->pquery('select * from vtiger_jobexpencereportcf 
										INNER JOIN 	vtiger_jobexpencereport on vtiger_jobexpencereport.jobexpencereportid = vtiger_jobexpencereportcf.jobexpencereportid
										where vtiger_jobexpencereportcf.cf_1248="1" 
											  AND vtiger_jobexpencereportcf.cf_1457="Selling" 
											  
											  AND vtiger_jobexpencereportcf.jobexpencereportid="'.$selling.'" limit 1');
			
			$data=$adb->fetch_array($jrer_selling);
			if(!empty($data['invoice_instruction_no']))
			{
				$job_id = $data['job_id'];
				$adb->pquery("update vtiger_jobexpencereport 
							set accept_generate_invoice=0, invoice_no='', invoice_instruction_no=''								
							where invoice_instruction_no='".$data['invoice_instruction_no']."' 
								  AND job_id='".$job_id."' AND jobexpencereportid='".$selling."' ");	
								  
				$adb->pquery('update vtiger_jobexpencereportcf 
							 set vtiger_jobexpencereportcf.cf_1250="Declined", 
								vtiger_jobexpencereportcf.cf_1248=0 
							 where vtiger_jobexpencereportcf.jobexpencereportid="'.$selling.'"');			  
			}
		}
	}
}
return true;
?>