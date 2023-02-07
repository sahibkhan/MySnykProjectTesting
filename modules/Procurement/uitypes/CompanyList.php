<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
class Procurement_CompanyList_UIType extends Vtiger_CompanyList_UIType {
	/**
	 * Function to get the Template name for the current UI Type Object
	 * @return <String> - Template Name
	 */
	public function getTemplateName() {
		return 'uitypes/CompanyList.tpl';
	}

	public function getDisplayValue($value, $record=false, $recordInstance=false) {
		
		$assign_company_id= explode(' |##| ', $value);
		$final_assign_company = implode('","', $assign_company_id);
		
		//module=VPO&view=Detail	
		if(($_REQUEST['module']=='VPO' || $_REQUEST['module']=='BO')  && $_REQUEST['view']=='Detail')
		{
			$fieldname = $this->get('field')->get('column');
			if($fieldname == 'cf_1375' || $fieldname =='cf_1581')
			{
				$db = PearDatabase::getInstance();
				$result = $db->pquery('SELECT companyid, name FROM vtiger_company WHERE companyid IN("'.$final_assign_company.'") ',
							array());
							
				if($db->num_rows($result)) {
					//return $db->query_result($result, 0, 'cf_996');
					$num_rows = $db->num_rows($result);
					for($i = 0; $i<$num_rows; $i++) {				
						$assigned_arr[] = $db->query_result($result, $i, 'name');
					}
					$value = implode(' |##| ', $assigned_arr);
					return $value;
				}
			}
			else{
				return $value;
			}
		}else{

			//$name = Vtiger_Cache::get('CompanyData' . $value, $value);
			
			//if ($name) {
			//	return $name;
			//}
					
			$db = PearDatabase::getInstance();
			$result = $db->pquery('SELECT companyid, cf_996 FROM vtiger_companycf WHERE companyid IN("'.$final_assign_company.'") ',
						array());
			
			$name = false;					
			if($db->num_rows($result)) {
				//return $db->query_result($result, 0, 'cf_996');
				$num_rows = $db->num_rows($result);
				for($i = 0; $i<$num_rows; $i++) {				
					$assigned_arr[] = $db->query_result($result, $i, 'cf_996');
				}
				$value = implode(' |##| ', $assigned_arr);
				//$name = implode(' |##| ', $assigned_arr);
				return $value;
			}
			//Vtiger_Cache::set('CompanyData' . $value, $value, $name);
			//return $name;
		}
		return $value;
	}
	
	public function getCompanyReportingCurrency($value)
	{
		$db = PearDatabase::getInstance();
			
		$result = $db->pquery('SELECT currency.currency_code FROM vtiger_companycf as company 
							   INNER JOIN vtiger_currency_info as currency ON currency.id = company.cf_1459 
							   where company.companyid = ? ',
					array($value));
					
		if($db->num_rows($result)) {
			return $db->query_result($result, 0, 'currency_code');
		}
		return $value;
	}
	
	public function getCompanyReportingCurrencyID($value)
	{
		$db = PearDatabase::getInstance();
			
		$result = $db->pquery('SELECT currency.id FROM vtiger_companycf as company 
							   INNER JOIN vtiger_currency_info as currency ON currency.id = company.cf_1459 
							   where company.companyid = ? ',
					array($value));
					
		if($db->num_rows($result)) {
			return $db->query_result($result, 0, 'id');
		}
		return $value;
	}
	
	public function getListSearchTemplateName() {
        return 'uitypes/CompanyListFieldSearchView.tpl';
    }
}
?>
