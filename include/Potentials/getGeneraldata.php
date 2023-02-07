<?php

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
   if(!empty($_POST["general_country_id"])) {
   	
	   $sql = "select DISTINCT port_city_code,port_city From air_sea_codes WHERE port_country_code='".$_POST["general_country_id"]."'";
	    $res = $db->getData($sql);
	    ?>
	    <option   value="">Select An Option</option>
	    <?php
	    foreach ($res as $cities) {?>
				<option value="<?php echo $cities["port_city_code"];?>"><?php echo $cities["port_city"]; ?></option>
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


 


 
