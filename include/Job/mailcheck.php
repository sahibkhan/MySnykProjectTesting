<?php
	$eol = PHP_EOL;
	$separator = md5(time());
	$filename = 'abc.pdf';
	$from = "From: mehtab<s.mehtab@globalinklogistics.com>";
						//$from = $current_user->get('email1');
						//$to = $job_user_info->get('email1');
						$to ='z.smelykh@globalinklogistics.com';
						//$cc = $current_user->get('email1').';g.moldakanova@globalinklogistics.com;s.mehtab@globalinklogistics.com;warehouse@globalinklogistics.com;';
						$cc= '';
						
						// main header
						$headers  = $from.$eol;
						$headers .= 'Reply-To: '.$to.'' .$eol;
						$headers .= "CC:" . $cc .$eol;
						$headers .= "MIME-Version: 1.0".$eol; 
						$headers .= "Content-Type: multipart/mixed; boundary=\"".$separator."\"".$eol;
						
						$body = "--".$separator.$eol;
						$body .= "Content-Type: text/html; charset=\"UTF-8\"".$eol;
						$body .= "Content-Transfer-Encoding: 7bit".$eol.$eol;
						//$body .= "This is a MIME<br> encoded message.".$eol;
						
						
						$body .="<p>Dear&nbsp; Zinaida,</p>".$eol;
						$body .="<p>Please issue below packaging material list for job file test.<br />".$eol;
						$body .="<br>Packaging Material Items.</p>".$eol;
						$body .='<table  border=1 cellspacing=0 cellpadding=5  width="100%"   ><tbody>
									<tr><td width="304"><strong>Packaging Ref #</strong></td>
										<td width="144"><strong>test</strong>
										</td><td width="323"><strong></strong>
										</td><td width="157"><strong>Warehouse ID</strong>
										</td><td width="356"><strong>test</strong>
										</td></tr>								
								</tbody>    
							</table>
							<br>
							<table border=1 cellspacing=0 cellpadding=5  width="100%"><tbody>
							<tr><td width="20"><strong>#</strong></td><td width="60"><strong>Type</strong></td><td width="60"><strong>Quantity
							</strong></td><td width="60"><strong>Requested Date</strong></td></tr>
							test
							</tbody>
							</table>'.$eol;
							
							$body .= "--".$separator.$eol;
						$body .= "Content-Type: application/pdf; name=\"".$filename."\"".$eol; 
						$body .= "Content-Transfer-Encoding: base64".$eol;
						$body .= "Content-Disposition: attachment".$eol.$eol;
						$body .= $attachment.$eol;
						$body .= "--".$separator."--";

$subject = "checking email format";
mail($to,$subject,$body,$headers);
?>