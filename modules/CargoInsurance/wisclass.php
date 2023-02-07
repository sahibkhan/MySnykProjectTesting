<?php

class WIS
{
    /**URL:      https://wis.test.nsure.net
        Company: Globalink Transportation and Logistics Worldwide LLC
        Policy Number: 87HK7014/19
        UserID:  api740705181
        Password: VXHfGja5Yttu
        API Key: BfqkxhPsmA1dtRG4D6IIAAgDkWTbkw0NTpIF6OYOm5WP4IUarQ8CTjcN4Bbzm7HP
    */



    protected $api_home = "https://wis.test.nsure.net/api/v1/";
   // const X_NS_API_Key = 'nwDgXyqX4tmF444IeeLD21CemzbP4AXD15BAvmJrSwtwtKYCBWRrRViCQoj0NS5J';
	const X_NS_API_Key = 'BfqkxhPsmA1dtRG4D6IIAAgDkWTbkw0NTpIF6OYOm5WP4IUarQ8CTjcN4Bbzm7HP';
    protected $username = 'api740705181';
    protected $password = 'VXHfGja5Yttu';
    protected $assuredId = 'a5a335ba-b50e-485d-9975-a1ca48b1f4b8';
    protected $policyId = 'b94b8510-ac8a-4532-9acc-9b8271aff116';
    private $token = '';
    protected $declarationId = '';
    private static $pdo = null;

    public static function DB()
    {
        if (self::$pdo == null) {
            $host = 'localhost';
            $db   = 'devcloudy_gems';
            $user = 'root';
            $pass = '!gl0b@l1nk';
            $charset = 'utf8';

            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

            $opt = array(
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => false,
                \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY
            );
            try {
                self::$pdo = new \PDO($dsn, $user, $pass, $opt);
            } catch (\PDOException $e) {
                die('Connection failed: ' . $e->getMessage());
            }
        }

        return self::$pdo;
    }

    public function getToken() //works
    {       

        return $this->token;
    }

