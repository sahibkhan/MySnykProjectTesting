<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Project_Approval_Action extends Vtiger_Action_Controller {

    function __construct() {
        parent::__construct();       
        $this->exposeMethod('setApproval');
    }

    public function requiresPermission(Vtiger_Request $request){
		$permissions = parent::requiresPermission($request);
		$mode = $request->getMode();
		if(!empty($mode)) {
			switch ($mode) {
				case 'setApproval':
					$permissions[] = array('module_parameter' => 'module', 'action' => 'Approval', 'record_parameter' => 'recordId');
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


	public function setApproval(Vtiger_Request $request) {
		global $adb;
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
		require_once("libraries/PHPExcel/PHPExcel.php");
        $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        $workbook = $objReader->load("modules/Project/TasksReportTemplate.xlsx");
        $worksheet = $workbook->setActiveSheetIndex(0);
        $currentUserLocationId = $currentUserModel->get('location_id');
        $currentUserDepartmentId = $currentUserModel->get('department_id');
        
        $location = $request->get('location');
        $departments = $request->get('department');
        $devTeam = $request->get('devteam');

        if ($devTeam){
            $sqlPrefix = " AND vtiger_crmentity.smownerid IN (374, 9, 1635, 1673, 1681, 1083, 1636)";
        } else {

            $sqlPrefix = ' AND vtiger_projectcf.cf_7854 = '.$currentUserLocationId;
            if (!empty($departments)){
                $sqlDeps = implode(',', $departments);
                $sqlPrefix .= ' AND vtiger_projectcf.cf_7166 IN ('.$sqlDeps.') ';
            } else {            
                $sqlPrefix .= ' AND vtiger_projectcf.cf_7166 IN ('.$currentUserDepartmentId.') ';
            }

        }



        // $datePeriod1 = $request->get('datePeriod');
        // $datePeriod = explode(',', $datePeriod1); */
        // $locationModel = Vtiger_Record_Model::getInstanceById($locationId, 'Location');
        // $departmentModel = Vtiger_Record_Model::getInstanceById($departmentID, 'Department');

        // $sqlPrefix = '';
        // if (!empty($datePeriod1)){
        //     $dateFrom = Date('Y-m-d', strtotime($datePeriod[0]));
        //     $dateTo = Date('Y-m-d', strtotime($datePeriod[1]));
        //     $sqlPrefix = ' AND vtiger_crmentity.createdtime BETWEEN "'.$dateFrom.'" AND "'.$dateTo.'" ';
        // }  

        // Heading of report     
        $subject = "Report";
        $whoRequestedName = $currentUserModel->get('first_name').' '.$currentUserModel->get('last_name');
		$whoRequestedEmail = strtolower(trim($currentUserModel->get('email1')));
		$whoRequestedDep = $currentUserModel->getDisplayValue('department_id');
		$whoRequestedTel = $currentUserModel->get('phone_work');
  
        $worksheet->setCellValueByColumnAndRow(3, 6, $whoRequestedName);
        $worksheet->setCellValueByColumnAndRow(3, 7, $whoRequestedTel);
        $worksheet->setCellValueByColumnAndRow(3, 8, $whoRequestedEmail);
        $worksheet->setCellValueByColumnAndRow(3, 9, $whoRequestedDep);

        $worksheet->setCellValueByColumnAndRow(1, 9, $subject);
        $worksheet->setCellValueByColumnAndRow(1, 8, date('Y-m-d'));

 



        // Report body        
		$queryProjects = $adb->pquery("SELECT CONCAT(vtiger_users.first_name,' ',vtiger_users.last_name) as projectcreator,
                                              vtiger_project.projectname, vtiger_projectmilestone.projectmilestonename, vtiger_projectmilestone.projectmilestoneid,
                                              vtiger_projectmilestone.projectmilestonedate, vtiger_projectmilestonecf.*, 
                                              vtiger_project.targetenddate, vtiger_project.actualenddate, vtiger_projectcf.cf_7842

                                            FROM vtiger_project 
                                            INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_project.projectid
                                            INNER JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid
                                            INNER JOIN vtiger_projectcf ON vtiger_projectcf.projectid = vtiger_crmentity.crmid
                                            LEFT JOIN vtiger_projectmilestone ON vtiger_projectmilestone.projectid = vtiger_project.projectid
                                            LEFT JOIN vtiger_projectmilestonecf ON vtiger_projectmilestonecf.projectmilestoneid = vtiger_projectmilestone.projectmilestoneid
                                          
                        
                                            WHERE vtiger_crmentity.deleted = 0 ".$sqlPrefix);
		$nRows = $adb->num_rows($queryProjects);
        $rowCol = 11;
		for ($i = 0; $i< $nRows; $i++){

            
            $rowCol ++;
            $projectCreator = $adb->query_result($queryProjects, $i, 'projectcreator');
            $projectname = $adb->query_result($queryProjects, $i, 'projectname');
            
            $projectmilestonename = $adb->query_result($queryProjects, $i, 'projectmilestonename');
            $projectmilestoneid = $adb->query_result($queryProjects, $i, 'projectmilestoneid');

            $projectRevisedDeadline = $adb->query_result($queryProjects, $i, 'actualenddate');
            $taskRevisedDeadline = $adb->query_result($queryProjects, $i, 'cf_7860');

            $projectDeadlineReason = $adb->query_result($queryProjects, $i, 'cf_7862');
            $taskDeadlineReason = $adb->query_result($queryProjects, $i, 'cf_7858');
            
            $projectStatus = $adb->query_result($queryProjects, $i, 'cf_7842');
            $taskStatus = $adb->query_result($queryProjects, $i, 'cf_7838');
            
            $projectDeadline = $adb->query_result($queryProjects, $i, 'targetenddate');
            $taskDeadline = $adb->query_result($queryProjects, $i, 'projectmilestonedate');


            $worksheet->setCellValueByColumnAndRow(0, $rowCol, $projectname);
            $worksheet->setCellValueByColumnAndRow(1, $rowCol, $projectCreator);
            $worksheet->setCellValueByColumnAndRow(2, $rowCol, $projectmilestonename);

            $worksheet->setCellValueByColumnAndRow(2, $rowCol, $startdate);
            $worksheet->setCellValueByColumnAndRow(2, $rowCol, $deadline);


            // Get task info

            $queryProjectTask = $adb->pquery("SELECT vtiger_crmentity.description, CONCAT(vtiger_users.first_name,' ',vtiger_users.last_name) as taskassignedto

                                            FROM vtiger_projectmilestone
                                            INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_projectmilestone.projectmilestoneid
                                            INNER JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid
 
                                            WHERE vtiger_crmentity.deleted = 0 AND vtiger_projectmilestone.projectmilestoneid = ?", array($projectmilestoneid));
      
            $numTasks = $adb->num_rows($queryProjectTask);

            $description = $adb->query_result($queryProjectTask, 0, 'description');
            $taskassignedto = $adb->query_result($queryProjectTask, 0, 'taskassignedto');

            if ($numTasks > 0){
                $status = $taskStatus;
                $deadline = $taskDeadline;
                $reviseddeadline = $taskRevisedDeadline;
                $reason = $taskDeadlineReason;
            } else {
                $status = $projectStatus;                
                $deadline = $projectDeadline;
                $reviseddeadline = $projectRevisedDeadline;
                $reason = $projectDeadlineReason;
            } 

            $worksheet->setCellValueByColumnAndRow(0, $rowCol, $projectname);
            $worksheet->setCellValueByColumnAndRow(1, $rowCol, $projectCreator);
            $worksheet->setCellValueByColumnAndRow(2, $rowCol, $projectmilestonename);    
            $worksheet->setCellValueByColumnAndRow(3, $rowCol, $description);
            $worksheet->setCellValueByColumnAndRow(4, $rowCol, $taskassignedto);
                      
            $worksheet->setCellValueByColumnAndRow(5, $rowCol, $deadline);       
            $worksheet->setCellValueByColumnAndRow(6, $rowCol, $reviseddeadline);       
            $worksheet->setCellValueByColumnAndRow(7, $rowCol, $reason);       

            // Deadline / Revised deadline / Reason to change



            $worksheet->setCellValueByColumnAndRow(8, $rowCol, $status);
		}

 
        $worksheet->getStyle('A1:D'.$worksheet->getHighestRow())->getAlignment()->setWrapText(true); 
    

        $worksheet->getStyle("A11:I{$rowCol}")->applyFromArray(array(
            'borders' => array(
                'allborders' => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN
                )
            )
        ));



			header('Content-Type: application/vnd.ms-excel');
			header("Content-Disposition: attachment;filename=Tasks Report.xls");
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
	}






}