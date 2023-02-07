<?php
//	ini_set('display_errors','on'); version_compare(PHP_VERSION, '5.5.0') <= 0 ? error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED) : error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);   // DEBUGGING

	include ('../RateCalculator/ratecalculatorfunc.php');
	//var_dump($_POST);
	$db = new query();
//General Origin States select on the base General Origin Country only for USA and Canada 
   if(!empty($_POST["country_states"])) {
   	
	   $sql = "select DISTINCT state_code,state_name FROM air_sea_codes WHERE port_country_code='".$_POST["country_states"]."'";
	   $res = $db->getData($sql);
	   $json_data = json_encode($res);?>

       <option value="">Select An Option </option>
       <?php 
	   foreach ($res as $states) 
	   {?>
		 <option value="<?php echo $states['state_code']; ?>"><?php echo $states["state_name"];?></option>
		<?php  
	   } 

   }

//select General Origin Cities on the base of General Origin Country instead of USA and Canada 
	//var_dump($results);
   if(!empty($_POST["general_country_id"])) {
   	
	  $sql = "select DISTINCT port_city_code,port_city From air_sea_codes WHERE port_country_code='".$_POST["general_country_id"]."'";
	 
	    $res = $db->getData($sql);
	    $json_data = json_encode($res);
	    //var_dump($json_data);?>
	    <option   value="">Select An Option</option>
	    <?php
	    foreach ($res as $cities) {?>
				<option value="<?php echo $cities["port_city_code"];?>"><?php echo $cities["port_city"]; ?></option>
		   <?php
	        } 

    }if(!empty($_POST["country_id"])) {
   	
	    $sql = "select DISTINCT port_city_code,port_city From air_sea_codes WHERE port_unlocode in (select air_unlocode from air_codes) and port_country_code='".$_POST["country_id"]."'";
	  
	    $res = $db->getData($sql);
	    $json_data = json_encode($res);
	    //var_dump($json_data);?>
	    <option   value="">Select An Option</option>
	    <?php
	    foreach ($res as $cities) {?>
				<option value="<?php echo $cities["port_city_code"];?>"><?php echo $cities["port_city"]; ?></option>
		   <?php
	        } 

    }
    if(!empty($_POST["sea_loading_country_id"])){

   	   $sql = "select DISTINCT port_city_code,port_city From sea_codes WHERE   port_country_code='".$_POST["sea_loading_country_id"]."'";
   	
	    $res = $db->getData($sql);
	    $json_data = json_encode($res);
	    //var_dump($json_data);?>
     <option value="">Select an Option</option>
	    <?php
	    foreach ($res as $cities) {?>

            <option value="<?php  echo $cities["port_city_code"];?>"><?php echo $cities["port_city"]; ?></option>
		   <?php
	        } 


   }

   //Select General Origin City on the base of General Origin States of USA and Canada
 if(!empty($_POST["origin_state_code_gernal"])) {
	  $sql = "select DISTINCT port_city_code,port_city From air_sea_codes WHERE   state_code ='".$_POST["origin_state_code_gernal"]."'  && port_country_code='".$_POST["port_country_code"]."'";

	    $res = $db->getData($sql);?>
	  <option value="">Select an Option</option>
	      <?php
	    foreach ($res as $states_cities) {?>
				<option value="<?php echo $states_cities['port_city_code'];?>"><?php echo $states_cities["port_city"]; ?></option>
		   <?php
	        } 
	 

   }

   if(!empty($_POST["origin_state_code"])) {
	  $sql = "select DISTINCT port_city_code,port_city From air_sea_codes WHERE port_unlocode in (select air_unlocode from air_codes) and  state_code ='".$_POST["origin_state_code"]."'  && port_country_code='".$_POST["port_country_code"]."'";

	    $res = $db->getData($sql);?>
	  <option value="">Select an Option</option>
	      <?php
	    foreach ($res as $states_cities) {?>
				<option value="<?php echo $states_cities['port_city_code'];?>"><?php echo $states_cities["port_city"]; ?></option>
		   <?php
	        } 
	 

   }
    if(!empty($_POST["origin_state_code_sea"])) {
	  $sql = "select DISTINCT port_city_code,port_city From sea_codes WHERE  state_code ='".$_POST["origin_state_code_sea"]."'  && port_country_code='".$_POST["port_country_code"]."'";

	    $res = $db->getData($sql);?>
	  <option value="">Select an Option</option>
	      <?php
	    foreach ($res as $states_cities) {?>
				<option value="<?php echo $states_cities['port_city_code'];?>"><?php echo $states_cities["port_city"]; ?></option>
		   <?php
	        } 
	 

   }
   if(!empty($_POST["air_origin_state_code_general"])) {
	   $sql = "select DISTINCT state_code,state_name From air_sea_codes WHERE  port_country_code='".$_POST["port_country_code"]."' ";
	    $res = $db->getData($sql);?>
	      <?php
	   
				foreach ($res as $states) {?>
						<option value="<?php  echo $states["state_code"];?>" <?php if($_POST["air_origin_state_code_general"]==$states["state_code"]) {?> SELECTED <?php }?>><?php echo $states["state_name"]; ?></option>
				   <?php
				     } 

	}

 

