<?
include_once "../lib/var.php";
include_once "lib/cls_main.php"; // lib/cls_coupon_code_karyawan.php, 

/*
menampilkan stok barang dengan urutan dari gudang stok terbanyak dlu yang ditampilkan
opsional isset( all_gudang ) => tampilkan semua gudang dengan stok > 0, default akan tampilkan stok dari gudang terbanyak saja
*/

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
3. dealer_id : opsional
*/
$data = json_decode(file_get_contents('php://input'), true);
if( is_array( $data)){
	foreach( $data as $d=>$v ){
		if($d == "items"){
			foreach( $v as $item ){
				$_REQUEST["item"][] = $item["item"];
				$_REQUEST["preorder"][] = $item["preorder"];
				$_REQUEST["kuantitas"][] = $item["kuantitas"];
			}
		}
		else
			$_REQUEST[$d] = $v;
		
	}
} 

// otorisasi key
$auth = sha1(__KEY__ . @$_REQUEST["rand"] . sha1( trim( is_array($_REQUEST["item"]) ? implode("|", $_REQUEST["item"]) : $_REQUEST["item"] ) ) ) ;
//die($auth);
if( $auth != @$_REQUEST["auth"] ) die("E1 " . $auth);

$arr_available_grade = ["B","K"];

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

$dealer_id = isset( $_REQUEST["dealer_id"] ) && $_REQUEST["dealer_id"] != "" ? $_REQUEST["dealer_id"] : __IDCUST_FG__;

// dapatkan gudang : pertama ambil dari parameter gudang .. kalau tidak ada parameter gudang, maka ambil dealer_id, kalau tidak ada dealer_id so ambil dari parameter kota
if(!isset($_REQUEST["gudang"]) || $_REQUEST["gudang"] == ""){
	$_REQUEST["gudang"] = __GUDANG_PUSAT__;
	if( @$_REQUEST["dealer_id"] != "" ){
		$arr_par = array("c"=>"kebetot", "dealer_id"=>$_REQUEST["dealer_id"]);
			$server_output = panggil_curl(__API__ . "gudang-dealer", $arr_par);	
			
			if( @$server_output["gudang"] != "" )   
				$_REQUEST["gudang"] = $server_output["gudang"];
	}else{
		if( @$_REQUEST["kota"] != "" ){
			$arr_par = array("c"=>"kebetot", "kota"=>$_REQUEST["kota"]);
			$server_output = panggil_curl(__API__ . "gudang", $arr_par);	
			
			if( @$server_output["gudang"] != "" )   
				$_REQUEST["gudang"] = $server_output["gudang"];
		}
	}
}

// untuk setting grade gudang sesuai request
if( isset($_REQUEST["grade"]) && in_array($_REQUEST["grade"], $arr_available_grade) ){
	$_REQUEST["gudang"] = substr($_REQUEST["gudang"], 0, strlen($_REQUEST["gudang"])-1) . $_REQUEST["grade"];
}

// dapatkan stok semuanya
$arr_par = array();
$arr_par["__STOK_AMAN__"] = 0;//__STOK_AMAN__;
$arr_par["dealer_id"] = $dealer_id;
$arr_argumen = array("item", "gudang", "negara", "mata_uang", "all_gudang");
if( isset($_REQUEST["grade"]) && in_array($_REQUEST["grade"], $arr_available_grade) ){
	array_push($arr_argumen, grade);
}
foreach( $arr_argumen as $argumen )
    if( @$_REQUEST[$argumen] != "" )    $arr_par[$argumen] = $_REQUEST[$argumen];
//die(__API__ . "?ws=stok_level.v4&". http_build_query($arr_par));
$server_output = panggil_curl(__API__ . "?ws=stok_level.v4&", $arr_par);	

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
$arr_par["dealer_id"] = $dealer_id;
foreach( $server_output as $__kode_produk=>$arr_gudang_produk ){
	$kode_produk = $arr_gudang_produk[ $_REQUEST["gudang"] ]["itemno_ori"];
	if( $arr_preorder_item[ $kode_produk ] > 0 ){
	    $arr_par_item_preorder[] = $__kode_produk . "_0";
	}
}
$arr_par["item"] = $arr_par_item_preorder;
$arr_par["auth"] = sha1(__KEY_AIR__ . $rand . implode("", array_values($arr_par["item"])));
//die(__API__ . "preorder_kuota" . "?" . http_build_query($arr_par));	
$arr_kuota_preorder_dm = panggil_curl(__API__ . "preorder_kuota", $arr_par);


