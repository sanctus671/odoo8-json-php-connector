<?php

class OdooConnector { 
	private $baseUrl;
	private $db; 
	private $sessionId;
	private $context;
	
	public function __construct ( $baseUrl, $db) {
		$this->baseUrl = $baseUrl;
		$this->db = $db; //safer/easier to pass this as a constructor and hardcode in php
	  }	
    
	public function login($login,$password){
		$result = $this->sendRequest('/web/session/authenticate',array("db"=>$this->db,"login"=>$login,"password"=>$password));
		if (isset($result["result"]["uid"])){
			$this->context = $result["result"]["user_context"];
			$this->sessionId = $result["result"]["session_id"];
			return $result;
		}
		else{
			throw new Exception($result);
		}
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


	public function getSessionInfo(){
		if (!isset($this->sessionId)){
			throw new Exception("No session set.");
		}
                $sessionData = $this->sendRequest('/web/session/get_session_info?session_id=' .$this->sessionId, array());
                $this->context = $sessionData["result"]["user_context"];
		return $sessionData;
	}	
	
	public function setSession($sessionId){
		$this->sessionId = $sessionId;
                $this->getSessionInfo();
	}
	
	public function getServerInfo(){
		return $this->sendRequest('/web/webclient/version_info',array());
	}	
	
	public function call($model, $method, $args, $kwargs = array()){

		if (!isset($kwargs["context"])){
                    
			$kwargs["context"] = array();
			array_merge($kwargs["context"], $this->context);
                        
		}
                
                if (!$args){
                    $args = array();
                }
				//$kwargs = array("context"=>array("lang" => "en_US", "tz" => "Pacific/Auckland", "uid"=> 1, "params" => array("action" => 173)));
				
		return $this->sendRequest('/web/dataset/call_kw?session_id=' .$this->sessionId, array("model"=>$model,"method"=>$method,"args"=>$args,"kwargs"=>$kwargs));
		
	}
	
	public function getReport($model, $method, $args, $kwargs = array()){
            
		if (!isset($kwargs["context"])){
                    
			$kwargs["context"] = array();
			array_merge($kwargs["context"], $this->context);
                        
		}
                
                if (!$args){
                    $args = array();
                }
		return $this->sendRequest('/web/web_graph/check_xlwt?session_id=' .$this->sessionId, array("model"=>$model,"method"=>$method,"args"=>$args,"kwargs"=>$kwargs));
		
	}	
        
        function print_json($result, $data){
                print_r(json_encode(array("result"=>$result,"data"=>$data)));
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
			throw new Exception("Error: call to URL $url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
		}

		curl_close($curl);
		$response = json_decode($json_response, true);
		
		return $response;
    } 
} 

?>