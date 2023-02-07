<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class DailyTimeSheet_Detail_View extends Vtiger_Detail_View {
	
	function __construct() {
		parent::__construct();
	}

	function process(Vtiger_Request $request) {

		
		$mode = $request->getMode();
		
		if(!empty($mode)) {
			echo $this->invokeExposedMethod($mode, $request);
			return;
		}
		
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		
		if ($currentUserModel->get('default_record_view') === 'Summary') {
			echo $this->showModuleBasicView($request);
		} else {
				
			echo $this->showModuleDetailView($request);
		}
	}


	/**
	 * Function shows basic detail for the record
	 * @param <type> $request
	 */
	function showModuleBasicView($request) {

		$recordId = $request->get('record');
		$moduleName = $request->getModule();

		if(!$this->record){
			$this->record = Vtiger_DetailView_Model::getInstance($moduleName, $recordId);
		}
		$recordModel = $this->record->getRecord();

		$detailViewLinkParams = array('MODULE'=>$moduleName,'RECORD'=>$recordId);
		$detailViewLinks = $this->record->getDetailViewLinks($detailViewLinkParams);

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('MODULE_SUMMARY', $this->showModuleSummaryView($request));

		$viewer->assign('DETAILVIEW_LINKS', $detailViewLinks);
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->assign('IS_AJAX_ENABLED', $this->isAjaxEnabled($recordModel));
		$viewer->assign('MODULE_NAME', $moduleName);

		$recordStrucure = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_DETAIL);
		$structuredValues = $recordStrucure->getStructure();

        $moduleModel = $recordModel->getModule();

		$viewer->assign('DT_LIST', $this->getDailyTimeSheetInfo($recordId));

		$viewer->assign('RECORD_STRUCTURE', $structuredValues);
        $viewer->assign('BLOCK_LIST', $moduleModel->getBlocks());

		echo $viewer->view('DetailViewSummaryContents.tpl', $moduleName, true);
	}

	public function showModuleDetailView(Vtiger_Request $request) {
		$recordId = $request->get('record');
		$moduleName = $request->getModule();

		$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
		$viewer = $this->getViewer($request);

		//$viewer->assign('IMAGE_DETAILS', $recordModel->getImageDetails());

		$viewer->assign('DT_LIST', $this->getDailyTimeSheetInfo($recordId));

		return parent::showModuleDetailView($request);
	}
	
	function showModuleSummaryView($request) {
		$recordId = $request->get('record');
		$moduleName = $request->getModule();

		if(!$this->record){
			$this->record = Vtiger_DetailView_Model::getInstance($moduleName, $recordId);
		}
		$recordModel = $this->record->getRecord();
		$recordStrucure = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_SUMMARY);
		

        $moduleModel = $recordModel->getModule();

		$viewer = $this->getViewer($request);

		$viewer->assign('RECORD', $recordModel);

        $viewer->assign('BLOCK_LIST', $moduleModel->getBlocks());
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());

		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('IS_AJAX_ENABLED', $this->isAjaxEnabled($recordModel));
		$viewer->assign('SUMMARY_RECORD_STRUCTURE', $recordStrucure->getStructure());
		$viewer->assign('RELATED_ACTIVITIES', $this->getActivities($request));
		
		
		
		return $viewer->view('ModuleSummaryView.tpl', $moduleName, true);
	}
	
	function isAjaxEnabled($recordModel) {
		// $currentusermodel = users_record_model::getcurrentusermodel();
		// if ($currentusermodel->isadminuser() == true) {
		// 	return true;
		// } else {
		// 	return false;
		// }
		return false;
	}

	
	// get employee's work info
	private function getDailyTimeSheetInfo($sourcerecordid)
	{
		$db = PearDatabase::getInstance();
		//$id = $request->get('record');	
			$query = "SELECT * 
						FROM
							vtiger_dailytimesheettaskcf
							INNER JOIN vtiger_dailytimesheettask ON vtiger_dailytimesheettask.dailytimesheettaskid = vtiger_dailytimesheettaskcf.dailytimesheettaskid
							INNER JOIN vtiger_crmentity ON vtiger_dailytimesheettask.dailytimesheettaskid = vtiger_crmentity.crmid
							INNER JOIN vtiger_crmentityrel ON vtiger_dailytimesheettask.dailytimesheettaskid = vtiger_crmentityrel.relcrmid
						WHERE
							vtiger_crmentityrel.crmid = '$sourcerecordid' 
							AND vtiger_crmentity.deleted = 0 
						ORDER BY
							vtiger_dailytimesheettaskcf.dailytimesheettaskid ASC;"; 
			// echo $queryEdu; exit;
        	$result = $db->pquery($query, array());

				for($i=0;$i<$db->num_rows($result);$i++){
					$dailyTaskList[$i]['relatedrecordid'] = $db->query_result($result,$i,'dailytimesheettaskid');
					$dailyTaskList[$i]['entryType'] = $db->query_result($result,$i,'name'); 
					$dailyTaskList[$i]['taskType'] = $db->query_result($result,$i,'cf_6904');
					$dailyTaskList[$i]['description'] = $db->query_result($result,$i,'cf_6906');
					$dailyTaskList[$i]['quantity'] = $db->query_result($result,$i,'cf_6908');
					$dailyTaskList[$i]['hoursSpent'] = $db->query_result($result,$i,'cf_6910');
				}
			return $dailyTaskList;
	}
	// get employee's language info
	
}
