<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class PackagingMaterial_MassActionAjax_View extends Vtiger_MassActionAjax_View {
	function __construct() {
		parent::__construct();
		$this->exposeMethod('showMassEditForm');
		$this->exposeMethod('showMassEditFormRelated');
		$this->exposeMethod('showAddCommentForm');
		$this->exposeMethod('showComposeEmailForm');
		$this->exposeMethod('showSendSMSForm');
		$this->exposeMethod('showDuplicatesSearchForm');
		$this->exposeMethod('transferOwnership');
		$this->exposeMethod('generatePackagingNo');
	}

	function process(Vtiger_Request $request) {
		$mode = $request->get('mode');
		if(!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	function generatePackagingNo (Vtiger_Request $request){
		//unset(@$_SESSION['packaging_ref_no']);
		$s_generate_packaging_flag  =true;
		$current_user = Users_Record_Model::getCurrentUserModel();
        $package_no = $request->get('packaging_no');
		$_SESSION['packaging_ref_no']='';
		if(isset($package_no))
		{
		 foreach($package_no as $id)
		 {
			 if(!empty($id))
			 {
				 $packaging_material_id = $id;
				 if(empty($_SESSION['packaging_ref_no']) && $s_generate_packaging_flag==true)
				{
					 $s_generate_packaging_flag = false;
					 $db = PearDatabase::getInstance();
					 $packaging_material_info = Vtiger_Record_Model::getInstanceById($packaging_material_id, 'PackagingMaterial');
					 $warehouse_id = $packaging_material_info->get('cf_5764');
					 $sql =  'SELECT MAX(p_serial_number) as max_ordering from vtiger_packagingmaterial
							  INNER JOIN vtiger_packagingmaterialcf ON 
							  vtiger_packagingmaterialcf.packagingmaterialid = vtiger_packagingmaterial.packagingmaterialid
							  WHERE vtiger_packagingmaterial.year_no=? AND vtiger_packagingmaterialcf.cf_5764=?';

					$value = date('Y');
					$params = array($value, $warehouse_id);
					$result = $db->pquery($sql, $params);
					$row = $db->fetch_array($result);
					if($db->num_rows($result)==0 or !$row)
					{
						$ordering = 0;
					}
					else{
						$max_ordering = $row["max_ordering"];
						if ( ! is_numeric($max_ordering))
						{
							$ordering = 0;
						}
						else
						{
							$ordering = $max_ordering;
						}
					}
					$p_serial_number = $ordering+1;

					$db->pquery('UPDATE vtiger_packagingmaterial SET year_no=?, p_serial_number = ? WHERE packagingmaterialid=?', array( date('Y'), $p_serial_number, $packaging_material_id));
					//packaging ref #::cf_5754
					$warehouse_name = $packaging_material_info->getDisplayValue('cf_5764');
					$packaging_ref_no = strtoupper($warehouse_name).'-'.str_pad($p_serial_number, 4, "0", STR_PAD_LEFT).'/'.date('y');
					$_SESSION['packaging_ref_no'] = $packaging_ref_no;
					$_SESSION['p_serial_number'] = $p_serial_number;
					$db->pquery('UPDATE vtiger_packagingmaterialcf SET cf_5754=?, cf_6124=? WHERE packagingmaterialid=?', array($packaging_ref_no, 'Requested',$packaging_material_id));
					$db->pquery('UPDATE vtiger_packagingmaterial SET name=? WHERE packagingmaterialid=?', array($packaging_ref_no, $packaging_material_id));
				}
				$db->pquery('UPDATE vtiger_packagingmaterial SET year_no=?, p_serial_number = ? WHERE packagingmaterialid=?', array( date('Y'), $_SESSION['p_serial_number'], $packaging_material_id ) );
				$db->pquery('UPDATE vtiger_packagingmaterialcf SET cf_5754=?, cf_6124=? WHERE packagingmaterialid=?', array($_SESSION['packaging_ref_no'],'Requested', $packaging_material_id));
				$db->pquery('UPDATE vtiger_packagingmaterial SET name=? WHERE packagingmaterialid=?', array($_SESSION['packaging_ref_no'], $packaging_material_id));
			}

		 }

		 //To add packing material own service detail in job expense at the time of generating packing ref #
		 $sourceModule = 'PackagingMaterial';
		 $packaging_material_info = Vtiger_Record_Model::getInstanceById($packaging_material_id, $sourceModule);

		 $packaging_material_user_info = Users_Record_Model::getInstanceById($packaging_material_info->get('assigned_user_id'), 'Users');
		 $packaging_material_user_company_id = $packaging_material_user_info->get('company_id');

		 $packaging_material_user_local_currency_code = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$packaging_material_user_company_id);
		 $packaging_material_user_currency_id = Vtiger_CompanyList_UIType::getCompanyReportingCurrencyID(@$packaging_material_user_company_id);
		 $CompanyAccountTypeList = Vtiger_Field_Model::getCompanyAccountTypeList();
		 $CompanyAccountType_Bank_R_Key = array_search ('Bank R', $CompanyAccountTypeList);
		 //$CompanyAccountType_Cash_R_Key = array_search ('Cash R', $CompanyAccountTypeList);
		 $local_account_type[] = $CompanyAccountType_Bank_R_Key;
		 //$local_account_type[] = $CompanyAccountType_Cash_R_Key;
		 $local_account  = implode(",",$local_account_type);

		 //SubJRER Packing Material Own Expense
		 $job_id = $this->get_job_id_from_PackagingMaterial($packaging_material_id);

		 $sourceModule_job 	= 'Job';
		 $job_info_detail = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule_job);

		 //Bank R
		 $packing_material_own_expenses = array(
		                               'b_job_charges_id' => '85880', //packing material own service
		                               'b_expected_buy_local_currency_net' => 0,
		                               //'b_type_id' => '85785',
		                               'b_type_id' => $CompanyAccountType_Bank_R_Key,
		                               'b_pay_to_id' => '',
		                               'label'	=> 'SubJRER Packing Material Own Expense',
		                               'parentmodule' => 'Job',
		                               'packaging_ref_no'	 => @$_SESSION['packaging_ref_no'],
		                               'invoice_no'   => @$_SESSION['packaging_ref_no'],
		                               );
		 $sub_jobexpencereportcfid = $this->savePackingMaterialExpense($packaging_material_id, $packaging_material_info, $packing_material_own_expenses, $job_info_detail, $job_id);

		 if($job_info_detail->get('assigned_user_id')!=$packaging_material_info->get('assigned_user_id'))
		 {
		   //To Main JRER
		   $packaging_material_id = $packaging_material_id;
		   //Bank R = 85785
		   $adb = PearDatabase::getInstance();
		   $query_count_job_jrer_buying = "SELECT * FROM vtiger_jobexpencereportcf
		                                   INNER JOIN vtiger_jobexpencereport ON vtiger_jobexpencereport.jobexpencereportid=vtiger_jobexpencereportcf.jobexpencereportid
		                                   INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereportcf.jobexpencereportid
		                                   INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid
		                                   WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid=?
		                                   AND crmentityrel.module='Job' AND crmentityrel.relmodule='Jobexpencereport'
		                                   AND vtiger_jobexpencereport.packaging_ref_no=?
																			 AND vtiger_jobexpencereport.user_id=?
																			 AND vtiger_jobexpencereport.owner_id=?
		                                   AND vtiger_jobexpencereportcf.cf_1453 = '85880'
		                                   AND vtiger_jobexpencereportcf.cf_1214 = '".$CompanyAccountType_Bank_R_Key."'
		                                  ";
		   //AND vtiger_jobexpencereportcf.cf_1214 = '85785'
		   //AND vtiger_jobexpencereportcf.cf_2195=?
		   //$truck_id
		   $job_id = $job_id;
		   $params_packing_material_jrer = array($job_id, @$_SESSION['packaging_ref_no'], $packaging_material_info->get('assigned_user_id'), $job_info_detail->get('assigned_user_id'));
		   $result_packing_material_jrer = $adb->pquery($query_count_job_jrer_buying, $params_packing_material_jrer);
		   $row_packing_material_jrer = $adb->fetch_array($result_packing_material_jrer);
		   $count_job_jrer_buying = $adb->num_rows($result_packing_material_jrer);

		   if($count_job_jrer_buying==0)
		   {
		     $packing_material_own_expenses = array(
		                                     'b_job_charges_id' => '85880',
		                                     'b_expected_buy_local_currency_net' => 0,
		                                     //'b_type_id' => '85785',
		                                     'b_type_id' => $CompanyAccountType_Bank_R_Key,
		                                     'b_pay_to_id' => '',
		                                     'label'	=> 'Main JRER Packing Material Own Expense',
		                                     'parentmodule' => 'Job',
		                                     'packaging_ref_no'	 => @$_SESSION['packaging_ref_no'],
		                                     'invoice_no'   => @$_SESSION['packaging_ref_no'],
											 'sub_jobexpencereportcfid' => $sub_jobexpencereportcfid
		                                     );

		     //to check its showing or not in Main JRER
		     $this->savePackingMaterialExpense_MainJRER($packaging_material_id, $packaging_material_info, $packing_material_own_expenses, $job_info_detail, $job_id);
		   }

		 }


		  $packaging['packaging_ref_no'] = @$_SESSION['packaging_ref_no'];
		  $_SESSION['packaging_ref_no']='';
		  
		  //Email Notification to Branch Warehouse Coordiantor
		    $current_user = Users_Record_Model::getCurrentUserModel();	
		 	$packaging_items='';  
			$pagingModel_1 = new Vtiger_Paging_Model();
			$pagingModel_1->set('page','1');
			
			$relatedModuleName_1 = 'PackagingMaterial';
			$parentRecordModel_1 = $job_info_detail;
			$relationListView_1 = Vtiger_RelationListView_Model::getInstance($parentRecordModel_1, $relatedModuleName_1, $label);
			$models_1 = $relationListView_1->getEntries($pagingModel_1);
			
			$pm_items = '';
			$total_amount=0;
			$i=1;
			foreach($models_1 as $key => $model){
					$packaging_material_items_id  = $model->getId();			
					$sourceModule   = 'PackagingMaterial';	
					$pmitem_info = Vtiger_Record_Model::getInstanceById($packaging_material_items_id, $sourceModule);
					if($pmitem_info->get('cf_5754')==$packaging['packaging_ref_no'])
					{
						$packaging_items .='<tr>
											<td>'.$i++.'</td>
											<td>'.$pmitem_info->getDisplayValue('cf_5738').'</td>
											<td>'.$pmitem_info->getDisplayValue('cf_5740').'</td>
											<td>'.$pmitem_info->getDisplayValue('cf_5744').'</td>											
											</tr>';								
					}
					
			}
			
						$body = '';
						
						$body .="<p>Dear&nbsp; Inna/Zinaida,</p>";
						$body .="<p>Please issue below packaging material list for job file ".$job_info_detail->get('cf_1198').".<br />";
						$body .="<br>Packaging Material Items.</p>";
						$body .='<table  border=1 cellspacing=0 cellpadding=5  width="100%"   ><tbody>
									<tr><td width="304"><strong>Packaging Ref #</strong></td>
										<td width="144"><strong>'.$packaging['packaging_ref_no'].'</strong>
										</td><td width="323"><strong></strong>
										</td><td width="157"><strong>Warehouse ID</strong>
										</td><td width="356"><strong>'.$packaging_material_info->getDisplayValue('cf_5764').'</strong>
										</td></tr>								
								</tbody>    
							</table>
							<br>
							<table border=1 cellspacing=0 cellpadding=5  width="100%"><tbody>
							<tr><td width="20"><strong>#</strong></td><td width="60"><strong>Type</strong></td><td width="60"><strong>Quantity
							</strong></td><td width="60"><strong>Requested Date</strong></td></tr>
							'.$packaging_items.'
							</tbody>
							</table>';
						$body .="<p>Regards,</p>";
						$body .="<p><strong>".$current_user->get('first_name')." ".$current_user->get('last_name')."</strong></p>";
						$body .="<p><strong>Globalink Logistics - </strong><br />";
						$body .="<u><a href='mailto:".$current_user->get('email1')."'>".$current_user->get('email1')."</a></u>&nbsp; <strong>I&nbsp;</strong> Web: <u><a href='http://www.globalinklogistics.com/'>www.globalinklogistics.com</a></u><br />";
						$body .="ASIA SPECIALIST ∙ CHINA FOCUS ∙ GLOBAL NETWORK<br />";
						$body .="Important Notice. All Globalink services are undertaken subject to Globalink&#39;s Terms and Conditions of Trading. These may exclude or limit our liability in the event of claims for loss, damage and delay to cargo or otherwise and provide for all disputes to be arbitrated in London under English law.&nbsp; Please view and download our Terms and Conditions of Trading from our website <a href='http://globalinklogistics.com/Trading-Terms-and-Conditions'>http://globalinklogistics.com/Trading-Terms-and-Conditions</a></p>";
						
						$from = "From: ".$current_user->get('email1')." <".$current_user->get('email1').">";
						//$from = $current_user->get('email1');
						//$to = $job_user_info->get('email1');
						$to ='i.terzidi@globalinklogistics.com;z.smelykh@globalinklogistics.com';
						$cc  = $current_user->get('email1').';g.moldakanova@globalinklogistics.com;s.mehtab@globalinklogistics.com;';
						//$cc= '';
						
					    $headers = "MIME-Version: 1.0" . "\n";
					    $headers .= "Content-type:text/html;charset=UTF-8" . "\n";
					    $headers .= $from . "\n";
					    $headers .= 'Reply-To: '.$to.'' . "\n";
					    $headers .= "CC:" . $cc . "\r\n";
						$subject = "Job File Packaging Material Request :: ".$packaging['packaging_ref_no']."";
						mail($to,$subject,$body,$headers);
		  
		  
		  echo json_encode($packaging);
		 }

	}
	/**
	 * Function returns the mass edit form
	 * @param Vtiger_Request $request
	 */
	function showMassEditFormRelated (Vtiger_Request $request){
		$moduleName = $request->getModule();
		$cvId = $request->get('viewname');
		$selectedIds = $request->get('selected_ids');
		$excludedIds = $request->get('excluded_ids');

		$viewer = $this->getViewer($request);
		//$record = @$selectedIds[0];
		$record = @$selectedIds;
		$packaging_material_status='';
		if(!empty($record)) {
      		$recordModel = Vtiger_Record_Model::getInstanceById($record, $moduleName);
			$recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_EDIT);
			$moduleModel = $recordModel->getModule();

			$viewer->assign('WHID', $recordModel->get('cf_5764')); //FROM WAREHOUSE
			$viewer->assign('DOC_TYPE', 'Transfer Outward'); //Document Type
			$viewer->assign('IN_HOUSE', 'Yes');
			$current_user = Users_Record_Model::getCurrentUserModel();
			$glk_company_id = $current_user->get('company_id');
			$viewer->assign('GLK_COMPANY_ID', $glk_company_id);
			$packaging_material_status = $recordModel->get('cf_6124'); //Packaging Material Status
			$viewer->assign('PACKAGING_MATERIAL_STATUS', $packaging_material_status);

		}
		else{
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceForModule($moduleModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_MASSEDIT);
		}

		$fieldInfo = array();
		$fieldList = $moduleModel->getFields();
		foreach ($fieldList as $fieldName => $fieldModel) {
			$fieldInfo[$fieldName] = $fieldModel->getFieldInfo();
		}
		$picklistDependencyDatasource = Vtiger_DependencyPicklist::getPicklistDependencyDatasource($moduleName);

		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE',Zend_Json::encode($picklistDependencyDatasource));
		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		$viewer->assign('MODE', 'massedit');
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('CVID', $cvId);
		$viewer->assign('SELECTED_IDS', '['.$selectedIds.']');
		$viewer->assign('EXCLUDED_IDS', $excludedIds);
		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
		$viewer->assign('MODULE_MODEL',$moduleModel);
		$viewer->assign('MASS_EDIT_FIELD_DETAILS',$fieldInfo);
		$viewer->assign('RECORD_STRUCTURE', $recordStructureInstance->getStructure());
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
        $viewer->assign('MODULE_MODEL', $moduleModel);
        $searchKey = $request->get('search_key');
        $searchValue = $request->get('search_value');
		$operator = $request->get('operator');
        if(!empty($operator)) {
			$viewer->assign('OPERATOR',$operator);
			$viewer->assign('ALPHABET_VALUE',$searchValue);
            $viewer->assign('SEARCH_KEY',$searchKey);
		}
		$viewer->assign('VIEW_MODE', 'RELATED');
		$viewer->assign('SCRIPTS', $this->getHeaderScripts($request));
		echo $viewer->view('MassEditForm.tpl',$moduleName,true);
	}

	function showMassEditForm (Vtiger_Request $request){
		$moduleName = $request->getModule();
		$cvId = $request->get('viewname');
		$selectedIds = $request->get('selected_ids');
		$excludedIds = $request->get('excluded_ids');

		$viewer = $this->getViewer($request);
		$record = @$selectedIds[0];
		$packaging_material_status='';
		if(!empty($record)) {
     		$recordModel = Vtiger_Record_Model::getInstanceById($record, $moduleName);
			$recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_EDIT);
			$moduleModel = $recordModel->getModule();

			$viewer->assign('WHID', $recordModel->get('cf_5764')); //FROM WAREHOUSE
			$viewer->assign('DOC_TYPE', 'Transfer Outward'); //Document Type
			$viewer->assign('IN_HOUSE', 'Yes');
			$current_user = Users_Record_Model::getCurrentUserModel();
			$glk_company_id = $current_user->get('company_id');
			$viewer->assign('GLK_COMPANY_ID', $glk_company_id);
			$packaging_material_status = $recordModel->get('cf_6124'); //Packaging Material Status
			$viewer->assign('PACKAGING_MATERIAL_STATUS', $packaging_material_status);
			
			$warehouseid = $recordModel->get('cf_5764');
			//if($warehouseid=='1137684'){
			//	$recordModel->set('cf_6118','1137721');
			//}

		}
		else{
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceForModule($moduleModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_MASSEDIT);
		}

		$fieldInfo = array();
		$fieldList = $moduleModel->getFields();
		foreach ($fieldList as $fieldName => $fieldModel) {
			$fieldInfo[$fieldName] = $fieldModel->getFieldInfo();
		}
		$picklistDependencyDatasource = Vtiger_DependencyPicklist::getPicklistDependencyDatasource($moduleName);

		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE',Zend_Json::encode($picklistDependencyDatasource));
		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		$viewer->assign('MODE', 'massedit');
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('CVID', $cvId);
		$viewer->assign('SELECTED_IDS', $selectedIds);
		$viewer->assign('EXCLUDED_IDS', $excludedIds);
		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
		$viewer->assign('MODULE_MODEL',$moduleModel);
		$viewer->assign('MASS_EDIT_FIELD_DETAILS',$fieldInfo);
		$viewer->assign('RECORD_STRUCTURE', $recordStructureInstance->getStructure());
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
        $viewer->assign('MODULE_MODEL', $moduleModel);
        $searchKey = $request->get('search_key');
        $searchValue = $request->get('search_value');
		$operator = $request->get('operator');
        if(!empty($operator)) {
			$viewer->assign('OPERATOR',$operator);
			$viewer->assign('ALPHABET_VALUE',$searchValue);
            $viewer->assign('SEARCH_KEY',$searchKey);
		}
		$viewer->assign('VIEW_MODE', 'LISTING');
		$viewer->assign('SCRIPTS', $this->getHeaderScripts($request));
		echo $viewer->view('MassEditForm.tpl',$moduleName,true);
	}

	public function getHeaderScripts(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$jsFileNames = array(
			"modules.$moduleName.resources.MassEdit"
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return $jsScriptInstances;
	}


	/**
	 * Function returns the Add Comment form
	 * @param Vtiger_Request $request
	 */
	function showAddCommentForm(Vtiger_Request $request){
		$sourceModule = $request->getModule();
		$moduleName = 'ModComments';
		$cvId = $request->get('viewname');
		$selectedIds = $request->get('selected_ids');
		$excludedIds = $request->get('excluded_ids');

		$viewer = $this->getViewer($request);
		$viewer->assign('SOURCE_MODULE', $sourceModule);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('CVID', $cvId);
		$viewer->assign('SELECTED_IDS', $selectedIds);
		$viewer->assign('EXCLUDED_IDS', $excludedIds);
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());

        $searchKey = $request->get('search_key');
        $searchValue = $request->get('search_value');
		$operator = $request->get('operator');
        if(!empty($operator)) {
			$viewer->assign('OPERATOR',$operator);
			$viewer->assign('ALPHABET_VALUE',$searchValue);
            $viewer->assign('SEARCH_KEY',$searchKey);
		}

		echo $viewer->view('AddCommentForm.tpl',$moduleName,true);
	}

	/**
	 * Function returns the Compose Email form
	 * @param Vtiger_Request $request
	 */
	function showComposeEmailForm(Vtiger_Request $request) {
		$moduleName = 'Emails';
		$sourceModule = $request->getModule();
		$cvId = $request->get('viewname');
		$selectedIds = $request->get('selected_ids');
		$excludedIds = $request->get('excluded_ids');
		$step = $request->get('step');
		$selectedFields = $request->get('selectedFields');
		$relatedLoad = $request->get('relatedLoad');

		$moduleModel = Vtiger_Module_Model::getInstance($sourceModule);
		$emailFields = $moduleModel->getFieldsByType('email');
        $accesibleEmailFields = array();
        $emailColumnNames = array();
        $emailColumnModelMapping = array();

        foreach($emailFields as $index=>$emailField) {
            $fieldName = $emailField->getName();
            if($emailField->isViewable()) {
                $accesibleEmailFields[] = $emailField;
                $emailColumnNames[] = $emailField->get('column');
                $emailColumnModelMapping[$emailField->get('column')] = $emailField;
            }
        }
        $emailFields = $accesibleEmailFields;

        $emailFieldCount = count($emailFields);
        $tableJoined = array();
        if($emailFieldCount > 1) {
            $recordIds = $this->getRecordsListFromRequest($request);

            $moduleMeta = $moduleModel->getModuleMeta();
            $wsModuleMeta = $moduleMeta->getMeta();
            $tabNameIndexList = $wsModuleMeta->getEntityTableIndexList();

            $queryWithFromClause = 'SELECT '. implode(',',$emailColumnNames). ' FROM vtiger_crmentity ';
            foreach($emailFields as $emailFieldModel) {
                $fieldTableName = $emailFieldModel->table;
                if(in_array($fieldTableName, $tableJoined)){
                    continue;
                }

                $tableJoined[] = $fieldTableName;
                $queryWithFromClause .= ' INNER JOIN '.$fieldTableName .
                            ' ON '.$fieldTableName.'.'.$tabNameIndexList[$fieldTableName].'= vtiger_crmentity.crmid';
            }
            $query =  $queryWithFromClause . ' WHERE vtiger_crmentity.deleted = 0 AND crmid IN ('.  generateQuestionMarks($recordIds).') AND (';

            for($i=0; $i<$emailFieldCount;$i++) {
                for($j=($i+1);$j<$emailFieldCount;$j++){
                    $query .= ' (' . $emailFields[$i]->getName() .' != \'\' and '. $emailFields[$j]->getName().' != \'\')';
                    if(!($i == ($emailFieldCount-2) && $j == ($emailFieldCount-1))) {
                        $query .= ' or ';
                    }
                }
            }
            $query .=') LIMIT 1';

            $db = PearDatabase::getInstance();
            $result = $db->pquery($query,$recordIds);

            $num_rows = $db->num_rows($result);

            if($num_rows == 0) {
                $query = $queryWithFromClause . ' WHERE vtiger_crmentity.deleted = 0 AND crmid IN ('.  generateQuestionMarks($recordIds).') AND (';
                foreach($emailColumnNames as $index =>$columnName) {
                    $query .= " $columnName != ''";
                    //add glue or untill unless it is the last email field
                    if($index != ($emailFieldCount -1 ) ){
                        $query .= ' or ';
                    }
                }
                $query .= ') LIMIT 1';
                $result = $db->pquery($query, $recordIds);
                if($db->num_rows($result) > 0) {
                    //Expecting there will atleast one row
                    $row = $db->query_result_rowdata($result,0);

                    foreach($emailColumnNames as $emailColumnName) {
                        if(!empty($row[$emailColumnName])) {
                            //To send only the single email field since it is only field which has value
                            $emailFields = array($emailColumnModelMapping[$emailColumnName]);
                            break;
                        }
                    }
                }else{
                    //No Record which has email field value
                    foreach($emailColumnNames as $emailColumnName) {
                        //To send only the single email field since it has no email value
                        $emailFields = array($emailColumnModelMapping[$emailColumnName]);
                        break;
                    }
                }
            }
        }

		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('VIEWNAME', $cvId);
		$viewer->assign('SELECTED_IDS', $selectedIds);
		$viewer->assign('EXCLUDED_IDS', $excludedIds);
		$viewer->assign('EMAIL_FIELDS', $emailFields);
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());

        $searchKey = $request->get('search_key');
        $searchValue = $request->get('search_value');
		$operator = $request->get('operator');
        if(!empty($operator)) {
			$viewer->assign('OPERATOR',$operator);
			$viewer->assign('ALPHABET_VALUE',$searchValue);
            $viewer->assign('SEARCH_KEY',$searchKey);
		}
		$parentModule = $request->get('sourceModule');
		$parentRecord = $request->get('sourceRecord');
		if (!empty($parentModule)) {
			$viewer->assign('PARENT_MODULE', $parentModule);
			$viewer->assign('PARENT_RECORD', $parentRecord);
			$viewer->assign('RELATED_MODULE', $sourceModule);
		}
		if($relatedLoad){
			$viewer->assign('RELATED_LOAD', true);
		}

		if($step == 'step1') {
			echo $viewer->view('SelectEmailFields.tpl', $moduleName, true);
			exit;
		}
	}

	/**
	 * Function shows form that will lets you send SMS
	 * @param Vtiger_Request $request
	 */
	function showSendSMSForm(Vtiger_Request $request) {

		$sourceModule = $request->getModule();
		$moduleName = 'SMSNotifier';
		$selectedIds = $this->getRecordsListFromRequest($request);
		$excludedIds = $request->get('excluded_ids');
		$cvId = $request->get('viewname');

		$user = Users_Record_Model::getCurrentUserModel();
        $moduleModel = Vtiger_Module_Model::getInstance($sourceModule);
        $phoneFields = $moduleModel->getFieldsByType('phone');

		$viewer = $this->getViewer($request);

		if(count($selectedIds) == 1){
			$recordId = $selectedIds[0];
			$selectedRecordModel = Vtiger_Record_Model::getInstanceById($recordId, $sourceModule);
			$viewer->assign('SINGLE_RECORD', $selectedRecordModel);
		}
		$viewer->assign('VIEWNAME', $cvId);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('SOURCE_MODULE', $sourceModule);
		$viewer->assign('SELECTED_IDS', $selectedIds);
		$viewer->assign('EXCLUDED_IDS', $excludedIds);
		$viewer->assign('USER_MODEL', $user);
		$viewer->assign('PHONE_FIELDS', $phoneFields);

        $searchKey = $request->get('search_key');
        $searchValue = $request->get('search_value');
		$operator = $request->get('operator');
        if(!empty($operator)) {
			$viewer->assign('OPERATOR',$operator);
			$viewer->assign('ALPHABET_VALUE',$searchValue);
            $viewer->assign('SEARCH_KEY',$searchKey);
		}

		echo $viewer->view('SendSMSForm.tpl', $moduleName, true);
	}

	/**
	 * Function returns the record Ids selected in the current filter
	 * @param Vtiger_Request $request
	 * @return integer
	 */
	function getRecordsListFromRequest(Vtiger_Request $request) {
		$cvId = $request->get('viewname');
		$selectedIds = $request->get('selected_ids');
		$excludedIds = $request->get('excluded_ids');

		if(!empty($selectedIds) && $selectedIds != 'all') {
			if(!empty($selectedIds) && count($selectedIds) > 0) {
				return $selectedIds;
			}
		}

		$sourceRecord = $request->get('sourceRecord');
		$sourceModule = $request->get('sourceModule');
		if ($sourceRecord && $sourceModule) {
			$sourceRecordModel = Vtiger_Record_Model::getInstanceById($sourceRecord, $sourceModule);
			return $sourceRecordModel->getSelectedIdsList($request->getModule(), $excludedIds);
		}

		$customViewModel = CustomView_Record_Model::getInstanceById($cvId);
		if($customViewModel) {
			$searchKey = $request->get('search_key');
			$searchValue = $request->get('search_value');
			$operator = $request->get('operator');
			if(!empty($operator)) {
				$customViewModel->set('operator', $operator);
				$customViewModel->set('search_key', $searchKey);
				$customViewModel->set('search_value', $searchValue);
			}
			return $customViewModel->getRecordIds($excludedIds,$request->getModule());
		}
	}

	/**
	 * Function shows the List of Mail Merge Templates
	 * @param Vtiger_Request $request
	 */
	function showMailMergeTemplates(Vtiger_Request $request) {
		$selectedIds = $request->get('selected_ids');
		$excludedIds = $request->get('excluded_ids');
		$cvId = $request->get('viewname');
		$module = $request->getModule();
		$templates = Settings_MailMerge_Record_Model::getByModule($module);

		$viewer = $this->getViewer($request);
		$viewer->assign('TEMPLATES', $templates);
		$viewer->assign('SELECTED_IDS', $selectedIds);
		$viewer->assign('EXCLUDED_IDS', $excludedIds);
		$viewer->assign('VIEWNAME', $cvId);
		$viewer->assign('MODULE', $module);

		return $viewer->view('showMergeTemplates.tpl', $module);
	}

	/**
	 * Function shows the duplicate search form
	 * @param Vtiger_Request $request
	 */
	function showDuplicatesSearchForm(Vtiger_Request $request) {
		$module = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($module);
		$fields = $moduleModel->getFields();

		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE', $module);
		$viewer->assign('FIELDS', $fields);
		$viewer->view('showDuplicateSearch.tpl', $module);
	}

	function transferOwnership(Vtiger_Request $request){
		$module = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($module);
		$relatedModules = $moduleModel->getRelations();
		//User doesn't have the permission to edit related module,
		//then don't show that module in related module list.
		foreach ($relatedModules as $key => $relModule) {
			if (!Users_Privileges_Model::isPermitted($relModule->get('relatedModuleName'), 'EditView')) {
				unset($relatedModules[$key]);
			}
		}

		$viewer = $this->getViewer($request);
		$skipModules = array('Emails');
		$viewer->assign('MODULE',$module);
		$viewer->assign('RELATED_MODULES', $relatedModules);
		$viewer->assign('SKIP_MODULES', $skipModules);
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->view('TransferRecordOwnership.tpl', $module);
	}


	function savePackingMaterialExpense_MainJRER($packaging_material_id, $packaging_material_info, $packing_material_own_expenses=array(), $job_info_detail, $job_id)
	{
	  $adb = PearDatabase::getInstance();
	  $current_user = Users_Record_Model::getCurrentUserModel();
	  $ownerId = $packaging_material_info->get('assigned_user_id');
	  $date_var = date("Y-m-d H:i:s");
	  $usetime = $adb->formatDate($date_var, true);
	  //$job_fleet_id = $recordModel->getId();
	  $job_packaging_material_id = $packaging_material_id;
	  //$roundtrip_id = $recordModel->getId();

	  $packaging_material_invoice_date = date('Y-m-d');

	  $packaging_material_info = $packaging_material_info;

	  $current_id = $adb->getUniqueId('vtiger_crmentity');
	  //$source_id = $request->get('sourceRecord');
	  $source_id = $job_id;


	  //INSERT data in JRER expense module from job costing
	  $adb->pquery("INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid,
	     setype, description, createdtime, modifiedtime, presence, deleted, label)
	    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
	    //array($current_id, $job_info_detail->get('assigned_user_id'), $job_info_detail->get('assigned_user_id'), 'Jobexpencereport', 'NULL', $date_var, $date_var, 1, 0, $fleet_expence['label']));

	    array($current_id, $packaging_material_info->get('assigned_user_id'), $packaging_material_info->get('assigned_user_id'), 'Jobexpencereport', 'NULL', $date_var, $date_var, 1, 0, $packing_material_own_expenses['label']));

	  //INSERT data in jobexpencereport module from Fleet
	  $adb_e = PearDatabase::getInstance();
	  $jobexpencereport_insert_query = "INSERT INTO vtiger_jobexpencereport(jobexpencereportid, name, user_id, owner_id, job_id, packaging_ref_no, jrer_buying_id) VALUES(?,?,?,?,?,?,?)";
	  $params_jobexpencereport= array($current_id, $job_packaging_material_id, $packaging_material_info->get('assigned_user_id'), $job_info_detail->get('assigned_user_id'), $source_id, $packing_material_own_expenses['packaging_ref_no'], $packing_material_own_expenses['sub_jobexpencereportcfid']);
	  $adb_e->pquery($jobexpencereport_insert_query, $params_jobexpencereport);
	  $jobexpencereportid = $adb_e->getLastInsertID();

	  //cf_1477 = Office
	  //cf_1479 = Department
	  //cf_1367 = pay_to
	  //cf_1345 = vendor currency
	  //cf_1222 = exchange rate
	  //cf_1351 = Expected Buy (Local Currency NET)
	  $adb_ecf = PearDatabase::getInstance();
	  $jobexpencereportcf_insert_query = "INSERT INTO vtiger_jobexpencereportcf(jobexpencereportid, cf_1479, cf_1477, cf_1453, cf_1214, cf_1351, cf_1457, cf_1337, cf_1343, cf_1347, cf_1349, cf_1353, cf_1212, cf_1216)
	                    VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
	  $params_jobexpencereportcf = array($current_id, $current_user->get('department_id'), $current_user->get('location_id'), $packing_material_own_expenses['b_job_charges_id'], $packing_material_own_expenses['b_type_id'],
	                     $packing_material_own_expenses['b_expected_buy_local_currency_net'], 'Expence', 0, 0, 0, 0, 0, $packing_material_own_expenses['invoice_no'],date('Y-m-d'));
	  $adb_ecf->pquery($jobexpencereportcf_insert_query, $params_jobexpencereportcf);
	  $jobexpencereportcfid = $adb_ecf->getLastInsertID();

	  $adb_rel = PearDatabase::getInstance();
	  $crmentityrel_insert_query = "INSERT INTO vtiger_crmentityrel(crmid, module, relcrmid, relmodule) VALUES(?, ?, ?, ?)";
	  $params_crmentityrel = array($source_id, $packing_material_own_expenses['parentmodule'], $jobexpencereportcfid, 'Jobexpencereport');
	  $adb_rel->pquery($crmentityrel_insert_query, $params_crmentityrel);
	}



	function savePackingMaterialExpense($packaging_material_id, $packaging_material_info, $packing_material_own_expenses=array(), $job_info_detail, $job_id)
	{
	  $adb = PearDatabase::getInstance();
	  $current_user = Users_Record_Model::getCurrentUserModel();
	  $ownerId = $packaging_material_info->get('assigned_user_id');
	  $date_var = date("Y-m-d H:i:s");
	  $usetime = $adb->formatDate($date_var, true);
	  $job_packaging_material_id = $packaging_material_id;
	  //$roundtrip_id = $recordModel->getId();

	  $packaging_material_invoice_date = date('Y-m-d');

	  $packaging_material_info = $packaging_material_info;

	  $current_id = $adb->getUniqueId('vtiger_crmentity');
	  $source_id = $job_id;


	  //INSERT data in JRER expense module from job costing
	  $adb->pquery("INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid,
	     setype, description, createdtime, modifiedtime, presence, deleted, label)
	    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
	    array($current_id, $packaging_material_info->get('assigned_user_id'), $packaging_material_info->get('assigned_user_id'), 'Jobexpencereport', 'NULL', $date_var, $date_var, 1, 0, $packing_material_own_expenses['label']));

	  //INSERT data in jobexpencereport module from Packing Material
	  $adb_e = PearDatabase::getInstance();
	  $jobexpencereport_insert_query = "INSERT INTO vtiger_jobexpencereport(jobexpencereportid, name, user_id, owner_id, job_id, packaging_ref_no) VALUES(?,?,?,?,?,?)";
	  $params_jobexpencereport= array($current_id, $job_packaging_material_id, $packaging_material_info->get('assigned_user_id'), $packaging_material_info->get('assigned_user_id'), $source_id, $packing_material_own_expenses['packaging_ref_no']);
	  $adb_e->pquery($jobexpencereport_insert_query, $params_jobexpencereport);
	  $jobexpencereportid = $adb_e->getLastInsertID();

	  //cf_1477 = Office
	  //cf_1479 = Department
	  //cf_1367 = pay_to
	  //cf_1345 = vendor currency
	  //cf_1222 = exchange rate
	  //cf_1351 = Expected Buy (Local Currency NET)
	  //cf_1216 = Invoice Date
	  $adb_ecf = PearDatabase::getInstance();
	  $jobexpencereportcf_insert_query = "INSERT INTO vtiger_jobexpencereportcf(jobexpencereportid, cf_1479, cf_1477, cf_1453, cf_1214, cf_1351, cf_1457,  cf_1337, cf_1343, cf_1347, cf_1349, cf_1353, cf_1212, cf_1216)
	                                      VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
	  $params_jobexpencereportcf = array($current_id, $current_user->get('department_id'), $current_user->get('location_id'), $packing_material_own_expenses['b_job_charges_id'], $packing_material_own_expenses['b_type_id'],
	                     $packing_material_own_expenses['b_expected_buy_local_currency_net'], 'Expence', 0, 0, 0, 0, 0, $packing_material_own_expenses['invoice_no'],date('Y-m-d'));
	  $adb_ecf->pquery($jobexpencereportcf_insert_query, $params_jobexpencereportcf);
	  $jobexpencereportcfid = $adb_ecf->getLastInsertID();

	  $adb_rel = PearDatabase::getInstance();
	  $crmentityrel_insert_query = "INSERT INTO vtiger_crmentityrel(crmid, module, relcrmid, relmodule) VALUES(?, ?, ?, ?)";
	  $params_crmentityrel = array($source_id, $packing_material_own_expenses['parentmodule'], $jobexpencereportcfid, 'Jobexpencereport');
	  $adb_rel->pquery($crmentityrel_insert_query, $params_crmentityrel);
	  return $jobexpencereportcfid;
	}


	function get_job_id_from_PackagingMaterial($recordId=0) {
	    $adb = PearDatabase::getInstance();

	    $checkjob = $adb->pquery("SELECT rel1.crmid AS job_id
	                              FROM `vtiger_crmentityrel` AS rel1
	                              WHERE rel1.relcrmid = '".$recordId."' AND rel1.module='Job' AND rel1.relmodule='PackagingMaterial'", array());
	    $crmId = $adb->query_result($checkjob, 0, 'job_id');
	    $job_id = $crmId;
	    return $job_id;
	  }
}
