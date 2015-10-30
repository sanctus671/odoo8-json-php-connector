app.controller('MainController', function ($scope, $timeout, $rootScope, $state,$mdUtil, $mdSidenav, OdooService, $mdToast) {
    var init = function init() {     
    };	
    $scope.toggleNav = $mdUtil.debounce(function(){$mdSidenav("left").toggle()},200);
    $scope.logout = function(){
        OdooService.logout().then(function(){
           $state.go("login"); 
        });
    }
    $scope.showToast = function(message){
        $mdToast.show(
          $mdToast.simple()
            .content(message)
            .position("top right")
            .hideDelay(3000)
        );        
    }

    
    
    init(); 

    
    
    });
    
    
app.controller('LoginController', function ($scope, $state, $rootScope, $mdToast,OdooService) {
    var init = function init() {     
        $scope.user = {
            username:"",
            password:"",
            remember:false
        };
        $scope.error = "";
    };	
    

    $scope.login = function(){
        $scope.error = "";
        $scope.loading = "indeterminate";
        OdooService.loginPortal($scope.user.username,$scope.user.password).then(function(){
            $scope.loading = "";
            $state.go("portal");
        },function(data){
            $scope.loading = "";
            $scope.error =  "Incorrect username or password.";
            $mdToast.show($mdToast.simple().content('Failed to login').position("top right").hideDelay(3000));
                        
        });
    };
    
    init(); 
    });
    
    
app.controller('RegisterController', function ($scope, $rootScope,$state,$mdToast,OdooService) {
    var init = function init() {     
        $scope.user = {
            username:"",
            password:"",
            confirmpassword:"",
            email:"",
            company:"",
            type:"buyer"
        }
        $scope.error = "";
    };	
    
    
    $scope.register = function(){
        $scope.error = "";
        $scope.loading = "indeterminate";
        OdooService.register($scope.user.username,$scope.user.password, $scope.user.passwordconfirm, $scope.user.type, $scope.user.email).then(function(){
            $scope.loading = "";
            $mdToast.show($mdToast.simple().content('Registration successful!').position("top right").hideDelay(3000));
            $state.go("portal");
        },function(data){
            $scope.loading = "";
            $scope.error =  "Invalid";
            $mdToast.show($mdToast.simple().content('Failed to register').position("top right").hideDelay(3000));
                        
        });        
    }
    
    init(); 

    
    
    });
    
app.controller('ForgotPasswordController', function ($scope, $state,$mdToast, $rootScope, OdooService) {
    var init = function init() {   
        $scope.user = {email:""};
        $scope.error = "";
    };	
    
    $scope.reset = function(){
        $scope.error = "";
        $scope.loading = "indeterminate";
        OdooService.resetPassword($scope.user.email).then(function(){
            $scope.loading = "";
            $mdToast.show($mdToast.simple().content('A new password has been sent to your address!').position("top right").hideDelay(3000));
            $state.go("login");
        },function(data){
            $scope.loading = "";
            $scope.error =  "Invalid";
            $mdToast.show($mdToast.simple().content('Failed to reset password').position("top right").hideDelay(3000));
                        
        });        
    }    
    
    init(); 

    
    
    });  
    
app.controller('HomeController', function($scope, OdooService){
    var init = function init(){
        $scope.user = {};
        $scope.latest = [];
        $scope.products = [];
        $scope.itemType = "Orders";
    }
 
    $scope.userData = OdooService.getUser();
    $scope.user = $scope.userData.user;
    $scope.partner = $scope.userData.partner;

   
    console.log($scope.userData);
    
    OdooService.getAllData('product.template', 1, 5, '-id').then(function(response){
        console.log(response);
        $scope.products = response.data;        

   console.log($scope.userData);
        if ($scope.userData.user.type === "picker" || $scope.userData.user.type === "grower"){
            $scope.itemType = "Stock";
            OdooService.getAllData('stock.move', 1, 10, '-create_date').then(function(response){
                console.log(response);
                $scope.latest = response.data;

            });        
        }
        else{
            $scope.itemType = "Orders";
            OdooService.getAllData('sale.order', 1, 10, '-create_date').then(function(response){
                console.log(response);
                $scope.latest = response.data;

            });          
        }
    
    });      
    
    $scope.convertDate = function(date){
        if (!date)return "";
        return new Date(date);
    };    
    //get latest orders/stock
    //get summary of key products
    
    
    
    init();
})    
    
