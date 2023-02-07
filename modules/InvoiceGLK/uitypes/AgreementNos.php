<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
class InvoiceGLK_AgreementNos_UIType extends Vtiger_Base_UIType {
	/**
	 * Function to get the Template name for the current UI Type Object
	 * @return <String> - Template Name
	 */
	public function getTemplateName() {
		return 'uitypes/AgreementNos.tpl';
	}

public function getDisplayValue($value) {
		$db = PearDatabase::getInstance();
		
		//$result = $db->pquery('SELECT jobid, cf_1198 FROM `vtiger_jobcf` WHERE jobid = ? ',
					//array($value));
		$result = $db->pquery('SELECT vtiger_serviceagreement.name as agreement_no FROM `vtiger_serviceagreement` WHERE serviceagreementid = ? ',
					array($value));
					
		if($db->num_rows($result)) {
			 $job_ref_no = $db->query_result($result, 0, 'agreement_no');
			 $value = $job_ref_no;
		}
		return $value;
	}

}
?>
