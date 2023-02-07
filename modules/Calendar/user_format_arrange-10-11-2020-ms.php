<?php
  
  //ini_set('display_errors', 1);
  //error_reporting(E_ALL);
  $mysqli = new mysqli("aurora-db-cluster.cluster-cetdkylp4m5f.us-east-1.rds.amazonaws.com","salman","GLK_7143#2020Y","auroraerpdb");

if ($mysqli->connect_errno) {
    printf("Не удалось подключиться: %s\n", $mysqli->connect_error);
    exit();
}
$mysqli->set_charset("utf8");

$query = "SELECT user_name, department, address_city, first_name, last_name FROM vtiger_users WHERE `status` = 'Active'  ";
$result = $mysqli->query($query);

while ($row = $result->fetch_assoc()) {
   
    $login = trim($row['user_name']);		
    // echo 'login = ' . $login.'<br>'; 
    $title = $row['department'];		 
    $location = $row['address_city'];	 
/*     $str = '';	
    if ($location == 'Almaty'){
      $str = $title.', Almaty'; 
    } else {
      $str = $location;
    }
   */
    $first_name = trim($row['first_name']);
    $last_name = trim($row['last_name']);

    $userList[] = array('first_name' => $first_name, 'last_name' => $last_name, 'login' => $login);
}
      
   echo json_encode($userList);

?>
