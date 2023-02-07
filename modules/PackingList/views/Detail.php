<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class PackingList_Detail_View extends Vtiger_Detail_View {
	protected $record = false;

	function __construct() {
		
		parent::__construct();
		$this->exposeMethod('showModuleDetailView');
		$this->exposeMethod('showModuleBasicView');
		$this->exposeMethod('excelprint');
	}

	function preProcessTplName(Vtiger_Request $request) {
		$viewer = $this->getViewer ($request);
		$mode = $request->get('mode');
        if($mode == 'excelprint') {
            $this->invokeExposedMethod($mode,$request);
			exit;
		}
		$recordId = $request->get('record');
		$moduleName = $request->getModule();
		return parent::preProcessTplName($request);
	}

	public function showModuleDetailView(Vtiger_Request $request) {
		//$viewer->assign('IMAGE_DETAILS', $recordModel->getImageDetails());
		return parent::showModuleDetailView($request);
	}

	public function showModuleBasicView(Vtiger_Request $request) {
		return $this->showModuleDetailView($request);
	}
	
	function isAjaxEnabled($recordModel) {
	$currentusermodel = users_record_model::getcurrentusermodel();
		if ($currentusermodel->isadminuser() == true) {
			return true;
		} else {
			return false;
		}
	}
	

	public function getDictionary($englishName){
		global $adb;
		$result = $adb->pquery("SELECT russianName FROM vtiger_packinglist_dictionary_list WHERE englishName = ?", array($englishName));
		$ruName = $adb->query_result($result, 0);
		return $ruName;
	}


	public function excelprint(Vtiger_Request $request) {
		global $adb;
		$recordId = $request->get('record');
		// $recordModel = Vtiger_Record_model::getInstanceById($recordId, 'ExitInterview');
		require_once("libraries/PHPExcel/PHPExcel.php");
			$objReader = PHPExcel_IOFactory::createReader('Excel2007');
			$workbook = $objReader->load("modules/PackingList/CustomsInventory.xlsx");
			//$workbook = new PHPExcel();
			$worksheet = $workbook->setActiveSheetIndex(0);
			$sqlH = "SELECT vtiger_crmentityrel.relcrmid
								FROM vtiger_crmentityrel
								LEFT JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_crmentityrel.relcrmid			
								WHERE vtiger_crmentity.deleted = 0 AND vtiger_crmentityrel.relmodule = 'PackingListItem' 
								AND vtiger_crmentityrel.module = 'PackingList' AND vtiger_crmentityrel.crmid = ?";
			$resultH = $adb->pquery($sqlH, array($recordId));
			$num_rows = $adb->num_rows($resultH);
			
			for ($i=0; $i<$num_rows; $i++){
				$itemId = $adb->query_result($resultH, $i, 'relcrmid');				
				
					$queryDItems = "
					SELECT COUNT(vtiger_packinglistditemcf.packinglistditemid) as num, vtiger_packinglistdictionary.name
					
					FROM vtiger_packinglistditemcf		
					LEFT JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_packinglistditemcf.packinglistditemid
					LEFT JOIN vtiger_packinglistdictionary ON vtiger_packinglistdictionary.packinglistdictionaryid = vtiger_packinglistditemcf.cf_7130
					LEFT JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_packinglistditemcf.packinglistditemid			

					 
					WHERE vtiger_crmentityrel.crmid = ? AND vtiger_crmentityrel.module = 'PackingListItem' 
																AND vtiger_crmentityrel.relmodule = 'PackingListDItem' AND vtiger_crmentity.deleted = 0
																
															 GROUP BY vtiger_packinglistdictionary.name";
						$resultDItems = $adb->pquery($queryDItems, array($itemId));
						$nRowItem = $adb->num_rows($resultDItems);

						for ($j = 0; $j<$nRowItem; $j++){ 
								$itemName = $adb->query_result($resultDItems, $j, 'name');
								$num = $adb->query_result($resultDItems, $j, 'num');
								$items[] = array('name' => $itemName, "count" => $num);
						}
				}


			$r = 6;
			foreach ($items as $item){
				$r ++;
				$itemName = trim($item['name']);
				$ruName = $this->getDictionary($itemName);
				
				$itemCount = $item['count'];
				$worksheet->setCellValueByColumnAndRow(1, $r, $itemName);
				$worksheet->setCellValueByColumnAndRow(2, $r, $ruName);
				$worksheet->setCellValueByColumnAndRow(3, $r, $itemCount);
			}

 
			header('Content-Type: application/vnd.ms-excel');
			header("Content-Disposition: attachment;filename=Customs Inventory.xls");
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

			$loadUrl = "index.php?module=PackingList&view=Detail&record=".$request->get('record');
					echo '<script> 
				var url= "'.$loadUrl.'"; 
				window.location = url; 
			</script>';
	}



/* 	public function excelprint(Vtiger_Request $request) {

		global $adb;
		$recordId = $request->get('record');
		$recordModel = Vtiger_Record_model::getInstanceById($recordId, 'ExitInterview');
		require_once("libraries/PHPExcel/PHPExcel.php");
			$objReader = PHPExcel_IOFactory::createReader('Excel2007');
			$workbook = $objReader->load("include/ExitInterview/exitInterviewReport.xlsx");
			//$workbook = new PHPExcel();
			$worksheet = $workbook->setActiveSheetIndex(0);


			$sqlH = "SELECT vtiger_exitinterview.name, vtiger_exitinterview.exitinterviewid, vtiger_crmentity.createdtime, vtiger_exitinterviewcf.cf_7466, 
											vtiger_exitinterviewcf.cf_7494, vtiger_exitinterviewcf.cf_7472, vtiger_positiontitle.name as `position`
			FROM vtiger_exitinterviewcf 
			INNER JOIN vtiger_exitinterview ON vtiger_exitinterviewcf.exitinterviewid = vtiger_exitinterview.exitinterviewid 
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_exitinterviewcf.exitinterviewid 
			LEFT JOIN vtiger_positiontitle ON vtiger_positiontitle.positiontitleid = vtiger_exitinterviewcf.cf_7466
			WHERE vtiger_crmentity.deleted = ?";
			$resultH = $adb->pquery($sqlH, array(0));
			$num_rows = $adb->num_rows($resultH);

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

			$worksheet->setCellValueByColumnAndRow(4, $r, $totalLow.'('.(round($totalLow / $overAllTotal) * 100).'%)');	
			$worksheet->setCellValueByColumnAndRow(5, $r, $totalOffice.'('.(round($totalOffice / $overAllTotal) * 100).'%)');	
			$worksheet->setCellValueByColumnAndRow(6, $r, $totalManamagent.'('.(round($totalManamagent / $overAllTotal) * 100).'%)');	
			$worksheet->setCellValueByColumnAndRow(7, $r, $totalLong.'('.(round($totalLong / $overAllTotal) * 100).'%)');	
			$worksheet->setCellValueByColumnAndRow(8, $r, $totalOther.'('.(round($totalOther / $overAllTotal) * 100).'%)');	

			

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
	} */
	
	
}
