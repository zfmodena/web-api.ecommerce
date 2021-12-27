<?

function mixed_regular_tradein(){
// harus return variabel float, dibaca oleh function order_online_promo::check_ipc()
	$diskon = 0;
	$arg_list = func_get_args();
	list($diskon_reguler, $diskon_tradein) = explode(",", $arg_list[0]);
	
	foreach( $_SESSION["shopping_cart"] as $productid=>$qty ){
	    
	    $rs=order_online_promo::isnt_ipc_per_item($productid);
		if(mysql_num_rows($rs)>0){	
			$d=mysql_fetch_array($rs);
			if($d["promocode_safe"]!="1" )	continue;
		}else{
			if(mysql_num_rows(order_online_promo::discount_product_source($productid))>0) continue;
		}
		
		$diskon_digunakan = $diskon_reguler;
		if( in_array($productid, $_SESSION["tradein"]) )	$diskon_digunakan = $diskon_tradein;
		$diskon += $_SESSION["shopping_cart_subtotal"][$productid] * $diskon_digunakan;
		
	}
	$pembulatan = 100;
	return ceil($diskon / $pembulatan) * $pembulatan;
}
?>
