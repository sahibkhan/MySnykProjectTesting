<?php

// database connection 
class database{

	private $DBServer="localhost";
	private $DBUserID="root";
	private $DBPassword="100372";
	private $DBName="vtigernew";	


	
	
	
	protected function connect(){

		if($_SERVER['SERVER_NAME']=="localhost")
		{
			$this->DBServer="localhost";
			$this->DBUserID="root";
			$this->DBPassword="100372";
			$this->DBName="vtigernew";
		}
		else
		{
			$this->DBServer="localhost";
			$this->DBUserID="root";
			$this->DBPassword="!gl0b@l1nk";
			$this->DBName="live_gems";	
		}

		$conn = new mysqli($this->DBServer,$this->DBUserID,$this->DBPassword,$this->DBName);

		if ($conn->connect_error) {
			die("Connection failed: " . $conn->connect_error);
		} 

		// Change character set to utf8
		mysqli_set_charset($conn,"utf8");
		ini_set('memory_limit', '1024M'); // or you could use 1G

		date_default_timezone_set('Asia/Karachi');

		return $conn;
	}


}

// query class
class query extends database{ 
	
	
	public $rowCount;
	
	//getting data in array
	public function getData($sql){
		$result = $this->connect()->query($sql);

		if(!$result)
		{
			die("Query failed,$sql Error description: " . $this->connect() -> error);
		}

		$this->rowCount = $result->num_rows;

		if($this->rowCount > 0)
		{
			$arr = array();
			while($row=$result->fetch_assoc()){
				$arr[]=$row;
			}
			return $arr;
		}
		else
		{
			return array();
		}
	}

	//getting data in array
	public function updateData($sql){
		$result = $this->connect()->query($sql);

		if(!$result)
		{
			die("Query [$sql] failed, Error description: " . $this->connect()-> error);
		}

		return true;
	}



	// adding record
	public function insertData($sql){
		$cn = $this->connect();		

		$result = $cn->query($sql);
		

		if ( $result === TRUE) {
	
			$last_id = $cn->insert_id;
			return $last_id;
		} else {
			die("Error: " . $sql . "<br>" .   $cn-> error);
		}
	}


}

// function get country code
function getCountryCode($CountryName)
{
	$db = new query();
	$sql= "select country_code from countries where country_name='$CountryName'";
	//echo $sql; exit;
	$rv="";
	$rs = $db->getData($sql);
	//print_r($rs); exit;
	if(count($rs)>0)
	{
		$rv = $rs[0]["country_code"];
	}
	//echo $rv; exit;
	return $rv;
}


	//echo "functiions";
	//exit;
	function getrs($qry)
	{
		//echo $qry;
		//connection to db
		$DBServer="localhost";
		$DBUserID="root";
		//$DBPassword="!gl0b@l1nk";
		//$DBName="live_gems";	
		
		$DBPassword="100372";
		$DBName="vtigernew";	

		//echo $DBPassword;
		$conn = mysqli_connect ($DBServer, $DBUserID, $DBPassword,$DBName);

		//Check connection
		if (!$conn) {
			die("Connection failed: " . mysqli_connect_error());
		}
		
		$rs = mysqli_query($conn,$qry) or die(mysqli_error($conn)." ".$qry);
		return($rs);
	}

	
?>