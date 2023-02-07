<?php
//	ini_set('display_errors','on'); version_compare(PHP_VERSION, '5.5.0') <= 0 ? error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED) : error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);   // DEBUGGING

	include ('../RateCalculator/ratecalculatorfunc.php');
	//var_dump($_POST);
	$db = new query();

	//var_dump($results);
   if(!empty($_POST["port_country_code"])) {
   	
	    $sql = "select DISTINCT port_city_code,port_city From sea_codes WHERE port_country_code='".$_POST["port_country_code"]."'";
	    $res = $db->getData($sql);
	   // echo $json_data = json_encode($res);
	    //var_dump($json_data);?>
	     <option   value="">Select Port City </option>
	    <?php
	    foreach ($res as $cities) {?>
				<option value="<?php echo $cities["port_city_code"];?>"><?php echo $cities["port_city"]; ?></option>
		   <?php
	        } 

    }

 ?>
 <?php 
 //Port Code select Qry
    if(!empty($_POST["port_city_code"])) {
	    $sql_portcode = "select port_unlocode,port_city From sea_codes WHERE port_city_code='".$_POST["port_city_code"]."' && port_country_code='".$_POST["portcountrycode"]."'";
	    $res_portcode = $db->getData($sql_portcode);
	 //   $json_data =  json_encode($res);?>
	  <option   value="">Select Loading Port Code </option>
	      <?php
	    foreach ($res_portcode as $portCode) {?>
				<option value="<?php echo trim($portCode["port_unlocode"]);?>"><?php echo trim($portCode["port_unlocode"].'-'.$portCode["port_city"]); ?></option>
		   <?php
	        } 
	 

    }
?>


<?php
//Fetch value for fill read only input on the base of  Airport Name  select Qry
   if(!empty($_POST["port_unlocode"])) {
	   $sql = "select * FROM sea_codes WHERE port_unlocode like '%".$_POST["port_unlocode"]."%'";
	    $res = $db->getData($sql);
	    echo json_encode($res);  	 

    }
?>



<?php

 //Discharge Block   

if(!empty($_POST["dis_country_code"])) {
	    $sql = "select DISTINCT port_city_code,port_city from sea_codes WHERE port_country_code='".$_POST["dis_country_code"]."'";
	    $res = $db->getData($sql);
	    $json_data = json_encode($res);
	    //var_dump($json_data);?>
	     <option   value="">Select Dis.City Name </option>
	   <?php foreach ($res as $dis_cities) {?>
				<option value="<?php echo $dis_cities["port_city_code"]; ?>"><?php echo $dis_cities["port_city"]; ?></option>
		   <?php
	        } 

    }

   ?>
 <?php 
 //Port Code select Qry
    if(!empty($_POST["dis_port_city_code"])) {
	    $sql_portcode = "select port_unlocode,port_city From sea_codes WHERE port_city_code='".$_POST["dis_port_city_code"]."' && port_country_code='".$_POST["dis_portcountrycode"]."'";
	    $res_portcode = $db->getData($sql_portcode);
	 //   $json_data =  json_encode($res);?>
	  <option value="">Select Discharge Port Code </option>
	      <?php
	    foreach ($res_portcode as $dis_portCode) {?>
				<option value="<?php echo trim($dis_portCode["port_unlocode"]);?>"><?php echo trim($dis_portCode["port_unlocode"].'-'.$dis_portCode["port_city"]); ?></option>
		   <?php
	        } 
	 

    }
?>
<?php
//Fetch value for fill read only input on the base of  dis unloccode  select Qry
   if(!empty($_POST["dis_port_unlocode"])) {
	    $sql = "select * FROM sea_codes WHERE port_unlocode like '%".$_POST["dis_port_unlocode"]."%'";
	    $res = $db->getData($sql);
	    echo json_encode($res);  	 

    }

?>
