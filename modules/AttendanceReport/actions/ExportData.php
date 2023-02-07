<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class AttendanceReport_ExportData_Action extends Vtiger_ExportData_Action {

	function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

		$currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		if(!$currentUserPriviligesModel->hasModuleActionPermission($moduleModel->getId(), 'Export')) {
			throw new AppException('LBL_PERMISSION_DENIED');
		}
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
		}

		$this->output($request, $translatedHeaders, $entries);
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
		$queryGenerator->setFields($fields);
		$query = $queryGenerator->getQuery();

		if(in_array($moduleName, getInventoryModules())){
			$query = $this->moduleInstance->getExportQuery($this->focus, $query);
		}

		$this->accessibleFields = $queryGenerator->getFields();

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
	function output($request, $headers, $rawentries) {
		$indexassigned = array_search('Assigned To', $headers);
		unset($headers[$indexassigned]);
		$indexcreated = array_search('Created Time', $headers);
		unset($headers[$indexcreated]);
		$indexmodified = array_search('Modified Time', $headers);
		unset($headers[$indexmodified]);
		$indexpicarid = array_search('User ID (PICAR)', $headers);
		unset($headers[$indexpicarid]);
		//echo "<pre>";
		//print_r($headers); exit;
		$entries = array();
		foreach($rawentries as $rawentry){
		unset($rawentry["smownerid"]);
		unset($rawentry["createdtime"]);
		unset($rawentry["modifiedtime"]);
		//unset($rawentry["cf_5952"]);
		//unset($rawentry["cf_5962"]);
		unset($rawentry["cf_5958"]);
		unset($rawentry["cf_5960"]);
		//unset($rawentry["cf_5962"]);
		//unset($rawentry["cf_5976"]);
		//unset($rawentry["cf_5978"]);
		$entries[] = $rawentry;
		}
		//print_r($entries); exit;
		$moduleName = $request->get('source_module');
		$fileName = str_replace(' ','_',vtranslate($moduleName, $moduleName));    
		$exportType = $this->getExportContentType($request);
		if($request->get('export_type') == "xls")
		{
			
			require_once("libraries/PHPExcel/PHPExcel.php");
	
			$workbook = new PHPExcel();
			$worksheet = $workbook->setActiveSheetIndex(0);
			$worksheet->mergeCells('D3:E3');
			$worksheet->mergeCells('D4:E4');
			$worksheet->mergeCells('J3:K3');
			$worksheet->mergeCells('F3:G3');
			$worksheet->mergeCells('H3:I3');
			$worksheet->mergeCells('L3:Q3');
			$worksheet->mergeCells('R3:S3');
			$worksheet->mergeCells('U3:V3');
			$worksheet->mergeCells('J4:K4');
			$worksheet->mergeCells('L4:M4');
			$worksheet->mergeCells('F4:G4');
			$worksheet->mergeCells('H4:I4');
			$worksheet->mergeCells('N4:S4');
			$worksheet->mergeCells('T4:U4');
			$worksheet->mergeCells('W4:X4');
			$worksheet->setCellValueByColumnAndRow(1, 2, "Пример:");
			$worksheet->setCellValueByColumnAndRow(1, 3, "Февраль (February)");
			$worksheet->setCellValueByColumnAndRow(3, 3, "Данные подтягиваются из производственного календаря (per production calendar)");
			$worksheet->setCellValueByColumnAndRow(9, 3, "Данные корректировки вводятся в ручную (Manual corrections)");
			$worksheet->setCellValueByColumnAndRow(11, 3, "Данные подтягиваются из модуля Leave request (per Leave request module)");
			$worksheet->setCellValueByColumnAndRow(1, 4, "Подразделение организации (Department)");
			$worksheet->setCellValueByColumnAndRow(3, 4, "График работы (Working days per month)");
			$worksheet->setCellValueByColumnAndRow(5, 4, "Данные из 1С ЗУП (per 1C)");
			$worksheet->setCellValueByColumnAndRow(7, 4, "Данные из Picar (per Picar)");
			$worksheet->setCellValueByColumnAndRow(9, 4, "Данные из Biotrack (per Biotrack)");
			$worksheet->setCellValueByColumnAndRow(11, 4, "Корректировки HR (HR corrections)");
			$worksheet->setCellValueByColumnAndRow(13, 4, "Leave requests");
			$worksheet->setCellValueByColumnAndRow(19, 4, "Разница между официальным количеством дней по произв. Кал-рю и ERP (Difference between official days per production calenadar and ERP)");
			$worksheet->setCellValueByColumnAndRow(22, 4, "Разница 1С и Picar (difference between 1C and Picar)");
			$worksheet->setCellValueByColumnAndRow(1, 5, "Сотрудник (Employee)");
			$worksheet->setCellValueByColumnAndRow(2, 5, "Должность (Position)");
			$worksheet->setCellValueByColumnAndRow(3, 5, "дни (days)");
			$worksheet->setCellValueByColumnAndRow(4, 5, "часы (hours)");
			$worksheet->setCellValueByColumnAndRow(5, 5, "Отработано дней (worked days)");
			$worksheet->setCellValueByColumnAndRow(6, 5, "Отработано часов (worked hours)");
			$worksheet->setCellValueByColumnAndRow(7, 5, "Отработано дней (worked days)");
			$worksheet->setCellValueByColumnAndRow(8, 5, "Отработано часов (worked hours)");
			$worksheet->setCellValueByColumnAndRow(9, 5, "Отработано дней (worked days)");
			$worksheet->setCellValueByColumnAndRow(10, 5, "Отработано часов (worked hours)");
			$worksheet->setCellValueByColumnAndRow(11, 5, "дни (days)");
			$worksheet->setCellValueByColumnAndRow(12, 5, "часы (hours)");
			$worksheet->setCellValueByColumnAndRow(13, 5, "Annual paid leave");
			$worksheet->setCellValueByColumnAndRow(14, 5, "Sick leave");
			$worksheet->setCellValueByColumnAndRow(15, 5, "Casual paid leave");
			$worksheet->setCellValueByColumnAndRow(16, 5, "Unpaid leave");
			$worksheet->setCellValueByColumnAndRow(17, 5, "Maternity leave");
			$worksheet->setCellValueByColumnAndRow(18, 5, "Child care leave");
			$worksheet->setCellValueByColumnAndRow(19, 5, "дни (days)");
			$worksheet->setCellValueByColumnAndRow(20, 5, "часы (hours)");
			$worksheet->setCellValueByColumnAndRow(21, 5, "Комментарии (Comments)");
			$worksheet->setCellValueByColumnAndRow(22, 5, "дни (days)");
			$worksheet->setCellValueByColumnAndRow(23, 5, "часы (hours)");
			$worksheet->setCellValueByColumnAndRow(24, 5, "Комментарии (Comments)");
			$worksheet->setCellValueByColumnAndRow(25, 5, "PICAR ID");
			$worksheet->setCellValueByColumnAndRow(26, 5, "BIOTRACK ID");
			$worksheet->setCellValueByColumnAndRow(27, 5, "Department");
			$worksheet->setCellValueByColumnAndRow(28, 5, "Office");
			$worksheet->setAutoFilter("B5:AC5");
			$worksheet->getStyle('B5:AC5')->getAlignment()->setWrapText(true); 
			$worksheet->getStyle('B4:AC4')->getAlignment()->setWrapText(true);
			$worksheet->getStyle('B5:AC5')->applyFromArray(
				array(
					'borders' => array(
								  'allborders' => array(
									  'style' => PHPExcel_Style_Border::BORDER_MEDIUM
								  )
								)
					)
				);
				$worksheet->getStyle('B4:AC4')->applyFromArray(
				array(
					'borders' => array(
								  'allborders' => array(
									  'style' => PHPExcel_Style_Border::BORDER_MEDIUM
								  )
								)
					)
				);
			$col = 1;
			$row = 5;
			//exit;
			foreach($entries as $entryval)
			{
				$col = 1;
				$row ++;
				foreach($entryval as $entval)
				{
					$worksheet->setCellValueByColumnAndRow($col, $row, $entval);
					if($col == 18)
					{
						//echo $col+2; exit;
						$worksheet->setCellValueByColumnAndRow(19, $row, "=D".$row."-H".$row."-J".$row."-SUM(N".$row.":S".$row.")-L".$row);
						$worksheet->setCellValueByColumnAndRow(20, $row, "=E".$row."-I".$row."-K".$row."-(8*SUM(N".$row.":S".$row."))-M".$row);
						//$worksheet->setCellValueByColumnAndRow(18, $row, "=E".$row."-I".$row."-(8*SUM(L".$row.":Q".$row.",))-K".$row);
						$col = $col + 6;
					}
					$col = $col + 1;
				}
			}
			//setting style start
			$column = PHPExcel_Cell::stringFromColumnIndex($col);
			$workbook->getActiveSheet()->getStyle("B5:AC5")->applyFromArray(
	array(
			'fill' 	=> array(
								'type'		=> PHPExcel_Style_Fill::FILL_SOLID,
								'color'		=> array('rgb' => '9BBB59')
							),
			'font'		=> array('bold' => true)
		 )
	);
$workbook->getActiveSheet()->getStyle("B4:AC4")->applyFromArray(
	array(
			'fill' 	=> array(
								'type'		=> PHPExcel_Style_Fill::FILL_SOLID,
								'color'		=> array('rgb' => '9BBB59')
							),
			'font'		=> array('bold' => true)
		 )
	);
	$workbook->getActiveSheet()->freezePane('B6');
	//echo "in"; exit;
			//setting style ends
			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header("Content-Disposition: attachment;filename=$fileName.xls");
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
		}
		else
		{
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
				$line = implode("\",\"",$row);
				$line = "\"" .$line;
				$line .= "\"\r\n";
				echo $line;
			}
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
			}elseif($uitype==898){
				$value = Vtiger_LocationList_UIType::getDisplayValue($value);
			}
			elseif($uitype==899){
				$value = Vtiger_DepartmentList_UIType::getDisplayValue($value);
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