app.controller('AccountController', function ($scope, $rootScope, OdooService) {
    var init = function init() {   
        $scope.user = {};
        
    };	
    init(); 
    
    $scope.userData = OdooService.getUser();
    $scope.user = $scope.userData.user;
    $scope.partner = $scope.userData.partner;

    $scope.updateUser = function(){

        angular.extend($scope.user, {"email":$scope.partner.email, "business":$scope.partner.business});
        
        OdooService.updateUser($scope.user).then(function(data){
            console.log(data);
            $scope.$parent.showToast('User Updated!'); 
        },function(){
            $scope.$parent.showToast('Failed to update user'); 
        });
            
    }
    
    
    });     
    
app.controller('StockController', function($scope, $rootScope, $q, $timeout, $mdDialog, OdooService, $mdToast, $mdBottomSheet){
    var init = function init() {     
        $scope.selected = [];
        $scope.query = {
          order: '-id',
          limit: 5,
          offset:0,
          page: 1,
          search:''
        };  
        $scope.products = [];
        console.log($scope.$parent);
    };	
    init();    

  OdooService.getAllData('stock.move', $scope.query.page, $scope.query.limit, $scope.query.order).then(function(response){
      console.log(response);
        $scope.stock = response;
        console.log(response);
        OdooService.getAllData('product.template', 1, 99999, 'name').then(function(response2){
            console.log(response2);
            $scope.products = response2.data;        

        });

  });
  
  
  $scope.getStatusTypes = function () {
    return ['draft', 'cancel', 'confirmed', 'assigned', 'done'];
  };
  
  $scope.changeStatus = function(stock){
        var map = {draft:"draft", cancel: "cancel", confirmed:"confirm", assigned:"assign", done:"done"};
        OdooService.changeState('stock.move', map[stock.state], [stock.id])
  }
  
  $scope.onPageChange = function(page, limit) {
    $scope.query.page = page;
    $scope.query.limit = limit;
    var deferred = $q.defer();
    OdooService.getAllData('stock.move', $scope.query.page, $scope.query.limit, $scope.query.order).then(function(response){
        $scope.stock = response;
        deferred.resolve(response);
    });
    
    return deferred.promise;
  };
  
  $scope.onOrderChange = function(order) {
    var deferred = $q.defer();
    OdooService.getAllData('stock.move', $scope.query.page, $scope.query.limit, $scope.query.order).then(function(response){
        $scope.stock = response;
        $scope.query.order = order;
        deferred.resolve(response);
    });
    
    return deferred.promise;
  };
  
  
  $scope.addItem = function(event){
    $mdDialog.show({
          controller: StockDialogController,
          templateUrl: 'app/partials/dialog/add-stock.html',
          parent: angular.element(document.body),
          targetEvent: event,
          clickOutsideToClose:true,
          locals:{selected:$scope.selected, products:$scope.products}
        }).then(function(newStock){
            OdooService.addData('stock.move',newStock.create).then(function(){
                $scope.$parent.showToast('New item added!');               
                OdooService.getAllData('stock.move', $scope.query.page, $scope.query.limit, $scope.query.order).then(function(response){$scope.stock = response;})
            },function(){
                $scope.$parent.showToast('Failed to add item');       
            });
        });
  };

    $scope.editItem = function(event){
      $mdDialog.show({
            controller: StockDialogController,
            templateUrl: 'app/partials/dialog/edit-stock.html',
            parent: angular.element(document.body),
            targetEvent: event,
            clickOutsideToClose:true,
            locals:{selected:$scope.selected,products:$scope.products}
          }).then(function(stock){
            OdooService.updateData('stock.move',stock.update.ids, stock.update.data, {context: {lang: "en_US", tz: "Pacific/Auckland", uid: 1, params: {action: 173}}}).then(function(){
                $scope.$parent.showToast('Items updated!');              
                OdooService.getAllData('stock.move', $scope.query.page, $scope.query.limit, $scope.query.order).then(function(response){$scope.stock = response;})
            },function(){
                $scope.$parent.showToast('Failed to update items');
            });
        });
    }; 
    
    $scope.removeItem = function(event){
        for (var index in $scope.selected){
            var selectedItem = $scope.selected[index];
            if (selectedItem.state !== "draft"){
                $scope.$parent.showToast('Can only remove items in with status draft'); 
                return;
            }
        }
      $mdDialog.show({
            controller: StockDialogController,
            templateUrl: 'app/partials/dialog/remove-stock.html',
            parent: angular.element(document.body),
            targetEvent: event,
            clickOutsideToClose:true,
            locals:{selected:$scope.selected, products:$scope.products}
          }).then(function(removeStock){
                OdooService.removeData('stock.move',removeStock.remove).then(function(){
                    $scope.$parent.showToast('Items removed!');        
                OdooService.getAllData('stock.move', $scope.query.page, $scope.query.limit, $scope.query.order).then(function(response){$scope.stock = response;});
                },function(){
                    $scope.$parent.showToast('Failed to remove items');
                });
            });
    }; 
    
    $scope.convertDate = function(date){
        if (!date)return "";
        return new Date(date);
    };
    $scope.getProduct = function(stock){
        if ($scope.stock.length < 1){return;}
        if (stock.product_id){
            var stockProductId = stock.product_id[0] -4;
            for (var index in $scope.products){
                var product = $scope.products[index];
                if (product.id === stockProductId){stock.breed = product.name; return stock.breed;}
            }      
        }       
    };    
  

});


