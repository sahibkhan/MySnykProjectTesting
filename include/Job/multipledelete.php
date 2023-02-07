<?php
     	
    include ('../RateCalculator/ratecalculatorfunc.php');
	//var_dump($_POST);
	$db = new query();

     if(isset($_POST['deleteid']))
    {
            	 $deleteid = $_POST['deleteid'];
            	 echo  $query_delete =  $db->updateData("UPDATE  `job_container_type_size` SET deleted_status ='1' WHERE `id`= '".$deleteid."'");
            exit;
       
	}

 ?>

