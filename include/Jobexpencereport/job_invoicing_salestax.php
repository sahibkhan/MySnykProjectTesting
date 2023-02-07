<?php
//echo "sale tax invoice";
//die();
/*
$firstHtmlString = '<table>
                  <tr>
                      <td>Hello World</td>
                  </tr>
              </table>';
$secondHtmlString = '<table>
                  <tr>
                      <td>Hello World</td>
                  </tr>
              </table>';

$reader = new \PhpOffice\PhpSpreadsheet\Reader\Html();
$spreadsheet = $reader->loadFromString($firstHtmlString);
$reader->setSheetIndex(1);
$spreadhseet = $reader->loadFromString($secondHtmlString, $spreadsheet);

$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');
$writer->save('write.xls');




phpinfo();
die(); 
*/




	chdir(dirname(__FILE__) . '/../..');
	include_once 'vtlib/Vtiger/Module.php';
	include_once 'includes/main/WebUI.php';
	include_once 'include/Webservices/Utils.php';
    set_time_limit(0);
	date_default_timezone_set("UTC");	
	ini_set('memory_limit','64M');
	global $adb;
@session_start();
$sellingids = $_REQUEST['sellingids'];

$_SESSION['invoice_instruction_no'] = '';
$s_generate_invoice_instruction_flag = true;
$invoice_instruction = '';
$job_id = (int) $_REQUEST['job_id'];

$selling_arr = explode(',', $sellingids);
//print_r($selling_arr);



foreach($selling_arr as $key => $selling)
{
	if(!empty($selling))
	{

	$jrer_selling_invoice_count = $adb->pquery('select COUNT(*) as total_invoice, vtiger_jobexpencereportcf.cf_1250 from vtiger_jobexpencereportcf 
										INNER JOIN 	vtiger_jobexpencereport on vtiger_jobexpencereport.jobexpencereportid = vtiger_jobexpencereportcf.jobexpencereportid
										where 
										vtiger_jobexpencereportcf.cf_1457="Selling" 
										AND vtiger_jobexpencereport.job_id="'.$job_id.'" 
										AND vtiger_jobexpencereportcf.jobexpencereportid="'.(int) $selling.'"');

		$data=$adb->fetch_array($jrer_selling_invoice_count);
	
		if($data['total_invoice']==1 && $data['cf_1250']=='Approved')
		{
			$update_selling_ids[] = (int) $selling;					
				
		}
		
	}
		
}

if ( count($update_selling_ids) > 0 ) {
	set_tax_invoice($adb,$update_selling_ids);
	echo count($update_selling_ids);
}

function set_tax_invoice($adb,$update_selling_ids) {
	// get max seq of current year
	$dbinv_seq = $adb->pquery("select max(SUBSTRING_INDEX(SUBSTRING_INDEX(invoice_tax,'-',-1),'/',1) ) inv_seq FROM vtiger_jobexpencereport where invoice_tax != '' and SUBSTRING_INDEX(invoice_tax,'/',-1)=".date('y')."");
			$dbseq=$adb->fetch_array($dbinv_seq);
			
			if (count($dbseq) > 0) {
				$seq = $dbseq['inv_seq'];
			} else {
				$seq = '000';
			}			

				if ($seq < 999) {
					$seq++;
					$seq = str_pad($seq, 3, '0', STR_PAD_LEFT);
				} else {
					$seq++;
				}

				$invoice_tax = 'DXB-VT-'.$seq.'/'.date('y');				
				$selling_ids = implode(",", $update_selling_ids);

				$adb->pquery("update vtiger_jobexpencereport set 						
							vtiger_jobexpencereport.invoice_tax='".$invoice_tax."'						
							where vtiger_jobexpencereport.jobexpencereportid IN (".$selling_ids.")");
}
//--------------------------create excel--------------------------
/*
$firstHtmlString = '<table>
                  <tr>
                      <td>Hello World</td>
                  </tr>
              </table>';
$secondHtmlString = '<table>
                  <tr>
                      <td>Hello World</td>
                  </tr>
              </table>';

$reader = new \PhpOffice\PhpSpreadsheet\Reader\Html();
$spreadsheet = $reader->loadFromString($firstHtmlString);
$reader->setSheetIndex(1);
$spreadhseet = $reader->loadFromString($secondHtmlString, $spreadsheet);

$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');
$writer->save('write.xls');
*/







return true;
?>