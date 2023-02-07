<?php
            $web1C = 'http://89.218.38.221/gl/ws/CheckReceivableBalance?wsdl';
			$con1C = array( 'login' => 'AdmWS',
									'password' => '906900',
									'soap_version' => SOAP_1_2,
									'cache_wsdl' => WSDL_CACHE_NONE, //WSDL_CACHE_MEMORY, //, WSDL_CACHE_NONE, WSDL_CACHE_DISK or WSDL_CACHE_BOTH
									'exceptions' => true,
									'trace' => 1);
									
			if (!function_exists('is_soap_fault')) {
				echo '<br>not found module php-soap.<br>';
				return false;
			}
			
			try {
				$Client1C = new SoapClient($web1C, $con1C);
				echo "connected";
			} catch(SoapFault $e) {
				var_dump($e);
				echo '<br>error at connecting to 1C<br>';
				return false;
			}
			if (is_soap_fault($Client1C)){
				echo '<br>inner server error at connecting to 1C<br>';
				return false;
			}


?>