    public function sendRequest($uri, $data = NULL, $customRequest = "POST", $token_header = false)  //works
    {

        $header = array(
            "Content-Type: application/json",
            "Accept: application/json",
            "X-NS-API-Key: ".WIS::X_NS_API_Key
        );

        if($token_header) {
            $token = $this->getToken();
            array_push($header, "X-NS-API-Token: ".$token);
        }
		//echo "Send REqu"; exit;
		
	  
	  
        $curl = curl_init();
        curl_setopt($curl,CURLOPT_URL,$this->api_home.$uri);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl,CURLOPT_ENCODING,"");
        curl_setopt($curl,CURLOPT_MAXREDIRS,10);
        curl_setopt($curl,CURLOPT_TIMEOUT,0);
        curl_setopt($curl,CURLOPT_FOLLOWLOCATION,true);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST,$customRequest);
        if(isset($data)) {
            /*if (count($data) > 3){
                echo '<pre>'.json_encode($data).'</pre>';
                exit;
            }*/
            $data = json_encode($data);
            curl_setopt($curl,CURLOPT_POSTFIELDS,$data);
        }
        curl_setopt($curl,CURLOPT_HTTPHEADER,$header);

        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            $date = date('d/m/Y H:i:s', time());
            $error_msg = curl_error($curl);
            error_log($error_msg. " time: ". $date . PHP_EOL, 3, "modules/CargoInsurance/log.txt");
        }
        curl_close($curl);
        $response =  json_decode($response,true);
        return $response;
    }
   

    public function login() //3.1 works.
    {
        
        $data = array(
            "username" => $this->username,
            "password" => $this->password
        );

        $uri = "login";
        $header = array(
            "Content-Type: application/json",
            "Accept: application/json",
            "X-NS-API-Key: ".WIS::X_NS_API_Key
        );

        $curl = curl_init();
        curl_setopt($curl,CURLOPT_URL,$this->api_home.$uri);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl,CURLOPT_ENCODING,"");
        curl_setopt($curl,CURLOPT_MAXREDIRS,10);
        curl_setopt($curl,CURLOPT_TIMEOUT,0);
        curl_setopt($curl,CURLOPT_FOLLOWLOCATION,true);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST, "POST");

		$data = json_encode($data);
        curl_setopt($curl,CURLOPT_POSTFIELDS,$data);

        curl_setopt($curl,CURLOPT_HTTPHEADER,$header);

        $responseCurl = curl_exec($curl);
        
		

        if (curl_errno($curl)) {
            $date = date('d/m/Y H:i:s', time());
            $error_msg = curl_error($curl);
            error_log($error_msg. " time: ". $date . PHP_EOL, 3, "log.txt");
            return array(
              'status' => 0,
              'message' => $error_msg.", ".$response
            );
        }
        curl_close($curl);
       
		
	   
        $response =  json_decode($responseCurl,true);
		
		//print_r($responseCurl);
		
        if(!isset($response['token'])){

            return array(
                'status' => 0,
                'message' => $response
            );
        }

		
        $this->token = $response['token'];
        return array('status' => 1);
    }


    /**
     * @return bool
     */
    public function checkToken() //works
    {
        $uri = "login//".$this->token;
        $customRequest = "GET";

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://wis.test.nsure.net/api/v1/".$uri,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "Accept: application/json",
                "X-NS-API-Key: ".WIS::X_NS_API_Key
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $response = json_decode($response,true);
        if(isset($response['status']) && $response['status'] == 'active') {
            return true;
        }
        return false;
    }

    public function listPolicies() //4.1
    {
        $uri = "policies";
        $custom_request = "GET";
        return $this->sendRequest($uri,null, $custom_request, true);
    }

    /**
     * @param $date
     * @return mixed
     */
    public function createDeclaration($date) //5.2
    {
        $uri = "declarations/cargo/shipments";
        $customRequest = "POST";
        $data = array(
            "assuredId" =>  $this->assuredId,
            "policyId"  =>  $this->policyId,
            "departureDate" =>  $date
        );
		
		
		
        $response = $this->sendRequest($uri,$data,$customRequest,true);
		
		//print_r($response); exit;


        if( !isset($response['id']) ) {
            return array(
                'status' => 0,
                'message' => $response
            );
        }

        return $response['id'];
    }

    public function searchCommodities($declarationId) //5.3
    {
        $uri = "declarations/cargo/shipments//".$declarationId."/commodities";
        $customRequest = "GET";

        return $this->sendRequest($uri,null,$customRequest,true);
    }    

    public function commodityOptions($declarationId,$commodityId) //5.4
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(

            CURLOPT_URL => "https://wis.test.nsure.net/api/v1/declarations/cargo/shipments//".$declarationId."/commodities/".$commodityId."/options",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "Accept: application/json",
                "X-NS-API-Key: nwDgXyqX4tmF444IeeLD21CemzbP4AXD15BAvmJrSwtwtKYCBWRrRViCQoj0NS5J",
                "X-NS-API-Token: ot6oS10yT18M04GehTMJWd8rUxLXO4ToXNpkpQxUxPjCBtri5DsSrzyyD7HIJP1g"
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;
    }  

    public function searchVessels($declarationId) // 5.5
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://wis.test.nsure.net/api/v1/declarations/cargo/shipments//".$declarationId."/vessels",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "Accept: application/json",
                "X-NS-API-Key: nwDgXyqX4tmF444IeeLD21CemzbP4AXD15BAvmJrSwtwtKYCBWRrRViCQoj0NS5J",
                "X-NS-API-Token: ot6oS10yT18M04GehTMJWd8rUxLXO4ToXNpkpQxUxPjCBtri5DsSrzyyD7HIJP1g"
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;
    }

    public function listCurrencies($declarationId) //5.6
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://wis.test.nsure.net/api/v1/declarations/cargo/shipments//".$declarationId."/currencies",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "Accept: application/json",
                "X-NS-API-Key: nwDgXyqX4tmF444IeeLD21CemzbP4AXD15BAvmJrSwtwtKYCBWRrRViCQoj0NS5J",
                "X-NS-API-Token: ot6oS10yT18M04GehTMJWd8rUxLXO4ToXNpkpQxUxPjCBtri5DsSrzyyD7HIJP1g"
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;

    }

    public function listConveyances($declarationId) // 5.8
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://wis.test.nsure.net/api/v1/declarations/cargo/shipments//".$declarationId."/conveyances",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "Accept: application/json",
                "X-NS-API-Key: nwDgXyqX4tmF444IeeLD21CemzbP4AXD15BAvmJrSwtwtKYCBWRrRViCQoj0NS5J",
                "X-NS-API-Token: ot6oS10yT18M04GehTMJWd8rUxLXO4ToXNpkpQxUxPjCBtri5DsSrzyyD7HIJP1g"
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;

    }

    public function listPackaging($declarationId,$conveyanceId)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://wis.test.nsure.net/api/v1/declarations/cargo/shipments//".$declarationId."/conveyances/".$conveyanceId."/packaging",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "Accept: application/json",
                "X-NS-API-Key: nwDgXyqX4tmF444IeeLD21CemzbP4AXD15BAvmJrSwtwtKYCBWRrRViCQoj0NS5J",
                "X-NS-API-Token: ot6oS10yT18M04GehTMJWd8rUxLXO4ToXNpkpQxUxPjCBtri5DsSrzyyD7HIJP1g"
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;
    }

    public function getQuote($declarationId, $quoteData)
    {
//        /declarations/cargo/shipments//[:id|externalid=:id]/actions/quote
        $uri = "declarations/cargo/shipments//".$declarationId."/actions/quote";
        $customRequest = "POST";

        return $this->sendRequest($uri, $quoteData,$customRequest,true);

    }

    public function confirm($declarationId,$quoteId)
    {
        $uri = "declarations/cargo/shipments//".$declarationId."/actions/confirm";
        $customRequest = "POST";

        return $this->sendRequest($uri, $quoteId,$customRequest,true);
    }

    public function download($declarationId, $securityCode)
    {
        $uri = "declarations/cargo/shipments//".$declarationId."/actions/certificate?security-code=". $securityCode; //."&type=download"
        ///declarations/cargo/shipments//[:id|externalid=:id]/actions/certificate[?options]
		$uri = "declarations/cargo/shipments//".$declarationId."/actions/certificate?security-code=".$securityCode;
		//https://wis.test.nsure.net/client/declarations/cargo/certificate/12c2ef68-913a-4431-8fed-f5c868c20f8b/version/6/Certificate
		
        $customRequest = "GET";
        $token = $this->getToken();
        $header = array(
            "Content-Type: application/json",
            "Accept: application/json",
            "X-NS-API-Key: " . WIS::X_NS_API_Key,
            "X-NS-API-Token: " . $token
        );
		//echo "Token ".$token;
		//error_log("Token ".$token. " time: ". $date . PHP_EOL, 3, "log.txt");
		
		//echo $this->api_home.$uri;

        $curl = curl_init();
        curl_setopt($curl,CURLOPT_URL,$this->api_home.$uri);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl,CURLOPT_ENCODING,"");
        curl_setopt($curl,CURLOPT_MAXREDIRS,10);
        curl_setopt($curl,CURLOPT_TIMEOUT,0);
        curl_setopt($curl,CURLOPT_FOLLOWLOCATION,true);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST,$customRequest);
        curl_setopt($curl,CURLOPT_HTTPHEADER,$header);

        $response = curl_exec($curl);
		//echo $this->api_home.$uri;
		//print_r($response);
		
        if (curl_errno($curl)) {
            $date = date('d/m/Y H:i:s', time());
            $error_msg = curl_error($curl);
			echo "Err ".$error_msg;
            error_log($error_msg. " time: ". $date . PHP_EOL, 3, "log.txt");
            return array(
                'status' => 0,
                'message' => $response
            );
        }
		//echo " No err";
		//print_r($response);
        curl_close($curl);
		//exit;
        $response =  json_decode($response,true);
        return $response;
    }

    public function purchase($declarationId)
    {
        $uri = "declarations/cargo/shipments//".$declarationId."/actions/purchase";
        $customRequest = "POST";

        return $this->sendRequest($uri, null,$customRequest,true);
    }

    public function cancel($declarationId, $cancellationReason)
    {
        $uri = "declarations/cargo/shipments//".$declarationId."/actions/cancel";
        $customRequest = "POST";
        $data = $cancellationReason;
        return $this->sendRequest($uri, $data, $customRequest,true);
    }

    public static function getConveyanceId($transportationMode)
    {
        //check if $transportationMode has more than one value
        if (strpos($transportationMode, '|##|') !== false) {
            $transportationMode = explode('|##|', $transportationMode);
            $transportationMode = array_map('trim', $transportationMode);
        }

		
		
        if (!is_array($transportationMode)) {
			
			//echo "is array ".$transportationMode;
			
			$stmt2 = WIS::DB()->prepare("SELECT `wis_key` FROM `vtiger_transport_mode` WHERE `name` LIKE ? ");
			$stmt2->execute([$transportationMode]);
			$result = $stmt2->fetch();
			$conveyanceid = $result["wis_key"];
			
			/*
            $stmt2 = WIS::DB()->query("SELECT `wis_key` FROM `vtiger_transport_mode` WHERE `name` LIKE ? ");

            $stmt2->execute(array($transportationMode));

            $conveyanceid = $stmt2->fetchColumn();
			*/
        } else {
            if (in_array('Sea', $transportationMode)) {
                $stmt2 = WIS::DB()->query("SELECT `wis_key` FROM `vtiger_transport_mode` WHERE `name` LIKE 'Sea' ");

                $conveyanceid = $stmt2->fetchColumn();

            } 
			elseif (in_array('Ocean', $transportationMode)) {
				$stmt2 = WIS::DB()->query("SELECT `wis_key` FROM `vtiger_transport_mode` WHERE `name` LIKE 'Sea' ");

                $conveyanceid = $stmt2->fetchColumn();

            }
			elseif (in_array('Air', $transportationMode)) {
				//return "Check Air";
				
				
                $stmt2 = WIS::DB()->query("SELECT `wis_key` FROM `vtiger_transport_mode` WHERE `name` LIKE 'Air' ");

                $conveyanceid = $stmt2->fetchColumn();

            } elseif (in_array('Road', $transportationMode)) {
                $stmt2 = WIS::DB()->query("SELECT `wis_key` FROM `vtiger_transport_mode` WHERE `name` LIKE 'Road' ");

                $conveyanceid = $stmt2->fetchColumn();

            } elseif (in_array('Rail', $transportationMode)) {
                $stmt2 = WIS::DB()->query("SELECT `wis_key` FROM `vtiger_transport_mode` WHERE `name` LIKE 'Rail' ");

                $conveyanceid = $stmt2->fetchColumn();
            }
        }
        return $conveyanceid;
    }

    public function getDate()  // возвращает текущую дату и время
    {
        date_default_timezone_set('Asia/Almaty');

        $d = new DateTime();
        $date = $d->format('Y-m-d H:i:s');

        return $date;
    }

    public function getCancelReply($status)
    {
        switch ($status) {
            case "cancelled":
                $cancelStatus = "cancelled";
                break;
            case "referred":
                $cancelStatus = "in referral stage";
                break;
            case "cantbecancelled":
                $cancelStatus = "can't be cancelled";
                break;
            default:
                $cancelStatus = "in referral stage";
        }

        return $cancelStatus;
    }

    public function saveCancelReply( $request)
    {
        switch ($request['status']) {
            case null:
            case "cancelled":
            case "ntu":
                $cancelStatus = "cancelled";
                break;
            case "referred":
                $cancelStatus = "referred";
                break;
            case "booked":
            case "accepted":
                $cancelStatus = "cantbecancelled";
                break;
            default:
                $cancelStatus = "referred";
        }

        $stmt = WIS::DB()->prepare("UPDATE `vtiger_cargoinsurance_certificate` SET `cancel_status` = ?, `modified_at` = ? WHERE `declaration_id`  = ?");
        $stmt->execute(array($cancelStatus, $this->getDate(), $request['id'])); //status 3 declined
		
		//getting insuranceid no 
		$stmt2 = WIS::DB()->query("SELECT `insurance_id` FROM `vtiger_cargoinsurance_certificate` WHERE `declaration_id`  = '".$request['id']."'");
        $insid = $stmt2->fetchColumn();
		
		//updating status 
		$stmt3 = WIS::DB()->prepare("UPDATE `vtiger_cargoinsurancecf` SET `cf_7610` = ?, `cf_3623` = ? WHERE `cargoinsuranceid`  = ?");
        $stmt3->execute(array($cancelStatus, $this->getDate(), $insid)); //status 3 declined


        return $cancelStatus;

    }

    public function saveConfirmReply($request)
    {
        $stmt = WIS::DB()->prepare("SELECT `insurance_id` FROM `vtiger_cargoinsurance_certificate` WHERE `declaration_id` = ?");
        $stmt->execute(array($request['id']));
        $insurance_id = $stmt->fetchColumn();

        switch ( $request['status'] ) {
            case "accepted":
                //5.13 Purchase declaration
                $this->login();
                $purchaseResponse =  $this->purchase($request['id']);
                error_log("time: ". $this->getDate() . " data: " . implode(",",$purchaseResponse) . PHP_EOL, 3, "log_purchase.txt");


                $stmt = WIS::DB()->prepare("UPDATE `vtiger_cargoinsurance_certificate` SET `confirm_status` = ?, `security_code` = ?, 
                                                        `certificate_number` = ?, `modified_at` = ?, `completed` = ?, `referal` = ? WHERE `declaration_id`  = ?");
                $stmt->execute( array($purchaseResponse['status'], $purchaseResponse['securityCode'], $purchaseResponse['certificateNumber'], $this->getDate(), 'yes', 'no', $request['id'])); //status 1 accepted

                // save certificateNumber and date to database after purchasing declaration

                $stmt = WIS::DB()->prepare("UPDATE `vtiger_cargoinsurancecf` SET `cf_3621` = ?, `cf_3623` = ?, `cf_7090` = ? WHERE `cargoinsuranceid`  = ?");
                $stmt->execute( array( $purchaseResponse['certificateNumber'], $this->getDate(), 'Booked', $insurance_id) );

                break;
            case "booked":
                $stmt = WIS::DB()->prepare("UPDATE `vtiger_cargoinsurance_certificate` SET `confirm_status` = ?, `security_code` = ?, `certificate_number` = ?,
                                                        `modified_at` = ? `completed` = ?, `referal` = ? WHERE `declaration_id`  = ?");

                $stmt->execute( array($request['status'], $request['securityCode'], $request['certificateNumber'], $this->getDate(), 'yes', 'no', $request['id'])); //status 2 booked

                $stmt = WIS::DB()->prepare("UPDATE `vtiger_cargoinsurancecf` SET `cf_3621` = ?, `cf_3623` = ?, `cf_7090` = ? WHERE `cargoinsuranceid`  = ?");
                $stmt->execute( array( $request['certificateNumber'], $this->getDate(), 'Booked', $insurance_id) );

                break;
            case "declined":
                $stmt = WIS::DB()->prepare("UPDATE `vtiger_cargoinsurance_certificate` SET `confirm_status` = ?, `modified_at` = ? WHERE `declaration_id`  = ?");
                $stmt->execute(array($request['status'], $this->getDate(), $request['id'])); //status 3 declined

                //set wis status
                $stmt = WIS::DB()->prepare("UPDATE `vtiger_cargoinsurancecf` SET `cf_7090` = ? WHERE `cargoinsuranceid`  = ?");
                $stmt->execute( array( 'Declined', $insurance_id) );
                break;

            case "cancelled":
                $stmt = WIS::DB()->prepare("UPDATE `vtiger_cargoinsurance_certificate` SET `confirm_status` = ?, `modified_at` = ? WHERE `declaration_id`  = ?");
                $stmt->execute(array($request['status'], $this->getDate(), $request['id'])); //status 4 cancelled

                //set wis status
                $stmt = WIS::DB()->prepare("UPDATE `vtiger_cargoinsurancecf` SET `cf_7090` = ? WHERE `cargoinsuranceid`  = ?");
                $stmt->execute( array( 'Cancelled', $insurance_id) );
                break;
            default:

        }
    }

    public function getUserEmail($declarationId)
    {

        $stmt = WIS::DB()->prepare("SELECT `user` FROM `vtiger_cargoinsurance_certificate` WHERE `declaration_id` = ?");
        $stmt->execute(array($declarationId));
        $user_id = $stmt->fetchColumn();


        $stmt = WIS::DB()->prepare("SELECT `email1` FROM `vtiger_users` WHERE `id` = ?");
        $stmt->execute(array($user_id));

        $userEmail = $stmt->fetchColumn();

        return $userEmail;

    }

}