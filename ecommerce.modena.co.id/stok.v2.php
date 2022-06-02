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

$default_qty = 1;
if( !is_array($_REQUEST["item"]) ) {$temp_item = $_REQUEST["item"]; unset($_REQUEST["item"]); $_REQUEST["item"][] = $temp_item;}
if( !is_array($_REQUEST["kuantitas"]) ) {$temp_kuantitas = $_REQUEST["kuantitas"]; unset($_REQUEST["kuantitas"]); $_REQUEST["kuantitas"][] = $temp_kuantitas;}
if( !is_array($_REQUEST["preorder"]) ) {$temp_preorder = $_REQUEST["preorder"]; unset($_REQUEST["preorder"]); $_REQUEST["preorder"][] = $temp_preorder;}
foreach( $_REQUEST["item"] as $index=>$itemno ){
    // simpan data kuantitas
    $arr_item[$itemno]["kuantitas"] = $default_qty;
    if( @$_REQUEST["kuantitas"][$index] != "" && is_numeric( $_REQUEST["kuantitas"][$index] ) && $_REQUEST["kuantitas"][$index] > 0 )
        $arr_item[$itemno]["kuantitas"] = $_REQUEST["kuantitas"][$index];
        
    // simpan data status preorder
    $arr_preorder_item[$itemno] = @$_REQUEST["preorder"][$index] != "" && is_numeric($_REQUEST["preorder"][$index]) ? $_REQUEST["preorder"][$index] : 0;
}

// dapatkan gudang
$_REQUEST["gudang"] = __GUDANG_PUSAT__;
if( @$_REQUEST["kota"] != "" ){
    $arr_par = array("c"=>"kebetot", "kota"=>$_REQUEST["kota"]);
    $server_output = panggil_curl(__API__ . "gudang", $arr_par);	
    
    if( @$server_output["gudang"] != "" )   
        $_REQUEST["gudang"] = $server_output["gudang"];
}

// dapatkan stok semuanya
$arr_par = array();
$arr_par["__STOK_AMAN__"] = __STOK_AMAN__;
$arr_par["dealer_id"] = __IDCUST_FG__;
$arr_argumen = array("item", "gudang", "negara", "mata_uang");
foreach( $arr_argumen as $argumen )
    if( @$_REQUEST[$argumen] != "" )    $arr_par[$argumen] = $_REQUEST[$argumen];
//die(__API__ . "?ws=stok_level.v2&". http_build_query($arr_par));
$server_output = panggil_curl(__API__ . "?ws=stok_level.v2&", $arr_par);	

// kuantitas dari order website yang masih terpending (order_status = 0 dan -1)
$quantity_booking_order_total = array();
if( is_array($server_output) && count( $server_output ) > 0 ){
    unset($sql_parameter);
    foreach( array_keys($server_output) as $sku )
        $sql_parameter[] = "'". main::formatting_query_string( $sku ) ."'";
	$sql = "
		select sum(c.kuantitas_order_item_sedang_proses) kuantitas_order_item_sedang_proses, c.sku from 
		ordercustomer a, 
			(select sum(quantity) kuantitas_order_item_sedang_proses, order_id, sku from orderproduct where sku is not null group by order_id, sku) c 
		where 
		a.order_id = c.order_id and
		TIMESTAMPDIFF(MINUTE, a.order_date, CURRENT_TIMESTAMP) < ". $GLOBALS["payment_va_expiration"] ."+5  and 
		order_status < 1 and c.sku in (". implode( ",", array_values($sql_parameter) ) .")
		". @$sqladd ." group by c.sku
	"; 
	$rs = mysql_query($sql);
	if( mysql_num_rows($rs) > 0 ){
		
		while( $data = mysql_fetch_array($rs) )
			$quantity_booking_order_total[ $data["sku"] ] = $data["kuantitas_order_item_sedang_proses"];
	
	}
}

// cek kuota preorder dari DM
$arr_kuota_preorder_dm = $arr_par_item_preorder = $arr_paket_preorder_dm = array();
unset($arr_par);
$rand = rand(0,100000);
$arr_par["c"] = "kebetot";
$arr_par["rand"] = $rand;
$arr_par["dealer_id"] = __IDCUST_FG__;
foreach( $server_output as $kode_produk=>$arr_gudang_produk ){
	if( $arr_preorder_item[ $kode_produk ] > 0 )
	    $arr_par_item_preorder[] = $kode_produk . "_0";
}
$arr_par["item"] = $arr_par_item_preorder;
$arr_par["auth"] = sha1(__KEY_AIR__ . $rand . implode("", array_values($arr_par["item"])));
//die(__API__ . "preorder_kuota" . "?" . http_build_query($arr_par));	
$arr_kuota_preorder_dm = panggil_curl(__API__ . "preorder_kuota", $arr_par);

