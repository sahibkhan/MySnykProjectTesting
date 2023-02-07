<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

include_once 'modules/Vtiger/CRMEntity.php';

class OrgChart extends Vtiger_CRMEntity {
	var $table_name = 'vtiger_orgchart';
	var $table_index= 'orgchartid';

	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('vtiger_orgchartcf', 'orgchartid');

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array('vtiger_crmentity', 'vtiger_orgchart', 'vtiger_orgchartcf');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_orgchart' => 'orgchartid',
		'vtiger_orgchartcf'=>'orgchartid');

	/**
	 * Mandatory for Listing (Related listview)
	 */
	var $list_fields = Array (
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'Name' => Array('orgchart', 'name'),
		'Assigned To' => Array('crmentity','smownerid')
	);
	var $list_fields_name = Array (
		/* Format: Field Label => fieldname */
		'Name' => 'name',
		'Assigned To' => 'assigned_user_id',
	);

	// Make the field link to detail view
	var $list_link_field = 'name';

	// For Popup listview and UI type support
	var $search_fields = Array(
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'Name' => Array('orgchart', 'name'),
		'Assigned To' => Array('vtiger_crmentity','assigned_user_id'),
	);
	var $search_fields_name = Array (
		/* Format: Field Label => fieldname */
		'Name' => 'name',
		'Assigned To' => 'assigned_user_id',
	);

	// For Popup window record selection
	var $popup_fields = Array ('name');

	// For Alphabetical search
	var $def_basicsearch_col = 'name';

	// Column value to use on detail view record text display
	var $def_detailview_recname = 'name';

	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vtiger_field.fieldname values.
	var $mandatory_fields = Array('name','assigned_user_id');

	var $default_order_by = 'name';
	var $default_sort_order='ASC';

	// function OrgChart(){
		
	// 	$db = PearDatabase::getInstance();
	// 	$jsontext = "var datascource = {
	// 		'name': 'CEO',
	// 		'userlistid': '412373',
	// 		'title': 'Siddique Khan',
	// 		'children': [
	// 			{
	// 				'name': 'KZ',
	// 				'title': 'Kazakhstan',
	// 				'children': [";
		
	// 	// getting cities list
	// 	$query_city = "SELECT DISTINCT vtiger_userlistcf.cf_3421
	// 								 FROM vtiger_userlistcf
	// 								 WHERE vtiger_userlistcf.cf_3353 = 'KZ'
	// 								 AND vtiger_userlistcf.cf_3421 != ''";

	// 	$result_city = $db->pquery($query_city);

	// 	for($ci=0; $ci<$db->num_rows($result_city); $ci++){
	// 		$city_row = $db->query_result_rowdata($result_city, $ci);
	// 		$city_name = $city_row['cf_3421'];
	// 		if($city_name == 'Almaty'){
	// 			$jsontext .= "
	// 					{
	// 						'name': '$city_name',
	// 						'title': 'Head office',
	// 						'children': [";
				
	// 			// getting Almaty departments list
	// 			$query = "SELECT DISTINCT vtiger_userlistcf.cf_3349
	// 								FROM vtiger_userlistcf
	// 								WHERE cf_3353 = 'KZ'
	// 								AND vtiger_userlistcf.cf_3421 = 'Almaty'
	// 								AND vtiger_userlistcf.cf_3349 != ''";

	// 			$result = $db->pquery($query);

	// 			for($i=0; $i<$db->num_rows($result); $i++) {
	// 				$row = $db->query_result_rowdata($result, $i);
	// 				$department = $row['cf_3349'];
	// 				$jsontext .= "
	// 							{
	// 								'name': '$department',
	// 								'children': [";
					
	// 				// getting Almaty departments' heads
	// 				$query_head = "SELECT vtiger_userlist.name,
	// 															vtiger_userlistcf.userlistid,
	// 															vtiger_userlistcf.cf_3341,
	// 															vtiger_userlistcf.cf_3387
	// 											 FROM vtiger_userlistcf
	// 											 INNER JOIN vtiger_userlist
	// 											 ON vtiger_userlist.userlistid = vtiger_userlistcf.userlistid
	// 											 LEFT JOIN vtiger_crmentity
	// 											 ON vtiger_userlistcf.userlistid = vtiger_crmentity.crmid
	// 											 WHERE vtiger_crmentity.deleted != 1
	// 											 AND vtiger_userlistcf.cf_3349 = '$department'
	// 											 AND vtiger_userlistcf.cf_3353 = 'KZ'
	// 											 AND (vtiger_userlistcf.cf_3387 = 412373 OR vtiger_userlistcf.cf_3341 = 'Chief Accountant')";
					
	// 				$result_head = $db->pquery($query_head);

	// 				for($j=0; $j<$db->num_rows($result_head); $j++) {
	// 					$row1 = $db->query_result_rowdata($result_head, $j);
	// 					$head_name = $row1['name'];
	// 					$head_id = $row1['userlistid'];
	// 					$head_position = $row1['cf_3341'];
	// 					$head_parent = $row1['cf_3387'];
	// 					// echo $head_name."<br/>";
	// 					$jsontext .= "
	// 									{
	// 										'name': '$head_position',
	// 										'userlistid': '$head_id',
	// 										'title': '$head_name',
	// 										'parentId': '$head_parent',
	// 										'children': [";

	// 					// getting Almaty employees list
	// 					$query_employee = "SELECT vtiger_userlist.name,
	// 																		vtiger_userlistcf.userlistid,
	// 																		vtiger_userlistcf.cf_3341,
	// 																		vtiger_userlistcf.cf_3387
	// 													 FROM vtiger_userlistcf
	// 													 INNER JOIN vtiger_userlist
	// 													 ON vtiger_userlist.userlistid = vtiger_userlistcf.userlistid
	// 													 LEFT JOIN vtiger_crmentity
	// 													 ON vtiger_userlistcf.userlistid = vtiger_crmentity.crmid
	// 													 WHERE vtiger_crmentity.deleted != 1
	// 													 AND vtiger_userlistcf.cf_3349 = '$department'
	// 													 AND vtiger_userlistcf.cf_3387 = $head_id
	// 													 AND vtiger_userlistcf.cf_3341 NOT LIKE '%Branch manager%'";

	// 					$result_employee = $db->pquery($query_employee);

	// 					for($h=0; $h<$db->num_rows($result_employee); $h++) {
	// 						$row_employee = $db->query_result_rowdata($result_employee, $h);
	// 						$semployee_name = $row_employee['name'];
	// 						$semployee_id = $row_employee['userlistid'];
	// 						$semployee_position = $row_employee['cf_3341'];
	// 						$semployee_parent = $row_employee['cf_3387'];
	// 						$jsontext .= "
	// 											{
	// 												'name': '$semployee_position',
	// 												'userlistid': '$semployee_id',
	// 												'title': '$semployee_name',
	// 												'parentId': '$semployee_parent',
	// 												'children': [";

	// 						// getting ordinary list
	// 						$query_ordinary = "SELECT vtiger_userlist.name,
	// 																			vtiger_userlistcf.userlistid,
	// 																			vtiger_userlistcf.cf_3341,
	// 																			vtiger_userlistcf.cf_3387
	// 															 FROM vtiger_userlistcf
	// 															 INNER JOIN vtiger_userlist
	// 															 ON vtiger_userlist.userlistid = vtiger_userlistcf.userlistid
	// 															 LEFT JOIN vtiger_crmentity
	// 															 ON vtiger_userlistcf.userlistid = vtiger_crmentity.crmid
	// 															 WHERE vtiger_crmentity.deleted != 1
	// 															 AND vtiger_userlistcf.cf_3349 = '$department'
	// 															 AND vtiger_userlistcf.cf_3387 = $semployee_id";

	// 						$result_ordinary = $db->pquery($query_ordinary);
	// 						for($h1=0; $h1<$db->num_rows($result_ordinary); $h1++){
	// 							$row_ordinary = $db->query_result_rowdata($result_ordinary, $h1);
	// 							$ordinary_name = $row_ordinary['name'];
	// 							$ordinary_id = $row_ordinary['userlistid'];
	// 							$ordinary_position = $row_ordinary['cf_3341'];
	// 							$ordinary_parent = $row_ordinary['cf_3387'];
	// 							$jsontext .= "
	// 													{
	// 														'name': '$ordinary_position',
	// 														'userlistid': '$ordinary_id',
	// 														'title': '$ordinary_name',
	// 														'parentId': '$ordinary_parent'
	// 													},";
	// 						}
	// 						$jsontext .= "
	// 												]
	// 											},";
	// 					}
	// 					$jsontext .= "
	// 										]
	// 									},";
	// 				}
	// 				$jsontext .= "
	// 								]
	// 							},";
	// 			}
	// 			$jsontext .= "
	// 						]
	// 					},";
	// 		} else {
	// 			$jsontext .= "
	// 					{
	// 						'name': '".$city_name."',
	// 						'title':'Branch office',
	// 						'children': [";

	// 			// getting branch head
	// 			$query_kz_branch_head = "SELECT vtiger_userlist.name,
	// 																			vtiger_userlistcf.userlistid,
	// 																			vtiger_userlistcf.cf_3341,
	// 																			vtiger_userlistcf.cf_3387
	// 															 FROM vtiger_userlistcf
	// 															 INNER JOIN vtiger_userlist
	// 															 ON vtiger_userlist.userlistid = vtiger_userlistcf.userlistid
	// 															 LEFT JOIN vtiger_crmentity
	// 															 ON vtiger_userlistcf.userlistid = vtiger_crmentity.crmid
	// 															 WHERE vtiger_crmentity.deleted != 1
	// 															 AND vtiger_userlistcf.cf_3421 = '$city_name'
	// 															 AND vtiger_userlistcf.cf_3341 LIKE '%Branch manager%'
	// 															 AND vtiger_userlistcf.cf_3353 = 'KZ'";
				
	// 			$result_kz_branch_head = $db->pquery($query_kz_branch_head);
	// 			for($bkh=0; $bkh<$db->num_rows($result_kz_branch_head); $bkh++){
	// 				$row_kz_branch_head = $db->query_result_rowdata($result_kz_branch_head, $bkh);
	// 				$branch_kz_head_name = $row_kz_branch_head['name'];
	// 				$branch_kz_head_id = $row_kz_branch_head['userlistid'];
	// 				$branch_kz_head_position = $row_kz_branch_head['cf_3341'];
	// 				$branch_kz_head_parent = $row_kz_branch_head['cf_3387'];
	// 				$jsontext .= "
	// 							{
	// 								'name': '$branch_kz_head_position',
	// 								'userlistid': '$branch_kz_head_id',
	// 								'title': '$branch_kz_head_name',
	// 								'parentId': '$branch_kz_head_parent',
	// 								'children': [";
					
	// 				// getting departments' list
	// 				$query_kz_branch_dep = "SELECT DISTINCT vtiger_userlistcf.cf_3349
	// 																FROM vtiger_userlistcf
	// 																WHERE vtiger_userlistcf.cf_3421 = '$city_name'
	// 																AND vtiger_userlistcf.cf_3349 != ''";
					
	// 				$result_branch_kz_dep = $db->pquery($query_kz_branch_dep);

	// 				for($bkzdep=0; $bkzdep<$db->num_rows($result_branch_kz_dep); $bkzdep++){
	// 					$row_kz_branch_dep = $db->query_result_rowdata($result_branch_kz_dep, $bkzdep);
	// 					$branch_kz_dep_name = $row_kz_branch_dep['cf_3349'];
	// 					$jsontext .= "
	// 									{
	// 										'name': '$branch_kz_dep_name',
	// 										'children': [";
						
	// 					// getting department head
	// 					$query_kz_branch_employee = "SELECT vtiger_userlist.name,
	// 																							vtiger_userlistcf.userlistid,
	// 																							vtiger_userlistcf.cf_3341,
	// 																							vtiger_userlistcf.cf_3387
	// 																			 FROM vtiger_userlistcf
	// 																			 INNER JOIN vtiger_userlist
	// 																			 ON vtiger_userlist.userlistid = vtiger_userlistcf.userlistid
	// 																			 LEFT JOIN vtiger_crmentity
	// 																			 ON vtiger_userlistcf.userlistid = vtiger_crmentity.crmid
	// 																			 WHERE vtiger_crmentity.deleted != 1
	// 																			 AND vtiger_userlistcf.cf_3421 = '$city_name'
	// 																			 AND vtiger_userlistcf.cf_3387 = '$branch_kz_head_id'
	// 																			 AND vtiger_userlistcf.cf_3349 = '$branch_kz_dep_name'";
						
	// 					$result_kz_branch_employee = $db->pquery($query_kz_branch_employee);

	// 					for($bkze=0; $bkze<$db->num_rows($result_kz_branch_employee); $bkze++) {
	// 						$row_kz_branch_employee = $db->query_result_rowdata($result_kz_branch_employee, $bkze);
	// 						$b_kz_emp_position = $row_kz_branch_employee['cf_3341'];
	// 						$b_kz_emp_id = $row_kz_branch_employee['userlistid'];
	// 						$b_kz_emp_name = $row_kz_branch_employee['name'];
	// 						$b_kz_emp_parent = $row_kz_branch_employee['cf_3387'];
	// 						$jsontext .= "
	// 											{
	// 												'name': '$b_kz_emp_position',
	// 												'userlistid': '$b_kz_emp_id',
	// 												'title': '$b_kz_emp_name',
	// 												'parentId': '$b_kz_emp_parent',
	// 												'children': [";

	// 						// getting employees list
	// 						$query_kz_branch_ordinary = "SELECT vtiger_userlist.name,
	// 																								vtiger_userlistcf.userlistid,
	// 																								vtiger_userlistcf.cf_3341,
	// 																								vtiger_userlistcf.cf_3387
	// 																				 FROM vtiger_userlistcf
	// 																				 INNER JOIN vtiger_userlist
	// 																				 ON vtiger_userlist.userlistid = vtiger_userlistcf.userlistid
	// 																				 LEFT JOIN vtiger_crmentity
	// 																				 ON vtiger_userlistcf.userlistid = vtiger_crmentity.crmid
	// 																				 WHERE vtiger_crmentity.deleted != 1
	// 																				 AND vtiger_userlistcf.cf_3421 = '$city_name'
	// 																				 AND vtiger_userlistcf.cf_3387 = '$b_kz_emp_id'";
							
	// 						$result_kz_branch_ordinary = $db->pquery($query_kz_branch_ordinary);

	// 						for ($bkzo=0; $bkzo<$db->num_rows($result_kz_branch_ordinary); $bkzo++) {
	// 							$row_kz_branch_ordinary = $db->query_result_rowdata($result_kz_branch_ordinary, $bkzo);
	// 							$b_kz_ordinary_position = $row_kz_branch_ordinary['cf_3341'];
	// 							$b_kz_ordinary_id = $row_kz_branch_ordinary['userlistid'];
	// 							$b_kz_ordinary_name = $row_kz_branch_ordinary['name'];
	// 							$b_kz_ordinary_parent = $row_kz_branch_ordinary['cf_3387'];
	// 							$jsontext .= "
	// 													{
	// 														'name': '$b_kz_ordinary_position',
	// 														'userlistid': '$b_kz_ordinary_id',
	// 														'title': '$b_kz_ordinary_name',
	// 														'parentId': '$b_kz_ordinary_parent',
	// 														'children': [";

	// 							$query_kz_branch_assistants = "SELECT vtiger_userlist.name,
	// 																										vtiger_userlistcf.userlistid,
	// 																										vtiger_userlistcf.cf_3341,
	// 																										vtiger_userlistcf.cf_3387
	// 																						 FROM vtiger_userlistcf
	// 																						 INNER JOIN vtiger_userlist
	// 																						 ON vtiger_userlist.userlistid = vtiger_userlistcf.userlistid 
	// 																						 LEFT JOIN vtiger_crmentity
	// 																						 ON vtiger_userlistcf.userlistid = vtiger_crmentity.crmid
	// 																						 WHERE vtiger_crmentity.deleted != 1
	// 																						 AND vtiger_userlistcf.cf_3421 = '$city_name'
	// 																						 AND  vtiger_userlistcf.cf_3387 = '$b_kz_ordinary_id'";

	// 							$result_kz_branch_assistants = $db->pquery($query_kz_branch_assistants);

	// 							for ($bkza=0; $bkza<$db->num_rows($result_kz_branch_assistants); $bkza++){
	// 								$row_kz_branch_assistant = $db->query_result_rowdata($result_kz_branch_assistants, $bkza);
	// 								$b_kz_assistant_position = $row_kz_branch_assistant['cf_3341'];
	// 								$b_kz_assistant_id = $row_kz_branch_assistant['userlistid'];
	// 								$b_kz_assistant_name = $row_kz_branch_assistant['name'];
	// 								$b_kz_assistant_parent = $row_kz_branch_assistant['cf_3387'];
	// 								$jsontext .= "
	// 															{
	// 																'name': '$b_kz_assistant_position',
	// 																'userlistid': '$b_kz_assistant_id',
	// 																'title': '$b_kz_assistant_name',
	// 																'parentId': '$b_kz_assistant_parent',
	// 																'children': [";

	// 								$query_kz_branch_last_level = "SELECT vtiger_userlist.name,
	// 																											vtiger_userlistcf.userlistid,
	// 																											vtiger_userlistcf.cf_3341,
	// 																											vtiger_userlistcf.cf_3387
	// 																							 FROM vtiger_userlistcf
	// 																							 INNER JOIN vtiger_userlist
	// 																							 ON vtiger_userlist.userlistid = vtiger_userlistcf.userlistid 
	// 																							 LEFT JOIN vtiger_crmentity
	// 																							 ON vtiger_userlistcf.userlistid = vtiger_crmentity.crmid
	// 																							 WHERE vtiger_crmentity.deleted != 1
	// 																							 AND vtiger_userlistcf.cf_3421 = '$city_name'
	// 																							 AND  vtiger_userlistcf.cf_3387 = '$b_kz_assistant_id'";

	// 								$result_kz_branch_last_level = $db->pquery($query_kz_branch_last_level);

	// 								for ($bkzll=0; $bkzll<$db->num_rows($result_kz_branch_last_level); $bkzll++) {
	// 									$row_kz_branch_assistant = $db->query_result_rowdata($result_kz_branch_last_level, $bkzll);
	// 									$b_kz_lasllevel = $row_kz_branch_assistant['cf_3341'];
	// 									$b_kz_lasllevel_id = $row_kz_branch_assistant['userlistid'];
	// 									$b_kz_lasllevel_name = $row_kz_branch_assistant['name'];
	// 									$b_kz_lasllevel_parent = $row_kz_branch_assistant['cf_3387'];
										
	// 									$jsontext .= "
	// 																	{
	// 																		'name': '$b_kz_lasllevel',
	// 																		'userlistid': '$b_kz_lasllevel_id',
	// 																		'title': '$b_kz_lasllevel_name',
	// 																		'parentId': '$b_kz_lasllevel_parent'
	// 																	},";
	// 								}
	// 								$jsontext .="
	// 																]
	// 															},";
	// 							}
	// 							$jsontext .="
	// 														]
	// 													},";
	// 						}
	// 						$jsontext .= "
	// 												]
	// 											},";
	// 					}
	// 					$jsontext .= "
	// 										]
	// 									},";
	// 				}
	// 				$jsontext .= "
	// 								]
	// 							},";
	// 			}
	// 			$jsontext .= "
	// 						]
	// 					},";
	// 		}
	// 	}


	// 	$jsontext .= "
	// 				]
	// 			},";
	// 	$jsontext .="
	// 			{
	// 				'name': 'DWC',
	// 				'title': 'Abroad',
	// 				'children': [";

	// 	// getting DWC branches					
	// 	$query_branches = "SELECT DISTINCT vtiger_userlistcf.cf_3359
	// 										 FROM vtiger_userlistcf
	// 										 LEFT JOIN vtiger_crmentity
	// 										 ON vtiger_userlistcf.userlistid = vtiger_crmentity.crmid
	// 										 WHERE vtiger_crmentity.deleted != 1
	// 										 AND vtiger_userlistcf.cf_3353 = 'DWC'
	// 										 AND vtiger_userlistcf.cf_3359 != ''";

	// 	$result_branches = $db->pquery($query_branches);

	// 	for($b=0; $b<$db->num_rows($result_branches); $b++) {
	// 		$row = $db->query_result_rowdata($result_branches, $b);
	// 		$branch = $row['cf_3359'];
	// 		$jsontext .= "
	// 					{
	// 						'name': '$branch',
	// 						'title': 'Branch office',
	// 						'children': [";

	// 		$query_branch_head = "SELECT vtiger_userlist.name,
	// 																 vtiger_userlistcf.userlistid,
	// 																 vtiger_userlistcf.cf_3341,
	// 																 vtiger_userlistcf.cf_3387
	// 			  									FROM vtiger_userlistcf
	// 			  									INNER JOIN vtiger_userlist
	// 			  									ON vtiger_userlist.userlistid = vtiger_userlistcf.userlistid
	// 			  									LEFT JOIN vtiger_crmentity
	// 													ON vtiger_userlistcf.userlistid = vtiger_crmentity.crmid
	// 													WHERE vtiger_crmentity.deleted != 1
	// 													AND vtiger_userlistcf.cf_3359 = '$branch'
	// 			  									AND vtiger_userlistcf.cf_3421 <> 'Almaty'
	// 			  									AND vtiger_userlistcf.cf_3387 = 412373";

	// 		$result_branch_head = $db->pquery($query_branch_head);

	// 		for($bh=0; $bh<$db->num_rows($result_branch_head); $bh++) {
	// 			$row_bh = $db->query_result_rowdata($result_branch_head, $bh);
	// 			$branch_head_name = $row_bh['name'];
	// 			$branch_head_id = $row_bh['userlistid'];
	// 			$branch_head_position = $row_bh['cf_3341'];
	// 			$branch_head_parent = $row_bh['cf_3387'];
	// 			$jsontext .= "
	// 							{
	// 								'name': '$branch_head_position',
	// 								'userlistid': '$branch_head_id',
	// 								'title': '$branch_head_name', 
	// 								'parentId': '$branch_head_parent',
	// 								'children': [";

	// 			// getting branch departments
	// 			$query_branch_dep = "SELECT DISTINCT vtiger_userlistcf.cf_3349
	// 													 FROM vtiger_userlistcf
	// 													 LEFT JOIN vtiger_crmentity
	// 													 ON vtiger_userlistcf.userlistid = vtiger_crmentity.crmid
	// 													 WHERE vtiger_crmentity.deleted != 1
	// 													 AND vtiger_userlistcf.cf_3359 = '$branch'
	// 													 AND vtiger_userlistcf.cf_3349 != ''";
				
	// 			$result_branch_dep = $db->pquery($query_branch_dep);
				
	// 			for($bdep=0; $bdep<$db->num_rows($result_branch_dep); $bdep++) {
	// 				$row_branch_dep = $db->query_result_rowdata($result_branch_dep, $bdep);
	// 				$branch_dep_name = $row_branch_dep['cf_3349'];
	// 				$jsontext .= "
	// 									{
	// 										'name': '$branch_dep_name',
	// 										'children': [";

	// 				$query_branch_employee = "SELECT vtiger_userlist.name,
	// 																				 vtiger_userlistcf.userlistid,
	// 																				 vtiger_userlistcf.cf_3341,
	// 																				 vtiger_userlistcf.cf_3387
	// 																	FROM vtiger_userlistcf
	// 																	INNER JOIN vtiger_userlist
	// 																	ON vtiger_userlist.userlistid = vtiger_userlistcf.userlistid 
	// 																	LEFT JOIN vtiger_crmentity
	// 																	ON vtiger_userlistcf.userlistid = vtiger_crmentity.crmid
	// 																	WHERE vtiger_crmentity.deleted != 1
	// 																	AND vtiger_userlistcf.cf_3359 = '$branch'
	// 																	AND vtiger_userlistcf.cf_3387 = '$branch_head_id'
	// 																	AND vtiger_userlistcf.cf_3349 = '$branch_dep_name'
	// 																	";

	// 				$result_branch_employee = $db->pquery($query_branch_employee);

	// 				for($be=0; $be<$db->num_rows($result_branch_employee); $be++){
	// 					$row_branch_employee = $db->query_result_rowdata($result_branch_employee, $be);
	// 					$b_emp_name = $row_branch_employee['name'];
	// 					$b_emp_id = $row_branch_employee['userlistid'];
	// 					$b_emp_position = $row_branch_employee['cf_3341'];
	// 					$b_emp_parent = $row_branch_employee['cf_3387'];
	// 					$jsontext .= "
	// 											{
	// 												'name': '$b_emp_position',
	// 												'userlistid': '$b_emp_id',
	// 												'title': '$b_emp_name',
	// 												'parentId': '$b_emp_parent',
	// 												'children': [";

	// 					$query_branch_ordinary = "SELECT vtiger_userlist.name,
	// 																					 vtiger_userlistcf.userlistid,
	// 																					 vtiger_userlistcf.cf_3341,
	// 																					 vtiger_userlistcf.cf_3387
	// 																		FROM vtiger_userlistcf
	// 																		INNER JOIN vtiger_userlist
	// 																		ON vtiger_userlist.userlistid = vtiger_userlistcf.userlistid 
	// 																		LEFT JOIN vtiger_crmentity
	// 																		ON vtiger_userlistcf.userlistid = vtiger_crmentity.crmid
	// 																		WHERE vtiger_crmentity.deleted != 1
	// 																		AND vtiger_userlistcf.cf_3359 = '$branch'
	// 																		AND vtiger_userlistcf.cf_3387 = '$b_emp_id'
	// 																		AND vtiger_userlistcf.cf_3349 = '$branch_dep_name'";

	// 					$result_branch_ordinary = $db->pquery($query_branch_ordinary);

	// 					for($bo=0; $bo<$db->num_rows($result_branch_ordinary); $bo++){
	// 						$row_branch_ordinary = $db->query_result_rowdata($result_branch_ordinary, $bo);
	// 						$b_ordinary_name = $row_branch_ordinary['name'];
	// 						$b_ordinary_id = $row_branch_ordinary['userlistid'];
	// 						$b_ordinary_position = $row_branch_ordinary['cf_3341'];
	// 						$b_ordinary_parent = $row_branch_ordinary['cf_3387'];
	// 						$jsontext .= "
	// 													{
	// 														'name': '$b_ordinary_position',
	// 														'userlistid': '$b_ordinary_id',
	// 														'title': '$b_ordinary_name',
	// 														'parentId': '$b_ordinary_parent',
	// 														'children': [";

	// 						$query_branch_assistants = "SELECT vtiger_userlist.name,
	// 																							 vtiger_userlistcf.userlistid,
	// 																							 vtiger_userlistcf.cf_3341,
	// 																							 vtiger_userlistcf.cf_3387
	// 																				FROM vtiger_userlistcf
	// 																				INNER JOIN vtiger_userlist
	// 																				ON vtiger_userlist.userlistid = vtiger_userlistcf.userlistid 
	// 																				LEFT JOIN vtiger_crmentity
	// 																				ON vtiger_userlistcf.userlistid = vtiger_crmentity.crmid
	// 																				WHERE vtiger_crmentity.deleted != 1
	// 																				AND vtiger_userlistcf.cf_3359 = '$branch'
	// 																				AND vtiger_userlistcf.cf_3387 = '$b_ordinary_id'
	// 																				AND vtiger_userlistcf.cf_3349 = '$branch_dep_name'";
							
	// 						$result_branch_assistants = $db->pquery($query_branch_assistants);

	// 						for($ba=0; $ba<$db->num_rows($result_branch_assistants); $ba++){
	// 							$row_branch_assistant = $db->query_result_rowdata($result_branch_assistants, $ba);
	// 							$b_asisstant_name = $row_branch_assistant['name'];
	// 							$b_asisstant_id = $row_branch_assistant['userlistid'];
	// 							$b_asisstant_position = $row_branch_assistant['cf_3341'];
	// 							$b_asisstant_parent = $row_branch_assistant['cf_3387'];
	// 							$jsontext .= "
	// 															{
	// 																'name': '$b_asisstant_position',
	// 																'userlistid': '$b_asisstant_id',
	// 																'title': '$b_asisstant_name',
	// 																'parentId': '$b_asisstant_parent'
	// 															},";
	// 						}
	// 						$jsontext .= "
	// 														]
	// 													},";
	// 					}
	// 					$jsontext .="
	// 												]
	// 											},";
	// 				}
	// 				$jsontext .="
	// 										]
	// 									},";
	// 			}
	// 			$jsontext .="
	// 								]
	// 							},";
	// 		}
	// 		$jsontext .= "
	// 						]
	// 					},";
	// 	}
	// 	$jsontext .= "
	// 				]
	// 			}
	// 		]
	// 	};";
	// 	file_put_contents('include/OrgChart/data.js', $jsontext);
	// }


	/**
	* Invoked when special actions are performed on the module.
	* @param String Module name
	* @param String Event Type
	*/
	function vtlib_handler($moduleName, $eventType) {
		global $adb;
		if($eventType == 'module.postinstall') {
			// TODO Handle actions after this module is installed.
		} else if($eventType == 'module.disabled') {
			// TODO Handle actions before this module is being uninstalled.
		} else if($eventType == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
		} else if($eventType == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} else if($eventType == 'module.postupdate') {
			// TODO Handle actions after this module is updated.
		}
	}
}