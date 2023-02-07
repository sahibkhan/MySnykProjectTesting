<?php
	
	include ('ratecalculatorfunc.php');
	//var_dump($_POST);
	$db = new query();
	
	$tcid=$_POST["tcid"];
	$opt=$_POST["opt"];
	$handling=$_POST["handling"];

	$sqltac = "select * from termsandcond where id='$tcid'";
	$tacrs = $db->getData($sqltac);
	$tac = $tacrs[0]["termsandcond"];

	if($handling=="**")
	{
		$tac .= "<br><br><b>Station operates both with 20ft and 40ft containers</b>"; 
	}
	else if($handling=="*")
	{
		$tac .= "<br><br><b>Station operates only with 20ft containers</b>"; 
	}
	else if($handling=="-")
	{
		$tac .= "<br><br><b>station doesn't operate with 20' and 40' container</b>"; 
	}
	



	echo "<h1>Option $opt Terms and Condtions</h1> <br>".$tac;

?>