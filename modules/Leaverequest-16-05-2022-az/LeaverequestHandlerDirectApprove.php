<?php
	ini_set('display_errors','on'); version_compare(PHP_VERSION, '5.5.0') <= 0 ? error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED) : error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);   // DEBUGGING
	date_default_timezone_set('Asia/Karachi');
	//include_once("/var/www/html/include/Leaverequest/leaverequestfunc.php");
	//include_once("/var/www/html/modules/Leaverequest/LeaverequestHandler.php");
	
	include_once("/var/www/html/live-gems/include/Leaverequest/leaverequestfunc.php");
	include_once("/var/www/html/live-gems/modules/Leaverequest/LeaverequestHandler.php");
	
	$qry = $_SERVER['QUERY_STRING'];
	//echo "Connected to db";
	$rid = $_POST['rid'];
	$uid = $_POST['uid'];
	
	$user_type = "HR";

	if($uid=='1588' || $uid=='1078' || $uid=='1249' || $uid=='1269'  || $uid=='1600')
	{
		$from="HR";
		if($uid=="1588")
		{
			$from="HR Head";
		}
		
		
		
		//echo "from leaverequesthandler 2";
		//echo " RID :".$rid." UID:".$uid;
	}
	elseif($uid=='279')
	{
		$from="FD Head";
		$user_type="FD Head";
	}
	else
	{
		//echo "Not allowed direct approve";
		//echo " RID :".$rid." UID:".$uid;
		//exit;
		
		$from="Head";
		$user_type="Head";
	}
	
	//$burl = "https://gems.globalink.net/modules/Leaverequest/LeaveRequestSetStatus.php";
	$burl = "http://tiger.globalink.net/live-gems/modules/Leaverequest/LeaveRequestSetStatus.php";
	
	$aurl = $burl."?e=".urlencode(encryptIt("a,".$user_type.",".$rid.",".$uid));
	
	echo $aurl;
	
	//$cont =   file_get_contents($aurl);
	//openNewTap($aurl);
	//$result ="App Approved ".$cont;
	
	//echo $result;
	//$result = leave_msg_handler('Leaverequest',$rid, $uid, "");

	function openNewTap(string $url)
	{
		echo '<script type="text/javascript">';
		echo 'window.open("'.$url.'");';
		echo '</script>';
	}
	
	
?>