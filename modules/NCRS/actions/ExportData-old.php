<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
class NCRS_ExportData_Action extends Vtiger_ExportData_Action {

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
		//echo "<pre>"; print_r($query); exit;
		$headers = array();
		//$headers[] = "ncrsid";
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
			$entries[$db->fetchByAssoc($result, $j)['ncrsid']] = $this->sanitizeValues($db->fetchByAssoc($result, $j));
		}
		//echo "<pre>"; print_r($result); exit;
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
		//print_r($fieldInstances); exit;
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
		$query = str_replace("SELECT","select vtiger_ncrs.ncrsid,",$query); 
		//echo $query; exit;
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
	function output($request, $headers, $entries) {
		$moduleName = $request->get('source_module');
		$fileName = str_replace(' ','_',vtranslate($moduleName, $moduleName));    
		$exportType = $this->getExportContentType($request);
		if($request->get('export_type') == "xls")
		{
			
			require_once("libraries/PHPExcel/PHPExcel.php");
			$objReader = PHPExcel_IOFactory::createReader('Excel2007');
			$workbook = $objReader->load("include/NCRS/ncrtemplatenew.xlsx");
			//$workbook = new PHPExcel();
			$worksheet = $workbook->setActiveSheetIndex(0);
			$col = 1;
			$row = 4;
			//exit;
			foreach($entries as $ncrsid => $entryval)
			{
				//print_r($ncrsid); exit;
				$col = 0;
				$row ++;
				//ncr raised for details to be fetched start
				$pagingModel = new Vtiger_Paging_Model();
				$pagingModel->set('page','1');

				$recordModel = Vtiger_Record_model::getInstanceById($ncrsid, 'NCRS');		
				$relatedModuleName = 'NCRRaised';
				$parentRecordModel = $recordModel;
				$relationListView = Vtiger_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName, $label);
				$models = $relationListView->getEntries($pagingModel);
				$raised_for_details='';
				$i=0;
				foreach($models as $key => $model)
				{
					$i++;
					$raised_for_persons = $model->getDisplayValue('cf_6512');
					$raised_for_department = $model->getDisplayValue('cf_6508');
					$raised_for_location = $model->getDisplayValue('cf_6510');
					$raised_for_details .= $raised_for_persons." ".$raised_for_department." / ".$raised_for_location."\n";
				}
	
				
				$pagingModel = new Vtiger_Paging_Model();
				$pagingModel->set('page','1');
				//$NCR_info_detail = Vtiger_Record_Model::getInstanceById($ncrsid, 'NCRS');
				$relatedModuleName = 'CorretiveAction';
				$parentRecordModel = $recordModel;
				$relationListView = Vtiger_RelationListView_Model::getInstance($parentRecordModel, $relatedModuleName, $label);
				$models = $relationListView->getEntries($pagingModel);
				$corrective_action_details=''; $deadline = ""; $responsible_person= "";
				$i=0;
				foreach($models as $key => $model)
				{
					$i++;
					$corrective_action = $model->get('cf_6434');
					$responsible_id = $model->get('cf_6436');
					$deadline .= $model->getDisplayValue('cf_6438')."\n";

					$responsible_user_info = Users_Record_Model::getInstanceById($responsible_id, 'Users');
					$responsible_info = $responsible_user_info->get('first_name').' '.$responsible_user_info->get('last_name');
					$responsible_location= $responsible_user_info->getDisplayValue('location_id');
					$responsible_department= $responsible_user_info->getDisplayValue('department_id');
					$responsible_person .= $responsible_info."  : ".$responsible_location." / ".$responsible_department."\n";

					
					$corrective_action_details .= $i.')'.$corrective_action."\n";

				}
				//ncr raised for details to be fetched end
				$worksheet->setCellValueByColumnAndRow(0, $row, $entryval['createdtime']);
				$worksheet->setCellValueByColumnAndRow(1, $row, $entryval['cf_1963']);
				$worksheet->setCellValueByColumnAndRow(2, $row, $entryval['cf_1961']);
				$worksheet->setCellValueByColumnAndRow(3, $row, $entryval['cf_6414']);
				$worksheet->setCellValueByColumnAndRow(4, $row, $raised_for_details); //department received ncr
				$worksheet->setCellValueByColumnAndRow(5, $row, $entryval['cf_1965']);
				$worksheet->setCellValueByColumnAndRow(6, $row, $entryval['cf_6410']);
				$worksheet->setCellValueByColumnAndRow(7, $row, $entryval['cf_1967']);
				$worksheet->setCellValueByColumnAndRow(8, $row, $corrective_action_details); //description of corrective action
				$worksheet->setCellValueByColumnAndRow(9, $row, $deadline); //deadline
				$worksheet->setCellValueByColumnAndRow(10, $row, $responsible_person); //person responsible
				$worksheet->setCellValueByColumnAndRow(11, $row, $entryval['cf_6514']);
				//$worksheet->setCellValueByColumnAndRow(12, $row, $entryval['cf_6514']); //reminders
				$worksheet->setCellValueByColumnAndRow(13, $row, $entryval['cf_6426']); //status
				//$worksheet->setCellValueByColumnAndRow(14, $row, $entryval['cf_6514']); //comments
				//$worksheet->setCellValueByColumnAndRow(15, $row, $entryval['cf_6514']); //verification pf corrective action
				$worksheet->getRowDimension($row)->setRowHeight(-1);
				$worksheet->getStyle("A5:P".$row)->getAlignment()->applyFromArray(
					array(
							'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
							'vertical'   => PHPExcel_Style_Alignment::VERTICAL_TOP,
							'rotation'   => 0,
							'wrap'		 => true
						)
				);
			}
			$worksheet->getStyle("A2:P".$row)->applyFromArray(
				array(
						'borders' => array(
											  'allborders' => array(
												  'style' => PHPExcel_Style_Border::BORDER_MEDIUM
											  )
											)
					 )
				);
			$worksheet->setTitle("NCR Report");
			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header("Content-Disposition: attachment;filename=$fileName.xlsx");
			header('Cache-Control: max-age=0');
			// If you're serving to IE 9, then the following may be needed
			header('Cache-Control: max-age=1');
			
			// If you're serving to IE over SSL, then the following may be needed
			header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
			header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
			header ('Pragma: public'); // HTTP/1.0
			$workbookWriter = PHPExcel_IOFactory::createWriter($workbook, 'Excel2007');
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
			elseif($uitype==597){
				$value = Vtiger_GLKUserList_UIType::getDisplayValue($value);
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