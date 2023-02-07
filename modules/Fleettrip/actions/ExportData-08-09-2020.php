<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Fleettrip_ExportData_Action extends Vtiger_ExportData_Action {

	var $moduleCall = false;
	public function requiresPermission(\Vtiger_Request $request) {
		//$permissions = parent::requiresPermission($request);
		//$permissions[] = array('module_parameter' => 'module', 'action' => 'Export');
       // if (!empty($request->get('source_module'))) {
        ///    $permissions[] = array('module_parameter' => 'source_module', 'action' => 'Export');
       // }
		return $permissions=true;
	}

	/**
	 * Function is called by the controller
	 * @param Vtiger_Request $request
	 */
	function process(Vtiger_Request $request) {
		$this->ExportData($request);
	}

	private $moduleInstance;
	private $focus;

	/**
	 * Function exports the data based on the mode
	 * @param Vtiger_Request $request
	 */
	function ExportData(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();
		$moduleName = $request->get('source_module');

		$this->moduleInstance = Vtiger_Module_Model::getInstance($moduleName);
		$this->moduleFieldInstances = $this->moduleFieldInstances($moduleName);
		$this->focus = CRMEntity::getInstance($moduleName);

		$query = $this->getExportQuery($request);
		$result = $db->pquery($query, array());

		$redirectedModules = array('Users', 'Calendar');
		if($request->getModule() != $moduleName && in_array($moduleName, $redirectedModules) && !$this->moduleCall){
			$handlerClass = Vtiger_Loader::getComponentClassName('Action', 'ExportData', $moduleName);
			$handler = new $handlerClass();
			$handler->ExportData($request);
			return;
		}
		$translatedHeaders = $this->getHeaders();
		$entries = array();
		for ($j = 0; $j < $db->num_rows($result); $j++) {
			$entries[] = $this->sanitizeValues($db->fetchByAssoc($result, $j));
		}

			$entries_new = array();
			$Exporttype = $request->get('Exporttype');
			if($Exporttype=='fleet_trip_format')
			{
			include("include/Exchangerate/exchange_rate_class.php");
			/*
			$stats = array();
			foreach($entries as $key => $entry)
			{
				$rs_fleettrip = mysql_query("select vtiger_fleettripcf.fleettripid, vtiger_crmentity.smownerid, vtiger_fleettripcf.cf_3253 as standard_days,
											 vtiger_fleettripcf.cf_5159 as standard_indirect_cost
											 from vtiger_fleettripcf
											 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid=vtiger_fleettripcf.fleettripid
											 where vtiger_fleettripcf.cf_3283='".$entry['cf_3283']."' AND vtiger_crmentity.deleted=0 limit 1");
				$row_fleetrip = mysql_fetch_assoc($rs_fleettrip);
				$fleettripid = $row_fleetrip['fleettripid'];
				
				$rs_roundtrip = mysql_query("SELECT * from vtiger_roundtripcf
											 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_roundtripcf.roundtripid
											 INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_roundtripcf.roundtripid
											 WHERE vtiger_crmentity.deleted=0 AND
											 	   vtiger_crmentityrel.relmodule='Roundtrip' AND
												   vtiger_crmentityrel.module='Fleettrip'
												   AND vtiger_crmentityrel.crmid = '".$fleettripid."'
											");
				
				while($row_round_trip = mysql_fetch_assoc($rs_roundtrip))
				{
					$stats[] = $row_round_trip['cf_3175'];
				}
			}
			$final_stats = array_count_values($stats);
			$job_array_counter = array_diff($final_stats, array('1'));
			$new_job_array_counter_stats = array();
			*/
			foreach($entries as $key => $entry)
			{
				$rs_fleettrip = $db->pquery("select vtiger_fleettripcf.fleettripid, vtiger_crmentity.smownerid, vtiger_fleettripcf.cf_3253 as standard_days,
											 vtiger_fleettripcf.cf_5159 as standard_indirect_cost
											 from vtiger_fleettripcf
											 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid=vtiger_fleettripcf.fleettripid
											 where vtiger_fleettripcf.cf_3283='".$entry['cf_3283']."' AND vtiger_crmentity.deleted=0 limit 1");
				$row_fleetrip = $db->fetch_array($rs_fleettrip);
				$fleettripid = $row_fleetrip['fleettripid'];


				$fleet_user_info = Users_Record_Model::getInstanceById($row_fleetrip['smownerid'], 'Users');

				$trip_template_title =  Vtiger_TripTemplatesList_UIType::getDisplayValue($entry['cf_4517']);

				$rs_total_pretrip = $db->pquery("SELECT SUM(vtiger_pretripcf.cf_4309) as pretrip_total FROM vtiger_pretripcf
												 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_pretripcf.pretripid
												 INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_pretripcf.pretripid
												 WHERE vtiger_crmentity.deleted=0 AND
												 	   vtiger_crmentityrel.relmodule='PreTrip' AND
													   vtiger_crmentityrel.module='TripTemplates'
													   AND vtiger_crmentityrel.crmid = '".$entry['cf_4517']."'
												");
				$row_total_pretrip = $db->fetch_array($rs_total_pretrip);
				$total_pretrip = $row_total_pretrip['pretrip_total'];


				$rs_trip_sum = $db->pquery("SELECT sum(fuel_sum) as fuel_sum, sum(business_trip) as business_trip,
												   sum(extra_sum) as extra_sum, sum(cost_of_flt) as cost_of_flt, sum(internal_selling) as internal_selling,
												   sum(fleet_profit) as fleet_profit
												   FROM fleet_report
											WHERE fleet_trip_id='".$fleettripid."' ");
				$row_trip_sum = $db->fetch_array($rs_trip_sum);


				$rs_total_revenue = $db->pquery("SELECT SUM(vtiger_roundtripcf.cf_3173) as total_revenew_final FROM vtiger_roundtripcf
												 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_roundtripcf.roundtripid
												 INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_roundtripcf.roundtripid
												 WHERE vtiger_crmentity.deleted=0 AND
												 	   vtiger_crmentityrel.relmodule='Roundtrip' AND
													   vtiger_crmentityrel.module='Fleettrip'
													   AND vtiger_crmentityrel.crmid = '".$fleettripid."'
												");

				$row_total_revenue = $db->fetch_array($rs_total_revenue);
				$total_revenew_final = $row_total_revenue['total_revenew_final'];

				$standard_days = $row_fleetrip['standard_days'];
				if($row_fleetrip['standard_days']==0)
				{
					$standard_days = 1;
				}
				$indirect_cost =  $standard_days * $row_fleetrip['standard_indirect_cost'];
				$profit_with_indirect_cost = 0;

				$entries_new[] = array('cf_3165' => $entry['cf_3165'], 'cf_3283' => $entry['cf_3283'],'fleet_trip'=> 'Total', 'driver_info' => '', 'round_trip_date' => '','job_no' => '', 'fleet_ref_no_job_no' => '',
									   'Job Created By' => '', 'trip_created_by' => $fleet_user_info->get('first_name').' '.$fleet_user_info->get('last_name'),
									   'subject_job' => $entry['name'], 'file_title' => '', 'location' => '', 'type' => '', 'department' => '', 'customer' => '',
									   'job_status' => '', 'shipper' => '', 'consignee' => '', 'origin_country' =>$entry['cf_3237'], 'destination_country' => $entry['cf_3239'],
									   'origin_city' => $entry['cf_3241'], 'destination_city' => $entry['cf_3243'],
									   'roundtrip_origin_city' => '', 'roundtrip_destination_city' => '',
									    'pickup_address' => '', 'delivery_address' => '',
									   'expected_date_pickup' => '', 'expected_date_delivery' => '', 'ETD' => '', 'ETA' => '', 'mode' => '', 'no_of_pieces' => $entry['cf_3227'],
									   'weight' => $entry['cf_3229'], 'weight_unit' => $entry['cf_3229'], 'volume' => $entry['cf_3231'], 'volume_unit' => $entry['cf_3231'], 'waybill' => '',
									   'fleet_expected_to_date' => $entry['cf_3249'], 'fleet_expected_from_date' => $entry['cf_3251'],
									   'from_date' => $entry['cf_3245'], 'to_date' => $entry['cf_3247'],
									   'mileage' => $entry['cf_3263'], 'fuel_l' => $entry['cf_3273'], 'backload' => '',
									   'internal_selling_dollar'=> number_format($total_revenew_final , 2 ,  "." , ","),
									   'fuel_sum' => number_format($row_trip_sum['fuel_sum'] , 2 ,  "." , ","), 'business_sum'=> number_format($row_trip_sum['business_trip'] , 2 ,  "." , ","),
									   'extra_sum' => number_format($row_trip_sum['extra_sum'] , 2 ,  "." , ","), 'cost_of_flt' => number_format($row_trip_sum['cost_of_flt'] , 2 ,  "." , ","),
									   'internal_selling' => number_format($row_trip_sum['internal_selling'] , 2 ,  "." , ","), 'profit' => number_format($row_trip_sum['fleet_profit'] , 2 ,  "." , ","),
									   'indirect_cost' => number_format($indirect_cost , 2 ,  "." , ","),
									   'profit_with_indirect_cost' => number_format($profit_with_indirect_cost , 2 ,  "." , ","),
									   'trip_template' => $trip_template_title,
									   'pre_trip_budget' => number_format($total_pretrip , 2 ,  "." , ","),
									   'cf_4803' => $entry['cf_4803']);

				$job_entry_key = key($entries_new);
				$job_entry_key = count($entries_new)-1;


				$rs_roundtrip_1 = $db->pquery("SELECT * from vtiger_roundtripcf
											 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_roundtripcf.roundtripid
											 INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_roundtripcf.roundtripid
											 WHERE vtiger_crmentity.deleted=0 AND
											 	   vtiger_crmentityrel.relmodule='Roundtrip' AND
												   vtiger_crmentityrel.module='Fleettrip'
												   AND vtiger_crmentityrel.crmid = '".$fleettripid."'
											");
				$stats= array();
				while($row_round_trip_1 = $db->fetch_array($rs_roundtrip_1))
				{
					$stats[] = $row_round_trip_1['cf_3175'];
				}
				
				$final_stats = array_count_values($stats);
				$job_array_counter = array_diff($final_stats, array('1'));
				$new_job_array_counter_stats = array();
				
				$rs_roundtrip = $db->pquery("SELECT * from vtiger_roundtripcf
											 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_roundtripcf.roundtripid
											 INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_roundtripcf.roundtripid
											 WHERE vtiger_crmentity.deleted=0 AND
											 	   vtiger_crmentityrel.relmodule='Roundtrip' AND
												   vtiger_crmentityrel.module='Fleettrip'
												   AND vtiger_crmentityrel.crmid = '".$fleettripid."'
											");
											
				$sum_internal_selling = 0;
				$sum_profit = 0;
				$sum_internal_selling_dollar = 0;
				while($row_round_trip = $db->fetch_array($rs_roundtrip))
				{
					$rs_job_info = $db->pquery("SELECT vtiger_jobcf.*, vtiger_crmentity.smownerid, vtiger_crmentity.label  FROM vtiger_jobcf
												INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid=vtiger_jobcf.jobid
												where vtiger_jobcf.jobid='".$row_round_trip['cf_3175']."' limit 1");
					$row_job_info = $db->fetch_array($rs_job_info);

					$created_time_internal_selling = $row_round_trip['createdtime'];
					$timestamp = strtotime($created_time_internal_selling);
					//$selling_date = date('Y-m-d', $timestamp);

					$round_trip_invoice_date = $row_round_trip['cf_5766'];

					$selling_date = date('Y-m-d', $timestamp);
					if($str_round_trip_invoice_date > strtotime(date('Y-m-d')))
					{
						$selling_date = date('Y-m-d', $timestamp);
					}
					else{
						$selling_date = $round_trip_invoice_date;
					}

					//$sum_internal_selling_dollar += $row_round_trip['cf_3173'];

					$internal_selling_final = $row_round_trip['cf_3173'];

					$b_exchange_rate = $b_exchange_rate = currency_rate_convert('KZT', 'USD',  1, $selling_date);

					$internal_selling_per_job = $internal_selling_final*$b_exchange_rate;




					$fuel_breakdown_b_buy_local_currency_net = 0;
					$daily_breakdown_b_buy_local_currency_net = 0;
					$extra_breakdown_b_buy_local_currency_net = 0;
					$cost_of_flt = 0;
					if($total_revenew_final>0)
					{
						$rs_fleet_report = $db->pquery("select * from fleet_report where fleet_trip_id='".$fleettripid."'
																					 AND job_trip_id='".$row_round_trip['cf_3175']."'
																					 AND round_trip_id='".$row_round_trip['roundtripid']."' ");
						if($db->num_rows($rs_fleet_report)>0)
						{
							$row_fleet_report = $db->fetch_array($rs_fleet_report);
							$fuel_breakdown_b_buy_local_currency_net = $row_fleet_report['fuel_sum'];
							$daily_breakdown_b_buy_local_currency_net = $row_fleet_report['business_trip'];
							$extra_breakdown_b_buy_local_currency_net = $row_fleet_report['extra_sum'];
							$cost_of_flt = $row_fleet_report['cost_of_flt'];
						}

					}

					$profit = $internal_selling_per_job - $cost_of_flt;

					$file_title = Vtiger_CompanyList_UIType::getDisplayValue($row_job_info['cf_1186']);
					$location =  Vtiger_LocationList_UIType::getDisplayValue($row_job_info['cf_1188']);
					$department =  Vtiger_DepartmentList_UIType::getDisplayValue($row_job_info['cf_1190']);

					$account_details = '';
					$rs_result = $db->pquery("select crmid from vtiger_crmentity where deleted=0 AND crmid='".$row_job_info['cf_1441']."'");

					if($db->num_rows($rs_result)!=0)
					{
					$account_info = Vtiger_Record_Model::getInstanceById($row_job_info['cf_1441'], 'Accounts');
					$account_details = @$account_info->get('accountname');
					}


					$job_user_info = Users_Record_Model::getInstanceById($row_job_info['smownerid'], 'Users');
					
					$counter = $job_array_counter[$row_round_trip['cf_3175']];
					$final_prefix='';
					if(isset($counter))
					{
						$prefix = $job_array_counter[$row_round_trip['cf_3175']];
						$final_prefix = '-'.$prefix;
						$job_array_counter[$row_round_trip['cf_3175']] = $prefix-1;
					}

					$entries_new[] = array('cf_3165' => $entry['cf_3165'], 'cf_3283' => $entry['cf_3283'], 'fleet_trip'=> 'Sub Total','driver_info' => $entry['cf_3167'],
										   'round_trip_date' => $row_round_trip['cf_5766'], 'job_no' => $row_job_info['cf_1198'],
										   'fleet_ref_no_job_no' => $entry['cf_3283'].'::'.$row_job_info['cf_1198'].$final_prefix,
										   'job_created_by' => $job_user_info->get('first_name').' '.$job_user_info->get('last_name'),
										   'trip_created_by' => '','subject_job' => $row_job_info['label'], 'file_title' => $file_title, 'location' => $location,
										   'type' => $row_job_info['cf_1200'], 'department' => $department, 'customer' => @$account_details,
										   'job_status' => $row_job_info['cf_2197'], 'shipper' => $row_job_info['cf_1072'],
										   'consignee'  => $row_job_info['cf_1074'], 'origin_country' => $row_job_info['cf_1504'],
										   'destination_country' => $row_job_info['cf_1506'], 'origin_city' => $row_job_info['cf_1508'],
										   'destination_city' => $row_job_info['cf_1510'],
										   'roundtrip_origin_city' => $row_round_trip['cf_3169'], 'roundtrip_destination_city' => $row_round_trip['cf_3171'],
										   'pickup_address' => $row_job_info['cf_1512'],
										   'delivery_address' => $row_job_info['cf_1514'], 'expected_date_pickup' => $row_job_info['cf_1516'],
										   'expected_date_delivery' => $row_job_info['cf_1583'], 'ETD' => $row_job_info['cf_1589'], 'ETA' => $row_job_info['cf_1591'],
										   'mode' => $row_job_info['cf_1711'], 'no_of_pieces' => $row_job_info['cf_1429'], 'weight' => $row_job_info['cf_1084'],
										   'weight_unit' => $row_job_info['cf_1520'], 'volume' => $row_job_info['cf_1086'], 'volume_unit' => $row_job_info['cf_1522'],
										   'waybill' => $row_job_info['cf_1096'],
										   'fleet_expected_to_date' => '', 'fleet_expected_from_date' => '', 'from_date' => '', 'to_date' => '',
										   'mileage' => '', 'fuel_l' => '', 'backload' => ($row_round_trip['cf_3279']==1 ? 'Yes' : 'No' ),
										   'internal_selling_dollar' => number_format($internal_selling_final , 2 ,  "." , ","),
										   'fuel_sum'    =>  number_format($fuel_breakdown_b_buy_local_currency_net , 2 ,  "." , "," ),
										   'business_sum'=>  number_format($daily_breakdown_b_buy_local_currency_net , 2 ,  "." , ","),
										   'extra_sum'   =>  number_format($extra_breakdown_b_buy_local_currency_net , 2 ,  "." , ","),
										   'cost_of_flt' =>  number_format($cost_of_flt , 2 ,  "." , ","),
										   'internal_selling' => number_format($internal_selling_per_job , 2 ,  "." , ","),
										   'profit' => number_format($profit , 2 ,  "." , ","),
										   'indirect_cost' =>'',
										   'profit_with_indirect_cost' => '',
										   'trip_template' => '',
										   'pre_trip_budget' => '',
										   'cf_4803' => ''
										   );

					$sum_internal_selling +=$internal_selling_per_job;
					$sum_profit +=$profit;

					//$sum_internal_selling_dollar +=$internal_selling_final;

				}
				//echo "<pre>";
				//print_r($entries_new);

				//echo $job_entry_key;
				//exit;

				//$entries_new[$job_entry_key]['internal_selling_dollar'] = $sum_internal_selling_dollar;
				$entries_new[$job_entry_key]['internal_selling'] = number_format($sum_internal_selling , 2 ,  "." , ",");
				$entries_new[$job_entry_key]['profit'] = number_format($sum_profit , 2 ,  "." , ",");

				$profit_with_indirect_cost = $sum_internal_selling - ($row_trip_sum['cost_of_flt'] + $indirect_cost);
				$entries_new[$job_entry_key]['profit_with_indirect_cost'] = number_format($profit_with_indirect_cost , 2 ,  "." , ",");
				//echo "<pre>";
				//print_r($entries_new);

			}

			$translatedHeaders = array('Truck', 'Fleet Ref No', 'Fleet Trip', 'Driver', 'Round Trip Date', 'Job List', 'Fleet Ref No / Job No',
									   'Job Created By','Trip Created By','Subject', 'File Title', 'Location', 'Type', 'Department', 'Customer','Job Status','Shipper', 'Consignee',
									   'Origin Country', 'Destination Country', 'Origin City' , 'Destination City', 'Round Trip Origin City' , 'Round Trip Destination City',
									    'Job Pickup Address', 'Job Delivery Address',
									   'Job Expected Date of Pickup', 'Job Expected Date of Delivery', 'Job ETD', 'Job ETD', 'Mode', 'No of Pieces', 'Weight', 'Weight Unit',
									   'Volume', 'Volume Unit', 'Waybill',
									   'Trip Expected From Date', 'Trip Expected To Date', 'Trip From Date', 'Trip To Date','Mileage(km)', 'Fuel(l)', 'Back Load',
									   'Internal Selling $', 'Fuel Sum', 'Business Trip', 'Extra Sum', 'Costs of FLT', 'Internal Selling KZT', 'Profit',
									   'Indirect Cost', 'Profit With Indirect Cost', 'Trip Template', 'Pre Trip Budget', 'Fleet Trip Status');



			$this->getReportXLS($request, $translatedHeaders, $entries_new);
			}
			elseif($Exporttype=='fuel_norm')
			{
				$entries_new = array();
				foreach($entries as $key => $entry)
				{
					if(empty($entry['cf_3283'])) { continue;}
					$rs_fleettrip = $db->pquery("select vtiger_fleettripcf.fleettripid, vtiger_crmentity.smownerid
											 from vtiger_fleettripcf
											 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid=vtiger_fleettripcf.fleettripid
											 where vtiger_fleettripcf.cf_3283='".$entry['cf_3283']."' AND vtiger_crmentity.deleted=0 limit 1");
					$row_fleetrip = $db->fetch_array($rs_fleettrip);
					$fleettripid = $row_fleetrip['fleettripid'];


					$rs_triplistfuel = $db->pquery("SELECT SUM(vtiger_triplistfuelcf.cf_5029) as actual_fuel_consumtion from vtiger_triplistfuelcf
											 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_triplistfuelcf.triplistfuelid
											 INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_triplistfuelcf.triplistfuelid
											 WHERE vtiger_crmentity.deleted=0 AND
											 	   vtiger_crmentityrel.relmodule='TripListFuel' AND
												   vtiger_crmentityrel.module='Fleettrip'
												   AND vtiger_crmentityrel.crmid = '".$fleettripid."'
											");
					$row_triplist_fuel = $db->fetch_array($rs_triplistfuel);
					$actual_fuel_consumption = $row_triplist_fuel['actual_fuel_consumtion'];


					$rs_posttrip = $db->pquery("SELECT SUM(vtiger_posttripcf.cf_4321) as norm_fuel from vtiger_posttripcf
											 INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_posttripcf.posttripid
											 INNER JOIN vtiger_crmentityrel ON vtiger_crmentityrel.relcrmid = vtiger_posttripcf.posttripid
											 WHERE vtiger_crmentity.deleted=0 AND
											 	   vtiger_crmentityrel.relmodule='PostTrip' AND
												   vtiger_crmentityrel.module='Fleettrip'
												   AND vtiger_crmentityrel.crmid = '".$fleettripid."'
												   AND vtiger_posttripcf.cf_4315 in('549397','548700') AND vtiger_posttripcf.cf_4333='Fuel'
											");
					$row_post_trip = $db->fetch_array($rs_posttrip);
					$norm_fuel = $row_post_trip['norm_fuel'];

					$difference = $actual_fuel_consumption - $norm_fuel;
					$percentage_difference = 0;
					if($actual_fuel_consumption > 0){
					$percentage_difference = (($norm_fuel/$actual_fuel_consumption)-1)*100;
					}

					$entries_new[] = array('cf_3283' => $entry['cf_3283'], 'actual_fuel_consumtion' => number_format($actual_fuel_consumption , 2 ,  "." , ","),
										   'norm_fuel' => number_format($norm_fuel , 2 ,  "." , ","), 'difference' => number_format($difference , 2 ,  "." , ","),
										   'percentage_difference' => $percentage_difference,
										   'comments1' => ''
										   );

				}

				$translatedHeaders = array('Fleet Trip #','Fuel', 'Actual (lt)', 'Norm (lt)', 'Difference (Actual - Norm)', 'Percentage Difference*');

				$this->getReportXLS_XLSX($request, $translatedHeaders, $entries_new, $moduleName);
			}
			else{

				$this->output($request, $translatedHeaders, $entries);
			}
	}

	function getReportXLS($request, $headers, $entries) {

		$rootDirectory = vglobal('root_directory');
		$tmpDir = vglobal('tmp_dir');

		$tempFileName = tempnam($rootDirectory.$tmpDir, 'xls');

		$moduleName = $request->get('source_module');

		//$fileName = $this->getName().'.xls';
		$fileName = $moduleName.'.xls';



		$this->writeReportToExcelFile($tempFileName, $headers, $entries, false);

		if(isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
			header('Pragma: public');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		}

		header('Content-Type: application/x-msexcel');
		header('Content-Length: '.@filesize($tempFileName));
		header('Content-disposition: attachment; filename="'.$fileName.'"');

		$fp = fopen($tempFileName, 'rb');
		fpassthru($fp);
		//unlink($tempFileName);

	}

	function writeReportToExcelFile($fileName, $headers, $entries, $filterlist='') {
		global $currentModule, $current_language;
		$mod_strings = return_module_language($current_language, $currentModule);

		require_once("libraries/PHPExcel/PHPExcel.php");

		$workbook = new PHPExcel();
		$worksheet = $workbook->setActiveSheetIndex(0);

		$header_styles = array(
			//'fill' => array( 'type' => PHPExcel_Style_Fill::FILL_NONE, 'color' => array('rgb'=>'E1E0F7') ),
			'fill' => array( 'type' => PHPExcel_Style_Fill::FILL_NONE ),

			//'font' => array( 'bold' => true )
		);


		if(isset($headers)) {
			$count = 0;
			$rowcount = 1;

			$arrayFirstRowValues = $headers;
			//array_pop($arrayFirstRowValues);

			// removed action link in details
			foreach($arrayFirstRowValues as $key=>$value) {
				$worksheet->setCellValueExplicitByColumnAndRow($count, $rowcount, $key, true);
				$worksheet->getStyleByColumnAndRow($count, $rowcount)->applyFromArray($header_styles);

				// NOTE Performance overhead: http://stackoverflow.com/questions/9965476/phpexcel-column-size-issues
				//$worksheet->getColumnDimensionByColumn($count)->setAutoSize(true);

				$count = $count + 1;
			}

			$rowcount++;

			$count = 0;
			//array_pop($array_value);	// removed action link in details
			foreach($headers as $hdr => $value) {
				$value = decode_html($value);
				// TODO Determine data-type based on field-type.
				// String type helps having numbers prefixed with 0 intact.
				$worksheet->setCellValueExplicitByColumnAndRow($count, $rowcount, $value, PHPExcel_Cell_DataType::TYPE_STRING);
				$count = $count + 1;
			}
			//$rowcount++;

			$rowcount++;
			foreach($entries as $key => $array_value) {
				$count = 0;
				//array_pop($array_value);	// removed action link in details
				foreach($array_value as $hdr => $value_excel) {
					if(is_array($value_excel))
					{
						$value = '';
					}
					else{
					$value = decode_html($value_excel);
					}
					// TODO Determine data-type based on field-type.
					// String type helps having numbers prefixed with 0 intact.
					$worksheet->setCellValueExplicitByColumnAndRow($count, $rowcount, $value, PHPExcel_Cell_DataType::TYPE_STRING);
					$count = $count + 1;
				}
				$rowcount++;
			}


		}


		$workbookWriter = PHPExcel_IOFactory::createWriter($workbook, 'Excel5');
		ob_end_clean();
		$workbookWriter->save($fileName);

	}

	
	function getReportXLS_XLSX($request, $headers, $entries, $moduleName, $format = 'kerry_format') {

		$rootDirectory = vglobal('root_directory');
		$tmpDir = vglobal('tmp_dir');

		$tempFileName = tempnam($rootDirectory.$tmpDir, 'xlsx');

		$moduleName = $request->get('source_module');

		//$fileName = $this->getName().'.xls';
		$fileName = $moduleName.'.xlsx';
		$Exporttype = $request->get('Exporttype');

		if($Exporttype=='fuel_norm')
		{
		$fileName = 'fuelnorm.xlsx';
		}



		if($moduleName=='Fleettrip')
		{
			$this->writeReportToExcelFile_FuelNorm($tempFileName, $headers, $entries, false);
		}
		

		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="'.$fileName.'"');
		header('Cache-Control: max-age=0');
		/*
		if(isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
			header('Pragma: public');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		}

		header('Content-Type: application/x-msexcel');
		header('Content-Length: '.@filesize($tempFileName));
		header('Content-disposition: attachment; filename="'.$fileName.'"');
		*/
		$fp = fopen($tempFileName, 'rb');
		fpassthru($fp);
		//unlink($tempFileName);

	}

	
	function writeReportToExcelFile_FuelNorm($fileName, $headers, $entries, $filterlist='') {
		global $currentModule, $current_language;
		$mod_strings = return_module_language($current_language, $currentModule);


		require_once 'libraries/PHPExcel/PHPExcel.php';

		//echo date('H:i:s') . " Create new PHPExcel object\n";
		$objPHPExcel = new PHPExcel();

		// Set properties
		//echo date('H:i:s') . " Set properties\n";
		$current_user = Users_Record_Model::getCurrentUserModel();

		$full_name = $current_user->get('first_name')." ".$current_user->get('last_name');
		$objPHPExcel->getProperties()->setCreator($full_name)
									 ->setLastModifiedBy($full_name)
									 ->setTitle($fileName)
									 ->setSubject($fileName)
									 ->setDescription($fileName)
									 ->setKeywords($fileName)
									 ->setCategory($fileName);



		//echo date('H:i:s') . " Add data\n";
		$objPHPExcel->setActiveSheetIndex(0);

		$sharedStyle1 = new PHPExcel_Style();
		$sharedStyle2 = new PHPExcel_Style();

		$sharedStyle1->applyFromArray(
			array('fill' 	=> array(
										'type'		=> PHPExcel_Style_Fill::FILL_SOLID,
										'color'		=> array('argb' => 'FFCCFFCC')
									),
				  'borders' => array( 'allborders' =>array(
										  'style' => PHPExcel_Style_Border::BORDER_THIN
										//'bottom'	=> array('style' => PHPExcel_Style_Border::BORDER_THIN),
										//'right'		=> array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
									)),
					'alignment' => array(
							            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
										'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
        							)
				 ));

		$sharedStyle2->applyFromArray(
			array('fill' 	=> array(
										'type'		=> PHPExcel_Style_Fill::FILL_SOLID,
										'color'		=> array('argb' => 'ed7024')
									),
				  'borders' => array( 'allborders' =>array(
										  'style' => PHPExcel_Style_Border::BORDER_THIN
										//'bottom'	=> array('style' => PHPExcel_Style_Border::BORDER_THIN),
										//'right'		=> array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
									)),
					'alignment' => array(
							            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
										'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
        							)
				 ));

		$objPHPExcel->getActiveSheet()->setSharedStyle($sharedStyle1, "A1:J1");
		$objPHPExcel->getActiveSheet()->setSharedStyle($sharedStyle2, "A2:J2");


		$objPHPExcel->getActiveSheet()->mergeCells('A1:B1');
		$objPHPExcel->getActiveSheet()->setCellValue('B1', "Fleet Trip #");
		$objPHPExcel->getActiveSheet()->mergeCells('C1:F1');
		$objPHPExcel->getActiveSheet()->setCellValue('C1', "Fuel");
		$objPHPExcel->getActiveSheet()->mergeCells('G1:J1');
		$objPHPExcel->getActiveSheet()->setCellValue('G1', "Comments");


		$fuel_norm_headers = array('#', 'Fleet Trip #', 'Actual (lt)', 'Norm (lt)', 'Difference (Actual - Norm)', 'Percentage Difference*', '');
  		//$objPHPExcel->getActiveSheet()->fromArray($wis_headers, null, 'A5');
		$objPHPExcel->getActiveSheet()->fromArray($fuel_norm_headers, null, 'A2');
		$objPHPExcel->getActiveSheet()->mergeCells('G2:J2');
		$objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(25);


		//$objPHPExcel->getActiveSheet()->getRowDimension('6')->setRowHeight(60);
		//$objPHPExcel->getActiveSheet()->getStyle('A2:J2')->getAlignment()->setWrapText(true);
		//$objPHPExcel->getActiveSheet()->getStyle('A6:AS6')->getAlignment()->setWrapText(true);



		// Freeze panes
		//echo date('H:i:s') . " Freeze panes\n";
		//$objPHPExcel->getActiveSheet()->freezePane('A2');
		$objPHPExcel->getActiveSheet()->freezePane('A3');


		// Rows to repeat at top
		//echo date('H:i:s') . " Rows to repeat at top\n";
		$objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);

		$entries_new = array();
		foreach($entries as $key => $entry)
		{
			$key++;

			$entries_new[] = array('key' => $key, 'cf_3283' => $entry['cf_3283'], 'actual_fuel_consumtion' => $entry['actual_fuel_consumtion'],
								   'norm_fuel' => $entry['norm_fuel'], 'difference' => $entry['difference'],
								   'percentage_difference'=> $entry['percentage_difference']."%",
								   'comments1' => ''
								   );
			$cell = $key+2;
			$objPHPExcel->getActiveSheet()->mergeCells('G'.$cell.':J'.$cell.'');
		}

		// Add data
		//$entries = array_map("html_entity_decode",$entries);
		$objPHPExcel->getActiveSheet()->fromArray($entries_new, null, 'A3');
		//echo date('H:i:s') . " Set autofilter\n";
		//$objPHPExcel->getActiveSheet()->setAutoFilter('A6:AS6');
		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex(0);


		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		//$objWriter->setUseBOM(true);
		ob_end_clean();
		$objWriter->save($fileName);


	}

	public function getHeaders() {
		$headers = array();
		//Query generator set this when generating the query
		if(!empty($this->accessibleFields)) {
			$accessiblePresenceValue = array(0,2);
			foreach($this->accessibleFields as $fieldName) {
				$fieldModel = $this->moduleFieldInstances[$fieldName];
				// Check added as querygenerator is not checking this for admin users
				$presence = $fieldModel->get('presence');
				if(in_array($presence, $accessiblePresenceValue) && $fieldModel->get('displaytype') != '6') {
					$headers[] = $fieldModel->get('label');
				}
			}
		} else {
			foreach($this->moduleFieldInstances as $field) {
				$headers[] = $field->get('label');
			}
		}

		$translatedHeaders = array();
		foreach($headers as $header) {
			$translatedHeaders[] = vtranslate(html_entity_decode($header, ENT_QUOTES), $this->moduleInstance->getName());
		}

		$translatedHeaders = array_map('decode_html', $translatedHeaders);
		return $translatedHeaders;
	}

	function getAdditionalQueryModules(){
		return array_merge(getInventoryModules(), array('Products', 'Services', 'PriceBooks'));
	}

	/**
	 * Function that generates Export Query based on the mode
	 * @param Vtiger_Request $request
	 * @return <String> export query
	 */
	function getExportQuery(Vtiger_Request $request) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$mode = $request->getMode();
		$cvId = $request->get('viewname');
		$moduleName = $request->get('source_module');

		$queryGenerator = new EnhancedQueryGenerator($moduleName, $currentUser);
		$queryGenerator->initForCustomViewById($cvId);
		$fieldInstances = $this->moduleFieldInstances;

		$orderBy = $request->get('orderby');
		$orderByFieldModel = $fieldInstances[$orderBy];
		$sortOrder = $request->get('sortorder');

		if ($mode !== 'ExportAllData') {
			$operator = $request->get('operator');
			$searchKey = $request->get('search_key');
			$searchValue = $request->get('search_value');

			$tagParams = $request->get('tag_params');
			if (!$tagParams) {
				$tagParams = array();
			}

			$searchParams = $request->get('search_params');
			if (!$searchParams) {
				$searchParams = array();
			}

			$glue = '';
			if($searchParams && count($queryGenerator->getWhereFields())) {
				$glue = QueryGenerator::$AND;
			}
			$searchParams = array_merge($searchParams, $tagParams);
			$searchParams = Vtiger_Util_Helper::transferListSearchParamsToFilterCondition($searchParams, $this->moduleInstance);
			$queryGenerator->parseAdvFilterList($searchParams, $glue);

			if($searchKey) {
				$queryGenerator->addUserSearchConditions(array('search_field' => $searchKey, 'search_text' => $searchValue, 'operator' => $operator));
			}

			if ($orderBy && $orderByFieldModel) {
				if ($orderByFieldModel->getFieldDataType() == Vtiger_Field_Model::REFERENCE_TYPE || $orderByFieldModel->getFieldDataType() == Vtiger_Field_Model::OWNER_TYPE) {
					$queryGenerator->addWhereField($orderBy);
				}
			}
		}

		/**
		 *  For Documents if we select any document folder and mass deleted it should delete documents related to that 
		 *  particular folder only
		 */
		if($moduleName == 'Documents'){
			$folderValue = $request->get('folder_value');
			if(!empty($folderValue)){
				 $queryGenerator->addCondition($request->get('folder_id'),$folderValue,'e');
			}
		}

		$accessiblePresenceValue = array(0,2);
		foreach($fieldInstances as $field) {
			// Check added as querygenerator is not checking this for admin users
			$presence = $field->get('presence');
			if(in_array($presence, $accessiblePresenceValue) && $field->get('displaytype') != '6') {
				$fields[] = $field->getName();
			}
		}
		$queryGenerator->setFields($fields);
		$query = $queryGenerator->getQuery();

		$additionalModules = $this->getAdditionalQueryModules();
		if(in_array($moduleName, $additionalModules)) {
			$query = $this->moduleInstance->getExportQuery($this->focus, $query);
		}

		$this->accessibleFields = $queryGenerator->getFields();

		switch($mode) {
			case 'ExportAllData'	:	if ($orderBy && $orderByFieldModel) {
											$query .= ' ORDER BY '.$queryGenerator->getOrderByColumn($orderBy).' '.$sortOrder;
										}
										break;

			case 'ExportCurrentPage' :	$pagingModel = new Vtiger_Paging_Model();
										$limit = $pagingModel->getPageLimit();

										$currentPage = $request->get('page');
										if(empty($currentPage)) $currentPage = 1;

										$currentPageStart = ($currentPage - 1) * $limit;
										if ($currentPageStart < 0) $currentPageStart = 0;

										if ($orderBy && $orderByFieldModel) {
											$query .= ' ORDER BY '.$queryGenerator->getOrderByColumn($orderBy).' '.$sortOrder;
										}
										$query .= ' LIMIT '.$currentPageStart.','.$limit;
										break;

			case 'ExportSelectedRecords' :	$idList = $this->getRecordsListFromRequest($request);
											$baseTable = $this->moduleInstance->get('basetable');
											$baseTableColumnId = $this->moduleInstance->get('basetableid');
											if(!empty($idList)) {
												if(!empty($baseTable) && !empty($baseTableColumnId)) {
													$idList = implode(',' , $idList);
													$query .= ' AND '.$baseTable.'.'.$baseTableColumnId.' IN ('.$idList.')';
												}
											} else {
												$query .= ' AND '.$baseTable.'.'.$baseTableColumnId.' NOT IN ('.implode(',',$request->get('excluded_ids')).')';
											}

											if ($orderBy && $orderByFieldModel) {
												$query .= ' ORDER BY '.$queryGenerator->getOrderByColumn($orderBy).' '.$sortOrder;
											}
											break;


			default :	break;
		}
		return $query;
	}

	/**
	 * Function returns the export type - This can be extended to support different file exports
	 * @param Vtiger_Request $request
	 * @return <String>
	 */
	function getExportContentType(Vtiger_Request $request) {
		$type = $request->get('export_type');
		if(empty($type)) {
			return 'text/csv';
		}
	}

	/**
	 * Function that create the exported file
	 * @param Vtiger_Request $request
	 * @param <Array> $headers - output file header
	 * @param <Array> $entries - outfput file data
	 */
	function output($request, $headers, $entries) {
		$moduleName = $request->get('source_module');
		$fileName = str_replace(' ','_',decode_html(vtranslate($moduleName, $moduleName)));
		// for content disposition header comma should not be there in filename 
		$fileName = str_replace(',', '_', $fileName);
		$exportType = $this->getExportContentType($request);

		header("Content-Disposition:attachment;filename=$fileName.csv");
		header("Content-Type:$exportType;charset=UTF-8");
		header("Expires: Mon, 31 Dec 2000 00:00:00 GMT" );
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
		header("Cache-Control: post-check=0, pre-check=0", false );

		$header = implode("\", \"", $headers);
		$header = "\"" .$header;
		$header .= "\"\r\n";
		echo $header;

		foreach($entries as $row) {
			foreach ($row as $key => $value) {
				/* To support double quotations in CSV format
				 * To review: http://creativyst.com/Doc/Articles/CSV/CSV01.htm#EmbedBRs
				 */
				$row[$key] = str_replace('"', '""', $value);
			}
			$line = implode("\",\"",$row);
			$line = "\"" .$line;
			$line .= "\"\r\n";
			echo $line;
		}
	}

	private $picklistValues;
	private $fieldArray;
	private $fieldDataTypeCache = array();
	/**
	 * this function takes in an array of values for an user and sanitizes it for export
	 * @param array $arr - the array of values
	 */
	function sanitizeValues($arr){
		$db = PearDatabase::getInstance();
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$roleid = $currentUser->get('roleid');
		if(empty ($this->fieldArray)){
			$this->fieldArray = $this->moduleFieldInstances;
			foreach($this->fieldArray as $fieldName => $fieldObj){
				//In database we have same column name in two tables. - inventory modules only
				if($fieldObj->get('table') == 'vtiger_inventoryproductrel' && ($fieldName == 'discount_amount' || $fieldName == 'discount_percent')){
					$fieldName = 'item_'.$fieldName;
					$this->fieldArray[$fieldName] = $fieldObj;
				} else {
					$columnName = $fieldObj->get('column');
					$this->fieldArray[$columnName] = $fieldObj;
				}
			}
		}
		$moduleName = $this->moduleInstance->getName();
		foreach($arr as $fieldName=>&$value){
			if(isset($this->fieldArray[$fieldName])){
				$fieldInfo = $this->fieldArray[$fieldName];
			}else {
				unset($arr[$fieldName]);
				continue;
			}
			//Track if the value had quotes at beginning
			$beginsWithDoubleQuote = strpos($value, '"') === 0;
			$endsWithDoubleQuote = substr($value,-1) === '"'?1:0;

			$value = trim($value,"\"");
			$uitype = $fieldInfo->get('uitype');
			$fieldname = $fieldInfo->get('name');

			if(!$this->fieldDataTypeCache[$fieldName]) {
				$this->fieldDataTypeCache[$fieldName] = $fieldInfo->getFieldDataType();
			}
			$type = $this->fieldDataTypeCache[$fieldName];

			//Restore double quote now.
			if ($beginsWithDoubleQuote) $value = "\"{$value}";
			if($endsWithDoubleQuote) $value = "{$value}\"";
			if($fieldname != 'hdnTaxType' && ($uitype == 15 || $uitype == 16 || $uitype == 33)){
				if(empty($this->picklistValues[$fieldname])){
					$this->picklistValues[$fieldname] = $this->fieldArray[$fieldname]->getPicklistValues();
				}
				// If the value being exported is accessible to current user
				// or the picklist is multiselect type.
				if($uitype == 33 || $uitype == 16 || array_key_exists($value,$this->picklistValues[$fieldname])){
					// NOTE: multipicklist (uitype=33) values will be concatenated with |# delim
					$value = trim($value);
				} else {
					$value = '';
				}
			} elseif($uitype == 52 || $type == 'owner') {
				$value = Vtiger_Util_Helper::getOwnerName($value);
			}elseif($type == 'reference'){
				$value = trim($value);
				if(!empty($value)) {
					$parent_module = getSalesEntityType($value);
					$displayValueArray = getEntityName($parent_module, $value);
					if(!empty($displayValueArray)){
						foreach($displayValueArray as $k=>$v){
							$displayValue = $v;
						}
					}
					if(!empty($parent_module) && !empty($displayValue)){
						$value = $parent_module."::::".$displayValue;
					}else{
						$value = "";
					}
				} else {
					$value = '';
				}
			} elseif($uitype == 72 || $uitype == 71) {
                $value = CurrencyField::convertToUserFormat($value, null, true, true);
			} elseif($uitype == 7 && $fieldInfo->get('typeofdata') == 'N~O' || $uitype == 9){
				$value = decimalFormat($value);
			} elseif($type == 'date') {
				if ($value && $value != '0000-00-00') {
					$value = DateTimeField::convertToUserFormat($value);
				}
			} elseif($type == 'datetime') {
				if ($moduleName == 'Calendar' && in_array($fieldName, array('date_start', 'due_date'))) {
					$timeField = 'time_start';
					if ($fieldName === 'due_date') {
						$timeField = 'time_end';
					}
					$value = $value.' '.$arr[$timeField];
				}
				if (trim($value) && $value != '0000-00-00 00:00:00') {
					$value = Vtiger_Datetime_UIType::getDisplayDateTimeValue($value);
				}
			}
			if($moduleName == 'Documents' && $fieldname == 'description'){
				$value = strip_tags($value);
				$value = str_replace('&nbsp;','',$value);
				array_push($new_arr,$value);
			}
		}
		return $arr;
	}

	public function moduleFieldInstances($moduleName) {
		return $this->moduleInstance->getFields();
	}
}