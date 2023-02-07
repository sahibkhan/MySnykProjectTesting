<?php 
ini_set("display_errors",1);
require_once('config.php');

// $sql = $pdo->query("CALL InsertCountryDetail('Pakistan JiTest',1,@LID)");
// $rs = $pdo->query("SELECT @LID as LAST_ID");
// $row = $rs->fetchObject();
// echo $row->LAST_ID;

if(isset($_POST['sub'])){
    $country_name = $_POST['country_name'];

    $status = $_POST['status'];
      //insert in to database
    $pdo->query("CALL UPDATE_LAST_INSERT_ID(@LAST_ID)");
    $rs_last_id = $pdo->query("SELECT @LAST_ID as LAST_INSERT_ID");
    $row = $rs_last_id->fetchObject();
  
    $sql = $pdo->query("CALL InsertCountryDetail('".$row->LAST_INSERT_ID."','".$country_name."','".$status."',@LID)");
    $rs = $pdo->query("SELECT @LID as LAST_ID");
    $row = $rs->fetchObject();
    //echo 'Last Insert Id '. $row->LAST_ID;
  
    header('Location:view_countries.php?lastId='.$row->LAST_ID);
    }

?>

<!DOCTYPE html>
<html>
<head>
  <title>Mysql store procedure Test </title>
  <style>
    .container {
      margin: 0 auto;
      padding: 10px;
    }
    .error {
      width: 100%;
      color: red;
    }
    .success {
      width: 100%;
      color: green;
    }
  </style>
</head>
<body>
<div class="container">
  <h2>mysql stored procedure  insert of record</h2>
  <h4>Country</h4>

  <form name="form1" method = "post">
    <p>
    Country name:<br>
    <input type="text" placeholder='Country Name' name="country_name" value="" required >
    </p>
    <p>
    Status:<br>
    <input type="text" name="status" value="" required >
   </p>
   <p>
    <input type="submit" value="Add Country" name='sub'>
   </p>
  </form>
 
</div>
</body>
</html>
