<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Jobexpencereport_QuickCreateAjax_View extends Vtiger_QuickCreateAjax_View {

	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();

		if (!(Users_Privileges_Model::isPermitted($moduleName, 'EditView'))) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED', $moduleName));
		}
	}

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();

		$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
		$moduleModel = $recordModel->getModule();
		
		$fieldList = $moduleModel->getFields();
		$requestFieldList = array_intersect_key($request->getAll(), $fieldList);

		foreach($requestFieldList as $fieldName => $fieldValue){
			$fieldModel = $fieldList[$fieldName];
			if($fieldModel->isEditable()) {
				$recordModel->set($fieldName, $fieldModel->getDBInsertValue($fieldValue));
			}
		}

		//$recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_QUICKCREATE);
		$recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_EDIT);
		$picklistDependencyDatasource = Vtiger_DependencyPicklist::getPicklistDependencyDatasource($moduleName);

		$viewer = $this->getViewer($request);
		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE',Zend_Json::encode($picklistDependencyDatasource));
		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('SINGLE_MODULE', 'SINGLE_'.$moduleName);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
		$viewer->assign('RECORD_STRUCTURE', $recordStructureInstance->getStructure());
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		
		$viewer->assign('SCRIPTS', $this->getHeaderScripts($request));
		
		$jrertype = $request->get('jrertype');
		
		$viewer->assign('JRER_TYPE', ($jrertype=='expence') ? 'selling' : 'expence' );
		
		$job_id = $request->get('sourceRecord');
		include("include/Exchangerate/exchange_rate_class.php");
		$job_info = get_job_details($job_id);
		//$reporting_currency = get_company_details(@$job_info['cf_1186'], 'currency');
		$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info['cf_1186']);
		$file_title_currency = $reporting_currency;		
		$viewer->assign('FILE_TITLE_CURRENCY', $file_title_currency);
		if($file_title_currency!='USD')
		{			
			$final_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, date('Y-m-d'));			
		}else{
			$final_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, date('Y-m-d'));
		}
		$viewer->assign('FINAL_EXCHANGE_RATE' , number_format ( $final_exchange_rate , 2 ,  "." , "," ));
		
		$ref_no = @$job_info['cf_1198'];		
		$viewer->assign('REFERENCE_NO' , $ref_no);
		
		echo $viewer->view('QuickCreate.tpl',$moduleName,true);

	}
	
	
	public function getHeaderScripts(Vtiger_Request $request) {
		
		$moduleName = $request->getModule();
		
		$jsFileNames = array(
			"modules.$moduleName.resources.Edit"
			//,"modules.$moduleName.resources.Exchangerate"
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return $jsScriptInstances;
	}
}