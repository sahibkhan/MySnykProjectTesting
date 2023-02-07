<?php
function sendEmail($a) {
    
    $adb = PearDatabase::getInstance();

$sql = $adb->query("SELECT `user`.`location_id`,`user`.`first_name`,`user`.`last_name`,`user`.`email1` 
                        FROM `vtiger_users` AS `user`
                        INNER JOIN `vtiger_crmentity` AS `crm` ON `crm`.`smownerid`=`user`.`id` WHERE `crm`.`crmid`='".$a[0]."' LIMIT 1");
if ($adb->num_rows($sql) === 0) return; else $arr = $adb->fetch_array($sql);

$location = $arr['location_id'];
$name = $arr['first_name'].' '.$arr['last_name'];
$email0 = $arr['email1'];
$b = $adb->pquery("SELECT `user`.`email1` FROM `vtiger_users` AS `user` WHERE `user`.`location_id`='".$location."' AND `title`='RRSSupervisor'");
$num =$adb->num_rows($b);
$emails = [];
//for ($i=0;$i<$num;$i++) $emails[] =$adb->fetch_array($b)['email1'];
//if (sizeof($emails) === 0) return; else $emails[] = $email0;

//$emails[] = 'supervisors@globalinklogistics.com';
//$emails = implode(';',$emails);
//$emails .= ';';

$link  = 'https://gems.globalink.net/index.php?module=PackerInquiry&view=Detail&record='.$a[0];
//$from = 'From: '.$name.' <'.$email0.'>';
$from = $email0;
$body = "<html><head></head><body><table>";
$body .= "<tr><td colspan=2> Created new Packer Inquiry by ".$name."</td></tr>";
$body .= "<tr><td colspan=2> Please see details on this link: <a href='$link'> Packer Inquiry </a></td></tr>";
$body .= "</table></body></html> ";
$headers = "MIME-Version: 1.0" . "\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\n";
$headers .= $from . "\n";
$headers .= 'Reply-To: '.$email0."\n";
//mail($emails,'New Packer Inquiry #'.$a[0].' :: '.$a[1],$body,$headers);
$subject = 'New Packer Inquiry #'.$a[0].' :: '.$a[1];
require_once 'vtlib/Vtiger/Mailer.php';
global $HELPDESK_SUPPORT_EMAIL_ID;
$mailer = new Vtiger_Mailer();
$mailer->IsHTML(true);
$mailer->ConfigSenderInfo($from, $name);
$mailer->Subject =$subject;
$mailer->Body = $body;

$mailer->AddAddress($email0);
for ($i=0;$i<$num;$i++) {
$emails = $adb->fetch_array($b)['email1'];
$mailer->AddAddress($emails);
}

$mailer->AddCC('supervisors@globalinklogistics.com');
$mailer->AddCC('erp.support@globalinklogistics.com');
$status = $mailer->Send(true);

}
?>