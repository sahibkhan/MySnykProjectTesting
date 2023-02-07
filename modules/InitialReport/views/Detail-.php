<?php

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class InitialReport_Detail_View extends Vtiger_Detail_View {

	function __construct() {
		parent::__construct();
	}

	public function showModuleDetailView(Vtiger_Request $request) {
		$recordId = $request->get('record');
		$moduleName = $request->getModule();

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


	
}
