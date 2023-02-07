<?php
	include ('../RateCalculator/ratecalculatorfunc.php');
	//var_dump($_POST);
	$db = new query();

	 if(isset($_POST['editId']))
   {
       $sql= "select *  From vtiger_jobcf WHERE jobid = '".$_POST['editId']."'";
	   $results = $db->getData($sql);
	   //echo  json_encode($results);
	    echo $Mode = $results[0]['cf_1711'];

   }
 ?>

