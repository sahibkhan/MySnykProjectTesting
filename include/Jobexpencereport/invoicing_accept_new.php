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

//$jz_group_arr = array('85772','85768','85761', '85771', '85763', '85762');
$jz_group_arr = array('85772','85761', '85771', '85763', '85762');
$dw_group_arr = array('85756','205751', '420284', '85768');

if($status=='accept')
{
	$selling_arr = explode(',', $sellingids);
			
	foreach($selling_arr as $key => $selling)
	{
		if(!empty($selling))
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
				
				$where_file_title = '';
				$STR_PAD_LEFT = 5;
				if(in_array($file_title,$jz_group_arr))
				{
					$STR_PAD_LEFT = 5;
					$where_file_title = "AND vtiger_jobexpencereportcf.cf_2191 IN ('85772','85768','85761', '85771', '85763', '85762')";
					if($department_id=='85840')
					{
						$where_file_title .= " AND vtiger_jobexpencereport.invoice_no NOT IN('R-02172/17', 'R-02171/17','R-02170/17')";
					}
				}
				elseif(in_array($file_title,$dw_group_arr))
				{
					$STR_PAD_LEFT = 4;
					$where_file_title = "AND vtiger_jobexpencereportcf.cf_2191 IN ('85756','205751', '420284','85768')";
				}
				
					
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
				
				
				
				$db_obj = $adb->pquery('select MAX(serial_number) as max_ordering from vtiger_jobexpencereport
							   INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid
							   where vtiger_jobexpencereport.year_no="'.date('Y').'" 
							   		AND vtiger_jobexpencereportcf.cf_1457="Selling" 							   		
									AND vtiger_jobexpencereportcf.cf_1479= "'.$department_id.'"
									
									'.$where_file_title.'
									');
							   //AND vtiger_jobexpencereportcf.cf_2191 = "'.$file_title.'"
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
				
				//$job_info = get_job_details($job_id);	
				$job_info = Vtiger_Record_Model::getInstanceById($job_id, 'Job');	
				//$ref_no = $job_info['cf_1198'];
				$ref_no = $job_info->get('cf_1198');
				
				$ref_no_arr = explode('-',$ref_no);
				$department_short_code = $ref_no_arr[1];
				
				$rs_serial_number = $adb->pquery("select * from vtiger_jobexpencereport 
												 INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid
							   					 where vtiger_jobexpencereport.year_no='".date('Y')."'  AND 
												 vtiger_jobexpencereport.serial_number ='".$serial_number."' 
												 AND vtiger_jobexpencereport.invoice_no like '".$department_short_code."-%'
												
												".$where_file_title."
												 ");
				// AND vtiger_jobexpencereportcf.cf_2191 = '".$file_title."'
				if($adb->num_rows($rs_serial_number)>0)
				{
					
					$new_db_obj = $adb->pquery('select MAX(serial_number) as max_ordering from vtiger_jobexpencereport
							   INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid
							   where vtiger_jobexpencereport.year_no="'.date('Y').'" 
							   		AND vtiger_jobexpencereportcf.cf_1457="Selling" 
									AND vtiger_jobexpencereport.invoice_no like "'.$department_short_code.'-%"
									
									'.$where_file_title.'
									');
					//AND vtiger_jobexpencereportcf.cf_2191 = "'.$file_title.'"				
					$new_order_row = $adb->fetch_array($new_db_obj);
					$new_ordering = $new_order_row['max_ordering'];
					$serial_number = $new_ordering+1;				
				}
				
				$adb->pquery("update vtiger_jobexpencereport set serial_number='".$serial_number."'															
							 where year_no='".date("Y")."' and invoice_instruction_no='".$data['invoice_instruction_no']."' and job_id='".$job_id."' ");
																
				
				
				//$invoice_no = $ref_no_arr[1].'-'.$ref_no_arr[2].'-'.str_pad($serial_number, 5, "0", STR_PAD_LEFT).'/'.date('y');
				$invoice_no = $ref_no_arr[1].'-'.str_pad($serial_number, $STR_PAD_LEFT, "0", STR_PAD_LEFT).'/'.date('y');
				//$exra_invoicing['invoice_no'] = $invoice_no;
				if($file_title=='420284')
				{
					$invoice_no = $invoice_no.'AZ';
				}
				elseif($file_title=='205751')
				{
					$invoice_no = $invoice_no.'AM';
				}
				elseif($file_title=='85768')
				{
					$invoice_no = $invoice_no.'TM';
				}
				
				$adb->pquery("update vtiger_jobexpencereport set invoice_no='".$invoice_no."'															
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