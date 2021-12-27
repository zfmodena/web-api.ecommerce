<?

$json["status"] = "ok";

// cek stok dlu sebelum mulai, khusus utk mode checkout dan order barang modena
if ( @$_REQUEST["mode"] == "checkout" )
    $order_status = "-1";

$sql = "update `group_ordercustomer` a inner join group_ordercustomer a1 on a.parent_order_no = a1.order_no or a.order_no = a1.order_no or a.order_no = a1.parent_order_no 
    left join ordercustomer b on b.order_no = a1.order_no
    left join culinaria_ordercustomer c on c.order_no = a1.order_no
    set b.order_status = $order_status, c.order_status = $order_status, b.order_date = CURRENT_TIMESTAMP, c.order_date = CURRENT_TIMESTAMP
where a.order_no = '". main::formatting_query_string(@$_REQUEST["order_no"]) ."'";

mysql_query($sql);


SkipProcess:
    
?>