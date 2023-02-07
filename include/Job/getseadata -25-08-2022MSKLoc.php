<?php
	include ('../RateCalculator/ratecalculatorfunc.php');
	//var_dump($_POST);
	$db = new query();

	//var_dump($results);
   if(!empty($_POST["air_port_code_iata"]))
    {
   	
	    $sql = "select DISTINCT * From air_sea_codes WHERE port_unlocode like '%".$_POST["air_port_code_iata"]."%' && port_country_code='".$_POST["country"]."' && port_city_code='".$_POST["city"]."'";
	    $res = $db->getData($sql);
	     echo json_encode($res);  	 
    }

 ?>
 