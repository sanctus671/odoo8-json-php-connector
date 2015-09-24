<?php
error_reporting(E_ALL);
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);
echo "<pre>";
print_r("sdfsdf");

class OdooConnector { 
	public $baseUrl;
	private $sessionId;
	private $context;
	
	public function __construct ( $baseUrl) {
		$this->baseUrl = $baseUrl;
	  }	
    
	public function login($db,$login,$password){
		$result = $this->sendRequest('/web/session/authenticate',array("db"=>$db,"login"=>$login,"password"=>$password));
		if (isset($result["result"]["uid"])){
			$this->context = $result["result"]["user_context"];
			$this->sessionId = $result["result"]["session_id"];
		}
		return $result;
	}
	
	public function isLoggedIn($force){ 
		if (!$force){
			return isset($this->sessionId);
		}
		$result = $this->getSessionInfo();
		return isset($result["uid"]);
	}	
	
	public function logout($force) {
		unset($this->session_id);
		if ($force){
			$result = $this->getSessionInfo();
			if (isset($result["db"])){
				$this->login($result["db"],"","");
			}
		}
	}
	
	public function searchRead($model, $domain, $fields){
		$this->sendRequest('/web/dataset/search_read', array("session_id"=>$this->sessionId, "model"=>$model,"domain"=>$domain,"fields"=>$fields));
	}


	public function getSessionInfo(){
		if (!isset($this->sessionId)){
			return false;
		}
		return $this->sendRequest('/web/session/get_session_info?session_id=' .$this->sessionId, array());
	}	
	
	public function getServerInfo(){
		return $this->sendRequest('/web/webclient/version_info',array());
	}	
    
    private function sendRequest($url,$params) { 
		$content = json_encode(array("jsonrpc: '2.0'" => '2.0', "method" => "call", "params" => $params));

		$curl = curl_init($this->baseUrl . $url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER,array("Content-type: application/json"));
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
		$json_response = curl_exec($curl);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		if ( $status != 200 ) {
			die("Error: call to URL $url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
		}

		curl_close($curl);
		$response = json_decode($json_response, true);
		
		return $response;
    } 
} 

$odoo = new OdooConnector("http://test.triotech.co.nz:8069");

print_r($odoo->login("flowers","admin","admin"));

print_r($odoo->getSessionInfo());

  
  ?>