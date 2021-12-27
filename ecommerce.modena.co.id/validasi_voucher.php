<?

include_once "../lib/var.php";
include_once "lib/cls_main.php";

if( !isset($_REQUEST["voucher"]) || $_REQUEST["voucher"] == "" ) die("E0");

$data = array(
        "status" => "invalid"
    );

$sql = "select *, case when expired_date < CURRENT_TIMESTAMP then 1 else 0 end expired_status from discount_coupon where enabled = 1 and coupon_code = '". main::formatting_query_string($_REQUEST["voucher"]) ."'";
$rs = mysql_query($sql);
if( mysql_num_rows($rs) > 0 ){
    $data["status"] = "valid";
    $voucher = mysql_fetch_array($rs);
    $data["detail"] = array(
            "expiration" => $voucher["expired_status"] == 1 ? "expired" : "active",
            "remark_id" => $voucher["remark_id"],
            "remark_en" => $voucher["remark_en"],
        );
}

header("Content-Type: application/json");
echo json_encode($data);


?>