//Air Origin AND Destination Country Select on the base of General Origin AND Destination Country 
   if(!empty($_POST["General_Code"]) ) {
   	
        $sql = "select DISTINCT port_country_code,port_country From air_sea_codes ";

	    $res = $db->getData($sql);
	    $json_data = json_encode($res);?>
	    
	    <?php
	    foreach ($res as $countries) {?>
				<option value="<?php  echo $countries["port_country_code"];?>" <?php if($_POST["General_Code"]==$countries["port_country_code"]) {?> SELECTED <?php }?>><?php echo $countries["port_country"]; ?></option>
		   <?php
	        } 

   }
   else{
       
   	$sql = "select DISTINCT port_city_code,port_city From air_sea_codes WHERE  port_unlocode in (select air_unlocode from air_codes) AND  port_country_code='".$_POST["Origin_Country_Code"]."'";
	    $res = $db->getData($sql);
	     //$json_data = json_encode($res);
	    if($_POST["general_country_id"]==''){?>
	     <option value="">Select an Option</option>
	     <?php }?>
	 
	    <?php
	    foreach ($res as $cities) {?>

            <option value="<?php  echo $cities["port_city_code"];?>"><?php echo $cities["port_city"]; ?></option>
		   <?php
	        } 


   }

//Destination




   if(!empty($_POST["Dest_General_Code"])) {
	     $sql = "select DISTINCT port_country_code,port_country From air_sea_codes";
	    $res = $db->getData($sql);
	    $json_data = json_encode($res);?>
	    <?php
	    foreach ($res as $countries) {?>
			 	<option value="<?php  echo $countries["port_country_code"];?>" <?php if($_POST["Dest_General_Code"]==$countries["port_country_code"]) {?> SELECTED <?php }?>><?php echo $countries["port_country"]; ?></option>
		   <?php
	        } 

    }else{

   	  $sql = "select DISTINCT port_city_code,port_city From air_sea_codes WHERE  port_unlocode in (select air_unlocode from air_codes) AND  port_country_code='".$_POST["Dest_Destination_Code"]."'";
	    $res = $db->getData($sql);
	    $json_data = json_encode($res);
	    //var_dump($json_data);?>

	    <?php
	    foreach ($res as $cities) {?>

            <option value="<?php  echo $cities["port_city_code"];?>"><?php echo $cities["port_city"]; ?></option>
		   <?php
	        } 


   }







   //Air city select on the base Gernal Origin City

   if(!empty($_POST["GenralCitySelect"])) {
   	if($_POST["job_origin_states"]!=''){
   		$sql = "select DISTINCT port_city_code,port_city From air_sea_codes WHERE  port_unlocode in (select air_unlocode from air_codes) and port_country_code='".$_POST["port_country_code"]."' &&  state_code ='".$_POST["job_origin_states"]."'  ";
   	}else{
	   $sql = "select DISTINCT port_city_code,port_city From air_sea_codes WHERE   port_unlocode in (select air_unlocode from air_codes) AND  port_country_code='".$_POST["port_country_code"]."'";
     	}
	    $res = $db->getData($sql);
	    $json_data = json_encode($res);
	    //var_dump($json_data);?>
	    
	    <?php
	    foreach ($res as $cities) {?>

            <option value="<?php  echo $cities["port_city_code"];?>" <?php if($_POST["GenralCitySelect"]==$cities["port_city_code"]) {?> SELECTED <?php }?>><?php echo $cities["port_city"]; ?></option>
		   <?php
	        } 

    }
