<?

foreach( $_SESSION[ "culinaria_shopping_cart_diskon" ] as $productid => $arr_promo_per_item )
	$arr_list_promo_per_item_culinaria[ $arr_promo_per_item["productid"] ] = $arr_promo_per_item;

$total_culinaria = 0;
$cart_culinaria = "";
$arr_cart_culinaria = array();
$total_berat_produk_culinaria = 0;
unset($arr_item);

// reset tabel culinaria_orderproduct
if( $data_order_id["culinaria_order_id"] != "" )
    mysql_query("delete from culinaria_orderproduct where order_id = '". main::formatting_query_string($data_order_id["culinaria_order_id"]) ."'");

if( count($_SESSION["culinaria_shopping_cart"]) >0 ){

	foreach( $_SESSION["culinaria_shopping_cart"] as $productid => $qty ){
		$arr_item[ $productid ] = array( 
			"productid" =>  $productid, "nama" => $_SESSION["culinaria_shopping_cart_nama"][$productid], "harga" => $_SESSION["culinaria_shopping_cart_price"][$productid], "qty" => $qty, "berat" => $_SESSION["culinaria_shopping_cart_berat"][$productid],
			"diskon" => $_SESSION["culinaria_shopping_cart_diskon"][$productid], "sku" => $_SESSION["culinaria_shopping_cart_sku"][$productid]
		);
		$total_berat_produk_culinaria += $_SESSION["culinaria_shopping_cart_berat"][$productid];
	}

	// load stok culinaria
	// cek stok di API server, comment line di bawah ini agar mempercepat proses loading
	$server_output = $_SESSION["culinaria_shopping_cart_stok"];
	// cari kuantitas item terbooking, walaupun belum dibayar namun dengan rentang waktu kurang dari 5 jam.. sebagai pengurang data stok accpac yang masih diproses di CBN
	// diberikan waktu 5 jam dari mulai proses di doku agar tetap diperhitungkan sebagai booking di CBN
	// sedangkan dari doku ngasih waktu 4 jam
	// basis waktu diganti menjadi menit, dengam ambil data dari global variabel + 15 menit utk ekstensi
	if(@$_SESSION["email"] != "")
		$sqladd = "and 
			memberid not in (select memberid from membersdata where email='". main::formatting_query_string($_SESSION["email"]) ."')";
		
	$quantity_booking_order_total = array();
	if( is_array($server_output) && count( $server_output ) > 0 ){
	    unset($sql_parameter);
	    foreach( array_keys($server_output) as $productid )
	        $sql_parameter[] = "'". main::formatting_query_string( $productid ) ."'";
		$sql = "
			select sum(c.kuantitas_order_item_sedang_proses) kuantitas_order_item_sedang_proses, c.productid from 
			culinaria_ordercustomer a, 
			(select sum(quantity) kuantitas_order_item_sedang_proses, order_id, product_id productid from culinaria_orderproduct where sku is not null group by order_id, product_id) c 
			where 
			a.order_id = c.order_id and
			(   
			    (TIMESTAMPDIFF(MINUTE, a.order_date, CURRENT_TIMESTAMP) < ". $GLOBALS["payment_va_expiration"] ."+5 and order_status in (-1)) or 
			    order_status > 0 or 
			    (TIMESTAMPDIFF(MINUTE, a.order_date, CURRENT_TIMESTAMP) < ". $GLOBALS["payment_va_expiration"] ."+5 and order_status = 0 ". @$sqladd ." )
			) and c.productid in (". implode( ",", array_values($sql_parameter) ) .")
			group by c.productid
		"; 
		$rs = mysql_query($sql);
		if( mysql_num_rows($rs) > 0 ){
			
			while( $data = mysql_fetch_array($rs) )
				$quantity_booking_order_total[ $data["productid"] ] = $data["kuantitas_order_item_sedang_proses"];
		
		}
	}

	if( count($server_output) > 0 ){			
		// iterating regular item
		unset($_SESSION["culinaria_shopping_cart_subtotal"]);
		
		$parameter_sql_insert = array();
		$parameter_sql_qty = "";
		foreach( $server_output as $productid=>$stok ){
		    $kode_produk = $productid;
			unset($arr_rpl);
			// cek stok gudang
			$gudang_terpilih = $_SESSION["gudang"];			
			if( $stok <= $arr_item[$kode_produk]["qty"] ){
				$arr_item[$kode_produk]["qty"] = 0;
			} 
			
			$is_tradein = 0;
			
			$nilai_diskon_per_item = cari_nilai_diskon_per_item( $_REQUEST["kode_promo"], $arr_item[$kode_produk], array(), $arr_list_promo_per_item_culinaria/*, $arr_list_promo_per_item_dengan_voucher*/ );
			$diskon = $arr_item[$kode_produk]["qty"] > 0 ? $nilai_diskon_per_item["diskon"] : 0;
			$subtotal = ($arr_item[$kode_produk]["qty"] * $arr_item[$kode_produk]["harga"]) - $diskon;
			$total_culinaria += $subtotal;
			
			$_SESSION["culinaria_shopping_cart_diskon"][ $arr_item[$kode_produk]["productid"] ] = $diskon;
			$_SESSION["culinaria_shopping_cart_subtotal"][ $arr_item[$kode_produk]["productid"] ] = $subtotal;
			
			$parameter_sql_insert[] = "(
			                            '". main::formatting_query_string($data_order_id["culinaria_order_id"]) ."', '". main::formatting_query_string( $arr_item[$kode_produk]["productid"] ) ."',
			                            '". main::formatting_query_string($arr_item[$kode_produk]["qty"]) ."', '". main::formatting_query_string($arr_item[$kode_produk]["harga"]) ."',
			                            '". main::formatting_query_string($arr_item[$kode_produk]["diskon"]["diskon"]) ."', '". main::formatting_query_string( $arr_item[$kode_produk]["harga"] - $arr_item[$kode_produk]["diskon"]["diskon"] ) ."',
			                            '". main::formatting_query_string($is_tradein) ."', '". main::formatting_query_string( $arr_item[$kode_produk]["diskon"]["diskon"] > 0 ? "'". main::formatting_query_string($arr_item[$kode_produk]["diskon"]["status"]) ."'" : "" ) ."',
			                            NULL, '". main::formatting_query_string($arr_item[$kode_produk]["sku"]) ."',
			                            '". main::formatting_query_string($arr_item[$kode_produk]["nama"]) ."', '". main::formatting_query_string($arr_item[$kode_produk]["berat"]) ."'
			                            )";
			
			$arr_cart_culinaria[] = array(
				"kode_produk" => $arr_item[$kode_produk]["sku"],
				"produk" => $arr_item[$kode_produk]["nama"],
				"stok" => $stok - $quantity_booking_order_total[ $productid ],
				"qty" => $arr_item[$kode_produk]["qty"],
				"harga" => $arr_item[$kode_produk]["harga"],
				"subtotal" => $arr_item[$kode_produk]["qty"] * $arr_item[$kode_produk]["harga"],
				"diskon" => $diskon,
				"total" => $subtotal,
				"productid" => $arr_item[$kode_produk]["productid"]
			);
		}
		
			// insert baru di tabel culinaria_orderproduct
		$sql = "insert into culinaria_orderproduct(order_id, product_id, quantity, product_price, product_promo, product_pricepromo, tradein, note, gudang, sku, nama_produk, berat) values " . implode(",", $parameter_sql_insert);
		mysql_query($sql);
		
		// iterating free item
		/*if( is_array($server_output_diskon["item_free"]) &&  count($server_output_diskon["item_free"]) > 0 ){
			foreach( $server_output_diskon["item_free"] as $index => $item ){
				unset($arr_rpl);
				
			}
		}*/
		
		// diskon invoice dari kupon
		$discount_price_culinaria = 0; $discount_subtotal = $total_culinaria;
		$status_diskon_invoice_culinaria = "";
		if($_REQUEST["kode_promo_culinaria"]!="" /*&& !$is_discount_per_item && order_online_promo::is_ipc_all_item(@$_REQUEST["kode_promo"])*/ && count( $arr_list_promo_per_item_dengan_voucher ) <= 0){
			$discount_coupon_=main::culinaria_counting_discount($_REQUEST["kode_promo_culinaria"], $total_culinaria, $lang);
			$discount_coupon=$discount_coupon_[0];
			$discount_price_culinaria=$discount_coupon_[1];
			$discount_grandtotal=$discount_coupon_[2];	
			if($discount_coupon>0){
				//$s_basket.=$lang_discount.",-".$discount_price.".00,1,-".$discount_price.".00;";
				$status_diskon_invoice_culinaria = $discount_coupon_[3];
			}else
				unset($discount_coupon, $discount_price_culinaria, $discount_grandtotal);
		}

		$culinaria_grandtotal=(isset($discount_grandtotal)?$discount_grandtotal:$total_culinaria);
	}

} 

?>