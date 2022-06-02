<?

function kuota_all_item_pricelist_disc(){
	// harus return variabel float, dibaca oleh function order_online_promo::check_ipc()	
	$diskon = 0;	
	$arg_list = func_get_args();		
	list( $persentase_diskon, $kuota_all_item_pricelist_disc, $list_productid /*dipisahkan dengan &*/ ) = explode(",", $arg_list[0]);		
	// cek pembelanjaan sesuai kuota all item, di cart masa lalu
	$sql = "select sum(b.quantity) quantity from ordercustomer a 				inner join orderproduct b on a.order_id = b.order_id 				
	inner join discount_coupon c on concat(left(a.coupon_code, length(trim(c.coupon_code)) ),'-') = concat(c.coupon_code, '-')
	where a.coupon_code = '". main::formatting_query_string($_REQUEST["t_discount_coupon"]) ."' 			and b.product_id in ( ". str_replace("&", ",", $list_productid) ." ) 			
	and (a.order_status>'0' or ( a.order_status <0 and date_add(a.order_date, interval ".$GLOBALS["payment_va_expiration"]." minute) >= CURRENT_TIMESTAMP ) ) 
	and a.order_no != '". main::formatting_query_string($_SESSION["order_no"]) ."'; ";	
	$jumlah_item = 0;	
	$rs_jumlah_item = mysql_query($sql);	
	if( mysql_num_rows( $rs_jumlah_item ) > 0 ){
	    $data_jumlah_item = mysql_fetch_array($rs_jumlah_item);		
	    $jumlah_item = $data_jumlah_item["quantity"];	
   
	}
	// cek pembelanjaan di cart saat ini
	foreach($_SESSION["shopping_cart"] as $product => $qty){	
	    if( in_array($product , explode("&", $list_productid) ) ){			
	        $jumlah_item += $_SESSION["shopping_cart"][ $product ];			
	    }	
	}
	if( $jumlah_item <= $kuota_all_item_pricelist_disc )		
	    $diskon = $persentase_diskon;	
	$nilai_diskon = 0;	
	foreach($_SESSION["shopping_cart"] as $product => $qty){	
	    if( in_array($product , explode("&", $list_productid) ) ){			
	        $nilai_diskon += $_SESSION["shopping_cart_subtotal"][ $product ] * $diskon;			
	    //echo $_SESSION["shopping_cart_subtotal"][ $product ] . " x " . $diskon;		
	    }	
	}	
	//$pembulatan = 100;
	//return ceil($diskon / $pembulatan) * $pembulatan;	
	return $nilai_diskon;
}
?>
