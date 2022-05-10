<?

foreach( $_SESSION[ "shopping_cart_diskon" ] as $productid => $arr_promo_per_item )
	$arr_list_promo_per_item[ $arr_promo_per_item["productid"] ] = $arr_promo_per_item;

$total_fg = $total_fg_sblm_diskon = 0;
$cart_fg = "";
$arr_cart_fg = array();
$total_berat_produk = 0;

$ada_diskon_per_item_fg = false;

// reset tabel orderproduct
if( $data_order_id["order_id"] != "" )
    mysql_query("delete from orderproduct where order_id = '". main::formatting_query_string($data_order_id["order_id"]) ."'");

if( count($_SESSION["shopping_cart"]) >0 && @$_SESSION["gudang"] != "" ){

	foreach( $_SESSION["shopping_cart"] as $productid => $qty ){
		$arr_item[ $_SESSION["shopping_cart_sku"][$productid] ] = array( 
			"productid" =>  $productid, "nama" => $_SESSION["shopping_cart_nama"][$productid], "harga" => $_SESSION["shopping_cart_price"][$productid], "qty" => $qty, "berat" => $_SESSION["shopping_cart_berat"][$productid],
			"diskon" => $_SESSION["shopping_cart_diskon"][$productid],
			"sku" => $_SESSION["shopping_cart_sku"][$productid]
		);
	}

	// load stok
	unset($arr_par);
	$rand = rand(0,100000);
	$arr_par["c"] = "kebetot";
	$arr_par["sc"] = "shipping_interval";
	$arr_par["rand"] = $rand;
	$arr_par["dealer_id"] = __IDCUST_FG__;
	$arr_par["gudang"] = $_SESSION["gudang"];
	$arr_par["__STOK_AMAN__"] = __STOK_AMAN__;
	$arr_par["negara"] = $_REQUEST["negara"];
	$arr_par["mata_uang"] = $_REQUEST["mata_uang"];
	foreach( array_keys($arr_item) as $kode_produk ){
	  $arr_par["itemno"][] = $kode_produk;
	  $arr_par["qty"][] = 0;//$arr_item[ $kode_produk ]["qty"];
	} 
	$arr_par["auth"] = sha1(__KEY_AIR__ . $rand . implode("", array_keys($arr_item) ) );
//die(__API__ . "item.v2" . "?" . http_build_query($arr_par));	
    $server_output = panggil_curl(__API__ . "item.v2", $arr_par);

	// cari kuantitas item terbooking (order_status = 0 dan -1), walaupun belum dibayar namun dengan rentang waktu kurang dari 2 jam.. sebagai pengurang data stok accpac yang masih diproses di API
	// diberikan waktu 2 jam dari mulai proses di midtrans agar tetap diperhitungkan sebagai booking di API
	// sedangkan dari midtrans ngasih waktu 2 jam
	// basis waktu diganti menjadi menit, dengam ambil data dari global variabel + 15 menit utk ekstensi
	if(@$_SESSION["email"] != "")
		$sqladd = "and 
			a.order_id != '". main::formatting_query_string($data_order_id["order_id"]) ."'";
		
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
	

	if( count($server_output) > 0 ){			
		// iterating regular item
		unset($_SESSION["shopping_cart_subtotal"]);

    	// cek kuota preorder dari DM
    	$arr_kuota_preorder_dm = $arr_par_item_preorder = $arr_paket_preorder_dm = array();
    	unset($arr_par);
    	$rand = rand(0,100000);
    	$arr_par["c"] = "kebetot";
    	$arr_par["rand"] = $rand;
    	$arr_par["dealer_id"] = __IDCUST_FG__;
    	foreach( $server_output as $kode_produk=>$arr_gudang_produk ){
			if( $_SESSION["shopping_cart_stok"][ $arr_item[$kode_produk]["productid"] ] > 0 )
			    $arr_par_item_preorder[] = $kode_produk . "_0";// . $arr_item[$kode_produk]["qty"];
    	}
    	$arr_par["item"] = $arr_par_item_preorder;
    	$arr_par["auth"] = sha1(__KEY_AIR__ . $rand . implode("", array_values($arr_par["item"])));
//die(__API__ . "preorder_kuota" . "?" . http_build_query($arr_par));	
        $arr_kuota_preorder_dm = panggil_curl(__API__ . "preorder_kuota", $arr_par);

		// cek promo dari RDM, diisi ke $server_output_diskon
		$server_output_diskon = array();
    	unset($arr_par);
    	$rand = rand(0,100000);
    	$arr_par["c"] = "kebetot";
    	$arr_par["rand"] = $rand;
    	$arr_par["appid"] = "WEB";
    	$arr_par["order_id"] = $data_order_id["order_id"];
    	$arr_par["dealer_id"] = __IDCUST_FG__;
        $arr_par["kode_campaign"] = $_REQUEST["kode_promo"];
    	$arr_par["auth"] = sha1(__KEY_AIR__ . $rand . $arr_par["order_id"] );

    	$counter = 0;
    	$temp_gudang_terpilih_per_item = array();
    	// cek stok gudang/preorder + generate parameter item
    	foreach( $server_output as $kode_produk=>$arr_gudang_produk ){
    		$gudang_terpilih = $_SESSION["gudang"];			

			$kode_produk_ori = $arr_gudang_produk[ $gudang_terpilih ]["itemno_ori"];
			
    		// cek stok gudang cabang
    		if( $arr_gudang_produk[ $gudang_terpilih ]["stok"] - @$quantity_booking_order_total[$kode_produk] < $arr_item[$kode_produk_ori]["qty"] ){
    		    
    			$gudang_terpilih = __GUDANG_PUSAT__;
				if( !array_key_exists($gudang_terpilih, $arr_gudang_produk) )	$gudang_terpilih = __GUDANG_PUSAT_TGN__;
    			
    			// cek stok gudang pusat
    			if( $arr_gudang_produk[ $gudang_terpilih ]["stok"] - @$quantity_booking_order_total[$kode_produk] < $arr_item[$kode_produk_ori]["qty"] ){
    			    
    			    // stok kosong
    			    if( $_SESSION["shopping_cart_stok"][ $arr_item[$kode_produk_ori]["productid"] ] <= 0 )
    				    $arr_item[$kode_produk_ori]["qty"] = 0;
    				    
    				// cek preorder
    				elseif( $_SESSION["shopping_cart_stok"][ $arr_item[$kode_produk_ori]["productid"] ] > 0 ){
    				    $gudang_terpilih = "GDGPRO";

    				    $arr_paket_preorder_dm[ $kode_produk ] = $arr_kuota_preorder_dm[ $kode_produk ]["paket_id"];
    				    if( $arr_kuota_preorder_dm[ $kode_produk ]["kuota"] < 0  )
    				        $arr_item[$kode_produk_ori]["qty"] = 0;
    				        
    				    // cek stok item induk di gudang pusat karena rework stiker di pusat
    				    else{
    				        $item_id_induk = $arr_gudang_produk[ __GUDANG_PUSAT__ ]["item_induk"];
    				        if( $item_id_induk != "" && $server_output[ $item_id_induk ][__GUDANG_PUSAT__]["stok"] < $arr_item[$kode_produk_ori]["qty"] )
    				            $arr_item[$kode_produk_ori]["qty"] = 0;
    				            
    				    }
    				}
    			}
    		}
    	    
    	    $temp_gudang_terpilih_per_item[ $kode_produk ] = $gudang_terpilih;
    	    
    	    $arr_par["item"][$counter]["item_id"] = $kode_produk;
    	    $arr_par["item"][$counter]["harga"] = $arr_item[$kode_produk_ori]["harga"];
    	    $arr_par["item"][$counter]["qty"] = $arr_item[$kode_produk_ori]["qty"];
    	    $arr_par["item"][$counter]["gudang"] = $gudang_terpilih;
    	    $counter++;
    	}

//die(__API__ . "rdm" . "?" . http_build_query($arr_par));	
        $server_output_diskon = panggil_curl(__API__ . "rdm", $arr_par);

		$parameter_sql_insert = array();
		foreach( $server_output as $__kode_produk=>$arr_gudang_produk ){
			
			$kode_produk = $arr_gudang_produk[ (@$_REQUEST["gudang"] != "" ? $_REQUEST["gudang"] : $_SESSION["gudang"]) ]["itemno_ori"];
			unset($arr_rpl);

			$gudang_terpilih = $temp_gudang_terpilih_per_item[ $__kode_produk ]; 
			$opsi_preorder = $_SESSION["shopping_cart_stok"][ $arr_item[$kode_produk]["productid"] ] > 0 ? 1 : 0;
			
            // lewati utk item induk
    		if( $arr_gudang_produk[ $gudang_terpilih ]["status"] == "induk" )
    		    continue;
			
			// opsi tradein
			$is_tradein = 0;
			if( count(@$_SESSION["tradein"]) > 0 && in_array($arr_item[$kode_produk]["productid"], $_SESSION["tradein"]) )
				$is_tradein = 1;

			$nilai_diskon_per_item = cari_nilai_diskon_per_item( $_REQUEST["kode_promo"], $arr_item[$kode_produk], $server_output_diskon["item"], $arr_list_promo_per_item/*, $arr_list_promo_per_item_dengan_voucher*/ );
			$diskon = $arr_item[$kode_produk]["qty"] > 0 ? $nilai_diskon_per_item["diskon"] : 0;
			$paketid = $opsi_preorder > 0 ? $arr_paket_preorder_dm[$kode_produk] : $nilai_diskon_per_item["paketid"];
			$subtotal_sblm_diskon = $arr_item[$kode_produk]["qty"] * $arr_item[$kode_produk]["harga"];
			$subtotal = $subtotal_sblm_diskon - $diskon;
			$total_fg += $subtotal;
			$total_fg_sblm_diskon += $subtotal_sblm_diskon;
			
			$_SESSION["shopping_cart_diskon"][ $arr_item[$kode_produk]["productid"] ] = $diskon;
			$_SESSION["shopping_cart_subtotal"][ $arr_item[$kode_produk]["productid"] ] = $subtotal;
			$shipment_delay = $gudang_terpilih == (@$_REQUEST["gudang"] != "" ? $_REQUEST["gudang"] : $_SESSION["gudang"]) ?
					__DURASI_HARI_KIRIM_REGULER__ : $arr_gudang_produk[ $_SESSION["gudang"] ]["interval_pengiriman"];
		    
		    
			$parameter_sql_insert[] = "(
			                            '". main::formatting_query_string($data_order_id["order_id"]) ."', '". main::formatting_query_string( $arr_item[$kode_produk]["productid"] ) ."',
			                            '". main::formatting_query_string($arr_item[$kode_produk]["qty"]) ."', '". main::formatting_query_string($arr_item[$kode_produk]["harga"]) ."',
			                            '". main::formatting_query_string($diskon/$arr_item[$kode_produk]["qty"]) ."', '". main::formatting_query_string( $arr_item[$kode_produk]["harga"] - ($diskon/$arr_item[$kode_produk]["qty"]) ) ."',
			                            '". main::formatting_query_string($is_tradein) ."', '". main::formatting_query_string( $diskon > 0 ? "'". main::formatting_query_string($arr_item[$kode_produk]["diskon"]["status"]) ."'" : "" ) ."',
			                            '". main::formatting_query_string($gudang_terpilih) ."', '". main::formatting_query_string($kode_produk) ."',
			                            '". main::formatting_query_string($arr_item[$kode_produk]["nama"]) ."', '". main::formatting_query_string($arr_item[$kode_produk]["berat"]) ."',
			                            '". main::formatting_query_string($shipment_delay) ."', '". main::formatting_query_string($opsi_preorder) ."', '". main::formatting_query_string($paketid) ."'
			                            )";
			$gudang_pusat = __GUDANG_PUSAT__;
			if( !array_key_exists($gudang_pusat, $arr_gudang_produk) )	$gudang_pusat = __GUDANG_PUSAT_TGN__;
				
			$arr_cart_fg[] = array(
				"kode_produk" => $__kode_produk,
				"kode_produk_ori" => $kode_produk,
				"produk" => $arr_item[$kode_produk]["nama"],
				"stok" => $arr_gudang_produk[ $_SESSION["gudang"] ]["stok"] - @$quantity_booking_order_total[$kode_produk],
				"stok_pusat" => $arr_gudang_produk[ $gudang_pusat ]["stok"] - @$quantity_booking_order_total[$kode_produk],
				"gudang" => $gudang_terpilih,
				// harus dikirimkan parameter kode gudang utk menggantikan session gudang
				"shipment_delay" => $shipment_delay,
				"qty" => $arr_item[$kode_produk]["qty"],
				"harga" => $arr_item[$kode_produk]["harga"],
				"subtotal" => $subtotal_sblm_diskon,
				"diskon" => $diskon,
				"total" => $subtotal,
				"productid" => $arr_item[$kode_produk]["productid"],
				"berat" => $arr_item[$kode_produk]["berat"],
				"tradein" => $is_tradein,
				"preorder" => $opsi_preorder
			);
			if( $diskon > 0 )   $ada_diskon_per_item_fg = true;
			$total_berat_produk += $_SESSION["shopping_cart_berat"][ $arr_item[$kode_produk]["productid"] ] * $arr_item[$kode_produk]["qty"];
		}
		
		// insert baru di tabel orderproduct
		$sql = "insert into orderproduct(order_id, product_id, quantity, product_price, product_promo, product_pricepromo, tradein, note, gudang, sku, nama_produk, berat, shipment_delay, preorder, paket_id) values " . implode(",", $parameter_sql_insert);
		mysql_query($sql);
		
		// iterating free item
		/*if( is_array($server_output_diskon["item_free"]) &&  count($server_output_diskon["item_free"]) > 0 ){
			foreach( $server_output_diskon["item_free"] as $index => $item ){
				unset($arr_rpl);
				
			}
		}*/
		
		// diskon invoice dari kupon
		$discount_price = 0; $discount_subtotal = $total_fg;
		$status_diskon_invoice = "";
		if($_REQUEST["kode_promo"]!="" /*&& !$is_discount_per_item && order_online_promo::is_ipc_all_item(@$_REQUEST["kode_promo"])*/ && count( $arr_list_promo_per_item_dengan_voucher ) <= 0){
			$discount_coupon_=main::counting_discount($_REQUEST["kode_promo"], $total_fg, $lang);
			$discount_coupon=$discount_coupon_[0];
			$discount_price=$discount_coupon_[1];
			$discount_grandtotal=$discount_coupon_[2];	
			if($discount_coupon>0){
				$s_basket.=$lang_discount.",-".$discount_price.".00,1,-".$discount_price.".00;";
				$status_diskon_invoice = $discount_coupon_[3];
			}else
				unset($discount_coupon, $discount_price, $discount_grandtotal);
		}

		$fg_grandtotal=(isset($discount_grandtotal)?$discount_grandtotal:$total_fg);
	}

} 

?>