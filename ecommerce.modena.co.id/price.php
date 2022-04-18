<?
include_once "../lib/var.php";
include_once "lib/cls_main.php"; // lib/cls_coupon_code_karyawan.php, 

function panggil_curl($url, $arr_par){
	$ch = curl_init();
	//echo $url . "?" . http_build_query($arr_par);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arr_par));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, __CURL_TIMEOUT); 
	curl_setopt($ch, CURLOPT_TIMEOUT, __CURL_TIMEOUT);
	curl_setopt($ch, CURLOPT_FAILONERROR, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$server_output = curl_exec ($ch); 
	$server_output = json_decode($server_output, true);	
    return $server_output;    
}

/*
argumen dibutuhkan :
1. item : array|string kode item/sku
2. kota : opsional string; id kota
*/

// otorisasi key
$auth = sha1(__KEY__ . @$_REQUEST["rand"] . sha1( trim( is_array($_REQUEST["item"]) ? implode("|", $_REQUEST["item"]) : $_REQUEST["item"] ) ) ) ;
//die($auth);
if( $auth != @$_REQUEST["auth"] ) die("E1 " . $auth);

if( !is_array($_REQUEST["item"]) ) {$temp_item = $_REQUEST["item"]; unset($_REQUEST["item"]); $_REQUEST["item"][] = $temp_item;}

// dapatkan stok semuanya
$arr_par = array();
$arr_par["__STOK_AMAN__"] = __STOK_AMAN__;
$arr_par["dealer_id"] = __IDCUST_FG__;
$arr_argumen = array("item", "negara", "mata_uang");
foreach( $arr_argumen as $argumen )
    if( @$_REQUEST[$argumen] != "" )    $arr_par[$argumen] = $_REQUEST[$argumen];
//die(__API__ . "?ws=price&". http_build_query($arr_par));
$server_output = panggil_curl(__API__ . "?ws=price&", $arr_par);	

foreach( $server_output as $__kode_produk=>$arr_data_produk ){
	$kode_produk = $__kode_produk;
	
	list($item_1, $item_2) = explode(".", $arr_data_produk["itemno"]);
    $arr_item[$kode_produk]["nama_item"] = $arr_data_produk["item"];
	$arr_item[$kode_produk]["itemno"] = $arr_data_produk["itemno"];
	$arr_item[$kode_produk]["harga_idr"] = $arr_data_produk["harga_idr"];
	$arr_item[$kode_produk]["pricelist_idr"] = $arr_data_produk["pricelist_idr"];
	$arr_item[$kode_produk]["harga_usd"] = $arr_data_produk["harga_usd"];
	$arr_item[$kode_produk]["pricelist_usd"] = $arr_data_produk["pricelist_usd"];
	$arr_item[$kode_produk]["harga_eur"] = $arr_data_produk["harga_eur"];
	$arr_item[$kode_produk]["pricelist_eur"] = $arr_data_produk["pricelist_eur"];
	$arr_item[$kode_produk]["negara"] = substr($item_2, 0,2);
	
}

header("Content-Type: application/json");
echo json_encode($arr_item);

?>