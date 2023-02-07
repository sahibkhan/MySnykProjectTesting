<?php
	ini_set('display_errors','on'); version_compare(PHP_VERSION, '5.5.0') <= 0 ? error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED) : error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);   // DEBUGGING

	include ('../RateCalculator/ratecalculatorfunc.php');
	//var_dump($_POST);
	$db = new query();
    // $_POST["country_id"];
   if(! empty($_POST["country_id"])) {
	    $sql = "select * from air_codes WHERE country_code='".$_POST["country_id"]."'";
	    $res = $db->getData($sql);
	    $json_data = json_encode($res);
	    //var_dump($json_data);
	    ?>
	    <select name="Air_Origin_City" id="Air_Origin_City" class="inputElement select2  select2-offscreen">
			<?php
				foreach ($res as $cities) {
				    ?>
				<option value="<?php echo $cities["city_code"]; ?>"><?php echo $cities["city_name"]; ?></option>
		    <?php
	        } ?>
        </select> 
<?php 
    }

?>