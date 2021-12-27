<?

//include_once "../lib/mainclass.php";
//include_once "../lib/cls_culinaria_program.php";
//include_once "../lib/cls_culinaria_cart.php";

/*
Isi form berikut ini :
$arr=array("c"=>"order", "sc"=>"io", //
	"txtItem"=>array("VC1500Y0408N05", "BC1320S1206S13"),//
	"txtQty"=>array(1, 3),//
	"txtPrice"=>array(1023000, 4224000),//
	"txtDisc"=>array("15", "10"),//
	"txtKet"=>array(""),//
	"txtKodeSales"=>"modena1", //
	"txtKeterangan"=>"", //
	"txtDealerId"=>"DK02A11021B0", //
	"txtTotal"=>"25047495", //
	"txtSubTotal"=>"27830550", //
	"txtDiscHeader"=>"10" //
	);*/

/*
fungsi untuk sinkronisasi data order dari modena.co.id / membercard ke ACCPAC di server pusat satrio
parameter :
$order_number : (string) nomor order
return : void
*/
function culinaria_sync_accpac($order_number, $debug = false){
		
	$sql_argumen = "select * from culinaria_ordercustomer where order_no = '". main::formatting_query_string($order_number) ."'";
	$rs_data_order = mysql_query($sql_argumen);
	$data_order = mysql_fetch_array($rs_data_order);

	// dapatkan header
	$arr_curl_header = array("c"=>"order", "sc"=>"io", "order_number"=>"COS-" . $order_number, "custemail" => $data_order["custemail"]);
	
	// dapatkan alamat kirim + tagih
	$arr_curl_tagih = array(	"nama_tagih" => substr( trim( $data_order["billing_first_name"] . " " . $data_order["billing_last_name"] ), 0, 30 ),
								"alamat_tagih" => substr( trim( $data_order["billing_address"] ), 0, 240 ),
								"kota_tagih" => substr( trim( $data_order["billing_address_city"] ), 0, 30 ),
								"propinsi_tagih" => substr( trim( $data_order["billing_address_state"] ), 0, 30 ),
								"telp_tagih" => substr( trim( $data_order["billing_phone_no"] ), 0, 30 ),
								"hp_tagih" => substr( trim( $data_order["billing_handphone_no"] ), 0, 30 )
							);
	
	$arr_curl_kirim = array(	"nama_kirim" => substr( trim( $arr_curl_tagih["nama_tagih"] ), 0, 30  ), 
								"alamat_kirim" => substr( trim( $arr_curl_tagih["alamat_tagih"] ), 0, 240 ),
								"kota_kirim" => substr( trim( $arr_curl_tagih["kota_tagih"] ), 0, 30 ),
								"propinsi_kirim" => substr( trim( $arr_curl_tagih["propinsi_tagih"] ), 0, 30 ),
								"telp_kirim" => substr( trim( $arr_curl_tagih["telp_tagih"] ), 0, 30 ),
								"hp_kirim" => substr( trim( $arr_curl_tagih["hp_tagih"] ), 0, 30 )
							);

	// dapatkan dealer id = konstanta kode dari tabel sgtdat..arcus = penjualan online dari website
	$kode_customer = "C018-000261"; // kode customer default untuk culinaria sales
		
	$arr_curl_dealerid = array("txtDealerId"=>$kode_customer);	
	
	// dapatkan kode sales = konstanta kode dari tabel sgtdat..arsap = PIC di BDRE
	$arr_curl_kodesales = array("txtKodeSales" => "284");

	// dapatkan daftar item
	$txtItem = $txtQty = $txtPrice = $txtDisc = $txtKet = array();
	
	unset( $arr_parameter );
	$arr_parameter["a.order_id"] = array("=", "'" . main::formatting_query_string( $data_order["order_id"] ) . "'" );
	$rs_data_order = culinaria_cart::browse_culinaria_cart_item( $arr_parameter );
	//$sql_order_item = "select *, sku program_kode_accpac from culinaria_orderproduct where order_id = '" . main::formatting_query_string( $data_order["order_id"] ) . "'";
	//$rs_data_order = mysql_query($sql_order_item);

	while($daftar_item = sql::sql_fetch_array($rs_data_order)){
		$arr_data_order[ $daftar_item["program_id"] ] = $daftar_item;
		$txtItem["txtItem"][] = $daftar_item["sku"];
		$txtQty["txtQty"][] = $daftar_item["quantity"];
		$txtPrice["txtPrice"][] = $daftar_item["product_price"];
		$txtDisc["txtDisc"][] = $daftar_item["product_promo"];
		$txtKet["txtKet"][] = "CULINARIA-" . $daftar_item["nama_produk"];;
	}
	$arr_curl_item = $txtItem + $txtQty + $txtPrice + $txtDisc + $txtKet;

	// dapatkan data keterangan yang berisi informasi ongkir
	$arr_curl_keterangan = array("txtKeterangan" => "");

	// dapatkan data total, sub total dan disc header
	$data_diskon = culinaria_cart::hitung_nilai_diskon_kupon( $arr_data_order, $data_order["coupon_code"], "xxx-asal tidak kosong" );
	$arr_curl_total = array(
					"txtSubTotal" => $data_diskon["subtotal"] ,
					"txtTotal" => $data_diskon["total_setelah_diskon"],
					"txtDiscHeader" => ($data_diskon["persen_diskon"] * 100)
					);

	$arr_curl = $arr_curl_header + $arr_curl_kirim + $arr_curl_tagih + $arr_curl_dealerid + $arr_curl_kodesales + $arr_curl_item + $arr_curl_keterangan + $arr_curl_total;

	//debug
	//print_r($arr_curl);exit;
	//echo http_build_query($arr_curl);
    
    if( $debug ) goto SkipProcess;
    
	// kirimkan ke pusat
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $GLOBALS["culinaria_auto_stock_url"]);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arr_curl));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , $GLOBALS["curl_connection_timeout"]); 
	curl_setopt($ch, CURLOPT_TIMEOUT, $GLOBALS["curl_timeout"]); 
	
	$server_output = curl_exec ($ch);

	$curl_error = curl_errno($ch);
	if($curl_error != 0)
		main::send_email(SUPPORT_EMAIL, "[modena.co.id] :: Error sinkronisasi data order ke ACCPAC", 
		"Mas bro, tolong segera dicek ada error sinkron data order ke ACCPAC. Nomor order ".$order_number .".<br />
		Lokasinya di accpac_sync_modena <br />
		Kode errornya adalah : ". $curl_error);
	
	curl_close ($ch);

	// further processing ....
	//echo $server_output;

	SkipProcess:

}	

// debug
//culinaria_sync_accpac($_REQUEST["order_number"]);


?>