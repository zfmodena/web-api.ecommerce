<?
/*
cek pemakaian kode promo utk order :: 
yg belum dibayar + masih pending payment (terbatas waktu)  : ordercustomer.order_status in (0 dan -1)
order yg sudah dibayar (ordercustomer.order_status = 2)  
*/
$sql = "select 0 status from ordercustomer where order_id != '". main::formatting_query_string( $_REQUEST["order_no"] ) ."' 
	and coupon_code = '". main::formatting_query_string($_REQUEST["kode_voucher"]) ."' and 
	( 
	        (TIMESTAMPDIFF(MINUTE, order_date, CURRENT_TIMESTAMP) < ". $GLOBALS["payment_va_expiration"] ."+5 and order_status <= 0 ) or 
	        order_status > 0
	)";
$data = mysql_fetch_array(mysql_query($sql));

echo json_encode( array("status" => is_null(@$data["status"]) ? 1 : @$data["status"]) );

?>