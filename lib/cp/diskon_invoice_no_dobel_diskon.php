<?

function diskon_invoice_no_dobel_diskon(){
// harus return variabel float, dibaca oleh function order_online_promo::check_ipc()
	$diskon = 0;
	$arg_list = func_get_args();
	list($diskon_reguler) = explode(",", $arg_list[0]);
	
	foreach( $_SESSION["shopping_cart"] as $productid=>$qty ){
	    
		// tidak boleh dobel diskon_digunakan
		if( $_SESSION["shopping_cart_diskon"][$productid] > 0 ) continue;
				
		$diskon_digunakan = $diskon_reguler;
		if( in_array($productid, $_SESSION["tradein"]) )	$diskon_digunakan = $diskon_tradein;
		$diskon += $_SESSION["shopping_cart_subtotal"][$productid] * $diskon_digunakan;
		
	}
	$pembulatan = 100;
	return ceil($diskon / $pembulatan) * $pembulatan;
}
?>
