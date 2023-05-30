<?

//include_once "../lib/var.php";
//include_once "../lib/cls_main.php";
//include_once "../lib/cls_order.php";

// otorisasi key
//$auth = sha1(__KEY__ . @$_REQUEST["rand"] . str_replace(" ", "", trim(@$_REQUEST["shopping_cart"] . @$_REQUEST["shopping_cart_culinaria"]) ) ) ;
//if( $auth != @$_REQUEST["auth"] ) die("E1");


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
	"txtGudang"=>"GDGPST",
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
function sync_accpac($order_no, $debug = false){
	
	$sql_argumen = "
	        select a.*,
	        ifnull(shippingcost, 0) ongkir, 
			round(case 
				when coupon_discount>1 then coupon_discount
				else	(b.item_priceamount * coupon_discount) 
			end) diskon_header,
			case 
				when ipg_promo_discount > 1 then ipg_promo_discount
				else (
					b.item_priceamount -
						(case 
							when coupon_discount>1 then coupon_discount
							else	(b.item_priceamount * coupon_discount) end) +
						ifnull(shippingcost,0)
					) * ipg_promo_discount
			end diskon_ipg,
			round(case 
				when coupon_discount>1 then coupon_discount
				else	(b.item_priceamount * coupon_discount) 
			end +
			case 
				when ipg_promo_discount > 1 then ipg_promo_discount
				else (
					b.item_priceamount -
						(case 
							when coupon_discount>1 then coupon_discount
							else	(b.item_priceamount * coupon_discount) end) +
						ifnull(shippingcost,0)
					) * ipg_promo_discount
			end) diskon_total, 
			(
			case 
				when coupon_discount>1 then coupon_discount
				else	(b.item_priceamount * coupon_discount) 
			end +
			case 
				when ipg_promo_discount > 1 then ipg_promo_discount
				else (
					b.item_priceamount -
					(
					case 
						when coupon_discount>1 then coupon_discount
						else	(b.item_priceamount * coupon_discount) 
					end
					) 
				) * ipg_promo_discount
				end
			) / 
			(
				b.item_priceamount 
			) * 100 diskon_total_persen,
			round(b.item_priceamount -
			(case 
				when coupon_discount>1 then coupon_discount
				else	(b.item_priceamount * coupon_discount) end) -
			(case 
				when ipg_promo_discount > 1 then ipg_promo_discount
				else (
					b.item_priceamount -
						(case 
							when coupon_discount>1 then coupon_discount
							else	(b.item_priceamount * coupon_discount) end) +
						ifnull(shippingcost,0)
					) * ipg_promo_discount end) +
			ifnull(shippingcost,0)) order_amount, 
			round(b.item_priceamount -
			(case 
				when coupon_discount>1 then coupon_discount
				else	(b.item_priceamount * coupon_discount) end) -
			(case 
				when ipg_promo_discount > 1 then ipg_promo_discount
				else (
					b.item_priceamount -
						(case 
							when coupon_discount>1 then coupon_discount
							else	(b.item_priceamount * coupon_discount) end) +
						ifnull(shippingcost,0)
					) * ipg_promo_discount end))
			order_amount_tanpa_ongkir
            from ordercustomer a 
	        inner join (select order_id, sum(product_pricepromo*quantity) item_priceamount from orderproduct group by order_id) b 
				on a.order_id = b.order_id where 
			a.order_id is not null and a.order_no = '". main::formatting_query_string( $order_no ) ."' and a.order_status > 0";
	$rs_argumen = mysql_query($sql_argumen);
	if(mysql_num_rows($rs_argumen) <= 0) return "E3";
	$argumen = mysql_fetch_array($rs_argumen);
	
	$order_number = $argumen["order_no"];
	$data_order["custemail"] = $argumen["custemail"];
	$data_order["receiver_name_for_shipping"] = $argumen["receiver_name_for_shipping"];
	$data_order["shipping_address"] = $argumen["shipping_address"];
	$data_order["shipping_address_city"] = $argumen["shipping_address_city"];
	$data_order["shipping_address_state"] = $argumen["shipping_address_state"];
	$data_order["shipping_phone_no"] = $argumen["shipping_phone_no"];
	$data_order["shipping_handphone_no"] = $argumen["shipping_handphone_no"];
	
	$data_order["billing_first_name"] = $argumen["receiver_name_for_shipping"];
	$data_order["billing_last_name"] = "";
	$data_order["billing_address"] = $argumen["shipping_address"];
	$data_order["billing_address_city"] = $argumen["shipping_address_city"];
	$data_order["billing_address_state"] = $argumen["shipping_address_state"];
	$data_order["billing_phone_no"] = $argumen["shipping_phone_no"];
	$data_order["billing_handphone_no"] = $argumen["shipping_handphone_no"];
	$data_order["shipping_date"] = $argumen["shipping_date"];
	$data_order["shipping_note"] = $argumen["shipping_note"];
	
	$data_order["ongkir"] = $argumen["ongkir"];
	$data_order["order_amount"] = $argumen["order_amount"]; // net amount dengan ongkir, termasuk diskon per item + diskon invoice
	$data_order["order_amount_tanpa_ongkir"] = $argumen["order_amount_tanpa_ongkir"]; //net amount tanpa ongkir, termasuk diskon per item + diskon invoice
	$data_order["diskon_total"] = $argumen["diskon_total"]; //nilai/amount diskon invoice total: % dihitung dari net amount tanpa ongkir
	$data_order["diskon_total_persen"] = $argumen["diskon_total_persen"]; //diskon
	
	$daftar_item = $argumen["daftar_item"];
	//format array daftar_item
	//daftar_item[] = array("categoryid"=>"", "tradein" => "", "kode"=>"", "quantity"=>"", "product_price"=>"", "product_promo_percent"=>"","note"=>"", "gudang"=>"")

	define("__MEMBERCARD_DB__", "modenaim_membercard");


	// terkait dengan operasi di membercard, terutama untuk konsumen yang bayar dengan transfer .. maka order ttp harus dientri di ACCPAC (perlakuan sama kayak online, bayar debit card)
	//if($data_order["order_status"] < 1) goto SkipProcess;

	// dapatkan header
	$arr_curl_header = array("c"=>"order", "sc"=>"io", "order_number"=>"MOS-" . $order_number, "custemail" => $data_order["custemail"]);
	$arr_curl_header_tradein = array("c"=>"order", "sc"=>"io", "order_number"=>"MTR-" . $order_number, "custemail" => $data_order["custemail"]);
	$arr_curl_header_sparepart = array("c"=>"order", "sc"=>"io", "order_number"=>"MSP-" . $order_number, "custemail" => $data_order["custemail"]);
	
	// dapatkan alamat kirim + tagih
	$arr_curl_kirim = array(	"nama_kirim" => $data_order["receiver_name_for_shipping"], 
								"alamat_kirim" => substr( trim( $data_order["shipping_address"] ), 0, 240 ),
								"kota_kirim" => substr( trim( $data_order["shipping_address_city"] ), 0, 30 ),
								"propinsi_kirim" => substr( trim( $data_order["shipping_address_state"] ), 0, 30 ),
								"telp_kirim" => substr( trim( $data_order["shipping_phone_no"] ), 0, 30 ),
								"hp_kirim" => substr( trim( $data_order["shipping_handphone_no"] ), 0, 30 ),
								"tanggal_kirim" => $data_order["shipping_date"], 
								"note_kirim" => $data_order["shipping_note"] 
							);
	$arr_curl_tagih = array(	"nama_tagih" => substr( trim( $data_order["billing_first_name"] . " " . $data_order["billing_last_name"] ), 0, 30 ),
								"alamat_tagih" => substr( trim( $data_order["billing_address"] ), 0, 240 ),
								"kota_tagih" => substr( trim( $data_order["billing_address_city"] ), 0, 30 ),
								"propinsi_tagih" => substr( trim( $data_order["billing_address_state"] ), 0, 30 ),
								"telp_tagih" => substr( trim( $data_order["billing_phone_no"] ), 0, 30 ),
								"hp_tagih" => substr( trim( $data_order["billing_handphone_no"] ), 0, 30 )
							);

	// dapatkan dealer id = konstanta kode dari tabel sgtdat..arcus = penjualan online dari website
	$kode_customer = "C018-000761"; // kode customer default untuk penjualan online dari telemarketing
	$kode_customer_tradein = "C018-000762"; // kode customer default untuk tradein online dari telemarketing
	$kode_customer_sparepart = "C001-000331"; // kode customer default untuk penjualan sparepart online dari website
	
	if( isset( $_REQUEST["kode_customer"] ) && $_REQUEST["kode_customer"] != "" ) $kode_customer = $_REQUEST["kode_customer"];
	if( isset( $_REQUEST["kode_customer_tradein"] ) && $_REQUEST["kode_customer_tradein"] != "" ) $kode_customer_tradein = $_REQUEST["kode_customer_tradein"];
	if( isset( $_REQUEST["kode_customer_sparepart"] ) && $_REQUEST["kode_customer_sparepart"] != "" ) $kode_customer_sparepart = $_REQUEST["kode_customer_sparepart"];
	
	// cek kode customer dari membercard, apabila ada berarti penjualan dari showroom
	/*$sql = "select kode_customer from `". __MEMBERCARD_DB__ ."`.order_kodecustomer where order_no = '". main::formatting_query_string($order_number) ."' ";
	$rs_cek_kode_customer = mysql_query($sql) or die();
	if( mysql_num_rows($rs_cek_kode_customer) > 0 ){
		$cek_kode_customer = mysql_fetch_array($rs_cek_kode_customer);
		$kode_customer = $cek_kode_customer["kode_customer"];
	}*/
		
	$arr_curl_dealerid = array("txtDealerId"=>$kode_customer);
	$arr_curl_dealerid_tradein = array("txtDealerId"=>$kode_customer_tradein);
	$arr_curl_dealerid_sparepart = array("txtDealerId"=>$kode_customer_sparepart);

	// dapatkan propinsi dan kota gudang/cabang, khusus untuk membercard gunakan variabel $_POST["txtGudang"] untuk mengirimkan kode gudang ke server
	//if( $kode_customer != "C018-000389" ) // kode customer untuk penjualan dari showroom
	//	$arr_curl_branch = array("txtGudang" => $_SESSION["showroom_kode_gudang"]);
	//else{
		$sql = "select a.branch_state_id, a.branch_region_id from branch_service a, shipment_exception b, shipment_state c where 
			a.service_state_id=b.state_id and a.service_region_id=b.region_id and b.state_id=c.state_id 
			and upper(trim(b.region)) like concat('%',upper(trim('". main::formatting_query_string($data_order["shipping_address_city"]) ."')),'%')
			and upper(trim(c.state)) like concat('%',upper(trim('". main::formatting_query_string($data_order["shipping_address_state"]) ."')),'%')"; 
		$rs_branch = mysql_query($sql) or die();
		$branch = mysql_fetch_array($rs_branch);
		$arr_curl_branch = array("propinsi"=>$branch["branch_state_id"], "kota"=>$branch["branch_region_id"]);
	//}
	
	// dapatkan kode sales = konstanta kode dari tabel sgtdat..arsap = PIC di BDRE
	$arr_curl_kodesales = array("txtKodeSales" => isset($_REQUEST["kode_sales"]) && $_REQUEST["kode_sales"] != "" ? $_REQUEST["kode_sales"] : 11620);

	// dapatkan daftar item
	$txtItem = $txtQty = $txtPrice = $txtDisc = $txtKet = $txtItembrkid = array();
	$txtItem_tradein = $txtQty_tradein = $txtPrice_tradein = $txtDisc_tradein = $txtKet_tradein = $txtItembrkid_tradein = array();
	$txtItem_sparepart = $txtQty_sparepart = $txtPrice_sparepart = $txtDisc_sparepart = $txtKet_sparepart = $txtItembrkid_sparepart = array();
	$total_nilai_item = $total_nilai_item_tradein = $total_nilai_item_sparepart = 0;
	
	//$rs_daftar_item = order::order_product_for_accpac_sync($data_order["order_no"]);
	$sql_daftar_item = "select a.nama_produk name, a.product_id, a.sku kode, 
			a.quantity, a.product_price, ifnull(a.product_promo*a.quantity, 0) product_promo, (ifnull(a.product_promo, 0) / a.product_price) * 100 product_promo_percent, 
			a.discount, a.product_pricepromo, 
			(a.product_pricepromo*a.quantity) total_amount, a.tradein, a.note, a.gudang, a.paket_id paketid
			from orderproduct a 
			inner join ordercustomer b on a.order_id=b.order_id
			where 
			b.order_no='".main::formatting_query_string($order_number)."'";
    $rs_daftar_item = mysql_query($sql_daftar_item);

	while($daftar_item = mysql_fetch_array($rs_daftar_item)){
		
		if( $daftar_item["quantity"] <= 0 ) continue;
		
		// pemisah untuk kategori spare part/asesoris dan FG
		if( in_array( $daftar_item["categoryid"], $GLOBALS["arr_kategori_sp_acs"] ) ) {
			$txtItem_sparepart["txtItem"][] = str_replace(array("/", "-"), array("", ""), $daftar_item["kode"]);
			$txtQty_sparepart["txtQty"][] = $daftar_item["quantity"];
			$txtPrice_sparepart["txtPrice"][] = $daftar_item["product_price"];
			$txtDisc_sparepart["txtDisc"][] = $daftar_item["product_promo_percent"];
			$txtDiscNominal_sparepart["txtDiscNominal"][] = $daftar_item["product_promo"];
			$txtKet_sparepart["txtKet"][] = $daftar_item["note"];
			$txtItembrkid_sparepart["txtItembrkid"][] = "SP";
			$txtGudang_sparepart["txtGudangItem"][] = $daftar_item["gudang"];
			$txtPaketId_sparepart["txtPaketId"][] = $daftar_item["paketid"];
			$total_nilai_item_sparepart += ($daftar_item["product_price"] * $daftar_item["quantity"]) * ( 1- ( $daftar_item["product_promo_percent"]/100 ) );

		}else{
			
			if( $daftar_item["tradein"] == 1 ){ // cek utk item tradein
				$txtItem_tradein["txtItem"][] = str_replace(array("/", "-"), array("", ""), $daftar_item["kode"]);
				$txtQty_tradein["txtQty"][] = $daftar_item["quantity"];
				$txtPrice_tradein["txtPrice"][] = $daftar_item["product_price"];
				$txtDisc_tradein["txtDisc"][] = $daftar_item["product_promo_percent"];
				$txtDiscNominal_tradein["txtDiscNominal"][] = $daftar_item["product_promo"];
				$txtKet_tradein["txtKet"][] = $daftar_item["note"];
				$txtItembrkid_tradein["txtItembrkid"][] = "FG";
				$txtGudang_tradein["txtGudangItem"][] = $daftar_item["gudang"];
				$txtPaketId_tradein["txtPaketId"][] = $daftar_item["paketid"];
				$total_nilai_item_tradein += ($daftar_item["product_price"] * $daftar_item["quantity"]) * ( 1- ( $daftar_item["product_promo_percent"]/100 ) );
				
			}else{ // barang jadi
				$txtItem["txtItem"][] = str_replace(array("/", "-"), array("", ""), $daftar_item["kode"]);
				$txtQty["txtQty"][] = $daftar_item["quantity"];
				$txtPrice["txtPrice"][] = $daftar_item["product_price"];
				$txtDisc["txtDisc"][] = $daftar_item["product_promo_percent"];
				$txtDiscNominal["txtDiscNominal"][] = $daftar_item["product_promo"];
				$txtKet["txtKet"][] = $daftar_item["note"];
				$txtItembrkid["txtItembrkid"][] = "FG";				
				$txtGudang["txtGudangItem"][] = $daftar_item["gudang"];
				$txtPaketId["txtPaketId"][] = $daftar_item["paketid"];
				$total_nilai_item += ($daftar_item["product_price"] * $daftar_item["quantity"]) * ( 1- ( $daftar_item["product_promo_percent"]/100 ) );
				
			}
		}
	}

	$arr_curl_item = $arr_curl_item_sparepart = $arr_curl_item_tradein = array();
	if( $total_nilai_item > 0 )	$arr_curl_item = $txtItem + $txtQty + $txtPrice + $txtDisc + $txtDiscNominal + $txtKet + $txtItembrkid + $txtGudang + $txtPaketId;
	if( $total_nilai_item_sparepart > 0 )	$arr_curl_item_sparepart = $txtItem_sparepart + $txtQty_sparepart + $txtPrice_sparepart + $txtDisc_sparepart + $txtDiscNominal_sparepart + $txtKet_sparepart + $txtItembrkid_sparepart + $txtGudang_sparepart + $txtPaketId_sparepart;
	if( $total_nilai_item_tradein > 0 )	$arr_curl_item_tradein = $txtItem_tradein + $txtQty_tradein + $txtPrice_tradein + $txtDisc_tradein + $txtDiscNominal_tradein + $txtKet_tradein + $txtItembrkid_tradein + $txtGudang_tradein + $txtPaketId_tradein;

	// dapatkan data keterangan yang berisi informasi ongkir
	$arr_curl_keterangan = array("txtKeterangan" => "");
	$arr_curl_keterangan_tradein = array("txtKeterangan" => "");
	$arr_curl_keterangan_sparepart = array("txtKeterangan" => "");
	if($data_order["ongkir"] > 0){
		// ongkir dibuat prorate sesuai dengan nominal masing2 order
		$ongkir = $data_order["ongkir"] * ( $total_nilai_item / ( $total_nilai_item + $total_nilai_item_tradein + $total_nilai_item_sparepart ) );
		$arr_curl_keterangan = array("txtKeterangan"=>"Biaya kirim ke alamat pengiriman konsumen sebesar Rp" . number_format($ongkir));
		
		$ongkir_tradein = $data_order["ongkir"] * ( $total_nilai_item_tradein / ( $total_nilai_item + $total_nilai_item_tradein + $total_nilai_item_sparepart ) );
		$arr_curl_keterangan_tradein = array("txtKeterangan"=>"Biaya kirim ke alamat pengiriman konsumen sebesar Rp" . number_format($ongkir_tradein));
		
		$ongkir_sparepart = $data_order["ongkir"] * ( $total_nilai_item_sparepart / ( $total_nilai_item + $total_nilai_item_tradein + $total_nilai_item_sparepart ) );
		$arr_curl_keterangan_sparepart = array("txtKeterangan"=>"Biaya kirim ke alamat pengiriman konsumen sebesar Rp" . number_format($ongkir_sparepart ));
		
		$arr_curl_kirim["ongkos_kirim"] = $data_order["ongkir"];
	}
	
	// dapatkan data total, sub total dan disc header
	$txtsubtotal = ($data_order["order_amount_tanpa_ongkir"] + $data_order["diskon_total"]) * ( $total_nilai_item / ( $total_nilai_item + $total_nilai_item_tradein + $total_nilai_item_sparepart ) );
	$txttotal = $data_order["order_amount_tanpa_ongkir"] * ( $total_nilai_item / ( $total_nilai_item + $total_nilai_item_tradein + $total_nilai_item_sparepart ) );
	$arr_curl_total = array(
					"txtSubTotal" => $txtsubtotal,
					"txtTotal" => $txttotal,
					"txtDiscHeader" => $data_order["diskon_total"],
					"txtDiscHeaderPersen" => $data_order["diskon_total_persen"]
				);
				
	$txtsubtotal_tradein = ($data_order["order_amount_tanpa_ongkir"] + $data_order["diskon_total"]) * ( $total_nilai_item_tradein / ( $total_nilai_item + $total_nilai_item_tradein + $total_nilai_item_sparepart ) );
	$txttotal_tradein = $data_order["order_amount_tanpa_ongkir"] * ( $total_nilai_item_tradein / ( $total_nilai_item + $total_nilai_item_tradein + $total_nilai_item_sparepart ) );
	$arr_curl_total_tradein = array(
					"txtSubTotal" => $txtsubtotal_tradein,
					"txtTotal" => $txttotal_tradein,
					"txtDiscHeader" => $data_order["diskon_total"],
					"txtDiscHeaderPersen" => $data_order["diskon_total_persen"]
				);
				
	$txtsubtotal_sparepart = ($data_order["order_amount_tanpa_ongkir"] + $data_order["diskon_total"]) * ( $total_nilai_item_sparepart / ( $total_nilai_item + $total_nilai_item_tradein + $total_nilai_item_sparepart ) );
	$txttotal_sparepart = $data_order["order_amount_tanpa_ongkir"] * ( $total_nilai_item_sparepart / ( $total_nilai_item + $total_nilai_item_tradein + $total_nilai_item_sparepart ) );
	$arr_curl_total_sparepart = array(
					"txtSubTotal" => $txtsubtotal_sparepart,
					"txtTotal" => $txttotal_sparepart,
					"txtDiscHeader" => $data_order["diskon_total"],
					"txtDiscHeaderPersen" => $data_order["diskon_total_persen"]
				);
				
	$arr_otorisasi = array(
		"rand"	=> $GLOBALS["random"],
		"auth"   => sha1($GLOBALS["ftp_address"].$GLOBALS["random"].$GLOBALS["ftp_username"])
		);
	
	if( $total_nilai_item > 0 )
		$arr_curl[] = $arr_otorisasi + $arr_curl_header + $arr_curl_kirim + $arr_curl_tagih + $arr_curl_branch + $arr_curl_dealerid + $arr_curl_kodesales + $arr_curl_item + $arr_curl_keterangan + $arr_curl_total;
	if( $total_nilai_item_tradein > 0 )
		$arr_curl[] = $arr_otorisasi + $arr_curl_header_tradein + $arr_curl_kirim + $arr_curl_tagih + $arr_curl_branch + $arr_curl_dealerid_tradein + $arr_curl_kodesales + $arr_curl_item_tradein + $arr_curl_keterangan_tradein + $arr_curl_total_tradein;
	if( $total_nilai_item_sparepart > 0 )
		$arr_curl[] = $arr_otorisasi + $arr_curl_header_sparepart + $arr_curl_kirim + $arr_curl_tagih + $arr_curl_branch + $arr_curl_dealerid_sparepart + $arr_curl_kodesales + $arr_curl_item_sparepart + $arr_curl_keterangan_sparepart + $arr_curl_total_sparepart;
	
	// print_r($_SESSION['shopping_cart']);
	// //debug
	//print_r($arr_curl);exit;
	//echo $GLOBALS["auto_stock_url"] . "?" . http_build_query($arr_curl);exit;
	if( $debug ) goto SkipProcess;
	
	// kirimkan ke pusat
	foreach( $arr_curl as $curl ){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $GLOBALS["auto_stock_url"]);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($curl));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , $GLOBALS["curl_connection_timeout"]); 
		curl_setopt($ch, CURLOPT_TIMEOUT, $GLOBALS["curl_timeout"]); 
		
		$server_output = curl_exec ($ch);
		
		$curl_error = curl_errno($ch);
		if($curl_error != 0)
			main::send_email(SUPPORT_EMAIL, "[modena.co.id] :: Error sinkronisasi data order ke ACCPAC", 
			"Mas bro, tolong segera dicek ada error sinkron data order ke ACCPAC. Nomor order ".$order_number .".<br />
			Lokasinya di accpac_sync_modena <br />
			Kode errornya adalah : ". $curl_error);
	    
	    echo $server_output; 	
	    
		return $server_output;
		curl_close ($ch);
	}
	
	// further processing ....
	return $server_output;

	SkipProcess:

}	

// debug
//if($_REQUEST["order_number"] != "")
//	sync_accpac($_REQUEST);


?>
