<?

$order_status = 2;

include "cart_sync_checkout.php";

function kirim_email($url, $arr_par){
    $ch = curl_init();
    //echo "https://www.modena.com/ecommerce/" . $url . "?" . http_build_query($arr_par) . " -- ";
	curl_setopt($ch, CURLOPT_URL, "https://www.modena.com/ecommerce/" . $url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arr_par));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, __CURL_TIMEOUT); 
	curl_setopt($ch, CURLOPT_TIMEOUT, __CURL_TIMEOUT);
	curl_setopt($ch, CURLOPT_FAILONERROR, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_exec ($ch); 
    curl_close($ch);
}

// trap utk proses berikutnya, pastikan di pembayaran midtrans sudah berhasil (status code = 200)
$sql = "select 1 from modenado_modena_prod.transactions a inner join modenado_modena_prod.orders b on a.order_id = b.id where a.status_code = 200 and b.modena_order_no = '". main::formatting_query_string(@$_REQUEST["order_no"]) ."'";
//$rs_cek_status_code = mysql_query($sql);
//if( mysql_num_rows( $rs_cek_status_code ) <= 0 ) die("E3");

// mekanisme sinkron ke accpac
include_once "accpac_sync_modena_active.php";
include_once "culinaria_accpac_sync_modena.php";

$sql = "SELECT a1.order_no, case when b.order_status = 2 then 'modena' when c.order_status = 2 then 'culinaria' end jenis_order, b.custemail email_cust_modena, c.custemail email_cust_culinaria 
    FROM `group_ordercustomer` a inner join group_ordercustomer a1 on a.parent_order_no = a1.order_no or a.order_no = a1.order_no or a.order_no = a1.parent_order_no left join ordercustomer b on b.order_no = a1.order_no
    left join culinaria_ordercustomer c on c.order_no = a1.order_no
    where a.order_no = '". main::formatting_query_string(@$_REQUEST["order_no"]) ."'";
$rs_list_order = mysql_query($sql);

if( mysql_num_rows($rs_list_order) > 0 ){
    
    while( $list_order = mysql_fetch_array($rs_list_order) ){
        if( $list_order["jenis_order"] == "modena" ){
            $return = sync_accpac( $list_order["order_no"], false );    
            
            if( $list_order["email_cust_modena"] != "" ){
                unset($arr_par);
                $arr_par["custemail"] = $list_order["email_cust_modena"];
                $arr_par["order_no"] = $list_order["order_no"];
                if( $return !== false ) $arr_par["curl_return"] = $return;
                kirim_email("email_order.php", $arr_par);
                kirim_email("email_order_customer.php", $arr_par);

            }
            
        }elseif( $list_order["jenis_order"] == "culinaria" ){
            culinaria_sync_accpac( $list_order["order_no"], false );    
            
            if( $list_order["email_cust_culinaria"] != "" ){
                unset($arr_par);
                $arr_par["custemail"] = $list_order["email_cust_culinaria"];
                $arr_par["order_no"] = $list_order["order_no"];
                kirim_email("email_order_culinaria.php", $arr_par);     
                kirim_email("email_order_culinaria_customer.php", $arr_par);   
            }
            
        }
    }
    
}else die("E2");





?>