foreach( $server_output as $__kode_produk=>$arr_gudang_produk ){
	$kode_produk = $arr_gudang_produk[ $_REQUEST["gudang"] ]["itemno_ori"];
	
    $arr_item[$kode_produk]["nama_item"] = $arr_gudang_produk[ $_REQUEST["gudang"] ]["item"];
	$arr_item[$kode_produk]["itemno_negara"] = $arr_gudang_produk[ $_REQUEST["gudang"] ]["itemno"];
	$arr_item[$kode_produk]["harga"] = $arr_gudang_produk[ $_REQUEST["gudang"] ]["harga"];
	$arr_item[$kode_produk]["pricelist"] = $arr_gudang_produk[ $_REQUEST["gudang"] ]["pricelist"];
	$arr_item[$kode_produk]["mata_uang"] = $arr_gudang_produk[ $_REQUEST["gudang"] ]["mata_uang"];
	$arr_item[$kode_produk]["stok"] = $arr_gudang_produk[ $_REQUEST["gudang"] ]["stok"] - @$quantity_booking_order_total[$__kode_produk];
	
	// cek stok gudang cabang
	if( $arr_item[$kode_produk]["stok"] < $arr_item[$kode_produk]["kuantitas"] ){
		
		$gudang_pusat = __GUDANG_PUSAT__;
		$arr_item[$kode_produk]["stok"] = $arr_gudang_produk[ $gudang_pusat ]["stok"] - @$quantity_booking_order_total[$__kode_produk];
		if( $arr_item[$kode_produk]["stok"] <= 0 ){
			$gudang_pusat = __GUDANG_PUSAT_TGN__;
			$arr_item[$kode_produk]["stok"] = $arr_gudang_produk[ $gudang_pusat ]["stok"] - @$quantity_booking_order_total[$__kode_produk];
		}
		
		// cek stok gudang pusat
		if( $arr_item[$kode_produk]["stok"] < $arr_item[$kode_produk]["kuantitas"] ){
		    
		    if( $arr_item[$kode_produk]["stok"] <= 0 ) $arr_item[$kode_produk]["stok"] = 0;
		    
		    // stok kosong
		    if( $arr_preorder_item[$kode_produk] <= 0 )
			    $arr_item[$kode_produk]["kuantitas"] = ($arr_item[$kode_produk]["stok"] > 0 ? $arr_item[$kode_produk]["stok"] : 0);
			    
			// cek preorder
			elseif( $arr_preorder_item[$kode_produk] > 0 ){

                $arr_item[$kode_produk]["stok"] = $arr_kuota_preorder_dm[ $kode_produk ]["kuota"] <= 0 ? 0 : $arr_kuota_preorder_dm[ $kode_produk ]["kuota"];
			    $arr_paket_preorder_dm[ $kode_produk ] = $arr_kuota_preorder_dm[ $kode_produk ]["paket_id"];
			    if( $arr_item[$kode_produk]["stok"] > $arr_item[$kode_produk]["kuantitas"] )
			        //$arr_item[$kode_produk]["kuantitas"] = ($arr_item[$kode_produk]["stok"] > 0 ? $arr_item[$kode_produk]["stok"] : 0);
			        $arr_item[$kode_produk]["kuantitas"] = $arr_item[$kode_produk]["kuantitas"];
			        
			    // cek stok item induk di gudang pusat karena rework stiker di pusat
			    else{
			        
			        $item_id_induk = $arr_gudang_produk[ $gudang_pusat ]["item_induk"];
			        $arr_item[$kode_produk]["stok"] = $server_output[ $item_id_induk ][$gudang_pusat]["stok"];
			        //if( $server_output[ $item_id_induk ][__GUDANG_PUSAT__]["stok"] < $arr_item[$kode_produk]["kuantitas"] )
			         //   $arr_item[$kode_produk]["kuantitas"] = ($arr_item[$kode_produk]["stok"] > 0 ? $arr_item[$kode_produk]["stok"] : 0);
			        if( $arr_item[$kode_produk]["stok"] > $arr_item[$kode_produk]["kuantitas"] )
			            $arr_item[$kode_produk]["kuantitas"] = $arr_item[$kode_produk]["kuantitas"];

			    }
			}
		}
	}
}

// trimming  kode item sesuai dengan yang direquest dan atur sesuai format lama (cek stok v1)
$arr_return = array();
foreach( array_keys( $arr_item ) as $itemno ){
    if( !in_array($itemno, $_REQUEST["item"]) ) 
        unset( $arr_item[ $itemno ] );
    else{
		if( array_key_exists("itemno_negara", $arr_item[ $itemno ]) )
			$arr_return[] = $arr_item[ $itemno ] + array("itemno" => $itemno);
		else {
			unset($arr_item[ $itemno ]["kuantitas"]);
			$arr_return[] = $arr_item[ $itemno ] + array("itemno" => $itemno);
		}
	}
}

header("Content-Type: application/json");
echo json_encode($arr_return);

?>