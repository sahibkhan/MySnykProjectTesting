<?php
	//ini_set('display_errors','on'); version_compare(PHP_VERSION, '5.5.0') <= 0 ? error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED) : error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);   // DEBUGGING
	date_default_timezone_set('Asia/Karachi');
	include_once("/var/www/html/include/Leaverequest/leaverequestfunc.php");
	
	if(!isset($_GET['e']))
	{
		die("Invalid Use");
	}

	$qs = $_GET["e"];

	//echo $qs;
	//echo "<br> dec ";
	$ds = decryptIt($qs);
	//echo $ds;
	$a = explode(",",$ds);
	//print_r($a);
	$status = $a[0];
	$from = $a[1];
	$rid = $a[2];
	
	$cusrid ="";
	if(isset($a[3]))
	{
		$cusrid = $a[3];
	}
	
	
	
	
	/*if($from=="HR Head")
	{
		$from="HR";
	}*/
	
	
	
	/*
	$status = $_GET["st"];
	$from = $_GET["from"];
	$rid = $_GET["rid"];
	*/

	//echo $status,$from,$rid," uid ".$cusrid;
	//exit;
	
	


	//check if already approved/reject
	$sqllr = "SELECT * FROM vtiger_leaverequestcf WHERE leaverequestid='".$rid."'";
	//echo $sqllr;
	$rslr = getrs($sqllr);
	$rlr = mysqli_fetch_assoc($rslr);
	
	$hdaprv = $rlr['cf_3411'];
	$hdaprvdt = $rlr['cf_3413'];
	$pusrid = $rlr['cf_3423'];
	$lvcrtdt = $rlr["cf_3413"];
	$hraprv = $rlr['cf_3415'];
	$hraprvdt = $rlr['cf_3417'];
	
	$fdaprv = $rlr['cf_6618'];
	$fdaprvdt = $rlr['cf_6620'];
	$locid=$rlr["cf_3469"];
	$locsname = getLocationShortName($locid);
	$locLongName = getLocationLongName($locid);
	// get leave request date
	$sqlcd = "SELECT * FROM vtiger_crmentity where crmid='".$rid."'";
	//echo $sqllr;
	$rscd = getrs($sqlcd);
	$rcd = mysqli_fetch_assoc($rslr);
	$created_date=date_format(date_create($rcd["createdtime"]),"D d F Y");
	
	
	
	
	//$lvfrom = date_format(date_create($rlr["cf_3391"]),"D d F Y");
	//$lvto = date_format(date_create($rlr["cf_3393"]),"D d F Y");
	$lvfrom = date_format(date_create($rlr["cf_3391"]),"D jS F Y");
	$lvto = date_format(date_create($rlr["cf_3393"]),"D jS F Y");

	//days diff count
	$sdt = strtotime($rlr["cf_3391"]);
	$edt = strtotime($rlr["cf_3393"]);
	$lvdays = round(($edt - $sdt)/(60*60*24))+1;

	$lvtype = $rlr["cf_3409"];
	
	
	//getting manager info
	$sqlmgr = "SELECT * FROM `vtiger_userlistcf` where userlistid='".$pusrid."'";
	
	$rsmgr = getrs($sqlmgr);
	$rmgr = mysqli_fetch_assoc($rsmgr);
	$empemail = $rmgr["cf_3355"];
	$usremail = $rmgr["cf_3355"];
	$empno = $rmgr["cf_4799"];
	$emppaiddays = $rmgr["cf_3433"];
	$empunpaiddays = $rmgr["cf_3473"];
	$empdept = $rmgr["cf_3349"];
	$empdeptname = getDeptName($empdept);
	$empdeptsname = getDeptShortName($empdept);
	$empgmid = $rmgr["cf_3385"];
	$emphdid = $rmgr["cf_3387"];
	
	$emp_leaves_days = $rmgr["cf_3429"];
	$emp_remaining_leaves = $rmgr["cf_3377"];
	
	//getting creator user id
	$sqlusr = "SELECT * FROM vtiger_crmentity where crmid='".$rid."'";
	$rsusr = getrs($sqlusr);
	$rusr = mysqli_fetch_assoc($rsusr);
	$usrid = $rusr["smcreatorid"];
	
	//getting creator user email
	$usrlstid =getUserListId($usrid);
	$creator_user_email = getHeadEmail($usrlstid);
	
	
	//getting user email
	$sqlusr = "SELECT * FROM vtiger_users where email1='".$usremail."'";
	$rsusr = getrs($sqlusr);
	$rusr = mysqli_fetch_assoc($rsusr);
	//$usremail = $rusr["email1"];
	$username = $rusr["first_name"]." ".$rusr["last_name"];
	
	$lvunqid = $locsname."-".$empdeptsname."-".$rid."/".date_format(date_create($lvcrtdt),"y");
				
	
	
	// getting user manager info
	$gmemail = getHeadEmail($empgmid);		
	$gmname = getHeadName($gmemail);
	
	// getting user head info
	$hdemail = getHeadEmail($emphdid);		
	$hdname = getHeadName($hdemail);
	
	
	//check is useremail not exist (not gems user)
	if($usremail=="")
	{
		$usremail=$creator_user_email;
		$empemail = $creator_user_email;
		
		
		//getting user name
		$sqlusr2 = "SELECT * FROM vtiger_userlist where userlistid='".$pusrid."'";
		$rsusr2 = getrs($sqlusr2);
		$rusr2 = mysqli_fetch_assoc($rsusr2);
		//$usremail = $rusr["email1"];
		$username = $rusr2["name"];
	}
	
	
	//echo "GM ",$gmname,$gmemail,"<br>";
	//echo "HD ",$hdname,$hdemail,"<br>";
	// getting hr group email
	$hremail = getHREmail();				
	//$hremail = "a.naseem@globalinklogistics.com"; // for testing
	$hrname = "HR Department";
	//getting hr head info
	$hrhdid = getHRHeadUserID();
	$hrhdemail = getHeadEmail($hrhdid);		
	$hrhdname = getHeadName($hrhdemail);
	
	$fdemail = getFDEmail();
	$fdname = getFDName();
	
	//$hremail ="a.naseem@globalinklogistics.com";
	//$empemail = $hremail; // temp for testing
	
	
	//if user id exist // from button
	$cusremail="";
	$cusrname="";
	if($cusrid!="")
	{
		$cusrlstid = getUserListId($cusrid);
		$cusremail = getHeadEmail($cusrlstid);		
		$cusrname = getHeadName($cusremail);
		if($cusremail==$gmemail)
		{
			$from="Head";
			
		}
		else
		{
			$hrname=$cusrname;
			$hremail= $cusremail;
			$from="HR";
			
		}
	}
	
	
	//echo $cusremail." ".$cusrname;
	//echo "from ".$from. " status ".$status." hr aprdate ".$hraprvdt;
	//exit;
	$record_link = "https://gems.globalink.net/index.php?module=Leaverequest&view=Detail&record=".$rid;
	
	$txt = "<p>[ApproveMessage]</p>";
	//$txt .= "<p>Require your approval.</p>";
	$txt .= '<h3 style="background-color:#f47824;color:white; margin-top: 0; margin-bottom: 0; font-weight: 500; vertical-align: center; font-size: 20px; padding: 3px;" align="left">Leave Application</h3>';
	$txt .= "<table style='width:100%'>";
	$txt .= "<tr  ><td style='border-bottom:1px solid  #abb2b9;'>Leave Ref.#      : </td><td style='border-bottom:1px solid  #abb2b9;'><b>".$lvunqid."</b></td></tr>";
	$txt .= "<tr><td style='border-bottom:1px solid  #abb2b9;'>Employee Name    : </td><td style='border-bottom:1px solid  #abb2b9;'><b>".$username."</b></td></tr>";
	$txt .= "<tr><td style='border-bottom:1px solid  #abb2b9;'>Employee Number  : </td><td style='border-bottom:1px solid  #abb2b9;'><b>".$empno."</b></td></tr>";
	if($locLongName !="Almaty")
	{
		$txt .= "<tr><td style='border-bottom:1px solid  #abb2b9;'>Location         : </td><td style='border-bottom:1px solid  #abb2b9;'><b>".$locLongName."</b></td></tr>";
	}		
	$txt .= "<tr><td style='border-bottom:1px solid  #abb2b9;'>Department       : </td><td style='border-bottom:1px solid  #abb2b9;'><b>".$empdeptname."</b></td></tr>";
	$txt .= "<tr><td style='border-bottom:1px solid  #abb2b9;'>Leave Request Id : </td><td style='border-bottom:1px solid  #abb2b9;'><b>  ".$rid."<a href='".$record_link."' target='_blank'> Click here to see details </a>"."</b></td></tr>";
	$txt .= "<tr><td style='border-bottom:1px solid  #abb2b9;'>Type of Leave    : </td><td style='border-bottom:1px solid  #abb2b9;'><b>".$lvtype."</b></td></tr>";
	$txt .= "<tr><td style='border-bottom:1px solid  #abb2b9;'>Start Date       : </td><td style='border-bottom:1px solid  #abb2b9;'><b>".$lvfrom."</b></td></tr>";
	$txt .= "<tr><td style='border-bottom:1px solid  #abb2b9;'>End Date         : </td><td style='border-bottom:1px solid  #abb2b9;'><b>".$lvto."</b></td></tr>";
	$txt .= "<tr><td style='border-bottom:1px solid  #abb2b9;'>Leave Requested For: </td><td style='border-bottom:1px solid  #abb2b9;'><b>".$lvdays." Day(s)</b></td></tr>";
	$txt .= "<tr><td style='border-bottom:1px solid  #abb2b9;'>Status           : </td><td style='border-bottom:1px solid #abb2b9;'>[ApprovalStatus]</td></tr>";
	//$txt .= "<tr><td>Balance Summary </td><td><b></td></tr>";
	//$txt .= "<tr><td>Approvel For Extention        : </td><td><b></b></td></tr>";
	//$txt .= "<tr><td>Eligiblle Entitile for this year: </td><td><b></b></td></tr>";
	//$txt .= "<tr><td>Leave Approved  : </td><td></td><b></b></tr>";
	$txt .= "</table>";
	
	

		
	$hdaprvmsg = "";
	$hraprvmsg = "";
	$msg ="";
	$tdt = date("Y-m-d");
	$tdtd = date("D d F Y");
	$arname="";
	
	$sendemail="";
	
	
	$fromEmail="";
	$fromName="";
	// for head 
	if($from=="Head")
	{
		$fromEmail = $hdemail;
		$fromName=$hdname;
		if($status=="a" && $hdaprvdt=="")
		{
			$arname = $gmname;
			$msg = $fromName. " Approve Leave on ".$tdtd;
			$sendemail="Y";
			
		}
		
		if($status=="r" && $hdaprvdt=="")
		{
			$arname = $gmname." (Cancel)";
			$msg = $fromName. " Cancel Leave on ".$tdtd;
			$sendemail="Y";
		}
		
		if($hdaprvdt!="")
		{
			$iscn = strpos($hdaprv,"Cancel");
			if($iscn>0)
			{
				$msg = "Already Cancel by ".$hdaprv." on ".$hdaprvdt;
			}
			else
			{
				$msg = "Already Approved by ".$hdaprv." on ".$hdaprvdt;
			}
		}	
		
		if($sendemail=="Y")
		{
			$sqlup = "update vtiger_leaverequestcf set cf_3411='".$arname."',cf_3413='".$tdt."' ";
			$sqlup .= " where leaverequestid='".$rid."'";
			$rsup = getrs($sqlup);
		}
	}
	
	
	// for HR
	if($from=="HR")
	{	
		$fromEmail = $hremail;
		$fromName =$hrname;
		if($status=="a" && $hraprvdt=="")
		{
			$arname = $hrname;
			$msg = $fromName. " Approve Leave on ".$tdtd;
			$sendemail="Y";
		}
		
		if($status=="r" && $hraprvdt=="")
		{
			$arname = $hrname." (Cancel)";
			$msg = $fromName. " Cancel Leave on ".$tdtd;
			$sendemail="Y";
		}
		
		if($hraprvdt!="")
		{
			$iscn = strpos($hraprv,"Cancel");
			if($iscn>0)
			{
				$msg = "Already Cancel by ".$hraprv." on ".$hraprvdt;
			}
			else
			{
				$msg = "Already Approved by ".$hraprv." on ".$hraprvdt;
			}
		}
		
		if($sendemail=="Y")
		{
			$sqlup = "update vtiger_leaverequestcf set cf_3415='".$arname."',cf_3417='".$tdt."' ";
			$sqlup .= " where leaverequestid='".$rid."'";
			$rsup = getrs($sqlup);
		}
	}
	
	// for HR Head
	if($from=="HR Head")
	{	
		$fromEmail = $hrhdemail;
		$fromName = $hrhdname;
		if($status=="a" && $hraprvdt=="")
		{
			$arname = $hrhdname;
			$msg = $fromName. " Approve Leave on ".$tdtd;
			$sendemail="Y";
		}
		
		if($status=="r" && $hraprvdt=="")
		{
			$arname = $hrhdname." (Cancel)";
			$msg = $fromName. " Cancel Leave on ".$tdtd;
			$sendemail="Y";
		}
		
		if($hraprvdt!="")
		{
			$iscn = strpos($hraprv,"Cancel");
			if($iscn>0)
			{
				$msg = "Already Cancel by ".$hraprv." on ".$hraprvdt;
			}
			else
			{
				$msg = "Already Approved by ".$hraprv." on ".$hraprvdt;
			}
		}
		
		if($sendemail=="Y")
		{
			$sqlup = "update vtiger_leaverequestcf set cf_3415='".$arname."',cf_3417='".$tdt."' ";
			$sqlup .= " where leaverequestid='".$rid."'";
			$rsup = getrs($sqlup);
		}
	}
	
	
	
	
	// for FD
	
	if($from=="FD Head")
	{
		$fromEmail = $fdemail;
		$fromName = $fdname;
		if($status=="a" && $fdaprvdt=="")
		{
			$arname = $fdname;
			$msg = $fromName. " Approve Leave on ".$tdtd;
			$sendemail="Y";
		}
		
		if($status=="r" && $fdaprvdt=="")
		{
			$arname = $fdname." (Cancel)";
			$msg = $fromName. " Cancel Leave on ".$tdtd;
			$sendemail="Y";
		}
		
		if($fdaprvdt!="")
		{
			$iscn = strpos($fdaprv,"Cancel");
			if($iscn>0)
			{
				$msg = "Already Cancel by ".$fdaprv." on ".$fdaprvdt;
			}
			else
			{
				$msg = "Already Approved by ".$fdaprv." on ".$fdaprvdt;
			}
		}
		
		if($sendemail=="Y")
		{
			$sqlup = "update vtiger_leaverequestcf set cf_6618='".$arname."',cf_6620='".$tdt."' ";
			$sqlup .= " where leaverequestid='".$rid."'";
			$rsup = getrs($sqlup);
		}
		
	}
	
	
	if($fromEmail=="")  // this shoudnt true
	{
		$fromEmail=$cusremail;
	}
	
	//$header = "From:hr@globalinklogistics.com \r\n";
	$header = "From:".$fromEmail." \r\n";
	$header .= "Cc:s.mehtab@globalinklogistics.com \r\n";
	$header .= "MIME-Version: 1.0\r\n";
	$header .= "Content-type: text/html\r\n";
	
	
	//getting current status of leave
	$sqllr = "SELECT * FROM vtiger_leaverequestcf WHERE leaverequestid='".$rid."'";
	//echo $sqllr;
	$rslr = getrs($sqllr);
	$rlr = mysqli_fetch_assoc($rslr);
	
	$hdaprv = $rlr['cf_3411'];
	$hdaprvdt = $rlr['cf_3413'];
	$lvcrtdt = $rlr["cf_3413"];
	$hraprv = $rlr['cf_3415'];
	$hraprvdt = $rlr['cf_3417'];
	
	$fdaprv = $rlr['cf_6618'];
	$fdaprvdt = $rlr['cf_6620'];
	
	$cstatus = "<table style='width:100%;'>";
	$cstatus .= "<tr><td>Leave Created On : ".$created_date."</td></tr>";
	if($hdaprv=="")
	{
		$cstatus .= "<tr><td><span style='color:red;'>Head Approval Is Pending</span></td></tr>";
	}
	else
	{
		$cstatus .= "<tr><td><span style='color:green;'>".$hdaprv." Approved on ".date_format(date_create($hdaprvdt),"D d F Y") . "</span></td></tr>";
	}
	
	if($hraprv=="")
	{
		$cstatus .= "<tr><td><span style='color:red;'>HR Approval Is Pending</span></td></tr>";
	}
	else
	{
		$cstatus .= "<tr><td><span style='color:green;'>".$hraprv." Approved on ".date_format(date_create($hraprvdt),"D d F Y") . "</span></td></tr>";
	}
	
	//check is leave request required fd approval
	// For FD Approval in case of FD user
	$sqlfd = "SELECT vtiger_users.email1
						  FROM `vtiger_users2group` 
						  INNER JOIN vtiger_users ON vtiger_users.id = vtiger_users2group.userid		
						  WHERE vtiger_users2group.`groupid` = 1298 and vtiger_users.email1='".$empemail."'";
	$rsfd = getrs($sqlfd);
	$rfd = mysqli_fetch_assoc($rsfd);
	if($rfd)
	{
		if($fdaprv=="")
		{
			$cstatus .= "<tr><td><span style='color:red;'>FD Approval Is Pending</span></td></tr>";
		}
		else
		{
			$cstatus .= "<tr><td><span style='color:green;'>".$fdaprv." Approved on ".date_format(date_create($fdaprvdt),"D d F Y") . "</span></td></tr>";
		}
	}
	else
	{
		$cstatus .= "<tr><td><span style='color:pink'>FD Approval Not Required</span></td></tr>";
	}
	$cstatus .= "</table>";
	
	//echo $cstatus;
	//exit;
	
	/// creating email msg
	//$txt .= "<br><h1>".$msg."<h1/>";
	$message = getmsgbody();
	
	$txt = str_replace("[ApproveMessage]",$msg,$txt);
	$txt = str_replace("[ApprovalStatus]",$cstatus,$txt);
	
	$approval_status = "Leave Request Created On : ";
	
	
	$txtBody = "<p>Dear <b>".$username.",</b><br></p>";
	$txtBody .= $txt;
		//$txt .= "<br><small>".$ToCopy."</small><br>";
	$txtBody .= "<br>";
		
	$message = str_replace("[LeaveTextBody]",$txtBody,$message);
	
	//remove button row
	$message = str_replace("[LeaveAppHeading]","",$message);
	$message = str_replace("[LeaveAppSubHeading]","",$message);
	$srch ='<tr id="btnRow">

				  <td style="border-spacing: 0px; border-collapse: collapse; line-height: 24px; font-size: 16px; border-radius: 4px; margin: 0;" align="center" bgcolor="#007bff">

					<a href="[AcceptURL]" style="display:none;font-size: 16px; font-family: sans-serif !important; text-decoration: none; border-radius: 4px; line-height: 20px; display: inline-block; font-weight: normal; white-space: nowrap; background-color: #007bff; color: #ffffff; padding: 8px 12px; border: 1px solid #007bff;">Accept Leave Application</a>

				  </td>
				<td>&nbsp;&nbsp;</td>
					
					<td style="border-spacing: 0px; border-collapse: collapse; line-height: 24px; font-size: 16px; border-radius: 4px; margin: 0;" align="center" bgcolor="#dc3545">
					
					<a href="[RejectURL]" style="display:none;font-size: 16px; font-family: sans-serif !important; text-decoration: none; border-radius: 4px; line-height: 20px; display: inline-block; font-weight: normal; white-space: nowrap; background-color: #dc3545; color: #ffffff; padding: 8px 12px; border: 1px solid #dc3545;">Reject Leave Application</a>
					
					
				</tr>';
	$message = str_replace($srch,"",$message);
	
	//echo $message;
	//exit;
	
				
	


	


	//echo "<br>";
	//echo $header;
	

	//echo $message;
	//exit;


	//sending email to user
	$sendmsg="";
	if($sendemail=="Y")
	{
		/*
		$empemail="a.naseem@globalinklogistics.com";
		$msg.=" (".$lvunqid.")";
		$retval = mail ($empemail,$msg,$message,$header);
		if( $retval == true ) {
			$sendmsg= "Message sent to user successfully...";
		}else {
			$sendmsg= "Message could not be sent to user...";
		}
		*/
		//New email code:: SMTP
		require_once "class.phpmailer.php";
		$mail = new PHPMailer;
		$mail->isSMTP();
		$mail->Host = 'mail.globalink.world';
		$mail->Port = 587;
		//Whether to use SMTP authentication
		$mail->SMTPAuth = true;
		$mail->Username = 'vtiger';
		//Password to use for SMTP authentication
		$mail->Password = 'VT_glk@2021#';
		$mail->CharSet = 'UTF-8';
		// Set PHPMailer to use the sendmail transport
		$mail->isSendmail();
		$mail->IsHTML(true);
		//Set who the message is to be sent from
		$mail->setFrom($fromEmail, $fromName);
		//Set an alternative reply-to address
		//$mail->addReplyTo('replyto@example.com', 'First Last');
		//Set who the message is to be sent to
		$mail->addAddress($empemail, $username);
		$mail->AddCC('erp.support@globalinklogistics.com', 'GEMS Support');
		
		//$mail->addBCC("s.aftab@globalinklogistics.com", 'Ruslan');
		//Set the subject line
		$mail->Subject = $msg;
		//Read an HTML message body from an external file, convert referenced images to embedded,
		//convert HTML into a basic plain-text alternative body
		$mail->msgHTML('');
		//Replace the plain text body with one created manually
		//$mail->AltBody = 'This is a plain-text message body';
		$mail->Body = $message;	

		//$retval = mail ($empemail,$msg,$message,$header);
		if (!$mail->send()) {
			$sendmsg= "Message could not be sent to user...";			
		}else {
			$sendmsg= "Message sent to user successfully...";
		}
	}
	$stmsg= $msg;

	
	

	
