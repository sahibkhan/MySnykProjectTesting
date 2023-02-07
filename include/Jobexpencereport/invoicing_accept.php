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

$selling_arr = explode(',', $sellingids);

foreach($selling_arr as $key => $selling)
{
	if(!empty($selling))
	{
		$jrer_selling = $adb->pquery('select * from vtiger_jobexpencereportcf 
										INNER JOIN 	vtiger_jobexpencereport on vtiger_jobexpencereport.jobexpencereportid = vtiger_jobexpencereportcf.jobexpencereportid
										where vtiger_jobexpencereportcf.cf_1248="1" AND vtiger_jobexpencereportcf.cf_1457="Selling" 
										AND vtiger_jobexpencereport.job_id="'.$job_id.'" and vtiger_jobexpencereportcf.jobexpencereportid="'.$selling.'" limit 1');
		
		$data=$adb->fetch_array($jrer_selling);
		if(!empty($data['invoice_instruction_no']))
		{			
			$rs_pay_to_info = $adb->pquery("SELECT * FROM `vtiger_accountscf` where accountid='".$data['cf_1445']."'");
			$pay_to_info = $adb->fetch_array($rs_pay_to_info);
						
			$s_invoice_date = date('Y-m-d');
			$s_invoice_date_ =  date('Y-m-d',strtotime($s_invoice_date));
			$due_date = date('Y-m-d',strtotime($s_invoice_date_));
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
									
			$adb->pquery("update vtiger_jobexpencereport set accept_generate_invoice=1, 
															year_no='".date("Y")."' 
															where invoice_instruction_no='".$data['invoice_instruction_no']."' and job_id='".$job_id."' ");						
									
			
			
			$invoice_status= 'Complete';
			
			$db_obj = $adb->pquery('select MAX(serial_number) as max_ordering from vtiger_jobexpencereport
						   INNER JOIN 	vtiger_jobexpencereportcf on vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid
	 					   where vtiger_jobexpencereport.year_no="'.date('Y').'" and vtiger_jobexpencereportcf.cf_1457="Selling" 
						   and vtiger_jobexpencereport.invoice_instruction_no="'.$data['invoice_instruction_no'].'" ');
						   
			if ($adb->num_rows($db_obj)  == 0 or !$db_obj)
			{
				$ordering = 0;
			}
			else
			{				
				$order_row = $adb->fetch_array($db_obj);
				if ( ! is_numeric($order_row['max_ordering']))
				{
					$ordering = 0;
				}
				else
				{
					$ordering = $order_row['max_ordering'];
				}
			}
			
			$serial_number = $ordering+1;
			
			$adb->pquery("update vtiger_jobexpencereport set serial_number='".$serial_number."'															
						 where year_no='".date("Y")."' and invoice_instruction_no='".$data['invoice_instruction_no']."' and job_id='".$job_id."' ");
															
			//$job_info = get_job_details($job_id);	
			//$ref_no = $job_info['cf_1198'];
			$job_info = Vtiger_Record_Model::getInstanceById($job_id, 'Job');	
			$ref_no = $job_info->get('cf_1198');
			
			$ref_no_arr = explode('-',$ref_no);
			
			$invoice_no = $ref_no_arr[1].'-'.$ref_no_arr[2].'-'.str_pad($serial_number, 5, "0", STR_PAD_LEFT).'/'.date('y');
			//$exra_invoicing['invoice_no'] = $invoice_no;
			$adb->pquery("update vtiger_jobexpencereport set invoice_no='".$invoice_no."'															
						 where invoice_instruction_no='".$data['invoice_instruction_no']."' and job_id='".$job_id."' ");
				
			
			$adb->pquery("update vtiger_jobexpencereportcf, vtiger_jobexpencereport set vtiger_jobexpencereportcf.cf_1250='Approved'
						where vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid 
						and vtiger_jobexpencereport.invoice_instruction_no='".$data['invoice_instruction_no']."' 
						and vtiger_jobexpencereport.job_id='".$job_id."' ");
			
		}
										
	}
}


?>