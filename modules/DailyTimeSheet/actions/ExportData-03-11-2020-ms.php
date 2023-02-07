<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class DailyTimeSheet_ExportData_Action extends Vtiger_ExportData_Action {

	var $moduleCall = false;
	public function requiresPermission(\Vtiger_Request $request) {
		//$permissions = parent::requiresPermission($request);
		//$permissions[] = array('module_parameter' => 'module', 'action' => 'Export');
        //if (!empty($request->get('source_module'))) {
        //    $permissions[] = array('module_parameter' => 'source_module', 'action' => 'Export');
        //}
		return $permissions=true;
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

		$exportType = $request->get('Exporttype');	
		

		$this->moduleInstance = Vtiger_Module_Model::getInstance($moduleName);
		$this->moduleFieldInstances = $this->moduleFieldInstances($moduleName);
		$this->focus = CRMEntity::getInstance($moduleName);

		$request->set('orderby', 'cf_6892');
		$request->set('sortorder', 'DESC');

		
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
			//$entries[] = $this->sanitizeValues($db->fetchByAssoc($result, $j));			
			$entries[] = $this->sanitizeValues($db->fetchByAssoc($result, $j));	
			//$dailytimesheetid_arr[] = $db->fetch_array($result2, $i)['dailytimesheetid'];			
		}

		$this->output($request, $translatedHeaders, $entries);
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

		//echo $query;
	//	exit;

		$query = str_replace("SELECT","SELECT vtiger_dailytimesheetcf.dailytimesheetid,",$query); 

		$additionalModules = $this->getAdditionalQueryModules();
		if(in_array($moduleName, $additionalModules)) {
			$query = $this->moduleInstance->getExportQuery($this->focus, $query);
		}

		$this->accessibleFields = $queryGenerator->getFields();

		$exportType = $request->get('Exporttype');	

		switch($mode) {
			case 'ExportAllData'	:	
					switch($exportType){
						case 'report_with_task_count':
							if ($orderBy && $orderByFieldModel) {
								$query .= ' GROUP BY vtiger_crmentity.smownerid
											ORDER BY '.$queryGenerator->getOrderByColumn($orderBy).' '.$sortOrder;
							}
						break;
						default :
						if ($orderBy && $orderByFieldModel) {
							$query .= ' ORDER BY '.$queryGenerator->getOrderByColumn($orderBy).' '.$sortOrder;
						}							
						break;	
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
		$db = PearDatabase::getInstance();
		$moduleName = $request->get('source_module');
		$fileName = str_replace(' ','_',decode_html(vtranslate($moduleName, $moduleName)));
		// for content disposition header comma should not be there in filename 
		$fileName = str_replace(',', '_', $fileName);
		$exportType = $this->getExportContentType($request);
	
		if($request->get('export_type') == "xls")
		{
			$exportType = $request->get('Exporttype');
			if($exportType=='report_with_task_count')
			{
				require_once("libraries/PHPExcel/PHPExcel.php");
				$objReader = PHPExcel_IOFactory::createReader('Excel2007');
				$workbook = $objReader->load("include/DailyTimeSheet/email_format_template.xlsx");
				//$workbook = new PHPExcel();
				$worksheet = $workbook->setActiveSheetIndex(0);
				$col = 1;
				$row = 2;

				foreach($entries as $dailytimesheetid_key => $entryval)
				{
					$col = 0;
					$row ++;


					$query_dailytask = "Select * from vtiger_dailytimesheet where name = '".$entryval['name']."' " ;
					$result_dailytask = $db->pquery($query_dailytask, array());
					$row_dailytask = $db->fetch_array($result_dailytask);
					$dailytimesheetid = $row_dailytask['dailytimesheetid'];
					//$recordModel = Vtiger_Record_model::getInstanceById($dailytimesheetid, 'DailyTimeSheet');	
					$days_spent =1;
					$total_days_spent +=1;
					$value_smownerid = Vtiger_Util_Helper::getFullOwnerName($entryval['smownerid']);
					$worksheet->setCellValueByColumnAndRow(0, $row, $value_smownerid);
					$worksheet->setCellValueByColumnAndRow(1, $row, $entryval['cf_6884']); //Location
					$worksheet->setCellValueByColumnAndRow(2, $row, $entryval['cf_6882']);  //department
					
					//AND vtiger_crmentity.createdtime between '".$starttime."' and '".$endtime."' 
					$query_dt = "	SELECT sum(cf_6908) as task_total, vtiger_dailytimesheettaskcf.cf_6904 as task_name FROM `vtiger_crmentity` 
									INNER JOIN vtiger_dailytimesheetcf ON vtiger_dailytimesheetcf.dailytimesheetid = vtiger_crmentity.crmid
									INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.crmid = vtiger_dailytimesheetcf.dailytimesheetid
									INNER JOIN vtiger_dailytimesheettaskcf ON vtiger_dailytimesheettaskcf.dailytimesheettaskid = vtiger_crmentityrel.relcrmid
									WHERE vtiger_crmentity.smcreatorid = ?
									AND  vtiger_crmentity.deleted = 0
									GROUP BY vtiger_dailytimesheettaskcf.cf_6904
									"; 
					$params = array($entryval['smownerid']);
					$result_dt = $db->pquery($query_dt, $params);
					$othersum = 0;
					$quatitionssum = 0;
					$jobsum= 0;
					$iesum= 0;
					$oesum= 0;
					$totalactivity = 0;
					while($row_dt = $db->fetch_array($result_dt))
					{
						$task_name = $row_dt['task_name'];
						switch($task_name)
						{
							case 'Quotes':
								$quatitionssum = $row_dt['task_total'];
							break;
							case 'Job':
								$jobsum = $row_dt['task_total'];
							break;
							case 'Incoming Emails':
								$iesum = $row_dt['task_total'];
							break;
							case 'Outgoing Emails':
								$oesum = $row_dt['task_total'];
							break;
							default:
								$othersum += $row_dt['task_total'];
							break;
						}
						
					}
					$totalactivity = $quatitionssum + $jobsum + $iesum + $oesum + $othersum;
					//$quatitionssum = $row_dt['quotes'];
					$worksheet->setCellValueByColumnAndRow(3, $row, $quatitionssum); //total_quotes					
					$worksheet->setCellValueByColumnAndRow(4, $row, $jobsum); //total_jobs				
					$worksheet->setCellValueByColumnAndRow(5, $row, $iesum); //total_incoming_emails					
					$worksheet->setCellValueByColumnAndRow(6, $row, $oesum); //total_outgoing_emails
					$worksheet->setCellValueByColumnAndRow(7, $row, $othersum); //total_other								
					$worksheet->setCellValueByColumnAndRow(8, $row, $totalactivity); //total_outgoing_emails	
				}


				$worksheet->getStyle("A2:I".$row)->applyFromArray(
					array(
							'borders' => array(
												'allborders' => array(
													'style' => PHPExcel_Style_Border::BORDER_MEDIUM
												)
												)
						)
					);
				
				$worksheet->setTitle("Time Sheet Report");
				
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
			else{
				require_once("libraries/PHPExcel/PHPExcel.php");
				$objReader = PHPExcel_IOFactory::createReader('Excel2007');
				$workbook = $objReader->load("include/DailyTimeSheet/monthly_format_template.xlsx");
				//$workbook = new PHPExcel();
				$worksheet = $workbook->setActiveSheetIndex(0);
				$col = 1;
				$row = 2;

				//[name] => ALA-26918/20
				//[smownerid] => Mehtab Shah
				//[createdtime] => 25-06-2020 22:29:57
				//[modifiedtime] => 25-06-2020 22:29:57
				//[cf_6882] => IT
				//[cf_6884] => ALA
				//[cf_6892] => 25-06-2020
				//[cf_6912] => 2020-06-25 08:56 AM
				$final_hours = 0;
				$final_minutes = 0;
				$total_days_spent=0;
				foreach($entries as $dailytimesheetid_key => $entryval)
				{
					$col = 0;
					$row ++;
					
					$query_dailytask = "Select * from vtiger_dailytimesheet where name = '".$entryval['name']."' " ;
					$result_dailytask = $db->pquery($query_dailytask, array());
					$row_dailytask = $db->fetch_array($result_dailytask);
					$dailytimesheetid = $row_dailytask['dailytimesheetid'];
					//$recordModel = Vtiger_Record_model::getInstanceById($dailytimesheetid, 'DailyTimeSheet');	
					$days_spent =1;
					$total_days_spent +=1;
					$value_smownerid = Vtiger_Util_Helper::getFullOwnerName($entryval['smownerid']);
					$worksheet->setCellValueByColumnAndRow(0, $row, $value_smownerid);
					$worksheet->setCellValueByColumnAndRow(1, $row, $entryval['cf_6884']); //Location
					$worksheet->setCellValueByColumnAndRow(2, $row, $entryval['cf_6882']);  //department
					$worksheet->setCellValueByColumnAndRow(3, $row, $entryval['cf_6892']); //date
					$worksheet->setCellValueByColumnAndRow(4, $row, $entryval['cf_6912']); //login
					$worksheet->setCellValueByColumnAndRow(5, $row, $days_spent); //days spent
					
					$daily_task_sum_sql = " SELECT sum(vtiger_dailytimesheettaskcf.cf_6910) as total_hours FROM `vtiger_dailytimesheettaskcf` 
									INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_dailytimesheettaskcf.dailytimesheettaskid
									INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid 
									where vtiger_crmentity.deleted=0 AND crmentityrel.crmid=? AND crmentityrel.module='DailyTimeSheet' 
									AND crmentityrel.relmodule='DailyTimeSheetTask' AND vtiger_dailytimesheettaskcf.cf_7140='Hours'"; 
					$params = array($dailytimesheetid);
					$result_daily_task = $db->pquery($daily_task_sum_sql, $params);
					$row_daily_task = $db->fetch_array($result_daily_task);
					$hours_spent = $row_daily_task['total_hours'];
					
					$daily_task_sum_sql = " SELECT sum(vtiger_dailytimesheettaskcf.cf_6910) as total_minutes FROM `vtiger_dailytimesheettaskcf` 
									INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_dailytimesheettaskcf.dailytimesheettaskid
									INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid 
									where vtiger_crmentity.deleted=0 AND crmentityrel.crmid=? AND crmentityrel.module='DailyTimeSheet' 
									AND crmentityrel.relmodule='DailyTimeSheetTask' AND vtiger_dailytimesheettaskcf.cf_7140='Minutes'"; 
					$params = array($dailytimesheetid);
					$result_daily_task = $db->pquery($daily_task_sum_sql, $params);
					$row_daily_task = $db->fetch_array($result_daily_task);
					$minutes_spent = $row_daily_task['total_minutes'];
					
					$hours_minutes = date('H:i', mktime(0,$minutes_spent));
					list($hours, $minutes) = split('[:]', $hours_minutes);
				
					$total_hours = $hours_spent + $hours;
					$total_minutes = $minutes;

					$final_hours +=$total_hours;
					$final_minutes +=$total_minutes;
					
					$total_hours_minutes = date('H:i', mktime($total_hours, $total_minutes));

					$worksheet->setCellValueByColumnAndRow(6, $row, $total_hours_minutes); //days spent

					//$worksheet->setCellValueByColumnAndRow(7, $row, $entryval['cf_7140']); //days spent
				
					$worksheet->getStyle("A3:G".$row)->getAlignment()->applyFromArray(
						array(
								'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
								'vertical'   => PHPExcel_Style_Alignment::VERTICAL_TOP,
								'rotation'   => 0,
								'wrap'		 => true
							)
					);
				}

				$row++;
				$worksheet->setCellValueByColumnAndRow(0, $row, 'Total'); //days spent
				$worksheet->setCellValueByColumnAndRow(5, $row, $total_days_spent); //days spent

				$fina_total_hours_minutes = date('H:i', mktime($final_hours, $final_minutes));
				$worksheet->setCellValueByColumnAndRow(6, $row, $fina_total_hours_minutes); //days spent		
				
				$worksheet->getStyle("A2:G".$row)->applyFromArray(
					array(
							'borders' => array(
												'allborders' => array(
													'style' => PHPExcel_Style_Border::BORDER_MEDIUM
												)
												)
						)
					);
				
				$worksheet->setTitle("Time Sheet Report");
				
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

		}else{

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
				//$value = Vtiger_Util_Helper::getOwnerName($value);
				//$value = Vtiger_Util_Helper::getFullOwnerName($value);
				$value = $value;
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
			elseif($uitype==898){
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

	public function moduleFieldInstances($moduleName) {
		return $this->moduleInstance->getFields();
	}
}