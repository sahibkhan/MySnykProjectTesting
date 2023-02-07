<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
class PerformanceAppraisal_ExportData_Action extends Vtiger_ExportData_Action {

	function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

		$currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		if(!$currentUserPriviligesModel->hasModuleActionPermission($moduleModel->getId(), 'Export')) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
	}

	function __construct() {
		parent::__construct();
		
	}

	/**
	 * Function is called by the controller
	 * @param Vtiger_Request $request
	 */
	function process(Vtiger_Request $request) {
		
		$this->ExportData($request);
	}

	private $moduleInstance;
	private $focus;

	/**
	 * Function exports the data based on the mode
	 * @param Vtiger_Request $request
	 */
	function ExportData(Vtiger_Request $request) {
		
		$db = PearDatabase::getInstance();
		$moduleName = $request->get('source_module');

		$this->moduleInstance = Vtiger_Module_Model::getInstance($moduleName);
		$this->moduleFieldInstances = $this->moduleInstance->getFields();
		$this->focus = CRMEntity::getInstance($moduleName);

		$query = $this->getExportQuery($request);

		$result = $db->pquery($query, array());

		$headers = array();
		//Query generator set this when generating the query
		if(!empty($this->accessibleFields)) {
			$accessiblePresenceValue = array(0,2);
			foreach($this->accessibleFields as $fieldName) {
				$fieldModel = $this->moduleFieldInstances[$fieldName];
				// Check added as querygenerator is not checking this for admin users
				$presence = $fieldModel->get('presence');
				if(in_array($presence, $accessiblePresenceValue)) {
					$headers[] = $fieldModel->get('label');
				}
			}
		} else {
			foreach($this->moduleFieldInstances as $field) $headers[] = $field->get('label');
		}
		$translatedHeaders = array();
		foreach($headers as $header) $translatedHeaders[] = vtranslate(html_entity_decode($header, ENT_QUOTES), $moduleName);

		$entries = array();
		for($j=0; $j<$db->num_rows($result); $j++) {
			$entries[] = $this->sanitizeValues($db->fetchByAssoc($result, $j));
			usleep(1);
		}


		$Exporttype = $request->get('Exporttype');		
		
		if($moduleName=='PerformanceAppraisal' && $Exporttype=='by_final_score')
		{
			$entries_new = array();
			$out = [];
			$k = 1;
						
			foreach($entries as $key => $entry)
			{
				$db = PearDatabase::getInstance();
				$rs_performance_appraisal = $db->pquery("select vtiger_performanceappraisal.performanceappraisalid, vtiger_crmentity.smownerid
											 from vtiger_performanceappraisal
											 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid=vtiger_performanceappraisal.performanceappraisalid
											 where vtiger_performanceappraisal.name=? AND vtiger_crmentity.deleted=0 limit 1", array($entry['name']));
				$performanceappraisal_id = $db->query_result($rs_performance_appraisal, '0', 'performanceappraisalid');		
				$performance_appraisal_owner_id = $db->query_result($rs_performance_appraisal, '0', 'smownerid');							 
				
				$performanceAppraisal_info_detail = Vtiger_Record_Model::getInstanceById($performanceappraisal_id, 'PerformanceAppraisal');
				
				$immediate_supervisor_user_info = Users_Record_Model::getInstanceById($performance_appraisal_owner_id, 'Users');
				
				$result_answer_s = $db->pquery("SELECT sum(answer_value) as subtotal FROM `appraisal_questions_answer` WHERE appraisal_id=? ",array($performanceappraisal_id));
				$row_answer_s = $db->fetch_array($result_answer_s);
				$subtotal = $row_answer_s['subtotal'];
				$voice_average_score = round($subtotal/12,1);

				$info = [];
				$info['No.'] = $k++;
				$info['Appraisee Name'] = $entry['cf_6560'];
				$info['Department'] = $entry['cf_6564'];
				$info['Location'] = $entry['cf_6562'];
				$info['Location'] = $entry['cf_6562'];
				$info['Immediate Supervisor'] = $immediate_supervisor_user_info->get('first_name').' '.$immediate_supervisor_user_info->get('last_name');
				$info['Date of Survey'] = date('m/d/Y',strtotime($entry['createdtime']));

				switch($voice_average_score)
				{
					case $voice_average_score >= '7.8' and $voice_average_score <= '8':
						$average_score_grade = 'Excellent';
						$final_grade = 'A';
					break;
					case $voice_average_score >= '6.8' and $voice_average_score <= '7.7':
						$average_score_grade = 'Outstanding';
						$final_grade = 'A-';
						break;
					case $voice_average_score >= '4.8' and $voice_average_score <= '6.7':
						$average_score_grade = 'Good';
						if($voice_average_score >='5.8' and $voice_average_score <='6.7')
						{
							$final_grade = 'B+';
						}
						elseif($voice_average_score >='4.8' and $voice_average_score <='5.7')
						{
							$final_grade = 'B';
						}
						break;
					case $voice_average_score >= '3.8' and $voice_average_score <= '4.7':
						$average_score_grade = 'Average';
						$final_grade = 'B-';
						break;	
					case $voice_average_score >= '2.8' and $voice_average_score <= '3.7':
						$average_score_grade = 'Improvement Needed';
						if($voice_average_score >='2.8' and $voice_average_score <='3.7')
						{
							$final_grade = 'C+';
						}
						elseif($voice_average_score >='1.8' and $voice_average_score <='2.7')
						{
							$final_grade = 'C';
						}
						break;	
					case $voice_average_score >= '1' and $voice_average_score <= '1.7':
						$average_score_grade = 'Poor';
						$final_grade = 'D';
						break;	
					default:
						$average_score_grade ='';
						$final_grade ='';
				}

				$info['TOTAL SCORE'] = $final_grade .'  ('.$voice_average_score.')';
				$info['TOTAL VOICE SCORE'] = $average_score_grade;
				$out[] = $info;

				
			}
			
			$entries = $out;
			$translatedHeaders = array('No.', 'Appraisee Name', 'Department', 'Location', 'Immediate Supervisor',
									   'Date of Survey','TOTAL SCORE','TOTAL VOICE SCORE');
			$this->getReportXLS_XLSX($request, $translatedHeaders, $entries, $moduleName, $Exporttype);			
		}
		elseif($moduleName=='PerformanceAppraisal' && $Exporttype=='by_categories')
		{
			$entries_new = array();
			$out = [];
			$k = 1;
			
			foreach($entries as $key => $entry)
			{
				$db = PearDatabase::getInstance();
				$rs_performance_appraisal = $db->pquery("select vtiger_performanceappraisal.performanceappraisalid, vtiger_crmentity.smownerid
											 from vtiger_performanceappraisal
											 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid=vtiger_performanceappraisal.performanceappraisalid
											 where vtiger_performanceappraisal.name=? AND vtiger_crmentity.deleted=0 limit 1", array($entry['name']));
				$performanceappraisal_id = $db->query_result($rs_performance_appraisal, '0', 'performanceappraisalid');		
				$performance_appraisal_owner_id = $db->query_result($rs_performance_appraisal, '0', 'smownerid');							 
				
				$performanceAppraisal_info_detail = Vtiger_Record_Model::getInstanceById($performanceappraisal_id, 'PerformanceAppraisal');
				
				$immediate_supervisor_user_info = Users_Record_Model::getInstanceById($performance_appraisal_owner_id, 'Users');
				
				$result_answer_s = $db->pquery("SELECT sum(answer_value) as subtotal FROM `appraisal_questions_answer` WHERE appraisal_id=? ",array($performanceappraisal_id));
				$row_answer_s = $db->fetch_array($result_answer_s);
				$subtotal = $row_answer_s['subtotal'];
				$voice_average_score = round($subtotal/12,1);

				$info = [];
				$info_grade = [];
				$j = $k;	
				$info['No.'] = $k++;
				$info['Appraisee Name'] = $entry['cf_6560'];
				$info['Department'] = $entry['cf_6564'];
				$info['Location'] = $entry['cf_6562'];
				$info['Location'] = $entry['cf_6562'];
				$info['Immediate Supervisor'] = $immediate_supervisor_user_info->get('first_name').' '.$immediate_supervisor_user_info->get('last_name');
				$info['Date of Survey'] = date('m/d/Y',strtotime($entry['createdtime']));
				$info['Type'] = 'Remarks';

				//For Grade
				$info_grade['No.'] = $j;
				$info_grade['Appraisee Name'] = '';
				$info_grade['Department'] ='';
				$info_grade['Location'] = '';
				$info_grade['Location'] = '';
				$info_grade['Immediate Supervisor'] = '';
				$info_grade['Date of Survey'] = '';
				$info_grade['Type'] = 'Grade';


				$result_cat = $db->pquery("SELECT * FROM `vtiger_appraisalcategoriescf` ",array());
				for($qq=0; $qq< $db->num_rows($result_cat); $qq++ ) {
					$row_sub_cat = $db->fetch_row($result_cat,$qq);
					$appraisalcategoriesid = $row_sub_cat['appraisalcategoriesid'];
					$sub_cat_eng = $row_sub_cat['cf_6520'];	
					$result_answer = $db->pquery("SELECT * FROM `appraisal_questions_answer` WHERE appraisal_id=? AND question_category_id=? ",array($performanceappraisal_id, $appraisalcategoriesid));
					$row_answer = $db->fetch_array($result_answer);
					$question_answer_value = $row_answer['answer_value'];
					switch($question_answer_value)
					{
						case '8':
						$question_answer_value = 'Excellent';
						$grade_value = 'A';
						break;
						case '7':
						$question_answer_value = 'Outstanding';
						$grade_value = 'A-';
						break;
						case '6':
						$grade_value = 'B+';
						case '5':
						$question_answer_value = 'Good';
						$grade_value = 'B';
						break;
						case '4':
						$question_answer_value = 'Average';
						$grade_value = 'B-';
						break;
						case '3':
						$grade_value = 'C+';
						case '2':
						$question_answer_value = 'Improvement Needed';
						$grade_value = 'C';
						break;
						case '1':
						$question_answer_value = 'Poor';
						$grade_value = 'D';
						break;
						default:
						$question_answer_value = '';
						$grade_value = '';
					}
					$info[$qq] = $question_answer_value;
					$info_grade[$qq] = $grade_value;
				}				

				$info['VOICE AVERAGE SCORE'] = $voice_average_score;

				//For final Grading based on voice average score
				switch($voice_average_score)
				{
					case $voice_average_score >= '7.8' and $voice_average_score <= '8':
						$average_score_grade = 'Excellent';
						$final_grade = 'A';
					break;
					case $voice_average_score >= '6.8' and $voice_average_score <= '7.7':
						$average_score_grade = 'Outstanding';
						$final_grade = 'A-';
						break;
					case $voice_average_score >= '4.8' and $voice_average_score <= '6.7':
						$average_score_grade = 'Good';
						if($voice_average_score >='5.8' and $voice_average_score <='6.7')
						{
							$final_grade = 'B+';
						}
						elseif($voice_average_score >='4.8' and $voice_average_score <='5.7')
						{
							$final_grade = 'B';
						}
						break;
					case $voice_average_score >= '3.8' and $voice_average_score <= '4.7':
						$average_score_grade = 'Average';
						$final_grade = 'B-';
						break;	
					case $voice_average_score >= '2.8' and $voice_average_score <= '3.7':
						$average_score_grade = 'Improvement Needed';
						if($voice_average_score >='2.8' and $voice_average_score <='3.7')
						{
							$final_grade = 'C+';
						}
						elseif($voice_average_score >='1.8' and $voice_average_score <='2.7')
						{
							$final_grade = 'C';
						}
						break;	
					case $voice_average_score >= '1' and $voice_average_score <= '1.7':
						$average_score_grade = 'Poor';
						$final_grade = 'D';
						break;	
					default:
						$average_score_grade ='';
						$final_grade ='';
				}

				$info['TOTAL VOICE SCORE'] = $average_score_grade;
				$info_grade['VOICE AVERAGE SCORE'] = '';
				$info_grade['TOTAL VOICE SCORE'] = $final_grade;
				$out[] = $info;
				$out[] = $info_grade;	

			}
		
			$entries = $out;
			$translatedHeaders = array('No.', 'Appraisee Name', 'Department', 'Location', 'Immediate Supervisor',
									   'Date of Survey', 'Type');
			
			$result_cat = $db->pquery("SELECT * FROM `vtiger_appraisalcategoriescf` ",array());
			for($qq=0; $qq< $db->num_rows($result_cat); $qq++ ) {
				$row_sub_cat = $db->fetch_row($result_cat,$qq);
				$translatedHeaders[] = $row_sub_cat['cf_6520'];
			}
			$translatedHeaders[] = 'VOICE AVERAGE SCORE';
			$translatedHeaders[] = 'TOTAL VOICE SCORE';
			
			$this->getReportXLS_XLSX($request, $translatedHeaders, $entries, $moduleName, $Exporttype);
				
		}
		elseif($moduleName=='PerformanceAppraisal' && $Exporttype=='by_potential_assessment')
		{
			$entries_new = array();
			$out = [];
			$k = 1;
			
			foreach($entries as $key => $entry)
			{
				$db = PearDatabase::getInstance();
				$rs_performance_appraisal = $db->pquery("select vtiger_performanceappraisal.performanceappraisalid, vtiger_crmentity.smownerid
											 from vtiger_performanceappraisal
											 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid=vtiger_performanceappraisal.performanceappraisalid
											 where vtiger_performanceappraisal.name=? AND vtiger_crmentity.deleted=0 limit 1", array($entry['name']));
				$performanceappraisal_id = $db->query_result($rs_performance_appraisal, '0', 'performanceappraisalid');		
				$performance_appraisal_owner_id = $db->query_result($rs_performance_appraisal, '0', 'smownerid');							 
				
				$performanceAppraisal_info_detail = Vtiger_Record_Model::getInstanceById($performanceappraisal_id, 'PerformanceAppraisal');
				
				$immediate_supervisor_user_info = Users_Record_Model::getInstanceById($performance_appraisal_owner_id, 'Users');
				
				$result_answer_s = $db->pquery("SELECT sum(answer_value) as subtotal FROM `appraisal_questions_answer` WHERE appraisal_id=? ",array($performanceappraisal_id));
				$row_answer_s = $db->fetch_array($result_answer_s);
				$subtotal = $row_answer_s['subtotal'];
				$voice_average_score = round($subtotal/12,1);

				$info = [];
				$info['No.'] = $k++;
				$info['Appraisee Name'] = $entry['cf_6560'];
				$info['Department'] = $entry['cf_6564'];
				$info['Location'] = $entry['cf_6562'];
				$info['Location'] = $entry['cf_6562'];
				$info['Immediate Supervisor'] = $immediate_supervisor_user_info->get('first_name').' '.$immediate_supervisor_user_info->get('last_name');
				$info['Date of Survey'] = date('m/d/Y',strtotime($entry['createdtime']));

				switch($voice_average_score)
				{
					case $voice_average_score >= '7.8' and $voice_average_score <= '8':
						$average_score_grade = 'Excellent';
						$final_grade = 'A';
					break;
					case $voice_average_score >= '6.8' and $voice_average_score <= '7.7':
						$average_score_grade = 'Outstanding';
						$final_grade = 'A-';
						break;
					case $voice_average_score >= '4.8' and $voice_average_score <= '6.7':
						$average_score_grade = 'Good';
						if($voice_average_score >='5.8' and $voice_average_score <='6.7')
						{
							$final_grade = 'B+';
						}
						elseif($voice_average_score >='4.8' and $voice_average_score <='5.7')
						{
							$final_grade = 'B';
						}
						break;
					case $voice_average_score >= '3.8' and $voice_average_score <= '4.7':
						$average_score_grade = 'Average';
						$final_grade = 'B-';
						break;	
					case $voice_average_score >= '2.8' and $voice_average_score <= '3.7':
						$average_score_grade = 'Improvement Needed';
						if($voice_average_score >='2.8' and $voice_average_score <='3.7')
						{
							$final_grade = 'C+';
						}
						elseif($voice_average_score >='1.8' and $voice_average_score <='2.7')
						{
							$final_grade = 'C';
						}
						break;	
					case $voice_average_score >= '1' and $voice_average_score <= '1.7':
						$average_score_grade = 'Poor';
						$final_grade = 'D';
						break;	
					default:
						$average_score_grade ='';
						$final_grade ='';
				}

				$info['TOTAL SCORE'] = $final_grade .'  ('.$voice_average_score.')';
				$info['TOTAL VOICE SCORE'] = $average_score_grade;
				$info['question_1'] = $entry['cf_6612'];
				$info['question_2'] = $entry['cf_6614'];
				$out[] = $info;
				
			}
			
			$entries = $out;
			$translatedHeaders = array('No.', 'Appraisee Name', 'Department', 'Location', 'Immediate Supervisor',
									   'Date of Survey','TOTAL SCORE', 'TOTAL VOICE SCORE','List and discuss the strengths of the appraisee.', 'List and discuss the areas that the appraisee needs to improve. Is any training required?');
			$this->getReportXLS_XLSX($request, $translatedHeaders, $entries, $moduleName, $Exporttype);	
		}
		else{
		$this->output($request, $translatedHeaders, $entries);
		}
	}

	function writeReportToExcelFile_PerformanceAppraisal($fileName, $headers, $entries, $filterlist='') {
		global $currentModule, $current_language;
		$mod_strings = return_module_language($current_language, $currentModule);

		require_once 'libraries/PHPExcel/PHPExcel.php';
		//echo date('H:i:s') . " Create new PHPExcel object\n";
		$objPHPExcel = new PHPExcel();
		// Set properties
		//echo date('H:i:s') . " Set properties\n";
		$current_user = Users_Record_Model::getCurrentUserModel();

		$full_name = $current_user->get('first_name')." ".$current_user->get('last_name');
		$objPHPExcel->getProperties()->setCreator($full_name)
									 ->setLastModifiedBy($full_name)
									 ->setTitle($fileName)
									 ->setSubject($fileName)
									 ->setDescription($fileName)
									 ->setKeywords($fileName)
									 ->setCategory($fileName);
		//echo date('H:i:s') . " Add data\n";
		$objPHPExcel->setActiveSheetIndex(0);

		$sharedStyle1 = new PHPExcel_Style();
		$sharedStyle2 = new PHPExcel_Style();

		$sharedStyle1->applyFromArray(
			array('fill' 	=> array(
										'type'		=> PHPExcel_Style_Fill::FILL_SOLID,
										'color'		=> array('argb' => 'FFCCFFCC')
									),
				  'borders' => array( 'allborders' =>array(
										  'style' => PHPExcel_Style_Border::BORDER_THIN
										//'bottom'	=> array('style' => PHPExcel_Style_Border::BORDER_THIN),
										//'right'		=> array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
									)),
					'alignment' => array(
							            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
        							)
				 ));
		$sharedStyle2->applyFromArray(
			array('fill' 	=> array(
										'type'		=> PHPExcel_Style_Fill::FILL_SOLID,
										'color'		=> array('argb' => 'ed7024')
									),
				  'borders' => array( 'allborders' =>array(
										  'style' => PHPExcel_Style_Border::BORDER_THIN
										//'bottom'	=> array('style' => PHPExcel_Style_Border::BORDER_THIN),
										//'right'		=> array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
									)),
					'alignment' => array(
							            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
        							)
				 ));

		//$objPHPExcel->getActiveSheet()->setSharedStyle($sharedStyle1, "A5:Q5");
		$objPHPExcel->getActiveSheet()->setSharedStyle($sharedStyle2, "A3:H3");		
		
		$objPHPExcel->getActiveSheet()->mergeCells('B1:F1');
		$objPHPExcel->getActiveSheet()->setCellValue('B1', "Performance Appraisal Report");
		
  		$objPHPExcel->getActiveSheet()->fromArray($headers, null, 'A3');		

		$objPHPExcel->getActiveSheet()->getRowDimension('3')->setRowHeight(70);	
		$objPHPExcel->getActiveSheet()->getStyle('A3:H3')->getAlignment()->setWrapText(true);
		
		$objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);

		$entries_new = $entries;
		// Add data
		//$entries = array_map("html_entity_decode",$entries);
		$objPHPExcel->getActiveSheet()->fromArray($entries_new, null, 'A4');
		//echo date('H:i:s') . " Set autofilter\n";
		$objPHPExcel->getActiveSheet()->setAutoFilter('A3:H3');
		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex(0);

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		//$objWriter->setUseBOM(true);
		ob_end_clean();
		$objWriter->save($fileName);
	}

	function writeReportToExcelFile_PerformanceAppraisal_bycategories($fileName, $headers, $entries, $filterlist='') {
		global $currentModule, $current_language;
		$mod_strings = return_module_language($current_language, $currentModule);

		require_once 'libraries/PHPExcel/PHPExcel.php';
		//echo date('H:i:s') . " Create new PHPExcel object\n";
		$objPHPExcel = new PHPExcel();
		// Set properties
		//echo date('H:i:s') . " Set properties\n";
		$current_user = Users_Record_Model::getCurrentUserModel();

		$full_name = $current_user->get('first_name')." ".$current_user->get('last_name');
		$objPHPExcel->getProperties()->setCreator($full_name)
									 ->setLastModifiedBy($full_name)
									 ->setTitle($fileName)
									 ->setSubject($fileName)
									 ->setDescription($fileName)
									 ->setKeywords($fileName)
									 ->setCategory($fileName);
		//echo date('H:i:s') . " Add data\n";
		$objPHPExcel->setActiveSheetIndex(0);

		$sharedStyle1 = new PHPExcel_Style();
		$sharedStyle2 = new PHPExcel_Style();

		$sharedStyle1->applyFromArray(
			array('fill' 	=> array(
										'type'		=> PHPExcel_Style_Fill::FILL_SOLID,
										'color'		=> array('argb' => 'FFCCFFCC')
									),
				  'borders' => array( 'allborders' =>array(
										  'style' => PHPExcel_Style_Border::BORDER_THIN
										//'bottom'	=> array('style' => PHPExcel_Style_Border::BORDER_THIN),
										//'right'		=> array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
									)),
					'alignment' => array(
							            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
        							)
				 ));
		$sharedStyle2->applyFromArray(
			array('fill' 	=> array(
										'type'		=> PHPExcel_Style_Fill::FILL_SOLID,
										'color'		=> array('argb' => 'ed7024')
									),
				  'borders' => array( 'allborders' =>array(
										  'style' => PHPExcel_Style_Border::BORDER_THIN
										//'bottom'	=> array('style' => PHPExcel_Style_Border::BORDER_THIN),
										//'right'		=> array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
									)),
					'alignment' => array(
							            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
        							)
				 ));

		//$objPHPExcel->getActiveSheet()->setSharedStyle($sharedStyle1, "A5:Q5");
		$objPHPExcel->getActiveSheet()->setSharedStyle($sharedStyle2, "A3:U3");		
		
		$objPHPExcel->getActiveSheet()->mergeCells('B1:R1');
		$objPHPExcel->getActiveSheet()->setCellValue('B1', "PERFORMANCE APPRAISAL REPORT");

		$objPHPExcel->getActiveSheet()->mergeCells('H2:U2');
		$objPHPExcel->getActiveSheet()->setCellValue('H2',"RATE FOR EACH CATEGORY");
		
  		$objPHPExcel->getActiveSheet()->fromArray($headers, null, 'A3');		

		$objPHPExcel->getActiveSheet()->getRowDimension('3')->setRowHeight(70);	
		$objPHPExcel->getActiveSheet()->getStyle('A3:U3')->getAlignment()->setWrapText(true);
		
		$objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);

		$entries_new = $entries;
		// Add data
		//$entries = array_map("html_entity_decode",$entries);
		$objPHPExcel->getActiveSheet()->fromArray($entries_new, null, 'A4');
		//echo date('H:i:s') . " Set autofilter\n";
		//$objPHPExcel->getActiveSheet()->setAutoFilter('A3:S3');
		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex(0);

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		//$objWriter->setUseBOM(true);
		ob_end_clean();
		$objWriter->save($fileName);
	}

	function writeReportToExcelFile_PerformanceAppraisal_bypotentialassessment($fileName, $headers, $entries, $filterlist='') {
		global $currentModule, $current_language;
		$mod_strings = return_module_language($current_language, $currentModule);

		require_once 'libraries/PHPExcel/PHPExcel.php';
		//echo date('H:i:s') . " Create new PHPExcel object\n";
		$objPHPExcel = new PHPExcel();
		// Set properties
		//echo date('H:i:s') . " Set properties\n";
		$current_user = Users_Record_Model::getCurrentUserModel();

		$full_name = $current_user->get('first_name')." ".$current_user->get('last_name');
		$objPHPExcel->getProperties()->setCreator($full_name)
									 ->setLastModifiedBy($full_name)
									 ->setTitle($fileName)
									 ->setSubject($fileName)
									 ->setDescription($fileName)
									 ->setKeywords($fileName)
									 ->setCategory($fileName);
		//echo date('H:i:s') . " Add data\n";
		$objPHPExcel->setActiveSheetIndex(0);

		$sharedStyle1 = new PHPExcel_Style();
		$sharedStyle2 = new PHPExcel_Style();

		$sharedStyle1->applyFromArray(
			array('fill' 	=> array(
										'type'		=> PHPExcel_Style_Fill::FILL_SOLID,
										'color'		=> array('argb' => 'FFCCFFCC')
									),
				  'borders' => array( 'allborders' =>array(
										  'style' => PHPExcel_Style_Border::BORDER_THIN
										//'bottom'	=> array('style' => PHPExcel_Style_Border::BORDER_THIN),
										//'right'		=> array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
									)),
					'alignment' => array(
							            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
        							)
				 ));
		$sharedStyle2->applyFromArray(
			array('fill' 	=> array(
										'type'		=> PHPExcel_Style_Fill::FILL_SOLID,
										'color'		=> array('argb' => 'ed7024')
									),
				  'borders' => array( 'allborders' =>array(
										  'style' => PHPExcel_Style_Border::BORDER_THIN
										//'bottom'	=> array('style' => PHPExcel_Style_Border::BORDER_THIN),
										//'right'		=> array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
									)),
					'alignment' => array(
							            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
        							)
				 ));

		//$objPHPExcel->getActiveSheet()->setSharedStyle($sharedStyle1, "A5:Q5");
		$objPHPExcel->getActiveSheet()->setSharedStyle($sharedStyle2, "A3:J3");		
		
		$objPHPExcel->getActiveSheet()->mergeCells('B1:J1');
		$objPHPExcel->getActiveSheet()->setCellValue('B1', "Performance Appraisal Report By Potential Assessment");
		
  		$objPHPExcel->getActiveSheet()->fromArray($headers, null, 'A3');		

		$objPHPExcel->getActiveSheet()->getRowDimension('3')->setRowHeight(70);	
		$objPHPExcel->getActiveSheet()->getStyle('A3:J3')->getAlignment()->setWrapText(true);
		
		$objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);

		$entries_new = $entries;
		// Add data
		//$entries = array_map("html_entity_decode",$entries);
		$objPHPExcel->getActiveSheet()->fromArray($entries_new, null, 'A4');
		//echo date('H:i:s') . " Set autofilter\n";
		$objPHPExcel->getActiveSheet()->setAutoFilter('A3:J3');
		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex(0);

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		//$objWriter->setUseBOM(true);
		ob_end_clean();
		$objWriter->save($fileName);
	}

	function getReportXLS_XLSX($request, $headers, $entries, $moduleName, $format = 'by_final_score') {

		$rootDirectory = vglobal('root_directory');
		$tmpDir = vglobal('tmp_dir');

		$tempFileName = tempnam($rootDirectory.$tmpDir, 'xlsx');

		$moduleName = $request->get('source_module');

		//$fileName = $this->getName().'.xls';
		$fileName = $moduleName.'.xlsx';
		$Exporttype = $request->get('Exporttype');

		
		if($moduleName=='PerformanceAppraisal' && $Exporttype=='by_final_score')
		{
			$this->writeReportToExcelFile_PerformanceAppraisal($tempFileName, $headers, $entries, false);
		}
		elseif($moduleName=='PerformanceAppraisal' && $Exporttype=='by_categories')	
		{
			$this->writeReportToExcelFile_PerformanceAppraisal_bycategories($tempFileName, $headers, $entries, false);
		}
		elseif($moduleName=='PerformanceAppraisal' && $Exporttype=='by_potential_assessment')	
		{
			$this->writeReportToExcelFile_PerformanceAppraisal_bypotentialassessment($tempFileName, $headers, $entries, false);
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



	/**
	 * Function that generates Export Query based on the mode
	 * @param Vtiger_Request $request
	 * @return <String> export query
	 */
	function getExportQuery(Vtiger_Request $request) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$mode = $request->getMode();
		$cvId = $request->get('viewname');

		$moduleName = $request->get('source_module');

		$queryGenerator = new QueryGenerator($moduleName, $currentUser);
		$queryGenerator->initForCustomViewById($cvId);
		$fieldInstances = $this->moduleFieldInstances;

        $accessiblePresenceValue = array(0,2);
		foreach($fieldInstances as $field) {
            // Check added as querygenerator is not checking this for admin users
            $presence = $field->get('presence');
            if(in_array($presence, $accessiblePresenceValue)) {
                $fields[] = $field->getName();
            }
        }
		//$fields[] = 'crmid';

		$queryGenerator->setFields($fields);

		$query = $queryGenerator->getQuery();
		if(in_array($moduleName, getInventoryModules())){
			$query = $this->moduleInstance->getExportQuery($this->focus, $query);
		}

		$this->accessibleFields = $queryGenerator->getFields();
		
		//$query = str_replace('SELECT','SELECT vtiger_performanceappraisal.performanceappraisalid, ',$query);
		
		switch($mode) {
			case 'ExportAllData' :	return $query;
									break;

			case 'ExportCurrentPage' :	$pagingModel = new Vtiger_Paging_Model();
										$limit = $pagingModel->getPageLimit();

										$currentPage = $request->get('page');
										if(empty($currentPage)) $currentPage = 1;

										$currentPageStart = ($currentPage - 1) * $limit;
										if ($currentPageStart < 0) $currentPageStart = 0;
										$query .= ' LIMIT '.$currentPageStart.','.$limit;

										return $query;
										break;

			case 'ExportSelectedRecords' :	$idList = $this->getRecordsListFromRequest($request);
											$baseTable = $this->moduleInstance->get('basetable');
											$baseTableColumnId = $this->moduleInstance->get('basetableid');
											if(!empty($idList)) {
												if(!empty($baseTable) && !empty($baseTableColumnId)) {
													$idList = implode(',' , $idList);
													$query .= ' AND '.$baseTable.'.'.$baseTableColumnId.' IN ('.$idList.')';
												}
											} else {
												$query .= ' AND '.$baseTable.'.'.$baseTableColumnId.' NOT IN ('.implode(',',$request->get('excluded_ids')).')';
											}
											return $query;
											break;


			default :	return $query;
						break;
		}
	}

	/**
	 * Function returns the export type - This can be extended to support different file exports
	 * @param Vtiger_Request $request
	 * @return <String>
	 */
	function getExportContentType(Vtiger_Request $request) {
		$type = $request->get('export_type');
		if(empty($type)) {
			return 'text/csv';
		}
	}

	/**
	 * Function that create the exported file
	 * @param Vtiger_Request $request
	 * @param <Array> $headers - output file header
	 * @param <Array> $entries - outfput file data
	 */
	function output($request, $headers, $entries) {
		$moduleName = $request->get('source_module');
		$fileName = str_replace(' ','_',vtranslate($moduleName, $moduleName));
		$exportType = $this->getExportContentType($request);

		header("Content-Disposition:attachment;filename=$fileName.csv");
		header("Content-Type:$exportType;charset=UTF-8");
		header("Expires: Mon, 31 Dec 2000 00:00:00 GMT" );
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
		header("Cache-Control: post-check=0, pre-check=0", false );

		$header = implode("\"; \"", $headers);
		$header = "\"" .$header;
		$header .= "\"\r\n";
		echo $header;

		foreach($entries as $row) {
			$line = implode("\";\"",$row);
			$line = "\"" .$line;
			$line .= "\"\r\n";
			echo $line;
		}
	}

	private $picklistValues;
	private $fieldArray;
	private $fieldDataTypeCache = array();
	/**
	 * this function takes in an array of values for an user and sanitizes it for export
	 * @param array $arr - the array of values
	 */
	function sanitizeValues($arr){
		$db = PearDatabase::getInstance();
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$roleid = $currentUser->get('roleid');
		
		if(empty ($this->fieldArray)){
			$this->fieldArray = $this->moduleFieldInstances;
			foreach($this->fieldArray as $fieldName => $fieldObj){
				//In database we have same column name in two tables. - inventory modules only
				if($fieldObj->get('table') == 'vtiger_inventoryproductrel' && ($fieldName == 'discount_amount' || $fieldName == 'discount_percent')){
					$fieldName = 'item_'.$fieldName;
					$this->fieldArray[$fieldName] = $fieldObj;
				} else {
					$columnName = $fieldObj->get('column');
					$this->fieldArray[$columnName] = $fieldObj;
				}
			}
		}
		$moduleName = $this->moduleInstance->getName();

				
		foreach($arr as $fieldName=>&$value){
			if(isset($this->fieldArray[$fieldName])){
				$fieldInfo = $this->fieldArray[$fieldName];
			}else {
				unset($arr[$fieldName]);
				continue;
			}
			$value = decode_html($value);
			$uitype = $fieldInfo->get('uitype');
			$fieldname = $fieldInfo->get('name');

			if(!$this->fieldDataTypeCache[$fieldName]) {
				$this->fieldDataTypeCache[$fieldName] = $fieldInfo->getFieldDataType();
			}
			$type = $this->fieldDataTypeCache[$fieldName];

			if($fieldname != 'hdnTaxType' && ($uitype == 15 || $uitype == 16 || $uitype == 33)){
				if(empty($this->picklistValues[$fieldname])){
					$this->picklistValues[$fieldname] = $this->fieldArray[$fieldname]->getPicklistValues();
				}
				// If the value being exported is accessible to current user
				// or the picklist is multiselect type.
				if($uitype == 33 || $uitype == 16 || in_array($value,$this->picklistValues[$fieldname])){
					// NOTE: multipicklist (uitype=33) values will be concatenated with |# delim
					$value = trim($value);
				} else {
					$value = '';
				}
			} elseif($uitype == 52 || $type == 'owner') {
				$value = Vtiger_Util_Helper::getOwnerName($value);
			}elseif($type == 'reference'){
				$value = trim($value);
				if(!empty($value)) {
					$parent_module = getSalesEntityType($value);
					$displayValueArray = getEntityName($parent_module, $value);
					if(!empty($displayValueArray)){
						foreach($displayValueArray as $k=>$v){
							$displayValue = $v;
						}
					}
					if(!empty($parent_module) && !empty($displayValue)){
						//$value = $parent_module."::::".$displayValue;
						$value = $displayValue;
					}else{
						$value = "";
					}
				} else {
					$value = '';
				}
			} elseif($uitype == 72 || $uitype == 71) {
                $value = CurrencyField::convertToUserFormat($value, null, true, true);
			} elseif($uitype == 7 && $fieldInfo->get('typeofdata') == 'N~O' || $uitype == 9){
				$value = decimalFormat($value);
			}
			elseif($uitype ==999)
			{
				$value = Vtiger_CompanyList_UIType::getDisplayValue($value);
			}
			elseif($uitype==898)
			{
				$value = Vtiger_LocationList_UIType::getDisplayValue($value);
			}
			elseif($uitype==899)
			{
				$value = Vtiger_DepartmentList_UIType::getDisplayValue($value);
			}
			elseif($uitype==117)
			{
				$value = Vtiger_CurrencyList_UIType::getDisplayValue($value);
			}
			elseif($uitype==55)
			{
				if($fieldname!='cf_1084' && $fieldname!='cf_1086')
				{
					$value = Vtiger_CurrencyList_UIType::getDisplayValue($value);
				}
				else{
					$value = $value;
				}
			}
			elseif($uitype==699)
			{
				$value = Vtiger_InsuranceRateList_UIType::getDisplayValue($value);
			}
			elseif($uitype==768)
			{
				$value = Vtiger_TruckList_UIType::getDisplayValue($value);
			}
			elseif($uitype==599)
			{
				$value = Vtiger_DriverList_UIType::getDisplayValue($value);
			}
			elseif($uitype==698)
			{
				$value = Vtiger_CommodityTypeList_UIType::getDisplayValue($value);
			}
			elseif($uitype==697)
			{
				$value = Vtiger_SpecialRangeList_UIType::getDisplayValue($value);
			}
			elseif($uitype==597)
			{
				$value = Vtiger_GLKUserList_UIType::getDisplayValue($value);
			}
			elseif($uitype==994)
			{
				$value = Vtiger_PackerList_UIType::getDisplayValue($value);
			}
			elseif($uitype==995)
			{
				$value =Vtiger_CompanyAccountTypeList_UIType::getDisplayValue($value);
			}
			elseif($uitype==601)
			{
				$value =Vtiger_UsersList_UIType::getDisplayValue($value);
			}
			elseif($uitype=='11010')
			{
				$value =Vtiger_WarehouseList_UIType::getDisplayValue($value);
			}
			elseif($uitype==11011)
			{
				$value =Vtiger_WHItemMasterList_UIType::getDisplayValue($value);
			}
			elseif($uitype==695)
			{
				$value =Vtiger_InsuranceTypeList_UIType::getDisplayValue($value);
			}
			


			if($moduleName == 'Documents' && $fieldname == 'description'){
				$value = strip_tags($value);
				$value = str_replace('&nbsp;','',$value);
				array_push($new_arr,$value);
			}
		}
		return $arr;
	}
}