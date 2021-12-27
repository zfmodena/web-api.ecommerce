<?
include_once "../lib/var.php";
include_once "lib/cls_main.php"; // lib/cls_coupon_code_karyawan.php, 

/*
argumen dibutuhkan :
1. item : array|string kode item/sku
2. kota : opsional string; id kota
*/

// otorisasi key
$auth = sha1(__KEY__ . @$_REQUEST["rand"] . sha1( trim( is_array($_REQUEST["item"]) ? implode("|", $_REQUEST["item"]) : $_REQUEST["item"] ) ) ) ;
//die($auth);
if( $auth != @$_REQUEST["auth"] ) die("E1 " . $auth);

$_REQUEST["cabang"] = "GDGPST";
if( @$_REQUEST["kota"] != "" ){
    $arr_par = array("c"=>"kebetot", "kota"=>$_REQUEST["kota"]);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, __API__ . "gudang");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arr_par));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, __CURL_TIMEOUT); 
    curl_setopt($ch, CURLOPT_TIMEOUT, __CURL_TIMEOUT);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $server_output = curl_exec ($ch);
    $server_output = json_decode($server_output, true);		
    
    if( @$server_output["gudang"] != "" )   
        $_REQUEST["cabang"] = $server_output["gudang"];
}
    

$arr_par = array();
$arr_par["__STOK_AMAN__"] = __STOK_AMAN__;
$arr_argumen = array("item", "cabang");
foreach( $arr_argumen as $argumen )
    if( @$_REQUEST[$argumen] != "" )    $arr_par[$argumen] = $_REQUEST[$argumen];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, __API__ . "?ws=stok_level");
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