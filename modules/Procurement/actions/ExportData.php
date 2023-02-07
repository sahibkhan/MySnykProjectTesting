<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Procurement_ExportData_Action extends Vtiger_ExportData_Action {

	var $moduleCall = false;
	public function requiresPermission(\Vtiger_Request $request) {
		//$permissions = parent::requiresPermission($request);
		//$permissions[] = array('module_parameter' => 'module', 'action' => 'Export');
        //if (!empty($request->get('source_module'))) {
        //    $permissions[] = array('module_parameter' => 'source_module', 'action' => 'Export');
        //}
		return $permissions = true;
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
		//echo "<pre>"; print_r($request);exit;
		$this->moduleInstance = Procurement_Module_Model::getInstance($moduleName);
		$this->moduleFieldInstances = $this->moduleFieldInstances($moduleName);
		$this->focus = CRMEntity::getInstance($moduleName);
		$query = $this->getExportQuery($request);
		//echo "<pre>"; print_r($query);exit;
		/*Use string_replace below to fix search issue for company, location & department*/
		$query = str_replace("vtiger_crmentityproc_title.label","vtiger_procurementcf.proc_title",$query);
		$query = str_replace("vtiger_crmentityproc_location.label","vtiger_procurementcf.proc_location",$query);
		$query = str_replace("vtiger_crmentityproc_department.label","vtiger_procurementcf.proc_department",$query);
		$result = $db->pquery($query, array());
		//echo "<pre>"; print_r($result);exit;
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

		//echo "<pre> data "; print_r($procurement_detail);exit;
		
		spl_autoload_register(function ($class_name) {
			$path = str_replace('\\', '/', $class_name);
		    include getcwd().'/libraries/'.$path . '.php';
		});

		$file  = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$active_sheet = $file->getActiveSheet();
	  $main_field_header_titles = array('Created Date','Request No','Created By','Location','DPRT','Procurement Title','Order Status','Requested Item','For which DPRT','PMNT Docs No','PMNT Docs Type','Date of issue','Supplier','Company STATUS','Supplier Location','BIN Number','Agreement No','Currency Rate 1USD =','Amount LOC Currency','Total Amount USD','Packaging Mode','Fleet Mode','Vehicle Number','Vehicle Milage','Job Number','Comment');
	  $sub_field_header_titles = array('Expense Type','Description','Quantity','Price Per Unit','Local Price','VAT (%)','Price VAT','Gross (Local)','Currency','Total (Local Currency Net)','Gross (Local)','Total USD');
	  $field_header_titles = array_merge($main_field_header_titles,$sub_field_header_titles);
	  $field_pointer = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ','BA','BB','BC','BD','BE','BF','BG','BH','BI','BJ','BK','BL','BM','BN','BO','BP','BQ','BR','BS','BT','BU','BV','BW','BX','BY','BZ');
	  $i = 1;
	  $add_cell = count($main_field_header_titles);
	  //echo "<pre>"; print_r($entries); echo "</pre>";exit;
	  foreach($field_header_titles as $key => $field_header_title)
	  {
		$active_sheet->setCellValue($field_pointer[$key].$i, $field_header_title); //set titles
	  }
	  
	  foreach($entries as $procurement_record) //loop on export records
	  {
		 
		 $procurement_detail = Vtiger_Record_Model::getInstanceById($procurement_record['procurementid'], 'Procurement');
		 //echo "<pre>"; print_r($procurement_detail); echo "</pre>";
		 /*Prepare dynamic array of procurement record*/
		 $procurement_data_record = array(
		 
			$procurement_detail->get('createdtime'),
			$procurement_detail->get('proc_request_no'),
			strip_tags($procurement_detail->getDisplayValue('assigned_user_id')),
			$procurement_detail->getDisplayValue('proc_location'),
			$procurement_detail->getDisplayValue('proc_department'),
			$procurement_detail->getDisplayValue('proc_title'),
			$procurement_detail->getDisplayValue('proc_order_status'),
			$procurement_detail->getDisplayValue('proc_proctype'),
			$procurement_detail->getDisplayValue('proc_which_department'),
			$procurement_detail->get('proc_doc_no'),
			$procurement_detail->getDisplayValue('proc_doc_type'),
			$procurement_detail->get('proc_issue_date'),
			strip_tags($procurement_detail->getDisplayValue('proc_supplier')),
			$procurement_detail->getDisplayValue('proc_company_status'),
			$procurement_detail->get('proc_supplier_location'),
			$procurement_detail->get('proc_bin_number'),
			$procurement_detail->get('proc_agreement_no'),
			$procurement_detail->get('proc_currency_usd_rate'),
			$procurement_detail->get('proc_loc_currency'),
			$procurement_detail->get('proc_total_amount'),
			$procurement_detail->get('proc_purchase_type_pm'),
			$procurement_detail->get('proc_purchase_type_fleet'),
			$procurement_detail->getDisplayValue('proc_vehicle_no'),
			$procurement_detail->get('proc_vehicle_mileage'),
			$procurement_detail->getDisplayValue('proc_job_no'),
			$procurement_detail->get('proc_comments')
		 
		 );
		 //echo "<pre>"; print_r($procurement_data_record); echo "</pre><br>-----------<br>";
			//echo $procurement_detail->getDisplayValue('proc_which_department');exit;
		$i++; //record on next row
		foreach($procurement_data_record as $k=>$procurement_data) //set procurement record cells dynamically
		{
			$active_sheet->setCellValue($field_pointer[$k].$i, $procurement_data);
		}
		$procurement_id = $procurement_record['procurementid'];
		/*prepare items list for current procurement record*/
		$procurementitems_result = $db->pquery("SELECT * FROM `vtiger_procurementitemscf` 
				inner join  `vtiger_crmentity` on vtiger_crmentity.`crmid` = `vtiger_procurementitemscf`.procurementitemsid				
				where procitem_procid = $procurement_id AND vtiger_crmentity.deleted=0");
		//echo "<pre>"; print_r($procurementitems_result); echo "</pre>";exit;
		for($j=0; $j<$db->num_rows($procurementitems_result); $j++)
		{
			$row = $db->query_result_rowdata($procurementitems_result, $j);
			$pm_items_id  = $row['procurementitemsid'];
			$procurementitem_detail = Vtiger_Record_Model::getInstanceById($pm_items_id, 'ProcurementItems');
			
			$procurementitems_expense_type = $db->pquery("SELECT * FROM `vtiger_procurementtypeitems`				
				where procurementtypeitemsid =".$procurementitem_detail->get('procitem_proctypeitem_id'));
			$expense_type = $db->query_result($procurementitems_expense_type, 0, 'name');
			$procurementitems_currency_code = $db->pquery("SELECT * FROM `vtiger_currency_info`				
				where id =".$procurementitem_detail->get('procitem_currency'));
			$currency_code = $db->query_result($procurementitems_currency_code, 0, 'currency_code');
			/*Prepare dynamic item details*/
			$procurement_item_record = array(		 
				$expense_type, //expense type
				$procurementitem_detail->get('procitem_description'), //description
				$procurementitem_detail->get('procitem_qty'), //qty
				number_format($procurementitem_detail->get('procitem_unit_price') , 2 ,  "." , ","), //Price Per Unit
				number_format($procurementitem_detail->get('procitem_line_price') , 2 ,  "." , ","),//local price
				number_format($procurementitem_detail->get('procitem_vat_unit') , 2 ,  "." , ","), //VAT
				number_format($procurementitem_detail->get('procitem_vat_amount') , 2 ,  "." , ","), //Price VAT
				number_format($procurementitem_detail->get('procitem_gross_local') , 2 ,  "." , ","), //Gross Local
				$currency_code, //currency
				number_format($procurementitem_detail->get('procitem_gross_finalamount') , 2 ,  "." , ","), //Final Amount (Net)
				number_format($procurementitem_detail->get('procitem_gross_local') , 2 ,  "." , ","), //Final Amount (Gross)
				number_format($procurementitem_detail->get('procitem_total_usd') , 2 ,  "." , ",") //Total USD	 
			);
			$i++; //show items on next row
			
			foreach($procurement_item_record as $ki=>$procurementitem_data) //set procurement item cells dynamically
			{
				$active_sheet->setCellValue($field_pointer[$ki+$add_cell].$i, $procurementitem_data); //$add_cell make cell start from item detail column
			}
			//echo "<pre>"; print_r($procurement_item_record); echo "</pre><br>-----------<br>";
		}
	
	  }
	  
	  $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($file,'Xlsx');

	  ob_clean();
	  $fileName = 'Procurement_Requests_Report_'.date('dmY').'.xlsx';
	  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	  header('Content-Disposition: attachment; filename="'. urlencode($fileName).'"');
	  $writer->save('php://output');
	  exit();
		
		
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
		$fields[] = 'procurementid';
		$queryGenerator->setFields($fields);
		$query = $queryGenerator->getQuery();
		$query = str_replace("FROM",",vtiger_procurementcf.procurementid FROM",$query); //manually add Procurement-ID field
		$request_type = $request->get('request_type');
		$location_id = $request->get('location_id');
		$date_range1 = $request->get('date_range1');
		$date_range2 = $request->get('date_range2');
		
		//echo $request_type.' '.$location_id.' '.$date_range1.' '.$date_range2;exit;
		if($request_type!='')
		{
			$query .= " AND vtiger_procurement.proc_proctype = '$request_type' ";
		}
		if($location_id!='')
		{
			$query .= " AND vtiger_procurementcf.proc_location = '$location_id' ";
		}
		if($date_range1!='' && $date_range2!='')
		{
			$query .= " AND DATE(vtiger_crmentity.createdtime) BETWEEN '$date_range1' AND '$date_range2' ";
		}
		echo $query;exit;
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
		//echo "<pre>"; print_r($this->fieldArray); echo "</pre>";exit;
		$moduleName = $this->moduleInstance->getName();
		foreach($arr as $fieldName=>&$value){
			if(isset($this->fieldArray[$fieldName])){
				$fieldInfo = $this->fieldArray[$fieldName];
			}else {
				//unset($arr[$fieldName]);
				//continue;
			}
			//echo $fieldName.'<br>';
			
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