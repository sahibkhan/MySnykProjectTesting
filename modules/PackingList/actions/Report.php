<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class ExitInterview_Report_Action extends Vtiger_Action_Controller {

    function __construct() {
        parent::__construct();       
        $this->exposeMethod('setReport');
    }

    public function requiresPermission(Vtiger_Request $request){
		$permissions = parent::requiresPermission($request);
		$mode = $request->getMode();
		if(!empty($mode)) {
			switch ($mode) {
				case 'setReport':
					$permissions[] = array('module_parameter' => 'module', 'action' => 'Report', 'record_parameter' => 'recordId');
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


	public function setReport(Vtiger_Request $request) {
		global $adb;
		require_once("libraries/PHPExcel/PHPExcel.php");
        $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        $workbook = $objReader->load("include/ExitInterview/exitInterviewReport.xlsx");
        $worksheet = $workbook->setActiveSheetIndex(0);

        $locationId = $request->get('location');
        $sqlH = "SELECT vtiger_exitinterview.name, vtiger_exitinterview.exitinterviewid, vtiger_crmentity.createdtime, vtiger_exitinterviewcf.cf_7466, 
                        vtiger_exitinterviewcf.cf_7494, vtiger_exitinterviewcf.cf_7472, vtiger_positiontitle.name as `position`
        
        FROM vtiger_exitinterviewcf 
        INNER JOIN vtiger_exitinterview ON vtiger_exitinterviewcf.exitinterviewid = vtiger_exitinterview.exitinterviewid 
        INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_exitinterviewcf.exitinterviewid 
        LEFT JOIN vtiger_positiontitle ON vtiger_positiontitle.positiontitleid = vtiger_exitinterviewcf.cf_7466
        WHERE vtiger_crmentity.deleted = 0 AND vtiger_exitinterviewcf.cf_7780 = ?";
        $resultH = $adb->pquery($sqlH, array($locationId));
        $num_rows = $adb->num_rows($resultH);

        // Number of active users

        $sqlNumUsers = "SELECT vtiger_userlistcf.userlistid        
        FROM vtiger_userlistcf
        INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_userlistcf.userlistid
        WHERE vtiger_crmentity.deleted = 0 AND vtiger_userlistcf.cf_3421 = ? AND vtiger_userlistcf.cf_6206 = ?";
        $resultNumUsers = $adb->pquery($sqlNumUsers, array($locationId, 'Active'));
        $numUsers = $adb->num_rows($resultNumUsers);

        $reasonList = array('Low salary level and absence of bonus', 'Office Location', 'Management Style', 'Long Approval Decision Taking Process', 'Other');
        
        $j = 0;
        $r = 7;

        $totalLow = 0;
        $totalOffice = 0;
        $totalManamagent = 0;
        $totalLong = 0;
        $totalOther = 0;
        $overAllTotal = 0;

        for ($i=0; $i<$num_rows; $i++){

            $exitinterviewid = $adb->query_result($resultH, $i, 'exitinterviewid');
            $employeeName = $adb->query_result($resultH, $i, 'name');
            $dateOfRegistration = $adb->query_result($resultH, $i, 'createdtime');
            $position = $adb->query_result($resultH, $i, 'position');
            $refNo = $adb->query_result($resultH, $i, 'cf_7494');
            $reasonType = $adb->query_result($resultH, $i, 'cf_7472');
            $userModel = Vtiger_Record_model::getInstanceById($employeeName, 'UserList');


            $j ++; $r ++;
            $worksheet->setCellValueByColumnAndRow(0, $r, $userModel->get('name'));
            $worksheet->setCellValueByColumnAndRow(1, $r, $refNo);
            $worksheet->setCellValueByColumnAndRow(2, $r, $dateOfRegistration);
            $worksheet->setCellValueByColumnAndRow(3, $r, $position);


            if ($reasonType == $reasonList[0]){
                $worksheet->setCellValueByColumnAndRow(4, $r, 1);
                $totalLow ++;
            } 
            if ($reasonType == $reasonList[1]){
                $worksheet->setCellValueByColumnAndRow(5, $r, 1);
                $totalOffice ++;
            }

            
            if ($reasonType == $reasonList[2]){
                $worksheet->setCellValueByColumnAndRow(6, $r, 1);
                $totalManamagent ++;
            }
            
            if ($reasonType == $reasonList[3]){
                $worksheet->setCellValueByColumnAndRow(7, $r, 1);
                $totalLong ++;
            }
            
            if ($reasonType == $reasonList[4]){
                $worksheet->setCellValueByColumnAndRow(8, $r, 1);
                $totalOther ++;
            }


        }

    
        $r ++;
        $overAllTotal = $totalLow +	$totalOffice + $totalManamagent + $totalLong + $totalOther;
        $worksheet->setCellValueByColumnAndRow(4, $r, $totalLow.'('.round($totalLow / $overAllTotal * 100).'%)');	
        $worksheet->setCellValueByColumnAndRow(5, $r, $totalOffice.'('.round($totalOffice / $overAllTotal * 100).'%)');	
        $worksheet->setCellValueByColumnAndRow(6, $r, $totalManamagent.'('.round($totalManamagent / $overAllTotal * 100).'%)');	
        $worksheet->setCellValueByColumnAndRow(7, $r, $totalLong.'('.round($totalLong / $overAllTotal * 100).'%)');	
        $worksheet->setCellValueByColumnAndRow(8, $r, $totalOther.'('.round($totalOther / $overAllTotal * 100).'%)');
        
        $r ++;
        $worksheet->setCellValueByColumnAndRow(0, $r, 'Staff Turnover');	        
        $worksheet->setCellValueByColumnAndRow(1, $r, round($num_rows / $numUsers * 100));  

        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=Exit InterView Report.xls");
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');
        
        // If you're serving to IE over SSL, then the following may be needed
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        $workbookWriter = PHPExcel_IOFactory::createWriter($workbook, 'Excel5');
        ob_end_clean();
        $workbookWriter->save('php://output');
        exit;

        $loadUrl = "index.php?module=ExitInterview&view=Detail&record=".$request->get('record');
                echo '<script> 
            var url= "'.$loadUrl.'"; 
            window.location = url; 
        </script>';

	}






}