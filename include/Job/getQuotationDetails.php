<?php
  require_once('../custom_connectdb.php'); // Подключение к базе данных
  require_once('../Vtiger/crm_data_arrange.php');

  if (isset($_REQUEST['record'])){
    $record = $_REQUEST['record'];
  }
  
  if (isset($_REQUEST['field_type'])){
    $field_type = $_REQUEST['field_type'];
  }
  
					// Get Quotation name
  if ($field_type == 'quotation_name'){
     $value = get_quotes_details($record,'subject');
	 echo $value;
  }
  
					// Get Quotation ID
  if ($field_type == 'quotation_id'){
     $value = get_quotes_details($record,'quoteid');
	 echo $value;
  }
  
  
  
  
  /*

function parse_links($str)
{
    $str = str_replace('https:', 'http:', $str);
	$str = str_replace('www.', 'http://www.', $str);
    $str = preg_replace('|http://([a-zA-Z0-9-./]+)|', '<a href="http://$1" target="_blank">$1</a>', $str);
    $str = preg_replace('/(([a-z0-9+_-]+)(.[a-z0-9+_-]+)*@([a-z0-9-]+.)+[a-z]{2,6})/', '<a href="mailto:$1">$1</a>', $str);
    return $str;
}

if (isset($_REQUEST['type'])){
    $type = $_REQUEST['type'];
}

if ($type == 'subject'){
    $subject = get_quotes_details($record,'subject');
    echo 'Globalink_'.$subject;
}
else
    if ($type == 'emails'){
        $contactid = get_quotes_details($record,'contactid');
        $email = get_contact_details($contactid,'email');
        echo $email;
    }
    else
        if ($type == 'cc'){
            $smcreatorid = get_crmentity_details($record,'smcreatorid');
            $email = get_user_details($smcreatorid,1);
            echo $email;
        }
        else
            if ($type == 'description'){
                $contactid = get_quotes_details($record,'contactid');
                $s = get_contact_details($contactid,'salutation');
                $fistname = get_contact_details($contactid,'firstname');
                $lastname = get_contact_details($contactid,'lastname');

                // Creator details
                $smcreatorid = get_crmentity_details($record,'smcreatorid');

                //$smcreatorid = $current_id_user;

                $creator_name = get_user_details($smcreatorid,2);
                //$creator_name = $user_name;
                $creator_title = get_user_details($smcreatorid,4);

                $creator_city = get_user_details($smcreatorid,5);
                $branch_tel = get_branch_details($creator_city,'tel');
                $creator_email = get_user_details($smcreatorid,1);

                $name = $s . ' ' . $fistname . ' ' . $lastname;
                $signature .= '
	<div class="best-regards-block">
	<p>Thank you and Best Wishes,<br />
	'.$creator_name.'<br />
	'.$creator_title.'<br />
	Globalink Logistics Group
	<p>

	Tel:'.$branch_tel.'<br />
	E-mail: '.$creator_email.'<br />
	Web Site: www.globalink.info</p>
	</div>';

                $email_body = "Dear $name <br> Please find our quotation as attachment <br/> <br/> ";
                $email_body .= $signature;


                echo $email_body;
            }
            else
                if ($type == 'to'){
                    $contactid = get_quotes_details($record,'contactid');
                    $email = get_contact_details($contactid,'email');
                    $value = '["'.$email.'"]';

                    echo $value;
                }
                else
                    if ($type == 'selected_ids'){
						$smownerid = get_crmentity_details($record,'smownerid');
						$value = '["'.$smownerid.'"]';						
                        echo $value;
                    }
                    else
                        if ($type == 'toemailinfo'){
                            $contactid = get_quotes_details($record,'contactid');
                            $email = get_contact_details($contactid,'email');
                            $value = '{"'.$contactid.'":["'.$email.'"]}';
                            echo $value;
                        }
                        else
                            if ($type == 'general_remarks'){
                               $text = get_crmentity_details($record,'description');
								$text1 = parse_links($text);
                                
								echo $text1;
                            }
							
				*/			
?> 