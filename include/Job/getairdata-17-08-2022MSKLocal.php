<?php
//	ini_set('display_errors','on'); version_compare(PHP_VERSION, '5.5.0') <= 0 ? error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED) : error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);   // DEBUGGING

	include ('../RateCalculator/ratecalculatorfunc.php');
	//var_dump($_POST);
	$db = new query();
   //echo  $_POST["edit_id"];
  // var_dump($_POST);
//Origin Country Select on the base of General Origin Country 
//var_dump($results);
   if(!empty($_POST["General_Code"])) {
   	
	    $sql = "select DISTINCT country_code,country_name From air_codes WHERE country_code='".$_POST["General_Code"]."'";
	    $res = $db->getData($sql);
	    $json_data = json_encode($res);
	    //var_dump($json_data);?>
	     <option   value="">Select City Name </option>
	    <?php
	    foreach ($res as $countries) {?>
				<option value="<?php echo $countries["country_code"];?>"><?php echo $countries["country_name"]; ?></option>
		   <?php
	        } 

    }





//end here 
	//var_dump($results);
   if(!empty($_POST["country_id"])) {
   	
	    $sql = "select DISTINCT city_code,city_name From air_codes WHERE country_code='".$_POST["country_id"]."'";
	    $res = $db->getData($sql);
	    $json_data = json_encode($res);
	    //var_dump($json_data);?>
	     <option   value="">Select City Name </option>
	    <?php
	    foreach ($res as $cities) {?>
				<option value="<?php echo $cities["city_code"];?>"><?php echo $cities["city_name"]; ?></option>
		   <?php
	        } 

    }

 ?>
 <?php 
 //Port Code select Qry
    if(!empty($_POST["city_id"])) {
	    $sql_portcode = "select airport_code,airport_name From air_codes WHERE city_code='".$_POST["city_id"]."' && country_code='".$_POST["country"]."'";
	    $res_portcode = $db->getData($sql_portcode);
	 //   $json_data =  json_encode($res);?>
	  <option   value="">Select Port Code(IATA) </option>
	      <?php
	    foreach ($res_portcode as $portCode) {?>
				<option value="<?php echo $portCode['airport_code'];?>"><?php echo trim($portCode["airport_code"].'-'.$portCode["airport_name"]); ?></option>
		   <?php
	        } 
	 

    }
?>
<?php
//Origin Airport Name  select Qry
   if(!empty($_POST["air_port_code_iata"])) {
	    $sql = "select airport_code,airport_name From air_codes WHERE airport_code = '".$_POST["air_port_code_iata"]."' && country_code='".$_POST["country"]."' && city_code='".$_POST["city"]."'";
	    $res = $db->getData($sql);
	   // $json_data =   json_encode($res);?>
	    <option   value="">Select AirportName </option>
	   <?php
	   foreach($res as $airportName) {?>
				<option   value="<?php echo $airportName['airport_code'];?>"><?php echo  $airportName["airport_name"]; ?></option>
		   <?php
	        } 
    }
?>

<?php
//Fetch value for fill read only input on the base of  Airport Name  select Qry
   if(!empty($_POST["air_port_code"])) {
	    $sql = "select * FROM air_codes WHERE airport_code = '".$_POST["air_port_code"]."'";
	    $res = $db->getData($sql);
	    echo json_encode($res);  	 

    }
?>



<?php

 //Dest Block   

if(! empty($_POST["destcountry_id"])) {
	    $sql = "select DISTINCT city_code,city_name from air_codes WHERE country_code='".$_POST["destcountry_id"]."'";
	    $res = $db->getData($sql);
	    $json_data = json_encode($res);
	    //var_dump($json_data);?>
	     <option   value="">Select Dest.City Name </option>
	   <?php foreach ($res as $destcities) {?>
				<option value="<?php echo $destcities["city_code"]; ?>"><?php echo $destcities["city_name"]; ?></option>
		   <?php
	        } 

    }

   //Dest IATA Block   

if(!empty($_POST["DestcityCityCode"])) {
	    $sql = "select airport_code,airport_name From air_codes WHERE country_code='".$_POST["DestcountryId"]."' && city_code='".$_POST["DestcityCityCode"]."'";
	    $res = $db->getData($sql);
	   // $json_data = json_encode($res);
	    //var_dump($json_data);?>
	     <option value="">Select IATA Port Code</option>
	    <?php
	    foreach ($res as $destportcode) {?>
				<option value="<?php echo trim($destportcode["airport_code"]); ?>"><?php echo trim($destportcode["airport_code"].'-'.$destportcode["airport_name"]); ?></option>
		   <?php
	        } 

    }  
 //Dest AirPort Block   
    if(!empty($_POST["destination_port_code"])) {
	    $sql = "select airport_code,airport_name from air_codes WHERE country_code='".$_POST["DestcountryId"]."' && city_code='".$_POST["DestcityCityCodeID"]."' && airport_code='".$_POST["destination_port_code"]."'";
	    $res = $db->getData($sql);
	   // $json_data = json_encode($res);
	    //var_dump($json_data);?>
	    <option value="">Select Airport Name</option>
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
?>
