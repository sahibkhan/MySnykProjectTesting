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
$status = $_REQUEST['status'];

if($status=='accept')
{
	$expense_arr = explode(',', $expenseids);
	foreach($expense_arr as $key => $expense)
	{
		if(!empty($expense))
		{													  
			/*$jrer_expense_lock_count = $adb->pquery('select COUNT(*) as total_send, vtiger_jobexpencereport.b_head_of_department_approval_status 
													FROM vtiger_jobexpencereportcf 
													INNER JOIN 	vtiger_jobexpencereport ON vtiger_jobexpencereport.jobexpencereportid = vtiger_jobexpencereportcf.jobexpencereportid
													WHERE vtiger_jobexpencereportcf.cf_1457="Expence" 
												  		  AND vtiger_jobexpencereportcf.jobexpencereportid="'.$expense.'" 
												  		  AND vtiger_jobexpencereport.b_send_to_head_of_department_for_approval=1 
														  AND b_send_to_payables_and_generate_payment_voucher=1
												 
											');*/
			$jrer_expense_lock_count = $adb->pquery('select vtiger_crmentityrel.crmid as jobid, vtiger_jobexpencereportcf.*, vtiger_jobexpencereport.*  from vtiger_jobexpencereportcf 
													INNER JOIN 	vtiger_jobexpencereport ON vtiger_jobexpencereport.jobexpencereportid = vtiger_jobexpencereportcf.jobexpencereportid
													INNER JOIN vtiger_crmentity ON vtiger_jobexpencereport.jobexpencereportid = vtiger_crmentity.crmid
													INNER JOIN vtiger_crmentityrel ON vtiger_crmentity.crmid = vtiger_crmentityrel.relcrmid 
													where vtiger_jobexpencereportcf.cf_1973="Approved" 
														  AND vtiger_jobexpencereportcf.cf_1457="Expence" 
														  AND vtiger_jobexpencereportcf.jobexpencereportid="'.$expense.'" limit 1');								
			// and b_head_of_department_approval_status="Approved"
			$data = $adb->fetch_array($jrer_expense_lock_count);
			if($data['cf_1973']=='Approved')
			{
				$adb->pquery("update vtiger_jobexpencereport set b_payables_approval_status='Approved', b_confirmed_send_to_accounting_software='1' where jobexpencereportid='".$expense."' ");
				$adb->pquery("update vtiger_jobexpencereportcf set  cf_1975='Approved' where  jobexpencereportid='".$expense."' ");
			}
			
		}
	}
}

if($status=='reject')
{
	$expense_arr = explode(',', $expenseids);
	foreach($expense_arr as $key => $expense)
	{
		if(!empty($expense))
		{
			/*$jrer_expense_lock_count = mysql_query('select COUNT(*) as total_send from vtiger_jobexpencereportcf 
											INNER JOIN 	vtiger_jobexpencereport ON vtiger_jobexpencereport.jobexpencereportid = vtiger_jobexpencereportcf.jobexpencereportid
											where vtiger_jobexpencereportcf.cf_1457="Expence" 
												  and vtiger_jobexpencereportcf.jobexpencereportid="'.$expense.'" and vtiger_jobexpencereport.b_send_to_head_of_department_for_approval=1
												  and b_send_to_payables_and_generate_payment_voucher=1 and b_confirmed_send_to_accounting_software!=1
											');*/
			$jrer_expense_lock_count = $adb->pquery('select vtiger_crmentityrel.crmid as jobid, vtiger_jobexpencereportcf.*, vtiger_jobexpencereport.*  from vtiger_jobexpencereportcf 
													INNER JOIN 	vtiger_jobexpencereport ON vtiger_jobexpencereport.jobexpencereportid = vtiger_jobexpencereportcf.jobexpencereportid
													INNER JOIN vtiger_crmentity ON vtiger_jobexpencereport.jobexpencereportid = vtiger_crmentity.crmid
													INNER JOIN vtiger_crmentityrel ON vtiger_crmentity.crmid = vtiger_crmentityrel.relcrmid 
													where vtiger_jobexpencereportcf.cf_1973="Approved" 
														  AND vtiger_jobexpencereportcf.cf_1457="Expence" 
														  AND vtiger_jobexpencereportcf.jobexpencereportid="'.$expense.'" limit 1');	
														  	
			$data=$adb->fetch_array($jrer_expense_lock_count);	
			if($data['cf_1973']=='Approved')
			{
				$adb->pquery("update vtiger_jobexpencereport set b_send_to_head_of_department_for_approval='0', b_send_to_payables_and_generate_payment_voucher='0', b_confirmed_send_to_accounting_software='0',  b_payables_approval_status='Rejected' where jobexpencereportid='".$expense."' ");
				$adb->pquery("update vtiger_jobexpencereportcf set  cf_1975='Declined' where  jobexpencereportid='".$expense."' ");
			}
		}
	}
}

return true;