<?

$arr_data = array("customer" => array(), "customer_product" => array());

if( sha1("modena328_ptim*328" . trim($_REQUEST["rand"])  ) != $_REQUEST["key"] ) die( json_encode($arr_data) );

$limit = 3000;

// customer
$sql = "select a.memberid konsumen_id, a.name nama_konsumen, a.email, 
	left(a.address, 8000) alamat, case when ifnull(c.state, '') = '' then a.homecity else c.state end propinsi, case when ifnull(d.region, '') = '' then a.homecity else d.region end kota, a.homepostcode kode_pos, 
	a.Phone telepon, a.HandPhone hp, case when a.memberid like 'CUL%' then 'CULINARIA' else 'ONLINE' end jenis_konsumen_online
from membersdata a 
left outer join shipment_state c on a.homestate = c.state_id
left outer join shipment_exception d on c.state_id = d.state_id and a.HomeRegion = d.region_id where a.memberid > ". $_REQUEST["max"] ." limit $limit";
$rs = mysql_query($sql);
while( $data = mysql_fetch_array($rs) )
	$arr_data["customer"][] = $data;

// customer product
/*$sql = "select a.memberid konsumen_id, a.order_id, c.kode product_id, a.order_date tanggal_pembelian 
from ordercustomer a inner join orderproduct b on a.order_id = b.order_id inner join __kode_produk_accpac c on b.product_id = c.productid where a.order_status > 0 and a.order_id > ". $_REQUEST["max_product"] ." limit $limit";
$rs = mysql_query($sql);
while( $data = mysql_fetch_array($rs) )
	$arr_data["customer_product"][] = $data;
*/
echo json_encode( $arr_data );

?>