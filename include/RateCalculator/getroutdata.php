<?php
	//ini_set('display_errors','on'); version_compare(PHP_VERSION, '5.5.0') <= 0 ? error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED) : error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);   // DEBUGGING

	include ('ratecalculatorfunc.php');
	//var_dump($_POST);
	$db = new query();

	$origin = $_POST["origin"];
    $dest = $_POST["dest"];
    $ctype = $_POST["ctype"];
    $sdate = $_POST["sdate"];

	$origin=trim(substr($origin,0,strpos($origin, '(')-1));
	$dest=trim(substr($dest,0,strpos($dest, '(')-1));
	
	//echo "Origin ".$origin. " ".$dest; exit;

    //check is direct rout available
    $sqldr = "select * from vtiger_ratecalculatorcf where port_of_loading='$origin' and port_of_discharge='$dest' and container_type='$ctype' and validity_start_date >='$sdate' and validity_end_date >= '$sdate' and ratecalculatorid in (SELECT crmid from vtiger_crmentity WHERE deleted=0)";
	//validity_start_date <='16-02-2022' and validity_end_date >= '16-02-2022'
	
    //echo "<br>".$sqldr; exit;
    $drs = $db->getData($sqldr);
    if(count($drs)>0) //if records founds direct rout
    {
        //print_r($drs); exit;
        $opt=1;
        foreach($drs as $fr)
        {
			$handling = $fr["handling"];
			//saving main rout to variables
			if($opt==1)
			{
				?>
				<input id="fromport" type="hidden" value="<?php echo $fr["port_of_loading"]; ?>" />
				<input id="fromcountry" type="hidden" value="<?php echo $fr["origin_country"]; ?>" />
				<input id="toport" type="hidden" value="<?php echo $fr["port_of_discharge"]; ?>" />
				<input id="tocountry" type="hidden" value="<?php echo $fr["transit_country"]; ?>" />
				<?php
			}
			//print_r($fr); exit;
			
			$rates = "<table border='1' cellpadding='0' cellspacing='0' width='100%'>";
			$rates .= "<tr>";
			$rates .= "<th>";
			$rates .= "Option $opt";
			$rates .= "</th>";
			$rates .= "<th>";
			$rates .= "Container Type : ".$fr["container_type"];
			$rates .= "</th>";
			$rates .= "<th>";
			$rates .= "INCO TERMS : ".$fr["inco_terms"];
			$rates .= "</th>";
			$rates .= "</tr>";
			
			$rates .= "<tr>";
			$rates .= "<th>";
			$rates .= "";
			$rates .= "</th>";
			$rates .= "<th>";
			$rates .= "From";
			$rates .= "</th>";
			$rates .= "<th>";
			$rates .= "To";
			$rates .= "</th>";
			$rates .= "</tr>";
			
			
			$rates .= "<tr>";
			$rates .= "<th>";
			$rates .= "Validity";
			$rates .= "</th>";
			
			$rates .= "<td>";
			$rates .= $fr["validity_start_date"];
			$rates .= "</td>";
			$rates .= "<td>";
			$rates .= $fr["validity_end_date"];
			$rates .= "</td>";
			$rates .= "</tr>";
			// rout first
			$rates .= "<tr>";
			$rates .= "<th>";
			$rates .= "Rout ".$fr["rate_type"];
			$rates .= "</th>";
			
			$rates .= "<td>";
			$rates .= $fr["port_of_loading"]."(".$fr["origin_country"].")";
			$rates .= "</td>";
			$rates .= "<td>";
			$rates .= $fr["port_of_discharge"]."(".$fr["transit_country"].")";
			$rates .= "</td>";
			$rates .= "</tr>";
			
			// USD
			/*
			$rates .= "<tr id='raterow'>";
			$rates .= "<th>";
			$rates .= "Rate ";
			$rates .= "</th>";
			
			$rates .= "<td>";
			$rates .= $fr["all_in_usd"]." USD";
			$rates .= "</td>";
			$rates .= "<td>";
			$rates .= "";
			$rates .= "</td>";
			$rates .= "</tr>";	
			*/
			// USD after percentage
			$rates .= "<tr id='percentrow' style='display:none'>";
			$rates .= "<th>";
			$rates .= "Rate ";
			$rates .= "</th>";
			
			$rates .= "<td>";
			$rates .= "percentrate-".$opt;
			$rates .= "</td>";
			$rates .= "<td>";
			$rates .= "";
			$rates .= "</td>";
			$rates .= "</tr>";
			
			$rates .= "</table>";
			
			if($fr["rate_type"]=="Ocean")
			{
				$sqltac = "select * from termsandcond where type='Ocean'";
			}
			else
			{
				$cond ="Rail";
				$dest_cont = getCountryCode($fr["transit_country"]);
				if($dest_cont=="KG")
				{
					$cond="Just Rail for KRG destinations";
				}
				else if($dest_cont=="KZ")
				{
					$cond="Just Rail for KZ Destinations";
				}
				else if($dest_cont=="TJ")
				{
					$cond="Just Rail for TJ Destinations";
				}
				
				$sqltac = "select * from termsandcond where type='$cond'";
				//echo $sqltac ,$dest_cont; exit;
			}
			
			
			$tacrs = $db->getData($sqltac);
			$tac = $tacrs[0]["termsandcond"];
			$tacid = $tacrs[0]["id"];
		
			//$rslt .= "<br>'".$tac."'";
			//$rslt="Rate result";
			$tac="Terms and condition";
			$resp = array();
				
			$resp["Error"] = "err";
			$resp["Rate"] = urlencode($rates);
			
			//echo getCountryCode($fr["ToCountry"]); exit;
			
			$resp["FromPort"] = $fr["port_of_loading"];
			$resp["FromCountry"] = getCountryCode($fr["origin_country"]);
			$resp["ToPort"] = $fr["port_of_discharge"];
			$resp["ToCountry"] = getCountryCode($fr["transit_country"]);
			
			$resp["RatePercent"] = "ratepercenttemp-".$opt;
			$resp["RateNew"] = "ratenewtemp-".$opt;
			$resp["TaC"] = $tacid;
			$resp["Handling"] = $handling;

			//$resp["Optn"] = $optns;
			//$json_data = urlencode(json_encode($resp));
			$json_data = json_encode($resp);
			
			//echo $json_data; exit;
        ?>
			
			
		
            <div >
                
                <div class="row" style="margin-left:10px;margin-right:10px">
					<input id="txtdetails-<?php echo $opt; ?>" type="hidden" value='<?php echo $json_data; ?>' />
                    <div class="col-12 mb-2">
                        <div style="display:flex;justify-content:space-between" class="p-0">
                            <span>
                                <small style="color:grey;opacity:0.5">Validity</small>
                                <br>
                                <?php echo $fr["validity_start_date"]; ?> - <?php echo $fr["validity_end_date"]; ?>
								<br><br>
								<input type="checkbox" class="form-control" id="check-<?php echo $opt; ?>" />
                            </span>
                            <span>
                                <small style="color:grey;opacity:0.5">Container</small>
                                <br>
                                <?php echo $fr["container_type"]; ?> 
                            </span>
                            <span>
                                <small style="color:grey;opacity:0.5">Incoterms</small>
                                <br>
                                <?php echo $fr["inco_terms"]; ?>  
                            </span>
                            <span>
                                <small style="color:grey;opacity:0.5">Duration</small>
                                <br>
                                <?php echo $fr["transit_time"]; ?> Days(
                                    <?php
                                        if($fr["rate_type"]=="Ocean")
                                        {
                                            ?>
                                                <img src="include/RateCalculator/icons/ship-solid.svg" width="20px" />
                                            <?php
                                        }
                                        else
                                        {
                                            ?>
                                                <img src="include/RateCalculator/icons/train-subway-solid.svg" width="20px" />
                                            <?php
                                        }
                                        
                                    ?>
                                    
                                    ) 
                                
                            </span>
                            <span>
                                <small style="color:grey;opacity:0.5">Amount</small>
                                <br>
                                $<?php echo $fr["all_in_usd"]; ?> 
								<input id="totalusd-<?php echo $opt; ?>" type="hidden" value="<?php echo $fr["all_in_usd"]; ?>">
                            </span>
                            <span > 
								<div style="display:flex;flex-direction:column;justify-content:space-between" class="pull-right">
								 <div  class="input-group inputElement" >
									<input  id="percent-<?php echo $opt; ?>" type="number" class="form-control" placeholder="Margin %" step="any" min="0" max="100" 
									data-trigger="routRateChange"
									/>
									<span class="input-group-addon">%</span>
									
									<input class="form-control" style="text-align:right;background-color:white" type="text" id="percentnew-<?php echo $opt; ?>"   value="<?php echo "$ ".$fr["all_in_usd"]; ?>" readonly />
								</div>
								 
								<!--
								<a  style="width:100%;color:white" class="btn btn-danger"  href="" 
									role="button"  title="Margin Percent">
									Add to Quote
								</a>
								-->
								<br>
								<a  style="width:100%;color:white" class="btn btn-info" data-toggle="collapse" href="#tacDiv-<?php echo $opt; ?>" 
									role="button" aria-expanded="true" aria-controls="tacDiv-<?php echo $opt; ?>" title="T&C">
									Terms & Conditions
								</a>
							</div>
                            </span>
                        </div>
                    </div>
                </div>
                <br><br>
                <div class="row" style="margin-left:10px;margin-right:10px">
                    <div class="col-12 mb-2" style="color:#000">
                        <div style="display:flex;justify-content:space-between" class="p-0">
            
                            <span style="text-align:center;"><?php echo $fr["port_of_loading"]; ?><br><span style="color:grey;opacity:0.5"><?php echo $fr["origin_country"]; ?></span></span>
                            <span style="text-align:center">
                                <?php echo $fr["transit_time"]." Days"; ?>
                                <br>
                                $<?php echo $fr["all_in_usd"]; ?>
                            </span>
                            <span style="text-align:center;"><?php echo $fr["port_of_discharge"]; ?><br><span style="color:grey;opacity:0.5"><?php echo $fr["transit_country"]; ?></span></span>
                            
                        
                        </div>
                        
                    </div>
                    <div class="col-12" style="color:#fe991f">
                        <div style="display:flex;justify-content:space-between" class="p-0">
            
                            <img src="include/RateCalculator/icons/circle-solid.svg" width="10px" /> 
                            <?php
                                if($fr["rate_type"]=="Ocean")
                                {
                                    ?>
                                        <img src="include/RateCalculator/icons/ship-solid.svg" width="20px" />
                                    <?php
                                }
                                else
                                {
                                    ?>
                                        <img src="include/RateCalculator/icons/train-subway-solid.svg" width="20px" />
                                    <?php
                                }

                            ?>
                            <img src="include/RateCalculator/icons/circle-solid.svg" width="10px" /> 
                        
                        </div>
                        <div col="col-12" style="width:100%;height:3px;background-color:red;margin-top:-13px;"></div>
                    </div>
                    
                    
                </div>
                <div id="tacDiv-<?php echo $opt; ?>" class="col-12 mb-2 collapse multi-collapse " style="color:#000">               
                    <br>
                    <?php                    
                        if($fr["rate_type"]=="Ocean")
                        {
                            $sqltac = "select * from termsandcond where type='Ocean'";
                        }
                        else
                        {
							$cond ="Rail";
							$dest_cont = getCountryCode($fr["transit_country"]);
							if($dest_cont=="KG")
							{
								$cond="Just Rail for KRG destinations";
							}
							else if($dest_cont=="KZ")
							{
								$cond="Just Rail for KZ Destinations";
							}
							else if($dest_cont=="TJ")
							{
								$cond="Just Rail for TJ Destinations";
							}
                            $sqltac = "select * from termsandcond where type='$cond'";
							//echo "dest $dest_cont ".$sqltac;
                        }
						
                        $tacrs = $db->getData($sqltac);
                        $tac = $tacrs[0]["termsandcond"];
                    ?>
					
					
                    <?php 
						if($handling=="**")
						{
							echo $tac."<br><br><b>Station operates both with 20ft and 40ft containers</b>"; 
						}
						else if($handling=="*")
						{
							echo $tac."<br><br><b>Station operates only with 20ft containers</b>"; 
						}
						else if($handling=="-")
						{
							echo $tac."<br><br><b>station doesn't operate with 20' and 40' container</b>"; 
						}
						else 
						{
							echo $tac; //." handling ".$handling; 
						}
					?>
                </div>
            </div>

            
        <?php
        $opt++;
        }
        exit;
    }
    // not find direct route
    // check the destination
    $sql2 = "SELECT * FROM `vtiger_ratecalculatorcf` WHERE port_of_discharge='$dest' and container_type='$ctype' and validity_start_date >='$sdate' and validity_end_date >= '$sdate' and ratecalculatorid in (SELECT crmid from vtiger_crmentity WHERE deleted=0)";
    //echo "<br>".$sql2;
    $srs = $db->getData($sql2);
	//print_r($srs); exit;
    $dest2 = array();
    if(count($srs)>0) //if records founds
    {
        $g=1;
        foreach($srs as $rs)
        {
            $portofdisch=$rs["port_of_loading"];
            
            $sql3="SELECT * from vtiger_ratecalculatorcf WHERE port_of_loading='$origin' and port_of_discharge='$portofdisch' and container_type='$ctype' and validity_start_date >='$sdate' and validity_end_date >= '$sdate'  and ratecalculatorid in (SELECT crmid from vtiger_crmentity WHERE deleted=0)";
            //echo "<br>$sql3";
            $crs = $db->getData($sql3);
            
            if(count($crs)>0)
            {
                
                foreach($crs as $cr)
                {
                    //echo "g $g <br>";
                    $cr["rout"]="First";
                    $cr["group"]=$g;
                    $dest2[]=$cr;
                    $pofload = $cr["port_of_discharge"];
                    //$sql4 = "select * from vtiger_ratecalculatorcf where port_of_loading='$pofload' and port_of_discharge='$dest' and container_type='$ctype' and validity_start_date <='$sdate' and validity_end_date >= '$sdate'";
                    $sql4 = "select * from vtiger_ratecalculatorcf where port_of_loading='$pofload' and port_of_discharge='$dest'  and container_type='$ctype' and validity_start_date >='$sdate' and validity_end_date >= '$sdate' and ratecalculatorid in (SELECT crmid from vtiger_crmentity WHERE deleted=0)";
                    //echo "<br>".$sql4;
                    $nrs = $db->getData($sql4);
                    if(count($nrs)>0)
                    {
                        foreach($nrs as $nr)
                        {
                            $nr["group"]=$g;
                            $nr["rout"]="Second";
                            $dest2[]=$nr;
                        }
                        
                    }
                    
                }
                
            }
            $g++;
        }
    }
    //print_r($srs);
    //print_r($dest2); exit;
   // echo "<br>Second check <br>";
    $r=0;
    $final = array();
    $startby="";
    $startport="";
    $startusd="";
    $startdays="";
    foreach($dest2 as $drec)
    {
       
        if($drec["group"]==$r)
        {
            $rec=array();
            //echo "To ".$drec["group"]." ". $drec["rout"]." ".$drec["rate_type"]." ".$drec["port_of_loading"]. " ".$drec["port_of_discharge"]. " ".$drec["container_type"];
           // echo "<br>";
           // echo "d".$startby.$startport;
           $rec["RecId"]=$r;
            $rec["StartBy"]=$startby;
            $rec["FromPort"]=$startport;
            $rec["FromCountry"]=$startcountry;
            $rec["StartUsd"]=$startusd;
            $rec["StartDays"]=$startdays;

            $rec["StartValidFrom"]=$validfrom;
            $rec["StartValidTo"]=$validto;
            $rec["StartIncoTerms"]=$incoterms;
            $rec["StartContainerSize"]=$contsize;


            $rec["TransitPort"]=$drec["port_of_loading"];
            $rec["TransitCountry"]=$drec["origin_country"];
            $rec["EndBy"]=$drec["rate_type"];
            $rec["ToPort"]=$drec["port_of_discharge"];
            $rec["ToCountry"]=$drec["transit_country"];
            $rec["EndUsd"]=$drec["all_in_usd"];
            $rec["EndDays"]=$drec["transit_time"];

            $rec["EndValidFrom"]=$drec["validity_start_date"];
            $rec["EndValidTo"]=$drec["validity_end_date"];
            $rec["EndIncoTerms"]=$drec["inco_terms"];
            $rec["EndContainerSize"]=$drec["container_type"];

            //print_r($rec);
            //exit;
            $final[]=$rec;
            //break;
        }
        else
        {
            $r++;
            $startby = $drec["rate_type"];
            $startport = $drec["port_of_loading"];
            $startcountry = $drec["origin_country"];
            $startusd = $drec["all_in_usd"];
            $startdays = $drec["transit_time"];
            $validfrom = $drec["validity_start_date"];
            $validto = $drec["validity_end_date"];
            $incoterms = $drec["inco_terms"];
            $contsize = $drec["container_type"];
            //echo $startby.$starport;
            //echo "From ".$drec["group"]." ". $drec["rout"]." ".$drec["rate_type"]." ".$drec["port_of_loading"]. " ".$drec["port_of_discharge"]. " ".$drec["container_type"];
           // echo "<br>";
        }

        
       
    }
    //echo "<br>";
    //print_r($final); exit;
    //echo "<h1>Available Rout</h1>";
    $opt=1;
    foreach($final as $fr)
    {
		//saving main rout to variables
		if($opt==1)
		{
			?>
			<input id="fromport" type="hidden" value="<?php echo $fr["FromPort"]; ?>" />
			<input id="fromcountry" type="hidden" value="<?php echo $fr["FromCountry"]; ?>" />
			<input id="toport" type="hidden" value="<?php echo $fr["ToPort"]; ?>" />
			<input id="tocountry" type="hidden" value="<?php echo $fr["ToCountry"]; ?>" />
			<?php
		}
        //echo "<h3>Option $opt</h3>";
        //echo "From ".$fr["FromPort"]." To ".$fr["TransitPort"]." via ".$fr["StartBy"]." With Cost of $".$fr["StartUsd"]." in ".$fr["StartDays"]." Days <br>";
        //echo "And From ".$fr["TransitPort"]." To ".$fr["ToPort"]." via ".$fr["EndBy"]." With Cost of $".$fr["EndUsd"]." in ".$fr["EndDays"]." Days <br>";
        $totalusd = intVal($fr["StartUsd"])+intVal($fr["EndUsd"]);
        $totaldays = intVal($fr["StartDays"])+intVal($fr["EndDays"]);
       // echo " From ".$fr["FromPort"]." To ".$fr["ToPort"]." in ".$totaldays." days "." with cost of $".$totalusd;
	   
		$rates = "<table border='1' cellpadding='0' cellspacing='0' width='100%'>";
		$rates .= "<tr>";
		$rates .= "<th>";
		$rates .= "Option $opt";
		$rates .= "</th>";
		$rates .= "<th>";
		$rates .= "Container Type : ".$fr["StartContainerSize"];
		$rates .= "</th>";
		$rates .= "<th>";
		$rates .= "INCO TERMS : ".$fr["StartIncoTerms"];
		$rates .= "</th>";
		$rates .= "</tr>";
		
		$rates .= "<tr>";
		$rates .= "<th>";
		$rates .= "";
		$rates .= "</th>";
		$rates .= "<th>";
		$rates .= "From";
		$rates .= "</th>";
		$rates .= "<th>";
		$rates .= "To";
		$rates .= "</th>";
		$rates .= "</tr>";
		
		
		$rates .= "<tr>";
		$rates .= "<th>";
		$rates .= "Validity";
		$rates .= "</th>";
		
		$rates .= "<td>";
		$rates .= $fr["StartValidFrom"];
		$rates .= "</td>";
		$rates .= "<td>";
		$rates .= $fr["StartValidTo"];
		$rates .= "</td>";
		$rates .= "</tr>";
		// rout first
		$rates .= "<tr>";
		$rates .= "<th>";
		$rates .= "Rout ".$fr["StartBy"];
		$rates .= "</th>";
		
		$rates .= "<td>";
		$rates .= $fr["FromPort"]."(".$fr["FromCountry"].")";
		$rates .= "</td>";
		$rates .= "<td>";
		$rates .= $fr["TransitPort"]."(".$fr["TransitCountry"].")";
		$rates .= "</td>";
		$rates .= "</tr>";
		
		// rout second
		$rates .= "<tr>";
		$rates .= "<th>";
		$rates .= "Rout ".$fr["EndBy"];
		$rates .= "</th>";
		
		$rates .= "<td>";
		$rates .= $fr["TransitPort"]."(".$fr["TransitCountry"].")";
		$rates .= "</td>";
		$rates .= "<td>";
		$rates .= $fr["ToPort"]."(".$fr["ToCountry"].")";
		$rates .= "</td>";
		$rates .= "</tr>";
		
		// USD
		/*
		$rates .= "<tr id='raterow'>";
		$rates .= "<th>";
		$rates .= "Rate ".$totalusd." USD";
		$rates .= "</th>";
		
		$rates .= "<td>";
		$rates .= $fr["StartUsd"];
		$rates .= "</td>";
		$rates .= "<td>";
		$rates .= $fr["EndUsd"];
		$rates .= "</td>";
		$rates .= "</tr>";	
		*/
		// USD after percentage
		$rates .= "<tr id='percentrow' style='display:none'>";
		$rates .= "<th>";
		$rates .= "Rate ";
		$rates .= "</th>";
		
		$rates .= "<td>";
		$rates .= "percentrate-".$opt;
		$rates .= "</td>";
		$rates .= "<td>";
		$rates .= "";
		$rates .= "</td>";
		$rates .= "</tr>";
		
		
		$rates .= "</table>";
	   
		//echo $rates; exit;
	   
		
		//print_r($fr);
		
		$sqltac = "select * from termsandcond where type='Rail & Ocean'";
		$tacrs = $db->getData($sqltac);
		$tac = $tacrs[0]["termsandcond"];
		$tacid = $tacrs[0]["id"];
		
		//$rslt .= "<br>'".$tac."'";
		//$rslt="Rate result";
		$tac="Terms and condition";
		$resp = array();
            
		$resp["Error"] = "err";
		$resp["Rate"] = urlencode($rates);
		
		//echo getCountryCode($fr["ToCountry"]); exit;
		
		$resp["FromPort"] = $fr["FromPort"];
		$resp["FromCountry"] = getCountryCode($fr["FromCountry"]);
		$resp["ToPort"] = $fr["ToPort"];
		$resp["ToCountry"] = getCountryCode($fr["ToCountry"]);
		
		$resp["RatePercent"] = "ratepercenttemp-".$opt;
		$resp["RateNew"] = "ratenewtemp-".$opt;
		$resp["TaC"] = $tacid;

		//$resp["Optn"] = $optns;
		//$json_data = urlencode(json_encode($resp));
		$json_data = json_encode($resp);
		
		//echo $json_data; exit;
		
		//$_SESSION["rout-".$opt]=$json_data; 
		
		//echo $json_data;
		
		
        ?>
            <div class="row" style="margin-left:10px;margin-right:10px">
               
                <div class="col-12 mb-2">
					
					<input  id="txtdetails-<?php echo $opt; ?>" type="hidden" value='<?php echo $json_data; ?>' />
					
                    <div style="display:flex;justify-content:space-between" class="p-0">
                        <span>
                            <small style="color:grey;opacity:0.5">Validity</small>
                            <br>
                            <?php echo $fr["StartValidFrom"]; ?> - <?php echo $fr["StartValidTo"]; ?>
							<br><br>
							<input type="checkbox" class="form-control" id="check-<?php echo $opt; ?>" />
                        </span>
                        <span>
                            <small style="color:grey;opacity:0.5">Container</small>
                            <br>
                            <?php echo $fr["StartContainerSize"]; ?> 
                        </span>
                        <span>
                            <small style="color:grey;opacity:0.5">Incoterms</small>
                            <br>
                            <?php echo $fr["StartIncoTerms"]; ?> / <?php echo $fr["EndIncoTerms"]; ?>
                        </span>
                        <span>
                            <small style="color:grey;opacity:0.5">Duration</small>
                            <br>
                            <?php echo $fr["StartDays"]; ?> Days(<img src="include/RateCalculator/icons/ship-solid.svg" width="20px" />) + 
                            <?php echo $fr["EndDays"]; ?> Days(<img src="include/RateCalculator/icons/train-subway-solid.svg" width="15px"  />)
                            <br>
                            <?php echo $totaldays; ?> Days
                        </span>
                        <span>
                            <small style="color:grey;opacity:0.5">Amount</small>
                            <br>
                            $<?php echo $fr["StartUsd"]; ?>  + <?php echo $fr["EndUsd"]; ?> 
                            <br>
                            $<?php echo $totalusd; ?> 
							<input id="totalusd-<?php echo $opt; ?>" type="hidden" value="<?php echo $totalusd; ?>">
                        </span>
                        <span >
                             <!-- <button id="tacBtn-<?php //echo $opt; ?>"  class="col-1 btn btn-info">T & C</button> -->
							 <div style="display:flex;flex-direction:column;justify-content:space-between" class="pull-right">
								 <div  class="input-group inputElement" >
									<input  id="percent-<?php echo $opt; ?>" type="number" class="form-control" placeholder="Margin %" step="any" min="0" max="100" 
									data-trigger="routRateChange"
									/>
									<span class="input-group-addon">%</span>
									
									<input class="form-control" style="text-align:right;background-color:white" type="text" id="percentnew-<?php echo $opt; ?>" value="<?php echo "$ ".$totalusd; ?>" readonly />
								</div>
								 
								<!--
								<a  style="width:100%;color:white" class="btn btn-danger"  href="" 
									role="button"  title="Margin Percent">
									Add to Quote
								</a>
								-->
								<br>
								<a  style="width:100%;color:white" class="btn btn-info" data-toggle="collapse" href="#tacDiv-<?php echo $opt; ?>" 
									role="button" aria-expanded="true" aria-controls="tacDiv-<?php echo $opt; ?>" title="T&C">
									Terms & Conditions
								</a>
							</div>
                        </span>
                    </div>
                </div>
               
            </div>
            <br><br>
            <div class="row"  style="margin-left:10px;margin-right:10px">
                <div class="col-12 mb-2" style="color:#000">
                    <div style="display:flex;justify-content:space-between" class="p-0">
         
                        <span style="text-align:center;"><?php echo $fr["FromPort"]; ?><br><span style="color:grey;opacity:0.5"><?php echo $fr["FromCountry"]; ?></span></span>
						
						
						
                        <span style="text-align:center;margin-left:40px"><?php echo $fr["StartDays"]." Days"; ?><br><span style="color:grey;opacity:0.9">$<?php echo $fr["StartUsd"]; ?></span></span>
                        <span style="text-align:center;margin-left:50px"><?php echo $fr["TransitPort"]; ?><br><span style="color:grey;opacity:0.5"><?php echo $fr["TransitCountry"]; ?></span></span>
                        <span style="text-align:center;margin-left:70px"><?php echo $fr["EndDays"]." Days"; ?><br><span style="color:grey;opacity:0.9">$<?php echo $fr["EndUsd"]; ?></span></span>
                        <span style="text-align:center;"><?php echo $fr["ToPort"]; ?><br><span style="color:grey;opacity:0.5"><?php echo $fr["ToCountry"]; ?></span></span>
                        
                       
                    </div>
                    
                </div>
                <div class="col-12" style="color:#fe991f">
                    <div style="display:flex;justify-content:space-between" class="p-0">
         
                        <img src="include/RateCalculator/icons/circle-solid.svg" width="10px" /> 
                        <img src="include/RateCalculator/icons/ship-solid.svg" width="25px" />  
                        <img src="include/RateCalculator/icons/circle-solid.svg" width="10px" /> 
                        <img src="include/RateCalculator/icons/train-subway-solid.svg" width="20px"  />
                        <img src="include/RateCalculator/icons/circle-solid.svg" width="10px" /> 
                       
                    </div>
                    <div col="col-12" style="width:100%;height:3px;background-color:red;margin-top:-13px;"></div>
                </div>
                <div id="tacDiv-<?php echo $opt; ?>" class="col-12 mb-2 collapse multi-collapse " style="color:#000">               
                <br>
                    <?php                    
                        $sqltac = "select * from termsandcond where type='Rail & Ocean'";
                        $tacrs = $db->getData($sqltac);
                        $tac = $tacrs[0]["termsandcond"];
                    ?>
                    <?php echo $tac; ?>					
                </div>
                
            </div>

            
        <?php
        echo "<br><hr>";
        //print_r($fr); echo "<br>";
        $opt++;
    }

    //echo "<button id='btnClearFilter' onClick='clearFilter();' class='btn btn-info'>Clear Filter</button>";

?>