?>


<!doctype html>
<html lang="en" class="h-100">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Azhar Ul Haq">
    <meta name="generator" content="Hugo 0.79.0">
    <title>Globalink Logistics - Leave Request</title>

    <link rel="canonical" href="https://getbootstrap.com/docs/5.0/examples/cover/">

    

    <!-- Bootstrap core CSS -->
<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

    <style>
		/*
		 * Globals
		 */


		/* Custom default button */
		.btn-secondary,
		.btn-secondary:hover,
		.btn-secondary:focus {
		  color: #333;
		  text-shadow: none; /* Prevent inheritance from `body` */
		}


		/*
		 * Base structure
		 */

		body {
		  text-shadow: 0 .05rem .1rem rgba(0, 0, 0, .5);
		  box-shadow: inset 0 0 5rem rgba(0, 0, 0, .5);
		}

		.cover-container {
		  max-width: 42em;
		}


		/*
		 * Header
		 */

		.nav-masthead .nav-link {
		  padding: .25rem 0;
		  font-weight: 700;
		  color: rgba(255, 255, 255, .5);
		  background-color: transparent;
		  border-bottom: .25rem solid transparent;
		}

		.nav-masthead .nav-link:hover,
		.nav-masthead .nav-link:focus {
		  border-bottom-color: rgba(255, 255, 255, .25);
		}

		.nav-masthead .nav-link + .nav-link {
		  margin-left: 1rem;
		}

		.nav-masthead .active {
		  color: #fff;
		  border-bottom-color: #fff;
		}
	
	
      .bd-placeholder-img {
        font-size: 1.125rem;
        text-anchor: middle;
        -webkit-user-select: none;
        -moz-user-select: none;
        user-select: none;
      }

      @media (min-width: 768px) {
        .bd-placeholder-img-lg {
          font-size: 3.5rem;
        }
      }
    </style>

    
    
  </head>
  <body class="d-flex h-100 text-center text-white bg-dark">
    
	<div class="cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">
	 
	  <main class="px-3">
		<div class="pull-right">
		<img src="https://globalinklogistics.com/wp-content/uploads/2020/07/logo.png"  width="300px" class="img-thumbnail" />
		</div>
	  
	  
		<h1>Leave Request to <?php echo strtoupper($from); ?></h1>
		<h3>Leave Id <?php echo $lvunqid; ?></h3>
		<p class="lead">Request From : <b> <?php echo $username; ?> </></p>
		<br>
		<p><?php echo $lvtype; ?>
		<br>
		<p>From : <?php echo $lvfrom; ?> To : <?php echo $lvto; ?>
		<br>
		<h4><?php echo $stmsg; ?></h4>
		<h6><?php echo $sendmsg; ?></h6>
		
		<p class="lead">
		  <a href="#" class="btn btn-lg btn-secondary fw-bold" onclick="window.close()">Close This</a>
		</p>
		
		<?php //echo $txt; ?>
		
	  </main>

	  <footer class="mt-auto text-white-50">
		<p>Globalink Logistics</p>
	  </footer>
	</div>


    
  </body>
</html>
