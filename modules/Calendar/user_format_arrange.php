<?php
	chdir(dirname(__FILE__) . '/../..');
	include_once 'vtlib/Vtiger/Module.php';
	include_once 'includes/main/WebUI.php';
	include_once 'include/Webservices/Utils.php';
	
	  set_time_limit(0);
	  date_default_timezone_set("UTC");	
	  ini_set('memory_limit','64M');
    global $adb;
    //global $current_user;

    $current_user_id = $_REQUEST['current_user_id'];
 
    $current_user = Users_Record_Model::getInstanceById($current_user_id, 'Users');

    $status="Active";

     //02.10.2020::Mehtab Updated due to Azerbaijan and Armenia conflict
    //Location id : 85808 :: Baku
    //Location id: 85820 :: Yerevan
    $params = array($status);
    $query = "SELECT user_name, department, address_city, first_name, last_name from vtiger_users WHERE status=? and (user_name != 'admin' and  title != 'driver' )  ";
					

		if($current_user->get('location_id')=='85808')
		{
			//Restrict armenia user
			$query .= " AND location_id != ? ";
			array_push($params, '85820');
		}
		else if($current_user->get('location_id')=='85820')
		{
			//Restrict baku user
			$query .= " AND location_id != ? ";  
			array_push($params, '85808');
    }
  
     // Вывод данных пользователей
     $res_user = $adb->pquery($query, $params, true, "Error filling in user array: ");
     $userList = array();
     While($row = $adb->fetch_array($res_user)){

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