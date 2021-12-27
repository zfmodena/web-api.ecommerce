<?

include_once "../lib/var.php";
include_once "lib/cls_main.php"; // lib/cls_coupon_code_karyawan.php, 
include_once "lib/sql.php";
include_once "lib/cls_discount.php";
include_once "lib/cls_shoppingcart.php";
include_once "lib/cls_product.php";
include_once "lib/cls_culinaria_program.php"; // lib/sql.php
include_once "lib/cls_culinaria_cart.php";


include_once "accpac_sync_modena_active.php";
sync_accpac($_REQUEST["orderno"], false);

?>