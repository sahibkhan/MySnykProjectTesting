<?php
if (in_array("Air", $request->get('cf_1711')))
		{
		echo "Match found";
		}
		else
		{
		echo "Match not found";
		}
		foreach($request->get('cf_1711') as $value)
		{
			
			if($value=='Air' && $value=='Ocean')
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
			else if($value=='Air')
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
	
			}else if($value=='Ocean')
			{
			  

			  //Orgin Air Field disable by default 
	
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
			}else if($value=='Rail' OR $value=='Road')
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
		}
	?>


	
		if($country_code=='US' OR $country_code=='CA'){
			$city_query = 'SELECT  port_city,port_city_code  from `air_sea_codes` WHERE  state_code="'.$state_code.'"  AND port_country_code="'.$country_code.'" ';		
		}else{
			$city_query = 'SELECT  port_city,port_city_code  from `air_sea_codes` WHERE port_country_code="'.$country_code.'" ';
		}