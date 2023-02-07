<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class CargoInsurance_Save_Action extends Vtiger_Save_Action {

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
		try {
			global $adb;
			global $current_user;
			//$adb->setDebug(true);
			include("include/Exchangerate/exchange_rate_class.php");
			$invoice_sum = $request->get('cf_3629');
			$transportat_cost_for_inv = $request->get('cf_3631');
			$other_charges = $request->get('cf_3633');

			$rate_id = $request->get('cf_3625');
			$special_range_id = $request->get('cf_3627');		
			$selling_insurance_flag = $request->get('cf_4559');	
			$selling_insurance_flag_fsl = $request->get('cf_4559');	//From "No" to "Yes" from insurance team
			$assured_company = $request->get('cf_3599');
			$cargo_insurance_currency_id = $request->get('cf_3663');
			$temperature_control = $request->get('cf_3647');
			$insurance_type = $request->get('cf_6298');

			$departure_date = $request->get('cf_3613'); //From Date
			$departure_date = date('Y-m-d', strtotime($departure_date));

			//16.10.2020:Mehtab: For Halyk Insurance
			$request->set('cf_6298', $insurance_type);//For live

			//For Jysan Garant
			$origin_from = $request->get('cf_3605');
			$destination_to = $request->get('cf_3609');
			$jysan_garant_country = array('RU', 'BY');


			$FSL_BLACK_INSURANCE = FALSE;
			$NEW_FSL_BLACK_INSURANCE_FLAG = TRUE;

			$JYSAN_GARANT_INSURANCE = FALSE;

			//if($insurance_type!='Halyk Insurance') {
			//	$request->set('cf_6298', 'WIS Insurance');//For live
			//}

			//To check FSL black Insurance
			$cargo_beneficiary = $request->get('cf_3601');
			//19.10.2020:Mehtab: New condition for in case of Halyk insurance beneficiary
			$halyk_insurance_account = array('2995054', '4943', '2994752', '2994938', '2994957', '2994979');
			
			if($insurance_type =='Jysan Garant' && $temperature_control=='No' && (in_array($origin_from, $jysan_garant_country) || in_array($destination_to, $jysan_garant_country)))	
			{
				$insurance_type = 'Jysan Garant';
				$request->set('cf_6298', $insurance_type);//For live
				$request->set('cf_4559', 'Yes');
				$FSL_BLACK_INSURANCE = FALSE;
				$NEW_FSL_BLACK_INSURANCE_FLAG = FALSE;
				$JYSAN_GARANT_INSURANCE = TRUE;
				$halyk_insurance_flag = false;
			}
			else if(in_array($cargo_beneficiary, $halyk_insurance_account) && $assured_company=='85757' )
			{
				$insurance_type = 'Halyk Insurance';
				$selling_insurance_flag = 'Yes';
				$request->set('cf_6298', 'Halyk Insurance');//live
				$request->set('cf_4559', 'Yes');
				$FSL_BLACK_INSURANCE = FALSE;
				$NEW_FSL_BLACK_INSURANCE_FLAG = FALSE;
				$halyk_insurance_flag = true;
				$request->set('cf_3621','');//WIS REF
			}			
			else{
				//In case if user change beneficiary from Halyk insuarnce client to normal client				
				$insurance_type = 'WIS Insurance';
				$request->set('cf_6298', $insurance_type);//For live
				$halyk_insurance_flag = false;
			}
			
			$name_for_look_up = '';
			$legal_name = '';
			$customer_status='';
			if(!empty($cargo_beneficiary))
			{
				$account_info_detail = Vtiger_Record_Model::getInstanceById($cargo_beneficiary, 'Accounts');
				$name_for_look_up = $account_info_detail->get('accountname');
				$legal_name = $account_info_detail->get('cf_2395');
				$customer_status = $account_info_detail->get('cf_2403');

			}
			//$FSL_BLACK_INSURANCE = FALSE;
			//$NEW_FSL_BLACK_INSURANCE_FLAG = TRUE;

			$record= $request->get('record');
			$insurance_type_old='';
			if(!empty($record))
			{
				$cargoinsurance_info_detail = Vtiger_Record_Model::getInstanceById($record, 'CargoInsurance');
				$insurance_type_old = $cargoinsurance_info_detail->get('cf_6298'); //Insurance Type
			}
			
			if(!empty($rate_id))
			{
				//CommodityType
				//Get CommodityType FSL black
				$commoditytype_info_detail = Vtiger_Record_Model::getInstanceById($rate_id, 'CommodityType');
				$commoditytype_FSL_Black = $commoditytype_info_detail->get('cf_7036');
				if($commoditytype_FSL_Black=='No')
				{
					if($insurance_type!='Halyk Insurance' && $insurance_type!='Jysan Garant') {
						$request->set('cf_6298', 'WIS Insurance');//For Live
					}	
					//$selling_insurance_flag = 'Yes';
					$selling_insurance_flag = 'No';
					$FSL_BLACK_INSURANCE = FALSE;
					$NEW_FSL_BLACK_INSURANCE_FLAG = FALSE;
					if($insurance_type_old=='FSL Black Insurance')
					{
						$request->set('cf_3621','');//WIS REF
					}
				}
			}
			if($temperature_control=='Yes' && $selling_insurance_flag=='Yes')
			{
				//temperature control should be available for WIS
				if($insurance_type!='Halyk Insurance') {
					$request->set('cf_6298', 'WIS Insurance');//For Live
				}
				$FSL_BLACK_INSURANCE = FALSE;
				$NEW_FSL_BLACK_INSURANCE_FLAG = FALSE;
				$JYSAN_GARANT_INSURANCE = FALSE;
				if($insurance_type_old=='FSL Black Insurance' || $insurance_type_old=='Jysan Garant')
				{
					$request->set('cf_3621','');//WIS REF
				}
			}
			//Mode priority
			//1::Sea, 2::Air, 3::Road, 4::Rail
			$mode_arr = $request->get('cf_3619');
			$specialrange_info_detail = Vtiger_Record_Model::getInstanceById($special_range_id , 'SpecialRange');
			$specialrange_FSL_Black = $specialrange_info_detail->get('cf_7038');
			if(in_array('Ocean', $mode_arr) && $specialrange_FSL_Black=='No')
			{
				if($insurance_type!='Halyk Insurance' && $insurance_type!='Jysan Garant') {
					$request->set('cf_6298', 'WIS Insurance');//For Live
				}

				$FSL_BLACK_INSURANCE = FALSE;
				$NEW_FSL_BLACK_INSURANCE_FLAG = FALSE;
				if($insurance_type_old=='FSL Black Insurance')
				{
					$request->set('cf_3621','');//WIS REF
				}
			}

			//For Scenaior::
			/*1.In case any of below points is chosen in ‘Cargo Insurance’ with FSL black Client  
			-   FSL Insurance turns to WIS Insurance , with Selling: “NO” 
			-	Frozen Food
			-	Frozen meat			
			*/  
			//$GLK_DISCOUNT_FLAG = FALSE;
			//we will check later for jysan garant
			if(!$NEW_FSL_BLACK_INSURANCE_FLAG && !$halyk_insurance_flag)
			{
				if(!empty($cargo_beneficiary) && $assured_company=='85757' && $cargo_beneficiary!='1667')
				{
					$job_id = $request->get('returnrecord');
					if(empty($job_id))
					{
						$insurance_id = $record = $request->get('record');
						$adb_r = PearDatabase::getInstance();
						$query_rel = "select crmid from vtiger_crmentityrel where relcrmid =? AND relmodule='CargoInsurance' AND module='Job'";
						$params_rel = array($insurance_id);	
						$result_rel = $adb_r->pquery($query_rel, $params_rel);
						$row_rel = $adb_r->fetch_array($result_rel);
						$job_id = $row_rel['crmid'];				
					}

					$sourceModule_job 	= 'Job';	
					$job_info_detail = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule_job);
					$job_department_id = $job_info_detail->get('cf_1190');

					$adb_fsl = PearDatabase::getInstance();
					$query_fsl = "SELECT * FROM vtiger_fslblackcf 
								INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_fslblackcf.fslblackid
								WHERE vtiger_crmentity.deleted=0 AND vtiger_fslblackcf.cf_6244=? AND FIND_IN_SET($job_department_id,cf_6448)";								 
					$params_fsl = array($cargo_beneficiary);
					$result_fsl = $adb_fsl->pquery($query_fsl, $params_fsl);
					$count_beneficiary_fsl = $adb_fsl->num_rows($result_fsl);
					
					if($count_beneficiary_fsl!=0)
					{	
						$request->set('cf_4559', 'No');
						//$GLK_DISCOUNT_FLAG = TRUE;
						/*
						2.Before saving Cargo Insurance with above scenario (WIS Insurance , with Selling: “NO”), 
						Coordinator has to insert specific discount in the field “Discounted GLK Selling rate”. 
						Therefore there has to be below message, and saving of the same page has to be restricted.
						Message: “Please insert “Discounted GLK Selling rate”
						This scenario should not work in case if Insurance Team manually changes selling from “No” to “Yes”.
						In other words, if Selling is “Yes”, adding discount is not obligatory. 
						*/						
						$discounted_glk_rate = $request->get('cf_3643');
						// 
						if(($discounted_glk_rate==0 || empty($discounted_glk_rate)) && $selling_insurance_flag_fsl!='Yes'  )
						{
							throw new DiscountException('-	Please insert Discounted GLK Selling rate.');
						}
					}
				}
			}




			if(!empty($cargo_beneficiary) && $assured_company=='85757' && $cargo_beneficiary!='1667' && $NEW_FSL_BLACK_INSURANCE_FLAG) //Only For KZ and Except Air Astana Client
			{
				$job_id = $request->get('returnrecord');
				if(empty($job_id))
				{
					$insurance_id = $record = $request->get('record');
					$adb_r = PearDatabase::getInstance();
					$query_rel = "select crmid from vtiger_crmentityrel where relcrmid =? AND relmodule='CargoInsurance' AND module='Job'";
					$params_rel = array($insurance_id);	
					$result_rel = $adb_r->pquery($query_rel, $params_rel);
					$row_rel = $adb_r->fetch_array($result_rel);
					$job_id = $row_rel['crmid'];				
				}
				$sourceModule_job 	= 'Job';	
				$job_info_detail = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule_job);
				$job_department_id = $job_info_detail->get('cf_1190');

				$adb_fsl = PearDatabase::getInstance();
				$query_fsl = "SELECT * FROM vtiger_fslblackcf 
							INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_fslblackcf.fslblackid
							WHERE vtiger_crmentity.deleted=0 AND vtiger_fslblackcf.cf_6244=? AND FIND_IN_SET($job_department_id,cf_6448)";								 
				$params_fsl = array($cargo_beneficiary);
				$result_fsl = $adb_fsl->pquery($query_fsl, $params_fsl);
				$count_beneficiary_fsl = $adb_fsl->num_rows($result_fsl);

				if($count_beneficiary_fsl!=0)
				{							
					$request->set('cf_4559', 'No');
					$request->set('cf_6298', 'FSL Black Insurance');//For live
					
					$selling_insurance_flag = 'No';
					$FSL_BLACK_INSURANCE = TRUE;				
				}
				else{
					//Email Notification to register selected client in FSL Black Insurance
						$insurance_group = array('1217', '767', '290','1807');
						if($selling_insurance_flag=='No' && !in_array($current_user->id,$insurance_group))
						{
							$eol = PHP_EOL;
							$current_user = Users_Record_Model::getCurrentUserModel();
							$to = $current_user->get('email1');
							$cc  ='b.rustam@globalinklogistics.com;s.mehtab@globalinklogistics.com;a.serikbekkyzy@globalinklogistics.com;a.nursultan@globalinklogistics.com';
							//$cc  ='s.mehtab@globalinklogistics.com;';
							//$to = 's.mehtab@globalinklogistics.com';
							$from     = "From: Rustam Balayev <b.rustam@globalinklogistics.com>";
							$headers  = "MIME-Version: 1.0" . "\n";
							$headers .= "Content-type:text/html;charset=UTF-8" . "\n";
							$headers .= $from . "\n";
							$headers .= "CC:" . $cc . $eol;
							$headers .= 'Reply-To: '.$to.'' . "\n";	
							
							
							//$cc = '';
							$subject = "FSL Black Insurance Scheme Notification";
							
							$body = "";
							$body .="<p>Dear&nbsp; ".$current_user->get('first_name').",</p>".$eol;
							$body .="<p>Please notify the customer".$name_for_look_up." to insurance team to register customer in 
									 ERP system under FSL Black</p>".$eol;
							$body .="<p>Name for Look Up: ".$name_for_look_up."</p>".$eol;
							$body .="<p>Legal Name: ".$legal_name."</p>".$eol;
							$body .="<p>Customer Status: ".$customer_status."</p>".$eol;
							$body .="<p>Job Ref #: ".$request->get('name')."</p>".$eol;		
							$body .="<p>Regards,</p>".$eol;
							$body .="<p><strong>Rustam Balayev</strong></p>".$eol;
							$body .="<p><strong>Globalink Transportation and Logistics Worldwide LLC </strong><br />".$eol;
							//mail($to,$subject,$body,$headers);
							
							$record = $request->get('record');
							//$loadUrl = "index.php?module=CargoInsurance&view=Edit&record=".$record."";					
							$_SESSION['FSL_BLACK'] = '1';
							throw new FSLBlackException('-	Please notify the name of customer to insurance team for confirmation<br>
							-	Then you can start registering insurance in ERP system under FSL Black');
							/*
							if(empty($record))
							{
								$sourceRecord = $request->get('sourceRecord');
								$loadUrl = "index.php?module=CargoInsurance&view=Edit&sourceModule=Job&sourceRecord=".$sourceRecord."&relationOperation=true";												
							}
							header("Location: $loadUrl");
							exit;
							*/
						}
				}

			}
			
			if(!empty($rate_id)  && !empty($special_range_id))
			{
				$adb_cr = PearDatabase::getInstance();

				if($insurance_type!='Halyk Insurance' && $insurance_type!='Jysan Garant') {
					$insurance_type = 'WIS Insurance';
				}
				
				//New WIS Rates
				$policy_cover_id  = $assured_company;

				if($policy_cover_id!='85757')
				{
					$policy_cover_id = '85756'; //DW 
				}
				//DW rates apply to all files GE, RUK, AM, etc, except for KZ.
				//We have only 2 policies with Insurance KZ and DW.

				//cf_7194:: test Tiger insurance_type
				//cf_7146:: New GEMS insurance_type
				//Commented due to New WIS Rates based on KZ and DW
				//$query_cr = "select * from vtiger_commodityratescf where cf_3573=? AND cf_3575=? AND cf_7146=? ";
				//$params_cr = array($rate_id, $special_range_id, $insurance_type);

				$query_cr = "select * from vtiger_commodityratescf where cf_3573=? AND cf_3575=? AND cf_7146=? AND cf_7848=? ";
				$query_cr .= " AND '".$departure_date."' between cf_7850 AND cf_7852 ";
				$params_cr = array($rate_id, $special_range_id, $insurance_type, $policy_cover_id);
				$result_cr = $adb_cr->pquery($query_cr, $params_cr);

				$count_commodityrates_by_type = $adb_cr->num_rows($result_cr);

				$wis_rate = '0.0';
				$centras_rate = '0.0';
				$agent_rate = '0.0';
				$globalink_rate = '0.0';

				if($count_commodityrates_by_type!=0)
				{
					$row_cr = $adb_cr->fetch_array($result_cr);
				
					$commodity_rates_id = $row_cr['commodityratesid'];
					//Mode priority
					//1::Sea, 2::Air, 3::Road, 4::Rail
					$mode_arr = $request->get('cf_3619');
				
					$commodity_rate_list = Vtiger_Record_Model::getInstanceById($commodity_rates_id, 'CommodityRates');
				
			
					if(in_array('Ocean', $mode_arr))
					{
						$wis_rate = $commodity_rate_list->get('cf_3579'); //sea rate
						$centras_rate =  $commodity_rate_list->get('cf_3585');
						$agent_rate =  $commodity_rate_list->get('cf_3675');
						$globalink_rate =  $commodity_rate_list->get('cf_3587');
					}
					elseif(in_array('Air', $mode_arr))
					{
						$wis_rate = $commodity_rate_list->get('cf_3581'); //air rate
						$centras_rate = $commodity_rate_list->get('cf_3697'); // centras rate(air)
						$agent_rate = $commodity_rate_list->get('cf_3699');
						$globalink_rate = $commodity_rate_list->get('cf_3701');
					}
					elseif(in_array('Road', $mode_arr))
					{
						$wis_rate = $commodity_rate_list->get('cf_3583'); //land rate
						$centras_rate = $commodity_rate_list->get('cf_3703');
						$agent_rate = $commodity_rate_list->get('cf_3705');
						$globalink_rate = $commodity_rate_list->get('cf_3707');
					}
					elseif(in_array('Rail', $mode_arr))
					{
						$wis_rate = $commodity_rate_list->get('cf_3583'); //land rate
						$centras_rate = $commodity_rate_list->get('cf_3703');
						$agent_rate = $commodity_rate_list->get('cf_3705');
						$globalink_rate = $commodity_rate_list->get('cf_3707');
					}

				}
			
				//$centras_rate = $centras_rate;
				//$agent_rate = $agent_rate;			
				//$globalink_rate = $globalink_rate;
				$discounted_glk_rate = $request->get('cf_3643');
				
				$total_insured_sum = $invoice_sum + $transportat_cost_for_inv + $other_charges;
				
				//-	Any shipment who’s value is more than USD 100,000, must be insured under regular cover
				if($FSL_BLACK_INSURANCE)
				{
					$wis_date = date('Y-m-d');
					$fsl_file_title_currency = 'USD'; //DW
					$fsl_currency_id = $request->get('cf_3663');
					$fsl_currency_code = Vtiger_CurrencyList_UIType::getDisplayValue($fsl_currency_id);
					
					$fsl_exchange_rate = currency_rate_convert($fsl_currency_code, $fsl_file_title_currency, 1, $wis_date);
					
					$shipment_cargo_value = exchange_rate_convert($fsl_currency_code, $fsl_file_title_currency, $total_insured_sum, $wis_date);
								
					if($shipment_cargo_value>150000)//USD 150000
					{
						//$selling_insurance_flag = 'Yes';
						//$request->set('cf_4559', 'Yes');
						//$request->set('cf_6260', 'WIS Insurance');//For Test
						if($insurance_type!='Halyk Insurance') {	
							$request->set('cf_6298', 'WIS Insurance');//For Live
						}
						$FSL_BLACK_INSURANCE = FALSE;
						//Any shipment who’s value is more than USD 100,000, must be insured under regular cover
						$eol = PHP_EOL;
						$current_user = Users_Record_Model::getCurrentUserModel();
						$to = $current_user->get('email1');
						$cc  ='b.rustam@globalinklogistics.com;s.mehtab@globalinklogistics.com;a.serikbekkyzy@globalinklogistics.com;a.nursultan@globalinklogistics.com';
						//$cc  ='s.mehtab@globalinklogistics.com;';
						//$to = 's.mehtab@globalinklogistics.com';
						$from     = "From: Rustam Balayev <b.rustam@globalinklogistics.com>";
						$headers  = "MIME-Version: 1.0" . "\n";
						$headers .= "Content-type:text/html;charset=UTF-8" . "\n";
						$headers .= $from . "\n";
						$headers .= "CC:" . $cc . $eol;
						$headers .= 'Reply-To: '.$to.'' . "\n";	
						
						
						//$cc = '';
						$subject = "shipment who’s value is more than USD 150,000";
						
						$body = "";
						$body .="<p>Dear&nbsp; ".$current_user->get('first_name').",</p>".$eol;
						$body .='<p>Please note, any shipment who’s value is more than USD 150,000, must be insured under regular cover</p>'.$eol;
						$body .="<p>Name for Look Up: ".$name_for_look_up."</p>".$eol;
						$body .="<p>Legal Name: ".$legal_name."</p>".$eol;
						$body .="<p>Customer Status: ".$customer_status."</p>".$eol;
						$body .="<p>Job Ref #: ".$request->get('name')."</p>".$eol;	
						$body .="<p>Regards,</p>".$eol;
						$body .="<p><strong>Rustam Balayev</strong></p>".$eol;
						$body .="<p><strong>Globalink Transportation and Logistics Worldwide LLC </strong><br />".$eol;
						mail($to,$subject,$body,$headers);

					}
				}
			
				//End to check cargo value in case of FSL Black Insurance
				//Restrictions:
				//This coverage is only for Russia and Belarus routes (origin / destination). 
				//Limit: 100,000,000 KZT (total sum to be insured)
				if($JYSAN_GARANT_INSURANCE)
				{
					$wis_date = date('Y-m-d');
					$fsl_file_title_currency = 'USD'; //DW
					$fsl_currency_id = $request->get('cf_3663');
					$fsl_currency_code = Vtiger_CurrencyList_UIType::getDisplayValue($fsl_currency_id);
					
					$fsl_exchange_rate = currency_rate_convert($fsl_currency_code, $fsl_file_title_currency, 1, $wis_date);
					
					$shipment_cargo_value = exchange_rate_convert($fsl_currency_code, $fsl_file_title_currency, $total_insured_sum, $wis_date);
								
					if($shipment_cargo_value>150000)//USD 150000
					{
						//$selling_insurance_flag = 'Yes';
						//$request->set('cf_4559', 'Yes');
						//$request->set('cf_6260', 'WIS Insurance');//For Test
						if($insurance_type!='Halyk Insurance') {	
							$request->set('cf_6298', 'WIS Insurance');//For Live
						}
						$JYSAN_GARANT_INSURANCE = FALSE;
						//Any shipment who’s value is more than USD 100,000, must be insured under regular cover
						$eol = PHP_EOL;
						$current_user = Users_Record_Model::getCurrentUserModel();
						$to = $current_user->get('email1');
						$cc  ='b.rustam@globalinklogistics.com;s.mehtab@globalinklogistics.com;a.serikbekkyzy@globalinklogistics.com;a.nursultan@globalinklogistics.com';
						//$cc  ='s.mehtab@globalinklogistics.com;';
						//$to = 's.mehtab@globalinklogistics.com';
						$from     = "From: Rustam Balayev <b.rustam@globalinklogistics.com>";
						$headers  = "MIME-Version: 1.0" . "\n";
						$headers .= "Content-type:text/html;charset=UTF-8" . "\n";
						$headers .= $from . "\n";
						$headers .= "CC:" . $cc . $eol;
						$headers .= 'Reply-To: '.$to.'' . "\n";	
						
						
						//$cc = '';
						$subject = "shipment who’s value is more than USD 150,000";
						
						$body = "";
						$body .="<p>Dear&nbsp; ".$current_user->get('first_name').",</p>".$eol;
						$body .='<p>Please note, any shipment who’s value is more than USD 150,000, must be insured under regular cover</p>'.$eol;
						$body .="<p>Name for Look Up: ".$name_for_look_up."</p>".$eol;
						$body .="<p>Legal Name: ".$legal_name."</p>".$eol;
						$body .="<p>Customer Status: ".$customer_status."</p>".$eol;
						$body .="<p>Job Ref #: ".$request->get('name')."</p>".$eol;	
						$body .="<p>Regards,</p>".$eol;
						$body .="<p><strong>Rustam Balayev</strong></p>".$eol;
						$body .="<p><strong>Globalink Transportation and Logistics Worldwide LLC </strong><br />".$eol;
						mail($to,$subject,$body,$headers);

					}
				}


				
				$wis_premium = ($wis_rate/100*$total_insured_sum);
				$centras_premium = ($centras_rate/100*$total_insured_sum);
				
				if($discounted_glk_rate>0)
				{
					$globalink_premium = ($discounted_glk_rate/100*$total_insured_sum);
					$agent_rate = 0;	
				}
				else{
					$globalink_premium = ($globalink_rate/100*$total_insured_sum);
				}
				
				$wis_date = $request->get('cf_3623');
			
			
				if(!empty($wis_date))
				{
					$wis_date = date('Y-m-d', strtotime($wis_date));
					
					$currency_code = 'USD';	
					if($assured_company!='85757')
					{
						$currency_code = 'KZT';
					}
					
					$exchrates_q = 'SELECT * FROM vtiger_exchangerate rate
								INNER JOIN vtiger_exchangeratecf cf
								ON rate.exchangerateid = cf.exchangerateid
								where cf.cf_1108 = ? and cf.cf_1106=?
								';
					$params_cr = array($wis_date, $currency_code);
					$exchrates = $adb_cr->pquery($exchrates_q, $params_cr);
					$exchrate = $adb_cr->fetch_array($exchrates);
					$_exchrates = $exchrate['name'];
					/*$exchrates = mysql_query('SELECT * FROM vtiger_exchangerate rate
											INNER JOIN vtiger_exchangeratecf cf
											ON rate.exchangerateid = cf.exchangerateid
											where cf.cf_1108 = "'.$wis_date.'" and cf.cf_1106="'.$currency_code.'"
											');
					$exchrate = mysql_fetch_object($exchrates);
					$_exchrates = $exchrate->name;*/
					
					$check_premium = 11 * $_exchrates;
					if($assured_company!='85757')
					{
						//$check_premium = 10 * $_exchrates;
						if($cargo_insurance_currency_id==2){
							$check_premium = 10 * $_exchrates;
						}
						elseif($cargo_insurance_currency_id==13)
						{
							$check_premium = 9 * $_exchrates;
						}
					}
					if($globalink_premium < $check_premium && $insurance_type!='Halyk Insurance')
					{
						$globalink_premium = $check_premium;
						//$agent_rate = $commodity_rate_list->get('cf_3675');
						$agent_rate = $agent_rate;
					}
					
					$check_wis_premium = 10 * $_exchrates;
					if($cargo_insurance_currency_id==13)
					{
						$check_wis_premium = 9 * $_exchrates;
					}
					if($wis_premium < $check_wis_premium && $insurance_type!='Halyk Insurance')
					{
						$wis_premium = $check_wis_premium;				
					}
					
				}
				$globalink_premium = round($globalink_premium);
				$agent_premium = $globalink_premium - $centras_premium;
				//echo $wis_rate;
				//exit;
				$request->set('cf_3639', $total_insured_sum);
		
				$request->set('cf_3641', $globalink_rate);
				$request->set('cf_3635', $wis_rate);
			
				if($assured_company!='85757')
				{
					$agent_rate = '0.00';
					$centras_rate = '0.00';
					$agent_premium = '0.00';
					$centras_premium = '0.00';
				}
				
				$request->set('cf_3665', $agent_rate);
				$request->set('cf_3669', $centras_rate);
				
				$request->set('cf_3645', $globalink_premium);
				$request->set('cf_3637', $wis_premium);
				$request->set('cf_3667', $agent_premium);
				$request->set('cf_3671', $centras_premium);


				//In case if EUR currency choose then we must convert EUR value to USD for expnese and selling
				//EUR insurance must reflect in USD (Expense / Selling)
				if($cargo_insurance_currency_id==13 && ($assured_company!='85757')) 
				{
					$file_title_currency_EUR = 'USD';
					//$b_invoice_date = $request->get('cf_3623');
					if(!empty($wis_date))
					{
						$b_invoice_date_EUR = date('Y-m-d', strtotime($wis_date));
					}
					$b_invoice_date_format_EUR = (empty($wis_date) ? date('Y-m-d') : $b_invoice_date_EUR);
					$b_vendor_currency_code_EUR = Vtiger_CurrencyList_UIType::getDisplayValue($cargo_insurance_currency_id);

					if($file_title_currency_EUR =='KZT')
					{			
						$b_exchange_rate_EUR  = exchange_rate_currency($b_invoice_date_format_EUR, $b_vendor_currency_code_EUR);
					}
					elseif($file_title_currency_EUR =='USD')
					{
						$b_exchange_rate_EUR = currency_rate_convert($b_vendor_currency_code_EUR, $file_title_currency_EUR, 1, $b_invoice_date_format_EUR);
					}
					else{			
						$b_exchange_rate_EUR = currency_rate_convert_others($b_vendor_currency_code_EUR, $file_title_currency_EUR, 1, $b_invoice_date_format_EUR);
					}

					if($file_title_currency_EUR !='USD')
					{						
						$b_buy_local_currency_net_EUR = $wis_premium * $b_exchange_rate_EUR;
						$s_sell_local_currency_net_EUR = $globalink_premium * $b_exchange_rate_EUR;
					}else{
						$b_buy_local_currency_net_EUR = exchange_rate_convert($b_vendor_currency_code_EUR, $file_title_currency_EUR, $wis_premium, $b_invoice_date_format_EUR);
						$s_sell_local_currency_net_EUR = exchange_rate_convert($b_vendor_currency_code_EUR, $file_title_currency_EUR, $globalink_premium, $b_invoice_date_format_EUR);
					}

					//$wis_premium = $b_buy_local_currency_net_EUR;
					$wis_premium = ($wis_premium==9 ? floor($b_buy_local_currency_net_EUR) : $b_buy_local_currency_net_EUR);
					$globalink_premium = $s_sell_local_currency_net_EUR;

				}

			}

			//if($current_user->id==1217)
			//{
			//	$request->set('cf_4559', $request->get('cf_4559'));
			//}
			//echo "<pre>";
			//print_r($request);
			//exit;
			$recordModel = $this->saveRecord($request);
			$insurance_id = $recordModel->getId();
				
			if($FSL_BLACK_INSURANCE)
			{
				//save OWN generated WIS Ref and WIS Date
				//cf_3621 = wis ref
				$sql_q = "SELECT cf_3621 FROM vtiger_cargoinsurancecf 
								WHERE cf_6298 = 'FSL Black Insurance' AND cargoinsuranceid=? 
								LIMIT 1";
				//$params_in = array($insurance_id, $currency_code);
				$params_in = array($insurance_id);
				$sql = $adb->pquery($sql_q, $params_in);
				if ($adb->num_rows($sql)>0) {
					if (trim($adb->fetch_array($sql)['cf_3621']) == '') {
						$this->saveUniqueWISID('vtiger_cargoinsurancecf','cf_3621','cargoinsuranceid',$insurance_id);
					}
				
				}
				/*
				$sql = mysql_query("SELECT cf_3621 FROM vtiger_cargoinsurancecf 
									WHERE cf_6298 = 'FSL Black Insurance' AND cargoinsuranceid='".$insurance_id."'  
									LIMIT 1");
				if (mysql_num_rows($sql)>0) {
					if (trim(mysql_fetch_array($sql)['cf_3621']) == '') {
						$this->saveUniqueWISID('vtiger_cargoinsurancecf','cf_3621','cargoinsuranceid',$insurance_id);
					}
				}*/
				$globalink_premium = '0.00';
			}

			//For JYSAN GARANT Reference #
			if($JYSAN_GARANT_INSURANCE)
			{
				//save OWN generated WIS Ref and WIS Date
				//cf_3621 = wis ref
				$sql_q = "SELECT cf_3621 FROM vtiger_cargoinsurancecf 
								WHERE cf_6298 = 'Jysan Garant' AND cargoinsuranceid=? 
								LIMIT 1";
				//$params_in = array($insurance_id, $currency_code);
				$params_in = array($insurance_id);
				$sql = $adb->pquery($sql_q, $params_in);
				if ($adb->num_rows($sql)>0) {
					if (trim($adb->fetch_array($sql)['cf_3621']) == '') {
						$this->saveUniqueJYSANGARANTWISID('vtiger_cargoinsurancecf','cf_3621','cargoinsuranceid',$insurance_id);
					}
				
				}
			}

			//Code for Insurance Expense
			//To Main JRER
			$recordId = $request->get('record');
			$adb = PearDatabase::getInstance();
		/*mehtab*/
		//cf_1453 = charge/service = 47007
		$query_count_job_jrer_buying = "SELECT * FROM vtiger_jobexpencereportcf
										INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereportcf.jobexpencereportid
										INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid
										WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid=? 
										AND crmentityrel.module='Job' AND crmentityrel.relmodule='Jobexpencereport'
										AND vtiger_jobexpencereportcf.cf_1453=? AND vtiger_jobexpencereportcf.cf_1457='Expence'
										 ";
		
		if(!empty($recordId)) 
		{	
		$moduleName = $request->getModule();				
		//$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
		//$modelData = $recordModel->getData();	
		$adb_r = PearDatabase::getInstance();
		$query_rel = "select crmid from vtiger_crmentityrel where relcrmid =? AND relmodule='CargoInsurance' AND module='Job'";
		$params_rel = array($recordId);	
		$result_rel = $adb_r->pquery($query_rel, $params_rel);
		$row_rel = $adb_r->fetch_array($result_rel);
		$job_id = $row_rel['crmid'];
		$query_count_job_jrer_buying .=' AND vtiger_jobexpencereportcf.cf_2325=?';
		$params_insurance_jrer = array($job_id, '85875', $insurance_id);
		}
		else{
		$job_id = $request->get('returnrecord');
		$query_count_job_jrer_buying .=' AND vtiger_jobexpencereportcf.cf_2325=?';	
		$params_insurance_jrer = array($job_id, '85875', $insurance_id);
		}		
		
		$result_insurance_jrer = $adb->pquery($query_count_job_jrer_buying, $params_insurance_jrer);
				
		$count_job_jrer_buying = $adb->num_rows($result_insurance_jrer);
		
		$row_insurance_jrer = $adb->fetch_array($result_insurance_jrer);
		
		if($count_job_jrer_buying==0)
		{
			$job_id 			  = $request->get('returnrecord');
			if(empty($job_id))
			{
				$query_rel = "select crmid from vtiger_crmentityrel where relcrmid =? AND relmodule='CargoInsurance' AND module='Job'";
				$params_rel = array($insurance_id);	
				$result_rel = $adb_r->pquery($query_rel, $params_rel);
				$row_rel = $adb_r->fetch_array($result_rel);
				$job_id = $row_rel['crmid'];				
			}
		    $sourceModule_job 	= 'Job';	
	 	    $job_info_detail = Vtiger_Record_Model::getInstanceById($job_id, $sourceModule_job);
			
			$b_invoice_date = $request->get('cf_3623');
			if(!empty($b_invoice_date))
			{
				$b_invoice_date = date('Y-m-d', strtotime($b_invoice_date));
			}
			
			
			
			if($assured_company!='85757')
	  		{
				$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency($assured_company);
				$file_title_currency = $reporting_currency;
				
				$b_invoice_date_format = (empty($b_invoice_date) ? date('Y-m-d') : $b_invoice_date);
				$b_vendor_currency_code = Vtiger_CurrencyList_UIType::getDisplayValue(2);
				
				if($file_title_currency =='KZT')
				{			
					$b_exchange_rate  = exchange_rate_currency($b_invoice_date_format, $b_vendor_currency_code);
				}
				elseif($file_title_currency =='USD')
				{
					$b_exchange_rate = currency_rate_convert($b_vendor_currency_code, $file_title_currency, 1, $b_invoice_date_format);
				}
				else{			
					$b_exchange_rate = currency_rate_convert_others($b_vendor_currency_code, $file_title_currency, 1, $b_invoice_date_format);
				}
				
				if($file_title_currency !='USD')
				{						
					$b_buy_local_currency_gross = $wis_premium * $b_exchange_rate;
				}else{
					$b_buy_local_currency_gross = exchange_rate_convert($b_vendor_currency_code, $file_title_currency, $wis_premium, $b_invoice_date_format);
				}
				
				if($file_title_currency !='USD')
				{						
					$b_buy_local_currency_net = $wis_premium * $b_exchange_rate;
				}else{
					$b_buy_local_currency_net = exchange_rate_convert($b_vendor_currency_code, $file_title_currency, $wis_premium, $b_invoice_date_format);
				}


				
						
				$insurance_expenses = array(
								  'b_job_charges_id' => '85875',
								  'b_invoice_no'     => $request->get('cf_3621'),
								  'b_invoice_date'   => (empty($b_invoice_date) ? date('Y-m-d') : $b_invoice_date),//cf_1216
								  'b_due_date'       => (empty($b_invoice_date) ? date('Y-m-d') : $b_invoice_date),//cf_1210
								  'b_vat_rate'       => 0,//cf_1339
								  'b_vat'		    => 0, //cf_1341
								  'b_buy_vendor_currency_net'   => $wis_premium, //cf_1337
								  'b_buy_vendor_currency_gross' => $wis_premium,  //cf_1343
								  'b_vendor_currency'	=> 2, //cf_1345
								  'b_exchange_rate'      => $b_exchange_rate, //cf_1222
								  'b_buy_local_currency_gross' => $b_buy_local_currency_gross, //cf_1347
								  'b_buy_local_currency_net'   => $b_buy_local_currency_net, //cf_1349
								  'b_variation_expected_actual_buying' => (0-$wis_premium), //cf_1353
								  'b_type_id'    => '85773',  //cf_1214 DW Bank D
								  'b_pay_to_id'  => '438114',     //cf_1367
								  'insurance_id' => $insurance_id, //cf_2325
								  'label'	    => 'Main JRER DW Cargo Insurance Expense',
								  'parentmodule' => 'Job'								  
								  );
			}
			else{
			
			$insurance_expenses = array(
								  'b_job_charges_id' => '85875',
								  'b_invoice_no'     => $request->get('cf_3621'),
								  'b_invoice_date'   => (empty($b_invoice_date) ? date('Y-m-d') : $b_invoice_date),//cf_1216
								  'b_due_date'       => (empty($b_invoice_date) ? date('Y-m-d') : $b_invoice_date),//cf_1210
								  'b_vat_rate'       => 0,//cf_1339
								  'b_vat'		    => 0, //cf_1341
								  'b_buy_vendor_currency_net'   => $globalink_premium, //cf_1337
								  'b_buy_vendor_currency_gross' => $globalink_premium,  //cf_1343
								  'b_vendor_currency'	=> 1, //cf_1345
								  'b_exchange_rate'      => 1, //cf_1222
								  'b_buy_local_currency_gross' => $globalink_premium, //cf_1347
								  'b_buy_local_currency_net'   => $globalink_premium, //cf_1349
								  'b_variation_expected_actual_buying' => (0-$globalink_premium), //cf_1353
								  'b_type_id'    => '85785',  //cf_1214 KZ Bank R
								  'b_pay_to_id'  => '498559',     //cf_1367
								  'insurance_id' => $insurance_id, //cf_2325
								  'label'	    => 'Main JRER Cargo Insurance Expense',
								  'parentmodule' => 'Job'								  
								  );
			}
			 
			 if(!$FSL_BLACK_INSURANCE)	
			 {				  
				$this->saveInsuranceExpense($recordModel, $request, $insurance_expenses, $job_info_detail);
			 }
			
			if($assured_company!='85757')
	  		{
				$dw_final_premium = $globalink_premium;
				
				$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency($assured_company);
				$file_title_currency = $reporting_currency;
				
				$s_customer_currency_code = Vtiger_CurrencyList_UIType::getDisplayValue(2);
				
				$s_invoice_date_format =(empty($b_invoice_date) ? date('Y-m-d') : $b_invoice_date);
				
				if($file_title_currency =='KZT')
				{			
					$s_exchange_rate  		= exchange_rate_currency($s_invoice_date_format, $s_customer_currency_code);
				}
				elseif($file_title_currency =='USD')
				{
					$s_exchange_rate = currency_rate_convert($s_customer_currency_code, $file_title_currency, 1, $s_invoice_date_format);
				}
				else{			
					$s_exchange_rate = currency_rate_convert_others($s_customer_currency_code, $file_title_currency, 1, $s_invoice_date_format);
				}
				
				
				if($file_title_currency !='USD')
				{	
					$s_sell_local_currency_gross = $dw_final_premium * $s_exchange_rate;
				}else{
					$s_sell_local_currency_gross = exchange_rate_convert($s_customer_currency_code, $file_title_currency, $dw_final_premium, $s_invoice_date_format);
				}
				
				if($file_title_currency !='USD')
				{	
					$s_sell_local_currency_net = $dw_final_premium * $s_exchange_rate;
				}else{
					$s_sell_local_currency_net = exchange_rate_convert($s_customer_currency_code, $file_title_currency, $dw_final_premium, $s_invoice_date_format);
				}

				if($selling_insurance_flag=='No')
				{
					$dw_final_premium='0.0';
					$s_sell_local_currency_gross = '0.0';
					$s_sell_local_currency_net = '0.0';
				}

				
				$insurance_selling = array(
								  's_job_charges_id' => '85875',
								  's_invoice_date'   => (empty($b_invoice_date) ? date('Y-m-d') : $b_invoice_date), //cf_1355
								  's_selling_customer_currency_net' => $dw_final_premium, //cf_1357
								  's_vat_rate'	=> 0, //cf_1228
								  's_vat'		 => 0, //cf_1230
								  's_sell_customer_currency_gross' => $dw_final_premium, //cf_1232
								  's_customer_currency' => 2, //cf_1234
								  's_exchange_rate'     => $s_exchange_rate, //cf_1236
								  's_sell_local_currency_gross' => $s_sell_local_currency_gross, //cf_1238
								  's_sell_local_currency_net'   => $s_sell_local_currency_net, //cf_1240
								  's_expected_sell_local_currency_net' => 0, //cf_1242
								  's_variation_expected_and_actual_selling' => '0.00', //cf_1244	 
								  's_variation_expect_and_actual_profit' => '0.00', //cf_1246 
								  's_bill_to_id' => $request->get('cf_3601'),
								  'insurance_id' => $insurance_id, //cf_2325
								  'label'		=> 'Selling DW Cargo Insurance Expense',
								  'parentmodule' => 'Job',
								  );
				
			}else
			{
			
				if($selling_insurance_flag=='No')
				{
					$globalink_premium='0.0';
				}

			$insurance_selling = array(
								  's_job_charges_id' => '85875',
								  's_invoice_date'   => (empty($b_invoice_date) ? date('Y-m-d') : $b_invoice_date), //cf_1355
								  's_selling_customer_currency_net' => $globalink_premium, //cf_1357
								  's_vat_rate'	=> 0, //cf_1228
								  's_vat'		 => 0, //cf_1230
								  's_sell_customer_currency_gross' => $globalink_premium, //cf_1232
								  's_customer_currency' => 1, //cf_1234
								  's_exchange_rate'     => 1, //cf_1236
								  's_sell_local_currency_gross' => $globalink_premium, //cf_1238
								  's_sell_local_currency_net'   => $globalink_premium, //cf_1240
								  's_expected_sell_local_currency_net' => 0, //cf_1242
								  's_variation_expected_and_actual_selling' => '0.00', //cf_1244	 
								  's_variation_expect_and_actual_profit' => '0.00', //cf_1246 
								  's_bill_to_id' => $request->get('cf_3601'),
								  'insurance_id' => $insurance_id, //cf_2325
								  'label'		=> 'Selling Cargo Insurance Expense',
								  'parentmodule' => 'Job',
								  );
			}
			
			/*
			if($selling_insurance_flag=='Yes')
			{					  	
				$this->saveInsuranceSelling($recordModel, $request, $insurance_selling, $job_info_detail);
			}*/
			$this->saveInsuranceSelling($recordModel, $request, $insurance_selling, $job_info_detail);
		}
		else{						
			
			$b_invoice_date = $request->get('cf_3623');
			if(!empty($b_invoice_date))
			{
				$b_invoice_date = date('Y-m-d', strtotime($b_invoice_date));
			}
			else{
				$b_invoice_date = date('Y-m-d');
			}
			
			if($assured_company!='85757')
	  		{
				$reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency($assured_company);
				$file_title_currency = $reporting_currency;
				
				$b_invoice_date_format = (empty($b_invoice_date) ? date('Y-m-d') : $b_invoice_date);
				$b_vendor_currency_code = Vtiger_CurrencyList_UIType::getDisplayValue(2);
				
				if($file_title_currency =='KZT')
				{			
					$b_exchange_rate  = exchange_rate_currency($b_invoice_date_format, $b_vendor_currency_code);
				}
				elseif($file_title_currency =='USD')
				{
					$b_exchange_rate = currency_rate_convert($b_vendor_currency_code, $file_title_currency, 1, $b_invoice_date_format);
				}
				else{			
					$b_exchange_rate = currency_rate_convert_others($b_vendor_currency_code, $file_title_currency, 1, $b_invoice_date_format);
				}
				
				if($file_title_currency !='USD')
				{						
					$b_buy_local_currency_gross = $wis_premium * $b_exchange_rate;
				}else{
					$b_buy_local_currency_gross = exchange_rate_convert($b_vendor_currency_code, $file_title_currency, $wis_premium, $b_invoice_date_format);
				}
				
				if($file_title_currency !='USD')
				{						
					$b_buy_local_currency_net = $wis_premium * $b_exchange_rate;
				}else{
					$b_buy_local_currency_net = exchange_rate_convert($b_vendor_currency_code, $file_title_currency, $wis_premium, $b_invoice_date_format);
				}
				
				
				
				$dw_final_premium = $globalink_premium;
				
				$s_customer_currency_code = Vtiger_CurrencyList_UIType::getDisplayValue(2);
				
				$s_invoice_date_format =(empty($b_invoice_date) ? date('Y-m-d') : $b_invoice_date);
				
				if($file_title_currency =='KZT')
				{			
					$s_exchange_rate  		= exchange_rate_currency($s_invoice_date_format, $s_customer_currency_code);
				}
				elseif($file_title_currency =='USD')
				{
					$s_exchange_rate = currency_rate_convert($s_customer_currency_code, $file_title_currency, 1, $s_invoice_date_format);
				}
				else{			
					$s_exchange_rate = currency_rate_convert_others($s_customer_currency_code, $file_title_currency, 1, $s_invoice_date_format);
				}
				
				
				if($file_title_currency !='USD')
				{	
					$s_sell_local_currency_gross = $dw_final_premium * $s_exchange_rate;
				}else{
					$s_sell_local_currency_gross = exchange_rate_convert($s_customer_currency_code, $file_title_currency, $dw_final_premium, $s_invoice_date_format);
				}
				
				if($file_title_currency !='USD')
				{	
					$s_sell_local_currency_net = $dw_final_premium * $s_exchange_rate;
				}else{
					$s_sell_local_currency_net = exchange_rate_convert($s_customer_currency_code, $file_title_currency, $dw_final_premium, $s_invoice_date_format);
				}
				
			}
			
			
			
			$adb = PearDatabase::getInstance();
			$query_insurance_expense_after = "UPDATE vtiger_jobexpencereportcf SET cf_1222=?, cf_1337=?, cf_1343=?, cf_1347=?, cf_1349=?, cf_1353=?, cf_2325=?, cf_1216=?, cf_1212=? WHERE jobexpencereportid=? AND cf_2325=?";
			//$variation_expected_and_actual_buying = $row_insurance_jrer['cf_1351'] - $globalink_premium;
			$variation_expected_and_actual_buying = 0;
			$check_params_after = array('1',$globalink_premium, $globalink_premium, $globalink_premium, $globalink_premium, $variation_expected_and_actual_buying, $insurance_id, $b_invoice_date, $request->get('cf_3621'), $row_insurance_jrer['jobexpencereportid'], $insurance_id);
			
			if($assured_company!='85757')
	  		{
			$check_params_after = array($b_exchange_rate, $wis_premium, $wis_premium, $b_buy_local_currency_gross, $b_buy_local_currency_net, $variation_expected_and_actual_buying, $insurance_id, $b_invoice_date, $request->get('cf_3621'), $row_insurance_jrer['jobexpencereportid'], $insurance_id);	
			}
			//echo "<pre>";
			//print_r($check_params_after);
			//exit;
			$result_insurance_expense_after = $adb->pquery($query_insurance_expense_after, $check_params_after);
			
			/*
			3. In Case Selling is "No" in "Cargo insurance", selling in "Job Revenue & Expense" section has to be "0".
			*/
			$selling_insurance = $recordModel->get('cf_4559'); //Selling Insurance "Yes/No".
			if($selling_insurance=='No')
			{
				$globalink_premium = '0.0';
				$dw_final_premium = '0.0';
				$s_sell_local_currency_gross = '0.0';
				$s_sell_local_currency_net = '0.0';
			}
				
			$adb_s = PearDatabase::getInstance();
			//cf_1453 = charge/service = 47007
			$query_count_job_jrer_selling = "SELECT * FROM vtiger_jobexpencereportcf
											INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_jobexpencereportcf.jobexpencereportid
											INNER JOIN vtiger_crmentityrel as crmentityrel ON vtiger_crmentity.crmid= crmentityrel.relcrmid
											WHERE vtiger_crmentity.deleted=0 AND crmentityrel.crmid=? 
											AND crmentityrel.module='Job' AND crmentityrel.relmodule='Jobexpencereport'
											AND vtiger_jobexpencereportcf.cf_1455=? AND vtiger_jobexpencereportcf.cf_1457='Selling'
											 ";
			//$job_id = $request->get('sourceRecord');
			if(!empty($recordId)) 
			{
			$moduleName = $request->getModule();				
			//$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
			//$modelData = $recordModel->getData();	
			$adb_r = PearDatabase::getInstance();
			$query_rel = "select crmid from vtiger_crmentityrel where relcrmid =? AND relmodule='CargoInsurance' AND module='Job'";
			$params_rel = array($recordId);	
			$result_rel = $adb_r->pquery($query_rel, $params_rel);
			$row_rel = $adb_r->fetch_array($result_rel);
			$job_id = $row_rel['crmid'];
			$query_count_job_jrer_selling .=' AND vtiger_jobexpencereportcf.cf_2325=?';
			$params_insurance_jrer_s = array($job_id, '85875', $insurance_id);
			}
			else{
			$job_id = $request->get('returnrecord');	
			$query_count_job_jrer_selling .=' AND vtiger_jobexpencereportcf.cf_2325=?';
			$params_insurance_jrer_s = array($job_id, '85875', $insurance_id);
			}
			//$params_insurance_jrer_s = array($job_id, '85875');
			$result_insurance_jrer_s = $adb_s->pquery($query_count_job_jrer_selling, $params_insurance_jrer_s);
			$row_insurance_jrer_s = $adb_s->fetch_array($result_insurance_jrer_s);


						
			$adb_ss = PearDatabase::getInstance();
			$query_insurance_selling_after = "UPDATE vtiger_jobexpencereportcf SET cf_1236=?, cf_1355=?, cf_1357=?, cf_1232=?, cf_1238=?, cf_1240=?, cf_2325=?, cf_1445=?  WHERE jobexpencereportid=? AND cf_2325=?";
			
			$check_params_after_ss = array('1',$b_invoice_date, $globalink_premium, $globalink_premium, $globalink_premium, $globalink_premium, $insurance_id,$request->get('cf_3601'), $row_insurance_jrer_s['jobexpencereportid'], $insurance_id);
			if($assured_company!='85757')
	  		{
			$check_params_after_ss = array($s_exchange_rate, $b_invoice_date, $dw_final_premium, $dw_final_premium, $s_sell_local_currency_gross, $s_sell_local_currency_net, $insurance_id,$request->get('cf_3601'), $row_insurance_jrer_s['jobexpencereportid'], $insurance_id);
			}
			
			$result_insurance_selling_after = $adb_ss->pquery($query_insurance_selling_after, $check_params_after_ss);	
			
		}
		
		/*mehtab*/

		//exit;

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
			header("Location: $loadUrl");
		} catch(DiscountException $e)	{
			$requestData = $request->getAll();
			$moduleName = $request->getModule();
			unset($requestData['action']);
			unset($requestData['__vtrftk']);

			$requestData['view'] = 'Edit';
			$requestData['discount'] = true;
			
			$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

			global $vtiger_current_version;
			$viewer = new Vtiger_Viewer();

			$viewer->assign('REQUEST_DATA', $requestData);
			$viewer->assign('REQUEST_URL', $moduleModel->getCreateRecordUrl().'&record='.$request->get('record'));
			$viewer->view('RedirectToEditView.tpl', 'Vtiger');

		}catch(FSLBlackException $e)	{
			$requestData = $request->getAll();
			$moduleName = $request->getModule();
			unset($requestData['action']);
			unset($requestData['__vtrftk']);

			$requestData['view'] = 'Edit';
			$requestData['fsl_black'] = true;
			
			$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

			global $vtiger_current_version;
			$viewer = new Vtiger_Viewer();

			$viewer->assign('REQUEST_DATA', $requestData);
			$viewer->assign('REQUEST_URL', $moduleModel->getCreateRecordUrl().'&record='.$request->get('record'));
			$viewer->view('RedirectToEditView.tpl', 'Vtiger');

		}catch (DuplicateException $e) {
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


	public function saveUniqueWISID($module,$column,$table_id,$id) {
		$adb = PearDatabase::getInstance();
		$arr0 = 'FSL';//FSL Black Insurance 
		$insurance_type_where  =" cf_6298 = 'FSL Black Insurance' AND  "; //live field
		//$insurance_type_where = " cf_6260 = 'FSL Black Insurance' AND "; //Test2 field
		//$sql = mysql_query("SELECT `$column` FROM `$module` WHERE $insurance_type_where `$column` LIKE '$arr0%' ORDER BY `$column` DESC LIMIT 1");
		$sql = $adb->pquery("SELECT `$column` FROM `$module` WHERE $insurance_type_where `$column` LIKE '$arr0%' ORDER BY `$column` 
									DESC LIMIT 1",array());
		$arr = $adb->fetch_array($sql)[$column];
		//$arr = mysql_fetch_array($sql)[$column];
		$date = date('y');
		if ($arr != '') {
			$arr = explode('-',$arr);
			$arr1 = explode('/',$arr[1]);
			if ($arr1[1] == $date) {
				$num = (int)$arr1[0];
				$num++;
				$res = $arr0.'-'.sprintf("%05s", $num).'/'.$date;
			} else $res = $arr0.'-00001/'.$date;
		} else $res = $arr0.'-00001/'.$date;
		$adb->pquery("UPDATE `$module` SET `$column`='".$res."', cf_3623='".date('Y-m-d')."' WHERE `$table_id`='".$id."' LIMIT 1",array());
	}

	public function saveUniqueJYSANGARANTWISID($module,$column,$table_id,$id) {
		$adb = PearDatabase::getInstance();
		$arr0 = 'JSN';//Jysan Garant
		$insurance_type_where  =" cf_6298 = 'Jysan Garant' AND  "; //live field
		//$insurance_type_where = " cf_6260 = 'FSL Black Insurance' AND "; //Test2 field
		//$sql = mysql_query("SELECT `$column` FROM `$module` WHERE $insurance_type_where `$column` LIKE '$arr0%' ORDER BY `$column` DESC LIMIT 1");
		$sql = $adb->pquery("SELECT `$column` FROM `$module` WHERE $insurance_type_where `$column` LIKE '$arr0%' ORDER BY `$column` 
									DESC LIMIT 1",array());
		$arr = $adb->fetch_array($sql)[$column];
		//$arr = mysql_fetch_array($sql)[$column];
		$date = date('y');
		if ($arr != '') {
			$arr = explode('-',$arr);
			$arr1 = explode('/',$arr[1]);
			if ($arr1[1] == $date) {
				$num = (int)$arr1[0];
				$num++;
				$res = $arr0.'-'.sprintf("%05s", $num).'/'.$date;
			} else $res = $arr0.'-00001/'.$date;
		} else $res = $arr0.'-00001/'.$date;
		$adb->pquery("UPDATE `$module` SET `$column`='".$res."', cf_3623='".date('Y-m-d')."' WHERE `$table_id`='".$id."' LIMIT 1",array());
	}
	
	public function saveInsuranceSelling($recordModel, $request, $insurance_selling=array(), $job_info_detail)
	{
		$adb = PearDatabase::getInstance();
		$current_user = Users_Record_Model::getCurrentUserModel();
		$ownerId = $recordModel->get('assigned_user_id');
		$date_var = date("Y-m-d H:i:s");
		$usetime = $adb->formatDate($date_var, true);
		$job_insurance_id = $recordModel->getId();
		
		$current_id = $adb->getUniqueId('vtiger_crmentity');
		$source_id = $request->get('returnrecord');
		if(empty($source_id))
		{
			$adb_r = PearDatabase::getInstance();
			$query_rel = "select crmid from vtiger_crmentityrel where relcrmid =? AND relmodule='CargoInsurance' AND module='Job'";
			$params_rel = array($job_insurance_id);	
			$result_rel = $adb_r->pquery($query_rel, $params_rel);
			$row_rel = $adb_r->fetch_array($result_rel);
			$source_id = $row_rel['crmid'];				
		}
		
		//INSERT data in JRER expense module from job costing
		$adb->pquery("INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid,
			 setype, description, createdtime, modifiedtime, presence, deleted, label)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
			array($current_id, $job_info_detail->get('assigned_user_id'), $job_info_detail->get('assigned_user_id'), 'Jobexpencereport', 'NULL', $date_var, $date_var, 1, 0, $insurance_selling['label']));
		
		//INSERT data in jobexpencereport module from Fleet
		$adb_e = PearDatabase::getInstance();
		$jobexpencereport_insert_query = "INSERT INTO vtiger_jobexpencereport(jobexpencereportid, name, user_id, owner_id, job_id) VALUES(?,?,?,?,?)";
		$params_jobexpencereport= array($current_id, $recordModel->getId(), $job_info_detail->get('assigned_user_id'), $job_info_detail->get('assigned_user_id'), $source_id);
		//print_r($params_jobexpencereport);			
		$adb_e->pquery($jobexpencereport_insert_query, $params_jobexpencereport);			
		$jobexpencereportid = $current_id;
		
					
		//cf_1477 = Office
		//cf_1479 = Department
		//cf_1445 = bill_to
		//cf_1234 = selling currency
		//cf_1236 = exchange rate
		//cf_1242 = Expected Sell (Local Currency Net)
		$adb_ecf = PearDatabase::getInstance();
		$jobexpencereportcf_insert_query = "INSERT INTO vtiger_jobexpencereportcf(jobexpencereportid, cf_1479, cf_1477, cf_1455, cf_1242, cf_1457, cf_1355, cf_1357, cf_1228, cf_1230, cf_1232, cf_1234, cf_1236, cf_1238, cf_1240, cf_1244, cf_1246, cf_2325, cf_1445, cf_1365, cf_2695) 
											VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$params_jobexpencereportcf = array($current_id, $job_info_detail->get('cf_1190'), $job_info_detail->get('cf_1188'), $insurance_selling['s_job_charges_id'],  
										   $insurance_selling['s_expected_sell_local_currency_net'], 'Selling', 
										   $insurance_selling['s_invoice_date'], $insurance_selling['s_selling_customer_currency_net'],
										   $insurance_selling['s_vat_rate'], $insurance_selling['s_vat'], 
										   $insurance_selling['s_sell_customer_currency_gross'], $insurance_selling['s_customer_currency'],
										   $insurance_selling['s_exchange_rate'], $insurance_selling['s_sell_local_currency_gross'], 
										   $insurance_selling['s_sell_local_currency_net'], $insurance_selling['s_variation_expected_and_actual_selling'],
										   $insurance_selling['s_variation_expect_and_actual_profit'],
										   $insurance_selling['insurance_id'], 
										   $insurance_selling['s_bill_to_id'],
										   'Insurance',
										   'Without VAT'
										    );
								   
		$adb_ecf->pquery($jobexpencereportcf_insert_query, $params_jobexpencereportcf);
		$jobexpencereportcfid = $current_id;
		
		$adb_rel = PearDatabase::getInstance();
		$crmentityrel_insert_query = "INSERT INTO vtiger_crmentityrel(crmid, module, relcrmid, relmodule) VALUES(?, ?, ?, ?)";
		$params_crmentityrel = array($source_id, 'Job', $jobexpencereportcfid, 'Jobexpencereport');
		$adb_rel->pquery($crmentityrel_insert_query, $params_crmentityrel);
		
		
	}
	
	public function saveInsuranceExpense($recordModel, $request, $insurance_expence=array(), $job_info_detail)
	{   
		
		$adb = PearDatabase::getInstance();
		$current_user = Users_Record_Model::getCurrentUserModel();
		$ownerId = $recordModel->get('assigned_user_id');
		$date_var = date("Y-m-d H:i:s");
		$usetime = $adb->formatDate($date_var, true);
		$job_insurance_id = $recordModel->getId();
		
		$current_id = $adb->getUniqueId('vtiger_crmentity');
		$source_id = $request->get('returnrecord');
		if(empty($source_id))
		{
			$adb_r = PearDatabase::getInstance();
			$query_rel = "select crmid from vtiger_crmentityrel where relcrmid =? AND relmodule='CargoInsurance' AND module='Job'";
			$params_rel = array($job_insurance_id);	
			$result_rel = $adb_r->pquery($query_rel, $params_rel);
			$row_rel = $adb_r->fetch_array($result_rel);
			$source_id = $row_rel['crmid'];				
		}
		
		
		//INSERT data in JRER expense module from job costing
		$adb->pquery("INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid,
			 setype, description, createdtime, modifiedtime, presence, deleted, label)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
			array($current_id, $job_info_detail->get('assigned_user_id'), $job_info_detail->get('assigned_user_id'), 'Jobexpencereport', 'NULL', $date_var, $date_var, 1, 0, $insurance_expence['label']));
		
		
		//INSERT data in jobexpencereport module from Fleet
		$adb_e = PearDatabase::getInstance();
		$jobexpencereport_insert_query = "INSERT INTO vtiger_jobexpencereport(jobexpencereportid, name, user_id, owner_id, job_id) VALUES(?,?,?,?,?)";
		$params_jobexpencereport= array($current_id, $recordModel->getId(), $job_info_detail->get('assigned_user_id'), $job_info_detail->get('assigned_user_id'), $source_id);			
		$adb_e->pquery($jobexpencereport_insert_query, $params_jobexpencereport);			
		$jobexpencereportid = $current_id;
		
		//cf_1477 = Office
		//cf_1479 = Department
		//cf_1367 = pay_to
		//cf_1345 = vendor currency
		//cf_1222 = exchange rate
		//cf_1351 = Expected Buy (Local Currency NET)
		$adb_ecf = PearDatabase::getInstance();
		$jobexpencereportcf_insert_query = "INSERT INTO vtiger_jobexpencereportcf(jobexpencereportid, cf_1479, cf_1477, cf_1453, cf_1214, cf_1351, cf_1457, cf_1337, cf_1343, cf_1347, cf_1349, cf_1353, cf_1216, cf_1210, cf_1345, cf_1222, cf_1339, cf_1341, cf_1367, cf_2325, cf_1212) 
											VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$params_jobexpencereportcf = array($current_id, $job_info_detail->get('cf_1190'), $job_info_detail->get('cf_1188'), $insurance_expence['b_job_charges_id'], $insurance_expence['b_type_id'], 
										   $insurance_expence['b_expected_buy_local_currency_net'], 'Expence', $insurance_expence['b_buy_vendor_currency_net'], 
										   $insurance_expence['b_buy_vendor_currency_gross'], $insurance_expence['b_buy_local_currency_gross'], 
										   $insurance_expence['b_buy_local_currency_net'], $insurance_expence['b_variation_expected_actual_buying'], 
										   $insurance_expence['b_invoice_date'], $insurance_expence['b_due_date'], $insurance_expence['b_vendor_currency'], 
										   $insurance_expence['b_exchange_rate'], $insurance_expence['b_vat_rate'], $insurance_expence['b_vat'], 
										   $insurance_expence['b_pay_to_id'], $insurance_expence['insurance_id'], $insurance_expence['b_invoice_no']										   
										   );
		$adb_ecf->pquery($jobexpencereportcf_insert_query, $params_jobexpencereportcf);
		$jobexpencereportcfid = $current_id;
		
		$adb_rel = PearDatabase::getInstance();
		$crmentityrel_insert_query = "INSERT INTO vtiger_crmentityrel(crmid, module, relcrmid, relmodule) VALUES(?, ?, ?, ?)";
		$params_crmentityrel = array($source_id, $insurance_expence['parentmodule'], $jobexpencereportcfid, 'Jobexpencereport');
		$adb_rel->pquery($crmentityrel_insert_query, $params_crmentityrel);
	}
	

	/**
	 * Function to save record
	 * @param <Vtiger_Request> $request - values of the record
	 * @return <RecordModel> - record Model of saved record
	 */
	public function saveRecord($request) {
		$recordModel = $this->getRecordModelFromRequest($request);
		if($request->get('imgDeleted')) {
			$imageIds = $request->get('imageid');
			foreach($imageIds as $imageId) {
				$status = $recordModel->deleteImage($imageId);
			}
		}
	
		$recordModel->save();
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
		}elseif($request->get('returnmodule') == 'Job' && $request->get('returnrecord')) {
			$parentModuleName = $request->get('returnmodule');
			$parentRecordId = $request->get('returnrecord');
			$parentModuleModel = Vtiger_Module_Model::getInstance($parentModuleName);
			$relatedModule = $recordModel->getModule();
			$relatedRecordId = $recordModel->getId();
			$relationModel = Vtiger_Relation_Model::getInstance($parentModuleModel, $relatedModule);
			$relationModel->addRelation($parentRecordId, $relatedRecordId);
		}
	
		$this->savedRecordId = $recordModel->getId();
		return $recordModel;
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
		return $recordModel;
	}
}

class FSLBlackException extends Exception {};
class DiscountException extends Exception {};
