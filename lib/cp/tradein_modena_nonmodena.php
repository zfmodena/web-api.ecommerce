<?

function tradein_modena_nonmodena(){
	/* harus return variabel float, dibaca oleh function order_online_promo::check_ipc()	*/
	$arg_list = func_get_args();		
	$persentase_diskon = $arg_list[0];
	$arr_product = $arg_list[1]; 
	$arr_tradein_category = $arg_list[2]; 
	$arr_tradein_kuota = $arg_list[3]; 
	$arr_tradein_pemakaian_kuota = $arg_list[4];
	$diskon = 0;	
	if( !array_key_exists($arr_product["productid"] != "" ? $arr_product["productid"] : $arr_product["product_id"] , $arr_tradein_category) ) 
	    return 0;	
	foreach( $arr_tradein_category as $productid => $tradein_from_categoryid ){		
	    if( $arr_tradein_kuota[ $tradein_from_categoryid ] < $arr_tradein_pemakaian_kuota[ $tradein_from_categoryid ] )
	        return 0;
	}	
	$harga_produk = @$arr_product["price"] == "" ? $arr_product["product_pricepromo"] : $arr_product["price"];
	$harga_diskon_pricelist = ( $harga_produk / ( 1-$GLOBALS["diskon"] ) ) * ( 1 - $persentase_diskon);	
	$diskon =  $harga_produk - $harga_diskon_pricelist;	
	$pembulatan = 100;
	return ceil($diskon / $pembulatan) * $pembulatan;
}
?>
