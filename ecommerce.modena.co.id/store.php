<?

include "db_active.v2.php";

$json = array();
$arr_tipe = array("MODEC" => "MODENA Experience Center", "MODHC" => "MODENA HOME CENTER", "PS" => "Premium Stores", "KS" => "Kitchen Specialist", "MCS" => "Modern Chain Stores");
foreach($arr_tipe as $kode => $value)
    $arr_string_tipetoko[] = " when '". main::formatting_query_string($kode) ."' then '". main::formatting_query_string($value) ."' ";

$sql = "select a.categories kode_tipetoko, case a.categories ". implode("", $arr_string_tipetoko) ." end tipetoko, a.name namatoko, a.address, concat(b.name, ' ' , c.name) kota,   a.longitude, a.latitude,a. no_handphone phone 
from office a inner join cities b on a.province_id = b.province_id and a.city_id = b.id 
    inner join province c on b.province_id = c.id ";
if( isset($_REQUEST["tipe"]) && trim($_REQUEST["tipe"]) != "" )
    $sql .= " where a.categories ='". main::formatting_query_string($_REQUEST["tipe"]) ."'";

if( isset($_REQUEST["kota"]) && trim($_REQUEST["kota"]) != "" )
    $sql .= " and b.name like '%". main::formatting_query_string($_REQUEST["kota"]) ."%' ";
if( isset($_REQUEST["toko"]) && trim($_REQUEST["toko"]) != "" )
    $sql .= " and a.name like '%". main::formatting_query_string($_REQUEST["toko"]) ."%' ";
        
$rs = mysql_query($sql);
while( $data= mysql_fetch_array($rs, MYSQL_ASSOC) ){
    $arr_address = explode("\r\n \r\n \r\n", strip_tags($data["address"]));
    unset($temp_address, $data["address"]);
    foreach($arr_address as $address){
        $address = trim(str_replace("\r\n", "", $address));
        if( $address != "" ) $temp_address[] = $address;
    }
    $data["alamat"] = implode("|",$temp_address);
    $json[] = $data;
}
    
header("Content-Type: application/json");
echo json_encode($json, JSON_INVALID_UTF8_IGNORE);

?>