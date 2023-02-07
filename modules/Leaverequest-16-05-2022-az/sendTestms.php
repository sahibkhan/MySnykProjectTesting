<?php
  error_reporting(E_ALL);
	ini_set('display_errors', 1);


	require_once "class.phpmailer.php";
	$e_body = 'Test';
	$e_subject = ' The test record 123 ';
	//$e_toemail = 'r.gusseinov@globalinkllc.com';
	//$e_toemail = 's.aftab@globalinklogistics.com';
	$e_toemail = 's.mehtab@globalinklogistics.com';

	//Create a new PHPMailer instance
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
	//$mail->IsHTML(true);
	//Set who the message is to be sent from
	$mail->setFrom('erp.support@globalinklogistics.com', 'GEMS');
	//Set an alternative reply-to address
	//$mail->addReplyTo('replyto@example.com', 'First Last');
	//Set who the message is to be sent to
	$mail->addAddress($e_toemail, 'GEMS');
	//$mail->addBCC("s.aftab@globalinklogistics.com", 'Ruslan');
	//Set the subject line
	$mail->Subject = $e_subject;
	//Read an HTML message body from an external file, convert referenced images to embedded,
	//convert HTML into a basic plain-text alternative body
	$mail->msgHTML('');
	//Replace the plain text body with one created manually
	//$mail->AltBody = 'This is a plain-text message body';
	$mail->Body = '<p>'.$e_body.'</p>';	
	//Attach an image file
	//$mail->addAttachment('images/phpmailer_mini.png');
	//send the message, check for errors
	if (!$mail->send()) {
		//echo "Mailer Error: " . $mail->ErrorInfo;
	echo '<div align="center" style="margin-top: 20px; margin-bottom: -20px;color:red;" 
			class="alert alert-error " id="contactError">
			<strong>Error!</strong> There was an error sending your Mail.
		 </div>';
	} else {
		echo 'Sent ok ...';
	}


	

/* 

 	$to_email = 'r.gusseinov@globalinklogistics.com';

	// the message
	$msg = "First line of text\nSecond line of text";

	// use wordwrap() if lines are longer than 70 characters
	//$msg = wordwrap($msg,70);

	// send email
	if (mail($to_email,"My subject",$msg)){
		echo 'send!!!!!';
	}
 *//*


 	$to      = 'r.gusseinov@globalinklogistics.com';
	$subject = 'the subject';
	$message = 'hello';
	$headers = 'From: r.gusseinov@mail.ru' . "\r\n" .
			'Reply-To: webmaster@example.com' . "\r\n" .
			'X-Mailer: PHP/' . phpversion();

	$r = mail($to, $subject, $message, $headers);
	echo 'result = '.$r;
*/

?>
