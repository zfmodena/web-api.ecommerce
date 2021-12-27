<?

include_once "../lib/var.php";
include_once "lib/cls_main.php"; // lib/cls_coupon_code_karyawan.php, 
include_once "lib/cls_discount.php";
include_once "lib/cls_shoppingcart.php";
include_once "lib/cls_product.php";
include_once "lib/cls_culinaria_program.php"; // lib/sql.php
include_once "lib/cls_culinaria_cart.php";

// otorisasi key
$auth = sha1(__KEY__ . sha1(trim(@$_REQUEST["rand"]) . __KEY__) . trim(@$_REQUEST["order_no"]) ) ;
//die($auth);
if( $auth != @$_REQUEST["auth"] ) die("E1");

// header
$sql = "SELECT c.*, case when c.mode = 'culinaria' then e.total else d.total end subtotal, 
case when c.mode = 'culinaria' then e.total-ifnull(c.diskon,0) else d.total-ifnull(c.diskon,0) end total FROM `group_ordercustomer` a 
inner join group_ordercustomer a1 on a.parent_order_no = a1.order_no or a.order_no = a1.order_no or a.order_no = a1.parent_order_no
inner join (select order_id,
order_date,
order_no,
order_status,
coupon_code kode_promo,
coupon_discount diskon,
/*memberid,*/
custname,
custemail,
receiver_name_for_shipping,
shipping_address,
shipping_address_city,
shipping_address_state,
shipping_address_postcode,
shipping_phone_no,
shipping_handphone_no,
shippingcost ongkos_kirim,
'modena' mode
from ordercustomer union select order_id,
order_date,
order_no,
order_status,
coupon_code kode_promo,
coupon_discount diskon,
/*memberid,*/
custname,
custemail,
concat(billing_first_name, ' ', billing_last_name),
billing_address,
billing_address_city,
billing_address_state,
billing_address_postcode,
billing_phone_no,
billing_handphone_no,
0,
'culinaria'
from culinaria_ordercustomer) c on a1.order_no = c.order_no 
left join (select sum(product_price * quantity) subtotal, sum(product_pricepromo * quantity) total, order_id from orderproduct group by order_id) d on c.order_id = d.order_id
left join (select sum(product_price * quantity) subtotal, sum(product_pricepromo * quantity) total, order_id from culinaria_orderproduct group by order_id) e on c.order_id = e.order_id
where a.order_no = '". main::formatting_query_string($_REQUEST["order_no"]) ."'";

 $rs_header = mysql_query($sql);
 $grand_total = $ongkos_kirim = 0;
 while( $data_header = mysql_fetch_array($rs_header,MYSQL_ASSOC) ){
    $mode = $data_header["mode"];
    $order_id = $data_header["order_id"];
    $grand_total += $data_header["total"];
    $ongkos_kirim += $data_header["ongkos_kirim"];
    unset($data_header["order_id"], $data_header["mode"]);
    $json[ $mode ] = $data_header;
    
    $string_awalan = "";
    if( $mode == "culinaria" )    $string_awalan = "culinaria_";
    $sql = "
        select  
            sku kode_produk,
            nama_produk produk,
            gudang,
            shipment_delay,
            quantity qty,
            product_price harga,
            quantity * product_price subtotal,
            quantity * product_promo diskon,
            quantity * product_pricepromo total,
            product_id productid
        from ". $string_awalan ."orderproduct where order_id = '". main::formatting_query_string($order_id) ."'";
    $rs_detail = mysql_query($sql);
    while( $data_detail = mysql_fetch_array($rs_detail,MYSQL_ASSOC) )
        $json[ $mode ]["detail"][] = $data_detail;

 }

$json["grand_total"] = $grand_total + $ongkos_kirim;

$json = json_encode($json);
header("Content-Type: application/json");
echo $json;

?>