if( isset($_REQUEST["all_gudang"]) ){
	
	$arr_item_ = [];
	$index = 0;
	foreach( $server_output as $__kode_produk=>$arr_gudang_produk ){
		$kode_produk = $arr_gudang_produk[ $_REQUEST["gudang"] ]["itemno_ori"];
	
		foreach( array_values($arr_gudang_produk) as $gudang_produk ){
			$stok = $gudang_produk["stok"] - @$quantity_booking_order_total[$__kode_produk];
			if( ($gudang_produk["stok"] - @$quantity_booking_order_total[$__kode_produk]) < $arr_item[$kode_produk]["kuantitas"] ){
				$stok = 0;
			}
			$arr_item_[$index]["nama_item"] = $gudang_produk["item"];
			$arr_item_[$index]["itemno_negara"] = $gudang_produk["itemno"];
			$arr_item_[$index]["harga"] = $gudang_produk["harga"];
			$arr_item_[$index]["pricelist"] = $gudang_produk["pricelist"];
			$arr_item_[$index]["mata_uang"] = $gudang_produk["mata_uang"];
			$arr_item_[$index]["stok"] = $stok;
			$arr_item_[$index]["gudang"] = $gudang_produk["kode_gudang"];	
			$arr_item_[$index]["itemno"] = $kode_produk;	
			$index++;
		}		
	}
	$arr_return = $arr_item_;
	
}else{

	foreach( $server_output as $__kode_produk=>$arr_gudang_produk ){
		$kode_produk = $arr_gudang_produk[ $_REQUEST["gudang"] ]["itemno_ori"];
		
		$arr_item[$kode_produk]["nama_item"] = $arr_gudang_produk[ $_REQUEST["gudang"] ]["item"];
		$arr_item[$kode_produk]["itemno_negara"] = $arr_gudang_produk[ $_REQUEST["gudang"] ]["itemno"];
		$arr_item[$kode_produk]["harga"] = $arr_gudang_produk[ $_REQUEST["gudang"] ]["harga"];
		$arr_item[$kode_produk]["pricelist"] = $arr_gudang_produk[ $_REQUEST["gudang"] ]["pricelist"];
		$arr_item[$kode_produk]["mata_uang"] = $arr_gudang_produk[ $_REQUEST["gudang"] ]["mata_uang"];
		$arr_item[$kode_produk]["stok"] = $arr_gudang_produk[ $_REQUEST["gudang"] ]["stok"] - @$quantity_booking_order_total[$__kode_produk];
		$arr_item[$kode_produk]["gudang"] = $_REQUEST["gudang"];
		
		// cek stok gudang cabang
		if( $arr_item[$kode_produk]["stok"] < $arr_item[$kode_produk]["kuantitas"] ){
			
			$gudang_pusat = __GUDANG_PUSAT__;
			// untuk setting grade gudang sesuai request
			if( isset($_REQUEST["grade"]) && in_array($_REQUEST["grade"], $arr_available_grade) ){
				$gudang_pusat = substr($gudang_pusat, 0, strlen($gudang_pusat)-1) . $_REQUEST["grade"];
			}

			$arr_item[$kode_produk]["stok"] = $arr_gudang_produk[ $gudang_pusat ]["stok"] - @$quantity_booking_order_total[$__kode_produk];
			if( $arr_item[$kode_produk]["stok"] <= 0 ){
				$gudang_pusat = __GUDANG_PUSAT_TGN__;
				// untuk setting grade gudang sesuai request
				if( isset($_REQUEST["grade"]) && in_array($_REQUEST["grade"], $arr_available_grade) ){
					$gudang_pusat = substr($gudang_pusat, 0, strlen($gudang_pusat)-1) . $_REQUEST["grade"];
				}
			
				$arr_item[$kode_produk]["stok"] = $arr_gudang_produk[ $gudang_pusat ]["stok"] - @$quantity_booking_order_total[$__kode_produk];
				
				if( $arr_item[$kode_produk]["stok"] <= 0 ){
					$gudang_pusat = __GUDANG_PUSAT_JTK__;
					// untuk setting grade gudang sesuai request
					if( isset($_REQUEST["grade"]) && in_array($_REQUEST["grade"], $arr_available_grade) ){
						$gudang_pusat = substr($gudang_pusat, 0, strlen($gudang_pusat)-1) . $_REQUEST["grade"];
					}
				
					$arr_item[$kode_produk]["stok"] = $arr_gudang_produk[ $gudang_pusat ]["stok"] - @$quantity_booking_order_total[$__kode_produk];
				}
			}
			
			$arr_item[$kode_produk]["gudang"] = $gudang_pusat;
			
			// cek stok gudang pusat
			if( $arr_item[$kode_produk]["stok"] < $arr_item[$kode_produk]["kuantitas"] ){
				
				if( $arr_item[$kode_produk]["stok"] <= 0 ) $arr_item[$kode_produk]["stok"] = 0;
				
				$arr_item[$kode_produk]["gudang"] = $gudang_pusat;
				
				// stok kosong
				if( $arr_preorder_item[$kode_produk] <= 0 )
					$arr_item[$kode_produk]["kuantitas"] = ($arr_item[$kode_produk]["stok"] > 0 ? $arr_item[$kode_produk]["stok"] : 0);
					
				// cek preorder
				elseif( $arr_preorder_item[$kode_produk] > 0 ){
					
					$arr_item[$kode_produk]["gudang"] = __GUDANG_PUSAT__;

					$arr_item[$kode_produk]["stok"] = $arr_kuota_preorder_dm[ $__kode_produk ]["kuota"] <= 0 ? 0 : $arr_kuota_preorder_dm[ $__kode_produk ]["kuota"];
					$arr_item[$kode_produk]["campaign_preorder"]= $arr_kuota_preorder_dm[ $__kode_produk ]["paketid"];
					$arr_item[$kode_produk]["campaign_preorder_tersedia"]= $arr_kuota_preorder_dm[ $__kode_produk ]["paket_tersedia"];
					
					if( $arr_item[$kode_produk]["stok"] > $arr_item[$kode_produk]["kuantitas"] )
						$arr_item[$kode_produk]["kuantitas"] = $arr_item[$kode_produk]["kuantitas"];
						
					// cek stok item induk di gudang pusat karena rework stiker di pusat
					else{
						
						$item_id_induk = $arr_gudang_produk[ $gudang_pusat ]["item_induk"];
						$arr_item[$kode_produk]["stok"] = $server_output[ $item_id_induk ][$gudang_pusat]["stok"];
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

}

$return = [];
foreach( $arr_return as $itemcode => $iteminfo ){
	$return[] = $iteminfo;
}


header("Content-Type: application/json");
echo json_encode($return);

?>