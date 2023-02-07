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

	public function requiresPermission(Vtiger_Request $request){
		$permissions = parent::requiresPermission($request);
		$mode = $request->getMode();
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView');
		if(!empty($mode)) {
			switch ($mode) {
				case 'showMassEditForm':
					$permissions[] = array('module_parameter' => 'module', 'action' => 'EditView');
					break;
				case 'showMassEditFormRelated':
					$permissions[] = array('module_parameter' => 'module', 'action' => 'EditView');
					break;	
				case 'showAddCommentForm':
					$permissions[] = array('module_parameter' => 'custom_module', 'action' => 'CreateView');
					$request->set('custom_module', 'ModComments');
					break;
				case 'showComposeEmailForm':
					$permissions[] = array('module_parameter' => 'custom_module', 'action' => 'DetailView');
					$request->set('custom_module', 'Emails');
					break;
				case 'showSendSMSForm':
					$permissions[] = array('module_parameter' => 'custom_module', 'action' => 'CreateView');
					$request->set('custom_module', 'SMSNotifier');
					break;
				case 'generatePackagingNo':
					$permissions[] = array('module_parameter' => 'module', 'action' => 'EditView');
					break;	
				default:
					break;
			}
		}
		return $permissions;
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
						$detail = $pmitem_info->getDisplayValue('cf_6292');
						$packaging_items .='<tr>
											<td>'.$i++.'</td>
											<td>'.$pmitem_info->getDisplayValue('cf_5738').''.(!empty($detail) ? '<br>'.$detail : '' ).'</td>
											<td>'.$pmitem_info->getDisplayValue('cf_5740').'</td>
											<td>'.$pmitem_info->getDisplayValue('cf_5744').'</td>											
											</tr>';							
					}
					
			}
			
						//$content = $this->print_packaging_material($packaging_material_id);
						$this->print_packaging_material($packaging_material_id);
						//$content = chunk_split(base64_encode($content));
						
						//$separator = md5(time());
						// carriage return type (we use a PHP end of line constant)
						$eol = PHP_EOL;						
						// attachment name
						$filename = "packaging_material_".$packaging_material_id.".pdf";
						//$pdfdoc is PDF generated by FPDF
						//$attachment = $content;
						
						//$from = "From: ".$current_user->get('email1')." <".$current_user->get('email1').">";
						$from = $current_user->get('email1');
						//$from = $current_user->get('email1');
						//$to = $job_user_info->get('email1');
						//$to = 's.mehtab@globalinklogistics.com';
						$to ='warehouse@globalinklogistics.com';
						//$cc = $current_user->get('email1').',g.moldakanova@globalinklogistics.com,s.mehtab@globalinklogistics.com,z.smelykh@globalinklogistics.com,';
						//$cc= '';
						
						// main header
						//$headers  = $from.$eol;
						//$headers .= 'Reply-To: '.$to.'' . $eol;
						//$headers .= "CC:" . $cc . $eol;
						//$headers .= "MIME-Version: 1.0".$eol; 
						//$headers .= "Content-Type: multipart/mixed; boundary=\"".$separator."\"".$eol;
						
						//$body = "--".$separator.$eol;
						//$body .= "Content-Type: text/html; charset=\"UTF-8\"".$eol;
						//$body .= "Content-Transfer-Encoding: 7bit".$eol.$eol;
						//$body .= "This is a MIME<br> encoded message.".$eol;
						
						
						$body .="<p>Тимур,</p>".$eol;
						$body .="<p>Примите запрос на упаковочные материалы по работе  ".$job_info_detail->get('cf_1198').".<br />".$eol;
						$body .="<br>Packaging Material Items.</p>".$eol;
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
							</table>'.$eol;
						$body .="<p>Regards,</p>".$eol;
						$body .="<p><strong>".$current_user->get('first_name')." ".$current_user->get('last_name')."</strong></p>".$eol;
						$body .="<p><strong>Globalink Logistics - </strong><br />".$eol;
						$body .="<u><a href='mailto:".$current_user->get('email1')."'>".$current_user->get('email1')."</a></u>&nbsp; <strong>I&nbsp;</strong> Web: <u><a href='http://www.globalinklogistics.com/'>www.globalinklogistics.com</a></u><br />".$eol;
						$body .="ASIA SPECIALIST ∙ CHINA FOCUS ∙ GLOBAL NETWORK<br />".$eol;
						$body .="Important Notice. All Globalink services are undertaken subject to Globalink&#39;s Terms and Conditions of Trading. These may exclude or limit our liability in the event of claims for loss, damage and delay to cargo or otherwise and provide for all disputes to be arbitrated in London under English law.&nbsp; Please view and download our Terms and Conditions of Trading from our website <a href='http://globalinklogistics.com/Trading-Terms-and-Conditions'>http://globalinklogistics.com/Trading-Terms-and-Conditions</a></p>".$eol;
						
						// attachment
						//$body .= "--".$separator.$eol;
						//$body .= "Content-Type: application/pdf; name=\"".$filename."\"".$eol; 
						//$body .= "Content-Transfer-Encoding: base64".$eol;
						//$body .= "Content-Disposition: attachment".$eol.$eol;
						//$body .= $attachment.$eol;
						//$body .= "--".$separator."--";
						
					    
						$subject = "GEMS Job File Packaging Material Request :: ".$packaging['packaging_ref_no']."";

						require_once 'vtlib/Vtiger/Mailer.php';
						global $HELPDESK_SUPPORT_EMAIL_ID;
						$mailer = new Vtiger_Mailer();
						$mailer->IsHTML(true);
						$mailer->ConfigSenderInfo($from);
						$mailer->Subject =$subject;
						$mailer->Body = $body;
						$mailer->AddAddress($to);
						//$mail->AddCC('person1@domain.com', 'Person One');
						//$mail->AddCC('person2@domain.com', 'Person Two');
						$mailer->AddCC($current_user->get('email1'));
						$mailer->AddCC('g.moldakanova@globalinklogistics.com');
						$mailer->AddCC('s.mehtab@globalinklogistics.com');
						$mailer->AddCC('z.smelykh@globalinklogistics.com');
						$mailer->AddAttachment('pdf_docs/packaging_material_'.$packaging_material_id.'.pdf', decode_html($filename));
						$status = $mailer->Send(true);
						//require_once("modules/Emails/mail.php");
        				//send_mail('PackagingMaterial', $to, $from, $from, $subject, $body,$cc,'','current','','',true);
						//mail($to,$subject,$body,$headers);
		  
		  
		  echo json_encode($packaging);
		 }

	}


	function showMassEditFormRelated (Vtiger_Request $request){
		$moduleName = $request->getModule();
		$cvId = $request->get('viewname');
		$selectedIds = $request->get('selected_ids');
		$excludedIds = $request->get('excluded_ids');
		$tagParams = $request->get('tag_params');
		if(empty($tagParams)){
            $tagParams = array();
		}
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
		$recordStructure = $recordStructureInstance->getStructure();
		foreach($recordStructure as $blockName => $fields) {
			if(empty($fields)) {
				unset($recordStructure[$blockName]);
			}
		}

		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE',Vtiger_Functions::jsonEncode($picklistDependencyDatasource));
		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		$viewer->assign('MODE', 'massedit');
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('CVID', $cvId);
		$viewer->assign('SELECTED_IDS', $selectedIds);
		$viewer->assign('EXCLUDED_IDS', $excludedIds);
        $viewer->assign('TAG_PARAMS', $tagParams);
		$viewer->assign('VIEW_SOURCE','MASSEDIT');
		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
		$viewer->assign('MODULE_MODEL',$moduleModel); 
		$viewer->assign('MASS_EDIT_FIELD_DETAILS',$fieldInfo); 
		$viewer->assign('RECORD_STRUCTURE', $recordStructure);
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
        $viewer->assign('MODULE_MODEL', $moduleModel);
        //do not show any image details in mass edit form
        $viewer->assign('IMAGE_DETAILS', array());
        $searchKey = $request->get('search_key');
        $searchValue = $request->get('search_value');
		$operator = $request->get('operator');
        if(!empty($operator)) {
			$viewer->assign('OPERATOR',$operator);
			$viewer->assign('ALPHABET_VALUE',$searchValue);
            $viewer->assign('SEARCH_KEY',$searchKey);
		}
        $searchParams = $request->get('search_params');
        if(!empty($searchParams)) {
            $viewer->assign('SEARCH_PARAMS',$searchParams);
		}
		echo $viewer->view('MassEditForm.tpl', $moduleName, true);
	}
	/**
	 * Function returns the mass edit form
	 * @param Vtiger_Request $request
	 */
	function showMassEditForm(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$viewer = $this->getViewer($request);
		$this->initMassEditViewContents($request);
		
		echo $viewer->view('MassEditForm.tpl', $moduleName, true);
	}

	function initMassEditViewContents(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$cvId = $request->get('viewname');
		$selectedIds = $request->get('selected_ids');
		$excludedIds = $request->get('excluded_ids');
        $tagParams = $request->get('tag_params');
		if(empty($tagParams)){
            $tagParams = array();
        }

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
		$recordStructure = $recordStructureInstance->getStructure();
		foreach($recordStructure as $blockName => $fields) {
			if(empty($fields)) {
				unset($recordStructure[$blockName]);
			}
		}

		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE',Vtiger_Functions::jsonEncode($picklistDependencyDatasource));
		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		$viewer->assign('MODE', 'massedit');
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('CVID', $cvId);
		$viewer->assign('SELECTED_IDS', $selectedIds);
		$viewer->assign('EXCLUDED_IDS', $excludedIds);
        $viewer->assign('TAG_PARAMS', $tagParams);
		$viewer->assign('VIEW_SOURCE','MASSEDIT');
		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
		$viewer->assign('MODULE_MODEL',$moduleModel); 
		$viewer->assign('MASS_EDIT_FIELD_DETAILS',$fieldInfo); 
		$viewer->assign('RECORD_STRUCTURE', $recordStructure);
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
        $viewer->assign('MODULE_MODEL', $moduleModel);
        //do not show any image details in mass edit form
        $viewer->assign('IMAGE_DETAILS', array());
        $searchKey = $request->get('search_key');
        $searchValue = $request->get('search_value');
		$operator = $request->get('operator');
        if(!empty($operator)) {
			$viewer->assign('OPERATOR',$operator);
			$viewer->assign('ALPHABET_VALUE',$searchValue);
            $viewer->assign('SEARCH_KEY',$searchKey);
		}
        $searchParams = $request->get('search_params');
        if(!empty($searchParams)) {
            $viewer->assign('SEARCH_PARAMS',$searchParams);
		}
		$viewer->assign('VIEW_MODE', 'LISTING');
		$viewer->assign('SCRIPTS', $this->getHeaderScripts($request));
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
        $tagParams = $request->get('tag_params');
        if(empty($tagParams)){
            $tagParams = array();
        }

		$viewer = $this->getViewer($request);
		$viewer->assign('SOURCE_MODULE', $sourceModule);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('CVID', $cvId);
		$viewer->assign('SELECTED_IDS', $selectedIds);
		$viewer->assign('EXCLUDED_IDS', $excludedIds);
        $viewer->assign('TAG_PARAMS', $tagParams);
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
        
        $modCommentsModel = Vtiger_Module_Model::getInstance($moduleName);
		$fileNameFieldModel = Vtiger_Field::getInstance("filename", $modCommentsModel);
        $fileFieldModel = Vtiger_Field_Model::getInstanceFromFieldObject($fileNameFieldModel);
        
        
        $searchKey = $request->get('search_key');
        $searchValue = $request->get('search_value');
		$operator = $request->get('operator');
        if(!empty($operator)) {
			$viewer->assign('OPERATOR',$operator);
			$viewer->assign('ALPHABET_VALUE',$searchValue);
            $viewer->assign('SEARCH_KEY',$searchKey);
		}

        $searchParams = $request->get('search_params');
        if(!empty($searchParams)) {
            $viewer->assign('SEARCH_PARAMS',$searchParams);
        }
        $viewer->assign('FIELD_MODEL', $fileFieldModel);
        $viewer->assign('MAX_UPLOAD_LIMIT_MB', Vtiger_Util_Helper::getMaxUploadSize());
		$viewer->assign('MAX_UPLOAD_LIMIT_BYTES', Vtiger_Util_Helper::getMaxUploadSizeInBytes());

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
		$relatedLoad = $request->get('relatedLoad');
        $tagParams = $request->get('tag_params');
		if(empty($tagParams)){
            $tagParams = array();
        }
		
		$emailFieldsInfo = $this->getEmailFieldsInfo($request);
		$viewer = $this->getViewer($request);
		$viewer->assign('EMAIL_FIELDS_INFO', $emailFieldsInfo);
		$viewer->assign('MODULE', $moduleName);
        $viewer->assign('SOURCE_MODULE',$sourceModule);
		$viewer->assign('VIEWNAME', $cvId);
		$viewer->assign('SELECTED_IDS', $selectedIds);
		$viewer->assign('EXCLUDED_IDS', $excludedIds);
        $viewer->assign('TAG_PARAMS', $tagParams);
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
        $viewer->assign('SELECTED_EMAIL_SOURCE_MODULE', $sourceModule);
        
        $searchKey = $request->get('search_key');
        $searchValue = $request->get('search_value');
		$operator = $request->get('operator');
        if(!empty($operator)) {
			$viewer->assign('OPERATOR',$operator);
			$viewer->assign('ALPHABET_VALUE',$searchValue);
            $viewer->assign('SEARCH_KEY',$searchKey);
		}
        
        $searchParams = $request->get('search_params');
        if(!empty($searchParams)) {
            $viewer->assign('SEARCH_PARAMS',$searchParams);
        }

		$to = $request->get('to');
		if (!$to) {
			$to = array();
		}
		$viewer->assign('TO', $to);

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
			echo $viewer->view('SelectEmailFields.tpl', $request->getModule(), true);
			exit;
		}
	}
	
	protected function getEmailFieldsInfo(Vtiger_Request $request) {
		$sourceModule = $request->getModule();
		$emailFieldsInfo = array();
		$moduleModel = Vtiger_Module_Model::getInstance($sourceModule);
		$recipientPrefModel = Vtiger_RecipientPreference_Model::getInstance($sourceModule);
		
		if($recipientPrefModel)
		$recipientPrefs = $recipientPrefModel->getPreferences();
		$moduleEmailPrefs = $recipientPrefs[$moduleModel->getId()];
		$emailFields = $moduleModel->getFieldsByType('email');
        $accesibleEmailFields = array();
		
        foreach($emailFields as $index=>$emailField) {
            $fieldName = $emailField->getName();
            if($emailField->isViewable()) {
				if($moduleEmailPrefs && in_array($emailField->getId(),$moduleEmailPrefs)){
					$emailField->set('isPreferred',true);
				}
                $accesibleEmailFields[$fieldName] = $emailField;
            }
        }
		
        $emailFields = $accesibleEmailFields;
        if(count($emailFields) > 0) {
            $recordIds = $this->getRecordsListFromRequest($request);
			global $current_user;
            $baseTableId = $moduleModel->get('basetableid');
            $queryGen = new QueryGenerator($moduleModel->getName(), $current_user);
			$selectFields = array_keys($emailFields);
            array_push($selectFields,'id');
			$queryGen->setFields($selectFields);
			$query = $queryGen->getQuery();
            $query =  $query.' AND crmid IN ('.  generateQuestionMarks($recordIds).')';
			$emailOptout = $moduleModel->getField('emailoptout');
			if($emailOptout) {
				$query .= ' AND '.$emailOptout->get('column').' = 0';
			}
			
            $db = PearDatabase::getInstance();
            $result = $db->pquery($query,$recordIds);
            $num_rows = $db->num_rows($result);
			
			if($num_rows > 0) {
				for($i=0;$i<$num_rows;$i++){
					$emailFieldsList = array();
					foreach ($emailFields as $emailField) {
						$emailValue = $db->query_result($result, $i, $emailField->get('column')) ;
						if(!empty($emailValue)) {
							$emailFieldsList[$emailValue] = $emailField;
						}
					}
					if(!empty($emailFieldsList)) {
                        $recordId = $db->query_result($result, $i,$baseTableId);
						$emailFieldsInfo[$moduleModel->getName()][$recordId] = $emailFieldsList;
					}
				}
			}
        }
		$viewer = $this->getViewer($request);
		$viewer->assign('RECORDS_COUNT', count($recordIds));
		
		if($recipientPrefModel && !empty($recipientPrefs)) {
			$viewer->assign('RECIPIENT_PREF_ENABLED',true);
		}

		$viewer->assign('EMAIL_FIELDS', $emailFields);

		$viewer->assign('PREF_NEED_TO_UPDATE',  $this->isPreferencesNeedToBeUpdated($request));
		return $emailFieldsInfo;
	}
	
	protected function isPreferencesNeedToBeUpdated(Vtiger_Request $request) {
		$sourceModule = $request->getModule();
		$moduleModel = Vtiger_Module_Model::getInstance($sourceModule);
		$recipientPrefModel = Vtiger_RecipientPreference_Model::getInstance($sourceModule);
		$status = false;
		
		if(!$recipientPrefModel) return $status;
		$recipientPrefs = $recipientPrefModel->getPreferences();
		if(empty($recipientPrefs))	return true;
		$moduleEmailPrefs = $recipientPrefs[$moduleModel->getId()];
		if(!$moduleEmailPrefs) return $status;
		foreach ($moduleEmailPrefs as $fieldId) {
			$field = Vtiger_Field_Model::getInstance($fieldId, $moduleModel);
			if($field) {
				if(!$field->isActiveField()) {
					$status = true;
				}
			}else{
				$status = true;
			}
		}
		return $status;
	}

	/**
	 * Function shows form that will lets you send SMS
	 * @param Vtiger_Request $request
	 */
	function showSendSMSForm(Vtiger_Request $request) {

		$sourceModule = $request->getModule();
		$moduleName = 'SMSNotifier';

		$isCreateAllowed = Users_Privileges_Model::isPermitted($moduleName, 'CreateView');
		if(!$isCreateAllowed) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		
		$viewer = $this->getViewer($request);

		require_once 'modules/SMSNotifier/SMSNotifier.php';
		if (!SMSNotifier::checkServer()) {
			$viewer->assign('TITLE', vtranslate('LBL_SEND_SMS', $moduleName));
			$viewer->assign('BODY', vtranslate('LBL_NOT_ACCESSIBLE', $moduleName));
			echo $viewer->view('NotAccessible.tpl', $moduleName, true);
			exit;
		}

		$selectedIds = $this->getRecordsListFromRequest($request);
		$excludedIds = $request->get('excluded_ids');
		$cvId = $request->get('viewname');

		$user = Users_Record_Model::getCurrentUserModel();
        $moduleModel = Vtiger_Module_Model::getInstance($sourceModule);
        $phoneFields = $moduleModel->getFieldsByType('phone');
		
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

        $searchParams = $request->get('search_params');
        if(!empty($searchParams)) {
            $viewer->assign('SEARCH_PARAMS',$searchParams);
        }
        
		echo $viewer->view('SendSMSForm.tpl', $moduleName, true);
	}

	/**
	 * Function returns the record Ids selected in the current filter
	 * @param Vtiger_Request $request
	 * @return integer
	 */
	function getRecordsListFromRequest(Vtiger_Request $request, $module = false) {
		$cvId = $request->get('viewname');
		$selectedIds = $request->get('selected_ids');
		$excludedIds = $request->get('excluded_ids');
        if(empty($module)) {
            $module = $request->getModule();
        }
		if(!empty($selectedIds) && $selectedIds != 'all') {
			if(!empty($selectedIds) && count($selectedIds) > 0) {
				return $selectedIds;
			}
		}
        $tagParams = $request->get('tag_params');
		$tag = $request->get('tag');
		$listViewSessionKey = $module.'_'.$cvId;

		if(!empty($tag)) {
			$listViewSessionKey .='_'.$tag;
		}

		$orderParams = Vtiger_ListView_Model::getSortParamsSession($listViewSessionKey);
		if(!empty($tag) && empty($tagParams)){
			$tagParams = $orderParams['tag_params'];
		}

		if(empty($tagParams)){
			$tagParams = array();
		}
		$searchParams = $request->get('search_params');
		if(empty($searchParams) && !is_array($searchParams)){
			$searchParams = array();
		}
		$searchAndTagParams = array_merge($searchParams, $tagParams);
		
		$sourceRecord = $request->get('sourceRecord');
		$sourceModule = $request->get('sourceModule');
		if ($sourceRecord && $sourceModule) {
			$sourceRecordModel = Vtiger_Record_Model::getInstanceById($sourceRecord, $sourceModule);
			return $sourceRecordModel->getSelectedIdsList($module, $excludedIds);
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
            $customViewModel->set('search_params', $searchAndTagParams);
			return $customViewModel->getRecordIds($excludedIds,$module);
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
	  //$jobexpencereportid = $adb_e->getLastInsertID();
	  $jobexpencereportid = $current_id;

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
	  //$jobexpencereportcfid = $adb_ecf->getLastInsertID();
	  $jobexpencereportcfid = $current_id;

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
	  //$jobexpencereportid = $adb_e->getLastInsertID();
	  $jobexpencereportid =$current_id;

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
	  //$jobexpencereportcfid = $adb_ecf->getLastInsertID();
	  $jobexpencereportcfid = $current_id;

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

	    
	 public function print_packaging_material($record) {
		global $adb;
		$moduleName = 'PackagingMaterial';
		$record 	 =  $record;
		
			$parentid   = 0;
			if($parentid==0){
				$parentid = $this->get_job_id_from_PackagingMaterial($record);
			}
	
			$packaging_record_id = $record;
			$current_user = Users_Record_Model::getCurrentUserModel();
			
			$job_info_detail = Vtiger_Record_Model::getInstanceById($parentid, 'Job');
			
			$packaging_info_detail = Vtiger_Record_Model::getInstanceById($packaging_record_id, 'PackagingMaterial');
				
			$document = $this->loadTemplate('printtemplates/whm/packaging_request.html');
			
			$owner_user_info = Users_Record_Model::getInstanceById($packaging_info_detail->get('assigned_user_id'), 'Users');
			
			$this->setValue('useroffice',$owner_user_info->getDisplayValue('location_id'));
			$this->setValue('userdepartment',$owner_user_info->getDisplayValue('department_id'));
			$this->setValue('mobile',$owner_user_info->get('phone_mobile'));
			$this->setValue('fax',$owner_user_info->get('phone_fax'));
			$this->setValue('email',htmlentities($owner_user_info->get('email1'), ENT_QUOTES, "UTF-8"));
			$this->setValue('cityname',htmlentities($owner_user_info->getDisplayValue('location_id'), ENT_QUOTES, "UTF-8"));
			$this->setValue('countryname',htmlentities($owner_user_info->get('address_country'), ENT_QUOTES, "UTF-8"));
			$this->setValue('departmentcode',htmlentities($owner_user_info->getDisplayValue('department_id'), ENT_QUOTES, "UTF-8"));
			$this->setValue('dateadded',date('d.m.Y', strtotime($packaging_info_detail->get('CreatedTime'))));        
			$this->setValue('from', htmlentities($owner_user_info->get('first_name').' '.$owner_user_info->get('last_name'), ENT_QUOTES, "UTF-8"));
			
			$this->setValue('job_ref_no', $job_info_detail->get('cf_1198'));
			$this->setValue('packaging_ref_no',$packaging_info_detail->get('cf_5754')); 
			$this->setValue('warehouse_id',$packaging_info_detail->getDisplayValue('cf_5764'));
			
			$rs = $adb->pquery("SELECT cf_6294 FROM vtiger_packagingmaterialcf where cf_6294!='' AND cf_5754='".$packaging_info_detail->get('cf_5754')."' limit 1");
			$row_driver = $adb->fetch_array($rs);
			$driver_name = $row_driver['cf_6294'];
			$this->setValue('driver_name', $driver_name);
			
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
					if($pmitem_info->get('cf_5754')==$packaging_info_detail->get('cf_5754'))
					{
						$parent_numbering =$i;
						$packaging_items .='<tr>
											<td>'.$i++.'</td>
											<td>'.$pmitem_info->getDisplayValue('cf_5738').'</td>
											<td>'.$pmitem_info->getDisplayValue('cf_5740').'</td>
											<td>'.$pmitem_info->getDisplayValue('cf_5744').'</td>
											<td>&#x2610;</td>
											
											</tr>';
						//$total_amount +=$pmitem_info->get('cf_6142');
						
					}
					
			}
			
			
			$this->setValue('packaging_items',$packaging_items);
			$this->setValue('total_amount',$total_amount);
			
			include('include/mpdf60/mpdf.php');
			@date_default_timezone_set($current_user->get('time_zone'));
			
			
			$mpdf = new mPDF('utf-8', 'A4-L', '10', '', 10, 10, 30, 15, 10, 5); /*задаем формат, отступы и.т.д.*/
			$mpdf->charset_in = 'utf8';
			
			$mpdf->list_indent_first_level = 0; 
			//$filename = 'fleet_expense.txt';
			//$this->save('fleet_expense.txt'); 
			$mpdf->SetHTMLHeader('
			<table width="100%" cellpadding="0" cellspacing="0">
				<tr>
				<td align="right" style="font-size:9;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
					PMR Form, GLOBALINK
				</td>
				</tr>
				<tr>
				<td align="right"><img src="printtemplates/glklogo.jpg"/ width="160" height="30"></td>
				</tr>
			</table>');
			
			$mpdf->SetHTMLFooter('
			<table width="100%" cellpadding="0" cellspacing="0">
				<tr>
				<td width="40%" align="left" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
					Printed: '.date('d.m.Y; H:i').' by '.$current_user->get('user_name').'
				</td>
				<td width="20%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
					Page {PAGENO} of {nbpg}
				</td>
				<td width="40%" align="center" style="font-size:10;font-family:Verdana, Geneva, sans-serif;font-weight:bold;">
					&nbsp;
				</td>
				</tr>
			</table>');

			$stylesheet = file_get_contents('include/mpdf60/examples/mpdfstyletables.css');
			$mpdf->WriteHTML($stylesheet,1);  // The parameter 1 tells that this is css/style only and no body/html/text
			$mpdf->WriteHTML($this->_documentXML); /*формируем pdf*/
			
				
			$pdf_name = 'pdf_docs/packaging_material_'.$packaging_record_id.'.pdf';
			
			//return $content = $mpdf->Output('', 'S'); // Saving pdf to attach to email
			ob_clean();
			$mpdf->Output($pdf_name, 'F'); // Saving pdf to attach to email 
			// $mpdf->Output($pdf_name, 'F');
			//header('Location:http://mb.globalink.net/vt60/'.$pdf_name);
		// header('Location:'.$pdf_name);
    
  	}
	
	public function template($strFilename)
	  {
		$path = dirname($strFilename);
		//$this->_tempFileName = $path.time().'.docx';
		// $this->_tempFileName = $path.'/'.time().'.txt';
		$this->_tempFileName = $strFilename;
		//copy($strFilename, $this->_tempFileName); // Copy the source File to the temp File
		$this->_documentXML = file_get_contents($this->_tempFileName);
	  }
  
	  /**
	   * Set a Template value
	   * 
	   * @param mixed $search
	   * @param mixed $replace
	   */
	  public function setValue($search, $replace) {
		if(substr($search, 0, 2) !== '${' && substr($search, -1) !== '}') {
		  $search = '${'.$search.'}';
		}
		// $replace =  htmlentities($replace, ENT_QUOTES, "UTF-8");
		if(!is_array($replace)) {
		  // $replace = utf8_encode($replace);
		  $replace =iconv('utf-8', 'utf-8', $replace);
		}
		$this->_documentXML = str_replace($search, $replace, $this->_documentXML);
	  }
  
	  /**
	   * Save Template
	   * 
	   * @param string $strFilename
	   */
	  public function save($strFilename) {
		if(file_exists($strFilename)) {
		  unlink($strFilename);
		}
		//$this->_objZip->extractTo('Fleettrip.txt', $this->_documentXML);
		file_put_contents($this->_tempFileName, $this->_documentXML);
		// Close zip file
		/* if($this->_objZip->close() === false) {
		  throw new Exception('Could not close zip file.');
		}*/  
		rename($this->_tempFileName, $strFilename);
	  }
  
	  public function loadTemplate($strFilename) {
		if(file_exists($strFilename)) {
		  $template = $this->template($strFilename);
		  return $template;
		} else {
		  trigger_error('Template file '.$strFilename.' not found.', E_ERROR);
		}
	  } 
}
