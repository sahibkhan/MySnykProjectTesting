<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Overtime_ExportData_Action extends Vtiger_ExportData_Action {

	var $moduleCall = false;
	public function requiresPermission(\Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'Export');
        if (!empty($request->get('source_module'))) {
            $permissions[] = array('module_parameter' => 'source_module', 'action' => 'Export');
        }
		return $permissions;
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
		$this->moduleFieldInstances = $this->moduleFieldInstances($moduleName);
		$this->focus = CRMEntity::getInstance($moduleName);

		$query = $this->getExportQuery($request);
		$result = $db->pquery($query, array());

		$redirectedModules = array('Users', 'Calendar');
		if($request->getModule() != $moduleName && in_array($moduleName, $redirectedModules) && !$this->moduleCall){
			$handlerClass = Vtiger_Loader::getComponentClassName('Action', 'ExportData', $moduleName);
			$handler = new $handlerClass();
			$handler->ExportData($request);
			return;
		}
		$translatedHeaders = $this->getHeaders();
		$entries = array();
		for ($j = 0; $j < $db->num_rows($result); $j++) {
			$entries[] = $this->sanitizeValues($db->fetchByAssoc($result, $j));
		}

		if($moduleName =='Overtime')
		{
			$this->getReportXLS_XLSX($request, $translatedHeaders, $entries, $moduleName);
		}
		else{
			$this->output($request, $translatedHeaders, $entries);
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

		

		if($moduleName =='Overtime')
		{
			$this->writeReportToExcelFile_Overtime($tempFileName, $headers, $entries, false);
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

	function writeReportToExcelFile_Overtime($fileName, $headers, $entries, $filterlist='') {
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
										'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
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
										'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
        							)
				 ));

		$objPHPExcel->getActiveSheet()->setSharedStyle($sharedStyle1, "B1:V1");
		$objPHPExcel->getActiveSheet()->setSharedStyle($sharedStyle2, "B7:V7");


		$objPHPExcel->getActiveSheet()->mergeCells('C1:L1');
		$objPHPExcel->getActiveSheet()->setCellValue('C1', "RRSD  OPERATIONS AND OVERTIME SHEET");


		$overtime_headers = array('#', 'Activity For', 'Date', 'Supervisor', 'Packer / Driver', 'Start Time', 'Finish Time', 'Service Description',
								'Volume Executed (cbm)', 'Lunch Time', 'Taxi', 'Overtime Final Rate'
								);
  		//$objPHPExcel->getActiveSheet()->fromArray($wis_headers, null, 'A5');
		$objPHPExcel->getActiveSheet()->fromArray($overtime_headers, null, 'B7');
		//$objPHPExcel->getActiveSheet()->mergeCells('G2:J2');
		$objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(25);

		// Freeze panes
		//$objPHPExcel->getActiveSheet()->freezePane('F8');
		// Rows to repeat at top
		//echo date('H:i:s') . " Rows to repeat at top\n";
		//$objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);

		$entries_new = array();
		$activityfor_overtime = array();
		$individual_packer_overtime  = array();
		foreach($entries as $key => $entry)
		{
			$key++;
			$entries_new[] = array('key' => $key, 'activity_for' => $entry['cf_5413'], 'date' => $entry['cf_5291'],
								   'supervisor' => $entry['smownerid'], 'packer_driver' => $entry['cf_5397'],
								   'start_time' => $entry['cf_5405'], 'end_time' => $entry['cf_5407'], 'service_description' => $entry['cf_5299'],
								   'volume_executed_cbm' => $entry['cf_5301'], 'lunch_time' => ($entry['cf_5395']=='1'?'Yes':'No'), 'taxi' => ($entry['cf_5768']==1?'Yes':'No'),
								   'overtime_final_rate' => $entry['cf_5307']
								   );

			$activityfor_overtime[$entry['cf_5413']] += $entry['cf_5307'];
			$individual_packer_overtime[$entry['cf_5397']] += $entry['cf_5307'];
		}

		$job_wise_array = array();
		foreach($activityfor_overtime as $key => $activityfor)
		{
			$job_ref_no  = $key;
			$key_job++;
			if($job_ref_no!='Admin' && isset($job_ref_no))
			{
			$db_first = PearDatabase::getInstance();
			$query_first_jobid = "SELECT jobid from vtiger_jobcf
								  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobcf.jobid
								  where vtiger_crmentity.deleted=0 AND vtiger_jobcf.cf_1198=?";

			$params_jobid = array($job_ref_no);
			$result_job = $db_first->pquery($query_first_jobid, $params_jobid);
			$row_job_id_info = $db_first->fetch_array($result_job);
			$job_id = $row_job_id_info['jobid'];
			$job_info = Vtiger_Record_Model::getInstanceById($job_id, 'Job');
			$department = $job_info->getDisplayValue('cf_1190');
			$job_owner = $job_info->getDisplayValue('assigned_user_id');
			$job_owner_name= strip_tags($job_owner);
				$job_wise_array[] = array('key' => $key_job, 'job_ref_no' => $job_ref_no, 'total_overtime' => @$activityfor, 'department' => $department, 'job_owner' => $job_owner_name);
			}
			else{
			$job_wise_array[] = array('key' => $key_job, 'job_ref_no' => $job_ref_no, 'total_overtime' => $activityfor, 'department' => 'Admin', 'job_owner' => 'Admin');
			}
		}

		$packer_overtime = array();
		foreach($individual_packer_overtime as $key_p => $value_packer)
		{
			$key_packer++;
			$packer_overtime[] = array('key' => $key_packer, 'supervisor_driver_packer' => $key_p, 'total_to_pay' => $value_packer);
		}

		// Add data
		//$entries = array_map("html_entity_decode",$entries);
		$objPHPExcel->getActiveSheet()->fromArray($entries_new, null, 'B8');
		//echo date('H:i:s') . " Set autofilter\n";
		//Autofilter off
		//$objPHPExcel->getActiveSheet()->setAutoFilter('A2:Z2');
		$overtimejob_headers = array('#', 'Job Number', 'Total to write off', 'Department', 'Coordinator');
		$objPHPExcel->getActiveSheet()->fromArray($overtimejob_headers, null, 'B'.(count($entries)+10).'');
		$objPHPExcel->getActiveSheet()->fromArray($job_wise_array, null, 'B'.(count($entries)+11).'');

		$total_row =  count($entries)+ count($job_wise_array) + 12;
		$individual_packer_headers = array('#', 'Supervisor/Driver/Packer', 'TOTAL TO PAY');
		$objPHPExcel->getActiveSheet()->fromArray($individual_packer_headers, null, 'B'.$total_row.'');
		$objPHPExcel->getActiveSheet()->fromArray($packer_overtime, null, 'B'.($total_row+1).'');


		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex(0);


		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		//$objWriter->setUseBOM(true);
		ob_end_clean();
		$objWriter->save($fileName);


	}

	public function getHeaders() {
		$headers = array();
		//Query generator set this when generating the query
		if(!empty($this->accessibleFields)) {
			$accessiblePresenceValue = array(0,2);
			foreach($this->accessibleFields as $fieldName) {
				$fieldModel = $this->moduleFieldInstances[$fieldName];
				// Check added as querygenerator is not checking this for admin users
				$presence = $fieldModel->get('presence');
				if(in_array($presence, $accessiblePresenceValue) && $fieldModel->get('displaytype') != '6') {
					$headers[] = $fieldModel->get('label');
				}
			}
		} else {
			foreach($this->moduleFieldInstances as $field) {
				$headers[] = $field->get('label');
			}
		}

		$translatedHeaders = array();
		foreach($headers as $header) {
			$translatedHeaders[] = vtranslate(html_entity_decode($header, ENT_QUOTES), $this->moduleInstance->getName());
		}

		$translatedHeaders = array_map('decode_html', $translatedHeaders);
		return $translatedHeaders;
	}

	function getAdditionalQueryModules(){
		return array_merge(getInventoryModules(), array('Products', 'Services', 'PriceBooks'));
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

		$queryGenerator = new EnhancedQueryGenerator($moduleName, $currentUser);
		$queryGenerator->initForCustomViewById($cvId);
		$fieldInstances = $this->moduleFieldInstances;

		$orderBy = $request->get('orderby');
		$orderByFieldModel = $fieldInstances[$orderBy];
		$sortOrder = $request->get('sortorder');

		if ($mode !== 'ExportAllData') {
			$operator = $request->get('operator');
			$searchKey = $request->get('search_key');
			$searchValue = $request->get('search_value');

			$tagParams = $request->get('tag_params');
			if (!$tagParams) {
				$tagParams = array();
			}

			$searchParams = $request->get('search_params');
			if (!$searchParams) {
				$searchParams = array();
			}

			$glue = '';
			if($searchParams && count($queryGenerator->getWhereFields())) {
				$glue = QueryGenerator::$AND;
			}
			$searchParams = array_merge($searchParams, $tagParams);
			$searchParams = Vtiger_Util_Helper::transferListSearchParamsToFilterCondition($searchParams, $this->moduleInstance);
			$queryGenerator->parseAdvFilterList($searchParams, $glue);

			if($searchKey) {
				$queryGenerator->addUserSearchConditions(array('search_field' => $searchKey, 'search_text' => $searchValue, 'operator' => $operator));
			}

			if ($orderBy && $orderByFieldModel) {
				if ($orderByFieldModel->getFieldDataType() == Vtiger_Field_Model::REFERENCE_TYPE || $orderByFieldModel->getFieldDataType() == Vtiger_Field_Model::OWNER_TYPE) {
					$queryGenerator->addWhereField($orderBy);
				}
			}
		}

		/**
		 *  For Documents if we select any document folder and mass deleted it should delete documents related to that 
		 *  particular folder only
		 */
		if($moduleName == 'Documents'){
			$folderValue = $request->get('folder_value');
			if(!empty($folderValue)){
				 $queryGenerator->addCondition($request->get('folder_id'),$folderValue,'e');
			}
		}

		$accessiblePresenceValue = array(0,2);
		foreach($fieldInstances as $field) {
			// Check added as querygenerator is not checking this for admin users
			$presence = $field->get('presence');
			if(in_array($presence, $accessiblePresenceValue) && $field->get('displaytype') != '6') {
				$fields[] = $field->getName();
			}
		}
		$queryGenerator->setFields($fields);
		$query = $queryGenerator->getQuery();

		$additionalModules = $this->getAdditionalQueryModules();
		if(in_array($moduleName, $additionalModules)) {
			$query = $this->moduleInstance->getExportQuery($this->focus, $query);
		}

		$this->accessibleFields = $queryGenerator->getFields();

		switch($mode) {
			case 'ExportAllData'	:	if ($orderBy && $orderByFieldModel) {
											$query .= ' ORDER BY '.$queryGenerator->getOrderByColumn($orderBy).' '.$sortOrder;
										}
										break;

			case 'ExportCurrentPage' :	$pagingModel = new Vtiger_Paging_Model();
										$limit = $pagingModel->getPageLimit();

										$currentPage = $request->get('page');
										if(empty($currentPage)) $currentPage = 1;

										$currentPageStart = ($currentPage - 1) * $limit;
										if ($currentPageStart < 0) $currentPageStart = 0;

										if ($orderBy && $orderByFieldModel) {
											$query .= ' ORDER BY '.$queryGenerator->getOrderByColumn($orderBy).' '.$sortOrder;
										}
										$query .= ' LIMIT '.$currentPageStart.','.$limit;
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

											if ($orderBy && $orderByFieldModel) {
												$query .= ' ORDER BY '.$queryGenerator->getOrderByColumn($orderBy).' '.$sortOrder;
											}
											break;


			default :	break;
		}
		return $query;
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
		$fileName = str_replace(' ','_',decode_html(vtranslate($moduleName, $moduleName)));
		// for content disposition header comma should not be there in filename 
		$fileName = str_replace(',', '_', $fileName);
		$exportType = $this->getExportContentType($request);

		header("Content-Disposition:attachment;filename=$fileName.csv");
		header("Content-Type:$exportType;charset=UTF-8");
		header("Expires: Mon, 31 Dec 2000 00:00:00 GMT" );
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
		header("Cache-Control: post-check=0, pre-check=0", false );

		$header = implode("\", \"", $headers);
		$header = "\"" .$header;
		$header .= "\"\r\n";
		echo $header;

		foreach($entries as $row) {
			foreach ($row as $key => $value) {
				/* To support double quotations in CSV format
				 * To review: http://creativyst.com/Doc/Articles/CSV/CSV01.htm#EmbedBRs
				 */
				$row[$key] = str_replace('"', '""', $value);
			}
			$line = implode("\",\"",$row);
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
			//Track if the value had quotes at beginning
			$beginsWithDoubleQuote = strpos($value, '"') === 0;
			$endsWithDoubleQuote = substr($value,-1) === '"'?1:0;

			$value = trim($value,"\"");
			$uitype = $fieldInfo->get('uitype');
			$fieldname = $fieldInfo->get('name');

			if(!$this->fieldDataTypeCache[$fieldName]) {
				$this->fieldDataTypeCache[$fieldName] = $fieldInfo->getFieldDataType();
			}
			$type = $this->fieldDataTypeCache[$fieldName];

			//Restore double quote now.
			if ($beginsWithDoubleQuote) $value = "\"{$value}";
			if($endsWithDoubleQuote) $value = "{$value}\"";
			if($fieldname != 'hdnTaxType' && ($uitype == 15 || $uitype == 16 || $uitype == 33)){
				if(empty($this->picklistValues[$fieldname])){
					$this->picklistValues[$fieldname] = $this->fieldArray[$fieldname]->getPicklistValues();
				}
				// If the value being exported is accessible to current user
				// or the picklist is multiselect type.
				if($uitype == 33 || $uitype == 16 || array_key_exists($value,$this->picklistValues[$fieldname])){
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
						$value = $parent_module."::::".$displayValue;
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
			} elseif($type == 'date') {
				if ($value && $value != '0000-00-00') {
					$value = DateTimeField::convertToUserFormat($value);
				}
			} elseif($type == 'datetime') {
				if ($moduleName == 'Calendar' && in_array($fieldName, array('date_start', 'due_date'))) {
					$timeField = 'time_start';
					if ($fieldName === 'due_date') {
						$timeField = 'time_end';
					}
					$value = $value.' '.$arr[$timeField];
				}
				if (trim($value) && $value != '0000-00-00 00:00:00') {
					$value = Vtiger_Datetime_UIType::getDisplayDateTimeValue($value);
				}
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
				Vtiger_CompanyAccountTypeList_UIType::getDisplayValue($value);
			}
			elseif($uitype==601)
			{
				Vtiger_UsersList_UIType::getDisplayValue($value);
			}
			elseif($uitype=='11010')
			{
				Vtiger_WarehouseList_UIType::getDisplayValue($value);
			}
			elseif($uitype==11011)
			{
				Vtiger_WHItemMasterList_UIType::getDisplayValue($value);
			}
			elseif($uitype==695)
			{
				Vtiger_InsuranceTypeList_UIType::getDisplayValue($value);
			}
			elseif($uitype==766)
			{
				Vtiger_TruckTypeList_UIType::getDisplayValue($value);
			}

			
			if($moduleName == 'Documents' && $fieldname == 'description'){
				$value = strip_tags($value);
				$value = str_replace('&nbsp;','',$value);
				array_push($new_arr,$value);
			}
		}
		return $arr;
	}

	public function moduleFieldInstances($moduleName) {
		return $this->moduleInstance->getFields();
	}
}