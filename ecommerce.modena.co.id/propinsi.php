<?
include_once "../lib/var.php";
include_once "lib/cls_main.php"; // lib/cls_coupon_code_karyawan.php, 

define("__API__", "https://dm.modena.com/ws/");

$arr_par = array();
$arr_argumen = array("mode");
foreach( $arr_argumen as $argumen )
    if( @$_REQUEST[$argumen] != "" )    $arr_par[$argumen] = $_REQUEST[$argumen];
    
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, __API__ . "?ws=propinsi");
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