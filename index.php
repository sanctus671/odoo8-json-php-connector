<?php

require_once("odooconnector.class.php");
$odoo = new OdooConnector("http://test.triotech.co.nz:8069", "flowers");
//$odoo->print_json(true,$_POST);
try{
    
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST))
		$_POST = (array) json_decode(file_get_contents('php://input'), true);


	//login GET username, password
	if (isset($_GET["server"])){
		$odoo->print_json(true,$odoo->getServerInfo());
	}
		
	//login GET username, password
	else if (isset($_GET["loginportal"]) && isset($_GET["username"]) && isset($_GET["password"])){
		$odoo->print_json(true,$odoo->loginPortal($_GET["username"],$_GET["password"]));
            //$odoo->print_json(true,$odoo->login("flowers","admin","admin"));
	}
        
	//loginportal GET username, password
	else if (isset($_GET["login"]) && isset($_GET["username"]) && isset($_GET["password"])){
		$odoo->print_json(true,$odoo->login($_GET["username"],$_GET["password"]));
            //$odoo->print_json(true,$odoo->login("flowers","admin","admin"));
	}        
	//logout GET sessionid
	else if (isset($_GET["logout"]) && isset($_GET["sessionid"])){
		$odoo->setSession($_GET["sessionid"]);
		$odoo->print_json(true,$odoo->logout(true));
	}
        //register
	else if (isset($_POST["register"]) && isset($_POST["username"]) && isset($_POST["password"]) && isset($_POST["passwordconfirm"])){
            $odoo->print_json(true,$odoo->register($_POST));
	}    
        //reset password
        else if (isset($_POST["resetpassword"]) && isset($_POST["email"])){
            $odoo->print_json(true,$odoo->resetPassword($_POST["email"]));
        }
        //update user
	else if (isset($_POST["updateuser"]) && isset($_POST["sessionid"])){
            $odoo->setSession($_POST["sessionid"]);
            $odoo->print_json(true,$odoo->updateUser($_POST));
	}         
        
	//get user info GET sessionid
	else if (isset($_GET["user"]) && isset($_GET["sessionid"])){
		$odoo->setSession($_GET["sessionid"]);
		$odoo->print_json(true,$odoo->getSessionInfo());
	}
	
	//get specials
	else if (isset($_GET["specials"]) && isset($_GET["sessionid"])){
		$odoo->setSession($_GET["sessionid"]);
		$odoo->print_json(true,$odoo->getSpecials());
	}	
	//update specials
	else if (isset($_POST["updatespecial"]) && isset($_POST["sessionid"])){
		$odoo->setSession($_POST["sessionid"]);
		$odoo->print_json(true,$odoo->updateSpecial($_POST["id"], $_POST["productid"], $_POST["colour"]));
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
                
                elseif (isset($_GET["partnerid"])){
                    $search = array(array("partner_id", "=", (int) $_GET["partnerid"]));
                }
		$count = $odoo->call($_GET["model"],'search_count',array($search));
		$data = $odoo->call($_GET["model"],"search_read",array($search),array("offset"=>$offset,"limit"=>$limit,"order"=>$order));
                foreach($data["result"] as $key => $item){ //get local user info                
                    if (count($item["partner_id"]) > 0){
                        $data["result"][$key]["user_data"] = $odoo->getLocalUser($item["partner_id"][0]);
                    }
                    else if ($_GET["model"] === "res.partner"){
                        $data["result"][$key]["user_data"] = $odoo->getLocalUser($item["id"]);
                    }
                }
		$odoo->print_json(true,array("count"=>$count["result"],"data"=>$data["result"]));
	}
        //GET WITH SEARCH
	else if (isset($_POST["records"]) && isset($_POST["search"]) && isset($_POST["searchdata"]) && isset($_POST["model"]) && isset($_POST["args"]) && isset($_POST["sessionid"])){
		$odoo->setSession($_POST["sessionid"]);
		$offset = 0;$limit = 5;$order = "id DESC";
		if (isset($_POST["offset"])){$offset = (int) $_POST["offset"];}
		if (isset($_POST["limit"])){$limit = (int) $_POST["limit"];}     
                if (isset($_POST["order"])){$order = $_POST["order"];}
		$search = $_POST["searchdata"];				
		if ($_POST["model"] === "product.template"){
                    array_push($search, array("uom_id", "ilike", 1));
		}                
                elseif (isset($_POST["partnerid"])){
                    array_push($search, array("partner_id", "ilike", (int) $_POST["partnerid"]));
                }
		$count = $odoo->call($_POST["model"],'search_count',array($search));
		$data = $odoo->call($_POST["model"],"search_read",array($search),array("offset"=>$offset,"limit"=>$limit,"order"=>$order));
                foreach($data["result"] as $key => $item){ //get local user info                
                    if (count($item["partner_id"]) > 0){
                        $data["result"][$key]["user_data"] = $odoo->getLocalUser($item["partner_id"][0]);
                    }
                    else if ($_POST["model"] === "res.partner"){
                        $data["result"][$key]["user_data"] = $odoo->getLocalUser($item["id"]);
                    }
                }
		$odoo->print_json(true,array("count"=>$count["result"],"data"=>$data["result"]));
	}        
        //GET SINGLE/SPECIFIC ID
	else if (isset($_POST["records"]) && isset($_POST["single"]) && isset($_POST["model"]) && isset($_POST["sessionid"])){
		$odoo->setSession($_POST["sessionid"]);		
		$data = $odoo->call($_POST["model"],"read",$_POST["single"]);
		$odoo->print_json(true,$data);
	}	
        
	else if (isset($_POST["report"]) && isset($_POST["model"]) && isset($_POST["sessionid"]) && isset($_POST["args"])){
		$odoo->setSession($_GET["sessionid"]);

		$data = $odoo->call($_GET["model"],"search",array($_POST["args"]) );
		$odoo->print_json(true,array("count"=>$count["result"],"data"=>$data["result"]));
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
	//PUT STATE/STATUS
	else if (isset($_POST["records"]) && isset($_POST["state"]) && isset($_POST["model"]) && isset($_POST["args"]) && isset($_POST["sessionid"])){
            $odoo->setSession($_POST["sessionid"]);
            $odoo->print_json(true,$odoo->callAction($_POST["model"],"action_" . $_POST["state"],array($_POST["args"]["id"])));		
	}
	
	//DELETE
	else if ( isset($_POST["records"]) && isset($_POST["delete"]) && isset($_POST["model"]) && isset($_POST["args"]) && isset($_POST["sessionid"])){
            $odoo->setSession($_POST["sessionid"]);
            $odoo->print_json(true,$odoo->call($_POST["model"],"unlink",array($_POST["args"])));
	}
        
        //SEND RESULTS
        else if (isset($_POST["auction"]) && isset($_POST["results"]) && isset($_POST["args"]) && isset($_POST["sessionid"])){
            $session = $odoo->setSession($_POST["sessionid"]);
            $odoo->print_json(true,$odoo->sendResults($_POST["args"]["collated"], $_POST["args"]["notSold"], $_POST["args"]["partners"]));            
        }        
        
        
        //CREATE INVOICE
        else if (isset($_POST["createinvoice"]) && isset($_POST["args"]) && isset($_POST["kwargs"]) && isset($_POST["sessionid"])){
            $session = $odoo->setSession($_POST["sessionid"]);
            $kwargs = array("context"=>array_merge($_POST["kwargs"]["context"], $session["result"]["user_context"]));
            $odoo->print_json(true,$odoo->call("sale.advance.payment.inv","create",$_POST["args"], $kwargs));            
        }
        
        //ASSIGN INVOICE
        else if (isset($_POST["assigninvoice"]) && isset($_POST["args"]) && isset($_POST["sessionid"])){
            $odoo->setSession($_POST["sessionid"]);
            $odoo->print_json(true,$odoo->callAction("sale.advance.payment.inv","create_invoices",$_POST["args"]));            
        }  
        
        //SEND INVOICE
        else if (isset($_POST["sendinvoice"]) && isset($_POST["id"]) && $_POST["kwargs"] && isset($_POST["sessionid"])){
            $session = $odoo->setSession($_POST["sessionid"]);
            $kwargs = array("context"=>array_merge($_POST["kwargs"]["context"], $session["result"]["user_context"]));
            //2 parts: validate invoice -> send email
            $odoo->callWorkflow("account.invoice",$_POST["id"],"invoice_open");
            
            //$odoo->print_json(true,$odoo->callWorkflow("sale.advance.payment.inv","create_invoices",$_POST["args"]));   

            //create invoice template
            $messageKwargs = array("active_model"=>"account.invoice", "default_composition_mode"=> "comment", "default_model"=> "account.invoice", "default_res_id"=> $_POST["id"], "default_template_id"=> 9, "default_use_template"=> true, "mark_invoice_as_sent"=> true, "type"=> "out_invoice");
            
            $messageKwargsMerge = array_merge($kwargs["context"], $messageKwargs);
            $kwargs["context"] = $messageKwargsMerge;
            $messageData = $odoo->call("mail.compose.message","default_get",array(array("no_auto_thread","mail_server_id","notify","subject","composition_mode","attachment_ids","is_log","parent_id","partner_ids","res_id","body","model","use_active_domain","email_from","reply_to","template_id")),$kwargs);

            $invoiceArgs = $messageData["result"];
            
            $emailData = $odoo->call("mail.compose.message", "create", array($invoiceArgs), $kwargs);
            $emailArgs = $emailData["result"];
        
            $odoo->print_json($odoo->callAction("mail.compose.message", "send_mail", array(array($emailArgs, $kwargs["params"] ))));
            
            
            
            
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


}catch(Exception $e){
	return $odoo->print_json(false,$e->getMessage());
}





  
  ?>