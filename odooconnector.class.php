<?php
require  'database/medoo.php';

class OdooConnector { 
	private $baseUrl;
	private $db; 
	private $sessionId;
	private $context;
	private $localDb;
	
	public function __construct ( $baseUrl, $db) {
		$this->baseUrl = $baseUrl;
		$this->db = $db; //safer/easier to pass this as a constructor and hardcode in php
		$this->localDb = new medoo(array('database_type' => 'sqlite','database_file' => 'database/users.db')); //required pdo php extension
	  }	
          
	public function register($data){
		if ($data["password"] !== $data["passwordconfirm"]){ //check passwords match
			throw new Exception("Passwords do not match.");
		}
		$login = $this->login("admin","admin"); //login to odoo

		if (!isset($login["result"]["session_id"])){ //check odoo login worked
			throw new Exception("Registration failed. Invalid Odoo authentication.");
		}
		$sessionid = $login["result"]["session_id"];
		$this->setSession($sessionid);
		if ($data["type"] === "grower" || $data["type"] === "picker"){ //if user type is grower or picker, set as supplier in odoo
			$result = $this->call("res.partner","create",array(array("name"=>$data["username"], "supplier"=>true, "email"=>$data["email"])));
		}              
		else{$result = $this->call("res.partner","create",array(array("name"=>$data["username"], "customer"=>true, "email"=>$data["email"])));} //otherwise set at customer
		$partnerid = $result["result"];
		$password = password_hash($data["password"], PASSWORD_DEFAULT);
	   
		$localid = $this->localDb->insert("users", array("username"=>$data["username"], "password"=>$password, "partnerid"=>$partnerid, "type"=>$data["type"], "productid"=>$data["productid"])); //insert into local database
		return array("user"=>array("username"=>$data["username"], "partnerid"=>$partnerid, "session_id"=>$sessionid, "local_userid"=>$localid, "type"=>$data["type"]), "partner"=>array("id"=>$partnerid));
	}  
	
	public function updateUser($data){
		if (isset($data["password"]) && isset($data["currentpassword"]) && ($data["password"] === $data["passwordconfirm"])){ //password change - local db only
			$result = $this->localDb->select("users", "*", ["id"=>$data["userid"]]); 

			if (count($result) < 1){ //make sure this user actually exists 
				throw new Exception("No user with this id.");
			}
			$user = $result[0];

			if (!password_verify($data["currentpassword"], $user["password"])){ //check they entered their current password correctly
				throw new Exception("Current password invalid.");

			};                
			$password = password_hash($data["password"], PASSWORD_DEFAULT); //hash password
			$this->localDb->update("users", array("password"=>$password), array("id"=>$data["userid"])); //update it
		}
		
		
		if (isset($data["type"])){ //user type change - local db and on odoo
			$this->localDb->update("users", array("type"=>$data["type"]), array("id"=>$data["userid"])); //change type locally
			if ($data["type"] === "buyer"){ //if changed to buyer, update odoo record as customer
				$result = $this->call("res.partner","write",array(array((int) $data["partnerid"]), array('supplier'=>false, 'customer'=>true)), array("context"=>array("params"=>array("action"=>61), "uid"=>1)));
			}
			else if ($data["type"] === "grower" || $data["type"] === "picker"){ //if changed to grower or picker, update odoo record as supplier
				$this->call("res.partner","write",array(array((int) $data["partnerid"]),array('supplier'=>true, 'customer'=>false)), array("context"=>array("params"=>array("action"=>61), "uid"=>1)));
			}

		}

		if (isset($data["productid"])){ //user default flower type change
			$this->localDb->update("users", array("productid"=>$data["productid"]), array("id"=>$data["userid"])); //change locally

		}                
                
		if (isset($data["email"])){ //email + other field changes - only on odoo
			$this->call("res.partner","write",array(array((int) $data["partnerid"]),array('email'=>$data['email'])),array("context"=>array("params"=>array("action"=>61), "uid"=>1)));
		}
		return array("user"=>array("partnerid"=>$data["partnerid"], "local_userid"=>$data["userid"], "type"=>$data["type"]));
		
	}  
        
        public function getLocalUser($id){
            $result = $this->localDb->select("users", ["username", "id","partnerid", "productid"], ["partnerid"=>$id]); 
            if (count($result) < 1){ //make sure this user actually exists 
                    return array();
            }
            return $result[0];            
        }
        
