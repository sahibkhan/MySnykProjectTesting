<?php

// include 'include/database/PearDatabase.php';
require 'rb.php';
include(dirname(__FILE__).'/modules/Vtiger/models/Record.php');
//$b = Vtiger_Record_Model::getInstanceById(29538, 'Accounts');
$b = new Vtiger_Record_Model();
$b = $b->getInstanceById(29538, 'Accounts');
var_dump($b);
exit;

class CustomerStatus
{
    function connectADB()
    {
        R::setup('mysql:host=localhost;dbname=live_gems', 'root', '!gl0b@l1nk');
        R::freeze('true');
    }

    function getAccounts()
    {

        $this->connectADB();

        $query =    "SELECT
                        vtiger_crmentity.crmid AS account_id,
                        vtiger_crmentity.setype AS account_setype,
                        vtiger_crmentity.label AS account_label,
                        vtiger_jobcf.cf_1198 AS job_ref_number,
                        vtiger_jobexpencereport.jobexpencereportid AS expence_report_id
                    FROM
                        vtiger_crmentity
                    INNER JOIN vtiger_jobcf ON vtiger_crmentity.crmid = vtiger_jobcf.cf_1441
                    INNER JOIN vtiger_jobexpencereport ON vtiger_jobexpencereport.job_id = vtiger_jobcf.jobid
                    INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid
                    WHERE
                        vtiger_crmentity.deleted = 0 AND vtiger_crmentity.setype = 'Accounts'
                    ORDER BY vtiger_crmentity.label ASC";

        return R::getAll($query, array());
    }

    function getJobs($account_ids)
    {
        $this->connectADB();

        $account_ids = implode(',', $account_ids);

        $query =    "SELECT
                        jobexpencereportid,
                        job_id,
                        jobFileEntity.smownerid AS jobFileCreatorId,
                        invoice_instruction_no,
                        vtiger_crmentity.createdtime
                    FROM
                        vtiger_jobexpencereport
                    INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid
                    INNER JOIN vtiger_crmentity AS jobFileEntity
                    ON
                        jobFileEntity.crmid = vtiger_jobexpencereport.job_id
                    WHERE
                        invoice_instruction_no <> '' AND vtiger_jobexpencereport.job_id IN($account_ids)
                    GROUP BY
                        job_id,
                        invoice_instruction_no";

        return R::getAll($query, array());
        
    }
}

$a = new CustomerStatus();

$b = Vtiger_Record_Model::getInstanceById(29538, 'Accounts');

var_dump($b);

// $a->getAccounts();
// echo '<pre>';
// $account_ids = array(3445300, 3639143);
// var_dump($a->getJobs($account_ids));
