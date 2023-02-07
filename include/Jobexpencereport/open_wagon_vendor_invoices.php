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

    $expense_arr = explode(',', $expenseids);
	foreach($expense_arr as $key => $expense)
	{
		if(!empty($expense))
		{
            $jrer_expense_lock_count = $adb->pquery('select vtiger_jobexpencereport.b_send_to_head_of_department_for_approval, vtiger_jobexpencereport.jrer_buying_id from vtiger_jobexpencereportcf 
										INNER JOIN 	vtiger_jobexpencereport ON vtiger_jobexpencereport.jobexpencereportid = vtiger_jobexpencereportcf.jobexpencereportid
										where vtiger_jobexpencereportcf.cf_1457="Expence" 
										AND vtiger_jobexpencereportcf.jobexpencereportid="'.$expense.'" 
																					  
                                        ');
                                        												
            $data=$adb->fetch_array($jrer_expense_lock_count);
            if($data['b_send_to_head_of_department_for_approval']==1)
		    {
                $sub_jrer_buying_id =$data['jrer_buying_id'];

                $adb->pquery("update vtiger_jobexpencereport
                set b_send_to_head_of_department_for_approval='0', 
                    b_head_of_department_approval_status='', 
                    b_payables_approval_status='', 
                    b_confirmed_send_to_accounting_software='0',
                    b_send_to_payables_and_generate_payment_voucher='0'
                where jobexpencereportid='".$expense."' ");
                //cf_1975 For payables
                //cf_1973 for Head
                $adb->pquery("update vtiger_jobexpencereportcf set  cf_1975='Declined', cf_1973='Declined' where  jobexpencereportid='".$expense."' ");

                if(!empty($sub_jrer_buying_id))
                {
                    //For Subj JRER id
                    $adb->pquery("update vtiger_jobexpencereport
                            set b_send_to_head_of_department_for_approval='0', 
                                b_head_of_department_approval_status='', 
                                b_payables_approval_status='', 
                                b_confirmed_send_to_accounting_software='0',
                                b_send_to_payables_and_generate_payment_voucher='0'
                            where jobexpencereportid='".$sub_jrer_buying_id."' ");
                    //cf_1975 For payables
                    //cf_1973 for Head
                    $adb->pquery("update vtiger_jobexpencereportcf set  cf_1975='Declined', cf_1973='Declined' where  jobexpencereportid='".$sub_jrer_buying_id."' ");
                }

            }
        }
    }
return true;
?>