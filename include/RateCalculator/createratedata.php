<?php
	include ('ratecalculatorfunc.php');
	//var_dump($_POST);
	$db = new query();


	$data = $_POST["data"];
	
	$fromport = $_POST["fromport"];
	$fromcountry = $_POST["fromcountry"];
	
	$toport = $_POST["toport"];
	$tocountry = $_POST["tocountry"];
	
	$rates = explode("recdevider",$data);
	
	//$rates["fromPort"] = $fromport;
	///$rates["fromCountry"] = $fromcountry;
	
	//$rates["toPort"] = $toport;
	//$rates["toCountry"] = $tocountry;
	
	//print_r($rates); exit;

	$json_data = json_encode($rates);
	//echo $json_data; exit;
	//$json_data = str_replace("recdevider",",",$json_data).trim();
	echo urlencode($json_data); exit;
	
	
	
	$resp = array();
	$resp["Error"] = "err";
	$resp["Rate"] = urlencode($rates);
	$resp["TaC"] = $tac;
	
	echo "Rate Data ".$data;

?>