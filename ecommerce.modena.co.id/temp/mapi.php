<?

$order_id = $_REQUEST["order_id"];
$transaction = $_REQUEST["transaction"];

define("__API__", "https://ecommerce.modena.co.id/");
define("__KEY__", "KopiKapalApi17");
define("__PAYMENTOK__", "paymentok");

// hanya menangani untuk MAPI (dari website dan chatbot)
if( strtolower(substr($order_id,0,4)) == "mapi" ){
    
    if( in_array(strtolower($transaction), array("settlement", "capture") ) ){
        
        // call api cart_sync dengan mode = payment_ok
        $rand = rand(10000,99999);        
        $arr_par["c"] = "cart_sync";
        $arr_par["order_no"] = $order_id;
        $arr_par["mode"] = __PAYMENTOK__;
        $arr_par["rand"] = $rand;
        $arr_par["auth"] = sha1( __KEY__ . $rand . trim(sha1($order_id)) . __PAYMENTOK__ );

        $ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, __API__ );
    	curl_setopt($ch, CURLOPT_POST, true);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arr_par));
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($ch, CURLOPT_FAILONERROR, true);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $server_output = curl_exec ($ch); 
        $server_output = json_decode($server_output, true);		

        print_r($server_output);
    }
    
    exit;
}

?>