function StockDialogController($scope, $mdDialog, $timeout, Upload, WEB_API_URL, selected, products, OdooService) {
    $scope.newStock = {};
    $scope.stock = selected.length > 0 ? angular.copy(selected[0]) : {};
    if ($scope.stock){
        $scope.stock.date_expected = new Date($scope.stock.date_expected);
    }
    $scope.products = [];
    $scope.productsMap = {};
    for (var index in products){
        var product = products[index];
        $scope.products.push(product.name);
        $scope.productsMap[product.name] = product.id +4;
    }
   
    
    $scope.cancel = function() {
      $mdDialog.cancel();
    };
    $scope.create = function() {
      $mdDialog.hide({create:$scope.convertForOdoo($scope.newStock)});
    };
    $scope.confirm = function() {
        var ids = [];
        for (var index in selected){ids.push(selected[index].id);}
        $mdDialog.hide({remove:ids});
    };    
    $scope.update = function() {
        var ids = [];
        for (var index in selected){ids.push(selected[index].id);}      
        console.log(ids);
        $mdDialog.hide({update:{ids:ids,data:$scope.convertForOdoo($scope.stock)}});
    };    
    $scope.uploadFiles = function(file) {
        if (file && !file.$error) {
            $scope.uploading = "indeterminate";
            file.upload = Upload.upload({
                url: WEB_API_URL,
                data: {file: file,upload:true}
            });

            file.upload.then(function (response) {
                $timeout(function () {
                    $scope.uploading = "";
                    console.log(response);
                    if (response.data.result === true){
                        file.result = response.data;
                        $scope.stock.x_img_url = WEB_API_URL + response.data.data.url;
                        $scope.newStock.x_img_url = WEB_API_URL + response.data.data.url;
                    }
                    else{
                        $scope.errorMsg = response.data;
                    }
                });
            }, function (response) {
                $scope.uploading = "";
                if (response.status > 0)
                    $scope.errorMsg = response.status + ': ' + response.data;
            });
        }   
    } //cr, uid, id, field, value, arg
    $scope.convertForOdoo = function(object){
        return {
            name:object.name,
            product_id:$scope.productsMap[object.breed],
            product_tmpl_id:$scope.productsMap[object.breed] - 4,
            product_uom_qty:object.product_uom_qty,
            date_expected:object.date_expected,
            location_id:2,
            location_dest_id:12,
            product_uom:1,
            x_quality:object.x_quality,
            x_img_url:object.x_img_url,
            x_notes:object.x_notes

        }
    };

}



app.controller('ProductsController', function($scope, $rootScope, $q, $timeout, $mdDialog, OdooService, $mdToast, $mdBottomSheet){
    var init = function init() {     
        $scope.selected = [];
        $scope.query = {
          order: '-id',
          limit: 5,
          offset:0,
          page: 1,
          search:''
        };  
        $scope.products = [];
        console.log($scope.$parent);
    };	
    init();    

  OdooService.getAllData('product.template', $scope.query.page, $scope.query.limit, $scope.query.order).then(function(response){
      console.log(response);
        $scope.products = response;
        

  });
  
  
  $scope.onPageChange = function(page, limit) {
    $scope.query.page = page;
    $scope.query.limit = limit;
    var deferred = $q.defer();
    OdooService.getAllData('product.template', $scope.query.page, $scope.query.limit, $scope.query.order).then(function(response){
        $scope.products = response;
        deferred.resolve(response);
    });
    
    return deferred.promise;
  };
  
  $scope.onOrderChange = function(order) {
    var deferred = $q.defer();
    OdooService.getAllData('product.template', $scope.query.page, $scope.query.limit, $scope.query.order).then(function(response){
        $scope.products = response;
        $scope.query.order = order;
        deferred.resolve(response);
    });
    
    return deferred.promise;
  };  
  
});



