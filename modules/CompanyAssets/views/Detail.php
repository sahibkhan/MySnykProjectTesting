<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
/* ini_set('display_errors', 1);
error_reporting(E_ALL); */

class CompanyAssets_Detail_View extends Vtiger_Detail_View {
	protected $record = false;
	protected $isAjaxEnabled = null;

	function __construct() {
		parent::__construct();
		$this->exposeMethod('uploadAssets');
	}

	
	public function uploadAssets(Vtiger_Request $request){
		global $adb;
    $CREATEDBY_ID = 411; // larisa mametanova


    $queryAssets = $adb->pquery("SELECT * FROM vtiger_companyassets_from_1c ");    
    $nOfRecords = $adb->num_rows($queryAssets);

    for ($i=0; $i<$nOfRecords;$i++){
      $created_by = $adb->query_result($queryAssets, $i, 'created_by');
      $part_number = $adb->query_result($queryAssets, $i, 'part_number');
      $item_type = $adb->query_result($queryAssets, $i, 'item_type');
      $parent_category = $adb->query_result($queryAssets, $i, 'parent_category');
      $item_category = $adb->query_result($queryAssets, $i, 'item_category');
      $location = $adb->query_result($queryAssets, $i, 'location');
      $employee = $adb->query_result($queryAssets, $i, 'employee');
      $email = trim($adb->query_result($queryAssets, $i, 'email'));
      $item_name = $adb->query_result($queryAssets, $i, 'item_name');
      $department = $adb->query_result($queryAssets, $i, 'department');
      $inventory = $adb->query_result($queryAssets, $i, 'inventory');
      $given_date = $adb->query_result($queryAssets, $i, 'given_date');
    
      $queryEmployeeEmail = $adb->pquery("SELECT userlistid
                                   FROM vtiger_userlistcf
                                   WHERE cf_3355 = ?", array($email));  
      $nOfEmployees = $adb->num_rows($queryEmployeeEmail);
      
      if ($nOfEmployees > 0){
        $employeeId = $adb->query_result($queryEmployeeEmail, 0, 'userlistid');
      } else if ($nOfEmployees == 0){
        
        $queryEmployee = $adb->pquery("SELECT vtiger_userlist.userlistid 
                                       FROM vtiger_userlist
                                       INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_userlist.userlistid
                                       WHERE vtiger_crmentity.deleted = 0 AND vtiger_userlist.name = '".$employee."'");  
        $nOfEmployees2 = $adb->num_rows($queryEmployee);
        $employeeId = $adb->query_result($queryEmployee, 0, 'userlistid');

      }

      $locationId = $this->getLocationIdByCode($location);
      $departmentId = $this->getDepartmentIdByCode($department); 
      $givenDateFormat = date("Y-m-d", strtotime($given_date)); 
      
      $companyAssetModel = Vtiger_Record_Model::getCleanInstance('CompanyAssets');	
      $companyAssetModel->set('assigned_user_id', $CREATEDBY_ID);
      $companyAssetModel->set('mode', 'create');
      $companyAssetModel->set("name", $item_name);
      $companyAssetModel->set("cf_6190", $part_number);
      $companyAssetModel->set("cf_6192", $item_type);
      $companyAssetModel->set("cf_7054", $parent_category);
      $companyAssetModel->set("cf_6194", $item_category);
      $companyAssetModel->set("cf_6706", $locationId); 
      $companyAssetModel->set("cf_6708", $departmentId);
      $companyAssetModel->set("cf_6704", $inventory);
      $companyAssetModel->set("cf_6198", $givenDateFormat);
      $companyAssetModel->set("cf_6202", $employeeId);
      $companyAssetModel->set("cf_8366", 'Uploaded From 1C'); 
      $companyAssetModel->save();

      if ($employeeId > 0){
        $companyAssetId = $companyAssetModel->get('id');
        $insertRelation = $adb->pquery("INSERT INTO vtiger_crmentityrel(crmid, module, relcrmid, relmodule)
                                        VALUES (?, ?, ?, ?)",
                                        array($employeeId, 'UserList', $companyAssetId, 'CompanyAssets')); 
      }

    }
    exit;


	}


  function getLocationIdByCode($location){
    global $adb;
    $query = $adb->pquery("SELECT locationid
                           FROM vtiger_locationcf
                           WHERE cf_1559 = ?", array($location)); 
    $locationId = $adb->query_result($query, 0, 'locationid');
    return $locationId;
  }

  function getDepartmentIdByCode($department){
    global $adb;
    $query = $adb->pquery("SELECT departmentid
                           FROM vtiger_departmentcf
                           WHERE cf_1542 = ?", array($department)); 
    $departmentid = $adb->query_result($query, 0, 'departmentid');
    return $departmentid;
  }


}