//Sea Loading 
    if(!empty($_POST["GenralCitySelect_Sea"])) {
   	if($_POST["job_origin_states"]!=''){
   		$sql = "select DISTINCT port_city_code,port_city From sea_codes WHERE  port_country_code='".$_POST["port_country_code"]."' &&  state_code ='".$_POST["job_origin_states"]."'  ";
   	}else{
	    $sql = "select DISTINCT port_city_code,port_city From sea_codes WHERE  port_country_code='".$_POST["port_country_code"]."'";
     	}
     
	    $res = $db->getData($sql);
	    $json_data = json_encode($res);
	    //var_dump($json_data);?>
	    
	    <?php
	    foreach ($res as $cities) {?>

            <option value="<?php  echo $cities["port_city_code"];?>" <?php if($_POST["GenralCitySelect_Sea"]==$cities["port_city_code"]) {?> SELECTED <?php }?>><?php echo $cities["port_city"]; ?></option>
		   <?php
	        } 

    }

 ?>
 <?php 

  //Port Code select Qry
    if(!empty($_POST["city_id_air_code"])) {
	     $sql_portcode = "select * From air_codes WHERE city_code='".$_POST["city_id_air_code"]."' && country_code='".$_POST["country"]."'";
	    
	   $res_portcode = $db->getData($sql_portcode);
	  // echo  json_encode($res_portcode);?>
	      <?php
	   foreach($res_portcode as $portCode) {?>
				<option value="<?php echo $portCode['airport_code'];?>" <?php if($_POST['city_id_air_code']==$portCode['city_code']){?>SELECTED<?php }?>><?php echo trim($portCode["airport_code"]);?></option>
		   <?php
	   } 
	 

    }
   // /Port Code select Qry
    if(!empty($_POST["city_id_air_code_sea"])) {
	   $sql_portcode = "select * From sea_codes WHERE 	port_city_code='".$_POST["city_id_air_code_sea"]."' && port_country_code='".$_POST["country"]."'";
	   
	   $res_portcode = $db->getData($sql_portcode);
	  // echo  json_encode($res_portcode);?>
	      <?php
	   foreach ($res_portcode as $portCode) {?>
				<option value="<?php echo $portCode['port_unlocode'];?>" <?php if($_POST["city_id_air_code_sea"]==$portCode["port_city_code"]) {?> SELECTED <?php }?>><?php echo trim($portCode["port_unlocode"]); ?></option>
		   <?php
	   } 
	 

    }
 //Port Code select Qry
    if(!empty($_POST["city_id"])) {
	    $sql_portcode = "select * From air_sea_codes WHERE port_city_code='".$_POST["city_id"]."' && port_country_code='".$_POST["country"]."'";
	    
	   $res_portcode = $db->getData($sql_portcode);
	  // echo  json_encode($res_portcode);?>
	      <?php
	   foreach ($res_portcode as $portCode) {?>
				<option value="<?php echo trim($portCode['port_unlocode']);?>" <?php if($_POST["city_id"]==$portCode["port_city_code"]) {?> SELECTED <?php }?>><?php echo trim($portCode["port_unlocode"]); ?></option>
		   <?php
	   } 
	 

    }
?>



 <?php