	public function loginPortal($username, $password){
		$login = $this->login("admin","admin"); //login in odoo
		
		if (!isset($login["result"]["session_id"])){ //check it worked
			throw new Exception("Registration failed. Invalid Odoo authentication.");
		}
		$sessionid = $login["result"]["session_id"];
		$this->setSession($sessionid);   
		
		$result = $this->localDb->select("users", "*", ["username"=>$username]);
		
		if (count($result) < 1){ //check user exists
			throw new Exception("No user with this username.");
		}
		$user = $result[0];

		if (!password_verify($password, $user["password"])){ //verify password
			throw new Exception("Invalid password.");
			
		};
		
		$partner = $this->call("res.partner","search_read",array(array(array("id", "=", $user["partnerid"])))); //get odoo data for user
		
		return array("user"=>array("session_id"=>$sessionid,"username"=>$username, "local_userid"=>$user["id"], "partnerid"=>$user["partnerid"], "productid"=>$user["productid"], "type"=>$user["type"]), "partner"=>$partner["result"][0]);

		
		
	}
        
	public function login($login,$password){ //odoo login
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
			return isset($this->sessionId); //check if the current object has a session
		}
		$result = $this->getSessionInfo(); //check odoo as well
		return isset($result["uid"]);
	}	
	
	public function logout($force) {
		unset($this->session_id); //destroy local session store
		if ($force){
			$result = $this->getSessionInfo();
			if (isset($result["db"])){
				$this->login($result["db"],"",""); //destroy it in odoo
			}
		}
	}
        
        public function resetPassword($email){
		$login = $this->login("admin","admin"); //login in odoo
		
		if (!isset($login["result"]["session_id"])){ //check it worked
			throw new Exception("Password reset failed. Invalid Odoo authentication.");
		}
		$sessionid = $login["result"]["session_id"];
		$this->setSession($sessionid);             
            
                //find partnerid associated to user in odoo
                $partner = $this->call("res.partner","search_read",array(array(array("email", "=", $email))));

                if (count($partner["result"]) < 1){
                    throw new Exception("No user with this email.");
                }
                $partnerid = $partner["result"][0]["id"];
                
                
                $result = $this->localDb->select("users", "*", ["partnerid"=>$partnerid]);

                if (count($result) < 1){ //check user exists
                        throw new Exception("No user assigned to this email in Odoo.");
                }
                $user = $result[0];   
                
                $newPassword = md5($user["username"] . time());
                $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT); //hash password
                $this->localDb->update("users", array("password"=>$newPasswordHash), array("id"=>$user["id"])); //update it                
                
                $this->sendEmail($partner["result"][0]["email"], 'Password Reset - Manawatu Flowers Portal', "You're new password is: " . $newPassword);             
                
                return true;
                
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
		return $this->getSessionInfo();
	}
	
	public function getServerInfo(){
		return $this->sendRequest('/web/webclient/version_info',array());
	}	
	
	public function getSpecials(){
		$result = $this->localDb->select("specials", "*"); 
		return $result;   
	}

	public function updateSpecial($id,$productid, $colour){
		$this->localDb->update("specials", array("productid"=>$productid, "colour"=>$colour), array("id"=>$id)); //change locally
		return true;
	}	
	
	public function call($model, $method, $args, $kwargs = array()){
		if (!isset($kwargs["context"])){//if the context hasn't been set manually, set it based on current session
			$kwargs["context"] = array();
			array_merge($kwargs["context"], $this->context);                     
		}
		if (!$args){
			$args = array();
		}
		//$kwargs = array("context"=>array("lang" => "en_US", "tz" => "Pacific/Auckland", "uid"=> 1, "params" => array("action" => 173))); //action attribute is important for edits

		return $this->sendRequest('/web/dataset/call_kw?session_id=' .$this->sessionId, array("model"=>$model,"method"=>$method,"args"=>$args,"kwargs"=>$kwargs));
		
	}
        
	public function callAction($model, $method, $args, $kwargs = array()){
		if (!isset($kwargs["context"])){//if the context hasn't been set manually, set it based on current session
			$kwargs["context"] = array();
			array_merge($kwargs["context"], $this->context);                     
		}
		if (!$args){
			$args = array();
		}
		//$kwargs = array("context"=>array("lang" => "en_US", "tz" => "Pacific/Auckland", "uid"=> 1, "params" => array("action" => 173))); //action attribute is important for edits
				
		return $this->sendRequest('/web/dataset/call_button?session_id=' .$this->sessionId, array("model"=>$model,"method"=>$method,"args"=>$args));
		
	}  
        
	public function callWorkflow($model, $id, $signal){	
		return $this->sendRequest('/web/dataset/exec_workflow?session_id=' .$this->sessionId, array("model"=>$model,"id"=>$id,"signal"=>$signal));
		
	}  
        
        public function sendResults($collated, $notSold, $partners){
            require('libraries/fpdf/fpdf.php');
            require('libraries/fpdf/fpdf-ex.php');          
            
            $pdf = new PDF();
            $pdf->AliasNbPages();
            $pdf->AddPage();
            $pdf->SetFont('Times','B',12);
            $pdf->Cell(0,10,'Auctions',0,1);
            $pdf->SetFont('Times','',12);
            foreach($collated as $key=>$auctions){
                $pdf->SetFont('Times','B',12);
                $pdf->Cell(0,10,'Product:' . $key,0,1);
                $pdf->SetFont('Times','',12);
                foreach($auctions as $auction){
                    $pdf->Cell(0,10, $auction["quantity"] . " bought by " . $auction["buyerName"] . " (user ID: " . $auction["buyer"] . ") for $" . $auction["price"] . " (order ID: " . $auction["orderid"] . ")",0,1);
                }
            }
            $pdf->SetFont('Times','B',12);
            $pdf->Cell(0,10,'Remaining Stock',0,1);
            $pdf->SetFont('Times','',12);
            foreach($notSold as $key=>$remaining){
                $pdf->Cell(0,10,$key . " (product ID: " . $remaining["productid"] . "): " . $remaining["quantity"] . " unsold." ,0,1);
            }            
            $doc = $pdf->Output('', 'S');
            
            $this->sendEmail($partners, 'Auction Summary ' . date("d-m-Y"), "Attached in the summary from the Manawatu Flowers auction for the date " . date("d-m-Y"), $doc);

            return true;
        }
        
	
	public function getReport($model, $method, $args, $kwargs = array()){ //TODO       
		if (!isset($kwargs["context"])){            
			$kwargs["context"] = array();
			array_merge($kwargs["context"], $this->context);                    
		}             
		if (!$args){
			$args = array();
		}
		return $this->sendRequest('/web/web_graph/check_xlwt?session_id=' .$this->sessionId, array("model"=>$model,"method"=>$method,"args"=>$args,"kwargs"=>$kwargs));	
	}
        
        
        
	public function print_json($result, $data){ //for returning json to client
			print_r(json_encode(array("result"=>$result,"data"=>$data)));
	}    
        
        private function sendEmail($to, $subject, $body, $attachment=null){
            require('libraries/PHPMailer/PHPMailerAutoload.php');
            $mail = new PHPMailer;
            $mail->IsSMTP();                           // telling the class to use SMTP
            $mail->SMTPAuth   = true;                  // enable SMTP authentication
            $mail->Host       = "smtpout.secureserver.net"; // set the SMTP server
            $mail->Port       = 25;                    // set the SMTP port
            $mail->Username   = "taylor@taylorhamling.com"; // SMTP account username
            $mail->Password   = "msnmail1337";        // SMTP account password
            $mail->From = "taylor@triotech.co.nz";
            $mail->FromName = "Manawatu Flowers";
            
            if (is_array($to)){
                foreach ($to as $partner){
                    $mail->addAddress($partner);
                }
            }
            else{
                $mail->addAddress($to);
            }
            
            if (!is_null($attachment)){
                $mail->AddStringAttachment($attachment, 'auction-summary_' . date("d-m-Y") . '.pdf', 'base64', 'application/pdf');  
            }


            $mail->Subject = $subject;
            $mail->Body = $body;

            if(!$mail->send()) 
            {
                throw new Exception("Mailer Error: " . $mail->ErrorInfo);
            } 
            return true;
        }
    
        private function sendRequest($url,$params) { //actually sending stuff to odoo
            
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