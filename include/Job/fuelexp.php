<?php
include_once '/vtlib/Vtiger/Module.php';
include_once '/modules/Vtiger/CRMEntity.php';
include_once '/includes/main/WebUI.php';
include_once '/include/Webservices/Utils.php';
$adb = PearDatabase::getInstance();

$query_roundtrip = 'SELECT * FROM vtiger_roundtripcf
                                             INNER JOIN vtiger_roundtrip ON vtiger_roundtrip.roundtripid = vtiger_roundtripcf.roundtripid
                                             INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_roundtripcf.roundtripid 
                                             INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid)
                                             WHERE vtiger_crmentityrel.crmid=?  AND vtiger_crmentity.deleted=0
                                             AND vtiger_crmentityrel.module="Fleettrip"
                                             AND vtiger_crmentityrel.relmodule="Roundtrip" 
                                             ';
                    
                    $params_roundtrip = array($request->get('sourceRecord'));                       
                    $result_roundtrip = $db_roundtrip->pquery($query_roundtrip, $params_roundtrip);
                   // $numRows_roundtrip = $db_roundtrip->num_rows($result_roundtrip);


                    $master_job_file_arr_final = array();
                    $total_revenew_final = 0;
                    for($jj=0; $jj< $db_roundtrip->num_rows($result_roundtrip); $jj++ ) {
                         $row_roundtrip = $db_roundtrip->fetch_row($result_roundtrip,$jj);
                           $total_revenew_final += $row_roundtrip['cf_3173'];
                         
                         $master_job_file_arr_final[] = array(

													'roundtripid'=> $row_roundtrip['roundtripid'], 
													'job_id' => $row_roundtrip['cf_3175'], 
													'internal_selling' => $row_roundtrip['cf_3173'], 
													'round_trip_id' => $row_roundtrip['roundtripid'], 
													'fleet_trip_id'=> $request->get('sourceRecord'),
													'selling_created_time' => $row_roundtrip['createdtime'],
													'round_trip_invoice_date' => $row_roundtrip['cf_5766'],
													'fleettrip_truck_location_id' => $row_roundtrip['fleettrip_truck_location_id']);    

                              
                                                                  
                    }
               
                 
    include("include/Exchangerate/exchange_rate_class.php");   
    foreach($master_job_file_arr_final as $key => $value)
    {
		   $internal_selling_final = $value['internal_selling'];
			
			 $round_trip_invoice_date = $value['round_trip_invoice_date'];
			 $str_round_trip_invoice_date = strtotime($round_trip_invoice_date); 
			 
			 $created_time_internal_selling = $value['selling_created_time'];
			 $timestamp = strtotime($created_time_internal_selling);
			 $selling_date = date('Y-m-d', $timestamp);
			 if($str_round_trip_invoice_date > strtotime(date('Y-m-d')))
			 {
			      $selling_date = date('Y-m-d', $timestamp);
			 }
			 else{
			      $selling_date = $round_trip_invoice_date;
			 }


			 $fleet_expence_label = 'Fuel Expense';
			 $job_id = $value['job_id'];

			 $sourceModule		= 'Job';	
			 $source_id = $job_id;
			
			 
			$job_info = Vtiger_Record_Model::getInstanceById($job_id,$sourceModule);
			$job_reporting_currency = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$job_info->get('cf_1186'));
			$job_file_title_currency = $job_reporting_currency;
	  	
      $roundtripid = $value['roundtripid'];
	    $fleet_trip_id   =$value['fleet_trip_id'];
			$sourceModule_trip 	= 'Fleettrip';
			$fleet_trip_info = Vtiger_Record_Model::getInstanceById($fleet_trip_id, $sourceModule_trip);
			$fleet_trip_user_info = Users_Record_Model::getInstanceById($fleet_trip_info->get('assigned_user_id'), 'Users');
			$fleet_trip_user_company_id = $fleet_trip_user_info->get('company_id');
			
			$fleet_trip_user_local_currency_code = Vtiger_CompanyList_UIType::getCompanyReportingCurrency(@$fleet_trip_user_company_id);
			$fleet_trip_user_currency_id = Vtiger_CompanyList_UIType::getCompanyReportingCurrencyID(@$fleet_trip_user_company_id);
     
		  $b_vendor_currency = $fleet_trip_user_currency_id;
		  $b_invoice_date = $selling_date;
			$b_invoice_date_format = date('Y-m-d', strtotime($b_invoice_date));
			
			$b_vendor_currency_code = Vtiger_CurrencyList_UIType::getDisplayValue($b_vendor_currency);
			if($job_file_title_currency =='KZT')
			{			
			$b_exchange_rate  		= exchange_rate_currency($b_invoice_date_format, $b_vendor_currency_code);
			}
			elseif($job_file_title_currency =='USD')
			{

				$b_exchange_rate = currency_rate_convert($b_vendor_currency_code, $job_file_title_currency, 1, $b_invoice_date_format);
			}
			else{			
			  $b_exchange_rate  = currency_rate_convert_others($b_vendor_currency_code, $job_file_title_currency, 1, $b_invoice_date_format);
			}


		
			 //$b_exchange_rate_selling = $b_exchange_rate = currency_rate_convert('KZT', 'USD',  1, $selling_date); //Old code for KZT only
			 $b_exchange_rate_selling = currency_rate_convert($fleet_trip_user_local_currency_code, 'USD',  1, $selling_date);
			 
			 
			 $internal_selling_per_job_kz = $internal_selling_final*$b_exchange_rate_selling;
       
			 $percentage_per_job = ($internal_selling_final*100)/$total_revenew_final;
		      
		        
		        //internal selling percentage end there now i want to get expenses 

         
			$fleet_id = $value['fleet_trip_id'];

			$db_fuel_expenses = PearDatabase::getInstance();
			$query_fuel_expenses = 'SELECT 	vtiger_jobexpencereportcf.jobexpencereportid,vtiger_jobexpencereportcf.cf_1367 ,sum(vtiger_jobexpencereportcf.cf_1337) as b_buy_vendor_currency_net, 
								   sum(vtiger_jobexpencereportcf.cf_1339) as b_vat_rate, 
								   sum(vtiger_jobexpencereportcf.cf_1341) as b_vat, 
								   sum(vtiger_jobexpencereportcf.cf_1343) as b_buy_vendor_currency_gross, 
								   sum(vtiger_jobexpencereportcf.cf_1347) as b_buy_local_currency_gross, 
								   sum(vtiger_jobexpencereportcf.cf_1349) as b_buy_local_currency_net, 
								   sum(vtiger_jobexpencereportcf.cf_1351) as b_expected_buy_local_currency_net, 
								   sum(vtiger_jobexpencereportcf.cf_1353) as b_variation_expected_and_actual_buying,
								   vtiger_jobexpencereportcf.cf_1453 as fuel_type,
								    vtiger_jobexpencereportcf.cf_1479 as department,
								    vtiger_jobexpencereportcf.cf_1477 as location,
								     vtiger_jobexpencereportcf.cf_1345 as currencyTitle,
								     vtiger_jobexpencereportcf.cf_1222 as exchangeRate
								    
								   
							FROM vtiger_jobexpencereport 
							INNER JOIN vtiger_jobexpencereportcf ON vtiger_jobexpencereportcf.jobexpencereportid = vtiger_jobexpencereport.jobexpencereportid 
							INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_jobexpencereport.jobexpencereportid 
							WHERE vtiger_crmentityrel.crmid=? 
							AND vtiger_jobexpencereportcf.cf_1457="Expence" 
                            AND vtiger_jobexpencereport.owner_id = "'.$fleet_trip_info->get('assigned_user_id').'" ORDER BY vtiger_jobexpencereportcf.jobexpencereportid  DESC';
	         
				  $params_fleet = array($job_id);
			
				$result_fuel_expenses = $db_fuel_expenses->pquery($query_fuel_expenses,$params_fleet);

				$jer_fuel_expenses = $db_fuel_expenses->fetch_array($result_fuel_expenses);
        
				$fuelExpJob_vendor_currency = ($percentage_per_job/100)* $jer_fuel_expenses['b_buy_vendor_currency_net'];
				$fuelExpJob_vendor_currency_cross = ($percentage_per_job/100)* $jer_fuel_expenses['b_buy_vendor_currency_gross'];
				
				$fuelExpJob_local_currency_cross = ($percentage_per_job/100)*$jer_fuel_expenses['b_buy_local_currency_gross'];
				$fuelExpJob_local_local_currency = ($percentage_per_job/100)*$jer_fuel_expenses['b_buy_local_currency_net'];
      
          if($job_file_title_currency!='USD')
          {
              $b_buy_local_currency_gross = $fuelExpJob_local_currency_cross * $b_exchange_rate;

               $b_buy_local_currency_net  = $fuelExpJob_local_local_currency * $b_exchange_rate;

                $b_buy_vendor_currency_net = $fuelExpJob_vendor_currency * $b_exchange_rate;
              
          }else{
                  $b_buy_local_currency_gross = $fuelExpJob_local_currency_cross;
                  $b_buy_local_currency_net  = $fuelExpJob_local_local_currency;
                  $b_buy_vendor_currency_net = $fuelExpJob_vendor_currency;
          	  
          }


       //updated on internal selling code is start here  
				$adb_roundtrip_check = PearDatabase::getInstance();
			 $query_roundtrip_check = "SELECT * FROM vtiger_jer WHERE vtiger_jer.fleettripid=? AND vtiger_jer.roundtripid ='".$roundtripid."'";
				$params_roundtrip_check = array($fleet_trip_id);        
				$result_roundtrip_check = $adb_roundtrip_check->pquery($query_roundtrip_check, $params_roundtrip_check);
				$row_oundtrip_check = $adb_roundtrip_check->fetch_row($result_roundtrip_check);
			 $num = $adb_roundtrip_check->num_rows($result_roundtrip_check);
			 if($adb_roundtrip_check->num_rows($result_roundtrip_check)>0){

					$jer_id =   $row_oundtrip_check['jerid'];
					$jer_roundtripid =  $row_oundtrip_check['roundtripid'];

					$jer_fleetripid =  $row_oundtrip_check['fleettripid'];


					 $adb->pquery("update vtiger_jercf 
					 	SET
					   	cf_1154 ='".$b_buy_vendor_currency_net."' ,
					   	cf_1160 ='".$b_buy_local_currency_net."' ,
					   	cf_6352 ='".$b_buy_local_currency_gross."' 
					  WHERE jerid = '".$jer_id."'");

	      }else
	      {

	      	
				
		     //	print_r($jer_fuel_expenses);
	        //update internal selling 

					$current_id = $adb->getUniqueId('vtiger_crmentity');
					$jobCostingJRfid =$current_id;
					$sourceModule_JER 	= 'JER';	

					//INSERT data in jobcosting module from roundtrip

					$vtiger_crm_entity = "INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid,
							 setype, description, createdtime, modifiedtime, presence, deleted, label) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
					$params_vtiger_crm_entity= array($current_id, $job_info->get('assigned_user_id'), $job_info->get('assigned_user_id'), 'JobCosting', 'NULL', '', '', 1, 0,'SubJRER Fleet Expense');					
					$adb->pquery($vtiger_crm_entity, $params_vtiger_crm_entity);
						
					// $adb_n->setDebug(true);
					// $adb_n->setDieOnError(true);

					//INSERT data in jobcosting module from job
					$adb_e = PearDatabase::getInstance();
					$jobcosting_jer_insert_query = "INSERT INTO vtiger_jer(jerid, name, accountid,fleettripid,roundtripid) VALUES(?,?,?,?,?)";
					$params_jobcosting_jer= array($current_id, $job_info->get('cf_1198'), $job_info->get('assigned_user_id'),$roundtripid,$fleet_trip_id);					
					$adb_e->pquery($jobcosting_jer_insert_query, $params_jobcosting_jer);	

					$adb_ecf = PearDatabase::getInstance();
					$jobcosting_jercf_insert_query = "INSERT INTO vtiger_jercf(jerid, cf_1024, cf_1026, cf_1028, cf_1154, cf_1156, cf_1158, cf_1160, cf_1162, cf_1164, cf_1166, cf_1168, cf_1170, cf_1176, cf_1433, cf_1435,cf_1443,cf_1451,cf_6350,cf_6352,cf_6354,cf_6356) 
															VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?,?,?,?,?)";
					$params_jobcosting_jercf = array($current_id,0 , 0, 0,$b_buy_vendor_currency_net ,$job_file_title_currency,$jer_fuel_expenses['exchangerate'], $b_buy_local_currency_net,0 ,0, 0, 0, 0, Null, $jer_fuel_expenses['location'], $jer_fuel_expenses['department'],0 ,$jer_fuel_expenses['fuel_type'], $jer_fuel_expenses['b_vat_rate'],$b_buy_local_currency_gross, 0, 0);
					$adb_ecf->pquery($jobcosting_jercf_insert_query, $params_jobcosting_jercf);



					$adb_rel = PearDatabase::getInstance();
					$crmentityrel_insert_query = "INSERT INTO vtiger_crmentityrel(crmid, module, relcrmid, relmodule) VALUES(?, ?, ?, ?)";
					$params_crmentityrel = array($source_id, $sourceModule, $jobCostingJRfid, $sourceModule_JER);
					$adb_rel->pquery($crmentityrel_insert_query, $params_crmentityrel);
		  } 

	}      		
   
?>