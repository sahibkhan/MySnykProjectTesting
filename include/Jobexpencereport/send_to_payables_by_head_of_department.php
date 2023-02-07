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
$job_id = $_REQUEST['job_id'];
//$head_role = $_REQUEST['role'];
$status = $_REQUEST['status'];

if($status=='accept')
{
	$expense_arr = explode(',', $expenseids);
	foreach($expense_arr as $key => $expense)
	{
		if(!empty($expense))
		{
			$jrer_expense_lock_count = $adb->pquery('select COUNT(*) as total_send, vtiger_jobexpencereport.b_head_of_department_approval_status from vtiger_jobexpencereportcf 
											INNER JOIN 	vtiger_jobexpencereport ON vtiger_jobexpencereport.jobexpencereportid = vtiger_jobexpencereportcf.jobexpencereportid
											where vtiger_jobexpencereportcf.cf_1457="Expence" AND vtiger_jobexpencereport.job_id="'.$job_id.'" 
												  and vtiger_jobexpencereportcf.jobexpencereportid="'.$expense.'" 
												  and vtiger_jobexpencereport.b_send_to_head_of_department_for_approval=1
												 
											');
				// and b_head_of_department_approval_status="Approved"
			$data=$adb->fetch_array($jrer_expense_lock_count);	
			if($data['total_send']==1)
			{
				//if($data['b_head_of_department_approval_status']!='Approved')
				//{
					$adb->pquery("update vtiger_jobexpencereport set b_head_of_department_approval_status='Approved', b_payables_approval_status='Pending', b_send_to_payables_and_generate_payment_voucher='1' where jobexpencereportid='".$expense."' ");
					$adb->pquery("update vtiger_jobexpencereportcf set  cf_1975='Submitted' where  jobexpencereportid='".$expense."' ");
				//}
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
			$jrer_expense_lock_count = $adb->pquery('select COUNT(*) as total_send from vtiger_jobexpencereportcf 
											INNER JOIN 	vtiger_jobexpencereport ON vtiger_jobexpencereport.jobexpencereportid = vtiger_jobexpencereportcf.jobexpencereportid
											where vtiger_jobexpencereportcf.cf_1457="Expence" AND vtiger_jobexpencereport.job_id="'.$job_id.'" 
												  and vtiger_jobexpencereportcf.jobexpencereportid="'.$expense.'" and vtiger_jobexpencereport.b_send_to_head_of_department_for_approval=1
											');
				
			$data=$adb->fetch_array($jrer_expense_lock_count);	
			if($data['total_send']==1)
			{
				$adb->pquery("update vtiger_jobexpencereport set b_send_to_head_of_department_for_approval='0', b_head_of_department_approval_status='Rejected' where jobexpencereportid='".$expense."' ");
			}
		}
	}
}

return true;

?>