<?

include "../lib/var.php";
include "../lib/cls_main.php";

$sql = "SELECT `date` tanggal, remark keterangan FROM `shipment_date_off` where year(date) = 2021 and enabled = 1 order by `date`";

$rs = mysql_query($sql);
while( $data= mysql_fetch_array($rs, MYSQL_ASSOC) )
    $json[] = $data;

    
header("Content-Type: application/json");
echo json_encode($json);

?>