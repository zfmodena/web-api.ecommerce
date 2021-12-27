<?

include "db_active.v2.php";

$sql = "select id, name from category_faq a ";
$rs_topik = mysql_query($sql);

$sql = "select a.name, b.title, b.description from category_faq a inner join faq b on a.id = b.category_faq_id order by a.id";
$rs_qa = mysql_query($sql);

$json = array();
$counter = 0;
while( $data_topik = mysql_fetch_array($rs_topik, MYSQL_ASSOC) ){
    $kategori_faq = json_decode( iconv('UTF-8', 'UTF-8//IGNORE', $data_topik["name"]) );
    $json["data_topik"]["en"][$counter] = $kategori_faq->en;
    $json["data_topik"]["id"][$counter] = $kategori_faq->id;
    $counter++;
}

$counter_id = $counter_en = 0;
$temp_topik_en = $temp_topik_id = "";
while( $data_qa = mysql_fetch_array($rs_qa, MYSQL_ASSOC) ){
    $kategori_faq = json_decode( iconv('UTF-8', 'UTF-8//IGNORE', $data_qa["name"]) );
    $tanya = json_decode( iconv('UTF-8', 'UTF-8//IGNORE', $data_qa["title"]) );
    $jawab = json_decode( iconv('UTF-8', 'UTF-8//IGNORE', $data_qa["description"]) );
    $qa_id["tanya"] = $tanya->id;
    $qa_id["jawab"] = strip_tags(str_replace(array("</li>","<br>","<br />","<br/>","</p>"), "\n", $jawab->id));
    $qa_en["tanya"] = $tanya->en;
    $qa_en["jawab"] = strip_tags(str_replace(array("</li>","<br>","<br />","<br/>","</p>"), "\n", $jawab->en));
    if( $temp_topik_en != $kategori_faq->en ) {
        $counter_en = 0;
        $temp_topik_en = $kategori_faq->en;
    }
    if( $temp_topik_id != $kategori_faq->id ) {
        $counter_id = 0;
        $temp_topik_id = $kategori_faq->id;
    }
    $json["tanya_jawab"]["en"][ $kategori_faq->en ][ $counter_en ] = $qa_en;
    $json["tanya_jawab"]["id"][ $kategori_faq->id ][ $counter_id ] = $qa_id;
    $counter_id++;
    $counter_en++;
}

header("Content-Type: application/json");
echo json_encode($json);


?>