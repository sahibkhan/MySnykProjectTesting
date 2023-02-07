<?php
	ini_set('display_errors','on'); version_compare(PHP_VERSION, '5.5.0') <= 0 ? error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED) : error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);   // DEBUGGING

	include ('../RateCalculator/ratecalculatorfunc.php');
	//var_dump($_POST);
	$db = new query();
   //echo  $_POST["edit_id"];
  // var_dump($_POST);
	  $sql_rec = "select *  From vtiger_jobcf WHERE jobid='".$_POST["edit_id"]."'";
	   $results = $db->getData($sql_rec);

	//var_dump($results[0]['job_air_origin_city_code']);
	$cityCode = $results[0]['job_air_origin_city_code']; 
	$PortCode = $results[0]['job_air_origin_port_code'];
   $AirportName = $results[0]['job_air_origin_airport_name'];
	//var_dump($results);
   if(!empty($_POST["country_id"])) {
   	
	    $sql = "select DISTINCT city_code,city_name From air_codes WHERE country_code='".$_POST["country_id"]."'";
	    $res = $db->getData($sql);
	    $json_data = json_encode($res);
	    //var_dump($json_data);
	    foreach ($res as $cities) {?>
				<option value="<?php echo $cities["city_code"];?>"><?php echo $cities["city_name"]; ?></option>
		   <?php
	        } 

    }

 ?>
 <?php 
 //Port Code select Qry
    if(!empty($_POST["city_id"])) {
	    $sql_portcode = "select air_unlocode,airport_name From air_codes WHERE city_code='".$_POST["city_id"]."'";
	    $res_portcode = $db->getData($sql_portcode);
	 //   $json_data =  json_encode($res);?>
	      <?php
	    foreach ($res_portcode as $portCode) {?>
				<option value="<?php echo trim($portCode["air_unlocode"]);?>"><?php echo trim($portCode["air_unlocode"].'-'.$portCode["airport_name"]); ?></option>
		   <?php
	        } 
	 

    }
?>
<?php
//Origin Airport Name  select Qry
   if(!empty($_POST["air_unlocode"])) {
	    $sql = "select airport_code,airport_name From air_codes WHERE air_unlocode like '%".$_POST["air_unlocode"]."%'";
	    $res = $db->getData($sql);
	   // $json_data =   json_encode($res);?>
	   <?php
	   foreach($res as $airportName) {?>
				<option   value="<?php echo $airportName["airport_code"];?>"><?php echo  $airportName["airport_name"]; ?></option>
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
	    //var_dump($json_data);
	    foreach ($res as $destcities) {?>
				<option value="<?php echo $destcities["city_code"]; ?>"><?php echo $destcities["city_name"]; ?></option>
		   <?php
	        } 

    }

   //Dest IATA Block   

if(!empty($_POST["destcity_id"])) {
	    $sql = "select air_unlocode,airport_name from air_codes WHERE city_code='".$_POST["destcity_id"]."'";
	    $res = $db->getData($sql);
	    $json_data = json_encode($res);
	    //var_dump($json_data);?>
	     <option value="0">Select Code</option>
	    <?php
	    foreach ($res as $destportcode) {?>
				<option value="<?php echo trim($destportcode["air_unlocode"]); ?>"><?php echo trim($destportcode["air_unlocode"].'-'.$destportcode["airport_name"]); ?></option>
		   <?php
	        } 

    }  
 //Dest AirPort Block   
    if(!empty($_POST["destAircodeId"])) {
	    $sql = "select * from air_codes WHERE air_unlocode like '%".$_POST["destAircodeId"]."%'";
	    $res = $db->getData($sql);
	   // $json_data = json_encode($res);
	    //var_dump($json_data);?>
	    <option value="0">Select Name</option>
	    <?php 
	    foreach ($res as $destAirPortName) {?>
				<option value="<?php echo  trim($destAirPortName["airport_code"]); ?>"><?php echo $destAirPortName["airport_name"]; ?></option>
		   <?php
	        } 

    }  
?>

<?php
//Fetch value for fill read only input on the base of  Airport Name  select Qry
   if(!empty($_POST["destair_port_code"])) {
	    $sql = "select * FROM air_codes WHERE airport_code = '".$_POST["destair_port_code"]."'";
	    $res = $db->getData($sql);
	   // echo json_encode($res);
	   	 

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
