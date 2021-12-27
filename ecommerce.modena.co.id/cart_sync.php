<?

include_once "../lib/var.php";
include_once "lib/cls_main.php"; // lib/cls_coupon_code_karyawan.php, 
include_once "lib/sql.php";
include_once "lib/cls_discount.php";
include_once "lib/cls_shoppingcart.php";
include_once "lib/cls_product.php";
include_once "lib/cls_culinaria_program.php"; // lib/sql.php
include_once "lib/cls_culinaria_cart.php";

// otorisasi key
$auth = sha1(__KEY__ . @$_REQUEST["rand"] . trim(sha1(@$_REQUEST["order_no"])) . @$_REQUEST["mode"] ) ;
//die($auth);
if( $auth != @$_REQUEST["auth"] ) die( $auth . " E1");

$arr_mode = array("checkout","paymentpending","paymentok","paymentnotok");
if( in_array( @$_REQUEST["mode"], $arr_mode) && file_exists("cart_sync_" . @$_REQUEST["mode"] . ".php" ) )
    include "cart_sync_" . @$_REQUEST["mode"] . ".php";

$json = json_encode($json);
header("Content-Type: application/json");
echo $json;

?>