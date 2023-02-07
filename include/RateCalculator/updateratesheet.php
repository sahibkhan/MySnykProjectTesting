<?php
	// rate sheet update for handling
	include ('ratecalculatorfunc.php');
	//var_dump($_POST);
	$db = new query();
	echo "Already done"; exit;
	$sql = "select * from ratesheethandling";
	$rs = $db->getData($sql);
	
	//print_r($rs);
	echo "Start checking";
	if(count($rs)>0) //if records founds direct rout
    {
		foreach($rs as $r)
        {
			$ratetype = $r["rate_type"];
			$loadingport =$r["port_of_loading"];
			$dischargeport =$r["port_of_discharge"];
			$conttype = $r["container_type"];
			$handling = $r["handling"];
			
			$sqlsrch = "select * from vtiger_ratecalculatorcf 
			WHERE rate_type='$ratetype' and port_of_loading='$loadingport'
			AND port_of_discharge='$dischargeport' and container_type='$conttype'
			AND ratecalculatorid in (select crmid from vtiger_crmentity where deleted=0 AND setype='RateCalculator')";
			//echo "<br>SQL ".$sqlsrch; exit;
			$srs = $db->getData($sqlsrch);
			
			if(count($srs)>0)
			{
				// update main table
				$sid = $srs[0]["ratecalculatorid"];
				$qryupdate = "UPDATE vtiger_ratecalculatorcf 
				SET handling='$handling' where ratecalculatorid= $sid";
				if($db->updateData($qryupdate))
				{
					echo "<br>Found ".$sid." $handling";
				}
				
				
			}
			else
			{
				echo "<br>Not found ".$sqlsrch;
			}
		}
	}
	else
	{
		echo "No Record or all record done";
	}
	echo "<br> End process";

?>