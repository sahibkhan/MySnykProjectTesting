<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
class Procurement_DepartmentList_UIType extends Vtiger_DepartmentList_UIType {
	/**
	 * Function to get the Template name for the current UI Type Object
	 * @return <String> - Template Name
	 */
	public function getTemplateName() {
		return 'uitypes/DepartmentList.tpl';
	}

	public function getDisplayValue($value, $record=false, $recordInstance=false) {
		//$assign_department_id = $value;	
		//$value = explode(' |##| ', $value);
		//$final_assign_department = implode('","', $value); 

		if($_REQUEST['module']=='FSLBlack')
		{
			$value = explode(',', $value);
			$final_assign_department = implode('","', $value); 
			//$field_module = 'DepartmentData_Module';
			//$name = Vtiger_Cache::get('DepartmentData_Module' . $value, $value);

		}else{
			$value = explode(' |##| ', $value);
			$final_assign_department = implode('","', $value); 
			//$field_module = 'DepartmentData';
			//$name = Vtiger_Cache::get('DepartmentData' . $value, $value);
		}
		//if ($name) {
		//	return $name;
		//}
		 	
		$db = PearDatabase::getInstance();
		$result = $db->pquery('SELECT department.departmentid, name, cf_1542 FROM vtiger_department as department 
							   INNER JOIN vtiger_departmentcf as departmentcf ON departmentcf.departmentid=department.departmentid
		 					   WHERE departmentcf.departmentid IN("'.$final_assign_department.'")' , array());
		//array('Active', $value)
		$name = false;				
		if($db->num_rows($result)) {
			$num_rows = $db->num_rows($result);
	   		for($i = 0; $i<$num_rows; $i++) {				
				$assigned_arr[] = $db->query_result($result, $i, 'cf_1542');
			}
			$value = implode(' |##| ', $assigned_arr);
			return $value;
			//$name = implode(' |##| ', $assigned_arr);
		}
		return $value;
		//Vtiger_Cache::set($field_module . $value, $value, $name);
		//return $name;
	}

	public function getFSL($fsl_id) {
		//$assign_department_id = $value;	
		$db = PearDatabase::getInstance();
		
		$result_fsl = $db->query('SELECT * from vtiger_fslblackcf WHERE  fslblackid="'.$fsl_id.'" ');
		$fsl_arr = $db->fetch_array($result_fsl);
		$value = $fsl_arr['cf_6448'];
		

		$value = explode(',', $value);
		$final_assign_department = implode('","', $value); 
			
	
		$result = $db->pquery('SELECT department.departmentid, name, cf_1542 FROM vtiger_department as department 
							   INNER JOIN vtiger_departmentcf as departmentcf ON departmentcf.departmentid=department.departmentid
		 					   WHERE departmentcf.departmentid IN("'.$final_assign_department.'")' , array());
		//array('Active', $value)
					
		if($db->num_rows($result)) {
			$num_rows = $db->num_rows($result);
	   		for($i = 0; $i<$num_rows; $i++) {				
				$assigned_arr[] = $db->query_result($result, $i, 'cf_1542');
			}
			$value = implode(' |##| ', $assigned_arr);
			return $value;
		}
		return $value;
	}
	
	public function getDepartment($value)
	{
		$db = PearDatabase::getInstance();
		
		$result = $db->pquery('SELECT cf_1544 FROM vtiger_departmentcf WHERE departmentid = ? ',
					array($value));
					
		if($db->num_rows($result)) {
			return $db->query_result($result, 0, 'cf_1544');
		}
		return $value;
	}
	public function getListSearchTemplateName() {
        return 'uitypes/DepartmentListFieldSearchView.tpl';
    }
}
?>
