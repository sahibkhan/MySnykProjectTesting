<?php

if (!isset($_POST['val'])) exit;
elseif (!isset($_POST['txt'])) exit;
elseif (!isset($_POST['ct'])) exit;
elseif (!isset($_POST['id'])) exit;

//define('DB_HOST', '192.168.100.191');
//define('DB_USER', 'theuseroferp'); 
//define('DB_PASS', 'k0ItFGGzdKTSo'); 
//define('DB_NAME', 'erp');
define('DB_HOST', 'aurora-db-cluster.cluster-cetdkylp4m5f.us-east-1.rds.amazonaws.com');
define('DB_USER', 'salman'); 
define('DB_PASS', 'GLK_7143#2020Y'); 
define('DB_NAME', 'auroraerpdb'); 
$db = mysqli_connect(DB_HOST, DB_USER, DB_PASS);
mysqli_set_charset($db, 'utf8');
mysqli_select_db($db, DB_NAME);

$val = $_POST['val'];
$txt = strtoupper($_POST['txt']);
$ct = $_POST['ct'];
$id = $_POST['id'];
$dep = $_POST['department'];
$def = isset($_POST['def'])?$_POST['def']:'';

$sql = $dep=='AXG'?'SELECT `cf`.*,`codes`.`name` FROM `vtiger_portcodescf` AS `cf` INNER JOIN `vtiger_portcodes` AS `codes` On `codes`.`portcodesid`=`cf`.`portcodesid`':'SELECT DISTINCT `cf`.*,`codes`.`name` FROM `vtiger_portcodescf` AS `cf` INNER JOIN `vtiger_portcodes` AS `codes` On `codes`.`portcodesid`=`cf`.`portcodesid` WHERE `cf`.`cf_5425` LIKE \'%'.$txt.'%\' ORDER BY `cf`.`cf_5427` ASC';
$sql = mysqli_query($db,$sql);

$num = mysqli_num_rows($sql);
$bd = '';
if ($dep=='AXG') {
for ($i=0;$i<$num;$i++) {
	$arr = mysqli_fetch_array($sql);
	$nm = ucwords(strtolower($arr['cf_5427'])).' ('.$arr['name'].')';
	$bd .= '<div onclick="selectPort(this)" data-txt="'.$nm.'">'.$nm.'</div>';
}
$bd = '<div id="country-all-list" onclick="return showDrop(this,stop(event))"><div id="selected-port">Select Port</div><div class="port-after" onclick="stop(event)"><div class="search-main"><input type="text" placeholder="Search" onkeyup="searchPort(this)"></div><div id="found-portcode-list"></div><div id="all-portcode-list">'.$bd.'</div></div></div>';
} else {
$bd = '<option value="0">Select Port</option>';
for ($i=0;$i<$num;$i++) {
	$arr = mysqli_fetch_array($sql);
	$nm = ucwords(strtolower($arr['cf_5427'])).' ('.$arr['name'].')';
	$bd .= '<option value="'.$nm.'" '.($def==$nm?'selected':'').'>'.$nm.'</option>';
}
$bd = '<select class="coutryPicklist" id="port_select">'.$bd.'</select>';
}
$out = ['html'=>$bd,'ct'=>$ct,'id'=>$id];
echo json_encode($out);



?>