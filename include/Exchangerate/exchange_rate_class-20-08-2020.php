<?php	
require_once('include/database/PearDatabase.php');

    function currency_rate_convert_kz($from,$to,$amount, $exchange_rate_date)
	{
		$from = trim($from);		
		if(empty($from))
		{
			return 0;
		}				
		
		$_exchrates = exchange_rate($exchange_rate_date);	
			
		 if ($to=="KZT")   //converting to EUR then find the Inverse of the currency
		  {	
			
		   //echo "<font color=red>invert</font>";
		   if ( $_exchrates[$to] == 0 ||  $_exchrates[$from] == 0 )
		   {
		   		//echo "Error: Unable to retrieve exchange rates";
		   		$value=0;
		   }
		   else
		   		$value= $amount * (1 / $_exchrates[$from] )/ $_exchrates[$to];
		   }		
		   else
		  {
			  // echo $amount." * ".$_exchrates[$to]." / ".$_exchrates[$from];			 
			  // echo "<br>";
			  //echo $value= $amount * (1 / $_exchrates[$from] )/ $_exchrates[$to];
			  if ( @$_exchrates[$from] == 0 )
			  $value=0;
			  else
			  $value= $amount * $_exchrates[$to] / $_exchrates[$from];
		  }		 
		  
		 return $value;
	}	
	
	function currency_rate_convert_others($from,$to,$amount, $exchange_rate_date)
	{
		$from = trim($from);		
		if(empty($from))
		{
			return 0;
		}				
		
		$_exchrates = exchange_rate($exchange_rate_date);	
			
		 if ($to=="KZT")   //converting to EUR then find the Inverse of the currency
		  {	
			
		   //echo "<font color=red>invert</font>";
		   if ( $_exchrates[$to] == 0 ||  $_exchrates[$from] == 0 )
		   {
		   		//echo "Error: Unable to retrieve exchange rates";
		   		$value=0;
		   }
		   else
		   		$value= $amount * (1 / $_exchrates[$from] )/ $_exchrates[$to];
		   }		
		   else
		  {
			  //echo $amount." * ".$_exchrates[$to]." / ".$_exchrates[$from];			 
			  // echo "<br>";
			  //echo $value= $amount * (1 / $_exchrates[$from] )/ $_exchrates[$to];
			  if ( @$_exchrates[$to] == 0 )
			  $value=0;
			  else
			  $value= $amount * $_exchrates[$from] / $_exchrates[$to];
		  }		 
		  
		 return $value;
	}
	
	function currency_rate_convert($from,$to,$amount, $exchange_rate_date)
	{
		$from = trim($from);		
		if(empty($from))
		{
			return 0;
		}
		
		$_exchrates = exchange_rate($exchange_rate_date);	
			
		 if ($to=="KZT")   //converting to EUR then find the Inverse of the currency
		  {	
			
		   //echo "<font color=red>invert</font>";
			   if ( $_exchrates[$to] == 0 ||  $_exchrates[$from] == 0 )
			   {
					//echo "Error: Unable to retrieve exchange rates";
					$value=0;
			   }
			   else
					$value= $amount * (1 / $_exchrates[$from] )/ $_exchrates[$to];
		   }		
		   else
		  {
			  //echo $amount." * ".$_exchrates[$to]." / ".$_exchrates[$from];			 
			  // echo "<br>";
			  //echo $value= $amount * (1 / $_exchrates[$from] )/ $_exchrates[$to];
			  if ( @$_exchrates[$from] == 0 )
			  $value=0;
			  else
			  $value= $amount * $_exchrates[$to] / $_exchrates[$from];
		  }		 
		  
		 return $value;
	}
	
	
	function exchange_rate_convert_kz($from,$to,$amount, $exchange_rate_date)
	{		
		$from = trim($from);				
		if(empty($from))
		{
			return 0;
		}
				

		$_exchrates =  exchange_rate($exchange_rate_date);			
			
		 if ($to=="KZT")   //converting to EUR then find the Inverse of the currency
		  {	
			
		   //echo "<font color=red>invert</font>";
		   if ( $_exchrates[$to] == 0 ||  $_exchrates[$from] == 0 )
		   {
		   		//echo "Error: Unable to retrieve exchange rates";
		   		$value=0;
		   }
		   else
		   		$value= $amount * (1 / $_exchrates[$from] )/ $_exchrates[$to];				
		   }
		
		   else
		  {
			  // echo $amount." * ".$_exchrates[$to]." / ".$_exchrates[$from];			 
			  // echo "<br>";
			  //echo $value= $amount * (1 / $_exchrates[$from] )/ $_exchrates[$to];
			  if ( @$_exchrates[$from] == 0 )
			  $value=0;
			  else
			  $value= $amount * $_exchrates[$to] / $_exchrates[$from];
		  }
		  
		 return $value;
	}


	function exchange_rate_convert($from,$to,$amount, $exchange_rate_date)
	{		
		$from = trim($from);				
		if(empty($from))
		{
			return 0;
		}
				
		$_exchrates = exchange_rate($exchange_rate_date);		

			
		 if ($to=="USD")   //converting to EUR then find the Inverse of the currency
		  {	
			//echo $value= $amount ."* (1 /". $_exchrates[$from]." )/". $_exchrates[$to];
		   //echo "<font color=red>invert</font>";
		   if ( $_exchrates[$to] == 0 ||  $_exchrates[$from] == 0 )
		   {
		   		//echo "Error: Unable to retrieve exchange rates";
		   		$value=0;
		   }
		   else
		   		$convertion = $_exchrates[$to] / $_exchrates[$from];
				$value =  $amount / $convertion;
		   		//$value= $amount * (1 / $_exchrates[$from] )/ $_exchrates[$to];				
		   }
		
		   else
		  {
			  // echo $amount." * ".$_exchrates[$to]." / ".$_exchrates[$from];			 
			  // echo "<br>";
			  //echo $value= $amount * (1 / $_exchrates[$from] )/ $_exchrates[$to];
			  if ( @$_exchrates[$from] == 0 )
			  $value=0;
			  else
			  $value= $amount * $_exchrates[$to] / $_exchrates[$from];
		  }		 
		  
		 return $value;
	}
  
  
	 function exchange_rate($date)
	 {
		 global $adb;
		 $_exchrates = array();
		 $exchrates = $adb->pquery('SELECT rate.name, cf.cf_1106 FROM vtiger_exchangerate rate
								   INNER JOIN vtiger_exchangeratecf cf
								   ON rate.exchangerateid = cf.exchangerateid
								   where cf.cf_1108 = "'.$date.'"
								  ', array());
			while($exchrate = $adb->fetchByAssoc($exchrates)){
				$_exchrates[trim($exchrate['cf_1106'])] = $exchrate['name'];
			}						  
		/*while($exchrate = mysql_fetch_object($exchrates))						  
		{
			$_exchrates[trim($exchrate->cf_1106)] = $exchrate->name;
		}
		*/
		
		return $_exchrates;
	 }

	 function exchange_rate_currency($date, $currency_code)
	 {
		 global $adb;
		 $_exchrates = array();
		 $exchrates = 'SELECT rate.name FROM vtiger_exchangerate rate
								   INNER JOIN vtiger_exchangeratecf cf
								   ON rate.exchangerateid = cf.exchangerateid
								   where cf.cf_1108 = "'.$date.'" and cf.cf_1106="'.$currency_code.'"
								  ';
		$result = $adb->pquery($exchrates, array());

		//pick up the first field.
		$_exchrates = $adb->query_result($result,0,'name');
		//$exchrate = mysql_fetch_object($exchrates);
		//$_exchrates = $exchrate->name;
				
		return $_exchrates;
	 }
?>