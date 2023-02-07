<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
//include_once 'modules/Leaverequest/LeaverequestHandler.php';

class Job_JobDataCollection_Action extends Vtiger_Action_Controller {

    function __construct() {
        parent::__construct();       
        $this->exposeMethod('JobDataCollection');
        //$this->exposeMethod('setReject');
    }

    public function requiresPermission(Vtiger_Request $request){
        $permissions = parent::requiresPermission($request);
        $mode = $request->getMode();
        if(!empty($mode)) {
            switch ($mode) {
                // case 'setReject':
                //  $permissions[] = array('module_parameter' => 'module', 'action' => 'Approval');
                //  break;
                case 'JobDataCollection':
                    $permissions[] = array('module_parameter' => 'module', 'action' => 'Approval');
                    break;
                default:
                    break;
            }
        }
        return $permissions;
    }


    function process(Vtiger_Request $request) {
        $mode = $request->getMode();
        if(!empty($mode)) {
            echo $this->invokeExposedMethod($mode, $request);
            return;
        }
        return false;
    }
    
    function JobAssignment(Vtiger_Request $request) {
     include('include/Exchangerate/exchange_rate_class.php');
        global $adb;
        //$adb->setDebug(true);
     $moduleName = 'Job';
     $request->set('source_module','Job');
     $module = $request->get('module');
     $from_date = new DateTime($request->get('fromdate'));
     $to_date = new DateTime($request->get('todate'));
     $Exporttype = $request->get('mode_type');
     
        
     $db = PearDatabase::getInstance();
     $db1 = PearDatabase::getInstance();
     $sno = 0;
     $entries = array();
     $entries_vendor = array();
     foreach($userids as $userid)
     {
             $query = "SELECT * FROM vtiger_jobtask 
             inner join vtiger_jobcf on vtiger_jobtask.job_id = vtiger_jobcf.jobid 
             inner join vtiger_crmentity on vtiger_jobcf.jobid = vtiger_crmentity.crmid
             inner join job_profit on vtiger_jobcf.jobid = job_profit.job_id
             where vtiger_crmentity.deleted = 0 and user_id = ".$userid." and ps_owner_id = ".$userid." and job_owner = '0' and vtiger_crmentity.createdtime between '".$from_date->format("Y-m-d h:i:s")."' and '".$to_date->format("Y-m-d h:i:s")."'"; //job with crmentity can be made
             $result = $db->pquery($query, array());
                
             for($j=0; $j<$db->num_rows($result); $j++) {
                 $entries_vendor[] = $db->fetchByAssoc($result, $j);
                 $sql1 = $db1->pquery("SELECT * FROM `vtiger_users` WHERE `id`=".$db->query_result($result,$j,'ps_owner_id')." LIMIT 1");
                 $sql2 = $db1->pquery("SELECT * FROM `vtiger_users` WHERE `id`=".$db->query_result($result,$j,'ps_owner_id')." LIMIT 1");
                 $entries[] = Array
        (
            'cf_1198' => $db->query_result($result,$j,'cf_1198'),
            'location_id' => Vtiger_LocationList_UIType::getDisplayValue($db->query_result($result,$j,'location_id')),
            'department_id1' => Vtiger_DepartmentList_UIType::getDisplayValue($db->query_result($result,$j,'department_id')),
            'cost' => $db->query_result($result,$j,'cost'),
            'profit_share_received' => $db->query_result($result,$j,'profit_share_received'),
            'ps_owner_id' => Vtiger_GLKUserList_UIType::getDisplayValue($db->query_result($result,$j,'ps_owner_id')),
            'owner_location_id' => Vtiger_LocationList_UIType::getDisplayValue($db1->fetch_array($sql1)['location_id']),
            'owner_department_id' => Vtiger_DepartmentList_UIType::getDisplayValue($db1->fetch_array($sql2)['department_id'])
     );
                 //$entries[] = $db->fetchByAssoc($result, $j);
             }
     }
      //echo "<pre>";
      //print_r($entries); exit;
     $MIS_report_header = array('Job Ref No.', 'Loc','Srv','Cost', 'Profit', 'Coordinator', 'Coordinator Location', 'Coordinatior Department');
     //$Exporttype ='vendor';
     //$this->writeReportToExcelFile_Vendor($'job', $MIS_report_header, $entries, '');
     //$this->getReportXLS($request, $MIS_report_header, $entries);
     if($Exporttype == 'vendor')
     {
         $out = [];
         $i = 0;
         $db = PearDatabase::getInstance();
         foreach ($entries_vendor as $key => $value) {
             $info = [];
             $info['title'] = 'Job';
             $info['Job Ref #'] = $value['cf_1198'];
             $info['File Title'] = Vtiger_CompanyList_UIType::getDisplayValue($value['cf_1186']);
             $info['Location'] = Vtiger_LocationList_UIType::getDisplayValue($value['cf_1188']);
             $info['Department'] = Vtiger_DepartmentList_UIType::getDisplayValue($value['cf_1190']);
             //$customer_details = Vtiger_Record_model::getInstanceById($value['cf_1441'], 'Accounts');
             $sql = $db->pquery("SELECT `accountname` FROM `vtiger_account` WHERE `accountid`='".$value['cf_1441']."' LIMIT 1");
             $info['Customer'] = $db->fetch_array($sql)['accountname']; 
             $info['Job status'] = $value['cf_2197'];
             $info['Charge'] = '';
             $info['Pay To'] = '';
             $info['Invoice Date'] = '';
             $info['Buy (Vendor Cur Net)'] = '';
             $info['VAT Rate'] = '';
             $info['VAT'] = '';
             $info['Buy (Vendor Cur Gross)'] = '';
             $info['Vendor Curr'] = '';
             $info['Exch Rate'] = '';
             $info['Buy (Local Curr Gross)'] = '';
             $info['Buy (Local Cur NET)'] = '';
             $info['Cost in ($)'] = '';
             $out[] = $info;
            
             $job_id = $value['job_id'];
             $owner_id = $value['ps_owner_id'];
             $sql = $db->pquery("SELECT  * FROM `vtiger_jobexpencereport` 
                INNER JOIN `vtiger_crmentity` ON `vtiger_crmentity`.crmid=`vtiger_jobexpencereport`.`jobexpencereportid` 
                INNER JOIN `vtiger_crmentityrel` ON (`vtiger_crmentityrel`.`relcrmid` = `vtiger_crmentity`.`crmid`) 
                LEFT JOIN `vtiger_jobexpencereportcf` AS `vtiger_jobexpencereportcf` ON `vtiger_jobexpencereportcf`.`jobexpencereportid`=`vtiger_jobexpencereport`.`jobexpencereportid` 
                INNER JOIN `vtiger_locationcf` AS `vtiger_locationcf` ON `vtiger_locationcf`.`locationid`=`vtiger_jobexpencereportcf`.`cf_1477` 
                INNER JOIN `vtiger_departmentcf` AS `vtiger_departmentcf` ON `vtiger_departmentcf`.`departmentid`=`vtiger_jobexpencereportcf`.`cf_1479` 
                INNER JOIN `vtiger_currency_info` AS `vtiger_currency_info` ON `vtiger_currency_info`.`id`=`vtiger_jobexpencereportcf`.`cf_1345` 
                WHERE `vtiger_crmentity`.`deleted`=0 AND `vtiger_crmentityrel`.`crmid`=? and `vtiger_crmentityrel`.`module`='Job' AND `vtiger_jobexpencereport`.`owner_id`=".$owner_id." AND `vtiger_crmentityrel`.`relmodule`='Jobexpencereport' and `vtiger_jobexpencereportcf`.`cf_1457`='Expence'", [$job_id]);

             $num = $db->num_rows($sql);

             for ($i=0;$i<$num;$i++) {
                 $res = $db->fetch_array($sql);
                 //print_r($res); exit;
                 //usd rate
                 $invoice_date = $res['cf_1216'];
                 $b_exchange_rate = 0;
                 if($invoice_date == ''){ 
                        $invoice_date = date('Y-m-d', strtotime($res['createdtime']));
                 }
                    //$company_id = $value['cf_2191'];      
                    //$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency($company_id);        
                    $file_title_currency = $res['currency_code'];//$reporting_currency;
                 if($file_title_currency!='USD')
                    {
                        $b_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $invoice_date);
                    }else{
                        $b_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $invoice_date);
                    }
                    $value_in_usd_normal = $res['cf_1349']; 
                    if($file_title_currency!='USD')
                    {
                        $value_in_usd_normal = $res['cf_1349']/$b_exchange_rate;
                    }
                    
                    $value_in_usd = number_format($value_in_usd_normal,2,'.','');
                    //echo $value_in_usd." , ".$res['cf_1349']."<br>"; 
                 //usd rate
                 $info = [];
                 $info['title'] = 'Expense';
                 $info['Job Ref #'] = $value['cf_1198'];
                 $info['File Title'] = '';
                 $info['Location'] = $res['cf_1559'];//$res['cf_1477'];
                 $info['Department'] = $res['cf_1542'];//$res['cf_1479'];
                 $info['Customer'] = '';
                 $info['Job status'] = '';
                 $sql1 = $db->pquery("SELECT `name` FROM `vtiger_chartofaccount` WHERE `chartofaccountid`='".$res['cf_1453']."' LIMIT 1");
                 $info['Charge'] = $db->fetch_array($sql1)['name'];
                 $sql1 = $db->pquery("SELECT `accountname` FROM `vtiger_account` WHERE `accountid`='".$res['cf_1367']."' LIMIT 1");
                 $info['Pay To'] = $db->fetch_array($sql1)['accountname'];
                 $info['Invoice No'] = $res['cf_1212'];
                 $info['Invoice Date'] = $res['cf_1216'];
                 $info['Buy (Vendor Cur Net)'] = $res['cf_1337'];
                 $info['VAT Rate'] = $res['cf_1339'];
                 $info['VAT'] = $res['cf_1341'];
                 $info['Buy (Vendor Cur Gross)'] = $res['cf_1343'];
                 $info['Vendor Curr'] = $res['currency_code'];//$res['cf_1345'];
                 $info['Exch Rate'] = $res['cf_1222'];
                 $info['Buy (Local Curr Gross)'] = $res['cf_1347'];
                 $info['Buy (Local Cur NET)'] = $res['cf_1349'];
                 //$info['cost in ($)'] = '';
                 $info['Exchange Rate'] = $b_exchange_rate;
                 $info['Value in USD'] = $value_in_usd;
                 $out[] = $info;
             } 
         }

         $entries = $out;
         //echo "<pre>"; print_r($entries); exit;
         $this->getReportXLS_XLSX($request, $MIS_report_header, $entries, $moduleName, $Exporttype,"");
     }
     else{
         $this->getReportXLS($request, $MIS_report_header, $entries);
     }
    }

    function getReportXLS_XLSX($request, $headers, $entries, $moduleName, $format = 'kerry_format') {

        $rootDirectory = vglobal('root_directory');
        $tmpDir = vglobal('tmp_dir');

        $tempFileName = tempnam($rootDirectory.$tmpDir, 'xlsx');

        $moduleName = $request->get('source_module');

        //$fileName = $this->getName().'.xls';
        $fileName = $moduleName.'.xlsx';
        $Exporttype = $request->get('Exporttype');

        

        if($moduleName=='Job' && $format == 'kerry_format'){
            $this->writeReportToExcelFile_Job($tempFileName, $headers, $entries, false);
        } else if ($moduleName=='Job' && $format == 'kpi') {
            $this->writeReportToExcelFile_kpi_Job($tempFileName, $headers, $entries, false);
        } elseif ($moduleName == 'Job' && $format == 'vendor') {
            $this->writeReportToExcelFile_Vendor($tempFileName, $headers, $entries, false);
        }
        

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fileName.'"');
        header('Cache-Control: max-age=0');
        /*
        if(isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
            header('Pragma: public');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        }

        header('Content-Type: application/x-msexcel');
        header('Content-Length: '.@filesize($tempFileName));
        header('Content-disposition: attachment; filename="'.$fileName.'"');
        */
        $fp = fopen($tempFileName, 'rb');
        fpassthru($fp);
        //unlink($tempFileName);

    }

    function getReportXLS($request, $headers, $entries) {

        $rootDirectory = vglobal('root_directory');
        $tmpDir = vglobal('tmp_dir');

        $tempFileName = tempnam($rootDirectory.$tmpDir, 'xls');

        $moduleName = $request->get('source_module');

        //$fileName = $this->getName().'.xls';
        $fileName = $moduleName.'.xls';



        $this->writeReportToExcelFile($tempFileName, $headers, $entries, false);

        if(isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
            header('Pragma: public');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        }

        header('Content-Type: application/x-msexcel');
        header('Content-Length: '.@filesize($tempFileName));
        header('Content-disposition: attachment; filename="'.$fileName.'"');

        $fp = fopen($tempFileName, 'rb');
        fpassthru($fp);
        //unlink($tempFileName);

    }

    function writeReportToExcelFile_Vendor($fileName, $headers, $entries, $filterlist='') {
        global $currentModule, $current_language;
        $mod_strings = return_module_language($current_language, $currentModule);
        require_once 'libraries/PHPExcel/PHPExcel.php';
        $objPHPExcel = new PHPExcel();
        $current_user = Users_Record_Model::getCurrentUserModel();
        $full_name = $current_user->get('first_name')." ".$current_user->get('last_name');
        $objPHPExcel->getProperties()->setCreator($full_name)
                                     ->setLastModifiedBy($full_name)
                                     ->setTitle($fileName)
                                     ->setSubject($fileName)
                                     ->setDescription($fileName)
                                     ->setKeywords($fileName)
                                     ->setCategory($fileName);
        $objPHPExcel->setActiveSheetIndex(0);

        $objPHPExcel->getActiveSheet()->setTitle("Vendor JCR Report");

        // $air_headers = array('title','Job Ref #','File Title','Location','Department','Customer','Job status','Charge','Pay To','Buy (Local Cur NET)','Vendor Curr');

        $air_headers = array('title','Job Ref #','File Title','Location','Department','Customer','Job status','Charge','Pay To', 'Invoice No', 'Invoice Date','Buy (Vendor Cur Net)','VAT Rate','VAT','Buy (Vendor Cur Gross)','Vendor Curr','Exch Rate','Buy (Local Curr Gross)','Buy (Local Cur NET)', 'Exchange Rate', 'Value in USD');


        $objPHPExcel->getActiveSheet()->fromArray($air_headers, null, 'A1');
        $objPHPExcel->getActiveSheet()->freezePane('G3');
        $objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);
        $length = sizeof($entries)+3;

        $sharedStyle1 = new PHPExcel_Style();
        $sharedStyle1->applyFromArray(
            array('fill'    => array(
                                        'type'      => PHPExcel_Style_Fill::FILL_SOLID,
                                        'color'     => array('argb' => 'FFCCFFCC')
                                    ),
                  'borders' => array( 'allborders' =>array(
                                          'style' => PHPExcel_Style_Border::BORDER_THIN
                                        //'bottom'  => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                                        //'right'       => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
                                    )),
                    'alignment' => array(
                                       'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                                       'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                                       'wrap' => true

                                    )
                 ));
        $sharedStyle2 = new PHPExcel_Style();
        $sharedStyle2->applyFromArray(
            array('fill'    => array(
                                        'type'      => PHPExcel_Style_Fill::FILL_SOLID,
                                        'color'     => array('argb' => 'f2f2f2')
                                    ),
                  'borders' => array( 'allborders' =>array(
                                          'style' => PHPExcel_Style_Border::BORDER_THIN
                                        //'bottom'  => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                                        //'right'       => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
                                    )),
                    'alignment' => array(
                                        'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                                        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                                    )
                 ));

        $style = new PHPExcel_Style();
        $style->applyFromArray(
            array('fill'    => array(
                                        'type'      => PHPExcel_Style_Fill::FILL_SOLID,
                                        'color'     => array('argb' => 'FFCCFFCC')
                                    ),
                  'borders' => array('allborders' =>array(
                                          'style' => PHPExcel_Style_Border::BORDER_THIN
                                        //'bottom'  => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                                        //'right'       => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
                                    )),
                    'alignment' => array(
                                       'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                                       'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                                       'wrap' => true

                                    )
                 ));

        $objPHPExcel->getActiveSheet()->setSharedStyle($sharedStyle1, "A1:U1");
        $objPHPExcel->getActiveSheet()->fromArray($entries, null, 'A3');
        PHPExcel_Shared_Font::setAutoSizeMethod(PHPExcel_Shared_Font::AUTOSIZE_METHOD_EXACT);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        ob_end_clean();
        $objWriter->save($fileName);

    }


    function writeReportToExcelFile($fileName, $headers, $entries, $filterlist='') {
        global $currentModule, $current_language;
        $mod_strings = return_module_language($current_language, $currentModule);

        require_once("libraries/PHPExcel/PHPExcel.php");

        $workbook = new PHPExcel();
        $worksheet = $workbook->setActiveSheetIndex(0);

        $header_styles = array(
            //'fill' => array( 'type' => PHPExcel_Style_Fill::FILL_NONE, 'color' => array('rgb'=>'E1E0F7') ),
            'fill' => array( 'type' => PHPExcel_Style_Fill::FILL_NONE ),

            //'font' => array( 'bold' => true )
        );


        if(isset($headers)) {
            $count = 0;
            $rowcount = 1;

            $arrayFirstRowValues = $headers;
            //array_pop($arrayFirstRowValues);

            // removed action link in details
            foreach($arrayFirstRowValues as $key=>$value) {
                $worksheet->setCellValueExplicitByColumnAndRow($count, $rowcount, $key, true);
                $worksheet->getStyleByColumnAndRow($count, $rowcount)->applyFromArray($header_styles);

                // NOTE Performance overhead: http://stackoverflow.com/questions/9965476/phpexcel-column-size-issues
                //$worksheet->getColumnDimensionByColumn($count)->setAutoSize(true);

                $count = $count + 1;
            }

            $rowcount++;

            $count = 0;
            //array_pop($array_value);  // removed action link in details
            foreach($headers as $hdr => $value) {
                $value = decode_html($value);
                // TODO Determine data-type based on field-type.
                // String type helps having numbers prefixed with 0 intact.
                $worksheet->setCellValueExplicitByColumnAndRow($count, $rowcount, $value, PHPExcel_Cell_DataType::TYPE_STRING);
                $count = $count + 1;
            }
            //$rowcount++;

            $rowcount++;
            foreach($entries as $key => $array_value) {
                $count = 0;
                //array_pop($array_value);  // removed action link in details
                foreach($array_value as $hdr => $value_excel) {
                    if(is_array($value_excel))
                    {
                        $value = '';
                    }
                    else{
                    $value = decode_html($value_excel);
                    }
                    // TODO Determine data-type based on field-type.
                    // String type helps having numbers prefixed with 0 intact.
                    $worksheet->setCellValueExplicitByColumnAndRow($count, $rowcount, $value, PHPExcel_Cell_DataType::TYPE_STRING);
                    $count = $count + 1;
                }
                $rowcount++;
            }


        }


        $workbookWriter = PHPExcel_IOFactory::createWriter($workbook, 'Excel5');
        ob_end_clean();
        $workbookWriter->save($fileName);

    }

}