app.controller('OrdersController', function($scope, $rootScope, $q, $timeout, $mdDialog, OdooService, $mdToast, $mdBottomSheet){
    var init = function init() {     
        $scope.selected = [];
        $scope.query = {
          order: '-id',
          limit: 5,
          offset:0,
          page: 1,
          search:''
        };  
        $scope.products = [];
        console.log($scope.$parent);
    };	
    init();    

  OdooService.getAllData('sale.order', $scope.query.page, $scope.query.limit, $scope.query.order).then(function(response){
      console.log(response);
        $scope.orders = response;
        OdooService.getAllData('product.template', 1, 99999, 'name').then(function(response2){
            console.log(response2);
            $scope.products = response2.data;        

        });

  });
  
  
  $scope.onPageChange = function(page, limit) {
    $scope.query.page = page;
    $scope.query.limit = limit;
    var deferred = $q.defer();
    OdooService.getAllData('sale.order', $scope.query.page, $scope.query.limit, $scope.query.order).then(function(response){
        $scope.orders  = response;
        deferred.resolve(response);
    });
    
    return deferred.promise;
  };
  
  $scope.onOrderChange = function(order) {
    var deferred = $q.defer();
    OdooService.getAllData('sale.order', $scope.query.page, $scope.query.limit, $scope.query.order).then(function(response){
        $scope.orders = response;
        $scope.query.order = order;
        deferred.resolve(response);
    });
    
    return deferred.promise;
  };  
  
  $scope.getStatusTypes = function () {
    return ['draft', 'cancel', 'sent', 'progress', 'manual', 'done'];
  };
  
  $scope.changeStatus = function(order){
        var map = {draft:"draft", cancel: "cancel", progress:"progress", manual:"manual", sent:"sent", done:"done"};
        console.log(map[order.state]);
        OdooService.changeState('sale.order', map[order.state], [order.id]);
  }  
  
    $scope.getProduct = function(order){
        if ($scope.orders.length < 1){return;}
        if (order.product_id){
            var orderProductId = order.product_id[0] -4;
            for (var index in $scope.products){
                var product = $scope.products[index];
                if (product.id === orderProductId){order.breed = product.name; return order.breed;}
            }      
        }       
    };  
    
    $scope.convertDate = function(date){
        if (!date)return "";
        return new Date(date);
    };  
    
  $scope.addItem = function(event){
    $mdDialog.show({
          controller: OrderDialogController,
          templateUrl: 'app/partials/dialog/add-order.html',
          parent: angular.element(document.body),
          targetEvent: event,
          clickOutsideToClose:true,
          locals:{selected:$scope.selected, products:$scope.products}
        }).then(function(newOrder){
            OdooService.addData('sale.order',newOrder.create).then(function(){
                $scope.$parent.showToast('New item added!');               
                OdooService.getAllData('sale.order', $scope.query.page, $scope.query.limit, $scope.query.order).then(function(response){$scope.orders = response;})
            },function(){
                $scope.$parent.showToast('Failed to add item');       
            });
        });
  };

    $scope.editItem = function(event){
      $mdDialog.show({
            controller: OrderDialogController,
            templateUrl: 'app/partials/dialog/edit-order.html',
            parent: angular.element(document.body),
            targetEvent: event,
            clickOutsideToClose:true,
            locals:{selected:$scope.selected,products:$scope.products}
          }).then(function(order){
            OdooService.updateData('sale.order',order.update.ids, order.update.data, {context: {lang: "en_US", tz: "Pacific/Auckland", uid: 1, params: {action: 380}}}).then(function(){
                $scope.$parent.showToast('Items updated!');              
                OdooService.getAllData('sale.order', $scope.query.page, $scope.query.limit, $scope.query.order).then(function(response){$scope.orders = response;})
            },function(){
                $scope.$parent.showToast('Failed to update items');
            });
        });
    }; 
    
    $scope.removeItem = function(event){
        for (var index in $scope.selected){
            var selectedItem = $scope.selected[index];
            if (selectedItem.state !== "draft"){
                $scope.$parent.showToast('Can only remove items in with status draft'); 
                return;
            }
        }
      $mdDialog.show({
            controller: OrderDialogController,
            templateUrl: 'app/partials/dialog/remove-order.html',
            parent: angular.element(document.body),
            targetEvent: event,
            clickOutsideToClose:true,
            locals:{selected:$scope.selected, products:$scope.products}
          }).then(function(removeOrder){
                OdooService.removeData('sale.order',removeOrder.remove).then(function(){
                    $scope.$parent.showToast('Items removed!');        
                OdooService.getAllData('sale.order', $scope.query.page, $scope.query.limit, $scope.query.order).then(function(response){$scope.orders = response;});
                },function(){
                    $scope.$parent.showToast('Failed to remove items');
                });
            });
    };

    $scope.viewProducts = function(event, order){
        console.log("here");
        $mdDialog.show({
              controller: OrderDialogController,
              templateUrl: 'app/partials/dialog/view-products.html',
              parent: angular.element(document.body),
              targetEvent: event,
              clickOutsideToClose:true,
              locals:{selected:[order], products:$scope.products}
            })      
    }    
  
});


