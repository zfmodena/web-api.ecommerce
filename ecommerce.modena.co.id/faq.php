<?

include "faq.v2.php";exit;

include "db_active.php";

$sql = "select a.title topik_id, a.title topik_en/*,a.description keterangan_topik*/ from faq a ";
$rs_topik = mysql_query($sql);

$sql = "select a.title topik_id, a.title topik_en, b.title tanya_id, b.title tanya_en, b.description jawab_id, b.description jawab_en from faq a inner join culinaria_introduction b on a.title = b.category_page where b.title is not null";
$rs_qa = mysql_query($sql);

$json = array();
$counter = 0;
while( $data_topik = mysql_fetch_array($rs_topik, MYSQL_ASSOC) ){
    //$data_topik["keterangan_topik"] = iconv('UTF-8', 'UTF-8//IGNORE', $data_topik["keterangan_topik"]);
    $json["data_topik"]["en"][$counter] = $data_topik["topik_en"];
    $json["data_topik"]["id"][$counter] = $data_topik["topik_id"];
    $counter++;
}

$counter_id = $counter_en = 0;
$temp_topik_en = $temp_topik_id = "";
while( $data_qa = mysql_fetch_array($rs_qa, MYSQL_ASSOC) ){
    $qa_id["tanya"] = $data_qa["tanya_id"];
    $qa_id["jawab"] = iconv('UTF-8', 'UTF-8//IGNORE', $data_qa["jawab_id"]);
    $qa_en["tanya"] = $data_qa["tanya_en"];
    $qa_en["jawab"] = iconv('UTF-8', 'UTF-8//IGNORE', $data_qa["jawab_en"]);
    if( $temp_topik_en != $data_qa["topik_en"] ) {
        $counter_en = 0;
        $temp_topik_en = $data_qa["topik_en"];
    }
    if( $temp_topik_id != $data_qa["topik_id"] ) {
        $counter_id = 0;
        $temp_topik_id = $data_qa["topik_id"];
    }
    $json["tanya_jawab"]["en"][ $data_qa["topik_en"] ][ $counter_en ] = $qa_en;
    $json["tanya_jawab"]["id"][ $data_qa["topik_id"] ][ $counter_id ] = $qa_id;
    $counter_id++;
    $counter_en++;
}

header("Content-Type: application/json");
echo json_encode($json);


?>