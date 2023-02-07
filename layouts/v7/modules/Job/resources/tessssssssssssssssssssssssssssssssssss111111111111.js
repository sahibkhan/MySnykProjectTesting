var_dump($request->get('job_sea_loading_country_id'));
		foreach($request->get('cf_1711') as $value){
			
			if($value=='Air')
			{
			
                $job_sea_loading_country = $request->get('job_sea_loading_country');
				$request->set('cf_6936', ); 
				echo "<br>";
				echo $request->get('job_sea_loading_state');
				echo "<br>";
				echo $request->get('job_sea_loading_city');
				echo "<br>";
				echo $request->get('job_sea_loading_port_code');
				echo "<br>";
				echo "<br>";
				echo $request->get('job_sea_loading_country_id');
				echo "<br>";
				echo $request->get('job_sea_loading_city_id');
				echo "<br>";
				echo $request->get('job_sea_loading_unlocode');

	
			}else if($value=='Ocean')
			{
				 echo 'Sea Block <br>';
			}else if($value=='Rail')
			{
                echo 'Rail Block <br>';
			}else if($value=='Road')
			{
               echo 'Road Block<br>';
			}
		}
		//var_dump($request->get('cf_1711'));
		   
		
		
	    exit;