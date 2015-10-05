<?php

require_once("odooconnector.class.php");

$odoo = new OdooConnector("http://test.triotech.co.nz:8069", "flowers");
//$odoo->print_json(true,$odoo->login("flowers","admin","admin")); //login to odoo
try{
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST))
		$_POST = (array) json_decode(file_get_contents('php://input'), true);


	//login GET username, password
	if (isset($_GET["server"])){
		$odoo->print_json(true,$odoo->getServerInfo());
	}
		
	//login GET username, password
	else if (isset($_GET["login"]) && isset($_GET["username"]) && isset($_GET["password"])){
		$odoo->print_json(true,$odoo->login($_GET["username"],$_GET["password"]));
            //$odoo->print_json(true,$odoo->login("flowers","admin","admin"));
	}
	//logout GET sessionid
	else if (isset($_GET["logout"]) && isset($_GET["sessionid"])){
		$odoo->setSession($_GET["logout"]);
		$odoo->print_json(true,$odoo->logout(true));
	}
	//get user info GET sessionid
	else if (isset($_GET["user"]) && isset($_GET["sessionid"])){
		$odoo->setSession($_GET["logout"]);
		$odoo->print_json(true,$odoo->getSessionInfo());
	}

	//records GET/POST/PUT/DELETE model, args, kwargs (optional)
	//GET
	else if (isset($_GET["records"]) && isset($_GET["model"]) && isset($_GET["sessionid"])){
		$odoo->setSession($_GET["sessionid"]);
		$offset = 0;$limit = 5;$order = "id DESC";
		if (isset($_GET["offset"])){$offset = (int) $_GET["offset"];}
		if (isset($_GET["limit"])){$limit = (int) $_GET["limit"];}     
                if (isset($_GET["order"])){$order = $_GET["order"];}
		$search = array();				
		if ($_GET["model"] === "product.template"){
			$search = array(array("uom_id", "=", 1));
		}
		$count = $odoo->call($_GET["model"],'search_count',array($search));
		$data = $odoo->call($_GET["model"],"search_read",array($search),array("offset"=>$offset,"limit"=>$limit,"order"=>$order));
		$odoo->print_json(true,array("count"=>$count["result"],"data"=>$data["result"]));
	}

	else if (isset($_POST["report"]) && isset($_POST["model"]) && isset($_POST["sessionid"]) && isset($_POST["args"])){
		$odoo->setSession($_GET["sessionid"]);

		$data = $odoo->call($_GET["model"],"search",array($_POST["args"]) );
		$odoo->print_json(true,array("count"=>$count["result"],"data"=>$data["result"]));
	}
	
	//POST SEARCH - WITH ARGUMENTS (REQUIRES POSTING JSON)
	else if (isset($_POST["records"]) && isset($_POST["search"]) && isset($_POST["model"]) && isset($_POST["args"]) && isset($_POST["sessionid"])){
		$odoo->setSession($_POST["sessionid"]);
		$kwargs = array();
		if (isset($_POST["kwargs"])){
			$kwargs = json_decode($_POST["kwargs"]);
		}
		$odoo->print_json(true,$odoo->call($_POST["model"],"search_read",json_decode($_POST["args"]),$kwargs));
	}	
	
	//POST
	else if (isset($_POST["records"]) && isset($_POST["create"]) && isset($_POST["model"]) && isset($_POST["args"]) && isset($_POST["sessionid"])){
            $odoo->setSession($_POST["sessionid"]);
            $kwargs = array();
            if (isset($_POST["kwargs"])){$kwargs = $_POST["kwargs"];} 
            $odoo->print_json(true,$odoo->call($_POST["model"],"create",array($_POST["args"]),$kwargs));
	}
	//PUT
	else if (isset($_POST["records"]) && isset($_POST["update"]) && isset($_POST["model"]) && isset($_POST["args"]) && isset($_POST["kwargs"]) && isset($_POST["sessionid"])){
            $odoo->setSession($_POST["sessionid"]);
            $odoo->print_json(true,$odoo->call($_POST["model"],"write",array($_POST["args"]["ids"], $_POST["args"]["data"]), $_POST["kwargs"]));
	}
	//PUT STATE
	else if (isset($_POST["records"]) && isset($_POST["state"]) && isset($_POST["model"]) && isset($_POST["args"]) && isset($_POST["sessionid"])){
            $odoo->setSession($_POST["sessionid"]);
            $odoo->print_json(true,$odoo->call($_POST["model"],"action_" . $_POST["state"],array($_POST["args"]["id"])));		
	}
	
	//DELETE
	else if ( isset($_POST["records"]) && isset($_POST["delete"]) && isset($_POST["model"]) && isset($_POST["args"]) && isset($_POST["sessionid"])){
            $odoo->setSession($_POST["sessionid"]);
            $odoo->print_json(true,$odoo->call($_POST["model"],"unlink",array($_POST["args"])));
	}
	
	//UPLOAD FILE
	else if (isset($_POST["upload"])){
		$target_dir = "uploads/";
		$target_file = $target_dir . md5(basename($_FILES["file"]["name"]) . time()) . "." . pathinfo(basename($_FILES["file"]["name"]),PATHINFO_EXTENSION);
		$uploadOk = 1;
		$fileType = pathinfo(basename($_FILES["file"]["name"]),PATHINFO_EXTENSION);
		// Check if file already exists
		if (file_exists($target_file)) {
			throw new Exception('Sorry, file already exists.');
		}
		// Check file size
		if ($_FILES["file"]["size"] > 500000) {
			throw new Exception('Sorry, your file is too large.');
		}
		else {
			if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
				$odoo->print_json(true,array('result'=>'The file '. basename( $_FILES["file"]["name"]). ' has been uploaded.', 'url' => $target_file));
			} else {
				throw new Exception('Sorry, there was an error uploading your file.');
			}
		}  
	}
	
	else{
		throw new Exception('No end point found with that request type!');
	}
	

	//create new customer POST
	//update customer PUT
	//delete customer DELETE
	//add order POST
	//update order PUT
	//delete order DELETE
	//add stock POST
	//update stock PUT
	//delete stock DELETE




	

	//print_r($odoo->getSessionInfo()); //get current session info, returns false if no session exists

	//print_r($odoo->call("res.partner","search",array(array(array('is_company', '=', true),array('customer', '=', true))))); //search customers
				  
	//print_r($odoo->call('res.partner', 'create',array(array('name'=>"New Partner from api")))); //create new customer	  

}catch(Exception $e){
	return $odoo->print_json(false,$e);
}





  
  ?>