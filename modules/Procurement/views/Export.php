<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Procurement_Export_View extends Vtiger_Export_View {

	public function requiresPermission(\Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'Export');
		
		return $permissions;
	}

	function process(Vtiger_Request $request) {
		global $adb;
		$viewer = $this->getViewer($request);

		$source_module = $request->getModule();
		$viewId = $request->get('viewname');
		$selectedIds = $request->get('selected_ids');
		$excludedIds = $request->get('excluded_ids');
		$orderBy = $request->get('orderby');
		$sortOrder = $request->get('sortorder');
		$tagParams = $request->get('tag_params');
		$page = $request->get('page');

		$viewer->assign('SELECTED_IDS', $selectedIds);
		$viewer->assign('EXCLUDED_IDS', $excludedIds);
		$viewer->assign('VIEWID', $viewId);
		$viewer->assign('PAGE', $page);
		$viewer->assign('SOURCE_MODULE', $source_module);
		$viewer->assign('MODULE','Export');
		$viewer->assign('ORDER_BY', $orderBy);
		$viewer->assign('SORT_ORDER', $sortOrder);
		$viewer->assign('TAG_PARAMS', $tagParams);
		
		//assign filter of procurement types & location
		$procurement_types = $adb->pquery("SELECT * FROM `vtiger_procurementtypes` 
	inner join  `vtiger_crmentity` on vtiger_crmentity.`crmid` = `vtiger_procurementtypes`.procurementtypesid 
	where vtiger_crmentity.deleted=0");
	$procurementTypes = array();
	for($j=0; $j<$adb->num_rows($procurement_types); $j++) { 
		$row = $adb->query_result_rowdata($procurement_types, $j);
		$procurementTypes[$row['procurementtypesid']] = $row['name'];
	}
		$locations = $adb->pquery("SELECT * FROM `vtiger_locationcf` 
	inner join  `vtiger_crmentity` on vtiger_crmentity.`crmid` = `vtiger_locationcf`.locationid 
	where vtiger_crmentity.deleted=0");
	$locationsList = array();
		for($k=0; $k<$adb->num_rows($locations); $k++) { 
			$row = $adb->query_result_rowdata($locations, $k);
			$locationsList[$row['locationid']] = $row['cf_1559'];
		}
		$viewer->assign('procurementTypes', $procurementTypes);
		$viewer->assign('locationsList', $locationsList);
		//end assign filter

         // for the option of selecting currency while exporting inventory module records
        if(in_array($source_module, Vtiger_Functions::getLineItemFieldModules())){
           $viewer->assign('MULTI_CURRENCY',true);
        }
        
        $searchKey = $request->get('search_key');
        $searchValue = $request->get('search_value');
		$operator = $request->get('operator');
        if(!empty($operator)) {
			$viewer->assign('OPERATOR',$operator);
			$viewer->assign('ALPHABET_VALUE',$searchValue);
            $viewer->assign('SEARCH_KEY',$searchKey);
		}
		$viewer->assign('SUPPORTED_FILE_TYPES', array('csv', 'ics'));
		$viewer->assign('SEARCH_PARAMS', $request->get('search_params'));
		$viewer->view('Export.tpl', $source_module);
	}

	function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);

		$moduleName = $request->getModule();
		if (in_array($moduleName, getInventoryModules())) {
			$moduleEditFile = 'modules.'.$moduleName.'.resources.Edit';
			unset($headerScriptInstances[$moduleEditFile]);

			$jsFileNames = array(
				'modules.Inventory.resources.Edit',
				'modules.'.$moduleName.'.resources.Edit',
			);
		}

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}