//Origin Airport Name  select Qry
   if(!empty($_POST["AirportName_CODE"])) {
	    $sql = "select DISTINCT airport_code,airport_name,city_code From air_codes WHERE  country_code='".$_POST["country"]."' && city_code='".$_POST["AirportName_CODE"]."'";
	   
	    $res = $db->getData($sql);
	   // $json_data =   json_encode($res);?>
	   <?php
	   foreach($res as $airportName) {?>
				<option   value="<?php echo $airportName['airport_code'];?>"  <?php if($_POST["AirportName_CODE"]==$airportName["city_code"]) {?> SELECTED <?php }?> ><?php echo $airportName["airport_code"].'-'.$airportName["airport_name"]; ?></option>
		   <?php
	        } 
    }else {
	    $sql = "select airport_code,airport_name From air_codes WHERE  country_code='".$_POST["country"]."' && city_code='".$_POST["origin_airport_code"]."'";
	   
	    $res = $db->getData($sql);?>
	   <?php
	   foreach($res as $airportName) {?>
				<option   value="<?php echo $airportName['airport_code'];?>" ><?php echo  $airportName['airport_code'].'-'.$airportName["airport_name"]; ?></option>
		   <?php
	        } 
    }
?>

<?php

 //Dest Block   

//end here





if(! empty($_POST["destcountry_id"])) {
	    $sql = "select DISTINCT city_code,city_name from air_codes WHERE  port_unlocode in (select air_unlocode from air_codes) and country_code='".$_POST["destcountry_id"]."'";
	    $res = $db->getData($sql);
	    $json_data = json_encode($res);
	    //var_dump($json_data);?>
	     <option   value="">Select Destinaton City</option>
	   <?php foreach ($res as $destcities) {?>
				<option value="<?php echo $destcities["city_code"]; ?>"><?php echo $destcities["city_name"]; ?></option>
		   <?php
	        } 

    }

   //Dest IATA Block   


 //Dest AirPort Block   

    if(!empty($_POST["destination_port_code"])) {
	    $sql = "select airport_code,airport_name from air_codes WHERE country_code='".$_POST["DestcountryId"]."' && city_code='".$_POST["DestcityCityCodeID"]."' && airport_code='".$_POST["destination_port_code"]."'";
	    $res = $db->getData($sql);
	   // $json_data = json_encode($res);
	    //var_dump($json_data);?>
	
	    <?php 
	    foreach ($res as $destAirPortName) {?>
				<option value="<?php echo  trim($destAirPortName["airport_code"]); ?>"><?php echo $destAirPortName["airport_name"]; ?></option>
		   <?php
	        } 

    }  
?>

<?php
//Fetch value for fill read only input on the base of  Airport Name  select Qry
   if(!empty($_POST["destairport_code"])) {
	    $sql = "select * FROM air_codes WHERE airport_code = '".$_POST["destairport_code"]."'";
	    $res = $db->getData($sql);
	    echo json_encode($res);
    }


  if(!empty($_POST["recordId"])) {

      $sql_detail= "select *  From vtiger_jobcf WHERE jobid='".$_POST["recordId"]."'";
	   $results = $db->getData($sql_detail);
	   $countryCode = $results[0]['job_air_origin_country']; 
	   $sql = "select * FROM air_codes WHERE country_code = '".$countryCode."'";
	   $res = $db->getData($sql);
	    echo json_encode($res);

	}

	

	//Port Code select Qry
   if(!empty($_POST["job_air_origin_port_code"])) {
	    $sql = "select airport_code,airport_name,city_code from air_codes WHERE country_code='".$_POST["country"]."' && city_code='".$_POST["cityId"]."' && airport_code='".$_POST["job_air_origin_port_code"]."'";
	    $res = $db->getData($sql);?>
	
	    <?php 
	    foreach ($res as $AirPortName) {?>
				<option value="<?php echo $AirPortName['airport_code'];?>"<?php if($_POST["job_air_origin_port_code"]==$AirPortName["airport_code"]){?> SELECTED <?php }?>><?php echo $AirPortName["airport_code"].'-'.$AirPortName["airport_name"]; ?></option>
		   <?php
	        } 

    }  

?>
