<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Job_Save_Action extends Vtiger_Save_Action {

	public function requiresPermission(\Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$moduleParameter = $request->get('source_module');
		if (!$moduleParameter) {
			$moduleParameter = 'module';
		}else{
			$moduleParameter = 'source_module';
		}
		$record = $request->get('record');
		$recordId = $request->get('id');
		if (!$record) {
			$recordParameter = '';
		}else{
			$recordParameter = 'record';
		}
		$actionName = ($record || $recordId) ? 'EditView' : 'CreateView';
        $permissions[] = array('module_parameter' => $moduleParameter, 'action' => 'DetailView', 'record_parameter' => $recordParameter);
		$permissions[] = array('module_parameter' => $moduleParameter, 'action' => $actionName, 'record_parameter' => $recordParameter);
		return $permissions;
	}
	
	public function checkPermission(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$record = $request->get('record');

		$nonEntityModules = array('Users', 'Events', 'Calendar', 'Portal', 'Reports', 'Rss', 'EmailTemplates');
		if ($record && !in_array($moduleName, $nonEntityModules)) {
			$recordEntityName = getSalesEntityType($record);
			if ($recordEntityName !== $moduleName) {
				throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
			}
		}
		return parent::checkPermission($request);
	}
	
	public function validateRequest(Vtiger_Request $request) {
		return $request->validateWriteAccess();
	}

	public function process(Vtiger_Request $request) {
// 		echo '<pre>';
// 		print_r($request);
// exit;
		
		try {
			$current_user = Users_Record_Model::getCurrentUserModel();
		
			$job_status = $request->get('cf_2197');
			if($job_status=='Cancelled')
			{
				//if($current_user->getId()!=405 && $current_user->getId()!=463)
				if($current_user->getId()!=405 && $current_user->getId()!=420)
				{
					$request->set('cf_2197','Request For Cancellation');		
				}
			}
			$job_type = $request->get('cf_1200');
			if(empty($job_type))
			{
				$_SESSION['job_type'] = '1';			
				//$loadUrl = "index.php?module=Job&view=Edit";
				$moduleModel = $request->getModule();
				$loadUrl = $moduleModel->getCreateRecordUrl();
				header("Location: $loadUrl");
				exit;
			}
                //sahib add multiple container size  
			    $recordModel = $this->saveRecord($request);
                $container_type = $request->get('container_type');
                $container_size = $request->get('container_size');
                $container_qty = $request->get('container_qty');
                $container_fcl_lcl = $request->get('container_fcl_lcl');
           
               if(!empty($request->get('countId')))
               { 
	                if(count($request->get('contaierID')) ==  $request->get('countId'))
	                 {
	                 	 
		                if(!empty($request->get('contaierID'))){
		                	
		                	 for($i=0; $i<sizeof($request->get('contaierID')); $i++)
				             {
				             
								//echo sizeof($request->get('container_type'));
								$db = PearDatabase::getInstance();
								$jobid = $recordModel->getId();
								$db->pquery("UPDATE  job_container_type_size SET
								jobid = '".$jobid."',
								container_type ='".$container_type[$i]."',
								container_size ='".$container_size[$i]."',
								container_qty ='".$container_qty[$i]."',
								container_fcl_lcl ='".$container_fcl_lcl[$i]."'
		                        WHERE id = '".$request->get('contaierID')[$i]."'");  
								
				            }

		                }
		            } 
              
		            else if(count($request->get('contaierID')) >$request->get('countId'))
	                 {
	                 
	                 
	                    $count = $request->get('countId');
	                    for($i=$count; $i<sizeof($request->get('container_type')); $i++)
			             {
			              
							//echo sizeof($request->get('container_type'));
							$db = PearDatabase::getInstance();
							$jobid = $recordModel->getId();
							$db->pquery("INSERT  INTO job_container_type_size SET
							jobid = '".$jobid."',
							container_type ='".$container_type[$i]."',
							container_size ='".$container_size[$i]."',
							container_qty ='".$container_qty[$i]."',
							container_fcl_lcl ='".$container_fcl_lcl[$i]."'");  
							
			            }

	                 }
                }  
                else{
                   
		             for($i=0; $i<sizeof($request->get('container_type')); $i++)
		             {
		             
						//echo sizeof($request->get('container_type'));
						$db = PearDatabase::getInstance();
						$jobid = $recordModel->getId();
						$db->pquery("INSERT  INTO job_container_type_size SET
						jobid = '".$jobid."',
						container_type ='".$container_type[$i]."',
						container_size ='".$container_size[$i]."',
						container_qty ='".$container_qty[$i]."',
						container_fcl_lcl ='".$container_fcl_lcl[$i]."'");  
						
		            }
		     
                }
             
			$oog = $request->get('cf_7156');
			$recordId = $request->get('record');
			if ($oog=='Project/OOG Cargo' && empty($recordId)) {
					$savedRecordId = $recordModel->getId();
					//$this->send_email($savedRecordId);
					$this->send_email_oog($savedRecordId);
			}

			if ($request->get('returntab_label')){
				$loadUrl = 'index.php?'.$request->getReturnURL();
			} else if($request->get('relationOperation')) {
				$parentModuleName = $request->get('sourceModule');
				$parentRecordId = $request->get('sourceRecord');
				$parentRecordModel = Vtiger_Record_Model::getInstanceById($parentRecordId, $parentModuleName);
				//TODO : Url should load the related list instead of detail view of record
				$loadUrl = $parentRecordModel->getDetailViewUrl();
			} else if ($request->get('returnToList')) {
				$loadUrl = $recordModel->getModule()->getListViewUrl();
			} else if ($request->get('returnmodule') && $request->get('returnview')) {
				$loadUrl = 'index.php?'.$request->getReturnURL();
			} else {
				$loadUrl = $recordModel->getDetailViewUrl();
			}
			//append App name to callback url
			//Special handling for vtiger7.
			$appName = $request->get('appName');
			if(strlen($appName) > 0){
				$loadUrl = $loadUrl.$appName;
			}

			/*$recordId = $request->get('record');
			$oog_cargo = $request->get('cf_7156');
			if(empty($recordId) && $oog_cargo=='Project') {
				$job_record_Id = $recordModel->getId();
				$loadUrl = 'index.php?module=ProjectCargoCheckList&view=Edit&returnmode=showRelatedList&returntab_label=ProjectCargoCheckList&returnrecord='.$job_record_Id.'&returnmodule=Job&returnview=Detail&returnrelatedModuleName=ProjectCargoCheckList&returnrelationId=326&app=MARKETING';
			}*/
			header("Location: $loadUrl");
		} catch (DuplicateException $e) {
			$requestData = $request->getAll();
			$moduleName = $request->getModule();
			unset($requestData['action']);
			unset($requestData['__vtrftk']);

			if ($request->isAjax()) {
				$response = new Vtiger_Response();
				$response->setError($e->getMessage(), $e->getDuplicationMessage(), $e->getMessage());
				$response->emit();
			} else {
				$requestData['view'] = 'Edit';
				$requestData['duplicateRecords'] = $e->getDuplicateRecordIds();
				$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

				global $vtiger_current_version;
				$viewer = new Vtiger_Viewer();

				$viewer->assign('REQUEST_DATA', $requestData);
				$viewer->assign('REQUEST_URL', $moduleModel->getCreateRecordUrl().'&record='.$request->get('record'));
				$viewer->view('RedirectToEditView.tpl', 'Vtiger');
			}
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}

	/**
	 * Function to save record
	 * @param <Vtiger_Request> $request - values of the record
	 * @return <RecordModel> - record Model of saved record
	 */
	public function saveRecord($request) {
	
		$current_user = Users_Record_Model::getCurrentUserModel();	
       
		
	
		//$current_user = Users_Record_Model::getCurrentUserModel();
		//$request->set('cf_1188', $current_user->get('location_id'));
		$recordId = $request->get('record');
		$old_job_status = '';
		if(!empty($recordId)) {
			$moduleName = $request->getModule();
			$recordModel_old = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
			$old_job_status = $recordModel_old->get('cf_2197');

			$old_customer_id = $recordModel_old->get('cf_1441');
			$new_costomer_id = $request->get('cf_1441');
			if($old_customer_id != $new_costomer_id)
			{
				//customer status and sales person profile update
				$customer_details = Vtiger_Record_model::getInstanceById($request->get('cf_1441'), 'Accounts');
				//echo "<pre>"; print_r($customer_details->get('assigned_user_id')); exit;
				$sale_person = $customer_details->get('assigned_user_id');
				$job_department = $request->get('cf_1190');
				if($job_department == '85836') //AXG
				{
					$customer_status = $customer_details->get('cf_4785');
				}
				elseif($job_department == '85841') //RTD
				{
					$customer_status = $customer_details->get('cf_4789');
				}
				elseif($job_department == '85837') //CTD
				{
					$customer_status = $customer_details->get('cf_4793');
				}
				elseif($job_department == '85838') //PRO
				{
					$customer_status = $customer_details->get('cf_4787');
				}
				elseif($job_department == '85840') //RRS
				{
					$customer_status = $customer_details->get('cf_4791');
				}
				elseif($job_department == '85844') //FLT
				{
					$customer_status = $customer_details->get('cf_5155');
				}
				else //any other department key account manager
				{
					$customer_status = $customer_details->get('cf_725');
				}
				if($customer_status == "")
				$customer_status = $customer_details->get('cf_725');
				if($customer_status == 'Lost' || $customer_status == 'lost client')
				$customer_status = "Lost and Found";
				if($customer_status == 'No Business' || $customer_status == 'no business')
				$customer_status = "New Client";
				$request->set('cf_6934', str_replace("And","and",ucwords($customer_status)));
				$request->set('cf_6936', $sale_person);
				//customer status and sales person profile update
			}
			$adb = PearDatabase::getInstance();
			
			$new_job_status = $request->get('cf_2197');
			
			if($new_job_status=='Completed' || $new_job_status=='Request For Cancellation')
			{
				//if($current_user->getId()!=405 && $current_user->getId()!=463)
				if($current_user->getId()!=405 && $current_user->getId()!=420)
				{	
				$jrer_e_sum_sql =  "SELECT sum(vtiger_jobexpencereportcf.cf_1349) as buy_local_currency_net
										 FROM `vtiger_jobexpencereport` 
								  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
								  INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid ) 
								  LEFT JOIN vtiger_jobexpencereportcf as vtiger_jobexpencereportcf 
								  ON vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid 
								  WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? AND vtiger_crmentityrel.module='Job' 
								  AND vtiger_crmentityrel.relmodule='Jobexpencereport' AND vtiger_jobexpencereportcf.cf_1457='Expence'";			
				$jrer_e_sum_sql .=' AND vtiger_jobexpencereport.owner_id = "'.$recordModel_old->get('assigned_user_id').'" ' ;
				$params_e = array($recordId);
				
				$result_e = $adb->pquery($jrer_e_sum_sql, $params_e);
				$row_job_e_jrer = $adb->fetch_array($result_e);
				$buy_local_currency_net = $row_job_e_jrer['buy_local_currency_net'];
				
				$jrer_selling_sum_sql =  "SELECT sum(vtiger_jobexpencereportcf.cf_1240) as sell_local_currency_net
										 FROM `vtiger_jobexpencereport` 
										 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
										 INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid ) 
										 LEFT JOIN vtiger_jobexpencereportcf as vtiger_jobexpencereportcf  
										 ON vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid 
										 WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid=? AND vtiger_crmentityrel.module='Job' 
										 AND vtiger_crmentityrel.relmodule='Jobexpencereport' and vtiger_jobexpencereportcf.cf_1457='Selling'";			
				$jrer_selling_sum_sql .=' AND vtiger_jobexpencereport.owner_id = "'.$recordModel_old->get('assigned_user_id').'" ' ;
				$params_selling = array($recordId);
				$result_selling = $adb->pquery($jrer_selling_sum_sql, $params_selling);
				$row_job_jrer_selling = $adb->fetch_array($result_selling);	
				$sell_local_currency_net = $row_job_jrer_selling['sell_local_currency_net'];
				
					if($new_job_status=='Completed')
					{
						
						if($buy_local_currency_net==0 && $sell_local_currency_net==0) //to check actual expense and revenue
						{
							$_SESSION['job_status_'.$recordId] = '1';
							//$loadUrl = "index.php?module=Job&view=Edit&record=".$recordId."";								
							$loadUrl = $recordModel_old->getEditViewUrl();	
							//append App name to callback url
							//Special handling for vtiger7.
							$appName = $request->get('appName');
							if(strlen($appName) > 0){
								$loadUrl = $loadUrl.$appName;
							}
							header("Location: $loadUrl");							
							exit;
						}
						
						$check_profit = $sell_local_currency_net - $buy_local_currency_net;
						
						if($check_profit<=0) // to check job in loss
						{
							$_SESSION['job_status_'.$recordId] = '2';
							//$loadUrl = "index.php?module=Job&view=Edit&record=".$recordId."";					
							//header("Location: $loadUrl");
							$loadUrl = $recordModel_old->getEditViewUrl();	
							//append App name to callback url
							//Special handling for vtiger7.
							$appName = $request->get('appName');
							if(strlen($appName) > 0){
								$loadUrl = $loadUrl.$appName;
							}
							header("Location: $loadUrl");
							exit;
						}
						
						//“Deviations Cost” and “Deviation Revenue”.
					
						$deviation = $this->deviation($recordId, $recordModel_old);
						$deviation_cost = ($deviation['actual_expense_cost_usd']>0 ? (($deviation['actual_expense_cost_usd'] - $deviation['expected_cost_usd'])/$deviation['actual_expense_cost_usd']) : '0' );
						$deviation_revenue = ($deviation['actual_selling_cost_usd']>0 ? (($deviation['actual_selling_cost_usd'] - $deviation['expected_revenue_usd'])/$deviation['actual_selling_cost_usd']) : '0');
						$expense_deviation = $deviation_cost*100;
						$selling_deviation = $deviation_revenue*100;
						
						if($expense_deviation > 10 || $expense_deviation < (-10))
						{
							$_SESSION['job_deviation_cost_'.$recordId] = '1';
						}
						
						if($selling_deviation > 10 || $selling_deviation < (-10))
						{
							$_SESSION['job_deviation_revenue_'.$recordId] = '1';
						}
						
					}
					
					if($new_job_status=='Request For Cancellation')
					{
						
						if($buy_local_currency_net>0 || $sell_local_currency_net>0)//check if actual revenue and expense exist.. must be zero
						{
							$_SESSION['job_status_'.$recordId] = '3'; // Create credit memo		
							//echo $_SESSION['job_status_'.$recordId];
							//exit;					
							$request->set('cf_2197',$old_job_status);
							//$loadUrl = "index.php?module=Job&view=Edit&record=".$recordId."";			
							//header("Location: $loadUrl");
							$loadUrl = $recordModel_old->getEditViewUrl();	
							//append App name to callback url
							//Special handling for vtiger7.
							$appName = $request->get('appName');
							if(strlen($appName) > 0){
								$loadUrl = $loadUrl.$appName;
							}
							header("Location: $loadUrl");
							exit;
						}
						//
					}
					
					
				}				
				
				if($new_job_status=='Completed')
				{
					$request->set('cf_4805', (empty($recordModel_old->get('cf_4805')) ? date('Y-m-d') :$recordModel_old->get('cf_4805')));
				}
			
			}		
		}
		
		$recordModel = $this->getRecordModelFromRequest($request);
		if($request->get('imgDeleted')) {
			$imageIds = $request->get('imageid');
			foreach($imageIds as $imageId) {
				$status = $recordModel->deleteImage($imageId);
			}
		}
		$recordModel->save();
		$this->scheduleJobFileForInteration($recordModel->get('id'), $recordModel->get('cf_2197'));
		
		if($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			$parentModuleModel = Vtiger_Module_Model::getInstance($parentModuleName);
			$parentRecordId = $request->get('sourceRecord');
			$relatedModule = $recordModel->getModule();
			$relatedRecordId = $recordModel->getId();
			if($relatedModule->getName() == 'Events'){
				$relatedModule = Vtiger_Module_Model::getInstance('Calendar');
			}

			$relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relatedModule);
			$relationModel->addRelation($parentRecordId, $relatedRecordId);

			// If Job file created from Quotation, then change QT status to Secured
			if (($parentModuleName == 'Quotes') && ($parentRecordId > 0)){
				// Ruslan code 10.01.2017
				$db = PearDatabase::getInstance();
				$db->pquery(" UPDATE `vtiger_quotes` SET `quotestage` = 'Secured' WHERE `quoteid`= $parentRecordId");
			}
		}
		elseif($request->get('returnmodule') == 'Quotes' && $request->get('returnrecord')) {
			$parentModuleName = $request->get('returnmodule');
			$parentRecordId = $request->get('returnrecord');
			$parentModuleModel = Vtiger_Module_Model::getInstance($parentModuleName);
			$relatedModule = $recordModel->getModule();
			$relatedRecordId = $recordModel->getId();
			$relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relatedModule);
			$relationModel->addRelation($parentRecordId, $relatedRecordId);

			// If Job file created from Quotation, then change QT status to Secured
			if (($parentModuleName == 'Quotes') && ($parentRecordId > 0)){
				// Ruslan code 10.01.2017
				$db = PearDatabase::getInstance();
				$db->pquery(" UPDATE `vtiger_quotes` SET `quotestage` = 'Secured' WHERE `quoteid`= $parentRecordId");
			}
		}

		


		$this->savedRecordId = $recordModel->getId();
		$recordId = $request->get('record');

		if(empty($recordId)) {

			//customer status and sales person profile update
			$customer_details = Vtiger_Record_model::getInstanceById($request->get('cf_1441'), 'Accounts');
			$sale_person = $customer_details->get('assigned_user_id');
			$job_department = $request->get('cf_1190');

			if($job_department == '85836') //AXG
			{
				$customer_status = $customer_details->get('cf_4785');
			}
			elseif($job_department == '85841') //RTD
			{
				$customer_status = $customer_details->get('cf_4789');
			}
			elseif($job_department == '85837') //CTD
			{
				$customer_status = $customer_details->get('cf_4793');
			}
			elseif($job_department == '85838') //PRO
			{
				$customer_status = $customer_details->get('cf_4787');
			}
			elseif($job_department == '85840') //RRS
			{
				$customer_status = $customer_details->get('cf_4791');
			}
			elseif($job_department == '85844') //FLT
			{
				$customer_status = $customer_details->get('cf_5155');
			}
			else //any other department key account manager
			{
				$customer_status = $customer_details->get('cf_725');
			}

			if($customer_status == "")
			$customer_status = $customer_details->get('cf_725');
			if($customer_status == 'Lost' || $customer_status == 'lost client')
			$customer_status = "Lost and Found";
			if($customer_status == 'No Business' || $customer_status == 'no business')
			$customer_status = "New Client";
			//customer status and sales person profile update

			$db = PearDatabase::getInstance();
			//sleep(4);
			$db->startTransaction();
			$sql =  'SELECT MAX(serial_number) as max_ordering from vtiger_job 
					 INNER JOIN vtiger_jobcf ON vtiger_jobcf.jobid = vtiger_job.jobid
					 where vtiger_job.year_no=? AND vtiger_jobcf.cf_1200=?';
				 
			$value = date('Y');
			$params = array($value, $request->get('cf_1200'));
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
			$serial_number = $ordering+1;	
			//$db->pquery('update vtiger_job set year_no=?, serial_number = ? where jobid=?', array( date('Y'), $serial_number, $recordModel->getId() ) );
		
			$location_of_branch = Vtiger_LocationList_UIType::getDisplayValue($request->get('cf_1188'));
			$department = Vtiger_DepartmentList_UIType::getDepartment($request->get('cf_1190'));
			
			//$ref_no = strtoupper(substr($_POST['cf_1200'],0,3)).'-'.strtoupper($location_of_branch).'-'.strtoupper($department).'-'.str_pad($serial_number, 5, "0", STR_PAD_LEFT).'/'.date('y');
			$ref_no = strtoupper(substr($_POST['cf_1200'],0,3)).'-'.strtoupper($department).'-'.str_pad($serial_number, 5, "0", STR_PAD_LEFT).'/'.date('y');
			//$db->pquery('update vtiger_jobcf set cf_2197=?, cf_1198 = ? ,cf_6934 = ?, cf_6936 = ? where jobid=?', array('No Costing', $ref_no,str_replace("And","and",ucwords($customer_status)), $sale_person, $recordModel->getId()));

			 $db->pquery('update vtiger_jobcf set cf_6934 = ?, cf_6936 = ? where jobid=?', array(str_replace("And","and",ucwords($customer_status)), $sale_person, $recordModel->getId()));
			
			//cf_1853: Account Agreement Number
			$account_id = $request->get('cf_1441');
			$account_info = Vtiger_Record_Model::getInstanceById($account_id, 'Accounts');
			$agreement_no_job = $request->get('cf_1879');
			if(empty($agreement_no_job))
			{
				$db->pquery('UPDATE vtiger_jobcf SET cf_1879=? WHERE jobid=?', array($account_info->get('cf_1853'), $recordModel->getId()));
			}
			
			$db->completeTransaction();
			
			//Add Job Assignment Record for Owner
			$jobid = $recordModel->getId();
			$assigned_to_id='';
			$this->job_assignment($jobid, '1', $assigned_to_id);
			
			//Add Job Assignment Record for Inna Terzidi to Add Truck Fuel Expense for CTD and RRS department
			$location_id = $request->get('cf_1188'); //85805:: ALA
			$department_id = $request->get('cf_1190'); // 85837::CTD, 85840::RRS
			$job_file_title = $request->get('cf_1186');//file title 85757:: KZ
			//if($job_file_title=='85757' && $location_id=='85805' && ($department_id=='85837' || $department_id=='85840'))
			if($location_id=='85805' && ($department_id=='85837' || $department_id=='85840'))
			{
				$assigned_to_id = 458; //Inna Terzidi
				$this->job_assignment($jobid, '0', $assigned_to_id);
				
				if($department_id=='85837')
				{				
					$assigned_to_id = 615; //Zinaida Smelykh
					$this->job_assignment($jobid, '0', $assigned_to_id);
				}
				
				if($department_id=='85840') //Job Assignment For RRS Supervisor
				{
					$result_group = $db->pquery("SELECT * FROM `vtiger_users2group` where groupid='1187'");//ALA RRS Job Assignment (Group)
					for($ik=0; $ik<$db->num_rows($result_group); $ik++) {
						 $assigned_to_id = $db->query_result($result_group, $ik, 'userid');
						 $this->job_assignment($jobid, '0', $assigned_to_id);
					}					
				}
				
			}			
		
		}
		else{		
				
			if($current_user->getId()==405 && $recordModel->get('cf_1188')!='85805') // check department not equal to ALA
			{
				$old_department = $recordModel_old->get('cf_1190');
				$new_department = $request->get('cf_1190');
				
				
				if($old_department!=$new_department)
				{
					$new_department_code = Vtiger_DepartmentList_UIType::getDepartment($request->get('cf_1190'));
					$old_department_code = Vtiger_DepartmentList_UIType::getDepartment($old_department);
					
					$old_ref_no = $recordModel->get('cf_1198');
					$old_department_code = '-'.strtoupper($old_department_code).'-';
					$new_department_code = '-'.strtoupper($new_department_code).'-';
					$new_ref_no = str_replace($old_department_code, $new_department_code, $old_ref_no);
					
					$db = PearDatabase::getInstance();
					$db->pquery('update vtiger_jobcf set cf_1198 = ? where jobid=?', array($new_ref_no, $recordModel->getId()));
				}
				
			}
			
			$new_job_status = $request->get('cf_2197');
			
			if($new_job_status=='Request For Cancellation' || $new_job_status=='Request For Revision')
			{	
				$_SESSION['new_job_status_'.$recordId] = $new_job_status;			
				$loadUrl = $recordModel->getEditViewUrl();
				//append App name to callback url
				//Special handling for vtiger7.
				$appName = $request->get('appName');
				if(strlen($appName) > 0){
					$loadUrl = $loadUrl.$appName;
				}		
				header("Location: $loadUrl");
				exit;
			}			
			else if($old_job_status =='Passed to Archive' || $old_job_status=='Completed' || $old_job_status=='Cancelled' || $new_job_status=='Cancelled' || $old_job_status=='Request For Cancellation')
			{
				$_SESSION['new_job_status_'.$recordId] = '';
				$db = PearDatabase::getInstance();	
				if($new_job_status !='Passed to Archive')
				{			
					$db->pquery("update vtiger_jobcf set cf_2197='".$old_job_status."' where jobid='".$recordId."'");
				}
				//$current_user = Users_Record_Model::getCurrentUserModel();
				//Bakytgul Umtaliyeva[405] || Makpal Zhumakanova[463] || Maral[420] (can revise job status only)
				//if($current_user->getId()==405 || $current_user->getId()==463)
				if($current_user->getId()==405 || $current_user->getId()==420)
				{
					$db->pquery("update vtiger_jobcf set cf_2197='".$request->get('cf_2197')."' where jobid='".$recordId."'");
				}
				else{
					if($new_job_status !='Passed to Archive')
					{				
						//session_unregister('new_job_status');
						$_SESSION['new_job_status_'.$recordId] = $new_job_status;		
						$loadUrl = $recordModel->getEditViewUrl();
						//append App name to callback url
						//Special handling for vtiger7.
						$appName = $request->get('appName');
						if(strlen($appName) > 0){
							$loadUrl = $loadUrl.$appName;
						}					
						header("Location: $loadUrl");
						exit;
					}
				}
			}
			
			
			//if($current_user->getId()==405 || $current_user->getId()==463)
			if($current_user->getId()==405 || $current_user->getId()==420)
			{	
				if($new_job_status=='Revision')
				 {
					 	$assigned_user_id = $recordModel_old->get('assigned_user_id');
						$job_user_info = Vtiger_Record_Model::getInstanceById($assigned_user_id, 'Users');
						
						//For Email notification to BFM and accountant
						$branch_bfm_emails = array();
						if($recordModel_old->get('cf_1188')!='85805')//Restricted for ALA branch
						{
							//SELECT distinct(email1) FROM `vtiger_users` where company_id=85764 and location_id=85816 and department_id IN(85843, 85842)
							$db_n = PearDatabase::getInstance();
							$query_notification = "SELECT DISTINCT(email1) FROM `vtiger_users` WHERE company_id=? AND location_id=? AND department_id IN(85843, 85842) AND status=? ";
							$params_notification = array($job_user_info->get('company_id'), $job_user_info->get('location_id'), 'Active');
							$result_notification = $db_n->pquery($query_notification, $params_notification);
							$numRows_notification = $db_n->num_rows($result_notification);
							//$branch_bfm_emails[] = 's.mehtab@globalinklogistics.com';
							for($jj=0; $jj< $db_n->num_rows($result_notification); $jj++ ) {
								$row_notification = $db_n->fetch_row($result_notification,$jj);
								$branch_bfm_emails[] = $row_notification['email1'];
							}
						}
						
						
						$body = '';

						global $site_URL;
						
						$body .="<p>Dear&nbsp;".$job_user_info->get('first_name').",</p>";
						$body .="<p>Your reqeust for Revision has been approved, please update the job and don&#39;t forget to mark it as completed.<br />";
						$body .="Please put in comment section what you revised.</p>";
						if(!empty($branch_bfm_emails))
						{
						$body .="<p>Dear BFM,</p>";
						$body .="<p>Please note that ".$job_user_info->get('first_name')." ".$job_user_info->get('last_name')." received access for revision of JF: <a href='".$site_URL."index.php?module=Job&view=Detail&record=".$recordId."&app=JOBS' target='_blank'>".$recordModel_old->get('cf_1198')."</a></p>";
						}
						$body .="<p>Regards,</p>";
						$body .="<p><strong>Bakhytgul Umtaliyeva</strong>, Manager Job Files Control and Audit</p>";
						$body .="<p><strong>Globalink Logistics - </strong>52, Kabanbai Batyr Street, 050010, Almaty, Kazakhstan&nbsp;<br />";
						$body .="Tel.: + 7727 258 88 80, ext 217; Mob.: +7 701 737 8541<br />";
						$body .="<u><a href='mailto:b.umtaliyeva@globalinkllc.com'>b.umtaliyeva@globalinklogistics.com</a></u>&nbsp; <strong>I&nbsp;</strong> Web: <u><a href='http://www.globalinklogistics.com/'>www.globalinklogistics.com</a></u><br />";
						$body .="ASIA SPECIALIST ∙ CHINA FOCUS ∙ GLOBAL NETWORK<br />";
						$body .="Important Notice. All Globalink services are undertaken subject to Globalink&#39;s Terms and Conditions of Trading. These may exclude or limit our liability in the event of claims for loss, damage and delay to cargo or otherwise and provide for all disputes to be arbitrated in London under English law.&nbsp; Please view and download our Terms and Conditions of Trading from our website <a href='http://globalinklogistics.com/Trading-Terms-and-Conditions'>http://globalinklogistics.com/Trading-Terms-and-Conditions</a></p>";
						
						//$from = "From: ".$current_user->get('email1')." <".$current_user->get('email1').">";
						$from = $current_user->get('email1');
						$to = $job_user_info->get('email1');
						//$to = 's.mehtab@globalinklogistics.com';
						//$cc  = 's.mehtab@globalinklogistics.com';
						$cc= '';
						//if(!empty($branch_bfm_emails))
						//{
						//	$cc = implode(",", $branch_bfm_emails);
						//}
					    $headers = "MIME-Version: 1.0" . "\n";
					    $headers .= "Content-type:text/html;charset=UTF-8" . "\n";
					    $headers .= $from . "\n";
					    $headers .= 'Reply-To: '.$to.'' . "\n";
					    $headers .= "CC:" . $cc . "\r\n";
						$subject = "Revision Job File :: ".$recordModel_old->get('cf_1198')."";
						//mail($to,$subject,$body,$headers);

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
						if(!empty($branch_bfm_emails))
						{
							foreach($branch_bfm_emails as $bfm_emails){
								$mailer->AddCC($bfm_emails);
							}
						}

						$mailer->AddCC($current_user->get('email1'));
						//$mailer->AddCC('s.mehtab@globalinklogistics.com');
						$status = $mailer->Send(true);
						
				 }				
			}


			
		}


		return $recordModel;
	}

	public function job_assignment($job_id, $job_owner, $assigned_to_id)
	{
		$adb = PearDatabase::getInstance();
		$new_id = $adb->getUniqueId('vtiger_crmentity');
		
		$current_user = Users_Record_Model::getCurrentUserModel();
		//$assigned_to_id = 458;
		$smownerid = ($job_owner==1 ? $current_user->getId() : $assigned_to_id);
		$name = ($job_owner==1 ? 'owner' : 'Add Expense');
		
		$db = PearDatabase::getInstance();
		$db->pquery("INSERT INTO vtiger_crmentity SET crmid = '".$new_id."', smcreatorid ='".$current_user->getId()."', smownerid ='".$smownerid."', setype = 'JobTask', createdtime='".date('Y-m-d H:i:s')."', modifiedtime='".date('Y-m-d H:i:s')."' ");
		$db->pquery("INSERT INTO vtiger_jobtask SET jobtaskid = '".$new_id."', job_id = '".$job_id."', name = '".$name."', 
					             user_id ='".$smownerid."', job_owner = '".$job_owner."'");		
		$db->pquery("INSERT INTO vtiger_crmentityrel SET crmid = '".$job_id."', module = 'Job', relcrmid = '".$new_id."', relmodule = 'JobTask'");
	}

	
	public function deviation($job_id, $recordModel_old)
	{

		 $entries = array();	
		//“Deviations Cost” and “Deviation Revenue”.
		include("include/Exchangerate/exchange_rate_class.php");
		//For JER
		 $db = PearDatabase::getInstance();
		 $db->setDebug(true);
		 $jer_sum_sql =  $db->pquery("SELECT sum(jercf.cf_1160) as total_cost_local_currency , sum(jercf.cf_1168) as total_revenue_local_currency 
						   FROM `vtiger_jercf` as jercf 
						   INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = jercf.jerid
						   INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid 
						   WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid='".$job_id."' 
						   AND crmentityrel.module='Job' AND crmentityrel.relmodule='JER' ");
		 $row_job_costing = $db->fetch_array($jer_sum_sql);				   
		 $total_cost_local_currency = $row_job_costing['total_cost_local_currency'];
		 $total_revenue_local_currency = $row_job_costing['total_revenue_local_currency'];
		 
		 $file_title = $recordModel_old->get('cf_1186');
		 $file_title_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$file_title);
		 
		 $jer_last_sql =  $db->pquery("SELECT vtiger_crmentity.modifiedtime, vtiger_crmentity.createdtime FROM `vtiger_jercf` as jercf 
									   INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = jercf.jerid
									   INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid 
									   WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid='".$job_id."'  AND crmentityrel.module='Job' 
									   AND crmentityrel.relmodule='JER' order by vtiger_crmentity.modifiedtime DESC limit 1 ");
			
		 $count_last_modified = $db->num_rows($jer_last_sql);	
		 $exchange_rate_date  = date('Y-m-d');
		 if($count_last_modified>0)
		 {
			 $row_costing_last = $db->fetch_array($jer_last_sql);
			 $modifiedtime = $row_costing_last['modifiedtime'];
			 if($modifiedtime=='0000-00-00 00:00:00')
			 {
				 $createdtime = strtotime($row_costing_last['createdtime']);
				 $exchange_rate_date = date('Y-m-d', $createdtime);
			 }
			 else{
				$modifiedtime = strtotime($row_costing_last['modifiedtime']);
				$exchange_rate_date = date('Y-m-d', $modifiedtime);
			 }
		 }
		 if($file_title_currency!='USD')
		{			
			$final_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $exchange_rate_date);			
		}else{
			$final_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $exchange_rate_date);
		}					   
		
		$TOTAL_COST_USD = 0;
		$TOTAL_REVENUE_USD = 0;
				
		if($final_exchange_rate>0)
		{
			$total_cost_usd = $total_cost_local_currency/$final_exchange_rate;
			$total_revenue_usd = $total_revenue_local_currency/$final_exchange_rate;
			
			$TOTAL_COST_USD = number_format ( (empty($total_cost_usd) ? 0 : $total_cost_usd) , 2 ,  "." , "" );
			$TOTAL_REVENUE_USD = number_format ( (empty($total_revenue_usd) ? 0 : $total_revenue_usd) , 2 ,  "." , "" );
		}
		
		$entries['expected_cost_usd'] = $TOTAL_COST_USD;
		$entries['expected_revenue_usd'] = $TOTAL_REVENUE_USD;
		
		//For Actual Cost
		//For JRER Expense
		//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
		$jrer_sql_expense =  "SELECT vtiger_jobexpencereportcf.cf_1347 as buy_local_currency_gross, 
								     vtiger_jobexpencereportcf.cf_1349 as buy_local_currency_net,
									 vtiger_jobexpencereportcf.cf_1351 as expected_buy_local_currency_net, 
									 vtiger_jobexpencereportcf.cf_1353 as variation_expected_and_actual_buying,
									 vtiger_jobexpencereportcf.cf_1216 as expense_invoice_date,
									 vtiger_jobexpencereportcf.cf_1345 as buy_currency_id
							  FROM `vtiger_jobexpencereport` 
							  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
 							  INNER JOIN vtiger_crmentityrel ON 
							  (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid ) 
 							  Left join vtiger_jobexpencereportcf as vtiger_jobexpencereportcf ON
							  vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid 
		 					  WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid='".$job_id."' AND vtiger_crmentityrel.module='Job' 
							  AND vtiger_crmentityrel.relmodule='Jobexpencereport' AND vtiger_jobexpencereportcf.cf_1457='Expence'
							  AND vtiger_jobexpencereport.owner_id = '".$recordModel_old->get('assigned_user_id')."' 
							  ORDER BY vtiger_jobexpencereportcf.cf_1345 DESC";
		$result_expense = $db->pquery($jrer_sql_expense);
		$numRows_expnese = $db->num_rows($result_expense);		
		
		$total_cost_in_usd_net = 0;
				
		if($numRows_expnese>0)
		{	
			while($row_job_jrer_expense = $db->fetch_array($result_expense))
			{
				$expense_invoice_date = $row_job_jrer_expense['expense_invoice_date'];
						
				$CurId = $row_job_jrer_expense['buy_currency_id'];
				if ($CurId) {
				  $q_cur = $db->pquery('select * from vtiger_currency_info where id = "'.$CurId.'"');
				  $row_cur = $db->fetch_array($q_cur);
				  $Cur = $row_cur['currency_code'];
				}
				
				$b_exchange_rate = $final_exchange_rate;					
				if(!empty($expense_invoice_date))
				{
					if($file_title_currency!='USD')
					{
						$b_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $expense_invoice_date);
					}else{
						$b_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $expense_invoice_date);
					}
				}
				
				if($file_title_currency!='USD')
				{					
					$total_cost_in_usd_net += $row_job_jrer_expense['buy_local_currency_net']/$b_exchange_rate;					
				}
				else{					
					$total_cost_in_usd_net += $row_job_jrer_expense['buy_local_currency_net'];
				}
			}
			
		}
		$entries['actual_expense_cost_usd'] = number_format ( $total_cost_in_usd_net , 2 ,  "." , "" );
		
		
		//For JRER Selling
		//OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid
		$jrer_selling_sql_selling =  "SELECT vtiger_jobexpencereportcf.cf_1232 as sell_customer_currency_gross, 
								      vtiger_jobexpencereportcf.cf_1238 as sell_local_currency_gross,
									  vtiger_jobexpencereportcf.cf_1240 as sell_local_currency_net, 
									  vtiger_jobexpencereportcf.cf_1242 as expected_sell_local_currency_net,
									  vtiger_jobexpencereportcf.cf_1244 as variation_expected_and_actual_selling,
									  vtiger_jobexpencereportcf.cf_1246 as variation_expect_and_actual_profit,
									  vtiger_jobexpencereportcf.cf_1355 as sell_invoice_date,
									  vtiger_jobexpencereportcf.cf_1234 as currency_id
									  FROM `vtiger_jobexpencereport` 
									  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereport.jobexpencereportid 
									  INNER JOIN vtiger_crmentityrel ON 
									  (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid ) 
									  Left JOIN vtiger_jobexpencereportcf as vtiger_jobexpencereportcf ON
									  vtiger_jobexpencereportcf.jobexpencereportid=vtiger_jobexpencereport.jobexpencereportid						  
									  WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentityrel.crmid='".$job_id."' AND vtiger_crmentityrel.module='Job' 
									  AND vtiger_crmentityrel.relmodule='Jobexpencereport' AND vtiger_jobexpencereportcf.cf_1457='Selling'
									  AND vtiger_jobexpencereport.owner_id = '".$recordModel_old->get('assigned_user_id')."' 
									  ORDER BY vtiger_jobexpencereportcf.cf_1234 DESC";
									  
		$result_invoice = $db->pquery($jrer_selling_sql_selling);
		$numRows_invoice = $db->num_rows($result_invoice);	
		$total_cost_in_usd_sell_net = 0;		
		if($numRows_invoice>0)
			{	
				
				while($row_job_jrer_invoice = $db->fetch_array($result_invoice))
				{
					$sell_invoice_date = $row_job_jrer_invoice['sell_invoice_date'];
					$exchange_rate_date_invoice =$sell_invoice_date;
					
					$CurId = $row_job_jrer_invoice['currency_id'];
					if ($CurId) {
					  $q_cur = $db->pquery('select * from vtiger_currency_info where id = "'.$CurId.'"');
					  $row_cur = $db->fetch_array($q_cur);
					  $Cur = $row_cur['currency_code'];
					}						
					
					$s_exchange_rate = $final_exchange_rate;
					if(!empty($exchange_rate_date_invoice))
					{
						if($file_title_currency!='USD')
						{
							$s_exchange_rate = currency_rate_convert_kz($file_title_currency, 'USD',  1, $exchange_rate_date_invoice);
						}else{
							$s_exchange_rate = currency_rate_convert($file_title_currency, 'USD',  1, $exchange_rate_date_invoice);
						}
					}					
									
					$new_rate = $s_exchange_rate;								
					if($file_title_currency!='USD')
					{											
						$total_cost_in_usd_sell_net += $row_job_jrer_invoice['sell_local_currency_net']/$s_exchange_rate;
					}
					else{
						$total_cost_in_usd_sell_net += $row_job_jrer_invoice['sell_local_currency_net'];
					}						
				}
			}
			$entries['actual_selling_cost_usd'] = number_format ( $total_cost_in_usd_sell_net , 2 ,  "." , "" );
			
			return $entries;
	}
	

	/**
	 * Function to get the record model based on the request parameters
	 * @param Vtiger_Request $request
	 * @return Vtiger_Record_Model or Module specific Record Model instance
	 */
	protected function getRecordModelFromRequest(Vtiger_Request $request) {

		$moduleName = $request->getModule();
		$recordId = $request->get('record');
       
       
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

		if(!empty($recordId)) {
			$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
			$recordModel->set('id', $recordId);
			$recordModel->set('mode', 'edit');
		} else {
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$recordModel->set('mode', '');
		}

		$fieldModelList = $moduleModel->getFields();
		foreach ($fieldModelList as $fieldName => $fieldModel) {
			$fieldValue = $request->get($fieldName, null);
			$fieldDataType = $fieldModel->getFieldDataType();
			if($fieldDataType == 'time' && $fieldValue !== null){
				$fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
			}
			if($fieldValue !== null) {
				if(!is_array($fieldValue) && $fieldDataType != 'currency') {
					$fieldValue = trim($fieldValue);
				}
				$recordModel->set($fieldName, $fieldValue);
			}
		}
  

		if($request->get('cf_4945')=='')
		{
		    $recordModel->set('cf_4945',0.0);
		}
		 $request->get('cf_1711');
		 if($request->get('job_origin_states')=='')
		 {
		 	$recordModel->set('job_origin_states','');
		 }
		 if($request->get('job_destination_states')=='')
		 {  
		      $recordModel->set('job_destination_states','');
		 }

		  //AirblockState
		  if($request->get('job_air_origin_state')=='')
		 {
		      $recordModel->set('job_air_origin_state','');
		 }
		 if($request->get('job_air_destination_state')=='')
		 {
		     $recordModel->set('job_air_destination_state','');
		 }
		 //seablockState
		  if($request->get('job_sea_loading_state')=='')
		 {
		      $recordModel->set('job_sea_loading_state','');
		 }
		 if($request->get('job_sea_discharge_state')=='')
		 {
		      $recordModel->set('job_sea_discharge_state','');
		 }
		
		if(in_array("Air", $request->get('cf_1711')) && in_array("Ocean", $request->get('cf_1711')))
		{
			$recordModel->set('job_sea_loading_country',$request->get('job_sea_loading_country')); 
			$recordModel->set('job_sea_loading_state',$request->get('job_sea_loading_state')); 
			$recordModel->set('job_sea_loading_city',$request->get('job_sea_loading_city')); 
			$recordModel->set('job_sea_loading_port_code',$request->get('job_sea_loading_port_code')); 
			$recordModel->set('job_sea_loading_country_id',$request->get('job_sea_loading_country_id')); 
			$recordModel->set('job_sea_loading_city_id',$request->get('job_sea_loading_city_id')); 
			$recordModel->set('job_sea_loading_unlocode',$request->get('job_sea_loading_unlocode')); 

			$recordModel->set('job_sea_discharge_country',$request->get('job_sea_discharge_country')); 
			$recordModel->set('job_sea_discharge_state',$request->get('job_sea_discharge_state')); 
			$recordModel->set('job_sea_discharge_city',$request->get('job_sea_discharge_city')); 
			$recordModel->set('job_sea_discharge_port_code',$request->get('job_sea_discharge_port_code')); 
			$recordModel->set('job_sea_discharge_country_id',$request->get('job_sea_discharge_country_id')); 
			$recordModel->set('job_sea_discharge_city_id',$request->get('job_sea_discharge_city_id')); 
			$recordModel->set('job_sea_discharge_unlocode',$request->get('job_sea_discharge_unlocode'));
			$recordModel->set('job_air_origin_country',$request->get('job_air_origin_country')); 
			$recordModel->set('job_air_origin_state',$request->get('job_air_origin_state')); 
			$recordModel->set('job_air_origin_city',$request->get('job_air_origin_city')); 
			$recordModel->set('job_air_origin_port_code',$request->get('job_air_origin_port_code')); 
			$recordModel->set('job_air_origin_airport_name',$request->get('job_air_origin_airport_name')); 
			$recordModel->set('job_air_origin_country_id',$request->get('job_air_origin_country_id')); 
			$recordModel->set('job_air_origin_city_code',$request->get('job_air_origin_city_code')); 
			$recordModel->set('job_air_origin_unlocode',$request->get('job_air_origin_unlocode')); 

			$recordModel->set('job_air_destination_country',$request->get('job_air_destination_country')); 
			$recordModel->set('job_air_destination_state',$request->get('job_air_destination_state')); 
			$recordModel->set('job_air_destination_city',$request->get('job_air_destination_city')); 
			$recordModel->set('job_air_destination_port_code',$request->get('job_air_destination_port_code'));
			$recordModel->set('job_air_destination_airport_name',$request->get('job_air_destination_airport_name'));  
			$recordModel->set('job_air_destination_country_id',$request->get('job_air_destination_country_id')); 
			$recordModel->set('job_air_destination_city_code',$request->get('job_air_destination_city_code')); 
			$recordModel->set('job_air_destination_unlocode',$request->get('job_air_destination_unlocode')); 	

		}
		else if(in_array("Air", $request->get('cf_1711')))
		{
		   
			$recordModel->set('job_sea_loading_country',''); 
			$recordModel->set('job_sea_loading_state',''); 
			$recordModel->set('job_sea_loading_city',''); 
			$recordModel->set('job_sea_loading_port_code',''); 
			$recordModel->set('job_sea_loading_country_id',''); 
			$recordModel->set('job_sea_loading_city_id',''); 
			$recordModel->set('job_sea_loading_unlocode',''); 

			$recordModel->set('job_sea_discharge_country',''); 
			$recordModel->set('job_sea_discharge_state',''); 
			$recordModel->set('job_sea_discharge_city',''); 
			$recordModel->set('job_sea_discharge_port_code',''); 
			$recordModel->set('job_sea_discharge_country_id',''); 
			$recordModel->set('job_sea_discharge_city_id',''); 
			$recordModel->set('job_sea_discharge_unlocode','');
		}
		else if(in_array("Ocean", $request->get('cf_1711')))
		{
		   
			$recordModel->set('job_air_origin_country',''); 
			$recordModel->set('job_air_origin_state',''); 
			$recordModel->set('job_air_origin_city',''); 
			$recordModel->set('job_air_origin_port_code',''); 
			$recordModel->set('job_air_origin_airport_name',''); 
			$recordModel->set('job_air_origin_country_id',''); 
			$recordModel->set('job_air_origin_city_code',''); 
			$recordModel->set('job_air_origin_unlocode',''); 

			$recordModel->set('job_air_destination_country',''); 
			$recordModel->set('job_air_destination_state',''); 
			$recordModel->set('job_air_destination_city',''); 
			$recordModel->set('job_air_destination_port_code','');
			$recordModel->set('job_air_destination_airport_name','');  
			$recordModel->set('job_air_destination_country_id',''); 
			$recordModel->set('job_air_destination_city_code',''); 
			$recordModel->set('job_air_destination_unlocode','');

		}else if(in_array("Road", $request->get('cf_1711')) OR in_array("Rail", $request->get('cf_1711')) OR  empty($request->get('cf_1711')))
		{

			$recordModel->set('job_sea_loading_country',''); 
			$recordModel->set('job_sea_loading_state',''); 
			$recordModel->set('job_sea_loading_city',''); 
			$recordModel->set('job_sea_loading_port_code',''); 
			$recordModel->set('job_sea_loading_country_id',''); 
			$recordModel->set('job_sea_loading_city_id',''); 
			$recordModel->set('job_sea_loading_unlocode',''); 

			$recordModel->set('job_sea_discharge_country',''); 
			$recordModel->set('job_sea_discharge_state',''); 
			$recordModel->set('job_sea_discharge_city',''); 
			$recordModel->set('job_sea_discharge_port_code',''); 
			$recordModel->set('job_sea_discharge_country_id',''); 
			$recordModel->set('job_sea_discharge_city_id',''); 
			$recordModel->set('job_sea_discharge_unlocode','');
			$recordModel->set('job_air_origin_country',''); 
			$recordModel->set('job_air_origin_state',''); 
			$recordModel->set('job_air_origin_city',''); 
			$recordModel->set('job_air_origin_port_code',''); 
			$recordModel->set('job_air_origin_airport_name',''); 
			$recordModel->set('job_air_origin_country_id',''); 
			$recordModel->set('job_air_origin_city_code',''); 
			$recordModel->set('job_air_origin_unlocode',''); 

			$recordModel->set('job_air_destination_country',''); 
			$recordModel->set('job_air_destination_state',''); 
			$recordModel->set('job_air_destination_city',''); 
			$recordModel->set('job_air_destination_port_code','');
			$recordModel->set('job_air_destination_airport_name','');  
			$recordModel->set('job_air_destination_country_id',''); 
			$recordModel->set('job_air_destination_city_code',''); 
			$recordModel->set('job_air_destination_unlocode','');

		}
		
		return $recordModel;
	}

	/*
		Adding job file in scheduler queue for further sending to KIP3 (kerry system)
	*/
	function scheduleJobFileForInteration($recordId, $jobFileStatus){
 
		if (in_array($jobFileStatus, array('No Costing', 'Completed', 'Cancelled', 'Revision'))){
			$db = PearDatabase::getInstance();
			$queryQueue = "SELECT id
											FROM `vtiger_jobfile_cron`
											WHERE `jobfileid` = ? AND `status` = ?";			
			$resultQueue = $db->pquery($queryQueue, array($recordId, 'Initiated'));
			if ($db->num_rows($resultQueue) == 0){
				$db->pquery("INSERT INTO `vtiger_jobfile_cron` (`jobfileid`, `status`) VALUES ($recordId, 'Initiated')");
			}
		}
	}




	/*
	public function send_email($rid){

		global $adb;
		$adb = PearDatabase::getInstance();
		//get creator user info
		$current_user = Users_Record_Model::getCurrentUserModel();
		$creator_user_id = $current_user->getId();//$this->getRecCreatorId($rid);
		
		//get creator gm
		$sql_creator_gm = "SELECT * FROM vtiger_userlistcf WHERE cf_3355='".$creator_user_email."'";
		$rsgm = $adb->pquery($sql_creator_gm);
		$rsgm = $adb->fetch_array($rsgm);
		$creator_gm_id = $rgm['cf_3385'];
		
		//get creator gm email
		$sql_creator_gm_email = "SELECT * FROM vtiger_userlistcf WHERE userlistid='".$creator_gm_id."'";
		$rsgmemail = $adb->pquery($sql_creator_gm_email);
		$rgmemail = $adb->fetch_array($rsgmemail);
		$creator_user_gm_email = $rgmemail['cf_3355'];
		
		//get creator gm name
		$sql_creator_gm_name = "SELECT * FROM vtiger_users WHERE email1='$creator_user_gm_email'";
		$rsgmname = $adb->pquery($sql_creator_gm_name);
		$rgmname = $adb->fetch_array($rsgmname);
		$creator_user_gm_name = $rgmname['first_name']." ".$rgmname['last_name'];

		$body = '';
		global $site_URL;
		$body .="<p>Dear&nbspColleagues</p>";
		$body .="<p>Please Note that new Job File Created against OOG Cargo/Project";
		//$from = "From: ".$current_user->get('email1')." <".$current_user->get('email1').">";
		$from = 'm.ali@globalinklogistics.com';//$current_user->get('email1');
		//$to = $job_user_info->get('email1');
		$to = 'm.ali@globalinklogistics.com';
		//$cc  = 'm.ali@globalinklogistics.com';
		$cc= '';
	
	    $headers = "MIME-Version: 1.0" . "\n";
	    $headers .= "Content-type:text/html;charset=UTF-8" . "\n";
	    $headers .= $from . "\n";
	    $headers .= 'Reply-To: '.$to.'' . "\n";
	    $headers .= "CC:" . $cc . "\r\n";
		$subject = "Job file (OOG Cargo)";
		//mail($to,$subject,$body,$headers);

		require_once 'vtlib/Vtiger/Mailer.php';
		global $HELPDESK_SUPPORT_EMAIL_ID;
		$mailer = new Vtiger_Mailer();
		$mailer->IsHTML(true);
		$mailer->ConfigSenderInfo($from);
		$mailer->Subject =$subject;
		$mailer->Body = $body;
		$mailer->AddAddress($to);
		//$mail->AddCC('b.rustam@globalinklogistics.com');
		//$mail->AddCC('o.stefanidi@globalinklogistics.com');
		
		//sending email to GM
		if(!empty($creator_gm_id))
		{
			$mailer->AddCC($creator_user_gm_email);
		}
		//$mailer->AddCC($current_user->get('email1'));
		$mailer->AddCC('m.ali@globalinklogistics.com');
		$status = $mailer->Send(true);
	}*/



	public function send_email_oog($rid){
		global $adb;
		$adb = PearDatabase::getInstance();
		//get creator user info
		$current_user = Users_Record_Model::getCurrentUserModel();
		$creator_user_id = $current_user->getId();//$this->getRecCreatorId($rid);
		$creator_user_email	=$current_user->get('email1');// for email
		
		//get creator gm
		$sql_creator_gm = "SELECT * FROM vtiger_userlistcf WHERE cf_3355='".$creator_user_email."'";
		$rsgm = $adb->pquery($sql_creator_gm);
		$rsgm = $adb->fetch_array($rsgm);
		$creator_gm_id = $rgm['cf_3385'];
		
		//get creator gm email
		$sql_creator_gm_email = "SELECT * FROM vtiger_userlistcf WHERE userlistid='".$creator_gm_id."'";
		$rsgmemail = $adb->pquery($sql_creator_gm_email);
		$rgmemail = $adb->fetch_array($rsgmemail);
		$creator_user_gm_email = $rgmemail['cf_3355'];
		
		//get creator gm name
		$sql_creator_gm_name = "SELECT * FROM vtiger_users WHERE email1='$creator_user_gm_email'";
		$rsgmname = $adb->pquery($sql_creator_gm_name);
		$rgmname = $adb->fetch_array($rsgmname);
		$creator_user_gm_name = $rgmname['first_name']." ".$rgmname['last_name'];

		$body = '';
		global $site_URL;
		$body .="<p>Dear&nbsp;"."Colleagues".",</p>";
		$body .="<p>Please Note that new Job File Created against OOG Cargo/Project";
		$body .="<p>The Link of Job File is : <a href='".$site_URL."index.php?module=Job&view=Detail&record=".$rid."&app=JOBS' target='_blank'>".'Job File'."</a></p>";

		//$from = "From: ".$current_user->get('email1')." <".$current_user->get('email1').">";
		$from = 'm.ali@globalinklogistics.com';//$current_user->get('email1');
		//$to = $job_user_info->get('email1');
		$to = 'm.ali@globalinklogistics.com';
		//$cc  = 'm.ali@globalinklogistics.com';
		$cc= '';
	
	    $headers = "MIME-Version: 1.0" . "\n";
	    $headers .= "Content-type:text/html;charset=UTF-8" . "\n";
	    $headers .= $from . "\n";
	    $headers .= 'Reply-To: '.$to.'' . "\n";
	    $headers .= "CC:" . $cc . "\r\n";
		$subject = "Job file (OOG Cargo)";
		//mail($to,$subject,$body,$headers);

		require_once 'vtlib/Vtiger/Mailer.php';
		global $HELPDESK_SUPPORT_EMAIL_ID;
		$mailer = new Vtiger_Mailer();
		$mailer->IsHTML(true);
		$mailer->ConfigSenderInfo($from);
		$mailer->Subject =$subject;
		$mailer->Body = $body;
		$mailer->AddAddress($to);
		//$mail->AddCC('b.rustam@globalinklogistics.com');
		//$mail->AddCC('o.stefanidi@globalinklogistics.com');
		
		//sending email to GM
		if(!empty($creator_gm_id))
		{
			$mailer->AddCC($creator_user_gm_email);
		}
		//$mailer->AddCC($current_user->get('email1'));
		$mailer->AddCC('m.ali@globalinklogistics.com');
		$status = $mailer->Send(true);
	}


  

    

}

