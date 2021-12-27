<?

$arr_tradein_category = $arr_tradein_kuota = $arr_tradein_pemakaian_kuota = array();

// cek apakah ada data session tradein / konsumen pilih opsi tradein
if( count( @$_SESSION["tradein"] ) <= 0 ) goto Skip_tradein;

// cek categoryid dari semua item tradein di shopping_cart dan kuota tradein
$sql = "select d.productid, d.categoryid, c.tradein_from_categoryid, b.tradein_kuantitas from tradein_konsumen a 
	inner join tradein_produk b on a.tradein_regid = b.tradein_regid 
	inner join tradein_matrix c on b.tradein_categoryid = c.tradein_from_categoryid
	inner join product d on c.tradein_to_categoryid = d.categoryid
	where a.tradein_coupon_code = '". main::formatting_query_string($_REQUEST["kode_promo"]) ."' and d.productid in (". implode(",", $_SESSION["tradein"]) .") and b.tradein_alasanproduk = 0";
$rs_tradein_category = mysql_query($sql);
if( mysql_num_rows($rs_tradein_category) <= 0 ) goto Skip_tradein; // belum diverifikasi
while( $tradein_category =  mysql_fetch_array($rs_tradein_category) ){
	$arr_tradein_kuota[ $tradein_category["tradein_from_categoryid"] ] = $tradein_category["tradein_kuantitas"];
	$arr_tradein_category[ $tradein_category["productid"] ] = $tradein_category["tradein_from_categoryid"];
	$sql_union[] = "select ". $tradein_category["tradein_from_categoryid"] .", ". $tradein_category["productid"] .", ". $tradein_category["categoryid"] .", ". $_SESSION["shopping_cart"][ $tradein_category["productid"] ] ;
}


// cek kuantitas produknya (kuota terpakai) dari semua item tradein di 
// CURRENT shopping_cart 
// SUBMITTED shopping_cart (baik yg sudah berhasil dibayar atau expired pembayaran) yang menggunakan kode voucher tsb
$sql = "
	select sum(quantity) total_quantity, tradein_from_categoryid from(
		select d.tradein_from_categoryid, c.productid, c.categoryid tradein_to_categoryid, b.quantity from ordercustomer a inner join orderproduct b on a.order_id = b.order_id 
		inner join product c on b.product_id = c.productid inner join tradein_matrix d on c.categoryid = d.tradein_to_categoryid
		where left(a.coupon_code, length('". main::formatting_query_string( $_REQUEST["kode_promo"] ) ."') ) = '". main::formatting_query_string( $_REQUEST["kode_promo"] ) ."' 
			and b.tradein != 0 and a.order_status != 0
		union
		select d.tradein_from_categoryid, c.productid, c.categoryid tradein_to_categoryid, b.quantity from ordercustomer_log a inner join orderproduct_log b on a.order_id = b.order_id 
		inner join product c on b.product_id = c.productid inner join tradein_matrix d on c.categoryid = d.tradein_to_categoryid
		where left(a.coupon_code, length('". main::formatting_query_string( $_REQUEST["kode_promo"] ) ."') ) = '". main::formatting_query_string( $_REQUEST["kode_promo"] ) ."' 
			and b.tradein != 0 and a.order_status != 0
		union 
		" . implode(" union ", $sql_union) 
		. ") x group by tradein_from_categoryid";
$rs_tradein_category = mysql_query($sql);
while( $tradein_category =  mysql_fetch_array($rs_tradein_category) )
	$arr_tradein_pemakaian_kuota[ $tradein_category["tradein_from_categoryid"] ] = $tradein_category["total_quantity"];

Skip_tradein:
?>