<?php
//	ini_set('display_errors','on'); version_compare(PHP_VERSION, '5.5.0') <= 0 ? error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED) : error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);   // DEBUGGING

	include ('../RateCalculator/ratecalculatorfunc.php');
	$db = new query();
   if(!empty($_POST["country_states"])) {
   	
	   $sql = "select * FROM air_sea_codes WHERE port_country_code='".$_POST["country_states"]."'";
	   $res = $db->getData($sql);
	   $json_data = json_encode($res);?>

       <option value="">Select States</option>
       <?php 
	   foreach ($res as $states) 
	   {?>
		 <option value="<?php echo $states['state_code']; ?>"><?php echo $states["state_name"];?></option>
		<?php  
	   } 

    }

?>
