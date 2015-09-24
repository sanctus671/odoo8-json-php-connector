<?php
require_once("odooconnector.class.php");

$odoo = new OdooConnector("http://test.triotech.co.nz:8069", "flowers");
print_r($odoo->print_json(true,$odoo->login("flowers","admin","admin"))); //login to odoo
try{
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST))
		$_POST = (array) json_decode(file_get_contents('php://input'), true);

	if ($_SERVER['REQUEST_METHOD'] == 'PUT')
		$_PUT = (array) json_decode(file_get_contents('php://input'), true);

	if ($_SERVER['REQUEST_METHOD'] == 'DELETE'){
		$_DELETE = $_REQUEST;	
		}

	//login GET username, password
	if (isset($_GET["server"])){
		$odoo->print_json(true,$odoo->getServerInfo());
	}
		
	//login GET username, password
	else if (isset($_GET["login"]) && isset($_GET["username"]) && isset($_GET["password"])){
		$odoo->print_json(true,$odoo->login($_GET["username"],$_GET["password"]));
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
	else if (isset($_GET["records"]) && isset($_GET["model"]) && isset($_GET["args"]) && isset($_GET["sessionid"])){
		$odoo->print_json(true,$odoo->call($_GET["model"],"search_read",json_decode($_GET["args"]),json_decode($_GET["kwargs"])));
	}
	//POST
	else if (isset($_POST["records"]) && isset($_POST["model"]) && isset($_POST["args"]) && isset($_POST["sessionid"])){
		$odoo->print_json(true,$odoo->call($_POST["create"],"create",json_decode($_POST["args"]),json_decode($_POST["kwargs"])));
	}
	//PUT
	else if (isset($_PUT["records"]) && isset($_PUT["model"]) && isset($_PUT["args"]) && isset($_PUT["sessionid"])){
		$odoo->print_json(true,$odoo->call($_PUT["model"],"write",json_decode($_PUT["args"]),json_decode($_PUT["kwargs"])));
	}
	//DELETE
	else if ( isset($_DELETE["records"]) && isset($_DELETE["model"]) && isset($_DELETE["args"]) && isset($_DELETE["sessionid"])){
		$odoo->print_json(true,$odoo->call($_DELETE["model"],"unlink",json_decode($_DELETE["args"]),json_decode($_DELETE["kwargs"])));
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