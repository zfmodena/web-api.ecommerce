<?
unset($sql_parameter);

foreach( $_REQUEST["item"] as $sku )
    $sql_parameter[] = "'". main::formatting_query_string( $sku ) ."'";

if(!isset($sql_parameter) || count($sql_parameter) <=0 ) goto Skip_all;

// Deteksi stok website terpakai, hanya utk order yg tipenya menunggu pembayaran (status -1)

$sql = "
	select sum(c.kuantitas_order_item_sedang_proses) kuantitas_order_item_sedang_proses, c.sku, c.gudang from 
	ordercustomer a, 
		(select sum(quantity) kuantitas_order_item_sedang_proses, order_id, sku, gudang from orderproduct where sku is not null group by order_id, sku, gudang) c 
	where 
	a.order_id = c.order_id and
	TIMESTAMPDIFF(MINUTE, a.order_date, CURRENT_TIMESTAMP) < ". $GLOBALS["payment_va_expiration"] ."+5  and 
	order_status < 0 and c.sku in (". implode( ",", $sql_parameter ) .")
	". @$sqladd ." group by c.sku, c.gudang"; 

$quantity_booking_order_total = array();	
$rs = mysql_query($sql);
if( mysql_num_rows($rs) > 0 ){
	
	while( $data = mysql_fetch_array($rs) )
		$quantity_booking_order_total[ trim($data["sku"]) ][ trim($data["gudang"]) ] = $data["kuantitas_order_item_sedang_proses"];

}

echo json_encode( $quantity_booking_order_total );

Skip_all:
    
?>