function OrderDialogController($scope, $mdDialog, $timeout, Upload, WEB_API_URL, selected, products, OdooService) {
    $scope.user = OdooService.getUser();
    $scope.newOrder = {lineItems:[{}], date_order:new Date()};
    console.log(selected);
    $scope.order = selected.length > 0 ? angular.copy(selected[0]) : {};
    $scope.lineItems = [];
    $scope.query = {};
    if ($scope.order){
        $scope.order.date_order = new Date($scope.order.date_order);
        $scope.query.order = 'name';
        $scope.order.lineItems = [{}];
        OdooService.getData("sale.order.line", $scope.order.order_line,["sequence","delay","state","th_weight","product_packaging","product_id","name","product_uom_qty","product_uom","product_uos_qty","product_uos","route_id","price_unit","tax_id","discount","price_subtotal"]).then(function(data){
            $scope.order.lineItems = data;
            $scope.lineItems = data;
        });
    }
    
    
    
    $scope.products = [];
    $scope.productsMap = {};
    for (var index in products){
        var product = products[index];
        $scope.products.push(product.name);
        $scope.productsMap[product.name] = product.id +4;
    }

    $scope.addLineItem = function(lineItems){
        lineItems.push({});
    }
    
    $scope.removeLineItem = function(index, lineItems){
        if (lineItems.length < 2){return;}
        if (lineItems[index].id){
            OdooService.removeData('sale.order.line',[lineItems[index].id]).then(function(){
                lineItems.splice(index,1);
            });
        }
        else{
            lineItems.splice(index,1);
        }
    }   
    
    
    $scope.cancel = function() {
      $mdDialog.cancel();
    };
    $scope.create = function() {
      for (var index in $scope.newOrder.lineItems){if (!$scope.newOrder.lineItems[index].name || !$scope.newOrder.lineItems[index].product_uom_qty){return;}}
      $mdDialog.hide({create:$scope.convertForOdoo($scope.newOrder)});
    };
    $scope.confirm = function() {
        var ids = [];
        for (var index in selected){ids.push(selected[index].id);}
        $mdDialog.hide({remove:ids});
    };    
    $scope.update = function() {
        for (var index in $scope.order.lineItems){if (!$scope.order.lineItems[index].name || !$scope.order.lineItems[index].product_uom_qty){return;}}
        var ids = [];
        for (var index in selected){ids.push(selected[index].id);}      
        console.log(ids);
        $mdDialog.hide({update:{ids:ids,data:$scope.convertForOdoo($scope.order)}});
    };    
    


     //cr, uid, id, field, value, arg
    $scope.convertForOdoo = function(object){
        //important for odoo:
        var order = {"partner_id":$scope.user.user.partnerid,"partner_invoice_id":$scope.user.user.partnerid,"partner_shipping_id":$scope.user.user.partnerid,"project_id":false, "client_order_ref":false,"warehouse_id":1,"pricelist_id":1,"incoterm":false,"picking_policy":"direct","order_policy":"manual","user_id":1,"section_id":false,"origin":false,"payment_term":false,"fiscal_position":false,"message_follower_ids":false,"message_ids":false};
        order["date_order"] = object.date_order;
        order["note"] = object.note;
        order["order_line"] = [];
        for (var index in object.lineItems){
            var line = object.lineItems[index];
            if (line.id){
                order.order_line.push([1,line.id,{"product_uom_qty":line.product_uom_qty,"product_id":$scope.productsMap[line.name],"name":line.name}]);               
            }
            else{
                order.order_line.push([0,false,{"delay":7,"th_weight":0,"product_packaging":false,"product_id":$scope.productsMap[line.name],"name":line.name,"product_uom_qty":line.product_uom_qty,"product_uom":1,"product_uos_qty":1,"product_uos":false,"route_id":false,"price_unit":100,"tax_id":[[6,false,[]]],"discount":0}]);
            }
        }         
        return order;
    };

}


app.controller('ReportsController', function($scope, $rootScope, $q, $timeout, $mdDialog, OdooService, $mdToast, $mdBottomSheet){
    $scope.generateReport = function(){
        console.log("here");
        OdooService.getReport("account.invoice", [["type", "=", "out_invoice"], ["state", "=", "open"]]).then(function(data){
            console.log(data);
        });
    }
});