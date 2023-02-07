<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class CargoInsurance_ExportData_Action extends Vtiger_ExportData_Action {

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

						
		$Exporttype = $request->get('Exporttype');

			foreach($entries as $key => $entry)
			{
				$beneficiary = $entry['cf_3601'];

				$account_bill_country = '';
				$account_legal_name = '';
				$db_ci = PearDatabase::getInstance();
				
				$rs_result_c = $db_ci->pquery("select * from vtiger_crmentity where deleted=0 AND setype='Accounts' AND label = '".html_entity_decode($beneficiary)."' ", array());
				$ci_count  = $db_ci->num_rows($rs_result_c);

				if($ci_count>0)
				{
					$crmentity_detail = $db_ci->fetch_array($rs_result_c);
					$account_id =  $crmentity_detail['crmid'];
					$account_info = Vtiger_Record_Model::getInstanceById($account_id, 'Accounts');

					$account_bill_country = @$account_info->get('bill_country');
					$account_legal_name = @$account_info->get('cf_2395');
				}

				$entries[$key]['country'] = $account_bill_country;
				$entries[$key]['legal_name'] = $account_legal_name;
			}

			$translatedHeaders[] = 'Country';
			$translatedHeaders[] = 'Beneficiary Legal Name';


			if($moduleName=='CargoInsurance' && $Exporttype=='wis_insurance')
			{
				$this->getReportXLS_XLSX($request, $translatedHeaders, $entries, $moduleName);
			}
			elseif($moduleName=='CargoInsurance' && $Exporttype=='accountancy_insurance')
			{
				$this->getReportXLS($request, $translatedHeaders, $entries);
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

	
		//if($moduleName=='CargoInsurance')
		//{
		$this->writeReportToExcelFile_Cargo($tempFileName, $headers, $entries, false);
		//}
	

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

	function writeReportToExcelFile_Cargo($fileName, $headers, $entries, $filterlist='') {
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

		$objPHPExcel->getActiveSheet()->setSharedStyle($sharedStyle1, "A5:AS5");
		$objPHPExcel->getActiveSheet()->setSharedStyle($sharedStyle2, "A6:AS6");


		$objPHPExcel->getActiveSheet()->mergeCells('B1:H1');
		$objPHPExcel->getActiveSheet()->setCellValue('B1', "Бордеро № 99-ДСГ-165 от 01.06.2017г. к договору страхования № 99-ДСГ-163");
		$objPHPExcel->getActiveSheet()->setCellValue('B2', "From:");
		$objPHPExcel->getActiveSheet()->setCellValue('B3', "To:");
		$objPHPExcel->getActiveSheet()->setCellValue('D2', "INSURANCE RECORDS LIST");

		$wis_headers =   array('Report','Reg Date', '(WIS)', 'Ref No.', 'PERIOD OF SHIPMENT FROM', 'PERIOD OF SHIPMENT TO','NAME OF BENEFICIARY', 'Man Type', 'Country of residence',
							   'IIN', 'PASP_NO', 'CONVEYANCE BY', 'VOYAGE FROM country', 'VOYAGE FROM city', 'VOYAGE TO country', 'VOYAGE TO city', 'DESCRIPTION OF THE GOODS',
							   'Group of the goods', 'Insured Value', 'Total insured sum', 'Interest, %', 'Discounted GLK selling rate', 'Premium', 'Excess, value',
							   'Excess, dimension', 'Excess, type', 'Excess, min value', 'Excess, currency', 'Excess comment', 'Cur.', 'Insured Agreement', 'WIS bill amount',
							   "Agent's fee",'Commodity', 'Mode', 'Special Range / Method', '');

		$erp_headers = array('ERP', 'WIS date', 'WIS Ref #', 'Ref No.', 'FROM DATE', 'TO DATE', 'BENEFICIARY', '', 'Country', 'IIN', '', 'MODE', 'FROM country',
							 'FROM city', 'TO country', 'TO city', 'DESCRIPTION OF THE GOODS', 'Commodity type', 'Total sum to be insured', 'Total sum to be insured',
							 'Globalink selling rate', 'Discounted GLK selling rate', 'Globalink Premium', 'Excess, value', 'Excess, dimension', 'Excess, type',
							 'Excess, min value', 'Excess, currency', 'Excess comment', 'Cur.', 'Insured Agreement', 'WIS premium', 'Agent comission rate', '',
							 '', '', 'Beneficiary Legal Name');
  		$objPHPExcel->getActiveSheet()->fromArray($wis_headers, null, 'A5');
		$objPHPExcel->getActiveSheet()->fromArray($erp_headers, null, 'A6');

		$objPHPExcel->getActiveSheet()->getRowDimension('5')->setRowHeight(70);
		$objPHPExcel->getActiveSheet()->getRowDimension('6')->setRowHeight(60);
		$objPHPExcel->getActiveSheet()->getStyle('A5:AS5')->getAlignment()->setWrapText(true);
		$objPHPExcel->getActiveSheet()->getStyle('A6:AS6')->getAlignment()->setWrapText(true);
		//$objPHPExcel->getRowDimension('5')->setRowHeight('40');
		//$objPHPExcel->getRowDimension('6')->setRowHeight('40');


		// Hide "Phone" and "fax" column
		//echo date('H:i:s') . " Hide \"Phone\" and \"fax\" column\n";
		//$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setVisible(false);
		//$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setVisible(false);


		// Set outline levels
		//echo date('H:i:s') . " Set outline levels\n";
		//$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setOutlineLevel(1);
		//$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setVisible(false);
		//$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setCollapsed(true);

		// Freeze panes
		//echo date('H:i:s') . " Freeze panes\n";
		//$objPHPExcel->getActiveSheet()->freezePane('A2');
		$objPHPExcel->getActiveSheet()->freezePane('D7');


		// Rows to repeat at top
		//echo date('H:i:s') . " Rows to repeat at top\n";
		$objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);

		$entries_new = array();
		foreach($entries as $key => $entry)
		{
			$key++;
			$mode_arr = explode(' |##| ',$entry['cf_3619']);
			if(in_array('Ocean', $mode_arr))
			{
				$wis_mode = 'Sea';
			}
			elseif(in_array('Air', $mode_arr))
			{
				$wis_mode = 'Air';
			}
			elseif(in_array('Road', $mode_arr))
			{
				$wis_mode='Land';
			}
			elseif(in_array('Rail', $mode_arr))
			{
				$wis_mode='Land';
			}

			$agent_comission_rate = $entry['cf_3665'].'%';
			$entries_new[] = array('key' => $key, 'cf_3623' => $entry['cf_3623'], 'cf_3621' => $entry['cf_3621'], 'name' => $entry['name'],
								   'cf_3613' => $entry['cf_3613'], 'cf_3615' => $entry['cf_3615'], 'cf_3601'=> html_entity_decode($entry['cf_3601']),
								   'man_type' =>'', 'country' => $entry['country'],
								   'cf_3603' => $entry['cf_3603'], 'pasp_no' => '', 'cf_3619' => str_replace(' |##| ',', ',$entry['cf_3619']), 'cf_3605' => $entry['cf_3605'],
								   'cf_3607' => $entry['cf_3607'], 'cf_3609' => $entry['cf_3609'], 'cf_3611' => $entry['cf_3611'], 'cf_3617' => html_entity_decode($entry['cf_3617']),
								   'cf_3625' => $entry['cf_3625'], 'cf_3639' => $entry['cf_3639'], 'total_sum_to_be_insured'=>'', 'cf_3641' => $entry['cf_3641'], 'cf_3643'=> $entry['cf_3643'],
								   'cf_3645' => $entry['cf_3645'], 'excess_value' => '0,5', 'excess_dimension' => 'процент', 'excess_type' => 'по каждому случаю',
								   'excess_min_value'=> '500', 'excess_currency' => 'USD',
								   'excess_comment' => html_entity_decode('0,5% от страховой суммы по каждому единичному случаю, но не менее 75 000 тенге или 500 долларов США'), 'cf_3663' => $entry['cf_3663'], 'insured_agreement'=> '', 'cf_3637' => $entry['cf_3637'], 'cf_3665' => $agent_comission_rate,
								   'cf_3625_wis' => $entry['cf_3625'], 'cf_3619_wis' => $wis_mode, 'cf_3627_wis' => $entry['cf_3627'],'legal_name' => html_entity_decode($entry['legal_name']));
		}

		// Add data
		//$entries = array_map("html_entity_decode",$entries);
		$objPHPExcel->getActiveSheet()->fromArray($entries_new, null, 'A7');
		//echo date('H:i:s') . " Set autofilter\n";
		$objPHPExcel->getActiveSheet()->setAutoFilter('A6:AS6');
		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex(0);


		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		//$objWriter->setUseBOM(true);
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
			//array_pop($array_value);	// removed action link in details
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
				//array_pop($array_value);	// removed action link in details
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
		//cf_3601::beneficiary
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