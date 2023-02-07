<?php
if (!isset($_POST['info'])) exit; $info = $_POST['info'];
$info['lunch'] = $info['lunch'] === 'true'?true:false;
$info['taxi'] = $info['taxi'] === 'true'?true:false;

define('DB_HOST', 'aurora-db-cluster.cluster-cetdkylp4m5f.us-east-1.rds.amazonaws.com');
define('DB_USER', 'salman'); 
define('DB_PASS', 'GLK_7143#2020Y'); 
define('DB_NAME', 'auroraerpdb'); 
$db = mysqli_connect(DB_HOST, DB_USER, DB_PASS);
mysqli_set_charset($db, 'utf8');
mysqli_select_db($db, DB_NAME);

$end = isWeekend($info['date']);

$weekday = 0;
$weekend = 0;

$weekday_taxi = 0;
$weekend_taxi = 0;

$final = 0;
$final_taxi = 0;

$sql = mysqli_query($db,"SELECT `ov`.* FROM `vtiger_overtimeratescf` AS `ov` INNER JOIN `vtiger_locationcf` AS `loc` ON `loc`.`cf_1557`=`ov`.`cf_5409` INNER JOIN `vtiger_rrspackerscrewcf` AS `rrs` ON `rrs`.`cf_5411`=`loc`.`locationid` WHERE `rrs`.`rrspackerscrewid`='".$info['id']."' AND `ov`.`cf_5277`=`rrs`.`cf_5393`");
$num = mysqli_num_rows($sql); 

for ($i=0;$i<$num;$i++) {

	$arr = mysqli_fetch_array($sql);
	if ($arr['cf_5279']=='WeekDays') $weekday = $arr['cf_5281'];
	elseif ($arr['cf_5279']=='WeekEnd') $weekend = $arr['cf_5281'];
	
	
	if($info['taxi'])
	{
		if ($arr['cf_5279']=='WeekDays'){
			$country = $arr['cf_5409'];
			$sql_taxi_weekdays = mysqli_query($db,"SELECT `ov`.* FROM `vtiger_overtimeratescf` AS `ov` where cf_5277='Taxi' AND cf_5279='".$arr['cf_5279']."' AND cf_5409='".$country."'");
			$arr_taxi_weekdays = mysqli_fetch_array($sql_taxi_weekdays);
			$weekday_taxi = $arr_taxi_weekdays['cf_5281'];
		}
		elseif($arr['cf_5279']=='WeekEnd')
		{
			$country = $arr['cf_5409'];
			$sql_taxi_weekend = mysqli_query($db,"SELECT `ov`.* FROM `vtiger_overtimeratescf` AS `ov` where cf_5277='Taxi' AND cf_5279='".$arr['cf_5279']."' AND cf_5409='".$country."'");
			$arr_taxi_weekend = mysqli_fetch_array($sql_taxi_weekend);
			$weekend_taxi = $arr_taxi_weekend['cf_5281'];
		}
	}
}

if ($end) $final = $weekend; else $final = $weekday;


if($end) $final_taxi = $weekend_taxi; else $final_taxi = $weekday_taxi;


$st = explode(':',$info['start']);
$ed = explode(':',$info['finish']);
$st[0] = (int)$st[0];
$st[1] = (int)$st[1];
$ed[0] = (int)$ed[0];
$ed[1] = (int)$ed[1];
$df = 0;


if ($end) {

	$a = ($ed[0]-$st[0]);
	if ($ed[1]===30) $a += 1;
	$final = $a*$final;

} else {

	if ($ed[0]>18) {
		if ($st[0]<=13) {
			if ($st[0]<9) $df = 9-$st[0];
			if ($info['lunch']) $df += 1;
		}
		$ed[0] -= 18;
		if ($ed[1]==30) $ed[0] += 1;
		$final = ($df+$ed[0])*$final;
	} else {
		if ($ed[0]<13) {

			if ($ed[0]<9) {
				$a = $ed[0]-$st[0];
				if ($ed[1]==30) $a += 1;
				if ($ed[0]>=14&&$info['lunch']) $a += 1;
				$final = $a*$final;
			} else {
				if ($st[0]>=9) $a = 0; else $a = 9-$st[0];
				$final = $a*$final; 
			}

		} else {
			if ($st[0]>=9) $a = 0; else $a = 9-$st[0];
			if ($ed[0]>=14&&$info['lunch']) $a += 1;
			if ($ed[0]==13&&$ed[1]==30&&$info['lunch']) $a += 1;
			$final = $a*$final;
		}
	}
}

$final = str_replace('-', '', $final);

if($info['taxi'])
{
	$final = $final + $final_taxi;
}

echo $final;

function isWeekend($date) {
    return (date('N', strtotime($date)) >= 6);
}

/*function startCheck($time) {
	global $info, $end;

	$a = explode(':',$time);
	$b = [0,[0,0]];
	if ($a[0]<=9) {
		$c = 9-$a[0];
		if ((int)$a[1]===30) $c -= 1;
		if (!$end&&($a[0]<14)&&$info['lunch']) $b[0] += 1;
		$b[0] = $c;
	} elseif ($a[0]>=18) {
		$c = $a[0]-18;
		if ((int)$a[1]===30) $b[1][1] = 1;
		$b[1][0] = $c;
	}
	return $b;
}

function endCheck($time,$start) {
	$a = explode(':',$time);
	$b = 0;
	$c = 18+$start[0];
	$d = $a[0]-$c;
	$d -= $start[1];
	if ((int)$a[1]===30) $d += 1;
	$b = $d;
	return $b;
}*/

?>