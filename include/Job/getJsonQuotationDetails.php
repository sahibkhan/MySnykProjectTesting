<?php
  require_once('../custom_connectdb.php'); // Подключение к базе данных
  require_once('../Vtiger/crm_data_arrange.php');
  
  $quotes_arr =array();
  
  if (isset($_REQUEST['record'])){
    $record = $_REQUEST['record'];
  }
  	
  if($record && $record!='undefined')
  {
	
	$quotes_info = get_json_quotes_details($record);

					// General
	
	$quotes_arr['subject'] = $quotes_info['subject'];
	
	$quotes_arr['shipper'] = $quotes_info['cf_777'];
	$quotes_arr['consignee'] = $quotes_info['cf_1611'];
	
	$quotes_arr['origin_country'] = $quotes_info['cf_1613'];
	$quotes_arr['origin_city'] = $quotes_info['cf_1615'];
	
	$quotes_arr['destination_country'] = $quotes_info['cf_1617'];
	$quotes_arr['destination_city'] = $quotes_info['cf_1619'];
	
	$quotes_arr['pickup_address'] = $quotes_info['cf_1621'];
	$quotes_arr['delivery_address'] = $quotes_info['cf_1623'];
 
	$quotes_arr['expected_date_of_loading'] = $quotes_info['cf_1625'];
	$quotes_arr['expected_date_of_delivery'] = $quotes_info['cf_1627'];
	
	$quotes_arr['etd'] = $quotes_info['cf_1629'];
	$quotes_arr['eta'] = $quotes_info['cf_1631'];
	
	$quotes_arr['vendor'] = $quotes_info['cf_1633'];
 
 
					// Cargo Details
	$quotes_arr['noofpieces'] = $quotes_info['cf_1637'];
	
	$quotes_arr['weight'] = $quotes_info['cf_1639'];
	$quotes_arr['volume'] = $quotes_info['cf_1643'];
	$quotes_arr['cargo_value'] = $quotes_info['cf_1647'];
	$quotes_arr['cargo_unit'] = $quotes_info['cf_1725'];	
	$quotes_arr['commodity'] = $quotes_info['cf_1651'];
	$quotes_arr['cargo_description'] = $quotes_info['cf_1653'];
	$quotes_arr['weight_units'] = $quotes_info['cf_1641'];
	$quotes_arr['volume_units'] = $quotes_info['cf_1645'];
	$quotes_arr['cntr_transport_types'] = $quotes_info['cf_1649'];
	$quotes_arr['mode'] = $quotes_info['cf_1709'];
	$quotes_arr['terms_of_delivery'] = $quotes_info['cf_1791'];	
	
	
	
	
	//$account = get_json_quotes_details($record);
	//$quotes_arr['account_id'] = $account['accountid'];
	//$quotes_arr['account_label'] = $account('accountname');
	
	
	$agent_is_payer = $quotes_info['cf_4027'];
	
	if($agent_is_payer=='1')
	{
		$quotes_arr['accountid'] = $quotes_info['cf_1827'];
		$quotes_arr['customer_label'] = get_account_details($quotes_info['cf_1827'],'accountname');
		
		$quotes_arr['campaignid'] ='';
		$quotes_arr['campaign_label'] ='';
		
	}
	else{
	
	$quotes_arr['accountid'] = $quotes_info['accountid'];	
	$quotes_arr['customer_label'] = get_account_details($quotes_info['accountid'],'accountname');	
 
	$quotes_arr['campaignid'] = $quotes_info['cf_1827'];
	$quotes_arr['campaign_label'] = get_account_details($quotes_info['cf_1827'],'accountname');
	}
	
	
	
	//cf_777 = Routing Info
	/* Routing Criteria for saving
	From:Jettingen-Scheppach,Germany |Mode:Road|To:Batumi,  Georgia|Inco:FOT-FOT|Wt:680.00 kgs|Vol:n/a|Dim:800 x 1200 x 2000 mm;  800 x 1200 x 1000 mm;  800 x 1200 x 2000 mm;  800 x 1200 x 1500 mm;  800 x 1200 x 200 mm |Comm: general cargo|Trans:LTL|Qty:6/pcs|Rate:980.00 euro |#From:Waddinxvee, Netherlands|Mode:Road|To:Batumi,  Georgia|Inco:FOT-FOT|Wt:50 kgs|Vol:n/a|Dim:80x120x80 cm |Comm: general cargo|Trans:LTL|Qty:9 cartons|Rate:330 euro |#
	*/
	
	/*
	$routing_info = $quotes_info['cf_777'];
	$routing_arr = explode('#' ,$routing_info);
	$routing_arr_1 = explode('|' ,@$routing_arr[0]);
	$from = explode(':', @$routing_arr_1[0]);
	

	//Job File Field Name
	//cf_1076 = Origin
	$quotes_arr['origin']  = @$from[1];	
	
	//cf_1080 = Transport mode
	$transport_mode = explode(':', @$routing_arr_1[1]);
	$quotes_arr['transport_mode']  = @$transport_mode[1];	
	
	//cf_1078 = Destination
	$to = explode(':', @$routing_arr_1[2]);
	$quotes_arr['destination']  = @$to[1];	
	
	//cf_1084 = Weight
	$weight = explode(':', @$routing_arr_1[4]);
	$quotes_arr['weight']  = @$weight[1];	
	
	//cf_1086 = Volume
	$volume = explode(':', @$routing_arr_1[5]);
	$quotes_arr['volume']  = @$volume[1];	
	
	//cf_1518 = Commodity
	$commodity = explode(':', @$routing_arr_1[7]);
	$quotes_arr['commodity']  = @$commodity[1];	
	
	
	//cf_1092 = Transport Type
	$volume = explode(':', @$routing_arr_1[8]);
	$quotes_arr['transport_type']  = @$volume[1];	
	
	//cf_1429 = QTY
	$volume = explode(':', @$routing_arr_1[9]);
	$quotes_arr['noofpieces']  = @$volume[1];	
	
	// account_id  = accountid
	$quotes_arr['customer_id'] = $quotes_info['accountid'];
	
	$customer_label = get_account_details($quotes_info['accountid'],'accountname');
	$quotes_arr['customer_label'] = $customer_label;
	
	// cf_1082 = campaignid
	$quotes_arr['campaign_id'] = $quotes_info['campaignid'];
	$quotes_arr['campaign_label'] = get_campaigns_details($quotes_info['campaignid'],'campaignname');
	*/
	
  	echo json_encode($quotes_arr);
  }		
  echo FALSE;		  
  
?>