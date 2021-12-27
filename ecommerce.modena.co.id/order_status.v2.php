<?
include_once "../lib/var.php";
include_once "lib/cls_main.php"; 

/*
argumen dibutuhkan :
1. order_no : array|string order_no
*/

// otorisasi key
$auth = sha1(__KEY__ . @$_REQUEST["rand"] . sha1( trim( $_REQUEST["order_no"] ) ) ) ;
//die($auth);
if( $auth != @$_REQUEST["auth"] ) die("E1 " . $auth);

$arr_par = array("c"=>"kebetot", "order_id"=>$_REQUEST["order_no"]);
//die(__API__ . "order_status.v2?" . http_build_query($arr_par));
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, __API__ . "order_status.v2");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arr_par));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, __CURL_TIMEOUT); 
curl_setopt($ch, CURLOPT_TIMEOUT, __CURL_TIMEOUT);
curl_setopt($ch, CURLOPT_FAILONERROR, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$server_output = curl_exec ($ch);
header("Content-Type: application/json");
echo $server_output;

?>